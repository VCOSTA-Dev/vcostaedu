/*
 * PROVA DA 1.103.0, C10 — o aviso no histórico do Ensino Médio sem a conclusão do Fundamental (D-133). APAGA o banco de
 * prova (a escola do zero, com a demonstração pela tela).
 *   1. a prévia do histórico do Médio de um aluno sem o registro da conclusão do Fundamental: o aviso diz o que falta e
 *      onde registrar ("Dados do aluno para o histórico"); é aviso, não bloqueio (a emissão continua possível);
 *   2. no Fundamental, o aviso não aparece;
 *   3. registrado o ano da conclusão, o aviso some;
 *   4. o documento (o HTML da prévia) é o mesmo com e sem o aviso: o layout não muda;
 *   sabotagem: o aviso desligado — a prévia do Médio sem ele: a prova reprova; o arquivo volta igual (sha256).
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const L = require('../lib');
const X = require('./_escola');

const ETAPA = 'onda8/etapa54';
const DOC = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'historicoEscola', 'AdmsHistDocumento.php');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const shaT = (t) => crypto.createHash('sha256').update(t).digest('hex');
const { sql, um } = X;
const texto = async (p, sel) => ((await p.textContent(sel).catch(() => '')) || '').replace(/\s+/g, ' ').trim();
const AVISO = /Não há registro da conclusão do Ensino Fundamental deste aluno: registre o ano e a escola em Dados do aluno para o histórico/;

async function previa(p, aluno, nivel) {
  await p.goto(L.BASE + `painel/historico-escola-emissao/previa/${aluno}?nivel=${nivel}`);
  return { avisos: await texto(p, '#avisos'), bloqueios: await texto(p, '#bloqueios'), emitir: !!(await p.$('form[action*="historico-escola-emissao/emitir"]')) };
}
async function documento(p, aluno) {
  const r = await p.request.get(L.BASE + `painel/historico-escola-emissao/previa-pdf/${aluno}?nivel=EM&formato=html`);
  return r.text();
}

(async () => {
  const { navegador } = await L.abrir();
  const shaD = sha(DOC);
  let em = 0;
  try {
    L.ok(X.montar('onda8-etapa54').status === 0, 'a escola instalada do zero, com a demonstração do colégio pela tela');
    const c = X.cred();
    em = um("SELECT u.id FROM rm_matriculas_curso m JOIN col_usuarios u ON u.id = m.usuario_id JOIN esc_hist_segmento s ON s.curso_id = m.curso_id AND s.nivel = 'EM' ORDER BY u.rm LIMIT 1");
    const ef = um("SELECT u.id FROM rm_matriculas_curso m JOIN col_usuarios u ON u.id = m.usuario_id JOIN esc_hist_segmento s ON s.curso_id = m.curso_id AND s.nivel = 'EF' ORDER BY u.rm LIMIT 1");
    sql(`DELETE FROM esc_hist_aluno WHERE aluno_id = ${em}`);
    const sec = await X.entrar(navegador, 'secretaria.demo', c.painel['secretaria.demo']);
    const p = sec.p;

    /* 1. o Médio sem a conclusão do Fundamental */
    const m1 = await previa(p, em, 'EM');
    await L.print(p, ETAPA, 'previa-medio-sem-conclusao');
    const doc1 = await documento(p, em);
    L.ok(em > 0 && AVISO.test(m1.avisos) && !AVISO.test(m1.bloqueios),
      `1. a prévia do Médio avisa que falta a conclusão do Fundamental e diz onde registrar; é aviso, não bloqueio (o botão de emitir: ${m1.emitir})`);

    /* 2. o Fundamental */
    const f1 = await previa(p, ef, 'EF');
    L.ok(ef > 0 && !AVISO.test(f1.avisos + f1.bloqueios), '2. no Fundamental, o aviso não aparece');

    /* 3. registrado o ano */
    sql(`INSERT INTO esc_hist_aluno (aluno_id, ef_conclusao_ano) VALUES (${em}, 2025) ON DUPLICATE KEY UPDATE ef_conclusao_ano = 2025`);
    const m2 = await previa(p, em, 'EM');
    L.ok(!AVISO.test(m2.avisos), '3. registrado o ano da conclusão do Fundamental, o aviso some');
    sql(`DELETE FROM esc_hist_aluno WHERE aluno_id = ${em}`);

    /* sabotagem: o aviso desligado; e o documento é o mesmo com e sem o aviso */
    const orig = fs.readFileSync(DOC, 'utf8');
    const trecho = "if ($nivel === 'EM' && (int) ($a['ef_conclusao_ano'] ?? 0) === 0) {";
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let sab = null;
    let doc2 = '';
    try {
      fs.writeFileSync(DOC, orig.replace(trecho, "if (false && (int) ($a['ef_conclusao_ano'] ?? 0) === 0) {"));
      sab = await previa(p, em, 'EM');
      doc2 = await documento(p, em);
    } finally {
      fs.writeFileSync(DOC, orig);
    }
    const limpa = (h) => h.replace(/\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}(:\d{2})?/g, '');
    L.ok(doc1.length > 1000 && shaT(limpa(doc1)) === shaT(limpa(doc2)), `4. o documento do Médio é o mesmo com e sem o aviso (${doc1.length} caracteres): o layout não muda`);
    L.ok(sab && !AVISO.test(sab.avisos), 'sabotagem (o aviso desligado): a prévia do Médio sem ele — a prova reprova');
    L.ok(sha(DOC) === shaD, 'o arquivo sabotado voltou igual (sha256)');
    await sec.ctx.close();
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split(String.fromCharCode(10))[0]);
  } finally {
    if (sha(DOC) !== shaD) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
