<?php

namespace App\adms\Models\helperApi;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsAlunosDisciplinas
{

    private $dadosId;
    private $resultado;

    public function disciplinaAlunos($dadosId)
    {

        $this->dadosId = (int) $dadosId;
        $this->verificarLista();
        return $this->resultado;

    }

    private function verificarLista()
    {
        $verListaAlunos = new \App\adms\Models\helper\AdmsRead();
        $verListaAlunos->fullRead("SELECT rm, nome_aluno, ordem, observacao, situacao FROM portal_disciplinas_alunos WHERE rm_prof_mat_id =:rm AND situacao =:sit ORDER BY ordem ASC", "rm={$this->dadosId}&sit=1");
        $listAlunos = $verListaAlunos->getResultado();
        if(isset($listAlunos) && !empty($listAlunos)){
            $this->resultado = $listAlunos;
        }else{
            $this->resultado = '';
        }
    }

}