<?php

namespace App\adms\Controllers;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class MinhasDisciplinas
{
    private $pagina = 115;
    private $dados;
    private $dadosId;
    private $dadosForm;
    
    public function listar($dadosId = null)
    {
        $this->dadosId = (int) $dadosId;
        $this->dadosForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if(!empty($this->dadosForm['PesqPeriodo'])){
            $urlDestino = URLADM . 'minhas-disciplinas/listar/'.$this->dadosForm['pesqAno'];
            header("Location: $urlDestino");
        }else{
            $this->dados['paginaAtiva'] = $this->pagina;
            $botao = ['vis_disc' => ['menu_controller' => 'disciplinas', 'menu_metodo' => 'visualizar']];
            $visBotao = new \App\adms\Models\AdmsBotao();
            $this->dados['botao'] = $visBotao->valBotao($botao);
    
            $listMenu = new \App\adms\Models\AdmsMenus();
            $this->dados['menus'] = $listMenu->itemMenu();
            $ano = (!empty($this->dadosId) ? $this->dadosId : 0);
            $listDisci = new \App\adms\Models\disciplinas\AdmsListarMinhasDisciplinas();
            $this->dados['listaDisciplinas'] = $listDisci->listaDisciplinas($ano);
    
            $listPesquisa = new \App\adms\Models\disciplinas\AdmsListarMinhasDisciplinas();
            $this->dados['select'] = $listPesquisa->listarCadastro();
    
            $carregarView = new \Core\ConfigView("adms/Views/disciplinas/listMinhasDisciplinas", $this->dados);
            $carregarView->renderizar('tabler');
        }
    }

}
?>