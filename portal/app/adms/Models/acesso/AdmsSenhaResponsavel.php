<?php

namespace App\adms\Models\acesso;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use PHPMailer\PHPMailer\PHPMailer;

require_once dirname(__DIR__, 5) . '/core/Banco.php';
require_once dirname(__DIR__, 5) . '/core/ContaFamilia.php';

/**
 * O "ESQUECI A SENHA" DO RESPONSÁVEL (Onda 7, Etapa 3; D-63). Só para quem tem e-mail, não está bloqueado, já criou a
 * senha alguma vez e tem papel em algum aluno: recebe por e-mail um link de senha nova (uso único, 2 horas), que abre a
 * mesma página do convite. Quem não tem e-mail é orientado, na tela, a pedir um convite novo à secretaria. A resposta
 * da tela é a mesma em todos os casos (não diz se a pessoa existe).
 */
class AdmsSenhaResponsavel
{
    /** @return bool true se um link foi gerado e enviado */
    public static function pedir(string $identificador): bool
    {
        $conta = new \VcContaFamilia(\VcBanco::daRequisicao());
        if (!$conta->ehEscola() || !$conta->pronto()) {
            return false;
        }
        /* sem o envio configurado, nem gera o link: ninguém o receberia */
        $conf = \VcBanco::daRequisicao()->query('SELECT nome, email, host, username, senhas, smtpsecure, porta FROM adms_confs_emails ORDER BY id LIMIT 1')->fetch(\PDO::FETCH_ASSOC);
        if (!$conf || empty($conf['host'])) {
            return false;
        }
        $r = $conta->pedirSenha($identificador);
        if (!$r) {
            return false;
        }
        $link = URLACAD . 'convite/abrir#c=' . $r['codigo'];
        $p = $r['pessoa'];
        $instituicao = \App\adms\Models\helper\AdmsInstituicao::nome();
        $html = '<p>Olá, ' . htmlspecialchars(explode(' ', trim((string) $p['nome']))[0]) . '.</p>'
            . '<p>Para criar uma senha nova no portal da escola, use o link abaixo. Ele vale por ' . \VcContaFamilia::HORAS_SENHA . ' horas e pode ser usado uma única vez.</p>'
            . '<p><a href="' . htmlspecialchars($link) . '">' . htmlspecialchars($link) . '</a></p>'
            . '<p>Se você não pediu isto, ignore esta mensagem: a sua senha continua a mesma.</p>';
        try {
            $mail = new PHPMailer(true);
            $mail->CharSet = 'UTF-8';
            $mail->isSMTP();
            $mail->Host = $conf['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $conf['username'];
            $mail->Password = $conf['senhas'];
            $mail->SMTPSecure = $conf['smtpsecure'];
            $mail->Port = (int) $conf['porta'];
            $mail->Timeout = 15;
            $mail->setFrom($conf['email'], $conf['nome']);
            $mail->addAddress((string) $p['email'], (string) $p['nome']);
            $mail->isHTML(true);
            $mail->Subject = 'Senha nova do portal — ' . $instituicao;
            $mail->Body = $html;
            $mail->AltBody = strip_tags(str_replace('</p>', "\n", $html));
            $mail->send();
        } catch (\Throwable $e) {
            return false;
        }
        $conta->marcarEnviado((int) $r['id'], (int) $p['id'], null, 'portal');
        return true;
    }
}
