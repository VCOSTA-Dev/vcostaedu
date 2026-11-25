<?php

namespace App\adms\Models\acesso;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}
class AdmsLogin
{
    private $dados;
    private $logDescricao;
    private $logCodigo;
    private $resultado;
    private $resultUser;

    function getResultado()
    {
        return $this->resultado;
    }

    public function login(array $dados)
    {
        $this->dados = $dados;

        $valCampoVazio = new \App\adms\Models\helper\AdmsValCampoVazio();
        $valCampoVazio->validarDados($this->dados);
        if($valCampoVazio->getResultado()){
            $this->validarUsuario();
        }else{
            $this->resultado = false;
        }
    }

    private function validarUsuario()
    {
        /* E7 (D-73): limite de tentativas por identificador e por IP, antes de consultar a conta */
        $espera = \App\adms\Models\helper\AdmsLimiteTentativas::espera('login_painel', (string) $this->dados['usuario']);
        if ($espera > 0) {
            \App\adms\Models\helper\AdmsLimiteTentativas::logBloqueio('login_painel', (string) $this->dados['usuario'], $espera);
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible' id='loginEspera'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>" . \App\adms\Models\helper\AdmsLimiteTentativas::mensagem($espera) . '</div>';
            $this->resultado = false;
            return;
        }
        $validaLogin = new \App\adms\Models\helper\AdmsRead();
        $validaLogin->fullRead("SELECT u.id, u.nome, u.email, u.senha, u.imagem, u.adms_niveis_acesso_id, u.adms_sits_usuario_id, nivac.ordem ordem_nivac, nivac.nome nome_nivac FROM adms_usuarios u INNER JOIN adms_niveis_acessos nivac ON nivac.id=u.adms_niveis_acesso_id WHERE u.usuario =:usuario OR u.email=:email LIMIT :limit", "usuario=" . urlencode((string) $this->dados['usuario']) . "&email=" . urlencode((string) $this->dados['usuario']) . "&limit=1");
        $this->resultUser = $validaLogin->getResultado();
        if (!empty($this->resultUser)) {
            $this->validarSenha();
        } else {
            $this->logCodigo = 400;
            $this->logDescricao = 'Falha na tentativa de login: usuário inexistente (identificador não registrado).';
            $this->salvarLog();
            password_verify((string) $this->dados['senha'], '$2y$10$x1mTqNqndU4u9NOyqHkHDOo8v9LdwDFOFE14tXt4/g9NgHn2koI2S'); // E7: mesmo tempo de resposta
            $this->falhaGenerica();
        }
    }
    
    private function validarSenha()
    {
        if (password_verify($this->dados['senha'], $this->resultUser[0]['senha'])) {
            /* 1.18.4 (D-503): "Atualizar Dados" (2) entra e é levado à tela de atualização (AdmsAtualizarDados::portao);
               Inativo, Bloqueado e Apagado continuam recusados como antes */
            if(in_array((int) $this->resultUser[0]['adms_sits_usuario_id'], [1, \App\adms\Models\helper\AdmsAtualizarDados::SITUACAO], true)){
                /* VCOSTAEDU (Etapa 2): identificador de sessão novo a cada login — fecha a fixação de sessão */
                session_regenerate_id(true);
                $_SESSION['colUsuario_id'] = $this->resultUser[0]['id'];
                $_SESSION['colUsuario_nome'] = $this->resultUser[0]['nome'];
                $_SESSION['colUsuario_email'] = $this->resultUser[0]['email'];
                $_SESSION['colUsuario_imagem'] = $this->resultUser[0]['imagem'];
                $_SESSION['colUsuario_nivel'] = $this->resultUser[0]['adms_niveis_acesso_id'];
                $_SESSION['colUsuario_situacao'] = $this->resultUser[0]['adms_sits_usuario_id'];
                $_SESSION['colUsuario_ordem'] = $this->resultUser[0]['ordem_nivac'];
                $_SESSION['colUsuario_nivel_nome'] = $this->resultUser[0]['nome_nivac']; // VCOSTAEDU (Etapa 3): o rail mostra o perfil
                \App\adms\Models\helper\AdmsLimiteTentativas::zerar('login_painel', (string) $this->dados['usuario']);
                $this->logCodigo = 200;
                $this->logDescricao = "Usuário acessou o sistema: {$this->resultUser[0]['id']} - {$this->resultUser[0]['nome']}!";
                $this->salvarLog();
                $this->resultado = true;
            }else{
                $this->logCodigo = 300;
                $this->logDescricao = "Falha na tentativa de login do usuário {$this->resultUser[0]['id']} - {$this->resultUser[0]['nome']}! Acesso Negado.";
                $this->salvarLog();
                $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Usuário bloqueado!</div>";
                $this->resultado = false;
            }
        } else {
            $this->logCodigo = 400;
            $this->logDescricao = "Falha na tentativa de login do usuário {$this->resultUser[0]['id']}: senha incorreta.";
            $this->salvarLog();
            $this->falhaGenerica();
        }
    }

    /** E7: a MESMA mensagem para usuário inexistente e senha errada; conta a falha e, passado o limite, avisa a espera. */
    private function falhaGenerica(): void
    {
        $espera = \App\adms\Models\helper\AdmsLimiteTentativas::registrar('login_painel', (string) $this->dados['usuario']);
        $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible' id='loginFalha'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Usuário ou senha incorretos." . ($espera > 0 ? ' ' . \App\adms\Models\helper\AdmsLimiteTentativas::mensagem($espera) : '') . '</div>';
        $this->resultado = false;
    }

    private function salvarLog()
    {
        $valLogsalvar = new \App\adms\Models\helper\AdmsSalvarLog();
        $valLogsalvar->salvarLogs(1, $this->logCodigo, 7, $this->logDescricao);
    }
}