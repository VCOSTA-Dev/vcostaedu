<?php

namespace App\adms\Models\matriz;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * Regras comuns da matriz curricular (E3a): curso → grade → módulo → disciplina.
 *
 * - Grade  = rm_cursos_grades (`versao` é o número digitado, único no curso).
 * - Módulo = rm_cursos_turma_series (pertence a uma grade; `ordem` é a posição).
 * - Linha  = uni_rh_cursos_disciplinas_grade (matéria do catálogo + CH).
 *
 * CH do módulo = soma das linhas; CH da grade = soma dos módulos. Nada é digitado.
 *
 * Só a grade ABERTA (situação 1) aceita mudança na matriz.
 *
 * Matrícula e oferta ainda acham o módulo por (curso, ordem): a matrícula não
 * tem grade até a E3b. Por isso "está em uso" é medido de forma conservadora.
 */
class AdmsMatrizRegras
{
    public const ABERTA = 1;
    public const DESATIVADA = 4;

    /** Grade com o curso e o nome da situação, ou null. */
    public static function grade(int $gradeId): ?array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT g.id, g.curso_id, g.versao, g.descricao, g.situacao,
                    c.nome AS curso, s.nome AS nomeSituacao, s.cores AS corSituacao
               FROM rm_cursos_grades g
               INNER JOIN rm_cursos c ON c.id = g.curso_id
               LEFT JOIN uni_rh_cursos_grades_situacao s ON s.id = g.situacao
              WHERE g.id = :id LIMIT :limit",
            "id={$gradeId}&limit=1"
        );
        return $ler->getResultado()[0] ?? null;
    }

    public static function modulo(int $moduloId): ?array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT id, curso_id, grade_id, codnome, descricao, ordem, observacao, situacao
               FROM rm_cursos_turma_series WHERE id = :id LIMIT :limit",
            "id={$moduloId}&limit=1"
        );
        return $ler->getResultado()[0] ?? null;
    }

    /** O usuário da sessão é administrador (níveis 1 e 2, D-496)? */
    public static function ehAdmin(): bool
    {
        return \App\adms\Models\helper\AdmsNivel::ehAdministrador();
    }

    /**
     * Pode trocar a situação da grade de `$atual` para `$nova`? (E3b, decisões 2 e 3 do responsável pelo projeto)
     * - Secretaria (e qualquer não-admin): só Aberta → Em análise.
     * - Administrador: qualquer troca — finalizar, desativar, reabrir (voltar a Aberta).
     */
    public static function podeTrocarSituacao(int $atual, int $nova): bool
    {
        if ($atual === $nova) {
            return false;
        }
        return self::ehAdmin() || ($atual === self::ABERTA && $nova === 2);
    }

    /** E8c (D-266): reabrir (voltar a Aberta) uma grade Finalizada (3) ou Desativada (4) exige justificativa. */
    public static function exigeJustificativa(int $atual, int $nova): bool
    {
        return $nova === self::ABERTA && in_array($atual, [3, self::DESATIVADA], true);
    }

    /** Situações para as quais o usuário da sessão pode levar a grade. */
    public static function situacoesPermitidas(int $atual, array $todas): array
    {
        return array_values(array_filter($todas, fn($s) => self::podeTrocarSituacao($atual, (int) $s['id'])));
    }

    /** Mensagem de recusa se a grade não aceita mudança na matriz; null se aceita. */
    public static function recusaEdicao(?array $grade): ?string
    {
        if (!$grade) {
            return 'Grade não encontrada.';
        }
        if ((int) $grade['situacao'] !== self::ABERTA) {
            return "A grade {$grade['versao']} de {$grade['curso']} está «{$grade['nomeSituacao']}». "
                . 'Só uma grade ABERTA aceita mudança na matriz. Reabrir (voltar para Aberta) é do administrador, com justificativa.';
        }
        return null;
    }

    /**
     * Quantas posições no ano (matrículas), ofertas e disciplinas usam o módulo.
     * E3b: por id — rm_matriculas_ensino.modulo_id e a linha da matriz da oferta
     * (rm_professores_materias.matriz_id → uni_rh_cursos_disciplinas_grade.modulo_id).
     */
    public static function usosDoModulo(array $modulo): array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT (SELECT COUNT(*) FROM rm_matriculas_ensino m WHERE m.modulo_id = :m1) AS matriculas,
                    (SELECT COUNT(*) FROM rm_professores_materias p
                       INNER JOIN uni_rh_cursos_disciplinas_grade l ON l.id = p.matriz_id
                      WHERE l.modulo_id = :m2) AS ofertas,
                    (SELECT COUNT(*) FROM uni_rh_cursos_disciplinas_grade l WHERE l.modulo_id = :m3) AS disciplinas",
            "m1={$modulo['id']}&m2={$modulo['id']}&m3={$modulo['id']}"
        );
        $r = $ler->getResultado()[0] ?? [];
        return [
            'matriculas' => (int) ($r['matriculas'] ?? 0),
            'ofertas' => (int) ($r['ofertas'] ?? 0),
            'disciplinas' => (int) ($r['disciplinas'] ?? 0),
        ];
    }

    /**
     * Ofertas que usam esta linha da matriz. E3b (D-86): pela ligação explícita
     * rm_professores_materias.matriz_id — é a regra "remover ou mover só se nenhuma
     * oferta usar".
     */
    public static function ofertasDaLinha(int $linhaId): int
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT COUNT(*) AS n FROM rm_professores_materias WHERE matriz_id = :id", "id={$linhaId}");
        return (int) ($ler->getResultado()[0]['n'] ?? 0);
    }

    public static function materia(int $id): ?array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT id, nome, carga_horaria FROM rm_materias WHERE id = :id AND situacao = :s LIMIT :limit", "id={$id}&s=1&limit=1");
        return $ler->getResultado()[0] ?? null;
    }

    /** Linha da matriz com o nome da matéria, ou null. */
    public static function linha(int $id): ?array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT l.id, l.grade_id, l.modulo_id, l.materia_id, l.cargahoraria, mt.nome AS materia
               FROM uni_rh_cursos_disciplinas_grade l
               INNER JOIN rm_materias mt ON mt.id = l.materia_id
              WHERE l.id = :id LIMIT :limit",
            "id={$id}&limit=1"
        );
        return $ler->getResultado()[0] ?? null;
    }

    /** A matéria já está neste módulo desta grade (fora a linha `$exceto`)? */
    public static function jaNoModulo(int $gradeId, int $moduloId, int $materiaId, int $exceto): bool
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT id FROM uni_rh_cursos_disciplinas_grade
              WHERE grade_id = :g AND modulo_id = :m AND materia_id = :mt AND id <> :id LIMIT :limit",
            "g={$gradeId}&m={$moduloId}&mt={$materiaId}&id={$exceto}&limit=1"
        );
        return !empty($ler->getResultado());
    }

    /** E8c (D-268): sem módulos, a matéria não se repete na grade inteira (fora a linha `$exceto`). */
    public static function jaNaGrade(int $gradeId, int $materiaId, int $exceto): bool
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT id FROM uni_rh_cursos_disciplinas_grade WHERE grade_id = :g AND materia_id = :mt AND id <> :id LIMIT :limit",
            "g={$gradeId}&mt={$materiaId}&id={$exceto}&limit=1"
        );
        return !empty($ler->getResultado());
    }

    /** CH digitada (1 a 9999); vazia usa a do catálogo, se houver matéria. */
    public static function ch($valor, ?array $materia = null): ?int
    {
        $v = trim((string) $valor);
        if ($v === "" && $materia !== null) {
            $v = preg_replace("/\D/", "", (string) $materia["carga_horaria"]);
        }
        return preg_match("/^\d{1,4}$/", $v) && (int) $v >= 1 ? (int) $v : null;
    }

    /** Número de grade digitado (1 a 9999), ou null. */
    public static function numeroGrade($valor): ?int
    {
        $v = trim((string) $valor);
        return preg_match("/^\d{1,4}$/", $v) && (int) $v >= 1 ? (int) $v : null;
    }

    public static function numeroGradeOcupado(int $cursoId, int $numero, int $exceto): bool
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT id FROM rm_cursos_grades WHERE curso_id = :c AND versao = :v AND id <> :id LIMIT :limit",
            "c={$cursoId}&v={$numero}&id={$exceto}&limit=1"
        );
        return !empty($ler->getResultado());
    }

    /** Registra a situação da grade no histórico (uni_rh_cursos_gradeamento_obs). */
    public static function historico(int $gradeId, int $situacao, string $obs): void
    {
        (new \App\adms\Models\helper\AdmsCreate())->exeCreate("uni_rh_cursos_gradeamento_obs", [
            "grade_id" => $gradeId,
            "alteracao" => $situacao,
            "observacao" => $obs,
            "usuario_cre" => $_SESSION["colUsuario_id"],
        ]);
    }

    public static function log(int $pagina, int $acao, string $texto): void
    {
        (new \App\adms\Models\helper\AdmsSalvarLog())->salvarLogs($pagina, 200, $acao, $texto);
    }

    /** Põe a recusa em $_SESSION["msg"] e devolve false. */
    public static function recusar(string $texto): bool
    {
        $_SESSION["msg"] = self::aviso("danger", "Erro: " . $texto);
        return false;
    }
    public static function aviso(string $tipo, string $texto): string
    {
        return "<div class='alert alert-{$tipo} alert-dismissible'><button type='button' "
            . "class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>"
            . htmlspecialchars($texto, ENT_QUOTES, 'UTF-8') . '</div>';
    }
}
