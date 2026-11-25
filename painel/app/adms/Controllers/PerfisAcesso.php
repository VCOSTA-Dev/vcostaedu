<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * PERFIS DE ACESSO (docs/PENTE_FINO.md): o que cada perfil pode fazer, por ação, só para ler. O que muda a matriz é a
 * atualização do sistema (as migrations), não uma tela.
 */
class PerfisAcesso
{
    private array $dados = [];

    public function index(): void
    {
        $this->dados['paginaAtiva'] = 492;
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
        $this->dados['matriz'] = (new \App\adms\Models\perfis\AdmsPerfisAcesso())->matriz();
        (new \Core\ConfigView('adms/Views/perfisAcesso/index', $this->dados))->renderizar('tabler');
    }
}
