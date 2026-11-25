<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
$h = fn($t) => htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
$grades = $this->dados['grades'] ?? [];
$grade = $this->dados['grade'] ?? null;
$modulos = $this->dados['modulos'] ?? [];
$anos = $this->dados['anos'] ?? [];
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Gerar oferta do ano<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php
      if (isset($_SESSION['msg'])) {
        echo $_SESSION['msg'];
        unset($_SESSION['msg']);
      }
      ?>
      <div class="alert alert-info">
        <p class="mb-0">Cria uma oferta por disciplina <?= !empty($this->dados['semModulos']) ? 'escolhida da grade' : 'da matriz dos módulos escolhidos' ?>, com a CH da matriz. Repetir não duplica:
          a oferta do mesmo ano, disciplina e turma que já existe é pulada. Os professores são vinculados depois, na tela de cada disciplina.</p>
      </div>
      <div class="card shadow">
        <div class="card-body">
          <form method="GET" action="<?= URLADM . 'disciplinas/gerar-oferta' ?>" class="d-flex flex-wrap align-items-center gap-2">
            <label class="me-2" for="grade">Curso e grade</label>
            <select name="grade" id="grade" class="form-select me-2">
              <option value="">Selecione</option>
              <?php foreach ($grades as $g) : ?>
                <option value="<?= htmlspecialchars((string) ((int) $g['id']), ENT_QUOTES, 'UTF-8') ?>" <?= htmlspecialchars((string) ($grade && (int) $grade['id'] === (int) $g['id'] ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>><?= $h($g['curso']) ?> — grade <?= htmlspecialchars((string) ((int) $g['versao']), ENT_QUOTES, 'UTF-8') ?> (<?= $h($g['nomeSituacao']) ?>)</option>
              <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-outline-primary">Escolher</button>
          </form>
        </div>
      </div>
      <?php if ($grade) : ?>
        <div class="card shadow">
          <form method="POST" action="<?= htmlspecialchars((string) (URLADM . 'disciplinas/gerar-oferta?grade=' . (int) $grade['id']), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="grade_id" value="<?= htmlspecialchars((string) ((int) $grade['id']), ENT_QUOTES, 'UTF-8') ?>">
            <div class="card-body">
              <?php if (!empty($this->dados['semModulos'])) : $dg = $this->dados['disciplinasGrade'] ?? []; /* E8c (D-268): a oferta é da grade */ ?>
              <div class="mb-3" id="disciplinasDaGrade">
                <label class="form-label">Disciplinas da grade</label>
                <small class="text-muted ms-2">todas vêm marcadas; desmarque as que não serão ofertadas agora</small><br>
                <?php if (!$dg) : ?><span class="text-muted">Esta grade não tem disciplinas.</span><?php endif; ?>
                <?php foreach ($dg as $d) : ?>
                  <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="linhas[]" id="lin<?= htmlspecialchars((string) ((int) $d['id']), ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars((string) ((int) $d['id']), ENT_QUOTES, 'UTF-8') ?>" checked>
                    <label class="form-check-label" for="lin<?= htmlspecialchars((string) ((int) $d['id']), ENT_QUOTES, 'UTF-8') ?>"><?= $h($d['materia']) ?> <small class="text-muted">(<?= htmlspecialchars((string) ((int) $d['cargahoraria']), ENT_QUOTES, 'UTF-8') ?> h)</small></label>
                  </div>
                <?php endforeach; ?>
              </div>
              <?php else : ?>
              <div class="mb-3">
                <label class="form-label">Módulos</label><br>
                <?php if (!$modulos) : ?><span class="text-muted">Esta grade não tem módulos.</span><?php endif; ?>
                <?php foreach ($modulos as $m) : ?>
                  <div class="form-check form-check-inline">
                    <input type="checkbox" class="form-check-input" name="modulos[]" id="mod<?= htmlspecialchars((string) ((int) $m['id']), ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars((string) ((int) $m['id']), ENT_QUOTES, 'UTF-8') ?>" checked>
                    <label class="form-check-label" for="mod<?= htmlspecialchars((string) ((int) $m['id']), ENT_QUOTES, 'UTF-8') ?>"><?= $h($m['codnome']) ?> (<?= htmlspecialchars((string) ((int) $m['disciplinas']), ENT_QUOTES, 'UTF-8') ?> disc.)</label>
                  </div>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>
              <div class="row g-2">
                <div class="mb-3 col-md-2"><label class="form-label" for="ano">Ano</label>
                  <select name="ano" id="ano" class="form-select"><?php foreach ($anos as $a) : ?><option value="<?= (int) $a ?>"><?= (int) $a ?></option><?php endforeach; ?></select></div>
                <div class="mb-3 col-md-2"><label class="form-label" for="codtur">Turma</label><input type="text" name="codtur" id="codtur" class="form-control" required></div>
                <div class="mb-3 col-md-2"><label class="form-label" for="encontros">Total de encontros</label><input type="text" inputmode="numeric" name="encontros" id="encontros" class="form-control" placeholder="ex.: 20"></div>
                <div class="mb-3 col-md-3"><label class="form-label" for="codfilial">Polo</label>
                  <select name="codfilial" id="codfilial" class="form-select">
                    <?php foreach (\App\adms\Models\polos\AdmsListarPolos::ativos() as $p) : ?><option value="<?= htmlspecialchars((string) ((int) $p['id']), ENT_QUOTES, 'UTF-8') ?>"><?= $h($p['nome']) ?></option><?php endforeach; ?>
                  </select></div>
                <div class="mb-3 col-md-3"><label class="form-label" for="modalidade_id">Modalidade</label>
                  <select name="modalidade_id" id="modalidade_id" class="form-select"><option value="">A do curso</option>
                    <?php foreach (\App\adms\Models\modalidades\AdmsModalidades::ativas() as $md) : ?><option value="<?= htmlspecialchars((string) ((int) $md['id']), ENT_QUOTES, 'UTF-8') ?>"><?= $h($md['nome']) ?></option><?php endforeach; ?>
                  </select></div>
              </div>
            </div>
            <div class="card-footer"><button type="submit" name="GerarOferta" value="1" class="btn btn-success">Gerar oferta</button></div>
          </form>
        </div>
      <?php endif; ?>
    </div>
  </section>
</div>
