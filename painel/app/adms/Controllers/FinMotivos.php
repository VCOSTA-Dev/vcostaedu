<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/** Os motivos das operações do financeiro, em Configurações (Onda 8, financeiro refeito, Parte 1; D-99). */
class FinMotivos
{
    public function listar(): void
    {
        $dados = ['paginaAtiva' => 583, 'menus' => (new \App\adms\Models\AdmsMenus())->itemMenu(), 'lista' => \App\adms\Models\financeiro\AdmsFinListas::motivos(), 'tipo' => 'motivos'];
        (new \Core\ConfigView('adms/Views/financeiro/listas', $dados))->renderizar('tabler');
    }

    public function salvar(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $erro = \App\adms\Models\financeiro\AdmsFinListas::salvarMotivo($_POST);
            $_SESSION['msg'] = $erro !== ''
                ? "<div class='alert alert-danger' id='listaRecusada'>" . htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') . '</div>'
                : "<div class='alert alert-success alert-dismissible' id='listaSalva'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Motivo salvo.</div>";
        }
        header('Location: ' . URLADM . 'fin-motivos/listar');
    }
}
