/*
 * PROVA DA ONDA 8, FINANCEIRO, PARTE 6 — Renegociar e Desfazer renegociação (D-98; refeita na D-105, a tela própria).
 * APAGA o banco de prova.
 *   1. a demonstração do colégio; as regras do padrão (2%, 1% ao mês); um plano de três parcelas vencidas (fevereiro a
 *      abril de 2026) aplicado a um aluno;
 *   2. Renegociar duas delas pela tela "Renegociar títulos" (a parcelada, sem entrada, em 2 parcelas no dia 15), com
 *      R$ 10,00 de desconto e a justificativa: a tela mostra o valor a renegociar da conta feita aqui (o valor, a multa e
 *      os juros pró-rata de cada uma, até hoje, menos o desconto); o acordo gravado tem o total com os encargos e o
 *      desconto; as duas ficam "renegociado"; as parcelas do acordo somam o total menos o desconto, a primeira no mês
 *      seguinte, no dia 15; a terceira continua em aberto;
 *   3. a parcela do acordo recebida: Desfazer renegociação é recusado e a tela diz o que desfazer antes; desfeito o
 *      recebimento, a renegociação se desfaz (com o motivo da lista e a observação): os originais voltam (vencidos, pela
 *      data), as parcelas do acordo ficam canceladas; nada é apagado;
 *   sabotagem: Desfazer renegociação sem conferir as parcelas recebidas — a prova acusa; o arquivo volta igual.
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const L = require('../lib');
const X = require('./_escola');

const ETAPA = 'onda8/etapa31';
const MODELO = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'financeiro', 'AdmsFinOperacoes.php');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');

/** A conta, feita aqui de novo: até hoje, a multa (2%) e os juros (1% ao mês, por dia), o meio para cima. */
function encargos(v, venc, hoje) {
  const dias = Math.round((Date.parse(hoje + 'T00:00:00Z') - Date.parse(venc + 'T00:00:00Z')) / 86400000);
  if (dias <= 0) { return v; }
  return v + Math.round((v * 200) / 10000) + Math.round((v * 100 * dias) / (10000 * 30));
}
const brl = (c) => (c / 100).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');

