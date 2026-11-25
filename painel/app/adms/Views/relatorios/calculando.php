<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* Onda 6, Etapa 4: um relatório com muitas ofertas calcula as notas em passos, com o andamento na tela, e abre sozinho
   no fim (AdmsRelatorioCalculo). */
$d = $this->dados;
$h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$def = $d['def'];
?>
<div class="vc-pagina">
  <?php $paginaTitulo = $h($def::NOME); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <div class="card" id="cartaoCalculando" style="max-width: 44rem">
        <div class="card-body">
          <p class="mb-2">Este relatório usa as notas de <b><?= htmlspecialchars((string) ((int) $d['ofertas']), ENT_QUOTES, 'UTF-8') ?> ofertas</b>. Para não passar do tempo de uma página,
            elas são calculadas em partes, com o andamento na tela; no fim, o relatório abre sozinho.</p>
          <input type="hidden" id="execucaoCalculo" value="<?= $h($d['execucao']) ?>">
          <button type="button" class="btn btn-primary" id="calcularRelatorio" data-progresso="progressoRelatorio" data-fase="iniciar"><i class="fas fa-calculator"></i> Calcular e abrir o relatório</button>
          <a href="<?= $h(URLADM . 'relatorios/listar') ?>" class="btn btn-ghost-secondary">Voltar aos relatórios</a>
        </div>
      </div>
    </div>
  </section>
</div>
<?php
$progressoId = 'progressoRelatorio';
$progressoTitulo = 'Calculando as notas do relatório';
$progressoGrupos = ['As ofertas, uma a uma' => max(1, (int) $d['total'])];
$progressoUrl = $d['urlRelatorio'];
$progressoFim = ['Abrir o relatório', $d['urlFim']];
$progressoCampos = ['execucao' => '#execucaoCalculo'];
$progressoNavegar = true;
include 'app/adms/Views/include_tabler/_progresso.php';
?>
<script>
  /* começa sozinho: o botão fica para quem voltar à página */
  document.addEventListener('DOMContentLoaded', function () { document.getElementById('calcularRelatorio').click(); });
</script>
