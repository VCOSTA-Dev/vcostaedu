<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Pagina nao encontrada!");
}
/* 1.17.1 (R-POR-01): o progresso do aluno da sessão no curso — a mesma conta do relatório "Desempenho do aluno" do painel. */
$h = fn($t) => htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
$p = $this->dados['progresso'];
$cor = ['Aprovado' => 'success', 'Reprovado' => 'danger', 'Em curso' => 'info', 'Não cursada' => 'secondary'];
?>
<div class="vc-pagina">
  <?php ob_start(); ?><i class="fas fa-chart-line me-2 text-info"></i>Meu progresso<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if ($this->dados['recusada']) : ?>
        <div class="alert alert-danger" id="progressoRecusado">Esta matrícula não é sua. Escolha uma das suas matrículas abaixo.</div>
      <?php endif; ?>
      <?php if (count($this->dados['matriculas']) > 1) : ?>
        <form method="GET" action="<?= URLACAD . 'progresso/index' ?>" class="d-flex flex-wrap align-items-center gap-2 mb-3">
          <label for="matricula" class="form-label mb-0">Curso</label>
          <select name="matricula" id="matricula" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
            <?php foreach ($this->dados['matriculas'] as $m) : ?>
              <option value="<?= htmlspecialchars((string) ((int) $m['id']), ENT_QUOTES, 'UTF-8') ?>"<?= htmlspecialchars((string) ((int) $m['id'] === $this->dados['mc'] ? ' selected' : ''), ENT_QUOTES, 'UTF-8') ?>><?= $h($m['curso'] . ' — ' . $m['situacao']) ?></option>
            <?php endforeach; ?>
          </select>
        </form>
      <?php endif; ?>
      <?php if (!$p) : ?>
        <?php if (!$this->dados['matriculas']) : ?><p class="text-muted">Você não tem matrícula.</p><?php endif; ?>
      <?php else : $t = $p['totais']; ?>
        <div class="row">
          <div class="col-md-4"><div class="card card-sm mb-3"><div class="card-status-top bg-info"></div><div class="card-body"><div class="h1 mb-1" id="chCumprida"><?= htmlspecialchars((string) ((int) $t['chCumprida']), ENT_QUOTES, 'UTF-8') ?> / <?= htmlspecialchars((string) ((int) $t['chTotal']), ENT_QUOTES, 'UTF-8') ?> h</div><div class="text-secondary">Carga horária cumprida</div></div></div></div>
          <div class="col-md-4"><div class="card card-sm mb-3"><div class="card-body"><div class="h1 mb-1"><?= htmlspecialchars((string) ((int) $t['aprovadas']), ENT_QUOTES, 'UTF-8') ?> de <?= htmlspecialchars((string) ((int) $t['disciplinas']), ENT_QUOTES, 'UTF-8') ?></div><div class="text-secondary">Disciplinas aprovadas</div></div></div></div>
          <div class="col-md-4"><div class="card card-sm mb-3"><div class="card-body"><div class="h1 mb-1" id="mediaFinal"><?= $t['mediaFinal'] !== '' ? $h($t['mediaFinal']) : '—' ?></div><div class="text-secondary">Média final (disciplinas encerradas)</div></div></div></div>
        </div>
        <div class="card shadow-sm">
          <div class="card-header"><h3 class="card-title"><b><?= $h($p['curso']) ?></b> — <?= $h($p['situacao']) ?></h3></div>
          <div class="card-body table-responsive p-0">
            <table class="table table-sm table-striped mb-0" id="tabelaProgresso">
              <thead><tr><th>Disciplina</th><th class="text-end">CH</th><th>Situação</th><th class="text-end">Média</th><th class="text-end">Frequência (%)</th><th>FS/FI</th></tr></thead>
              <tbody>
                <?php foreach ($p['linhas'] as $l) : ?>
                  <tr data-oferta="<?= htmlspecialchars((string) ((int) $l['oferta_id']), ENT_QUOTES, 'UTF-8') ?>">
                    <td><?= $h($l['disciplina']) ?></td><td class="text-end"><?= htmlspecialchars((string) ((int) $l['ch']), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge bg-<?= htmlspecialchars((string) ($cor[$l['situacao']] ?? 'secondary'), ENT_QUOTES, 'UTF-8') ?>-lt"><?= $h($l['situacao']) ?></span></td>
                    <td class="text-end media"><?= $h($l['media']) ?></td><td class="text-end frequencia"><?= $h($l['frequencia']) ?></td><td><?= $h($l['fsfi']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php if ($t['exigeTcc']) : ?>
            <div class="card-footer small">TCC: <b><?= $h($t['tcc'] ?? 'não cadastrado') ?></b><?= $t['notaTcc'] ? ' — nota ' . $h($t['notaTcc']) : '' ?></div>
          <?php endif; ?>
        </div>
        <p class="small text-muted">Disciplina em curso: a média é parcial (só das atividades com nota) e pode mudar até o encerramento.</p>
      <?php endif; ?>
    </div>
  </section>
</div>
