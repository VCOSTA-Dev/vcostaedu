<?php

namespace App\adms\Models\moodle;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * "ENVIAR TUDO O QUE FALTA" (Onda 3, Etapa 8), em passos curtos — a janela de progresso, como a demonstração:
 * o estado em adms_instalacao_estado ('moodle.lote'), um passo por chamado, a trava (GET_LOCK) contra dois lotes ao
 * mesmo tempo, e a retomada depois de falha ou de página fechada.
 *
 * O começo confere o que já existia antes da conexão (só nas disciplinas ainda abertas): o aluno enturmado e a
 * disciplina que nunca foram ao Moodle entram na fila. Cada passo envia itens da fila por até TEMPO segundos. Um item
 * recusado fica "falhou" com o motivo e o lote segue; o Moodle fora do ar (ou a chave recusada) PARA o lote — o
 * "Tentar de novo" retoma.
 */
class AdmsMoodleLote
{
    public const ESTADO = 'moodle.lote';
    private const TEMPO = 5.0;
    public const PARA = ['sem_resposta', 'tempo', 'certificado', 'invalidtoken', 'accessexception', 'webservicesdisabled', 'servicenotavailable', 'redirecionamento', 'nao_conectado'];

    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = AdmsMoodle::pdo();
    }

    public function estado(): ?array
    {
        $st = $this->pdo->prepare('SELECT valor FROM adms_instalacao_estado WHERE chave = ?');
        $st->execute([self::ESTADO]);
        $v = $st->fetchColumn();
        return $v === false ? null : json_decode((string) $v, true);
    }

    private function gravar(?array $e): void
    {
        if ($e === null) {
            $this->pdo->prepare('DELETE FROM adms_instalacao_estado WHERE chave = ?')->execute([self::ESTADO]);
            return;
        }
        $this->pdo->prepare('INSERT INTO adms_instalacao_estado (chave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)')
            ->execute([self::ESTADO, json_encode($e, JSON_UNESCAPED_UNICODE)]);
    }

    public static function publico(?array $e): ?array
    {
        if ($e === null) {
            return null;
        }
        $total = max(1, (int) $e['total']);
        return ['passo' => (int) $e['feitos'], 'total' => $total, 'grupoAtual' => 'Os envios ao Moodle', 'falhou' => $e['falhou'] ?? null,
            'concluida' => !empty($e['concluida']), 'enviados' => (int) ($e['enviados'] ?? 0), 'recusados' => (int) ($e['recusados'] ?? 0)];
    }

    private function trava(): string
    {
        return 'vcostaedu_moodle_' . substr(sha1((string) $this->pdo->query('SELECT DATABASE()')->fetchColumn()), 0, 32);
    }

    /** Começa (ou retoma o que parou): confere o que já existia e conta o que falta. */
    public function iniciar(): array
    {
        if (!AdmsMoodle::conectado()) {
            return [false, 'O Moodle não está conectado.'];
        }
        $e = $this->estado();
        if ($e && empty($e['concluida'])) {
            /* a retomada: o item que parou o lote (o Moodle fora do ar, a chave) volta para a fila */
            $this->pdo->exec("UPDATE adms_moodle_envios SET situacao = 'pendente' WHERE situacao = 'falhou' AND codigo IN ('" . implode("','", self::PARA) . "')");
            $e['falhou'] = null;
            $this->gravar($e);
            return [true, ''];
        }
        $this->conferirExistentes();
        $total = (int) $this->pdo->query("SELECT COUNT(*) FROM adms_moodle_envios WHERE situacao IN ('pendente', 'falhou')")->fetchColumn();
        $this->pdo->exec("UPDATE adms_moodle_envios SET situacao = 'pendente' WHERE situacao = 'falhou'");
        $this->gravar(['total' => $total, 'feitos' => 0, 'enviados' => 0, 'recusados' => 0, 'feitosIds' => [], 'falhou' => null, 'concluida' => $total === 0, 'inicio' => date('c')]);
        return [true, ''];
    }

    /** O que já existia antes da conexão, nas disciplinas abertas: a disciplina, o aluno, a enturmação e o professor. */
    private function conferirExistentes(): void
    {
        $abertas = $this->pdo->query('SELECT o.id FROM rm_professores_materias o WHERE o.encerrada_em IS NULL AND (o.idMoodle IS NULL OR o.idMoodle = 0)
                                       AND EXISTS (SELECT 1 FROM portal_disciplinas_alunos a WHERE a.rm_prof_mat_id = o.id)')->fetchAll(\PDO::FETCH_COLUMN);
        foreach ($abertas as $o) {
            AdmsMoodle::enfileirar('oferta', null, (int) $o);
            AdmsMoodle::enfileirar('professor', null, (int) $o);
        }
        $pares = $this->pdo->query("SELECT DISTINCT c.id aluno, a.rm_prof_mat_id oferta FROM portal_disciplinas_alunos a JOIN col_usuarios c ON c.rm = a.rm
                                      JOIN rm_professores_materias o ON o.id = a.rm_prof_mat_id
                                     WHERE o.encerrada_em IS NULL AND c.col_sits_usuario_id <> 5
                                       AND NOT EXISTS (SELECT 1 FROM adms_moodle_envios e WHERE e.chave = CONCAT('enturmar:', c.id, ':', a.rm_prof_mat_id) AND e.situacao IN ('enviado', 'aviso'))")->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($pares as $p) {
            AdmsMoodle::enfileirar('enturmar', (int) $p['aluno'], (int) $p['oferta']);
        }
    }

    /** Um passo: envia itens da fila por até TEMPO segundos. */
    public function passo(): array
    {
        $trava = $this->trava();
        if (!(int) $this->pdo->query("SELECT GET_LOCK('{$trava}', 0)")->fetchColumn()) {
            return ['ocupado' => true];
        }
        try {
            $e = $this->estado();
            if (!$e) {
                return ['erro' => 'Nada em andamento.'];
            }
            if (!empty($e['concluida'])) {
                return self::publico($e);
            }
            $ini = microtime(true);
            /* a busca por e-mail em grupo (até 100 por chamada): os alunos dos próximos itens que ainda não têm conta ligada */
            try {
                /* Onda 8, Etapa 2: o e-mail que vai, pelo modo (o da escola é montado; o do cadastro repetido não vai) */
                $alunos = $this->pdo->query("SELECT DISTINCT u.id, u.rm, u.email FROM adms_moodle_envios e JOIN col_usuarios u ON u.id = e.aluno_id
                                               WHERE e.situacao = 'pendente'
                                                 AND NOT EXISTS (SELECT 1 FROM adms_moodle_vinculos v WHERE v.tipo = 'aluno' AND v.local_id = u.id)
                                               ORDER BY e.id LIMIT 300")->fetchAll(\PDO::FETCH_ASSOC);
                $emails = array_values(array_filter(array_map(static fn ($a) => AdmsMoodle::emailParaMoodle((int) $a['id'], (string) $a['rm'], (string) $a['email'])[0], $alunos)));
                if ($emails) {
                    (new AdmsMoodleEnvio(AdmsMoodle::cliente(), AdmsMoodle::conexao()))->buscarEmails($emails, 'lote');
                }
            } catch (\Throwable $ex) {
                error_log('[vcostaedu][moodle] a busca por e-mail do lote: ' . $ex->getMessage());
            }
            while (microtime(true) - $ini < self::TEMPO) {
                $feitos = $e['feitosIds'] ?: [0];
                $id = (int) $this->pdo->query("SELECT id FROM adms_moodle_envios WHERE situacao = 'pendente' AND id NOT IN (" . implode(',', array_map('intval', $feitos)) . ') ORDER BY id LIMIT 1')->fetchColumn();
                if (!$id) {
                    $e['concluida'] = true;
                    $e['feitos'] = max((int) $e['feitos'], (int) $e['total']);
                    break;
                }
                $sit = AdmsMoodle::enviar($id);
                $codigo = (string) $this->pdo->query('SELECT codigo FROM adms_moodle_envios WHERE id = ' . $id)->fetchColumn();
                if ($sit === 'falhou' && in_array($codigo, self::PARA, true)) {
                    $e['falhou'] = ['motivo' => implode(' ', \VcMoodle::explicar($codigo))];
                    break;
                }
                $e['feitosIds'][] = $id;
                $e['feitos'] = min((int) $e['total'], (int) $e['feitos'] + 1);
                $sit === 'falhou' ? $e['recusados']++ : $e['enviados']++;
            }
            $this->gravar($e);
            return self::publico($e);
        } finally {
            $this->pdo->query("SELECT RELEASE_LOCK('{$trava}')");
        }
    }

    public function limparConcluido(): void
    {
        $e = $this->estado();
        if ($e && !empty($e['concluida'])) {
            $this->gravar(null);
        }
    }
}
