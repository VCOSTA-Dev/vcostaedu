<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/*
 * Onda 8, correção, segunda leva, 2.2 (D-88): a aba "Matrículas" da ficha do aluno NA ESCOLA — o segmento, a série, a
 * turma, o ano letivo e a situação. Nada do ensino superior (a grade, o polo, a modalidade, a forma de ingresso, o TCC,
 * a conclusão pela grade): a matrícula da escola guarda esses campos com os valores do segmento, que o formulário manda
 * escondidos, sem mudar. A série do ano vem da turma (a enturmação grava a posição); a turma se escolhe em Turmas.
 * Incluído por verAcademico.php no lugar de vinculoAluno.php quando a instalação é uma escola; usa $info (o aluno).
 */
$vinculos = $this->dados['vinculos'] ?? [];
$op = ($this->dados['opcoesVinculo'] ?? []) + ['modulos' => [], 'formas' => [], 'situacoes' => [], 'anos' => []];
$bt = $this->dados['botao'] ?? [];
$h = fn($t) => htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
$alunoId = (int) $info['id'];
$dt = fn($v) => $v ? date('d/m/Y', strtotime($v)) : '';
$turmas = \App\adms\Models\escola\AdmsTurmaAlunos::turmasDoAluno($alunoId);
$formaPadrao = (int) ($op['formas'][0]['id'] ?? 0);
?>
<?php if (!$vinculos) : ?>
  <div class="alert alert-warning" id="semVinculo">
    <?php $livreVinc = \App\adms\Models\helper\AdmsTipoInstituicao::ehLivre(); /* D-106: no curso livre, o curso (a série é a interna, nunca exibida) */ ?>
    <b>Este aluno não está matriculado em nenhum <?= $livreVinc ? 'curso' : 'segmento' ?>.</b> Matricule-o abaixo; depois, ponha-o numa turma em <b>Turmas</b>.
  </div>
<?php endif; ?>

<?php foreach ($vinculos as $v) : ?>
  <div class="card card-<?= $h($v['cores'] ?: 'secondary') ?> vinculo vinculo-escola" data-matricula="<?= $h((int) $v['id']) ?>">
    <div class="card-header">
      <h3 class="card-title"><b><?= $h($v['curso']) ?></b> <span class="badge bg-<?= $h($v['cores'] ?: 'secondary') ?>-lt ms-1 vc-situacao-matricula"><?= $h($v['situacao']) ?></span></h3>
      <?php if (\App\adms\Models\relatorios\AdmsRelatorios::pode('desempenho-aluno')) : ?>
        <a class="btn btn-sm btn-outline-info ms-auto atalhoDesempenho" href="<?= $h(URLADM . 'relatorios/desempenho-aluno?matricula=' . (int) $v['id']) ?>"><i class="fas fa-chart-line"></i> Desempenho</a>
      <?php endif; ?>
      <?php if (!empty($bt['transferencia'])) : /* D-102 (A2) */ ?>
        <a class="btn btn-sm btn-outline-secondary ms-2 atalhoTransferencia" href="<?= $h(URLADM . 'escola-transferencia/index/' . $alunoId) ?>"><i class="fas fa-right-left"></i> Notas de transferência</a>
      <?php endif; ?>
    </div>
    <div class="card-body">
      <p class="mb-2"><b>Matrícula em:</b> <?= $h($dt($v['data_ingresso'])) ?><?= $v['observacao'] ? '<br><small class="text-muted">' . $h($v['observacao']) . '</small>' : '' ?></p>
      <table class="table table-sm mb-2" id="anosDaMatricula">
        <?php $livreVinc = \App\adms\Models\helper\AdmsTipoInstituicao::ehLivre(); ?>
        <thead><tr><th>Ano letivo</th><?php if (!$livreVinc) : ?><th>Série</th><?php endif; ?><th>Turma</th><th></th></tr></thead>
        <tbody>
          <?php if (!$v['posicoes']) : ?><tr><td colspan="4" class="text-muted"><?= $livreVinc ? 'Ainda sem turma: ponha o aluno numa turma em Turmas.' : 'Ainda sem série em nenhum ano: ponha o aluno numa turma em Turmas.' ?></td></tr><?php endif; ?>
          <?php foreach ($v['posicoes'] as $p) : $t = $turmas[(int) $p['ano']] ?? null; ?>
            <tr class="posicao" data-ano="<?= $h((int) $p['ano']) ?>">
              <td><?= $h((int) $p['ano']) ?></td>
              <?php if (!$livreVinc) : ?><td class="vc-serie"><?= $h($p['modulo'] ?? '—') ?></td><?php endif; ?>
              <td class="vc-turma"><?= $t ? $h($t['nome']) . ($t['turno'] ? ' · ' . $h($t['turno']) : '') . ($t['sala'] ? ' · sala ' . $h($t['sala']) : '') : '<span class="text-muted">sem turma</span>' ?></td>
              <td class="text-end"><?php if ($t && !empty($bt['turma'])) : ?><a class="btn btn-sm btn-outline-primary" href="<?= $h(URLADM . 'turmas/ver/' . (int) $t['id']) ?>">Ver a turma</a><?php endif; ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php if (!empty($bt['editar_vinculo'])) : ?>
        <form method="POST" action="<?= $h(URLADM . 'academicos/editar-vinculo/' . (int) $v['id']) ?>" class="formVinculo row g-2 align-items-end">
          <?php /* a forma de ingresso, o polo e a modalidade não vão: na escola, o modelo guarda os que a matrícula já tem */ ?>
          <div class="col-md-2"><label class="small mb-0">Data da matrícula</label><input type="date" name="data_ingresso" value="<?= $h($v['data_ingresso']) ?>" class="form-control form-control-sm"></div>
          <div class="col-md-2"><label class="small mb-0">Situação</label><select name="situacao_id" class="form-select form-select-sm">
            <?php foreach ($op['situacoes'] as $s) : if ($s['nome'] === 'Trancado' && (int) $s['id'] !== (int) $v['situacao_id']) { continue; } /* D-103 (C2) */ ?><option value="<?= $h((int) $s['id']) ?>" <?= (int) $s['id'] === (int) $v['situacao_id'] ? 'selected' : '' ?>><?= $h($s['nome']) ?></option><?php endforeach; ?>
          </select></div>
          <div class="col-md-2"><label class="small mb-0" title="O atestado de matrícula a usa">Previsão de conclusão</label><input type="date" name="previsao_conclusao" value="<?= $h($v['previsao_conclusao'] ?? '') ?>" class="form-control form-control-sm"></div>
          <div class="col-md-2"><label class="small mb-0">Data de conclusão</label><input type="date" name="data_conclusao" value="<?= $h($v['data_conclusao'] ?? '') ?>" class="form-control form-control-sm" title="Só para a situação Concluído"></div>
          <div class="col-md-2"><label class="small mb-0">Observação</label><input type="text" name="observacao" value="<?= $h($v['observacao']) ?>" class="form-control form-control-sm"></div>
          <div class="col-md-2"><button type="submit" class="btn btn-sm btn-outline-warning w-100">Salvar matrícula</button></div>
        </form>
      <?php endif; ?>
    </div>
  </div>
