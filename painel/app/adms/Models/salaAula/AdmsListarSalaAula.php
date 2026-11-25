<?php

namespace App\adms\Models\salaAula;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsListarSalaAula
{
    private $resultado;

    public function listSalaAula()
    {
        $listPaginas = new \App\adms\Models\helper\AdmsRead();
        $listPaginas->fullRead("SELECT id, nome, capacidade_max, IFNULL((SELECT p.nome FROM rm_polos p WHERE p.id = rm_salas.codfilial), '—') as filial FROM rm_salas");
        $this->resultado = $listPaginas->getResultado();
        return $this->resultado;
    }

}