<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/** NOVIDADES do painel (E6c; rota 405): por versão, da mais nova para a mais antiga, só as do perfil, cada uma com o guia. */
class Novidades
{
    /** E7 (D-218): "Entendi" ou fechar o aviso — marca como visto e volta à página inicial (rota 406, POST). */
    public function visto(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            \App\adms\Models\helper\AdmsNovidades::marcarVisto();
        }
        header('Location: ' . URLADM . 'home/index');
    }

    public function listar(): void
    {
        $perfil = \App\adms\Models\helper\AdmsCentralGuias::perfil();
        $dados = [
            'perfil' => $perfil,
            'versoes' => \App\adms\Models\helper\AdmsNovidades::listar($perfil),
            'guias' => \App\adms\Models\helper\AdmsCentralGuias::visiveis($perfil),
            'rotas' => ['indice' => URLADM . 'ajuda/index', 'guia' => URLADM . 'ajuda/guia/', 'novidades' => URLADM . 'novidades/listar'],
            'paginaAtiva' => 405,
            'menus' => (new \App\adms\Models\AdmsMenus())->itemMenu(),
        ];
        (new \Core\ConfigView('adms/Views/novidades/listar', $dados))->renderizar('tabler');
    }
}
