<?php

namespace App\adms\Models\salaAula;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsCadastrarSala
{
    private $dados;
    private $logCodigo;
    private $logDescricao;
    private $resultado;

    function getResultado()
    {
        return $this->resultado;
    }

    public function cadSalas(array $dados)
    {
        $this->dados = $dados;
        $this->addSala();
    }
    
    private function addSala()
    {
        $this->dados['usuario_cre'] = $_SESSION['colUsuario_id'];
        $this->dados['situacao'] = 1;
        $addSa = new \App\adms\Models\helper\AdmsCreate();
        $addSa->exeCreate("rm_salas", $this->dados);
        if($addSa->getResultado()){
            $this->logCodigo = 200;
            $this->logDescricao = "Sala cadastrada com sucesso! nome={$this->dados['nome']}&capacidade={$this->dados['capacidade_max']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Sala cadastrada com sucesso!</div>";
            $this->resultado = true; 
        }else{
            $this->logCodigo = 400;
            $this->logDescricao = "Error: não foi possível cadastrar a sala! nome={$this->dados['nome']}&capacidade={$this->dados['capacidade_max']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: não foi possível cadastrar a sala!</div>";
            $this->resultado = false; 
        }
    }

    private function salvarLog()
    {
        $valLogsalvar = new \App\adms\Models\helper\AdmsSalvarLog();
        $valLogsalvar->salvarLogs(120, $this->logCodigo, 2, $this->logDescricao);
    }

}