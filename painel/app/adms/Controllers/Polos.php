<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/** POLOS (D-34, E3b). Rotas 378-380, só do administrador (nível 1). */
class Polos
{
    private $dados;
    private $pagina = 378;

    public function listar(): void
    {
        $this->comum();
        $this->dados['polos'] = (new \App\adms\Models\polos\AdmsListarPolos())->listar();
        $this->dados['botao'] = (new \App\adms\Models\AdmsBotao())->valBotao([
            'cadastrar' => ['menu_controller' => 'polos', 'menu_metodo' => 'cadastrar'],
            'editar' => ['menu_controller' => 'polos', 'menu_metodo' => 'editar'],
        ]);
        (new \Core\ConfigView('adms/Views/polos/listPolos', $this->dados))->renderizar('tabler');
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
        $form = filter_input_array(INPUT_POST, FILTER_DEFAULT) ?: [];
        $listar = new \App\adms\Models\polos\AdmsListarPolos();
        if ($id > 0 && !$listar->ver($id)) {
            header('Location: ' . URLADM . 'polos/listar');
            return;
        }
        if (!empty($form['SalvarPolo'])) {
            if ((new \App\adms\Models\polos\AdmsSalvarPolo())->salvar($form, $id) !== false) {
                header('Location: ' . URLADM . 'polos/listar');
                return;
            }
            $this->dados['polo'] = $form;
        } else {
            $this->dados['polo'] = $id > 0 ? $listar->ver($id) : ['situacao' => 1];
        }
        $this->comum();
        $this->dados['id'] = $id;
        $this->dados['ufs'] = \App\adms\Models\polos\AdmsListarPolos::ufs();
        (new \Core\ConfigView('adms/Views/polos/formPolo', $this->dados))->renderizar('tabler');
    }

    private function comum(): void
    {
        $this->dados['paginaAtiva'] = $this->pagina;
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
    }
}
