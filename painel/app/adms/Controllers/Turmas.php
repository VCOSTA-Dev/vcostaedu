<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\escola\AdmsTurmas as T;
use App\adms\Models\escola\AdmsTurmaAlunos as A;
use App\adms\Models\escola\AdmsTurmaLote as Lote;
use App\adms\Models\vinculo\AdmsVinculoRegras as R;

/**
 * AS TURMAS DA ESCOLA (Onda 4, Etapa 2; docs/ESCOLA.md) — só na instalação do tipo escola (módulo "escola").
 *   listar     as turmas do ano;
 *   cadastrar  a turma (gera as ofertas da série, uma por componente curricular);
 *   editar     nome, turno, sala, capacidade (o nome novo vai para as ofertas e para o Moodle);
 *   ver        a turma: as ofertas com os professores, os alunos, enturmar (em lote, em passos), sair, trocar de turma;
 *   enturmar   POST, em JSON: fase=iniciar (os alunos marcados) | passo — a janela de progresso;
 *   sair       POST: o aluno sai da turma (de todas as ofertas dela);
 *   trocar     POST: o aluno vai para outra turma da mesma série (o lançado vai junto);
 *   horario    o horário da turma na semana (Onda 8, D-71): a grade das aulas do turno nos dias;
 *   gravarHorario  POST, em JSON: uma aula da grade (cada escolha grava na hora).
 */
class Turmas
{
    private array $dados = [];

    private function comum(int $pagina): void
    {
        $this->dados['paginaAtiva'] = $pagina;
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
    }

    private function post(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
    }

    public function listar(): void
    {
        $this->comum(512);
        $ano = (int) ($_GET['ano'] ?? 0);
        $anos = T::opcoes()['anos'];
        $this->dados['ano'] = $ano ?: (int) ($anos[0] ?? 0);
        $this->dados['anos'] = $anos;
        $this->dados['turmas'] = T::listar($this->dados['ano']);
        (new \Core\ConfigView('adms/Views/turmas/listar', $this->dados))->renderizar('tabler');
    }

    public function cadastrar(): void
    {
        if ($this->post()) {
            $r = T::criar($_POST);
            if (is_int($r)) {
                $_SESSION['msg'] = R::aviso('success', \App\adms\Models\helper\AdmsTipoInstituicao::ehLivre() // D-106
                    ? 'Turma criada, com as disciplinas do curso. Indique o professor de cada uma e ponha os alunos na turma.'
                    : 'Turma criada, com as ofertas da série (uma por disciplina). Indique o professor de cada uma e enturme os alunos.');
                header('Location: ' . URLADM . 'turmas/ver/' . $r);
                return;
            }
            $_SESSION['msg'] = R::aviso('danger', $r);
            $this->dados['form'] = $_POST;
        }
        $this->comum(513);
        $this->dados['opcoes'] = T::opcoes();
        (new \Core\ConfigView('adms/Views/turmas/form', $this->dados))->renderizar('tabler');
    }

    public function editar($id = null): void
    {
        $id = (int) $id;
        $t = T::ver($id);
        if (!$t) {
            $_SESSION['msg'] = R::aviso('danger', 'Turma não encontrada.');
            header('Location: ' . URLADM . 'turmas/listar');
            return;
        }
        if ($this->post()) {
            $r = T::editar($id, $_POST);
            if ($r === true) {
                $_SESSION['msg'] = R::aviso('success', 'Turma alterada.');
                header('Location: ' . URLADM . 'turmas/ver/' . $id);
                return;
            }
            $_SESSION['msg'] = R::aviso('danger', $r);
        }
        $this->comum(514);
        $this->dados['turma'] = $t;
        $this->dados['form'] = $this->post() ? $_POST : $t;
        $this->dados['opcoes'] = T::opcoes();
        (new \Core\ConfigView('adms/Views/turmas/form', $this->dados))->renderizar('tabler');
    }

    public function ver($id = null): void
    {
        $id = (int) $id;
        $t = T::ver($id);
        if (!$t) {
            /* 2.3 (D-89): a turma de um segmento que a coordenadora não coordena é recusada como as outras telas do recorte */
            $_SESSION['msg'] = T::foraDoEscopo($id)
                ? "<div class='alert alert-danger alert-dismissible' id='foraDoEscopo'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Esta turma é de um segmento que você não coordena.</div>"
                : R::aviso('danger', 'Turma não encontrada.');
            header('Location: ' . URLADM . 'turmas/listar');
            return;
        }
        $this->comum(515);
        $this->dados['turma'] = $t;
        $this->dados['ofertas'] = T::ofertas($id);
        $this->dados['alunos'] = A::alunos($id);
        $this->dados['candidatos'] = A::candidatos($t);
        $this->dados['lote'] = Lote::estado($id);
        $this->dados['outras'] = array_values(array_filter(T::listar((int) $t['ano']), static fn($o) => (int) $o['modulo_id'] === (int) $t['modulo_id'] && (int) $o['id'] !== $id));
        (new \Core\ConfigView('adms/Views/turmas/ver', $this->dados))->renderizar('tabler');
        Lote::esquecer($id); // o lote concluído saiu na tela: some do banco
    }

