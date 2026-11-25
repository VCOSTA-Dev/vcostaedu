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
                $urlDestino = URLACAD . 'perfil/visualizar/';
                header("Location: $urlDestino"); 
            }else{
                $this->dados['form'] = $this->dadosForm;
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
        $botao = ['vis_perfil' => ['menu_controller' => 'perfil', 'menu_metodo' => 'visualizar'],
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
                $urlDestino = URLACAD . 'perfil/visualizar/';
                header("Location: $urlDestino"); 
            }else{
                /* a senha recusada volta para a tela da senha, com a mensagem (antes, abria a do perfil sem os dados) */
                $visPerfil = new \App\adms\Models\perfil\AdmsEditarPerfil();
                $this->dados['form'] = $visPerfil->verPerfil();
                $this->editarSenhaView();
            }
        }else{
            $visPerfil = new \App\adms\Models\perfil\AdmsEditarPerfil();
            $this->dados['form'] = $visPerfil->verPerfil();
            $this->editarSenhaView();
        }
    }

    private function editarSenhaView()
    {
        $this->dados['paginaAtiva'] = $this->pagina;
        $botao = ['vis_perfil' => ['menu_controller' => 'perfil', 'menu_metodo' => 'visualizar'],
        'edit_perfil' => ['menu_controller' => 'perfil', 'menu_metodo' => 'editar'],];
        $visBotao = new \App\adms\Models\AdmsBotao();
        $this->dados['botao'] = $visBotao->valBotao($botao);

        $listMenu = new \App\adms\Models\AdmsMenus();
        $this->dados['menus'] = $listMenu->itemMenu();

        $carregarView = new \Core\ConfigView("adms/Views/perfil/editSenhaPerfil", $this->dados);
        $carregarView->renderizar('tabler');
    }

}