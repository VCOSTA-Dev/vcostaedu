<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* O boletim do filho no ano (a mesma regra de liberação do aluno). */
$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$d = $this->dados;
?>
<div class="vc-pagina vc-familia">
  <?php ob_start(); ?>Boletim<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <?php include __DIR__ . '/_cabecalho.php'; ?>
      <?php $B = \App\adms\Models\notas\AdmsBoletimCurso::class; $b = $d['boletimCurso']; $rg = $b['regras'] ?? ['mediaMinima' => '7.00', 'frequenciaMinima' => '75.00']; $mostraParcial = \App\adms\Models\helper\AdmsParametro::mostrarMediaParcial(); ?>
      <?php if (empty($b['blocos'])) : ?><div class="alert alert-info" id="semBoletim">Ainda não há boletim neste ano.</div><?php endif; ?>
      <?php foreach ($b['blocos'] ?? [] as $bl) : ?>
        <div class="boletimCurso mb-3"><?= $B::faixaAluno($b['aluno'], $bl['matricula']) ?>
          <div class="card shadow-sm mb-0"><div class="card-body table-responsive p-0"><?= $B::tabela($bl, $rg, ['ocultarParcial' => !$mostraParcial]) ?></div></div>
          <?= $B::comoFoiCalculado($bl, !$mostraParcial) ?></div>
      <?php endforeach; ?>
      <?= $B::regrasTexto($rg, $mostraParcial, $b['blocos'] ?? []) ?>
    </div>
  </section>
</div>
