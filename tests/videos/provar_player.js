// Prova do PLAYER dos guias (1.18.0), na demonstração, sem janela: legenda ligada, capítulo que pula para o tempo, avanço
// para o meio (o Apache responde 206, pedido de trecho), guia sem vídeo sem nada, página Vídeos com busca, e o aluno sem os
// vídeos da equipe. Roda no Chromium do Playwright e, se instalados, no Edge e no Chrome (canais); registra os ausentes.
//   node provar_player.js [--guia=<slug do painel com vídeo>] [--guia-portal=<slug do portal com vídeo>]
const { chromium, firefox } = require('playwright-core');
const L = require('./lib');

const opt = k => (process.argv.find(a => a.startsWith('--' + k + '=')) || '').split('=')[1];
const resultados = [];
const ok = (cond, msg) => { resultados.push({ ok: !!cond, msg }); console.log(`${cond ? 'OK   ' : 'FALHA'} ${msg}`); };

async function entrar(browser, conta, senha) {
  const app = /^\d+$/.test(conta) ? 'portal' : 'painel';
  const ctx = await browser.newContext({ viewport: { width: 1280, height: 800 }, locale: 'pt-BR' });
  const p = await ctx.newPage();
  await p.goto(L.C.base + app + '/login/acesso');
  await p.fill('input[name="usuario"]', conta); await p.fill('input[name="senha"]', senha);
  await Promise.all([p.waitForLoadState('load'), p.click('input[name="SendLogin"]')]);
  return p;
}

async function provarPlayer(p, nav, rotaGuia, slug) {
  const faixas = [];
  p.on('response', r => { if (r.url().endsWith(slug + '.mp4')) faixas.push(r.status()); });
  await p.goto(L.C.base + rotaGuia + slug, { waitUntil: 'load' });
  ok(await p.$('#videoGuia'), `${nav}: o guia ${slug} mostra o player no topo`);
  const attrs = await p.$eval('#playerGuia', v => ({ preload: v.getAttribute('preload'), poster: !!v.poster, legenda: !!v.querySelector('track[kind="subtitles"][default]') }));
  ok(attrs.preload === 'none' && attrs.poster && attrs.legenda, `${nav}: preload="none", poster e legenda marcada como padrão`);
  await p.$eval('#playerGuia', v => { v.muted = true; return v.play(); });
  await p.waitForFunction(() => document.getElementById('playerGuia').currentTime > 1, null, { timeout: 30000 });
  ok(true, `${nav}: o vídeo toca`);
  const leg = await p.waitForFunction(() => { const v = document.getElementById('playerGuia'); const t = [...v.textTracks].find(x => x.kind === 'subtitles'); return t && t.mode === 'showing' && t.activeCues && t.activeCues.length ? t.activeCues[0].text : null; }, null, { timeout: 30000 }).then(h => h.jsonValue()).catch(() => null);
  ok(!!leg, `${nav}: a legenda aparece (cue ativa: "${(leg || '').slice(0, 60)}")`);
  const caps = await p.$$eval('#capitulosGuia .capitulo', as => as.map(a => parseFloat(a.dataset.inicio)));
  if (caps.length > 1) {
    const alvo = caps[caps.length - 1];
    await p.click('#capitulosGuia .capitulo >> nth=' + (caps.length - 1));
    await p.waitForTimeout(1500);
    const t = await p.$eval('#playerGuia', v => v.currentTime);
    const marcado = await p.$eval('#capitulosGuia .capitulo.atual', a => parseFloat(a.dataset.inicio)).catch(() => null);
    ok(t >= alvo - 0.3 && t < alvo + 4 && marcado === alvo, `${nav}: o último capítulo (${alvo}s) pula para ${t.toFixed(1)}s e fica marcado`);
  } else ok(false, `${nav}: o guia tem só ${caps.length} capítulo(s)`);
  const dur = await p.$eval('#playerGuia', v => v.duration);
  await p.$eval('#playerGuia', (v, d) => { v.currentTime = d / 2; }, dur);
  await p.waitForFunction(d => { const v = document.getElementById('playerGuia'); return !v.seeking && v.currentTime >= d / 2 - 0.5; }, dur, { timeout: 20000 });
  await p.waitForTimeout(1200);
  const t2 = await p.$eval('#playerGuia', v => v.currentTime);
  ok(t2 > dur / 2, `${nav}: avançar para o meio (${(dur / 2).toFixed(1)}s de ${dur.toFixed(1)}s) toca de lá (${t2.toFixed(1)}s)`);
  ok(faixas.includes(206), `${nav}: o servidor respondeu 206 (pedido de trecho) ao vídeo: ${[...new Set(faixas)].join(', ')}`);
  await p.click('#transcricaoGuia summary');
  ok(await p.$eval('#transcricaoGuia', d => d.open && d.innerText.length > 40), `${nav}: "Ler a transcrição" abre o texto`);
}

