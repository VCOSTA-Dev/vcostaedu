<?php

namespace App\adms\Controllers;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class Cursos
{
    private $pagina = 75;
    private $dados;
    private $dadosId;
    private $dadosForm;
    
    public function listar()
    {
        $this->dados['paginaAtiva'] = $this->pagina;
        $botao = ['cad_cur' => ['menu_controller' => 'cursos', 'menu_metodo' => 'cadastrar'],
        'edit_cur' => ['menu_controller' => 'cursos', 'menu_metodo' => 'editar'],
        'add_coord' => ['menu_controller' => 'cursos', 'menu_metodo' => 'cadastrar-usuario'],
        'vis_cur' => ['menu_controller' => 'cursos', 'menu_metodo' => 'visualizar'],
        'list_mat' => ['menu_controller' => 'materias', 'menu_metodo' => 'listar'],
        'ver_matriz' => ['menu_controller' => 'matriz', 'menu_metodo' => 'listar'],
        'del_cur' => ['menu_controller' => 'cursos', 'menu_metodo' => 'apagar'],
        'sit_cur' => ['menu_controller' => 'cursos', 'menu_metodo' => 'situacao'],];
        $visBotao = new \App\adms\Models\AdmsBotao();
        $this->dados['botao'] = $visBotao->valBotao($botao);

        $listMenu = new \App\adms\Models\AdmsMenus();
        $this->dados['menus'] = $listMenu->itemMenu();

        $listCurso = new \App\adms\Models\cursos\AdmsListarCursos();
        $this->dados['listaCursos'] = $listCurso->listCursos();

        $listSelect = new \App\adms\Models\cursos\AdmsListarCursos();
        $this->dados['select'] = $listSelect->listCadastro();

        $carregarView = new \Core\ConfigView("adms/Views/cursos/listCursos", $this->dados);
        $carregarView->renderizar('tabler');
    }

    public function cadastrar()
    {
        $this->dadosForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (!empty($this->dadosForm['CadCurso'])) {
            unset($this->dadosForm['CadCurso']);
            $cadCursoNovo = new \App\adms\Models\cursos\AdmsCadastrarCurso();
            $cadCursoNovo->cadCurso($this->dadosForm);
            if ($cadCursoNovo->getResultado()) {
                $urlDestino = URLADM . 'cursos/listar';
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

        $listSelect = new \App\adms\Models\cursos\AdmsCadastrarCurso();
        $this->dados['select'] = $listSelect->listCadastro();

        $carregarView = new \Core\ConfigView("adms/Views/cursos/cadCursos", $this->dados);
        $carregarView->renderizar('tabler');
    }

    public function visualizar($dadosId = null)
    {
        $this->dadosId = (int) $dadosId;
        if(!empty($this->dadosId)){
            $this->dados['paginaAtiva'] = $this->pagina;
            $botao = ['edit_cur' => ['menu_controller' => 'cursos', 'menu_metodo' => 'editar'], 
            'cad_grade' => ['menu_controller' => 'matriz', 'menu_metodo' => 'cadastrar-grade'],
            'add_coord' => ['menu_controller' => 'cursos', 'menu_metodo' => 'cadastrar-usuario'],
            'ver_matriz' => ['menu_controller' => 'matriz', 'menu_metodo' => 'visualizar'],
            'regra_curso' => ['menu_controller' => 'regra-media', 'menu_metodo' => 'curso'],]; // 1.16.1 (D-447)
            $visBotao = new \App\adms\Models\AdmsBotao();
            $this->dados['botao'] = $visBotao->valBotao($botao);
            /* 1.16.1 (D-447): a regra do TCC e dos documentos do curso (vazio = o padrão) e as regras ativas para escolher */
            $this->dados['regraCurso'] = \App\adms\Models\notas\AdmsRegraMedia::efetivaCurso($this->dadosId);
            $this->dados['regrasMedia'] = \App\adms\Models\notas\AdmsRegraMedia::catalogo(true);
            $this->dados['divergenciaCurso'] = \App\adms\Models\notas\AdmsRegraMedia::divergenciasDoCurso($this->dadosId); // 1.16.2 (D-450)

            $listMenu = new \App\adms\Models\AdmsMenus();
            $this->dados['menus'] = $listMenu->itemMenu();
            
            $visCursos = new \App\adms\Models\cursos\AdmsVerCursos();
            $this->dados['info_dado'] = $visCursos->visCursos($this->dadosId);

            $listSelect = new \App\adms\Models\cursos\AdmsListarCursos();
            $this->dados['select'] = $listSelect->listCadastro();

            $carregarView = new \Core\ConfigView("adms/Views/cursos/verCurso", $this->dados);
            $carregarView->renderizar('tabler');
        }else{
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não foi possível encontrar o ensino!</div>";
            $urlDestino = URLADM . 'cursos/listar';
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
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não foi possível encontrar o ensino!</div>";
            $urlDestino = URLADM . 'cursos/listar';
            header("Location: $urlDestino"); 
        }
    }

    private function editPrev()
    {
        if(!empty($this->dadosForm['EditCursoNovo'])){
            unset($this->dadosForm['EditCursoNovo']);
            $altCurso = new \App\adms\Models\cursos\AdmsEditarCurso();
            $altCurso->editCurso($this->dadosForm);
            if($altCurso->getResultado()){
                $urlDestino = URLADM . 'cursos/visualizar/'.$this->dadosId;
                header("Location: $urlDestino"); 
            }else{
                $this->dados['form'] = $this->dadosForm;
                $this->editView();
            }
        }else{
            $visCurs = new \App\adms\Models\cursos\AdmsEditarCurso();
            $this->dados['form'] = $visCurs->visCurso($this->dadosId);
            $this->editView();
        }

    }

    private function editView()
    {
        if(!empty($this->dados['form'])){
            $this->dados['paginaAtiva'] = $this->pagina;
            $botao = ['vis_cur' => ['menu_controller' => 'cursos', 'menu_metodo' => 'visualizar'],];
            $visBotao = new \App\adms\Models\AdmsBotao();
            $this->dados['botao'] = $visBotao->valBotao($botao);

            $listMenu = new \App\adms\Models\AdmsMenus();
            $this->dados['menus'] = $listMenu->itemMenu();

            $listSelect = new \App\adms\Models\cursos\AdmsCadastrarCurso();
            $this->dados['select'] = $listSelect->listCadastro();
            
            $carregarView = new \Core\ConfigView("adms/Views/cursos/editCursos", $this->dados);
            $carregarView->renderizar('tabler');
        
        }else{
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não foi possível encontrar o ensino!</div>";
            $urlDestino = URLADM . 'cursos/listar';
            header("Location: $urlDestino"); 
        }

    }

    /** E8d (P-8): inativar (situacao=2) ou reativar (situacao=1) o curso. Só POST (rota 413, administrador). */
    public function situacao($dadosId = null)
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            \App\adms\Models\helper\AdmsInativar::alternar('curso', (int) $dadosId, (int) filter_input(INPUT_POST, 'situacao', FILTER_VALIDATE_INT));
        }
        header('Location: ' . URLADM . 'cursos/listar');
    }

