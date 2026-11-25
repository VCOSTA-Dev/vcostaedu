<?php

namespace App\adms\Models\cursos;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsVerCursos
{
    private $dadosId;
    private $resultado;

    public function visCursos($dadosId)
    {
        $this->dadosId = (int) $dadosId;
        $this->informacao();
        $this->listCoordenacao();
        $this->listGrades();
        return $this->resultado;
    }

    private function informacao()
    {
        $listCurso = new \App\adms\Models\helper\AdmsRead();
        $listCurso->fullRead("SELECT c.id, c.nome, c.descricao, c.periodo, c.modalidade_id, c.nivel, c.abreviatura, c.nome_extenso, c.cabecalho_documento, c.frequencia_minima, s.nome nomeSituacao, 1 as grade, IFNULL((SELECT p.nome FROM rm_polos p WHERE p.id = c.localidade), '—') as localidade, (SELECT md.nome FROM uni_rh_cursos_modalidade md WHERE md.id = c.modalidade_id) as modalidade, (SELECT o.cor FROM adms_cors o WHERE o.id=s.cor_id) as corSituacao FROM rm_cursos c INNER JOIN adms_sits_pgs s ON s.id=c.situacao WHERE c.id=:id LIMIT :limit", "id={$this->dadosId}&limit=1");
        $this->resultado['info'] = $listCurso->getResultado();
    }

    /** E3a: grades do curso (matriz), com nº de módulos e CH somada. */
    private function listGrades()
    {
        $listGrades = new \App\adms\Models\helper\AdmsRead();
        $listGrades->fullRead("SELECT g.id, g.versao, s.nome AS nomeSituacao,
                (SELECT COUNT(*) FROM rm_cursos_turma_series m WHERE m.grade_id = g.id) AS modulos,
                (SELECT COUNT(*) FROM uni_rh_cursos_disciplinas_grade l WHERE l.grade_id = g.id) AS disciplinas,
                (SELECT IFNULL(SUM(l.cargahoraria), 0) FROM uni_rh_cursos_disciplinas_grade l WHERE l.grade_id = g.id) AS ch
            FROM rm_cursos_grades g LEFT JOIN uni_rh_cursos_grades_situacao s ON s.id = g.situacao
            WHERE g.curso_id = :cur ORDER BY g.versao", "cur={$this->dadosId}");
        $this->resultado['grades'] = $listGrades->getResultado() ?: [];
    }

    private function listCoordenacao()
    {
        $listCoordenadores = new \App\adms\Models\helper\AdmsRead();
        $listCoordenadores->fullRead("SELECT a.id, a.curso_id, a.usuario_id, c.nome professor, a.posicao_id, b.nome as posicao FROM rm_cursos_coordenacao a INNER JOIN rm_cursos_posicao b ON b.id=a.posicao_id INNER JOIN adms_usuarios c ON c.id=a.usuario_id WHERE a.curso_id =:cur AND a.situacao =:sit", "cur={$this->dadosId}&sit=1");
        $this->resultado['listCoordenacao'] = $listCoordenadores->getResultado();
    }

}