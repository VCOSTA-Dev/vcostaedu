<?php
/* VCOSTAEDU (Etapa 2): sem a configuração, o sistema ainda não foi instalado — vai para o instalador (D10 do
   vcostaedu: a checagem mora no arquivo mais simples e roda em toda requisição). */
if (!is_file(__DIR__ . "/painel/core/Config.php")) {
    header("Location: " . rtrim(str_replace("\\", "/", dirname((string) ($_SERVER["SCRIPT_NAME"] ?? "/index.php"))), "/") . "/instalar/", true, 302);
    exit;
}
/* Página de entrada. O endereço (protocolo, host e caminho) vem de core/Requisicao.php, a mesma lógica do
   painel e do portal (1.9.1). Os parâmetros PROXIES_CONFIAVEIS, HOSTS_PERMITIDOS e CAMINHO_BASE são lidos da
   Config do painel, que só declara constantes. Sem ela (instalação ainda por fazer), valem os padrões. */
require_once __DIR__ . "/core/Requisicao.php";
if (is_file(__DIR__ . "/painel/core/Config.php")) {
    define('VCOSTAEDU', true);
    foreach (['Un1Mv' . 'C05tA'] as $g) { defined($g) || define($g, true); } // a guarda de antes da 1.45.0, só para a Config.php já instalada (DECISIONS D-53)
    require_once __DIR__ . "/painel/core/Config.php";
    (new class extends \Core\Config {
        public function __construct()
        {
            $this->configAdm();
        }
    });
}
\Core\Requisicao::definir();
defined("VERSAODEV") || define("VERSAODEV", "1.9.1");
/* E6b: endereço curto da verificação pública do histórico — <site>/verificar[/<código>]. */
$rota = trim((string) ($_GET["url"] ?? ""), "/");
if (preg_match("#^verificar(?:/([A-Za-z0-9-]{1,20}))?$#", $rota, $m)) {
    header("Location: " . URLADM . "verificar/codigo" . (!empty($m[1]) ? "/" . strtoupper($m[1]) : ""), true, 302);
    exit;
}
?>
<?php
/* VCOSTAEDU (Etapa 3): a escolha entre o portal do aluno e o painel, em Tabler, sem nada por CDN (D-14). */
require_once __DIR__ . '/core/asset.php';
$raizAsset = static fn(string $r): string => htmlspecialchars(assetUrl($r, URL), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VCOSTAEDU</title>
  <link rel="icon" type="image/png" href="<?= $raizAsset('assets/vcostaedu/favicon.png') ?>">
  <?php require_once __DIR__ . '/core/Tema.php'; echo VcTema::script(null); /* Onda 3, Etapa 10: o tema (claro, escuro, automático) antes do primeiro desenho — core/Tema.php */ ?>
  <link rel="stylesheet" href="<?= $raizAsset('assets/plugins/tabler/css/tabler.min.css') ?>">
  <link rel="stylesheet" href="<?= $raizAsset('assets/plugins/fontawesome-free/css/all.min.css') ?>">
  <link rel="stylesheet" href="<?= $raizAsset('assets/css/vcostable-tabler.css') ?>">
  <link rel="stylesheet" href="<?= $raizAsset('assets/css/vcostaedu-tabler.css') ?>">
</head>
<body class="vc-tabler d-flex flex-column">
<div class="page page-center">
  <div class="container container-tight py-4">
    <div class="text-center mb-4">
      <img src="<?= $raizAsset('assets/marca/vcostaedu-wordmark.svg') ?>" alt="VCOSTAEDU" style="height:48px;width:auto">
    </div>
    <div class="row row-cards">
      <div class="col-sm-6">
        <a href="<?= URLACAD ?>" class="card card-link card-link-pop text-center text-decoration-none" id="entradaPortal">
          <div class="card-body py-4">
            <i class="fas fa-user-graduate fa-3x text-primary mb-3" aria-hidden="true"></i>
            <h3 class="card-title mb-1">Alunos</h3>
            <p class="text-secondary mb-0">Portal do aluno: boletim, notas, horário e documentos.</p>
          </div>
        </a>
      </div>
      <div class="col-sm-6">
        <a href="<?= URLADM ?>" class="card card-link card-link-pop text-center text-decoration-none" id="entradaPainel">
          <div class="card-body py-4">
            <i class="fas fa-chalkboard-teacher fa-3x text-primary mb-3" aria-hidden="true"></i>
            <h3 class="card-title mb-1">Professores e equipe</h3>
            <p class="text-secondary mb-0">Painel: disciplinas, notas, frequência e secretaria.</p>
          </div>
        </a>
      </div>
    </div>
    <div class="text-center text-secondary mt-4 small">
      <?php /* D-40, D-99: a página inicial é pública: a versão só aparece para quem entrou (antes saía a constante da configuração, fixa) */ ?>
      VCOSTAEDU — código aberto sob a AGPL-3.0
    </div>
  </div>
</div>
</body>
</html>
