/*
 * PROVA DA ONDA 8, FINANCEIRO, PARTE 5 — a ficha do caixa e os recebimentos por período (D-98). APAGA o banco de prova.
 *   1. a demonstração do colégio; um plano de três parcelas a vencer (dezembro de 2026 a fevereiro de 2027) aplicado a um
 *      aluno; hoje, a secretaria recebe duas (dinheiro e Pix) e a supervisão, uma (cartão); a supervisão desfaz o Pix;
 *   2. os dois relatórios na categoria "Financeiro" do menu de relatórios;
 *   3. a ficha do caixa de hoje: os recebimentos que valem (o desfeito fora), o total geral, o total por forma e o caixa
 *      de cada pessoa; a soma das linhas é o total; filtrada por quem recebeu, só a secretaria;
 *   4. os recebimentos por período: os três, o desfeito marcado com o motivo e fora do total; a tabela com a exportação
 *      (registrada antes de sair, como os outros relatórios);
 *   sabotagem: o caixa contando o recebimento desfeito — a prova acusa; o arquivo volta igual.
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const L = require('../lib');
const X = require('./_escola');

const ETAPA = 'onda8/etapa30';
const REL = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'relatorios', 'RelCaixa.php');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');

/** D-99: pela tela de Receber pagamento, cada forma com os dados que ela pede. */
const DADOS = { dinheiro: {}, pix: { documento: '000.265.001-08' }, cartao: { cartao_tipo: 'debito', bandeira_id: '1', cartao_final: '4242', cartao_documento: 'DOC1', cartao_autorizacao: 'AUT1' } };
async function receber(p, aluno, titulo, forma) {
  await X.receber(p, [titulo], { forma, campos: DADOS[forma] });
}
/** Os cartões e as linhas de um relatório. */
async function relatorio(p, slug, filtro) {
  await p.goto(L.BASE + `painel/relatorios/${slug}${filtro ? '?' + filtro : ''}`, { timeout: 120000 });
  return p.evaluate(() => ({
    cartoes: Object.fromEntries([...document.querySelectorAll('.vc-cartao-rel')].map((c) => [(c.querySelector('.text-secondary') || {}).textContent.trim(), (c.querySelector('.h1') || {}).textContent.trim()])),
    linhas: [...document.querySelectorAll('table.lista-dados tbody tr')].map((tr) => ({ classe: tr.className, celulas: [...tr.cells].map((td) => td.textContent.trim()) })),
    exporta: !!document.querySelector('table.lista-dados[data-auditoria]'),
  }));
}
const centavos = (t) => (/-/.test(String(t)) ? -1 : 1) * (+String(t).replace(/[^\d,]/g, '').replace(',', '') || 0);

