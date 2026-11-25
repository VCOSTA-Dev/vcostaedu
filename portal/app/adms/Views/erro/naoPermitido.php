<?php
if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}
/* Correção da escola, Parte 4 (B18; D-103): a página existe, mas não é do perfil de quem entrou. */
?>
<div class="vc-pagina" id="naoPermitido">
  <section class="page-body">
    <div class="container-xl">
      <div class="empty">
        <div class="empty-icon"><i class="fas fa-lock fa-3x text-secondary" aria-hidden="true"></i></div>
        <h1 class="empty-title">Não permitido</h1>
        <p class="empty-subtitle text-secondary">Esta página não é do seu perfil de acesso. Se precisar dela, fale com a secretaria.</p>
        <div class="empty-action">
          <a href="<?= URLACAD ?>home/index" class="btn btn-primary">Página Inicial</a>
        </div>
      </div>
    </div>
  </section>
</div>
