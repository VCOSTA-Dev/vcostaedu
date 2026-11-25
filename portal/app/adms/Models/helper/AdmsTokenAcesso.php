<?php

namespace App\adms\Models\helper;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Link de definição de senha do aluno (D-07 b), usado no PRIMEIRO ACESSO
 * (emitido pela secretaria) e no ESQUECI A SENHA (pedido pelo aluno).
 * Arquivo IDÊNTICO no painel e no portal.
 *
 * - O token vem de random_bytes (32 bytes = 64 hex) e só existe em claro na
 *   URL do link. No banco (col_usuarios_tokens) fica só o SHA-256.
 * - Uso ÚNICO (usado_em), com VALIDADE (expira_em, horas em
 *   adms_parametros.portal.senha.link_horas, padrão 48).
 * - Emitir um novo invalida os anteriores ainda abertos do mesmo usuário
 *   (invalidado_em).
 * - Nada aqui grava o token em claro em log nenhum.
 */
class AdmsTokenAcesso extends AdmsConn
{
    public const FINALIDADES = ['primeiro_acesso', 'recuperacao'];

    private $pdo;

    public function __construct()
    {
        $this->pdo = $this->connect();
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    public static function horasValidade(): int
    {
        $h = (int) AdmsParametro::ler('portal.senha.link_horas', '48');
        return ($h >= 1 && $h <= 720) ? $h : 48;
    }

    public static function link(string $token): string
    {
        /* E7 (D-70): o token vai no FRAGMENTO (#t=…), que o navegador não envia ao servidor — nunca chega ao
           access log. A página definir-senha o lê por JavaScript e o manda por POST. */
        return URLACAD . 'login/definir-senha#t=' . $token;
    }

    /** Emite um token novo (e invalida os abertos) e devolve o token EM CLARO, só para montar o link. */
    public function emitir(int $usuarioId, string $finalidade, ?int $criadoPor = null): string
    {
        if (!in_array($finalidade, self::FINALIDADES, true)) {
            throw new \InvalidArgumentException('Finalidade de token inválida.');
        }
        $this->pdo->prepare('UPDATE col_usuarios_tokens SET invalidado_em = NOW()
                              WHERE col_usuario_id = :u AND usado_em IS NULL AND invalidado_em IS NULL')
                  ->execute(['u' => $usuarioId]);

        $token = bin2hex(random_bytes(32));
        $this->pdo->prepare('INSERT INTO col_usuarios_tokens (col_usuario_id, finalidade, token_hash, expira_em, criado_por)
                             VALUES (:u, :f, :h, DATE_ADD(NOW(), INTERVAL :horas HOUR), :por)')
                  ->execute(['u' => $usuarioId, 'f' => $finalidade, 'h' => hash('sha256', $token),
                             'horas' => self::horasValidade(), 'por' => $criadoPor]);
        return $token;
    }

    /** Devolve o token válido (não usado, não invalidado, não expirado) ou null. */
    public function validar(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        $st = $this->pdo->prepare('SELECT t.id, t.col_usuario_id, t.finalidade, t.expira_em, u.nome
                                     FROM col_usuarios_tokens t
                                     INNER JOIN col_usuarios u ON u.id = t.col_usuario_id
                                    WHERE t.token_hash = :h AND t.usado_em IS NULL AND t.invalidado_em IS NULL
                                      AND t.expira_em > NOW() LIMIT 1');
        $st->execute(['h' => hash('sha256', $token)]);
        $r = $st->fetch(\PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    /**
     * Onda 8 (D-70): o link que JÁ CRIOU a senha — usado (não só invalidado nem vencido), e a conta com senha. Quem abre de
     * novo o link do e-mail depois de criar a senha (pelo botão do site, ou no clique duplo) vê que a senha já foi criada,
     * e não um erro. Só quem tem o link chega aqui: nada da conta é revelado além disso.
     */
    public function jaCriouASenha(string $token): bool
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return false;
        }
        $st = $this->pdo->prepare("SELECT COUNT(*) FROM col_usuarios_tokens t INNER JOIN col_usuarios u ON u.id = t.col_usuario_id
                                    WHERE t.token_hash = :h AND t.usado_em IS NOT NULL AND u.senha NOT IN ('', '-inexistente')");
        $st->execute(['h' => hash('sha256', $token)]);
        return (int) $st->fetchColumn() > 0;
    }

    /**
     * Define a senha e consome o token numa transação: o token só é marcado
     * como usado se ainda estiver válido (evita dois usos simultâneos).
     */
    public function definirSenha(string $token, string $senha): bool
    {
        $t = $this->validar($token);
        if (!$t) {
            return false;
        }
        $this->pdo->beginTransaction();
        try {
            $st = $this->pdo->prepare('UPDATE col_usuarios_tokens SET usado_em = NOW()
                                        WHERE id = :id AND usado_em IS NULL AND invalidado_em IS NULL AND expira_em > NOW()');
            $st->execute(['id' => $t['id']]);
            if ($st->rowCount() !== 1) {
                $this->pdo->rollBack();
                return false;
            }
            $this->pdo->prepare('UPDATE col_usuarios SET senha = :s, recuperar_senha = NULL WHERE id = :u')
                      ->execute(['s' => password_hash($senha, PASSWORD_DEFAULT), 'u' => $t['col_usuario_id']]);
            $this->pdo->prepare('UPDATE col_usuarios_tokens SET invalidado_em = NOW()
                                  WHERE col_usuario_id = :u AND id <> :id AND usado_em IS NULL AND invalidado_em IS NULL')
                      ->execute(['u' => $t['col_usuario_id'], 'id' => $t['id']]);
            $this->pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    /**
     * Envia o link por e-mail pelo SMTP de adms_confs_emails (a primeira
     * configuração). Devolve true só se o PHPMailer confirmou o envio; em
     * $motivo diz, honestamente, por que não enviou.
     */
    public function enviarEmail(string $para, string $nome, string $link, string $finalidade, ?string &$motivo = null): bool
    {
        $motivo = null;
        if (!filter_var($para, FILTER_VALIDATE_EMAIL)) {
            $motivo = 'o aluno não tem e-mail válido cadastrado';
            return false;
        }
        $conf = $this->pdo->query('SELECT nome, email, host, username, senhas, smtpsecure, porta FROM adms_confs_emails ORDER BY id LIMIT 1')->fetch(\PDO::FETCH_ASSOC);
        if (!$conf || empty($conf['host'])) {
            $motivo = 'não há SMTP configurado (Configurações de e-mail)';
            return false;
        }
        $horas = self::horasValidade();
        $instituicao = AdmsInstituicao::nome(); // E6a: o cadastro; NOME_INSTITUICAO é o fallback
        $assunto = $finalidade === 'primeiro_acesso' ? "Seu acesso ao portal do aluno — {$instituicao}" : "Redefinição de senha — {$instituicao}";
        $html = '<p>Olá, ' . htmlspecialchars($nome) . '.</p>'
              . '<p>Para definir sua senha do portal do aluno, use o link abaixo. Ele vale por ' . $horas . ' horas e pode ser usado uma única vez.</p>'
              . '<p><a href="' . htmlspecialchars($link) . '">' . htmlspecialchars($link) . '</a></p>'
              . '<p>Se você não pediu isto, ignore esta mensagem.</p>';
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
            $mail->addAddress($para, $nome);
            $mail->isHTML(true);
            $mail->Subject = $assunto;
            $mail->Body = $html;
            $mail->AltBody = strip_tags(str_replace('</p>', "\n", $html));
            $mail->send();
            return true;
        } catch (\Throwable $e) {
            $motivo = 'o servidor de e-mail recusou ou não respondeu';
            return false;
        }
    }
}
