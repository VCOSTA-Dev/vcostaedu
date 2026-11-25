<?php
/*
 * ONDA 4, ETAPA 5 — o cálculo REAL de um aluno numa oferta, pela fachada de sempre (AdmsCalculoNotas::alunoNaOferta),
 * no banco de prova: a prova do acréscimo (etapa5.js) compara com o simulador da regra.
 *   php tests/tabler/onda4/media_real.php <oferta> <rm>   → {"media": "6.0", "memoria": [...]}
 */
if (PHP_SAPI !== 'cli' || $argc < 3) {
    fwrite(STDERR, "uso: php tests/tabler/onda4/media_real.php <oferta> <rm>\n");
    exit(2);
}
$raiz = dirname(__DIR__, 3);
define('VCOSTAEDU', true);
foreach (['Un1Mv' . 'C05tA'] as $g) { defined($g) || define($g, true); } // a guarda de antes da 1.45.0, só para a Config.php já instalada (DECISIONS D-53)
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
$r = \App\adms\Models\notas\AdmsCalculoNotas::alunoNaOferta((int) $argv[1], (string) $argv[2]);
echo json_encode(['media' => $r['media'] ?? null, 'memoria' => $r['memoria']['linhas'] ?? null], JSON_UNESCAPED_UNICODE), "\n";