<?php endforeach; ?>

<?php if (!empty($bt['vincular']) && $formaPadrao) : ?>
  <div class="card" id="matricularSegmento">
    <?php $livreVinc = \App\adms\Models\helper\AdmsTipoInstituicao::ehLivre(); ?>
    <div class="card-header"><h3 class="card-title"><?= $livreVinc ? 'Matricular no curso' : 'Matricular num segmento' ?></h3></div>
    <form method="POST" action="<?= $h(URLADM . 'academicos/vincular/' . $alunoId) ?>">
      <input type="hidden" name="forma_ingresso_id" value="<?= $h($formaPadrao) ?>">
      <div class="card-body">
        <div class="row g-2">
          <div class="mb-3 col-md-2">
            <label class="form-label" for="vAno">Ano letivo</label>
            <select name="ano" id="vAno" class="form-select">
              <?php foreach ($op['anos'] as $a) : ?><option value="<?= (int) $a ?>"><?= (int) $a ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3 col-md-6">
            <label class="form-label" for="vModulo"><?= $livreVinc ? 'Curso' : 'Segmento e série' ?></label>
            <select name="modulo_id" id="vModulo" class="form-select">
              <option value="">Selecione</option>
              <?php foreach ($op['modulos'] as $m) : ?><option value="<?= $h((int) $m['id']) ?>"><?= $h($m['curso']) ?><?= $livreVinc ? '' : ' — ' . $h($m['codnome']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3 col-md-3">
            <label class="form-label" for="vData">Data da matrícula</label>
            <input type="date" name="data_ingresso" id="vData" class="form-control" value="<?= date('Y-m-d') ?>">
          </div>
          <?php /* Correção da escola, Parte 3 (C8; D-102): a turma, no mesmo passo (opcional): só as da série e do ano escolhidos */
            $turmasOp = \App\adms\Models\escola\AdmsTurmaAlunos::turmasParaMatricula(); ?>
          <div class="mb-3 col-md-4">
            <label class="form-label" for="vTurma">Turma <small class="text-muted">(opcional)</small></label>
            <select name="turma_id" id="vTurma" class="form-select">
              <option value="">Depois, em Turmas</option>
              <?php foreach ($turmasOp as $tu) : ?><option value="<?= (int) $tu['id'] ?>" data-ano="<?= (int) $tu['ano'] ?>" data-modulo="<?= (int) $tu['modulo_id'] ?>"><?= $h($tu['codtur']) ?><?= $tu['turno'] ? ' · ' . $h($tu['turno']) : '' ?></option><?php endforeach; ?>
            </select>
          </div>
          <?php include __DIR__ . '/_vinculoPlano.php'; // Onda 8, financeiro (D-98) ?>
        </div>
        <small class="text-muted">Sem a turma aqui, ponha o aluno numa turma <?= $livreVinc ? 'do curso' : 'da série' ?> em <b>Turmas</b>.</small>
        <script>
        (function () {
          var ano = document.getElementById('vAno'), mod = document.getElementById('vModulo'), tu = document.getElementById('vTurma');
          if (!tu) { return; }
          function filtrar() {
            Array.prototype.forEach.call(tu.options, function (o) {
              if (!o.value) { return; }
              var ok = o.dataset.ano === (ano ? ano.value : '') && o.dataset.modulo === (mod ? mod.value : '');
              o.hidden = !ok; o.disabled = !ok;
              if (!ok && o.selected) { tu.value = ''; }
            });
          }
          if (ano) { ano.addEventListener('change', filtrar); }
          if (mod) { mod.addEventListener('change', filtrar); }
          filtrar();
        })();
        </script>
      </div>
      <div class="card-footer"><button type="submit" class="btn btn-success" id="vincularAluno">Matricular</button></div>
    </form>
  </div>
<?php endif; ?>
