<?php

namespace App\adms\Models\materias;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsVerMaterias
{
    private $dadosId;
    private $resultado;

    public function visMaterias($dadosId)
    {
        $this->dadosId = (int) $dadosId;
        $verMaterial = new \App\adms\Models\helper\AdmsRead();
        $verMaterial->fullRead("SELECT m.id, m.nome, m.abreviacao, m.carga_horaria, s.nome nomeSituacao, (SELECT c.cor FROM adms_cors c WHERE c.id=s.cor_id) as corSituacao FROM rm_materias m INNER JOIN adms_sits_pgs s ON s.id=m.situacao WHERE m.id=:id LIMIT :limit", "id={$this->dadosId}&limit=1");
        $this->resultado = $verMaterial->getResultado();
        return $this->resultado;
    }

}