<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* E3b: vínculo do aluno — matrícula no curso (ingresso, situação) e posição por ano/módulo (E8c: sem módulo com o parâmetro em Não).
   E6a: polo, modalidade e conclusão da matrícula; TCC; link para a prévia do histórico.
   Incluído pela aba "Matrículas" de verAcademico.php; usa $info (o aluno). */
$vinculos = $this->dados['vinculos'] ?? [];
$op = ($this->dados['opcoesVinculo'] ?? []) + ['modulos' => [], 'formas' => [], 'situacoes' => [], 'anos' => [], 'polos' => [], 'modalidades' => [], 'orientadores' => []];
$bt = $this->dados['botao'] ?? [];
$h = fn($t) => htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
$alunoId = (int) $info['id'];
$dt = fn($v) => $v ? date('d/m/Y', strtotime($v)) : '';
$semMod = !empty($this->dados['semModulos']); /* E8c (D-268): sem módulo no vínculo */
?>
<?php if (!$vinculos) : ?>
  <div class="alert alert-warning" id="semVinculo">
    <b>Este aluno não tem vínculo com nenhum curso.</b> Sem vínculo não há enturmação sugerida nem boletim do curso.
    Use <b>Vincular a um curso</b> abaixo.
  </div>
<?php endif; ?>

