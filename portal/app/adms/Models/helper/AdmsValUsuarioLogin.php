<?php

namespace App\adms\Models\helper;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsValUsuarioLogin
{
    private $username;
    private $email;
    private $edit;
    private $id;
    private $resultado;
    private $resultadoBd;

    function getResultado()
    {
        return $this->resultado;
    }

    public function validarLoginUsuario($username, $edit = null, $id = null)
    {
        $this->username = $username;
        $this->edit = $edit;
        $this->id = $id;

        $verEmailunico = new \App\adms\Models\helper\AdmsRead();
        if(($this->edit == true) AND (!empty($this->id))){
            $verEmailunico->fullRead("SELECT id FROM col_usuarios WHERE usuario=:usuario AND id <>:id LIMIT :limit" , "usuario=" . urlencode((string) $this->username) . "&id={$this->id}&limit=1");
        }else{
            $verEmailunico->fullRead("SELECT id FROM col_usuarios WHERE usuario=:usuario LIMIT :limit" , "usuario=" . urlencode((string) $this->username) . "&limit=1");
        }
        $this->resultadoBd = $verEmailunico->getResultado();
        
        if(!$this->resultadoBd){
            $this->resultado = true;
        }else{
            if(!empty($this->id)){
                $_SESSION['msg'] = "<div class='alert alert-danger'>Erro: Usuario de acesso já está cadastrado no sistema e pertence a outro usuário!<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button></div>";
            }else{
                $_SESSION['msg'] = "<div class='alert alert-danger'>Erro: Usuario de acesso já está cadastrado no sistema!<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button></div>";
            }
            $this->resultado = false;
        }

    }
    
}
?>