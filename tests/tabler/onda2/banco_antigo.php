<?php
/*
 * ONDA 2, ETAPA 7 — um banco "da versão anterior", para provar a atualização.
 *
 *   php tests/tabler/onda2/banco_antigo.php <commit>
 *
 * Recria o banco de prova de atualização (VCOSTAEDU_BANCO_TESTE + "2"; nenhum outro nome é aceito) só com as migrations
 * que existiam no <commit> (lidas do git, sem tocar a pasta), aplicadas pelo MESMO runner, e cria o administrador pela
 * MESMA função do instalador (a senha de VCOSTAEDU_ADMIN_SENHA). É o banco de quem instalou naquela versão e acabou de
 * trazer os arquivos novos.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$commit = $argv[1] ?? '';
$base = (string) getenv('VCOSTAEDU_BANCO_TESTE');
if ($commit === '' || !preg_match('/^[0-9a-f]{6,40}$/', $commit) || !preg_match('/_teste$/', $base)) {
    fwrite(STDERR, "uso: php banco_antigo.php <commit> (com VCOSTAEDU_BANCO_TESTE terminando em _teste)\n");
    exit(2);
}
$banco = $base . '2';
$raiz = dirname(__DIR__, 3);

/* as migrations do commit, numa pasta temporária */
$lista = [];
exec('git -C ' . escapeshellarg($raiz) . ' ls-tree --name-only ' . escapeshellarg($commit) . ' database/migrations/', $lista, $rc);
if ($rc !== 0 || !$lista) {
    fwrite(STDERR, "commit sem migrations: {$commit}\n");
    exit(3);
}
$dir = sys_get_temp_dir() . '/vcostaedu_migrations_' . $commit;
@mkdir($dir, 0777, true);
foreach (glob($dir . '/*.sql') ?: [] as $velho) {
    unlink($velho);
}
foreach ($lista as $arq) {
    $conteudo = shell_exec('git -C ' . escapeshellarg($raiz) . ' show ' . escapeshellarg($commit . ':' . $arq));
    file_put_contents($dir . '/' . basename($arq), (string) $conteudo);
}

/* o banco, do zero (só o de prova de atualização) */
define('VCOSTAEDU', true);
foreach (['Un1Mv' . 'C05tA'] as $g) { defined($g) || define($g, true); } // a guarda de antes da 1.45.0, só para a Config.php já instalada (DECISIONS D-53)
define('DBNAME', $banco);
$root = new PDO('mysql:host=localhost;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
if ($banco !== $base . '2' || !str_ends_with($banco, '_teste2')) {
    exit(4);
}
$root->exec("DROP DATABASE IF EXISTS `{$banco}`");
$root->exec("CREATE DATABASE `{$banco}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo = new PDO("mysql:host=localhost;dbname={$banco};charset=utf8mb4", 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

require $raiz . '/painel/app/adms/Models/banco/AdmsMigrations.php';
$runner = new \App\adms\Models\banco\AdmsMigrations($dir, $pdo);
$n = $runner->aplicar(false);

require $raiz . '/database/ferramentas/admin_comum.php';
$adm = vcostaedu_criar_admin($pdo, 'administrador', 'administrador@prova.invalid', 'Administrador da Prova', (string) (getenv('VCOSTAEDU_ADMIN_SENHA') ?: 'chave-somente-local-123'));
echo json_encode(['banco' => $banco, 'commit' => $commit, 'migrations' => array_map('basename', $lista), 'aplicadas' => $n, 'admin' => $adm['ok']]), "\n";
exit($adm['ok'] ? 0 : 5);
