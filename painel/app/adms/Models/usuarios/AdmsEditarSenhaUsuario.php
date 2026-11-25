<?php

namespace App\adms\Models\usuarios;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsEditarSenhaUsuario
{
    private $dados;
    private $logDescricao;
    private $logCodigo;
    private $resultado;

    function getResultado()
    {
        return $this->resultado;
    }

    public function editSenhaUsuarios(array $dados)
    {
        /* 1.18.3 (D-497/D-498): só id e senha, e só de usuário ABAIXO do seu (a própria senha é pelo Perfil) */
        $this->dados = array_intersect_key($dados, array_flip(['id', 'senha']));
        if (!\App\adms\Models\helper\AdmsHierarquia::podeAlterarUsuario((int) ($this->dados['id'] ?? 0))) {
            \App\adms\Models\helper\AdmsHierarquia::recusar();
            $this->resultado = false;
            return;
        }

        $valCampoVazio = new \App\adms\Models\helper\AdmsValCampoVazio();
        $valCampoVazio->validarDados($this->dados);
        if($valCampoVazio->getResultado()){
            $this->valInput();
        }else{
            $this->resultado = false;
        }
    }

    private function valInput()
    {
        $validSenha = new \App\adms\Models\helper\AdmsValSenha();
        $validSenha->valSenha($this->dados['senha'], \App\adms\Models\helper\AdmsValSenha::pessoaisDoUsuario((int) $this->dados['id']));
        if($validSenha->getResultado()){
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
        $altPg->exeUpdate("adms_usuarios", $this->dados, "WHERE id=:id", "id={$this->dados['id']}");
        if($altPg->getResultado()){
            $this->logCodigo = 200;
            $this->logDescricao = "Senha atualizada com sucesso do usuário! id={$this->dados['id']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Senha trocada com sucesso!</div>";
            $this->resultado = true; 
        }else{
            $this->logCodigo = 400;
            $this->logDescricao = "Error: Não foi possível atualizar a senha do usuário! id={$this->dados['id']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: ao tentar trocar a senha do usuário!</div>";
            $this->resultado = false;
        }

    }

    private function salvarLog()
    {
        $valLogsalvar = new \App\adms\Models\helper\AdmsSalvarLog();
        $valLogsalvar->salvarLogs(38, $this->logCodigo, 3, $this->logDescricao);
    }

}