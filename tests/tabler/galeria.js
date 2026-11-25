/*
 * A GALERIA DAS PROVAS (Etapa 8): monta provas/tabler/index.html com cada tela de cada etapa — o "antes" (AdminLTE) e
 * o "depois" (Tabler), em 1366 e 390 px — a partir das listas que o telas.js grava (telas-depois.json) e dos prints
 * que existem na pasta. Os prints de comportamento (passos, modais) entram numa seção própria. Página única, sem
 * recurso externo; abre direto do disco.
 *
 *   node tests/tabler/galeria.js
 */
const fs = require('fs');
const path = require('path');

const RAIZ = path.resolve(__dirname, '..', '..');
const PROVAS = process.env.VCOSTAEDU_PROVAS || path.join(RAIZ, 'provas', 'tabler');
const h = (s) => String(s).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
const existe = (rel) => fs.existsSync(path.join(PROVAS, rel));

const etapas = fs.readdirSync(PROVAS).filter((d) => /^etapa\d+$/.test(d)).sort((a, b) => +a.slice(5) - +b.slice(5));
let corpo = '';
let indice = '';
let total = 0;
for (const e of etapas) {
  const lista = path.join(PROVAS, e, 'telas-depois.json');
  const telas = fs.existsSync(lista) ? JSON.parse(fs.readFileSync(lista, 'utf8')) : [];
  const nomes = new Set(telas.map((t) => t.nome));
  const extras = fs.existsSync(path.join(PROVAS, e, 'depois'))
    ? [...new Set(fs.readdirSync(path.join(PROVAS, e, 'depois')).filter((f) => f.endsWith('-1366.png')).map((f) => f.replace(/-1366\.png$/, '')))].filter((n) => !nomes.has(n)).sort()
    : [];
  indice += `<li><a href="#${e}">${h(e.replace('etapa', 'Etapa '))}</a> <small>${telas.length} telas${extras.length ? ' + ' + extras.length + ' provas de comportamento' : ''}</small></li>`;
  corpo += `<section id="${e}"><h2>${h(e.replace('etapa', 'Etapa '))}</h2>`;
  let area = null;
  for (const t of telas) {
    if (t.area !== area) { area = t.area; corpo += `<h3>${h(area || '—')}</h3>`; }
    total++;
    const img = (modo, w) => existe(`${e}/${modo}/${t.nome}-${w}.png`)
      ? `<a href="${e}/${modo}/${t.nome}-${w}.png" target="_blank" rel="noopener"><img loading="lazy" src="${e}/${modo}/${t.nome}-${w}.png" alt="${h(t.nome)} ${modo} ${w}"></a>`
      : '<span class="falta">sem print</span>';
    corpo += `<article><header><b>${h(t.nome)}</b> <code>${h(t.rota)}</code>${t.perfil ? ` <span class="perfil">${h(t.perfil)}</span>` : ''}</header>
      <div class="par"><figure><figcaption>antes · 1366</figcaption>${img('antes', 1366)}</figure><figure><figcaption>depois · 1366</figcaption>${img('depois', 1366)}</figure>
      <figure class="tel"><figcaption>antes · 390</figcaption>${img('antes', 390)}</figure><figure class="tel"><figcaption>depois · 390</figcaption>${img('depois', 390)}</figure></div></article>`;
  }
  /* prints soltos na pasta da etapa (o instalador da Etapa 2) */
  const soltos = fs.readdirSync(path.join(PROVAS, e)).filter((f) => f.endsWith('-1366.png')).map((f) => f.replace(/-1366\.png$/, '')).sort();
  if (soltos.length) {
    corpo += '<h3>Telas</h3><div class="extras">';
    for (const n of soltos) {
      total++;
      corpo += `<figure><figcaption>${h(n)}</figcaption><a href="${e}/${n}-1366.png" target="_blank" rel="noopener"><img loading="lazy" src="${e}/${n}-1366.png" alt="${h(n)}"></a></figure>`;
    }
    corpo += '</div>';
  }
  if (extras.length) {
    corpo += '<h3>Provas de comportamento e do iframe</h3><div class="extras">';
    for (const n of extras) {
      corpo += `<figure><figcaption>${h(n)}</figcaption><a href="${e}/depois/${n}-1366.png" target="_blank" rel="noopener"><img loading="lazy" src="${e}/depois/${n}-1366.png" alt="${h(n)}"></a></figure>`;
    }
    corpo += '</div>';
  }
  corpo += '</section>';
}

/* A SEGUNDA ONDA (provas/tabler/onda2/etapaN): os prints de cada prova (1366 e 390 lado a lado) e os das listas de telas
   (depois/). Os "falha-*" são de tentativas que reprovaram e foram corrigidas: não entram. */
