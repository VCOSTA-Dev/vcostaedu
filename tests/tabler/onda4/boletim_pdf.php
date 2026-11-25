<?php
/*
 * ONDA 4, ETAPA 3 — o HTML da tabela do boletim que vai para o PDF (AdmsGerarBoletimPdf: AdmsBoletimCurso::doAluno e
 * tabela(…, 'pdf' => true) — exatamente o que o Dompdf recebe), para a prova conferir que o PDF mostra o mesmo número da
 * tela e do portal. Só linha de comando, no banco de teste.
 *   php tests/tabler/onda4/boletim_pdf.php <RA>
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
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
if (!preg_match('/_teste2?$/', DBNAME)) {
    fwrite(STDERR, "só no banco de teste\n");
    exit(2);
}
require_once $raiz . '/core/Requisicao.php';
\Core\Requisicao::definir();
$_SESSION = ['colUsuario_id' => 1, 'colUsuario_nivel' => 1, 'colUsuario_ordem' => 1];
$B = \App\adms\Models\notas\AdmsBoletimCurso::class;
$b = $B::doAluno((string) ($argv[1] ?? ''));
$html = '';
foreach ($b['blocos'] ?? [] as $bl) {
    $html .= $B::tabela($bl, $b['regras'], ['ano' => true, 'pdf' => true]);
}
echo $html, "\n";
