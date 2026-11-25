<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* 1.16.0 (D-438 b): APLICAR A DISCIPLINAS, em lote — filtro por curso e grade, seleção múltipla e confirmação listando o
   que muda (disciplina: regra de hoje → esta). Agrupar por curso, tipo ou módulo é aplicar a mesma regra ao grupo. */
$h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$d = $this->dados;
$r = $d['regra'];
$id = (int) $r['id'];
$conf = $d['confirmar'] ?? null;
$marcadas = $d['marcadas'] ?? [];
$url = URLADM . 'regra-media/aplicar/' . $id . '?curso=' . (int) $d['curso'] . '&grade=' . (int) $d['grade'];
?>
<div class="vc-pagina">
  <?php ob_start(); ?><i class="fas fa-list-check me-2 text-info"></i>Aplicar a disciplinas <small class="text-muted"><?= $h($r['nome']) ?></small><?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <?php if (!$d['ativa']) : ?><div class="alert alert-secondary">Regra inativa: reative-a no catálogo para aplicar a disciplinas.</div><?php endif; ?>

      <form method="GET" action="<?= URLADM . 'regra-media/aplicar/' . $id ?>" class="card shadow-sm" id="filtroLote"><div class="card-body py-2 d-flex flex-wrap align-items-end">
        <div class="me-3 mb-1"><label class="small mb-0" for="fCurso">Curso</label>
          <select class="form-select form-select-sm" id="fCurso" name="curso"><option value="0">Todos</option>
            <?php $vistos = []; foreach ($d['cursosGrades'] as $cg) : if (isset($vistos[$cg['id']])) { continue; } $vistos[$cg['id']] = 1; ?>
              <option value="<?= htmlspecialchars((string) ((int) $cg['id']), ENT_QUOTES, 'UTF-8') ?>" <?= htmlspecialchars((string) ((int) $cg['id'] === (int) $d['curso'] ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>><?= $h($cg['nome']) ?></option>
            <?php endforeach; ?></select></div>
        <div class="me-3 mb-1"><label class="small mb-0" for="fGrade">Grade</label>
          <select class="form-select form-select-sm" id="fGrade" name="grade"><option value="0">Todas</option>
            <?php foreach ($d['cursosGrades'] as $cg) : if ((int) $d['curso'] && (int) $cg['id'] !== (int) $d['curso']) { continue; } ?>
              <option value="<?= htmlspecialchars((string) ((int) $cg['grade_id']), ENT_QUOTES, 'UTF-8') ?>" <?= htmlspecialchars((string) ((int) $cg['grade_id'] === (int) $d['grade'] ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>><?= $h($cg['nome']) ?> — grade <?= htmlspecialchars((string) ((int) $cg['versao']), ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?></select></div>
        <button type="submit" class="btn btn-sm btn-outline-primary mb-1" id="filtrar"><i class="fas fa-filter"></i> Filtrar</button>
      </div></form>

      <form method="POST" action="<?= $h($url) ?>" id="formLote">
        <input type="hidden" name="acao" value="aplicar">
        <?php if ($conf) : ?>
          <input type="hidden" name="confirmado" value="1">
          <div class="card shadow" id="confirmarLote">
            <div class="card-header"><b>Confirme: <?= htmlspecialchars((string) (count($conf['linhas'])), ENT_QUOTES, 'UTF-8') ?> disciplina(s) passam a usar "<?= $h($conf['regra']) ?>"</b></div>
            <div class="card-body small"><ul class="mb-2" id="listaMuda"><?php foreach ($conf['linhas'] as $l) : ?><li><?= $h($l['texto']) ?>: <?= $h($l['de']) ?> → <b><?= $h($l['para']) ?></b></li><?php endforeach; ?></ul>
              <p class="mb-0">As ofertas abertas dessas disciplinas passam a calcular pela regra nova (menos as que trocaram a regra na própria oferta). O que já foi encerrado não muda.</p></div>
          </div>
        <?php endif; ?>
        <div class="card shadow">
          <div class="card-body table-responsive p-0">
            <table class="table table-sm table-hover mb-0" id="tabelaLote">
              <thead><tr><th style="width:1%"><input type="checkbox" id="marcarTodas" title="Marcar todas" aria-label="Marcar todas"></th><th>Disciplina</th><th>Curso</th><th>Grade</th><th>Regra de hoje</th></tr></thead>
              <tbody>
              <?php foreach ($d['disciplinas'] as $x) : $ja = (int) ($x['regra_media_id'] ?? 0) === $id; ?>
                <tr data-linha="<?= htmlspecialchars((string) ((int) $x['id']), ENT_QUOTES, 'UTF-8') ?>">
                  <td><input type="checkbox" class="marcaLinha" name="linhas[]" value="<?= htmlspecialchars((string) ((int) $x['id']), ENT_QUOTES, 'UTF-8') ?>" <?= htmlspecialchars((string) (in_array((int) $x['id'], $marcadas, true) ? 'checked' : ''), ENT_QUOTES, 'UTF-8') ?> <?= $conf ? 'onclick="return false"' : '' ?> aria-label="Escolher"></td>
                  <td><?= $h($x['materia']) ?></td><td><?= $h($x['curso']) ?></td><td><?= htmlspecialchars((string) ((int) $x['grade']), ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= $ja ? '<b>esta regra</b>' : $h($x['regra'] ?: 'Padrão da instituição') ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$d['disciplinas']) : ?><tr><td colspan="5" class="text-muted">Nenhuma disciplina com este filtro.</td></tr><?php endif; ?>
              </tbody>
            </table>
          </div>
          <div class="card-footer">
            <?php if ($d['ativa']) : ?>
              <button type="submit" class="btn <?= $conf ? 'btn-warning' : 'btn-primary' ?>" id="aplicarBtn"><i class="fas fa-check"></i> <?= $conf ? 'Confirmar e aplicar' : 'Ver o que muda' ?></button>
              <?php if ($conf) : ?><a href="<?= $h($url) ?>" class="btn btn-outline-secondary ms-2">Cancelar</a><?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
      </form>
    </div>
  </section>
</div>
<script>
(function () {
  var t = document.getElementById('marcarTodas');
  if (t) t.addEventListener('change', function () { document.querySelectorAll('.marcaLinha').forEach(function (c) { c.checked = t.checked; }); });
})();
</script>
