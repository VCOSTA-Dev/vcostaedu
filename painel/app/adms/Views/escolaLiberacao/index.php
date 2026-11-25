<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* A LIBERAÇÃO DO BOLETIM (D-100): por ano letivo, uma linha por etapa, a média final e a situação final. Um botão
   Salvar; antes de gravar, a tela diz em palavras o que muda no portal (a janela de confirmação do sistema). */
$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$d = $this->dados;
$ano = (int) $d['ano'];
$pode = (new \App\adms\Models\AdmsBotao())->valBotao(['salvar' => ['menu_controller' => 'escola-liberacao', 'menu_metodo' => 'salvar']]);
$podeSalvar = !empty($pode['salvar']);
$cor = ['oculto' => 'secondary', 'visivel' => 'green', 'programado' => 'blue'];
$rotulo = ['oculto' => 'Oculto', 'visivel' => 'No portal', 'programado' => 'Programado'];
$dtl = fn($x) => $x ? date('Y-m-d\TH:i', strtotime((string) $x)) : '';
?>
<div class="vc-pagina" id="liberacaoBoletim">
  <?php ob_start(); ?>Liberação do boletim<?php $paginaTitulo = ob_get_clean();
  $paginaPretitulo = 'Ensino';
  include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <p class="text-secondary">O que a família e o aluno veem do boletim no portal, por ano letivo. Cada etapa se libera sozinha, em qualquer ordem. Sem a <b>média final</b> liberada, o portal não mostra média anual em lugar nenhum; sem a <b>situação final</b>, não mostra aprovado nem reprovado. No painel, o boletim continua mostrando tudo o que está fechado. A tela das notas das atividades do portal é em tempo real e não depende desta liberação.</p>
      <?php if (!$d['anos']) : ?>
        <div class="alert alert-info" id="semAnosLiberacao">Nenhum ano letivo tem etapas ainda. Abra o ano em <a href="<?= URLADM ?>escola-anos/index">Ensino → Ano letivo e etapas</a>.</div>
      <?php else : ?>
      <form method="get" action="<?= URLADM ?>escola-liberacao/index" class="d-flex flex-wrap gap-2 align-items-end mb-3" id="escolherAno">
        <div><label class="form-label" for="anoLiberacao">Ano letivo</label>
          <select class="form-select" name="ano" id="anoLiberacao" onchange="this.form.submit()">
            <?php foreach ($d['anos'] as $a) : ?><option value="<?= (int) $a ?>"<?= $a === $ano ? ' selected' : '' ?>><?= (int) $a ?></option><?php endforeach; ?>
          </select></div>
        <noscript><button type="submit" class="btn btn-outline-secondary">Abrir</button></noscript>
      </form>
      <form method="post" action="<?= URLADM ?>escola-liberacao/salvar" class="card mb-3" id="formLiberacao" data-vc-confirma="Salvar a liberação do boletim de <?= $ano ?>?">
        <input type="hidden" name="ano" value="<?= $ano ?>">
        <div class="table-responsive">
          <table class="table table-vcenter card-table" id="tabelaLiberacao">
            <thead><tr><th>Item</th><th class="text-center">Exibir no portal</th><th>Liberar a partir de</th><th>Situação agora</th><th>Última alteração</th></tr></thead>
            <tbody>
            <?php foreach ($d['linhas'] as $l) : $k = $l['chave']; $idc = str_replace(':', '-', $k); ?>
              <tr class="vc-liberacao" data-chave="<?= $h($k) ?>" data-item="<?= $h($l['item']) ?>" data-nome="<?= $h($l['nome']) ?>" data-abertas="<?= (int) $l['abertas'] ?>"
                  data-exibir="<?= $l['exibir'] ? '1' : '0' ?>" data-a-partir="<?= $h($dtl($l['a_partir'])) ?>" data-estado="<?= $h($l['estado']) ?>">
                <td><b><?= $h($l['nome']) ?></b>
                  <?php if ($l['item'] === 'etapa' && $l['abertas'] > 0) : ?><div class="small text-warning vc-abertas"><i class="fas fa-exclamation-triangle me-1"></i><?= $l['abertas'] === 1 ? '1 disciplina ainda está com esta etapa aberta' : $h($l['abertas'] . ' disciplinas ainda estão com esta etapa aberta') ?></div><?php endif; ?></td>
                <td class="text-center"><label class="form-check form-switch d-inline-block m-0">
                  <input class="form-check-input vc-exibir" type="checkbox" name="item[<?= $h($k) ?>][exibir]" value="1" id="exibir-<?= $h($idc) ?>"<?= $l['exibir'] ? ' checked' : '' ?><?= $podeSalvar ? '' : ' disabled' ?> aria-label="Exibir <?= $h($l['nome']) ?> no portal"></label></td>
                <td><input type="datetime-local" class="form-control form-control-sm vc-a-partir" style="max-width: 14rem" name="item[<?= $h($k) ?>][a_partir]" id="apartir-<?= $h($idc) ?>" value="<?= $h($dtl($l['a_partir'])) ?>"<?= $podeSalvar ? '' : ' disabled' ?> aria-label="Liberar <?= $h($l['nome']) ?> a partir de"></td>
                <td class="vc-estado"><span class="badge bg-<?= $cor[$l['estado']] ?>-lt me-1"><?= $h($rotulo[$l['estado']]) ?></span> <span class="small"><?= $h($l['texto']) ?></span></td>
                <td class="small text-secondary vc-ultima"><?= $l['em'] ? $h(($l['por'] ?: 'a atualização do sistema') . ', ' . date('d/m/Y H:i', strtotime((string) $l['em']))) : '—' ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php if ($podeSalvar) : ?>
        <div class="card-footer d-flex flex-wrap gap-2 align-items-center">
          <div class="text-secondary small flex-fill" id="resumoMudancas">Nada mudou ainda.</div>
          <button type="submit" class="btn btn-primary" id="salvarLiberacao"><i class="fas fa-save me-1"></i> Salvar</button>
        </div>
        <?php endif; ?>
      </form>
      <p class="small text-secondary">"Liberar a partir de" vazio: aparece assim que salvar. Com a data e a hora, aparece só a partir delas. Sem "Exibir no portal", a data não vale.</p>
      <?php if ($d['historico']) : ?>
      <div class="card" id="historicoLiberacao">
        <div class="card-header"><h3 class="card-title">O histórico de <?= $ano ?></h3></div>
        <div class="table-responsive"><table class="table table-sm card-table">
          <thead><tr><th>Quando</th><th>Quem</th><th>Item</th><th>Antes</th><th>Depois</th></tr></thead>
          <tbody><?php foreach ($d['historico'] as $x) : ?><tr><td><?= $h(date('d/m/Y H:i', strtotime($x['em']))) ?></td><td><?= $h($x['por'] ?: '—') ?></td><td><?= $h($x['nome']) ?></td><td><?= $h($x['antes']) ?></td><td><?= $h($x['depois']) ?></td></tr><?php endforeach; ?></tbody>
        </table></div>
      </div>
      <?php endif; ?>
      <?php endif; ?>
    </div>
  </section>
