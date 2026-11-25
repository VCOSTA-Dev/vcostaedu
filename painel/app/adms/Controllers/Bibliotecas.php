<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * BIBLIOTECAS (Onda 3, Etapa 6), em Manutenção, só o Administrador: as bibliotecas de terceiros, com a versão e a
 * licença de cada uma (a mesma lista que a página Sobre mostra a quem entrou no painel — VcLegal::bibliotecas).
 */
class Bibliotecas
{
    private array $dados = [];

    public function index(): void
    {
        require_once dirname(__DIR__, 4) . '/core/Legal.php';
        $this->dados['paginaAtiva'] = 496;
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
        $this->dados['bibliotecas'] = \VcLegal::bibliotecas(dirname(__DIR__, 4));
        (new \Core\ConfigView('adms/Views/manutencao/bibliotecas', $this->dados))->renderizar('tabler');
    }
}
