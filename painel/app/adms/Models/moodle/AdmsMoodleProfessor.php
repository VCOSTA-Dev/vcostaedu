<?php

namespace App\adms\Models\moodle;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

require_once dirname(__DIR__, 5) . '/core/Moodle.php';

/**
 * A ENTRADA DO PROFESSOR NO MOODLE PELO PAINEL (a correção do ensaio, B2.6; D-121). O mesmo mecanismo do aluno no portal
 * (AdmsMoodlePortal): o servidor pede ao Moodle um link de entrada de uso único (local_vcostaedu_request_login_url, a
 * função do plugin, que não muda) com o nome de usuário do professor no Moodle, e redireciona na hora. O link nunca
 * aparece na página, no endereço guardado nem no registro. O professor já é criado e inscrito no curso do Moodle com o
 * papel de professor pelo envio de sempre (AdmsMoodleEnvio: o professor da disciplina). Não há botão sem a conexão, sem
 * o professor no Moodle, para o usuário inativo, nem para a disciplina encerrada ou ainda não enviada. Um limite de
 * pedidos por hora (o mesmo parâmetro do aluno).
 */
class AdmsMoodleProfessor
{
    /** As disciplinas do professor (abertas, ligadas a ele), com o curso e o id no Moodle. @return list<array> */
    public static function disciplinas(int $usuarioId): array
    {
        $st = AdmsMoodle::pdo()->prepare("SELECT o.id, o.materia, o.codtur, o.ano, o.idMoodle, c.nome AS curso
              FROM rm_professores_mat_liga l JOIN rm_professores_materias o ON o.id = l.rm_prof_mat_id
              LEFT JOIN rm_cursos c ON c.id = o.curso_id
             WHERE l.usuario_id = ? AND l.acesso = 1 AND l.situacao = 1 AND o.encerrada_em IS NULL
             ORDER BY o.ano DESC, c.nome, o.materia");
        $st->execute([$usuarioId]);
        return $st->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** O nome de usuário do professor no Moodle ('' sem o vínculo). Sem o nome guardado (vínculo antigo), pergunta ao Moodle uma vez e guarda. */
    private static function usuario(int $usuarioId): string
    {
        $pdo = AdmsMoodle::pdo();
        $st = $pdo->prepare("SELECT moodle_id, usuario FROM adms_moodle_vinculos WHERE tipo = 'professor' AND local_id = ? ORDER BY id LIMIT 1");
        $st->execute([$usuarioId]);
        $v = $st->fetch(\PDO::FETCH_ASSOC);
        if (!$v) {
            return '';
        }
        if ((string) $v['usuario'] !== '') {
            return (string) $v['usuario'];
        }
        $r = AdmsMoodle::cliente()->chamar('core_user_get_users_by_field', ['field' => 'id', 'values' => [(int) $v['moodle_id']]], 'professor ' . $usuarioId);
        $nome = (string) (($r['ok'] ? ($r['dados'][0]['username'] ?? '') : ''));
        if ($nome !== '') {
            $pdo->prepare("UPDATE adms_moodle_vinculos SET usuario = ? WHERE tipo = 'professor' AND local_id = ?")->execute([$nome, $usuarioId]);
        }
        return $nome;
    }

    /** O que a Página Inicial mostra: ['entrar' => bool, 'disciplinas' => as enviadas ao Moodle]. Nunca chama o Moodle. */
    public static function doProfessor(int $usuarioId): array
    {
        $nada = ['entrar' => false, 'disciplinas' => []];
        if ($usuarioId <= 0 || !AdmsMoodle::disponivel() || !AdmsMoodle::conectado()) {
            return $nada;
        }
        $pdo = AdmsMoodle::pdo();
        $st = $pdo->prepare("SELECT u.adms_sits_usuario_id FROM adms_usuarios u JOIN adms_moodle_vinculos v ON v.tipo = 'professor' AND v.local_id = u.id WHERE u.id = ? LIMIT 1");
        $st->execute([$usuarioId]);
        $sit = $st->fetchColumn();
        if ($sit === false || (int) $sit !== 1) {
            return $nada;
        }
        $enviadas = array_values(array_filter(self::disciplinas($usuarioId), static fn($d) => (int) $d['idMoodle'] > 0));
        return ['entrar' => true, 'disciplinas' => $enviadas];
    }

    /** Pede o link e devolve [link|null, mensagem]. $ofertaId null = a página inicial do Moodle. */
    public static function link(int $usuarioId, ?int $ofertaId): array
    {
        $pode = self::doProfessor($usuarioId);
        if (!$pode['entrar'] || ($ofertaId !== null && !in_array($ofertaId, array_map('intval', array_column($pode['disciplinas'], 'id')), true))) {
            return [null, 'O acesso ao Moodle por aqui não está disponível para você agora. Se você dá aula numa disciplina enviada ao Moodle, procure a secretaria.'];
        }
        $pdo = AdmsMoodle::pdo();
        $limite = (int) AdmsMoodle::parametro('moodle.entradas_por_hora', '30');
        $ref = 'professor ' . $usuarioId;
        $st = $pdo->prepare("SELECT COUNT(*) FROM adms_moodle_registro WHERE funcao = 'local_vcostaedu_request_login_url' AND referencia = ? AND created > NOW() - INTERVAL 1 HOUR");
        $st->execute([$ref]);
        if ((int) $st->fetchColumn() >= $limite) {
            return [null, 'Muitas entradas no Moodle na última hora. Espere alguns minutos e tente de novo.'];
        }
        $usuario = self::usuario($usuarioId);
        if ($usuario === '') {
            return [null, 'A sua conta no Moodle ainda não está pronta. Procure a secretaria.'];
        }
        $c = AdmsMoodle::conexao();
        $base = rtrim((string) $c['url'], '/');
        $destino = $base . '/my/';
        if ($ofertaId !== null) {
            $st = $pdo->prepare('SELECT idMoodle FROM rm_professores_materias WHERE id = ?');
            $st->execute([$ofertaId]);
            $destino = $base . '/course/view.php?id=' . (int) $st->fetchColumn();
        }
        $r = AdmsMoodle::cliente($c)->chamar('local_vcostaedu_request_login_url', ['username' => $usuario, 'returnurl' => $destino], $ref);
        if (!$r['ok']) {
            return [null, self::mensagem((string) ($r['codigo'] ?? ''))];
        }
        $host = parse_url($base, PHP_URL_HOST);
        $link = null;
        array_walk_recursive($r['dados'], static function ($v) use (&$link, $host) {
            if ($link === null && is_string($v) && preg_match('#^https?://#i', $v) && parse_url($v, PHP_URL_HOST) === $host) {
                $link = $v;
            }
        });
        return $link ? [$link, ''] : [null, self::mensagem('resposta_invalida')];
    }

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
