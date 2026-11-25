/*
 * PROVA DA ONDA 6, ETAPA 1 — uma conexão com o banco por requisição.
 *   1. as dez telas de uso comum (painel e portal, três perfis) abrem, cada uma, UMA conexão ao banco (o contador do
 *      próprio servidor de banco: medir_telas.js);
 *   2. sabotagem: o AdmsConn do painel volta a abrir uma conexão por consulta — a prova reprova as telas do painel; o
 *      arquivo volta igual (o sha256 confere).
 * A transação, a auditoria e a importação têm as provas próprias (onda2/etapa3, a importação), que a bateria reduzida
 * desta etapa roda junto.
 *
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE; a demonstração do ensino superior montada (o ciclo).
 */
const { execFileSync } = require('child_process');
const crypto = require('crypto');
const fs = require('fs');
const path = require('path');

const raiz = path.join(__dirname, '..', '..', '..');
const SAIDA = path.join(__dirname, '..', 'saida');
let ok = 0, falha = 0;
const marca = (bom, msg) => { console.log(`  [${bom ? 'OK' : 'FALHA'}]    ${msg}`); if (bom) ok++; else falha++; };
const medir = (rotulo) => {
  execFileSync(process.execPath, [path.join(__dirname, 'medir_telas.js'), rotulo], { stdio: 'pipe' });
  return JSON.parse(fs.readFileSync(path.join(SAIDA, `onda6-telas-${rotulo}.json`), 'utf8')).telas;
};
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');

console.log('\n== Onda 6, Etapa 1: uma conexão por requisição');
const telas = medir('prova-etapa1');
for (const t of telas) marca(t.conexoes === 1, `${t.nome}: ${t.conexoes} conexão(ões) ao banco, ${t.ms} ms`);

const conn = path.join(raiz, 'painel', 'app', 'adms', 'Models', 'helper', 'AdmsConn.php');
const orig = fs.readFileSync(conn, 'utf8');
const shaAntes = sha(conn);
try {
  const sab = orig.replace('\\VcBanco::daRequisicao();',
    "\\VcBanco::abrir('mysql:host=' . HOST . ';dbname=' . DBNAME . ';charset=utf8mb4', USER, PASS);");
  if (sab === orig) throw new Error('a sabotagem não achou o ponto da conexão');
  fs.writeFileSync(conn, sab);
  const s = medir('prova-etapa1-sabotagem');
  const doPainel = s.filter((t) => t.rota.startsWith('painel/'));
  marca(doPainel.every((t) => t.conexoes > 1), `sabotagem (uma conexão por consulta no painel): as ${doPainel.length} telas do painel reprovam (${doPainel.map((t) => t.conexoes).join(', ')} conexões)`);
} finally {
  fs.writeFileSync(conn, orig);
}
marca(sha(conn) === shaAntes, 'o arquivo sabotado voltou igual (sha256)');
console.log(`onda6/etapa1: ${ok} OK, ${falha} FALHA`);
process.exit(falha ? 1 : 0);
