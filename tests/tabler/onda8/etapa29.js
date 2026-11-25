/*
 * PROVA DA ONDA 8, FINANCEIRO, PARTE 4 — Baixar, Cancelar, Desfazer baixa e Desfazer cancelamento (D-98). APAGA o banco
 * de prova.
 *   1. a demonstração do colégio; um plano de três parcelas (20 de outubro, 20 de novembro e 20 de dezembro de 2026)
 *      aplicado a um aluno: as duas primeiras vencidas, a terceira a vencer (pela data de hoje);
 *   2. Baixar sem o motivo: recusado, nada muda; com o motivo: baixado, e a baixa não entra no caixa (nenhum valor
 *      recebido na operação); Cancelar com o motivo: cancelado;
 *   3. o nome da ação é o mesmo no botão, na confirmação e no histórico ("Baixar", "Cancelar", "Desfazer baixa",
 *      "Desfazer cancelamento");
 *   4. Desfazer baixa (a supervisão, com o motivo): a parcela de outubro volta como vencida; Desfazer cancelamento: a de
 *      dezembro volta como a vencer (a situação sai da data de hoje); a secretaria não tem os botões do desfazer;
 *   sabotagem: Baixar sem exigir o motivo — a prova acusa; o arquivo volta igual.
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const L = require('../lib');
const X = require('./_escola');

const ETAPA = 'onda8/etapa29';
const MODELO = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'financeiro', 'AdmsFinOperacoes.php');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');

/** Abre a ação, escreve o motivo, guarda a pergunta de confirmação e envia. */
async function agir(p, titulo, acao, motivo) {
  /* D-99: a ação sai do menu Ações da linha; a confirmação é a janela do próprio sistema */
  const form = await X.abrirAcao(p, titulo, acao);
  const botao = (await p.textContent(`#tabelaTitulos tr[data-titulo="${titulo}"] .vc-item-${acao.replace(/_/g, '-')}`)).trim();
  if (motivo !== null) { await p.fill(`${form} input[name="motivo"]`, motivo); }
  await p.click(`${form} button[type="submit"]`);
  await p.waitForSelector('#vcConfirmar.show', { timeout: 5000 });
  const pergunta = (await p.textContent('#vcConfirmarTitulo')).trim();
  await Promise.all([p.waitForNavigation(), p.click('#vcConfirmarSim')]);
  return { botao, pergunta };
}
const mostrada = (p, t) => p.$eval(`#tabelaTitulos tr[data-titulo="${t}"][data-situacao]`, (e) => e.dataset.situacao);

