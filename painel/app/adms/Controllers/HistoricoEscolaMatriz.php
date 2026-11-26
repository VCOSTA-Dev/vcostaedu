<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\historicoEscola\AdmsHistBase as B;
use App\adms\Models\historicoEscola\AdmsHistMatriz as M;

/**
 * A MATRIZ CURRICULAR DO HISTÓRICO (Parte B; D-111) — só na escola (páginas 630 a 636).
 *   listar      as matrizes por segmento e ano, com os totais de cada série; criar (vazia ou copiando)
 *   criar       POST
 *   editar      a matriz do ano: os parâmetros, os itinerários, a grade com os totais ao vivo, o registro
 *   salvar      POST: a tela inteira (travada: com o motivo)
 *   travar      POST
 *   folha       o PDF da folha da matriz
 *   salvarArea  POST: a área de conhecimento (o Médio)
 */
class HistoricoEscolaMatriz
{
    private array $dados = [];

    public function listar(): void
    {
        $this->dados['matrizes'] = M::listar();
        $this->dados['grades'] = M::grades();
        $this->dados['pode'] = HistoricoEscola::pode('criar', 'historico-escola-matriz');
        $this->render('adms/Views/historicoEscola/matrizListar', 630);
    }

    public function criar(): void
    {
        $r = self::post() ? M::criar((int) ($_POST['grade_id'] ?? 0), (int) ($_POST['ano'] ?? 0), (int) ($_POST['copiar_de'] ?? 0)) : 'Nada foi enviado.';
        if (is_int($r)) {
            $_SESSION['msg'] = B::aviso('success', 'A matriz do ano foi criada. Confira as aulas semanais de cada linha e salve.', 'matrizCriada');
            header('Location: ' . URLADM . 'historico-escola-matriz/editar/' . $r);
            return;
        }
        $_SESSION['msg'] = B::aviso('danger', $r, 'matrizRecusada');
        header('Location: ' . URLADM . 'historico-escola-matriz/listar');
    }

    public function editar($id = null): void
    {
        $m = M::ver((int) $id);
        if (!$m) {
            $_SESSION['msg'] = B::aviso('danger', 'A matriz não foi encontrada.');
            header('Location: ' . URLADM . 'historico-escola-matriz/listar');
            return;
        }
        $this->dados['m'] = $m;
        $this->dados['totais'] = M::totais((int) $id);
        $this->dados['areas'] = M::areas();
        $this->dados['alteracoes'] = B::todos("SELECT a.*, u.nome AS usuario FROM esc_hist_alteracao a LEFT JOIN adms_usuarios u ON u.id = a.usuario_id
            WHERE a.tabela IN ('esc_hist_matriz', 'esc_hist_matriz_item') AND a.registro_id = ? ORDER BY a.id DESC LIMIT 200", [(int) $id]);
        $this->dados['pode'] = ['salvar' => HistoricoEscola::pode('salvar', 'historico-escola-matriz'), 'travar' => HistoricoEscola::pode('travar', 'historico-escola-matriz'),
            'area' => HistoricoEscola::pode('salvar-area', 'historico-escola-matriz')];
        $this->render('adms/Views/historicoEscola/matrizEditar', 632);
    }

    public function salvar($id = null): void
    {
        $id = (int) $id;
        $r = self::post() ? M::salvar($id, $_POST, (string) ($_POST['motivo'] ?? '')) : 0;
        $_SESSION['msg'] = is_int($r) ? B::aviso($r ? 'success' : 'info', $r ? 'A matriz foi gravada; o que mudou está no registro de alterações.' : 'Nada mudou.', 'matrizGravada') : B::aviso('danger', $r, 'matrizRecusada');
        header('Location: ' . URLADM . 'historico-escola-matriz/editar/' . $id);
    }

    public function travar($id = null): void
    {
        $id = (int) $id;
        $r = self::post() ? M::travar($id) : 'Nada foi enviado.';
        $_SESSION['msg'] = $r === true ? B::aviso('success', 'A matriz foi travada: cada correção, daqui em diante, pede o motivo.', 'matrizTravada') : B::aviso('danger', (string) $r, 'matrizRecusada');
        header('Location: ' . URLADM . 'historico-escola-matriz/editar/' . $id);
    }

    public function folha($id = null): void
    {
        $m = M::ver((int) $id);
        $pdf = $m ? M::folhaPdf((int) $id) : null;
        if ($pdf === null) {
            http_response_code($m ? 500 : 404);
            echo $m ? 'A folha não foi gerada.' : 'A matriz não foi encontrada.';
            return;
        }
        \App\adms\Models\historicoEscola\AdmsHistPdf::enviar($pdf, 'matriz_' . (int) $m['ano'] . '_' . (int) $m['id'] . '.pdf');
    }

    public function salvarArea(): void
    {
        $r = self::post() ? M::salvarArea((int) ($_POST['id'] ?? 0), (string) ($_POST['nome'] ?? ''), (int) ($_POST['ordem'] ?? 0)) : 0;
        $_SESSION['msg'] = is_int($r) && $r > 0 ? B::aviso('success', 'A área foi gravada.', 'areaGravada') : B::aviso('danger', (string) $r, 'areaRecusada');
        $volta = (int) ($_POST['matriz'] ?? 0);
        header('Location: ' . URLADM . ($volta ? 'historico-escola-matriz/editar/' . $volta : 'historico-escola-matriz/listar'));
    }

    private static function post(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
    }

    private function render(string $view, int $pagina): void
    {
        $this->dados['paginaAtiva'] = $pagina;
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
        (new \Core\ConfigView($view, $this->dados))->renderizar('tabler');
    }
}
