<?php
/*
 * MONTA A DEMONSTRAÇÃO FICTÍCIA no banco de PROVA, pelas ferramentas da origem:
 *   1. confere que a instalação aponta para um banco que termina em _teste (senão RECUSA);
 *   2. põe AMBIENTE = 'demonstracao' e BANCO_DEMONSTRACAO = o próprio banco nas duas Configs (locais, fora do git);
 *   3. database/ferramentas/limpar_demo.php (apaga a anterior);
 *   4. database/ferramentas/seed_demo_processar.php <URL> (monta tudo PELAS TELAS, por HTTP);
 *   5. guarda os logins e senhas fictícios que o seed mostra em tests/tabler/saida/credenciais.json (fora do git),
 *      para as provas entrarem com cada perfil.
 *
 * Uso: VCOSTAEDU_BASE=<url> php tests/tabler/montar_demo.php
 * Conta do administrador criada pelo instalador: VCOSTAEDU_ADMIN_USUARIO (padrão administrador) e
 * VCOSTAEDU_ADMIN_SENHA (padrão a senha só de prova do tests/tabler/instalar.js).
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$raiz = dirname(__DIR__, 2);
$base = (string) getenv('VCOSTAEDU_BASE');
if (!preg_match('#^https?://.+/$#', $base)) {
    fwrite(STDERR, "Defina VCOSTAEDU_BASE (com barra no fim).\n");
    exit(2);
}
$adminUsuario = getenv('VCOSTAEDU_ADMIN_USUARIO') ?: 'administrador';
$adminSenha = getenv('VCOSTAEDU_ADMIN_SENHA') ?: 'chave-somente-local-123';

foreach (['painel', 'portal'] as $app) {
    $arq = "{$raiz}/{$app}/core/Config.php";
    $s = (string) @file_get_contents($arq);
    if (!preg_match("/define\\('DBNAME', '([^']+)'\\);/", $s, $m) || !str_ends_with($m[1], '_teste')) {
        fwrite(STDERR, "RECUSADO: {$app}/core/Config.php não aponta para um banco de prova (_teste).\n");
        exit(2);
    }
    $banco = $m[1];
    $s = preg_replace("/define\\('AMBIENTE', [^;]*\\);/", "define('AMBIENTE', 'demonstracao');", $s, 1);
    if (!preg_match("/^\\s*define\\('BANCO_DEMONSTRACAO'/m", $s)) {
        $s = preg_replace("/(define\\('AMBIENTE', 'demonstracao'\\);)/", "$1\n        define('BANCO_DEMONSTRACAO', " . var_export($banco, true) . ");", $s, 1);
    }
    file_put_contents($arq, $s);
}
echo "Configs em AMBIENTE = 'demonstracao' (banco de prova).\n";

$php = escapeshellarg(PHP_BINARY);
passthru("{$php} " . escapeshellarg("{$raiz}/database/ferramentas/limpar_demo.php"), $rc);
if ($rc !== 0) {
    fwrite(STDERR, "limpar_demo.php falhou ({$rc}).\n");
    exit(3);
}

$inicio = microtime(true);
$p = proc_open("{$php} " . escapeshellarg("{$raiz}/database/ferramentas/seed_demo_processar.php") . ' ' . escapeshellarg($base),
    [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pp, $raiz);
fwrite($pp[0], "{$adminUsuario}\n{$adminSenha}\n");
fclose($pp[0]);
$saida = stream_get_contents($pp[1]);
$erro = stream_get_contents($pp[2]);
fclose($pp[1]);
fclose($pp[2]);
$rc = proc_close($p);
/* A saída tem as senhas fictícias da demonstração: não vai inteira para o terminal. */
echo preg_replace('/^Senhas da demonstração.*$/ms', '(senhas guardadas em tests/tabler/saida/credenciais.json)', $saida);
if ($rc !== 0) {
    fwrite(STDERR, $erro . "\nseed_demo_processar.php falhou ({$rc}).\n");
    exit(4);
}
printf("Demonstração montada em %.0f s.\n", microtime(true) - $inicio);

$cred = ['painel' => [], 'portal' => []];
foreach (preg_split('/\R/', $saida) as $l) {
    if (preg_match('/^\s+painel\s+(\S+)\s+(\S+)\s*$/', $l, $m)) {
        $cred['painel'][$m[1]] = $m[2];
    } elseif (preg_match('/^\s+portal\s+RA\s+(\d+)\s+.*?\s{2,}(\S{8,})\s*$/', $l, $m)) {
        $cred['portal'][$m[1]] = $m[2];
    }
}
$cred['painel'][$adminUsuario] = $adminSenha;
@mkdir(__DIR__ . '/saida', 0755, true);
file_put_contents(__DIR__ . '/saida/credenciais.json', json_encode($cred, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo 'Credenciais: ' . count($cred['painel']) . ' do painel e ' . count($cred['portal']) . " do portal.\n";
