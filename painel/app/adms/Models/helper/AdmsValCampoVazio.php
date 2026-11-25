<?php

namespace App\adms\Models\helper;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsValCampoVazio
{
    private $dados;
    private $resultado;

    function getResultado()
    {
        return $this->resultado;
    }

    public function validarDados(array $dados)
    {
        $this->dados = $dados;
        $this->dados = array_map('strip_tags', $this->dados);
        $this->dados = array_map('trim', $this->dados);

        if(in_array('', $this->dados)){
            $_SESSION['msg'] = "<div class='alert alert-danger'>Erro: Existem campos vazios. Necessário preencher todos os campos obrigatórios!<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button></div>";
            $this->resultado = false;
        }else{
            $this->resultado = true;
        }

    }
}
?>