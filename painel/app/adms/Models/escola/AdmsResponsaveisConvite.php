<?php

namespace App\adms\Models\escola;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\escola\AdmsResponsaveis as M;
use PHPMailer\PHPMailer\PHPMailer;

require_once dirname(__DIR__, 5) . '/core/ContaFamilia.php';

/**
 * O CONVITE DO RESPONSÁVEL (Onda 7, Etapa 3; docs/RESPONSAVEIS.md, D-63) — o primeiro acesso ao portal.
 *
 * O convite é um link de uso único, com validade (Regras da escola; o padrão, 7 dias). No banco fica só o resumo do
 * código (sha256): o código em claro existe só no link que a secretaria entrega. O código vai no FRAGMENTO do link
 * (#c=…), que o navegador não manda ao servidor (não chega ao registro de acessos); a página do portal o lê e o envia
 * por POST. Gerar de novo invalida o anterior. A secretaria envia por e-mail (com e-mail e o envio configurado), copia o
 * link, ou abre o WhatsApp de quem está na secretaria com a mensagem pronta (a mensagem das Regras da escola). Nada de
 * integração com serviço de mensagens: é só um link "wa.me".
 */
class AdmsResponsaveisConvite
{
    private static function pdo(): \PDO
    {
        return AdmsEscolaBanco::pdo();
    }

    public static function conta(): \VcContaFamilia
    {
        return new \VcContaFamilia(self::pdo());
    }

    public static function dias(): int
    {
        return self::conta()->diasConvite();
    }

    public static function link(string $codigo): string
    {
        return URLACAD . 'convite/abrir#c=' . $codigo;
    }

    /**
     * Gera o convite (o anterior em aberto deixa de valer). Pede e-mail ou CPF (o identificador de entrada), a pessoa
     * sem bloqueio e com papel em algum aluno. @return array|string ['id', 'codigo', 'link', 'expira'] ou a recusa
     */
    public static function gerar(int $pessoa, ?int $por = null)
    {
        $p = M::pessoa($pessoa);
        if (!$p) {
            return 'Responsável não encontrado.';
        }
        if (!empty($p['bloqueado'])) {
            return 'O acesso desta pessoa está bloqueado. Desbloqueie antes de convidar.';
        }
        if (!$p['email'] && !$p['cpf']) {
            return 'Para o convite, a pessoa precisa de e-mail ou de CPF: é com um deles que ela entra no portal. Edite o cadastro.';
        }
        if (!self::conta()->temAluno($pessoa)) {
            return 'Esta pessoa ainda não tem papel em nenhum aluno. Designe o papel antes de convidar.';
        }
        /* Onda 8 (D-67): a conta nasce no convite com o login escolhido; sem nenhum livre, o convite não adianta */
        if ((string) ($p['login'] ?? '') === '') {
            $op = self::conta()->opcoesLogin($pessoa);
            $livres = array_filter($op, fn($o) => $o['livre']);
            if ($op && !$livres) {
                return 'O e-mail e o CPF desta pessoa já são o login de outra conta no portal. ' . \VcLoginPortal::mensagemEmUso($op[0]['quem'], \VcLoginPortal::mostrar($op[0]['valor']));
            }
        }
        $g = self::conta()->gerar($pessoa, 'convite', $por ?? ((int) ($_SESSION['colUsuario_id'] ?? 0) ?: null), 'tela');
        return $g + ['link' => self::link($g['codigo'])];
    }

    /** Onda 8 (D-67): troca o login do portal (o e-mail ou o CPF do cadastro). @return true|string a recusa */
    public static function trocarLogin(int $pessoa, string $tipo)
    {
        return self::conta()->trocarLogin($pessoa, $tipo, (int) ($_SESSION['colUsuario_id'] ?? 0) ?: null, 'tela');
    }

    /** O primeiro nome dos alunos da pessoa (o convite e a mensagem não mostram mais que isso). */
    public static function primeirosNomes(int $pessoa): array
    {
        return self::conta()->primeirosNomes($pessoa);
    }

    /** A mensagem das Regras da escola, com o nome, os alunos, a validade e o link. */
    public static function mensagem(array $p, string $link, string $expira): string
    {
        $lista = \VcContaFamilia::lista(self::primeirosNomes((int) $p['id']), 'o aluno');
        return strtr(AdmsEscolaConfig::valor('responsaveis.mensagem'), ['{nome}' => explode(' ', trim($p['nome']))[0], '{alunos}' => $lista,
            '{validade}' => date('d/m/Y', strtotime($expira)), '{link}' => $link]);
    }

