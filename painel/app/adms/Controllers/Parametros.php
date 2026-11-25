<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * Configurações da instituição (E2b). Rotas 364 (listar) e 365 (salvar),
 * que já existiam em adms_paginas sem controller.
 */
class Parametros
{
    private $pagina = 364;
    private $dados;

    public function listar()
    {
        $this->dados['paginaAtiva'] = $this->pagina;
        $listMenu = new \App\adms\Models\AdmsMenus();
        $this->dados['menus'] = $listMenu->itemMenu();

        $model = new \App\adms\Models\parametros\AdmsParametrosInstituicao();
        $this->dados['parametros'] = $model->listar();
        $this->dados['campoLogin'] = \App\adms\Models\helper\AdmsParametro::campoLoginPortal();
        $this->dados['horasLink'] = \App\adms\Models\helper\AdmsTokenAcesso::horasValidade();
        $this->dados['regras'] = \App\adms\Models\helper\AdmsParametro::regrasNotas();
        $this->dados['pendencias'] = $_SESSION['pendencias_login'] ?? [];
        $this->dados['pendenciasCampo'] = $_SESSION['pendencias_login_campo'] ?? '';
        unset($_SESSION['pendencias_login'], $_SESSION['pendencias_login_campo']);

        $carregarView = new \Core\ConfigView("adms/Views/parametros/listParametros", $this->dados);
        $carregarView->renderizar('tabler');
    }

    public function salvar()
    {
        $dadosForm = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (!empty($dadosForm['SalvarParametros'])) {
            $model = new \App\adms\Models\parametros\AdmsParametrosInstituicao();
            $model->salvar($dadosForm);
            if (!$model->getResultado() && $model->getPendencias()) {
                $_SESSION['pendencias_login'] = $model->getPendencias();
                $_SESSION['pendencias_login_campo'] = (string) $dadosForm['portal_login_campo'];
            }
        }
        header("Location: " . URLADM . 'parametros/listar');
    }
}
