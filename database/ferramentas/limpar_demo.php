<?php
/*
 * LIMPA A DEMONSTRAÇÃO (E7, D-220) — o ÚNICO caminho para rodar database/seed_demo.sql.
 *
 * Recusa (sai com erro, sem tocar no banco) quando:
 *   - AMBIENTE (painel/core/Config.php) não é 'demonstracao';
 *   - existe emissão de histórico que não é da demonstração (coluna ambiente <> 'demonstracao').
 * Passando, roda o seed_demo.sql pelo cliente mysql com @vcostaedu_limpeza_demo (o próprio SQL
 * confere de novo as duas travas), que tira os gatilhos, apaga as emissões da demonstração e
 * os recria; no fim, confere os gatilhos (conferir_gatilhos.php).
 *
 * Uso: php database/ferramentas/limpar_demo.php [--sobrescrever-instituicao-configurada]
 * 1.18.0 (D-471): num banco de demonstração SEPARADO (nome diferente do oficial), declare na Config
 * define('BANCO_DEMONSTRACAO', '<o mesmo DBNAME>'); sem isso, o seed_demo.sql recusa outro nome de banco.
 * E1b (D-352): recusa também quando a instituição foi configurada pela tela (a demonstração a apagaria), a não ser
 * com a confirmação acima.
 * Cliente mysql: o do PATH, ou o caminho em VCOSTAEDU_MYSQL. A senha do banco vai por MYSQL_PWD
 * (variável de ambiente do processo filho), nunca na linha de comando.
 */
require __DIR__ . '/comum.php';

$ambiente = defined('AMBIENTE') ? (string) AMBIENTE : 'producao';
$demoPelaTela = vcostaedu_demonstracao_ativa(vcostaedu_pdo()); // Onda 2, Etapa 4
if ($ambiente !== 'demonstracao' && !$demoPelaTela) {
    fwrite(STDERR, "RECUSADO: AMBIENTE = '{$ambiente}' em painel/core/Config.php. A limpeza da demonstração só roda com AMBIENTE = 'demonstracao'. Nada foi alterado.\n");
    exit(2);
}
$pdo = vcostaedu_pdo();
$reais = (int) $pdo->query("SELECT COUNT(*) FROM rm_historico_emissoes WHERE ambiente <> 'demonstracao'")->fetchColumn();
if ($reais > 0) {
    fwrite(STDERR, "RECUSADO: há {$reais} emissão(ões) de histórico que NÃO são da demonstração. A limpeza apagaria documentos reais. Nada foi alterado.\n");
    exit(3);
}
vcostaedu_trava_instituicao($pdo, $argv, 'limpar_demo.php'); // E1b (D-352)

/* 1.18.0 (D-471): banco de DEMONSTRAÇÃO separado. O seed_demo.sql só aceita o banco oficial ou o banco declarado em
   BANCO_DEMONSTRACAO na Config — e a declaração só vale se for igual ao DBNAME em uso (AMBIENTE já conferido acima). */
$bancoDemo = (defined('BANCO_DEMONSTRACAO') && (string) BANCO_DEMONSTRACAO !== '' && (string) BANCO_DEMONSTRACAO === (string) DBNAME) ? (string) DBNAME : '';
if ($demoPelaTela) {
    $bancoDemo = (string) DBNAME; // a demonstração ligada pela tela declara o banco em uso (Onda 2, Etapa 4)
}
if (defined('BANCO_DEMONSTRACAO') && (string) BANCO_DEMONSTRACAO !== '' && $bancoDemo === '') {
    fwrite(STDERR, "RECUSADO: BANCO_DEMONSTRACAO na Config não é o banco em uso (DBNAME). Nada foi alterado.
");
    exit(7);
}

$mysql = getenv('VCOSTAEDU_MYSQL') ?: (is_file('C:/xampp/mysql/bin/mysql.exe') ? 'C:/xampp/mysql/bin/mysql.exe' : 'mysql');
$sql = dirname(__DIR__) . '/seed_demo.sql';
putenv('MYSQL_PWD=' . PASS);
$cmd = escapeshellarg($mysql) . ' --default-character-set=utf8mb4 -h ' . escapeshellarg(HOST) . ' -u ' . escapeshellarg(USER)
    . ' ' . escapeshellarg("--init-command=SET SESSION sql_mode = '" . \VcBanco::MODO . "', @vcostaedu_limpeza_demo = 'demonstracao', @vcostaedu_banco_demonstracao = '" . str_replace("'", '', $bancoDemo) . "'") . ' ' . escapeshellarg(DBNAME) . ' < ' . escapeshellarg($sql) . ' 2>&1';
exec($cmd, $saida, $rc);
putenv('MYSQL_PWD');
echo implode("\n", array_filter($saida, fn($l) => !str_starts_with($l, 'aviso'))) . "\n";
if ($rc !== 0) {
    fwrite(STDERR, "FALHA ao rodar o seed_demo.sql (código {$rc}).\n");
    exit(4);
}
echo "Demonstração anterior apagada.\n";
passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/conferir_gatilhos.php'), $rcG);
exit($rcG === 0 ? 0 : 5);
