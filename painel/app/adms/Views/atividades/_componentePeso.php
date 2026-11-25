<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* 1.13.0 (D-394): COMPONENTE da regra de média que vale para a oferta e PESO da atividade. O peso só aparece quando
   o modo do componente escolhido usa peso (ponderada); fica 10 nos outros. Variáveis: $ofertaCp, $compAtual, $pesoAtual
   e, na edição, $atividadeCp (o id da atividade).
   E10 (1.15.0, D-423): componente com MÁXIMO mostra "(n/máx)"; cheio, fica desabilitado — menos o componente em que a
   atividade editada já está (editar sem trocar não conta). O servidor recusa do mesmo jeito (AdmsRegraMedia::validarAtividade). */
$comps = \App\adms\Models\notas\AdmsRegraMedia::componentesDaOferta((int) $ofertaCp);
$contCp = \App\adms\Models\notas\AdmsRegraMedia::contagemOferta((int) $ofertaCp, array_column($comps, 'codigo'));
$hcp = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$edicaoCp = (int) ($atividadeCp ?? 0) > 0;
$proprioCp = $edicaoCp ? ((string) ($compAtual ?? '') ?: (string) ($comps[0]['codigo'] ?? '')) : null;
$cheioCp = [];
foreach ($comps as $c) {
  $mx = \Core\MotorMedia::maximo($c);
  $cheioCp[$c['codigo']] = \App\adms\Models\notas\AdmsRegraMedia::componenteCheio($c, (int) ($contCp[$c['codigo']] ?? 0), $c['codigo'] === $proprioCp); // 1.15.2: a trava única (D-430)
}
$compAtual = (string) ($compAtual ?? '') ?: (string) ($comps[0]['codigo'] ?? '');
if (!empty($cheioCp[$compAtual])) { /* no cadastro, o padrão cheio cede ao primeiro que ainda aceita */
  foreach ($comps as $c) {
    if (empty($cheioCp[$c['codigo']])) { $compAtual = $c['codigo']; break; }
  }
}
$pesoAtual = str_replace('.', ',', \App\adms\Models\notas\AdmsRegraMedia::pesoTexto((string) ($pesoAtual ?? '10')));
?>
<div class="row g-2" id="componentePeso">
  <div class="mb-3 col-md-6">
    <label class="form-label" for="componente_codigo">Componente da média</label>
    <select name="componente_codigo" id="componente_codigo" class="form-select">
      <?php foreach ($comps as $c) : $mx = \Core\MotorMedia::maximo($c); ?>
        <option value="<?= $hcp($c['codigo']) ?>" data-usa-peso="<?= htmlspecialchars((string) (in_array($c['modo'], \Core\MotorMedia::MODOS_COM_PESO, true) ? '1' : '0'), ENT_QUOTES, 'UTF-8') ?>" <?= htmlspecialchars((string) ($c['codigo'] === $compAtual ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string) (!empty($cheioCp[$c['codigo']]) ? 'disabled' : ''), ENT_QUOTES, 'UTF-8') ?>><?= $hcp($c['nome']) ?> (peso <?= $hcp(str_replace('.', ',', $c['peso'])) ?>)<?= htmlspecialchars((string) ($mx !== null ? ' (' . (int) ($contCp[$c['codigo']] ?? 0) . '/' . $mx . ')' : ''), ENT_QUOTES, 'UTF-8') ?><?= \Core\MotorMedia::minimo($c) > 0 ? ' — obrigatório' : '' ?></option>
      <?php endforeach; ?>
    </select>
    <small class="form-text text-muted">Os componentes vêm da regra de média que vale para esta <?= \App\adms\Models\helper\AdmsTipoInstituicao::ehEscola() ? 'disciplina' : 'oferta' ?> (barra da disciplina → Regra de média). "(n/máx)" = atividades cadastradas / máximo; o componente cheio fica desabilitado.</small>
  </div>
  <div class="mb-3 col-md-3" id="grupoPeso">
    <label class="form-label" for="pesoAtividade">Peso da atividade</label>
    <input type="text" inputmode="decimal" name="peso" id="pesoAtividade" class="form-control" value="<?= $hcp($pesoAtual) ?>">
    <small class="form-text text-muted">Conta na média ponderada do componente.</small>
  </div>
</div>
<script>
(function () {
  var s = document.getElementById('componente_codigo'), g = document.getElementById('grupoPeso');
  function ver() { g.style.display = s.options[s.selectedIndex] && s.options[s.selectedIndex].getAttribute('data-usa-peso') === '1' ? '' : 'none'; }
  s.addEventListener('change', ver); ver();
})();
</script>
