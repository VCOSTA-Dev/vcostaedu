<?php

namespace App\adms\Models\salaAula;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsEditarSala
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

    public function visSala($dadosId)
    {
        $this->dadosId = (int) $dadosId;
        $verSala = new \App\adms\Models\helper\AdmsRead();
        $verSala->fullRead("SELECT * FROM rm_salas WHERE id=:id LIMIT :limit", "id={$this->dadosId}&limit=1");
        $this->resultado = $verSala->getResultado();
        return $this->resultado;
    }

    public function altSalas(array $dados)
    {
        $this->dados = $dados;
        $this->modSala();
    }
    
    private function modSala()
    {
        $this->dados['usuario_mod'] = $_SESSION['colUsuario_id'];
        $altSala = new \App\adms\Models\helper\AdmsUpdate();
        $altSala->exeUpdate("rm_salas", $this->dados, "WHERE id=:id", "id={$this->dados['id']}");
        if($altSala->getResultado()){
            $this->logCodigo = 200;
            $this->logDescricao = "Sala atualizada com sucesso! id={$this->dados['id']}&nome={$this->dados['nome']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Sala atualizada com sucesso!</div>";
            $this->resultado = true; 
        }else{
            $this->logCodigo = 400;
            $this->logDescricao = "Error: Não é possível atualizar sala! id={$this->dados['id']}&nome={$this->dados['nome']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: não foi possível atualizar a sala!</div>";
            $this->resultado = false;
        }
    }

    private function salvarLog()
    {
        $valLogsalvar = new \App\adms\Models\helper\AdmsSalvarLog();
        $valLogsalvar->salvarLogs(122, $this->logCodigo, 3, $this->logDescricao);
    }

}