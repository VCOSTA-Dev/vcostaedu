<?php
/*
 * ONDA 2, ETAPA 6 — as rotas que o servidor da API implementa (AdmsApiServidor::ROTAS), em JSON, para a prova
 * conferir com a especificação docs/api/openapi.json (nos dois sentidos).
 *   php tests/tabler/onda2/rotas_api.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
define('VCOSTAEDU', true);
foreach (['Un1Mv' . 'C05tA'] as $g) { defined($g) || define($g, true); } // a guarda de antes da 1.45.0, só para a Config.php já instalada (DECISIONS D-53)
require dirname(__DIR__, 3) . '/painel/app/adms/Models/api/AdmsApiServidor.php';
echo json_encode(array_map(fn($r) => ['metodo' => $r[0], 'caminho' => $r[1], 'operacao' => $r[2], 'escrita' => $r[3], 'assunto' => $r[4]], \App\adms\Models\api\AdmsApiServidor::ROTAS), JSON_UNESCAPED_SLASHES), "\n";
