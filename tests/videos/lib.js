// Gravador dos vídeos dos guias (1.18.0) — a biblioteca que os roteiros usam (tests/videos/roteiros/<slug>.js).
// Só grava na DEMONSTRAÇÃO: quem chama (gravar.js) passa pela guarda de banco.php antes de abrir o navegador.
// Nada de dado real aqui: a URL, a raiz e as pastas vêm do ambiente ou de config.local.json (ignorado).
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { execFileSync } = require('child_process');
const { CURSOR, criar } = require('../comum/visivel');
const N = require('./narrador'); // Onda 5: a narração no padrão do "Faça comigo", a máscara, a prova e o registro da tela

/* ---------------------------------------------------------------- configuração */
const RAIZ_REPO = path.resolve(__dirname, '..', '..');
const local = (() => { try { return JSON.parse(fs.readFileSync(path.join(__dirname, 'config.local.json'), 'utf8')); } catch (e) { return {}; } })();
const cfg = (k, padrao) => process.env['VCOSTAEDU_VIDEOS_' + k] || local[k] || padrao;
const C = {
  base: cfg('BASE', 'http://localhost/vcostaedu-demo/').replace(/\/?$/, '/'),
  raiz: cfg('RAIZ', path.join(RAIZ_REPO, '..', 'vcostaedu-demo')), // a instalação servida pela BASE (a pasta vizinha da demonstração)
  saida: cfg('SAIDA', path.join(RAIZ_REPO, 'assets', 'videos', 'guias')), // mp4, poster, legendas e transcrição (fora do git)
  trabalho: cfg('TRABALHO', path.join(__dirname, 'saida')),               // webm intermediário e retratos do banco (fora do git)
  php: cfg('PHP', 'C:/xampp/php/php.exe'),
  ffmpeg: cfg('FFMPEG', (() => { try { return require('ffmpeg-static'); } catch (e) { return 'ffmpeg'; } })()),
  largura: 1280, altura: 720,
};
fs.mkdirSync(C.saida, { recursive: true });
fs.mkdirSync(C.trabalho, { recursive: true });
const MANIFESTO = path.join(RAIZ_REPO, 'docs', 'guia', 'videos.json');
const GUIAS = path.join(RAIZ_REPO, 'docs', 'guia', 'tarefas');
const ROTEIROS = path.join(__dirname, 'roteiros');

