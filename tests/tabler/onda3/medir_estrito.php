<?php
/*
 * ONDA 3, ETAPA 2 — mede o que o modo estrito recusa NAS MIGRATIONS: num banco de prova vazio (VCOSTAEDU_BANCO_TESTE +
 * "2"), executa cada instrução de cada migration no modo do sistema; a que falha é anotada (o arquivo, o começo da
 * instrução e o erro) e refeita no modo antigo, para seguir. Não registra nada em adms_migrations.
 *
 *   php tests/tabler/onda3/medir_estrito.php [modo extra]
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$base = (string) getenv('VCOSTAEDU_BANCO_TESTE');
if (!preg_match('/_teste$/', $base)) {
    exit(2);
}
$banco = $base . '2';
$raiz = dirname(__DIR__, 3);
require $raiz . '/core/Banco.php';
$root = new PDO('mysql:host=localhost;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
if (!str_ends_with($banco, '_teste2')) {
    exit(4);
}
$root->exec("DROP DATABASE IF EXISTS `{$banco}`");
$root->exec("CREATE DATABASE `{$banco}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$modo = VcBanco::MODO . (isset($argv[1]) && $argv[1] !== '' ? ',' . $argv[1] : '');
$pdo = new PDO("mysql:host=localhost;dbname={$banco};charset=utf8mb4", 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$recusas = 0;
foreach (glob($raiz . '/database/migrations/*.sql') as $arq) {
    $sql = (string) file_get_contents($arq);
    /* as instruções: separa no ";" do fim de linha, fora de gatilho (a 0003 e a 0007 usam DELIMITER) */
    if (preg_match('/^\s*DELIMITER/m', $sql)) {
        continue;
    }
    foreach (preg_split('/;\s*\R/', $sql) as $instr) {
        $instr = trim(preg_replace('/^\s*--.*$/m', '', $instr));
        if ($instr === '') {
            continue;
        }
        try {
            $pdo->exec("SET SESSION sql_mode = '{$modo}'");
            $pdo->exec($instr);
        } catch (PDOException $e) {
            $recusas++;
            echo basename($arq), ' | ', mb_substr(preg_replace('/\s+/', ' ', $instr), 0, 110), "\n    -> ", $e->errorInfo[2] ?? $e->getMessage(), "\n";
            $pdo->exec("SET SESSION sql_mode = 'NO_ZERO_IN_DATE,NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION'");
            try {
                $pdo->exec($instr);
            } catch (PDOException $e2) {
                echo "    (nem no modo antigo: ", $e2->errorInfo[2] ?? '', ")\n";
            }
        }
    }
}
echo "recusas no modo {$modo}: {$recusas}\n";
