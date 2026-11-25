<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\notas\AdmsRegraMedia as Regra;
use App\adms\Models\notas\AdmsCalculoNotas as Calc;

/**
 * REGRAS DE MÉDIA — o CATÁLOGO (1.16.0, D-436 a D-446). Rotas:
 * 417 listar (Configurações → Regras de média) · 427 editar · 428 cadastrar · 429 duplicar (POST) · 430 inativar/reativar
 * (POST) · 431 padrão (POST, com confirmação; só administrador) · 432 aplicar a disciplinas (lote) · 433 regra da
 * disciplina da matriz (POST) · 419 a regra da OFERTA (e a troca, só aberta, com justificativa) · 420 simulador (JSON).
 * 418 (curso) e o antigo "instituicao" redirecionam ao catálogo: o nível curso saiu (D-436).
 */
class RegraMedia
{
    private $dados = [];

    /* ------------------------------------------------------------------ catálogo */

    public function listar(): void
    {
        $this->tela('lista', 417, ['regras' => Regra::catalogo(), 'admin' => $this->admin()]);
    }

    /** Rotas antigas (1.13.0 a 1.15.2): instituição e curso levam ao catálogo. */
    public function instituicao(): void
    {
        $this->ir('regra-media/listar');
    }

    /**
     * 1.16.1 (D-447): POST (na tela do curso) grava a regra do TCC e dos documentos do curso (`regra_media_id`; vazio =
     * o padrão). GET: o nível curso saiu (1.16.0) — leva ao catálogo.
     */
    public function curso($dadosId = null): void
    {
        $id = (int) $dadosId;
        if ($this->ehPost() && $id > 0) {
            $m = new Regra();
            $r = (int) ($this->post()['regra_media_id'] ?? 0);
            /* 1.16.2 (D-450): antes de salvar, o MESMO aviso da tela do curso, calculado com a regra escolhida; salva só com a confirmação */
            $div = Regra::divergenciasDoCurso($id, $r);
            if ($div['divergencias'] && empty($this->post()['confirmado'])) {
                $_SESSION['msg'] = "<div class='card border-warning' id='confirmarRegraCurso'><div class='card-body'><b>Confira antes de salvar:</b>"
                    . Regra::divergenciaHtml($div, 'avisoDivergenciaSalvar')
                    . "<form method='POST' action='" . URLADM . 'regra-media/curso/' . $id . "' class='mt-2'><input type='hidden' name='regra_media_id' value='" . ($r ?: '') . "'><input type='hidden' name='confirmado' value='1'>"
                    . "<button type='submit' class='btn btn-sm btn-warning' id='confirmarRegraCursoBtn'>Confirmar e salvar assim mesmo</button> <a href='" . URLADM . 'cursos/visualizar/' . $id . "' class='btn btn-sm btn-outline-secondary'>Cancelar</a></form></div></div>";
                $this->ir('cursos/visualizar/' . $id);
                return;
            }
            if ($m->definirRegraDoCurso($id, $r ?: null, $this->usuario())) {
                $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible' id='regraCursoSalva'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Regra de média do TCC e dos documentos salva.</div>";
            } else {
                $this->recusa($m->getErros());
            }
            $this->ir('cursos/visualizar/' . $id);
            return;
        }
        $_SESSION['msg'] = "<div class='alert alert-info'>A regra de média agora é do <b>catálogo</b>, associada às disciplinas da matriz (o nível curso saiu). Para o curso, aplique a regra às disciplinas dele em <b>Aplicar a disciplinas…</b>.</div>";
        $this->ir('regra-media/listar');
    }

    public function cadastrar(): void
    {
        $post = $this->post();
        if (($post['acao'] ?? '') === 'salvar') {
            $m = new Regra();
            if ($m->salvar(0, $post, $this->usuario())) {
                $this->msgSalva('Regra de média criada.', $m->getAvisos());
                $this->ir('regra-media/editar/' . $m->getNovoId());
                return;
            }
            $this->recusa($m->getErros());
            $this->dados['form'] = Regra::doFormulario($post);
            $this->dados['nome'] = (string) ($post['nome'] ?? '');
            $this->dados['descricao'] = (string) ($post['descricao'] ?? '');
        }
        $p = Regra::padrao();
        $this->editor(0, null, $p['regra'], 428);
    }

