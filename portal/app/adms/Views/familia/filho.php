<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* O resumo do filho no ano (Onda 8, correção, D-83): a turma, os números do ano (do boletim), as aulas de hoje e os
   disciplinas com o professor; os atalhos para as telas de cada assunto. */
$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$d = $this->dados;
$res = $d['resumo'] ?? [];
$tur = $d['turma'] ?? null;
$vv = fn($n) => \Core\MediaEscola::v($n === null ? null : (string) $n);
$abaixoMin = fn($n) => $n !== null && $n !== '' && \Core\MotorMedia::centesimosDe((string) $n) < \Core\MotorMedia::centesimosDe((string) ($res['mediaMinima'] ?? '0'));
?>
<div class="vc-pagina vc-familia">
  <?php ob_start(); ?>Acompanhamento<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <?php include __DIR__ . '/_cabecalho.php'; ?>
      <?php if (!empty($this->dados['cartaoFinanceiro'])) { /* Onda 8, financeiro, Parte 3 (D-98): só o responsável financeiro */
        $vencidosF = (int) $this->dados['cartaoFinanceiro']['vencidos']; $urlConsultarF = (string) $this->dados['cartaoFinanceiro']['url'];
        include 'app/adms/Views/include_tabler/_cartao_financeiro.php';
      } ?>

      <?php if ($tur) : ?>
      <div class="card mb-3" id="dadosTurma"><div class="card-body py-2"><dl class="row mb-0">
        <dt class="col-5 col-md-2">Segmento</dt><dd class="col-7 col-md-4"><?= $h($tur['segmento']) ?></dd>
        <dt class="col-5 col-md-2">Turma</dt><dd class="col-7 col-md-4"><?= $h(trim($tur['serie'] . ' ' . $tur['nome'])) ?></dd>
        <dt class="col-5 col-md-2">Turno</dt><dd class="col-7 col-md-4"><?= $h($tur['turno'] ?: '—') ?></dd>
        <dt class="col-5 col-md-2">Sala</dt><dd class="col-7 col-md-4"><?= $h($tur['sala'] ?: '—') ?></dd>
      </dl></div></div>
      <?php else : ?><div class="alert alert-info" id="semTurma">O aluno não está numa turma neste ano.</div><?php endif; ?>

      <div class="row row-cards mb-3" id="numerosFilho">
        <div class="col-6 col-md-3"><div class="card card-sm"><div class="card-body">
          <div class="h2 mb-0 <?= $abaixoMin($res['mediaGeral'] ?? null) ? 'text-danger' : '' ?>" id="mediaGeral"><?= $h($vv($res['mediaGeral'] ?? null)) ?></div>
          <div class="text-secondary">Média geral</div>
          <small class="text-muted" id="legendaMedia"><?= !empty($d['mediaOculta']) ? 'a média anual aparece quando a escola liberar' : 'a média mínima da escola é ' . $h($vv($res['mediaMinima'] ?? null)) ?></small>
        </div></div></div>
        <div class="col-6 col-md-3"><div class="card card-sm"><div class="card-body">
          <div class="h2 mb-0" id="frequenciaGeral"><?= ($res['frequencia'] ?? null) !== null ? $h($vv($res['frequencia'])) . '%' : '—' ?></div>
          <div class="text-secondary">Frequência</div>
        </div></div></div>
        <div class="col-6 col-md-3"><div class="card card-sm"><div class="card-body">
          <div class="h2 mb-0" id="faltasAno"><?= $h((int) ($res['faltas'] ?? 0)) ?></div>
          <div class="text-secondary">Faltas no ano</div>
        </div></div></div>
        <div class="col-6 col-md-3"><div class="card card-sm"><div class="card-body">
          <div class="h2 mb-0 <?= (int) ($res['abaixo'] ?? 0) ? 'text-danger' : '' ?>" id="abaixoMedia"><?= !empty($d['mediaOculta']) ? '—' : $h((int) ($res['abaixo'] ?? 0)) ?></div>
          <div class="text-secondary"><?= (int) ($res['abaixo'] ?? 0) === 1 ? 'Disciplina abaixo da média' : 'Disciplinas abaixo da média' ?></div>
        </div></div></div>
      </div>

      <div class="card mb-3" id="aulasHoje">
        <div class="card-header"><h3 class="card-title"><i class="far fa-clock me-1"></i> Hoje</h3></div>
        <?php if (empty($res['hoje'])) : ?>
          <div class="card-body text-secondary">Sem aula hoje no horário.</div>
        <?php else : ?>
          <div class="list-group list-group-flush">
            <?php foreach ($res['hoje'] as $x) : ?>
              <div class="list-group-item"><span class="text-secondary me-2"><?= $h(substr((string) $x['inicio'], 0, 5) . '–' . substr((string) $x['termino'], 0, 5)) ?></span><strong><?= $h($x['materia']) ?></strong><?= !empty($x['professores']) ? ' <span class="text-secondary">· ' . $h($x['professores']) . '</span>' : '' ?></div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="card mb-3">
        <div class="card-header"><h3 class="card-title">As disciplinas</h3></div>
        <div class="table-responsive"><table class="table table-vcenter card-table" id="componentesTurma">
          <thead><tr><th>Disciplina</th><th>Professor</th><th class="text-end">Média</th><th class="text-end">Faltas</th><th class="text-end">Frequência</th></tr></thead>
          <tbody>
          <?php foreach ($res['componentes'] ?? [] as $c) : ?>
            <tr><td><?= $h($c['materia']) ?></td><td><?= $h($c['professores'] ?: '—') ?></td>
              <td class="text-end <?= $abaixoMin($c['media']) ? 'text-danger fw-bold' : '' ?>"><?= $h($vv($c['media'])) ?></td>
              <td class="text-end"><?= $h((int) $c['faltas']) ?></td>
              <td class="text-end"><?= $c['frequencia'] !== null && $c['frequencia'] !== '' ? $h($vv($c['frequencia'])) . '%' : '—' ?></td></tr>
          <?php endforeach; ?>
          </tbody>
        </table></div>
      </div>

      <div class="row row-cards" id="atalhosFilho">
        <?php foreach (['boletim' => ['Boletim', 'fas fa-file-alt'], 'notas' => ['Notas das atividades', 'fas fa-list-ol'], 'frequencia' => ['Frequência', 'fas fa-calendar-check'], 'horario' => ['Horário', 'far fa-clock'], 'atestado' => ['Atestado de matrícula', 'fas fa-file-signature']] as $rota => [$rot, $ic]) : ?>
          <div class="col-6 col-md-4"><a class="card card-link" href="<?= $h($urlF($rota)) ?>"><div class="card-body d-flex align-items-center gap-2"><i class="<?= $h($ic) ?> text-primary"></i> <?= $h($rot) ?></div></a></div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
</div>
