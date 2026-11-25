// Jornada de validação — biblioteca comum dos blocos (navegador VISÍVEL, painel de narração com "Próximo ▶").
// Nada de dado real aqui: a URL base, o usuário, a senha e as pastas vêm do ambiente ou de config.local.json (ignorado).
const fs = require('fs');
const path = require('path');
const { spawn, execFileSync } = require('child_process');
const { chromium } = require('playwright-core');

/* ---------------------------------------------------------------- configuração ---------------------------------------------------------------- */
const local = (() => { try { return JSON.parse(fs.readFileSync(path.join(__dirname, 'config.local.json'), 'utf8')); } catch (e) { return {}; } })();
const cfg = (k, padrao) => process.env['JORNADA_' + k] || local[k] || padrao;
const C = {
  base: cfg('BASE', 'http://localhost/vcostaedu/'),
  usuario: cfg('USUARIO', ''),
  senha: cfg('SENHA', ''),
  porta: +cfg('PORTA', '9333'),
  saida: cfg('SAIDA', path.join(__dirname, 'saida')),            // estado da jornada, CSV gerados e exports (fora do git)
  videos: cfg('VIDEOS', ''),                                      // pasta dos vídeos (fora do git); vazio = sem vídeo
  ffmpeg: cfg('FFMPEG', ''),
  slowMo: +cfg('SLOWMO', '400'),
  pdftotext: cfg('PDFTOTEXT', ''),                                // para ler o texto de um PDF (opcional)
};
fs.mkdirSync(C.saida, { recursive: true });
const ESTADO = path.join(C.saida, 'estado.json');
const estado = () => { try { return JSON.parse(fs.readFileSync(ESTADO, 'utf8')); } catch (e) { return {}; } };
const guardar = (k, v) => { const e = estado(); e[k] = v; fs.writeFileSync(ESTADO, JSON.stringify(e, null, 2)); return v; };

/* ---------------------------------------------------------------- registro ---------------------------------------------------------------- */
const LOG = path.join(C.saida, 'registro.jsonl');
let blocoAtual = 0, passoAtual = 0;
function registrar(tipo, texto) {
  const linha = { quando: new Date().toISOString(), bloco: blocoAtual, passo: passoAtual, tipo, texto };
  fs.appendFileSync(LOG, JSON.stringify(linha) + '\n');
  console.log(`[bloco ${blocoAtual} · passo ${passoAtual}] ${tipo.toUpperCase()}: ${texto}`);
}
const ok = (cond, texto) => { registrar(cond ? 'ok' : 'estranheza', texto); return cond; };

/* ---------------------------------------------------------------- painel de narração ---------------------------------------------------------------- */
// Injetado em toda página do contexto (addInitScript): lê o texto do localStorage, para reaparecer depois de um recarregamento.
const PAINEL = `(() => {
  const montar = () => {
    if (!document.body || document.getElementById('jornadaPainel')) return;
    const d = document.createElement('div');
    d.id = 'jornadaPainel';
    d.style.cssText = 'position:fixed;width:330px;z-index:2147483647;background:rgba(20,30,48,.94);color:#fff;font:13px/1.4 system-ui,sans-serif;border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,.4);padding:10px 12px';
    d.innerHTML = '<div id="jpTitulo" style="font-weight:700;color:#ffd54f;margin-bottom:4px"></div><div id="jpTexto" style="max-height:220px;overflow:auto;white-space:pre-line"></div>' +
      '<div style="display:flex;align-items:center;justify-content:space-between;margin-top:8px"><label style="font-size:12px;cursor:pointer"><input type="checkbox" id="jpSemPausas"> Sem pausas neste bloco</label>' +
      '<button id="jpProximo" type="button" style="background:#ffd54f;color:#000;border:0;border-radius:5px;padding:5px 12px;font-weight:700;cursor:pointer">Próximo ▶</button></div>';
    document.body.appendChild(d);
    const ler = () => { try { return JSON.parse(localStorage.getItem('jornada.painel') || '{}'); } catch (e) { return {}; } };
    const pintar = () => { const p = ler(); document.getElementById('jpTitulo').textContent = p.titulo || 'Jornada de validação';
      document.getElementById('jpTexto').textContent = p.texto || ''; const b = document.getElementById('jpProximo');
      b.style.visibility = p.esperando ? 'visible' : 'hidden'; document.getElementById('jpSemPausas').checked = localStorage.getItem('jornada.semPausas') === String(p.bloco || ''); };
    document.getElementById('jpProximo').onclick = () => { window.__jornadaProximo = true; const p = ler(); p.esperando = false; localStorage.setItem('jornada.painel', JSON.stringify(p)); pintar(); };
    document.getElementById('jpSemPausas').onchange = e => { const p = ler(); localStorage.setItem('jornada.semPausas', e.target.checked ? String(p.bloco || '') : ''); if (e.target.checked) window.__jornadaProximo = true; };
    const CANTOS = { be: 'left:10px;bottom:10px;right:auto;top:auto', bd: 'right:10px;bottom:10px;left:auto;top:auto', sd: 'right:10px;top:64px;left:auto;bottom:auto' };
    const posicionar = k => { k = CANTOS[k] ? k : 'be'; for (const par of CANTOS[k].split(';')) { const [p, v] = par.split(':'); d.style[p] = v; } localStorage.setItem('jornada.canto', k); };
    posicionar(localStorage.getItem('jornada.canto') || 'be');
    /* o painel nunca cobre o que vai ser clicado: vai para o primeiro canto que não cruza o alvo */
    window.__jornadaFugir = r => { const cruza = () => { const c = d.getBoundingClientRect(); return !(c.right < r.x - 6 || c.left > r.x + r.width + 6 || c.bottom < r.y - 6 || c.top > r.y + r.height + 6); };
      if (!cruza()) return; for (const k of ['be', 'bd', 'sd']) { posicionar(k); if (!cruza()) return; } };
    window.__jornadaPintar = pintar; pintar();
  };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', montar); else montar();
})();`;

