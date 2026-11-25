<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\escola\AdmsNotasTransferencia as T;
use App\adms\Models\vinculo\AdmsVinculoRegras as R;

/**
 * AS NOTAS DE TRANSFERÊNCIA (correção da escola, Parte 3, A2; D-102) — só na escola. Do aluno que entrou no meio do ano:
 * por disciplina e etapa, a nota e as faltas trazidas da escola de origem, com o nome dela e o documento apresentado.
 *   index   a tela do aluno (?ano=; sem ele, o ano mais novo em que o aluno está numa turma);
 *   salvar  POST: a tela inteira.
 * Lançam a secretaria, a supervisão de secretaria e o administrador (a ação 110); o professor não.
 */
class EscolaTransferencia
{
    private array $dados = [];

    public function index($id = null): void
    {
        $id = (int) $id;
        $aluno = T::aluno($id);
        if (!$aluno) {
            $_SESSION['msg'] = R::aviso('danger', 'Aluno não encontrado.');
            header('Location: ' . URLADM . 'academicos/listar');
            return;
        }
        $anos = T::anos($id);
        $ano = (int) ($_GET['ano'] ?? 0);
        if (!in_array($ano, $anos, true)) {
            $ano = (int) ($anos[0] ?? date('Y'));
        }
        $this->dados['paginaAtiva'] = 592;
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
        $this->dados['aluno'] = $aluno;
        $this->dados['anos'] = $anos;
        $this->dados['ano'] = $ano;
        $this->dados['quadro'] = T::quadro($id, $ano);
        (new \Core\ConfigView('adms/Views/escolaTransferencia/index', $this->dados))->renderizar('tabler');
    }

    public function salvar($id = null): void
    {
        $id = (int) $id;
        $ano = (int) ($_POST['ano'] ?? 0);
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $r = T::salvar($id, $ano, (array) ($_POST['nota'] ?? []), (array) ($_POST['faltas'] ?? []), (string) ($_POST['escola_origem'] ?? ''), (string) ($_POST['documento'] ?? ''));
            if (is_int($r)) {
                $_SESSION['msg'] = R::aviso($r ? 'success' : 'info', $r ? 'As notas de transferência foram gravadas (' . ($r === 1 ? '1 célula mudou' : "{$r} células mudaram") . '); contam na média anual e aparecem marcadas no boletim.' : 'Nada mudou.');
            } else {
                $_SESSION['msg'] = R::aviso('danger', $r);
            }
        }
        header('Location: ' . URLADM . 'escola-transferencia/index/' . $id . ($ano ? '?ano=' . $ano : ''));
    }
}