(async () => {
  const { navegador } = await L.abrir();
  const shaRel = sha(REL);
  try {
    L.ok(X.montar('onda8-etapa30').status === 0, 'a demonstração do colégio montada pela tela, numa instalação nova');
    const c = X.cred();
    const sec = await X.entrar(navegador, 'secretaria.demo', c.painel['secretaria.demo']);
    const a = await X.arranjo(sec.p, '265001', { mes: 12 });
    const [t1, t2, t3] = a.titulos;
    await receber(sec.p, a.aluno, t1.id, 'dinheiro');
    await receber(sec.p, a.aluno, t2.id, 'pix');
    const sup = await X.entrar(navegador, 'supervisao.demo', c.painel['supervisao.demo']);
    await receber(sup.p, a.aluno, t3.id, 'cartao');
    await X.desfazerRecebimento(sup.p, t2.id, { motivo: 4, justificativa: 'Pix não caiu na conta' }); // D-99: o recibo inteiro, com o motivo da lista
    L.ok([t1, t2, t3].map((t) => X.situacao(t.id)).join() === 'recebido,aberto,recebido', 'o arranjo: hoje, dinheiro e Pix (a secretaria) e cartão (a supervisão); o Pix desfeito');

    /* 2. no menu de relatórios */
    await sec.p.goto(L.BASE + 'painel/relatorios/listar');
    const menu = await sec.p.evaluate(() => (document.querySelector('.page-body') || document.body).innerText);
    const temLinks = await sec.p.$$eval('a[href$="/relatorios/caixa"], a[href$="/relatorios/recebimentos"]', (l) => l.length);
    L.ok(/Financeiro/.test(menu) && /Ficha do caixa/.test(menu) && /Recebimentos por período/.test(menu) && temLinks >= 2, 'os dois relatórios na categoria "Financeiro" do menu de relatórios');

    /* 3. a ficha do caixa de hoje */
    const cx = await relatorio(sec.p, 'caixa', `dia=${X.hoje()}`);
    await L.print(sec.p, ETAPA, 'ficha-do-caixa');
    const somaLinhas = cx.linhas.reduce((s, l) => s + centavos(l.celulas[l.celulas.length - 1]), 0);
    const totalDia = Object.entries(cx.cartoes).find(([k]) => /^Total do dia/.test(k)) || [];
    const nomeSup = X.sql("SELECT nome FROM adms_usuarios WHERE usuario = 'supervisao.demo'");
    const nomeSec = X.sql("SELECT nome FROM adms_usuarios WHERE usuario = 'secretaria.demo'");
    /* D-99: o recibo entra no dia dele, no caixa de quem recebeu; o estorno sai no dia dele, no caixa de quem desfez — hoje,
       as três entradas (dinheiro, Pix, cartão) e o estorno do Pix (negativo, no caixa da supervisão) */
    /* as linhas desta prova (o aluno 265001: a demonstração também desfaz um recebimento hoje, o da aluna da ficha) */
    const valor = (l) => centavos(l.celulas[l.celulas.length - 1]);
    const minhas = cx.linhas.filter((l) => /265001/.test(l.celulas[2]));
    const estornos = minhas.filter((l) => /estorno/.test(l.celulas.join('|')));
    /* cada cartão é a soma das linhas dele (por forma e por pessoa) */
    const somaPor = (f) => cx.linhas.filter(f).reduce((s, l) => s + valor(l), 0);
    const cartaoBate = ['Dinheiro', 'Pix manual', 'Cartão'].every((k) => centavos(cx.cartoes[k] || '0') === somaPor((l) => l.celulas[4].replace(' — estorno', '') === k))
      && [nomeSup, nomeSec].every((n) => centavos(cx.cartoes['Caixa de ' + n] || '0') === somaPor((l) => l.celulas[0] === n));
    L.ok(minhas.length === 4 && estornos.length === 1 && valor(estornos[0]) === -50000 && estornos[0].celulas[0] === nomeSup && minhas.reduce((s, l) => s + valor(l), 0) === 100000
      && centavos(totalDia[1]) === somaLinhas && cartaoBate && minhas.filter((l) => l.celulas[0] === nomeSec).reduce((s, l) => s + valor(l), 0) === 100000,
      `a ficha do caixa de hoje: as ${minhas.length} linhas do aluno (as três entradas e o estorno do Pix, ${estornos.length ? estornos[0].celulas.slice(-1)[0] : '—'}, no caixa de quem desfez), somando R$ 1.000,00; o total do dia ${totalDia[1]} = a soma das linhas; cada cartão por forma e por pessoa = a soma das linhas dele (dinheiro ${cx.cartoes.Dinheiro}, cartão ${cx.cartoes['Cartão']}, Pix ${cx.cartoes['Pix manual']})`);
    const idSec = X.um("SELECT id FROM adms_usuarios WHERE usuario = 'secretaria.demo'");
    const cxSec = await relatorio(sec.p, 'caixa', `dia=${X.hoje()}&usuario=${idSec}`);
    /* Renegociar (D-105): a demonstração também recebe hoje, pela secretaria, a entrada de um acordo — as linhas desta prova são as do aluno 265001 */
    const secMinhas = cxSec.linhas.filter((l) => /265001/.test(l.celulas[2]));
    L.ok(secMinhas.length === 2 && cxSec.linhas.every((l) => l.celulas[0] === nomeSec) && /Dinheiro/.test(secMinhas.map((l) => l.celulas.join('|')).join('#')) && !/estorno/.test(secMinhas.map((l) => l.celulas.join('|')).join('#')),
      `filtrada por quem recebeu (a secretaria): ${secMinhas.length} entradas do aluno (o dinheiro e o Pix), todas as linhas da secretaria; o estorno é do caixa de quem desfez`);

    /* 4. os recebimentos por período */
    const rp = await relatorio(sec.p, 'recebimentos', `de=${X.hoje()}&ate=${X.hoje()}`);
    await L.print(sec.p, ETAPA, 'recebimentos-por-periodo');
    /* Renegociar (D-105): a entrada do acordo da demonstração também é de hoje — as linhas desta prova são as do aluno
       265001; os cartões, a soma das linhas que valem */
    const rpMinhas = rp.linhas.filter((l) => /265001/.test(l.celulas[2]));
    const desf = rpMinhas.filter((l) => /Estornado em .*Pagamento não compensado/.test(l.celulas.join('|')));
    const valem = (ls, f) => ls.filter((l) => /^Vale$/.test(l.celulas[11]) && (!f || f(l))).reduce((s, l) => s + centavos(l.celulas[8]), 0);
    const daForma = (k) => (l) => l.celulas[9] === k;
    L.ok(rpMinhas.length === 3 && desf.length === 1 && /table-warning/.test(desf[0].classe) && valem(rpMinhas) === 100000
      && centavos(rp.cartoes['Total recebido']) === valem(rp.linhas) && rp.cartoes['Recibos estornados'] === String(rp.linhas.filter((l) => /^Estornado/.test(l.celulas[11])).length)
      && valem(rpMinhas, daForma('Dinheiro')) === 50000 && valem(rpMinhas, daForma('Cartão')) === 50000 && valem(rpMinhas, daForma('Pix manual')) === 0
      && ['Dinheiro', 'Cartão', 'Pix manual'].every((k) => centavos(rp.cartoes[k] || '0') === valem(rp.linhas, daForma(k))) && rp.exporta,
      `os recebimentos por período: ${rpMinhas.length} recibos do aluno, o estornado marcado com o motivo, ${valem(rpMinhas)} centavos sem ele; o total ${rp.cartoes['Total recebido']} = a soma das linhas que valem; por forma (dinheiro ${rp.cartoes.Dinheiro}, cartão ${rp.cartoes['Cartão']}, Pix ${rp.cartoes['Pix manual'] || '—'}); a tabela exporta como as outras`);
    await sup.ctx.close();

    /* sabotagem: o estorno sem sair do caixa */
    const orig = fs.readFileSync(REL, 'utf8');
    const trecho = ", ['estorno', 'r.estorno_data = :dia', 'r.estornado_por', -1]";
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let n = 0;
    try {
      fs.writeFileSync(REL, orig.replace(trecho, ''));
      const s = await relatorio(sec.p, 'caixa', `dia=${X.hoje()}`);
      n = centavos((Object.entries(s.cartoes).find(([k]) => /^Total do dia/.test(k)) || [])[1]);
    } finally {
      fs.writeFileSync(REL, orig);
    }
    L.ok(n !== centavos(totalDia[1]), `sabotagem (o estorno sem sair do caixa do dia): a prova reprova — o total do dia ficou ${n}, e é ${centavos(totalDia[1])}`);
    L.ok(sha(REL) === shaRel, 'o arquivo sabotado voltou igual (sha256)');
    await sec.ctx.close();
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(REL) !== shaRel) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