(async () => {
  let guarda;
  try { guarda = JSON.parse(L.banco('guarda')); } catch (e) { process.stderr.write(String(e.stderr || e.message)); console.error('ABORTADA pela guarda.'); process.exit(9); }
  console.log(`Demonstração: ${guarda.instituicao} · versão ${guarda.versao}`);
  const man = L.lerManifesto().videos;
  const painel = opt('guia') || Object.keys(man).find(s => !man[s].perfis.includes('aluno') && man[s].capitulos.length > 2);
  const portal = opt('guia-portal') || Object.keys(man).find(s => man[s].perfis.includes('aluno'));
  const semVideo = L.guias().find(s => !man[s] && s !== painel && /secretaria/.test(L.cabecalhoGuia(s).perfis || '') && (L.cabecalhoGuia(s).tipo || '') !== 'escola'); // um guia que a secretaria abre
  await L.comTrava(() => L.comRetrato(['secretaria.demo', '260104', 'administrador', 'prof.laura.demo'], async senhas => {
    const navs = [['Chromium do Playwright', () => chromium.launch({ channel: 'chromium', args: ['--lang=pt-BR'] })], ['Edge', () => chromium.launch({ channel: 'msedge' })], ['Chrome', () => chromium.launch({ channel: 'chrome' })], ['Firefox', () => firefox.launch()]];
    for (const [nome, abrir] of navs) {
      let b; try { b = await abrir(); } catch (e) { ok(true, `${nome}: não instalado neste computador (não testado)`); continue; }
      try {
        const p = await entrar(b, 'secretaria.demo', senhas['secretaria.demo']);
        await provarPlayer(p, nome, 'painel/ajuda/guia/', painel);
        if (nome === 'Chromium do Playwright') {
          if (semVideo) { await p.goto(L.C.base + 'painel/ajuda/guia/' + semVideo); ok(!(await p.$('#videoGuia')) && (await p.$('#conteudoGuia')), `guia sem vídeo (${semVideo}): nada aparece, nem espaço vazio`); }
          await p.goto(L.C.base + 'painel/ajuda/videos');
          const n = await p.$$eval('#gradeVideos .item-video', x => x.length);
          ok(n > 0, `página Vídeos do painel: ${n} vídeo(s) da secretaria`);
          await p.fill('#buscaVideos', 'link de acesso');
          const vis = await p.$$eval('#gradeVideos .item-video', x => x.filter(e => !e.hidden).length);
          ok(vis >= 1 && vis < n, `busca "link de acesso" (título e transcrição): ${vis} de ${n}`);
          if (portal) {
            const a = await entrar(b, '260104', senhas['260104']);
            await provarPlayer(a, 'Portal (aluno)', 'portal/home/guia/', portal);
            await a.goto(L.C.base + 'portal/home/videos');
            const lista = await a.$$eval('#gradeVideos .item-video a', x => x.map(e => e.getAttribute('href').split('/').pop().split('#')[0]));
            const daEquipe = lista.filter(s => !man[s] || !man[s].perfis.includes('aluno'));
            ok(lista.length > 0 && daEquipe.length === 0, `página Vídeos do aluno: ${lista.length} vídeo(s), todos de guias do aluno; nenhum da equipe`);
            await a.goto(L.C.base + 'portal/home/guia/' + painel);
            ok(await a.$('#guiaRecusado') && !(await a.$('#videoGuia')), `o aluno abrindo o guia da equipe (${painel}) pela URL: recusado, sem o vídeo`);
          } else ok(false, 'nenhum guia do portal com vídeo no manifesto');
          /* Onda 5: CADA vídeo do manifesto abre no seu guia (com a conta de um perfil do guia): o player, o vídeo servido
             (206, pedido de trecho), o poster e a legenda. Os guias só da escola não aparecem numa instalação de ensino superior. */
          const conta = { administrador: 'administrador', secretaria: 'secretaria.demo', professor: 'prof.laura.demo', aluno: '260104' };
          const pags = {};
          let abertos = 0, soEscola = [];
          for (const slug of Object.keys(man).sort()) {
            if ((L.cabecalhoGuia(slug).tipo || '') === 'escola') { soEscola.push(slug); continue; }
            const perfil = ['administrador', 'secretaria', 'professor', 'aluno'].find((x) => man[slug].perfis.includes(x));
            const login = conta[perfil];
            if (!pags[login]) pags[login] = await entrar(b, login, senhas[login]);
            const pg = pags[login];
            await pg.goto(L.C.base + (login === '260104' ? 'portal/home/guia/' : 'painel/ajuda/guia/') + slug, { waitUntil: 'load' });
            const src = await pg.$eval('#playerGuia source, #playerGuia', (v) => v.getAttribute('src') || (v.querySelector('source') || {}).src || '').catch(() => '');
            const url = src ? new URL(src, pg.url()).href : '';
            const r = url ? await pg.request.get(url, { headers: { Range: 'bytes=0-1023' } }) : null;
            const poster = await pg.$eval('#playerGuia', (v) => v.poster).catch(() => '');
            const rp = poster ? await pg.request.get(poster) : null;
            const leg = await pg.$eval('#playerGuia track[kind="subtitles"]', (t) => t.src).catch(() => '');
            const rl = leg ? await pg.request.get(leg) : null;
            const bom = !!(await pg.$('#videoGuia')) && r && [200, 206].includes(r.status()) && rp && rp.ok() && rl && rl.ok() && /WEBVTT/.test(await rl.text());
            if (bom) abertos++; else ok(false, `o guia ${slug} (${perfil}) não abriu o vídeo: player ${!!(await pg.$('#videoGuia'))}, vídeo ${r && r.status()}, poster ${rp && rp.status()}, legenda ${rl && rl.status()}`);
          }
          const total = Object.keys(man).length;
          ok(abertos === total - soEscola.length, `a Ajuda abre ${abertos} de ${total - soEscola.length} vídeos do manifesto (o player, o vídeo com pedido de trecho, o poster e a legenda)${soEscola.length ? '; só da escola, não abertos nesta instalação: ' + soEscola.join(', ') : ''}`);
        }
      } finally { await b.close(); }
    }
  }));
  const f = resultados.filter(r => !r.ok).length;
  console.log(`\n${resultados.length - f} OK, ${f} FALHA`);
  process.exitCode = f ? 1 : 0;
})();
