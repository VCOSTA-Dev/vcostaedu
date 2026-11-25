<?php
/*
 * RESET DO AMBIENTE DE PROVA — de fora para dentro, como manda a recusa do instalador:
 *   1. apaga os arquivos de configuração gerados (painel e portal);
 *   2. apaga a pasta de storage padrão e a pasta do logo da instituição (o instalador as recria);
 *   3. DROP DATABASE + CREATE DATABASE ... utf8mb4 / utf8mb4_unicode_ci.
 *
 * Uso: VCOSTAEDU_BANCO_TESTE=<nome>_teste php tests/tabler/reset.php [--manter-config]
 *
 * O nome do banco vem do AMBIENTE, nunca de arquivo versionado, e a ferramenta RECUSA qualquer nome que não
 * termine em "_teste": o DROP nunca roda solto. Só linha de comando. Conecta como root sem senha (XAMPP local)
 * ou com VCOSTAEDU_MYSQL_USUARIO / VCOSTAEDU_MYSQL_SENHA.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$banco = (string) getenv('VCOSTAEDU_BANCO_TESTE');
if ($banco === '' || !preg_match('/^[a-z0-9_]+_teste$/', $banco)) {
    fwrite(STDERR, "RECUSADO: defina VCOSTAEDU_BANCO_TESTE com um nome que termine em _teste.\n");
    exit(2);
}

$raiz = dirname(__DIR__, 2);
$manterConfig = in_array('--manter-config', $argv, true);

if (!$manterConfig) {
    foreach (['painel/core/Config.php', 'portal/core/Config.php'] as $arq) {
        if (is_file("{$raiz}/{$arq}")) {
            unlink("{$raiz}/{$arq}");
            echo "apagado {$arq}\n";
        }
    }
}

$apagarPasta = function (string $dir) use (&$apagarPasta): void {
    if (!is_dir($dir)) {
        return;
    }
    foreach (scandir($dir) as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $c = "{$dir}/{$item}";
        is_dir($c) && !is_link($c) ? $apagarPasta($c) : unlink($c);
    }
    rmdir($dir);
};
foreach (['storage', 'assets/instituicao'] as $pasta) {
    if (is_dir("{$raiz}/{$pasta}")) {
        $apagarPasta("{$raiz}/{$pasta}");
        echo "apagada {$pasta}/\n";
    }
}

$usuario = getenv('VCOSTAEDU_MYSQL_USUARIO') ?: 'root';
$senha = getenv('VCOSTAEDU_MYSQL_SENHA') ?: '';
$pdo = new PDO('mysql:host=localhost;charset=utf8mb4', $usuario, $senha, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->exec("DROP DATABASE IF EXISTS `{$banco}`");
$pdo->exec("CREATE DATABASE `{$banco}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
echo "banco recriado vazio (utf8mb4_unicode_ci)\n";
