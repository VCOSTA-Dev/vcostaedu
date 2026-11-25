<?php
/*
 * O ENDEREÇO FALSO da versão nova, para a prova da Etapa 6 (php -S, só na máquina da prova).
 * Cada pedido fica anotado (o método, o caminho com a query, os cabeçalhos) no arquivo do ambiente VC_VERSAO_LOG.
 *   /versao        -> {"versao": "9.9.9", "notas": "https://exemplo.invalid/notas"}
 *   /devagar       -> espera 10 segundos antes de responder (o tempo curto da consulta)
 *   /grande        -> 64 KB (a consulta só lê um JSON pequeno)
 */
$log = getenv('VC_VERSAO_LOG');
if ($log) {
    file_put_contents($log, json_encode(['metodo' => $_SERVER['REQUEST_METHOD'], 'uri' => $_SERVER['REQUEST_URI'],
        'cabecalhos' => function_exists('getallheaders') ? getallheaders() : [], 'corpo' => file_get_contents('php://input')]) . "\n", FILE_APPEND);
}
$caminho = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
header('Content-Type: application/json');
if ($caminho === '/devagar') {
    sleep(10);
}
if ($caminho === '/grande') {
    echo str_repeat(' ', 65536) . '{"versao": "9.9.9"}';
    return true;
}
echo '{"versao": "9.9.9", "notas": "https://exemplo.invalid/notas"}';
return true;
