<?php

namespace App\adms\Models\acesso;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}
class AdmsEsqueceuSenha
{
    private $dados;
    private $logCodigo;
    private $logDescricao;
    private $emailDados;
    private $resultado;
    private $resultUser;
    private $dadosUsuario;

    function getResultado()
    {
        return $this->resultado;
    }

    public function recuperarLogin(array $dados)
    {
        $this->dados = $dados;
        /* E7 (D-73): cada pedido conta, por e-mail e por IP */
        $espera = \App\adms\Models\helper\AdmsLimiteTentativas::espera('esqueci_painel', (string) ($dados['email'] ?? ''));
        if ($espera > 0) {
            \App\adms\Models\helper\AdmsLimiteTentativas::logBloqueio('esqueci_painel', (string) ($dados['email'] ?? ''), $espera);
            $_SESSION['msg'] = "<div class='alert alert-warning alert-dismissible' id='esqueciEspera'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>" . \App\adms\Models\helper\AdmsLimiteTentativas::mensagem($espera) . '</div>';
            $this->resultado = false;
            return;
        }
        \App\adms\Models\helper\AdmsLimiteTentativas::registrar('esqueci_painel', (string) ($dados['email'] ?? ''));
        $valCampoVazio = new \App\adms\Models\helper\AdmsValCampoVazio();
        $valCampoVazio->validarDados($this->dados);
        if($valCampoVazio->getResultado()){
            $this->validarDados();
        }else{
            $this->resultado = false;
        }
        
    }

    private function validarDados()
    {
        $valEmail = new \App\adms\Models\helper\AdmsValEmail();
        $valEmail->validarEmail($this->dados['email']);
        if($valEmail->getResultado()){
            $this->verificaEmail();
        }else{
            $this->resultado = false;
        }   
    }

    private function verificaEmail()
    {

        $verificaEmail = new \App\adms\Models\helper\AdmsRead();
        $verificaEmail->fullRead("SELECT id, nome, usuario, email, recuperar_senha FROM adms_usuarios WHERE email=:email LIMIT :limit", "email=" . urlencode((string) $this->dados['email']) . "&limit=1");
        $this->resultUser = $verificaEmail->getResultado();
        if(isset($this->resultUser[0]['id']) && !empty($this->resultUser[0]['id'])){
            $this->valChaveRecSenha();
        }else{
            $this->logCodigo = 400;
            $this->logDescricao = 'Pedido de recuperação de senha para e-mail não cadastrado (e-mail não registrado).';
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-info alert-dismissible' id='esqueciEnviado'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Se o e-mail estiver cadastrado e ativo, enviamos um link para definir a nova senha. Confira a caixa de entrada e o spam.</div>"; // E7: a mesma resposta, exista ou não o e-mail
            $this->resultado = true;
        }

    }

    private function valChaveRecSenha()
    {
        $this->dadosUsuario['recuperar_senha'] = bin2hex(random_bytes(32)); // E7: era md5(id + data e hora), adivinhável
        $this->dadosUsuario['modified'] = date('Y-m-d H:i:s');
        $updateRecSenha = new \App\adms\Models\helper\AdmsUpdate();
        $updateRecSenha->exeUpdate("adms_usuarios", $this->dadosUsuario, "WHERE id =:id", "id={$this->resultUser[0]['id']}");
        if ($updateRecSenha->getResultado()) {
            $this->enviarEmail();
        } else {
            $this->logCodigo = 400;
            $this->logDescricao = "Error: Não foi possível fazer a recuperação da senha! id={$this->resultUser[0]['id']};nome={$this->resultUser[0]['nome']};email={$this->dados['email']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não foi possível registrar a recuperação da senha!</div>";
            $this->resultado = false;
        }
    }

    private function enviarEmail()
    {
        $this->emailHtml();
        $sendEmail = new \App\adms\Models\helper\AdmsSendPhpMailer();
        $sendEmail->enviarEmail($this->emailDados,3);
        if($sendEmail->getResultado()){
            $this->logCodigo = 200;
            $this->logDescricao = "E-mail enviado com sucesso, verifique sua caixa de entrada! id={$this->resultUser[0]['id']};nome={$this->resultUser[0]['nome']};email={$this->dados['email']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-info alert-dismissible' id='esqueciEnviado'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Se o e-mail estiver cadastrado e ativo, enviamos um link para definir a nova senha. Confira a caixa de entrada e o spam.</div>";
            $this->resultado = true; 
        }else{
            $this->logCodigo = 300;
            $this->logDescricao = "Error: O sistema do mailer falhou no envio! id={$this->resultUser[0]['id']};nome={$this->resultUser[0]['nome']};email={$this->dados['email']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-info alert-dismissible' id='esqueciEnviado'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Se o e-mail estiver cadastrado e ativo, enviamos um link para definir a nova senha. Confira a caixa de entrada e o spam.</div>";
            $this->resultado = true; 
        }
    }

    private function emailHtml()
    {
        $name = explode(" ", $this->resultUser[0]['nome']);
        $primeiroNome = $name[0];

        $this->emailDados['toEmail'] = $this->resultUser[0]['email'];
        $this->emailDados['toName'] = $primeiroNome;
        $this->emailDados['toSubject'] = "Recuperação de Senha.";

        $this->emailDados['contentHtml'] = "Olá, {$primeiroNome}<br><br>";
        $this->emailDados['contentHtml'] .= "Você solicitou uma alteração de senha.<br>";
        $this->emailDados['contentHtml'] .= "Seguindo o link abaixo você poderá alterar sua senha.<br>";
        $this->emailDados['contentHtml'] .= "Para continuar o processo de recuperação de sua senha, clique no link abaixo ou cole o endereço abaixo no seu navegador.<br><br>";
        $this->emailDados['contentHtml'] .= "<a href='" . URLADM . "login/definir-senha#t={$this->dadosUsuario['recuperar_senha']}'>Clique aqui</a><br><br>"; // E7 (D-70): token no fragmento
        $this->emailDados['contentHtml'] .= "Se você não solicitou este código, poderá ignorar com segurança este email. Outra pessoa pode ter digitado seu endereço de email por engano.<br><br>";
        
        $this->emailDados['contentText'] = "Olá, {$primeiroNome}.\n\n Você solicitou uma alteração de senha.\n Seguindo o link abaixo você poderá alterar sua senha. Para continuar o processo de recuperação de sua senha, clique no link abaixo ou cole o endereço abaixo no seu navegador.\n\n " . URLADM . "login/definir-senha#t={$this->dadosUsuario['recuperar_senha']}\n Usuário: {$primeiroNome}.\n\n Se você não solicitou essa alteração, nenhuma ação é necessária. Sua senha permanecerá a mesma até que você ative este código.";

    }

    private function salvarLog()
    {
        $valLogsalvar = new \App\adms\Models\helper\AdmsSalvarLog();
        $valLogsalvar->salvarLogs(28, $this->logCodigo, 3, $this->logDescricao);
    }

}