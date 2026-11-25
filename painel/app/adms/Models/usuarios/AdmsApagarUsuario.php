<?php

namespace App\adms\Models\usuarios;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsApagarUsuario
{
    private $dados;
    private $logCodigo;
    private $logDescricao;
    private $resultado;

    function getResultado()
    {
        return $this->resultado;
    }

    public function deletarUsuario(array $dados)
    {
        /* 1.18.3 (D-497): só o id, e só de usuário ABAIXO do seu (nunca o próprio) */
        $this->dados = ['id' => (int) ($dados['id'] ?? 0)];
        if (!\App\adms\Models\helper\AdmsHierarquia::podeAlterarUsuario($this->dados['id'])) {
            \App\adms\Models\helper\AdmsHierarquia::recusar();
            $this->resultado = false;
            return;
        }
        $this->desligaUser();
    }
    
    private function desligaUser()
    {
        $this->dados['adms_sits_usuario_id'] = 5;
        $this->dados['usuario_mod'] = $_SESSION['colUsuario_id'];
        $this->dados['modified'] = date("Y-m-d H:i:s");
        $altPg = new \App\adms\Models\helper\AdmsUpdate();
        $altPg->exeUpdate("adms_usuarios", $this->dados, "WHERE id=:id", "id={$this->dados['id']}");
        if($altPg->getResultado()){
            $this->logCodigo = 200;
            $this->logDescricao = "Usuário apagado com sucesso! id={$this->dados['id']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Usuário apagado com sucesso!</div>";
            $this->resultado = true;
        }else{
            $this->logCodigo = 400;
            $this->logDescricao = "Error: Não foi possível apagar o usuário! id={$this->dados['id']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>ERRO: Não foi possível apagar o usuário!</div>";
            $this->resultado = false;
        }
    }

    private function salvarLog()
    {
        $valLogsalvar = new \App\adms\Models\helper\AdmsSalvarLog();
        $valLogsalvar->salvarLogs(12, $this->logCodigo, 4, $this->logDescricao);
    }

}