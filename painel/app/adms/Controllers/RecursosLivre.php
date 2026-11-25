<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\helper\AdmsTipoInstituicao as Tipo;

/**
 * OS RECURSOS DO CURSO LIVRE (Fechamento; D-106) — só no curso livre (a página tem a marca "livre"). O financeiro e a
 * matrícula pela secretaria, que a instalação já liga ou desliga, ligados e desligados de novo aqui. Desligar não apaga
 * nada: as telas saem do menu e não abrem; ligar de novo traz tudo como estava.
 */
class RecursosLivre
{
    private array $dados = [];

    public function index(): void
    {
        $this->dados['paginaAtiva'] = 597;
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
        $this->dados['recursos'] = [];
        foreach (\VcVocabulario::RECURSOS_LIVRE as $r => [$nome, $frase]) {
            $this->dados['recursos'][$r] = ['nome' => $nome, 'frase' => $frase, 'ligado' => Tipo::recursoLigado($r)];
        }
        (new \Core\ConfigView('adms/Views/recursosLivre/index', $this->dados))->renderizar('tabler');
    }

    public function salvar(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && Tipo::ehLivre()) {
            $marcados = (array) ($_POST['recurso'] ?? []);
            $ligados = [];
            foreach (\VcVocabulario::RECURSOS_LIVRE as $r => $x) {
                $ligados[$r] = isset($marcados[$r]);
            }
            Tipo::gravarRecursos($ligados);
            $txt = [];
            foreach (\VcVocabulario::RECURSOS_LIVRE as $r => [$nome]) {
                $txt[] = $nome . ': ' . ($ligados[$r] ? 'ligado' : 'desligado');
            }
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible' id='recursosGravados'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Os recursos do curso livre foram gravados. "
                . htmlspecialchars(implode('; ', $txt), ENT_QUOTES, 'UTF-8') . '.</div>';
        }
        header('Location: ' . URLADM . 'recursos-livre/index');
    }
}