    public function editar($dadosId = null): void
    {
        $id = (int) $dadosId;
        $cab = Regra::cabecalho($id);
        if (!$cab) {
            $this->naoEncontrada();
            return;
        }
        $post = $this->post();
        if (($post['acao'] ?? '') === 'salvar') {
            if ((int) $cab['padrao'] === 1 && !$this->admin()) {
                $this->recusa(['Só o administrador muda o Padrão da instituição.']);
            } else {
                $m = new Regra();
                if ($m->salvar($id, $post, $this->usuario(), !empty($post['confirmado']))) {
                    $this->msgSalva('Regra de média salva.', $m->getAvisos());
                    $this->ir('regra-media/editar/' . $id);
                    return;
                }
                if ($m->getConfirmacao()) {
                    $this->dados['confirmar'] = $m->getConfirmacao();
                } else {
                    $this->recusa($m->getErros());
                }
            }
            $this->dados['form'] = Regra::doFormulario($post);
            $this->dados['nome'] = (string) ($post['nome'] ?? '');
            $this->dados['descricao'] = (string) ($post['descricao'] ?? '');
        }
        Regra::limparCache();
        $ef = Regra::daRegra($id, 'catalogo');
        $this->editor($id, $cab, $ef['regra'] ?? Regra::padrao()['regra'], 427);
    }

    public function duplicar($dadosId = null): void
    {
        $m = new Regra();
        if ($this->ehPost() && $m->duplicar((int) $dadosId, $this->usuario())) {
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible' id='regraDuplicada'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Cópia criada, sem associações. Ajuste o nome e os componentes e salve.</div>";
            $this->ir('regra-media/editar/' . $m->getNovoId());
            return;
        }
        $this->recusa($m->getErros() ?: ['Use o botão Duplicar.']);
        $this->ir('regra-media/listar');
    }

    public function inativar($dadosId = null): void
    {
        $m = new Regra();
        $reativar = ($this->post()['acao'] ?? '') === 'reativar';
        if ($this->ehPost() && $m->inativar((int) $dadosId, $this->usuario(), $reativar)) {
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible' id='regraSituacao'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>" . ($reativar ? 'Regra reativada: volta a aparecer para escolha.' : 'Regra inativada: não aparece mais para escolha.') . '</div>';
        } else {
            $this->recusa($m->getErros() ?: ['Use o botão da lista.'], 'regraEmUso');
        }
        $this->ir('regra-media/listar');
    }

    /** Trocar o padrão: ação explícita, só administrador, com confirmação (quantas disciplinas mudam). */
    public function padrao($dadosId = null): void
    {
        if (!$this->admin() || !$this->ehPost()) {
            $this->recusa(['Só o administrador troca o Padrão da instituição.']);
            $this->ir('regra-media/listar');
            return;
        }
        $m = new Regra();
        if ($m->definirPadrao((int) $dadosId, $this->usuario(), !empty($this->post()['confirmado']))) {
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible' id='padraoTrocado'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Padrão da instituição trocado.</div>";
            $this->ir('regra-media/listar');
            return;
        }
        if ($m->getConfirmacao()) {
            $this->tela('lista', 417, ['regras' => Regra::catalogo(), 'admin' => true, 'confirmarPadrao' => $m->getConfirmacao() + ['id' => (int) $dadosId]]);
            return;
        }
        $this->recusa($m->getErros());
        $this->ir('regra-media/listar');
    }

