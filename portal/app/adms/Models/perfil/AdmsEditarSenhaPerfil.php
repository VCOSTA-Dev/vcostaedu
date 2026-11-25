<?php

namespace App\adms\Models\perfil;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsEditarSenhaPerfil
{
    private $dados;
    private $dadosUser;
    private $dadoslogs;
    private $resultado;

    function getResultado()
    {
        return $this->resultado;
    }

    public function altSenhaPerfil(array $dados)
    {
        /* Onda 2: só a senha, e só do aluno da sessão (o id vinha do formulário e podia ser trocado — achado herdado);
           a regra da senha é a única do sistema (core/Senha.php). */
        $this->dados = ['id' => (int) ($_SESSION['academico_id'] ?? 0), 'senha' => (string) ($dados['senha'] ?? '')];
        $valCampoVazio = new \App\adms\Models\helper\AdmsValCampoVazio();
        $valCampoVazio->validarDados($this->dados);
        $valSenha = new \App\adms\Models\helper\AdmsValSenha();
        if($valCampoVazio->getResultado() && $this->dados['id'] > 0
            && $valSenha->valSenha($this->dados['senha'], \App\adms\Models\helper\AdmsValSenha::pessoaisDoAluno($this->dados['id']))){
            $this->altUsuario();
        }else{
            $this->resultado = false;
        }
    }

    private function altUsuario()
    {
        $this->dados['senha'] = password_hash($this->dados['senha'], PASSWORD_DEFAULT);
        $this->dados['modified'] = date("Y-m-d H:i:s");
        $altPg = new \App\adms\Models\helper\AdmsUpdate();
        $altPg->exeUpdate("col_usuarios", $this->dados, "WHERE id=:id", "id={$this->dados['id']}");
        if($altPg->getResultado()){
            $this->dadosUser = $this->dados;
            unset($this->dadosUser['senha']);
            $this->dadoslogs['codigo'] = 200;
            $this->dadoslogs['observacao'] = "Senha atualizada com sucesso!";
            $this->dadoslogs['descricao'] = json_encode($this->dadosUser, true);
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Senha atualizada com sucesso!</div>";
            $this->resultado = true; 
        }else{
            $this->dadoslogs['codigo'] = 400;
            $this->dadoslogs['observacao'] = "Não foi possível atualizar a senha!";
            $this->dadoslogs['descricao'] = json_encode($this->dadosUser, true);
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: ao tentar mudar a senha!</div>";
            $this->resultado = false;
        }

    }

    private function salvarLog()
    {
        $this->dadoslogs['pagina_id'] = 8;
        $valLogsalvar = new \App\adms\Models\helper\AdmsSalvarLogs();
        $valLogsalvar->salvarLogs($this->dadoslogs);
    }

}