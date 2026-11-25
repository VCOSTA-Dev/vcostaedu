<?php
if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}
/* O layout das telas de ACESSO do painel em Tabler (login, esqueci a senha, definir e atualizar a senha): a página
   centrada do Tabler (page-center + container-tight), como o sign-in do projeto de referência (Etapa 4d de lá). Nada por CDN. */
require_once dirname(__DIR__, 5) . '/core/asset.php';
$tblAsset = static fn(string $relativo): string => htmlspecialchars(assetUrl($relativo, URL), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VCOSTAEDU — Painel</title>
  <link rel="icon" type="image/png" href="<?= htmlspecialchars(\App\adms\Models\helper\AdmsInstituicao::iconeAbaUrl(), ENT_QUOTES, 'UTF-8') ?>"><?php /* o ícone da aba: o do VCOSTAEDU ou o escolhido pela instituição */ ?>
  <?php require_once dirname(__DIR__, 5) . '/core/Tema.php'; echo \VcTema::script(null); /* Onda 3, Etapa 10: o tema (claro, escuro, automático) antes do primeiro desenho — core/Tema.php */ ?>
  <link rel="stylesheet" href="<?= $tblAsset('assets/plugins/tabler/css/tabler.min.css') ?>">
  <link rel="stylesheet" href="<?= $tblAsset('assets/plugins/fontawesome-free/css/all.min.css') ?>">
  <link rel="stylesheet" href="<?= $tblAsset('assets/css/vcostable-tabler.css') ?>">
  <link rel="stylesheet" href="<?= $tblAsset('assets/vcostaedu/vcostaedu-ui.css') ?>">
  <link rel="stylesheet" href="<?= $tblAsset('assets/css/vcostaedu-tabler.css') ?>">
  <link rel="stylesheet" href="<?= $tblAsset('assets/css/vcostaedu-componentes.css') ?>">
</head>
<body class="vc-tabler d-flex flex-column">
<div class="page page-center">
