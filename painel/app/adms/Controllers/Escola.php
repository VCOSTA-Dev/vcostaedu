<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * A ESTRUTURA DA ESCOLA (Onda 4, Etapa 1; docs/ESCOLA.md) — só numa instalação do tipo escola (a página é do módulo
 * "escola": no ensino superior a rota é recusada e o item não aparece no menu). Os segmentos (os cursos), as séries de
 * cada um (os módulos da matriz) e os componentes curriculares de cada série, com a carga horária do ano. A coordenação
 * vê o segmento que coordena (AdmsEscopo::cursos).
 */
class Escola
{
    private array $dados = [];

    public function index(): void
    {
        $this->dados['paginaAtiva'] = 507;
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
        $this->dados['segmentos'] = (new \App\adms\Models\escola\AdmsEstruturaEscola())->segmentos();
        (new \Core\ConfigView('adms/Views/escola/index', $this->dados))->renderizar('tabler');
    }
}
