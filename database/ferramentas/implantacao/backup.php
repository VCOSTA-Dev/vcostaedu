<?php
/*
 * BACKUP (E8, D-245): banco (com os gatilhos) + arquivos da PASTA_STORAGE, com data, sha256 e retenção.
 *
 * Uso: php database/ferramentas/implantacao/backup.php --destino=PASTA [--manter=7]
 *   --destino: onde guardar, FORA da pasta do sistema (e, de preferência, fora da raiz pública).
 *   --manter:  quantos backups guardar nessa pasta (os mais antigos além disso são apagados). Padrão 7.
 *
 * Cria <destino>/vcostaedu_backup_AAAAMMDD_HHMMSS/ com:
 *   banco.sql.gz   — mysqldump (transação única, com gatilhos), sem as cláusulas DEFINER (para restaurar com
 *                    outro usuário do banco);
 *   storage.zip    — a PASTA_STORAGE inteira (PDFs das emissões e das vias, assinaturas);
 *   manifesto.json — data, versão, banco, ambiente, sha256 dos dois arquivos, CHECKSUM de cada tabela e as
 *                    contagens de emissões e vias (a restauração confere tudo isso).
 * A senha do banco vai por variável de ambiente do processo filho (nunca na linha de comando).
 * Agendamento (cron) e cópia fora do servidor: ver README, "Backup".
 */
require __DIR__ . '/_base.php';
require __DIR__ . '/../comum.php';

$o = vcostaedu_args($argv);
$raiz = str_replace('\\', '/', realpath(VCOSTAEDU_RAIZ));
$manter = max(1, (int) ($o['manter'] ?? 7));
if (empty($o['destino']) || $o['destino'] === true) {
    fwrite(STDERR, "Informe --destino=PASTA (fora da pasta do sistema).\n");
    exit(2);
}
$destino = rtrim(str_replace('\\', '/', (string) $o['destino']), '/');
if (!is_dir($destino) && !@mkdir($destino, 0770, true)) {
    Saida::falha("não foi possível criar {$destino}");
    Saida::fim();
}
$destino = str_replace('\\', '/', realpath($destino));
if (str_starts_with($destino . '/', $raiz . '/')) {
    Saida::falha("o destino ({$destino}) está dentro da pasta do sistema: escolha uma pasta fora dela");
    Saida::fim();
}

Saida::secao('Banco');
$pdo = vcostaedu_pdo();
$tabelas = $pdo->query('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME')->fetchAll(PDO::FETCH_COLUMN);
$sch = $pdo->query('SELECT DEFAULT_CHARACTER_SET_NAME cs, DEFAULT_COLLATION_NAME co FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = DATABASE()')->fetch();
$pasta = $destino . '/vcostaedu_backup_' . date('Ymd_His');
if (!@mkdir($pasta, 0770)) {
    Saida::falha("não foi possível criar {$pasta}");
    Saida::fim();
}
$cmd = escapeshellarg(vcostaedu_bin('mysqldump')) . ' --single-transaction --quick --routines --triggers --hex-blob --no-tablespaces --default-character-set=utf8mb4 -h '
    . escapeshellarg(HOST) . ' -P ' . (defined('PORTA') ? (int) constant('PORTA') : 3306) . ' -u ' . escapeshellarg(USER) . ' ' . escapeshellarg(DBNAME);
$env = array_merge(getenv(), ['MYSQL_PWD' => PASS]);
$p = proc_open($cmd, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pp, null, $env);
if (!is_resource($p)) {
    Saida::falha('não foi possível rodar o mysqldump (defina VCOSTAEDU_MYSQLDUMP com o caminho)');
    Saida::fim();
}
fclose($pp[0]);
$gz = gzopen("{$pasta}/banco.sql.gz", 'wb6');
$criacoes = 0;
$gatilhos = 0;
while (($l = fgets($pp[1])) !== false) {
    /* sem DEFINER: a restauração cria os gatilhos com o usuário que restaura */
    $l = preg_replace(['#/\*!50017 DEFINER=`[^`]*`@`[^`]*`\s*\*/\s?#', '#\bDEFINER=`[^`]*`@`[^`]*`\s?#'], '', $l);
    $criacoes += str_starts_with($l, 'CREATE TABLE') ? 1 : 0;
    $gatilhos += preg_match('/CREATE\*\/ .*TRIGGER|^CREATE TRIGGER/', $l) ? 1 : 0;
    gzwrite($gz, $l);
}
gzclose($gz);
$erro = stream_get_contents($pp[2]);
fclose($pp[1]);
fclose($pp[2]);
$rc = proc_close($p);
if ($rc !== 0) {
    Saida::falha("mysqldump parou (código {$rc}): " . trim($erro));
    Saida::fim();
}
$criacoes === count($tabelas) ? Saida::ok("dump de {$criacoes} tabelas e {$gatilhos} gatilhos (" . vcostaedu_tam(filesize("{$pasta}/banco.sql.gz")) . ')') : Saida::falha("o dump tem {$criacoes} CREATE TABLE para " . count($tabelas) . ' tabelas');

