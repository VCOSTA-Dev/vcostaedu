<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* RENEGOCIAR TÍTULOS (Onda 8, financeiro refeito, Parte 2; D-105), no desenho do sistema de origem: aberta pela seleção
   da ficha (Em lote › Renegociar). No alto, a volta e o aluno; os títulos, com a data da renegociação (a conta de cada um
   é refeita ao mudar a data, pelo servidor); os quadros (o total original, os encargos, o acréscimo, o desconto e o valor
   a renegociar; com acréscimo ou desconto, a justificativa); as duas condições (a parcelada e a à vista, cada uma com o
   "interesse"); "Gerar proposta" monta as parcelas de cada uma; depois, o motivo do acordo, a observação e os três
   botões. Toda confirmação é a janela do sistema; o servidor refaz a conta ao gravar e recusa se ela mudou. */
use App\adms\Models\financeiro\AdmsFinanceiro as F;
use App\adms\Models\financeiro\AdmsFinFicha as Ficha;
use App\adms\Models\financeiro\AdmsFinRenegociacao as RN;

$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$d = $this->dados;
$a = $d['aluno'];
$k = $d['conta'];
$c = $k['conta'];
$cab = $d['cabecalho'] ?: [];
$serieAno = trim((string) ($cab['segmento'] ?? '') . (!empty($cab['serie']) ? ' · ' . $cab['serie'] : ''));
$alunoId = (int) $a['id'];
$dt = fn($x) => $x ? date('d/m/Y', strtotime((string) $x)) : '';
$brl = fn($v) => 'R$ ' . F::brl((int) $v);
$rotNum = Ficha::rotuloNumero();
$hoje = date('Y-m-d');
$ids = array_map(static fn($t) => (int) $t['id'], $c['titulos']);
$anosTit = $c['anos'] ?? [(int) $c['ano']];
$diaPadrao = (int) date('j', strtotime($hoje)) > 28 ? 10 : (int) date('j', strtotime($hoje));
?>
<div class="vc-pagina" id="renegociarTitulos" data-aluno="<?= $h($alunoId) ?>">
  <?php ob_start(); ?>Renegociar títulos<?php $paginaTitulo = ob_get_clean();
  $paginaAcoesHtml = '<a href="' . URLADM . 'financeiro/ficha/' . $alunoId . '?ano=' . (int) $c['ano'] . '" class="btn btn-outline-secondary" id="voltarFicha"><i class="fas fa-arrow-left me-1"></i> Ficha financeira</a>'
    . '<a href="' . URLADM . 'academicos/visualizar/' . $alunoId . '" class="btn btn-outline-secondary" id="voltarMatricula"><i class="fas fa-user me-1"></i> Matrícula</a>';
  include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-fluid">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <form method="post" action="<?= URLADM ?>financeiro/renegociar/<?= $h($alunoId) ?>" id="formRenegociar" autocomplete="off">
        <input type="hidden" name="envio" value="<?= $h(bin2hex(random_bytes(12))) ?>">
        <?php foreach ($ids as $id) : ?><input type="hidden" name="titulos[]" value="<?= $h($id) ?>"><?php endforeach; ?>
        <input type="hidden" name="valor_tela" id="valorTela" value="<?= $h($k['valor']) ?>">

        <div class="card mb-3" id="renegociarAluno"><div class="card-body py-2">
          <div class="row g-2 align-items-center">
            <div class="col-md-5"><div class="small text-secondary">Aluno</div><div class="fw-bold"><?= $h($rotNum) ?> <?= $h($a['rm']) ?> — <?= $h($a['nome_social'] ?: $a['nome']) ?></div></div>
            <div class="col-md-3"><div class="small text-secondary"><?= $h(\App\adms\Models\helper\AdmsTipoInstituicao::termo('curso', false, true)) ?> / <?= $h(\App\adms\Models\helper\AdmsTipoInstituicao::termo('modulo', false, true)) ?> (<?= $h($c['ano']) ?>)</div>
              <div><?= $h($serieAno !== '' ? $serieAno : '—') ?></div></div>
            <div class="col-md-4"><div class="small text-secondary"><?= $d['pagador']['aluno'] ? 'Devedor' : 'Responsável financeiro' ?> (<?= $h($c['ano']) ?>)</div><div id="pagador"><?= $h($d['pagador']['nome']) ?></div></div>
          </div>
          <?php if (count($anosTit) > 1) : ?>
            <div class="alert alert-warning mt-2 mb-0 py-2" id="avisoAnosRenegociar">Os títulos são de anos letivos diferentes (<?= $h(implode(' e ', $anosTit)) ?>): o acordo fica em <?= $h($c['ano']) ?>, o ano letivo corrente, em nome do responsável financeiro desse ano.</div>
          <?php endif; ?>
        </div></div>

        <!-- OS TÍTULOS QUE SERÃO RENEGOCIADOS -->
        <div class="card mb-3">
          <div class="card-header"><h3 class="card-title">Os títulos que serão renegociados</h3></div>
          <div class="card-body">
            <div class="d-flex align-items-end gap-3 flex-wrap mb-3">
              <div><label class="form-label" for="dataRenegociacao">Data da renegociação</label>
                <input type="date" class="form-control" id="dataRenegociacao" name="data" value="<?= $h($k['data']) ?>" max="<?= $h($hoje) ?>" required></div>
              <div class="small text-secondary pb-2" id="avisoData" aria-live="polite">Hoje por padrão; pode ser um dia anterior. Os juros e a multa saem da data, e a proposta vale até ela.</div>
            </div>
            <div class="table-responsive">
              <table class="table table-sm table-bordered table-vcenter" id="tabelaRenegociar">
                <thead><tr><th>Título</th><th>Venc.</th><th>Descrição</th><th class="text-center">Parcela</th><th class="text-end">Valor original</th><th class="text-end">Juros</th><th class="text-end">Multa</th><th class="text-end">Valor atualizado</th></tr></thead>
                <tbody>
                <?php foreach ($c['titulos'] as $t) : $e = $t['e']; ?>
                  <tr data-titulo="<?= $h($t['id']) ?>" data-encargos="<?= URLADM ?>financeiro/encargos/<?= $h($t['id']) ?>">
                    <td><?= $h($t['id']) ?></td><td class="text-nowrap"><?= $h($dt($t['vencimento'])) ?></td><td><?= $h($t['descricao']) ?></td>
                    <td class="text-center"><?= $h($t['parcela'] !== null ? ($t['renegociacao_id'] ? RN::rotuloParcela((int) $t['parcela'], (int) $t['parcelas_total']) : $t['parcela'] . ($t['parcelas_total'] ? '.' . $t['parcelas_total'] : '')) : '—') ?></td>
                    <td class="text-end text-nowrap vc-original" data-c="<?= $h($e['original']) ?>"><?= $h($brl($e['original'])) ?></td>
                    <td class="text-end text-nowrap vc-juros" data-c="<?= $h($e['juros']) ?>"><?= $h($brl($e['juros'])) ?></td>
                    <td class="text-end text-nowrap vc-multa" data-c="<?= $h($e['multa'] - $e['desconto']) ?>"><?= $e['desconto'] ? '−' . $h($brl($e['desconto'])) . ' <small class="text-secondary">(desconto)</small>' : $h($brl($e['multa'])) ?></td>
                    <td class="text-end text-nowrap fw-bold vc-atualizado" data-c="<?= $h($e['total']) ?>"><?= $h($brl($e['total'])) ?></td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot><tr class="fw-bold"><td colspan="4">Total (<?= $h(\App\adms\Models\helper\AdmsUi::plural(count($c['titulos']), 'título', 'títulos')) ?>)</td>
                  <td class="text-end text-nowrap" id="totOriginal"><?= $h($brl($c['original'])) ?></td><td class="text-end text-nowrap" id="totJuros"><?= $h($brl($c['juros'])) ?></td>
                  <td class="text-end text-nowrap" id="totMulta"><?= $h($brl($c['multa'] - $c['pontualidade'])) ?></td><td class="text-end text-nowrap" id="totAtualizado"><?= $h($brl($c['devido'])) ?></td></tr></tfoot>
              </table>
            </div>
            <div class="row g-2 mt-2" id="quadros">
              <div class="col-6 col-md"><div class="card card-sm"><div class="card-body p-2"><div class="small text-secondary">Total original</div><div class="h3 mb-0" id="qOriginal"><?= $h($brl($c['original'])) ?></div></div></div></div>
              <div class="col-6 col-md"><div class="card card-sm"><div class="card-body p-2"><div class="small text-secondary">Encargos</div><div class="h3 mb-0" id="qEncargos"><?= $h($brl($c['multa'] + $c['juros'] - $c['pontualidade'])) ?></div></div></div></div>
              <div class="col-6 col-md"><div class="card card-sm"><div class="card-body p-2"><label class="small text-secondary" for="acrescimo">Acréscimo (R$)</label><input type="text" inputmode="decimal" class="form-control form-control-sm vc-muda" id="acrescimo" name="acrescimo" placeholder="0,00"></div></div></div>
              <div class="col-6 col-md"><div class="card card-sm"><div class="card-body p-2"><label class="small text-secondary" for="desconto">Desconto (R$)</label><input type="text" inputmode="decimal" class="form-control form-control-sm vc-muda" id="desconto" name="desconto" placeholder="0,00"></div></div></div>
              <div class="col-12 col-md"><div class="card card-sm bg-primary-lt"><div class="card-body p-2"><div class="small">Valor a renegociar</div><div class="h2 mb-0" id="qRenegociar" data-c="<?= $h($k['valor']) ?>"><?= $h($brl($k['valor'])) ?></div></div></div></div>
            </div>
            <div class="mt-2 d-none" id="blocoJustificativa"><label class="form-label required" for="justificativa">Justificativa do acréscimo ou do desconto</label>
              <input type="text" class="form-control vc-muda" id="justificativa" name="justificativa" maxlength="500" minlength="5" placeholder="Ex.: desconto combinado com a direção"></div>
          </div>
        </div>

        <!-- AS DUAS CONDIÇÕES -->
        <div class="row g-3 mb-3" id="condicoes">
          <div class="col-lg-6">
            <div class="card h-100" id="cartaoParcelada">
              <div class="card-header"><h3 class="card-title">Proposta parcelada</h3>
                <div class="card-actions"><label class="form-check form-switch mb-0"><input class="form-check-input" type="checkbox" name="p_interesse" value="1" id="pInteresse" checked><span class="form-check-label">Interesse</span></label></div></div>
              <div class="card-body">
                <div class="row g-2">
                  <div class="col-sm-6"><label class="form-label" for="pEntrada">Entrada</label>
                    <div class="input-group"><select class="form-select vc-muda" name="p_entrada_tipo" id="pEntradaTipo" style="max-width:5.5rem" aria-label="A entrada em reais ou em porcentagem"><option value="reais">R$</option><option value="pct">%</option></select>
                      <input type="text" inputmode="decimal" class="form-control vc-muda" name="p_entrada" id="pEntrada" placeholder="0,00"></div></div>
                  <div class="col-sm-6"><label class="form-label" for="pEntradaVenc">Vencimento da entrada</label><input type="date" class="form-control vc-muda" name="p_entrada_vencimento" id="pEntradaVenc" value="<?= $h($hoje) ?>"></div>
                  <div class="col-sm-6"><div class="small text-secondary">Saldo restante</div><div class="fw-bold" id="pSaldo">—</div></div>
                  <div class="col-sm-3"><label class="form-label" for="pParcelas">Qtde parc.</label><input type="number" min="1" max="<?= RN::MAX_PARCELAS ?>" class="form-control vc-muda" name="p_parcelas" id="pParcelas" value="3"></div>
                  <div class="col-sm-3"><label class="form-label" for="pDia">Vencimento todo dia</label><input type="number" min="1" max="28" class="form-control vc-muda" name="p_dia" id="pDia" value="<?= $h($diaPadrao) ?>"></div>
                  <div class="col-12"><div class="small text-secondary">Valor da parcela</div><div class="fw-bold" id="pValorParcela">—</div></div>
                </div>
                <table class="table table-sm mt-3 mb-0" id="tabelaParcelada"><thead><tr><th>Parcela</th><th>Vencimento</th><th class="text-end">Valor</th></tr></thead>
                  <tbody><tr class="vc-vazio"><td colspan="3" class="text-secondary">Nenhuma proposta gerada ainda.</td></tr></tbody></table>
              </div>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="card h-100" id="cartaoAvista">
              <div class="card-header"><h3 class="card-title">Proposta à vista</h3>
                <div class="card-actions"><label class="form-check form-switch mb-0"><input class="form-check-input" type="checkbox" name="v_interesse" value="1" id="vInteresse" checked><span class="form-check-label">Interesse</span></label></div></div>
              <div class="card-body">
                <div class="row g-2">
                  <div class="col-sm-6"><label class="form-label" for="vDesconto">Desconto</label>
                    <div class="input-group"><select class="form-select vc-muda" name="v_desconto_tipo" id="vDescontoTipo" style="max-width:5.5rem" aria-label="O desconto em reais ou em porcentagem"><option value="reais">R$</option><option value="pct">%</option></select>
                      <input type="text" inputmode="decimal" class="form-control vc-muda" name="v_desconto" id="vDesconto" placeholder="0,00"></div></div>
                  <div class="col-sm-6"><label class="form-label" for="vVencimento">Vencimento</label><input type="date" class="form-control vc-muda" name="v_vencimento" id="vVencimento" value="<?= $h($hoje) ?>"></div>
                  <div class="col-12"><div class="small text-secondary">Valor a pagar</div><div class="fw-bold" id="vValor">—</div></div>
                </div>
                <table class="table table-sm mt-3 mb-0" id="tabelaAvista"><thead><tr><th>Parcela</th><th>Vencimento</th><th class="text-end">Valor</th></tr></thead>
                  <tbody><tr class="vc-vazio"><td colspan="3" class="text-secondary">Nenhuma proposta gerada ainda.</td></tr></tbody></table>
              </div>
            </div>
          </div>
          <div class="col-12 text-center"><button type="button" class="btn btn-primary" id="gerarProposta"><i class="fas fa-calculator me-1"></i> Gerar proposta</button>
            <div class="small text-danger mt-1" id="erroProposta" aria-live="polite"></div></div>
        </div>

        <!-- DEPOIS DE GERAR: O MOTIVO, A OBSERVAÇÃO E OS BOTÕES -->
        <div class="card mb-3 d-none" id="cartaoAcordo">
          <div class="card-header"><h3 class="card-title">Condições</h3></div>
          <div class="card-body row g-2">
            <div class="col-md-5"><label class="form-label required" for="motivoAcordo">Motivo do acordo</label>
              <select class="form-select" name="motivo_id" id="motivoAcordo" required>
                <?php foreach ($d['motivos'] as $mid => $mnome) : ?><option value="<?= $h($mid) ?>"><?= $h($mnome) ?></option><?php endforeach; ?>
              </select></div>
            <div class="col-md-7"><label class="form-label" for="observacaoAcordo">Observação <small class="text-muted">(opcional)</small></label>
              <input type="text" class="form-control" name="observacao" id="observacaoAcordo" maxlength="500"></div>
            <div class="col-12 small text-secondary">Como você deseja prosseguir? Salvar a proposta (vale até a data da renegociação e pode ser efetivada depois, na aba Renegociações) ou efetivar uma das condições agora.</div>
          </div>
          <div class="card-footer d-flex flex-wrap gap-2 justify-content-end">
            <button type="submit" name="acao" value="salvar" class="btn btn-outline-primary" id="salvarProposta" data-vc-confirma="Salvar a proposta sem efetivar?" data-vc-rotulo="Salvar a proposta"><i class="fas fa-save me-1"></i> Salvar proposta</button>
            <button type="submit" name="acao" value="avista" class="btn btn-success" id="efetivarAvista" data-vc-confirma="Efetivar a renegociação à vista?" data-vc-rotulo="Efetivar à vista"><i class="fas fa-check me-1"></i> Efetivar proposta à vista</button>
            <button type="submit" name="acao" value="parcelada" class="btn btn-success" id="efetivarParcelada" data-vc-confirma="Efetivar a renegociação parcelada?" data-vc-rotulo="Efetivar parcelada"><i class="fas fa-check-double me-1"></i> Efetivar proposta parcelada</button>
          </div>
        </div>
      </form>
    </div>
  </section>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var HOJE = '<?= $h($hoje) ?>';
  var MAXP = <?= (int) RN::MAX_PARCELAS ?>;
  var brl = function (c) { c = Number(c); if (!Number.isFinite(c)) { return '—'; } return (c < 0 ? '−' : '') + 'R$ ' + (Math.abs(c) / 100).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };
  var dataBR = function (v) { return v ? v.split('-').reverse().join('/') : ''; };
  var esc = function (s) { return String(s).replace(/[&<>"]/g, function (x) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[x]; }); };
  var dataValida = function (v) { var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(v || ''); return !!m && +m[1] >= 2000 && +m[1] <= 2100; };
  var centavos = function (v) { // "1.234,56" → 123456; vazio → 0; inválido → NaN
    v = String(v || '').replace(/R\$|\s/g, '');
    if (v === '') { return 0; }
    if (v.indexOf(',') >= 0) { v = v.replace(/\./g, '').replace(',', '.'); }
    if (!/^\d+(\.\d{1,2})?$/.test(v)) { return NaN; }
    return Math.round(parseFloat(v) * 100);
  };
  var pontos = function (v) { v = String(v || '').replace(/%|\s/g, '').replace(',', '.'); if (v === '') { return 0; } if (!/^\d{1,3}(\.\d{1,2})?$/.test(v)) { return NaN; } return Math.round(parseFloat(v) * 100); };
  var mult = function (a, b, d) { return Math.floor((2 * a * b + d) / (2 * d)); }; // o meio para cima, como no servidor
  var noMes = function (base, meses, dia) { // o mesmo dia, meses depois (o dia que não existe vira o último do mês)
    var y = +base.substr(0, 4), m = +base.substr(5, 2) - 1 + meses;
    y += Math.floor(m / 12); m = ((m % 12) + 12) % 12;
    var ult = new Date(Date.UTC(y, m + 1, 0)).getUTCDate();
    var dd = Math.min(dia, ult);
    return y + '-' + String(m + 1).padStart(2, '0') + '-' + String(dd).padStart(2, '0');
  };
  var rot = function (n, t) { return String(n).padStart(2, '0') + '.' + String(t).padStart(2, '0'); };
  var val = function (id) { return document.getElementById(id).value; };
  var gerada = false;

  /* os quadros: o acréscimo e o desconto mudam o valor a renegociar; com algum deles, a justificativa */
  function valor() {
    var devido = 0, orig = 0, juros = 0, multa = 0;
    document.querySelectorAll('#tabelaRenegociar tbody tr').forEach(function (tr) {
      orig += +tr.querySelector('.vc-original').dataset.c; juros += +tr.querySelector('.vc-juros').dataset.c;
      multa += +tr.querySelector('.vc-multa').dataset.c; devido += +tr.querySelector('.vc-atualizado').dataset.c;
    });
    var acr = centavos(val('acrescimo')), des = centavos(val('desconto'));
    document.getElementById('totOriginal').textContent = brl(orig); document.getElementById('totJuros').textContent = brl(juros);
    document.getElementById('totMulta').textContent = brl(multa); document.getElementById('totAtualizado').textContent = brl(devido);
    document.getElementById('qOriginal').textContent = brl(orig); document.getElementById('qEncargos').textContent = brl(juros + multa);
    var v = devido + (isNaN(acr) ? 0 : acr) - (isNaN(des) ? 0 : des);
    var q = document.getElementById('qRenegociar');
    q.textContent = brl(v); q.dataset.c = v;
    document.getElementById('valorTela').value = v;
    var com = (acr > 0 || des > 0);
    document.getElementById('blocoJustificativa').classList.toggle('d-none', !com);
    document.getElementById('justificativa').required = com;
    return { valor: v, ruim: isNaN(acr) || isNaN(des) };
  }

  /* as duas condições, a mesma conta do servidor (que refaz ao gravar) */
  function condicoes(v) {
    var data = val('dataRenegociacao');
    var tipo = val('pEntradaTipo'), bruto = val('pEntrada');
    var entrada = tipo === 'pct' ? mult(v, pontos(bruto), 10000) : centavos(bruto);
    var n = parseInt(val('pParcelas'), 10), dia = parseInt(val('pDia'), 10);
    if (isNaN(entrada) || (tipo === 'pct' && pontos(bruto) > 10000)) { return { erro: 'A entrada é um valor em reais ou uma porcentagem de 0 a 100.' }; }
    if (!(n >= 1 && n <= MAXP)) { return { erro: 'O número de parcelas vai de 1 a ' + MAXP + '.' }; }
    if (!(dia >= 1 && dia <= 28)) { return { erro: 'O dia do vencimento das parcelas vai de 1 a 28.' }; }
    if (entrada >= v) { return { erro: 'A entrada tem de ser menor que o valor a renegociar (' + brl(v) + ').' }; }
    var entVenc = val('pEntradaVenc');
    if (entrada > 0 && (!dataValida(entVenc) || entVenc < data)) { return { erro: 'Informe o vencimento da entrada (da data da renegociação em diante).' }; }
    var saldo = v - entrada, vp = Math.floor(saldo / n);
    if (vp < 1) { return { erro: 'O saldo não dá para ' + n + ' parcelas.' }; }
    var total = n + (entrada > 0 ? 1 : 0), base = entrada > 0 ? entVenc : data, parc = [];
    if (entrada > 0) { parc.push({ numero: 1, total: total, venc: entVenc, valor: entrada, entrada: true }); }
    for (var k = 1; k <= n; k++) { parc.push({ numero: k + (entrada > 0 ? 1 : 0), total: total, venc: noMes(base, k, dia), valor: k === n ? saldo - vp * (n - 1) : vp }); }
    var vt = val('vDescontoTipo'), vb = val('vDesconto');
    var vd = vt === 'pct' ? mult(v, pontos(vb), 10000) : centavos(vb);
    if (isNaN(vd) || (vt === 'pct' && pontos(vb) > 10000)) { return { erro: 'O desconto à vista é um valor em reais ou uma porcentagem de 0 a 100.' }; }
    if (vd >= v) { return { erro: 'O desconto à vista tem de ser menor que o valor a renegociar (' + brl(v) + ').' }; }
    var vv = dataValida(val('vVencimento')) ? val('vVencimento') : data;
    if (vv < data) { return { erro: 'O vencimento à vista não pode ser antes da data da renegociação.' }; }
    return { entrada: entrada, saldo: saldo, vp: vp, parc: parc, avista: { valor: v - vd, venc: vv } };
  }

  function mostrar() {
    var q = valor();
    var c = q.ruim ? { erro: 'O acréscimo e o desconto são valores em reais (por exemplo, 15,00).' } : condicoes(q.valor);
    var erro = document.getElementById('erroProposta');
    erro.textContent = c.erro || '';
    if (c.erro) { return false; }
    document.getElementById('pSaldo').textContent = brl(c.saldo);
    document.getElementById('pValorParcela').textContent = brl(c.vp) + (c.parc[c.parc.length - 1].valor !== c.vp ? ' (a última, ' + brl(c.parc[c.parc.length - 1].valor) + ')' : '');
    document.getElementById('vValor').textContent = brl(c.avista.valor);
    document.querySelector('#tabelaParcelada tbody').innerHTML = c.parc.map(function (x) {
      var campo = x.entrada ? esc(dataBR(x.venc)) : '<input type="date" class="form-control form-control-sm vc-venc-parcela" name="p_venc[' + x.numero + ']" value="' + esc(x.venc) + '" aria-label="O vencimento da parcela ' + rot(x.numero, x.total) + '">';
      return '<tr data-numero="' + x.numero + '"><td>' + rot(x.numero, x.total) + (x.entrada ? ' <small class="text-secondary">(entrada)</small>' : '') + '</td><td>' + campo + '</td><td class="text-end text-nowrap vc-valor-parcela" data-c="' + x.valor + '">' + brl(x.valor) + '</td></tr>';
    }).join('');
    document.querySelector('#tabelaAvista tbody').innerHTML = '<tr data-numero="1"><td>' + rot(1, 1) + '</td><td>' + esc(dataBR(c.avista.venc)) + '</td><td class="text-end text-nowrap vc-valor-parcela" data-c="' + c.avista.valor + '">' + brl(c.avista.valor) + '</td></tr>';
    var resumo = '<div class="h2 my-2">' + brl(q.valor) + '</div><div class="small">' + document.querySelectorAll('#tabelaRenegociar tbody tr').length + ' título(s), conta de ' + esc(dataBR(val('dataRenegociacao'))) + '</div>';
    document.getElementById('salvarProposta').dataset.vcConfirmaExtra = resumo + '<div class="small mt-1">Vale até ' + esc(dataBR(val('dataRenegociacao'))) + '.</div>';
    document.getElementById('efetivarAvista').dataset.vcConfirmaExtra = '<div class="h2 my-2">' + brl(c.avista.valor) + '</div><div class="small">à vista, com vencimento em ' + esc(dataBR(c.avista.venc)) + '</div>';
    document.getElementById('efetivarParcelada').dataset.vcConfirmaExtra = '<div class="h2 my-2">' + brl(q.valor) + '</div><div class="small">' + (c.entrada > 0 ? 'entrada de ' + brl(c.entrada) + ' + ' : '') + (c.parc.length - (c.entrada > 0 ? 1 : 0)) + ' × ' + brl(c.vp) + '</div>';
    document.getElementById('cartaoAcordo').classList.remove('d-none');
    gerada = true;
    return true;
  }
  /* mudou algo da conta: a proposta gerada não vale mais (a tela pede para gerar de novo) */
  function invalidar() {
    valor();
    if (!gerada) { return; }
    gerada = false;
    document.getElementById('cartaoAcordo').classList.add('d-none');
    ['#tabelaParcelada', '#tabelaAvista'].forEach(function (s) { document.querySelector(s + ' tbody').innerHTML = '<tr class="vc-vazio"><td colspan="3" class="text-secondary">A conta mudou: gere a proposta de novo.</td></tr>'; });
  }
  document.getElementById('gerarProposta').addEventListener('click', mostrar);
  document.querySelectorAll('.vc-muda').forEach(function (el) { el.addEventListener('input', invalidar); el.addEventListener('change', invalidar); });
  document.getElementById('pEntradaTipo').addEventListener('change', function () { document.getElementById('pEntrada').placeholder = this.value === 'pct' ? '0' : '0,00'; });
  document.getElementById('vDescontoTipo').addEventListener('change', function () { document.getElementById('vDesconto').placeholder = this.value === 'pct' ? '0' : '0,00'; });

  /* a data: a conta de cada título, refeita pelo servidor (a resposta sem os números não muda nada) */
  var dataEl = document.getElementById('dataRenegociacao');
  var aviso = document.getElementById('avisoData');
  var contaValida = function (j) { return j && j.ok === true && ['original', 'multa', 'juros', 'desconto', 'total'].every(function (k) { return Number.isFinite(Number(j[k])); }); };
  var feita = dataEl.value; // a data da conta mostrada (ou pedida): o "input" e o "change" da mesma data não refazem a conta duas vezes
  var refazer = function () {
    var pedida = dataEl.value;
    if (pedida === feita) { return; }
    feita = pedida;
    if (!dataValida(pedida) || pedida > HOJE) {
      aviso.textContent = pedida && pedida > HOJE ? 'A data da renegociação não pode ser depois de hoje.' : 'Escolha a data da renegociação (dia, mês e ano).';
      return;
    }
    var linhas = Array.prototype.slice.call(document.querySelectorAll('#tabelaRenegociar tbody tr'));
    aviso.textContent = 'Refazendo a conta para ' + dataBR(pedida) + '…';
    Promise.all(linhas.map(function (tr) {
      return fetch(tr.dataset.encargos + '?data=' + encodeURIComponent(pedida), { credentials: 'same-origin' }).then(function (r) { return r.json(); }).catch(function () { return null; });
    })).then(function (res) {
      if (dataEl.value !== pedida) { return; }
      if (!res.every(contaValida)) { feita = ''; aviso.textContent = 'Não deu para fazer a conta de ' + dataBR(pedida) + ' agora: a conta mostrada continua a da data anterior. O servidor refaz a conta ao gravar.'; return; }
      res.forEach(function (j, k) {
        var tr = linhas[k];
        tr.querySelector('.vc-juros').dataset.c = Number(j.juros); tr.querySelector('.vc-juros').textContent = brl(j.juros);
        var m = tr.querySelector('.vc-multa');
        m.dataset.c = Number(j.multa) - Number(j.desconto);
        m.innerHTML = Number(j.desconto) ? '−' + brl(j.desconto) + ' <small class="text-secondary">(desconto)</small>' : brl(j.multa);
        tr.querySelector('.vc-atualizado').dataset.c = Number(j.total); tr.querySelector('.vc-atualizado').textContent = brl(j.total);
      });
      aviso.textContent = 'A conta é a de ' + dataBR(pedida) + '; a proposta vale até essa data.';
      if (document.getElementById('pEntradaVenc').value < pedida) { document.getElementById('pEntradaVenc').value = pedida; }
      if (document.getElementById('vVencimento').value < pedida) { document.getElementById('vVencimento').value = pedida; }
      invalidar();
    });
  };
  dataEl.addEventListener('change', refazer);
  dataEl.addEventListener('input', refazer);
  valor();
});
</script>
