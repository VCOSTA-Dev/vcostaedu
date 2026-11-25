<?php
/*
 * A conferência final do instalador (instalar/administrador.php::conferirAcesso), rodada contra a instalação atual —
 * a MESMA função, para a prova poder sabotá-la (uma página sem perfil tem de reprovar). Só linha de comando.
 * Imprime OK ou a lista do que falhou.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$raiz = dirname(__DIR__, 3);
$config = (string) file_get_contents($raiz . '/painel/core/Config.php');
$c = static fn(string $k): string => preg_match("/define\\('{$k}', '([^']*)'\\);/", $config, $m) ? $m[1] : '';
if (!str_ends_with($c('DBNAME'), '_teste') && !str_ends_with($c('DBNAME'), '_teste2')) {
    fwrite(STDERR, "RECUSADO: a configuração não aponta para um banco de prova.\n");
    exit(2);
}
define('VCOSTAEDU_INSTALADOR', true);
$_SESSION = [];
require $raiz . '/core/Senha.php';
require $raiz . '/instalar/administrador.php';
$pdo = new PDO('mysql:host=' . $c('HOST') . ';dbname=' . $c('DBNAME') . ';charset=utf8mb4', $c('USER'), $c('PASS'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$r = conferirAcesso($pdo);
if ($r['ok']) {
    echo "OK\n";
    exit(0);
}
foreach ($r['itens'] as $i) {
    if (!$i['ok']) {
        echo $i['rotulo'] . ': ' . $i['detalhe'] . "\n";
    }
}
exit(1);
