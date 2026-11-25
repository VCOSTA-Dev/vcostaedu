<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* O atestado de matrícula do filho. */
$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$d = $this->dados;
?>
<div class="vc-pagina vc-familia">
  <?php ob_start(); ?>Atestado de matrícula<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <?php include __DIR__ . '/_cabecalho.php'; ?>
      <?php if (!$d['matriculas']) : ?><div class="alert alert-info" id="semMatricula">Não há matrícula em curso para emitir o atestado.</div><?php else : ?>
      <form method="post" action="<?= $h($urlF('atestado')) ?>" class="card mb-3" id="formAtestado"><div class="card-body d-flex flex-wrap gap-2 align-items-end">
        <div><label class="form-label" for="matricula">Matrícula</label><select name="matricula" id="matricula" class="form-select"><?php foreach ($d['matriculas'] as $m) : ?><option value="<?= $h((int) $m['id']) ?>"><?= $h($m['curso'] ?? '') ?></option><?php endforeach; ?></select></div>
        <button type="submit" class="btn btn-primary" id="emitirAtestado">Emitir o atestado</button></div></form>
      <?php endif; ?>
      <?php if ($d['emissoes']) : ?><div class="card"><div class="list-group list-group-flush" id="atestadosEmitidos">
        <?php foreach ($d['emissoes'] as $e) : ?><a class="list-group-item" target="_blank" rel="noopener" href="<?= URLACAD ?>familia/atestado-pdf/<?= $h((int) $d['filho']['id']) ?>?ano=<?= $h((int) $d['ano']) ?>&amp;emissao=<?= $h((int) $e['id']) ?>"><i class="fas fa-file-pdf text-danger"></i> <?= $h($e['codigo'] ?? ('Atestado ' . (int) $e['id'])) ?> <span class="text-secondary small"><?= $h(isset($e['emitido_em']) ? date('d/m/Y', strtotime((string) $e['emitido_em'])) : '') ?></span></a><?php endforeach; ?>
      </div></div><?php endif; ?>
    </div>
  </section>
</div>
