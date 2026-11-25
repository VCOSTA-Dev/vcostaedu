<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* As faltas e a frequência do filho no ano. */
$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$d = $this->dados;
?>
<div class="vc-pagina vc-familia">
  <?php ob_start(); ?>Frequência<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <?php include __DIR__ . '/_cabecalho.php'; ?>
      <?php if (!$d['frequencia']) : ?><div class="alert alert-info" id="semFrequencia">Ainda não há frequência neste ano.</div><?php else : ?>
      <div class="card"><div class="table-responsive"><table class="table table-vcenter card-table" id="tabelaFrequencia"><thead><tr><th>Disciplina</th><th class="text-end">Faltas</th><th class="text-end">Frequência</th></tr></thead><tbody>
        <?php foreach ($d['frequencia'] as $x) : ?><tr><td><?= $h($x['materia']) ?></td><td class="text-end"><?= $h($x['faltas']) ?></td><td class="text-end"><?= $x['frequencia'] === null ? '—' : $h(str_replace('.', ',', (string) $x['frequencia'])) . '%' ?></td></tr><?php endforeach; ?>
      </tbody></table></div></div>
      <?php endif; ?>
    </div>
  </section>
</div>
