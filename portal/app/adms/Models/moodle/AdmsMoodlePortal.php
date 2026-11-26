<?php

namespace App\adms\Models\moodle;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

require_once dirname(__DIR__, 5) . '/core/Moodle.php';

/**
 * A ENTRADA NO MOODLE PELO PORTAL (Onda 3, Etapa 8). O servidor pede ao Moodle um link de entrada de uso único
 * (local_vcostaedu_request_login_url) e redireciona na hora: o link nunca aparece na página, no endereço guardado nem
 * no registro. Não há botão (e o link não é pedido) para o aluno inativo, sem vínculo com o Moodle, nem com a matrícula
 * trancada ou cancelada; nem para a disciplina ainda não enviada. Um limite de pedidos por aluno por hora.
 */
class AdmsMoodlePortal
{
    private static ?\PDO $pdo = null;

    private static function pdo(): \PDO
    {
        if (self::$pdo === null) {
            require_once dirname(__DIR__, 5) . '/core/Banco.php'; // o modo de SQL do sistema
            self::$pdo = \VcBanco::daRequisicao(); // Onda 6, Etapa 1: a conexão da requisição
        }
        return self::$pdo;
    }

    public static function disponivel(): bool
    {
        try {
            return (bool) self::pdo()->query("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'adms_moodle_conexao'")->fetchColumn();
        } catch (\Throwable $e) {
            return false;
        }
    }

    private static function conexao(): array
    {
        $c = self::pdo()->query('SELECT url, chave_cifrada, testada_em FROM adms_moodle_conexao WHERE id = 1')->fetch() ?: [];
        try {
            $c['chave'] = \VcMoodle::decifrar($c['chave_cifrada'] ?? null);
        } catch (\Throwable $e) {
            $c['chave'] = '';
        }
        return $c;
    }

    private static function conectado(array $c): bool
    {
        return !empty($c['url']) && $c['chave'] !== '' && !empty($c['testada_em']);
    }

    /**
     * O aluno mudou o nome ou o e-mail no próprio perfil: o item entra na fila (pendente). O portal não chama o Moodle
     * nessa hora; o envio sai no próximo "Enviar tudo o que falta" (ou no comando agendado). Nunca lança.
     */
    public static function anotarAluno(int $alunoId): void
    {
        try {
            if ($alunoId <= 0 || !self::disponivel() || !self::conectado(self::conexao())) {
                return;
            }
            $pdo = self::pdo();
            $chave = 'aluno:' . $alunoId . ':';
            $st = $pdo->prepare("SELECT COUNT(*) FROM adms_moodle_envios WHERE chave = ? AND situacao IN ('pendente', 'falhou')");
            $st->execute([$chave]);
            if ((int) $st->fetchColumn() === 0) {
                $pdo->prepare('INSERT INTO adms_moodle_envios (tipo, chave, aluno_id) VALUES (?, ?, ?)')->execute(['aluno', $chave, $alunoId]);
            }
        } catch (\Throwable $e) {
            error_log('[vcostaedu][moodle] portal: ' . $e->getMessage());
        }
    }

