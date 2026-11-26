<?php
/*
 * O CÁLCULO DE UM ALUNO NUMA OFERTA, EM JSON (histórico escolar, Parte H; D-117): para a prova conferir a etapa que
 * ficou fora da conta depois da saída do aluno. Só lê.
 *   php tests/tabler/historico/calc.php <oferta_id> <rm>
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$raiz = dirname(__DIR__, 3);
define('VCOSTAEDU', true);
foreach (['Un1Mv' . 'C05tA'] as $g) { defined($g) || define($g, true); } // a guarda de antes da 1.45.0 (D-53)
$_SERVER += ['SERVER_NAME' => 'localhost', 'HTTP_HOST' => 'localhost', 'REQUEST_URI' => '/', 'SCRIPT_NAME' => '/painel/index.php'];
chdir($raiz . '/painel');
require $raiz . '/painel/vendor/autoload.php';
(new class extends \Core\Config {
    public function __construct()
    {
        $this->configAdm();
    }
});
require_once $raiz . '/core/Requisicao.php';
\Core\Requisicao::definir();
$_SESSION = ['colUsuario_id' => 1, 'colUsuario_nivel' => 1, 'colUsuario_ordem' => 1];
if (!preg_match('/_teste$/', (string) DBNAME)) {
    fwrite(STDERR, "só no banco de prova\n");
    exit(2);
}
$c = \App\adms\Models\notas\AdmsCalculoNotas::alunoNaOferta((int) ($argv[1] ?? 0), (string) ($argv[2] ?? ''));
echo json_encode($c ? [
    'etapas' => array_values(array_map(fn($e) => ['ordem' => (int) ($e['ordem'] ?? 0), 'nota' => $e['nota'] ?? null, 'depois' => !empty($e['depoisDaSaida'])], (array) ($c['etapas'] ?? []))),
    'media' => $c['media'] ?? null,
] : null, JSON_UNESCAPED_UNICODE), "\n";
