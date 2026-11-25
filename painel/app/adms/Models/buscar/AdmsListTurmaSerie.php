<?php

namespace App\adms\Models\buscar;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsListTurmaSerie
{
    private $dadosId;
    private $listaBusca;
    private $resultado;

    public function listSerie($dadosId)
    {
        $this->dadosId = (int) $dadosId;
        $this->buscaLista();
        return $this->resultado;
    }

    private function buscaLista()
    {
        $verTipoDisciplinas = new \App\adms\Models\helper\AdmsRead();
        $verTipoDisciplinas->fullRead("SELECT m.id AS ordem, IF((SELECT COUNT(*) FROM rm_cursos_grades g2 WHERE g2.curso_id = g.curso_id) > 1, CONCAT(m.codnome, ' (grade ', g.versao, ')'), m.codnome) AS codnome FROM rm_cursos_turma_series m INNER JOIN rm_cursos_grades g ON g.id = m.grade_id WHERE m.curso_id =:cur AND m.situacao =:sit ORDER BY g.versao, m.ordem", "cur={$this->dadosId}&sit=1");
        $this->listaBusca = $verTipoDisciplinas->getResultado();
        if(isset($this->listaBusca[0]['ordem']) && !empty($this->listaBusca[0]['ordem'])){
            $this->constroeLista();
            $this->resultado['status'] = true;
            $this->resultado['msg'] = "<div class='alert alert-danger'>ERRO: Não foi possível encontrar os módulos!</div>";
        }else{
            $this->resultado['opcoes'] = "<option value=''>Selecione o Módulo</option>";
            $this->resultado['status'] = false;
            $this->resultado['msg'] = "<div class='alert alert-danger'>ERRO: Não foi possível encontrar os módulos!</div>";
        }
    }

    private function constroeLista()
    {
        $this->resultado['opcoes'] = "<option value=''>Selecione o Módulo</option>";
        foreach($this->listaBusca as $bc){
            $this->resultado['opcoes'] .= "<option value='{$bc['ordem']}'>{$bc['codnome']}</option>";
        }
        
    }

}