/* CHECKSUM logo depois do dump (tabelas de registro de uso podem mudar no meio, num sistema em uso) */
$ck = [];
foreach ($tabelas as $t) {
    $ck[$t] = (string) $pdo->query('CHECKSUM TABLE `' . str_replace('`', '', $t) . '`')->fetch()['Checksum'];
}
$emissoes = (int) $pdo->query('SELECT COUNT(*) FROM rm_historico_emissoes')->fetchColumn();
$vias = (int) $pdo->query('SELECT COUNT(*) FROM rm_historico_emissao_vias')->fetchColumn();

Saida::secao('Arquivos (PASTA_STORAGE)');
$st = rtrim(str_replace('\\', '/', (string) realpath(PASTA_STORAGE)), '/');
$zip = new ZipArchive();
$zip->open("{$pasta}/storage.zip", ZipArchive::CREATE);
$n = 0;
if ($st !== '' && is_dir($st)) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($st, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
    foreach ($it as $f) {
        $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($st) + 1));
        if ($f->isDir()) {
            $zip->addEmptyDir($rel);
        } else {
            $zip->addFile($f->getPathname(), $rel);
            $n++;
        }
    }
}
$zip->close();
$n > 0 || $emissoes === 0 ? Saida::ok("{$n} arquivo(s) de {$st} (" . vcostaedu_tam(filesize("{$pasta}/storage.zip")) . ')') : Saida::falha("há {$emissoes} emissões, mas nenhum arquivo em {$st}");

$man = [
    'sistema' => 'VCOSTAEDU', 'gerado_em' => date('c'), 'versao_banco' => (string) $pdo->query('SELECT numero FROM adms_versoes WHERE situacao = 1 ORDER BY id DESC LIMIT 1')->fetchColumn(),
    'banco' => DBNAME, 'charset' => $sch['cs'], 'collation' => $sch['co'], 'ambiente' => defined('AMBIENTE') ? AMBIENTE : 'producao',
    'arquivos' => ['banco.sql.gz' => hash_file('sha256', "{$pasta}/banco.sql.gz"), 'storage.zip' => hash_file('sha256', "{$pasta}/storage.zip")],
    'tabelas' => $ck, 'gatilhos' => $gatilhos, 'emissoes' => $emissoes, 'vias' => $vias, 'arquivos_storage' => $n,
];
file_put_contents("{$pasta}/manifesto.json", json_encode($man, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
Saida::ok("manifesto.json: sha256 dos arquivos, CHECKSUM de " . count($ck) . " tabelas, {$emissoes} emissões e {$vias} vias");
Saida::ok("backup em {$pasta}");

Saida::secao("Retenção (manter {$manter})");
/* os backups de antes da 1.45.0 têm o prefixo antigo (DECISIONS D-53): entram na mesma conta, pela data do nome */
$antigo = 'escola' . '1';
$todos = array_merge(glob($destino . '/vcostaedu_backup_*', GLOB_ONLYDIR) ?: [], glob($destino . "/{$antigo}_backup_*", GLOB_ONLYDIR) ?: []);
$todos = array_values(array_filter($todos, fn($d) => preg_match("#/(vcostaedu|{$antigo})_backup_\\d{8}_\\d{6}$#", str_replace('\\', '/', $d))));
usort($todos, fn($a, $b) => strcmp(substr(basename($a), -15), substr(basename($b), -15)));
$apagar = array_slice($todos, 0, max(0, count($todos) - $manter));
foreach ($apagar as $d) {
    foreach (['banco.sql.gz', 'storage.zip', 'manifesto.json'] as $a) {
        @unlink("{$d}/{$a}");
    }
    @rmdir($d) ? Saida::ok('apagado o backup antigo ' . basename($d)) : Saida::alerta('não consegui apagar ' . basename($d) . ' (há arquivos a mais?)');
}
Saida::info(count($todos) - count($apagar) . " backup(s) em {$destino}. Copie a pasta nova para FORA do servidor (ver README, \"Backup\").");
Saida::fim();
