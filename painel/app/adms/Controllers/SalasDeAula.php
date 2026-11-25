<?php

namespace App\adms\Controllers;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class SalasDeAula
{
    private $pagina = 119;
    private $menuPagina = 91;
    private $dados;
    private $dadosForm;
    private $dadosId;

    public function listar()
    {
        $this->dados['paginaAtiva'] = $this->pagina;
        $botao = ['vis_sl' => ['menu_controller' => 'salas-de-aula', 'menu_metodo' => 'visualizar'],
        'list_hor' => ['menu_controller' => 'horario-aula', 'menu_metodo' => 'listar'],
        'cad_sl' => ['menu_controller' => 'salas-de-aula', 'menu_metodo' => 'cadastrar'],
        'edit_sl' => ['menu_controller' => 'salas-de-aula', 'menu_metodo' => 'editar'],
        'del_sl' => ['menu_controller' => 'salas-de-aula', 'menu_metodo' => 'apagar'],];
        $visBotao = new \App\adms\Models\AdmsBotao();
        $this->dados['botao'] = $visBotao->valBotao($botao);

        $listMenu = new \App\adms\Models\AdmsMenus();
        $this->dados['menus'] = $listMenu->itemMenu();

        $listSalas = new \App\adms\Models\salaAula\AdmsListarSalaAula();
        $this->dados['listSalas'] = $listSalas->listSalaAula();

        $carregarView = new \Core\ConfigView("adms/Views/salaAula/listSalas", $this->dados);
        $carregarView->renderizar('tabler');
    }

    public function cadastrar()
    {
        $this->dadosForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if(!empty($this->dadosForm['CadSalasAula'])){
            unset($this->dadosForm['CadSalasAula']);
            $cadSalasAula = new \App\adms\Models\salaAula\AdmsCadastrarSala();
            $cadSalasAula->cadSalas($this->dadosForm);
            if($cadSalasAula->getResultado()){
                $urlDestino = URLADM . 'salas-de-aula/listar';
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

        $carregarView = new \Core\ConfigView("adms/Views/salaAula/cadSala", $this->dados);
        $carregarView->renderizar('tabler');

    }

    public function visualizar($dadosId = null)
    {
        $this->dadosId = (int) $dadosId;
        if(!empty($this->dadosId)){
            $this->dados['paginaAtiva'] = $this->pagina;
            $botao = ['edit_sl' => ['menu_controller' => 'salas-de-aula', 'menu_metodo' => 'editar'],];
            $visBotao = new \App\adms\Models\AdmsBotao();
            $this->dados['botao'] = $visBotao->valBotao($botao);

            $listMenu = new \App\adms\Models\AdmsMenus();
            $this->dados['menus'] = $listMenu->itemMenu();
            
            $visSalaAula = new \App\adms\Models\salaAula\AdmsVisualizarSalaAula();
            $this->dados['dados_info'] = $visSalaAula->verSalaAula($this->dadosId);

            $carregarView = new \Core\ConfigView("adms/Views/salaAula/verSala", $this->dados);
            $carregarView->renderizar('tabler');
        }else{
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não foi possível encontrar a sala!</div>";
            $urlDestino = URLADM . 'salas-de-aula/listar';
            header("Location: $urlDestino"); 
        }
    }

    public function editar($dadosId = null)
    {
        $this->dadosId = (int) $dadosId;
        $this->dadosForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if(!empty($this->dadosId)){
            $this->editPrev();
        }else{
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não foi possível encontrar a sala!</div>";
            $urlDestino = URLADM . 'salas-de-aula/listar';
            header("Location: $urlDestino"); 
        }
    }

    private function editPrev()
    {
        if(!empty($this->dadosForm['EditSalaAula'])){
            unset($this->dadosForm['EditSalaAula']);
            $altSalasAula = new \App\adms\Models\salaAula\AdmsEditarSala();
            $altSalasAula->altSalas($this->dadosForm);
            if($altSalasAula->getResultado()){
                $urlDestino = URLADM . 'salas-de-aula/visualizar/'.$this->dadosId;
                header("Location: $urlDestino"); 
            }else{
                $this->dados['form'] = $this->dadosForm;
                $this->editView();
            }
        }else{
            $visSalaAula = new \App\adms\Models\salaAula\AdmsEditarSala();
            $this->dados['form'] = $visSalaAula->visSala($this->dadosId);
            $this->editView();
        }

    }

    private function editView()
    {
        if(!empty($this->dados['form'])){
            $this->dados['paginaAtiva'] = $this->pagina;
            $botao = ['vis_sl' => ['menu_controller' => 'salas-de-aula', 'menu_metodo' => 'visualizar'],];
            $visBotao = new \App\adms\Models\AdmsBotao();
            $this->dados['botao'] = $visBotao->valBotao($botao);

            $listMenu = new \App\adms\Models\AdmsMenus();
            $this->dados['menus'] = $listMenu->itemMenu();
            
            $carregarView = new \Core\ConfigView("adms/Views/salaAula/editSala", $this->dados);
            $carregarView->renderizar('tabler');
        
        }else{
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não foi possível encontrar a sala!</div>";
            $urlDestino = URLADM . 'salas-de-aula/listar';
            header("Location: $urlDestino"); 
        }

    }

    public function apagar()
    {
        $this->dadosForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if(!empty($this->dadosForm['ApagarSalasAula'])){
            unset($this->dadosForm['ApagarSalasAula']);
            $delSaladeAula = new \App\adms\Models\salaAula\AdmsApagarSala();
            $delSaladeAula->delSalaAula($this->dadosForm);
            if($delSaladeAula->getResultado()){
                $urlDestino = URLADM . 'salas-de-aula/listar';
                header("Location: $urlDestino");
            }else{
                $urlDestino = URLADM . 'salas-de-aula/listar';
                header("Location: $urlDestino");
            }
        }else{
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não foi possível encontrar a sala!</div>";
            $urlDestino = URLADM . 'salas-de-aula/listar';
            header("Location: $urlDestino");
        }
    }

}