/*
 * PROVA DA ONDA 8, FINANCEIRO REFEITO, PARTE 1 — Receber de vários títulos (D-99). APAGA o banco de prova.
 *   1. a demonstração do colégio; as regras (2%, 1% ao mês, sem desconto); um plano de três parcelas vencidas aplicado a
 *      um aluno (pela tela);
 *   1b. a data do recebimento trocada quatro vezes (inclusive antes do vencimento): a conta de cada linha e o valor a
 *      receber são os do servidor para aquela data, sem "NaN"; a data apagada não pede conta e não estraga a de antes;
 *   2. Receber os três num recibo só, com R$ 1,00 de acréscimo e R$ 10,01 de desconto: sem a justificativa, a tela não
 *      deixa enviar; com ela, um recibo, o total = o devido com os encargos + o acréscimo − o desconto; o acréscimo e o
 *      desconto rateados entre os títulos pelo devido de cada um, fechando ao centavo (o maior resto leva o centavo);
 *   3. Múltiplos que não fecha: a tela diz "Falta" e não envia; o pedido direto é recusado ("falta"), e nada é gravado;
 *      Múltiplos que fecha (dinheiro e Pix): um recibo, duas formas;
 *   4. o clique duplo: o mesmo envio duas vezes grava um recibo só;
 *   sabotagem: o rateio sem o centavo que sobra — a prova acusa; o arquivo volta igual.
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const L = require('../lib');
const X = require('./_escola');

const ETAPA = 'onda8/etapa35';
const MODELO = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'financeiro', 'AdmsFinRecibos.php');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const postar = (p, url, campos) => p.evaluate(async ([u, c]) => {
  const x = await fetch(u, { method: 'POST', credentials: 'same-origin', body: new URLSearchParams(c) });
  return { st: x.status, t: await x.text() };
}, [url, campos]);
const recusa = (t) => ((t.match(/id=['"]financeiroRecusado['"][^>]*>([\s\S]*?)<\/div>/) || [])[1] || '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
/** O rateio esperado (o mesmo da regra: a proporção do devido, o maior resto leva o centavo; no empate, o primeiro). */
function ratear(pesos, valor) {
  const soma = pesos.reduce((s, x) => s + x, 0);
  const partes = pesos.map((x) => Math.floor((valor * x) / soma));
  const restos = pesos.map((x) => (valor * x) % soma);
  const ordem = pesos.map((_, i) => i).sort((a, b) => restos[b] - restos[a] || a - b);
  const falta = valor - partes.reduce((s, x) => s + x, 0); // o que sobra, contado antes de distribuir
  for (let k = 0; k < falta; k++) { partes[ordem[k % pesos.length]]++; }
  return partes;
}
const reciboDe = (titulo) => X.um(`SELECT recibo_id FROM fin_recibo_titulos WHERE titulo_id = ${titulo} AND situacao = 'valido'`);

