<?php

namespace App\adms\Controllers;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class Usuarios
{
    private $pagina = 8;
    private $dados;
    private $dadosForm;
    private $dadosId;

    public function listar()
    {
        $this->dados['paginaAtiva'] = $this->pagina;
        $botao = ['vis_user' => ['menu_controller' => 'usuarios', 'menu_metodo' => 'visualizar'],
        'cad_user' => ['menu_controller' => 'usuarios', 'menu_metodo' => 'cadastrar'],
        'edit_user' => ['menu_controller' => 'usuarios', 'menu_metodo' => 'editar'],
        'del_user' => ['menu_controller' => 'usuarios', 'menu_metodo' => 'apagar'],
        'list_niv' => ['menu_controller' => 'perfis-acesso', 'menu_metodo' => 'index'],]; // Onda 3, Etapa 3: a tela de níveis saiu; os perfis, só leitura
        $visBotao = new \App\adms\Models\AdmsBotao();
        $this->dados['botao'] = $visBotao->valBotao($botao);

        $listMenu = new \App\adms\Models\AdmsMenus();
        $this->dados['menus'] = $listMenu->itemMenu();

        $listUser = new \App\adms\Models\usuarios\AdmsListarUsuarios();
        $this->dados['listUsers'] = $listUser->listUsuarios();

        $carregarView = new \Core\ConfigView("adms/Views/usuarios/listUsuarios", $this->dados);
        $carregarView->renderizar('tabler');
    }

    public function cadastrar()
    {
        $this->dadosForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if(!empty($this->dadosForm['CadUsuario'])){
            unset($this->dadosForm['CadUsuario']);
            $this->dadosForm['imagem_nova'] = ($_FILES['imagem_nova'] ? $_FILES['imagem_nova'] : null);
            $cadUsuario = new \App\adms\Models\usuarios\AdmsCadastrarUsuarios();
            $cadUsuario->cadUsuarios($this->dadosForm);
            if($cadUsuario->getResultado()){
                $urlDestino = URLADM . 'usuarios/listar';
                header("Location: $urlDestino");
            }else{
                $this->dados['form'] = $this->dadosForm;
                $this->cadastrarView();
            }
        }else{
            $this->cadastrarView();
        }
    }

    private function cadastrarView()
    {
        $this->dados['paginaAtiva'] = $this->pagina;
        $listMenu = new \App\adms\Models\AdmsMenus();
        $this->dados['menus'] = $listMenu->itemMenu();

        $listarSelect = new \App\adms\Models\usuarios\AdmsCadastrarUsuarios();
        $this->dados['select'] = $listarSelect->listarCadastro();

        $carregarView = new \Core\ConfigView("adms/Views/usuarios/cadUsuario", $this->dados);
        $carregarView->renderizar('tabler');
    } 

    public function visualizar($dadosId = null)
    {
        $this->dadosId = (int) $dadosId;
        if(!empty($this->dadosId)){
            $this->dados['paginaAtiva'] = $this->pagina;
            $botao = ['edit_user' => ['menu_controller' => 'usuarios', 'menu_metodo' => 'editar'],
            'edit_senha' => ['menu_controller' => 'usuarios', 'menu_metodo' => 'editar-senha'],];
            $visBotao = new \App\adms\Models\AdmsBotao();
            $this->dados['botao'] = $visBotao->valBotao($botao);
            $this->dados['podeAlterar'] = \App\adms\Models\helper\AdmsHierarquia::podeAlterarUsuario($this->dadosId); // 1.18.3 (D-497)

            $listMenu = new \App\adms\Models\AdmsMenus();
            $this->dados['menus'] = $listMenu->itemMenu();

            $visUser = new \App\adms\Models\usuarios\AdmsVerUsuario();
            $this->dados['info_user'] = $visUser->visUsuario($this->dadosId);

            $carregarView = new \Core\ConfigView("adms/Views/usuarios/verUsuario", $this->dados);
            $carregarView->renderizar('tabler');

        }else{
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro:Usuário não encontrado!</div>";
            $urlDestino = URLADM . 'usuários/listar';
            header("Location: $urlDestino"); 
        }
    }