    /** O link do WhatsApp (abre o aplicativo de quem está na secretaria), para o celular do cadastro. */
    public static function whatsapp(array $p, string $texto): ?string
    {
        $cel = M::digitos($p['celular'] ?? '');
        if (strlen($cel) < 10) {
            return null;
        }
        return 'https://wa.me/55' . $cel . '?text=' . rawurlencode($texto);
    }

    /** O envio está configurado (Configurações de e-mail)? */
    public static function envioConfigurado(): bool
    {
        $c = self::pdo()->query('SELECT host FROM adms_confs_emails ORDER BY id LIMIT 1')->fetch(\PDO::FETCH_ASSOC);
        return $c && !empty($c['host']);
    }

    /** Envia por e-mail. @return true|string */
    public static function enviarEmail(int $conviteId, string $link, ?int $por = null)
    {
        $st = self::pdo()->prepare('SELECT c.*, r.nome, r.email FROM esc_responsavel_convites c INNER JOIN esc_responsaveis r ON r.id = c.responsavel_id WHERE c.id = ?');
        $st->execute([$conviteId]);
        $c = $st->fetch(\PDO::FETCH_ASSOC);
        if (!$c || $c['usado_em'] || $c['cancelado_em'] || strtotime($c['expira_em']) < time()) {
            return 'O convite não está mais valendo. Gere outro.';
        }
        if (!filter_var((string) $c['email'], FILTER_VALIDATE_EMAIL)) {
            return 'A pessoa não tem e-mail cadastrado: copie o link ou use o WhatsApp.';
        }
        $conf = self::pdo()->query('SELECT nome, email, host, username, senhas, smtpsecure, porta FROM adms_confs_emails ORDER BY id LIMIT 1')->fetch(\PDO::FETCH_ASSOC);
        if (!$conf || empty($conf['host'])) {
            return 'O envio de e-mail não está configurado (Configurações de e-mail): copie o link ou use o WhatsApp.';
        }
        $p = M::pessoa((int) $c['responsavel_id']);
        $texto = $c['tipo'] === 'senha'
            ? 'Para criar uma senha nova no portal, use este link (vale por 2 horas, uma vez só): ' . $link
            : self::mensagem($p, $link, $c['expira_em']);
        $instituicao = \App\adms\Models\helper\AdmsInstituicao::nome();
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
            $mail->addAddress((string) $c['email'], (string) $c['nome']);
            $mail->isHTML(true);
            $mail->Subject = ($c['tipo'] === 'senha' ? 'Senha nova do portal' : 'Convite para o portal') . ' — ' . $instituicao;
            $mail->Body = '<p>' . nl2br(htmlspecialchars(str_replace($link, '', $texto))) . '</p><p><a href="' . htmlspecialchars($link) . '">' . htmlspecialchars($link) . '</a></p><p>Se você não esperava esta mensagem, ignore-a.</p>';
            $mail->AltBody = $texto;
            $mail->send();
        } catch (\Throwable $e) {
            return 'O servidor de e-mail recusou ou não respondeu: copie o link ou use o WhatsApp.';
        }
        self::conta()->marcarEnviado($conviteId, (int) $c['responsavel_id'], $por ?? ((int) ($_SESSION['colUsuario_id'] ?? 0) ?: null), 'tela');
        return true;
    }

    public static function cancelar(int $pessoa): bool
    {
        $st = self::pdo()->prepare('UPDATE esc_responsavel_convites SET cancelado_em = NOW() WHERE responsavel_id = ? AND usado_em IS NULL AND cancelado_em IS NULL');
        $st->execute([$pessoa]);
        if ($st->rowCount() > 0) {
            M::auditar('convite_cancelado', $pessoa, null, null, null, null, null);
            return true;
        }
        return false;
    }

    /** Bloqueia (com o motivo) ou desbloqueia o acesso. O bloqueio cancela o convite em aberto. @return true|string */
    public static function bloquear(int $pessoa, bool $bloquear, ?string $motivo)
    {
        $p = M::pessoa($pessoa);
        if (!$p) {
            return 'Responsável não encontrado.';
        }
        $motivo = trim((string) $motivo);
        if ($bloquear && mb_strlen($motivo) < 3) {
            return 'Informe o motivo do bloqueio.';
        }
        self::pdo()->prepare('UPDATE esc_responsaveis SET bloqueado = ?, bloqueado_motivo = ?, bloqueado_em = ?, bloqueado_por = ? WHERE id = ?')
            ->execute([$bloquear ? 1 : 0, $bloquear ? mb_substr($motivo, 0, 255) : null, $bloquear ? date('Y-m-d H:i:s') : null, $bloquear ? ((int) ($_SESSION['colUsuario_id'] ?? 0) ?: null) : null, $pessoa]);
        if ($bloquear) {
            self::cancelar($pessoa);
        }
        M::auditar($bloquear ? 'bloquear' : 'desbloquear', $pessoa, null, null, ['bloqueado' => (int) $p['bloqueado']], ['bloqueado' => $bloquear ? 1 : 0], $bloquear ? $motivo : null);
        return true;
    }

    /* ------------------------------------------------------------------ os convites de uma turma */

    /** As turmas do ano (a série e o nome). */
    public static function turmas(int $ano): array
    {
        $st = self::pdo()->prepare("SELECT t.id, t.ano, TRIM(CONCAT(m.codnome, ' ', t.nome)) AS nome FROM esc_turmas t INNER JOIN rm_cursos_turma_series m ON m.id = t.modulo_id WHERE t.ano = ? ORDER BY m.codnome, t.nome");
        $st->execute([$ano]);
        return $st->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Os responsáveis dos alunos da turma (com papel em vigor no ano da turma) que ainda não têm acesso: sem senha e
     * sem bloqueio. Cada um com os alunos da turma de quem é responsável.
     */
    public static function pendentesDaTurma(int $turma): array
    {
        $st = self::pdo()->prepare("SELECT r.id, r.nome, r.email, r.celular, r.cpf, GROUP_CONCAT(DISTINCT u.nome ORDER BY u.nome SEPARATOR ', ') AS alunos
              FROM esc_turmas t
              INNER JOIN esc_turma_alunos ta ON ta.turma_id = t.id AND ta.saida IS NULL
              INNER JOIN col_usuarios u ON u.rm = ta.rm AND u.col_niveis_acesso_id = 1
              INNER JOIN esc_responsavel_designacoes d ON d.aluno_id = u.id AND d.ano = t.ano AND d.fim IS NULL
              INNER JOIN esc_responsaveis r ON r.id = d.responsavel_id
             WHERE t.id = ? AND r.senha IS NULL AND r.bloqueado = 0
             GROUP BY r.id, r.nome, r.email, r.celular, r.cpf ORDER BY r.nome");
        $st->execute([$turma]);
        return $st->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * UM PASSO do convite da turma: gera o convite da pessoa (se ela é mesmo pendente na turma) e tenta o e-mail. O que
     * não foi por e-mail volta com o link e o WhatsApp, para o envio manual.
     */
    public static function passoTurma(int $turma, int $pessoa): array
    {
        $pend = array_column(self::pendentesDaTurma($turma), null, 'id');
        if (!isset($pend[$pessoa])) {
            return ['ok' => false, 'erro' => 'Esta pessoa não está entre os responsáveis sem acesso desta turma (já tem acesso, foi bloqueada ou não tem papel neste ano).'];
        }
        $g = self::gerar($pessoa);
        if (!is_array($g)) {
            return ['ok' => false, 'id' => $pessoa, 'nome' => $pend[$pessoa]['nome'], 'erro' => $g];
        }
        $p = M::pessoa($pessoa);
        $enviado = false;
        if (!filter_var((string) $p['email'], FILTER_VALIDATE_EMAIL)) {
            $motivo = 'sem e-mail';
        } elseif (!self::envioConfigurado()) {
            $motivo = 'o envio de e-mail não está configurado';
        } else {
            $r = self::enviarEmail((int) $g['id'], $g['link']);
            $enviado = $r === true;
            $motivo = $enviado ? '' : (string) $r;
        }
        $saida = ['ok' => true, 'id' => $pessoa, 'nome' => $p['nome'], 'enviado' => $enviado];
        if (!$enviado) {
            $texto = self::mensagem($p, $g['link'], $g['expira']);
            $saida += ['motivo' => $motivo, 'link' => $g['link'], 'texto' => $texto, 'whatsapp' => self::whatsapp($p, $texto)];
        }
        return $saida;
    }
}