(async () => {
  const { navegador } = await L.abrir();
  const shaModelo = sha(MODELO);
  try {
    L.ok(X.montar('onda8-etapa35').status === 0, 'a demonstração do colégio montada pela tela, numa instalação nova');
    const c = X.cred();
    const sec = await X.entrar(navegador, 'secretaria.demo', c.painel['secretaria.demo'], 'painel', 1536);
    const p = sec.p;
    await p.goto(L.BASE + 'painel/fin-regras/index');
    await p.fill('#multa', '2'); await p.fill('#juros', '1'); await p.fill('#desconto', '0');
    await Promise.all([p.waitForNavigation(), p.click('#salvarRegras')]);
    const a = await X.arranjo(p, '265001', { valor: '333,33' });
    const ids = a.titulos.map((t) => t.id);
    L.ok(ids.length === 3, `o arranjo: 3 parcelas vencidas de R$ 333,33 do aluno 265001 (${a.titulos.map((t) => t.vencimento).join(', ')})`);

    /* 1b. a troca da data, várias vezes, inclusive antes do
       vencimento: a conta de cada linha é a do servidor para aquela data, e nenhum valor vira "NaN" */
    await p.goto(L.BASE + `painel/financeiro/receber-pagamento/${a.aluno}?titulos=${ids.join(',')}`);
    const trocas = [];
    for (const data of ['2026-02-18', '2026-11-10', '2026-03-19', '2026-04-21']) {
      const [y, m, d] = data.split('-');
      await p.fill('#dataRecebimento', data); // dispara o input e o change, como a pessoa ao escolher a data
      await p.waitForFunction((br) => (document.getElementById('avisoData').textContent || '').includes('A conta é a de ' + br), `${d}/${m}/${y}`, { timeout: 10000 });
      const tela = await p.$$eval('#tabelaReceber tbody tr', (l) => l.map((tr) => ({ juros: +tr.querySelector('.vc-juros').dataset.c, multa: +tr.querySelector('.vc-multa').dataset.c,
        total: +tr.querySelector('.vc-atualizado').dataset.c })));
      const servidor = await p.evaluate(async ([u, t, dt]) => Promise.all(t.map(async (id) => (await fetch(`${u}painel/financeiro/encargos/${id}?data=${dt}`, { credentials: 'same-origin' })).json())), [L.BASE, ids, data]);
      const iguais = tela.every((x, i) => x.juros === servidor[i].juros && x.multa === servidor[i].multa - servidor[i].desconto && x.total === servidor[i].total);
      const somaTela = await p.$eval('#qReceber', (e) => +e.dataset.c);
      const semNaN = !/NaN/.test(await p.evaluate(() => document.body.innerText));
      trocas.push({ data, iguais, soma: somaTela === servidor.reduce((s, j) => s + j.total, 0), semNaN, antes: servidor[0].multa === 0 });
    }
    /* a data incompleta não pede conta: a da última data boa fica */
    await p.fill('#dataRecebimento', '');
    await p.dispatchEvent('#dataRecebimento', 'change');
    const incompleta = { semNaN: !/NaN/.test(await p.evaluate(() => document.body.innerText)), aviso: (await p.textContent('#avisoData')).trim() };
    await L.print(p, ETAPA, 'a-troca-da-data');
    L.ok(trocas.every((x) => x.iguais && x.soma && x.semNaN) && trocas[0].antes && !trocas[1].antes && incompleta.semNaN && /Escolha a data/.test(incompleta.aviso),
      `a troca da data, 4 vezes (${trocas.map((x) => x.data + (x.antes ? ' antes do vencimento' : '')).join(', ')}): a conta de cada linha e o valor a receber são os do servidor, sem "NaN"; a data apagada: "${incompleta.aviso}"`);

    /* 2. os três num recibo, com acréscimo e desconto rateados */
    await p.goto(L.BASE + `painel/financeiro/receber-pagamento/${a.aluno}?titulos=${ids.join(',')}`);
    const devidos = await p.$$eval('#tabelaReceber tbody tr', (l) => l.map((tr) => +tr.querySelector('.vc-atualizado').dataset.c));
    await p.fill('#acrescimo', '1,00');
    await p.fill('#desconto', '10,01');
    const aReceber = await p.$eval('#qReceber', (e) => +e.dataset.c);
    await p.click('#receberFinalizar');
    await p.waitForTimeout(600);
    const barrou = !(await p.isVisible('#vcConfirmar')) && (await p.$eval('#justificativa', (e) => !e.validity.valid));
    await p.fill('#justificativa', 'Combinado com a direção (prova)');
    await L.print(p, ETAPA, 'receber-varios');
    await X.enviar(p, '#formReceber', '#receberFinalizar');
    const rec = reciboDe(ids[0]);
    const r = X.sql(`SELECT CONCAT(total_centavos, '|', acrescimo_centavos, '|', desconto_centavos, '|', (SELECT COUNT(*) FROM fin_recibo_titulos WHERE recibo_id = ${rec})) FROM fin_recibos WHERE id = ${rec}`).split('|').map(Number);
    const ls = X.sql(`SELECT CONCAT(original_centavos + multa_centavos + juros_centavos - pontualidade_centavos, '|', acrescimo_centavos, '|', desconto_centavos, '|', recebido_centavos)
      FROM fin_recibo_titulos WHERE recibo_id = ${rec} ORDER BY titulo_id`).split('\n').map((l) => l.split('|').map(Number));
    const devido = ls.reduce((s, l) => s + l[0], 0);
    const eAcr = ratear(ls.map((l) => l[0]), 100);
    const eDes = ratear(ls.map((l) => l[0]), 1001);
    const fecha = ls.every((l, i) => l[1] === eAcr[i] && l[2] === eDes[i] && l[3] === l[0] + eAcr[i] - eDes[i]) && ls.reduce((s, l) => s + l[3], 0) === r[0];
    L.ok(barrou && r[3] === 3 && reciboDe(ids[1]) === rec && reciboDe(ids[2]) === rec && devido === devidos.reduce((s, x) => s + x, 0) && r[0] === devido + 100 - 1001 && r[0] === aReceber
      && r[1] === 100 && r[2] === 1001 && fecha,
      `Receber os três: sem a justificativa, a tela não envia; um recibo com os 3 títulos, total ${r[0]} = devido ${devido} + 100 − 1001 (a tela mostrava ${aReceber}); o acréscimo ${JSON.stringify(ls.map((l) => l[1]))} e o desconto ${JSON.stringify(ls.map((l) => l[2]))} rateados pelo devido, fechando ao centavo`);

    /* 3. Múltiplos: o que não fecha e o que fecha */
    const b = await X.arranjo(p, '265003', { nome: 'Plano dos multiplos', parcelas: 1, valor: '500,00', mes: 12 });
    const tb = b.titulos[0].id;
    await p.goto(L.BASE + `painel/financeiro/receber-pagamento/${b.aluno}?titulos=${tb}`);
    await p.check('.vc-escolhe-forma[value="multiplo"]', { force: true });
    await p.fill('.vc-linha-forma[data-i="0"] .vc-valor-linha', '300,00'); // as linhas começam no 0 (dinheiro e Pix)
    await p.selectOption('.vc-linha-forma[data-i="1"] .vc-forma-linha', 'dinheiro');
    await p.fill('.vc-linha-forma[data-i="1"] .vc-valor-linha', '100,00');
    const indicador = (await p.textContent('#indicadorMultiplo')).trim();
    await p.click('#receberFinalizar');
    await p.waitForTimeout(600);
    const naoAbriu = !(await p.isVisible('#vcConfirmar'));
    const direto = await postar(p, L.BASE + `painel/financeiro/receber/${b.aluno}`, [['titulos[]', String(tb)], ['data', X.hoje()], ['modo', 'multiplo'],
      ['formas[0][forma]', 'dinheiro'], ['formas[0][valor]', '300,00'], ['formas[1][forma]', 'dinheiro'], ['formas[1][valor]', '100,00'], ['envio', 'nf-' + Date.now()]]);
    const nada = X.um(`SELECT COUNT(*) FROM fin_recibo_titulos WHERE titulo_id = ${tb}`) === 0 && X.situacao(tb) === 'aberto';
    await p.goto(L.BASE + `painel/financeiro/receber-pagamento/${b.aluno}?titulos=${tb}`);
    await p.check('.vc-escolhe-forma[value="multiplo"]', { force: true });
    await p.fill('.vc-linha-forma[data-i="0"] .vc-valor-linha', '300,00');
    await p.fill('.vc-linha-forma[data-i="1"] .vc-valor-linha', '200,00');
    await p.fill('.vc-linha-forma[data-i="1"] [name="formas[1][documento]"]', '000.265.003-70');
    const fechou = (await p.textContent('#indicadorMultiplo')).trim();
    await L.print(p, ETAPA, 'multiplos-fechou');
    await X.enviar(p, '#formReceber', '#receberFinalizar');
    const formas = X.sql(`SELECT GROUP_CONCAT(CONCAT(forma, ':', valor_centavos) ORDER BY ordem) FROM fin_recibo_formas WHERE recibo_id = ${reciboDe(tb)}`);
    L.ok(/^Falta R\$ 100,00/.test(indicador) && naoAbriu && /falta R\$ 100,00/.test(recusa(direto.t)) && nada && /^Fechou/.test(fechou) && formas === 'dinheiro:30000,pix:20000',
      `Múltiplos: "${indicador}" e a tela não envia; o pedido direto: "${recusa(direto.t).slice(0, 90)}…" (nada gravado); "${fechou}": um recibo com ${formas}`);

    /* 4. o clique duplo */
    const cc = await X.arranjo(p, '265004', { nome: 'Plano do clique', parcelas: 1, valor: '200,00', mes: 12 });
    const tc = cc.titulos[0].id;
    const envio = 'duplo-' + Date.now();
    const campos = [['titulos[]', String(tc)], ['data', X.hoje()], ['modo', 'unica'], ['formas[0][forma]', 'dinheiro'], ['formas[0][valor]', ''], ['envio', envio]];
    await postar(p, L.BASE + `painel/financeiro/receber/${cc.aluno}`, campos);
    await postar(p, L.BASE + `painel/financeiro/receber/${cc.aluno}`, campos);
    const nRec = X.um(`SELECT COUNT(*) FROM fin_recibo_titulos WHERE titulo_id = ${tc}`);
    L.ok(nRec === 1 && X.um(`SELECT COUNT(*) FROM fin_recibos WHERE envio = '${envio}'`) === 1 && X.situacao(tc) === 'recebido', `o clique duplo (o mesmo envio duas vezes): um recibo só (${nRec})`);
    await L.semErros(p, 'a tela de Receber');

    /* sabotagem: o rateio sem o centavo que sobra */
    const orig = fs.readFileSync(MODELO, 'utf8');
    const trecho = '$partes[$ordem[$k % $n]]++;';
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let soma = null;
    let total = null;
    try {
      fs.writeFileSync(MODELO, orig.replace(trecho, ';'));
      const d = await X.arranjo(p, '265005', { nome: 'Plano da sabotagem', parcelas: 3, valor: '333,33' });
      await p.goto(L.BASE + `painel/financeiro/receber-pagamento/${d.aluno}?titulos=${d.titulos.map((t) => t.id).join(',')}`);
      await p.fill('#acrescimo', '1,00');
      await p.fill('#justificativa', 'Sabotagem da prova');
      await X.enviar(p, '#formReceber', '#receberFinalizar');
      const rs = reciboDe(d.titulos[0].id);
      soma = X.um(`SELECT SUM(acrescimo_centavos) FROM fin_recibo_titulos WHERE recibo_id = ${rs}`);
      total = X.um(`SELECT acrescimo_centavos FROM fin_recibos WHERE id = ${rs}`);
    } finally {
      fs.writeFileSync(MODELO, orig);
    }
    L.ok(soma !== total, `sabotagem (o rateio sem o centavo que sobra): a prova reprova — o acréscimo rateado soma ${soma}, o do recibo é ${total}`);
    L.ok(sha(MODELO) === shaModelo, 'o arquivo sabotado voltou igual (sha256)');
    await sec.ctx.close();
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(MODELO) !== shaModelo) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
