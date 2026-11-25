<?php

namespace App\adms\Models\configEmail;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsListarConfigEmail
{
    private $resultado;

    public function listEmails()
    {
        
        $listEmails = new \App\adms\Models\helper\AdmsRead();
        $listEmails->fullRead("SELECT id, titulo, nome, email FROM adms_confs_emails");
        $this->resultado = $listEmails->getResultado();

        return $this->resultado;

    }

}