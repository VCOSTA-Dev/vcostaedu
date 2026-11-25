// Conferência dos vídeos dos guias (1.18.0): lista os guias sem roteiro, sem vídeo e desatualizados (o roteiro mudou, o
// guia foi editado depois da gravação ou o arquivo não está na pasta). Não abre navegador nem banco.
//   node conferir.js            tabela; sai com 1 se houver guia sem vídeo ou desatualizado
//   node conferir.js --json     a mesma lista em JSON
const fs = require('fs');
const path = require('path');
const L = require('./lib');

const s = L.situacoes();
const m = L.lerManifesto().videos;
const sobrando = Object.keys(m).filter(k => !s.some(x => x.slug === k)); // vídeo de guia que não existe mais
if (process.argv.includes('--json')) { console.log(JSON.stringify({ guias: s, sobrando }, null, 2)); }
else {
  const conta = t => s.filter(x => x.situacao === t).length;
  let total = 0;
  for (const x of s) {
    const e = m[x.slug];
    if (e) total += e.tamanho || 0;
    if (x.situacao !== 'ok') console.log(`${x.situacao.padEnd(14)} ${x.slug}${x.motivo ? ' — ' + x.motivo : ''}`);
  }
  for (const k of sobrando) console.log(`sobrando       ${k} — o guia não existe mais`);
  const naPasta = fs.existsSync(L.C.saida) ? fs.readdirSync(L.C.saida).filter(f => !f.startsWith('.')).reduce((a, f) => a + fs.statSync(path.join(L.C.saida, f)).size, 0) : 0;
  console.log(`\n${s.length} guias: ${conta('ok')} com vídeo em dia, ${conta('sem vídeo')} sem vídeo, ${conta('desatualizado')} desatualizado(s), ${conta('sem roteiro')} sem roteiro.`);
  console.log(`Manifesto: ${(total / 1048576).toFixed(1)} MB em vídeos; pasta ${L.C.saida}: ${(naPasta / 1048576).toFixed(1)} MB (com posters e legendas).`);
}
if (s.some(x => x.situacao !== 'ok') || sobrando.length) process.exitCode = 1;
