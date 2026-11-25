<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* AS NOTAS DE TRANSFERÊNCIA (D-102): as disciplinas da turma do aluno × as etapas do ano; em cada célula, a nota e as
   faltas trazidas da escola de origem. A etapa que terminou antes da matrícula fica sem nota se nada for trazido. */
$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$d = $this->dados;
$a = $d['aluno'];
$q = $d['quadro'];
$ano = (int) $d['ano'];
$pode = !empty((new \App\adms\Models\AdmsBotao())->valBotao(['s' => ['menu_controller' => 'escola-transferencia', 'menu_metodo' => 'salvar']])['s']);
$num = fn($n) => $n === null || $n === '' ? '' : str_replace('.', ',', rtrim(rtrim(number_format((float) $n, 2, '.', ''), '0'), '.'));
?>
<div class="vc-pagina" id="notasTransferencia">
  <?php ob_start(); ?>Notas de transferência — <?= $h($a['nome']) ?><?php $paginaTitulo = ob_get_clean();
  $paginaPretitulo = $h(($q['turma']['codtur'] ?? 'sem turma') . ' · ' . $ano);
  $paginaAcoesHtml = '<a href="' . URLADM . 'academicos/visualizar/' . (int) $a['id'] . '?tab=tabeMatricula" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> O aluno</a>';
  include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <p class="text-secondary">Para o aluno que entrou no meio do ano: a nota e as faltas de cada disciplina, por etapa, trazidas da escola de origem. Elas contam na média anual, aparecem marcadas com "T" no boletim e valem mesmo na etapa já fechada; o professor não as muda. A etapa que terminou antes da matrícula<?= $q['ingresso'] ? ' (' . $h(date('d/m/Y', strtotime($q['ingresso']))) . ')' : '' ?> e não tem nota trazida fica sem nota, fora da média.</p>
      <?php if (count($d['anos']) > 1) : ?>
        <form method="get" class="mb-3"><label class="form-label" for="anoTransf">Ano letivo</label>
          <select name="ano" id="anoTransf" class="form-select w-auto" onchange="this.form.submit()"><?php foreach ($d['anos'] as $x) : ?><option value="<?= (int) $x ?>"<?= $x === $ano ? ' selected' : '' ?>><?= (int) $x ?></option><?php endforeach; ?></select></form>
      <?php endif; ?>
      <?php if (!$q['turma']) : ?>
        <div class="alert alert-info" id="semTurmaTransf">O aluno não está numa turma neste ano: enturme-o antes de lançar as notas de transferência.</div>
      <?php else : ?>
      <form method="post" action="<?= URLADM ?>escola-transferencia/salvar/<?= (int) $a['id'] ?>" class="card" id="formTransferencia">
        <input type="hidden" name="ano" value="<?= $ano ?>">
        <div class="card-body row g-3 border-bottom">
          <div class="col-md-6"><label class="form-label" for="escolaOrigem">Escola de origem</label><input type="text" class="form-control" name="escola_origem" id="escolaOrigem" maxlength="160" value="<?= $h($q['escola']) ?>"<?= $pode ? '' : ' disabled' ?>></div>
          <div class="col-md-6"><label class="form-label" for="documentoTransf">Documento apresentado</label><input type="text" class="form-control" name="documento" id="documentoTransf" maxlength="160" placeholder="Ex.: declaração de transferência, histórico parcial" value="<?= $h($q['documento']) ?>"<?= $pode ? '' : ' disabled' ?>></div>
        </div>
        <div class="table-responsive">
          <table class="table table-vcenter card-table" id="tabelaTransferencia">
            <thead><tr><th>Disciplina</th><?php foreach ($q['etapas'] as $e) : $antes = $q['ingresso'] && (string) $e['fim'] < (string) $q['ingresso']; ?><th class="text-center" data-etapa="<?= (int) $e['id'] ?>"><?= $h($e['nome']) ?><?= $antes ? '<div class="small text-secondary fw-normal">antes da matrícula</div>' : '' ?></th><?php endforeach; ?></tr></thead>
            <tbody>
            <?php foreach ($q['ofertas'] as $o) : ?>
              <tr data-oferta="<?= (int) $o['id'] ?>"><td><?= $h($o['materia']) ?></td>
                <?php foreach ($q['etapas'] as $e) : $val = $q['valores'][(int) $o['id']][(int) $e['id']] ?? null; ?>
                  <td class="text-center"><div class="d-inline-flex gap-1">
                    <input type="text" inputmode="decimal" class="form-control form-control-sm text-end" style="width: 4.5rem" name="nota[<?= (int) $o['id'] ?>][<?= (int) $e['id'] ?>]" value="<?= $h($num($val['nota'] ?? null)) ?>" placeholder="nota" aria-label="Nota de <?= $h($o['materia']) ?>, <?= $h($e['nome']) ?>"<?= $pode ? '' : ' disabled' ?>>
                    <input type="text" inputmode="numeric" class="form-control form-control-sm text-end" style="width: 4rem" name="faltas[<?= (int) $o['id'] ?>][<?= (int) $e['id'] ?>]" value="<?= $h($val && (int) $val['faltas'] ? (int) $val['faltas'] : '') ?>" placeholder="faltas" aria-label="Faltas de <?= $h($o['materia']) ?>, <?= $h($e['nome']) ?>"<?= $pode ? '' : ' disabled' ?>>
                  </div></td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php if ($pode) : ?><div class="card-footer d-flex gap-2 align-items-center"><button type="submit" class="btn btn-primary" id="salvarTransferencia"><i class="fas fa-save me-1"></i> Salvar</button>
          <span class="small text-secondary">Deixar a nota e as faltas vazias tira o que foi trazido (fica no histórico).</span></div><?php endif; ?>
      </form>
      <?php endif; ?>
    </div>
  </section>
</div>
