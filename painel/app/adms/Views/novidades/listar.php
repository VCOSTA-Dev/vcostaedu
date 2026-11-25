<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* E6c — NOVIDADES (painel e portal): por versão, da mais nova para a mais antiga, só as do perfil, cada uma com o guia. */
$h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$r = $this->dados['rotas'];
$guias = $this->dados['guias'];
?>
<div class="vc-pagina">
  <?php ob_start(); ?><i class="fas fa-bullhorn"></i> Novidades<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (!$this->dados['versoes']) : ?>
        <div class="alert alert-light border">Não há novidades para o seu perfil.</div>
      <?php endif; ?>
      <?php foreach ($this->dados['versoes'] as $v) : ?>
        <div class="card shadow versao-novidades" data-versao="<?= $h($v['numero']) ?>">
          <div class="card-header"><h3 class="card-title"><b>Versão <?= $h($v['numero']) ?></b> <small class="text-muted">— <?= htmlspecialchars((string) ($v['data'] ? date('d/m/Y', strtotime($v['data'])) : ''), ENT_QUOTES, 'UTF-8') ?></small><?= $v['atual'] ? ' <span class="badge bg-success-lt">atual</span>' : '' ?></h3></div>
          <div class="card-body py-2">
            <?php foreach ($v['itens'] as $n) : ?>
              <div class="mb-2 novidade"><b><?= $h($n['titulo']) ?></b><br><?= $h($n['texto']) ?>
                <?php if ($n['guia'] && isset($guias[$n['guia']])) : ?> <a href="<?= $h($r['guia'] . $n['guia']) ?>" class="link-guia">Ver o guia: <?= $h($guias[$n['guia']]['titulo']) ?> ›</a><?php endif; ?></div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
</div>
