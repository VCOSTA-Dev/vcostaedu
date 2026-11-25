/*
 * PROVA DA ONDA 8, CORREÇÃO, PARTE 3.2 E ACRÉSCIMO A (itens 1 e 2) — o painel acadêmico da escola (D-77). APAGA o
 * banco de prova.
 *   1. a demonstração do colégio, montada pela tela numa instalação nova;
 *   2. ANTES (o código de antes, posto de volta por um instante): o tempo do painel acadêmico, de pendências de
 *      lançamento, de frequência abaixo do mínimo e de alunos em risco, até a página final (com a janela de passos);
 *   3. DEPOIS: os quatro abrem de uma vez, sem a janela de passos, em poucos segundos; o tempo de cada um;
 *   4. o painel acadêmico mostra o resultado do ano concluído (2025): a taxa de aprovação, a reprovação por nota e por
 *      frequência e a frequência média, com o número de resultados (não "—", não "0 de 0");
 *   5. o resultado por disciplina mostra as ofertas de 2025, com os aprovados;
 *   sabotagem: o resultado anual da escola fora dos relatórios — a prova acusa (o painel volta a "0 de 0"); o arquivo
 *   volta igual (sha256). Os tempos ficam em saida/onda8-etapa11-tempos.json.
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { spawnSync, execFileSync } = require('child_process');
const L = require('../lib');

const ETAPA = 'onda8/etapa11';
const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste$/.test(BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
const SENHA = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const CALCULO = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'relatorios', 'AdmsRelatorioCalculo.php');
const FONTE = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'relatorios', 'AdmsRelatorioFonte.php');
const SAIDA = path.join(__dirname, '..', 'saida');
const RELS = ['painel-academico', 'pendencias-lancamento', 'frequencia-minimo', 'alunos-risco'];

const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const montar = (rotulo) => spawnSync(process.execPath, [path.join(__dirname, '..', 'onda6', 'medir_demo.js'), 'escola', rotulo], { encoding: 'utf8', maxBuffer: 1 << 24 });
async function entrar(navegador) {
  const ctx = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' });
  const p = await L.pagina(ctx);
  await p.goto(L.BASE + 'painel/login/acesso');
  await p.fill('input[name="usuario"]', 'administrador');
  await p.fill('input[name="senha"]', SENHA);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
  return { ctx, p };
}
/** Abre o relatório e espera a página final (passando pela janela de passos, se ela vier). @return {s, passos} */
async function medir(p, slug) {
  const t0 = Date.now();
  await p.goto(L.BASE + `painel/relatorios/${slug}`, { timeout: 300000 });
  const passos = !!(await p.$('#cartaoCalculando'));
  if (passos) {
    await p.waitForURL(/[?&]calc=[0-9a-f]{32}/, { timeout: 300000 });
    await p.waitForLoadState('load');
  }
  await p.waitForSelector('.vc-cartao-rel, table.lista-dados, #semDados, .page-body', { timeout: 60000 });
  return { s: Math.round((Date.now() - t0) / 100) / 10, passos };
}
/** Os cartões do painel acadêmico: rótulo → {valor, nota}. */
const cartoes = (p) => p.$$eval('.vc-cartao-rel', (l) => Object.fromEntries(l.map((c) => [c.querySelector('.text-secondary').textContent.trim(),
  { valor: c.querySelector('.h1').textContent.trim(), nota: (c.querySelector('small') || { textContent: '' }).textContent.trim() }])));

