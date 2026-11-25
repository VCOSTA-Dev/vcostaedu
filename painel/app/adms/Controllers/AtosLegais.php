<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/** ATOS LEGAIS (E6a, D-36). Rotas 386-388, só do administrador. */
class AtosLegais
{
    private $dados;
    private $pagina = 386;

    public function listar(): void
    {
        $this->comum();
        $modelo = new \App\adms\Models\atosLegais\AdmsAtosLegais();
        $this->dados['atos'] = $modelo->listar();
        $hoje = date('Y-m-d');
        $this->dados['vigentes'] = [];
        foreach (\App\adms\Models\modalidades\AdmsModalidades::ativas() as $m) {
            $this->dados['vigentes'][] = ['modalidade' => $m['nome']] + \App\adms\Models\atosLegais\AdmsAtosLegais::vigente((int) $m['id'], $hoje);
        }
        (new \Core\ConfigView('adms/Views/atosLegais/listAtos', $this->dados))->renderizar('tabler');
    }

    public function cadastrar(): void
    {
        $this->formulario(0);
    }

    public function editar($dadosId = null): void
    {
        $this->formulario((int) $dadosId);
    }

    private function formulario(int $id): void
    {
        $modelo = new \App\adms\Models\atosLegais\AdmsAtosLegais();
        if ($id > 0 && !$modelo->ver($id)) {
            header('Location: ' . URLADM . 'atos-legais/listar');
            return;
        }
        $form = filter_input_array(INPUT_POST, FILTER_DEFAULT) ?: [];
        if (!empty($form['SalvarAto'])) {
            if ($modelo->salvar($form, $id) !== false) {
                header('Location: ' . URLADM . 'atos-legais/listar');
                return;
            }
            $this->dados['ato'] = $form;
        } else {
            $this->dados['ato'] = $id > 0 ? $modelo->ver($id) : ['situacao' => 1];
        }
        $this->comum();
        $this->dados['id'] = $id;
        (new \Core\ConfigView('adms/Views/atosLegais/formAto', $this->dados))->renderizar('tabler');
    }

    private function comum(): void
    {
        $this->dados['paginaAtiva'] = $this->pagina;
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
    }
}