    /**
     * D-121 (a correção do ensaio, B2.8): no curso livre, o curso em que o aluno está (a matrícula cursando) e a turma
     * aberta dele nesse curso. Não chama o Moodle. @return list<array{curso: string, turma: string, ano: int}>
     */
    public static function cursosDoAluno(int $alunoId): array
    {
        if ($alunoId <= 0) {
            return [];
        }
        $st = self::pdo()->prepare("SELECT c.nome AS curso,
                   (SELECT t.nome FROM esc_turma_alunos ta JOIN esc_turmas t ON t.id = ta.turma_id
                     WHERE ta.usuario_id = m.usuario_id AND t.curso_id = m.curso_id AND ta.saida IS NULL ORDER BY t.ano DESC, t.id DESC LIMIT 1) AS turma,
                   (SELECT t.ano FROM esc_turma_alunos ta JOIN esc_turmas t ON t.id = ta.turma_id
                     WHERE ta.usuario_id = m.usuario_id AND t.curso_id = m.curso_id AND ta.saida IS NULL ORDER BY t.ano DESC, t.id DESC LIMIT 1) AS ano
              FROM rm_matriculas_curso m JOIN rm_cursos c ON c.id = m.curso_id
             WHERE m.usuario_id = ? AND m.situacao_id = 1 ORDER BY m.id DESC");
        $st->execute([$alunoId]);
        return array_map(static fn($r) => ['curso' => (string) $r['curso'], 'turma' => (string) ($r['turma'] ?? ''), 'ano' => (int) ($r['ano'] ?? 0)], $st->fetchAll());
    }

    /**
     * O que o aluno pode abrir: ['entrar' => bool, 'disciplinas' => [[id, materia, codtur, ano]]]. Vazio quando não há
     * conexão, o aluno não está no Moodle, está inativo ou não tem matrícula cursando.
     */
    public static function doAluno(int $alunoId): array
    {
        $nada = ['entrar' => false, 'disciplinas' => []];
        if ($alunoId <= 0 || !self::disponivel() || !self::conectado(self::conexao())) {
            return $nada;
        }
        $pdo = self::pdo();
        $st = $pdo->prepare("SELECT u.rm, u.col_sits_usuario_id sit, v.usuario FROM col_usuarios u JOIN adms_moodle_vinculos v ON v.tipo = 'aluno' AND v.local_id = u.id WHERE u.id = ?");
        $st->execute([$alunoId]);
        $a = $st->fetch();
        if (!$a || (int) $a['sit'] !== 1 || (string) $a['usuario'] === '') {
            return $nada;
        }
        /* as disciplinas enviadas em que ele está enturmado, no curso de uma matrícula CURSANDO */
        $st = $pdo->prepare('SELECT DISTINCT o.id, o.materia, o.codtur, o.ano FROM portal_disciplinas_alunos d JOIN rm_professores_materias o ON o.id = d.rm_prof_mat_id
                              JOIN rm_matriculas_curso m ON m.usuario_id = ? AND m.curso_id = o.curso_id AND m.situacao_id = 1
                             WHERE d.rm = ? AND o.idMoodle > 0 ORDER BY o.ano DESC, o.materia');
        $st->execute([$alunoId, $a['rm']]);
        $disc = $st->fetchAll();
        $cursando = (bool) $pdo->query('SELECT 1 FROM rm_matriculas_curso WHERE usuario_id = ' . (int) $alunoId . ' AND situacao_id = 1 LIMIT 1')->fetchColumn();
        return ['entrar' => $cursando, 'disciplinas' => $cursando ? $disc : []];
    }

    /**
     * Pede o link e devolve [link|null, mensagem para o aluno]. $ofertaId null = a página inicial do Moodle.
     */
    public static function link(int $alunoId, ?int $ofertaId): array
    {
        $pode = self::doAluno($alunoId);
        if (!$pode['entrar'] || ($ofertaId !== null && !in_array($ofertaId, array_map('intval', array_column($pode['disciplinas'], 'id')), true))) {
            return [null, 'O acesso ao Moodle por aqui não está disponível para você agora. Se a sua matrícula está ativa, procure a secretaria.'];
        }
        $pdo = self::pdo();
        $limite = (int) ($pdo->query("SELECT valor FROM adms_parametros WHERE chave = 'moodle.entradas_por_hora'")->fetchColumn() ?: 30);
        $ref = 'aluno ' . $alunoId;
        $st = $pdo->prepare("SELECT COUNT(*) FROM adms_moodle_registro WHERE funcao = 'local_vcostaedu_request_login_url' AND referencia = ? AND created > NOW() - INTERVAL 1 HOUR");
        $st->execute([$ref]);
        if ((int) $st->fetchColumn() >= $limite) {
            return [null, 'Muitas entradas no Moodle na última hora. Espere alguns minutos e tente de novo.'];
        }
        $c = self::conexao();
        $usuario = (string) $pdo->query("SELECT usuario FROM adms_moodle_vinculos WHERE tipo = 'aluno' AND local_id = " . (int) $alunoId . ' ORDER BY id LIMIT 1')->fetchColumn();
        $destino = rtrim((string) $c['url'], '/') . '/my/';
        if ($ofertaId !== null) {
            $curso = (int) $pdo->query('SELECT idMoodle FROM rm_professores_materias WHERE id = ' . (int) $ofertaId)->fetchColumn();
            $destino = rtrim((string) $c['url'], '/') . '/course/view.php?id=' . $curso;
        }
        $m = new \VcMoodle((string) $c['url'], (string) $c['chave'], static function ($funcao, $r, $res, $cod, $ms) use ($pdo) {
            $pdo->prepare('INSERT INTO adms_moodle_registro (funcao, referencia, resultado, codigo, ms) VALUES (?, ?, ?, ?, ?)')->execute([$funcao, mb_substr($r, 0, 60), $res, $cod !== '' ? $cod : null, $ms]);
            if ($res === 'ok') {
                $pdo->exec('UPDATE adms_moodle_conexao SET ultima_ok_em = NOW() WHERE id = 1');
            }
        });
        $r = $m->chamar('local_vcostaedu_request_login_url', ['username' => $usuario, 'returnurl' => $destino], $ref);
        if (!$r['ok']) {
            return [null, self::mensagem($r['codigo'])];
        }
        $link = self::achaLink($r['dados'], (string) $c['url']);
        return $link ? [$link, ''] : [null, self::mensagem('resposta_invalida')];
    }

    /** O link na resposta (o primeiro texto que é um endereço do próprio Moodle). */
    private static function achaLink($dados, string $base): ?string
    {
        $host = parse_url($base, PHP_URL_HOST);
        $achados = [];
        array_walk_recursive($dados, static function ($v) use (&$achados) {
            if (is_string($v) && preg_match('#^https?://#i', $v)) {
                $achados[] = $v;
            }
        });
        foreach ($achados as $u) {
            if (parse_url($u, PHP_URL_HOST) === $host) {
                return $u;
            }
        }
        return null;
    }

    /** A recusa do Moodle em linguagem do aluno, sem código. */
    private static function mensagem(string $codigo): string
    {
        return match ($codigo) {
            'vcostaedu_usersuspended', 'vcostaedu_userdeleted', 'vcostaedu_usernotconfirmed', 'vcostaedu_usernologin', 'vcostaedu_authdisabled', 'vcostaedu_usernotfound'
                => 'A sua conta no Moodle não está liberada para entrar por aqui. Procure a secretaria.',
            'sem_resposta', 'tempo', 'certificado' => 'O Moodle não respondeu agora. Tente de novo em alguns minutos.',
            default => 'Não foi possível abrir o Moodle agora. Tente de novo em alguns minutos; se continuar, procure a secretaria.',
        };
    }
}
