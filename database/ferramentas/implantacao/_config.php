<?php
/*
 * Lê a Config de UM app (painel ou portal) e imprime, em JSON, as constantes que as ferramentas conferem.
 * NUNCA imprime a senha do banco (só se está preenchida). Roda em processo próprio, porque as duas Configs
 * definem as mesmas constantes. Uso interno: php _config.php painel|portal
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$app = ($argv[1] ?? '') === 'portal' ? 'portal' : 'painel';
$arq = __DIR__ . "/../../../{$app}/core/Config.php";
if (!is_file($arq)) {
    echo json_encode(['existe' => false]);
    exit;
}
define('VCOSTAEDU', true);
define('VCOSTAEDU_APP_PORTAL', true); // o portal (antes, a pergunta era o nome da guarda dele)
foreach (['Un1Mv' . 'C05tA', 'ColMv' . 'C05tA'] as $g) { defined($g) || define($g, true); } // a guarda de antes da 1.45.0, só para a Config.php já instalada (DECISIONS D-53)
$_SERVER['SERVER_NAME'] = $_SERVER['SERVER_NAME'] ?? 'localhost';
require $arq;
final class VcostaeduLerConfig extends \Core\Config
{
    public function carregar(): void
    {
        $this->configAdm();
    }
}
(new VcostaeduLerConfig())->carregar();
require_once __DIR__ . '/../../../core/Requisicao.php';
\Core\Requisicao::definir();
$c = ['existe' => true, 'fuso' => date_default_timezone_get()];
foreach (['URL', 'VERSAODEV', 'HOST', 'USER', 'DBNAME', 'EXIGIR_HTTPS', 'ORIGENS_PERMITIDAS_IFRAME', 'COOKIE_PARTICIONADO', 'PROXIES_CONFIAVEIS', 'HOSTS_PERMITIDOS', 'CAMINHO_BASE', 'PASTA_STORAGE', 'AMBIENTE', 'NOME_INSTITUICAO', 'EMAILADM'] as $k) {
    $c[$k] = defined($k) ? constant($k) : null;
}
$c['PASS_preenchida'] = defined('PASS') && PASS !== '';
echo json_encode($c, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