async function mostrar(page, titulo, texto, esperando) {
  const v = { titulo, texto, esperando, bloco: blocoAtual };
  for (let i = 0; i < 20; i++) {
    try {
      await page.evaluate(p => { localStorage.setItem('jornada.painel', JSON.stringify(p)); window.__jornadaProximo = false; if (window.__jornadaPintar) window.__jornadaPintar(); }, v);
      return;
    } catch (e) { await page.waitForTimeout(300); }
  }
}

/** Mostra "Bloco X/12 — Passo Y: texto" e, se `esperar`, espera o clique em "Próximo ▶" (ou "Sem pausas neste bloco"). */
async function passo(page, texto, esperar = true) {
  passoAtual++;
  const titulo = `Bloco ${blocoAtual}/12 — Passo ${passoAtual}`;
  console.log(`\n${titulo}: ${texto.replace(/\n/g, ' ')}`);
  await mostrar(page, titulo, texto, esperar);
  if (!esperar) return;
  for (;;) {
    try {
      await page.waitForFunction(b => window.__jornadaProximo === true || localStorage.getItem('jornada.semPausas') === String(b), blocoAtual, { timeout: 0, polling: 300 });
      break;
    } catch (e) { await page.waitForTimeout(500); await mostrar(page, titulo, texto, true); } // a página navegou: mostra de novo e espera
  }
  await mostrar(page, titulo, texto + '\n\n⏳ executando…', false);
}

/* ---------------------------------------------------------------- ações visíveis (módulo comum com os vídeos dos guias) */
// O painel da jornada sai da frente do alvo antes do destaque.
const { destacar, clicar, clicarENavegar, digitar, escolher, ir, texto, alerta } = require('../comum/visivel').criar({
  base: C.base,
  antesDeDestacar: (page, caixa) => page.evaluate(r => window.__jornadaFugir && window.__jornadaFugir(r), caixa).catch(() => {}),
});

