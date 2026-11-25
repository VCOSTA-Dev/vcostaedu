<?php

namespace App\adms\Models\buscar;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsListaBusca
{
    private $dadosId;
    private $listaBusca;
    private $resultado;

    public function turmas($dadosId)
    {
        $this->dadosId = (int) $dadosId;
        $verTipoDisciplinas = new \App\adms\Models\helper\AdmsRead();
        $verTipoDisciplinas->fullRead("SELECT m.id, IF((SELECT COUNT(*) FROM rm_cursos_grades g2 WHERE g2.curso_id = g.curso_id) > 1, CONCAT(m.codnome, ' (grade ', g.versao, ')'), m.codnome) AS codnome, m.ordem FROM rm_cursos_turma_series m INNER JOIN rm_cursos_grades g ON g.id = m.grade_id WHERE m.curso_id =:ensino ORDER BY g.versao, m.ordem", "ensino={$this->dadosId}");
        $this->listaBusca = $verTipoDisciplinas->getResultado();
        $this->gerarListaTurmas();
        $this->resultado['status'] = true;
        $this->resultado['msg'] = '';
        return $this->resultado;
    }

    private function gerarListaTurmas()
    {
        $lista = '';
        foreach($this->listaBusca as $a){
            $lista .= "<div class='form-check form-check-inline'><input type='checkbox' name='submodalidade[]' class='form-check-input' id='ordem{$a['id']}' value='{$a['id']}' checked><label class='form-check-label' for='ordem{$a['id']}'>{$a['codnome']}</label></div>";
        }
        $this->resultado['lista'] = $lista;

    }

}