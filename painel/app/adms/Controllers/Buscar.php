<?php

namespace App\adms\Controllers;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class Buscar
{
    private $dados;
    private $url;
    private $dadosId;
    private $dadosForm;
    
    public function turmas($dadosId)
    {
        $this->dadosId = (int) $dadosId;
        if(!empty($this->dadosId)){
            $listBusca = new \App\adms\Models\buscar\AdmsListaBusca();
            $retorna = $listBusca->turmas($this->dadosId);
        }else{
            $retorna = ["status" => false, "lista" => '', "msg" => "<div class='alert alert-danger'>ERRO: Falha na comunicação com o sistema!</div>"];
        }
        echo json_encode($retorna);

    }

    public function turmaserie($dadosId)
    {
        $this->dadosId = (int) $dadosId;
        if(!empty($this->dadosId)){
            $visSerie = new \App\adms\Models\buscar\AdmsListTurmaSerie();
            $retorna = $visSerie->listSerie($this->dadosId);
        }else{
            $retorna = ["status" => false, "dados" => '', "msg" => "<div class='alert alert-danger'>ERRO: Falha na comunicação com o sistema!</div>"];
        }
        echo json_encode($retorna);

    }

}