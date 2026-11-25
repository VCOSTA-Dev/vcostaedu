<?php

namespace App\adms\Models\modulos;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * MÓDULOS da matriz (E3a) — no lugar das "séries" do sistema escolar de origem.
 *
 * O módulo é a linha de rm_cursos_turma_series: pertence a uma GRADE de um
 * curso, tem nome e ordem, e a CH é a soma das suas disciplinas.
 *
 * O que saiu da série: a "corrente da rematrícula" (promoção da `ordem` para a
 * `ordem + 1` atravessando cursos), o "promove para" e o "conclui". A
 * rematrícula saiu na E2a; só a tela de séries ainda falava dela. A `ordem`
 * agora é única DENTRO da grade (antes era única no sistema inteiro).
 */
class AdmsListarModulos
{
    /** Módulos com curso, grade, nº de disciplinas e CH somada. */
    public function listar(int $gradeId = 0): array
    {
        $filtro = $gradeId > 0 ? 'WHERE s.grade_id = :g' : 'WHERE :g = 0';
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT s.id, s.curso_id, s.grade_id, s.codnome, s.descricao, s.ordem, s.situacao,
                    c.nome AS curso, g.versao AS grade, g.situacao AS situacaoGrade,
                    gs.nome AS nomeSituacaoGrade, gs.cores AS corSituacaoGrade,
                    (SELECT COUNT(*) FROM uni_rh_cursos_disciplinas_grade l WHERE l.modulo_id = s.id) AS disciplinas,
                    (SELECT IFNULL(SUM(l.cargahoraria), 0) FROM uni_rh_cursos_disciplinas_grade l WHERE l.modulo_id = s.id) AS ch
               FROM rm_cursos_turma_series s
               INNER JOIN rm_cursos_grades g ON g.id = s.grade_id
               INNER JOIN rm_cursos c ON c.id = s.curso_id
               LEFT JOIN uni_rh_cursos_grades_situacao gs ON gs.id = g.situacao
               {$filtro}
              ORDER BY c.nome, g.versao, s.ordem",
            "g={$gradeId}"
        );
        return $ler->getResultado() ?: [];
    }

    /** Grades para o seletor: curso + número + situação. */
    public function grades(): array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT g.id, g.versao, g.situacao, c.nome AS curso, c.situacao AS situacaoCurso, s.nome AS nomeSituacao
               FROM rm_cursos_grades g
               INNER JOIN rm_cursos c ON c.id = g.curso_id
               LEFT JOIN uni_rh_cursos_grades_situacao s ON s.id = g.situacao
              ORDER BY c.nome, g.versao"
        );
        return $ler->getResultado() ?: [];
    }
}
