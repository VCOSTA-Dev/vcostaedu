<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\academico\AdmsProgressoPortal as Modelo;

/**
 * 1.17.1 (R-POR-01, D-459): Acadêmico → Meu progresso (só o nível Estudante, página 24 de col_paginas). A mesma conta do
 * relatório "Desempenho do aluno" do painel (AdmsProgressoAluno), filtrada pelo aluno da SESSÃO: a matrícula da URL só é
 * aceita se for dele; outra é recusada.
 */
class Progresso
{
    private $pagina = 24;
    private $dados = [];

    public function index(): void
    {
        $aluno = (int) ($_SESSION['academico_id'] ?? 0);
        $matriculas = Modelo::matriculas($aluno);
        $pedida = filter_input(INPUT_GET, 'matricula', FILTER_VALIDATE_INT);
        $ids = array_map('intval', array_column($matriculas, 'id'));
        $recusada = $pedida !== null && $pedida !== false && !in_array((int) $pedida, $ids, true);
        $mc = $recusada ? 0 : (int) ($pedida ?: ($ids[0] ?? 0));
        $this->dados += [
            'paginaAtiva' => $this->pagina, 'menus' => (new \App\adms\Models\AdmsMenus())->itemMenu(),
            'matriculas' => $matriculas, 'mc' => $mc, 'recusada' => $recusada, 'progresso' => $mc ? Modelo::progresso($aluno, $mc) : null,
        ];
        (new \Core\ConfigView('adms/Views/progresso/progresso', $this->dados))->renderizar('tabler');
    }
}
