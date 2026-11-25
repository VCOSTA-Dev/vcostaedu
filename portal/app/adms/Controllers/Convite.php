<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

require_once dirname(__DIR__, 4) . '/core/Banco.php';
require_once dirname(__DIR__, 4) . '/core/ContaFamilia.php';

/**
 * O CONVITE DO RESPONSÁVEL NO PORTAL (Onda 7, Etapa 3; docs/RESPONSAVEIS.md, D-63) — páginas públicas, só na escola.
 *   abrir     a página do link (…/convite/abrir#c=CÓDIGO): o código vem no FRAGMENTO, que o navegador não manda ao
 *             servidor; o JavaScript o lê, tira da barra de endereço e o envia por POST para "conferir";
 *   conferir  POST: o convite que vale mostra o nome da pessoa, o primeiro nome de cada aluno e o formulário da senha;
 *             o link usado, vencido, cancelado ou inventado mostra a mesma página clara, sem dizer se a pessoa existe;
 *   definir   POST: a senha (a regra das senhas do sistema); o código vale uma vez só; a pessoa entra em seguida.
 * O limite de tentativas conta cada link que não vale (por IP, como a verificação pública).
 */
class Convite
{
    private array $dados = [];

    private function conta(): ?\VcContaFamilia
    {
        $c = new \VcContaFamilia(\VcBanco::daRequisicao());
        return ($c->ehEscola() && $c->pronto()) ? $c : null;
    }

    private function tela(string $view): void
    {
        (new \Core\ConfigView('adms/Views/convite/' . $view, $this->dados))->renderizarLogin('tabler');
    }

    public function abrir(): void
    {
        if (!$this->conta()) {
            header('Location: ' . URLACAD . 'login/acesso');
            return;
        }
        $this->tela('abrir');
    }

    /** O código do POST, só com a forma certa (48 letras hexadecimais). */
    private static function codigo(): string
    {
        $c = strtolower(preg_replace('/[^A-Fa-f0-9]/', '', (string) ($_POST['codigo'] ?? '')));
        return \VcContaFamilia::formaValida($c) ? $c : '';
    }

    /** O convite que vale, ou a página do link que não vale (e a tentativa contada). */
    private function convite(\VcContaFamilia $conta, string $codigo): ?array
    {
        $espera = \App\adms\Models\helper\AdmsLimiteTentativas::espera('convite_portal', null);
        if ($espera > 0) {
            \App\adms\Models\helper\AdmsLimiteTentativas::logBloqueio('convite_portal', null, $espera);
            $this->dados['espera'] = \App\adms\Models\helper\AdmsLimiteTentativas::mensagem($espera);
            $this->tela('invalido');
            return null;
        }
        $c = $codigo !== '' ? $conta->convite($codigo) : null;
        if (!$c) {
            \App\adms\Models\helper\AdmsLimiteTentativas::registrar('convite_portal', null);
            $this->tela('invalido');
            return null;
        }
        return $c;
    }

    private function formulario(\VcContaFamilia $conta, array $c, string $codigo, ?string $erro = null): void
    {
        $this->dados['convite'] = $c;
        $this->dados['codigo'] = $codigo;
        $this->dados['alunos'] = $conta->primeirosNomes((int) $c['responsavel_id']);
        /* Onda 8 (D-67): o login da conta — o que ela já tem, ou as escolhas (o e-mail e o CPF do cadastro) */
        $this->dados['loginAtual'] = (string) ($conta->pessoa((int) $c['responsavel_id'])['login'] ?? '');
        $this->dados['opcoesLogin'] = $this->dados['loginAtual'] === '' ? $conta->opcoesLogin((int) $c['responsavel_id']) : [];
        $this->dados['escolha'] = (string) ($_POST['login_tipo'] ?? '');
        $this->dados['erro'] = $erro;
        $this->tela('senha');
    }

    public function conferir(): void
    {
        $conta = $this->conta();
        if (!$conta || ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Location: ' . URLACAD . 'login/acesso');
            return;
        }
        $codigo = self::codigo();
        $c = $this->convite($conta, $codigo);
        if ($c) {
            $this->formulario($conta, $c, $codigo);
        }
    }

    public function definir(): void
    {
        $conta = $this->conta();
        if (!$conta || ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Location: ' . URLACAD . 'login/acesso');
            return;
        }
        $codigo = self::codigo();
        $c = $this->convite($conta, $codigo);
        if (!$c) {
            return;
        }
        $r = $conta->definirSenha($codigo, (string) ($_POST['senha'] ?? ''), (string) ($_POST['senha_repetido'] ?? ''), (string) ($_POST['login_tipo'] ?? ''));
        if ($r !== true) {
            $this->formulario($conta, $c, $codigo, $r);
            return;
        }
        /* a senha criada: a pessoa entra em seguida (como o login do responsável) */
        $p = $conta->pessoa((int) $c['responsavel_id']);
        session_regenerate_id(true);
        unset($_SESSION['academico_id'], $_SESSION['academico_email'], $_SESSION['academico_imagem'], $_SESSION['academico_ordem'], $_SESSION['vc_familia_filho']);
        $_SESSION['vc_responsavel_id'] = (int) $p['id'];
        $_SESSION['academico_nome'] = (string) $p['nome'];
        $_SESSION['academico_nivel'] = 2;
        $_SESSION['academico_situacao'] = 1;
        $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible' id='senhaCriada'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Senha criada. Nas próximas vezes, entre no portal com o login <b>"
            . htmlspecialchars(\VcLoginPortal::mostrar($p['login'] ?? ''), ENT_QUOTES, 'UTF-8') . '</b> e esta senha.</div>';
        header('Location: ' . URLACAD . 'familia/index');
    }
}