/* ---------------------------------------------------------------- banco (banco.php, com a guarda) */
function banco(...args) {
  const env = { ...process.env, VCOSTAEDU_VIDEOS_RAIZ: C.raiz, VCOSTAEDU_VIDEOS_BASE: C.base };
  return execFileSync(C.php, [path.join(__dirname, 'banco.php'), ...args], { env, encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'] }).trim();
}

/* ---------------------------------------------------------------- manifesto e hashes */
const sha = t => crypto.createHash('sha256').update(t).digest('hex');
const lerManifesto = () => { try { return JSON.parse(fs.readFileSync(MANIFESTO, 'utf8')); } catch (e) { return { videos: {} }; } };
function gravarManifesto(m) {
  const ord = {}; for (const k of Object.keys(m.videos).sort()) ord[k] = m.videos[k];
  fs.writeFileSync(MANIFESTO, JSON.stringify({ ...m, videos: ord }, null, 2) + '\n');
}
/** O hash do roteiro (o arquivo do guia em roteiros/) e o do texto do guia: mudou um dos dois, o vídeo fica desatualizado. */
const hashRoteiro = slug => sha(fs.readFileSync(path.join(ROTEIROS, slug + '.js'), 'utf8'));
const hashGuia = slug => sha(fs.readFileSync(path.join(GUIAS, slug + '.md'), 'utf8').replace(/\r\n/g, '\n'));
function cabecalhoGuia(slug) {
  const t = fs.readFileSync(path.join(GUIAS, slug + '.md'), 'utf8').replace(/\r\n/g, '\n');
  const c = {}; for (const l of (t.match(/^---\n([\s\S]*?)\n---/) || ['', ''])[1].split('\n')) { const m = l.match(/^([a-z]+):\s*(.*)$/); if (m) c[m[1]] = m[2].trim(); }
  return c;
}
const guias = () => fs.readdirSync(GUIAS).filter(f => f.endsWith('.md')).map(f => f.slice(0, -3)).sort();

/** Situação de cada guia diante do manifesto: sem roteiro, sem vídeo, desatualizado (roteiro ou guia mudou, arquivo faltando) ou ok. */
function situacoes() {
  const m = lerManifesto().videos;
  return guias().map(slug => {
    const temRoteiro = fs.existsSync(path.join(ROTEIROS, slug + '.js'));
    const e = m[slug];
    let situacao = 'ok', motivo = '';
    if (!temRoteiro) { situacao = 'sem roteiro'; }
    else if (!e) { situacao = 'sem vídeo'; }
    else if (e.hash_roteiro !== hashRoteiro(slug)) { situacao = 'desatualizado'; motivo = 'o roteiro mudou'; }
    else if (e.hash_guia !== hashGuia(slug)) { situacao = 'desatualizado'; motivo = 'o guia foi editado depois da gravação'; }
    else if (!fs.existsSync(path.join(C.saida, e.arquivo))) { situacao = 'desatualizado'; motivo = 'o arquivo não está na pasta de vídeos'; }
    return { slug, situacao, motivo };
  });
}

/* ---------------------------------------------------------------- telas */
const ESTILO = '.preloader{display:none!important}';
/** Mascara CPF e RG gerados (D-267): a demonstração inventa os números, mas nenhum vídeo os mostra. */
const MASCARA = `(() => {
  const cpf = /\\b\\d{3}\\.?\\d{3}\\.?\\d{3}-?\\d{2}\\b/g, rg = /\\b\\d{7}\\b/g;
  const tratar = raiz => {
    const w = document.createTreeWalker(raiz, NodeFilter.SHOW_TEXT);
    for (let n = w.nextNode(); n; n = w.nextNode()) {
      if (n.parentElement && n.parentElement.closest('#vcostaeduVisor')) continue;
      const v = n.nodeValue; if (cpf.test(v) || rg.test(v)) n.nodeValue = v.replace(cpf, '•••.•••.•••-••').replace(rg, '•••••••'); cpf.lastIndex = 0; rg.lastIndex = 0;
    }
    document.querySelectorAll('input').forEach(i => { if (/cpf|(^|_)rg($|_)/i.test(i.name || i.id || '') && i.value) i.style.webkitTextSecurity = 'disc'; }); // Onda 5: só desenhado (o valor do formulário não muda)
  };
  const ligar = () => { if (!document.body) return; tratar(document.body); new MutationObserver(() => tratar(document.body)).observe(document.body, { childList: true, subtree: true, characterData: true }); const s = document.createElement('style'); s.textContent = '${ESTILO}'; document.head.appendChild(s); };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ligar); else ligar();
})();`;

function cartelaHtml(titulo, versao) {
  const esc = s => String(s).replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
  return `<html><head><meta charset="utf-8"><style>
    body{margin:0;height:100vh;display:flex;flex-direction:column;justify-content:center;align-items:center;background:linear-gradient(135deg,#0d3b66,#145c9e);color:#fff;font-family:"Source Sans Pro",Segoe UI,Arial,sans-serif}
    .rotulo{font-size:22px;letter-spacing:.2em;text-transform:uppercase;opacity:.75}.titulo{font-size:52px;font-weight:700;margin:18px 40px;text-align:center}
    .versao{font-size:20px;opacity:.8}</style></head><body>
    <div class="rotulo">Guia da Ajuda</div><div class="titulo">${esc(titulo)}</div><div class="versao">VCOSTAEDU · versão ${esc(versao)} · demonstração fictícia</div></body></html>`;
}

/* ---------------------------------------------------------------- tempos → legenda, capítulos, transcrição */
const fmt = ms => { const t = Math.max(0, Math.round(ms)); const h = String(Math.floor(t / 3600000)).padStart(2, '0'); const m = String(Math.floor(t / 60000) % 60).padStart(2, '0'); const s = String(Math.floor(t / 1000) % 60).padStart(2, '0'); return `${h}:${m}:${s}.${String(t % 1000).padStart(3, '0')}`; };
function vtt(cues) { return 'WEBVTT\n\n' + cues.map((c, i) => `${i + 1}\n${fmt(c.ini)} --> ${fmt(c.fim)}\n${c.texto}\n`).join('\n'); }
const leitura = texto => Math.min(9000, Math.max(2600, texto.split(/\s+/).length * 330)); // tempo mínimo de leitura de uma legenda

/* ---------------------------------------------------------------- a gravação de um guia */
class Gravacao {
  constructor({ browser, slug, roteiro, ids, senhas, versao, segredos = [], cartela = null }) {
    Object.assign(this, { browser, slug, roteiro, ids, senhas, versao, segredos, cartela });
    this.legendas = []; this.capitulos = []; this.posterMs = null;
    this.erros = []; this.telas = []; this.cortes = []; // Onda 5: a prova (console e PHP), o registro da tela e as esperas cortadas
    const v = criar({ base: C.base, cursor: true, destaqueMs: 700, atrasoTecla: 55 });
    for (const k of Object.keys(v)) this['_' + k] = v[k];
  }

  /** Entra (fora da gravação) e devolve o estado de sessão da conta. */
  async sessao(conta) {
    const app = /^\d+$/.test(conta) ? 'portal' : 'painel';
    const ctx = await this.browser.newContext({ viewport: { width: C.largura, height: C.altura }, locale: 'pt-BR', colorScheme: 'light' });
    const p = await ctx.newPage();
    await p.goto(C.base + app + '/login/acesso', { waitUntil: 'load' });
    await p.fill('input[name="usuario"]', conta);
    await p.fill('input[name="senha"]', this.senhas[conta]);
    await Promise.all([p.waitForLoadState('load'), p.click('input[name="SendLogin"]')]);
    await p.waitForLoadState('networkidle').catch(() => {});
    if (await p.$('input[name="SendLogin"]')) throw new Error(`login de ${conta} não entrou`);
    const estado = await ctx.storageState();
    await ctx.close();
    return estado;
  }

  async abrir() {
    const r = this.roteiro;
    const estado = r.conta ? await this.sessao(r.conta) : undefined;
    this.ctx = await this.browser.newContext({ viewport: { width: C.largura, height: C.altura }, locale: 'pt-BR', storageState: estado, acceptDownloads: true, colorScheme: 'light' });
    await this.ctx.addInitScript(CURSOR);
    await this.ctx.addInitScript(MASCARA);
    await this.ctx.addInitScript(N.mascaraNaPagina, this.segredos);
    await this.ctx.addInitScript(N.narradorNaPagina);
    this.page = await this.ctx.newPage();
    N.vigiar(this.page, this.erros);
    /* Onda 5: cada página carregada — o aviso do PHP recusa o vídeo; o texto (depois da máscara) vai para o registro da varredura */
    this.page.on('load', async () => {
      const pg = this.page;
      await pg.waitForTimeout(250).catch(() => {});
      const html = await pg.content().catch(() => '');
      const m = html.match(N.AVISO_PHP);
      if (m) this.erros.push('aviso do PHP em ' + pg.url().replace(/^https?:\/\/[^/]+/, '') + ': ' + m[0].slice(0, 160));
      this.telas.push(await N.textoVisivel(pg));
    });
    this.page.on('dialog', d => d.accept()); // confirmações do sistema (confirm) são aceitas
    await require('../tabler/lib').confirmarSozinho(this.page); // D-100: a janela de confirmação do sistema também
    /* D-473: quadros JPEG do screencast do Chromium, cada um com o seu horário (o mesmo relógio da narração), codificados
       direto em H.264 — imagem nítida e tempos exatos; nenhum webm intermediário. */
    this.quadros = []; this.pastaQuadros = path.join(C.trabalho, 'quadros_' + this.slug);
    fs.rmSync(this.pastaQuadros, { recursive: true, force: true }); fs.mkdirSync(this.pastaQuadros, { recursive: true });
    this.cdp = await this.ctx.newCDPSession(this.page);
    this.cdp.on('Page.screencastFrame', async f => {
      const arq = path.join(this.pastaQuadros, String(this.quadros.length).padStart(6, '0') + '.jpg');
      fs.writeFileSync(arq, Buffer.from(f.data, 'base64'));
      this.quadros.push({ t: f.metadata.timestamp * 1000, arq });
      try { await this.cdp.send('Page.screencastFrameAck', { sessionId: f.sessionId }); } catch (e) { /* fechando */ }
    });
    await this.page.setContent(this.cartela || cartelaHtml(r.titulo || cabecalhoGuia(this.slug).titulo, this.versao));
    await this.cdp.send('Page.startScreencast', { format: 'jpeg', quality: 88, maxWidth: C.largura, maxHeight: C.altura });
    await this.page.waitForTimeout(300);
    this.tIni = Date.now(); // o vídeo começa na cartela
    await this.page.waitForTimeout(2000);
  }

  /* --- narração: cada passo vira legenda; cada capítulo, um item da lista de capítulos */
  agora() { return Date.now() - this.tIni; }
  async passo(texto, janela = null) {
    const ult = this.legendas[this.legendas.length - 1];
    if (ult) { const falta = leitura(ult.texto) - (this.agora() - ult.ini); if (falta > 0) await this.page.waitForTimeout(falta); ult.fim = this.agora(); }
    if (!this.capitulos.length) this.capitulos.push({ ini: this.agora(), titulo: texto.split(/[.:—]/)[0].slice(0, 60) });
    this.legendas.push({ ini: this.agora(), fim: null, texto: janela && janela.t ? `${janela.t}. ${texto}` : texto });
    console.log(`  [${(this.agora() / 1000).toFixed(1)}s] ${texto}`);
    /* Onda 5: a janela de narração na tela (o padrão do "Faça comigo"): janela = { t (título), aviso ("Agora: …"), guia, cima } */
    { /* sempre: sem janela, o vídeo seria só o cursor agindo (o "Faça comigo" do produto, quando está na tela, a esconde) */
      janela = janela || {};
      this.janela = { guia: janela.guia || this.guiaJanela || (this.roteiro && this.roteiro.titulo) || '', t: janela.t || '', x: texto, aviso: janela.aviso || '', cima: !!janela.cima };
      await this.page.evaluate((d) => window.__vcNarrar && window.__vcNarrar(d), this.janela).catch(() => {});
    }
    this.telas.push(await N.textoVisivel(this.page));
  }
  /** Onda 5: o aviso do que foi feito, na janela de narração (e na legenda e na transcrição). */
  async feito(texto) {
    if (this.janela) {
      this.janela = { ...this.janela, aviso: '', feito: texto };
      await this.page.evaluate((d) => window.__vcNarrar && window.__vcNarrar(d), this.janela).catch(() => {});
    }
    const ult = this.legendas[this.legendas.length - 1];
    if (ult) { ult.fim = this.agora(); }
    this.legendas.push({ ini: this.agora(), fim: null, texto: '✓ ' + texto });
    console.log(`  [${(this.agora() / 1000).toFixed(1)}s] ✓ ${texto}`);
    await this.page.waitForTimeout(1500);
    this.telas.push(await N.textoVisivel(this.page));
  }
  /** Onda 5: tira a janela de narração (a tela fica limpa, ou o "Faça comigo" do painel assume). */
  async semJanela() { this.janela = null; await this.page.evaluate(() => window.__vcNarrar && window.__vcNarrar(null)).catch(() => {}); }
  /** Onda 5: uma espera longa (a montagem da demonstração, um lote) fica no vídeo com `alvoMs` de duração. */
  async acelerar(fn, alvoMs = 4000) {
    const ini = this.agora();
    try { return await fn(); } finally { const fim = this.agora(); if (fim - ini > alvoMs + 500) this.cortes.push({ ini, fim, alvo: alvoMs }); }
  }
  /** O tempo do vídeo (com as esperas cortadas) de um instante da gravação. */
  mapear(t) {
    let r = t;
    for (const c of this.cortes) {
      if (t <= c.ini) continue;
      const dur = c.fim - c.ini;
      r -= t >= c.fim ? dur - c.alvo : (t - c.ini) * (1 - c.alvo / dur);
    }
    return r;
  }
  capitulo(titulo) { this.capitulos.push({ ini: this.agora(), titulo }); }
  poster() { this.posterMs = this.agora(); }
  pausa(ms = 1200) { return this.page.waitForTimeout(ms); }

  /* --- ações visíveis (o cursor desenhado vai até o alvo) */
  ir(rota) { return this._ir(this.page, rota); }
  /** Onda 5: a janela de narração foge do alvo (vai para o alto quando ele está no canto de baixo), como a do produto. */
  async fugir(alvo) {
    if (!this.janela) return;
    const l = typeof alvo === 'string' ? this.page.locator(alvo).first() : alvo;
    await l.scrollIntoViewIfNeeded({ timeout: 3000 }).catch(() => {});
    const c = await l.boundingBox({ timeout: 3000 }).catch(() => null);
    if (!c) return;
    const cima = c.x + c.width > C.largura - 460 && c.y + c.height > C.altura - 280;
    if (cima !== !!this.janela.cima) { this.janela = { ...this.janela, cima }; await this.page.evaluate((d) => window.__vcNarrar && window.__vcNarrar(d), this.janela).catch(() => {}); }
  }
  async destacar(a) { await this.fugir(a); return this._destacar(this.page, a); }
  async clicar(a, o) { await this.fugir(a); return this._clicar(this.page, a, o); }
  async clicarENavegar(a) { await this.fugir(a); return this._clicarENavegar(this.page, a); }
  async digitar(a, t) { await this.fugir(a); return this._digitar(this.page, a, t); }
  async escolher(a, v) { await this.fugir(a); return this._escolher(this.page, a, v); }
  texto(s) { return this._texto(this.page, s); }
  alerta() { return this._alerta(this.page); }
  /** Onda 5: abre um item do menu lateral (o do painel e o do portal são o mesmo): abre o grupo, se fechado, e clica. */
  async menu(hrefParte) {
    const item = this.page.locator(`#railMenu a.nav-link[href*="${hrefParte}"]`).first();
    const grupo = item.locator('xpath=ancestor::li[contains(@class,"vc-side-grupo")]/button').first();
    /* o grupo fechado esconde o item por altura (para o navegador ele "existe", mas fica coberto): vale o aria-expanded */
    if (await grupo.count() && (await grupo.getAttribute('aria-expanded')) !== 'true') {
      await this.clicar(grupo);
      await this.page.waitForFunction((b) => b.getAttribute('aria-expanded') === 'true', await grupo.elementHandle(), { timeout: 5000 }).catch(() => {});
    }
    await this.pausa(600);
    await this.clicarENavegar(item);
  }
  /** O item marcado no menu lateral. */
  menuAtivo() { return this.page.$$eval('#railMenu a.nav-link.active', as => as.map(a => (a.querySelector('.nav-link-title') || a).innerText.trim())); }
  /** Rola a página devagar até o alvo (ou n pixels), para o espectador acompanhar. */
  async rolar(alvo) {
    if (typeof alvo === 'number') { for (let i = 0; i < 10; i++) { await this.page.mouse.wheel(0, alvo / 10); await this.page.waitForTimeout(60); } return; }
    await this.page.locator(alvo).first().evaluate(el => el.scrollIntoView({ behavior: 'smooth', block: 'center' })).catch(() => {});
    await this.page.waitForTimeout(900);
  }
  /** Confere uma condição da tela; falhando, o vídeo não é aceito (o roteiro não mostrou o que o guia ensina). */
  confere(cond, msg) { if (!cond) throw new Error('conferência: ' + msg); }

  /** Entra visivelmente com uma conta (para os guias de entrada): digita usuário e senha na tela de login. */
  async entrar(conta) {
    const app = /^\d+$/.test(conta) ? 'portal' : 'painel';
    await this.ir(app + '/login/acesso');
    await this.digitar('input[name="usuario"]', conta);
    await this.digitar('input[name="senha"]', this.senhas[conta]);
    await this.clicarENavegar('input[name="SendLogin"]');
  }

  /** Um link que abre noutra aba (target=_blank) é aberto NESTA aba: o vídeo é de uma aba só. */
  async abrirAqui(alvo) {
    const l = typeof alvo === 'string' ? this.page.locator(alvo).first() : alvo;
    await this._destacar(this.page, l);
    const href = await l.getAttribute('href');
    await this.page.goto(new URL(href, this.page.url()).href, { waitUntil: 'load' });
    await this.page.waitForLoadState('networkidle').catch(() => {});
  }

  /** Mostra um PDF do sistema (prévia, documento emitido) num visor, nesta aba, e volta à tela anterior. */
  async pdf(alvoOuUrl, segundos = 5) {
    let url = alvoOuUrl;
    if (typeof alvoOuUrl !== 'string' || !/^(https?:|[a-z-]+\/)/.test(alvoOuUrl)) {
      const l = typeof alvoOuUrl === 'string' ? this.page.locator(alvoOuUrl).first() : alvoOuUrl;
      await this._destacar(this.page, l);
      url = new URL(await l.getAttribute('href'), this.page.url()).href;
    } else if (!/^https?:/.test(url)) url = C.base + url;
    const r = await this.ctx.request.get(url);
    const corpo = await r.body();
    if (!corpo.slice(0, 5).toString().startsWith('%PDF')) throw new Error('não é PDF: ' + url);
    const voltar = this.page.url();
    const pdfjs = path.dirname(require.resolve('pdfjs-dist/package.json'));
    const ORIGEM = 'http://visor-pdf.VCOSTAEDU/';
    await this.page.route(ORIGEM + '**', rota => {
      const n = rota.request().url().slice(ORIGEM.length);
      if (n === 'doc.pdf') return rota.fulfill({ body: corpo, contentType: 'application/pdf' });
      if (n === 'visor.html') return rota.fulfill({ contentType: 'text/html', body: `<html><head><meta charset="utf-8"></head><body id="vcostaeduVisor" style="margin:0;background:#3c3f44;display:flex;justify-content:center;align-items:flex-start;overflow:hidden">
        <canvas id="c" style="margin-top:12px;box-shadow:0 4px 18px rgba(0,0,0,.5);background:#fff"></canvas>
        <script type="module">import * as pdfjs from './pdf.min.mjs'; pdfjs.GlobalWorkerOptions.workerSrc = './pdf.worker.min.mjs';
        const doc = await pdfjs.getDocument('./doc.pdf').promise; const pg = await doc.getPage(1); const v1 = pg.getViewport({ scale: 1 });
        const vp = pg.getViewport({ scale: (innerHeight - 24) / v1.height * 1.6 }); const c = document.getElementById('c'); c.width = vp.width; c.height = vp.height;
        c.style.height = (innerHeight - 24) + 'px'; const ctx = c.getContext('2d'); await pg.render({ canvasContext: ctx, viewport: vp }).promise;
        /* D-267 também no PDF: tarja sobre CPF, RG e sequências longas de dígitos, pela posição do texto do pdf.js */
        const re = /\\d{3}\\.?\\d{3}\\.?\\d{3}-?\\d{2}|\\d{1,2}\\.\\d{3}\\.\\d{3}-?[\\dxX]|\\d{7,}/g;
        for (const it of (await pg.getTextContent()).items) {
          const s = it.str || ''; let m; re.lastIndex = 0;
          while ((m = re.exec(s))) {
            const t = pdfjs.Util.transform(vp.transform, it.transform), h = Math.hypot(t[2], t[3]), w = it.width * vp.scale;
            const c1 = w / Math.max(1, s.length), x = t[4] + c1 * m.index - c1 * 0.5, mw = c1 * (m[0].length + 1.5); // um caractere a mais: a largura é estimada
            ctx.fillStyle = '#d5d8dc'; ctx.fillRect(x - 1, t[5] - h, mw + 2, h * 1.25);
            ctx.fillStyle = '#555'; ctx.font = (h * 0.8) + 'px sans-serif'; ctx.fillText('•••', x + 2, t[5] - h * 0.15);
          }
        }
        document.body.dataset.pronto = '1';</script></body></html>` });
      return rota.fulfill({ path: path.join(pdfjs, 'build', n), contentType: 'text/javascript' });
    });
    await this.page.goto(ORIGEM + 'visor.html');
    await this.page.waitForSelector('body[data-pronto="1"]', { timeout: 30000 });
    await this.page.waitForTimeout(segundos * 1000);
    await this.page.unroute(ORIGEM + '**');
    await this.page.goto(voltar, { waitUntil: 'load' });
  }

  /** Onda 5: um roteiro do "Faça comigo" (painel/app/adms/Roteiros/<slug>.json) no próprio motor do produto, no modo
   *  "junto": a janela do produto narra, destaca e confere; o gravador faz, à vista (o cursor, a digitação), o que a
   *  pessoa faria — as ações do arquivo (`faz`) e, nos passos sem ação, o valor dado em `valores` (seletor → texto).
   *  O mesmo arquivo é a prova (o roteiro tem de ir do começo ao fim) e o botão "Faça comigo" do guia. */
  async facaComigo(slug, { valores = {}, ano = String(new Date().getFullYear()) } = {}) {
    const r = JSON.parse(fs.readFileSync(path.join(C.raiz, 'painel', 'app', 'adms', 'Roteiros', slug + '.json'), 'utf8'));
    await this.semJanela();
    const inicio = String(r.inicio).replace(/\{ano\}/g, ano);
    await this.ir('painel/' + inicio + (inicio.includes('?') ? '&' : '?') + 'tour=' + encodeURIComponent(slug) + '&modo=junto&gravar=1');
    const p = this.page;
    const feitos = new Set();
    const subst = (v) => (typeof v === 'string' ? v.replace(/\{ano\}/g, ano) : v);
    const limpar = (t) => String(t || '').replace(/\*\*/g, '');
    for (let volta = 0; volta < 200; volta++) {
      if (await p.evaluate(() => window.vcTourFim === true).catch(() => false)) return feitos.size;
      const caixa = await p.waitForSelector('#vc-tour[data-passo]', { timeout: 15000 }).catch(() => null);
      if (!caixa) {
        if (await p.evaluate(() => window.vcTourFim === true).catch(() => false)) return feitos.size;
        throw new Error(`Faça comigo "${slug}": a janela não apareceu em ${p.url().replace(C.base, '')}`);
      }
      const i = +(await caixa.getAttribute('data-passo'));
      const passo = r.passos[i];
      if (!passo) throw new Error(`Faça comigo "${slug}": passo ${i + 1} fora do roteiro`);
      if (!feitos.has(i)) {
        feitos.add(i);
        await this.passo(`${limpar(passo.t)}. ${limpar(passo.x)}${passo.aviso ? ' Agora: ' + limpar(passo.aviso) : ''}`);
        await p.waitForTimeout(Math.min(5000, Math.max(2200, (passo.t.length + passo.x.length) * 28)));
        if (passo.a && !(await p.$(passo.a))) throw new Error(`Faça comigo "${slug}": passo ${i + 1} ("${passo.t}"): o alvo ${passo.a} não está na tela`);
        if (passo.a && valores[passo.a] === true) await this.clicarENavegar(passo.a); // o passo é o clique da pessoa (sem ação no arquivo)
        else if (passo.a && valores[passo.a] !== undefined) {
          /* o segredo (o endereço, a chave) é mascarado ANTES da primeira tecla: nem um pedaço aparece */
          if (this.segredos.includes(valores[passo.a])) await p.$eval(passo.a, (e) => { e.setAttribute('data-vc-gravar-mascarar', '1'); e.style.webkitTextSecurity = 'disc'; });
          await this.digitar(passo.a, valores[passo.a]);
        }
        for (const a of passo.faz || []) {
          const [tipo, sel] = a; const valor = subst(a[2]);
          if (tipo === 'esperar') { await p.waitForTimeout(Math.min(+sel || 500, 2500)); continue; }
          if (tipo === 'ir') { await this.ir('painel/' + String(subst(sel)).replace(/^\//, '')); continue; }
          if (tipo === 'chamar' || tipo === 'lembrar') continue;
          const el = await p.waitForSelector(sel, { state: 'attached', timeout: 8000 }).catch(() => null);
          if (!el) throw new Error(`Faça comigo "${slug}": passo ${i + 1}: a ação "${tipo}" não achou ${sel}`);
          if (tipo === 'preencher' || tipo === 'acrescentar') {
            if (await el.isVisible()) await this.digitar(sel, String(valor));
            else await el.evaluate((e, v) => { e.value = v; e.dispatchEvent(new Event('input', { bubbles: true })); e.dispatchEvent(new Event('change', { bubbles: true })); }, String(valor));
          } else if (tipo === 'escolher') {
            if (await el.isVisible()) await this._destacar(p, sel).catch(() => {});
            await el.evaluate((e, v) => {
              if (e.tomselect) { e.tomselect.setValue(String(v)); return; }
              let alvo = null;
              for (const o of e.options) { if (v === '{primeira}' ? (o.value !== '' && o.value !== '0') : (o.value === v || o.text.trim() === v)) { alvo = o; break; } }
              if (alvo) { if (e.multiple) alvo.selected = true; else e.value = alvo.value; }
              e.dispatchEvent(new Event('change', { bubbles: true }));
              if (window.jQuery) window.jQuery(e).trigger('change');
            }, valor);
          } else if (tipo === 'marcar' || tipo === 'desmarcar') {
            const quer = tipo === 'marcar';
            if (await el.evaluate((e) => e.checked) !== quer) {
              const rot = await el.evaluate((e) => (e.id && document.querySelector(`label[for="${e.id}"]`) ? `label[for="${e.id}"]` : ''));
              if (rot && await p.isVisible(rot)) await this.clicar(rot); else await el.evaluate((e) => e.click());
            }
          } else if (tipo === 'anexar') {
            await el.setInputFiles({ name: a[3], mimeType: /\.png$/i.test(a[3] || '') ? 'image/png' : 'application/pdf', buffer: Buffer.from(a[2], 'base64') });
          } else if (tipo === 'clicar') {
            if (await el.isVisible()) await this._destacar(p, sel).catch(() => {});
            await Promise.all([p.waitForLoadState('load').catch(() => {}), el.click()]);
            await p.waitForTimeout(700);
            await p.waitForLoadState('networkidle').catch(() => {});
          } else if (tipo === 'nota') {
            await this._destacar(p, sel).catch(() => {});
            await el.click();
            const inp = await p.waitForSelector('#tabelaLancaNotas input.tabledit-input[name="nota"]:visible', { timeout: 6000 });
            await inp.fill(''); await inp.type(String(valor), { delay: 60 });
            await Promise.all([p.waitForResponse((x) => /edit-notas/.test(x.url()), { timeout: 8000 }).catch(() => null), inp.press('Enter')]);
          }
        }
      }
      await p.waitForTimeout(1300);
      if (await p.evaluate(() => window.vcTourFim === true).catch(() => false)) return feitos.size;
      if (await p.$(`#vc-tour[data-passo="${i}"]`)) {
        const erro = await p.$eval('#vc-tour .vc-tour-erro-texto:not([hidden])', (e) => e.textContent).catch(() => '');
        if (erro && volta > 2 * r.passos.length + 4) throw new Error(`Faça comigo "${slug}": passo ${i + 1}: ${erro}`);
        const btn = await p.$('#vc-tour .vc-tour-avancar');
        if (btn && await btn.isEnabled()) { await this._destacar(p, '#vc-tour .vc-tour-avancar').catch(() => {}); await Promise.all([p.waitForLoadState('load').catch(() => {}), btn.click()]); await p.waitForTimeout(800); }
      }
    }
    throw new Error(`Faça comigo "${slug}": o roteiro não terminou`);
  }

  /** Fecha a gravação e produz MP4 (H.264, sem áudio), poster, legenda, capítulos e transcrição.
   *  Onda 5: as esperas longas saem cortadas (acelerar), o vídeo é recusado com erro no console ou aviso do PHP (a
   *  prova), o texto das telas vai para `<slug>.tela.txt` na pasta de trabalho (a varredura) e, com `separar`, cada
   *  capítulo vira também um vídeo próprio (`<slug>-NN`). */
  async fechar(opcoes = {}) {
    const ult = this.legendas[this.legendas.length - 1];
    if (ult) { const falta = leitura(ult.texto) - (this.agora() - ult.ini); if (falta > 0) await this.page.waitForTimeout(falta); ult.fim = this.agora(); }
    await this.page.waitForTimeout(800);
    this.telas.push(await N.textoVisivel(this.page));
    const durReal = this.agora();
    const fimAbs = Date.now();
    await this.cdp.send('Page.stopScreencast').catch(() => {});
    await this.page.waitForTimeout(300);
    await this.ctx.close();
    const s = this.slug;
    fs.writeFileSync(path.join(C.trabalho, s + '.tela.txt'), this.telas.join('\n\f\n'));
    /* o erro que o roteiro mostra de propósito (ex.: a recusa do navegador em abrir o painel dentro de um iframe) */
    const esperados = (this.roteiro && this.roteiro.errosEsperados) || [];
    this.erros = this.erros.filter((e) => !esperados.some((re) => re.test(e)));
    if (this.erros.length) {
      fs.rmSync(this.pastaQuadros, { recursive: true, force: true });
      throw new Error('a gravação viu erro na tela (o vídeo não é aceito): ' + [...new Set(this.erros)].slice(0, 5).join(' | '));
    }
    const m = (t) => this.mapear(t);
    const dur = m(durReal);
    const mp4 = path.join(C.saida, s + '.mp4');
    /* a lista de quadros com a duração de cada um (no tempo do vídeo, com os cortes), recortada entre a cartela e o fim */
    const q = this.quadros.filter(x => x.t <= fimAbs).sort((a, b) => a.t - b.t);
    let i0 = q.findIndex(x => x.t > this.tIni); i0 = i0 <= 0 ? 0 : i0 - 1;
    const usados = q.slice(i0);
    if (!usados.length) throw new Error('nenhum quadro gravado');
    const linhas = [];
    usados.forEach((x, i) => {
      const ini = m(Math.max(x.t, this.tIni) - this.tIni), fim = m((i + 1 < usados.length ? usados[i + 1].t : fimAbs) - this.tIni);
      if (fim - ini <= 0.5) return;
      linhas.push(`file '${x.arq.split(path.sep).join('/')}'`, `duration ${((fim - ini) / 1000).toFixed(3)}`);
    });
    linhas.push(`file '${usados[usados.length - 1].arq.split(path.sep).join('/')}'`);
    const lista = path.join(this.pastaQuadros, 'lista.txt');
    fs.writeFileSync(lista, linhas.join('\n') + '\n');
    execFileSync(C.ffmpeg, ['-y', '-loglevel', 'error', '-f', 'concat', '-safe', '0', '-i', lista, '-t', (dur / 1000).toFixed(3),
      '-vf', `scale=${C.largura}:${C.altura}:flags=lanczos,fps=25,format=yuv420p`, '-c:v', 'libx264', '-preset', 'slow', '-crf', '24', '-tune', 'stillimage',
      '-profile:v', 'main', '-movflags', '+faststart', '-an', mp4]);
    fs.rmSync(this.pastaQuadros, { recursive: true, force: true }); // os quadros são intermediários
    const tPoster = this.posterMs !== null ? m(this.posterMs) : m((this.legendas[Math.floor(this.legendas.length / 2)] || { ini: durReal / 2 }).ini) + 1500;
    execFileSync(C.ffmpeg, ['-y', '-loglevel', 'error', '-ss', (Math.max(0, Math.min(tPoster, dur - 500)) / 1000).toFixed(3), '-i', mp4, '-frames:v', '1', '-q:v', '5', path.join(C.saida, s + '.jpg')]);
    const legendas = this.legendas.map(l => ({ ini: m(l.ini), fim: m(l.fim ?? durReal), texto: l.texto }));
    const caps = this.capitulos.map((c, i) => ({ ini: m(c.ini), fim: m((this.capitulos[i + 1] || { ini: durReal }).ini), texto: c.titulo }));
    fs.writeFileSync(path.join(C.saida, s + '.vtt'), vtt(legendas));
    fs.writeFileSync(path.join(C.saida, s + '.capitulos.vtt'), vtt(caps));
    const transcricao = legendas.map(l => l.texto).join('\n');
    fs.writeFileSync(path.join(C.saida, s + '.txt'), transcricao + '\n');
    const partes = [];
    if (opcoes.separar) {
      caps.forEach((c, i) => {
        const nome = `${s}-${String(i + 1).padStart(2, '0')}`;
        const arq = path.join(C.saida, nome + '.mp4');
        execFileSync(C.ffmpeg, ['-y', '-loglevel', 'error', '-ss', (c.ini / 1000).toFixed(3), '-i', mp4, '-t', ((c.fim - c.ini) / 1000).toFixed(3),
          '-c:v', 'libx264', '-preset', 'slow', '-crf', '24', '-tune', 'stillimage', '-profile:v', 'main', '-movflags', '+faststart', '-an', arq]);
        execFileSync(C.ffmpeg, ['-y', '-loglevel', 'error', '-ss', (Math.min(3, (c.fim - c.ini) / 2000)).toFixed(3), '-i', arq, '-frames:v', '1', '-q:v', '5', path.join(C.saida, nome + '.jpg')]);
        const leg = legendas.filter(l => l.fim > c.ini && l.ini < c.fim).map(l => ({ ini: Math.max(0, l.ini - c.ini), fim: Math.min(c.fim, l.fim) - c.ini, texto: l.texto }));
        fs.writeFileSync(path.join(C.saida, nome + '.vtt'), vtt(leg));
        fs.writeFileSync(path.join(C.saida, nome + '.txt'), leg.map(l => l.texto).join('\n') + '\n');
        partes.push({ arquivo: nome + '.mp4', poster: nome + '.jpg', legenda: nome + '.vtt', titulo: c.texto, duracao: Math.round((c.fim - c.ini) / 100) / 10,
          tamanho: fs.statSync(arq).size, transcricao: leg.map(l => l.texto).join('\n') });
      });
    }
    return {
      duracao: Math.round(dur / 100) / 10, tamanho: fs.statSync(mp4).size,
      capitulos: caps.map(c => ({ inicio: Math.round(c.ini / 100) / 10, titulo: c.texto })),
      transcricao, partes,
    };
  }
}

/* ---------------------------------------------------------------- trava e retrato (a demonstração é uma só) */
/** Roda fn com a trava das gravações (outra em andamento: espera a vez, até 40 min; trava velha é tomada). */
async function comTrava(fn) {
  const trava = path.join(C.trabalho, '.gravando');
  for (let espera = 0; ; espera++) {
    try { fs.writeFileSync(trava, String(process.pid), { flag: 'wx' }); break; } catch (e) {
      const idade = Date.now() - (fs.statSync(trava, { throwIfNoEntry: false }) || { mtimeMs: Date.now() }).mtimeMs;
      if (idade > 40 * 60000) { fs.rmSync(trava, { force: true }); continue; }
      if (espera === 0) console.log('Outra gravação em andamento: esperando a vez…');
      await new Promise(r => setTimeout(r, 3000));
    }
  }
  try { return await fn(); } finally { fs.rmSync(trava, { force: true }); }
}
/** Roda fn com o banco da demonstração retratado antes e devolvido ao retrato depois (senhas das contas, temporárias). */
async function comRetrato(contas, fn) {
  const retrato = path.join(C.trabalho, 'retrato_prova.sql');
  banco('retrato', retrato);
  try { return await fn(contas.length ? JSON.parse(banco('senhas', JSON.stringify(contas))) : {}); } finally { console.log('banco: ' + banco('restaurar', retrato)); }
}

module.exports = { C, banco, Gravacao, lerManifesto, gravarManifesto, hashRoteiro, hashGuia, cabecalhoGuia, guias, situacoes, ROTEIROS, MANIFESTO, sha, comTrava, comRetrato };
