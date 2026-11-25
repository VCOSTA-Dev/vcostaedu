<?php

namespace App\adms\Models\atividades;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsApagarAtividades
{
    private $dados;
    private $notasAlunos;
    private $logCodigo;
    private $logDescricao;
    private $resultado;

    function getResultado()
    {
        return $this->resultado;
    }

    public function delAtividade(array $dados)
    {
        $this->dados = $dados;
        $this->checarAtividade();
        
    }

    private function checarAtividade()
    {
        $listNotasCheck = new \App\adms\Models\helper\AdmsRead();
        $listNotasCheck->fullRead("SELECT id, rm, nota, tipo FROM rm_disciplina_atividade_notas WHERE atividade_id=:atividade AND rm_prof_mat_id=:rmprof AND modified is not null", "atividade={$this->dados['id']}&rmprof={$this->dados['rm_prof_mat_id']}");
        if(!$listNotasCheck->getResultado()){
            $this->notasAlunos = $listNotasCheck->getResultado();
            $this->apagarAtividade();
        }else{
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>ERRO: Não foi é possível apagar a atividade!</div>";
            $this->resultado = false;
        }
    }

    private function apagarAtividade()
    {
        $delChamada = new \App\adms\Models\helper\AdmsDeletar();
        $delChamada->exeDelete("rm_disciplina_atividade", "WHERE id=:id AND rm_prof_mat_id=:rm", "id={$this->dados['id']}&rm={$this->dados['rm_prof_mat_id']}");
        if($delChamada->getResultado()){
            $this->logCodigo = 200;
            $this->logDescricao = "Atividade apagada com sucesso! id={$this->dados['id']}&rm_prof_mat_id={$this->dados['rm_prof_mat_id']}&tipo={$this->notasAlunos[0]['tipo']}";
            $this->salvarLog();
            $this->apagarAlunos();
        }else{
            $this->logCodigo = 400;
            $this->logDescricao = "Error: Não foi possível apagar a atividade! id={$this->dados['id']}&rm_prof_mat_id={$this->dados['rm_prof_mat_id']}&tipo={$this->notasAlunos[0]['tipo']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Error: Não foi possível apagar a atividade!</div>";
            $this->resultado = false;
        }
    }

    private function apagarAlunos()
    {
        $delChamada = new \App\adms\Models\helper\AdmsDeletar();
        $delChamada->exeDelete("rm_disciplina_atividade_notas", "WHERE atividade_id=:atividade AND rm_prof_mat_id=:rm", "atividade={$this->dados['id']}&rm={$this->dados['rm_prof_mat_id']}");
        if($delChamada->getResultado()){
            $this->logCodigo = 200;
            $this->logDescricao = "Tabela de alunos da atividade apagada com sucesso! id={$this->dados['id']}&rm_prof_mat_id={$this->dados['rm_prof_mat_id']}&tipo={$this->notasAlunos[0]['tipo']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Atividade apagada com sucesso!</div>";
            $this->resultado = true;
        }else{
            $this->logCodigo = 400;
            $this->logDescricao = "Error: Não foi possível apagar a lista de alunos da atividade! id={$this->dados['id']}&rm_prof_mat_id={$this->dados['rm_prof_mat_id']}&tipo={$this->notasAlunos[0]['tipo']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Atividade apagada com sucesso!</div>";
            $this->resultado = true;
        }
    }

    private function salvarLog()
    {
        $valLogsalvar = new \App\adms\Models\helper\AdmsSalvarLog();
        $valLogsalvar->salvarLogs(113, $this->logCodigo, 4, $this->logDescricao);
    }

}