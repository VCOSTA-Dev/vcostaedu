<?php

/**
 * A PÁGINA "SOBRE": o nome, os avisos legais, a licença inteira (o LICENSE) e a marca (o TRADEMARK.md).
 *
 * Avulsa e pública, como a verificação de documento: abre do instalador (antes de existir configuração ou banco), das
 * telas de acesso e de dentro do painel e do portal. Não lê banco.
 *
 * Onda 3, Etapa 6: a VERSÃO e as BIBLIOTECAS (com as versões delas) só aparecem para quem entrou no painel — a página
 * lê a sessão do painel, só para ler, se o navegador trouxer o cookie dela. Para quem não entrou, nenhuma versão (o que
 * ajudaria quem procura uma falha conhecida numa versão antiga).
 */

declare(strict_types=1);

ini_set('display_errors', '0');
require __DIR__ . '/core/asset.php';
require __DIR__ . '/core/Legal.php';
require __DIR__ . '/core/Vocabulario.php'; // Onda 4, Etapa 1: o nome do tipo de instituição
require __DIR__ . '/core/Markdown.php'; // a marca formatada a partir do TRADEMARK.md (Onda 3, Etapa 7)

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

$h = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
/* quem entrou no painel (a sessão do painel, só lida): vê a versão e as bibliotecas */
$entrou = false;
$tipoInst = '';
if (!empty($_COOKIE['VCOSTAEDU_PAINEL']) && preg_match('/^[A-Za-z0-9,-]{16,128}$/', (string) $_COOKIE['VCOSTAEDU_PAINEL'])) {
    session_name('VCOSTAEDU_PAINEL');
    if (@session_start(['read_and_close' => true])) {
        $entrou = !empty($_SESSION['colUsuario_id']);
        /* Onda 4, Etapa 1: o tipo de instituição que o painel guardou na sessão (o Sobre não lê o banco) */
        $tipoInst = $entrou && VcVocabulario::valido((string) ($_SESSION['vc_instituicao_tipo'] ?? '')) ? (string) $_SESSION['vc_instituicao_tipo'] : '';
    }
}
$versao = $entrou ? VcLegal::versaoDoPacote(__DIR__) : '';
$licenca = (string) @file_get_contents(__DIR__ . '/LICENSE');
$marca = (string) @file_get_contents(__DIR__ . '/TRADEMARK.md');
$bibliotecas = $entrou ? VcLegal::bibliotecas(__DIR__) : [];
$fonte = VcLegal::urlCodigoFonte();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sobre o <?= VcLegal::NOME ?></title>
  <link rel="icon" type="image/png" href="<?= $h(assetUrl('assets/vcostaedu/favicon.png', '')) ?>">
  <?php require_once __DIR__ . '/core/Tema.php'; echo VcTema::script(null); /* Onda 3, Etapa 10: o tema (claro, escuro, automático) antes do primeiro desenho — core/Tema.php */ ?>
  <link rel="stylesheet" href="<?= $h(assetUrl('assets/plugins/tabler/css/tabler.min.css', '')) ?>">
  <link rel="stylesheet" href="<?= $h(assetUrl('assets/css/vcostable-tabler.css', '')) ?>">
  <link rel="stylesheet" href="<?= $h(assetUrl('assets/css/vcostaedu-componentes.css', '')) ?>">
</head>
<body class="border-top-wide border-primary">
<div class="page">
  <div class="container container-narrow py-4">
    <div class="text-center mb-4">
      <img src="<?= $h(assetUrl('assets/marca/vcostaedu-wordmark.svg', '')) ?>" alt="<?= VcLegal::NOME ?>" style="height:44px;width:auto">
    </div>

    <div class="card mb-3" id="sobre">
      <div class="card-body">
        <h1 class="card-title h2">Sobre o <?= VcLegal::NOME ?></h1>
        <dl class="row mb-0">
          <?php if ($entrou): ?>
          <dt class="col-sm-4">Versão dos arquivos</dt><dd class="col-sm-8" id="versao"><?= $h($versao) ?></dd>
          <?php if ($tipoInst !== ''): ?><dt class="col-sm-4">Tipo de instituição</dt><dd class="col-sm-8" id="tipoInstituicao"><?= $h(VcVocabulario::nome($tipoInst)) ?></dd><?php endif; ?>
          <?php endif; ?>
          <dt class="col-sm-4">Direitos autorais</dt><dd class="col-sm-8"><?= $h(VcLegal::copyright()) ?></dd>
          <dt class="col-sm-4">Licença</dt><dd class="col-sm-8">GNU Affero General Public License, versão 3 (<?= VcLegal::LICENCA ?>)</dd>
          <?php if ($fonte !== ''): ?>
          <dt class="col-sm-4">Código-fonte</dt><dd class="col-sm-8"><a href="<?= $h($fonte) ?>" rel="noopener"><?= $h($fonte) ?></a></dd>
          <?php endif; ?>
        </dl>
        <p class="text-secondary mt-3 mb-0">
          Este programa é software livre: você pode redistribuí-lo e modificá-lo nos termos da licença <?= VcLegal::LICENCA ?>,
          publicada pela Free Software Foundation. Ele é distribuído <strong>sem nenhuma garantia</strong>, nem mesmo a
          garantia implícita de comercialização ou de adequação a um fim específico. Quem oferece este sistema a outras
          pessoas pela rede deve oferecer também o código-fonte correspondente. O texto completo está abaixo.
        </p>
        <p class="text-secondary mt-2 mb-0">
          O nome e a marca <?= VcLegal::NOME ?> não fazem parte da licença do código: veja <a href="#marca">a marca</a>, abaixo.
        </p>
      </div>
    </div>

    <?php if ($entrou): /* as bibliotecas e as versões delas: só para quem entrou no painel */ ?>
    <div class="card mb-3" id="creditos">
      <div class="card-header"><h2 class="card-title">Bibliotecas de terceiros</h2></div>
      <div class="table-responsive">
        <table class="table table-vcenter card-table">
          <thead><tr><th>Biblioteca</th><th>Versão</th><th>Licença</th></tr></thead>
          <tbody>
          <?php foreach ($bibliotecas as $p): ?>
            <tr><td><?= $h($p['nome']) ?></td><td><?= $h($p['versao']) ?></td><td><?= $h($p['licenca']) ?></td></tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="card-footer text-secondary small">Cada biblioteca mantém a própria licença, que acompanha os arquivos dela.</div>
    </div>
    <?php else: ?>
    <div class="card mb-3" id="creditos">
      <div class="card-body text-secondary">O sistema usa bibliotecas de terceiros, cada uma com a própria licença, que acompanha os arquivos dela. A lista, com as versões, aparece para quem entrou no sistema.</div>
    </div>
    <?php endif; ?>

    <div class="card mb-3" id="marca">
      <div class="card-header"><h2 class="card-title">A marca</h2></div>
      <div class="card-body">
        <div class="vc-marca-texto"><?= VcMarkdown::html($marca, 3) ?></div>
      </div>
    </div>

    <div class="card mb-3" id="licenca">
      <div class="card-header"><h2 class="card-title">Licença (texto integral, em inglês)</h2></div>
      <div class="card-body">
        <pre class="vc-licenca mb-0"><?= $h($licenca) ?></pre>
      </div>
    </div>

    <div class="text-center">
      <a href="javascript:history.back()" class="btn btn-outline-secondary" id="voltar">Voltar</a>
    </div>
    <?= VcLegal::rodape($versao, '', 'text-center mt-4') /* sem a versão para quem não entrou */ ?>
  </div>
</div>
</body>
</html>
