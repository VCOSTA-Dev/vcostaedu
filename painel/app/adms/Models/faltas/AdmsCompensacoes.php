<?php

namespace App\adms\Models\faltas;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * COMPENSAÇÃO DE FALTAS (E8c, D-264 e D-265).
 *
 * - Só a SECRETARIA (nível 4) e o ADMINISTRADOR (nível 1) lançam ou removem; o professor vê só o número.
 * - Cada lançamento exige TIPO (Atestado médico, Licença, Convocação de serviço, Outro), JUSTIFICATIVA (texto
 *   livre), PERÍODO (início e fim) e grava quem lançou e quando. Sem anexo nesta versão (dado de saúde, LGPD).
 * - Oferta ENCERRADA não aceita compensação nova nem remoção.
 * - O total compensado do aluno na oferta não passa das faltas lançadas.
 * - rm_disciplina_faltas.faltas_compensadas = soma dos lançamentos (é o que o motor de cálculo lê).
 * - O aluno vê só a quantidade no boletim; o motivo nunca sai no portal.
 */
class AdmsCompensacoes
{
    public const TIPOS = ['atestado' => 'Atestado médico', 'licenca' => 'Licença', 'convocacao' => 'Convocação de serviço', 'outro' => 'Outro'];
    public const NIVEIS = [1, 2, 4];

    private array $erros = [];

    public function getErros(): array
    {
        return $this->erros;
    }

    public static function podeLancar(): bool
    {
        /* Onda 2, Etapa 2: pelo papel (os antigos 1, 2 e 4: administrador, secretaria; e a supervisão da secretaria) */
        if (\App\adms\Models\helper\AdmsNivel::ehPapel('administrador', 'supervisao_secretaria', 'secretaria')) {
            return true;
        }
        /* D-130 (1.103.0, C7): o professor, quando a instituição liga a ação "Lançar compensação de ausência" para o perfil
           dele em Perfis de acesso (desligada de fábrica); só nas disciplinas dele (podeNaOferta) */
        return self::professorLiberado();
    }

    /** O professor com a permissão da página de compensar ligada no perfil dele. */
    private static function professorLiberado(): bool
    {
        if (!\App\adms\Models\helper\AdmsNivel::ehPapel('professor')) {
            return false;
        }
        $p = (new \App\adms\Models\AdmsBotao())->valBotao(['c' => ['menu_controller' => 'faltas', 'menu_metodo' => 'compensar']]);
        return !empty($p['c']);
    }

    /** Na oferta: a secretaria e o administrador em todas; o professor liberado, só nas ofertas dele (ligado, para gravar). */
    public static function podeNaOferta(int $ofertaId): bool
    {
        if (!self::podeLancar()) {
            return false;
        }
        return !self::professorLiberado() || \App\adms\Models\helper\AdmsEscopo::podeOferta($ofertaId, true);
    }

