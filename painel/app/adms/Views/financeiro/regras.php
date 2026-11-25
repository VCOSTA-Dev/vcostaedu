<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* As regras financeiras (Onda 8, D-98): a multa, os juros ao mês (por dia de atraso) e o desconto até o vencimento. */
use App\adms\Models\financeiro\AdmsFinanceiro as F;

$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$r = $this->dados['regras'];
$ex = F::encargos(100000, '2026-03-20', '2026-03-30', $r); // o exemplo: 1.000,00 com 10 dias de atraso
$exDesc = F::encargos(100000, '2026-03-20', '2026-03-18', $r);
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Regras financeiras<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <div class="row row-cards">
        <div class="col-lg-6">
          <form method="post" action="<?= URLADM ?>fin-regras/salvar" class="card" id="formRegrasFinanceiras">
            <div class="card-body">
              <div class="mb-3"><label class="form-label" for="multa">Multa por atraso (%)</label>
                <input type="text" inputmode="decimal" class="form-control" id="multa" name="multa" value="<?= $h(F::pct($r['multa_bp'])) ?>" required>
                <small class="form-hint">Cobrada uma vez, a partir do dia seguinte ao vencimento.</small></div>
              <div class="mb-3"><label class="form-label" for="juros">Juros ao mês (%)</label>
                <input type="text" inputmode="decimal" class="form-control" id="juros" name="juros" value="<?= $h(F::pct($r['juros_mes_bp'])) ?>" required>
                <small class="form-hint">Calculados por dia de atraso: o valor × os juros ÷ 30 × os dias.</small></div>
              <div class="mb-3"><label class="form-label" for="desconto">Desconto até o vencimento (%)</label>
                <input type="text" inputmode="decimal" class="form-control" id="desconto" name="desconto" value="<?= $h(F::pct($r['desconto_bp'])) ?>">
                <small class="form-hint">Opcional: 0 para não dar desconto. Vale para o pagamento feito até o dia do vencimento.</small></div>
            </div>
            <div class="card-footer"><button type="submit" class="btn btn-primary" id="salvarRegras">Salvar as regras</button></div>
          </form>
        </div>
        <div class="col-lg-6">
          <div class="card" id="exemploEncargos"><div class="card-body">
            <h3 class="card-title">Como fica, com as regras de agora</h3>
            <p class="mb-1">Um título de <strong>R$ 1.000,00</strong> que vence em 20/03:</p>
            <ul class="mb-0">
              <li>pago em 18/03: <strong>R$ <?= $h(F::brl($exDesc['total'])) ?></strong><?= $exDesc['desconto'] ? ' (desconto de R$ ' . $h(F::brl($exDesc['desconto'])) . ')' : ' (sem multa e sem juros)' ?>;</li>
              <li>pago em 30/03 (10 dias depois): <strong>R$ <?= $h(F::brl($ex['total'])) ?></strong> (multa de R$ <?= $h(F::brl($ex['multa'])) ?> e juros de R$ <?= $h(F::brl($ex['juros'])) ?>).</li>
            </ul>
          </div></div>
          <div class="card mt-3" id="avisoImpressos"><div class="card-body">
            <h3 class="card-title">Os impressos da renegociação</h3>
            <p class="mb-0">A proposta de acordo, o acordo efetivado e o termo de confissão de dívida saem com uma redação padrão, neutra: são modelos. Peça ao jurídico da instituição para revisá-los antes de usá-los com as famílias.</p>
          </div></div>
        </div>
      </div>
    </div>
  </section>
</div>
