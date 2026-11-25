<?php
/*
 * A API DE INTEGRAÇÃO (Onda 2, Etapa 6): /api/v1/... — JSON, UTF-8, chave no cabeçalho Authorization (Bearer).
 * Esta porta só carrega o painel e entrega a requisição a App\adms\Models\api\AdmsApiServidor. Nenhuma mensagem de
 * erro do PHP sai na resposta (o detalhe vai para o log do servidor). Sem CORS: outro site não chama a API pelo
 * navegador de quem está logado.
 */
ini_set('display_errors', '0');
error_reporting(E_ALL);
header_remove('X-Powered-By');

$raiz = dirname(__DIR__);
$responder = static function (int $status, array $corpo, array $cab = []): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    foreach ($cab as $k => $v) {
        header($k . ': ' . $v);
    }
    $b = (string) json_encode($corpo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
    /* Onda 8 (D-70): com o tamanho dito, quem chama recebe a resposta inteira sem esperar o que o fim da requisição
       ainda faz (a retomada dos envios ao Moodle) */
    header('Content-Length: ' . strlen($b));
    echo $b;
};

if (!is_file($raiz . '/painel/core/Config.php')) {
    $responder(503, ['erro' => ['codigo' => 'nao_instalado', 'mensagem' => 'O sistema ainda não foi instalado.', 'campo' => null]]);
    exit;
}

try {
    define('VCOSTAEDU', true);
    foreach (['Un1Mv' . 'C05tA'] as $g) { defined($g) || define($g, true); } // a guarda de antes da 1.45.0, só para a Config.php já instalada (DECISIONS D-53)
    chdir($raiz . '/painel'); // os modelos do painel esperam rodar de lá
    require $raiz . '/painel/vendor/autoload.php';
    (new class extends \Core\Config {
        public function __construct()
        {
            $this->configAdm();
        }
    });
    /* Onda 8 (D-70): no fim de toda chamada, depois do envio dela mesma ao Moodle, o que sobrou na fila anda sozinho,
       num orçamento curto (AdmsMoodle::retomar) */
    register_shutdown_function(static function (): void {
        register_shutdown_function([\App\adms\Models\moodle\AdmsMoodle::class, 'retomar']);
    });
    require_once $raiz . '/core/Requisicao.php';
    require_once $raiz . '/core/Transacao.php'; // Onda 3, Etapa 2: a conexão de quem registra a chamada (antes, faltava nas recusas que vêm antes de qualquer consulta)
    \Core\Requisicao::definir();
    $_SESSION = [];

    /* o caminho depois de /api/v1 */
    $uri = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    $base = rtrim(parse_url(URL, PHP_URL_PATH) ?: '/', '/') . '/api';
    $caminho = substr(rawurldecode($uri), strlen($base));
    if (!preg_match('#^/v1(/.*)?$#', (string) $caminho, $m)) {
        $responder(404, ['erro' => ['codigo' => 'rota_inexistente', 'mensagem' => 'A API fica em /api/v1. A documentação está em Integrações → Documentação da API.', 'campo' => null]]);
        exit;
    }
    $cabecalhos = function_exists('getallheaders') ? (getallheaders() ?: []) : [];
    if (!isset(array_change_key_case($cabecalhos, CASE_LOWER)['authorization'])) {
        $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null;
        if ($auth !== null) {
            $cabecalhos['Authorization'] = $auth;
        }
    }
    [$status, $corpo, $cab] = (new \App\adms\Models\api\AdmsApiServidor())->atender(
        (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'),
        $m[1] ?? '/',
        $_GET,
        (string) file_get_contents('php://input'),
        $cabecalhos,
        \Core\Seguranca::ipCliente(),
        \Core\Requisicao::https()
    );
    $responder($status, $corpo, $cab);
} catch (\Throwable $e) {
    error_log('[vcostaedu][api] ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine());
    $responder(500, ['erro' => ['codigo' => 'erro_interno', 'mensagem' => 'Erro interno. Tente de novo ou avise o administrador.', 'campo' => null]]);
}