    public function enturmar($id = null): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        $id = (int) $id;
        if (!$this->post() || !T::ver($id)) {
            http_response_code(405);
            echo json_encode(['erro' => 'Use a tela da turma.'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $fase = (string) ($_POST['fase'] ?? 'passo');
        if ($fase === 'iniciar') {
            $ids = array_filter(explode(',', (string) ($_POST['alunos'] ?? '')));
            if (!$ids && !Lote::estado($id)) {
                echo json_encode(['erro' => 'Marque ao menos um aluno.'], JSON_UNESCAPED_UNICODE);
                return;
            }
            echo json_encode(Lote::iniciar($id, $ids), JSON_UNESCAPED_UNICODE);
            return;
        }
        session_write_close(); // o passo não segura a sessão (a página pode pedir outro)
        echo json_encode(Lote::passo($id), JSON_UNESCAPED_UNICODE);
    }

    public function sair($id = null): void
    {
        $id = (int) $id;
        if ($this->post()) {
            $r = A::sair($id, (int) ($_POST['usuario_id'] ?? 0), (string) ($_POST['data_saida'] ?? '')); // D-117: a data digitada
            $_SESSION['msg'] = R::aviso($r === true ? 'success' : 'danger', $r === true ? 'O aluno saiu da turma (e de todas as ofertas dela). O que já tinha sido lançado ficou guardado.' : $r);
        }
        header('Location: ' . URLADM . 'turmas/ver/' . $id);
    }

    public function trocar($id = null): void
    {
        $id = (int) $id;
        if ($this->post()) {
            $destino = (int) ($_POST['destino_id'] ?? 0);
            $r = A::trocar($id, (int) ($_POST['usuario_id'] ?? 0), $destino);
            $_SESSION['msg'] = R::aviso($r === true ? 'success' : 'danger', $r === true ? 'O aluno mudou de turma. As notas e as faltas já lançadas foram junto.' : $r);
            if ($r === true) {
                header('Location: ' . URLADM . 'turmas/ver/' . $destino);
                return;
            }
        }
        header('Location: ' . URLADM . 'turmas/ver/' . $id);
    }

    /** Onda 8 (D-71): o horário da turma na semana — a grade das aulas do turno nos dias; cada escolha grava na hora. */
    public function horario($id = null): void
    {
        $t = T::ver((int) $id);
        if (!$t) {
            $_SESSION['msg'] = R::aviso('danger', 'Turma não encontrada.');
            header('Location: ' . URLADM . 'turmas/listar');
            return;
        }
        $this->comum(512);
        $turno = \App\adms\Models\escola\AdmsHorarioTurma::turno($t);
        $this->dados['turma'] = $t;
        $this->dados['turno'] = $turno;
        $this->dados['turnoNome'] = array_column(\App\adms\Models\horarioAula\AdmsAulasDoDia::turnos(), 'nome', 'id')[$turno] ?? '';
        $this->dados['aulas'] = \App\adms\Models\horarioAula\AdmsAulasDoDia::ativas($turno);
        $this->dados['ofertas'] = T::ofertas((int) $t['id']);
        $this->dados['grade'] = \App\adms\Models\escola\AdmsHorarioTurma::grade((int) $t['id']);
        (new \Core\ConfigView('adms/Views/turmas/horario', $this->dados))->renderizar('tabler');
    }

    /** POST, em JSON: uma aula do horário da turma (dia, aula, oferta; oferta 0 = vaga). */
    public function gravarHorario($id = null): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        $t = T::ver((int) $id);
        if (!$this->post() || !$t) {
            http_response_code(405);
            echo json_encode(['ok' => false, 'erro' => 'Use a tela do horário da turma.'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $r = \App\adms\Models\escola\AdmsHorarioTurma::gravar($t, (int) ($_POST['dia'] ?? 0), (int) ($_POST['aula'] ?? 0), (int) ($_POST['oferta'] ?? 0), (int) ($_SESSION['colUsuario_id'] ?? 0));
        if (!$r['ok']) {
            http_response_code(422);
        }
        echo json_encode($r, JSON_UNESCAPED_UNICODE);
    }

    /** Onda 8, correção, acréscimo C (D-80): os boletins de todos os alunos da turma, num PDF só (um aluno por página). */
    public function boletins($id = null): void
    {
        $t = T::ver((int) $id);
        if (!$t) {
            $_SESSION['msg'] = R::aviso('danger', 'Turma não encontrada.');
            header('Location: ' . URLADM . 'turmas/listar');
            return;
        }
        $rms = array_map(static fn($a) => (string) $a['rm'], A::alunos((int) $t['id']));
        (new \App\adms\Models\academicos\AdmsGerarBoletimPdf())->gerarTurma((int) $t['id'], (int) $t['ano'], $rms);
    }
}
