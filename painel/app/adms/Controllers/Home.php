<?php

namespace App\adms\Controllers;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class Home
{
    private $pagina = 2;
    private $dados;

    public function index()
    {
        /* Onda 2, Etapa 4: a escolha feita na última tela do instalador */
        if (!empty($_SESSION['vc_comecar']) && \App\adms\Models\helper\AdmsNivel::ehAdministrador()) {
            $c = $_SESSION['vc_comecar'];
            unset($_SESSION['vc_comecar']);
            header('Location: ' . URLADM . ($c === 'demonstracao' ? 'demonstracao/comecar' : 'demonstracao/comecar?escolha=instituicao'));
            return;
        }
        $this->dados['paginaAtiva'] = $this->pagina;
        /* Onda 6, Etapa 7: quem marcou "registrar" no instalador vê o convite até registrar (ou dispensar) */
        if (!empty($_SESSION['vc_convite_registro']) && \App\adms\Models\helper\AdmsNivel::ehAdministrador()
            && (\App\adms\Models\manutencao\AdmsRegistroInstalacao::estado()['situacao'] ?? 'nao') === 'nao') {
            $this->dados['conviteRegistro'] = true;
        }
        /* ⓘ Atalhos da página inicial. Cada chave passa por
           `AdmsBotao::valBotao()`: o botão só aparece para quem o RBAC autoriza.
           Na VCOSTAEDU ficam só os do acadêmico (E2a). */
        $botao = ['list_disc' => ['menu_controller' => 'disciplinas', 'menu_metodo' => 'listar'],
        'my_disc' => ['menu_controller' => 'minhas-disciplinas', 'menu_metodo' => 'listar'],
        'acad' => ['menu_controller' => 'academicos', 'menu_metodo' => 'listar'],
        'mr_modulos'   => ['menu_controller' => 'modulos',         'menu_metodo' => 'listar'],
        'mr_anos'      => ['menu_controller' => 'anos-letivos',    'menu_metodo' => 'listar'],
        ];
        $visBotao = new \App\adms\Models\AdmsBotao();
        $this->dados['botao'] = $visBotao->valBotao($botao);

        $listMenu = new \App\adms\Models\AdmsMenus();
        $this->dados['menus'] = $listMenu->itemMenu();

        $carregarView = new \Core\ConfigView("adms/Views/home/home", $this->dados);
        $carregarView->renderizar('tabler');
    }
}