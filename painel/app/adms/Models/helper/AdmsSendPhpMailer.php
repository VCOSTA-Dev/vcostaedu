<?php

namespace App\adms\Models\helper;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsSendPhpMailer
{

    private $dados;
    private $dadosInfoEmail;
    private $resultado;
    private $fromEmail;
    private $optionConfEmail;

    function getResultado()
    {
        return $this->resultado;
    }

    public function enviarEmail(array $dados, $optionConfEmail)
    {
        $this->optionConfEmail = (int) $optionConfEmail;
        $this->dados = $dados;
        // $this->dados['toEmail'] = 'suporte@exemplo.invalid';
        // $this->dados['toName'] = 'Marcelo';
        // $this->dados['toSubject'] = 'Confirmar email';
        // $this->dados['contentHtml'] = 'Olá cadastro realizado com sucesso!';
        // $this->dados['contentText'] = 'Olá \n\nCadastro realizado com sucesso!\n';
        $this->infoPhpMailer();
        /* E7: sem configuração de e-mail, não tenta enviar (antes: avisos de PHP e a página diferente) */
        if (empty($this->dadosInfoEmail[0]['host'])) {
            $this->resultado = false;
            return;
        }
        $this->sendEmailPhpMailer();
    }

    private function infoPhpMailer()
    {
        $confEmail = new \App\adms\Models\helper\AdmsRead();
        $confEmail->fullRead("SELECT nome, email, host, username, senhas, smtpsecure, porta FROM adms_confs_emails WHERE id=:id LIMIT :limit", "id={$this->optionConfEmail}&limit=1");
        $this->dadosInfoEmail = $confEmail->getResultado();
        /* E8 (D-244): sem a configuração pedida (o "Esqueci a senha" do painel pedia o id 3, herdado), usa a
           mesma da tela Configurações de e-mail — a primeira —, como o link de acesso do aluno. */
        if (empty($this->dadosInfoEmail[0]['host'])) {
            $confEmail->fullRead("SELECT nome, email, host, username, senhas, smtpsecure, porta FROM adms_confs_emails ORDER BY id LIMIT :limit", "limit=1");
            $this->dadosInfoEmail = $confEmail->getResultado();
        }
    }

    private function sendEmailPhpMailer()
    {
        $mail = new PHPMailer(true);
       
        try {

            // $mail->SMTPDebug = SMTP::DEBUG_SERVER;   
            $mail->CharSet = "UTF-8";                   
            $mail->isSMTP();                                            
            $mail->Host       = $this->dadosInfoEmail[0]['host'];                     
            $mail->SMTPAuth   = true;                                   
            $mail->Username   = $this->dadosInfoEmail[0]['username'];                   
            $mail->Password   = $this->dadosInfoEmail[0]['senhas'];                               
            $mail->SMTPSecure = $this->dadosInfoEmail[0]['smtpsecure'];        
            $mail->Port       = $this->dadosInfoEmail[0]['porta'];                                    

            //Recipients
            $mail->setFrom($this->dadosInfoEmail[0]['email'], $this->dadosInfoEmail[0]['nome']);
            $mail->addAddress($this->dados['toEmail'], $this->dados['toName']);           
            // $mail->addReplyTo('info@example.com', 'Information');
            // $mail->addCC('cc@example.com');
            // $mail->addBCC('bcc@example.com');

            //Attachments
            // $mail->addAttachment('/var/tmp/file.tar.gz');         
            // $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    

            //Content
            $mail->isHTML(true);                                  
            $mail->Subject = $this->dados['toSubject'];
            $mail->Body    = $this->dados['contentHtml'];
            $mail->AltBody = $this->dados['contentText'];

            $mail->send();
            $this->resultado = true;
        } catch (Exception $e) {
            /* E7: o erro vai para o log, não para a página (a página não pode mudar conforme o e-mail exista) */
            error_log('[vcostaedu][email] falha no envio: ' . $mail->ErrorInfo);
            $this->resultado = false;
        }
    
    }

}

?>