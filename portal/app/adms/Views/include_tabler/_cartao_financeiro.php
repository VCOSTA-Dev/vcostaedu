<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Pagina nao encontrada!");
}
/* O cartão "Ficha financeira" (Onda 8, D-98): "Em dia" ou "N títulos vencidos", e o botão "Consultar". Recebe
   $vencidosF (int) e $urlConsultarF. */
$hc = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
?>
<div class="card mb-3 vc-cartao-financeiro" data-vencidos="<?= $hc((int) $vencidosF) ?>">
  <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-2">
    <div>
      <div class="text-secondary small"><i class="fas fa-file-invoice-dollar me-1"></i> Ficha financeira</div>
      <div class="h3 mb-0 <?= (int) $vencidosF ? 'text-danger' : 'text-success' ?> vc-situacao-financeira"><?= (int) $vencidosF ? $hc((int) $vencidosF) . ' título' . ((int) $vencidosF > 1 ? 's' : '') . ' vencido' . ((int) $vencidosF > 1 ? 's' : '') : 'Em dia' ?></div>
    </div>
    <a href="<?= $hc($urlConsultarF) ?>" class="btn btn-outline-primary vc-consultar-financeiro">Consultar</a>
  </div>
</div>
