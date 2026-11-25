<?php

namespace App\adms\Models\matriz;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/** Cursos e as grades de cada um, com nº de módulos, de disciplinas e a CH somada. */
class AdmsListarMatriz
{
    public function listar(): array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT c.id AS curso_id, c.nome AS curso, c.situacao AS situacaoCurso, g.id AS grade_id, g.versao, g.descricao, g.situacao,
                    s.nome AS nomeSituacao, s.cores AS corSituacao,
                    (SELECT COUNT(*) FROM rm_cursos_turma_series m WHERE m.grade_id = g.id) AS modulos,
                    (SELECT COUNT(*) FROM uni_rh_cursos_disciplinas_grade l WHERE l.grade_id = g.id) AS disciplinas,
                    (SELECT IFNULL(SUM(l.cargahoraria), 0) FROM uni_rh_cursos_disciplinas_grade l WHERE l.grade_id = g.id) AS ch
               FROM rm_cursos c
               LEFT JOIN rm_cursos_grades g ON g.curso_id = c.id
               LEFT JOIN uni_rh_cursos_grades_situacao s ON s.id = g.situacao
              ORDER BY c.nome, g.versao"
        );
        $cursos = [];
        foreach ($ler->getResultado() ?: [] as $r) {
            $id = (int) $r['curso_id'];
            $cursos[$id] ??= ['id' => $id, 'nome' => $r['curso'], 'ativo' => (int) $r['situacaoCurso'] === 1, 'grades' => []]; // E8d (P-8)
            if ($r['grade_id'] !== null) {
                $cursos[$id]['grades'][] = $r;
            }
        }
        return array_values($cursos);
    }

    /**
     * Os cursos da escolha da grade. 1.18.1 (D-479): só os ATIVOS — curso inativo sai das listas de escolha (o servidor
     * também recusa); na edição, o curso da própria grade continua na lista mesmo inativo ($manter).
     */
    public function cursos(int $manter = 0): array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT id, nome FROM rm_cursos WHERE situacao = 1 OR id = :m ORDER BY nome", "m={$manter}");
        return $ler->getResultado() ?: [];
    }

    public function situacoes(): array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT id, nome, cores FROM uni_rh_cursos_grades_situacao ORDER BY id");
        return $ler->getResultado() ?: [];
    }
}
