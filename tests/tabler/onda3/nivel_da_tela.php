<?php
/*
 * ONDA 3, ETAPA 3 — um nível "criado pela tela" numa instalação anterior (a tela de níveis saiu na 1.29.0), com uma
 * pessoa nele, no banco de prova de atualização (VCOSTAEDU_BANCO_TESTE + "2"; nenhum outro nome é aceito).
 *
 *   php tests/tabler/onda3/nivel_da_tela.php
 *
 * O nível nasce como a tela de níveis o criava: sem papel e sem marcas, depois dos perfis do sistema na ordem, com as
 * permissões do Professor copiadas (como a tela "Liberar" fazia, uma a uma) e mais uma permissão numa tela de
 * programação. A pessoa é cópia do administrador (a mesma senha), com outro usuário e outro e-mail. Imprime o JSON.
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
$pdo = new PDO('mysql:host=localhost;dbname=' . $base . '2;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$inserir = function (string $tabela, array $linha) use ($pdo): int {
    unset($linha['id']);
    $cols = array_keys($linha);
    $pdo->prepare("INSERT INTO `{$tabela}` (`" . implode('`, `', $cols) . '`) VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')')->execute(array_values($linha));
    return (int) $pdo->lastInsertId();
};
$prof = $pdo->query("SELECT * FROM adms_niveis_acessos WHERE papel = 'professor'")->fetch();
$nivel = $prof;
$nivel['nome'] = 'Monitoria';
$nivel['papel'] = null;
$nivel['so_consulta'] = 0;
$nivel['acoes_sensiveis'] = 0;
$nivel['ordem'] = (int) $pdo->query('SELECT MAX(ordem) FROM adms_niveis_acessos')->fetchColumn() + 1;
$nivel['created'] = date('Y-m-d H:i:s');
$nivel['modified'] = null;
$nid = $inserir('adms_niveis_acessos', $nivel);
foreach ($pdo->query('SELECT * FROM adms_nivacs_pgs WHERE adms_niveis_acesso_id = ' . (int) $prof['id'])->fetchAll() as $l) {
    $l['adms_niveis_acesso_id'] = $nid;
    $inserir('adms_nivacs_pgs', $l);
}
/* uma permissão numa tela de programação (a de níveis), dada pela tela antiga */
$pgNivel = (int) $pdo->query("SELECT id FROM adms_paginas WHERE controller = 'NivelAcesso' AND metodo = 'listar'")->fetchColumn();
$pdo->exec("UPDATE adms_nivacs_pgs SET permissao = 1 WHERE adms_niveis_acesso_id = {$nid} AND adms_pagina_id = {$pgNivel}");
$adm = $pdo->query('SELECT * FROM adms_usuarios WHERE adms_niveis_acesso_id = 1 ORDER BY id LIMIT 1')->fetch();
$u = $adm;
$u['nome'] = 'Pessoa Monitora Exemplo';
$u['apelido'] = array_key_exists('apelido', $u) ? 'Monitora' : null;
if (!array_key_exists('apelido', $adm)) {
    unset($u['apelido']);
}
$u['email'] = 'monitora.prova@exemplo.invalid';
$u['usuario'] = 'monitora.prova';
$u['adms_niveis_acesso_id'] = $nid;
$u['created'] = date('Y-m-d H:i:s');
$u['modified'] = null;
$uid = $inserir('adms_usuarios', $u);
echo json_encode([
    'nivel' => $nid, 'usuario' => 'monitora.prova', 'id' => $uid,
    'permissoes' => (int) $pdo->query("SELECT COUNT(*) FROM adms_nivacs_pgs WHERE adms_niveis_acesso_id = {$nid} AND permissao = 1")->fetchColumn(),
    'naTelaDeNiveis' => (int) $pdo->query("SELECT permissao FROM adms_nivacs_pgs WHERE adms_niveis_acesso_id = {$nid} AND adms_pagina_id = {$pgNivel}")->fetchColumn(),
]) . "\n";
