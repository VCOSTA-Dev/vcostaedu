<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\escola\AdmsLiberacaoBoletim as L;
use App\adms\Models\vinculo\AdmsVinculoRegras as R;

/**
 * A LIBERAÇÃO DO BOLETIM (correção da escola, Parte 1; D-100) — só na escola. Por ano letivo, no desenho da tela do
 * sistema de origem: cada etapa, a média final e a situação final, com "Exibir no portal" e "Liberar a partir de".
 *   index   a tela do ano (?ano=; sem ele, o ano mais novo com etapas);
 *   salvar  POST: a tela inteira; só muda (e só vai para o histórico) a linha alterada.
 * Libera: o administrador, a direção acadêmica e a supervisão de secretaria (a ação 109, "Liberar o boletim no portal").
 */
class EscolaLiberacao
{
    private array $dados = [];

    public function index(): void
    {
        $anos = L::anos();
        $ano = (int) ($_GET['ano'] ?? 0);
        if (!in_array($ano, $anos, true)) {
            $ano = (int) ($anos[0] ?? 0);
            foreach ($anos as $a) { // o ano corrente, se tiver etapas
                if ($a === (int) date('Y')) {
                    $ano = $a;
                }
            }
        }
        $this->dados['paginaAtiva'] = 588;
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
        $this->dados['anos'] = $anos;
        $this->dados['ano'] = $ano;
        $this->dados['linhas'] = $ano ? L::linhas($ano) : [];
        $this->dados['historico'] = $ano ? L::historico($ano) : [];
        (new \Core\ConfigView('adms/Views/escolaLiberacao/index', $this->dados))->renderizar('tabler');
    }

    public function salvar(): void
    {
        $ano = (int) ($_POST['ano'] ?? 0);
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $r = L::salvar($ano, (array) ($_POST['item'] ?? []));
            if (is_int($r)) {
                $_SESSION['msg'] = R::aviso($r ? 'success' : 'info', $r ? ($r === 1 ? 'A liberação foi gravada: 1 linha mudou.' : "A liberação foi gravada: {$r} linhas mudaram.") : 'Nada mudou: a liberação continua a mesma.');
            } else {
                $_SESSION['msg'] = R::aviso('danger', $r);
            }
        }
        header('Location: ' . URLADM . 'escola-liberacao/index' . ($ano ? '?ano=' . $ano : ''));
    }
}
