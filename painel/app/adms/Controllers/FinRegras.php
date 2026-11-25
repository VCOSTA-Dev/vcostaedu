<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/** As regras financeiras (Onda 8, financeiro, Parte 1; D-98): a multa, os juros ao mês e o desconto até o vencimento. */
class FinRegras
{
    public function index(): void
    {
        $dados = ['paginaAtiva' => 560, 'menus' => (new \App\adms\Models\AdmsMenus())->itemMenu(), 'regras' => \App\adms\Models\financeiro\AdmsFinanceiro::regras()];
        (new \Core\ConfigView('adms/Views/financeiro/regras', $dados))->renderizar('tabler');
    }

    public function salvar(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $erro = \App\adms\Models\financeiro\AdmsFinTitulos::salvarRegras($_POST, (int) ($_SESSION['colUsuario_id'] ?? 0));
            $_SESSION['msg'] = $erro !== ''
                ? "<div class='alert alert-danger' id='regrasRecusadas'>" . htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') . '</div>'
                : "<div class='alert alert-success alert-dismissible' id='regrasSalvas'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Regras financeiras salvas. Valem para os recebimentos de agora em diante.</div>";
        }
        header('Location: ' . URLADM . 'fin-regras/index');
    }
}
