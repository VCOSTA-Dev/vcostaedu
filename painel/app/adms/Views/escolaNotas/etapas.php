<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* As etapas de um componente (escola): a etapa escolhida (recuperação, faltas, fechar/reabrir) e o resumo do ano. */
$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$v = fn($n) => \Core\MediaEscola::v($n === null ? null : (string) $n);
$d = $this->dados;
$o = $d['oferta'];
$etapas = $d['etapas'];
$sel = null;
foreach ($etapas as $e) {
  if ((int) $e['id'] === (int) $d['etapaAtual']) { $sel = $e; }
}
$sel = $sel ?? ($etapas[0] ?? null);
$alunos = $d['quadro']['calc']['alunos'] ?? [];
$faltasEt = $d['quadro']['faltas'];
$porChamada = ($o['frequencia_fonte'] ?? 'lancamento') === 'chamada'; // Onda 6, Etapa 5: as faltas vêm da chamada por aula
$pode = (new \App\adms\Models\AdmsBotao())->valBotao([
  'recuperacao' => ['menu_controller' => 'escola-notas', 'menu_metodo' => 'recuperacao'],
  'faltas' => ['menu_controller' => 'escola-notas', 'menu_metodo' => 'faltas'],
  'fechar' => ['menu_controller' => 'escola-notas', 'menu_metodo' => 'fechar'],
  'reabrir' => ['menu_controller' => 'escola-notas', 'menu_metodo' => 'reabrir'],
  'gravar' => ['menu_controller' => 'escola-notas', 'menu_metodo' => 'gravar'],
  'liberacao' => ['menu_controller' => 'escola-liberacao', 'menu_metodo' => 'index'],
]);
/* correção da escola, Parte 1 (D-100): o estado de cada etapa em palavras (aberta, fechada, liberada para a família); o
   que foi digitado e não gravou (a recusa de um dos lados) volta aos campos */
