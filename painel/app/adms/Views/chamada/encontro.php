<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* E7 (1.14.0, D-406): o ENCONTRO — data, horário opcional, título e conteúdo — e a chamada: todos presentes por
   padrão; um clique marca ausente. Encerrada: só leitura. */
use App\adms\Models\helper\AdmsUi as Ui;

$d = $this->dados;
$o = $d['oferta'];
$e = $d['encontro'];
$f = $d['form'] ?? null;
$h = fn($t) => htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
$leitura = $d['encerrada'] || $d['fonte'] !== 'chamada';
$val = fn($k, $padrao = '') => $f[$k] ?? ($e[$k] ?? $padrao);
$hora = fn($k) => substr((string) ($f[$k] ?? ($e[$k] ?? '')), 0, 5);
/* Onda 8, correção, Parte 2.2 (D-75): o encontro novo começa com todos presentes ou todos ausentes (a escolha do professor,
   lembrada do último encontro que ele criou); no encontro que já existe, cada clique grava na hora (chamada/presenca). */
$inicio = $d['inicio'] ?? 'presentes';
$porClique = $e && !$leitura;
$ausente = function (string $rm) use ($f, $e, $inicio) {
    if ($f !== null) {
        return (string) ($f['ausente'][$rm] ?? '') === '1';
    }
    if (!$e) {
        return $inicio === 'ausentes';
    }
    return isset($e['presencas'][$rm]) && (int) $e['presencas'][$rm] === 0;
};
$url = URLADM . 'chamada/encontro/' . (int) $o['id'] . ($e ? '?id=' . (int) $e['id'] : '');
?>
<div class="vc-pagina">
  <?php ob_start(); ?><?= htmlspecialchars((string) ($e ? 'Chamada de ' . date('d/m/Y', strtotime($e['data'])) : (\App\adms\Models\helper\AdmsTipoInstituicao::ehEscola() ? 'Nova aula' : 'Novo encontro')), ENT_QUOTES, 'UTF-8') ?> <small class="text-muted"><?= $h($o['materia']) ?> · <?= $h($o['codtur']) ?></small><?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?= Ui::barraDisciplina((int) $o['id'], 'chamada', '', $d['barra'] ?? []) ?>
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <?php if ($d['encerrada']) : ?>
        <?= Ui::faixaTrava('Disciplina encerrada: a chamada está em modo leitura.', 'Para mudar, o administrador reabre a disciplina, com justificativa.', null, 'chamadaEncerrada') ?>
      <?php elseif ($d['fonte'] !== 'chamada') : ?>
        <div class="alert alert-warning" id="fonteLancamento">A frequência desta oferta é por <b>lançamento de faltas</b>. Para fazer chamada, troque a fonte na tela da Chamada.</div>
      <?php endif; ?>
      <form method="POST" action="<?= $h($url) ?>" id="formChamada" novalidate>
        <fieldset <?= $leitura ? 'disabled' : '' ?>>
        <div class="card shadow-sm">
          <div class="card-body">
            <div class="row g-2">
              <div class="mb-3 col-md-3"><label class="form-label" for="data"><?= \App\adms\Models\helper\AdmsTipoInstituicao::ehEscola() ? 'Data da aula' : 'Data do encontro' ?></label><input type="date" class="form-control" id="data" name="data" value="<?= $h($val('data', date('Y-m-d'))) ?>" required>
                <div class="form-hint text-warning d-none" id="avisoFimDeSemana"><i class="fas fa-triangle-exclamation me-1"></i><span></span></div></div>
              <div class="mb-3 col-md-2"><label class="form-label" for="hora_inicio">Início <small class="text-muted">(opcional)</small></label><input type="time" class="form-control" id="hora_inicio" name="hora_inicio" value="<?= $h($hora('hora_inicio')) ?>"></div>
              <div class="mb-3 col-md-2"><label class="form-label" for="hora_fim">Fim <small class="text-muted">(opcional)</small></label><input type="time" class="form-control" id="hora_fim" name="hora_fim" value="<?= $h($hora('hora_fim')) ?>"></div>
              <div class="mb-3 col-md-5"><label class="form-label" for="titulo">Título <small class="text-muted">(opcional)</small></label><input type="text" class="form-control" id="titulo" name="titulo" maxlength="150" value="<?= $h($val('titulo')) ?>"></div>
            </div>
            <div class="mb-3 mb-0"><label class="form-label" for="conteudo">Conteúdo <small class="text-muted">(opcional)</small></label><textarea class="form-control" id="conteudo" name="conteudo" rows="2"><?= $h($val('conteudo')) ?></textarea></div>
          </div>
        </div>
        <div class="card shadow">
          <div class="card-header d-flex flex-wrap align-items-center gap-2"><b class="me-auto">Presença</b>
            <?php if (!$e && !$leitura) : ?>
              <span class="text-muted small">Começar com:</span>
              <div class="btn-group btn-group-sm" role="group" aria-label="Começar a chamada com" id="inicioChamada">
                <input type="radio" class="btn-check" name="inicio" id="inicioPresentes" value="presentes"<?= $inicio === 'presentes' ? ' checked' : '' ?>><label class="btn btn-outline-success" for="inicioPresentes">todos presentes</label>
                <input type="radio" class="btn-check" name="inicio" id="inicioAusentes" value="ausentes"<?= $inicio === 'ausentes' ? ' checked' : '' ?>><label class="btn btn-outline-danger" for="inicioAusentes">todos ausentes</label>
              </div>
              <small class="text-muted">Depois, clique em quem é diferente. Ao salvar <?= \App\adms\Models\helper\AdmsTipoInstituicao::ehEscola() ? 'a aula' : 'o encontro' ?>, cada clique passa a gravar na hora.</small>
            <?php elseif ($porClique) : ?>
              <small class="text-muted" id="avisoPorClique">Cada clique grava na hora.</small>
            <?php endif; ?>
            <span class="ms-2 badge bg-light text-dark" id="contagemAusentes"></span></div>
          <div class="card-body p-0">
            <table class="table table-sm table-hover mb-0" id="tabelaPresenca">
              <thead><tr><th><?= \App\adms\Models\helper\AdmsTipoInstituicao::rotuloNumero() ?></th><th>Aluno</th><th class="text-center" style="width: 170px">Presença</th></tr></thead>
              <tbody>
                <?php foreach ($d['alunos'] as $a) : $aus = $ausente((string) $a['rm']); ?>
                  <tr data-rm="<?= $h($a['rm']) ?>">
                    <td><?= $h($a['rm']) ?></td><td><?= $h($a['nome_aluno']) ?></td>
                    <td class="text-center">
                      <input type="hidden" name="ausente[<?= $h($a['rm']) ?>]" value="<?= $aus ? '1' : '0' ?>" class="campo-ausente">
                      <button type="button" class="btn btn-sm w-100 botao-presenca <?= $aus ? 'btn-danger' : 'btn-outline-success' ?>" aria-pressed="<?= $aus ? 'true' : 'false' ?>"><?= $aus ? 'AUSENTE' : 'PRESENTE' ?></button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            <?php if (!$d['alunos']) : ?><p class="text-muted m-3">Nenhum aluno enturmado.</p><?php endif; ?>
          </div>
          <?php if (!$leitura) : ?><div class="card-footer"><button type="submit" name="SalvarChamada" value="1" class="btn btn-primary" id="salvarChamada"><i class="fas fa-save"></i> Salvar a chamada</button>
            <small class="text-muted ms-2"><?= \App\adms\Models\helper\AdmsTipoInstituicao::ehEscola() ? 'Grava a aula e as presenças de uma vez; as faltas e o total de aulas da disciplina são recalculados.' : 'Grava o encontro e as presenças de uma vez; as faltas e o total de encontros da oferta são recalculados.' ?></small></div><?php endif; ?>
        </div>
        </fieldset>
      </form>
    </div>
  </section>
