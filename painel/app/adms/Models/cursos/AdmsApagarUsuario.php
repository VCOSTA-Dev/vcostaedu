<?php

namespace App\adms\Models\cursos;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsApagarUsuario
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

    public function delCoordenador(array $dados)
    {
        $this->dados = $dados;
        $this->desligaCoordenador();

    }

    private function desligaCoordenador()
    {
        $this->dados['usuario_mod'] = $_SESSION['colUsuario_id'];
        $this->dados['situacao'] = 2;
        $this->dados['modified'] = date("Y-m-d H:i:s");
        $altPg = new \App\adms\Models\helper\AdmsUpdate();
        $altPg->exeUpdate("rm_cursos_coordenacao", $this->dados, "WHERE id=:id", "id={$this->dados['id']}");
        if($altPg->getResultado()){
            $this->logCodigo = 200;
            $this->logAcao = 4;
            $this->logDescricao = "Coordenador desligado com sucesso! id={$this->dados['id']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Coordenador desligado com sucesso!</div>";
            $this->resultado = true; 
        }else{
            $this->logCodigo = 400;
            $this->logAcao = 4;
            $this->logDescricao = "ERRO: Não foi possível desligar o coordenador! id={$this->dados['id']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>ERRO: Não foi possível desligar o coordenador!</div>";
            $this->resultado = false;
        }

    }

    private function salvarLog()
    {
        $valLogsalvar = new \App\adms\Models\helper\AdmsSalvarLog();
        $valLogsalvar->salvarLogs(137, $this->logCodigo, $this->logAcao, $this->logDescricao);
    }

}