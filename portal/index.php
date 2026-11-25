<?php
/* VCOSTAEDU (Etapa 2): sem a configuração, o sistema ainda não foi instalado — vai para o instalador. */
if (!is_file(__DIR__ . '/core/Config.php')) {
    header('Location: ' . rtrim(str_replace(chr(92), '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/x/index.php'), 2)), '/') . '/instalar/', true, 302);
    exit;
}
/* D-11: sessão própria da VCOSTAEDU, separada entre painel e portal (VCOSTAEDU_PORTAL).
   E7: a Config vem antes da sessão — Core\Seguranca decide o cookie (Secure, SameSite, Partitioned)
   e os cabeçalhos (frame-ancestors, X-Frame-Options) pelo HTTPS e pelas origens do Moodle. */
define('VCOSTAEDU', true);
define('VCOSTAEDU_APP_PORTAL', true); // o portal (antes, a pergunta era o nome da guarda dele)
foreach (['ColMv' . 'C05tA'] as $g) { defined($g) || define($g, true); } // a guarda de antes da 1.45.0, só para a Config.php já instalada (DECISIONS D-53)
ob_start();
require './vendor/autoload.php';
use Core\ConfigController as Home;
$url = new Home();
\Core\Seguranca::iniciar('portal');
$url->carregar();
