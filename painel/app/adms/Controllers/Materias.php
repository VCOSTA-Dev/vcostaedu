<?php

namespace App\adms\Controllers;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class Materias
{
    private $pagina = 75;
    private $dados;
    private $dadosId;
    private $dadosForm;
    
    public function listar()
    {
        $this->dados['paginaAtiva'] = $this->pagina;
        $botao = ['cad_mat' => ['menu_controller' => 'materias', 'menu_metodo' => 'cadastrar'],
        'edit_mat' => ['menu_controller' => 'materias', 'menu_metodo' => 'editar'],
        'vis_mat' => ['menu_controller' => 'materias', 'menu_metodo' => 'visualizar'],
        'list_cur' => ['menu_controller' => 'cursos', 'menu_metodo' => 'listar'],
        'del_mat' => ['menu_controller' => 'materias', 'menu_metodo' => 'apagar'],
        'sit_mat' => ['menu_controller' => 'materias', 'menu_metodo' => 'situacao'],];
        $visBotao = new \App\adms\Models\AdmsBotao();
        $this->dados['botao'] = $visBotao->valBotao($botao);

        $listMenu = new \App\adms\Models\AdmsMenus();
        $this->dados['menus'] = $listMenu->itemMenu();

        $listaMaterias = new \App\adms\Models\materias\AdmsListarMaterias();
        $this->dados['listaMaterias'] = $listaMaterias->listMaterias();

        $carregarView = new \Core\ConfigView("adms/Views/materias/listMaterias", $this->dados);
        $carregarView->renderizar('tabler');
    }

    public function cadastrar()
    {
        $this->dadosForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (!empty($this->dadosForm['CadMaterias'])) {
            unset($this->dadosForm['CadMaterias']);
            $cadMateriaNova = new \App\adms\Models\materias\AdmsCadastrarMaterias();
            $cadMateriaNova->cadMateria($this->dadosForm);
            if ($cadMateriaNova->getResultado()) {
                $urlDestino = URLADM . 'materias/listar';
                header("Location: $urlDestino");
            } else {
                $this->dados['form'] = $this->dadosForm;
                $this->cadastrarView();
            }
        } else {
            $this->cadastrarView();
        }
    }

    private function cadastrarView()
    {
        $this->dados['paginaAtiva'] = $this->pagina;
        $listMenu = new \App\adms\Models\AdmsMenus();
        $this->dados['menus'] = $listMenu->itemMenu();

        $carregarView = new \Core\ConfigView("adms/Views/materias/cadMaterias", $this->dados);
        $carregarView->renderizar('tabler');
    }

    public function visualizar($dadosId = null)
    {
        $this->dadosId = (int) $dadosId;
        if(!empty($this->dadosId)){
            $this->dados['paginaAtiva'] = $this->pagina;
            $botao = ['edit_mat' => ['menu_controller' => 'materias', 'menu_metodo' => 'editar'],];
            $visBotao = new \App\adms\Models\AdmsBotao();
            $this->dados['botao'] = $visBotao->valBotao($botao);

            $listMenu = new \App\adms\Models\AdmsMenus();
            $this->dados['menus'] = $listMenu->itemMenu();
            
            $visMat = new \App\adms\Models\materias\AdmsVerMaterias();
            $this->dados['info_dado'] = $visMat->visMaterias($this->dadosId);

            $carregarView = new \Core\ConfigView("adms/Views/materias/verMaterias", $this->dados);
            $carregarView->renderizar('tabler');
        }else{
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não foi possível encontrar a matéria!</div>";
            $urlDestino = URLADM . 'materias/listar';
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
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não foi possível encontrar a matéria!</div>";
            $urlDestino = URLADM . 'materias/listar';
            header("Location: $urlDestino"); 
        }
    }

    private function editPrev()
    {
        if(!empty($this->dadosForm['EditMaterias'])){
            unset($this->dadosForm['EditMaterias']);
            $altCurso = new \App\adms\Models\materias\AdmsEditarMaterias();
            $altCurso->editMateria($this->dadosForm);
            if($altCurso->getResultado()){
                $urlDestino = URLADM . 'materias/visualizar/'.$this->dadosId;
                header("Location: $urlDestino"); 
            }else{
                $this->dados['form'] = $this->dadosForm;
                $this->editView();
            }
        }else{
            $visMat = new \App\adms\Models\materias\AdmsEditarMaterias();
            $this->dados['form'] = $visMat->visMaterias($this->dadosId);
            $this->editView();
        }

    }

    private function editView()
    {
        if(!empty($this->dados['form'])){
            $this->dados['paginaAtiva'] = $this->pagina;
            $botao = ['vis_mat' => ['menu_controller' => 'materias', 'menu_metodo' => 'visualizar'],];
            $visBotao = new \App\adms\Models\AdmsBotao();
            $this->dados['botao'] = $visBotao->valBotao($botao);

            $listMenu = new \App\adms\Models\AdmsMenus();
            $this->dados['menus'] = $listMenu->itemMenu();
            
            $carregarView = new \Core\ConfigView("adms/Views/materias/editMaterias", $this->dados);
            $carregarView->renderizar('tabler');
        
        }else{
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não foi possível encontrar a matéria!</div>";
            $urlDestino = URLADM . 'materias/listar';
            header("Location: $urlDestino"); 
        }

    }

    /** E8d (P-8): inativar (situacao=2) ou reativar (situacao=1) a matéria. Só POST (rota 414, administrador e secretaria). */
    public function situacao($dadosId = null)
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            \App\adms\Models\helper\AdmsInativar::alternar('materia', (int) $dadosId, (int) filter_input(INPUT_POST, 'situacao', FILTER_VALIDATE_INT));
        }
        header('Location: ' . URLADM . 'materias/listar');
    }

    public function apagar()
    {
        $this->dadosForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if(!empty($this->dadosForm['ApagarMateria'])){
            unset($this->dadosForm['ApagarMateria']);
            $delMat = new \App\adms\Models\materias\AdmsApagarMaterias();
            $delMat->delMateria($this->dadosForm);
            if($delMat->getResultado()){
                $urlDestino = URLADM . 'materias/listar';
                header("Location: $urlDestino");
            }else{
                $urlDestino = URLADM . 'materias/listar';
                header("Location: $urlDestino");
            }
        }else{
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não foi possível encontrar a materia!</div>";
            $urlDestino = URLADM . 'materias/listar';
            header("Location: $urlDestino");
        }
    }

}
?>