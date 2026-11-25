<?php

namespace App\adms\Models\horarioAula;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsCadastrarSala
{
    private $dados;
    private $salas;
    private $dadosInativo;
    private $dadosProf;
    private $idDisciplina;
    private $logCodigo;
    private $logAcao;
    private $logDescricao;
    private $resultado;

    function getResultado()
    {
        return $this->resultado;
    }

    public function cadSalaDisciplina(array $dados)
    {
        $this->dados = $dados;
        $this->salas = $this->dados['sala'];
        unset($this->dados['sala']);
        $this->inativaSalas();
        $this->separaSalas();
    }

    private function inativaSalas()
    {
        $this->dadosInativo['situacao'] = 2;
        $this->dadosInativo['usuario_mod'] = $_SESSION['colUsuario_id'];
        $altDiscSala = new \App\adms\Models\helper\AdmsUpdate();
        $altDiscSala->exeUpdate("rm_professores_materias_salas", $this->dadosInativo, "WHERE rm_prof_mat_id=:rmprof", "rmprof={$this->dados['rm_prof_mat_id']}");
    }

    private function separaSalas()
    {
        foreach($this->salas as $sa){
            $this->dados['sala_id'] = $sa;
            $this->checarDuplicidade();
        }
    }

    private function checarDuplicidade()
    {
        $verDuploDiscSala = new \App\adms\Models\helper\AdmsRead();
        $verDuploDiscSala->fullRead("SELECT id FROM rm_professores_materias_salas WHERE sala_id =:sala AND rm_prof_mat_id =:rmprof LIMIT :limit", "sala={$this->dados['sala_id']}&rmprof={$this->dados['rm_prof_mat_id']}&limit=1");
        $valorSala = $verDuploDiscSala->getResultado();
        if(isset($valorSala[0]['id']) && !empty($valorSala[0]['id'])){
            $this->dados['id'] = $valorSala[0]['id'];
            $this->atualizaSala();
        }else{
            unset($this->dados['id']);
            $this->addSala();
        }
    }
    
    private function infoSala()
    {
        $verInfoSala = new \App\adms\Models\helper\AdmsRead();
        $verInfoSala->fullRead("SELECT id, capacidade_max FROM rm_salas WHERE id =:sala LIMIT :limit", "sala={$this->dados['sala_id']}&limit=1");
        $capSala = $verInfoSala->getResultado();
        $this->dados['capacidade_max'] = $capSala[0]['capacidade_max'];
    }

    private function addSala()
    {
        $this->infoSala();
        $this->dados['usuario_cre'] = $_SESSION['colUsuario_id'];
        $this->dados['situacao'] = 1;
        $addDisc = new \App\adms\Models\helper\AdmsCreate();
        $addDisc->exeCreate("rm_professores_materias_salas", $this->dados);
        if($addDisc->getResultado()){
            $this->logCodigo = 200;
            $this->logAcao = 2;
            $this->logDescricao = "Sala adicionada com sucesso! sala={$this->dados['sala_id']}&materia={$this->dados['rm_prof_mat_id']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Sala adicionada com sucesso!</div>";
            $this->resultado = true;
        }else{
            $this->logCodigo = 400;
            $this->logAcao = 2;
            $this->logDescricao = "Erro: Não foi possível adicionar a sala! sala={$this->dados['sala_id']}&materia={$this->dados['rm_prof_mat_id']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não foi possível adicionar a sala!</div>";
            $this->resultado = false; 
        }
    }

    private function atualizaSala()
    {
        $this->infoSala();
        $this->dados['situacao'] = 1;
        $this->dados['usuario_mod'] = $_SESSION['colUsuario_id'];
        $upSalaDisc = new \App\adms\Models\helper\AdmsUpdate();
        $upSalaDisc->exeUpdate("rm_professores_materias_salas", $this->dados, "WHERE id=:id", "id={$this->dados['id']}");
        if($upSalaDisc->getResultado()){
            $this->logCodigo = 200;
            $this->logAcao = 3;
            $this->logDescricao = "Sala adicionada com sucesso! sala={$this->dados['sala_id']}&materia={$this->dados['rm_prof_mat_id']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Sala adicionada com sucesso!</div>";
            $this->resultado = true;
        }else{
            $this->logCodigo = 400;
            $this->logAcao = 3;
            $this->logDescricao = "Erro: Não foi possível adicionar a sala! sala={$this->dados['sala_id']}&materia={$this->dados['rm_prof_mat_id']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não foi possível adicionar a sala!</div>";
            $this->resultado = false; 
        }
    }

    private function salvarLog()
    {
        $valLogsalvar = new \App\adms\Models\helper\AdmsSalvarLog();
        $valLogsalvar->salvarLogs(124, $this->logCodigo, $this->logAcao, $this->logDescricao);
    }

}