<?php foreach ($vinculos as $v) : $tcc = $v['tcc'] ?? null; ?>
  <div class="card card-<?= $h($v['cores'] ?: 'secondary') ?> vinculo" data-matricula="<?= htmlspecialchars((string) ((int) $v['id']), ENT_QUOTES, 'UTF-8') ?>">
    <div class="card-header">
      <h3 class="card-title"><b><?= $h($v['curso']) ?></b> <span class="badge bg-secondary-lt ms-1 vc-grade" title="A versão da grade (a matriz) desta matrícula">Grade <?= htmlspecialchars((string) ((int) $v['versao']), ENT_QUOTES, 'UTF-8') ?></span>
        <span class="badge bg-<?= $h($v['cores'] ?: 'secondary') ?>-lt ms-1"><?= $h($v['situacao']) ?></span></h3>
      <?php if (!empty($bt['previa'])) : ?>
        <a class="btn btn-sm btn-outline-dark float-end" href="<?= htmlspecialchars((string) (URLADM . 'historico/previa/' . (int) $v['id']), ENT_QUOTES, 'UTF-8') ?>"><i class="fas fa-scroll"></i> Prévia do histórico</a>
      <?php endif; ?>
      <?php if (\App\adms\Models\relatorios\AdmsRelatorios::pode('desempenho-aluno')) : /* 1.17.0: atalho para o R-DES-05 */ ?>
        <a class="btn btn-sm btn-outline-info float-end me-1 atalhoDesempenho" href="<?= htmlspecialchars((string) (URLADM . 'relatorios/desempenho-aluno?matricula=' . (int) $v['id']), ENT_QUOTES, 'UTF-8') ?>"><i class="fas fa-chart-line"></i> Desempenho</a>
      <?php endif; ?>
    </div>
    <div class="card-body">
      <p class="mb-2"><b>Ingresso:</b> <?= $h($v['formaIngresso']) ?> em <?= $h($dt($v['data_ingresso'])) ?>
        · <b>Polo:</b> <?= $h($v['polo'] ?? '—') ?> · <b>Modalidade:</b> <?= $h($v['modalidade'] ?? '—') ?>
        <?= $v['data_conclusao'] ? ' · <b>Conclusão:</b> ' . $h($dt($v['data_conclusao'])) : '' ?>
        <?= !empty($v['previsao_conclusao']) ? ' · <b>Previsão de conclusão:</b> <span class="previsaoMesAno">' . $h(\App\adms\Models\historico\AdmsHistoricoVariaveis::mesAno($v['previsao_conclusao'])) . '</span>' : '' /* E1b (D-354) */ ?>
        <?= $v['observacao'] ? '<br><small class="text-muted">' . $h($v['observacao']) . '</small>' : '' ?></p>
      <table class="table table-sm mb-2">
        <thead><tr><th>Ano</th><?php if (!$semMod) : ?><th>Módulo</th><?php endif; ?><th class="text-center">Enturmações no ano</th><th>Enturmação confirmada</th><th></th></tr></thead>
        <tbody>
          <?php if (!$v['posicoes']) : ?><tr><td colspan="<?= $semMod ? 4 : 5 ?>" class="text-muted">Sem registro em nenhum ano.</td></tr><?php endif; ?>
          <?php foreach ($v['posicoes'] as $p) : ?>
            <tr class="posicao" data-ano="<?= htmlspecialchars((string) ((int) $p['ano']), ENT_QUOTES, 'UTF-8') ?>">
              <td><?= htmlspecialchars((string) ((int) $p['ano']), ENT_QUOTES, 'UTF-8') ?></td>
              <?php if (!$semMod) : ?><td><?= $h($p['modulo'] ?? '—') ?></td><?php endif; ?>
              <td class="text-center"><?= htmlspecialchars((string) ((int) $p['enturmacoes']), ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= $p['enturmacao_confirmada_em'] ? $h(date('d/m/Y H:i', strtotime($p['enturmacao_confirmada_em']))) . ' — ' . $h($p['confirmadaPor']) : '<span class="text-muted">não confirmada</span>' ?></td>
              <td class="text-end">
                <?php if (!empty($bt['enturmar'])) : ?>
                  <a class="btn btn-sm btn-outline-success" href="<?= URLADM . 'portal-alunos/visualizar/' . $h($info['rm']) . '?ano=' . (int) $p['ano'] ?>">Enturmação</a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div class="row">
        <?php if (!empty($bt['posicionar']) && (int) $v['situacao_id'] === 1) : ?>
          <div class="col-md-5">
            <form method="POST" action="<?= htmlspecialchars((string) (URLADM . 'academicos/posicionar-modulo/' . (int) $v['id']), ENT_QUOTES, 'UTF-8') ?>" class="d-flex flex-wrap align-items-center gap-2">
              <label class="me-1">Ano</label>
              <select name="ano" class="form-select form-select-sm me-1">
                <?php foreach ($op['anos'] as $a) : ?><option value="<?= (int) $a ?>"><?= (int) $a ?></option><?php endforeach; ?>
              </select>
              <?php if ($semMod) : ?>
                <input type="hidden" name="modulo_id" value="<?= htmlspecialchars((string) ((int) $v['moduloInterno']), ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="btn btn-sm btn-outline-primary" title="O aluno passa a ter o ano, para a enturmação sugerida"><i class="fas fa-calendar-check"></i> Registrar no ano</button>
              <?php else : ?>
              <select name="modulo_id" class="form-select form-select-sm me-1" aria-label="Módulo">
                <?php foreach ($v['modulosDaGrade'] as $m) : ?><option value="<?= htmlspecialchars((string) ((int) $m['id']), ENT_QUOTES, 'UTF-8') ?>"><?= $h($m['codnome']) ?></option><?php endforeach; ?>
              </select>
              <button type="submit" class="btn btn-sm btn-outline-primary">Gravar módulo no ano</button>
              <?php endif; ?>
            </form>
          </div>
        <?php endif; ?>
        <?php if (!empty($bt['editar_vinculo'])) : ?>
          <div class="col-md-7">
            <form method="POST" action="<?= htmlspecialchars((string) (URLADM . 'academicos/editar-vinculo/' . (int) $v['id']), ENT_QUOTES, 'UTF-8') ?>" class="formVinculo">
              <div class="row g-2">
                <div class="col-4"><label class="small mb-0">Forma de ingresso</label><select name="forma_ingresso_id" class="form-select form-select-sm">
                  <?php foreach ($op['formas'] as $fo) : ?><option value="<?= htmlspecialchars((string) ((int) $fo['id']), ENT_QUOTES, 'UTF-8') ?>" <?= htmlspecialchars((string) ((int) $fo['id'] === (int) $v['forma_ingresso_id'] ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>><?= $h($fo['nome']) ?></option><?php endforeach; ?>
                </select></div>
                <div class="col-4"><label class="small mb-0">Data de ingresso</label><input type="date" name="data_ingresso" value="<?= $h($v['data_ingresso']) ?>" class="form-control form-control-sm"></div>
                <div class="col-4"><label class="small mb-0">Situação</label><select name="situacao_id" class="form-select form-select-sm">
                  <?php foreach ($op['situacoes'] as $s) : ?><option value="<?= htmlspecialchars((string) ((int) $s['id']), ENT_QUOTES, 'UTF-8') ?>" <?= htmlspecialchars((string) ((int) $s['id'] === (int) $v['situacao_id'] ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>><?= $h($s['nome']) ?></option><?php endforeach; ?>
                </select></div>
              </div>
              <div class="row g-2 mt-1">
                <div class="col-4"><label class="small mb-0">Polo da matrícula</label><select name="polo_id" class="form-select form-select-sm">
                  <?php foreach ($op['polos'] as $pl) : ?><option value="<?= htmlspecialchars((string) ((int) $pl['id']), ENT_QUOTES, 'UTF-8') ?>" <?= htmlspecialchars((string) ((int) $pl['id'] === (int) ($v['polo_id'] ?: $v['poloCurso']) ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>><?= $h($pl['nome']) ?></option><?php endforeach; ?>
                </select></div>
                <div class="col-4"><label class="small mb-0">Modalidade da matrícula</label><select name="modalidade_id" class="form-select form-select-sm">
                  <?php foreach ($op['modalidades'] as $md) : ?><option value="<?= htmlspecialchars((string) ((int) $md['id']), ENT_QUOTES, 'UTF-8') ?>" <?= htmlspecialchars((string) ((int) $md['id'] === (int) ($v['modalidade_id'] ?: $v['modalidadeCurso']) ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>><?= $h($md['nome']) ?></option><?php endforeach; ?>
                </select></div>
                <div class="col-4"><label class="small mb-0">Conclusão <small>(só com "Concluído")</small></label><input type="date" name="data_conclusao" value="<?= $h($v['data_conclusao'] ?? '') ?>" class="form-control form-control-sm"></div>
              </div>
              <div class="row g-2 mt-1"><?php /* E1b (D-354): previsão de conclusão, opcional, com a sugestão das ofertas da grade */ ?>
                <div class="col-4"><label class="small mb-0">Previsão de conclusão <small>(opcional)</small></label><input type="date" name="previsao_conclusao" value="<?= $h($v['previsao_conclusao'] ?? '') ?>" class="form-control form-control-sm campoPrevisao"></div>
                <div class="col-8 d-flex align-items-end"><?php if (!empty($v['sugestaoConclusao'])) : ?><button type="button" class="btn btn-sm btn-outline-secondary usarSugestao" data-sugestao="<?= $h($v['sugestaoConclusao']) ?>" title="A maior data de fim das ofertas desta grade"><i class="fas fa-magic"></i> Usar a sugestão: <?= $h($dt($v['sugestaoConclusao'])) ?></button><?php else : ?><small class="text-muted">Sem sugestão: nenhuma oferta desta grade tem data de fim.</small><?php endif; ?></div>
              </div>
              <input type="text" name="observacao" value="<?= $h($v['observacao']) ?>" class="form-control form-control-sm mt-1" placeholder="Observação">
              <button type="submit" class="btn btn-sm btn-outline-warning mt-1">Salvar matrícula</button>
              <small class="text-muted d-block">A modalidade da matrícula escolhe o ato legal do histórico. "Concluído" exige todas as disciplinas da grade aprovadas<?= htmlspecialchars((string) ((int) $v['exige_tcc'] === 1 ? ' e o TCC aprovado' : ''), ENT_QUOTES, 'UTF-8') ?>.</small>
            </form>
          </div>
        <?php endif; ?>
      </div>

      <?php if (!empty($bt['tcc']) || $tcc) : ?>
        <div class="border-top mt-3 pt-2 tcc" data-matricula="<?= htmlspecialchars((string) ((int) $v['id']), ENT_QUOTES, 'UTF-8') ?>">
          <h6 class="mb-2"><b>TCC</b> <?= (int) $v['exige_tcc'] === 1 ? '<span class="badge bg-info-lt">o curso exige</span>' : '<span class="badge bg-light text-dark">opcional neste curso</span>' ?>
            <?= $tcc ? '<span class="badge bg-' . ((int) $tcc['situacao'] === 2 ? 'success' : ((int) $tcc['situacao'] === 3 ? 'danger' : 'secondary')) . '-lt">' . $h(\App\adms\Models\tcc\AdmsTcc::SITUACOES[(int) $tcc['situacao']] ?? '') . '</span>' : '' ?></h6>
          <?php if (!empty($bt['tcc'])) : ?>
            <form method="POST" action="<?= htmlspecialchars((string) (URLADM . 'tcc/salvar/' . (int) $v['id']), ENT_QUOTES, 'UTF-8') ?>">
              <div class="row g-2">
                <div class="col-md-6"><label class="small mb-0">Título</label><input type="text" name="titulo" class="form-control form-control-sm" value="<?= $h($tcc['titulo'] ?? '') ?>" maxlength="500"></div>
                <div class="col-md-3"><label class="small mb-0">Orientador</label><select name="orientador_id" class="form-select form-select-sm"><option value="">—</option>
                  <?php foreach ($op['orientadores'] as $or) : ?><option value="<?= htmlspecialchars((string) ((int) $or['id']), ENT_QUOTES, 'UTF-8') ?>" <?= htmlspecialchars((string) ((int) ($tcc['orientador_id'] ?? 0) === (int) $or['id'] ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>><?= $h($or['nome']) ?></option><?php endforeach; ?>
                </select></div>
                <div class="col-md-1"><label class="small mb-0">Nota</label><input type="text" inputmode="decimal" name="nota" class="form-control form-control-sm" value="<?= $h(isset($tcc['nota']) ? str_replace('.', ',', $tcc['nota']) : '') ?>"></div>
                <div class="col-md-2"><label class="small mb-0">Defesa/entrega</label><input type="date" name="data_defesa" class="form-control form-control-sm" value="<?= $h($tcc['data_defesa'] ?? '') ?>"></div>
              </div>
              <div class="row g-2 mt-1">
                <div class="col-md-3"><select name="situacao" class="form-select form-select-sm" aria-label="Situação do TCC">
                  <?php foreach (\App\adms\Models\tcc\AdmsTcc::SITUACOES as $k => $s) : ?><option value="<?= htmlspecialchars((string) ($k), ENT_QUOTES, 'UTF-8') ?>" <?= htmlspecialchars((string) ((int) ($tcc['situacao'] ?? 1) === $k ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>><?= $h($s) ?></option><?php endforeach; ?>
                </select></div>
                <div class="col-md-6"><input type="text" name="observacao" class="form-control form-control-sm" placeholder="Observação" value="<?= $h($tcc['observacao'] ?? '') ?>"></div>
                <div class="col-md-3"><button type="submit" class="btn btn-sm btn-outline-primary w-100">Salvar TCC</button></div>
              </div>
              <small class="text-muted">Nota de 0 a 10, até 2 casas (vírgula ou ponto). "Aprovado" exige orientador, data e nota a partir da média mínima.</small>
            </form>
          <?php else : ?>
            <p class="mb-0"><?= $h($tcc['titulo']) ?> — orientador <?= $h($tcc['orientador'] ?? '—') ?>, nota <?= $h($tcc['nota'] ?? '—') ?></p>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php endforeach; ?>

<?php if (!empty($bt['vincular'])) : ?>
  <div class="card">
    <div class="card-header"><h3 class="card-title">Vincular a um curso</h3></div>
    <form method="POST" action="<?= URLADM . 'academicos/vincular/' . $alunoId ?>">
      <div class="card-body">
        <div class="row g-2">
          <div class="mb-3 col-md-2">
            <label class="form-label" for="vAno">Ano</label>
            <select name="ano" id="vAno" class="form-select">
              <?php foreach ($op['anos'] as $a) : ?><option value="<?= (int) $a ?>"><?= (int) $a ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3 col-md-5">
            <label class="form-label" for="vModulo"><?= $semMod ? 'Curso' : (\App\adms\Models\helper\AdmsTipoInstituicao::ehEscola() ? 'Curso e série' : 'Curso e módulo') ?></label>
            <select name="modulo_id" id="vModulo" class="form-select">
              <option value="">Selecione</option>
              <?php $gradesPorCurso = []; foreach ($op['modulos'] as $m) { $gradesPorCurso[$m['curso']][(int) $m['versao']] = true; } /* Onda 7, Etapa 1: a grade só quando o curso tem mais de uma */ ?>
              <?php foreach ($op['modulos'] as $m) : $variasGrades = count($gradesPorCurso[$m['curso']] ?? []) > 1; ?>
                <option value="<?= htmlspecialchars((string) ((int) $m['id']), ENT_QUOTES, 'UTF-8') ?>" data-polo-curso="<?= $h($m['poloCurso'] ?? '') ?>"><?= $h($m['curso']) ?><?= $variasGrades ? ' (grade ' . (int) $m['versao'] . ', ' . $h(mb_strtolower((string) $m['situacaoGrade'])) . ')' : '' ?><?= $semMod ? '' : ' — ' . $h($m['codnome']) ?></option>
              <?php endforeach; ?>
            </select>
            <small class="text-muted">Grades desativadas não aparecem: não aceitam vínculo novo.</small>
          </div>
          <div class="mb-3 col-md-3">
            <label class="form-label" for="vForma">Forma de ingresso</label>
            <select name="forma_ingresso_id" id="vForma" class="form-select">
              <?php foreach ($op['formas'] as $fo) : ?><option value="<?= htmlspecialchars((string) ((int) $fo['id']), ENT_QUOTES, 'UTF-8') ?>"><?= $h($fo['nome']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3 col-md-2">
            <label class="form-label" for="vData">Data de ingresso</label>
            <input type="date" name="data_ingresso" id="vData" class="form-control" value="<?= date('Y-m-d') ?>">
          </div>
          <?php include __DIR__ . '/_vinculoPlano.php'; // Onda 8, financeiro (D-98) ?>
        </div>
        <div class="row g-2"><?php /* E1b (D-354) */ ?>
          <div class="mb-3 col-md-3">
            <label class="form-label" for="vPrevisao">Previsão de conclusão <small class="text-muted">(opcional)</small></label>
            <input type="date" name="previsao_conclusao" id="vPrevisao" class="form-control">
            <small class="text-muted">Sai no atestado como "março de 2027". Depois do vínculo, a tela sugere a maior data de fim das ofertas da grade.</small>
          </div>
        </div>
        <div class="row g-2">
          <div class="mb-3 col-md-4">
            <label class="form-label" for="vPolo">Polo da matrícula</label>
            <select name="polo_id" id="vPolo" class="form-select"><option value="" id="vPoloCurso">O do curso</option>
              <?php foreach ($op['polos'] as $pl) : ?><option value="<?= htmlspecialchars((string) ((int) $pl['id']), ENT_QUOTES, 'UTF-8') ?>"><?= $h($pl['nome']) ?></option><?php endforeach; ?>
            </select>
            <small class="text-muted">Sugestão: o polo do curso (fica gravado na matrícula).</small>
            <script>/* 1.13.0 (D-390): a opção sugerida mostra o polo do curso escolhido */
              document.getElementById('vModulo').addEventListener('change', function () {
                var p = this.options[this.selectedIndex].getAttribute('data-polo-curso') || '';
                document.getElementById('vPoloCurso').textContent = p ? 'O do curso (' + p + ')' : 'O do curso';
              });</script>
          </div>
          <div class="mb-3 col-md-4">
            <label class="form-label" for="vModalidade">Modalidade da matrícula</label>
            <select name="modalidade_id" id="vModalidade" class="form-select"><option value="">A do curso</option>
              <?php foreach ($op['modalidades'] as $md) : ?><option value="<?= htmlspecialchars((string) ((int) $md['id']), ENT_QUOTES, 'UTF-8') ?>"><?= $h($md['nome']) ?></option><?php endforeach; ?>
            </select>
            <small class="text-muted">Escolhe o ato legal do histórico (presencial: conselho estadual; EaD: MEC).</small>
          </div>
        </div>
      </div>
      <div class="card-footer"><button type="submit" class="btn btn-success">Vincular</button></div>
    </form>
  </div>
<?php endif; ?>
<script>
  /* E1b (D-354): "Usar a sugestão" preenche a previsão de conclusão do mesmo formulário */
  document.addEventListener('click', function (ev) {
    var b = ev.target.closest('.usarSugestao'); if (!b) { return; }
    var c = b.closest('form').querySelector('.campoPrevisao'); if (c) { c.value = b.getAttribute('data-sugestao'); c.focus(); }
  });
</script>
