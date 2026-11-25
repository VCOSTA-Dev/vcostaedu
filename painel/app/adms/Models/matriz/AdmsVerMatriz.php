<?php

namespace App\adms\Models\matriz;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\matriz\AdmsMatrizRegras as Regras;

/**
 * A matriz de uma grade: módulos em ordem, disciplinas de cada um, CH do módulo
 * (soma das disciplinas) e CH da grade (soma dos módulos).
 */
class AdmsVerMatriz
{
    public function ver(int $gradeId): ?array
    {
        $grade = Regras::grade($gradeId);
        if (!$grade) {
            return null;
        }

        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT id, codnome, descricao, ordem, situacao FROM rm_cursos_turma_series
              WHERE grade_id = :g ORDER BY ordem",
            "g={$gradeId}"
        );
        $modulos = [];
        foreach ($ler->getResultado() ?: [] as $m) {
            $m['disciplinas'] = [];
            $m['ch'] = 0;
            $modulos[(int) $m['id']] = $m;
        }

        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT l.id, l.modulo_id, l.materia_id, l.cargahoraria, l.ordem, l.regra_media_id, mt.nome AS materia, /* 1.16.0 (D-438 a) */ mt.abreviacao, mt.situacao AS situacaoMateria,
                    (SELECT COUNT(*) FROM rm_professores_materias p WHERE p.matriz_id = l.id) AS ofertas
               FROM uni_rh_cursos_disciplinas_grade l
               INNER JOIN rm_cursos_grades g ON g.id = l.grade_id
               INNER JOIN rm_materias mt ON mt.id = l.materia_id
              WHERE l.grade_id = :g
              ORDER BY l.ordem, mt.nome", // E1b (D-353): a ordem da grade; empate, o nome
            "g={$gradeId}"
        );
        foreach ($ler->getResultado() ?: [] as $l) {
            $mid = (int) $l['modulo_id'];
            if (isset($modulos[$mid])) {
                $modulos[$mid]['disciplinas'][] = $l;
                $modulos[$mid]['ch'] += (int) $l['cargahoraria'];
            }
        }

        $chGrade = 0;
        foreach ($modulos as $m) {
            $chGrade += $m['ch'];
        }

        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT o.alteracao, o.observacao, o.created, s.nome AS situacao, u.nome AS usuario
               FROM uni_rh_cursos_gradeamento_obs o
               LEFT JOIN uni_rh_cursos_grades_situacao s ON s.id = o.alteracao
               LEFT JOIN adms_usuarios u ON u.id = o.usuario_cre
              WHERE o.grade_id = :g ORDER BY o.id DESC",
            "g={$gradeId}"
        );
        $historico = $ler->getResultado() ?: [];

        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT id, nome, abreviacao, carga_horaria FROM rm_materias WHERE situacao = :s ORDER BY nome", "s=1");
        $materias = $ler->getResultado() ?: [];

        /* E8c (D-268): sem módulos, uma lista só com as disciplinas de todos os módulos da grade */
        $semModulos = \App\adms\Models\helper\AdmsModuloInterno::ativo();
        $disciplinas = [];
        if ($semModulos) {
            foreach ($modulos as $m) {
                $disciplinas = array_merge($disciplinas, $m['disciplinas']);
            }
            usort($disciplinas, fn($a, $b) => [(int) $a['ordem'], $a['materia']] <=> [(int) $b['ordem'], $b['materia']]); // E1b (D-353)
        }

        return [
            'grade' => $grade,
            'semModulos' => $semModulos,
            'disciplinas' => $disciplinas,
            'modulos' => array_values($modulos),
            'chGrade' => $chGrade,
            'historico' => $historico,
            'materias' => $materias,
            'editavel' => Regras::recusaEdicao($grade) === null,
        ];
    }
}
