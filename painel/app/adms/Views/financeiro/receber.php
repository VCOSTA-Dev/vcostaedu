<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* RECEBER PAGAMENTO (Onda 8, financeiro refeito, Parte 1; D-99), como a de origem: um título (pelo menu) ou vários (pelo
   modo lote). No alto, a volta para a ficha e para a matrícula, e o aluno; a data do recebimento (a conta de cada título
   é refeita na tela ao mudar, e de novo pelo servidor ao gravar); a tabela dos títulos; os quadros (o total original, os
   encargos, o acréscimo, o desconto e o valor a receber; com acréscimo ou desconto, a justificativa); as formas de
   pagamento, cada uma num cartão que abre, e "Múltiplos" (de 2 a 6 formas, com "Falta / Fechou / Passou"); e os dois
   botões. A confirmação é a janela do próprio sistema. */
use App\adms\Models\financeiro\AdmsFinanceiro as F;
use App\adms\Models\financeiro\AdmsFinFicha as Ficha;

$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$d = $this->dados;
$a = $d['aluno'];
$c = $d['conta'];
$cab = $d['cabecalho'] ?: []; // a matrícula do ano (vazia no ano sem matrícula: o traço)
$serieAno = trim((string) ($cab['segmento'] ?? '') . (!empty($cab['serie']) ? ' · ' . $cab['serie'] : ''));
$anosTit = $c['anos'] ?? [(int) $c['ano']];
$alunoId = (int) $a['id'];
$dt = fn($x) => $x ? date('d/m/Y', strtotime((string) $x)) : '';
$brl = fn($v) => 'R$ ' . F::brl((int) $v);
$rotNum = Ficha::rotuloNumero();
$hoje = date('Y-m-d');
$ids = array_map(static fn($t) => (int) $t['id'], $c['titulos']);
$encargos = $c['multa'] + $c['juros'] - $c['pontualidade'];
?>
<div class="vc-pagina" id="receberPagamento" data-aluno="<?= $h($alunoId) ?>">
  <?php ob_start(); ?>Receber pagamento<?php $paginaTitulo = ob_get_clean();
  $paginaAcoesHtml = '<a href="' . URLADM . 'financeiro/ficha/' . $alunoId . '?ano=' . (int) $c['ano'] . '" class="btn btn-outline-secondary" id="voltarFicha"><i class="fas fa-arrow-left me-1"></i> Ficha financeira</a>'
    . '<a href="' . URLADM . 'academicos/visualizar/' . $alunoId . '" class="btn btn-outline-secondary" id="voltarMatricula"><i class="fas fa-user me-1"></i> Matrícula</a>';
  include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-fluid">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <form method="post" action="<?= URLADM ?>financeiro/receber/<?= $h($alunoId) ?>" id="formReceber" data-vc-confirma="Receber" autocomplete="off">
        <input type="hidden" name="envio" value="<?= $h(bin2hex(random_bytes(12))) ?>">
        <?php foreach ($ids as $id) : ?><input type="hidden" name="titulos[]" value="<?= $h($id) ?>"><?php endforeach; ?>
        <input type="hidden" name="modo" id="modoFormas" value="unica">

        <div class="card mb-3" id="receberAluno"><div class="card-body py-2">
          <div class="row g-2 align-items-center">
            <div class="col-md-5"><div class="small text-secondary">Aluno</div><div class="fw-bold"><?= $h($rotNum) ?> <?= $h($a['rm']) ?> — <?= $h($a['nome_social'] ?: $a['nome']) ?></div></div>
            <div class="col-md-3"><div class="small text-secondary"><?= $h(\App\adms\Models\helper\AdmsTipoInstituicao::termo('curso', false, true)) ?> / <?= $h(\App\adms\Models\helper\AdmsTipoInstituicao::termo('modulo', false, true)) ?> (<?= $h($c['ano']) ?>)</div>
              <div id="serieDoAno"><?= $h($serieAno !== '' ? $serieAno : '—') ?></div></div>
            <div class="col-md-4"><div class="small text-secondary"><?= $d['pagador']['aluno'] ? 'Pagador' : 'Responsável financeiro' ?> (<?= $h($c['ano']) ?>)</div><div id="pagador"><?= $h($d['pagador']['nome']) ?></div></div>
          </div>
          <?php if (count($anosTit) > 1) : ?>
            <div class="alert alert-warning mt-2 mb-0 py-2" id="avisoAnosReceber">Os títulos são de anos letivos diferentes (<?= $h(implode(' e ', $anosTit)) ?>): o recibo sai em nome do responsável financeiro de <?= $h($c['ano']) ?>, o ano letivo corrente.</div>
          <?php endif; ?>
        </div></div>

        <div class="card mb-3">
          <div class="card-body">
            <div class="d-flex align-items-end gap-3 flex-wrap mb-3">
              <div><label class="form-label" for="dataRecebimento">Data do recebimento</label>
                <input type="date" class="form-control" id="dataRecebimento" name="data" value="<?= $h($d['data']) ?>" max="<?= $h($hoje) ?>" required></div>
              <div class="small text-secondary pb-2" id="avisoData" aria-live="polite">Hoje por padrão; pode ser um dia anterior. Os juros e a multa saem da data.</div>
            </div>
            <div class="table-responsive">
              <table class="table table-sm table-bordered table-vcenter" id="tabelaReceber">
                <thead><tr><th>Título</th><th>Venc.</th><th>Descrição</th><th class="text-end">Valor original</th><th class="text-end">Juros</th><th class="text-end">Multa</th><th class="text-end">Valor atualizado</th></tr></thead>
                <tbody>
                <?php foreach ($c['titulos'] as $t) : $e = $t['e']; ?>
                  <tr data-titulo="<?= $h($t['id']) ?>" data-encargos="<?= URLADM ?>financeiro/encargos/<?= $h($t['id']) ?>">
                    <td><?= $h($t['id']) ?></td><td class="text-nowrap"><?= $h($dt($t['vencimento'])) ?></td><td><?= $h($t['descricao']) ?></td>
                    <td class="text-end text-nowrap vc-original" data-c="<?= $h($e['original']) ?>"><?= $h($brl($e['original'])) ?></td>
                    <td class="text-end text-nowrap vc-juros" data-c="<?= $h($e['juros']) ?>"><?= $h($brl($e['juros'])) ?></td>
                    <td class="text-end text-nowrap vc-multa" data-c="<?= $h($e['multa'] - $e['desconto']) ?>"><?= $e['desconto'] ? '−' . $h($brl($e['desconto'])) . ' <small class="text-secondary">(desconto)</small>' : $h($brl($e['multa'])) ?></td>
                    <td class="text-end text-nowrap fw-bold vc-atualizado" data-c="<?= $h($e['total']) ?>"><?= $h($brl($e['total'])) ?></td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot><tr class="fw-bold"><td colspan="3">Total (<?= count($c['titulos']) ?> título(s))</td>
                  <td class="text-end text-nowrap" id="totOriginal"><?= $h($brl($c['original'])) ?></td><td class="text-end text-nowrap" id="totJuros"><?= $h($brl($c['juros'])) ?></td>
                  <td class="text-end text-nowrap" id="totMulta"><?= $h($brl($c['multa'] - $c['pontualidade'])) ?></td><td class="text-end text-nowrap" id="totAtualizado"><?= $h($brl($c['devido'])) ?></td></tr></tfoot>
              </table>
            </div>

            <div class="row g-2 mt-2" id="quadros">
              <div class="col-6 col-md"><div class="card card-sm"><div class="card-body p-2"><div class="small text-secondary">Total original</div><div class="h3 mb-0" id="qOriginal"><?= $h($brl($c['original'])) ?></div></div></div></div>
              <div class="col-6 col-md"><div class="card card-sm"><div class="card-body p-2"><div class="small text-secondary">Encargos</div><div class="h3 mb-0" id="qEncargos"><?= $h($brl($encargos)) ?></div></div></div></div>
              <div class="col-6 col-md"><div class="card card-sm"><div class="card-body p-2"><label class="small text-secondary" for="acrescimo">Acréscimo (R$)</label><input type="text" inputmode="decimal" class="form-control form-control-sm" id="acrescimo" name="acrescimo" placeholder="0,00"></div></div></div>
              <div class="col-6 col-md"><div class="card card-sm"><div class="card-body p-2"><label class="small text-secondary" for="desconto">Desconto (R$)</label><input type="text" inputmode="decimal" class="form-control form-control-sm" id="desconto" name="desconto" placeholder="0,00"></div></div></div>
              <div class="col-12 col-md"><div class="card card-sm bg-primary-lt"><div class="card-body p-2"><div class="small">Valor a receber</div><div class="h2 mb-0" id="qReceber" data-c="<?= $h($c['devido']) ?>"><?= $h($brl($c['devido'])) ?></div></div></div></div>
            </div>
            <div class="mt-2 d-none" id="blocoJustificativa"><label class="form-label required" for="justificativa">Justificativa do acréscimo ou do desconto</label>
              <input type="text" class="form-control" id="justificativa" name="justificativa" maxlength="500" minlength="5" placeholder="Ex.: desconto combinado com a direção"></div>
            <?php if (count($c['titulos']) > 1) : ?><div class="form-hint mt-1">O acréscimo e o desconto são rateados entre os títulos pelo valor atualizado de cada um, fechando ao centavo.</div><?php endif; ?>
          </div>
        </div>

        <!-- AS FORMAS DE PAGAMENTO -->
        <div class="card mb-3" id="formasPagamento">
          <div class="card-header"><h3 class="card-title">Forma de pagamento</h3></div>
          <div class="card-body">
            <div class="row g-2 mb-3" role="radiogroup" aria-label="Forma de pagamento" id="cartoesForma">
              <?php foreach (['dinheiro' => ['Dinheiro', 'fa-money-bill-wave'], 'pix' => ['Pix manual', 'fa-qrcode'], 'cartao' => ['Cartão', 'fa-credit-card'], 'multiplo' => ['Múltiplos', 'fa-layer-group']] as $k => [$rot, $ico]) : ?>
                <div class="col-6 col-sm-4 col-md"><label class="form-selectgroup-item w-100 vc-cartao-forma">
                  <input type="radio" name="forma_escolhida" value="<?= $h($k) ?>" class="form-selectgroup-input vc-escolhe-forma"<?= $k === 'dinheiro' ? ' checked' : '' ?>>
                  <span class="form-selectgroup-label vc-forma-rotulo"><span class="vc-forma-icone" aria-hidden="true"><i class="fas <?= $h($ico) ?>"></i></span><span class="vc-forma-nome"><?= $h($rot) ?></span></span>
                </label></div>
              <?php endforeach; ?>
            </div>
            <div id="formaUnica"></div>
            <div id="formaMultipla" class="d-none">
              <div id="linhasFormas"></div>
              <div class="d-flex align-items-center gap-3 mt-2">
                <button type="button" class="btn btn-sm btn-outline-primary" id="maisForma"><i class="fas fa-plus me-1"></i> Mais uma forma</button>
                <span class="fw-bold" id="indicadorMultiplo" aria-live="polite"></span>
              </div>
            </div>
            <div class="mt-3"><label class="form-label" for="observacao">Observação <small class="text-muted">(opcional; sai no recibo)</small></label>
              <input type="text" class="form-control" id="observacao" name="observacao" maxlength="500"></div>
          </div>
          <div class="card-footer d-flex gap-2 justify-content-end">
            <button type="submit" name="depois" value="ficha" class="btn btn-outline-success" id="receberFinalizar" data-vc-rotulo="Receber e finalizar">Receber e finalizar</button>
            <button type="submit" name="depois" value="recibo" class="btn btn-success" id="receberRecibo" data-vc-rotulo="Receber e emitir recibo"><i class="fas fa-receipt me-1"></i> Receber e emitir recibo</button>
          </div>
        </div>
      </form>
    </div>
  </section>
