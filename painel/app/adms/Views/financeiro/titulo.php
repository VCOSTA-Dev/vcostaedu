<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* VISUALIZAR O TÍTULO (Onda 8, financeiro refeito, Parte 1; D-99): todos os dados do título, os recibos dele (o válido e
   os estornados) e o histórico das operações. Só de leitura: mudar, só pelas ações (que deixam registro). */
use App\adms\Models\financeiro\AdmsFinanceiro as F;
use App\adms\Models\financeiro\AdmsFinFicha as Ficha;

$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$t = $this->dados['titulo'];
$a = $this->dados['aluno'];
$brl = fn($v) => $v === null ? '—' : 'R$ ' . F::brl((int) $v);
$dt = fn($x) => $x ? date('d/m/Y', strtotime((string) $x)) : '—';
$N = \App\adms\Models\financeiro\AdmsFinOperacoes::NOMES;
$hoje = date('Y-m-d');
$e = $t['situacao'] === 'aberto' ? F::encargosTitulo($t, $hoje) : null;
$numAcordo = static fn($id) => (int) F::pdo()->query('SELECT COALESCE(numero, id) FROM fin_renegociacoes WHERE id = ' . (int) $id)->fetchColumn(); // D-105: o número do acordo
$mostrada = F::situacaoMostrada($t, $hoje);
$rot = ['vencido' => 'Atrasado'][$mostrada] ?? F::rotulo($mostrada);
?>
<div class="vc-pagina" id="visualizarTitulo" data-titulo="<?= $h($t['id']) ?>">
  <?php ob_start(); ?>Título <?= $h($t['id']) ?><?php $paginaTitulo = ob_get_clean();
  $paginaPretitulo = $h(Ficha::rotuloNumero() . ' ' . $a['rm'] . ' · ' . ($a['nome_social'] ?: $a['nome']));
  $paginaAcoesHtml = '<a href="' . URLADM . 'financeiro/ficha/' . (int) $t['aluno_id'] . '?ano=' . (int) $t['ano'] . '" class="btn btn-outline-secondary" id="voltarFicha"><i class="fas fa-arrow-left me-1"></i> Ficha financeira</a>';
  include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <div class="row row-cards">
        <div class="col-lg-5">
          <div class="card" id="dadosTitulo"><div class="card-header"><h3 class="card-title">Os dados do título</h3></div><div class="card-body">
            <dl class="row mb-0">
              <dt class="col-5">Descrição</dt><dd class="col-7"><?= $h($t['descricao']) ?></dd>
              <dt class="col-5">Ano letivo</dt><dd class="col-7"><?= $h($t['ano']) ?></dd>
              <dt class="col-5">Parcela</dt><dd class="col-7"><?= $h($t['parcela'] !== null ? $t['parcela'] . ($t['parcelas_total'] ? '.' . $t['parcelas_total'] : '') : '—') ?></dd>
              <dt class="col-5">Plano</dt><dd class="col-7"><?= $h($t['plano'] ?: ($t['renegociacao_id'] ? 'Acordo nº ' . $numAcordo($t['renegociacao_id']) : 'Título avulso')) ?></dd>
              <dt class="col-5">Valor</dt><dd class="col-7"><?= $h($brl($t['valor_centavos'])) ?></dd>
              <dt class="col-5">Vencimento</dt><dd class="col-7"><?= $h($dt($t['vencimento'])) ?></dd>
              <dt class="col-5">Situação</dt><dd class="col-7"><span class="badge <?= $mostrada === 'vencido' ? 'bg-red text-red-fg' : 'bg-secondary-lt' ?>" id="situacaoTitulo"><?= $h($rot) ?></span></dd>
              <?php if ($e) : ?><dt class="col-5">Hoje, com os encargos</dt><dd class="col-7"><?= $h($brl($e['total'])) ?><?= $e['multa'] + $e['juros'] ? ' <span class="text-secondary small">(multa ' . $h(F::brl($e['multa'])) . ', juros ' . $h(F::brl($e['juros'])) . ')</span>' : '' ?></dd><?php endif; ?>
              <?php if ($t['renegociado_por']) : ?><dt class="col-5">Renegociado em</dt><dd class="col-7">Acordo nº <?= $h($numAcordo($t['renegociado_por'])) ?></dd><?php endif; ?>
              <dt class="col-5">Lançado</dt><dd class="col-7"><?= $h(date('d/m/Y H:i', strtotime((string) $t['criado_em']))) ?><?= $t['criador'] ? ' · ' . $h($t['criador']) : '' ?></dd>
            </dl>
          </div></div>
        </div>
        <div class="col-lg-7">
          <div class="card mb-3" id="recibosTitulo"><div class="card-header"><h3 class="card-title">Os recibos</h3></div>
            <div class="table-responsive"><table class="table table-sm card-table">
              <thead><tr><th>Nº</th><th>Recebido em</th><th class="text-end">Deste título</th><th>Quem recebeu</th><th>Situação</th><th></th></tr></thead>
              <tbody>
              <?php if (!$this->dados['recibos']) : ?><tr><td colspan="6" class="text-secondary">Nenhum recibo.</td></tr><?php endif; ?>
              <?php foreach ($this->dados['recibos'] as $r) : ?>
                <tr data-recibo="<?= $h($r['numero']) ?>" data-situacao="<?= $h($r['situacao']) ?>"><td><?= $h($r['numero']) ?></td><td><?= $h($dt($r['data_recebimento'])) ?></td><td class="text-end"><?= $h($brl($r['recebido_centavos'])) ?></td><td><?= $h($r['recebedor']) ?></td>
                  <td><?= $r['situacao'] === 'estornado' ? '<span class="badge bg-red-lt">estornado em ' . $h($dt($r['estorno_data'])) . '</span>' : '<span class="badge bg-green-lt">válido</span>' ?></td>
                  <td><a href="<?= URLADM ?>financeiro/recibo/<?= $h($r['id']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">Recibo</a></td></tr>
              <?php endforeach; ?>
              </tbody></table></div>
          </div>
          <div class="card" id="historicoTitulo"><div class="card-header"><h3 class="card-title">O histórico do título</h3></div>
            <div class="table-responsive"><table class="table table-sm card-table">
              <thead><tr><th>Quando</th><th>O quê</th><th>Detalhe</th><th>Quem</th></tr></thead>
              <tbody>
              <?php foreach ($this->dados['operacoes'] as $o) : ?>
                <tr class="<?= $o['desfeita_em'] ? 'text-secondary' : '' ?>"><td class="text-nowrap"><?= $h(date('d/m/Y H:i', strtotime((string) $o['criado_em']))) ?></td>
                  <td><?= $h($N[$o['tipo']] ?? $o['tipo']) ?><?= $o['desfeita_em'] ? ' <span class="badge bg-secondary-lt">desfeito</span>' : '' ?></td>
                  <td><?= $h($o['tipo'] === 'receber' ? 'R$ ' . F::brl((int) $o['recebido_centavos']) . ' em ' . $dt($o['data_operacao']) . ($o['observacao'] ? ' · ' . $o['observacao'] : '') : (string) ($o['motivo'] ?: $o['observacao'])) ?></td>
                  <td><?= $h($o['usuario'] ?? '') ?></td></tr>
              <?php endforeach; ?>
              </tbody></table></div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
