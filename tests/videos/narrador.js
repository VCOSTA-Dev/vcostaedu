// O NARRADOR DOS VÍDEOS (o padrão do "Faça comigo") e o que a gravação confere — usado por lib.js (a Gravacao).
//
// - A janela de narração: nas telas em que o "Faça comigo" do painel não roda (o instalador, o portal, o Moodle, o
//   portal de matrícula de exemplo), a gravação mostra uma janela com a MESMA cara (o título, o texto e o "Agora: …"),
//   e depois de cada ação o aviso do que foi feito. No painel, os roteiros do "Faça comigo" (painel/app/adms/Roteiros)
//   rodam no próprio motor do produto (Gravacao.facaComigo), com o gravador fazendo, à vista, o que a pessoa faria.
// - A máscara: nenhum endereço local, porta, caminho de servidor ou segredo aparece (o texto da tela é trocado por
//   "•••"; o campo, só desenhado com bolinhas — o valor continua o mesmo para o formulário).
// - A prova: erro no console ou aviso do PHP numa tela gravada recusa o vídeo.
// - O registro do texto de cada tela (depois da máscara), para a varredura (varrer.js).

/** A máscara (roda no início de cada página; SEG: os segredos da gravação — o endereço e a chave do Moodle de teste). */
function mascaraNaPagina(SEG) {
  if (window.__vcMascara) return; window.__vcMascara = true;
  const esc = (s) => s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  const padroes = [/\blocalhost(:\d+)?/gi, /\b127\.0\.0\.1(:\d+)?/g, /\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}(:\d+)?\b/g,
    /\b[A-Za-z]:[\\/][^\s"'<>]*/g, /\/(home|var|srv|opt|mnt)\/[^\s"'<>]*/g, /:\d{4,5}\b/g,
    /\bvce_[0-9A-Za-z]{6,}/g, /(?<=termina em )[^\s)]+/g]
    .concat((SEG || []).filter((s) => s && s.length >= 4).map((s) => new RegExp(esc(s), 'gi')));
  const sensivel = (v) => padroes.some((re) => { re.lastIndex = 0; return re.test(v); });
  const trocar = (v) => { let r = v; for (const re of padroes) { re.lastIndex = 0; r = r.replace(re, '•••'); } return r; };
  const campo = (i) => { i.dataset.vcMascarar = '1'; i.style.webkitTextSecurity = 'disc'; };
  const tratar = (raiz) => {
    const w = document.createTreeWalker(raiz, NodeFilter.SHOW_TEXT);
    for (let n = w.nextNode(); n; n = w.nextNode()) {
      const p = n.parentElement;
      if (!p || /^(SCRIPT|STYLE|TEXTAREA|NOSCRIPT)$/.test(p.tagName) || p.closest('#vcostaeduVisor')) continue;
      if (sensivel(n.nodeValue)) n.nodeValue = trocar(n.nodeValue);
    }
    document.querySelectorAll('input, textarea').forEach((i) => {
      if (i.placeholder && sensivel(i.placeholder)) i.placeholder = trocar(i.placeholder); // também no campo de senha ("termina em …")
      if (/^(hidden|password|checkbox|radio|submit|button)$/i.test(i.type || '')) return;
      if (i.dataset.vcMascarar === '1' || i.matches('[data-vc-gravar-mascarar]') || sensivel(i.value || '')) campo(i);
    });
    /* as senhas das contas da demonstração (a coluna Senha do "Conheça o sistema"): fictícias, mas senha não aparece */
    document.querySelectorAll('#tabelaPerfisDemo td:nth-child(3) code').forEach((c) => { if (c.textContent !== '••••••••') c.textContent = '••••••••'; });
  };
  /* desde o primeiro nó: o observador vale DURANTE o carregamento (a página que ainda chega não mostra nada sem máscara) */
  const raiz = () => document.body || document.documentElement;
  new MutationObserver(() => { if (raiz()) tratar(raiz()); }).observe(document, { childList: true, subtree: true, characterData: true });
  document.addEventListener('input', (e) => { const i = e.target; if (i && i.matches && i.matches('input, textarea') && sensivel(i.value || '')) campo(i); }, true);
  setInterval(() => { if (raiz()) tratar(raiz()); }, 250); // o valor posto por script depois do carregamento não dispara o observador
  if (raiz()) tratar(raiz());
}