(async () => {
  const { navegador } = await L.abrir();
  const shaModelo = sha(MODELO);
  try {
    L.ok(X.montar('onda8-etapa29').status === 0, 'a demonstração do colégio montada pela tela, numa instalação nova');
    const c = X.cred();
    const sec = await X.entrar(navegador, 'secretaria.demo', c.painel['secretaria.demo']);
    const a = await X.arranjo(sec.p, '265001', { mes: 10 });
    const [t1, t2, t3] = a.titulos;
    await sec.p.goto(L.BASE + `painel/financeiro/ficha/${a.aluno}`);
    const antes = [await mostrada(sec.p, t1.id), await mostrada(sec.p, t2.id), await mostrada(sec.p, t3.id)];
    L.ok(antes.join() === 'vencido,vencido,a_vencer', `o arranjo: outubro e novembro vencidos, dezembro a vencer (${antes.join(', ')})`);

    /* 2. Baixar sem o motivo (o pedido direto, sem a tela barrar) e com o motivo; Cancelar */
    /* o fetch segue o redirecionamento para a ficha, que mostra (e consome) o aviso: ele está na resposta */
    const sm = await sec.p.evaluate(async (u) => { const x = await fetch(u, { method: 'POST', credentials: 'same-origin', body: new URLSearchParams({ motivo: '', envio: 'sm-' + Date.now() }) }); return { st: x.status, t: await x.text() }; }, L.BASE + `painel/financeiro/baixar/${t1.id}`);
    const semMotivo = sm.st;
    const recusa = ((sm.t.match(/id=['"]financeiroRecusado['"][^>]*>([\s\S]*?)<\/div>/) || [])[1] || '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
    await sec.p.goto(L.BASE + `painel/financeiro/ficha/${a.aluno}`);
    const ficou = X.situacao(t1.id);
    const b = await agir(sec.p, t1.id, 'baixar', 'Bolsa integral concedida pela direção');
    const opB = X.sql(`SELECT CONCAT(IFNULL(recebido_centavos, 'nada'), '|', motivo) FROM fin_operacoes WHERE titulo_id = ${t1.id} AND tipo = 'baixar'`);
    const cc = await agir(sec.p, t3.id, 'cancelar', 'Aluno transferido em dezembro');
    await L.print(sec.p, ETAPA, 'baixado-e-cancelado');
    L.ok(semMotivo === 200 && /motivo/.test(recusa) && ficou === 'aberto' && X.situacao(t1.id) === 'baixado' && /^nada\|Bolsa/.test(opB) && X.situacao(t3.id) === 'cancelado',
      `Baixar sem o motivo: "${recusa}" (continua ${ficou}); com o motivo: baixado, sem valor recebido (não entra no caixa: ${opB.split('|')[0]}); Cancelar com o motivo: cancelado`);

    /* 4. o desfazer: a secretaria não tem os botões; a supervisão desfaz */
    const botoesSec = await sec.p.$$eval('.vc-item-desfazer-baixa, .vc-item-desfazer-cancelamento, .vc-item-desfazer-recebimento', (b) => b.length);
    await sec.ctx.close();
    const sup = await X.entrar(navegador, 'supervisao.demo', c.painel['supervisao.demo']);
    await sup.p.goto(L.BASE + `painel/financeiro/ficha/${a.aluno}`);
    const db = await agir(sup.p, t1.id, 'desfazer_baixa', 'Bolsa lançada no aluno errado');
    const dc = await agir(sup.p, t3.id, 'desfazer_cancelamento', 'A transferência não aconteceu');
    const depois = [await mostrada(sup.p, t1.id), await mostrada(sup.p, t3.id)];
    await sup.p.goto(L.BASE + `painel/financeiro/ficha/${a.aluno}?ver=historico`); // D-99: o histórico é a aba do alto
    const hist = (await sup.p.textContent('#historicoFinanceiro').catch(() => '')).replace(/\s+/g, ' ');
    await L.print(sup.p, ETAPA, 'desfeitos');
    await sup.ctx.close();
    const desfeitas = X.um(`SELECT COUNT(*) FROM fin_operacoes WHERE titulo_id IN (${t1.id}, ${t3.id}) AND tipo IN ('baixar', 'cancelar') AND desfeita_em IS NOT NULL`);
    L.ok(botoesSec === 0 && depois.join() === 'vencido,a_vencer' && desfeitas === 2,
      `Desfazer baixa e Desfazer cancelamento (a supervisão, com o motivo): outubro volta ${depois[0]}, dezembro volta ${depois[1]} (pela data de hoje); as operações ficam desfeitas; a secretaria não tem os botões (${botoesSec})`);

    /* 3. o mesmo nome no botão, na confirmação e no histórico */
    const nomes = [[b, 'Baixar'], [cc, 'Cancelar'], [db, 'Desfazer baixa'], [dc, 'Desfazer cancelamento']];
    const iguais = nomes.every(([r, n]) => r.botao === n && r.pergunta.startsWith(n) && hist.includes(n));
    L.ok(iguais, `o nome da ação é o mesmo no botão, na confirmação e no histórico: ${nomes.map(([r, n]) => `${n} ("${r.pergunta.slice(0, 40)}"${r.botao !== n ? ', botão "' + r.botao + '"' : ''}${hist.includes(n) ? '' : ', fora do histórico'})`).join('; ')}`);

    /* sabotagem: Baixar sem exigir o motivo */
    const orig = fs.readFileSync(MODELO, 'utf8');
    const trecho = "return self::falha('Escreva o motivo (ao menos 5 letras).');";
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let sit = '';
    try {
      fs.writeFileSync(MODELO, orig.replace(trecho, ';'));
      const s = await X.entrar(navegador, 'secretaria.demo', c.painel['secretaria.demo']);
      await s.p.goto(L.BASE + `painel/financeiro/ficha/${a.aluno}`);
      await s.p.evaluate(async (u) => fetch(u, { method: 'POST', credentials: 'same-origin', body: new URLSearchParams({ motivo: '', envio: 'sab-' + Date.now() }) }), L.BASE + `painel/financeiro/baixar/${t2.id}`);
      await s.ctx.close();
      sit = X.situacao(t2.id);
    } finally {
      fs.writeFileSync(MODELO, orig);
    }
    L.ok(sit !== 'aberto', `sabotagem (Baixar sem exigir o motivo): a prova reprova — sem o motivo, o título ficou ${sit}`);
    L.ok(sha(MODELO) === shaModelo, 'o arquivo sabotado voltou igual (sha256)');
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(MODELO) !== shaModelo) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
