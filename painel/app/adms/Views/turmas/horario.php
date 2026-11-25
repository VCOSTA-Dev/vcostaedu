<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* O horário da turma na semana (Onda 8, D-71): as aulas do turno nos dias; cada escolha grava na hora. */
$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$d = $this->dados;
$t = $d['turma'];
$hm = fn($x) => substr((string) $x, 0, 5);
$pode = (new \App\adms\Models\AdmsBotao())->valBotao([
  'gravar' => ['menu_controller' => 'turmas', 'menu_metodo' => 'gravar-horario'],
  'aulas' => ['menu_controller' => 'horarios-aula', 'menu_metodo' => 'listar'],
]);
$dias = \App\adms\Models\escola\AdmsHorarioTurma::DIAS;
$grade = $d['grade'];
/* o sábado aparece quando já tem aula nele, ou pelo botão "Mostrar o sábado" */
$comSabado = (bool) array_filter(array_keys($grade), fn($k) => str_ends_with($k, '-7'));
$prof = [];
foreach ($d['ofertas'] as $o) { $prof[(int) $o['id']] = (string) ($o['professores'] ?? ''); }
$conta = \App\adms\Models\escola\AdmsHorarioTurma::contagem($grade);
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Horário da turma <?= $h($t['codtur']) ?><?php $paginaTitulo = ob_get_clean();
  $paginaPretitulo = $h($t['segmento'] . ' · ' . $t['ano'] . ' · turno ' . $d['turnoNome']);
  $paginaAcoesHtml = '<a href="' . URLADM . 'turmas/ver/' . (int) $t['id'] . '" class="btn btn-outline-secondary" id="voltarTurma"><i class="fas fa-arrow-left"></i> A turma</a>'
    . (!empty($pode['aulas']) ? '<a href="' . URLADM . 'horarios-aula/listar#turno' . (int) $d['turno'] . '" class="btn btn-outline-secondary" id="editarAulas"><i class="far fa-clock"></i> Horários de aula</a>' : '')
    . '<a href="' . URLADM . 'ajuda/guia/montar-o-horario-da-turma" class="btn btn-outline-secondary" id="guiaHorario"><i class="fas fa-circle-question"></i> Ajuda</a>';
  include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <?php if (!$d['aulas']) : ?>
        <div class="alert alert-info" id="semAulas">O turno <?= $h($d['turnoNome']) ?> ainda não tem horários de aula ativos. Cadastre as aulas do dia em <a href="<?= URLADM ?>horarios-aula/listar#turno<?= $h($d['turno']) ?>">Horários de aula</a> e volte aqui.</div>
      <?php elseif (!$d['ofertas']) : ?>
        <div class="alert alert-info" id="semComponentes">A turma não tem disciplinas.</div>
      <?php else : ?>
      <p class="text-secondary"><?= !empty($pode['gravar']) ? 'Escolha a disciplina de cada aula: grava na hora (o sinal ao lado confirma). "Vaga" deixa a aula sem disciplina. O professor é o da disciplina, indicado na tela dela.' : 'O horário da turma na semana.' ?></p>
      <div class="row row-cards">
        <div class="col-xl-9">
          <div class="card" id="gradeTurma" data-turma="<?= $h($t['id']) ?>">
            <div class="table-responsive">
              <table class="table table-vcenter table-bordered card-table vc-grade-horario">
                <thead><tr><th>Aula</th>
                  <?php foreach ($dias as $di => $nomeDia) : ?><th class="vc-dia<?= $di === 7 && !$comSabado ? ' d-none vc-sabado' : ($di === 7 ? ' vc-sabado' : '') ?>" data-dia="<?= $h($di) ?>"><?= $h($nomeDia) ?></th><?php endforeach; ?>
                </tr></thead>
                <tbody>
                <?php foreach ($d['aulas'] as $a) : ?>
                  <tr data-aula="<?= $h($a['id']) ?>">
                    <th class="text-nowrap"><?= $h($a['nome']) ?><div class="small text-secondary fw-normal"><?= $h($hm($a['inicio']) . '–' . $hm($a['termino'])) ?></div></th>
                    <?php foreach ($dias as $di => $nomeDia) : $atual = $grade[(int) $a['id'] . '-' . $di] ?? 0; ?>
                      <td class="vc-celula<?= $di === 7 ? ' vc-sabado' . ($comSabado ? '' : ' d-none') : '' ?>" style="min-width: 10rem">
                        <?php if (!empty($pode['gravar'])) : ?>
                          <select class="form-select form-select-sm vc-aula-turma" data-dia="<?= $h($di) ?>" data-aula="<?= $h($a['id']) ?>" data-gravado="<?= $h($atual) ?>" aria-label="<?= $h($nomeDia . ', ' . $a['nome']) ?>" style="width: auto; max-width: 11rem; display: inline-block">
                            <option value="0">Vaga</option>
                            <?php foreach ($d['ofertas'] as $o) : ?><option value="<?= $h($o['id']) ?>"<?= (int) $o['id'] === $atual ? ' selected' : '' ?>><?= $h($o['materia']) ?></option><?php endforeach; ?>
                          </select>
                        <?php else : ?>
                          <span><?= $atual ? $h(array_column($d['ofertas'], 'materia', 'id')[$atual] ?? '') : '<span class="text-secondary">—</span>' ?></span>
                        <?php endif; ?>
                        <div class="small text-secondary vc-professor"><?= $h($atual ? ($prof[$atual] ?? '') : '') ?></div>
                      </td>
                    <?php endforeach; ?>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php if (!$comSabado) : ?><div class="card-footer"><button type="button" class="btn btn-sm btn-outline-secondary" id="mostrarSabado">Mostrar o sábado</button></div><?php endif; ?>
          </div>
        </div>
        <div class="col-xl-3">
          <div class="card" id="aulasPorComponente">
            <div class="card-header"><h3 class="card-title">Aulas por semana</h3></div>
            <div class="table-responsive">
              <table class="table table-vcenter card-table">
                <tbody>
                <?php foreach ($d['ofertas'] as $o) : ?>
                  <tr data-oferta="<?= $h($o['id']) ?>"><td><?= $h($o['materia']) ?><div class="small text-secondary"><?= $o['professores'] ? $h($o['professores']) : 'sem professor' ?></div></td><td class="text-end vc-conta"><?= $h($conta[(int) $o['id']] ?? 0) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </section>
