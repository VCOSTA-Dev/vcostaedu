<?php

namespace App\adms\Models\configEmail;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsApagarConfigEmail
{
    private $dados;
    private $infoEmail;
    private $logCodigo;
    private $logDescricao;
    private $resultado;

    function getResultado()
    {
        return $this->resultado;
    }

    public function delConfigEmail(array $dados)
    {
        $this->dados = $dados;
        $this->configEmail();
    }

    private function configEmail()
    {
        $verConfigEmail = new \App\adms\Models\helper\AdmsRead();
        $verConfigEmail->fullRead("SELECT id, titulo, nome, email FROM adms_confs_emails WHERE id=:id LIMIT :limit", "id={$this->dados['id']}&limit=1");
        $this->infoEmail = $verConfigEmail->getResultado();
        if(isset($this->infoEmail[0]['id']) && !empty($this->infoEmail[0]['id'])){
            $this->desligaConfigEmail();
        }else{
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>ERRO: Não foi possível apagar a configuração do e-mail!</div>";
            $this->resultado = false;
        }
    }

    private function desligaConfigEmail()
    {
        $delNivelAcesso = new \App\adms\Models\helper\AdmsDeletar();
        $delNivelAcesso->exeDelete("adms_confs_emails", "WHERE id=:id ", "id={$this->dados['id']}");
        if ($delNivelAcesso->getResultado()) {
            $this->logCodigo = 200;
            $this->logDescricao = "Configuração de e-mail apagada com sucesso! id={$this->dados['id']};nome={$this->infoEmail[0]['nome']};email={$this->infoEmail[0]['email']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Configuração de e-mail apagada com sucesso!</div>";
            $this->resultado = true;
        } else {
            $this->logCodigo = 400;
            $this->logDescricao = "Error: Não foi possível apagar a configuração de e-mail! id={$this->dados['id']};nome={$this->infoEmail[0]['nome']};email={$this->infoEmail[0]['email']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>ERRO:  Não foi possível apagar a configuração de e-mail!</div>";
            $this->resultado = false;
        }
    }

    private function salvarLog()
    {
        $valLogsalvar = new \App\adms\Models\helper\AdmsSalvarLog();
        $valLogsalvar->salvarLogs(33, $this->logCodigo, 4, $this->logDescricao);
    }

}