<?php

namespace App\adms\Models\perfil;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsEditarSenhaPerfil
{
    private $dados;
    private $logCodigo;
    private $logDescricao;
    private $resultado;

    function getResultado()
    {
        return $this->resultado;
    }

    public function altSenhaPerfil(array $dados)
    {
        /* 1.18.3 (D-498): só a senha (antes o POST inteiro ia para o UPDATE) */
        $this->dados = array_intersect_key($dados, array_flip(['senha']));
        $valCampoVazio = new \App\adms\Models\helper\AdmsValCampoVazio();
        $valCampoVazio->validarDados($this->dados);
        /* Onda 2: a regra da senha é a única do sistema (core/Senha.php) — antes esta tela não conferia nada. */
        $valSenha = new \App\adms\Models\helper\AdmsValSenha();
        if($valCampoVazio->getResultado()
            && $valSenha->valSenha((string) $this->dados['senha'], \App\adms\Models\helper\AdmsValSenha::pessoaisDoUsuario((int) $_SESSION['colUsuario_id']))){
            $this->altUsuario();
        }else{
            $this->resultado = false;
        }
    }

    private function altUsuario()
    {
        $this->dados['usuario_mod'] = $_SESSION['colUsuario_id'];
        $this->dados['senha'] = password_hash($this->dados['senha'], PASSWORD_DEFAULT);
        $this->dados['modified'] = date("Y-m-d H:i:s");
        $altPg = new \App\adms\Models\helper\AdmsUpdate();
        $altPg->exeUpdate("adms_usuarios", $this->dados, "WHERE id=:id", "id={$_SESSION['colUsuario_id']}");
        if($altPg->getResultado()){
            $this->logCodigo = 200;
            $this->logDescricao = "Senha alterada com sucesso! id={$_SESSION['colUsuario_id']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Senha alterada com sucesso!</div>";
            $this->resultado = true; 
        }else{
            $this->logCodigo = 400;
            $this->logDescricao = "Error: Não foi possível alterar a senha! id={$_SESSION['colUsuario_id']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não foi possível alterar a senha!</div>";
            $this->resultado = false;
        }

    } 

    private function salvarLog()
    {
        $valLogsalvar = new \App\adms\Models\helper\AdmsSalvarLog();
        $valLogsalvar->salvarLogs(39, $this->logCodigo, 3, $this->logDescricao);
    }

}