/*
 * A MEDIÇÃO DA MONTAGEM DA DEMONSTRAÇÃO PELA TELA (Onda 6, Etapas 1 e 2), passo a passo, num tipo de instituição:
 * o banco de prova vazio, o instalador (ensino superior ou escola), "Conhecer com dados de demonstração" e a janela dos
 * passos até o fim. O tempo de cada passo é o que o próprio servidor mede (a resposta de cada passo traz "tempos").
 *
 *   node tests/tabler/onda6/medir_demo.js superior|escola <rótulo>
 *       grava saida/onda6-demo-<tipo>-<rótulo>.json e imprime os passos, do mais caro ao mais barato
 *   node tests/tabler/onda6/medir_demo.js comparar <tipo> <antes> <depois>
 *
 * APAGA o banco de prova (reset.php: só aceita nome que termine em _teste). Ambiente: VCOSTAEDU_BASE,
 * VCOSTAEDU_BANCO_TESTE.
 */
const { spawnSync } = require('child_process');
const fs = require('fs');
const path = require('path');
const L = require('../lib');

const SAIDA = path.join(__dirname, '..', 'saida');
const PHP = process.env.VCOSTAEDU_PHP || 'C:/xampp/php/php.exe';
const SENHA = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const arq = (tipo, r) => path.join(SAIDA, `onda6-demo-${tipo}-${r}.json`);

if (process.argv[2] === 'comparar') {
  const [, , , tipo, ra, rd] = process.argv;
  const a = JSON.parse(fs.readFileSync(arq(tipo, ra), 'utf8'));
  const d = JSON.parse(fs.readFileSync(arq(tipo, rd), 'utf8'));
  console.log(`| passo | antes (s) | depois (s) |\n|---|---|---|`);
  for (const k of Object.keys(a.tempos)) console.log(`| ${k} | ${a.tempos[k]} | ${d.tempos[k] ?? '—'} |`);
  console.log(`| **total (a janela inteira)** | ${a.total} | ${d.total} |`);
  process.exit(0);
}

const tipo = ['escola', 'livre'].includes(process.argv[2]) ? process.argv[2] : 'superior'; // D-106: e o curso livre
const rotulo = process.argv[3] || 'medida';
(async () => {
  spawnSync(PHP, [path.join(L.RAIZ, 'tests', 'tabler', 'reset.php')], { encoding: 'utf8', cwd: L.RAIZ });
  const inst = spawnSync('node', [path.join(L.RAIZ, 'tests', 'tabler', 'instalar.js'), 'normal'], { encoding: 'utf8', cwd: L.RAIZ, env: { ...process.env, VCOSTAEDU_TIPO: tipo } });
  if (inst.status !== 0) throw new Error('o instalador falhou: ' + (inst.stdout || '').split('\n').filter((l) => /FALHA/.test(l)).join(' | '));
  const { navegador, contexto } = await L.abrir();
  try {
    const p = await contexto.newPage();
    await p.goto(L.BASE + 'painel/login/acesso');
    await p.fill('input[name="usuario"]', 'administrador');
    await p.fill('input[name="senha"]', SENHA);
    await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
    let ultima = null;
    p.on('response', async (r) => {
      if (/demonstracao\/montar/.test(r.url()) && /fase=passo/.test(r.request().postData() || '')) {
        try { ultima = await r.json(); } catch (e) { /* resposta cortada */ }
      }
    });
    await p.goto(L.BASE + 'painel/demonstracao/comecar');
    const t0 = Date.now();
    await p.click('#montarDemonstracao');
    await p.waitForSelector('#progressoMontagem[data-situacao="concluida"], #progressoMontagem[data-situacao="falhou"]', { timeout: 1800000 });
    const total = Math.round((Date.now() - t0) / 100) / 10;
    if ((await p.getAttribute('#progressoMontagem', 'data-situacao')) !== 'concluida') throw new Error('a montagem falhou: ' + (await p.textContent('#progressoMontagem')).replace(/\s+/g, ' ').slice(0, 200));
    await p.waitForTimeout(500);
    const tempos = (ultima && ultima.tempos) || {};
    fs.writeFileSync(arq(tipo, rotulo), JSON.stringify({ tipo, rotulo, quando: new Date().toISOString(), total, tempos }, null, 1));
    const ord = Object.entries(tempos).sort((a, b) => b[1] - a[1]);
    for (const [k, s] of ord) console.log(`  ${k.padEnd(28)} ${String(s).padStart(7)} s${s > 30 ? '   (acima de 30 s)' : ''}`);
    console.log(`${tipo}: ${Object.keys(tempos).length} passos; a janela inteira em ${total} s; o mais caro: ${ord[0] ? ord[0][0] + ' (' + ord[0][1] + ' s)' : '—'}`);
  } finally { await navegador.close(); }
})().catch((e) => { console.error('FALHA: ' + e.message); process.exit(1); });
