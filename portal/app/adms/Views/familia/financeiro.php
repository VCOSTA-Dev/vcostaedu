<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* A ficha financeira do filho, só de leitura (Onda 8, D-98): só para o responsável financeiro dele naquele ano. */
$d = $this->dados;
?>
<div class="vc-pagina vc-familia">
  <?php ob_start(); ?>Ficha financeira<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <?php include __DIR__ . '/_cabecalho.php'; ?>
      <?php $fichaF = $d['fichaFinanceira']; $filtroF = $d['filtroFinanceiro']; $urlFiltroF = fn($k) => $urlF('financeiro') . '&filtro=' . $k; $urlReciboF = fn($r, $ano) => in_array((int) $ano, $d['anosRecibo'] ?? [], true) ? $urlF('recibo') . '&recibo=' . (int) $r : ''; /* familia/recibo/{aluno}?ano=…&recibo=… (a rota começa pela barreira) */ /* D-99: só o recibo de um ano em que a pessoa é a financeira */
      include 'app/adms/Views/include_tabler/_ficha_financeira.php'; ?>
    </div>
  </section>
</div>
