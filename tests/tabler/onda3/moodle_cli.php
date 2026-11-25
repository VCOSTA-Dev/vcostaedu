<?php
/*
 * As provas da Etapa 8 pela linha de comando, no banco de teste: põe um item na fila e envia, ou lê o Moodle (só para
 * conferir o que a prova fez). Nunca imprime a chave.
 *
 *   php tests/tabler/onda3/moodle_cli.php enviar <tipo> <aluno|-> <oferta|->   enfileira e envia; imprime a situação
 *   php tests/tabler/onda3/moodle_cli.php chamar <funcao> '<json dos parâmetros>' chama o Moodle; imprime o JSON da resposta
 *   php tests/tabler/onda3/moodle_cli.php limpar                                  desenturma e suspende os usuários de prova
 *   php tests/tabler/onda3/moodle_cli.php buscar <n>                              a busca por e-mail do lote com n e-mails de prova
 *                                                                                 (a prova confere quantas chamadas foram)
 */
require dirname(__DIR__, 3) . '/bin/bootstrap-cli.php';
require dirname(__DIR__, 3) . '/painel/vendor/autoload.php';
if (!preg_match('/_teste2?$/', DBNAME)) {
    fwrite(STDERR, "só no banco de teste\n");
    exit(2);
}

use App\adms\Models\moodle\AdmsMoodle as M;

$acao = $argv[1] ?? '';
if ($acao === 'enviar') {
    $a = ($argv[3] ?? '-') === '-' ? null : (int) $argv[3];
    $o = ($argv[4] ?? '-') === '-' ? null : (int) $argv[4];
    $id = M::enfileirar((string) $argv[2], $a, $o);
    $sit = M::enviar($id);
    $st = M::pdo()->prepare('SELECT situacao, codigo, motivo, tentativas FROM adms_moodle_envios WHERE id = ?');
    $st->execute([$id]);
    echo json_encode(['id' => $id] + $st->fetch(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE), "\n";
    exit(0);
}
if ($acao === 'chamar') {
    $r = M::cliente()->chamar((string) $argv[2], (array) json_decode($argv[3] ?? '{}', true), 'prova');
    echo json_encode($r, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
    exit(0);
}
if ($acao === 'preparar') {
    /* o começo da prova: os usuários de prova que uma rodada anterior suspendeu (a limpeza) voltam a valer */
    $c = M::cliente();
    $n = 0;
    foreach (array_slice($argv, 2) as $idnumber) {
        if (!str_starts_with($idnumber, 'prova-')) {
            continue;
        }
        $d = $c->chamar('core_user_get_users_by_field', ['field' => 'idnumber', 'values' => [$idnumber]], 'preparo');
        foreach (($d['ok'] ? $d['dados'] : []) as $u) {
            if (!empty($u['suspended'])) {
                $r = $c->chamar('core_user_update_users', ['users' => [['id' => (int) $u['id'], 'suspended' => 0]]], 'preparo');
                $n += $r['ok'] ? 1 : 0;
            }
        }
    }
    echo json_encode(['reativados' => $n]), "\n";
    exit(0);
}
if ($acao === 'buscar') {
    $n = max(1, (int) ($argv[2] ?? 1));
    $emails = [];
    for ($i = 1; $i <= $n; $i++) {
        $emails[] = 'prova.busca.' . $i . '@exemplo.invalid';
    }
    (new \App\adms\Models\moodle\AdmsMoodleEnvio(M::cliente(), M::conexao()))->buscarEmails($emails, 'prova');
    echo json_encode(['emails' => $n]), "\n";
    exit(0);
}
if ($acao === 'limpar') {
    /* o que a prova deixou no Moodle: os usuários de prova (idnumber "prova-…") saem das disciplinas e ficam suspensos;
       as disciplinas e a categoria de prova ficam (o serviço não apaga) */
    $c = M::cliente();
    $usuarios = M::pdo()->query("SELECT DISTINCT moodle_id FROM adms_moodle_vinculos WHERE tipo IN ('aluno', 'professor')")->fetchAll(PDO::FETCH_COLUMN);
    $susp = 0;
    $desen = 0;
    $prova = [];
    foreach ($usuarios as $u) {
        $d = $c->chamar('core_user_get_users_by_field', ['field' => 'id', 'values' => [(int) $u]], 'limpeza');
        $reg = $d['ok'] ? ($d['dados'][0] ?? null) : null;
        if (!$reg || !str_starts_with((string) ($reg['idnumber'] ?? ''), 'prova-')) {
            continue; // só o que a prova criou
        }
        $prova[] = (int) $u;
        $cursos = $c->chamar('core_enrol_get_users_courses', ['userid' => (int) $u], 'limpeza');
        foreach (($cursos['ok'] ? $cursos['dados'] : []) as $curso) {
            if (str_starts_with((string) ($curso['idnumber'] ?? ''), 'prova-')) {
                $r = $c->chamar('enrol_manual_unenrol_users', ['enrolments' => [['userid' => (int) $u, 'courseid' => (int) $curso['id']]]], 'limpeza');
                $desen += $r['ok'] ? 1 : 0;
            }
        }
        $r = $c->chamar('core_user_update_users', ['users' => [['id' => (int) $u, 'suspended' => 1]]], 'limpeza');
        $susp += $r['ok'] ? 1 : 0;
    }
    echo json_encode(['usuarios_de_prova' => count($prova), 'suspensos' => $susp, 'desenturmados' => $desen]), "\n";
    exit(0);
}
fwrite(STDERR, "uso: enviar | chamar | limpar\n");
exit(2);
