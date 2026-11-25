/*
 * PROVA DA ONDA 8, CORREÇÃO, SEGUNDA LEVA, 2.6 — a inscrição que não duplica (D-92), contra o Moodle de teste. APAGA o
 * banco de prova.
 *   1. a demonstração do colégio, montada pela tela; o Moodle de teste conectado; a turma aberta do curso livre;
 *   2. uma depois da outra: a inscrição de um e-mail na turma e, de novo, o mesmo e-mail na mesma turma com OUTRO número
 *      de matrícula (o site gera um por envio): a segunda responde com sucesso e devolve a matrícula que já existe; uma
 *      pessoa, uma matrícula, um lugar na turma; nenhum envio novo ao Moodle; o mesmo envio repetido igual, também;
 *   3. as duas ao mesmo tempo (outro e-mail, dois números de matrícula): uma pessoa, uma matrícula; as duas respostas
 *      com a mesma matrícula; cada envio ao Moodle uma vez só;
 *   4. o registro das mudanças diz "inscrição que não duplica";
 *   sabotagem: sem a busca pelo e-mail na turma — a segunda inscrição cria outra pessoa; a prova acusa; o arquivo volta
 *   igual.
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE, VCOSTAEDU_MOODLE_URL, VCOSTAEDU_MOODLE_CHAVE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const L = require('../lib');
const X = require('./_escola');

const ETAPA = 'onda8/etapa20';
const RECURSOS = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'api', 'AdmsApiRecursos.php');
const API = L.BASE + 'api/v1';
const MURL = process.env.VCOSTAEDU_MOODLE_URL || '';
const MCHAVE = process.env.VCOSTAEDU_MOODLE_CHAVE || '';
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const R = String(Date.now()).slice(-5);
let proximo = +`8${R}100`;
const novoRa = () => String(proximo++);

function criarChave() {
  const chave = 'vce_' + crypto.randomBytes(20).toString('hex');
  X.sql(`INSERT INTO adms_api_chaves (nome, prefixo, hash, escopo, permissoes, situacao, usuario_id) VALUES ('Prova inscricao', '${chave.slice(0, 12)}', '${crypto.createHash('sha256').update(chave).digest('hex')}', 'escrita', 'alunos:gravar matriculas:gravar enturmacao:gravar cadastros:ler', 1, 1)`);
  return chave;
}
async function inscrever(chave, ra, email, turma) {
  const r = await fetch(API + '/inscricoes', { method: 'POST', headers: { Authorization: 'Bearer ' + chave, 'Content-Type': 'application/json' },
    body: JSON.stringify({ aluno: { ra, nome: 'Pessoa da Prova Inscrita', email, celular: '11987650001' }, destino: { turma_id: turma } }) });
  let j = null;
  try { j = await r.json(); } catch (e) { /* */ }
  return { st: r.status, mc: j && j.dados && j.dados.matricula ? +j.dados.matricula.id : 0, feito: j ? j.feito : null, erro: j && j.erro ? j.erro.mensagem : '' };
}
/** O que existe para o e-mail: as pessoas, as matrículas, os lugares na turma e os envios ao Moodle (e os repetidos). */
function contar(email, turma) {
  const ids = X.sql(`SELECT id FROM col_usuarios WHERE LOWER(email) = '${email}'`).split('\n').filter(Boolean).join(',') || '0';
  return {
    pessoas: X.um(`SELECT COUNT(*) FROM col_usuarios WHERE LOWER(email) = '${email}'`),
    matriculas: X.um(`SELECT COUNT(*) FROM rm_matriculas_curso WHERE usuario_id IN (${ids})`),
    naTurma: X.um(`SELECT COUNT(*) FROM esc_turma_alunos WHERE turma_id = ${turma} AND usuario_id IN (${ids})`),
    envios: X.um(`SELECT COUNT(*) FROM adms_moodle_envios WHERE aluno_id IN (${ids})`),
    repetidos: X.um(`SELECT COUNT(*) - COUNT(DISTINCT chave) FROM adms_moodle_envios WHERE aluno_id IN (${ids})`),
  };
}

