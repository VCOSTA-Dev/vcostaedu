<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* O ANO LETIVO DO HISTÓRICO (Parte H; D-117): fechar e reabrir, com o motivo e o histórico do marcador. Sem o marcador,
   o ano vale pelo critério de antes (todas as disciplinas encerradas). */
$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$d = $this->dados;
$br = fn($x) => $x ? date('d/m/Y H:i', strtotime((string) $x)) : '';
?>
<div class="vc-pagina" id="anoLetivo">
  <?php $paginaTitulo = 'O ano letivo: fechar e reabrir';
  $paginaPretitulo = 'Histórico escolar';
  include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <div class="alert alert-info">O ano letivo fechado faz a vida escolar propor o resultado final de cada série (aprovado ou retido, pela situação final das disciplinas). No ano aberto, a série fica "em curso". Reabrir pede o motivo, que fica no histórico abaixo.</div>
      <div class="card">
        <div class="table-responsive"><table class="table table-vcenter card-table" id="anosLetivos">
          <thead><tr><th>Ano</th><th>Turmas</th><th>Disciplinas encerradas</th><th>Situação</th><th class="text-end"></th></tr></thead>
          <tbody>
          <?php foreach ($d['anos'] as $a) : ?>
            <tr data-ano="<?= (int) $a['ano'] ?>" data-situacao="<?= $a['fechado'] ? 'fechado' : 'aberto' ?>">
              <td><strong><?= (int) $a['ano'] ?></strong></td>
              <td><?= (int) $a['turmas'] ?></td>
              <td><?= (int) $a['encerradas'] ?> de <?= (int) $a['ofertas'] ?></td>
              <td>
                <?php if ($a['fechado']) : ?><span class="badge bg-green-lt">Fechado</span><?php else : ?><span class="badge bg-blue-lt">Aberto</span><?php endif; ?>
                <div class="small text-secondary"><?= $a['pelo_criterio'] ? 'sem o marcador: pelo critério das disciplinas encerradas' : ($a['marcador'] === 'fechado' ? 'fechado' : 'reaberto') . ' por ' . $h($a['por']) . ' em ' . $h($br($a['em'])) ?></div>
              </td>
              <td class="text-end">
                <?php if ($d['podeFechar'] && $a['marcador'] !== 'fechado') : ?>
                <form method="post" action="<?= URLADM ?>historico-escola/fechar-ano" class="d-inline" data-vc-confirma="Fechar o ano letivo de <?= (int) $a['ano'] ?>?" data-vc-confirma-extra="A vida escolar passa a propor o resultado final das séries deste ano.">
                  <input type="hidden" name="ano" value="<?= (int) $a['ano'] ?>">
                  <button class="btn btn-sm btn-primary vc-fechar-ano">Fechar o ano letivo</button>
                </form>
                <?php endif; ?>
                <?php if ($d['podeReabrir'] && $a['marcador'] === 'fechado') : ?>
                <form method="post" action="<?= URLADM ?>historico-escola/reabrir-ano" class="d-inline-flex gap-1">
                  <input type="hidden" name="ano" value="<?= (int) $a['ano'] ?>">
                  <input class="form-control form-control-sm" name="motivo" maxlength="500" placeholder="O motivo da reabertura" aria-label="O motivo da reabertura" style="min-width:16rem">
                  <button class="btn btn-sm btn-outline-danger vc-reabrir-ano">Reabrir</button>
                </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$d['anos']) : ?><tr><td colspan="5" class="text-secondary">Nenhuma turma cadastrada.</td></tr><?php endif; ?>
          </tbody>
        </table></div>
      </div>
      <div class="card mt-3" id="historicoAnoLetivo">
        <div class="card-header"><h3 class="card-title">O histórico do marcador</h3></div>
        <div class="table-responsive"><table class="table table-vcenter card-table">
          <thead><tr><th>Quando</th><th>Ano</th><th>O quê</th><th>Quem</th><th>Motivo</th></tr></thead>
          <tbody>
          <?php foreach ($d['historico'] as $x) : ?>
            <tr><td><?= $h($br($x['em'])) ?></td><td><?= (int) $x['ano'] ?></td><td><?= $x['acao'] === 'fechar' ? 'Fechou' : 'Reabriu' ?></td><td><?= $h($x['por']) ?></td><td><?= $h($x['motivo']) ?></td></tr>
          <?php endforeach; ?>
          <?php if (!$d['historico']) : ?><tr><td colspan="5" class="text-secondary">Nenhum ano letivo foi fechado ainda.</td></tr><?php endif; ?>
          </tbody>
        </table></div>
      </div>
    </div>
  </section>
</div>
