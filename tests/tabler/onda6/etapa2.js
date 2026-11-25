/*
 * PROVA DA ONDA 6, ETAPA 2 — o tempo da demonstração da escola.
 *   1. a montagem do colégio fictício PELA TELA, do zero (medir_demo.js): nenhum passo acima de 30 s;
 *   2. a régua da escola idêntica à da demonstração da Onda 4 (o resultado não mudou);
 *   3. sabotagem: um passo que demora 31 s (o conselho de classe, com uma pausa posta pela prova) — a prova reprova;
 *      o arquivo volta igual (sha256).
 * APAGA o banco de prova (a bateria roda o ciclo antes desta prova). Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const { execFileSync, spawnSync } = require('child_process');
const crypto = require('crypto');
const fs = require('fs');
const path = require('path');

const raiz = path.join(__dirname, '..', '..', '..');
const SAIDA = path.join(__dirname, '..', 'saida');
const PHP = process.env.VCOSTAEDU_PHP || 'C:/xampp/php/php.exe';
const LIMITE = 30;
let ok = 0, falha = 0;
const marca = (bom, msg) => { console.log(`  [${bom ? 'OK' : 'FALHA'}]    ${msg}`); if (bom) ok++; else falha++; };
const medir = (rotulo) => {
  const r = spawnSync(process.execPath, [path.join(__dirname, 'medir_demo.js'), 'escola', rotulo], { encoding: 'utf8' });
  if (r.status !== 0) throw new Error('a medição falhou: ' + (r.stderr || r.stdout).slice(-300));
  return JSON.parse(fs.readFileSync(path.join(SAIDA, `onda6-demo-escola-${rotulo}.json`), 'utf8'));
};
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const CONFIG = 'adms_versoes,adms_novidades,adms_paginas,adms_nivacs_pgs,adms_acoes,adms_instalacao_estado,adms_paginas_modulo,adms_fases,adms_paginas_recurso,col_paginas_modulo,rm_media_regras,rm_media_regra_versoes,rm_media_regra_componentes,rm_media_regra_acrescimo,adms_parametros,col_niveis_acessos,col_paginas,col_nivacs_pgs';

console.log('\n== Onda 6, Etapa 2: o tempo da demonstração da escola');
const m = medir('prova-etapa2');
const tempos = Object.entries(m.tempos).sort((a, b) => b[1] - a[1]);
marca(tempos.length >= 30 && tempos[0][1] <= LIMITE, `pela tela, ${tempos.length} passos em ${m.total} s; o mais caro, ${tempos[0][0]}, em ${tempos[0][1]} s (o limite: ${LIMITE} s)`);
const regua = spawnSync(PHP, [path.join(raiz, 'tests', 'tabler', 'regua.php'), 'comparar', path.join(SAIDA, 'regua-escola-onda4-etapa4.json'), `--config=${CONFIG}`], { encoding: 'utf8', cwd: raiz, maxBuffer: 1 << 26 });
marca(/IDÊNTICA/.test(regua.stdout), 'a régua da escola idêntica à da demonstração da Onda 4: ' + (regua.stdout.match(/IDÊNTICA[^\n]*|DIFERENTE[^\n]*/) || ['?'])[0]);

const cen = path.join(raiz, 'database', 'ferramentas', 'demonstracao_escola.php');
const orig = fs.readFileSync(cen, 'utf8');
const shaAntes = sha(cen);
try {
  const ponto = '    public static function passoConselho(VcDemoContexto $c): void\n    {\n';
  if (!orig.includes(ponto)) throw new Error('a sabotagem não achou o passo do conselho');
  fs.writeFileSync(cen, orig.replace(ponto, ponto + '        sleep(31); // sabotagem da prova da Onda 6, Etapa 2\n'));
  const s = medir('prova-etapa2-sabotagem');
  const lento = Object.entries(s.tempos).filter(([, t]) => t > LIMITE);
  marca(lento.length > 0, `sabotagem (o conselho com uma pausa de 31 s): a prova reprova — ${lento.map(([k, t]) => `${k} em ${t} s`).join(', ') || 'nenhum passo acima do limite'}`);
} finally {
  fs.writeFileSync(cen, orig);
}
marca(sha(cen) === shaAntes, 'o arquivo sabotado voltou igual (sha256)');
console.log(`onda6/etapa2: ${ok} OK, ${falha} FALHA`);
process.exit(falha ? 1 : 0);
