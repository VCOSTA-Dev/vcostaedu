<?php
/*
 * ONDA 6, ETAPA 4 — o ajudante da prova (só linha de comando, só no banco de prova terminado em _teste):
 *   aplicar-de-uma-vez <lote>   a importação aplicada numa chamada só (o orçamento sem fim): o resultado "de uma vez",
 *                               para comparar com o da tela em passos
 *   moodle-falso <endereço>     a conexão do Moodle apontada para o Moodle falso da prova (guarda a de antes em saida/)
 *   moodle-voltar               a conexão de antes de volta
 * Responde JSON.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$raiz = dirname(__DIR__, 3);
$modo = (string) ($argv[1] ?? '');
$banco = (string) getenv('VCOSTAEDU_BANCO_TESTE');
if (!str_ends_with($banco, '_teste')) {
    fwrite(STDERR, "VCOSTAEDU_BANCO_TESTE precisa terminar em _teste\n");
    exit(2);
}
define('VCOSTAEDU', true);
$_SERVER += ['SERVER_NAME' => 'localhost', 'HTTP_HOST' => 'localhost', 'REQUEST_URI' => '/', 'SCRIPT_NAME' => '/painel/index.php'];
chdir($raiz . '/painel');
require $raiz . '/painel/vendor/autoload.php';
(new class extends \Core\Config {
    public function __construct()
    {
        $this->configAdm();
    }
});
if (DBNAME !== $banco) {
    fwrite(STDERR, "a Config do painel não aponta para o banco de prova\n");
    exit(3);
}
require_once $raiz . '/core/Requisicao.php';
require_once $raiz . '/core/Banco.php';
require_once $raiz . '/core/Transacao.php';
require_once $raiz . '/core/Moodle.php';
\Core\Requisicao::definir();
$_SESSION = ['colUsuario_id' => 1, 'colUsuario_nivel' => 1, 'colUsuario_ordem' => 1];
$pdo = \VcBanco::daRequisicao();
$guardada = $raiz . '/tests/tabler/saida/onda6-etapa4-moodle-conexao.json';

if ($modo === 'aplicar-de-uma-vez') {
    $lote = (int) ($argv[2] ?? 0);
    $forma = (int) $pdo->query('SELECT id FROM rm_matricula_tipos WHERE situacao = 1 ORDER BY id LIMIT 1')->fetchColumn();
    \App\adms\Models\importacao\AdmsImportacaoAlunos::$orcamento = 1e9;
    $imp = new \App\adms\Models\importacao\AdmsImportacaoAlunos();
    $t = microtime(true);
    $r = $imp->iniciarAplicacao($lote, $forma, date('Y-m-d'), true);
    $passos = 0;
    while (empty($r['erro']) && empty($r['concluida'])) {
        $r = $imp->passoAplicacao($lote);
        $passos++;
    }
    echo json_encode(['passos' => $passos, 'ok' => !empty($r['concluida']), 'erro' => $r['erro'] ?? null, 's' => round(microtime(true) - $t, 1)], JSON_UNESCAPED_UNICODE), "\n";
    exit(empty($r['concluida']) ? 1 : 0);
}
if ($modo === 'moodle-falso') {
    $url = (string) ($argv[2] ?? '');
    $antes = $pdo->query('SELECT * FROM adms_moodle_conexao WHERE id = 1')->fetch(PDO::FETCH_ASSOC);
    if (!is_file($guardada)) {
        file_put_contents($guardada, json_encode($antes ?: null));
    }
    $pdo->exec('DELETE FROM adms_moodle_conexao WHERE id = 1');
    $st = $pdo->prepare("INSERT INTO adms_moodle_conexao (id, url, chave_cifrada, chave_fim, testada_em, site_nome, funcoes_ok, envio) VALUES (1, ?, ?, 'alsa', NOW(), 'Moodle falso da prova', 17, 'automatico')");
    $st->execute([$url, \VcMoodle::cifrar('chave-falsa-da-prova')]);
    echo json_encode(['ok' => true]), "\n";
    exit(0);
}
if ($modo === 'moodle-voltar') {
    $antes = is_file($guardada) ? json_decode((string) file_get_contents($guardada), true) : null;
    $pdo->exec('DELETE FROM adms_moodle_conexao WHERE id = 1');
    if (is_array($antes)) {
        $cols = array_keys($antes);
        $st = $pdo->prepare('INSERT INTO adms_moodle_conexao (`' . implode('`, `', $cols) . '`) VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')');
        $st->execute(array_values($antes));
    }
    @unlink($guardada);
    echo json_encode(['ok' => true]), "\n";
    exit(0);
}
fwrite(STDERR, "modo desconhecido\n");
exit(2);
