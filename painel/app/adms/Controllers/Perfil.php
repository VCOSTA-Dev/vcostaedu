<?php

namespace App\adms\Controllers;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class Perfil
{
    private $pagina = 2;
    private $dados;
    private $dadosForm;
    
    public function visualizar()
    {
        $this->dados['paginaAtiva'] = $this->pagina;
        $botao = ['edit_perfil' => ['menu_controller' => 'perfil', 'menu_metodo' => 'editar'],
        'edit_senha' => ['menu_controller' => 'perfil', 'menu_metodo' => 'editar-senha'],];
        $visBotao = new \App\adms\Models\AdmsBotao();
        $this->dados['botao'] = $visBotao->valBotao($botao);

        $listMenu = new \App\adms\Models\AdmsMenus();
        $this->dados['menus'] = $listMenu->itemMenu();

        $visPerfil = new \App\adms\Models\perfil\AdmsMeuPerfil();
        $this->dados['info_user'] = $visPerfil->verPerfil();

        $carregarView = new \Core\ConfigView("adms/Views/perfil/meuPerfil", $this->dados);
        $carregarView->renderizar('tabler');
    }

    public function editar()
    {
        $this->dadosForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if(!empty($this->dadosForm['EditPerfil'])){
            unset($this->dadosForm['EditPerfil']);
            $this->dadosForm['imagem_nova'] = ($_FILES['imagem_nova'] ? $_FILES['imagem_nova'] : null);
            $editarPerfil = new \App\adms\Models\perfil\AdmsEditarPerfil();
            $editarPerfil->altPerfil($this->dadosForm);
            if($editarPerfil->getResultado()){
                $urlDestino = URLADM . 'perfil/visualizar/';
                header("Location: $urlDestino"); 
            }else{
                /* Onda 3, Etapa 2: na recusa, o perfil gravado com o que a pessoa digitou por cima — só o formulário deixava
                   a tela sem o nível, a situação e as datas (avisos do PHP na página) */
                $gravado = (new \App\adms\Models\perfil\AdmsEditarPerfil())->verPerfil()[0] ?? [];
                unset($this->dadosForm['imagem_nova']);
                $this->dados['form'] = [array_merge($gravado, array_intersect_key($this->dadosForm, $gravado))];
                $this->editarView();
            }
        }else{
            $visPerfil = new \App\adms\Models\perfil\AdmsEditarPerfil();
            $this->dados['form'] = $visPerfil->verPerfil();
            $this->editarView();
        }
       
    }

    private function editarView()
    {
        $this->dados['paginaAtiva'] = $this->pagina;
        $botao = ['meu_perfil' => ['menu_controller' => 'perfil', 'menu_metodo' => 'visualizar'],
        'edit_senha' => ['menu_controller' => 'perfil', 'menu_metodo' => 'editar-senha'],];
        $visBotao = new \App\adms\Models\AdmsBotao();
        $this->dados['botao'] = $visBotao->valBotao($botao);

        $listMenu = new \App\adms\Models\AdmsMenus();
        $this->dados['menus'] = $listMenu->itemMenu();

        $listarSelect = new \App\adms\Models\perfil\AdmsEditarPerfil();
        $this->dados['select'] = $listarSelect->listarCadastro();

        $carregarView = new \Core\ConfigView("adms/Views/perfil/editPerfil", $this->dados);
        $carregarView->renderizar('tabler');
    }

    public function editarSenha()
    {
        $this->dadosForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        if(!empty($this->dadosForm['EditSenhaPerfil'])){
            unset($this->dadosForm['EditSenhaPerfil']);
            $editarSenhaPerfil = new \App\adms\Models\perfil\AdmsEditarSenhaPerfil();
            $editarSenhaPerfil->altSenhaPerfil($this->dadosForm);
            if($editarSenhaPerfil->getResultado()){
                $urlDestino = URLADM . 'perfil/visualizar/';
                header("Location: $urlDestino"); 
            }else{
                $urlDestino = URLADM . 'perfil/visualizar/';
                header("Location: $urlDestino"); 
            }
        }else{
            $urlDestino = URLADM . 'perfil/visualizar/';
            header("Location: $urlDestino"); 
        }
    }


    /**
     * 1.18.4 (D-503): "Atualize seus dados" — a tela que o portão (AdmsAtualizarDados) abre enquanto a marca
     * "Atualizar Dados" está ligada. GET mostra os dados do "Meu perfil" já preenchidos; o POST (mesma rota) grava,
     * desliga a marca e segue para a Página Inicial. Sem a marca, volta para a Página Inicial.
     */
    public function atualizarDados()
    {
        if (!\App\adms\Models\helper\AdmsAtualizarDados::pendente()) {
            header('Location: ' . URLADM . 'home/index');
            return;
        }
        $this->dadosForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (!empty($this->dadosForm['AtualizarDados'])) {
            unset($this->dadosForm['AtualizarDados']);
            $this->dadosForm['imagem_nova'] = (!empty($_FILES['imagem_nova']) ? $_FILES['imagem_nova'] : null);
            $atualizar = new \App\adms\Models\perfil\AdmsAtualizarDadosPerfil();
            $atualizar->atualizar($this->dadosForm);
            if ($atualizar->getResultado()) {
                header('Location: ' . URLADM . 'home/index');
                return;
            }
            $atual = (new \App\adms\Models\perfil\AdmsEditarPerfil())->verPerfil()[0] ?? [];
            unset($this->dadosForm['imagem_nova']);
            $this->dados['form'] = array_merge($atual, $this->dadosForm); // os dados digitados voltam (não há senha nesta tela)
        } else {
            $this->dados['form'] = (new \App\adms\Models\perfil\AdmsEditarPerfil())->verPerfil()[0] ?? [];
        }
        $this->dados['paginaAtiva'] = $this->pagina;
        /* o menu, enquanto a marca está ligada, é só o Sair */
        $this->dados['menus'] = array_values(array_filter((new \App\adms\Models\AdmsMenus())->itemMenu() ?: [], fn($m) => ($m['menu_controller'] ?? '') === 'login'));
        $this->dados['select'] = (new \App\adms\Models\perfil\AdmsEditarPerfil())->listarCadastro();
        $carregarView = new \Core\ConfigView("adms/Views/perfil/atualizarDados", $this->dados);
        $carregarView->renderizar('tabler');
    }
}
