<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/** As bandeiras de cartão que o Receber oferece, em Configurações (Onda 8, financeiro refeito, Parte 1; D-99). */
class FinBandeiras
{
    public function listar(): void
    {
        $dados = ['paginaAtiva' => 585, 'menus' => (new \App\adms\Models\AdmsMenus())->itemMenu(), 'lista' => \App\adms\Models\financeiro\AdmsFinListas::bandeiras(), 'tipo' => 'bandeiras'];
        (new \Core\ConfigView('adms/Views/financeiro/listas', $dados))->renderizar('tabler');
    }

    public function salvar(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $erro = \App\adms\Models\financeiro\AdmsFinListas::salvarBandeira($_POST);
            $_SESSION['msg'] = $erro !== ''
                ? "<div class='alert alert-danger' id='listaRecusada'>" . htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') . '</div>'
                : "<div class='alert alert-success alert-dismissible' id='listaSalva'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Bandeira salva.</div>";
        }
        header('Location: ' . URLADM . 'fin-bandeiras/listar');
    }
}