    /** APLICAR A DISCIPLINAS (lote): filtro por curso e grade, seleção múltipla, confirmação listando o que muda. */
    public function aplicar($dadosId = null): void
    {
        $id = (int) $dadosId;
        $cab = Regra::cabecalho($id);
        if (!$cab) {
            $this->naoEncontrada();
            return;
        }
        $curso = (int) ($_GET['curso'] ?? 0);
        $grade = (int) ($_GET['grade'] ?? 0);
        $post = $this->post();
        $extra = [];
        if (($post['acao'] ?? '') === 'aplicar') {
            $m = new Regra();
            $linhas = (array) ($post['linhas'] ?? []);
            if ($m->associarDisciplinas($id, $linhas, $this->usuario(), !empty($post['confirmado']))) {
                $this->msgSalva('Regra aplicada às disciplinas escolhidas.', $m->getAvisos());
                $this->ir('regra-media/editar/' . $id . '#onde');
                return;
            }
            if ($m->getConfirmacao()) {
                $extra['confirmar'] = $m->getConfirmacao();
            } else {
                $this->recusa($m->getErros());
            }
            $extra['marcadas'] = array_map('intval', $linhas);
        }
        $ler = new \App\adms\Models\helper\AdmsRead();
        /* 1.18.1 (D-479): curso inativo fora da escolha (o já escolhido continua, para a tela não perder o filtro) */
        $ler->fullRead("SELECT c.id, c.nome, g.id AS grade_id, g.versao FROM rm_cursos c JOIN rm_cursos_grades g ON g.curso_id = c.id WHERE c.situacao = 1 OR c.id = :c ORDER BY c.nome, g.versao", 'c=' . (int) $curso);
        $this->tela('aplicar', 432, $extra + ['regra' => $cab, 'cursosGrades' => $ler->getResultado() ?: [], 'curso' => $curso, 'grade' => $grade,
            'disciplinas' => Regra::disciplinasParaLote($curso, $grade), 'ativa' => (int) $cab['situacao'] === 1]);
    }

    /** A regra da DISCIPLINA DA MATRIZ (na tela da matriz): POST `regra_media_id` (vazio = o padrão). */
    public function disciplina($dadosId = null): void
    {
        $linha = (int) $dadosId;
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT grade_id FROM uni_rh_cursos_disciplinas_grade WHERE id = :id LIMIT :limit", "id={$linha}&limit=1");
        $grade = (int) ($ler->getResultado()[0]['grade_id'] ?? 0);
        if ($this->ehPost() && $grade) {
            $r = (int) ($this->post()['regra_media_id'] ?? 0);
            $m = new Regra();
            if ($m->associarDisciplinas($r ?: null, [$linha], $this->usuario(), true)) {
                $this->msgSalva('Regra de média da disciplina salva.', $m->getAvisos(), 'regraDisciplinaSalva');
            } else {
                $this->recusa($m->getErros());
            }
        }
        $this->ir($grade ? 'matriz/visualizar/' . $grade : 'matriz/listar');
    }

