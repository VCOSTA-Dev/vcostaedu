// O QUADRO DO MATERIAL (Onda 5): escreve docs/MATERIAL.md — cada guia, com o tipo de instituição, se tem vídeo atual,
// print atual e "Faça comigo", a duração e a data da gravação; o percurso da palestra; e o tamanho dos vídeos em disco.
//   node tests/videos/material.js
// "Atual" = gravado (ou capturado) na interface de hoje: vídeo com data desde a versão 1.42 (16/11/2026) e o arquivo na
// pasta; print, idem pela data do registro da captura. O que é anterior aparece como "da 1.18 (a regravar)".
const fs = require('fs');
const path = require('path');
const { C, lerManifesto, guias, cabecalhoGuia } = require('./lib');

const raiz = path.join(__dirname, '..', '..');
const DESDE = '2026-11-16';
const man = lerManifesto().videos;
const roteiros = fs.readdirSync(path.join(raiz, 'painel', 'app', 'adms', 'Roteiros')).filter((f) => f.endsWith('.json'))
  .map((f) => JSON.parse(fs.readFileSync(path.join(raiz, 'painel', 'app', 'adms', 'Roteiros', f), 'utf8')));
const facaComigo = (slug) => roteiros.filter((r) => r.guia === slug).length;
const dur = (s) => (s ? `${Math.floor(s / 60)}:${String(Math.round(s % 60)).padStart(2, '0')}` : '—');
const palestra = (() => { try { return JSON.parse(fs.readFileSync(path.join(raiz, 'docs', 'guia', 'palestra.json'), 'utf8')); } catch (e) { return {}; } })();
/* Onda 8: as pendências da regravação (a lista do retorno da Onda 7), mantidas à mão em docs/guia/material-pendencias.md */
const pendencias = (() => { try { return fs.readFileSync(path.join(raiz, 'docs', 'guia', 'material-pendencias.md'), 'utf8').trim(); } catch (e) { return ''; } })();
const nPendentes = (pendencias.match(/\| pendente \|$/gm) || []).length;
const atualPrint = (() => { try { return JSON.parse(fs.readFileSync(path.join(raiz, 'docs', 'guia', 'prints.json'), 'utf8')); } catch (e) { return {}; } })();

