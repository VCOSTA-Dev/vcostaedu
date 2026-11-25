<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Pagina nao encontrada!");
}
/*
 * O HORÁRIO DA SEMANA (Onda 8, correção, D-71 e D-83), no portal do aluno e no do responsável: um cartão por dia (de
 * segunda a sexta, e o sábado quando há aula nele), com as aulas pela hora — o componente e o professor —, e o filtro
 * "Hoje" / "A semana" / um dia. Abre em "Hoje" quando hoje tem aula; senão, na semana. Recebe $semanaAulas: as linhas
 * de AdmsHorarioAluno::listarHorarioPorRmAno.
 */
$semanaAulas = $semanaAulas ?? [];
$hs = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$diasSemana = [2 => 'Segunda', 3 => 'Terça', 4 => 'Quarta', 5 => 'Quinta', 6 => 'Sexta', 7 => 'Sábado'];
$porDia = [];
foreach ($semanaAulas as $x) {
  $dia = (int) ($x['dia_id'] ?? 0);
  if (isset($diasSemana[$dia])) {
    $porDia[$dia][] = $x;
  }
}
if (empty($porDia[7])) { unset($diasSemana[7]); }
foreach ($porDia as &$lista) { usort($lista, fn($a, $b) => strcmp((string) $a['inicio'], (string) $b['inicio'])); }
unset($lista);
$hojeSemana = (int) date('N') + 1; // date('N'): 1 = segunda → 2 na tabela dos dias
$inicial = !empty($porDia[$hojeSemana]) ? 'hoje' : 'semana';
?>
<div id="semanaHorario" data-hoje="<?= $hs($hojeSemana) ?>">
  <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <label for="filtroSemana" class="mb-0"><strong>Mostrar</strong></label>
    <select class="form-select form-select-sm w-auto" id="filtroSemana">
      <option value="hoje"<?= $inicial === 'hoje' ? ' selected' : '' ?>>Hoje</option>
      <option value="semana"<?= $inicial === 'semana' ? ' selected' : '' ?>>A semana</option>
      <?php foreach ($diasSemana as $di => $nd) : ?><option value="<?= $hs($di) ?>"><?= $hs($nd) ?></option><?php endforeach; ?>
    </select>
    <span class="text-secondary small" id="semAulaHoje"<?= $inicial === 'hoje' ? ' hidden' : '' ?>>Hoje não tem aula no horário.</span>
  </div>
  <div class="row row-cards">
    <?php foreach ($diasSemana as $di => $nd) : $ehHoje = $di === $hojeSemana; ?>
      <div class="col-12 col-md-6 col-xl-4 vc-dia-semana" data-dia="<?= $hs($di) ?>"<?= $inicial === 'hoje' && !$ehHoje ? ' hidden' : '' ?>>
        <div class="card h-100<?= $ehHoje ? ' border-primary' : '' ?>">
          <div class="card-header<?= $ehHoje ? ' bg-primary-lt' : '' ?>"><h3 class="card-title"><?= $hs($nd) ?><?= $ehHoje ? ' <span class="badge bg-primary text-white ms-1">hoje</span>' : '' ?></h3></div>
          <?php if (empty($porDia[$di])) : ?>
            <div class="card-body text-secondary">Sem aula.</div>
          <?php else : ?>
            <div class="list-group list-group-flush">
              <?php foreach ($porDia[$di] as $x) : ?>
                <div class="list-group-item vc-aula-semana">
                  <div class="text-secondary small"><?= $hs(substr((string) ($x['inicio'] ?? ''), 0, 5) . '–' . substr((string) ($x['termino'] ?? ''), 0, 5)) ?></div>
                  <strong><?= $hs($x['materia'] ?? '') ?></strong>
                  <?php if (!empty($x['professores'])) : ?><div class="small text-secondary"><?= $hs($x['professores']) ?></div><?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<script>
(function () {
  var f = document.getElementById('filtroSemana');
  var raiz = document.getElementById('semanaHorario');
  if (!f || !raiz) { return; }
  f.addEventListener('change', function () {
    var v = f.value, hoje = raiz.dataset.hoje, temHoje = !!raiz.querySelector('.vc-dia-semana[data-dia="' + hoje + '"] .vc-aula-semana');
    raiz.querySelectorAll('.vc-dia-semana').forEach(function (c) {
      c.hidden = !(v === 'semana' || (v === 'hoje' ? c.dataset.dia === hoje : c.dataset.dia === v));
    });
    document.getElementById('semAulaHoje').hidden = !(v === 'hoje' && !temHoje);
  });
})();
</script>
