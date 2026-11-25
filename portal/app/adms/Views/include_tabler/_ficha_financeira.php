<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Pagina nao encontrada!");
}
/*
 * A FICHA FINANCEIRA, SÓ DE LEITURA (Onda 8, D-98), no portal do aluno e no do responsável: os filtros com as contagens
 * e os títulos (a descrição, o vencimento, o valor, a situação). Sem ações. Recebe $fichaF (AdmsFichaFinanceira::ficha),
 * $filtroF e $urlFiltroF (o endereço da tela com ?filtro=).
 */
$M = \App\adms\Models\financeiro\AdmsFichaFinanceira::class;
$hf = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$coresF = ['a_vencer' => 'bg-blue-lt', 'vencido' => 'bg-red-lt', 'recebido' => 'bg-green-lt', 'baixado' => 'bg-teal-lt', 'cancelado' => 'bg-secondary-lt', 'renegociado' => 'bg-purple-lt'];
?>
<div id="fichaFinanceira">
  <div class="d-flex flex-wrap gap-2 mb-3" id="filtrosFicha">
    <?php foreach ($M::FILTROS as $k => $rot) : ?>
      <a class="btn btn-sm <?= $filtroF === $k ? 'btn-primary' : 'btn-outline-primary' ?>" href="<?= $hf($urlFiltroF($k)) ?>" data-filtro="<?= $hf($k) ?>"><?= $hf($rot) ?> <span class="badge bg-white text-dark ms-1 vc-contagem"><?= $hf($fichaF['contagem'][$k] ?? 0) ?></span></a>
    <?php endforeach; ?>
  </div>
  <?php if (!$fichaF['titulos']) : ?>
    <div class="alert alert-info" id="semTitulos">Nenhum título neste filtro.</div>
  <?php else : ?>
    <div class="row row-cards">
      <?php foreach ($fichaF['titulos'] as $t) : ?>
        <div class="col-12 col-md-6 col-xl-4 vc-titulo-portal" data-situacao="<?= $hf($t['mostrada']) ?>">
          <div class="card"><div class="card-body py-2">
            <div class="d-flex justify-content-between align-items-start gap-2">
              <div><strong><?= $hf($t['descricao']) ?></strong><div class="text-secondary small">vence em <?= $hf(date('d/m/Y', strtotime((string) $t['vencimento']))) ?></div><?= !empty($t['acordo_numero']) ? '<div class="text-secondary small vc-acordo">renegociado no acordo nº ' . $hf($t['acordo_numero']) . '</div>' : '' ?></div>
              <span class="badge <?= $hf($coresF[$t['mostrada']] ?? 'bg-secondary-lt') ?>"><?= $hf($M::ROTULOS[$t['mostrada']] ?? $t['mostrada']) ?></span>
            </div>
            <div class="d-flex justify-content-between align-items-end mt-1">
              <div class="h3 mb-0">R$ <?= $hf($M::brl((int) $t['valor_centavos'])) ?></div>
              <?php $urlRec = !empty($t['recibo_id']) && isset($urlReciboF) ? $urlReciboF($t['recibo_id'], (int) $t['recibo_ano']) : ''; ?>
              <?php if ($urlRec !== '') : /* D-99: o recibo para baixar */ ?>
                <a class="btn btn-sm btn-outline-secondary vc-recibo-portal" target="_blank" href="<?= $hf($urlRec) ?>"><i class="fas fa-receipt me-1"></i> Recibo</a>
              <?php endif; ?>
            </div>
          </div></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <p class="text-secondary small mt-3 mb-0">Só para consulta. Para pagar ou tirar uma dúvida, fale com a secretaria da escola.</p>
</div>