(async () => {
  const { navegador } = await L.abrir();
  const shaModelo = sha(MODELO);
  try {
    L.ok(X.montar('onda8-etapa31').status === 0, 'a demonstração do colégio montada pela tela, numa instalação nova');
    const c = X.cred();
    const sup = await X.entrar(navegador, 'supervisao.demo', c.painel['supervisao.demo']);
    await sup.p.goto(L.BASE + 'painel/fin-regras/index');
    await sup.p.fill('#multa', '2'); await sup.p.fill('#juros', '1'); await sup.p.fill('#desconto', '0');
    await Promise.all([sup.p.waitForNavigation(), sup.p.click('#salvarRegras')]);
    const a = await X.arranjo(sup.p, '265001');
    const [t1, t2, t3] = a.titulos;
    const hoje = X.hoje();
    const esperado = encargos(50000, t1.vencimento, hoje) + encargos(50000, t2.vencimento, hoje);
    L.ok(a.titulos.length === 3, `o arranjo: 3 parcelas vencidas (${a.titulos.map((t) => t.vencimento).join(', ')}); hoje, as duas primeiras somam R$ ${brl(esperado)} com os encargos`);

    /* 2. Renegociar, pela tela própria */
    const primeiro = X.noMes(hoje, 1, 15);
    const r = await X.renegociar(sup.p, a.aluno, [t1.id, t2.id], { desconto: '10,00', justificativa: 'Desconto combinado com a direção (prova)', entrada: '', parcelas: 2, dia: 15, acao: 'parcelada' });
    const gravado = X.sql(`SELECT CONCAT(total_centavos, '|', desconto_centavos, '|', parcelas, '|', situacao) FROM fin_renegociacoes WHERE id = ${r.ren}`);
    const novas = X.sql(`SELECT id, valor_centavos, vencimento FROM fin_titulos WHERE renegociacao_id = ${r.ren} ORDER BY parcela`).split('\n').map((l) => l.split('\t'));
    const soma = novas.reduce((s, n) => s + +n[1], 0);
    await L.print(sup.p, ETAPA, 'renegociado');
    L.ok(r.recusa === '' && r.tela.valor === esperado - 1000 && gravado === `${esperado}|1000|2|vigente` && X.situacao(t1.id) === 'renegociado' && X.situacao(t2.id) === 'renegociado'
      && novas.length === 2 && soma === esperado - 1000 && novas[0][2] === primeiro && X.situacao(t3.id) === 'aberto',
      `Renegociar: a tela mostrou R$ ${brl(r.tela.valor)} a renegociar; gravado ${gravado} (a conta daqui: ${esperado}); 2 parcelas do acordo somando R$ ${brl(soma)}, a primeira em ${novas[0] && novas[0][2]} (esperada ${primeiro}); a terceira continua em aberto`);

    /* 3. a parcela do acordo recebida trava o desfazer; desfeito o recebimento, desfaz */
    await X.receber(sup.p, [+novas[0][0]]);
    const recusa = await X.desfazerRenegociacao(sup.p, a.aluno, r.ren, { motivo: 81, observacao: 'Os pais pediram outro acordo' });
    const ainda = X.sql(`SELECT situacao FROM fin_renegociacoes WHERE id = ${r.ren}`);
    await X.desfazerRecebimento(sup.p, +novas[0][0], { justificativa: 'Recebido por engano' }); // D-99: o recibo inteiro
    const ok2 = await X.desfazerRenegociacao(sup.p, a.aluno, r.ren, { motivo: 81, observacao: 'Os pais pediram outro acordo' });
    await sup.p.goto(L.BASE + `painel/financeiro/ficha/${a.aluno}`);
    const mostradas = await sup.p.$$eval(`#tabelaTitulos tr[data-titulo="${t1.id}"][data-situacao], #tabelaTitulos tr[data-titulo="${t2.id}"][data-situacao]`, (l) => l.map((t) => t.dataset.situacao));
    const fim = X.sql(`SELECT CONCAT(situacao, '|', motivo_desfazer, '|', motivo_desfazer_id) FROM fin_renegociacoes WHERE id = ${r.ren}`);
    const novasDepois = novas.map((n) => X.situacao(+n[0]));
    await L.print(sup.p, ETAPA, 'renegociacao-desfeita');
    L.ok(/Não dá para desfazer/.test(recusa) && /recebido/.test(recusa) && /Desfaça antes o recebimento/.test(recusa) && ainda === 'vigente',
      `com a parcela do acordo recebida, Desfazer renegociação é recusado: "${recusa.slice(0, 120)}"`);
    L.ok(ok2 === '' && fim === 'desfeita|Acordo não cumprido: Os pais pediram outro acordo|81' && mostradas.join() === 'vencido,vencido' && novasDepois.join() === 'cancelado,cancelado'
      && X.um(`SELECT COUNT(*) FROM fin_titulos WHERE renegociacao_id = ${r.ren}`) === 2,
      `desfeito o recebimento, Desfazer renegociação (o motivo da lista e a observação: ${fim}): os originais voltam ${mostradas.join(', ')}; as parcelas do acordo ficam ${novasDepois.join(', ')} (nada apagado)`);

    /* sabotagem: sem conferir as parcelas presas */
    const orig = fs.readFileSync(MODELO, 'utf8');
    const trecho = 'if ($presas) {';
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let sit = '';
    try {
      fs.writeFileSync(MODELO, orig.replace(trecho, 'if (false && $presas) {'));
      const r2 = await X.renegociar(sup.p, a.aluno, [t1.id, t2.id], { entrada: '', parcelas: 2, dia: 15, acao: 'parcelada' });
      const nova = X.um(`SELECT MIN(id) FROM fin_titulos WHERE renegociacao_id = ${r2.ren}`);
      await X.receber(sup.p, [nova]);
      await X.desfazerRenegociacao(sup.p, a.aluno, r2.ren, { observacao: 'Tentativa da sabotagem' });
      sit = X.sql(`SELECT situacao FROM fin_renegociacoes WHERE id = ${r2.ren}`);
    } finally {
      fs.writeFileSync(MODELO, orig);
    }
    L.ok(sit !== 'vigente', `sabotagem (Desfazer renegociação sem conferir a parcela recebida): a prova reprova — a renegociação ficou ${sit}`);
    L.ok(sha(MODELO) === shaModelo, 'o arquivo sabotado voltou igual (sha256)');
    await sup.ctx.close();
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(MODELO) !== shaModelo) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
