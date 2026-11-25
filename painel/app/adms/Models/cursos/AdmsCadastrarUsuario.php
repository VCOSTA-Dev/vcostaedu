<?php

namespace App\adms\Models\cursos;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsCadastrarUsuario
{
    private $dados;
    private $ordem;
    private $logAcao;
    private $logCodigo;
    private $logDescricao;
    private $resultado;

    function getResultado()
    {
        return $this->resultado;
    }

    public function cadCoordenador(array $dados)
    {
        $this->dados = $dados;
        $this->checarDuplicidade();

    }

    private function checarDuplicidade()
    {
        $checarCoord = new \App\adms\Models\helper\AdmsRead();
        $checarCoord->fullRead("SELECT id, situacao FROM rm_cursos_coordenacao WHERE usuario_id =:usuario AND curso_id =:curso LIMIT :limit", "usuario={$this->dados['usuario_id']}&curso={$this->dados['curso_id']}&limit=1");
        $sitCoord = $checarCoord->getResultado();
        if(isset($sitCoord[0]['id']) && !empty($sitCoord[0]['id'])){
            if($sitCoord[0]['situacao'] == 2){
                $this->dados['id'] = $sitCoord[0]['id'];
                $this->altCoordenador();
            }else{
                $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: O coordenador já está neste curso!</div>";
                $this->resultado = false;
            }
        }else{
            $this->addCoordenador();
        }

    }

    private function addCoordenador()
    {
        $this->dados['usuario_cre'] = $_SESSION['colUsuario_id'];
        $this->dados['situacao'] = 1;
        $addItMenu = new \App\adms\Models\helper\AdmsCreate();
        $addItMenu->exeCreate("rm_cursos_coordenacao", $this->dados);
        if($addItMenu->getResultado()){
            $identificador = $addItMenu->getResultado();
            $this->logCodigo = 200;
            $this->logAcao = 2;
            $this->logDescricao = "Coordenador adicionado com sucesso! id={$identificador}&curso={$this->dados['curso_id']}&usuario={$this->dados['usuario_id']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Coordenador adicionado com sucesso!</div>";
            $this->resultado = true; 
        }else{
            $this->logCodigo = 400;
            $this->logAcao = 2;
            $this->logDescricao = "ERRO: Não foi possível adicionar o coordenador! curso={$this->dados['curso_id']}&usuario={$this->dados['usuario_id']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não foi possível adicionar o coordenador!</div>";
            $this->resultado = false; 
        }
    }

    private function altCoordenador()
    {
        $this->dados['usuario_mod'] = $_SESSION['colUsuario_id'];
        $this->dados['situacao'] = 1;
        $this->dados['modified'] = date("Y-m-d H:i:s");
        $altPg = new \App\adms\Models\helper\AdmsUpdate();
        $altPg->exeUpdate("rm_cursos_coordenacao", $this->dados, "WHERE id=:id", "id={$this->dados['id']}");
        if($altPg->getResultado()){
            $this->logCodigo = 200;
            $this->logAcao = 3;
            $this->logDescricao = "Coordenador adicionado com sucesso! id={$this->dados['id']}&curso={$this->dados['curso_id']}&usuario={$this->dados['usuario_id']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Coordenador adicionado com sucesso!</div>";
            $this->resultado = true; 
        }else{
            $this->logCodigo = 400;
            $this->logAcao = 3;
            $this->logDescricao = "ERRO: Não foi possível adicionar o coordenador! id={$this->dados['id']}&curso={$this->dados['curso_id']}&usuario={$this->dados['usuario_id']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>ERRO: Não foi possível adicionar o coordenador!</div>";
            $this->resultado = false;
        }

    }

    private function salvarLog()
    {
        $valLogsalvar = new \App\adms\Models\helper\AdmsSalvarLog();
        $valLogsalvar->salvarLogs(107, $this->logCodigo, $this->logAcao, $this->logDescricao);
    }

}