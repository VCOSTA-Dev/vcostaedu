<?php

namespace App\adms\Models\acesso;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsAtualizaSenha
{
    private $dados;
    private $dadosId;
    private $resultado;
    private $logCodigo;
    private $logDescricao;
    private $dadosUsuario;

    function getResultado()
    {
        return $this->resultado;
    }

    public function checarPermissao($dadosId)
    {
        $this->dadosId = (string) $dadosId;
        $verificaToken = new \App\adms\Models\helper\AdmsRead();
        $verificaToken->fullRead("SELECT recuperar_senha FROM adms_usuarios WHERE recuperar_senha=:recuperar_senha LIMIT :limit", "recuperar_senha=" . urlencode((string) $this->dadosId) . "&limit=1");
        $this->resultado = $verificaToken->getResultado();
          
        return $this->resultado;

    }

    public function trocarSenha(array $dados)
    {
        $this->dados = $dados;
        $campoVazio = new \App\adms\Models\helper\AdmsValCampoVazio();
        $campoVazio->validarDados($this->dados);
        if($campoVazio->getResultado()){
            $validarSenha = new \App\adms\Models\helper\AdmsValSenha();
            $this->localizaId();
            $validarSenha->valSenha($this->dados['senha'], \App\adms\Models\helper\AdmsValSenha::pessoaisDoUsuario((int) ($this->dadosUsuario[0]['id'] ?? 0)));
            if($validarSenha->getResultado()){
                $this->updateAtualSenha();
            }else{
                $this->resultado = false;
            }
        }else{
            $this->resultado = false;
        }
        
    }

    private function localizaId()
    {
        $verificaToken = new \App\adms\Models\helper\AdmsRead();
        $verificaToken->fullRead("SELECT id FROM adms_usuarios WHERE recuperar_senha=:recuperar_senha LIMIT :limit", "recuperar_senha=" . urlencode((string) $this->dados['recuperar_senha']) . "&limit=1");
        $this->dadosUsuario = $verificaToken->getResultado();
    }
    
    private function updateAtualSenha()
    {
        $this->localizaId();
        if (!empty($this->dadosUsuario[0]['id'])){
            /* E7: grava SÓ estes campos. Antes gravava o formulário inteiro (outro campo postado, como o nível de
               acesso, iria para adms_usuarios). */
            $this->dados = ['recuperar_senha' => null, 'senha' => password_hash((string) $this->dados['senha'], PASSWORD_DEFAULT), 'usuario_mod' => $this->dadosUsuario[0]['id'], 'modified' => date('Y-m-d H:i:s')];
            $updateNovaSenha = new \App\adms\Models\helper\AdmsUpdate();
            $updateNovaSenha->exeUpdate("adms_usuarios", $this->dados, "WHERE id =:id", "id={$this->dadosUsuario[0]['id']}");
            if ($updateNovaSenha->getResultado()) {
                $this->logCodigo = 400;
                $this->logDescricao = "Senha alterada com sucesso! id={$this->dadosUsuario[0]['id']};";
                $this->salvarLog();
                $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Senha atualizada com sucesso!</div>";
                $this->resultado = true;
            } else {
                $this->logCodigo = 400;
                $this->logDescricao = "Error: Não foi possível atualizar a senha! id={$this->dadosUsuario[0]['id']};";
                $this->salvarLog();
                $_SESSION['msg'] = "<div class='alert alert-danger'>Erro: A senha não foi atualizada!</div>";
                $this->resultado = false;
            }
        } else {
            $this->logCodigo = 400;
            $this->logDescricao = "Error: Usuário não encontrado para recuperar a senha! codigo={$this->dados['recuperar_senha']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-danger'>Erro: A senha não pode ser atualizada!</div>";
            $this->resultado = false;
        }
    }

    private function salvarLog()
    {
        $valLogsalvar = new \App\adms\Models\helper\AdmsSalvarLog();
        $valLogsalvar->salvarLogs(35, $this->logCodigo, 3, $this->logDescricao);
    }
    
}