const NOMES_ONDA2 = { 1: 'O instalador profissional', 2: 'Os perfis de acesso', 3: 'A auditoria de notas e faltas', 4: 'Conhecer e configurar por fases',
  5: 'A importação de alunos', 6: 'A API de integração', 7: 'A atualização e a versão', 8: 'O fechamento' };
const onda2 = path.join(PROVAS, 'onda2');
const etapas2 = fs.existsSync(onda2) ? fs.readdirSync(onda2).filter((d) => /^etapa\d+$/.test(d)).sort((a, b) => +a.slice(5) - +b.slice(5)) : [];
if (etapas2.length) {
  indice += '</ul><h2 style="border:0;margin-top:1rem">A segunda onda</h2><ul>';
}
for (const e of etapas2) {
  const n = +e.slice(5);
  const id = 'onda2-' + e;
  const pasta = path.join(onda2, e);
  const prints = (dir) => (fs.existsSync(dir) ? fs.readdirSync(dir) : []).filter((f) => f.endsWith('-1366.png') && !f.startsWith('falha-')).map((f) => f.replace(/-1366\.png$/, '')).sort();
  const soltos = prints(pasta);
  const telas = prints(path.join(pasta, 'depois'));
  indice += `<li><a href="#${id}">${h('Etapa ' + n + ' — ' + (NOMES_ONDA2[n] || ''))}</a> <small>${soltos.length} da prova${telas.length ? ' + ' + telas.length + ' telas' : ''}</small></li>`;
  corpo += `<section id="${id}"><h2>${h('Segunda onda, Etapa ' + n + ' — ' + (NOMES_ONDA2[n] || ''))}</h2>`;
  const par = (rel, nome) => {
    total++;
    const img = (w) => existe(`${rel}/${nome}-${w}.png`)
      ? `<a href="${rel}/${nome}-${w}.png" target="_blank" rel="noopener"><img loading="lazy" src="${rel}/${nome}-${w}.png" alt="${h(nome)} ${w}"></a>`
      : '<span class="falta">sem print</span>';
    return `<article><header><b>${h(nome)}</b></header><div class="par" style="grid-template-columns:1fr 200px"><figure><figcaption>1366</figcaption>${img(1366)}</figure><figure class="tel"><figcaption>390</figcaption>${img(390)}</figure></div></article>`;
  };
  if (soltos.length) {
    corpo += '<h3>A prova da etapa</h3>' + soltos.map((s) => par(`onda2/${e}`, s)).join('');
  }
  if (telas.length) {
    corpo += '<h3>As telas novas (a lista de telas)</h3>' + telas.map((s) => par(`onda2/${e}/depois`, s)).join('');
  }
  corpo += '</section>';
}

const html = `<!doctype html>
<html lang="pt-br"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>VCOSTAEDU — galeria das provas</title>
<style>
  body{margin:0;font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;background:#f4f6fa;color:#1d273b}
  main{max-width:1500px;margin:0 auto;padding:16px}
  h1{font-size:1.5rem;margin:.5rem 0} h2{border-bottom:2px solid #206bc4;padding-bottom:4px;margin-top:2.5rem} h3{color:#4b5563;margin:1.5rem 0 .5rem}
  article{background:#fff;border:1px solid #e3e8ef;border-radius:8px;padding:10px;margin-bottom:12px}
  article header{margin-bottom:8px} code{background:#eef2f7;padding:1px 5px;border-radius:4px;font-size:.85em}
  .perfil{font-size:.8em;color:#206bc4} .par{display:grid;grid-template-columns:1fr 1fr 160px 160px;gap:8px;align-items:start}
  figure{margin:0} figcaption{font-size:.75rem;color:#6b7280;margin-bottom:2px}
  img{width:100%;border:1px solid #d6dde6;border-radius:4px;display:block;background:#fff}
  .falta{display:block;font-size:.8rem;color:#9ca3af;padding:20px 0;text-align:center;border:1px dashed #d6dde6;border-radius:4px}
  .extras{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:10px}
  @media (max-width:900px){.par{grid-template-columns:1fr 1fr}}
</style></head><body><main>
<h1>VCOSTAEDU — as provas, tela por tela</h1>
<p>${total} telas. Na primeira onda (Tabler), o "antes" é a cópia AdminLTE (commit da Etapa 2) e o "depois" é o Tabler; na segunda onda, cada prova em 1366 e 390 px. Clique num print para abrir em tamanho real.</p>
<ul>${indice}</ul>
${corpo}
</main></body></html>`;
fs.writeFileSync(path.join(PROVAS, 'index.html'), html);
console.log(`galeria: ${total} telas em ${etapas.length} etapas → ${path.join(PROVAS, 'index.html')}`);
