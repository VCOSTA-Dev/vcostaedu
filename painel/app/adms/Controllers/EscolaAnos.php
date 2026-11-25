<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\escola\AdmsEtapas as E;
use App\adms\Models\vinculo\AdmsVinculoRegras as R;

/**
 * O ANO LETIVO E AS ETAPAS (Onda 4, Etapa 2; docs/ESCOLA.md) — só na escola. Os anos letivos cadastrados; abrir um ano
 * com bimestres (o padrão) ou trimestres; os nomes e as datas das etapas e do conselho. Depois de haver lançamento no
 * ano, o modelo não muda.
 */
class EscolaAnos
{
    private array $dados = [];

    public function index(): void
    {
        $this->dados['paginaAtiva'] = 509;
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
        $this->dados['anos'] = E::anos();
        (new \Core\ConfigView('adms/Views/escolaAnos/index', $this->dados))->renderizar('tabler');
    }

    public function abrir(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $ano = (int) ($_POST['ano'] ?? 0);
            $r = E::abrir($ano, (string) ($_POST['modelo'] ?? ''));
            $_SESSION['msg'] = R::aviso($r === true ? 'success' : 'danger', $r === true ? "O ano letivo {$ano} está aberto, com as etapas sugeridas. Ajuste as datas se precisar." : $r);
        }
        header('Location: ' . URLADM . 'escola-anos/index');
    }

    public function salvar(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $ano = (int) ($_POST['ano'] ?? 0);
            $r = E::salvar($ano, (array) ($_POST['etapas'] ?? []));
            $_SESSION['msg'] = R::aviso($r === true ? 'success' : 'danger', $r === true ? "As etapas de {$ano} foram gravadas." : $r);
        }
        header('Location: ' . URLADM . 'escola-anos/index');
    }

    /** O endereço antigo da liberação (D-100): a liberação do boletim tem a tela dela; nada é gravado aqui. */
    public function liberar(): void
    {
        $ano = (int) ($_POST['ano'] ?? $_GET['ano'] ?? 0);
        header('Location: ' . URLADM . 'escola-liberacao/index' . ($ano ? '?ano=' . $ano : ''));
    }
}