</div>
<?php if ($porClique) { include 'app/adms/Views/include_tabler/_gravado.php'; } ?>
<?php if (\App\adms\Models\helper\AdmsTipoInstituicao::ehEscola()) : /* no ensino superior, o sábado pode ser dia de aula */ ?>
<script>
/* Correção da escola, Parte 2 (B14; D-101): a data num sábado ou num domingo (fora dos dias letivos da semana) avisa ao
   lado do campo e, ao salvar, pede a confirmação na janela do sistema; num dia de semana, grava como sempre */
(function () {
  var form = document.getElementById('formChamada'), data = document.getElementById('data'), aviso = document.getElementById('avisoFimDeSemana');
  if (!form || !data || !aviso) { return; }
  var NOMES = { 0: 'domingo', 6: 'sábado' };
  function dia() {
    var p = (data.value || '').split('-');
    if (p.length !== 3) { return null; }
    var d = new Date(+p[0], +p[1] - 1, +p[2]).getDay();
    return NOMES[d] || null;
  }
  function atualizar() {
    var n = dia();
    aviso.classList.toggle('d-none', !n);
    aviso.querySelector('span').textContent = n ? 'Esta data é um ' + n + ', que não é dia letivo.' : '';
    if (n) {
      form.dataset.vcConfirma = 'Gravar a chamada num ' + n + '?';
      form.dataset.vcConfirmaExtra = 'A data ' + data.value.split('-').reverse().join('/') + ' é um ' + n + ', que não é dia letivo. Confira a data antes de gravar.';
    } else {
      delete form.dataset.vcConfirma;
      delete form.dataset.vcConfirmaExtra;
    }
  }
  data.addEventListener('change', atualizar);
  data.addEventListener('input', atualizar);
  form.addEventListener('submit', atualizar);
  atualizar();
})();
</script>
<?php endif; ?>
<script>
(function () {
  var tabela = document.getElementById('tabelaPresenca');
  function contar() {
    var n = tabela.querySelectorAll('.campo-ausente[value="1"]').length;
    document.getElementById('contagemAusentes').textContent = n + (n === 1 ? ' ausente' : ' ausentes');
  }
  function marcar(b, aus) {
    var campo = b.parentElement.querySelector('.campo-ausente');
    campo.value = aus ? '1' : '0'; campo.setAttribute('value', campo.value);
    b.className = 'btn btn-sm w-100 botao-presenca ' + (aus ? 'btn-danger' : 'btn-outline-success');
    b.textContent = aus ? 'AUSENTE' : 'PRESENTE'; b.setAttribute('aria-pressed', aus ? 'true' : 'false');
  }
  /* Onda 8 (D-75): no encontro novo, "Começar com" marca todos de uma vez (nada é gravado até salvar o encontro) */
  document.querySelectorAll('#inicioChamada input[name="inicio"]').forEach(function (r) {
    r.addEventListener('change', function () {
      tabela.querySelectorAll('.botao-presenca').forEach(function (b) { marcar(b, r.value === 'ausentes'); });
      contar();
    });
  });
  var porClique = <?= json_encode($porClique) ?>;
  var url = <?= json_encode(URLADM . 'chamada/presenca/' . (int) $o['id']) ?>;
  var encontro = <?= json_encode($e ? (int) $e['id'] : 0) ?>;
  tabela.addEventListener('click', function (ev) {
    var b = ev.target.closest('.botao-presenca');
    if (!b || b.disabled) return;
    var aus = b.parentElement.querySelector('.campo-ausente').value !== '1';
    if (!porClique) { marcar(b, aus); contar(); return; }
    /* o encontro já existe: o clique grava na hora; a tela só muda depois da confirmação */
    if (b.dataset.enviando === '1') { return; }
    b.dataset.enviando = '1';
    vcGravado.gravando(b);
    var corpo = new URLSearchParams({ encontro: String(encontro), rm: b.closest('tr').dataset.rm, ausente: aus ? '1' : '0' });
    fetch(url, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' }, body: corpo.toString() })
      .then(function (r) { return r.json().catch(function () { return { ok: false, erro: 'Resposta inesperada do servidor (' + r.status + ').' }; }); })
      .then(function (j) {
        b.dataset.enviando = '';
        if (j && j.ok) { marcar(b, aus); contar(); vcGravado.ok(b); }
        else { vcGravado.erro(b, (j && j.erro) || 'A presença não foi gravada.'); }
      })
      .catch(function () { b.dataset.enviando = ''; vcGravado.erro(b, 'Sem conexão com o servidor: nada foi gravado.'); });
  });
  contar();
})();
</script>
