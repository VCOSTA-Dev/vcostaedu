<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* Os filhos: escolha de quem ver os dados. */
$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$d = $this->dados;
?>
<div class="vc-pagina vc-familia">
  <?php ob_start(); ?>Meus filhos<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <p class="text-secondary">Escolha o filho. Todas as telas a seguir são dele; o seletor no alto troca de filho.</p>
      <?php if (!$d['filhos']) : ?><div class="alert alert-info" id="semFilhos">Ainda não há nenhum aluno liberado para você. Procure a secretaria da escola.</div><?php endif; ?>
      <div class="row row-cards" id="listaFilhos">
        <?php foreach ($d['filhos'] as $f) : ?>
          <div class="col-sm-6 col-lg-4"><a class="card card-link" href="<?= URLACAD ?>familia/filho/<?= $h((int) $f['id']) ?>" data-aluno="<?= $h((int) $f['id']) ?>"><div class="card-body"><div class="h3 mb-0"><?= $h($f['nome_social'] ?: $f['nome']) ?></div></div></a></div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
</div>
