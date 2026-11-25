<?php

namespace App\adms\Models\salaAula;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsApagarSala
{
    private $dados;
    private $logCodigo;
    private $logDescricao;
    private $infoMenu;
    private $resultado;

    function getResultado()
    {
        return $this->resultado;
    }

    public function delSalaAula(array $dados)
    {
        $this->dados = $dados;
        $this->existeSalaDisciplina();
    }

    private function existeSalaDisciplina()
    {
        $versalaAula = new \App\adms\Models\helper\AdmsRead();
        $versalaAula->fullRead("SELECT id FROM rm_professores_materias_salas WHERE sala_id=:sal", "sal={$this->dados['id']}");
        $dadoSala = $versalaAula->getResultado();
        if(isset($dadoSala[0]['id']) && !empty($dadoSala[0]['id'])){
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>ERRO: Não é possível apagar a sala, pois ele está ligada em algumas disciplinas!</div>";
            $this->resultado = false;
        }else{
            $this->apagarSala();
        }
    }

    private function dadosSalaAula()
    {
        $verItensSala = new \App\adms\Models\helper\AdmsRead();
        $verItensSala->fullRead("SELECT id, nome FROM rm_salas WHERE id=:id LIMIT :limit", "id={$this->dados['id']}&limit=1");
        $this->infoMenu = $verItensSala->getResultado();
    }

    private function apagarSala()
    {
        $this->dadosSalaAula();
        $delSa = new \App\adms\Models\helper\AdmsDeletar();
        $delSa->exeDelete("rm_salas", "WHERE id=:id ", "id={$this->dados['id']}");
        if($delSa->getResultado()){
            $this->logCodigo = 200;
            $this->logDescricao = "Sala apagada com sucesso! id={$this->dados['id']}&nome={$this->infoMenu[0]['nome']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Sala apagada com sucesso!</div>";
            $this->resultado = true;
        }else{
            $this->logCodigo = 400;
            $this->logDescricao = "Error: Não foi possível apagar a sala! id={$this->dados['id']}&nome={$this->infoMenu[0]['nome']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>ERRO: Não foi possível apagar a sala!</div>";
            $this->resultado = false;
        }
    }

    private function salvarLog()
    {
        $valLogsalvar = new \App\adms\Models\helper\AdmsSalvarLog();
        $valLogsalvar->salvarLogs(123, $this->logCodigo, 4, $this->logDescricao);
    }

}