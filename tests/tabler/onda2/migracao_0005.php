<?php
/*
 * A 0005 numa instalação que JÁ EXISTE (Onda 2, Etapa 2): instala até a 0004 (as migrations novas ficam de lado
 * durante a instalação), põe usuários nos níveis que vão sair (2, 7, 8, 9), um nível criado na tela e uma visão de
 * relatório compartilhada com o nível 2; volta as migrations e aplica pelo runner (php bin/migrar.php aplicar);
 * confere para onde foi cada coisa. No fim o banco fica instalado e atualizado, SEM a demonstração.
 *
 *   php tests/tabler/onda2/migracao_0005.php
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE, NODE_PATH.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$raiz = dirname(__DIR__, 3);
$banco = (string) getenv('VCOSTAEDU_BANCO_TESTE');
if (!preg_match('/^[a-z0-9_]+_teste2?$/', $banco)) {
    fwrite(STDERR, "RECUSADO: defina VCOSTAEDU_BANCO_TESTE com um banco de prova.\n");
    exit(2);
}
$oks = 0;
$falhas = [];
$ok = static function (bool $c, string $t) use (&$oks, &$falhas): void {
    echo ($c ? '  [OK]    ' : '  [FALHA] ') . $t . "\n";
    $c ? $oks++ : $falhas[] = $t;
};
$php = escapeshellarg(PHP_BINARY);
$rodar = static function (string $cmd): array {
    exec($cmd . ' 2>&1', $s, $rc);
    return [$rc, implode("\n", $s)];
};

echo "\n== A 0005 numa instalação existente\n";
[$rc] = $rodar("{$php} " . escapeshellarg("{$raiz}/tests/tabler/reset.php"));
$ok($rc === 0, 'banco de prova recriado vazio');

/* as migrations da 0005 em diante ficam de lado durante a instalação */
$adiadas = [];
foreach (glob("{$raiz}/database/migrations/*.sql") as $f) {
    if (preg_match('/^(\d+)_/', basename($f), $m) && (int) $m[1] >= 5) {
        rename($f, $f . '.adiada');
        $adiadas[] = $f;
    }
}
try {
    putenv('VCOSTAEDU_ETAPA_PROVA=onda2/etapa2/instalacao-ate-0004');
    [$rc, $saida] = $rodar('node ' . escapeshellarg("{$raiz}/tests/tabler/instalar.js") . ' normal');
    $ok($rc === 0, 'instalado até a 0004 pelo instalador (a versão de antes desta etapa)');
} finally {
    foreach ($adiadas as $f) {
        rename($f . '.adiada', $f);
    }
}
$pdo = new PDO("mysql:host=localhost;dbname={$banco};charset=utf8mb4", 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$um = static fn(string $q) => $pdo->query($q)->fetchColumn();
$ok((int) $um('SELECT COUNT(*) FROM adms_niveis_acessos') === 9 && $um("SELECT nome FROM adms_niveis_acessos WHERE id = 1") === 'Super Administrador', 'antes: os 9 níveis da origem');

/* o que uma instalação em uso teria */
foreach ([2 => 'adm.instituicao', 7 => 'supervisor.fornecedor', 8 => 'fornecedor', 9 => 'suporte.fornecedor'] as $nivel => $login) {
    $pdo->prepare("INSERT INTO adms_usuarios (nome, genero_id, usuario, senha, email, adms_niveis_acesso_id, adms_sits_usuario_id, usuario_cre, created)
                   VALUES (?, 3, ?, '', ?, ?, 1, 1, NOW())")->execute(["Pessoa fictícia do nível {$nivel}", $login, "{$login}@exemplo.invalid", $nivel]);
}
$pdo->exec("INSERT INTO adms_niveis_acessos (nome, ordem, situacao, usuario_cre, created) VALUES ('Nível próprio da instituição', 10, 1, 1, NOW())");
$proprio = (int) $pdo->lastInsertId();
$pdo->prepare("INSERT INTO adms_usuarios (nome, genero_id, usuario, senha, email, adms_niveis_acesso_id, adms_sits_usuario_id, usuario_cre, created) VALUES ('Pessoa fictícia do nível próprio', 3, 'nivel.proprio', '', 'nivel.proprio@exemplo.invalid', ?, 1, 1, NOW())")->execute([$proprio]);
$cols = $pdo->query("SHOW COLUMNS FROM rm_relatorios_salvos")->fetchAll(PDO::FETCH_COLUMN);
$temSalvo = in_array('nivel_acesso_id', $cols, true);
if ($temSalvo) {
    $pdo->exec("INSERT INTO rm_relatorios_salvos (nome, relatorio, filtros, usuario_id, nivel_acesso_id) VALUES ('Visão compartilhada com o nível 2', 'B1', '', 1, 2)");
}
$ok(true, 'usuários postos nos níveis 2, 7, 8 e 9, um nível criado na tela e uma visão de relatório do nível 2');

/* os arquivos novos com a 0005 PENDENTE (o estado da instalação em uso entre o avanço e a atualização): o
   administrador abre todas as telas do menu sem erro do PHP (a fumaça da origem, só o perfil administrador) */
$senhaAdm = getenv('VCOSTAEDU_ADMIN_SENHA') ?: 'chave-somente-local-123';
$cmd = "{$php} " . escapeshellarg("{$raiz}/database/ferramentas/implantacao/fumaca.php") . ' ' . escapeshellarg((string) getenv('VCOSTAEDU_BASE')) . ' --limite=200';
$proc = proc_open($cmd, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pp);
fwrite($pp[0], "administrador\n{$senhaAdm}\n\n\n\n\n\n\n");
fclose($pp[0]);
$fum = stream_get_contents($pp[1]) . stream_get_contents($pp[2]);
fclose($pp[1]);
fclose($pp[2]);
proc_close($proc);
$ok(preg_match('/administrador: (\d+) telas do menu abertas, sem erro/', $fum, $mm) === 1 && !str_contains($fum, '[FALHA]'),
    'com a 0005 pendente, o administrador abre ' . ($mm[1] ?? '?') . ' telas do menu sem erro do PHP');

[$rc, $saida] = $rodar("{$php} " . escapeshellarg("{$raiz}/bin/migrar.php") . ' aplicar --sim');
$ok($rc === 0 && str_contains($saida, '0005_perfis_produto_aberto.sql'), 'a 0005 aplicada pelo runner (php bin/migrar.php aplicar)');

$movidos = (int) $um("SELECT COUNT(*) FROM adms_usuarios WHERE usuario IN ('adm.instituicao', 'supervisor.fornecedor', 'fornecedor', 'suporte.fornecedor') AND adms_niveis_acesso_id = 1");
$ok($movidos === 4, "os usuários dos níveis que saíram passaram ao Administrador: {$movidos} de 4");
$ok((int) $um('SELECT COUNT(*) FROM adms_niveis_acessos WHERE id IN (2, 7, 8, 9)') === 0, 'os níveis 2, 7, 8 e 9 saíram');
$ok($um("SELECT CONCAT(nome, '|', papel) FROM adms_niveis_acessos WHERE id = 1") === 'Administrador|administrador', 'o nível 1 é o Administrador');
$ok((int) $um("SELECT COUNT(*) FROM adms_niveis_acessos WHERE papel = 'supervisao_secretaria'") === 1, 'a Supervisão de secretaria nasceu');
$ok($um("SELECT CONCAT(ordem, '|', IFNULL(papel, '-'), '|', so_consulta, acoes_sensiveis) FROM adms_niveis_acessos WHERE id = {$proprio}") === '7|-|00', 'o nível criado na tela ficou, sem papel e sem marcas, logo depois dos papéis (ordem 7)');
$ok((int) $um("SELECT adms_niveis_acesso_id FROM adms_usuarios WHERE usuario = 'nivel.proprio'") === $proprio, 'o usuário do nível próprio continua nele');
if ($temSalvo) {
    $ok((int) $um("SELECT nivel_acesso_id FROM rm_relatorios_salvos WHERE nome = 'Visão compartilhada com o nível 2'") === 1, 'a visão de relatório do nível 2 passou ao Administrador');
}
$ok((int) $um('SELECT COUNT(*) FROM adms_nivacs_pgs WHERE adms_niveis_acesso_id NOT IN (SELECT id FROM adms_niveis_acessos)') === 0, 'nenhuma permissão de nível que não existe');
[$rc, $saida] = $rodar("{$php} " . escapeshellarg("{$raiz}/tests/tabler/onda2/conferir_acesso.php"));
$ok($rc === 0 && trim($saida) === 'OK', 'a conferência do instalador passa depois da atualização (nenhuma página sem perfil)');
[$rc, $saida] = $rodar("{$php} " . escapeshellarg("{$raiz}/bin/migrar.php") . ' aplicar --sim');
$ok($rc === 0 && str_contains($saida, '0 migration'), 'rodar de novo não aplica nada');

echo "\nmigração 0005: {$oks} OK, " . count($falhas) . " FALHA\n";
foreach ($falhas as $f) {
    echo "  - {$f}\n";
}
exit($falhas === [] ? 0 : 1);