/* O guia sem vídeo próprio: onde ele está (o capítulo da palestra) ou por que não há */
const SEM_VIDEO = {
  'o-tipo-de-instituicao': 'no percurso da palestra (cap. 1)',
  'conhecer-com-a-demonstracao': 'no percurso da palestra (cap. 2)',
  'conectar-ao-moodle': 'no percurso da palestra (cap. 3)',
  'entrar-no-moodle-pelo-portal': 'no percurso da palestra (cap. 7)',
  'atualizar-o-sistema': 'não (a demonstração não tem atualização por aplicar)',
  'erros-do-moodle': 'não (uma tabela de consulta)',
};
const linhas = [];
let nVideo = 0, nVelho = 0, nPrint = 0, nFaca = 0;
for (const slug of guias()) {
  const c = cabecalhoGuia(slug);
  const md = fs.readFileSync(path.join(raiz, 'docs', 'guia', 'tarefas', slug + '.md'), 'utf8');
  const imgs = (md.match(/\]\(img\/[^)]+\)/g) || []).length;
  const v = man[slug];
  const videoAtual = v && (v.gravado_em || '') >= DESDE && fs.existsSync(path.join(C.saida, v.arquivo));
  const video = videoAtual ? 'sim' : (v ? 'da 1.18 (a regravar)' : (SEM_VIDEO[slug] || 'não'));
  if (videoAtual) nVideo++; else if (v) nVelho++;
  const p = atualPrint[slug];
  const print = imgs === 0 ? '—' : (p && p >= DESDE ? 'sim' : 'da 1.18 (a refazer)');
  if (p && p >= DESDE) nPrint++;
  const fc = facaComigo(slug) ? 'sim' : 'não';
  if (fc === 'sim') nFaca++;
  linhas.push(`| ${c.titulo || slug} (\`${slug}\`) | ${c.tipo === 'escola' ? 'escola' : 'os dois'} | ${video} | ${print} | ${fc} | ${videoAtual ? dur(v.duracao) : '—'} | ${videoAtual ? v.gravado_em : '—'} |`);
}
/* o tamanho em disco dos vídeos (a pasta fora do git) */
let total = 0, maior = { n: '', t: 0 }, qtd = 0;
if (fs.existsSync(C.saida)) for (const f of fs.readdirSync(C.saida)) {
  const t = fs.statSync(path.join(C.saida, f)).size; total += t;
  if (f.endsWith('.mp4')) { qtd++; if (t > maior.t) maior = { n: f, t }; }
}
const mb = (b) => (b / 1e6).toFixed(1) + ' MB';
const pal = ['superior', 'escola'].map((t) => {
  const p = palestra[t];
  return p ? `| ${t === 'escola' ? 'Escola' : 'Ensino superior'} | ${dur(p.duracao)} | ${(p.partes || []).length} | ${p.gravado_em} |` : `| ${t === 'escola' ? 'Escola' : 'Ensino superior'} | não gravado | — | — |`;
});
const texto = `# O material: vídeos, prints e "Faça comigo"

O quadro de cada guia da Ajuda (\`docs/guia/tarefas/\`). É a fonte para saber o que falta. Gerado por
\`node tests/videos/material.js\`; não edite à mão.

- **Vídeo atual:** gravado na interface de hoje (desde 16/11/2026), com o arquivo na pasta dos vídeos. "Da 1.18" é o
  vídeo da interface antiga, a regravar.
- **Print atual:** os prints do guia capturados na interface de hoje. "—": o guia não tem print.
- **Faça comigo:** o guia tem um roteiro em \`painel/app/adms/Roteiros/\` (o botão "Faça comigo" do guia).

## As pendências da regravação

O que ficou para trás desde a Onda 3, um por linha (a lista mantida em \`docs/guia/material-pendencias.md\`). Cada item
termina "feito" ou "não se aplica", com o motivo. ${nPendentes ? `**${nPendentes} pendente(s).**` : '**Nenhuma pendência.**'}

${pendencias}

## O percurso da palestra

Um vídeo contínuo e um vídeo por capítulo (\`docs/guia/palestra.json\`), gravados por \`node tests/videos/palestra.js superior|escola\`.

| Tipo | Duração | Capítulos | Gravado em |
|---|---|---|---|
${pal.join('\n')}

## Os guias

${guias().length} guias: ${nVideo} com vídeo atual, ${nVelho} com vídeo da 1.18 a regravar; ${nPrint} com print atual; ${nFaca} com "Faça comigo".

| Guia | Tipo de instituição | Vídeo atual | Print atual | Faça comigo | Duração | Gravado em |
|---|---|---|---|---|---|---|
${linhas.join('\n')}

## A galeria

Uma página só para ver todos os vídeos de uma vez (a palestra, capítulo por capítulo, e cada guia), fora do produto:
\`node tests/videos/galeria.js\` gera \`assets/videos/galeria.html\`, na pasta dos vídeos (fora do git), aberta pelo
servidor local em \`assets/videos/galeria.html\` da instalação.

## O tamanho dos vídeos

Os vídeos não vão para o git nem para o pacote do produto (\`/assets/videos/\` e \`/tests/videos/saida/\` estão no
\`.gitignore\`); o git guarda os roteiros, o manifesto (\`docs/guia/videos.json\`, \`docs/guia/palestra.json\`) e as transcrições
dentro dele. Nesta máquina, na pasta dos vídeos: **${qtd} vídeos, ${mb(total)} ao todo** (com os posters e as legendas);
o maior arquivo é \`${maior.n || '—'}\` (${mb(maior.t)}).
`;
fs.writeFileSync(path.join(raiz, 'docs', 'MATERIAL.md'), texto);
console.log(`MATERIAL.md: ${guias().length} guias, ${nVideo} vídeos atuais, ${nFaca} Faça comigo; ${qtd} vídeos em disco (${mb(total)})`);