/* ---------------------------------------------------------------- vídeo (screencast do CDP → ffmpeg, VP8) ---------------------------------------------------------------- */
class Gravador {
  constructor(page, nome) { this.page = page; this.nome = nome; this.frames = []; }
  async iniciar() {
    if (!C.videos || !C.ffmpeg) return this;
    fs.mkdirSync(C.videos, { recursive: true });
    this.cdp = await this.page.context().newCDPSession(this.page);
    this.cdp.on('Page.screencastFrame', async f => {
      this.frames.push({ t: f.metadata.timestamp, data: Buffer.from(f.data, 'base64') });
      try { await this.cdp.send('Page.screencastFrameAck', { sessionId: f.sessionId }); } catch (e) { /* página fechada */ }
    });
    await this.cdp.send('Page.startScreencast', { format: 'jpeg', quality: 70, maxWidth: 1600, maxHeight: 1000 });
    return this;
  }
  async parar() {
    if (!this.cdp) return null;
    try { await this.cdp.send('Page.stopScreencast'); } catch (e) {}
    if (this.frames.length < 2) return null;
    const arq = path.join(C.videos, `${this.nome}.webm`);
    const ff = spawn(C.ffmpeg, ['-loglevel', 'error', '-f', 'image2pipe', '-c:v', 'mjpeg', '-framerate', '10', '-i', 'pipe:0', '-y', '-an', '-c:v', 'vp8', '-b:v', '1M', '-deadline', 'realtime', '-speed', '8', arq]);
    const fps = 10;
    for (let i = 0; i < this.frames.length; i++) {
      const dur = i + 1 < this.frames.length ? Math.min(this.frames[i + 1].t - this.frames[i].t, 3) : 1; // pausas longas encurtadas a 3 s
      const n = Math.max(1, Math.round(dur * fps));
      for (let k = 0; k < n; k++) { if (!ff.stdin.write(this.frames[i].data)) await new Promise(r => ff.stdin.once('drain', r)); }
    }
    ff.stdin.end();
    await new Promise(r => ff.on('close', r));
    return arq;
  }
}

/* ---------------------------------------------------------------- conexão ---------------------------------------------------------------- */
/** Conecta ao Chromium já aberto (abrir_navegador.js), reusa a aba e injeta o painel. Devolve { browser, context, page, gravador }. */
async function conectar(numeroBloco, tituloBloco) {
  blocoAtual = numeroBloco; passoAtual = 0;
  const browser = await chromium.connectOverCDP(`http://127.0.0.1:${C.porta}`, { slowMo: C.slowMo });
  const context = browser.contexts()[0];
  if (!context.__jornadaPainel) { await context.addInitScript(PAINEL); context.__jornadaPainel = true; }
  const page = context.pages().find(p => !p.url().startsWith('devtools')) || await context.newPage();
  await page.bringToFront();
  await page.evaluate(() => { const v = document.getElementById('jornadaPainel'); if (v) v.remove(); }).catch(() => {}); // o painel de uma versão anterior sai
  await page.evaluate(PAINEL).catch(() => {});
  await page.evaluate(() => localStorage.setItem('jornada.semPausas', '')).catch(() => {});
  const gravador = await new Gravador(page, `bloco${String(numeroBloco).padStart(2, '0')}`).iniciar();
  registrar('inicio', `Bloco ${numeroBloco}: ${tituloBloco}`);
  return { browser, context, page, gravador };
}

/** Encerra o bloco: painel "Bloco X concluído", vídeo, e DESCONECTA sem fechar a janela. */
async function concluir(s, resumo) {
  if (resumo !== null) await mostrar(s.page, `Bloco ${blocoAtual}/12 concluído`, (resumo ? resumo + '\n\n' : '') + 'Aguarde o próximo bloco.', false); // null: o bloco deixou a mensagem final
  const v = s.gravador ? await s.gravador.parar() : null;
  registrar('fim', `Bloco ${blocoAtual} concluído${v ? ' — vídeo ' + path.basename(v) : ''}`);
  await s.browser.close(); // em connectOverCDP, close() só desconecta: a janela continua aberta
}

/** Roda o bloco; erro de script ou da tela vira registro e o painel avisa (o navegador continua aberto). */
async function rodar(numero, titulo, fn) {
  let s;
  try {
    s = await conectar(numero, titulo);
    const resumo = await fn(s);
    await concluir(s, resumo);
  } catch (e) {
    registrar('interrompido', (e && e.message ? e.message : String(e)).split('\n')[0]);
    if (s) { await mostrar(s.page, `Bloco ${numero}/12 — interrompido`, 'O bloco parou: ' + (e.message || e).toString().split('\n')[0] + '\nO agente vai avisar no chat.', false).catch(() => {});
      if (s.gravador) await s.gravador.parar().catch(() => {}); await s.browser.close().catch(() => {}); }
    process.exitCode = 1;
  }
}

/** CSV com BOM e ";" (o formato dos modelos do sistema), gravado na pasta de saída. */
function csv(nome, cabecalho, linhas) {
  const f = path.join(C.saida, nome);
  fs.writeFileSync(f, '﻿' + cabecalho + '\r\n' + linhas.join('\r\n') + '\r\n');
  return f;
}

module.exports = { PAINEL, Gravador, C, estado, guardar, registrar, ok, passo, mostrar, destacar, clicar, clicarENavegar, digitar, escolher, ir, texto, alerta, rodar, csv, execFileSync };
