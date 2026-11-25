<?php

namespace App\adms\Models\configEmail;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsCadastrarConfigEmail
{
    private $dados;
    private $logCodigo;
    private $logDescricao;
    private $resultado;

    function getResultado()
    {
        return $this->resultado;
    }

    public function cadConfigEmail(array $dados)
    {
        $this->dados = $dados;
        $valCampoVazio = new \App\adms\Models\helper\AdmsValCampoVazio();
        $valCampoVazio->validarDados($this->dados);
        if($valCampoVazio->getResultado()){
            $this->valInput();
        }else{
            $this->resultado = false;
        }

    }

    private function valInput()
    {
        $valEmail = new \App\adms\Models\helper\AdmsValEmail();
        $valEmail->validarEmail($this->dados['email']);
        if($valEmail->getResultado()){
            $this->addConfiguracao();
        }else{
            $this->resultado = false;
        }

    }

    private function addConfiguracao()
    {
        $addUsuario = new \App\adms\Models\helper\AdmsCreate();
        $addUsuario->exeCreate("adms_confs_emails", $this->dados);
        if($addUsuario->getResultado()){
            $this->logCodigo = 200;
            $this->logDescricao = "Configuração cadastrada com sucesso! nome={$this->dados['nome']};email={$this->dados['email']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Configuração cadastrada com sucesso!</div>";
            $this->resultado = true; 
        }else{
            $this->logCodigo = 400;
            $this->logDescricao = "Error: Não foi possível cadastrar a configuração de email! nome={$this->dados['nome']};email={$this->dados['email']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro:Não foi possível cadastrar a configuração!</div>";
            $this->resultado = false; 
        }
    }

    private function salvarLog()
    {
        $valLogsalvar = new \App\adms\Models\helper\AdmsSalvarLog();
        $valLogsalvar->salvarLogs(30, $this->logCodigo, 2, $this->logDescricao);
    }

}