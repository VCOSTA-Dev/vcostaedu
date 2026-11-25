<?php

namespace App\adms\Models\helper;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsValEmail
{
    private $email;
    private $resultado;

    function getResultado()
    {
        return $this->resultado;
    }

    public function validarEmail($email)
    {
        $this->email = (string) $email;

        if(filter_var($this->email, FILTER_VALIDATE_EMAIL)){
            $this->resultado = true;
        }else{
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: E-mail inválido!</div>"; // 1.18.4 (D-505): no alerta vermelho, como os outros erros (antes, texto solto)
            $this->resultado = false;
        }

    }
}

?>