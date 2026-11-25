/*
 * PROVA DA ONDA 8, FINANCEIRO REFEITO, PARTE 1 — o recibo, a reimpressão e o Desfazer recebimento do recibo inteiro (D-99).
 * APAGA o banco de prova.
 *   1. a demonstração do colégio: o recibo da aluna da ficha com dois títulos e duas formas (Pix e dinheiro, com desconto);
 *   2. o recibo: o número, as duas vias, "Recebemos de" o responsável financeiro do ano, o valor por extenso, a aluna com
 *      a série, os dois títulos, o desconto com a justificativa, as formas por extenso (o Pix com o documento) e quem
 *      recebeu; a reimpressão pelo menu Ações de cada título dele abre o mesmo recibo;
 *   3. Desfazer recebimento: a secretaria não tem o item; a supervisão, sem a justificativa, é recusada; com a data, o
 *      motivo e a justificativa, desfaz o recibo inteiro: os dois títulos voltam a em aberto, o recibo, as linhas e as
 *      formas ficam estornados (nada é apagado); o recibo reimpresso sai com o mesmo número e a faixa de estornado;
 *   4. o caixa: o do dia do recebimento continua com as entradas; o do dia do estorno tem as saídas (negativas), no
 *      caixa de quem desfez;
 *   sabotagem: o desfazer voltando só o primeiro título do recibo — a prova acusa; o arquivo volta igual.
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const L = require('../lib');
const X = require('./_escola');

const ETAPA = 'onda8/etapa37';
const MODELO = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'financeiro', 'AdmsFinRecibos.php');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const centavos = (t) => (/-/.test(String(t)) ? -1 : 1) * (+String(t).replace(/[^\d,]/g, '').replace(',', '') || 0);
const postar = (p, url, campos) => p.evaluate(async ([u, c]) => {
  const x = await fetch(u, { method: 'POST', credentials: 'same-origin', body: new URLSearchParams(c) });
  return { st: x.status, t: await x.text() };
}, [url, campos]);
async function caixa(p, dia) {
  await p.goto(L.BASE + `painel/relatorios/caixa?dia=${dia}`, { timeout: 120000 });
  return p.$$eval('table.lista-dados tbody tr', (l) => l.map((tr) => [...tr.cells].map((td) => td.textContent.trim())));
}

(async () => {
  const { navegador } = await L.abrir();
  const shaModelo = sha(MODELO);
  try {
    L.ok(X.montar('onda8-etapa37').status === 0, 'a demonstração do colégio montada pela tela, numa instalação nova');
    const c = X.cred();
    const sec = await X.entrar(navegador, 'secretaria.demo', c.painel['secretaria.demo'], 'painel', 1536);
    const id = X.um("SELECT id FROM col_usuarios WHERE rm = '265022'");
    const rec = X.um(`SELECT r.id FROM fin_recibos r WHERE r.aluno_id = ${id} AND r.situacao = 'valido' AND (SELECT COUNT(*) FROM fin_recibo_formas f WHERE f.recibo_id = r.id) = 2`);
    const R = X.sql(`SELECT CONCAT_WS('|', numero, total_centavos, data_recebimento, desconto_centavos, pagador_nome) FROM fin_recibos WHERE id = ${rec}`).split('|');
    const titulos = X.sql(`SELECT titulo_id FROM fin_recibo_titulos WHERE recibo_id = ${rec} ORDER BY titulo_id`).split('\n').map(Number);
    const fin26 = X.sql(`SELECT r.nome FROM esc_responsavel_designacoes d JOIN esc_responsaveis r ON r.id = d.responsavel_id WHERE d.aluno_id = ${id} AND d.ano = 2026 AND d.papel = 'financeiro' AND d.fim IS NULL`);
    L.ok(rec > 0 && titulos.length === 2 && R[4] === fin26, `o arranjo da demonstração: o recibo nº ${R[0]} da aluna da ficha, com ${titulos.length} títulos e duas formas, de ${R[4]}`);

    /* 2. o recibo e a reimpressão */
    const p = sec.p;
    await p.goto(L.BASE + `painel/financeiro/recibo/${rec}`);
    await L.print(p, ETAPA, 'o-recibo');
    const via = await p.$$eval('.via', (l) => l.map((v) => v.innerText.replace(/\s+/g, ' ')));
    const v = via[0] || '';
    L.ok(via.length === 2 && v.includes(`Nº ${R[0]}`) && v.includes(`Recebemos de ${fin26}`) && /\(mil trezentos e nove reais/.test(v) && /RM 265022/.test(v) && /9º ano/.test(v)
      && titulos.every((t) => v.includes(` ${t} `)) && /Desconto — Desconto combinado/.test(v) && /Pix de 000\.265\.022-\d\d: R\$ 500,00; Dinheiro: R\$/.test(v) && /quem recebeu/.test(v),
      `o recibo nº ${R[0]}: duas vias; "Recebemos de ${fin26}", o valor por extenso, a aluna (RM) com a série, os dois títulos, o desconto com a justificativa, as formas por extenso, quem recebeu`);
    await p.goto(L.BASE + `painel/financeiro/ficha/${id}`);
    const links = await p.$$eval(titulos.map((t) => `#tabelaTitulos tr[data-titulo="${t}"][data-situacao] .vc-item-recibo`).join(', '), (l) => l.map((a) => a.getAttribute('href')));
    L.ok(links.length === 2 && links.every((h) => h.endsWith(`/financeiro/recibo/${rec}`)), `a reimpressão: o item "Recibo" do menu Ações dos dois títulos abre o mesmo recibo (${links.length})`);

    /* 3. Desfazer recebimento */
    await p.click(`#tabelaTitulos tr[data-titulo="${titulos[0]}"][data-situacao] .vc-acoes`);
    const itemSec = !!(await p.$(`#tabelaTitulos tr[data-titulo="${titulos[0]}"] .vc-item-desfazer-recebimento`));
    const sup = await X.entrar(navegador, 'supervisao.demo', c.painel['supervisao.demo'], 'painel', 1536);
    const semJust = await postar(sup.p, L.BASE + `painel/financeiro/desfazer-recebimento/${rec}`, [['data', X.hoje()], ['motivo_id', '1'], ['justificativa', ''], ['envio', 'sj-' + Date.now()]]);
    const ficou = X.sql(`SELECT situacao FROM fin_recibos WHERE id = ${rec}`);
    await X.desfazerRecebimento(sup.p, titulos[0], { motivo: 1, justificativa: 'A família tinha pago em duplicidade (prova)' });
    const depois = titulos.map((t) => X.situacao(t));
    const marc = X.sql(`SELECT CONCAT_WS('|', r.situacao, r.estorno_data, r.estorno_motivo, (SELECT GROUP_CONCAT(DISTINCT situacao) FROM fin_recibo_titulos WHERE recibo_id = r.id),
      (SELECT GROUP_CONCAT(DISTINCT situacao) FROM fin_recibo_formas WHERE recibo_id = r.id), (SELECT COUNT(*) FROM fin_recibo_titulos WHERE recibo_id = r.id)) FROM fin_recibos r WHERE r.id = ${rec}`).split('|');
    await sup.p.goto(L.BASE + `painel/financeiro/recibo/${rec}`);
    await L.print(sup.p, ETAPA, 'o-recibo-estornado');
    const faixa = (await sup.p.$$eval('.vc-faixa-estornado', (l) => l.map((e) => e.textContent.trim()))).join(' / ');
    const numero = await sup.p.$eval('#recibo', (e) => e.dataset.numero);
    const dataBr = X.hoje().split('-').reverse().join('/');
    L.ok(!itemSec && ficou === 'valido' && depois.every((s) => s === 'aberto') && marc[0] === 'estornado' && marc[1] === X.hoje() && marc[2] === 'Recebimento lançado em duplicidade'
      && marc[3] === 'estornado' && marc[4] === 'estornado' && marc[5] === '2' && numero === R[0] && faixa.includes(`ESTORNADO em ${dataBr} — não vale como comprovante`),
      `Desfazer recebimento: a secretaria não tem o item; sem a justificativa, recusado (o recibo continua ${ficou}); com o motivo e a justificativa, os dois títulos voltam (${depois.join(', ')}); o recibo, as linhas e as formas ${marc[0]} (nada apagado: ${marc[5]} linhas); reimpresso com o mesmo nº ${numero} e a faixa "${faixa.split(' / ')[0]}"`);

    /* 4. o caixa do dia do recebimento e o do dia do estorno */
    const dRec = await caixa(sup.p, R[2]);
    const dHoje = await caixa(sup.p, X.hoje());
    const nomeSup = X.sql("SELECT nome FROM adms_usuarios WHERE usuario = 'supervisao.demo'");
    const doRec = (l) => l.filter((x) => x[1] === R[0]);
    const entradas = doRec(dRec);
    const saidas = doRec(dHoje);
    L.ok(entradas.length === 2 && entradas.every((x) => centavos(x[5]) > 0) && entradas.reduce((s, x) => s + centavos(x[5]), 0) === +R[1]
      && saidas.length === 2 && saidas.every((x) => centavos(x[5]) < 0 && /estorno/.test(x[4]) && x[0] === nomeSup) && saidas.reduce((s, x) => s + centavos(x[5]), 0) === -R[1],
      `o caixa: no dia do recebimento (${R[2]}), as duas entradas do recibo continuam (${entradas.map((x) => x[5]).join(' + ')}); hoje, as duas saídas do estorno no caixa de quem desfez (${saidas.map((x) => x[5]).join(' + ')})`);
    await L.semErros(sup.p, 'o caixa');
    await sup.ctx.close();

    /* sabotagem: o desfazer voltando só o primeiro título */
    const orig = fs.readFileSync(MODELO, 'utf8');
    const trecho = 'foreach ($titulos as $t) {';
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let sit = '';
    try {
      fs.writeFileSync(MODELO, orig.replace(trecho, 'foreach (array_slice($titulos, 0, 1) as $t) {'));
      const a = await X.arranjo(p, '265001', { nome: 'Plano da sabotagem', parcelas: 2, valor: '100,00', mes: 12 });
      await X.receber(p, a.titulos.map((t) => t.id));
      const s = await X.entrar(navegador, 'supervisao.demo', c.painel['supervisao.demo'], 'painel', 1536);
      await X.desfazerRecebimento(s.p, a.titulos[0].id, { motivo: 1, justificativa: 'Sabotagem da prova' });
      await s.ctx.close();
      sit = a.titulos.map((t) => X.situacao(t.id)).join(',');
    } finally {
      fs.writeFileSync(MODELO, orig);
    }
    L.ok(sit !== 'aberto,aberto', `sabotagem (o desfazer voltando só o primeiro título do recibo): a prova reprova — os títulos ficaram ${sit}`);
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
