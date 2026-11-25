<?php

namespace App\adms\Models\configEmail;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsEditarConfigEmail
{
    private $dadosId;
    private $dados;
    private $logCodigo;
    private $logDescricao;
    private $resultado;

    function getResultado()
    {
        return $this->resultado;
    }

    public function verConfigEmail($dadosId)
    {
        $this->dadosId = (int) $dadosId;
        $verPagin = new \App\adms\Models\helper\AdmsRead();
        $verPagin->fullRead("SELECT * FROM adms_confs_emails WHERE id=:id", "id={$this->dadosId}");
        $this->resultado = $verPagin->getResultado();

        return $this->resultado;
    }

    public function editConfigEmail(array $dados)
    {
        $this->dados = $dados;
        /* 1.18.2 (D-489): a tela não mostra mais a senha gravada; em branco = manter a atual (não entra no UPDATE) */
        if (trim((string) ($this->dados['senhas'] ?? '')) === '') {
            unset($this->dados['senhas']);
        }
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
            $this->altConfig();
        }else{
            $this->resultado = false;
        }

    }

    private function altConfig()
    {
        $this->dados['modified'] = date("Y-m-d H:i:s");
        $altPg = new \App\adms\Models\helper\AdmsUpdate();
        $altPg->exeUpdate("adms_confs_emails", $this->dados, "WHERE id=:id", "id={$this->dados['id']}");
        if($altPg->getResultado()){
            $this->logCodigo = 200;
            $this->logDescricao = "Configuração de e-mail editada com sucesso! id={$this->dados['id']};nome={$this->dados['nome']};email={$this->dados['email']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Configuração de e-mail editada com sucesso!</div>";
            $this->resultado = true; 
        }else{
            $this->logCodigo = 400;
            $this->logDescricao = "Error: Erro ao tentar editar as configurações de email! id={$this->dados['id']};nome={$this->dados['nome']};email={$this->dados['email']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Erro ao tentar editar as configurações!</div>";
            $this->resultado = false;
        }

    }

    private function salvarLog()
    {
        $valLogsalvar = new \App\adms\Models\helper\AdmsSalvarLog();
        $valLogsalvar->salvarLogs(31, $this->logCodigo, 3, $this->logDescricao);
    }

}