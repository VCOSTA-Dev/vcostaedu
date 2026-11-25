<?php

namespace App\adms\Models\tcc;

use App\adms\Models\notas\AdmsCalculoNotas as Calc;
use App\adms\Models\vinculo\AdmsVinculoRegras as R;

if (!defined('VCOSTAEDU')) { // E6 (D-376): o portal também carrega (atestado do aluno)
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * TCC por matrícula no curso (E6a, P-5): título, orientador (professor cadastrado), nota,
 * data de defesa/entrega e situação. A nota segue a regra das notas da E4 (0 a 10, até 2
 * casas, vírgula ou ponto; gravada com ponto) e é mostrada com as casas da instituição.
 * Coerência: "Aprovado" exige orientador, data e nota a partir da média mínima;
 * "Reprovado" exige nota. Um TCC por matrícula.
 */
class AdmsTcc
{
    public const SITUACOES = [1 => 'Em andamento', 2 => 'Aprovado', 3 => 'Reprovado'];

    private $resultado;

    public function getResultado()
    {
        return $this->resultado;
    }

    public static function daMatricula(int $mcId): ?array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT t.*, u.nome AS orientador, ti.nome AS orientadorTitulacao
               FROM rm_tcc t LEFT JOIN adms_usuarios u ON u.id = t.orientador_id LEFT JOIN uni_rh_titulacao ti ON ti.id = u.titulacao_id
              WHERE t.matricula_curso_id = :mc LIMIT :limit",
            "mc={$mcId}&limit=1"
        );
        return $ler->getResultado()[0] ?? null;
    }

    /** Professores ativos (nível 6) — os orientadores possíveis. */
    public static function orientadores(): array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $n = (int) (\App\adms\Models\helper\AdmsNivel::idDoPapel('professor') ?? 6); // Onda 2: o nível do papel professor
        $ler->fullRead("SELECT id, nome FROM adms_usuarios WHERE adms_niveis_acesso_id = :n AND adms_sits_usuario_id = :s ORDER BY nome", "n={$n}&s=1");
        return $ler->getResultado() ?: [];
    }

    public function salvar(int $mcId, array $d)
    {
        $mc = R::matriculaCurso($mcId);
        if (!$mc) {
            return $this->resultado = R::recusar('Matrícula no curso não encontrada.');
        }
        $erros = [];
        $titulo = trim((string) ($d['titulo'] ?? ''));
        $orientador = trim((string) ($d['orientador_id'] ?? ''));
        $nota = trim((string) ($d['nota'] ?? ''));
        $data = trim((string) ($d['data_defesa'] ?? ''));
        $situacao = (int) ($d['situacao'] ?? 1);
        if ($titulo === '' || mb_strlen($titulo) > 500) {
            $erros[] = 'Informe o título do TCC (até 500 caracteres).';
        }
        if ($orientador !== '' && !in_array((int) $orientador, array_map('intval', array_column(self::orientadores(), 'id')), true)) {
            $erros[] = 'O orientador deve ser um professor ativo.';
        }
        $c = $nota === '' ? null : Calc::centesimos($nota);
        if ($nota !== '' && $c === null) {
            $erros[] = 'Nota inválida: use um número de 0 a 10 com até 2 casas decimais (vírgula ou ponto).';
        }
        if ($data !== '' && !R::dataValida($data)) {
            $erros[] = 'Data de defesa/entrega inválida.';
        }
        if (!isset(self::SITUACOES[$situacao])) {
            $erros[] = 'Situação do TCC inválida.';
        }
        $minimo = (string) Calc::regras((int) ($mc['curso_id'] ?? 0) ?: null)['mediaMinima']; // 1.16.1 (D-447): a regra do TCC e dos documentos do curso
        if ($situacao === 2) {
            if ($orientador === '' || $data === '' || $c === null) {
                $erros[] = 'Para "Aprovado", informe orientador, data e nota.';
            } elseif ($c < (int) round((float) $minimo * 100)) {
                $erros[] = 'Nota abaixo da média mínima (' . Calc::numero($minimo, 1) . '): o TCC não pode ficar "Aprovado".';
            }
        }
        if ($situacao === 3 && $c === null) {
            $erros[] = 'Para "Reprovado", informe a nota.';
        }
        if ($erros) {
            $_SESSION['msg'] = "<div class='alert alert-danger'><b>TCC não salvo:</b><ul class='mb-0'><li>" . implode('</li><li>', array_map('htmlspecialchars', $erros)) . '</li></ul></div>';
            return $this->resultado = false;
        }
        $linha = [
            'titulo' => $titulo, 'orientador_id' => $orientador === '' ? null : (int) $orientador,
            'nota' => $c === null ? null : number_format($c / 100, $c % 10 ? 2 : 1, '.', ''),
            'data_defesa' => $data === '' ? null : $data, 'situacao' => $situacao,
            'observacao' => trim((string) ($d['observacao'] ?? '')) ?: null,
        ];
        $atual = self::daMatricula($mcId);
        /* 1.14.0 (E9, D-350, D-414): o TCC e o registro no log numa TRANSAÇÃO — tudo ou nada */
        $T = \Core\Transacao::class;
        $ok = $T::executar(function () use ($T, $atual, $linha, $mcId, $situacao) {
            if ($atual) {
                $linha['usuario_mod'] = $_SESSION['colUsuario_id'];
                $up = new \App\adms\Models\helper\AdmsUpdate();
                $up->exeUpdate('rm_tcc', $linha, 'WHERE id = :id', "id={$atual['id']}");
                $T::exigir($up->getResultado() === null ? null : true, 'TCC');
            } else {
                $linha += ['matricula_curso_id' => $mcId, 'usuario_cre' => $_SESSION['colUsuario_id']];
                $add = new \App\adms\Models\helper\AdmsCreate();
                $add->exeCreate('rm_tcc', $linha);
                $T::exigir((int) $add->getResultado() > 0 ? true : null, 'TCC');
            }
            $lg = new \App\adms\Models\helper\AdmsSalvarLog();
            $lg->salvarLogs(389, 200, 3, "TCC salvo: matricula_curso={$mcId}; situacao={$situacao}");
            $T::exigir($lg->getResultado() ? true : null, 'registro do TCC');
            return true;
        });
        if (!$ok) {
            return $this->resultado = R::recusar('Não foi possível salvar o TCC: nada mudou (' . $T::ultimoErro() . ').');
        }
        $_SESSION['msg'] = R::aviso('success', "TCC de {$mc['aluno']} salvo (" . self::SITUACOES[$situacao] . ').');
        return $this->resultado = (int) $mc['usuario_id'];
    }
}
