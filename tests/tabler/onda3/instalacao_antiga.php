<?php
/*
 * ONDA 3, ETAPA 7 — os ARQUIVOS de uma versão antiga, servidos ao lado da pasta de trabalho, para montar a demonstração
 * com o código daquela versão (o banco antigo é o de tests/tabler/onda2/banco_antigo.php: VCOSTAEDU_BANCO_TESTE + "2").
 *
 *   php tests/tabler/onda3/instalacao_antiga.php <commit>      extrai e configura; imprime {"pasta", "base"}
 *   php tests/tabler/onda3/instalacao_antiga.php --apagar      apaga a pasta
 *
 * A pasta é "<pasta de trabalho>-antiga" (irmã dela). Os arquivos vêm do git (git archive: nada muda no repositório); as
 * duas Configs são cópias das da pasta de trabalho, apontando para o banco de prova de atualização, em produção.
 * Recusa qualquer banco que não termine em _teste2.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$raiz = str_replace('\\', '/', dirname(__DIR__, 3));
$pasta = $raiz . '-antiga';
$arg = $argv[1] ?? '';

function apagar(string $d): void
{
    if (!is_dir($d)) {
        return;
    }
    /* a lista primeiro, e só depois apagar: no Windows, o iterador aberto segura a pasta e o rmdir é recusado */
    $lista = [];
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $f) {
        $lista[] = [$f->getPathname(), $f->isDir() && !$f->isLink()];
    }
    foreach ($lista as [$caminho, $pasta]) {
        $pasta ? @rmdir($caminho) : @unlink($caminho);
    }
    @rmdir($d);
}

if ($arg === '--demo') {
    /* a demonstração montada com o CÓDIGO ANTIGO, pelas telas dele: o montar_demo daquela versão, que só aceita banco
       terminado em _teste — na cópia descartável, a conferência passa a aceitar o de prova de atualização (_teste2) */
    $m = $pasta . '/tests/tabler/montar_demo.php';
    $s = (string) @file_get_contents($m);
    if ($s === '' || substr_count($s, "str_ends_with(\$m[1], '_teste')") !== 1) {
        fwrite(STDERR, "a pasta antiga não tem o montar_demo esperado\n");
        exit(5);
    }
    file_put_contents($m, str_replace("str_ends_with(\$m[1], '_teste')", "str_ends_with(\$m[1], '_teste2')", $s));
    $baseAntiga = preg_replace('#[^/]+/$#', basename($pasta) . '/', (string) getenv('VCOSTAEDU_BASE'));
    putenv('VCOSTAEDU_BASE=' . $baseAntiga);
    passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($m), $rc);
    exit($rc);
}
if ($arg === '--apagar') {
    apagar($pasta);
    echo json_encode(['apagada' => !is_dir($pasta)]), "\n";
    exit(0);
}
$banco = getenv('VCOSTAEDU_BANCO_TESTE') . '2';
$base = (string) getenv('VCOSTAEDU_BASE');
if (!preg_match('/^[0-9a-f]{6,40}$/', $arg) || !str_ends_with($banco, '_teste2') || !preg_match('#^https?://.+/$#', $base)) {
    fwrite(STDERR, "uso: php instalacao_antiga.php <commit> (VCOSTAEDU_BANCO_TESTE terminando em _teste e VCOSTAEDU_BASE)\n");
    exit(2);
}
apagar($pasta);
@mkdir($pasta, 0777, true);
$tar = sys_get_temp_dir() . '/vcostaedu_antiga_' . $arg . '.tar';
exec('git -C ' . escapeshellarg($raiz) . ' archive --format=tar -o ' . escapeshellarg($tar) . ' ' . escapeshellarg($arg), $o, $rc);
if ($rc !== 0) {
    fwrite(STDERR, "git archive falhou\n");
    exit(3);
}
try {
    (new PharData($tar))->extractTo($pasta, null, true); /* o tar do PHP: sem depender do tar do sistema */
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
}
@unlink($tar);
if (!is_file($pasta . '/painel/index.php')) {
    fwrite(STDERR, "a extração falhou\n");
    exit(4);
}
foreach (['painel', 'portal'] as $app) {
    $s = (string) file_get_contents("{$raiz}/{$app}/core/Config.php");
    $s = preg_replace("/define\\('DBNAME',\\s*'[^']*'\\)/", "define('DBNAME', '{$banco}')", $s);
    $s = preg_replace("/define\\('PASTA_STORAGE',\\s*'[^']*'\\)/", "define('PASTA_STORAGE', '{$pasta}/storage/')", $s);
    $s = preg_replace("/define\\('AMBIENTE',\\s*'[^']*'\\);/", "define('AMBIENTE', 'producao');", $s);
    $s = preg_replace("/^\\s*define\\('BANCO_DEMONSTRACAO',[^\\n]*\\n/m", '', $s);
    /* o código de antes da 1.45.0 define a guarda antiga (DECISIONS D-53): a cópia da Config aceita as duas */
    $antiga = $app === 'painel' ? 'Un1Mv' . 'C05tA' : 'ColMv' . 'C05tA';
    $s = str_replace("if (!defined('VCOSTAEDU')) {", "if (!defined('VCOSTAEDU') && !defined('{$antiga}')) {", $s);
    file_put_contents("{$pasta}/{$app}/core/Config.php", $s);
}
/* as pastas de execução (o logo, os vídeos, os arquivos privados), pela função do instalador DAQUELA versão */
$tmp = sys_get_temp_dir() . '/vcostaedu_antiga_pastas.php';
file_put_contents($tmp, "<?php define('VCOSTAEDU_INSTALADOR', true); require " . var_export($pasta . '/instalar/comum.php', true) . '; require ' . var_export($pasta . '/instalar/pastas.php', true) . ';'
    . ' echo json_encode(criarPastasDeExecucao(' . var_export($pasta . '/storage', true) . '));');
$r = shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($tmp));
@unlink($tmp);
if (!is_dir($pasta . '/assets/instituicao') || !is_dir($pasta . '/storage')) {
    fwrite(STDERR, "as pastas de execução não nasceram: {$r}\n");
    exit(6);
}
@mkdir($pasta . '/tests/tabler/saida', 0777, true);
$baseAntiga = preg_replace('#[^/]+/$#', basename($pasta) . '/', $base);
echo json_encode(['pasta' => $pasta, 'base' => $baseAntiga, 'banco' => $banco], JSON_UNESCAPED_SLASHES), "\n";
