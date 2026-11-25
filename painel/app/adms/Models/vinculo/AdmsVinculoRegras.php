<?php

namespace App\adms\Models\vinculo;

if (!defined('VCOSTAEDU')) { // E6 (D-376): o portal também carrega (atestado do aluno)
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * VÍNCULO DO ALUNO (E3b) — regras e leituras comuns.
 *
 * Granularidade (D-100):
 * - rm_matriculas_curso  = a MATRÍCULA NO CURSO: curso, grade, forma e data de
 *   ingresso, situação. Uma por entrada do aluno no curso.
 * - rm_matriculas_ensino = a POSIÇÃO NO ANO: ano + grade + módulo (por id), ligada
 *   à matrícula no curso. Uma por ano (D-04). É ela que sugere a enturmação e é
 *   lida pelo boletim, pelo portal e pela lista de enturmação.
 *
 * Grade Desativada não aceita vínculo novo; Aberta, Em análise e Finalizada aceitam.
 */
class AdmsVinculoRegras
{
    public const CURSANDO = 1;
    public const TRANCADO = 3;

    /** Módulo com a grade e o curso dele, ou null. */
    public static function modulo(int $moduloId): ?array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT m.id, m.codnome, m.ordem, m.grade_id, g.versao, g.situacao AS situacaoGrade, g.curso_id, c.nome AS curso
               FROM rm_cursos_turma_series m
               INNER JOIN rm_cursos_grades g ON g.id = m.grade_id
               INNER JOIN rm_cursos c ON c.id = g.curso_id
              WHERE m.id = :id LIMIT :limit",
            "id={$moduloId}&limit=1"
        );
        return $ler->getResultado()[0] ?? null;
    }

    public static function matriculaCurso(int $id): ?array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT mc.*, u.rm, u.nome AS aluno, c.nome AS curso, g.versao
               FROM rm_matriculas_curso mc
               INNER JOIN col_usuarios u ON u.id = mc.usuario_id
               INNER JOIN rm_cursos c ON c.id = mc.curso_id
               INNER JOIN rm_cursos_grades g ON g.id = mc.grade_id
              WHERE mc.id = :id LIMIT :limit",
            "id={$id}&limit=1"
        );
        return $ler->getResultado()[0] ?? null;
    }

    /**
     * E6a (D-115): polo e modalidade da matrícula. Vazio = os do curso (gravados na matrícula,
     * para o histórico não mudar se o curso mudar depois). @return array{0: int|false, 1: int|false}
     */
    public static function poloModalidade(array $dados, int $cursoId): array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT localidade, modalidade_id FROM rm_cursos WHERE id = :id LIMIT :limit", "id={$cursoId}&limit=1");
        $c = $ler->getResultado()[0] ?? ['localidade' => 0, 'modalidade_id' => 1];
        $p = trim((string) ($dados['polo_id'] ?? ''));
        $m = trim((string) ($dados['modalidade_id'] ?? ''));
        $polo = $p === '' ? (int) $c['localidade'] : (int) $p;
        $mod = $m === '' ? (int) $c['modalidade_id'] : (int) $m;
        return [\App\adms\Models\disciplinas\AdmsOfertaMatriz::poloAtivo($polo) ? $polo : false, \App\adms\Models\modalidades\AdmsModalidades::existe($mod) ? $mod : false];
    }

    public static function anoLetivoExiste(int $ano): bool
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT id FROM adms_periodos_letivos WHERE ano = :a LIMIT :limit", "a={$ano}&limit=1");
        return !empty($ler->getResultado());
    }

    public static function existe(string $tabela, int $id): bool
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT id FROM {$tabela} WHERE id = :id LIMIT :limit", "id={$id}&limit=1");
        return !empty($ler->getResultado());
    }

    /**
     * E1b (D-354): PREVISÃO DE CONCLUSÃO — opcional; se vier, data válida e não antes do ingresso. Devolve a data
     * (Y-m-d), '' (vazia) ou false (inválida, com a recusa já na sessão).
     */
    public static function previsao(array $dados, string $ingresso)
    {
        $p = trim((string) ($dados['previsao_conclusao'] ?? ''));
        if ($p === '') {
            return '';
        }
        if (!self::dataValida($p)) {
            self::recusar('A previsão de conclusão não é uma data válida.');
            return false;
        }
        if ($ingresso !== '' && $p < $ingresso) {
            self::recusar('A previsão de conclusão não pode ser antes do ingresso.');
            return false;
        }
        return $p;
    }

    /** E1b (D-354): sugestão da previsão = a maior data de fim das ofertas da grade (ou null, se nenhuma tem data). */
    public static function sugestaoConclusao(int $gradeId): ?string
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT MAX(o.data_fim) AS fim FROM rm_professores_materias o INNER JOIN uni_rh_cursos_disciplinas_grade l ON l.id = o.matriz_id WHERE l.grade_id = :g",
            "g={$gradeId}"
        );
        return $ler->getResultado()[0]['fim'] ?? null;
    }

    public static function dataValida(string $data): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $data);
        return $d !== false && $d->format('Y-m-d') === $data;
    }

    public static function aviso(string $tipo, string $texto): string
    {
        return "<div class='alert alert-{$tipo} alert-dismissible'><button type='button' "
            . "class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>"
            . htmlspecialchars($texto, ENT_QUOTES, 'UTF-8') . '</div>';
    }

    public static function recusar(string $texto): bool
    {
        $_SESSION['msg'] = self::aviso('danger', 'Erro: ' . $texto);
        return false;
    }

    public static function log(int $pagina, int $acao, string $texto): void
    {
        (new \App\adms\Models\helper\AdmsSalvarLog())->salvarLogs($pagina, 200, $acao, $texto);
    }
}
