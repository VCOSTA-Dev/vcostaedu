<?php
/*
 * ONDA 4, ETAPA 6 — a bateria do zero na ESCOLA: uma instalação nova de escola (o instalador pela tela), a demonstração
 * do colégio pelo comando do produto, a régua da escola contra o retrato da Etapa 4 (as tabelas de configuração das
 * etapas seguintes nomeadas) e a fumaça dos perfis da demonstração do colégio.
 *   php tests/tabler/onda4/escola_do_zero.php     (no fim, o banco de prova é uma escola: rode o ciclo depois)
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$aqui = dirname(__DIR__);
$raiz = dirname(__DIR__, 3);
$banco = (string) getenv('VCOSTAEDU_BANCO_TESTE');
$base = (string) getenv('VCOSTAEDU_BASE');
if (!preg_match('/_teste$/', $banco) || $base === '') {
    fwrite(STDERR, "VCOSTAEDU_BANCO_TESTE (terminando em _teste) e VCOSTAEDU_BASE\n");
    exit(2);
}
$php = escapeshellarg(PHP_BINARY);
$ok = 0;
$falha = [];
$passo = static function (string $nome, string $cmd, ?string $entrada = null, ?callable $confere = null) use (&$ok, &$falha, $raiz): string {
    $t = microtime(true);
    $p = proc_open($cmd, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pp, $raiz);
    fwrite($pp[0], (string) $entrada);
    fclose($pp[0]);
    $saida = stream_get_contents($pp[1]) . stream_get_contents($pp[2]);
    $rc = proc_close($p);
    $bom = $rc === 0 && (!$confere || $confere($saida));
    $ultima = trim((string) (preg_split('/\R/', trim($saida)) ?: [''])[count(preg_split('/\R/', trim($saida)) ?: ['']) - 1]);
    printf("  [%s]    %s (%d s): %s\n", $bom ? 'OK' : 'FALHA', $nome, round(microtime(true) - $t), mb_substr($ultima, 0, 300));
    $bom ? $ok++ : $falha[] = $nome;
    return $saida;
};
echo "\n== Onda 4, Etapa 6 — a escola do zero\n";
$passo('o banco de prova vazio', "{$php} " . escapeshellarg("{$aqui}/reset.php"));
putenv('VCOSTAEDU_TIPO=escola');
putenv('VCOSTAEDU_ETAPA_PROVA=onda4/escola_do_zero');
$passo('a instalação pela tela, escolhendo "escola"', 'node ' . escapeshellarg("{$aqui}/instalar.js") . ' normal', null, fn($s) => str_contains($s, ' 0 FALHA'));
$senha = getenv('VCOSTAEDU_ADMIN_SENHA') ?: 'chave-somente-local-123';
$passo('a demonstração do colégio, pelo comando do produto', "{$php} " . escapeshellarg("{$raiz}/database/ferramentas/demonstracao.php") . ' montar ' . escapeshellarg($base),
    "administrador\n{$senha}\n", fn($s) => str_contains($s, 'A demonstração foi montada'));
$config = 'adms_versoes,adms_novidades,adms_paginas,adms_nivacs_pgs,adms_acoes,adms_instalacao_estado,adms_paginas_modulo,adms_fases,adms_paginas_recurso,col_paginas_modulo,rm_media_regras,rm_media_regra_versoes,rm_media_regra_componentes,rm_media_regra_acrescimo,adms_parametros,col_niveis_acessos,col_paginas,col_nivacs_pgs';
$passo('a régua da escola contra o retrato da demonstração da Etapa 4', "{$php} " . escapeshellarg("{$aqui}/regua.php") . ' comparar ' . escapeshellarg("{$aqui}/saida/regua-escola-onda4-etapa4.json") . " --config={$config}",
    null, fn($s) => str_contains($s, 'IDÊNTICA'));
/* as credenciais do colégio (gravadas pelo comando no banco) para a fumaça */
$pdo = new PDO("mysql:host=localhost;dbname={$banco};charset=utf8mb4", 'root', getenv('VCOSTAEDU_BANCO_SENHA') ?: '');
$cred = (string) $pdo->query("SELECT valor FROM adms_instalacao_estado WHERE chave = 'demonstracao.credenciais'")->fetchColumn();
$arq = "{$aqui}/saida/credenciais-escola.json";
$dados = json_decode($cred, true) ?: ['painel' => [], 'portal' => []];
$dados['painel'][getenv('VCOSTAEDU_ADMIN_USUARIO') ?: 'administrador'] = $senha; // o do instalador (a montagem não o grava)
file_put_contents($arq, json_encode($dados, JSON_PRETTY_PRINT));
putenv("VCOSTAEDU_CREDENCIAIS={$arq}");
$passo('a fumaça dos perfis da demonstração do colégio', "{$php} " . escapeshellarg("{$aqui}/fumaca.php"), null, fn($s) => (bool) preg_match('/0 FALHA/', $s));
printf("onda4/escola_do_zero: %d OK, %d FALHA\n", $ok, count($falha));
foreach ($falha as $f) {
    echo "  - {$f}\n";
}
exit($falha ? 1 : 0);
