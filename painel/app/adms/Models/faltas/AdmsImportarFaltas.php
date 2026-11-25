<?php

namespace App\adms\Models\faltas;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\importacao\AdmsImportacaoCsv as Csv;

/**
 * IMPORTAR FALTAS por planilha `rm;faltas` (E8, 1.14.0, D-411): o mesmo padrão da importação de notas.
 * - Só na oferta ABERTA e com a fonte "lançamento de faltas" (na oferta com chamada, as faltas vêm da chamada: recusa
 *   com a razão).
 * - As mesmas regras da tela de Faltas: inteiro de 0 a 999, não acima do total de encontros, não abaixo das faltas já
 *   compensadas. Conferido inteiro antes de gravar; gravado numa transação (tudo ou nada).
 */
class AdmsImportarFaltas
{
    private array $erros = [];
    private int $gravados = 0;

    public function getErros(): array
    {
        return $this->erros;
    }

    public function getGravados(): int
    {
        return $this->gravados;
    }

    public function importar(int $ofertaId, ?array $arquivo, int $usuario): bool
    {
        $this->erros = [];
        $o = \App\adms\Models\notas\AdmsCalculoNotas::dadosOferta($ofertaId);
        if (!$o) {
            $this->erros[] = 'Disciplina não encontrada.';
            return false;
        }
        if (!empty($o['encerrada_em'])) {
            $this->erros[] = 'A oferta está ENCERRADA: não aceita importação. O administrador reabre, com justificativa.';
            return false;
        }
        if (($o['frequencia_fonte'] ?? 'lancamento') === 'chamada') {
            $this->erros[] = 'As faltas desta oferta vêm da CHAMADA: a importação de faltas é recusada (uma fonte só por oferta, sem contar a falta duas vezes).';
            return false;
        }
        $lido = Csv::ler($arquivo, 'faltas');
        if ($lido['erros']) {
            $this->erros = $lido['erros'];
            return false;
        }
        $encontros = $o['encontros'] !== null ? (int) $o['encontros'] : null;
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT id, rm, faltas, faltas_compensadas FROM rm_disciplina_faltas WHERE rm_prof_mat_id = :o", "o={$ofertaId}");
        $atuais = [];
        foreach ($ler->getResultado() ?: [] as $l) {
            $atuais[(string) $l['rm']] = $l;
        }
        [$validas, $erros] = Csv::conferir($ofertaId, $lido['linhas'], 'faltas', function (string $v, string $rm) use ($encontros, $atuais) {
            if (!preg_match('/^\d{1,3}$/', $v)) {
                return "faltas \"" . mb_substr($v, 0, 20) . "\" em formato inválido (um número inteiro, 0 ou mais)";
            }
            if ($encontros !== null && (int) $v > $encontros) {
                return "{$v} falta(s) é mais que o total de {$encontros} encontros";
            }
            $comp = (int) ($atuais[$rm]['faltas_compensadas'] ?? 0);
            if ((int) $v < $comp) {
                return "{$v} falta(s) é menos que as {$comp} já compensadas";
            }
            return null;
        });
        if (!$erros && !$validas) {
            $erros[] = 'Nenhuma falta no arquivo (as linhas com o valor vazio são puladas).';
        }
        if ($erros) {
            $this->erros = $erros;
            return false;
        }
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT rm, nome_aluno FROM portal_disciplinas_alunos WHERE rm_prof_mat_id = :o AND situacao = :s", "o={$ofertaId}&s=1");
        $nomes = array_column($ler->getResultado() ?: [], 'nome_aluno', 'rm');
        $T = \Core\Transacao::class;
        $ok = \App\adms\Models\helper\AdmsAuditoria::comOrigem(\App\adms\Models\helper\AdmsAuditoria::IMPORTACAO, fn() => $T::executar(function () use ($T, $validas, $atuais, $nomes, $ofertaId, $usuario) {
            foreach ($validas as $rm => $f) {
                \App\adms\Models\helper\AdmsAuditoria::falta($ofertaId, (string) $rm, $nomes[$rm] ?? null, isset($atuais[$rm]) ? (int) $atuais[$rm]['faltas'] : null, (int) $f); // Onda 2, Etapa 3
                if (isset($atuais[$rm])) {
                    $u = new \App\adms\Models\helper\AdmsUpdate();
                    $u->exeUpdate('rm_disciplina_faltas', ['faltas' => (int) $f, 'usuario_mod' => $usuario], 'WHERE id = :id', "id={$atuais[$rm]['id']}");
                    $T::exigir($u->getResultado() === null ? null : true, 'faltas do RA ' . $rm);
                } else {
                    $c = new \App\adms\Models\helper\AdmsCreate();
                    $c->exeCreate('rm_disciplina_faltas', ['rm' => (string) $rm, 'nome_aluno' => $nomes[$rm] ?? '', 'rm_prof_mat_id' => $ofertaId, 'faltas' => (int) $f,
                        'faltas_compensadas' => 0, 'usuario_cre' => $usuario]);
                    $T::exigir($c->getResultado(), 'faltas do RA ' . $rm);
                }
            }
            return true;
        }));
        if (!$ok) {
            $this->erros[] = 'A gravação foi desfeita: ' . htmlspecialchars($T::ultimoErro(), ENT_QUOTES, 'UTF-8') . '.';
            return false;
        }
        $this->gravados = count($validas);
        (new \App\adms\Models\helper\AdmsSalvarLog())->salvarLogs(425, 200, 3, "Faltas importadas: oferta={$ofertaId}; alunos={$this->gravados}");
        return true;
    }
}
