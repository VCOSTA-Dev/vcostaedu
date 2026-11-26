<?php
/*
 * Apoio da Onda 8, etapa 55 (D-131): conecta pela conexão das telas (VcBanco::daRequisicao) com a porta pedida, lendo o
 * servidor, o usuário, a senha e o banco da Config.php do painel desta pasta. Imprime "conectou" ou "recusou".
 * Uso: php tests/tabler/onda8/porta_conexao.php <porta>
 */
$cfg = (string) file_get_contents(dirname(__DIR__, 3) . '/painel/core/Config.php');
foreach (['HOST', 'USER', 'PASS', 'DBNAME'] as $c) {
    if (!preg_match("/define\\('" . $c . "',\\s*(.+?)\\);/", $cfg, $m)) {
        fwrite(STDERR, "sem {$c}\n");
        exit(2);
    }
    define($c, (string) eval('return ' . $m[1] . ';')); // o valor gravado por var_export()
}
define('PORTA', (int) ($argv[1] ?? 3306));
require dirname(__DIR__, 3) . '/core/Banco.php';
try {
    \VcBanco::daRequisicao()->query('SELECT 1')->fetchColumn();
    echo "conectou\n";
} catch (\Throwable $e) {
    echo "recusou\n";
}
