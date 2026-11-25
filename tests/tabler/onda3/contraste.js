/*
 * O CONTRASTE do texto na tela (Onda 3, Etapa 10): para cada elemento visível com texto próprio, a cor do texto contra
 * o fundo REAL (as camadas de fundo dos ancestrais compostas, com a transparência), pela fórmula do WCAG. O mínimo:
 * 4,5:1; texto grande (24 px, ou 18,66 px em negrito), 3:1. Fora da conta, como o WCAG manda: controle desabilitado,
 * texto escondido, e o que tem imagem de fundo (não dá para saber o fundo).
 * contraste(page) => [{ onde, texto, razao, minimo, cor, fundo }] (só os abaixo do mínimo, sem repetir)
 */
async function contraste(page) {
  return page.evaluate(() => {
    /* rgb()/rgba() (0–255) e color(srgb r g b / a) (0–1), como o navegador devolve */
    const rgba = (s) => {
      s = s || '';
      const m = s.match(/[\d.]+(?:e-?\d+)?/g);
      if (!m) { return [0, 0, 0, 0]; }
      if (/^color\(srgb/.test(s)) { return [m[0] * 255, m[1] * 255, m[2] * 255, m[3] === undefined ? 1 : +m[3]]; }
      if (/^oklab\(/.test(s)) {
        const n = s.match(/-?[\d.]+(?:e-?\d+)?/g).map(Number);
        const [Lk, a, b] = n; const al = n[3] === undefined ? 1 : n[3];
        const l_ = (Lk + 0.3963377774 * a + 0.2158037573 * b) ** 3, m_ = (Lk - 0.1055613458 * a - 0.0638541728 * b) ** 3, s_ = (Lk - 0.0894841775 * a - 1.2914855480 * b) ** 3;
        const lin = [4.0767416621 * l_ - 3.3077115913 * m_ + 0.2309699292 * s_, -1.2684380046 * l_ + 2.6097574011 * m_ - 0.3413193965 * s_, -0.0041960863 * l_ - 0.7034186147 * m_ + 1.7076147010 * s_];
        const g = lin.map((v) => { v = Math.max(0, Math.min(1, v)); return 255 * (v <= 0.0031308 ? 12.92 * v : 1.055 * v ** (1 / 2.4) - 0.055); });
        return [g[0], g[1], g[2], al];
      }
      return [+m[0], +m[1], +m[2], m[3] === undefined ? 1 : +m[3]];
    };
    const sobre = (cima, baixo) => { const a = cima[3]; return [0, 1, 2].map((i) => cima[i] * a + baixo[i] * (1 - a)).concat(1); };
    const lum = (c) => { const v = c.slice(0, 3).map((x) => { x /= 255; return x <= 0.03928 ? x / 12.92 : ((x + 0.055) / 1.055) ** 2.4; }); return 0.2126 * v[0] + 0.7152 * v[1] + 0.0722 * v[2]; };
    const razao = (a, b) => { const x = lum(a), y = lum(b); return (Math.max(x, y) + 0.05) / (Math.min(x, y) + 0.05); };
    const raiz = rgba(getComputedStyle(document.documentElement).backgroundColor);
    const base = raiz[3] > 0 ? sobre(raiz, [255, 255, 255, 1]) : [255, 255, 255, 1];
    function fundo(el) {
      const pilha = [];
      for (let e = el; e && e.nodeType === 1; e = e.parentElement) {
        const cs = getComputedStyle(e);
        if (cs.backgroundImage && cs.backgroundImage !== 'none' && !/gradient/.test(cs.backgroundImage)) { return null; }
        const c = rgba(cs.backgroundColor);
        if (c[3] > 0) { pilha.push(c); if (c[3] >= 1) { break; } }
      }
      let cor = base;
      for (let i = pilha.length - 1; i >= 0; i--) { cor = sobre(pilha[i], cor); }
      return cor;
    }
    const visivel = (el) => { const r = el.getBoundingClientRect(); if (r.width < 1 || r.height < 1) { return false; } const cs = getComputedStyle(el); return cs.visibility !== 'hidden' && +cs.opacity > 0.1 && !el.closest('[hidden], [aria-hidden="true"], .d-none, .visually-hidden, .sr-only'); };
    const achados = new Map();
    for (const el of document.querySelectorAll('body *')) {
      if (['SCRIPT', 'STYLE', 'NOSCRIPT', 'SVG', 'PATH', 'OPTION', 'TEMPLATE'].includes(el.tagName)) { continue; }
      const texto = [...el.childNodes].filter((n) => n.nodeType === 3).map((n) => n.textContent).join('').replace(/\s+/g, ' ').trim();
      if (texto.length < 2 || !visivel(el)) { continue; }
      if (el.closest(':disabled, .disabled, [aria-disabled="true"], .acao-desabilitada, .vc-progresso[hidden]')) { continue; }
      const cs = getComputedStyle(el);
      const bg = fundo(el);
      if (!bg) { continue; }
      const fg = sobre(rgba(cs.color), bg);
      const px = parseFloat(cs.fontSize); const negrito = +cs.fontWeight >= 700;
      const minimo = px >= 24 || (px >= 18.66 && negrito) ? 3 : 4.5;
      const r = razao(fg, bg);
      if (r + 0.01 < minimo) {
        const onde = el.tagName.toLowerCase() + (el.id ? '#' + el.id : '') + [...el.classList].slice(0, 3).map((c) => '.' + c).join('');
        const chave = onde + '|' + cs.color + '|' + bg.slice(0, 3).map(Math.round).join(',');
        if (!achados.has(chave)) {
          achados.set(chave, { onde, texto: texto.slice(0, 40), razao: Math.round(r * 100) / 100, minimo, cor: cs.color, fundo: 'rgb(' + bg.slice(0, 3).map(Math.round).join(', ') + ')' });
        }
      }
    }
    return [...achados.values()];
  });
}
module.exports = { contraste };