(async () => {
  const { navegador } = await L.abrir();
  const shaRec = sha(RECURSOS);
  try {
    L.ok(X.montar('onda8-etapa20').status === 0, 'a demonstração do colégio montada pela tela, numa instalação nova');
    const turma = X.um("SELECT t.id FROM esc_turmas t JOIN rm_cursos c ON c.id = t.curso_id WHERE c.nome LIKE 'Curso livre:%' AND t.nome = 'Turma aberta'");
    /* o Moodle de teste, pela tela */
    const ctx = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' });
    const p = await L.pagina(ctx);
    await p.goto(L.BASE + 'painel/login/acesso');
    await p.fill('input[name="usuario"]', 'administrador');
    await p.fill('input[name="senha"]', process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123');
    await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
    X.sql("UPDATE adms_parametros SET valor = 'prova-' WHERE chave = 'moodle.prefixo'");
    await p.goto(L.BASE + 'painel/moodle/conexao');
    await p.fill('#url', MURL);
    await p.fill('#chave', MCHAVE);
    await p.fill('#emailDominio', 'alunos.escola-prova.invalid');
    await Promise.all([p.waitForNavigation(), p.click('#salvarMoodle')]);
    await p.goto(L.BASE + 'painel/moodle/conexao');
    await Promise.all([p.waitForNavigation(), p.click('#testarMoodle')]);
    await p.goto(L.BASE + 'painel/moodle/conexao');
    const temCat = await p.$$eval('#categoria_id option', (os) => os.some((o) => o.textContent.trim() === 'VCOSTAEDU provas'));
    if (temCat) { await p.selectOption('#categoria_id', { label: 'VCOSTAEDU provas' }); } else { await p.fill('#categoria_nova', 'VCOSTAEDU provas'); }
    await Promise.all([p.waitForNavigation({ timeout: 120000 }), p.click('#salvarMoodle')]);
    const k = criarChave();
    L.ok(turma > 0 && X.um("SELECT COUNT(*) FROM adms_moodle_conexao WHERE testada_em IS NOT NULL AND categoria_id > 0") === 1, `o Moodle de teste conectado; a turma aberta do curso livre (${turma})`);

    /* 2. uma depois da outra */
    const e1 = `prova.${R}.um@exemplo.invalid`;
    const a = await inscrever(k, novoRa(), e1, turma);
    const antes = contar(e1, turma);
    const b = await inscrever(k, novoRa(), e1, turma);
    const c2 = await inscrever(k, novoRa(), e1.toUpperCase(), turma);
    const depois = contar(e1, turma);
    L.ok(a.st === 201 && a.mc > 0 && b.st === 200 && b.mc === a.mc && c2.st === 200 && c2.mc === a.mc
      && JSON.stringify(depois) === JSON.stringify(antes) && depois.pessoas === 1 && depois.matriculas === 1 && depois.naTurma === 1 && depois.repetidos === 0,
      `uma depois da outra: a primeira ${a.st}; o mesmo e-mail com outro número de matrícula, ${b.st} e ${c2.st} (em maiúsculas), a mesma matrícula ${b.mc}; ${JSON.stringify(depois)} antes e depois`);

    /* 3. as duas ao mesmo tempo */
    const e2 = `prova.${R}.dois@exemplo.invalid`;
    const [x, y] = await Promise.all([inscrever(k, novoRa(), e2, turma), inscrever(k, novoRa(), e2, turma)]);
    const juntas = contar(e2, turma);
    L.ok([x.st, y.st].sort().join() === '200,201' && x.mc === y.mc && x.mc > 0 && juntas.pessoas === 1 && juntas.matriculas === 1 && juntas.naTurma === 1 && juntas.repetidos === 0,
      `as duas ao mesmo tempo: ${x.st} e ${y.st}, a mesma matrícula (${x.mc}); ${JSON.stringify(juntas)}`);

    /* 4. o registro das mudanças */
    await p.goto(L.BASE + 'painel/novidades/listar');
    const novidades = await p.evaluate(() => document.body.innerText);
    L.ok(/inscrição que não duplica/i.test(novidades), 'o registro das mudanças diz "inscrição que não duplica"');
    await ctx.close();

    /* sabotagem: sem a busca pelo e-mail na turma */
    const orig = fs.readFileSync(RECURSOS, 'utf8');
    const trecho = "$ra = (string) $naTurma['rm'];";
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let s = null;
    const e3 = `prova.${R}.tres@exemplo.invalid`;
    try {
      await inscrever(k, novoRa(), e3, turma);
      fs.writeFileSync(RECURSOS, orig.replace(trecho, '/* sabotagem */'));
      await inscrever(k, novoRa(), e3, turma);
      s = contar(e3, turma);
    } finally {
      fs.writeFileSync(RECURSOS, orig);
    }
    L.ok(s && s.pessoas !== 1, `sabotagem (sem a busca pelo e-mail na turma): a prova reprova — ${s ? s.pessoas : '?'} pessoas com o mesmo e-mail`);
    L.ok(sha(RECURSOS) === shaRec, 'o arquivo sabotado voltou igual (sha256)');
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(RECURSOS) !== shaRec) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
