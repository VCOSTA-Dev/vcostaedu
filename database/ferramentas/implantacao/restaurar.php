<?php
/*
 * RESTAURAR UM BACKUP (E8, D-245) num banco NOVO e numa pasta NOVA, e CONFERIR.
 *
 * Uso: php database/ferramentas/implantacao/restaurar.php <pasta do backup> --banco=NOME --storage=PASTA
 *   --banco:   banco de destino, vazio ou inexistente (é criado, se o usuário puder). NUNCA o banco da Config.
 *   --storage: pasta de destino dos arquivos, vazia ou inexistente.
 *
 * Confere, antes: o sha256 de banco.sql.gz e storage.zip contra o manifesto. Depois: o CHECKSUM de cada tabela,
 * as contagens de emissões e vias, os 4 gatilhos e, para cada emissão e cada via, que o PDF restaurado existe e
 * tem o sha256 gravado no banco (hash_pdf). Para USAR o restaurado, aponte DBNAME e PASTA_STORAGE das duas
 * Config.php para ele (docs/IMPLANTACAO.md, "Voltar atrás").
 * Usa o usuário do banco da Config (que precisa poder criar o banco de destino, ou ele já deve existir vazio).
 */
require __DIR__ . '/_base.php';
require __DIR__ . '/../comum.php';

$o = vcostaedu_args($argv);
$orig = rtrim(str_replace('\\', '/', (string) ($o['_'][0] ?? '')), '/');
$banco = (string) ($o['banco'] ?? '');
$stDest = rtrim(str_replace('\\', '/', (string) ($o['storage'] ?? '')), '/');
if ($orig === '' || $banco === '' || $stDest === '' || $banco === '1' || $stDest === '1') {
    fwrite(STDERR, "Uso: php restaurar.php <pasta do backup> --banco=NOME --storage=PASTA\n");
    exit(2);
}

Saida::secao('Backup');
$man = json_decode((string) @file_get_contents("{$orig}/manifesto.json"), true);
if (!is_array($man)) {
    Saida::falha("não achei {$orig}/manifesto.json");
    Saida::fim();
}
foreach ($man['arquivos'] as $a => $h) {
    is_file("{$orig}/{$a}") && hash_file('sha256', "{$orig}/{$a}") === $h ? Saida::ok("{$a}: sha256 confere") : Saida::falha("{$a}: ausente ou sha256 diferente do manifesto");
}
if (Saida::$falhas) {
    Saida::fim();
}
Saida::info("de {$man['gerado_em']}, versão {$man['versao_banco']}, banco {$man['banco']}, ambiente {$man['ambiente']}");

Saida::secao('Destino');
if (!preg_match('/^[A-Za-z0-9_]{1,64}$/', $banco)) {
    Saida::falha('nome de banco inválido (só letras, dígitos e _)');
    Saida::fim();
}
if ($banco === DBNAME) {
    Saida::falha("o destino é o banco da Config ({$banco}). Restaure num banco NOVO e depois aponte a Config para ele.");
    Saida::fim();
}
$pdo = vcostaedu_pdo();
$existe = (int) $pdo->query('SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ' . $pdo->quote($banco))->fetchColumn();
if ($existe) {
    $n = (int) $pdo->query('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = ' . $pdo->quote($banco))->fetchColumn();
    if ($n > 0) {
        Saida::falha("o banco {$banco} já tem {$n} tabela(s): use um banco vazio");
        Saida::fim();
    }
    Saida::ok("banco {$banco} existe e está vazio");
} else {
    try {
        $pdo->exec("CREATE DATABASE `{$banco}` CHARACTER SET {$man['charset']} COLLATE {$man['collation']}");
        Saida::ok("banco {$banco} criado ({$man['charset']} / {$man['collation']})");
    } catch (Throwable $e) {
        Saida::falha("não consegui criar o banco {$banco} (crie-o vazio no painel da hospedagem): " . $e->getMessage());
        Saida::fim();
    }
}
if (is_dir($stDest) && count(scandir($stDest)) > 2) {
    Saida::falha("a pasta {$stDest} não está vazia");
    Saida::fim();
}