    public function editar($dadosId = null)
    {
        $this->dadosId = (int) $dadosId;
        $this->dadosForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if(!empty($this->dadosId) && !\App\adms\Models\helper\AdmsHierarquia::podeAlterarUsuario($this->dadosId)){
            /* 1.18.3 (D-497): nível igual ou acima do seu (ou você mesmo: o próprio cadastro é pelo Perfil) */
            \App\adms\Models\helper\AdmsHierarquia::recusar();
            header("Location: " . URLADM . 'usuarios/listar');
            return;
        }
        if (is_array($this->dadosForm)) {
            $this->dadosForm['id'] = $this->dadosId; // o id é o da URL, já conferido (não o do POST)
        }
        if(!empty($this->dadosId)){
            $this->editPrev();
        }else{
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro:Usuário não encontrado!</div>";
            $urlDestino = URLADM . 'usuarios/listar';
            header("Location: $urlDestino"); 
        }
    }

    private function editPrev()
    {
        if(!empty($this->dadosForm['EditUsuario'])){
            unset($this->dadosForm['EditUsuario']);
            $this->dadosForm['imagem_nova'] = ($_FILES['imagem_nova'] ? $_FILES['imagem_nova'] : null);
            $altPg = new \App\adms\Models\usuarios\AdmsEditarUsuario();
            $altPg->editUsuarios($this->dadosForm);
            if($altPg->getResultado()){
                $urlDestino = URLADM . 'usuarios/visualizar/'.$this->dadosId;
                header("Location: $urlDestino"); 
            }else{
                $this->dados['form'] = $this->dadosForm;
                $this->editView();
            }
        }else{
            $visUs = new \App\adms\Models\usuarios\AdmsEditarUsuario();
            $this->dados['form'] = $visUs->verUsuario($this->dadosId);
            $this->editView();
        }

    }

    private function editView()
    {
        if(!empty($this->dados['form'])){
            $this->dados['paginaAtiva'] = $this->pagina;
            $botao = ['vis_user' => ['menu_controller' => 'usuarios', 'menu_metodo' => 'visualizar'],];
            $visBotao = new \App\adms\Models\AdmsBotao();
            $this->dados['botao'] = $visBotao->valBotao($botao);
            
            $listMenu = new \App\adms\Models\AdmsMenus();
            $this->dados['menus'] = $listMenu->itemMenu();
            
            $listarSelect = new \App\adms\Models\usuarios\AdmsCadastrarUsuarios();
            $this->dados['select'] = $listarSelect->listarCadastro();
            
            $carregarView = new \Core\ConfigView("adms/Views/usuarios/editUsuario", $this->dados);
            $carregarView->renderizar('tabler');
        
        }else{

            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro:Usuário não encontrado!</div>";
            $urlDestino = URLADM . 'usuarios/listar';
            header("Location: $urlDestino"); 
        }

    }

    public function apagar()
    {
        $this->dadosForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if(!empty($this->dadosForm['ApagarUsuario'])){
            unset($this->dadosForm['ApagarUsuario']);
            $apagarUsuario = new \App\adms\Models\usuarios\AdmsApagarUsuario();
            $apagarUsuario->deletarUsuario($this->dadosForm);
            if($apagarUsuario->getResultado()){
                $urlDestino = URLADM . 'usuarios/listar';
                header("Location: $urlDestino"); 
            }else{
                $urlDestino = URLADM . 'usuarios/listar';
                header("Location: $urlDestino"); 
            }
        }else{
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro:Usuário não encontrado!</div>";
            $urlDestino = URLADM . 'usuarios/listar';
            header("Location: $urlDestino"); 
        }

    }

    public function editarSenha($dadosId = null)
    {
        $this->dadosId = (int) $dadosId;
        $this->dadosForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if(!empty($this->dadosId)){
            if(!empty($this->dadosForm['EditSenhaUsuario'])){
                unset($this->dadosForm['EditSenhaUsuario']);
                $this->dadosForm['id'] = $this->dadosId; // 1.18.3 (D-497): o da URL; o model confere o nível
                $altSenhaUser = new \App\adms\Models\usuarios\AdmsEditarSenhaUsuario();
                $altSenhaUser->editSenhaUsuarios($this->dadosForm);
                if($altSenhaUser->getResultado()){
                    $urlDestino = URLADM . 'usuarios/visualizar/'.$this->dadosId;
                }else{
                    $urlDestino = URLADM . 'usuarios/visualizar/'.$this->dadosId;
                }
            }else{
                $urlDestino = URLADM . 'usuarios/visualizar/'.$this->dadosId;
            }
            header("Location: $urlDestino"); 
        }else{
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro:Usuário não encontrado!</div>";
            $urlDestino = URLADM . 'usuarios/listar';
            header("Location: $urlDestino"); 
        }
    }

}