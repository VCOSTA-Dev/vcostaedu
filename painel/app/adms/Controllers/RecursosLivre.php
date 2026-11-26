<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\helper\AdmsTipoInstituicao as Tipo;

/**
 * OS RECURSOS DA INSTITUIÇÃO (Fechamento, D-106; nos três tipos desde a 1.103.0, D-129). No curso livre, o financeiro e a
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
        foreach (self::lista() as $r => [$nome, $frase]) {
            $this->dados['recursos'][$r] = ['nome' => $nome, 'frase' => $frase, 'ligado' => Tipo::recursoLigado($r)];
        }
        $this->dados['livre'] = Tipo::ehLivre();
        (new \Core\ConfigView('adms/Views/recursosLivre/index', $this->dados))->renderizar('tabler');
    }

    public function salvar(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') { // D-129: nos três tipos (fora do curso livre, só o financeiro)
            $marcados = (array) ($_POST['recurso'] ?? []);
            $ligados = [];
            foreach (self::lista() as $r => $x) {
                $ligados[$r] = isset($marcados[$r]);
            }
            Tipo::gravarRecursos($ligados);
            $txt = [];
            foreach (self::lista() as $r => [$nome]) {
                $txt[] = $nome . ': ' . ($ligados[$r] ? 'ligado' : 'desligado');
            }
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible' id='recursosGravados'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Os recursos foram gravados. "
                . htmlspecialchars(implode('; ', $txt), ENT_QUOTES, 'UTF-8') . '.</div>';
        }
        header('Location: ' . URLADM . 'recursos-livre/index');
    }

    /**
     * D-129 (1.103.0, C4): os recursos de ligar e desligar deste tipo — no curso livre, o financeiro e a matrícula pela
     * secretaria (D-106); na escola e no ensino superior, só o financeiro.
     *
     * @return array<string, array{0:string, 1:string, 2:bool}>
     */
    private static function lista(): array
    {
        if (Tipo::ehLivre()) {
            return \VcVocabulario::RECURSOS_LIVRE;
        }
        return ['financeiro' => ['Financeiro', 'A ficha financeira do aluno, os planos de pagamento, o recebimento, o caixa, os relatórios do financeiro e, no portal, o cartão e a tela do financeiro do aluno e da família.', true]];
    }
}
