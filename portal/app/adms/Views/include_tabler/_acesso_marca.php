<?php
if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}
/* A marca no alto das telas de acesso: VCOSTAEDU (assets/marca/, fora da AGPL — TRADEMARK.md) e, quando a
   instituição enviou o logo pela tela, o logo dela logo abaixo. */
$acessoLogo = \App\adms\Models\helper\AdmsInstituicao::logoUrl();
?>
<div class="text-center mb-4">
  <img src="<?= URL ?>assets/marca/vcostaedu-wordmark.svg" alt="VCOSTAEDU" style="height:44px;width:auto">
  <?php if (!str_ends_with($acessoLogo, 'assets/marca/vcosta.png')): ?>
  <div class="mt-3"><img src="<?= htmlspecialchars($acessoLogo, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars(\App\adms\Models\helper\AdmsInstituicao::nome(), ENT_QUOTES, 'UTF-8') ?>" style="max-height:64px;max-width:220px"></div>
  <?php endif; ?>
</div>
