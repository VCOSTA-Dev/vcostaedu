<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * A VERSÃO (Onda 2, Etapa 7): qual versão está instalada (a do banco e a dos arquivos) e se há etapa pendente; para o
 * Administrador, o caminho para a tela de atualização.
 */
class Versao
{
    private array $dados = [];

    public function index(): void
    {
        $m = new \App\adms\Models\helper\AdmsAtualizacao();
        $this->dados['paginaAtiva'] = 491;
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
        $this->dados['versoes'] = \App\adms\Models\helper\AdmsAtualizacao::versoes();
        $this->dados['estado'] = $m->estado();
        $this->dados['administrador'] = \App\adms\Models\helper\AdmsNivel::ehAdministrador();
        (new \Core\ConfigView('adms/Views/versao/index', $this->dados))->renderizar('tabler');
    }
}