(async () => {
  const { navegador } = await L.abrir();
  const shas = { [CALCULO]: sha(CALCULO), [FONTE]: sha(FONTE) };
  const tempos = { antes: {}, depois: {} };
  try {
    const r = montar('onda8-etapa11');
    L.ok(r.status === 0, 'a demonstração do colégio montada pela tela, numa instalação nova');
    const adm = await entrar(navegador);

    /* 2. antes: sem o cálculo na própria página (o código de antes) */
    const origCalc = fs.readFileSync(CALCULO, 'utf8');
    const trechoCalc = '        if (self::limite() > 0) {\n            $t0 = microtime(true);';
    if (!origCalc.includes(trechoCalc)) { throw new Error('o "antes" não achou o trecho'); }
    try {
      fs.writeFileSync(CALCULO, origCalc.replace(trechoCalc, '        if (false) {\n            $t0 = microtime(true);'));
      for (const slug of RELS) { tempos.antes[slug] = await medir(adm.p, slug); }
    } finally {
      fs.writeFileSync(CALCULO, origCalc);
    }
    L.ok(Object.values(tempos.antes).some((x) => x.passos), `antes (o código de antes, por um instante): ${RELS.map((s) => `${s} ${tempos.antes[s].s} s${tempos.antes[s].passos ? ' (com a janela de passos)' : ''}`).join('; ')}`);

    /* 3. depois */
    for (const slug of RELS) { tempos.depois[slug] = await medir(adm.p, slug); }
    L.ok(RELS.every((s) => !tempos.depois[s].passos && tempos.depois[s].s <= 8),
      `depois: os quatro abrem de uma vez, sem a janela de passos — ${RELS.map((s) => `${s} ${tempos.depois[s].s} s`).join('; ')}`);
    fs.writeFileSync(path.join(SAIDA, 'onda8-etapa11-tempos.json'), JSON.stringify(tempos, null, 2));

    /* 4. o resultado do ano concluído no painel */
    await adm.p.goto(L.BASE + 'painel/relatorios/painel-academico');
    const c = await cartoes(adm.p);
    await L.print(adm.p, ETAPA, 'painel-academico-escola');
    const ap = c['Taxa de aprovação'] || {};
    const rn = c['Reprovação por nota'] || {};
    const rf = c['Reprovação por frequência'] || {};
    const fm = c['Frequência média'] || {};
    const n = +((ap.nota || '').match(/de (\d+) resultado/) || [0, 0])[1];
    L.ok(n > 0 && /%$/.test(ap.valor || '') && /%$/.test(rn.valor || '') && /%$/.test(rf.valor || '') && rf.valor !== '0,0%' && /%$/.test(fm.valor || ''),
      `o painel mostra o resultado do ano concluído: aprovação ${ap.valor} (${ap.nota}); reprovação por nota ${rn.valor}; por frequência ${rf.valor}; frequência média ${fm.valor}`);

    /* 5. o resultado por disciplina */
    await adm.p.goto(L.BASE + 'painel/relatorios/resultado-disciplina');
    const linhas = await adm.p.$$eval('table.lista-dados tbody tr', (l) => l.map((t) => t.textContent.replace(/\s+/g, ' ').trim())).catch(() => []);
    await L.print(adm.p, ETAPA, 'resultado-disciplina-escola');
    L.ok(linhas.length >= 9 && linhas.every((t) => /\d+ \(\d+,\d%\)/.test(t)), `o resultado por disciplina mostra os componentes de 2025 com os resultados (${linhas.length} linha(s); ex.: "${(linhas[0] || '').slice(0, 80)}")`);

    /* sabotagem: o resultado anual da escola fora dos relatórios */
    const origFonte = fs.readFileSync(FONTE, 'utf8');
    const trechoFonte = 'self::$parcial[$ofertaId] ??= self::anualDaEscola($ofertaId, Calc::oferta($ofertaId))';
    if (!origFonte.includes(trechoFonte)) { throw new Error('a sabotagem não achou o trecho'); }
    let cs = null;
    try {
      fs.writeFileSync(FONTE, origFonte.replace(trechoFonte, 'self::$parcial[$ofertaId] ??= Calc::oferta($ofertaId)'));
      await adm.p.goto(L.BASE + 'painel/relatorios/painel-academico');
      cs = await cartoes(adm.p);
    } finally {
      fs.writeFileSync(FONTE, origFonte);
    }
    await adm.ctx.close();
    L.ok(cs && /de 0 resultado/.test((cs['Taxa de aprovação'] || {}).nota || ''), `sabotagem (o resultado anual fora dos relatórios): a prova reprova — "${((cs || {})['Taxa de aprovação'] || {}).nota || '—'}"`);
    L.ok(sha(CALCULO) === shas[CALCULO] && sha(FONTE) === shas[FONTE], 'os arquivos mexidos voltaram iguais (sha256)');
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    for (const [f, s] of Object.entries(shas)) { if (sha(f) !== s) { console.log('  [AVISO] o arquivo não voltou igual: ' + path.basename(f)); } }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
