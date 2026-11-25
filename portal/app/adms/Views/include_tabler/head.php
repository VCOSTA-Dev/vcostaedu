<?php
if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}
/*
 * O CABEÇALHO DO LAYOUT TABLER DO PORTAL DO ALUNO (VCOSTAEDU, Etapa 6; o do painel).
 *
 * Abre o documento, desenha o rail (rail.php) e abre o .page-wrapper; quem fecha é o libraries_js.php. A view fica
 * só com o _page_header e o .page-body — o mesmo desenho do layout do vcostaedu (head, rail, view, rodapé).
 *
 * Nada por CDN (D-14): Tabler 1.4.0, Font Awesome 6.5.2 completo (D-06), DataTables com a integração Bootstrap 5
 * e Select2 vêm de assets/plugins/. A fonte é a pilha de sistema que o vcostable-tabler.css declara.
 *
 * Os endereços levam a marca de alteração do arquivo (core/asset.php): sem ela, uma folha de estilo nova não chega
 * ao navegador de quem já usava o sistema.
 */
require_once dirname(__DIR__, 5) . '/core/asset.php';
$tblAsset = static fn(string $relativo): string => htmlspecialchars(assetUrl($relativo, URL), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VCOSTAEDU — <?= !empty($_SESSION['vc_responsavel_id']) ? 'Portal do responsável' : 'Portal do aluno' /* Onda 8, correção, segunda leva, 2.5 (D-91): o responsável não está no portal do aluno */ ?></title>
  <link rel="icon" type="image/png" href="<?= htmlspecialchars(\App\adms\Models\helper\AdmsInstituicao::iconeAbaUrl(), ENT_QUOTES, 'UTF-8') ?>"><?php /* o ícone da aba: o do VCOSTAEDU ou o escolhido pela instituição */ ?>
  <?php /* Aplica o tema salvo (claro/escuro) antes do primeiro desenho da tela: evita o pisca. */ ?>
  <?= \App\adms\Models\helper\AdmsTemaPortal::script() ?><?php /* Onda 3, Etapa 10: o tema (claro, escuro, automático) antes do primeiro desenho — core/Tema.php */ ?>
  <link rel="stylesheet" href="<?= $tblAsset('assets/plugins/tabler/css/tabler.min.css') ?>">
  <link rel="stylesheet" href="<?= $tblAsset('assets/plugins/fontawesome-free/css/all.min.css') ?>">
  <link rel="stylesheet" href="<?= $tblAsset('assets/plugins/select2/css/select2.min.css') ?>">
  <link rel="stylesheet" href="<?= $tblAsset('assets/plugins/datatables-bs5/css/dataTables.bootstrap5.min.css') ?>">
  <link rel="stylesheet" href="<?= $tblAsset('assets/plugins/datatables-buttons/css/buttons.bootstrap5.min.css') ?>">
  <?php if (!empty($this->dados['carregarEditor'])): /* o editor padrão (R10): Summernote 0.8.20 no build Bootstrap 5 */ ?>
  <link rel="stylesheet" href="<?= $tblAsset('assets/plugins/summernote/summernote-bs5.min.css') ?>">
  <?php endif; ?>
  <?php /* A identidade (copiada do vcostaedu, intocada), os componentes comuns do sistema e, por último, os ajustes
           desta casa sobre o Tabler e os plugins. */ ?>
  <link rel="stylesheet" href="<?= $tblAsset('assets/css/vcostable-tabler.css') ?>">
  <link rel="stylesheet" href="<?= $tblAsset('assets/vcostaedu/vcostaedu-ui.css') ?>">
  <link rel="stylesheet" href="<?= $tblAsset('assets/css/vcostaedu-tabler.css') ?>">
  <link rel="stylesheet" href="<?= $tblAsset('assets/css/vcostaedu-componentes.css') ?>">
</head>
<body class="vc-tabler">
<?php include 'app/adms/Views/include_tabler/rail.php'; ?>
