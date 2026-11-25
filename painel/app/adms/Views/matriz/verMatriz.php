<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
use App\adms\Models\helper\AdmsUi as Ui;

$m = $this->dados['matriz'];
$g = $m['grade'];
$modulos = $m['modulos'];
/* E8c (D-268): sem módulos, a tela mostra UMA lista com as disciplinas da grade (o módulo interno nunca aparece) */
$semMod = !empty($m['semModulos']);
if ($semMod) {
  $modulos = [['id' => 0, 'codnome' => 'Disciplinas da grade', 'ordem' => 0, 'situacao' => 1, 'ch' => (int) $m['chGrade'], 'disciplinas' => $m['disciplinas']]];
}
$editavel = $m['editavel'];
$botao = $this->dados['botao'] ?? [];
$situacoes = $this->dados['situacoes'] ?? [];
$e = fn($t) => htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
$gid = (int) $g['id'];
$ehAdmin = \App\adms\Models\matriz\AdmsMatrizRegras::ehAdmin();
/* E8c (R4): com a grade travada, as ações aparecem DESABILITADAS com a razão (não somem) */
/* E1b (D-353): arrastar para ordenar só numa grade Aberta e com a permissão da rota 415 */
$ordenar = $editavel && !empty($this->dados['botao']['ord_disc']);
$travada = $editavel ? null : 'Grade «' . $g['nomeSituacao'] . '»: só uma grade Aberta muda';
?>
<div class="vc-pagina">
  <?php ob_start(); ?><a href="<?= htmlspecialchars((string) (URLADM . 'matriz/listar#curso-' . (int) $g['curso_id']), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary rounded-circle" title="Voltar"><i class="fas fa-arrow-left"></i></a>
            <?= $e($g['curso']) ?> — grade <?= htmlspecialchars((string) ((int) $g['versao']), ENT_QUOTES, 'UTF-8') ?><?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>

  <section class="page-body">
    <div class="container-xl">
      <?php
      if (isset($_SESSION['msg'])) {
        echo $_SESSION['msg'];
        unset($_SESSION['msg']);
      }
      ?>
      <div class="row">
        <div class="col-md-4">
          <div class="card card-sm vc-indicador shadow-sm">
            <span class="vc-indicador-icone bg-info"><i class="fas fa-layer-group"></i></span>
            <div class="vc-indicador-conteudo">
              <span class="vc-indicador-texto"><?= $semMod ? 'Disciplinas' : 'Módulos' ?></span>
              <span class="vc-indicador-numero" id="<?= $semMod ? 'qtdDisciplinas' : 'qtdModulos' ?>"><?= htmlspecialchars((string) ($semMod ? count($m['disciplinas']) : count($modulos)), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card card-sm vc-indicador shadow-sm">
            <span class="vc-indicador-icone bg-success"><i class="far fa-clock"></i></span>
            <div class="vc-indicador-conteudo">
              <span class="vc-indicador-texto">Carga horária da grade</span>
              <span class="vc-indicador-numero" id="chGrade"><?= htmlspecialchars((string) ((int) $m['chGrade']), ENT_QUOTES, 'UTF-8') ?> h</span>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card card-sm vc-indicador shadow-sm">
            <span class="vc-indicador-icone bg-<?= $e($g['corSituacao'] ?: 'secondary') ?>"><i class="fas fa-flag"></i></span>
            <div class="vc-indicador-conteudo">
              <span class="vc-indicador-texto">Situação</span>
              <span class="vc-indicador-numero" id="situacaoGrade"><?= $e($g['nomeSituacao']) ?></span>
            </div>
          </div>
        </div>
      </div>

      <?php if (!$editavel) : /* E8c (A5 + R6): por que está travada e como destravar */ ?>
        <?= Ui::faixaTrava('A grade está «' . $g['nomeSituacao'] . '»: ' . ($semMod ? 'a matriz (disciplinas e cargas horárias)' : 'a matriz (módulos, disciplinas e cargas horárias)') . ' só muda com a grade Aberta.',
          $ehAdmin ? 'Para mudar, volte a grade para «Aberta» no quadro «Situação da grade», abaixo' . (in_array((int) $g['situacao'], [3, 4], true) ? ', com justificativa.' : '.') : 'Para mudar, peça ao administrador que volte a grade para «Aberta».',
          ($ehAdmin && !empty($botao['sit_grade']) && $situacoes) ? ['Ir para a situação da grade', '#quadroSituacao'] : null) ?>
      <?php endif; ?>
      <?php if ($g['descricao'] !== null && $g['descricao'] !== '') : ?>
        <p class="text-muted"><?= $e($g['descricao']) ?></p>
      <?php endif; ?>

      <div class="mb-3 acoes-linha">
        <?php if (!$semMod && !empty($botao['cad_mod'])) : ?>
          <?= Ui::acao('novo', URLADM . 'modulos/cadastrar?grade=' . $gid, 'Incluir um módulo nesta grade', $travada, ['rotulo' => 'Incluir módulo']) ?>
        <?php endif; ?>
        <?php if (!empty($botao['gerar_oferta'])) : ?>
          <?= Ui::acao('acao', URLADM . 'disciplinas/gerar-oferta?grade=' . $gid, 'Gerar a oferta do ano a partir desta grade',
            (int) $g['situacao'] === 4 ? 'Grade Desativada: não gera oferta' : ($semMod ? (!$m['disciplinas'] ? 'A grade ainda não tem disciplinas' : null) : (!$modulos ? 'A grade ainda não tem módulos' : null)),
            ['rotulo' => 'Gerar oferta do ano', 'icone' => 'fas fa-calendar-plus', 'classe' => 'btn-outline-success']) ?>
        <?php endif; ?>
        <?php if (!empty($botao['edit_grade'])) : ?>
          <?= Ui::acao('editar', URLADM . 'matriz/editar-grade/' . $gid, 'Editar número e descrição da grade', $travada, ['rotulo' => 'Editar grade']) ?>
        <?php endif; ?>
      </div>

      <?php if (!$semMod && !$modulos) : ?>
        <div class="alert alert-info">Esta grade ainda não tem módulos.</div>
      <?php endif; ?>

      <?php foreach ($modulos as $mod) : $nDisc = count($mod['disciplinas']); ?>
        <div class="card shadow modulo" data-modulo="<?= htmlspecialchars((string) ((int) $mod['id']), ENT_QUOTES, 'UTF-8') ?>">
          <div class="card-header">
            <h3 class="card-title">
              <?php if ($semMod) : ?>
                Disciplinas da grade
              <?php else : ?>
              <b><?= htmlspecialchars((string) ((int) $mod['ordem']), ENT_QUOTES, 'UTF-8') ?>.</b> <?= $e($mod['codnome']) ?>
              <?= (int) $mod['situacao'] === 2 ? '<span class="badge bg-secondary-lt ms-1">Inativo</span>' : '' ?>
              <?php endif; ?>
            </h3>
            <div class="card-actions acoes-linha">
              <?php if ($semMod) : ?>
                <span class="badge bg-success-lt me-2" id="chListaGrade">CH da grade: <?= htmlspecialchars((string) ((int) $mod['ch']), ENT_QUOTES, 'UTF-8') ?> h</span>
              <?php else : ?>
              <span class="badge bg-success-lt me-2 chModulo">CH do módulo: <?= htmlspecialchars((string) ((int) $mod['ch']), ENT_QUOTES, 'UTF-8') ?> h</span>
              <?php endif; ?>
              <?php if (!$semMod && !empty($botao['edit_mod'])) : ?>
                <?= Ui::acao('editar', URLADM . 'modulos/editar/' . (int) $mod['id'], 'Editar o módulo', $travada) ?>
              <?php endif; ?>
              <?php if (!$semMod && !empty($botao['del_mod'])) : ?>
                <?= Ui::acaoPost('excluir', URLADM . 'modulos/apagar/' . (int) $mod['id'], [], 'Apagar o módulo',
                  $travada ?? ($nDisc ? "Tem {$nDisc} disciplina(s): remova-as antes de apagar o módulo" : null),
                  ['confirmar' => 'Apagar o módulo ' . $mod['codnome'] . '? Só módulo sem disciplina, sem matrícula e sem oferta pode ser apagado.']) ?>
              <?php endif; ?>
            </div>
          </div>
          <div class="card-body table-responsive p-0">
            <table class="table table-sm table-compacta mb-0">
              <thead>
                <tr><th class="num" style="width:1%" title="Ordem nos documentos">#</th><th>Disciplina</th><th class="num" style="width:90px">CH</th><th style="width:1%" title="Vazio = Padrão da instituição">Regra de média</th><th class="text-end" style="width:1%">Ações</th></tr>
              </thead>
              <tbody<?= $ordenar ? ' class="ordenavel"' : '' ?>>
                <?php if (!$mod['disciplinas']) : ?>
                  <tr><td colspan="5" class="text-muted"><?= $semMod ? 'Nenhuma disciplina nesta grade.' : 'Nenhuma disciplina neste módulo.' ?></td></tr>
                <?php endif; ?>
                <?php foreach ($mod['disciplinas'] as $d) : $of = (int) $d['ofertas']; $lid = (int) $d['id']; ?>
                  <tr class="linhaMatriz" data-linha="<?= htmlspecialchars((string) ($lid), ENT_QUOTES, 'UTF-8') ?>">
                    <td class="num text-nowrap<?= $ordenar ? ' alca-ordem' : '' ?>"<?= $ordenar ? ' title="Arraste para mudar a ordem" style="cursor:move"' : '' ?>><?= $ordenar ? '<i class="fas fa-grip-vertical text-muted me-1" aria-hidden="true"></i>' : '' ?><span class="num-ordem"><?= htmlspecialchars((string) ((int) $d['ordem']), ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td><?= $e($d['materia']) ?> <small class="text-muted"><?= $e($d['abreviacao']) ?></small><?= (int) ($d['situacaoMateria'] ?? 1) === 2 ? ' <span class="badge bg-secondary-lt" title="Matéria inativa: não entra em oferta nova">Inativa</span>' : '' /* E8d (P-8) */ ?>
                      <?= $of > 0 ? '<span class="badge bg-light text-dark" title="Ofertas que usam esta disciplina: ela não sai da matriz' . ($semMod ? '' : ' nem muda de módulo') . '">' . $of . ' oferta(s)</span>' : '' ?>
                    </td>
                    <td class="num"><?= htmlspecialchars((string) ((int) $d['cargahoraria']), ENT_QUOTES, 'UTF-8') ?> h</td>
                    <td class="text-nowrap regra-disciplina"><?php /* 1.16.0 (D-438 a): a regra de média da disciplina; vazio = o padrão. Vale também com a grade finalizada (não muda o currículo). */
                      $regrasM = $this->dados['regrasMedia'] ?? []; $rAtual = (int) ($d['regra_media_id'] ?? 0); $nomeAtual = 'Padrão da instituição';
                      foreach ($regrasM as $rm) { if ($rm['id'] === $rAtual) { $nomeAtual = $rm['nome']; } } ?>
                      <?php if (!empty($botao['regra_disc'])) : ?>
                        <form method="POST" action="<?= URLADM . 'regra-media/disciplina/' . $lid ?>" class="d-inline-flex align-items-center formRegraDisciplina">
                          <select name="regra_media_id" class="form-select form-select-sm me-1" aria-label="Regra de média" title="Regra de média da disciplina">
                            <option value="">Padrão da instituição</option>
                            <?php foreach ($regrasM as $rm) : if ($rm['padrao']) { continue; } ?><option value="<?= htmlspecialchars((string) ((int) $rm['id']), ENT_QUOTES, 'UTF-8') ?>" <?= htmlspecialchars((string) ($rm['id'] === $rAtual ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>><?= $e($rm['nome']) ?></option><?php endforeach; ?>
                          </select>
                          <button type="submit" class="btn btn-sm btn-acao btn-outline-primary" title="Salvar a regra da disciplina" aria-label="Salvar a regra da disciplina"><i class="fas fa-calculator"></i></button>
                        </form>
                      <?php else : ?><small><?= $e($rAtual ? $nomeAtual : 'Padrão da instituição') ?></small><?php endif; ?>
                    </td>
                    <td class="acoes-linha text-end">
                      <?php if (!empty($botao['mov_disc'])) : ?>
                        <?php if ($travada === null) : /* E8c (A6/A7): com oferta, só a CH muda; o módulo fica travado com a razão */ ?>
                          <form method="POST" action="<?= URLADM . 'matriz/mover-disciplina/' . $lid ?>" class="d-inline-flex align-items-center formLinhaMatriz">
                            <?php if ($semMod) : /* sem módulos: só a CH */ ?>
                            <?php elseif ($of > 0) : ?>
                              <input type="hidden" name="modulo_id" value="<?= htmlspecialchars((string) ((int) $d['modulo_id']), ENT_QUOTES, 'UTF-8') ?>">
                              <span class="acao-desabilitada me-1" tabindex="0" data-bs-toggle="tooltip" title="Usada por <?= htmlspecialchars((string) ($of), ENT_QUOTES, 'UTF-8') ?> oferta(s): não muda de módulo (só a CH)">
                                <select class="form-select form-select-sm" disabled aria-label="Módulo">
                                  <option><?= $e($mod['codnome']) ?></option>
                                </select>
                              </span>
                            <?php else : ?>
                              <select name="modulo_id" class="form-select form-select-sm me-1" aria-label="Módulo" title="Módulo da disciplina">
                                <?php foreach ($modulos as $op) : ?>
                                  <option value="<?= htmlspecialchars((string) ((int) $op['id']), ENT_QUOTES, 'UTF-8') ?>" <?= htmlspecialchars((string) ((int) $op['id'] === (int) $d['modulo_id'] ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>><?= $e($op['codnome']) ?></option>
                                <?php endforeach; ?>
                              </select>
                            <?php endif; ?>
                            <input type="number" name="cargahoraria" min="1" max="9999" value="<?= htmlspecialchars((string) ((int) $d['cargahoraria']), ENT_QUOTES, 'UTF-8') ?>" class="form-control form-control-sm me-1" style="width:80px" aria-label="CH" title="Carga horária (h)">
                            <button type="submit" class="btn btn-sm btn-acao btn-outline-primary" title="<?= $semMod ? 'Salvar a CH' : 'Salvar módulo e CH' ?>" aria-label="<?= $semMod ? 'Salvar a CH' : 'Salvar módulo e CH' ?>"><i class="fas fa-save"></i></button>
                          </form>
                        <?php else : ?>
                          <?= Ui::acao('acao', '#', 'Salvar módulo e CH', $travada, ['icone' => 'fas fa-save', 'classe' => 'btn-outline-primary']) ?>
                        <?php endif; ?>
                      <?php endif; ?>
                      <?php if (!empty($botao['rem_disc'])) : ?>
                        <?= Ui::acaoPost('excluir', URLADM . 'matriz/remover-disciplina/' . $lid, [], 'Remover da matriz',
                          $travada ?? ($of > 0 ? "Usada por {$of} oferta(s): não pode sair da matriz" : null),
                          ['confirmar' => 'Remover ' . $d['materia'] . ' da matriz?']) ?>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endforeach; ?>

      <?php if ($ordenar && $modulos) : /* E1b (D-353): a ordem arrastada vai num POST só */ ?>
        <form method="POST" action="<?= htmlspecialchars((string) (URLADM . 'matriz/ordenar-disciplinas/' . (int) $g['id']), ENT_QUOTES, 'UTF-8') ?>" id="formOrdem" class="mb-3 d-flex align-items-center">
          <input type="hidden" name="ordem" id="ordemDisciplinas" value="">
          <button type="submit" class="btn btn-sm btn-primary" id="btnSalvarOrdem" disabled><i class="fas fa-sort-amount-down"></i> Salvar a ordem</button>
          <small class="text-muted ms-2" id="avisoOrdem">Arraste as disciplinas pela alça <i class="fas fa-grip-vertical"></i> para a ordem do currículo. É a ordem do histórico.</small>
        </form>
      <?php endif; ?>

      <?php if ($editavel && $modulos && !empty($botao['add_disc'])) : ?>
        <div class="card shadow">
          <div class="card-header"><h3 class="card-title">Incluir disciplina</h3></div>
          <form method="POST" action="<?= URLADM . 'matriz/adicionar-disciplina/' . $gid ?>">
            <div class="card-body">
              <div class="row g-2">
                <?php if (!$semMod) : ?>
                <div class="mb-3 col-md-4">
                  <label class="form-label" for="modulo_id">Módulo</label>
                  <select name="modulo_id" id="modulo_id" class="form-select" required>
                    <?php foreach ($modulos as $op) : ?>
                      <option value="<?= htmlspecialchars((string) ((int) $op['id']), ENT_QUOTES, 'UTF-8') ?>"><?= $e($op['codnome']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <?php endif; ?>
                <div class="mb-3 col-md-5">
                  <label class="form-label" for="materia_id">Disciplina (catálogo)</label>
                  <select name="materia_id" id="materia_id" class="form-select" required>
                    <option value="">Selecione</option>
                    <?php foreach ($m['materias'] as $mt) : ?>
                      <option value="<?= htmlspecialchars((string) ((int) $mt['id']), ENT_QUOTES, 'UTF-8') ?>"><?= $e($mt['nome']) ?> (<?= $e($mt['carga_horaria']) ?> h)</option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="mb-3 col-md-3">
                  <label class="form-label" for="cargahoraria">CH (vazio = a do catálogo)</label>
                  <input type="number" min="1" max="9999" name="cargahoraria" id="cargahoraria" class="form-control">
                </div>
              </div>
            </div>
            <div class="card-footer"><button type="submit" class="btn btn-sm btn-success"><i class="fas fa-plus"></i> Incluir</button></div>
          </form>
        </div>
      <?php endif; ?>

      <div class="row">
        <?php if (!empty($botao['sit_grade']) && $situacoes) : ?>
          <div class="col-lg-6">
            <div class="card shadow" id="quadroSituacao">
              <div class="card-header"><h3 class="card-title">Situação da grade</h3></div>
              <form method="POST" action="<?= URLADM . 'matriz/situacao-grade/' . $gid ?>">
                <div class="card-body">
                  <div class="mb-3">
                    <label class="form-label" for="situacao">Nova situação</label>
                    <select name="situacao" id="situacao" class="form-select">
                      <?php foreach ($situacoes as $s) : ?>
                        <option value="<?= htmlspecialchars((string) ((int) $s['id']), ENT_QUOTES, 'UTF-8') ?>"><?= $e($s['nome']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="mb-3">
                    <?php /* E8c (D-266): "Observação" virou "Justificativa"; obrigatória só para reabrir uma grade Finalizada ou Desativada */ ?>
                    <label class="form-label" for="observacao">Justificativa <small class="text-muted"><?= htmlspecialchars((string) (in_array((int) $g['situacao'], [3, 4], true) ? '(obrigatória para reabrir)' : '(opcional)'), ENT_QUOTES, 'UTF-8') ?> — fica no registro de alterações</small></label>
                    <textarea name="observacao" id="observacao" class="form-control" rows="2"></textarea>
                  </div>
                </div>
                <div class="card-footer"><button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-flag"></i> Alterar situação</button></div>
              </form>
            </div>
          </div>
        <?php endif; ?>
        <div class="col-lg-6">
          <div class="card shadow" id="registroAlteracoes">
            <div class="card-header"><h3 class="card-title">Registro de alterações da grade</h3></div>
            <div class="card-body p-0">
              <ul class="list-group list-group-flush">
                <?php if (!$m['historico']) : ?>
                  <li class="list-group-item text-muted">Sem registros.</li>
                <?php endif; ?>
                <?php foreach ($m['historico'] as $h) : ?>
                  <li class="list-group-item">
                    <b><?= $e($h['situacao']) ?></b> — <?= $e(date('d/m/Y H:i', strtotime($h['created']))) ?>
                    <?= $h['usuario'] ? '— ' . $e($h['usuario']) : '' ?><br>
                    <small><?= ($h['observacao'] ?? '') !== '' ? 'Justificativa: ' . $e($h['observacao']) : '<span class="text-muted">sem justificativa</span>' ?></small>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
