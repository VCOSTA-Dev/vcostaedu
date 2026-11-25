<?php
/*
 * ONDA 3, ETAPA 2 — aplica TODAS as migrations num banco de prova vazio (VCOSTAEDU_BANCO_TESTE + "2"), com o modo de
 * SQL do sistema (core/Banco.php), e diz qual falha e por quê. Serve para medir o que o modo estrito recusa.
 *
 *   php tests/tabler/onda3/migrar_estrito.php [modo extra]      ex.: ONLY_FULL_GROUP_BY
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$base = (string) getenv('VCOSTAEDU_BANCO_TESTE');
if (!preg_match('/_teste$/', $base)) {
    fwrite(STDERR, "VCOSTAEDU_BANCO_TESTE precisa terminar em _teste\n");
    exit(2);
}
$banco = $base . '2';
$raiz = dirname(__DIR__, 3);
define('VCOSTAEDU', true);
foreach (['Un1Mv' . 'C05tA'] as $g) { defined($g) || define($g, true); } // a guarda de antes da 1.45.0, só para a Config.php já instalada (DECISIONS D-53)
define('DBNAME', $banco);
require $raiz . '/core/Banco.php';
$root = new PDO('mysql:host=localhost;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
if (!str_ends_with($banco, '_teste2')) {
    exit(4);
}
$root->exec("DROP DATABASE IF EXISTS `{$banco}`");
$root->exec("CREATE DATABASE `{$banco}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$modo = VcBanco::MODO . (isset($argv[1]) && $argv[1] !== '' ? ',' . $argv[1] : '');
$pdo = new PDO("mysql:host=localhost;dbname={$banco};charset=utf8mb4", 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::MYSQL_ATTR_INIT_COMMAND => "SET SESSION sql_mode = '{$modo}'"]);
echo 'modo: ', VcBanco::modoEmUso($pdo), "\n";
require $raiz . '/painel/app/adms/Models/banco/AdmsMigrations.php';
$runner = new \App\adms\Models\banco\AdmsMigrations($raiz . '/database/migrations', $pdo);
try {
    $n = $runner->aplicar(false);
    echo "aplicadas: {$n}\n";
} catch (Throwable $e) {
    foreach ($runner->resultados() as $r) {
        echo "  ok {$r['arquivo']}\n";
    }
    echo 'FALHA: ', $e->getMessage(), "\n";
    exit(1);
}
