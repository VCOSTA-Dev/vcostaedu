<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/** DADOS DA INSTITUIÇÃO (E6a, D-09). Rota 385, só do administrador; formulário com logo. */
class DadosInstituicao
{
    private $dados;
    private $pagina = 385;

    public function editar(): void
    {
        $form = filter_input_array(INPUT_POST, FILTER_DEFAULT) ?: [];
        if (!empty($form['SalvarInstituicao'])) {
            $ok = (new \App\adms\Models\instituicao\AdmsSalvarInstituicao())->salvar($form, $_FILES['logo'] ?? null);
            if ($ok) {
                header('Location: ' . URLADM . 'dados-instituicao/editar');
                return;
            }
            $this->dados['inst'] = $form + (\App\adms\Models\helper\AdmsInstituicao::dados() ?: []);
        } else {
            $this->dados['inst'] = \App\adms\Models\helper\AdmsInstituicao::dados() ?: [];
        }
        $this->dados['paginaAtiva'] = $this->pagina;
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
        $this->dados['ufs'] = \App\adms\Models\polos\AdmsListarPolos::ufs();
        (new \Core\ConfigView('adms/Views/instituicao/formInstituicao', $this->dados))->renderizar('tabler');
    }
}
