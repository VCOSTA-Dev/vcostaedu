<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* As notas das atividades do filho no ano. */
$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$d = $this->dados;
?>
<div class="vc-pagina vc-familia">
  <?php ob_start(); ?>Notas das atividades<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <?php include __DIR__ . '/_cabecalho.php'; ?>
      <?php if (!$d['notas']) : ?><div class="alert alert-info" id="semNotas">Ainda não há nota de atividade neste ano.</div><?php endif; ?>
      <?php $porDisc = []; foreach ($d['notas'] as $n) { $porDisc[$n['materia'] ?? 'Disciplina'][] = $n; } ?>
      <?php foreach ($porDisc as $mat => $ns) : ?>
        <div class="card mb-2 vc-notas-componente"><div class="card-header"><h3 class="card-title"><?= $h($mat) ?></h3></div>
          <div class="table-responsive"><table class="table table-sm card-table"><tbody>
          <?php foreach ($ns as $n) : ?><tr><td><?= $h($n['nomeAtividade'] ?? '') ?></td><td class="text-end"><?= $n['nota'] === null || $n['nota'] === '' ? '—' : $h(str_replace('.', ',', (string) $n['nota'])) ?></td></tr><?php endforeach; ?>
          </tbody></table></div></div>
      <?php endforeach; ?>
    </div>
  </section>
</div>