    /** A regra da OFERTA (barra da disciplina): de onde vem e a troca (só aberta, com justificativa). */
    public function oferta($dadosId = null): void
    {
        $id = (int) $dadosId;
        $o = Calc::dadosOferta($id);
        if (!$o) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Oferta não encontrada.</div>";
            $this->ir('disciplinas/listar');
            return;
        }
        $post = $this->post();
        if (($post['acao'] ?? '') === 'trocar') {
            $m = new Regra();
            $r = (string) ($post['regra_media_id'] ?? '');
            if ($m->trocarRegraOferta($id, $r === '' ? null : (int) $r, (string) ($post['justificativa'] ?? ''), $this->usuario())) {
                $this->msgSalva('Regra de média da oferta trocada.', $m->getAvisos(), 'regraOfertaTrocada');
                $this->ir('regra-media/oferta/' . $id);
                return;
            }
            $this->recusa($m->getErros(), 'trocaRecusada');
        }
        Regra::limparCache();
        $ef = Regra::efetivaOferta($id);
        $this->dados['barra'] = \App\adms\Models\notas\AdmsOfertaInfo::permissoesBarra();
        $this->tela('oferta', 419, ['oferta' => $o, 'ef' => $ef, 'resumo' => Regra::resumo($ef['regra']), 'trocas' => Regra::trocasDaOferta($id),
            'regras' => Regra::catalogo(true), 'daDisciplina' => !empty($o['matriz_id']) ? Regra::efetivaDisciplina((int) $o['matriz_id']) : Regra::padrao(),
            'podeEditar' => (new \App\adms\Models\AdmsBotao())->valBotao(['ed' => ['menu_controller' => 'regra-media', 'menu_metodo' => 'editar']])['ed'] ?? false]);
    }

    /** SIMULADOR: a regra do formulário (ou, sem ela, a regra `regra_id` do catálogo) e as notas de teste → JSON do motor. */
    public function simular(): void
    {
        $post = is_array($_POST) ? $_POST : [];
        if (empty($post['regra'])) {
            $ef = (int) ($post['regra_id'] ?? 0) ? (Regra::daRegra((int) $post['regra_id'], 'catalogo') ?? Regra::padrao()) : Regra::padrao();
            $r = $ef['regra'];
            $post['regra'] = ['casas' => $r['casas'], 'arredondamento' => $r['arredondamento'], 'momento' => $r['momento'], 'semNota' => $r['semNota'],
                'componenteVazio' => $r['componenteVazio'], 'notaMinima' => $r['notaMinima']]
                + (!empty($r['acrescimo']['permitir']) ? ['acrescimo' => '1', 'acrescimoLimite' => (string) ($r['acrescimo']['limite'] ?? '')] : []); // Onda 4, Etapa 5
            $post['comp'] = $r['componentes'];
        }
        $res = Regra::simular($post, (string) Calc::regras(null)['frequenciaMinima']);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
    }

    /* ------------------------------------------------------------------ apoio */

    private function editor(int $id, ?array $cab, array $regra, int $pagina): void
    {
        $padrao = $cab && (int) $cab['padrao'] === 1;
        $this->tela('regra', $pagina, [
            'id' => $id, 'cab' => $cab, 'regraAtual' => $regra, 'padrao' => $padrao,
            'podeEditar' => !$padrao || $this->admin(),
            'onde' => $id ? Regra::ondeUsada($id) : ['disciplinas' => [], 'ofertas' => []],
            'encerramentos' => $id ? Regra::encerramentos($id) : 0,
            'ultima' => $id ? Regra::ultimaAlteracao($id) : null,
            'url' => URLADM . ($id ? 'regra-media/editar/' . $id : 'regra-media/cadastrar'),
        ]);
    }

    private function tela(string $view, int $pagina, array $dados): void
    {
        /* VCOSTAEDU (Etapas 5 e 7): todas as telas da regra de média em Tabler */
        $layout = 'tabler';
        $this->dados += $dados + ['paginaAtiva' => 417 === $pagina || $pagina >= 427 ? 417 : $pagina, 'menus' => (new \App\adms\Models\AdmsMenus())->itemMenu()];
        (new \Core\ConfigView('adms/Views/regraMedia/' . $view, $this->dados))->renderizar($layout);
    }

    private function msgSalva(string $texto, array $avisos, string $id = 'regraSalva'): void
    {
        $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible' id='{$id}'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>" . htmlspecialchars($texto, ENT_QUOTES, 'UTF-8') . '</div>';
        if ($avisos) { /* E10 (D-421, D-423): salvo, com aviso */
            $_SESSION['msg'] .= "<div class='alert alert-warning' id='regraAvisos'><b>Atenção:</b><ul class='mb-0'><li>"
                . implode('</li><li>', array_map(fn($e) => htmlspecialchars($e, ENT_QUOTES, 'UTF-8'), $avisos)) . '</li></ul></div>';
        }
    }

    private function recusa(array $erros, string $id = 'regraRecusada'): void
    {
        $_SESSION['msg'] = "<div class='alert alert-danger' id='{$id}'><b>Nada foi salvo:</b><ul class='mb-0'><li>"
            . implode('</li><li>', array_map(fn($e) => htmlspecialchars($e, ENT_QUOTES, 'UTF-8'), $erros)) . '</li></ul></div>';
    }

    private function naoEncontrada(): void
    {
        $_SESSION['msg'] = "<div class='alert alert-danger'>Regra de média não encontrada.</div>";
        $this->ir('regra-media/listar');
    }

    private function post(): array
    {
        return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && is_array($_POST) ? $_POST : [];
    }

    private function ehPost(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
    }

    private function usuario(): int
    {
        return (int) $_SESSION['colUsuario_id'];
    }

    /** Só o administrador (níveis 1 e 2, D-496) muda o padrão (como a regra da instituição, D-398). */
    private function admin(): bool
    {
        return \App\adms\Models\helper\AdmsNivel::ehAdministrador();
    }

    private function ir(string $rota): void
    {
        header('Location: ' . URLADM . $rota);
    }
}