    public function apagar()
    {
        $this->dadosForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if(!empty($this->dadosForm['ApagarCurso'])){
            unset($this->dadosForm['ApagarCurso']);
            $delCurso = new \App\adms\Models\cursos\AdmsApagarCurso();
            $delCurso->delCurso($this->dadosForm);
            if($delCurso->getResultado()){
                $urlDestino = URLADM . 'cursos/listar';
                header("Location: $urlDestino");
            }else{
                $urlDestino = URLADM . 'cursos/listar';
                header("Location: $urlDestino");
            }
        }else{
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não foi possível encontrar o ensino!</div>";
            $urlDestino = URLADM . 'cursos/listar';
            header("Location: $urlDestino");
        }
    }

    public function cadastrarUsuario($dadosId = null)
    {
        $this->dadosId = (int) $dadosId;
        $this->dadosForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if(!empty($this->dadosForm['CadCoordenadorCurso'])){
            unset($this->dadosForm['CadCoordenadorCurso']);
            $cadCoord = new \App\adms\Models\cursos\AdmsCadastrarUsuario();
            $cadCoord->cadCoordenador($this->dadosForm);
            if($cadCoord->getResultado()){
                $urlDestino = URLADM . (!empty($this->dadosId) ? 'cursos/visualizar/'.$this->dadosId : 'cursos/listar/' );
                header("Location: $urlDestino");
            }else{
                $urlDestino = URLADM . (!empty($this->dadosId) ? 'cursos/visualizar/'.$this->dadosId : 'cursos/listar/' );
                header("Location: $urlDestino");
            }
        }else{
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não foi possível encontrar o ensino!</div>";
            $urlDestino = URLADM . 'cursos/listar';
            header("Location: $urlDestino");
        }
    }

    public function editarUsuario($dadosId = null)
    {
        $this->dadosId = (int) $dadosId;
        $this->dadosForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if(!empty($this->dadosForm['EditCoordCurso'])){
            unset($this->dadosForm['EditCoordCurso']);
            $altCoord = new \App\adms\Models\cursos\AdmsEditarUsuario();
            $altCoord->editCoordenador($this->dadosForm);
            if($altCoord->getResultado()){
                $urlDestino = URLADM . 'cursos/visualizar/'.$this->dadosId;
                header("Location: $urlDestino");
            }else{
                $urlDestino = URLADM . 'cursos/visualizar/'.$this->dadosId;
                header("Location: $urlDestino");
            }
        }else{
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não foi possível encontrar o ensino!</div>";
            $urlDestino = URLADM . 'cursos/listar';
            header("Location: $urlDestino");
        }
    }

    public function apagarUsuario($dadosId = null)
    {
        $this->dadosId = (int) $dadosId;
        $this->dadosForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if(!empty($this->dadosForm['DesliCoordCurso'])){
            unset($this->dadosForm['DesliCoordCurso']);
            $cadCoord = new \App\adms\Models\cursos\AdmsApagarUsuario();
            $cadCoord->delCoordenador($this->dadosForm);
            if($cadCoord->getResultado()){
                $urlDestino = URLADM . 'cursos/visualizar/'.$this->dadosId;
                header("Location: $urlDestino");
            }else{
                $urlDestino = URLADM . 'cursos/visualizar/'.$this->dadosId;
                header("Location: $urlDestino");
            }
        }else{
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não foi possível encontrar o ensino!</div>";
            $urlDestino = URLADM . 'cursos/listar';
            header("Location: $urlDestino");
        }
    }

}
?>