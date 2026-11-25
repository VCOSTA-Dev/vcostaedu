<?php

namespace App\adms\Controllers;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class HorarioAula
{
    private $pagina = 116;
    private $menuPagina = 91;
    private $dados;
    private $dadosId;
    private $dadosForm;
    
    public function listar($dadosId = null)
    {
        $this->dadosId = (int) $dadosId;
        if(!empty($this->dadosForm['PesqPeriodo'])){
            $urlDestino = URLADM . 'horario-aula/listar/'.$this->dadosForm['pesqAno'];
            header("Location: $urlDestino");
        }else{
        $this->dados['paginaAtiva'] = $this->menuPagina;
        $botao = ['list_disc' => ['menu_controller' => 'disciplinas', 'menu_metodo' => 'listar'],
        'cad_sala' => ['menu_controller' => 'horario-aula', 'menu_metodo' => 'cadastrar-sala'],
        'list_sala' => ['menu_controller' => 'salas-de-aula', 'menu_metodo' => 'listar'],
        'edit_hora' => ['menu_controller' => 'horario-aula', 'menu_metodo' => 'visualizar'],];
        $visBotao = new \App\adms\Models\AdmsBotao();
        $this->dados['botao'] = $visBotao->valBotao($botao);

        $listMenu = new \App\adms\Models\AdmsMenus();
        $this->dados['menus'] = $listMenu->itemMenu();

        $ano = (!empty($this->dadosId) ? $this->dadosId : 0);
        $listDisci = new \App\adms\Models\horarioAula\AdmsListarHorarioAula();
        $this->dados['listaDisciplinas'] = $listDisci->listaDisciplinas($ano);

        $listPesquisa = new \App\adms\Models\horarioAula\AdmsListarHorarioAula();
        $this->dados['select'] = $listPesquisa->listarCadastro();

        $carregarView = new \Core\ConfigView("adms/Views/horarioAula/listHorarioAula", $this->dados);
        $carregarView->renderizar('tabler');
        }
    }

    public function visualizar($dadosId = null)
    {
        $this->dadosId = (int) $dadosId;
        if (!empty($this->dadosId)) {
            $verDisciplinaHorario = new \App\adms\Models\horarioAula\AdmsVerHorarioAula();
            $retorna = $verDisciplinaHorario->verHorarios($this->dadosId);
        } else {
            $retorna = ["status" => false, "dados" => "", "msg" => "<div class='alert alert-danger'>ERRO: Falha na comunicação com o sistema!</div>"];
        }
        echo json_encode($retorna);

    }

    public function editar($dadosId = null)
    {
        $this->dadosId = (int) $dadosId;
        $dia = filter_input(INPUT_GET, 'dia', FILTER_DEFAULT);
        $campo = filter_input(INPUT_GET, 'campo', FILTER_DEFAULT);
        $sit = filter_input(INPUT_GET, 'sit', FILTER_DEFAULT);
        if(!empty($this->dadosId) && !empty($dia)){
            $altHorario = new \App\adms\Models\horarioAula\AdmsEditarHorarioAula();
            $altHorario->mudaHorario($this->dadosId, $dia, $campo, $sit);
            $retorna = $altHorario->getResultado();
        }else{
            $retorna = ["status" => false, "semana" => "", "msg" => "<div class='alert alert-danger'>ERRO: Falha na comunicação com o sistema!</div>"];
        }
        echo json_encode($retorna);
    }
    
    public function cadastrarSala($dadosId = null)
    {
        $this->dadosId = (int) $dadosId;
        $this->dadosForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if(!empty($this->dadosForm['AddSalaDisc'])){
            unset($this->dadosForm['AddSalaDisc']);
            $cadSalaHorario = new \App\adms\Models\horarioAula\AdmsCadastrarSala();
            $cadSalaHorario->cadSalaDisciplina($this->dadosForm);
            if($cadSalaHorario->getResultado()){
                $urlDestino = URLADM . 'horario-aula/listar/'.$this->dadosId;
                header("Location: $urlDestino");
            }else{
                $urlDestino = URLADM . 'horario-aula/listar/'.$this->dadosId;
                header("Location: $urlDestino");
            }
        }else{
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não foi possível encontrar a sala!</div>";
            $urlDestino = URLADM . 'horario-aula/listar';
            header("Location: $urlDestino");
        }
    }

}
?>