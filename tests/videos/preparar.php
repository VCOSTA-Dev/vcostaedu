<?php
/*
 * A INSTALAÇÃO DA GRAVAÇÃO — prepara a pasta servida dos vídeos (VCOSTAEDU_VIDEOS_RAIZ, um worktree à parte) e o banco dela.
 *
 *   php tests/videos/preparar.php zerar     apaga as Configs, a pasta storage/ e o logo da instituição da RAIZ, e recria
 *                                          vazio o banco VCOSTAEDU_VIDEOS_BANCO (só aceita nome que termine em "_demo")
 *
 * O nome do banco vem do ambiente, nunca de arquivo versionado; o DROP só roda com o nome terminado em "_demo" e com a
 * RAIZ diferente desta pasta (o worktree de trabalho nunca é zerado por aqui). Só linha de comando.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$raiz = rtrim(str_replace('\\', '/', (string) getenv('VCOSTAEDU_VIDEOS_RAIZ')), '/');
$banco = (string) getenv('VCOSTAEDU_VIDEOS_BANCO');
$aqui = rtrim(str_replace('\\', '/', dirname(__DIR__, 2)), '/');
if ($raiz === '' || !is_dir("{$raiz}/instalar") || strcasecmp($raiz, $aqui) === 0) {
    fwrite(STDERR, "RECUSADO: VCOSTAEDU_VIDEOS_RAIZ deve ser a pasta servida dos vídeos (não esta).\n");
    exit(2);
}
if (!preg_match('/^[a-z0-9_]+_demo$/', $banco)) {
    fwrite(STDERR, "RECUSADO: VCOSTAEDU_VIDEOS_BANCO deve terminar em _demo.\n");
    exit(2);
}
$acao = $argv[1] ?? '';
if ($acao === 'zerar') {
    foreach (['painel/core/Config.php', 'portal/core/Config.php'] as $arq) {
        if (is_file("{$raiz}/{$arq}")) {
            unlink("{$raiz}/{$arq}");
        }
    }
    $apagar = function (string $dir) use (&$apagar): void {
        if (!is_dir($dir)) {
            return;
        }
        foreach (array_diff(scandir($dir), ['.', '..']) as $item) {
            $c = "{$dir}/{$item}";
            is_dir($c) && !is_link($c) ? $apagar($c) : unlink($c);
        }
        @rmdir($dir); // a pasta presa por outro processo fica (vazia): o instalador a reaproveita
    };
    foreach (['storage', 'assets/instituicao'] as $pasta) {
        $apagar("{$raiz}/{$pasta}");
    }
    $pdo = new PDO('mysql:host=localhost;charset=utf8mb4', getenv('VCOSTAEDU_MYSQL_USUARIO') ?: 'root', getenv('VCOSTAEDU_MYSQL_SENHA') ?: '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec("DROP DATABASE IF EXISTS `{$banco}`");
    $pdo->exec("CREATE DATABASE `{$banco}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "zerado\n";
    exit(0);
}
fwrite(STDERR, "uso: preparar.php zerar\n");
exit(2);
