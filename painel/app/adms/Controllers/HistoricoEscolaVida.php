<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\historicoEscola\AdmsHistBase as B;
use App\adms\Models\historicoEscola\AdmsHistCadastros as C;
use App\adms\Models\historicoEscola\AdmsHistVida as V;

/**
 * A VIDA ESCOLAR (Parte C; D-112) — só na escola (páginas 640 a 646).
 *   buscar       os alunos ativos e os ex-alunos (?q=), com a situação real
 *   aluno        a vida escolar do aluno (?nivel=EF|EM; ?editar=<id> ou ?turma=<id> abre o formulário da série)
 *   confirmar    POST: confirma a proposta de uma turma
 *   salvarSerie  POST: edita a série gravada, ou digita uma nova
 *   tirar        POST
 *   notas        as notas da série (?turma=<id> para a proposta)
 *   salvarNotas  POST
 */
class HistoricoEscolaVida
{
    private array $dados = [];

    public function buscar(): void
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $this->dados['q'] = $q;
        $this->dados['alunos'] = V::buscar($q);
        $this->render('adms/Views/historicoEscola/vidaBuscar', 640);
    }

    public function aluno($id = null): void
    {
        $a = C::aluno((int) $id);
        if (!$a) {
            $_SESSION['msg'] = B::aviso('danger', 'Aluno não encontrado.');
            header('Location: ' . URLADM . 'historico-escola-vida/buscar');
            return;
        }
        $nivel = (string) ($_GET['nivel'] ?? '');
        if (!B::nivelValido($nivel)) {
            $nivel = 'EF';
            foreach (['EM', 'EF'] as $n) {
                if (V::vida((int) $id, $n)) {
                    $nivel = $n;
                    break;
                }
            }
        }
        $this->dados += ['aluno' => $a, 'nivel' => $nivel, 'vida' => V::vida((int) $id, $nivel), 'situacao' => V::situacaoReal((int) $id),
            'escolas' => C::escolas(true), 'observacoes' => array_values(array_filter(C::observacoes($nivel, true), fn($o) => $o['tipo'] === 'selecionavel')),
            'alteracoes' => B::alteracoes(null, (int) $id, 100)];
        $this->dados['pode'] = ['confirmar' => HistoricoEscola::pode('confirmar', 'historico-escola-vida'), 'salvar' => HistoricoEscola::pode('salvar-serie', 'historico-escola-vida'),
            'tirar' => HistoricoEscola::pode('tirar', 'historico-escola-vida'), 'aluno' => HistoricoEscola::pode('salvar-aluno')];
        $this->render('adms/Views/historicoEscola/vidaAluno', 641);
    }

    public function confirmar($id = null): void
    {
        $id = (int) $id;
        $r = self::post() ? V::confirmar($id, (int) ($_POST['turma_id'] ?? 0)) : 'Nada foi enviado.';
        $_SESSION['msg'] = is_int($r) ? B::aviso('success', 'A série foi confirmada na vida escolar.', 'serieConfirmada') : B::aviso('danger', $r, 'serieRecusada');
        header('Location: ' . URLADM . 'historico-escola-vida/aluno/' . $id . '?nivel=' . urlencode((string) ($_POST['nivel'] ?? '')));
    }

    public function salvarSerie($id = null): void
    {
        $id = (int) $id;
        $r = self::post() ? V::salvarSerie((int) ($_POST['id'] ?? 0), $id, $_POST) : 'Nada foi enviado.';
        $_SESSION['msg'] = is_int($r) ? B::aviso('success', 'A série foi gravada.', 'serieGravada') : B::aviso('danger', $r, 'serieRecusada');
        header('Location: ' . URLADM . 'historico-escola-vida/aluno/' . $id . '?nivel=' . urlencode((string) ($_POST['nivel'] ?? '')));
    }

    public function tirar($id = null): void
    {
        $id = (int) $id;
        $r = self::post() ? V::tirar((int) ($_POST['serie_id'] ?? 0), $id) : 'Nada foi enviado.';
        $_SESSION['msg'] = $r === true ? B::aviso('success', 'A série saiu da vida escolar (a confirmada volta a ser proposta).', 'serieTirada') : B::aviso('danger', (string) $r, 'serieRecusada');
        header('Location: ' . URLADM . 'historico-escola-vida/aluno/' . $id . '?nivel=' . urlencode((string) ($_POST['nivel'] ?? '')));
    }

    public function notas($id = null): void
    {
        $alunoId = (int) $id;
        $a = C::aluno($alunoId);
        $s = $a ? V::serie($alunoId, (int) ($_GET['serie'] ?? 0), (int) ($_GET['turma'] ?? 0)) : null;
        if (!$s) {
            $_SESSION['msg'] = B::aviso('danger', 'A série não foi encontrada.');
            header('Location: ' . URLADM . 'historico-escola-vida/aluno/' . $alunoId);
            return;
        }
        $this->dados += ['aluno' => $a, 'serie' => $s, 'quadro' => V::disciplinas($s), 'disciplinas' => array_values(array_filter(C::disciplinas(), fn($d) => $d['id'] > 0 || $d['materia_id'] > 0)),
            'pode' => HistoricoEscola::pode('salvar-notas', 'historico-escola-vida'),
            'alteracoes' => B::todos("SELECT a.*, u.nome AS usuario FROM esc_hist_alteracao a LEFT JOIN adms_usuarios u ON u.id = a.usuario_id WHERE a.tabela = 'esc_hist_vida_nota' AND a.registro_id = ? AND a.aluno_id = ? ORDER BY a.id DESC", [(int) $s['id'], $alunoId])];
        $this->render('adms/Views/historicoEscola/vidaNotas', 645);
    }

    public function salvarNotas($id = null): void
    {
        $alunoId = (int) $id;
        $serie = (int) ($_POST['serie_id'] ?? 0);
        $r = self::post() ? V::salvarNotas($alunoId, $serie, $_POST) : 'Nada foi enviado.';
        $_SESSION['msg'] = is_int($r) ? B::aviso($r ? 'success' : 'info', $r ? 'As notas foram gravadas; o que mudou está no registro.' : 'Nada mudou.', 'notasGravadas') : B::aviso('danger', $r, 'notasRecusadas');
        header('Location: ' . URLADM . 'historico-escola-vida/notas/' . $alunoId . '?serie=' . $serie);
    }

    /** D-116: o itinerário formativo dos alunos da turma do Médio (a matrícula do ano), pela tela da turma. */
    public function itinerarioTurma($id = null): void
    {
        $turmaId = (int) $id;
        $r = self::post() ? V::salvarItinerarios($turmaId, $_POST) : 'Nada foi enviado.';
        $_SESSION['msg'] = is_int($r) ? B::aviso($r ? 'success' : 'info', $r ? 'O itinerário dos alunos foi gravado; o histórico lê dali.' : 'Nada mudou.', 'itinerariosGravados') : B::aviso('danger', $r, 'itinerariosRecusados');
        header('Location: ' . URLADM . 'turmas/ver/' . $turmaId);
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