</div>
<?php if (!empty($d['linhas']) && $podeSalvar) : ?>
<script>
/* o que muda no portal, em palavras: o resumo ao pé da tabela e o texto da confirmação antes de gravar */
(function () {
  var form = document.getElementById('formLiberacao');
  if (!form) { return; }
  function esc(t) { var d = document.createElement('div'); d.textContent = t; return d.innerHTML; }
  function quando(v) { if (!v) { return ''; } var p = v.split('T'); var d = p[0].split('-'); return d[2] + '/' + d[1] + '/' + d[0] + ' às ' + (p[1] || '').slice(0, 5); }
  function oQue(item) { return item === 'etapa' ? 'as notas de ' : (item === 'media_final' ? 'a média anual (' : 'a situação final, aprovado ou reprovado ('); }
  function frase(tr) {
    var ex = tr.querySelector('.vc-exibir').checked, ap = tr.querySelector('.vc-a-partir').value;
    var exAntes = tr.dataset.exibir === '1', apAntes = tr.dataset.aPartir;
    if (!ex) { ap = ''; }
    if (!exAntes) { apAntes = ''; }
    if (ex === exAntes && ap === apAntes) { return null; }
    var item = tr.dataset.item, nome = tr.dataset.nome;
    var alvo = item === 'etapa' ? oQue(item) + nome : oQue(item) + nome + ')';
    var futuro = ap && new Date(ap) > new Date();
    var t;
    if (!ex) {
      t = 'A família e o aluno deixam de ver ' + alvo + '.';
    } else if (futuro) {
      t = 'A família e o aluno passam a ver ' + alvo + ' em ' + quando(ap) + '.';
    } else {
      t = 'A família e o aluno passam a ver ' + alvo + (exAntes && tr.dataset.estado === 'visivel' ? ' (continua à vista; muda só a data).' : ' assim que salvar.');
    }
    var ab = parseInt(tr.dataset.abertas || '0', 10);
    if (ex && item === 'etapa' && ab > 0) {
      t += ' Atenção: ' + (ab === 1 ? '1 disciplina ainda está' : ab + ' disciplinas ainda estão') + ' com esta etapa aberta (as notas dela podem mudar).';
    }
    return t;
  }
  function mudancas() {
    var l = [];
    form.querySelectorAll('tr.vc-liberacao').forEach(function (tr) { var f = frase(tr); if (f) { l.push(f); } });
    return l;
  }
  function atualizar() {
    var l = mudancas(), r = document.getElementById('resumoMudancas');
    if (r) { r.textContent = l.length ? (l.length === 1 ? '1 mudança ainda não salva.' : l.length + ' mudanças ainda não salvas.') : 'Nada mudou ainda.'; }
  }
  form.addEventListener('change', atualizar);
  form.addEventListener('input', atualizar);
  form.addEventListener('submit', function (ev) {
    var l = mudancas();
    if (!l.length) {
      ev.preventDefault();
      var r = document.getElementById('resumoMudancas');
      if (r) { r.textContent = 'Nada mudou: altere uma linha antes de salvar.'; r.classList.add('text-danger'); }
      return;
    }
    form.dataset.vcConfirmaExtra = '<ul class="text-start mb-0 ps-3" id="mudancasLiberacao">' + l.map(function (t) { return '<li>' + esc(t) + '</li>'; }).join('') + '</ul>';
  });
})();
</script>
<?php endif; ?>
