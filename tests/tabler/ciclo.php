<?php
/*
 * O CICLO COMPLETO DA RÉGUA: do zero até o retrato.
 *   1. tests/tabler/reset.php (apaga as Configs e as pastas de execução; DROP + CREATE do banco _teste);
 *   2. node tests/tabler/instalar.js normal (as 7 telas do instalador, pelo navegador);
 *   3. tests/tabler/montar_demo.php (a demonstração fictícia, pelas telas);
 *   4. tests/tabler/regua.php gravar|comparar <arquivo>.
 *
 * Uso: php tests/tabler/ciclo.php gravar|comparar <arquivo.json>
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE (e NODE_PATH apontando para tests/tabler/node_modules).
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
[$_, $modo, $arquivo] = $argv + [null, '', ''];
if (!in_array($modo, ['gravar', 'comparar'], true) || $arquivo === '') {
    fwrite(STDERR, "Uso: php tests/tabler/ciclo.php gravar|comparar <arquivo.json>\n");
    exit(2);
}
$php = escapeshellarg(PHP_BINARY);
/* Onda 4: as opções da régua (--config=…) seguem para ela */
$extra = implode('', array_map(static fn($o) => ' ' . escapeshellarg((string) $o), array_slice($argv, 3)));
$aqui = __DIR__;
$passos = [
    'reset'      => "{$php} " . escapeshellarg("{$aqui}/reset.php"),
    'instalador' => 'node ' . escapeshellarg("{$aqui}/instalar.js") . ' normal',
    'demo'       => "{$php} " . escapeshellarg("{$aqui}/montar_demo.php"),
    'régua'      => "{$php} " . escapeshellarg("{$aqui}/regua.php") . " {$modo} " . escapeshellarg($arquivo) . $extra,
];
foreach ($passos as $nome => $cmd) {
    $t = microtime(true);
    exec($cmd . ' 2>&1', $saida, $rc);
    printf("%-11s %s (%.0f s)\n", $nome, $rc === 0 ? 'ok' : "FALHOU ({$rc})", microtime(true) - $t);
    if ($rc !== 0 || $nome === 'régua') {
        echo '  ' . implode("\n  ", array_slice($saida, -25)) . "\n";
    }
    if ($rc !== 0) {
        exit(1);
    }
    $saida = [];
}
