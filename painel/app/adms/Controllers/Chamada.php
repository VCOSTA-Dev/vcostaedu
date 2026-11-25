<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\faltas\AdmsChamada as M;

/**
 * CHAMADA da oferta (E7, 1.14.0, D-405 a D-409). Rotas 421 listar (encontros e fonte), 422 encontro (fazer/editar a
 * chamada; GET formulário, POST grava), 423 excluir (POST) e 424 fonte (POST). Mesmas permissões de quem lança faltas.
 */
class Chamada
{
    private $dados = [];

    public function listar($dadosId = null): void
    {
        $ofertaId = (int) $dadosId;
        $o = \App\adms\Models\notas\AdmsCalculoNotas::dadosOferta($ofertaId);
        if (!$o) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Disciplina não encontrada.</div>";
            header('Location: ' . URLADM . 'minhas-disciplinas/listar');
            return;
        }
        $this->base($ofertaId, $o, 421);
        $this->dados['encontros'] = M::encontros($ofertaId);
        $this->dados['alunos'] = M::alunos($ofertaId);
        (new \Core\ConfigView('adms/Views/chamada/listChamada', $this->dados))->renderizar('tabler');
    }

    public function encontro($dadosId = null): void
    {
        $ofertaId = (int) $dadosId;
        $encontroId = (int) (filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0);
        $o = \App\adms\Models\notas\AdmsCalculoNotas::dadosOferta($ofertaId);
        if (!$o) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Disciplina não encontrada.</div>";
            header('Location: ' . URLADM . 'minhas-disciplinas/listar');
            return;
        }
        $post = is_array($_POST) ? $_POST : [];
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && !empty($post['SalvarChamada'])) {
            $m = new M();
            if ($m->salvar($ofertaId, $encontroId, $post, (int) $_SESSION['colUsuario_id'])) {
                /* Onda 8, correção (D-75): o jeito de começar (todos presentes ou todos ausentes) fica lembrado para o
                   próximo encontro; o encontro novo criado pela tela abre já gravado, e dali em diante cada clique grava */
                if (!$encontroId && !empty($post['inicio'])) {
                    M::lembrarInicio((int) $_SESSION['colUsuario_id'], (string) $post['inicio']);
                    $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible' id='chamadaSalva'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Encontro criado. Agora, cada clique na presença grava na hora.</div>";
                    header('Location: ' . URLADM . 'chamada/encontro/' . $ofertaId . '?id=' . $m->getId());
                    return;
                }
                $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible' id='chamadaSalva'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Chamada salva. Faltas e total de encontros da oferta atualizados.</div>";
                header('Location: ' . URLADM . 'chamada/listar/' . $ofertaId);
                return;
            }
            $_SESSION['msg'] = "<div class='alert alert-danger' id='chamadaRecusada'><b>Nada foi salvo:</b><ul class='mb-0'><li>"
                . implode('</li><li>', array_map(fn($e) => htmlspecialchars($e, ENT_QUOTES, 'UTF-8'), $m->getErros())) . '</li></ul></div>';
            $this->dados['form'] = $post;
        }
        $e = $encontroId ? M::encontro($encontroId) : null;
        if ($encontroId && (!$e || (int) $e['rm_prof_mat_id'] !== $ofertaId)) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Encontro não encontrado nesta oferta.</div>";
            header('Location: ' . URLADM . 'chamada/listar/' . $ofertaId);
            return;
        }
        $this->base($ofertaId, $o, 422);
        $this->dados['encontro'] = $e;
        $this->dados['alunos'] = M::alunos($ofertaId);
        $this->dados['inicio'] = M::inicio((int) $_SESSION['colUsuario_id']); // Onda 8, correção (D-75)
        (new \Core\ConfigView('adms/Views/chamada/encontro', $this->dados))->renderizar('tabler');
    }

    /**
     * Onda 8, correção (D-75): POST, em JSON — um clique da chamada (o encontro, o RA, ausente 1 ou 0), gravado na hora.
     * Responde se gravou, o motivo da recusa e quantos estão ausentes no encontro.
     */
    public function presenca($dadosId = null): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        $ofertaId = (int) $dadosId;
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false, 'erro' => 'Use a tela da chamada.'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $encontroId = (int) ($_POST['encontro'] ?? 0);
        $m = new M();
        $ok = $m->presenca($ofertaId, $encontroId, trim((string) ($_POST['rm'] ?? '')), (string) ($_POST['ausente'] ?? '0') !== '1', (int) $_SESSION['colUsuario_id']);
        if (!$ok) {
            http_response_code(422);
        }
        $e = M::encontro($encontroId);
        $ausentes = $e && (int) $e['rm_prof_mat_id'] === $ofertaId ? count(array_filter((array) ($e['presencas'] ?? []), static fn($p) => (int) $p === 0)) : 0;
        echo json_encode(['ok' => $ok, 'erro' => $ok ? '' : implode(' ', $m->getErros()), 'ausentes' => $ausentes], JSON_UNESCAPED_UNICODE);
    }

    public function excluir($dadosId = null): void
    {
        $ofertaId = 0;
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $m = new M();
            $ofertaId = $m->excluir((int) $dadosId, (int) $_SESSION['colUsuario_id']);
            $_SESSION['msg'] = $m->getErros()
                ? "<div class='alert alert-danger' id='exclusaoRecusada'>" . htmlspecialchars(implode(' ', $m->getErros()), ENT_QUOTES, 'UTF-8') . '</div>'
                : "<div class='alert alert-success alert-dismissible' id='encontroExcluido'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Encontro excluído. Faltas e total de encontros recalculados.</div>";
        }
        header('Location: ' . URLADM . ($ofertaId ? 'chamada/listar/' . $ofertaId : 'minhas-disciplinas/listar'));
    }

    public function fonte($dadosId = null): void
    {
        $ofertaId = (int) $dadosId;
        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $m = new M();
            $ok = $m->trocarFonte($ofertaId, (string) (filter_input(INPUT_POST, 'fonte') ?? ''), (int) $_SESSION['colUsuario_id']);
            $_SESSION['msg'] = $ok
                ? "<div class='alert alert-success alert-dismissible' id='fonteTrocada'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Fonte da frequência: " . htmlspecialchars(M::rotuloFonte(M::fonte($ofertaId)), ENT_QUOTES, 'UTF-8') . '.</div>'
                : "<div class='alert alert-danger' id='fonteRecusada'><b>A fonte não mudou:</b> " . htmlspecialchars(implode(' ', $m->getErros()), ENT_QUOTES, 'UTF-8') . '</div>';
        }
        header('Location: ' . URLADM . 'chamada/listar/' . $ofertaId);
    }

    private function base(int $ofertaId, array $o, int $pagina): void
    {
        $bt = (new \App\adms\Models\AdmsBotao())->valBotao(['list_disc' => ['menu_controller' => 'disciplinas', 'menu_metodo' => 'listar']]);
        $this->dados += [
            'paginaAtiva' => !empty($bt['list_disc']) ? 91 : 115, 'menus' => (new \App\adms\Models\AdmsMenus())->itemMenu(),
            'oferta' => $o, 'ofertaId' => $ofertaId, 'fonte' => M::fonte($ofertaId), 'encerrada' => !empty($o['encerrada_em']),
            'ficha' => \App\adms\Models\notas\AdmsOfertaInfo::ficha($ofertaId), 'barra' => \App\adms\Models\notas\AdmsOfertaInfo::permissoesBarra(),
            'ehAdmin' => \App\adms\Models\helper\AdmsNivel::podeSensivel(), // Onda 2: reabrir é ação sensível
        ];
    }
}
