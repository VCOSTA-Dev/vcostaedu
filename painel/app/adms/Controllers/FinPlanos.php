<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\financeiro\AdmsFinTitulos as T;

/** Os planos de pagamento (Onda 8, financeiro, Parte 1; D-98): listar (com o formulário), salvar, ativar e desativar. */
class FinPlanos
{
    private function msg(string $erro, string $ok): void
    {
        $_SESSION['msg'] = $erro !== ''
            ? "<div class='alert alert-danger' id='planoRecusado'>" . htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') . '</div>'
            : "<div class='alert alert-success alert-dismissible' id='planoSalvo'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>" . htmlspecialchars($ok, ENT_QUOTES, 'UTF-8') . '</div>';
    }

    public function listar(): void
    {
        $editar = (int) ($_GET['editar'] ?? 0);
        $dados = ['paginaAtiva' => 562, 'menus' => (new \App\adms\Models\AdmsMenus())->itemMenu(), 'planos' => T::planos(),
            'editando' => $editar ? T::plano($editar) : null, 'form' => $_SESSION['form_plano'] ?? null];
        unset($_SESSION['form_plano']);
        (new \Core\ConfigView('adms/Views/financeiro/planos', $dados))->renderizar('tabler');
    }

    public function salvar(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $erro = T::salvarPlano($_POST, (int) ($_SESSION['colUsuario_id'] ?? 0));
            $this->msg($erro, 'Plano de pagamento salvo.');
            if ($erro !== '') {
                $_SESSION['form_plano'] = $_POST;
            }
        }
        header('Location: ' . URLADM . 'fin-planos/listar');
    }

    public function situacao($id = null): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $sit = (int) ($_POST['situacao'] ?? 0);
            $this->msg(T::situacaoPlano((int) $id, $sit), $sit === 1 ? 'Plano em uso.' : 'Plano fora de uso: não aparece mais na matrícula; as parcelas já geradas ficam.');
        }
        header('Location: ' . URLADM . 'fin-planos/listar');
    }
}
