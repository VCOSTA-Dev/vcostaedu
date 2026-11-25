// A VARREDURA DO MATERIAL (Onda 5): em todo vídeo gravado e em todo print, nada que não seja da demonstração.
//   node tests/videos/varrer.js [--sabotar]
//
// O que é lido:
//   - o registro do texto de cada tela gravada (`<slug>.tela.txt`, na pasta de trabalho: o texto à vista e os campos,
//     DEPOIS da máscara da gravação — é o que o vídeo mostra), as legendas (.vtt) e as transcrições (.txt) dos vídeos;
//   - o texto dos prints dos guias, pelo registro que a captura grava ao lado (`<print>.tela.txt`), quando existe.
// O que reprova: endereço local (localhost, 127.0.0.1, IP), porta, caminho de servidor, a chave e o endereço do Moodle
// de teste (lidos do ambiente, nunca escritos), chave da API, e-mail fora de exemplo.invalid (e dos seus subdomínios), o nome da
// pasta ou do banco do responsável — e o jargão na tela (o mesmo critério das provas das telas).
// --sabotar: acrescenta um registro com "localhost:8080" e confere que a varredura reprova (e o apaga).
const fs = require('fs');
const path = require('path');
const { C } = require('./lib');

const segredos = [process.env.VCOSTAEDU_MOODLE_URL, process.env.VCOSTAEDU_MOODLE_CHAVE].map((s) => (s || '').trim()).filter((s) => s.length >= 6);
try { const h = new URL(process.env.VCOSTAEDU_MOODLE_URL || '').host; if (h) segredos.push(h); } catch (e) { /* sem endereço */ }
const REGRAS = [
  [/\b(?:[a-z]+\.demo|\d{6})\s+(?:no portal\s+)?(?!•)[A-Za-z0-9]{12,}\b/, 'senha da demonstração à mostra'], // a coluna Senha do "Conheça o sistema"
  [/\blocalhost\b/i, 'endereço local'],
  [/\b127\.0\.0\.1\b/, 'endereço local'],
  [/\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b/, 'endereço IP'],
  [/:\d{4,5}\b(?![.,]\d)/, 'porta'],
  [/\b[A-Za-z]:[\\/](xampp|Users|Windows|htdocs|wamp|inetpub)/i, 'caminho de servidor'],
  [/\/(home|var\/www|srv|opt)\/[a-z]/i, 'caminho de servidor'],
  [/\bvce_[0-9A-Za-z]{6,}/, 'chave da API'],
  [/[A-Za-z0-9._%+-]+@(?!(?:[A-Za-z0-9-]+\.)?exemplo\.invalid\b)[A-Za-z0-9.-]+\.[A-Za-z]{2,}/, 'e-mail fora da demonstração'],
];
/* os nomes da pasta e do banco de quem cuida da instalação: do ambiente (VCOSTAEDU_VARRER_NOMES, separados por vírgula),
   nunca escritos aqui */
for (const n of (process.env.VCOSTAEDU_VARRER_NOMES || '').split(',').map((x) => x.trim()).filter((x) => x.length >= 3)) {
  REGRAS.push([new RegExp(n.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'i'), 'nome de pasta ou de banco']);
}
/* o jargão: o mesmo critério das provas das telas (tests/tabler/lib.js) */
process.env.VCOSTAEDU_BASE = process.env.VCOSTAEDU_BASE || C.base;
const { jargao } = require('../tabler/lib');

function arquivos() {
  const l = [];
  for (const d of [C.trabalho, path.join(C.trabalho, 'prints'), C.saida, path.join(path.dirname(C.saida), 'palestra')]) {
    if (!fs.existsSync(d)) continue;
    for (const f of fs.readdirSync(d)) {
      if (/\.tela\.txt$|\.vtt$|\.txt$/.test(f) && !/^(lista|falha-)/.test(f)) l.push(path.join(d, f));
    }
  }
  const img = path.join(__dirname, '..', '..', 'docs', 'guia', 'img');
  if (fs.existsSync(img)) for (const f of fs.readdirSync(img)) if (/\.tela\.txt$/.test(f)) l.push(path.join(img, f));
  return l;
}

function varrer(lista) {
  const achados = [];
  for (const arq of lista) {
    const t = fs.readFileSync(arq, 'utf8');
    for (const [re, nome] of REGRAS) {
      const m = t.match(re);
      if (m) achados.push(`${path.basename(arq)}: ${nome} ("${t.slice(Math.max(0, m.index - 20), m.index + 30).replace(/\s+/g, ' ')}")`);
    }
    for (const s of segredos) if (t.toLowerCase().includes(s.toLowerCase())) achados.push(`${path.basename(arq)}: o endereço ou a chave do Moodle de teste`);
    for (const j of jargao(t)) achados.push(`${path.basename(arq)}: jargão — ${j}`);
  }
  return achados;
}

const lista = arquivos();
let achados = varrer(lista);
let falhou = achados.length > 0;
console.log(`varredura: ${lista.length} registro(s) de tela, legenda e transcrição`);
achados.slice(0, 60).forEach((a) => console.log('  [FALHA] ' + a));
if (process.argv.includes('--sabotar')) {
  const sab = path.join(C.trabalho, 'sabotagem.tela.txt');
  fs.writeFileSync(sab, 'Conectado ao servidor localhost:8080 com a chave vce_abcdef123456');
  const pegou = varrer([sab]).length >= 2;
  fs.rmSync(sab, { force: true });
  console.log(`  [${pegou ? 'OK' : 'FALHA'}]    sabotagem (um registro com endereço local, porta e chave): a varredura reprova; o registro foi apagado`);
  if (!pegou) falhou = true;
}
console.log(`varrer: ${falhou ? 'FALHA' : 'OK'} (${achados.length} achado(s))`);
process.exit(falhou ? 1 : 0);
