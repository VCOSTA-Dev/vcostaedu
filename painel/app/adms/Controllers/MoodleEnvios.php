<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\moodle\AdmsMoodle as M;
use App\adms\Models\moodle\AdmsMoodleLote as Lote;

/**
 * ENVIOS AO MOODLE (Onda 3, Etapa 8), em Integrações, até a Secretaria: a fila com o filtro por situação, o motivo de
 * cada falha em linguagem de uso, "Tentar de novo" e "Enviar tudo o que falta" (em passos, com a janela de progresso).
 * E "Ligar a disciplina a uma do Moodle", da tela da disciplina, quando a escolha é "vincular".
 */
class MoodleEnvios
{
    private array $dados = [];
    public const SITUACOES = ['pendente' => 'Pendente', 'enviado' => 'Enviado', 'aviso' => 'Enviado com aviso', 'falhou' => 'Falhou'];
    public const TIPOS = ['aluno' => 'Aluno', 'oferta' => 'Disciplina', 'enturmar' => 'Enturmação', 'desenturmar' => 'Saída da enturmação', 'situacao' => 'Situação da matrícula', 'professor' => 'Professor da disciplina'];

    private function disponivel(): bool
    {
        if (!M::disponivel()) {
            \App\adms\Models\helper\AdmsAtualizacaoPendente::responderPendente();
            return false;
        }
        return true;
    }

    private static function aviso(string $tipo, string $html, string $id = ''): void
    {
        $_SESSION['msg'] = "<div class='alert alert-{$tipo} alert-dismissible'" . ($id !== '' ? " id='{$id}'" : '') . "><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>{$html}</div>";
    }

    public function listar(): void
    {
        if (!$this->disponivel()) {
            return;
        }
        $situacao = array_key_exists((string) ($_GET['situacao'] ?? ''), self::SITUACOES) ? (string) $_GET['situacao'] : '';
        $pdo = M::pdo();
        $sql = "SELECT e.*, u.nome aluno_nome, u.rm aluno_ra, o.materia, o.codtur, o.ano FROM adms_moodle_envios e
                  LEFT JOIN col_usuarios u ON u.id = e.aluno_id LEFT JOIN rm_professores_materias o ON o.id = e.oferta_id"
            . ($situacao !== '' ? ' WHERE e.situacao = ?' : '') . ' ORDER BY (e.situacao = \'falhou\') DESC, (e.situacao = \'pendente\') DESC, e.id DESC LIMIT 300';
        $st = $pdo->prepare($sql);
        $st->execute($situacao !== '' ? [$situacao] : []);
        $this->dados['paginaAtiva'] = 501;
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
        $this->dados['itens'] = $st->fetchAll(\PDO::FETCH_ASSOC);
        $this->dados['filtro'] = $situacao;
        $this->dados['saude'] = M::saude();
        $this->dados['lote'] = Lote::publico((new Lote())->estado());
        $this->dados['demonstracao'] = \App\adms\Models\helper\AdmsAmbiente::demonstracaoAtiva();
        (new \Core\ConfigView('adms/Views/moodle/envios', $this->dados))->renderizar('tabler');
    }

    /** Tentar de novo um item (na hora, com o tempo curto). */
    public function tentar($id = null): void
    {
        if ($this->disponivel() && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            M::pdo()->prepare("UPDATE adms_moodle_envios SET situacao = 'pendente' WHERE id = ? AND situacao IN ('falhou', 'pendente')")->execute([(int) $id]);
            $sit = M::conectado() ? M::enviar((int) $id) : 'pendente';
            if (in_array($sit, ['enviado', 'aviso'], true)) {
                self::aviso('success', 'Enviado ao Moodle.', 'reenvioOk');
            } else {
                $st = M::pdo()->prepare('SELECT codigo FROM adms_moodle_envios WHERE id = ?');
                $st->execute([(int) $id]);
                [$o, $f] = \VcMoodle::explicar((string) ($st->fetchColumn() ?: 'nao_conectado'));
                self::aviso('warning', htmlspecialchars($o . ' ' . $f, ENT_QUOTES, 'UTF-8'), 'reenvioFalhou');
            }
        }
        header('Location: ' . URLADM . 'moodle-envios/listar');
    }

