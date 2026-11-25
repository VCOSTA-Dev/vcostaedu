/*
 * PROVA DA ONDA 8, FINANCEIRO, PARTE 7 — o histórico das ações e o atendimento (D-98). APAGA o banco de prova.
 *   1. a demonstração do colégio; um plano de três parcelas aplicado a um aluno; a secretaria recebe uma e baixa outra; a
 *      supervisão desfaz a baixa;
 *   2. o histórico na ficha: cada ação, com o nome, o título, o detalhe (o valor e a forma; o motivo) e quem fez — na
 *      ordem do que aconteceu; o desfeito marcado;
 *   3. o atendimento: o texto livre com a data (ontem) e, se quiser, o título; entra no histórico com quem atendeu; sem o
 *      texto, recusado; com a data depois de hoje, recusado; o mesmo envio duas vezes grava um só;
 *   sabotagem: o histórico sem o atendimento (o tipo fora da consulta) — a prova acusa; o arquivo volta igual.
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const L = require('../lib');
const X = require('./_escola');

const ETAPA = 'onda8/etapa32';
const MODELO = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'financeiro', 'AdmsFinOperacoes.php');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');

const historico = (p) => p.$$eval('#tabelaHistorico tbody tr[data-tipo]', (l) => l.map((tr) => ({ tipo: tr.dataset.tipo, desfeito: /desfeito/.test(tr.cells[1].textContent), celulas: [...tr.cells].map((td) => td.textContent.trim().replace(/\s+/g, ' ')) })));
const postar = (p, url, campos) => p.evaluate(async ([u, c]) => (await fetch(u, { method: 'POST', credentials: 'same-origin', body: new URLSearchParams(c) })).status, [url, campos]);

(async () => {
  const { navegador } = await L.abrir();
  const shaModelo = sha(MODELO);
  try {
    L.ok(X.montar('onda8-etapa32').status === 0, 'a demonstração do colégio montada pela tela, numa instalação nova');
    const c = X.cred();
    const sec = await X.entrar(navegador, 'secretaria.demo', c.painel['secretaria.demo']);
    const a = await X.arranjo(sec.p, '265001', { mes: 12 });
    const [t1, t2] = a.titulos;
    await sec.p.goto(L.BASE + `painel/financeiro/ficha/${a.aluno}`);
    /* D-99: a tela de Receber pagamento (o Pix pede o documento de quem pagou) */
    await X.receber(sec.p, [t1.id], { forma: 'pix', campos: { documento: '000.265.001-08' } });
    await sec.p.goto(L.BASE + `painel/financeiro/ficha/${a.aluno}`);
    let f = null;
    f = await X.abrirAcao(sec.p, t2.id, 'baixar');
    await sec.p.fill(`${f} input[name="motivo"]`, 'Bolsa de estudo da direção');
    await X.enviar(sec.p, f);
    const sup = await X.entrar(navegador, 'supervisao.demo', c.painel['supervisao.demo']);
    await sup.p.goto(L.BASE + `painel/financeiro/ficha/${a.aluno}`);
    f = await X.abrirAcao(sup.p, t2.id, 'desfazer_baixa');
    await sup.p.fill(`${f} input[name="motivo"]`, 'A bolsa começa só em março');
    await X.enviar(sup.p, f);
    await sup.ctx.close();

    /* 2. o histórico (D-99: a aba Histórico do alto da ficha) */
    await sec.p.goto(L.BASE + `painel/financeiro/ficha/${a.aluno}?ver=historico`);
    /* as operações dos títulos desta prova (o "Plano de prova"): desde a Parte 8, o aluno da demonstração tem o histórico
       do financeiro da demonstração também */
    const h = (await historico(sec.p)).filter((o) => /Plano de prova/.test(o.celulas[2] || ''));
    const nomeSec = X.sql("SELECT nome FROM adms_usuarios WHERE usuario = 'secretaria.demo'");
    const nomeSup = X.sql("SELECT nome FROM adms_usuarios WHERE usuario = 'supervisao.demo'");
    const ach = (tipo) => h.find((o) => o.tipo === tipo) || { celulas: [] };
    const rec = ach('receber'); const bx = ach('baixar'); const db = ach('desfazer_baixa');
    const ordem = h.map((o) => o.tipo).filter((t) => t !== 'gerar');
    L.ok(/Receber/.test(rec.celulas[1]) && /R\$ 500,00 · Pix manual/.test(rec.celulas[3]) && rec.celulas[4] === nomeSec
      && /Baixar/.test(bx.celulas[1]) && bx.desfeito && /Bolsa de estudo/.test(bx.celulas[3])
      && /Desfazer baixa/.test(db.celulas[1]) && /começa só em março/.test(db.celulas[3]) && db.celulas[4] === nomeSup
      && h.filter((o) => o.tipo === 'gerar').length === 3,
      `o histórico: cada ação com o nome, o título, o detalhe e quem fez (${ordem.join(' → ')}); a baixa marcada desfeita; as 3 parcelas geradas`);

    /* 3. o atendimento */
    const ontem = X.diasAntes(1);
    await sec.p.fill('#formAtendimento input[name="data"]', ontem);
    await sec.p.selectOption('#formAtendimento select[name="titulo_id"]', String(t2.id));
    await sec.p.fill('#formAtendimento input[name="texto"]', 'A mãe ligou: vai pagar a parcela de janeiro na primeira semana.');
    await Promise.all([sec.p.waitForNavigation(), sec.p.click('#registrarAtendimento')]);
    const at = (await historico(sec.p)).find((o) => o.tipo === 'atendimento') || { celulas: [] };
    const atBanco = X.sql(`SELECT CONCAT(data_operacao, '|', IFNULL(titulo_id, 0)) FROM fin_operacoes WHERE aluno_id = ${a.aluno} AND tipo = 'atendimento'`);
    await L.print(sec.p, ETAPA, 'historico-e-atendimento');
    L.ok(/Atendimento/.test(at.celulas[1]) && /primeira semana/.test(at.celulas[3]) && at.celulas[4] === nomeSec && atBanco === `${ontem}|${t2.id}`,
      `o atendimento registrado: "${(at.celulas[3] || '').slice(0, 50)}…", com a data de ontem, o título e quem atendeu`);
    const url = L.BASE + `painel/financeiro/atendimento/${a.aluno}`;
    const antes = X.um(`SELECT COUNT(*) FROM fin_operacoes WHERE aluno_id = ${a.aluno} AND tipo = 'atendimento'`);
    await postar(sec.p, url, { data: X.hoje(), texto: '', envio: 'v-' + Date.now() });
    const amanha = X.ymd(new Date(Date.now() + 86400000));
    await postar(sec.p, url, { data: amanha, texto: 'Combinado para amanhã', envio: 'a-' + Date.now() });
    const envio = 'd-' + Date.now();
    await postar(sec.p, url, { data: X.hoje(), texto: 'Ligou de novo para confirmar', envio });
    await postar(sec.p, url, { data: X.hoje(), texto: 'Ligou de novo para confirmar', envio });
    const depois = X.um(`SELECT COUNT(*) FROM fin_operacoes WHERE aluno_id = ${a.aluno} AND tipo = 'atendimento'`);
    L.ok(antes === 1 && depois === 2, `sem o texto e com a data depois de hoje, recusado; o mesmo envio duas vezes grava um só (${antes} → ${depois})`);

    /* sabotagem: o histórico sem o atendimento */
    const orig = fs.readFileSync(MODELO, 'utf8');
    const ini = orig.indexOf('public static function historico(');
    const corpo = orig.slice(ini, orig.indexOf('public static function', ini + 10));
    const m = corpo.match(/WHERE o\.aluno_id = \?/);
    if (ini < 0 || !m) { throw new Error('a sabotagem não achou o trecho'); }
    let n = -1;
    try {
      fs.writeFileSync(MODELO, orig.slice(0, ini) + corpo.replace(m[0], "WHERE o.tipo <> \\'atendimento\\' AND o.aluno_id = ?") + orig.slice(ini + corpo.length));
      await sec.p.goto(L.BASE + `painel/financeiro/ficha/${a.aluno}?ver=historico`);
      n = (await historico(sec.p)).filter((o) => o.tipo === 'atendimento').length;
    } finally {
      fs.writeFileSync(MODELO, orig);
    }
    L.ok(n !== 2, `sabotagem (o histórico sem o atendimento): a prova reprova — ${n} atendimentos na tela`);
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
