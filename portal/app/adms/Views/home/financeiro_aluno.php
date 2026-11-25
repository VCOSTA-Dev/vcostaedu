<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* A ficha financeira do aluno, só de leitura (Onda 8, financeiro, Parte 3; D-98): os mesmos filtros da secretaria; a
   descrição, o vencimento, o valor e a situação; sem ações. */
$d = $this->dados;
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Ficha financeira<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <?php $fichaF = $d['fichaFinanceira']; $filtroF = $d['filtroFinanceiro']; $urlFiltroF = fn($k) => URLACAD . 'home/financeiro-aluno?filtro=' . $k; $urlReciboF = fn($r, $ano = 0) => URLACAD . 'home/recibo-aluno/' . (int) $r;
      include 'app/adms/Views/include_tabler/_ficha_financeira.php'; ?>
    </div>
  </section>
</div>
