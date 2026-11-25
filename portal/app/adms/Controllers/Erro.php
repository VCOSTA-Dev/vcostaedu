<?php
namespace App\adms\Controllers;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class Erro
{
    private $dados;
    
    public function index()
    {
        $carregarView = new \Core\ConfigView("adms/Views/erro/pgErro", $this->dados);
        $carregarView->renderizar('tabler');
    }
}

?>