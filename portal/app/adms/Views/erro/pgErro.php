<?php

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}
/* VCOSTAEDU (Etapa 6): no layout Tabler, o mesmo aviso dentro do .page-body (o empty do Tabler). */
?>
<div class="vc-pagina">
  <section class="page-body">
    <div class="container-xl">
      <div class="empty">
        <div class="empty-icon"><i class="fas fa-circle-question fa-3x text-secondary" aria-hidden="true"></i></div>
        <h1 class="empty-title">Página inexistente!</h1>
        <div class="empty-action">
          <a href="<?= URLACAD ?>home/index" class="btn btn-primary">Página Inicial</a>
        </div>
      </div>
    </div>
  </section>
</div>