</div>
<style>
  /* os cartões das formas: o ícone em cima, o nome embaixo, sem sobrepor (o rótulo do Tabler põe o conteúdo numa linha) */
  .vc-cartao-forma .vc-forma-rotulo { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .35rem; padding: .75rem .5rem; min-height: 5rem; text-align: center; white-space: normal; }
  .vc-cartao-forma .vc-forma-icone { display: block; font-size: 1.35rem; line-height: 1; }
  .vc-cartao-forma .vc-forma-nome { display: block; line-height: 1.2; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var BANDEIRAS = <?= json_encode(array_map(static fn($b) => ['id' => (int) $b['id'], 'nome' => $b['nome']], $d['bandeiras']), JSON_UNESCAPED_UNICODE) ?>;
  var HOJE = '<?= $h($hoje) ?>';
  var NOMES = { dinheiro: 'Dinheiro', pix: 'Pix manual', cartao: 'Cartão' };
  var form = document.getElementById('formReceber');
  /* um valor que não é número nunca chega à tela como "NaN": vira um traço */
  var brl = function (c) { c = Number(c); if (!Number.isFinite(c)) { return '—'; } return (c < 0 ? '−' : '') + 'R$ ' + (Math.abs(c) / 100).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };
  var dataValida = function (v) { var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(v || ''); return !!m && +m[1] >= 2000 && +m[1] <= 2100 && v <= HOJE; };
  var contaValida = function (j) { return j && j.ok === true && ['original', 'multa', 'juros', 'desconto', 'total'].every(function (k) { return Number.isFinite(Number(j[k])); }); };
  var centavos = function (v) { // "1.234,56" → 123456; vazio → 0; inválido → NaN
    v = String(v || '').replace(/R\$|\s/g, '');
    if (v === '') { return 0; }
    if (v.indexOf(',') >= 0) { v = v.replace(/\./g, '').replace(',', '.'); }
    if (!/^\d+(\.\d{1,2})?$/.test(v)) { return NaN; }
    return Math.round(parseFloat(v) * 100);
  };
  var esc = function (s) { return String(s).replace(/[&<>"]/g, function (x) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[x]; }); };

  /* os campos de cada forma (o nome é o da linha: formas[i][...]) */
  function campos(i, forma) {
    var n = function (k) { return 'formas[' + i + '][' + k + ']'; };
    if (forma === 'pix') {
      return '<div class="col-md-4"><label class="form-label required">CPF ou CNPJ de quem pagou</label><input type="text" inputmode="numeric" class="form-control vc-campo" name="' + n('documento') + '" required maxlength="18"></div>';
    }
    if (forma === 'cartao') {
      var opts = BANDEIRAS.map(function (b) { return '<option value="' + b.id + '">' + esc(b.nome) + '</option>'; }).join('');
      return '<div class="col-md-2"><label class="form-label required">Tipo</label><select class="form-select vc-campo vc-cartao-tipo" name="' + n('cartao_tipo') + '"><option value="credito">Crédito</option><option value="debito">Débito</option></select></div>'
        + '<div class="col-md-1"><label class="form-label required">Parcelas</label><select class="form-select vc-campo vc-cartao-parcelas" name="' + n('cartao_parcelas') + '">' + Array.from({ length: 12 }, function (_, k) { return '<option>' + (k + 1) + '</option>'; }).join('') + '</select></div>'
        + '<div class="col-md-2"><label class="form-label required">Bandeira</label><select class="form-select vc-campo" name="' + n('bandeira_id') + '" required><option value="">—</option>' + opts + '</select></div>'
        + '<div class="col-md-2"><label class="form-label required">Final do cartão</label><input type="text" inputmode="numeric" class="form-control vc-campo" name="' + n('cartao_final') + '" maxlength="4" pattern="\\d{4}" required placeholder="1234"></div>'
        + '<div class="col-md-2"><label class="form-label required">Nº do documento</label><input type="text" class="form-control vc-campo" name="' + n('cartao_documento') + '" maxlength="40" required></div>'
        + '<div class="col-md-2"><label class="form-label required">Nº da autorização</label><input type="text" class="form-control vc-campo" name="' + n('cartao_autorizacao') + '" maxlength="40" required></div>';
    }
    return '<div class="col-md-6 text-secondary small align-self-end pb-2">Dinheiro: não pede nada.</div>';
  }

  /* uma forma só */
  function unica(forma) {
    document.getElementById('modoFormas').value = 'unica';
    document.getElementById('formaMultipla').classList.add('d-none');
    document.getElementById('linhasFormas').innerHTML = '';
    var u = document.getElementById('formaUnica');
    u.classList.remove('d-none');
    u.innerHTML = '<input type="hidden" name="formas[0][forma]" value="' + forma + '"><div class="row g-2" data-forma="' + forma + '">' + campos(0, forma) + '</div>';
  }

  /* Múltiplos: de 2 a 6 linhas, cada uma com a forma, o valor e os dados dela */
  var proxima = 0;
  function linha(forma, valor) {
    var i = proxima++;
    var div = document.createElement('div');
    div.className = 'border rounded p-2 mb-2 vc-linha-forma';
    div.dataset.i = i;
    var sel = '<select class="form-select vc-forma-linha" name="formas[' + i + '][forma]">' + Object.keys(NOMES).map(function (k) { return '<option value="' + k + '"' + (k === forma ? ' selected' : '') + '>' + NOMES[k] + '</option>'; }).join('') + '</select>';
    div.innerHTML = '<div class="row g-2 align-items-end"><div class="col-md-2"><label class="form-label">Forma</label>' + sel + '</div>'
      + '<div class="col-md-2"><label class="form-label required">Valor (R$)</label><input type="text" inputmode="decimal" class="form-control vc-valor-linha" name="formas[' + i + '][valor]" value="' + esc(valor || '') + '" required></div>'
      + '<div class="col"><div class="row g-2 vc-campos-linha">' + campos(i, forma) + '</div></div>'
      + '<div class="col-auto"><button type="button" class="btn btn-icon btn-outline-danger vc-tira-linha" title="Tirar esta forma"><i class="fas fa-trash"></i></button></div></div>';
    document.getElementById('linhasFormas').appendChild(div);
    contaMultiplo();
  }
  function multiplo() {
    document.getElementById('modoFormas').value = 'multiplo';
    var u = document.getElementById('formaUnica');
    u.innerHTML = '';
    u.classList.add('d-none');
    document.getElementById('formaMultipla').classList.remove('d-none');
    if (!document.querySelectorAll('.vc-linha-forma').length) { linha('dinheiro'); linha('pix'); }
  }
  function contaMultiplo() {
    var linhas = document.querySelectorAll('.vc-linha-forma');
    document.getElementById('maisForma').disabled = linhas.length >= 6;
    linhas.forEach(function (l) { l.querySelector('.vc-tira-linha').disabled = linhas.length <= 2; });
    if (document.getElementById('modoFormas').value !== 'multiplo') { return; }
    var soma = 0, ruim = false;
    linhas.forEach(function (l) { var v = centavos(l.querySelector('.vc-valor-linha').value); if (isNaN(v)) { ruim = true; } else { soma += v; } });
    var alvo = +document.getElementById('qReceber').dataset.c;
    var ind = document.getElementById('indicadorMultiplo');
    var dif = alvo - soma;
    ind.className = 'fw-bold ' + (ruim ? 'text-danger' : dif === 0 ? 'text-success' : 'text-danger');
    ind.textContent = ruim ? 'Algum valor não é um número' : dif === 0 ? 'Fechou: ' + brl(soma) : dif > 0 ? 'Falta ' + brl(dif) + ' (somam ' + brl(soma) + ')' : 'Passou ' + brl(-dif) + ' (somam ' + brl(soma) + ')';
    ind.dataset.fechou = (!ruim && dif === 0) ? '1' : '0';
  }
  document.getElementById('maisForma').addEventListener('click', function () { linha('dinheiro'); });
  document.getElementById('linhasFormas').addEventListener('click', function (ev) {
    var b = ev.target.closest('.vc-tira-linha');
    if (b && document.querySelectorAll('.vc-linha-forma').length > 2) { b.closest('.vc-linha-forma').remove(); contaMultiplo(); }
  });
  document.getElementById('linhasFormas').addEventListener('change', function (ev) {
    if (ev.target.classList.contains('vc-forma-linha')) {
      var l = ev.target.closest('.vc-linha-forma');
      l.querySelector('.vc-campos-linha').innerHTML = campos(l.dataset.i, ev.target.value);
    }
  });
  document.getElementById('linhasFormas').addEventListener('input', function (ev) { if (ev.target.classList.contains('vc-valor-linha')) { contaMultiplo(); } });
  document.addEventListener('change', function (ev) {
    if (ev.target.classList.contains('vc-cartao-tipo')) { // débito: uma parcela
      var p = ev.target.closest('.row').querySelector('.vc-cartao-parcelas');
      if (p) { p.value = '1'; p.disabled = ev.target.value === 'debito'; }
    }
  });
  document.querySelectorAll('.vc-escolhe-forma').forEach(function (r) {
    r.addEventListener('change', function () { if (r.checked) { r.value === 'multiplo' ? multiplo() : unica(r.value); } });
  });

  /* os quadros: o acréscimo e o desconto mudam o valor a receber; com algum deles, a justificativa */
  function quadros() {
    var devido = 0, orig = 0, juros = 0, multa = 0;
    document.querySelectorAll('#tabelaReceber tbody tr').forEach(function (tr) {
      orig += +tr.querySelector('.vc-original').dataset.c; juros += +tr.querySelector('.vc-juros').dataset.c;
      multa += +tr.querySelector('.vc-multa').dataset.c; devido += +tr.querySelector('.vc-atualizado').dataset.c;
    });
    var acr = centavos(document.getElementById('acrescimo').value), des = centavos(document.getElementById('desconto').value);
    acr = isNaN(acr) ? 0 : acr; des = isNaN(des) ? 0 : des;
    var receber = devido + acr - des;
    document.getElementById('totOriginal').textContent = brl(orig);
    document.getElementById('totJuros').textContent = brl(juros);
    document.getElementById('totMulta').textContent = brl(multa);
    document.getElementById('totAtualizado').textContent = brl(devido);
    document.getElementById('qOriginal').textContent = brl(orig);
    document.getElementById('qEncargos').textContent = brl(juros + multa);
    var q = document.getElementById('qReceber');
    q.textContent = brl(receber);
    q.dataset.c = receber;
    var j = document.getElementById('blocoJustificativa');
    j.classList.toggle('d-none', !(acr > 0 || des > 0));
    document.getElementById('justificativa').required = acr > 0 || des > 0;
    contaMultiplo();
  }
  document.getElementById('acrescimo').addEventListener('input', quadros);
  document.getElementById('desconto').addEventListener('input', quadros);

  /* a data: a conta de cada título, refeita na tela (o servidor refaz ao gravar) */
  var dataEl = document.getElementById('dataRecebimento');
  /* a data incompleta (o navegador manda cada pedaço enquanto se digita) não pede conta; a resposta sem os números (a data
     recusada, a sessão que caiu) não muda nada: a conta da última data boa fica, e o aviso diz o que houve */
  var aviso = document.getElementById('avisoData');
  var refazer = function () {
    var pedida = dataEl.value;
    if (!dataValida(pedida)) {
      aviso.textContent = pedida && pedida > HOJE ? 'A data do recebimento não pode ser depois de hoje.' : 'Escolha a data do recebimento (dia, mês e ano).';
      return;
    }
    var linhas = Array.prototype.slice.call(document.querySelectorAll('#tabelaReceber tbody tr'));
    aviso.textContent = 'Refazendo a conta para ' + pedida.split('-').reverse().join('/') + '…';
    Promise.all(linhas.map(function (tr) {
      return fetch(tr.dataset.encargos + '?data=' + encodeURIComponent(pedida), { credentials: 'same-origin' })
        .then(function (r) { return r.json(); }).catch(function () { return null; });
    })).then(function (res) {
      if (dataEl.value !== pedida) { return; } // a conta de uma data que já mudou chegou depois: vale a da data atual
      if (!res.every(contaValida)) {
        aviso.textContent = 'Não deu para fazer a conta de ' + pedida.split('-').reverse().join('/') + ' agora: a conta mostrada continua a da data anterior. O servidor refaz a conta ao gravar.';
        return;
      }
      res.forEach(function (j, k) {
        var tr = linhas[k];
        var multa = Number(j.multa), juros = Number(j.juros), desc = Number(j.desconto), total = Number(j.total);
        tr.querySelector('.vc-juros').dataset.c = juros; tr.querySelector('.vc-juros').textContent = brl(juros);
        var m = tr.querySelector('.vc-multa');
        m.dataset.c = multa - desc;
        m.innerHTML = desc ? '−' + brl(desc) + ' <small class="text-secondary">(desconto)</small>' : brl(multa);
        tr.querySelector('.vc-atualizado').dataset.c = total; tr.querySelector('.vc-atualizado').textContent = brl(total);
      });
      aviso.textContent = 'A conta é a de ' + pedida.split('-').reverse().join('/') + '.';
      quadros();
    });
  };
  dataEl.addEventListener('change', refazer);
  dataEl.addEventListener('input', refazer);

  /* antes de confirmar: a forma única leva o total; o Múltiplos só vai quando fecha; a janela diz o total e a forma */
  form.addEventListener('submit', function (ev) {
    if (form.dataset.vcConfirmado === '1') { return; }
    var modo = document.getElementById('modoFormas').value;
    var total = +document.getElementById('qReceber').dataset.c;
    if (modo === 'multiplo' && document.getElementById('indicadorMultiplo').dataset.fechou !== '1') {
      ev.preventDefault(); ev.stopImmediatePropagation();
      document.getElementById('indicadorMultiplo').scrollIntoView({ block: 'center' });
      return;
    }
    var formas = modo === 'multiplo'
      ? Array.prototype.map.call(document.querySelectorAll('.vc-linha-forma'), function (l) { return NOMES[l.querySelector('.vc-forma-linha').value] + ' ' + brl(centavos(l.querySelector('.vc-valor-linha').value)); }).join(' + ')
      : NOMES[form.querySelector('[name="formas[0][forma]"]').value];
    form.dataset.vcConfirmaExtra = '<div class="h2 my-2">' + brl(total) + '</div><div>' + esc(formas) + '</div><div class="small mt-1">' + document.querySelectorAll('#tabelaReceber tbody tr').length + ' título(s), em ' + esc(dataEl.value.split('-').reverse().join('/')) + '</div>';
  }, true);

  unica('dinheiro');
});
</script>
