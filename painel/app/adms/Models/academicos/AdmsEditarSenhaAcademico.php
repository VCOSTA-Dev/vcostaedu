<?php

namespace App\adms\Models\academicos;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsEditarSenhaAcademico
{
    private $dados;
    private $logCodigo;
    private $logDescricao;
    private $imgAntiga;
    private $resultado;

    function getResultado()
    {
        return $this->resultado;
    }

    public function editPassAcad(array $dados)
    {
        $this->dados = $dados;
        $this->valInput();
    }

    private function valInput()
    {
        $validSenha = new \App\adms\Models\helper\AdmsValSenha();
        $validSenha->valSenha($this->dados['senha'], \App\adms\Models\helper\AdmsValSenha::pessoaisDoAluno((int) ($this->dados['id'] ?? 0)));
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
        $altPg->exeUpdate("col_usuarios", $this->dados, "WHERE id=:id", "id={$this->dados['id']}");
        if($altPg->getResultado()){
            $this->logCodigo = 200;
            $this->logDescricao = "Senha do usuário atualizada com sucesso!--id={$this->dados['id']}&usuario={$this->dados['usuario']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Senha do usuário atualizada com sucesso!</div>";
            $this->resultado = true; 
        }else{
            $this->logCodigo = 400;
            $this->logDescricao = "Error: Não foi possível atualizar a senha do usuário acad!--id={$this->dados['id']}&usuario={$this->dados['usuario']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: ao tentar atualizar a senha do usuário!</div>";
            $this->resultado = false;
        }

    }

    private function salvarLog()
    {
        $valLogsalvar = new \App\adms\Models\helper\AdmsSalvarLog();
        $valLogsalvar->salvarLogs(38, $this->logCodigo, 3, $this->logDescricao);
    }

}