$visiveis = \App\adms\Models\notas\AdmsCalculoNotas::etapasLiberadas();
$estadoEtapa = fn(array $e) => in_array((int) $e['id'], $visiveis, true) ? ['liberada para a família', 'green'] : ($e['trava'] !== null ? ['fechada', 'secondary'] : ['aberta', 'blue']);
$digitado = (array) ($d['digitado'] ?? []);
$podeGravar = !empty($pode['gravar']) && (!empty($pode['recuperacao']) || (!empty($pode['faltas']) && !$porChamada));
$sit = \Core\MediaEscola::SITUACOES;
?>
<div class="vc-pagina">
  <?php ob_start(); ?><?= $h($o['materia']) ?> — etapas<?php $paginaTitulo = ob_get_clean();
  $paginaPretitulo = $h(($d['turma']['codtur'] ?? $o['codtur']) . ' · ' . $o['ano']);
  include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <?= \App\adms\Models\helper\AdmsUi::barraDisciplina((int) $o['id'], 'etapas', '', $d['barra'] ?? []) /* Onda 8, correção (D-72): a barra do componente, com a aba Etapas marcada */ ?>
      <?php if (!$etapas) : ?>
        <div class="alert alert-warning" id="semEtapas">O ano letivo <?= htmlspecialchars((string) ((int) $o['ano']), ENT_QUOTES, 'UTF-8') ?> ainda não tem etapas. Abra-o em Ensino → Ano letivo e etapas.</div>
      <?php else : ?>
      <ul class="nav nav-tabs mb-3" id="abasEtapas">
        <?php foreach ($etapas as $e) : $fech = $e['trava'] !== null; ?>
          <li class="nav-item"><a class="nav-link<?= htmlspecialchars((string) ((int) $e['id'] === (int) $sel['id'] ? ' active' : ''), ENT_QUOTES, 'UTF-8') ?>" data-etapa="<?= htmlspecialchars((string) ((int) $e['id']), ENT_QUOTES, 'UTF-8') ?>" data-fechada="<?= $fech ? '1' : '0' ?>"
            href="<?= URLADM ?>escola-notas/etapas/<?= htmlspecialchars((string) ((int) $o['id']), ENT_QUOTES, 'UTF-8') ?>?etapa=<?= htmlspecialchars((string) ((int) $e['id']), ENT_QUOTES, 'UTF-8') ?>"><?= $fech ? '<i class="fas fa-lock me-1" aria-hidden="true"></i>' : '' ?><?= $h($e['nome']) ?><?= (int) $e['id'] === (int) $d['corrente'] ? ' <span class="badge bg-blue-lt ms-1">atual</span>' : '' ?> <span class="small text-secondary ms-1 vc-estado-etapa" data-estado="<?= $h($estadoEtapa($e)[0]) ?>"><?= $h($estadoEtapa($e)[0]) ?></span></a></li>
        <?php endforeach; ?>
      </ul>
      <div class="card mb-3" id="etapaEscolhida" data-etapa="<?= htmlspecialchars((string) ((int) $sel['id']), ENT_QUOTES, 'UTF-8') ?>">
        <div class="card-header"><h3 class="card-title"><?= $h($sel['nome']) ?></h3>
          <div class="card-actions text-secondary small"><?= htmlspecialchars((string) (date('d/m/Y', strtotime($sel['inicio']))), ENT_QUOTES, 'UTF-8') ?> a <?= htmlspecialchars((string) (date('d/m/Y', strtotime($sel['fim']))), ENT_QUOTES, 'UTF-8') ?><?= htmlspecialchars((string) ($sel['conselho'] ? ' · conselho em ' . date('d/m/Y', strtotime($sel['conselho'])) : ''), ENT_QUOTES, 'UTF-8') ?>
            · <span class="badge bg-<?= $estadoEtapa($sel)[1] ?>-lt" id="estadoEtapa"><?= $h($estadoEtapa($sel)[0]) ?></span><?php if (!empty($pode['liberacao'])) : ?> · <a href="<?= URLADM ?>escola-liberacao/index?ano=<?= (int) $o['ano'] ?>" id="irLiberacao">Liberação do boletim</a><?php endif; ?></div></div>
        <div class="card-body">
          <?php if ($sel['trava'] !== null) : ?>
            <div class="alert alert-warning" id="etapaTravada"><i class="fas fa-lock me-1"></i> <?= $h($sel['trava']) ?></div>
            <?php if (!empty($pode['reabrir'])) : ?>
              <form method="post" action="<?= URLADM ?>escola-notas/reabrir/<?= htmlspecialchars((string) ((int) $o['id']), ENT_QUOTES, 'UTF-8') ?>" class="d-flex flex-wrap gap-2 align-items-end" id="formReabrir">
                <input type="hidden" name="etapa_id" value="<?= htmlspecialchars((string) ((int) $sel['id']), ENT_QUOTES, 'UTF-8') ?>">
                <div class="flex-fill" style="min-width: 18rem"><label class="form-label" for="motivo">Motivo da reabertura (ao menos 10 letras)</label><input type="text" class="form-control" name="motivo" id="motivo" minlength="10" maxlength="500" required></div>
                <button type="submit" class="btn btn-outline-danger" id="reabrirEtapa" data-vc-confirma="Reabrir <?= $h(\App\adms\Models\escola\AdmsEtapaNotas::comArtigo((string) $sel['nome'])[0]) ?>?" data-vc-confirma-extra="As notas, as faltas e a recuperação desta etapa voltam a mudar. O fechamento de agora fica guardado, com o motivo." data-vc-rotulo="Reabrir">Reabrir a etapa</button>
              </form>
            <?php endif; ?>
          <?php endif; ?>
          <?php if ($porChamada) : ?>
            <div class="alert alert-info" id="faltasDaChamada"><i class="fas fa-clipboard-user"></i> As faltas desta disciplina vêm da <b>chamada por aula</b>: a falta da etapa é a soma das chamadas com data nesta etapa.
              <a href="<?= URLADM ?>chamada/listar/<?= htmlspecialchars((string) ((int) $o['id']), ENT_QUOTES, 'UTF-8') ?>" class="alert-link">Abrir a chamada</a></div>
          <?php endif; ?>
          <form method="post" id="formEtapa" action="<?= URLADM ?>escola-notas/gravar/<?= (int) $o['id'] ?>">
            <input type="hidden" name="etapa_id" value="<?= htmlspecialchars((string) ((int) $sel['id']), ENT_QUOTES, 'UTF-8') ?>">
            <div class="table-responsive">
              <table class="table table-vcenter" id="tabelaEtapa">
                <?php /* Onda 8, correção, acréscimo B (D-81): as atividades da etapa, lado a lado (as notas, só para leitura; o nome leva a lançar) */
                  $ativsEtapa = $d['atividades'][(int) $sel['id']] ?? []; ?>
                <thead><tr><th>Aluno</th>
                  <?php foreach ($ativsEtapa as $at) : ?><th class="text-end vc-atividade-etapa" data-atividade="<?= $h($at['id']) ?>"><a href="<?= URLADM ?>atividades/visualizar/<?= $h($at['id']) ?>" title="Lançar as notas de <?= $h($at['nome']) ?>"><?= $h($at['nome']) ?></a></th><?php endforeach; ?>
                  <th class="text-end">Média da etapa</th><th>Recuperação</th><th class="text-end">Nota da etapa</th><th>Faltas na etapa</th></tr></thead>
                <tbody>
                <?php foreach ($alunos as $a) : $et = null; foreach ($a['etapas'] ?? [] as $x) { if ((int) $x['id'] === (int) $sel['id']) { $et = $x; } } ?>
                  <tr data-rm="<?= $h($a['rm']) ?>" data-em-recuperacao="<?= htmlspecialchars((string) (!empty($et['emRecuperacao']) ? '1' : '0'), ENT_QUOTES, 'UTF-8') ?>">
                    <td><?= $h($a['nome']) ?></td>
                    <?php foreach ($ativsEtapa as $at) : $nAt = $a['notas'][(int) $at['id']] ?? null; ?><td class="text-end text-secondary vc-nota-atividade" data-atividade="<?= $h($at['id']) ?>"><?= $v($nAt === null ? null : number_format($nAt / 100, 2, '.', '')) ?></td><?php endforeach; ?>
                    <td class="text-end vc-media-etapa"><?= $v($et['media'] ?? null) ?></td>
                    <td><?php if (!empty($et['emRecuperacao'])) : ?>
                      <input type="text" inputmode="decimal" class="form-control form-control-sm d-inline-block vc-rec" style="max-width: 6rem" autocomplete="off" data-rm="<?= $h($a['rm']) ?>" data-gravado="<?= $h($et['recuperacao'] !== null ? str_replace('.', ',', (string) $et['recuperacao']) : '') ?>" name="recuperacao[<?= $h($a['rm']) ?>]" value="<?= $h(array_key_exists((string) $a['rm'], (array) ($digitado['recuperacao'] ?? [])) ? $digitado['recuperacao'][(string) $a['rm']] : ($et['recuperacao'] !== null ? str_replace('.', ',', (string) $et['recuperacao']) : '')) ?>" aria-label="Recuperação de <?= $h($a['nome']) ?>"<?= htmlspecialchars((string) ($sel['trava'] !== null || empty($pode['recuperacao']) ? ' disabled' : ''), ENT_QUOTES, 'UTF-8') ?>>
                      <?php else : ?><span class="text-secondary">—</span><?php endif; ?></td>
                    <td class="text-end fw-medium vc-nota-etapa"><?= $v($et['nota'] ?? null) ?></td>
                    <td><input type="number" min="0" max="999" class="form-control form-control-sm" style="max-width: 6rem" name="faltas[<?= $h($a['rm']) ?>]" value="<?= $h(array_key_exists((string) $a['rm'], (array) ($digitado['faltas'] ?? [])) ? $digitado['faltas'][(string) $a['rm']] : (int) ($faltasEt[(int) $sel['id']][(string) $a['rm']] ?? 0)) ?>" aria-label="Faltas de <?= $h($a['nome']) ?>"<?= htmlspecialchars((string) ($sel['trava'] !== null || empty($pode['faltas']) || $porChamada ? ' disabled' : ''), ENT_QUOTES, 'UTF-8') ?>></td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php if ($sel['trava'] === null && $podeGravar) : ?>
              <div class="d-flex flex-wrap gap-2 align-items-center">
                <button type="submit" class="btn btn-primary" id="gravarEtapa"><i class="fas fa-save me-1"></i> Gravar</button>
                <span class="text-secondary small" id="legendaGravar"><?= !empty($pode['recuperacao']) && !empty($pode['faltas']) && !$porChamada ? 'Grava a recuperação e as faltas da etapa de uma vez.' : (!empty($pode['recuperacao']) ? 'Grava a recuperação da etapa.' : 'Grava as faltas da etapa.') ?></span>
              </div>
            <?php endif; ?>
          </form>
          <?php if ($sel['trava'] === null && !empty($pode['fechar'])) : [$artigoSel] = \App\adms\Models\escola\AdmsEtapaNotas::comArtigo((string) $sel['nome']); ?>
            <form method="post" action="<?= URLADM ?>escola-notas/fechar/<?= (int) $o['id'] ?>" class="d-flex justify-content-end mt-2" id="formFechar"
              data-vc-confirma="Fechar <?= $h($artigoSel) ?>?" data-vc-confirma-extra="As notas da etapa ficam guardadas e não mudam mais, salvo reabertura com o motivo.">
              <input type="hidden" name="etapa_id" value="<?= (int) $sel['id'] ?>">
              <button type="submit" class="btn btn-outline-secondary" id="fecharEtapa" data-vc-rotulo="Fechar a etapa"><i class="fas fa-lock me-1"></i> Fechar a etapa</button>
            </form>
          <?php endif; ?>
          <p class="text-secondary small mt-3 mb-0">Abaixo de <?= $v($d['conf']['mediaMinima']) ?>, o aluno fica em recuperação; a nota da etapa é <?= $h(mb_strtolower(\App\adms\Models\escola\AdmsEscolaConfig::RECUPERACAO[$d['conf']['recuperacao']] ?? '')) ?>. As notas das atividades se lançam na tela das atividades da disciplina; a atividade pertence à etapa da data dela.</p>
        </div>
      </div>
      <div class="card" id="resumoAno">
        <div class="card-header"><h3 class="card-title">O ano</h3></div>
        <div class="table-responsive">
          <table class="table table-vcenter card-table">
            <thead><tr><th>Aluno</th><?php foreach ($etapas as $e) : ?><th class="text-end"><?= $h($e['nome']) ?></th><?php endforeach; ?><th class="text-end">Média anual</th><th class="text-end">Frequência</th><th>Situação</th></tr></thead>
            <tbody>
            <?php foreach ($alunos as $a) : ?>
              <tr data-rm="<?= $h($a['rm']) ?>">
                <td><?= $h($a['nome']) ?></td>
                <?php foreach ($etapas as $e) : $x = null; foreach ($a['etapas'] ?? [] as $y) { if ((int) $y['id'] === (int) $e['id']) { $x = $y; } } ?>
                  <td class="text-end"><?= !empty($x['antesDaMatricula']) ? '<span class="text-secondary" title="Antes da matrícula do aluno: sem nota, fora da média anual">—</span>' : $v($x['nota'] ?? null) ?><?= !empty($x['emRecuperacao']) ? ' <span class="badge bg-orange-lt" title="Em recuperação">R</span>' : '' ?><?= isset($x['transferencia']) ? ' <span class="badge bg-purple-lt" title="Nota de transferência' . $h($x['transferencia'] !== '' ? ': ' . $x['transferencia'] : '') . '">T</span>' : '' /* D-102 */ ?></td>
                <?php endforeach; ?>
                <td class="text-end fw-medium vc-media-anual"><?= $v($a['media'] ?? null) ?></td>
                <td class="text-end"><?= $a['frequencia'] !== null ? $v((string) $a['frequencia']) . '%' : '—' ?></td>
                <td class="vc-situacao"><?= $h($sit[$a['situacao'] ?? 'em_curso'] ?? '') ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="card-body border-top" id="memoriasEscola">
          <h4 class="mb-2">A conta de cada aluno</h4>
          <?php foreach ($alunos as $a) : ?>
            <details class="vc-memoria-escola small mb-1" id="memoria-<?= $h($a['rm']) ?>"><summary><?= $h($a['nome']) ?>: média anual <?= $v($a['media'] ?? null) ?></summary>
              <ul class="mb-1 ps-3"><?php foreach ((array) ($a['memoria']['linhas'] ?? []) as $l) : ?><li><?= $h($l) ?></li><?php endforeach; ?></ul>
            </details>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </section>