    /** Lançamentos da oferta, do mais novo para o mais antigo, com o nome de quem lançou. */
    public static function listar(int $ofertaId): array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT c.id, c.rm, c.quantidade, c.tipo, c.justificativa, c.inicio, c.fim, c.created, u.nome AS usuario, a.nome_aluno AS aluno
               FROM rm_faltas_compensacoes c
               LEFT JOIN adms_usuarios u ON u.id = c.usuario_id
               LEFT JOIN portal_disciplinas_alunos a ON a.rm = c.rm AND a.rm_prof_mat_id = c.rm_prof_mat_id
              WHERE c.rm_prof_mat_id = :o ORDER BY c.created DESC, c.id DESC",
            "o={$ofertaId}"
        );
        return $ler->getResultado() ?: [];
    }

    public function lancar(int $ofertaId, array $d): bool
    {
        $this->erros = [];
        if (!self::podeLancar()) {
            return $this->recusa('Só a secretaria e o administrador lançam compensação de faltas.');
        }
        if (!self::podeNaOferta($ofertaId)) {
            return $this->recusa('Esta disciplina não é sua: o professor lança compensação só nas disciplinas em que dá aula.');
        }
        $oferta = \App\adms\Models\notas\AdmsCalculoNotas::dadosOferta($ofertaId);
        if (!$oferta) {
            return $this->recusa('Disciplina não encontrada.');
        }
        if (!empty($oferta['encerrada_em'])) {
            return $this->recusa('A disciplina está ENCERRADA: não aceita compensação nova. Só o administrador reabre.');
        }
        $rm = trim((string) ($d['rm'] ?? ''));
        $qtd = trim((string) ($d['quantidade'] ?? ''));
        $tipo = (string) ($d['tipo'] ?? '');
        $just = trim((string) ($d['justificativa'] ?? ''));
        $ini = (string) ($d['inicio'] ?? '');
        $fim = (string) ($d['fim'] ?? '');
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT rm FROM portal_disciplinas_alunos WHERE rm_prof_mat_id = :o AND situacao = :s AND rm = :rm LIMIT :limit", "o={$ofertaId}&s=1&rm={$rm}&limit=1");
        if ($rm === '' || !$ler->getResultado()) {
            $this->erros[] = 'Escolha um aluno enturmado nesta disciplina.';
        }
        if (!preg_match('/^\d{1,3}$/', $qtd) || (int) $qtd < 1) {
            $this->erros[] = 'Informe a quantidade de faltas compensadas (1 ou mais).';
        }
        if (!isset(self::TIPOS[$tipo])) {
            $this->erros[] = 'Escolha o tipo da justificativa (Atestado médico, Licença, Convocação de serviço ou Outro).';
        }
        if (mb_strlen($just) < 5) {
            $this->erros[] = 'A justificativa é obrigatória (ao menos 5 caracteres).';
        }
        $okIni = \DateTime::createFromFormat('Y-m-d', $ini) && \DateTime::createFromFormat('Y-m-d', $ini)->format('Y-m-d') === $ini;
        $okFim = \DateTime::createFromFormat('Y-m-d', $fim) && \DateTime::createFromFormat('Y-m-d', $fim)->format('Y-m-d') === $fim;
        if (!$okIni || !$okFim) {
            $this->erros[] = 'Informe o período (data de início e de fim).';
        } elseif ($fim < $ini) {
            $this->erros[] = 'O fim do período não pode ser antes do início.';
        }
        if (!$this->erros) {
            [$faltas, $comp] = $this->totais($ofertaId, $rm);
            if ($comp + (int) $qtd > $faltas) {
                $this->erros[] = "O aluno tem {$faltas} falta(s) e {$comp} já compensada(s): dá para compensar no máximo " . max(0, $faltas - $comp) . '.';
            }
        }
        if ($this->erros) {
            return false;
        }
        /* 1.14.0 (E9, D-414): o lançamento e o total do aluno numa TRANSAÇÃO */
        $T = \Core\Transacao::class;
        $ok = $T::executar(function () use ($T, $ofertaId, $rm, $qtd, $tipo, $just, $ini, $fim) {
            $c = new \App\adms\Models\helper\AdmsCreate();
            $c->exeCreate('rm_faltas_compensacoes', [
                'rm_prof_mat_id' => $ofertaId, 'rm' => $rm, 'quantidade' => (int) $qtd, 'tipo' => $tipo, 'justificativa' => $just,
                'inicio' => $ini, 'fim' => $fim, 'usuario_id' => (int) $_SESSION['colUsuario_id'], 'created' => date('Y-m-d H:i:s'),
            ]);
            $T::exigir($c->getResultado(), 'compensação');
            $this->recalcular($ofertaId, $rm);
            return true;
        });
        if (!$ok) {
            return $this->recusa('Nada foi gravado (' . $T::ultimoErro() . ').');
        }
        (new \App\adms\Models\helper\AdmsSalvarLog())->salvarLogs(410, 200, 3, "Compensação de faltas: oferta={$ofertaId}; RA={$rm}; quantidade={$qtd}; tipo={$tipo}");
        return true;
    }

    public function remover(int $ofertaId, int $id): bool
    {
        $this->erros = [];
        if (!self::podeLancar()) {
            return $this->recusa('Só a secretaria e o administrador removem compensação de faltas.');
        }
        if (!self::podeNaOferta($ofertaId)) {
            return $this->recusa('Esta disciplina não é sua: o professor lança compensação só nas disciplinas em que dá aula.');
        }
        $oferta = \App\adms\Models\notas\AdmsCalculoNotas::dadosOferta($ofertaId);
        if (!$oferta || !empty($oferta['encerrada_em'])) {
            return $this->recusa('A disciplina está ENCERRADA (ou não existe): as compensações não mudam.');
        }
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT id, rm, quantidade FROM rm_faltas_compensacoes WHERE id = :id AND rm_prof_mat_id = :o LIMIT :limit", "id={$id}&o={$ofertaId}&limit=1");
        $c = $ler->getResultado()[0] ?? null;
        if (!$c) {
            return $this->recusa('Lançamento de compensação não encontrado.');
        }
        $T = \Core\Transacao::class;
        $ok = $T::executar(function () use ($T, $ofertaId, $id, $c) {
            $d = new \App\adms\Models\helper\AdmsDeletar();
            $d->exeDelete('rm_faltas_compensacoes', 'WHERE id = :id', "id={$id}");
            $T::exigir($d->getResultado() ? true : null, 'compensação');
            $this->recalcular($ofertaId, (string) $c['rm']);
            return true;
        });
        if (!$ok) {
            return $this->recusa('Nada foi removido (' . $T::ultimoErro() . ').');
        }
        (new \App\adms\Models\helper\AdmsSalvarLog())->salvarLogs(411, 200, 3, "Compensação removida: oferta={$ofertaId}; RA={$c['rm']}; quantidade={$c['quantidade']}");
        return true;
    }

    /** [faltas lançadas, compensadas hoje] do aluno na oferta. */
    private function totais(int $ofertaId, string $rm): array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT faltas FROM rm_disciplina_faltas WHERE rm = :rm AND rm_prof_mat_id = :o LIMIT :limit", "rm={$rm}&o={$ofertaId}&limit=1");
        $faltas = (int) ($ler->getResultado()[0]['faltas'] ?? 0);
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT COALESCE(SUM(quantidade), 0) AS s FROM rm_faltas_compensacoes WHERE rm = :rm AND rm_prof_mat_id = :o", "rm={$rm}&o={$ofertaId}");
        return [$faltas, (int) ($ler->getResultado()[0]['s'] ?? 0)];
    }

    /** faltas_compensadas = soma dos lançamentos (cria a linha de faltas se ainda não existe). */
    private function recalcular(int $ofertaId, string $rm): void
    {
        [, $soma] = $this->totais($ofertaId, $rm);
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT id, nome_aluno, faltas_compensadas FROM rm_disciplina_faltas WHERE rm = :rm AND rm_prof_mat_id = :o LIMIT :limit", "rm={$rm}&o={$ofertaId}&limit=1");
        if ($id = $ler->getResultado()[0]['id'] ?? null) {
            $l = $ler->getResultado()[0];
            \App\adms\Models\helper\AdmsAuditoria::falta($ofertaId, $rm, $l['nome_aluno'] ?? null, (int) $l['faltas_compensadas'], (int) $soma, 'faltas_compensadas'); // Onda 2, Etapa 3
            $u = new \App\adms\Models\helper\AdmsUpdate();
            $u->exeUpdate('rm_disciplina_faltas', ['faltas_compensadas' => $soma, 'usuario_mod' => $_SESSION['colUsuario_id']], 'WHERE id = :id', "id={$id}");
            \Core\Transacao::exigir($u->getResultado() === null ? null : true, 'total compensado');
        }
    }

    private function recusa(string $t): bool
    {
        $this->erros[] = $t;
        return false;
    }
}
