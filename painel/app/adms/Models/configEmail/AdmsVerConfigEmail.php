<?php

namespace App\adms\Models\configEmail;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsVerConfigEmail
{
    private $dadosId;
    private $resultado;

    public function visConfigEmail($dadosId)
    {
        $this->dadosId = (int) $dadosId;
        $verConfigEmai = new \App\adms\Models\helper\AdmsRead();
        $verConfigEmai->fullRead("SELECT id, titulo, nome, email, DATE_FORMAT(created, '%d/%m/%Y') as criacao, DATE_FORMAT(modified, '%d/%m/%Y') as modificado FROM adms_confs_emails WHERE id=:id LIMIT :limit", "id={$this->dadosId}&limit=1");
        $this->resultado = $verConfigEmai->getResultado();

        return $this->resultado;
    }

}