</div>
<?php include 'app/adms/Views/include_tabler/_gravado.php'; ?>
<script>
(function () {
  var grade = document.getElementById('gradeTurma');
  if (!grade) { return; }
  var url = <?= json_encode(URLADM . 'turmas/gravar-horario/' . (int) $t['id']) ?>;
  var prof = <?= json_encode($prof, JSON_UNESCAPED_UNICODE) ?>;
  var sab = document.getElementById('mostrarSabado');
  if (sab) { sab.addEventListener('click', function () { document.querySelectorAll('.vc-sabado').forEach(function (e) { e.classList.remove('d-none'); }); sab.remove(); }); }
  function contar() {
    var n = {};
    grade.querySelectorAll('select.vc-aula-turma').forEach(function (s) { var v = s.dataset.gravado; if (v !== '0') { n[v] = (n[v] || 0) + 1; } });
    document.querySelectorAll('#aulasPorComponente tr[data-oferta]').forEach(function (tr) { tr.querySelector('.vc-conta').textContent = n[tr.dataset.oferta] || 0; });
  }
  grade.addEventListener('change', function (ev) {
    var s = ev.target;
    if (!s.classList.contains('vc-aula-turma')) { return; }
    var valor = s.value;
    var corpo = new URLSearchParams({ dia: s.dataset.dia, aula: s.dataset.aula, oferta: valor });
    vcGravado.gravando(s);
    fetch(url, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: corpo.toString() })
      .then(function (r) { return r.json().catch(function () { return { ok: false, erro: 'Resposta inesperada do servidor (' + r.status + ').' }; }); })
      .then(function (j) {
        if (s.value !== valor) { return; } /* a pessoa já escolheu outro: vale a resposta do último */
        if (j && j.ok) {
          s.dataset.gravado = valor;
          s.parentNode.querySelector('.vc-professor').textContent = valor !== '0' ? (prof[valor] || '') : '';
          vcGravado.ok(s);
          contar();
        } else {
          s.value = s.dataset.gravado; /* a tela volta ao que está gravado */
          vcGravado.erro(s, (j && j.erro) || 'Não foi possível gravar.');
        }
      })
      .catch(function () { s.value = s.dataset.gravado; vcGravado.erro(s, 'Sem conexão com o servidor: nada foi gravado.'); });
  });
})();
</script>
