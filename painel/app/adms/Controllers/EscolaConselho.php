<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\escola\AdmsConselho as C;
use App\adms\Models\vinculo\AdmsVinculoRegras as R;

/**
 * O CONSELHO DE CLASSE (Onda 4, Etapa 3) — só na escola. turma: os alunos com cada componente (a média anual e a
 * situação, como se o ano fechasse agora) e o que o conselho já registrou; registrar: POST, a observação de uma etapa ou
 * a decisão do fim do ano (aprovar quem ficou reprovado, com a observação obrigatória).
 */
class EscolaConselho
{
    private array $dados = [];

    public function turma($id = null): void
    {
        $id = (int) $id;
        $t = \App\adms\Models\escola\AdmsTurmas::ver($id);
        if (!$t) {
            $_SESSION['msg'] = R::aviso('danger', 'Turma não encontrada.');
            header('Location: ' . URLADM . 'turmas/listar');
            return;
        }
        $this->dados['paginaAtiva'] = 526;
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
        $this->dados['turma'] = $t;
        $this->dados['etapas'] = \App\adms\Models\notas\AdmsCalculoNotas::etapasDoAno((int) $t['ano']);
        $this->dados['quadro'] = C::quadro($id);
        (new \Core\ConfigView('adms/Views/escolaConselho/turma', $this->dados))->renderizar('tabler');
    }

    public function registrar($id = null): void
    {
        $id = (int) $id;
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $r = C::registrar($id, (int) ($_POST['etapa_id'] ?? 0), (string) ($_POST['rm'] ?? ''), (string) ($_POST['decisao'] ?? ''), (string) ($_POST['observacao'] ?? ''));
            $_SESSION['msg'] = R::aviso($r === true ? 'success' : 'danger', $r === true ? 'O conselho de classe registrou a decisão.' : $r);
        }
        header('Location: ' . URLADM . 'escola-conselho/turma/' . $id);
    }

    /** B15 (D-100): POST, corrige o texto de uma observação (o autor ou a direção). */
    public function corrigir($id = null): void
    {
        $id = (int) $id;
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $r = C::corrigir($id, (int) ($_POST['registro'] ?? 0), (string) ($_POST['observacao'] ?? ''));
            $_SESSION['msg'] = R::aviso($r === true ? 'success' : 'danger', $r === true ? 'A observação do conselho foi corrigida. O texto de antes ficou no histórico.' : $r);
        }
        header('Location: ' . URLADM . 'escola-conselho/turma/' . $id);
    }

    /** B15 (D-100): POST, retira uma observação ou decisão (o autor ou a direção); fica no histórico. */
    public function retirar($id = null): void
    {
        $id = (int) $id;
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $r = C::retirar($id, (int) ($_POST['registro'] ?? 0));
            $_SESSION['msg'] = R::aviso($r === true ? 'success' : 'danger', $r === true ? 'O registro do conselho foi retirado. Ele ficou no histórico.' : $r);
        }
        header('Location: ' . URLADM . 'escola-conselho/turma/' . $id);
    }
}
