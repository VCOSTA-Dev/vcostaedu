<?php
/*
 * Chama o aviso de versão nova (core/VersaoNova.php) como a tela chama, para a prova da Etapa 6.
 * Uso: php tests/tabler/onda3/versao_nova.php <endereço> <segundos a somar ao relógio> <versão instalada>
 * Imprime o JSON da situação e o tempo gasto (ms). Só no banco de teste.
 */
if (PHP_SAPI !== 'cli') {
    exit;
}
[, $url, $mais, $instalada] = $argv + [null, '', '0', '1.32.0'];
define('URL_VERSAO_NOVA', (string) $url);
require dirname(__DIR__, 3) . '/database/ferramentas/comum.php';
require_once dirname(__DIR__, 3) . '/core/VersaoNova.php';
if (!preg_match('/_teste2?$/', DBNAME)) {
    fwrite(STDERR, "só no banco de teste\n");
    exit(2);
}
$t = microtime(true);
$s = VcVersaoNova::situacao(vcostaedu_pdo(), (string) $instalada, time() + (int) $mais);
echo json_encode($s + ['ms' => (int) round((microtime(true) - $t) * 1000)], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), "\n";