</div>
<?php if ($etapas && $sel && $sel['trava'] === null && !empty($pode['recuperacao'])) : include 'app/adms/Views/include_tabler/_gravado.php'; ?>
<script>
/* Onda 8, correção, Parte 2.1 (D-74): a recuperação grava ao apertar Enter (ou ao sair do campo mudado), sem recarregar;
   o cursor vai para o campo do próximo aluno em recuperação; a nota da etapa da linha se atualiza. O botão "Gravar"
   continua gravando o formulário inteiro (D-100). */
(function () {
  var url = <?= json_encode(URLADM . 'escola-notas/recuperacao/' . (int) $o['id']) ?>;
  var etapa = <?= json_encode((int) $sel['id']) ?>;
  var tabela = document.getElementById('tabelaEtapa');
  if (!tabela) { return; }
  function campos() { return Array.prototype.slice.call(tabela.querySelectorAll('input.vc-rec')); }
  function abrir(inp) { if (inp) { inp.focus(); inp.select(); } }
  function proximo(inp) { var l = campos(); var i = l.indexOf(inp); return i >= 0 && i + 1 < l.length ? l[i + 1] : null; }
  /* Correção da escola, Parte 2 (B1, B2; D-101): o Enter desce na hora e a gravação vai em segundo plano, com o sinal no
     campo de origem; a nota fora da escala não sai do campo (a mesma regra do servidor) */
  var ESCALA = /^\d{1,2}([.,]\d{1,2})?$/;
  var MOTIVO = 'Nota de recuperação inválida: use um número de 0 a 10.';
  function valida(valor) { return valor === '' || (ESCALA.test(valor) && parseFloat(valor.replace(',', '.')) <= 10); }
  function gravar(inp) {
    var valor = inp.value.trim();
    if (inp.dataset.enviando === '1') { inp.dataset.pendente = '1'; return; }
    if (valor === inp.dataset.gravado && !inp.classList.contains('is-invalid')) { return; }
    if (!valida(valor)) { vcGravado.erro(inp, MOTIVO); return; }
    inp.dataset.enviando = '1';
    inp.dataset.pendente = '';
    vcGravado.gravando(inp);
    var corpo = new URLSearchParams();
    corpo.append('ajax', '1');
    corpo.append('etapa_id', String(etapa));
    corpo.append('recuperacao[' + inp.dataset.rm + ']', valor);
    var fim = function () { inp.dataset.enviando = ''; if (inp.dataset.pendente === '1') { inp.dataset.pendente = ''; gravar(inp); } };
    fetch(url, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' }, body: corpo.toString() })
      .then(function (r) { return r.json().catch(function () { return { ok: false, erro: 'Resposta inesperada do servidor (' + r.status + ').' }; }); })
      .then(function (j) {
        if (j && j.ok) {
          inp.dataset.gravado = valor;
          var n = j.nota && j.nota[inp.dataset.rm];
          var cel = inp.closest('tr').querySelector('.vc-nota-etapa');
          if (cel && n !== undefined) { cel.textContent = n; }
          if (inp.value.trim() === valor) { vcGravado.ok(inp); }
        } else {
          vcGravado.erro(inp, (j && j.erro) || 'A recuperação não foi gravada.');
        }
        fim();
      })
      .catch(function () { vcGravado.erro(inp, 'Sem conexão com o servidor: a nota não foi gravada.'); fim(); });
  }
  tabela.addEventListener('keydown', function (ev) {
    var inp = ev.target;
    if (!inp.classList || !inp.classList.contains('vc-rec')) { return; }
    if (ev.key === 'Enter') {
      ev.preventDefault();
      if (!valida(inp.value.trim())) { vcGravado.erro(inp, MOTIVO); abrir(inp); return; }
      gravar(inp);
      inp.dataset.saiuPorEnter = '1';
      abrir(proximo(inp));
    }
    if (ev.key === 'Escape') { inp.value = inp.dataset.gravado; vcGravado.limpar(inp); }
  });
  tabela.addEventListener('input', function (ev) { if (ev.target.dataset) { ev.target.dataset.saiuPorEnter = ''; } });
  /* sair do campo grava, um instante depois: se a saída foi para um botão do formulário, o formulário grava (não os dois) */
  var formEnviado = false;
  var form = tabela.closest('form');
  if (form) { form.addEventListener('submit', function () { formEnviado = true; }); }
  tabela.addEventListener('change', function (ev) {
    var inp = ev.target;
    if (inp.classList && inp.classList.contains('vc-rec')) {
      if (inp.dataset.saiuPorEnter === '1') { inp.dataset.saiuPorEnter = ''; return; } /* o Enter já mandou */
      setTimeout(function () { if (!formEnviado) { gravar(inp); } }, 250);
    }
  });
})();
</script>
<?php endif; ?>
<script>
/* D-100: fechar com o que foi digitado e não gravado avisa antes (a janela de confirmação diz o que se perde) */
(function () {
  var fechar = document.getElementById('formFechar'), etapa = document.getElementById('formEtapa');
  if (!fechar || !etapa) { return; }
  var base = fechar.dataset.vcConfirmaExtra || '';
  fechar.addEventListener('submit', function () {
    var mudou = Array.prototype.some.call(etapa.querySelectorAll('input:not([type=hidden]):not([disabled])'), function (i) { return i.value !== i.defaultValue && i.value !== (i.dataset.gravado || i.defaultValue); });
    fechar.dataset.vcConfirmaExtra = base + (mudou ? '<div class="text-danger mt-2" id="avisoNaoGravado">Há nota ou falta digitada e ainda não gravada nesta etapa: ela se perde ao fechar. Volte e clique em Gravar antes.</div>' : '');
  });
})();
</script>
