/*
 * A medida das marcas do rodapé (Onda 3, Etapa 7): em px, a altura de maiúscula do texto do rodapé e a altura das
 * LETRAS de cada marca, e a distância do pé das letras de cada marca à linha de base do texto.
 * As letras retas ocupam 377 das 618 unidades do SVG do VCOSTAEDU (o pé delas 6 unidades acima do pé da imagem) e 245
 * das 253 linhas do PNG do VCOSTA (4 acima do pé). Exporta medir(page) => {fonte, cap, edu, vcosta, baseEdu, baseVcosta}.
 */
async function medir(p) {
  return p.evaluate(() => {
    const leg = [...document.querySelectorAll('.vc-legal')].find((e) => e.getBoundingClientRect().height > 0);
    if (!leg) { return null; }
    const cs = getComputedStyle(leg);
    const cv = document.createElement('canvas').getContext('2d');
    cv.font = `${cs.fontWeight} ${cs.fontSize} ${cs.fontFamily}`;
    const cap = cv.measureText('H').actualBoundingBoxAscent;
    /* a linha de base do texto: um marcador vazio alinhado à base, no mesmo bloco */
    const m = document.createElement('span');
    m.style.cssText = 'display:inline-block;width:0;height:0;vertical-align:baseline';
    leg.querySelector('span').appendChild(m);
    const base = m.getBoundingClientRect().bottom;
    m.remove();
    const vis = (sel) => [...leg.querySelectorAll(sel)].find((i) => i.getBoundingClientRect().height > 0);
    const e = vis('.vc-legal-marca-edu'); const v = vis('.vc-legal-marca-vcosta');
    const re = e.getBoundingClientRect(); const rv = v.getBoundingClientRect();
    const r2 = (x) => Math.round(x * 100) / 100;
    return { fonte: cs.fontSize, cap: r2(cap), edu: r2(re.height * 377 / 618), vcosta: r2(rv.height * 245 / 253),
      baseEdu: r2(re.bottom - re.height * 6 / 618 - base), baseVcosta: r2(rv.bottom - rv.height * 4 / 253 - base),
      capelo: r2(base - re.top), arquivoEdu: e.getAttribute('src').split('/').pop(), arquivoVcosta: v.getAttribute('src').split('/').pop() };
  });
}
module.exports = { medir };
