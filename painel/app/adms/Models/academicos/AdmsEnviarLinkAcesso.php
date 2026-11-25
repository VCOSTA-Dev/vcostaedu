<?php

namespace App\adms\Models\academicos;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * "Enviar link de acesso" (secretaria). Emite o link de definição de senha do
 * aluno e tenta enviá-lo por e-mail.
 *
 * - Enviado: a secretaria vê só a confirmação.
 * - Não enviado (aluno sem e-mail, SMTP não configurado ou falha): a tela da
 *   SECRETARIA diz com honestidade que o e-mail NÃO foi enviado e mostra o
 *   link para copiar e entregar ao aluno. O link nunca aparece para o público.
 * - O log registra que o link foi emitido e se foi enviado — nunca o token.
 */
class AdmsEnviarLinkAcesso
{
    private $resultado;

    public function getResultado()
    {
        return $this->resultado;
    }

    public function enviar(int $idAluno): void
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT id, nome, email, senha FROM col_usuarios WHERE id = :id AND col_niveis_acesso_id = 1 LIMIT 1", "id={$idAluno}");
        $aluno = $ler->getResultado()[0] ?? null;
        if (!$aluno) {
            $this->msg('danger', 'Aluno não encontrado.');
            $this->resultado = false;
            return;
        }

        $finalidade = ($aluno['senha'] === '-inexistente' || $aluno['senha'] === '') ? 'primeiro_acesso' : 'recuperacao';
        $tokens = new \App\adms\Models\helper\AdmsTokenAcesso();
        $token = $tokens->emitir((int) $aluno['id'], $finalidade, (int) ($_SESSION['colUsuario_id'] ?? 0));
        $link = \App\adms\Models\helper\AdmsTokenAcesso::link($token);
        $horas = \App\adms\Models\helper\AdmsTokenAcesso::horasValidade();

        $motivo = null;
        $enviado = $tokens->enviarEmail((string) $aluno['email'], (string) $aluno['nome'], $link, $finalidade, $motivo);

        $log = new \App\adms\Models\helper\AdmsSalvarLog();
        $log->salvarLogs(366, $enviado ? 200 : 202, 2, "Link de acesso emitido para o aluno id={$aluno['id']} ({$finalidade}); e-mail " . ($enviado ? 'enviado' : 'NÃO enviado: ' . $motivo) . '.');

        if ($enviado) {
            $this->msg('success', 'Link de acesso enviado para <b>' . htmlspecialchars($aluno['email']) . "</b>. Ele vale por {$horas} horas e pode ser usado uma única vez. Links emitidos antes para este aluno deixaram de valer.");
        } else {
            $this->msg('warning', '<b>O e-mail NÃO foi enviado</b>: ' . htmlspecialchars((string) $motivo) . '.<br>'
                . 'Copie o link abaixo e entregue ao aluno por um canal seguro. Ele vale por ' . $horas . ' horas, pode ser usado uma única vez, e links emitidos antes para este aluno deixaram de valer.'
                . '<div class="input-group mt-2"><input type="text" class="form-control" id="linkAcessoAluno" readonly value="' . htmlspecialchars($link, ENT_QUOTES) . '">'
                . '<button type="button" class="btn btn-outline-dark" onclick="var c=document.getElementById(\'linkAcessoAluno\'); c.select(); document.execCommand(\'copy\');">Copiar</button></div>');
        }
        $this->resultado = true;
    }

    private function msg(string $tipo, string $html): void
    {
        $_SESSION['msg'] = "<div class='alert alert-{$tipo} alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>{$html}</div>";
    }
}
