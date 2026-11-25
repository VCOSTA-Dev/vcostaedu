<?php
/* VCOSTAEDU (Etapa 2): sem a configuração, o sistema ainda não foi instalado — vai para o instalador. */
if (!is_file(__DIR__ . '/core/Config.php')) {
    header('Location: ' . rtrim(str_replace(chr(92), '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/x/index.php'), 2)), '/') . '/instalar/', true, 302);
    exit;
}
/* D-11: sessão própria da VCOSTAEDU, separada entre painel e portal (VCOSTAEDU_PAINEL).
   E7: a Config vem antes da sessão — Core\Seguranca decide o cookie (Secure, SameSite)
   e os cabeçalhos (frame-ancestors, X-Frame-Options) pelo HTTPS. */
define('VCOSTAEDU', true);
foreach (['Un1Mv' . 'C05tA'] as $g) { defined($g) || define($g, true); } // a guarda de antes da 1.45.0, só para a Config.php já instalada (DECISIONS D-53)
ob_start();
require './vendor/autoload.php';
use Core\ConfigController as Home;
$url = new Home();
/* Onda 8 (D-70): no fim de toda requisição, depois do envio dela mesma ao Moodle (registrado durante a requisição), o
   que sobrou na fila anda sozinho, num orçamento curto (AdmsMoodle::retomar) */
register_shutdown_function(static function (): void {
    register_shutdown_function([\App\adms\Models\moodle\AdmsMoodle::class, 'retomar']);
});
\Core\Seguranca::iniciar('painel');
$url->carregar();
