<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* A recusa: sem dizer se o aluno existe. */
$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$d = $this->dados;
?>
<div class="vc-pagina vc-familia">
  <?php ob_start(); ?>Acesso não permitido<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <div class="card" id="acessoRecusado"><div class="card-body">
        <p class="mb-2">Esta página não está disponível para você.</p>
        <a href="<?= URLACAD ?>familia/index" class="btn btn-primary">Voltar para os meus filhos</a>
      </div></div>
    </div>
  </section>
</div>
