// Ações VISÍVEIS no navegador (Playwright), comuns à jornada de validação (tests/jornada) e aos vídeos dos guias
// (tests/videos): destacar o elemento antes de agir, mover um cursor desenhado na página (o Playwright não grava o
// cursor do sistema), clicar com um efeito, digitar devagar e ler a tela. Nada de dado real aqui.

/** Cursor desenhado na página: segue o mouse do Playwright e mostra um círculo a cada clique. */
const CURSOR = `(() => {
  if (window.__vcostaeduCursor) return; window.__vcostaeduCursor = true;
  const montar = () => {
    if (!document.body || document.getElementById('vcostaeduCursor')) return;
    const c = document.createElement('div');
    c.id = 'vcostaeduCursor';
    c.innerHTML = '<svg width="22" height="30" viewBox="0 0 22 30"><path d="M1 1 L1 24 L7 18 L11 28 L15 26 L11 16 L19 16 Z" fill="#111" stroke="#fff" stroke-width="1.6" stroke-linejoin="round"/></svg>';
    c.style.cssText = 'position:fixed;left:0;top:0;width:22px;height:30px;z-index:2147483647;pointer-events:none;transform:translate(-200px,-200px);transition:transform .02s linear';
    document.body.appendChild(c);
    const pos = JSON.parse(sessionStorage.getItem('vcostaedu.cursor') || '[640,360]');
    c.style.transform = 'translate(' + pos[0] + 'px,' + pos[1] + 'px)';
  };
  document.addEventListener('mousemove', e => {
    const c = document.getElementById('vcostaeduCursor'); if (!c) return;
    c.style.transform = 'translate(' + e.clientX + 'px,' + e.clientY + 'px)';
    try { sessionStorage.setItem('vcostaedu.cursor', JSON.stringify([e.clientX, e.clientY])); } catch (x) {}
  }, true);
  document.addEventListener('mousedown', e => {
    const r = document.createElement('div');
    r.style.cssText = 'position:fixed;z-index:2147483646;pointer-events:none;border:3px solid #ff1744;border-radius:50%;width:10px;height:10px;left:' + (e.clientX - 5) + 'px;top:' + (e.clientY - 5) + 'px;opacity:1;transition:all .45s ease-out';
    document.body.appendChild(r);
    requestAnimationFrame(() => { r.style.width = r.style.height = '44px'; r.style.left = (e.clientX - 22) + 'px'; r.style.top = (e.clientY - 22) + 'px'; r.style.opacity = '0'; });
    setTimeout(() => r.remove(), 600);
  }, true);
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', montar); else montar();
})();`;

/**
 * Cria as ações visíveis.
 * opcoes: base (URL base do sistema), destaqueMs (800), atrasoTecla (45), cursor (mover o mouse até o alvo antes de
 * clicar), antesDeDestacar(page, caixa) (ex.: o painel da jornada sai da frente do alvo).
 */
function criar(opcoes = {}) {
  const o = { base: '', destaqueMs: 800, atrasoTecla: 45, cursor: false, antesDeDestacar: null, ...opcoes };
  const loc = (page, alvo) => (typeof alvo === 'string' ? page.locator(alvo).first() : alvo);

  async function moverPara(page, caixa) {
    if (!o.cursor || !caixa) return;
    await page.mouse.move(caixa.x + caixa.width / 2, caixa.y + Math.min(caixa.height / 2, 18), { steps: 18 });
  }
  async function destacar(page, alvo) {
    const l = loc(page, alvo);
    await l.scrollIntoViewIfNeeded({ timeout: 5000 }).catch(() => {}); // Onda 5: sem espera morta por um alvo fora da tela
    const caixa = await l.boundingBox({ timeout: 5000 }).catch(() => null);
    if (caixa && o.antesDeDestacar) await o.antesDeDestacar(page, caixa);
    await moverPara(page, caixa);
    // o estilo de antes fica aqui (no Node), não no elemento: nada sobra no HTML (ex.: o editor de modelo salva o HTML)
    const antes = await l.evaluate(el => { const a = { s: el.getAttribute('style') }; el.style.outline = '4px solid #ff1744'; el.style.outlineOffset = '2px'; return a; }, undefined, { timeout: 5000 }).catch(() => null);
    await page.waitForTimeout(o.destaqueMs);
    if (antes) await l.evaluate((el, s) => { if (s === null) el.removeAttribute('style'); else el.setAttribute('style', s); }, antes.s).catch(() => {});
    return l;
  }
  async function clicar(page, alvo, opc = {}) { const l = await destacar(page, alvo); await l.click(opc); }
  async function clicarENavegar(page, alvo) {
    const l = await destacar(page, alvo);
    await Promise.all([page.waitForNavigation({ waitUntil: 'load', timeout: 60000 }).catch(() => {}), l.click()]);
    await page.waitForLoadState('networkidle').catch(() => {});
  }
  async function digitar(page, alvo, texto) {
    const l = await destacar(page, alvo);
    await l.fill('');
    if (l.pressSequentially) await l.pressSequentially(String(texto), { delay: o.atrasoTecla }); else await l.type(String(texto), { delay: o.atrasoTecla }); // ElementHandle
  }
  async function escolher(page, alvo, valor) { const l = await destacar(page, alvo); await l.selectOption(valor); }
  async function ir(page, rota) { await page.goto(o.base + rota, { waitUntil: 'load' }); await page.waitForLoadState('networkidle').catch(() => {}); }
  const texto = async (page, sel) => ((await page.$(sel)) ? (await page.textContent(sel)) : '').replace(/\s+/g, ' ').trim();
  const alerta = async page => ((await page.$$eval('.alert', a => a.filter(x => !x.hidden && x.getClientRects().length > 0).map(x => x.textContent).join(' | ')).catch(() => '')) || '').replace(/\s+/g, ' ').replace(/×/g, '').trim();

  return { moverPara, destacar, clicar, clicarENavegar, digitar, escolher, ir, texto, alerta };
}

module.exports = { CURSOR, criar };