Saida::secao('Restauração');
$cmd = escapeshellarg(vcostaedu_bin('mysql')) . ' --default-character-set=utf8mb4 -h ' . escapeshellarg(HOST) . ' -u ' . escapeshellarg(USER) . ' ' . escapeshellarg($banco);
$p = proc_open($cmd, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pp, null, array_merge(getenv(), ['MYSQL_PWD' => PASS]));
$gz = gzopen("{$orig}/banco.sql.gz", 'rb');
while (!gzeof($gz)) {
    fwrite($pp[0], (string) gzread($gz, 1 << 20));
}
gzclose($gz);
fclose($pp[0]);
$erro = stream_get_contents($pp[1]) . stream_get_contents($pp[2]);
fclose($pp[1]);
fclose($pp[2]);
$rc = proc_close($p);
$rc === 0 ? Saida::ok("banco restaurado em {$banco}") : Saida::falha("o mysql parou (código {$rc}): " . trim($erro));
if (!is_dir($stDest) && !@mkdir($stDest, 0770, true)) {
    Saida::falha("não consegui criar {$stDest}");
    Saida::fim();
}
$zip = new ZipArchive();
$zip->open("{$orig}/storage.zip") === true && $zip->extractTo($stDest) ? Saida::ok("{$zip->numFiles} entrada(s) de storage.zip em {$stDest}") : Saida::falha('não consegui extrair storage.zip');
$zip->close();

Saida::secao('Conferência');
require_once dirname(__DIR__, 3) . '/core/Banco.php'; // Onda 3, Etapa 2: o modo de SQL do sistema
$r = VcBanco::abrir('mysql:host=' . HOST . ';dbname=' . $banco . ';charset=utf8mb4', USER, PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$tabs = $r->query('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()')->fetchAll(PDO::FETCH_COLUMN);
$dif = [];
foreach ($man['tabelas'] as $t => $c) {
    $cr = in_array($t, $tabs, true) ? (string) $r->query("CHECKSUM TABLE `{$t}`")->fetch()['Checksum'] : 'ausente';
    if ($cr !== (string) $c) {
        $dif[] = "{$t} ({$cr} ≠ {$c})";
    }
}
count($tabs) === count($man['tabelas']) && !$dif ? Saida::ok(count($tabs) . ' tabelas; CHECKSUM de todas igual ao do manifesto')
    : Saida::falha(count($tabs) . ' tabelas (manifesto: ' . count($man['tabelas']) . '); CHECKSUM diferente em: ' . implode(', ', array_slice($dif, 0, 10)));
$ne = (int) $r->query('SELECT COUNT(*) FROM rm_historico_emissoes')->fetchColumn();
$nv = (int) $r->query('SELECT COUNT(*) FROM rm_historico_emissao_vias')->fetchColumn();
$ne === (int) $man['emissoes'] && $nv === (int) $man['vias'] ? Saida::ok("{$ne} emissões e {$nv} vias, como no manifesto") : Saida::falha("{$ne} emissões e {$nv} vias (manifesto: {$man['emissoes']} e {$man['vias']})");
$ng = (int) $r->query("SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE() AND TRIGGER_NAME LIKE 'rm\\_historico%'")->fetchColumn();
$ng === 4 ? Saida::ok('os 4 gatilhos do histórico restaurados') : Saida::falha("{$ng} gatilhos do histórico (esperados 4)");
$ruins = [];
$pdfs = $r->query("SELECT CONCAT('emissão ', id) quem, pdf_arquivo, hash_pdf FROM rm_historico_emissoes UNION ALL SELECT CONCAT('via ', id, ' da emissão ', emissao_id), pdf_arquivo, hash_pdf FROM rm_historico_emissao_vias")->fetchAll();
foreach ($pdfs as $e) {
    $f = "{$stDest}/historico/emissoes/" . basename((string) $e['pdf_arquivo']);
    if (!is_file($f)) {
        $ruins[] = "{$e['quem']}: PDF ausente";
    } elseif (!hash_equals((string) $e['hash_pdf'], hash_file('sha256', $f))) {
        $ruins[] = "{$e['quem']}: sha256 do PDF diferente";
    }
}
$ruins ? Saida::falha(implode('; ', array_slice($ruins, 0, 10))) : Saida::ok(count($pdfs) . ' PDF(s) de emissões e vias: todos presentes, com o sha256 gravado no banco');
Saida::info("Para usar o restaurado: DBNAME = '{$banco}' e PASTA_STORAGE = '{$stDest}/' nas duas Config.php.");
Saida::fim();
