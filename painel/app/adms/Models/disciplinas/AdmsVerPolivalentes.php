<?php

namespace App\adms\Models\disciplinas;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsVerPolivalentes
{
    private $dadosId;
    private $info;
    private $resultado;

    public function visPolis($dadosId)
    {
        $this->dadosId = (int) $dadosId;
        $this->disciplinas();
        $this->dadosDisciplina();
        $this->listDisciplinas();
        return $this->resultado;
    }

    private function disciplinas()
    {
        $listCurso = new \App\adms\Models\helper\AdmsRead();
        $listCurso->fullRead("SELECT a.id, b.id rm_prof_mat_id, b.codtur, b.materia, b.curso_id, (SELECT c.nome FROM rm_cursos c WHERE c.id=b.curso_id) as ensino, a.situacao FROM rm_professores_materias_polivalencias a INNER JOIN rm_professores_materias b ON b.id=a.rm_prof_valido WHERE a.rm_polivalente =:disc ORDER BY b.id ASC", "disc={$this->dadosId}");
        $this->resultado['polivalentes'] = $listCurso->getResultado();
    }

    private function dadosDisciplina()
    {
        $dadosDiscInfo = new \App\adms\Models\helper\AdmsRead();
        $dadosDiscInfo->fullRead("SELECT a.curso_id, a.ano, a.codtur, (SELECT GROUP_CONCAT(b.usuario_id SEPARATOR ',') FROM rm_professores_mat_liga b WHERE b.rm_prof_mat_id=a.id AND b.acesso=:acesso AND b.situacao =:sit) AS professor FROM rm_professores_materias a WHERE a.id =:disc LIMIT :limit", "disc={$this->dadosId}&acesso=1&sit=1&limit=1");
        $this->info = $dadosDiscInfo->getResultado();
    }
    
    private function listDisciplinas()
    {
        $dadosDiscInfo = new \App\adms\Models\helper\AdmsRead();
        $dadosDiscInfo->fullRead("SELECT DISTINCT a.id, a.curso_id, a.codtur, a.codmat, a.materia, (SELECT GROUP_CONCAT(d.nome SEPARATOR ' | ') FROM rm_professores_mat_liga c INNER JOIN adms_usuarios d ON d.id=c.usuario_id WHERE c.rm_prof_mat_id=a.id AND c.acesso=:acesso AND c.situacao=:sit) as professores FROM rm_professores_materias a LEFT JOIN rm_professores_mat_liga b ON b.rm_prof_mat_id=a.id AND b.acesso=:acesso AND b.situacao=:sit WHERE a.curso_id =:curso AND a.ano =:ano AND a.codtur =:turma AND b.usuario_id in ({$this->info[0]['professor']}) AND a.id <>:discpoli", "discpoli={$this->dadosId}&curso={$this->info[0]['curso_id']}&ano={$this->info[0]['ano']}&turma=" . urlencode((string) $this->info[0]['codtur']) . "&acesso=1&sit=1");
        $this->resultado['selecoes'] = $dadosDiscInfo->getResultado();

    }

}