    /**
     * Escolher a conta (1.36.1): o e-mail do aluno está em mais de uma conta do Moodle; o operador escolhe, entre as
     * contas que a busca achou, qual é a da pessoa. O aluno fica ligado a ela e o envio segue.
     */
    public function escolherConta($id = null): void
    {
        if ($this->disponivel() && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && M::conectado()) {
            $st = M::pdo()->prepare("SELECT * FROM adms_moodle_envios WHERE id = ? AND situacao = 'falhou' AND codigo = 'email_varias_contas'");
            $st->execute([(int) $id]);
            $item = $st->fetch(\PDO::FETCH_ASSOC);
            $conta = (int) ($_POST['moodle_conta'] ?? 0);
            $candidatas = $item ? array_map('intval', (array) json_decode((string) ($item['contas'] ?? ''), true)) : [];
            if (!$item || !$item['aluno_id'] || !in_array($conta, $candidatas, true)) {
                self::aviso('danger', 'Escolha uma das contas da lista.', 'contaRecusada');
            } elseif (!(new \App\adms\Models\moodle\AdmsMoodleEnvio(M::cliente(), M::conexao()))->ligarConta((int) $item['aluno_id'], $conta)) {
                self::aviso('danger', 'O Moodle não achou essa conta agora. Tente de novo em alguns minutos.', 'contaRecusada');
            } else {
                M::pdo()->prepare("UPDATE adms_moodle_envios SET situacao = 'pendente', contas = NULL WHERE id = ?")->execute([(int) $id]);
                $sit = M::enviar((int) $id);
                self::aviso($sit === 'falhou' ? 'warning' : 'success', $sit === 'falhou' ? 'A conta ficou ligada; o envio ainda não foi — o motivo está na lista.' : 'A conta ficou ligada e o envio foi.', 'contaEscolhida');
            }
        }
        header('Location: ' . URLADM . 'moodle-envios/listar');
    }

    /** "Enviar tudo o que falta": a janela de progresso (POST fase=iniciar|passo), JSON. */
    public function enviarTudo(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !M::disponivel()) {
            http_response_code(405);
            echo json_encode(['erro' => 'Use a tela Envios ao Moodle.'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $l = new Lote();
        $fase = (string) ($_POST['fase'] ?? 'estado');
        if ($fase === 'iniciar') {
            [$ok, $msg] = $l->iniciar();
            echo json_encode($ok ? Lote::publico($l->estado()) : ['erro' => $msg], JSON_UNESCAPED_UNICODE);
            return;
        }
        if ($fase === 'passo') {
            session_write_close();
            @ignore_user_abort(true);
            $r = $l->passo();
            if (!empty($r['concluida']) && @session_start()) {
                self::aviso('success', 'Pronto: ' . (int) $r['enviados'] . ' envio(s) feito(s)' . ($r['recusados'] ? ', ' . (int) $r['recusados'] . ' recusado(s) — o motivo de cada um está na lista' : '') . '.', 'loteConcluido');
                $l->limparConcluido();
            }
            echo json_encode($r, JSON_UNESCAPED_UNICODE);
            return;
        }
        echo json_encode(Lote::publico($l->estado()) ?? ['nada' => true], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Ligar a disciplina ofertada a uma disciplina do Moodle (a escolha "vincular"): pelo número dela no Moodle, ou pelo
     * nome curto. Ligada, os alunos enturmados e o professor entram na fila.
     */
    public function ligarOferta($ofertaId = null): void
    {
        $volta = URLADM . 'disciplinas/visualizar/' . (int) $ofertaId;
        if (!$this->disponivel() || ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Location: ' . $volta);
            return;
        }
        if (!M::conectado()) {
            self::aviso('warning', 'O Moodle não está conectado.', 'ligarRecusado');
            header('Location: ' . $volta);
            return;
        }
        $busca = trim((string) ($_POST['moodle_disciplina'] ?? ''));
        $r = ctype_digit($busca) ? M::cliente()->chamar('core_course_get_courses_by_field', ['field' => 'id', 'value' => $busca], 'oferta ' . (int) $ofertaId)
            : M::cliente()->chamar('core_course_get_courses_by_field', ['field' => 'shortname', 'value' => $busca], 'oferta ' . (int) $ofertaId);
        $curso = $r['ok'] ? ($r['dados']['courses'][0] ?? null) : null;
        if ($busca === '' || !$curso) {
            [$o, $f] = $r['ok'] ? ['Nenhuma disciplina do Moodle com esse número ou nome curto.', 'Confira no Moodle (o número aparece no endereço da disciplina: course/view.php?id=…).'] : \VcMoodle::explicar($r['codigo']);
            self::aviso('danger', htmlspecialchars($o . ' ' . $f, ENT_QUOTES, 'UTF-8'), 'ligarRecusado');
            header('Location: ' . $volta);
            return;
        }
        M::pdo()->prepare('UPDATE rm_professores_materias SET idMoodle = ? WHERE id = ?')->execute([(int) $curso['id'], (int) $ofertaId]);
        $st = M::pdo()->prepare('SELECT DISTINCT c.id FROM portal_disciplinas_alunos a JOIN col_usuarios c ON c.rm = a.rm WHERE a.rm_prof_mat_id = ?');
        $st->execute([(int) $ofertaId]);
        foreach ($st->fetchAll(\PDO::FETCH_COLUMN) as $aluno) {
            M::enfileirar('enturmar', (int) $aluno, (int) $ofertaId);
        }
        M::anotar('professor', null, (int) $ofertaId);
        self::aviso('success', 'Disciplina ligada a "' . htmlspecialchars((string) $curso['fullname'], ENT_QUOTES, 'UTF-8') . '" do Moodle. Os alunos enturmados seguem pela fila.', 'ofertaLigada');
        header('Location: ' . $volta);
    }
}
