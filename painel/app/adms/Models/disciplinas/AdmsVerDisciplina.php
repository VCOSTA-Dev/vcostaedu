<?php

namespace App\adms\Models\disciplinas;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsVerDisciplina
{
    private $dadosId;
    private $resultado;

    public function visDisciplina($dadosId)
    {
        $this->dadosId = (int) $dadosId;
        $this->informacao();
        $this->qtdeAlunos();
        return $this->resultado;
    }

    private function informacao()
    {
        $listCurso = new \App\adms\Models\helper\AdmsRead();
        $listCurso->fullRead("SELECT m.id, m.periodo, m.codmat, m.codtur, m.materia, m.bimestre, m.ano, m.codfilial, m.tipo, m.idMoodle, m.encontros, m.encerrada_em, m.matriz_id, (SELECT p.nome FROM rm_polos p WHERE p.id = m.codfilial) AS polo, (SELECT md.nome FROM uni_rh_cursos_modalidade md WHERE md.id = m.modalidade_id) AS modalidade, (SELECT CONCAT('Grade ', g.versao, ' — ', sm.codnome) FROM uni_rh_cursos_disciplinas_grade l INNER JOIN rm_cursos_grades g ON g.id = l.grade_id INNER JOIN rm_cursos_turma_series sm ON sm.id = l.modulo_id WHERE l.id = m.matriz_id) AS linhaMatriz, (SELECT GROUP_CONCAT(uo.nome SEPARATOR '<br>') FROM rm_cursos_coordenacao co INNER JOIN adms_usuarios uo ON uo.id=co.usuario_id WHERE co.curso_id=m.curso_id AND co.posicao_id=:ace AND co.situacao=:sit) as coordenacao, (SELECT GROUP_CONCAT(u.nome SEPARATOR '<br>') FROM rm_professores_mat_liga l INNER JOIN adms_usuarios u ON u.id=l.usuario_id WHERE l.rm_prof_mat_id=m.id AND l.acesso=:ace AND l.situacao=:sit) as professores, (SELECT GROUP_CONCAT(x.nome SEPARATOR '<br>') FROM rm_professores_materias_salas z INNER JOIN rm_salas x ON x.id=z.sala_id WHERE z.rm_prof_mat_id=m.id) as salas FROM rm_professores_materias m WHERE m.id =:id LIMIT :limit", "id={$this->dadosId}&ace=1&sit=1&limit=1");
        $this->resultado['info'] = $listCurso->getResultado();
    }

    private function qtdeAlunos()
    {
        $listCurso = new \App\adms\Models\helper\AdmsRead();
        $listCurso->fullRead("SELECT ordem, rm, nome_aluno, observacao, situacao FROM portal_disciplinas_alunos WHERE rm_prof_mat_id = :rm AND situacao=:sit ORDER BY ordem ASC ", "rm={$this->dadosId}&sit=1");
        $this->resultado['qtdeAluno'] = $listCurso->getResultado();
    }

}