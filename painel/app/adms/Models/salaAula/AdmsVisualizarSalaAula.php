<?php

namespace App\adms\Models\salaAula;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsVisualizarSalaAula
{
    private $dadosId;
    private $resultado;

    public function verSalaAula($dadosId)
    {
        $this->dadosId = (int) $dadosId;
        $listPaginas = new \App\adms\Models\helper\AdmsRead();
        $listPaginas->fullRead("SELECT id, nome, capacidade_max, IFNULL((SELECT p.nome FROM rm_polos p WHERE p.id = rm_salas.codfilial), '—') as filial, DATE_FORMAT(created, '%d/%m/%Y') as criacao, DATE_FORMAT(modified, '%d/%m/%Y') as modificado FROM rm_salas WHERE id=:id LIMIT :limit", "id={$this->dadosId}&limit=1");
        $this->resultado = $listPaginas->getResultado();
        return $this->resultado;
    }

}