/** A janela de narração da gravação (fora do painel, ou quando o roteiro não é um "Faça comigo"): a mesma cara. */
function narradorNaPagina() {
  if (window.__vcNarrador) return; window.__vcNarrador = true;
  const CSS = '#vc-narra{position:fixed;right:16px;bottom:16px;width:420px;max-width:calc(100% - 32px);z-index:2147483600;background:#fff;color:#1d273b;border:1px solid #e3e7ee;border-left:4px solid #0FA3A3;border-radius:8px;box-shadow:0 12px 32px rgba(15,42,67,.28);padding:14px 16px;font:400 17px/1.5 "Inter",system-ui,-apple-system,"Segoe UI",Roboto,sans-serif}'
    + '#vc-narra.vc-sd{bottom:auto;top:16px}#vc-narra .t{font-weight:600;font-size:22px;line-height:1.25;margin-bottom:4px}#vc-narra .g{font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:.03em;margin-bottom:6px}'
    + '#vc-narra .a{margin-top:10px;padding:6px 10px;border-radius:6px;background:rgba(15,42,67,.08);font-size:15px}#vc-narra .f{margin-top:10px;padding:6px 10px;border-radius:6px;background:rgba(15,163,163,.16);font-size:15px}';
  const md = (t) => String(t || '').replace(/[&<>]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' }[c])).replace(/\*\*(.+?)\*\*/g, '<b>$1</b>');
  const desenhar = () => {
    if (!document.body) return;
    if (!document.getElementById('vc-narra-css')) { const s = document.createElement('style'); s.id = 'vc-narra-css'; s.textContent = CSS; (document.head || document.body).appendChild(s); }
    let d = null; try { d = JSON.parse(sessionStorage.getItem('vc-narra') || 'null'); } catch (e) { d = window.__vcNarraMem || null; }
    let c = document.getElementById('vc-narra');
    if (!d || document.getElementById('vc-tour')) { if (c) c.remove(); return; }
    if (!c) { c = document.createElement('div'); c.id = 'vc-narra'; c.setAttribute('role', 'status'); document.body.appendChild(c); }
    c.className = d.cima ? 'vc-sd' : '';
    c.innerHTML = (d.guia ? '<div class="g">' + md(d.guia) + '</div>' : '') + '<div class="t">' + md(d.t) + '</div>' + (d.x ? '<div>' + md(d.x) + '</div>' : '')
      + (d.aviso ? '<div class="a"><b>Agora:</b> ' + md(d.aviso) + '</div>' : '') + (d.feito ? '<div class="f">✓ ' + md(d.feito) + '</div>' : '');
  };
  window.__vcNarrar = (d) => { window.__vcNarraMem = d; try { if (d) sessionStorage.setItem('vc-narra', JSON.stringify(d)); else sessionStorage.removeItem('vc-narra'); } catch (e) { /* sem storage */ } desenhar(); };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', desenhar); else desenhar();
  const vigiarTour = () => new MutationObserver(() => { if (document.getElementById('vc-tour') && document.getElementById('vc-narra')) document.getElementById('vc-narra').remove(); })
    .observe(document.documentElement, { childList: true, subtree: true });
  if (document.documentElement) vigiarTour(); else document.addEventListener('DOMContentLoaded', vigiarTour);
}

/** O que a prova recusa numa tela gravada: aviso do PHP no HTML. */
const AVISO_PHP = /<b>(Warning|Notice|Deprecated|Fatal error|Parse error)<\/b>|\b(Warning|Notice|Deprecated|Fatal error|Parse error): [^<]{0,160} on line \d+/;

/** Ligado à página: junta os erros do console e da página. */
function vigiar(page, erros) {
  page.on('console', (m) => {
    if (m.type() !== 'error') return;
    const t = m.text();
    if (/favicon/i.test(t)) return;
    erros.push('console em ' + (page.url() || '').replace(/^https?:\/\/[^/]+/, '') + ': ' + t.slice(0, 200));
  });
  page.on('pageerror', (e) => erros.push('página: ' + String(e.message || e).slice(0, 200)));
}

/** O texto da tela (depois da máscara) e os campos à vista, para o registro da varredura. */
async function textoVisivel(page) {
  await page.waitForLoadState('load').catch(() => {}); // a página inteira, já com a máscara
  return page.evaluate(() => {
    const campos = Array.from(document.querySelectorAll('input, textarea'))
      .filter((i) => !/^(hidden|password|checkbox|radio)$/i.test(i.type || '') && i.getClientRects().length && i.style.webkitTextSecurity !== 'disc')
      .map((i) => i.value).filter(Boolean);
    return (document.title || '') + '\n' + (document.body ? document.body.innerText : '') + '\n' + campos.join('\n');
  }).catch(() => '');
}

module.exports = { mascaraNaPagina, narradorNaPagina, AVISO_PHP, vigiar, textoVisivel };
