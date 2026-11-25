<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* E3b: a oferta nasce da LINHA da matriz (curso, grade, módulo, disciplina e CH vêm dela),
   com turma, polo, modalidade (vazio = a do curso) e id do Moodle. Incluído por
   cadDisciplinas.php e editDisciplinas.php; usa $valorForm. */
$h = fn($t) => htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
$linhaSel = (int) ($valorForm['matriz_id'] ?? 0);
?>
<div class="row g-2">
  <div class="mb-3 col-md-12">
    <?php $semMod = \App\adms\Models\helper\AdmsModuloInterno::ativo(); /* E8c (D-268): sem módulo no rótulo */ ?>
    <label class="form-label" for="matriz_id">Disciplina da matriz (curso — grade — <?= $semMod ? '' : 'módulo — ' ?>disciplina)</label>
    <select name="matriz_id" id="matriz_id" class="form-select select2" <?= !empty($travaLinha) ? 'disabled' : '' ?>>
      <option value="">Escolha na matriz</option>
      <?php foreach (\App\adms\Models\disciplinas\AdmsOfertaMatriz::linhas($linhaSel) as $l) : ?>
        <option value="<?= htmlspecialchars((string) ((int) $l['id']), ENT_QUOTES, 'UTF-8') ?>" <?= htmlspecialchars((string) ((int) $l['id'] === $linhaSel ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>><?= $h($l['curso']) ?> — grade <?= htmlspecialchars((string) ((int) $l['versao']), ENT_QUOTES, 'UTF-8') ?> — <?= $semMod ? '' : $h($l['modulo']) . ' — ' ?><?= $h($l['materia']) ?> (<?= htmlspecialchars((string) ((int) $l['cargahoraria']), ENT_QUOTES, 'UTF-8') ?> h)</option>
      <?php endforeach; ?>
    </select>
    <?php if (!empty($travaLinha)) : ?>
      <input type="hidden" name="matriz_id" value="<?= htmlspecialchars((string) ($linhaSel), ENT_QUOTES, 'UTF-8') ?>">
      <small class="form-text text-muted">A disciplina da matriz não muda: a oferta já tem alunos ou atividades.</small>
    <?php endif; ?>
  </div>
</div>
<div class="row g-2">
  <div class="mb-3 col-md-2">
    <label class="form-label" for="encontros">Total de encontros</label>
    <?php $encPorChamada = !empty($valorForm['id']) && \App\adms\Models\faltas\AdmsChamada::fonte((int) $valorForm['id']) === 'chamada'; /* 1.14.0 (D-405) */ ?>
    <input type="text" inputmode="numeric" class="form-control" name="encontros" id="encontros" value="<?= $h($valorForm['encontros'] ?? '') ?>" placeholder="ex.: 20" <?= $encPorChamada ? 'readonly' : '' ?>>
    <?php if ($encPorChamada) : ?><small class="form-text text-muted">Vem da chamada (encontros registrados).</small><?php endif; ?>
  </div>
  <?php if ($encPorChamada) : /* 1.17.2 (D-462): os previstos, só na oferta por chamada */ ?>
    <div class="mb-3 col-md-2">
      <label class="form-label" for="encontros_previstos">Encontros previstos</label>
      <input type="text" inputmode="numeric" class="form-control" name="encontros_previstos" id="encontros_previstos" value="<?= $h($valorForm['encontros_previstos'] ?? '') ?>" placeholder="opcional">
      <small class="form-text text-muted">Base dos relatórios de frequência e do alerta de risco. A frequência do boletim continua sobre os registrados.</small>
    </div>
  <?php endif; ?>
  <div class="mb-3 col-md-2">
    <label class="form-label" for="codtur">Turma</label>
    <input type="text" class="form-control" name="codtur" id="codtur" placeholder="Turma" value="<?= $h($valorForm['codtur'] ?? '') ?>" required>
  </div>
  <div class="mb-3 col-md-3">
    <label class="form-label" for="codfilial">Polo</label>
    <select name="codfilial" id="codfilial" class="form-select">
      <?php foreach (\App\adms\Models\polos\AdmsListarPolos::ativos() as $p) : ?>
        <option value="<?= htmlspecialchars((string) ((int) $p['id']), ENT_QUOTES, 'UTF-8') ?>" <?= htmlspecialchars((string) ((int) ($valorForm['codfilial'] ?? 0) === (int) $p['id'] ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>><?= $h($p['nome']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="mb-3 col-md-3">
    <label class="form-label" for="modalidade_id">Modalidade</label>
    <select name="modalidade_id" id="modalidade_id" class="form-select">
      <option value="">A do curso</option>
      <?php foreach (\App\adms\Models\modalidades\AdmsModalidades::ativas() as $m) : ?>
        <option value="<?= htmlspecialchars((string) ((int) $m['id']), ENT_QUOTES, 'UTF-8') ?>" <?= htmlspecialchars((string) ((int) ($valorForm['modalidade_id'] ?? 0) === (int) $m['id'] ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>><?= $h($m['nome']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="mb-3 col-md-2">
    <label class="form-label" for="data_inicio">Início <small class="text-muted">(datas de realização)</small></label>
    <input type="date" class="form-control" name="data_inicio" id="data_inicio" value="<?= $h($valorForm['data_inicio'] ?? '') ?>">
  </div>
  <div class="mb-3 col-md-2">
    <label class="form-label" for="data_fim">Fim</label>
    <input type="date" class="form-control" name="data_fim" id="data_fim" value="<?= $h($valorForm['data_fim'] ?? '') ?>">
  </div>
</div>
