<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* DESFAZER RECEBIMENTO (Onda 8, financeiro refeito, Parte 1; D-99): o recibo inteiro, como no de origem — todos os títulos
   dele de uma vez. Pede a data, o motivo (da lista de Configurações) e a justificativa, obrigatórios. Nada é apagado: o
   recibo, as linhas e as formas ficam estornados; os títulos voltam a "em aberto"; o estorno conta no caixa do dia dele. */
use App\adms\Models\financeiro\AdmsFinanceiro as F;
use App\adms\Models\financeiro\AdmsFinFicha as Ficha;
use App\adms\Models\financeiro\AdmsFinRecibos as R;

$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$r = $this->dados['recibo'];
$brl = fn($v) => 'R$ ' . F::brl((int) $v);
$dt = fn($x) => $x ? date('d/m/Y', strtotime((string) $x)) : '';
$valido = $r['situacao'] === 'valido';
?>
<div class="vc-pagina" id="desfazerRecebimento">
  <?php ob_start(); ?>Desfazer recebimento<?php $paginaTitulo = ob_get_clean();
  $paginaPretitulo = $h('Recibo nº ' . $r['numero'] . ' · ' . Ficha::rotuloNumero() . ' ' . $r['aluno']['rm'] . ' · ' . ($r['aluno']['nome_social'] ?: $r['aluno']['nome']));
  $paginaAcoesHtml = '<a href="' . URLADM . 'financeiro/ficha/' . (int) $r['aluno_id'] . '?ano=' . (int) $r['ano'] . '" class="btn btn-outline-secondary" id="voltarFicha"><i class="fas fa-arrow-left me-1"></i> Ficha financeira</a>'
    . '<a href="' . URLADM . 'financeiro/recibo/' . (int) $r['id'] . '" target="_blank" class="btn btn-outline-secondary"><i class="fas fa-receipt me-1"></i> O recibo</a>';
  include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <div class="card mb-3">
        <div class="card-header"><h3 class="card-title">O recibo nº <?= $h($r['numero']) ?> — <?= $h($brl($r['total_centavos'])) ?>, recebido em <?= $h($dt($r['data_recebimento'])) ?> por <?= $h($r['recebedor']) ?></h3></div>
        <div class="table-responsive">
          <table class="table table-sm table-vcenter card-table" id="titulosDoRecibo">
            <thead><tr><th>Título</th><th>Descrição</th><th>Venc.</th><th class="text-end">Recebido</th></tr></thead>
            <tbody><?php foreach ($r['linhas'] as $l) : ?><tr data-titulo="<?= $h($l['titulo_id']) ?>"><td><?= $h($l['titulo_id']) ?></td><td><?= $h($l['descricao']) ?></td><td><?= $h($dt($l['vencimento'])) ?></td><td class="text-end"><?= $h($brl($l['recebido_centavos'])) ?></td></tr><?php endforeach; ?></tbody>
          </table>
        </div>
        <div class="card-body small text-secondary">Forma: <?= $h(implode('; ', array_map(static fn($f) => R::formaPorExtenso($f) . (count($r['formas']) > 1 ? ': ' . 'R$ ' . F::brl((int) $f['valor_centavos']) : ''), $r['formas']))) ?>.</div>
      </div>
      <?php if (!$valido) : ?>
        <div class="alert alert-warning" id="jaEstornado">Este recibo já foi desfeito em <?= $h($dt($r['estorno_data'])) ?>: <?= $h($r['estorno_motivo'] . ($r['estorno_justificativa'] ? ' — ' . $r['estorno_justificativa'] : '')) ?>.</div>
      <?php else : ?>
      <form method="post" action="<?= URLADM ?>financeiro/desfazer-recebimento/<?= $h($r['id']) ?>" class="card" id="formDesfazerRecebimento"
        data-vc-confirma="Desfazer o recibo nº <?= $h($r['numero']) ?>" data-vc-confirma-extra="<?= $h(count($r['linhas']) . ' título(s) voltam a em aberto · ' . $brl($r['total_centavos']) . ' sai do caixa no dia do desfazer') ?>">
        <input type="hidden" name="envio" value="<?= $h(bin2hex(random_bytes(12))) ?>">
        <div class="card-body row g-3">
          <div class="col-md-3"><label class="form-label required" for="dataDesfazer">Data</label><input type="date" class="form-control" id="dataDesfazer" name="data" value="<?= $h(date('Y-m-d')) ?>" min="<?= $h($r['data_recebimento']) ?>" max="<?= $h(date('Y-m-d')) ?>" required></div>
          <div class="col-md-4"><label class="form-label required" for="motivoDesfazer">Motivo</label><select class="form-select" id="motivoDesfazer" name="motivo_id" required><option value="">—</option>
            <?php foreach ($this->dados['motivos'] as $id => $nome) : ?><option value="<?= $h($id) ?>"><?= $h($nome) ?></option><?php endforeach; ?></select></div>
          <div class="col-md-5"><label class="form-label required" for="justificativaDesfazer">Justificativa</label><input type="text" class="form-control" id="justificativaDesfazer" name="justificativa" minlength="5" maxlength="500" required></div>
          <div class="col-12 form-hint">Todos os títulos deste recibo voltam a "em aberto". O recibo continua existindo, com o mesmo número e a faixa "ESTORNADO"; nada é apagado.</div>
        </div>
        <div class="card-footer text-end"><button type="submit" class="btn btn-warning" id="gravarDesfazer"><i class="fas fa-undo-alt me-1"></i> Desfazer recebimento</button></div>
      </form>
      <?php endif; ?>
    </div>
  </section>
</div>
