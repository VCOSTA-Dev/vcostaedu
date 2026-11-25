// A PROVA DOS ROTEIROS DO "FAÇA COMIGO" (Onda 5): cada roteiro de painel/app/adms/Roteiros/ que não é de uma fase dos
// Primeiros passos (as fases já têm a jornada da onda 2) é percorrido do começo ao fim no próprio motor do produto, no
// modo "junto", na instalação da gravação (a demonstração), sem gravar vídeo; o guia ligado abre com o botão
// "Faça comigo". Sabotagem: um roteiro com um alvo que não existe é reprovado.
//   node tests/videos/provar_faca_comigo.js [slug,...]
const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright-core');
const L = require('./lib');

const pasta = path.join(L.C.raiz, 'painel', 'app', 'adms', 'Roteiros');
const pedidos = (process.argv[2] || '').split(',').filter(Boolean);
const slugs = pedidos.length ? pedidos : fs.readdirSync(pasta).filter((f) => f.endsWith('.json') && !/^fase-/.test(f)).map((f) => f.slice(0, -5));
const VALORES = { // o que a pessoa digitaria nos passos sem ação no arquivo (os segredos vêm do ambiente)
  'conectar-moodle': { '#url': (process.env.VCOSTAEDU_MOODLE_URL || '').trim(), '#chave': (process.env.VCOSTAEDU_MOODLE_CHAVE || '').trim(), '#salvarMoodle': true, '#testarMoodle': true },
};
let ok = 0; const falhas = [];
const marca = (bom, msg) => { console.log(`  [${bom ? 'OK' : 'FALHA'}]    ${msg}`); if (bom) ok++; else falhas.push(msg); };

(async () => {
  JSON.parse(L.banco('guarda'));
  const browser = await chromium.launch({ headless: true });
  const retrato = path.join(L.C.trabalho, 'retrato_faca.sql');
  L.banco('retrato', retrato);
  try {
    const senhas = JSON.parse(L.banco('senhas', JSON.stringify(['administrador'])));
    const rodar = async (slug) => {
      const g = new L.Gravacao({ browser, slug: 'prova-' + slug, roteiro: { conta: 'administrador', titulo: slug }, ids: {}, senhas, versao: '' });
      await g.abrir();
      try {
        const n = await g.facaComigo(slug, { valores: VALORES[slug] || {} });
        const erros = [...new Set(g.erros)];
        return { n, erros };
      } finally { await g.cdp.send('Page.stopScreencast').catch(() => {}); await g.ctx.close().catch(() => {}); fs.rmSync(g.pastaQuadros, { recursive: true, force: true }); }
    };
    console.log('\n== Onda 5: os roteiros do "Faça comigo"');
    for (const slug of slugs) {
      const r = JSON.parse(fs.readFileSync(path.join(pasta, slug + '.json'), 'utf8'));
      try {
        const x = await rodar(slug);
        marca(x.n === r.passos.length && !x.erros.length, `"${slug}": ${x.n} de ${r.passos.length} passos, do começo ao fim${x.erros.length ? ' — ' + x.erros.join(' | ') : ''}`);
      } catch (e) { marca(false, `"${slug}": ${String(e.message).split('\n')[0]}`); }
      /* o guia ligado mostra o botão */
      if (r.guia) {
        const ctx = await browser.newContext(); const p = await ctx.newPage();
        await p.goto(L.C.base + 'painel/login/acesso'); await p.fill('#usuario', 'administrador'); await p.fill('#senha', senhas.administrador);
        await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
        await p.goto(L.C.base + 'painel/ajuda/guia/' + r.guia);
        marca(!!(await p.$(`a[href*="faca-comigo/iniciar/${slug}"]`)), `o guia "${r.guia}" tem o botão Faça comigo`);
        await ctx.close();
      }
    }
    /* sabotagem: o alvo de um passo que não existe */
    const alvo = slugs.find((s) => s !== 'conectar-moodle') || slugs[0];
    const arq = path.join(pasta, alvo + '.json');
    const orig = fs.readFileSync(arq, 'utf8');
    let pegou = false;
    try {
      const r = JSON.parse(orig); r.passos[0].a = '#alvoQueNaoExiste'; fs.writeFileSync(arq, JSON.stringify(r));
      try { const x = await rodar(alvo); pegou = x.n < r.passos.length; } // o motor pula o passo sem alvo: a prova conta os passos
      catch (e) { pegou = /não está na tela|não apareceu|não terminou/.test(e.message); }
    } finally { fs.writeFileSync(arq, orig); }
    marca(pegou && fs.readFileSync(arq, 'utf8') === orig, `sabotagem (o alvo de um passo de "${alvo}" que não existe): a prova reprova; o arquivo voltou`);
  } finally {
    console.log('  banco: ' + L.banco('restaurar', retrato));
    await browser.close();
  }
  console.log(`onda5/faca-comigo: ${ok} OK, ${falhas.length} FALHA`);
  process.exit(falhas.length ? 1 : 0);
})();
