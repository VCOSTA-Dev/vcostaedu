<?php

namespace App\adms\Models\cursos;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsEditarUsuario
{
    private $dados;
    private $logAcao;
    private $logCodigo;
    private $logDescricao;
    private $resultado;

    function getResultado()
    {
        return $this->resultado;
    }

    public function editCoordenador(array $dados)
    {
        $this->dados = $dados;
        $this->altCoordenador();

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
            $this->logDescricao = "Coordenador atualizado com sucesso! id={$this->dados['id']}&curso={$this->dados['curso_id']}&usuario={$this->dados['usuario_id']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Coordenador atualizado com sucesso!</div>";
            $this->resultado = true; 
        }else{
            $this->logCodigo = 400;
            $this->logAcao = 3;
            $this->logDescricao = "ERRO: Não foi possível atualizar o coordenador! id={$this->dados['id']}&curso={$this->dados['curso_id']}&usuario={$this->dados['usuario_id']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>ERRO: Não foi possível atualizar o coordenador!</div>";
            $this->resultado = false;
        }

    }

    private function salvarLog()
    {
        $valLogsalvar = new \App\adms\Models\helper\AdmsSalvarLog();
        $valLogsalvar->salvarLogs(138, $this->logCodigo, $this->logAcao, $this->logDescricao);
    }

}