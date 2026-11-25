// A GALERIA (Onda 5): uma página simples, FORA do produto, para assistir a todo o material em sequência.
//   node tests/videos/galeria.js
// Escreve `galeria.html` na pasta dos vídeos (assets/videos/, fora do git), ao lado de guias/: primeiro o percurso da
// palestra (o vídeo contínuo e os capítulos, nos dois tipos de instituição), depois os vídeos dos guias da Ajuda, cada um
// com a duração, a data e a transcrição. Abre direto do disco ou pelo servidor local.
const fs = require('fs');
const path = require('path');
const { C, lerManifesto, cabecalhoGuia } = require('./lib');

const raiz = path.join(__dirname, '..', '..');
const esc = (s) => String(s == null ? '' : s).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
const dur = (s) => `${Math.floor(s / 60)}:${String(Math.round(s % 60)).padStart(2, '0')}`;
const pasta = path.relative(path.dirname(C.saida), C.saida).split(path.sep).join('/'); // "guias"
const existe = (a) => fs.existsSync(path.join(C.saida, a));
const video = (v, titulo, extra = '') => `<article><h3>${esc(titulo)}</h3><p class="m">${dur(v.duracao || 0)} · ${(v.tamanho / 1e6 || 0).toFixed(1)} MB${v.gravado_em ? ' · gravado em ' + esc(v.gravado_em) : ''}${extra}</p>
  ${existe(v.arquivo) ? `<video controls preload="none" poster="${pasta}/${esc(v.poster)}" src="${pasta}/${esc(v.arquivo)}">${v.legenda ? `<track kind="captions" srclang="pt-BR" label="Português" src="${pasta}/${esc(v.legenda)}">` : ''}</video>` : '<p class="falta">o arquivo não está nesta máquina</p>'}
  ${v.transcricao ? `<details><summary>Transcrição</summary><pre>${esc(v.transcricao)}</pre></details>` : ''}</article>`;

const palestra = (() => { try { return JSON.parse(fs.readFileSync(path.join(raiz, 'docs', 'guia', 'palestra.json'), 'utf8')); } catch (e) { return {}; } })();
const guias = lerManifesto().videos;
let corpo = '<h2>O percurso da palestra</h2>';
for (const tipo of ['superior', 'escola']) {
  const p = palestra[tipo];
  if (!p) { corpo += `<p class="falta">${tipo === 'escola' ? 'Escola' : 'Ensino superior'}: ainda não gravado.</p>`; continue; }
  corpo += `<section><h2 class="t2">${esc(p.titulo)}</h2>` + video(p, 'O vídeo contínuo', ` · ${(p.partes || []).length} capítulos`);
  for (const parte of p.partes || []) corpo += video({ ...parte, gravado_em: p.gravado_em }, parte.titulo);
  corpo += '</section>';
}
corpo += '<h2>Os vídeos dos guias da Ajuda</h2>';
const slugs = Object.keys(guias).sort();
const atuais = slugs.filter((s) => (guias[s].gravado_em || '') >= '2026-11-16');
corpo += `<p class="m">${atuais.length} gravados nesta versão; ${slugs.length - atuais.length} da versão anterior (a regravar).</p>`;
for (const s of atuais) {
  let titulo = s; try { titulo = cabecalhoGuia(s).titulo || s; } catch (e) { /* sem guia */ }
  corpo += video(guias[s], titulo);
}
const html = `<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Galeria do material</title><style>
:root{--f:#f5f7fb;--t:#1d273b;--s:#6b7280;--b:#e3e7ee;--c:#fff}
body{margin:0;background:var(--f);color:var(--t);font:16px/1.5 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif}
main{max-width:960px;margin:0 auto;padding:24px 16px}h1{margin:0 0 4px}h2{margin:32px 0 8px}.t2{font-size:1.15rem;color:#145c9e}
article{background:var(--c);border:1px solid var(--b);border-radius:8px;padding:12px 16px;margin:12px 0}article h3{margin:0 0 2px;font-size:1.05rem}
video{width:100%;max-width:100%;border-radius:6px;background:#000;margin-top:6px}.m{color:var(--s);font-size:.9rem;margin:0}.falta{color:#b45309}
pre{white-space:pre-wrap;font-size:.9rem;background:var(--f);padding:8px;border-radius:6px}
</style></head><body><main><h1>Galeria do material</h1><p class="m">Só a demonstração fictícia. Gerada em ${new Date().toISOString().slice(0, 16).replace('T', ' ')}.</p>${corpo}</main></body></html>\n`;
const saida = path.join(path.dirname(C.saida), 'galeria.html');
fs.writeFileSync(saida, html);
console.log('galeria: ' + path.relative(raiz, saida).split(path.sep).join('/'));
