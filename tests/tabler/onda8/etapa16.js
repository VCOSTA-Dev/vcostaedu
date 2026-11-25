/*
 * PROVA DA ONDA 8, CORREÇÃO, SEGUNDA LEVA, 2.2 — a ficha do aluno na escola, sem o que é do ensino superior (D-88).
 * APAGA o banco de prova.
 *   1. a demonstração do colégio, montada pela tela numa instalação nova;
 *   2. a secretaria abre a ficha de uma aluna do 6º ano: o resumo e a aba Matrículas mostram o segmento, a série, a
 *      turma, o ano letivo e a situação; nada de "Módulo", "Gravar módulo no ano", "Polo da matrícula", "Modalidade",
 *      "Forma de ingresso", "Processo seletivo", "Grade", "Vincular a um curso", "Salvar TCC", "TCC" nem "todas as
 *      disciplinas da grade aprovadas";
 *   3. salvar a matrícula pela ficha da escola (a observação) grava e não mexe na forma de ingresso, no polo e na
 *      modalidade guardados;
 *   4. o formulário "Matricular num segmento" (segmento e série, ano letivo, data);
 *   sabotagem: a ficha da escola com a aba do ensino superior — a prova acusa; o arquivo volta igual.
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const L = require('../lib');
const X = require('./_escola');

const ETAPA = 'onda8/etapa16';
const FICHA = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Views', 'academicos', 'verAcademico.php');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const PROIBIDOS = ['Módulo', 'Gravar módulo no ano', 'Polo da matrícula', 'Modalidade', 'Forma de ingresso', 'Processo seletivo', 'Grade ', 'Vincular a um curso', 'Salvar TCC', 'TCC', 'todas as disciplinas da grade aprovadas'];

async function ficha(p, aluno) {
  await p.goto(L.BASE + `painel/academicos/visualizar/${aluno}?tab=tabeMatricula`);
  return p.evaluate(() => {
    const corpo = (document.querySelector('.page-body') || document.body);
    const tudo = corpo.innerText + ' ' + [...corpo.querySelectorAll('label, th, button, option, input[type=submit]')].map((e) => e.textContent || e.value || '').join(' ');
    return {
      tudo,
      serie: [...document.querySelectorAll('#anosDaMatricula tr.posicao')].map((tr) => [tr.dataset.ano, (tr.querySelector('.vc-serie') || {}).textContent, ((tr.querySelector('.vc-turma') || {}).textContent || '').trim()]),
      matricular: !!document.querySelector('#matricularSegmento select#vModulo'),
    };
  });
}

(async () => {
  const { navegador } = await L.abrir();
  const shaFicha = sha(FICHA);
  try {
    L.ok(X.montar('onda8-etapa16').status === 0, 'a demonstração do colégio montada pela tela, numa instalação nova');
    const c = X.cred();
    const sec = await X.entrar(navegador, 'secretaria.demo', c.painel['secretaria.demo']);
    const aluno = X.um("SELECT id FROM col_usuarios WHERE rm = '265001'");
    const turma = X.sql(`SELECT t.nome FROM esc_turma_alunos a JOIN esc_turmas t ON t.id = a.turma_id WHERE a.usuario_id = ${aluno} AND t.ano = 2026 AND a.saida IS NULL`);
    const segmento = X.sql(`SELECT c.nome FROM rm_matriculas_curso mc JOIN rm_cursos c ON c.id = mc.curso_id WHERE mc.usuario_id = ${aluno} ORDER BY mc.id DESC LIMIT 1`);
    const f = await ficha(sec.p, aluno);
    await L.print(sec.p, ETAPA, 'ficha-matriculas-escola');
    const achados = PROIBIDOS.filter((x) => f.tudo.includes(x));
    const linha2026 = f.serie.find((s) => s[0] === '2026') || [];
    L.ok(achados.length === 0, `a ficha da escola sem o que é do ensino superior${achados.length ? ' — apareceu: ' + achados.join(', ') : ''}`);
    L.ok(f.tudo.includes(segmento) && /Cursando/.test(f.tudo) && /6º ano/.test(linha2026[1] || '') && (linha2026[2] || '').startsWith(turma) && f.matricular,
      `o segmento (${segmento}), a situação, o ano letivo 2026, a série (${linha2026[1]}) e a turma (${linha2026[2]}); o formulário "Matricular num segmento"`);

    /* 3. salvar a matrícula não mexe no que é guardado escondido */
    const antes = X.sql(`SELECT CONCAT(forma_ingresso_id, '|', IFNULL(polo_id, 0), '|', IFNULL(modalidade_id, 0)) FROM rm_matriculas_curso WHERE usuario_id = ${aluno} ORDER BY id DESC LIMIT 1`);
    await sec.p.fill('.vinculo-escola form.formVinculo input[name="observacao"]', 'Transferida de outra escola em fevereiro (prova)');
    await Promise.all([sec.p.waitForNavigation(), sec.p.click('.vinculo-escola form.formVinculo button[type="submit"]')]);
    const depois = X.sql(`SELECT CONCAT(forma_ingresso_id, '|', IFNULL(polo_id, 0), '|', IFNULL(modalidade_id, 0), '|', observacao) FROM rm_matriculas_curso WHERE usuario_id = ${aluno} ORDER BY id DESC LIMIT 1`);
    L.ok(depois === `${antes}|Transferida de outra escola em fevereiro (prova)`, `salvar a matrícula pela ficha da escola grava a observação e guarda o resto como estava (${antes})`);
    await sec.ctx.close();

    /* sabotagem: a aba do superior na escola */
    const orig = fs.readFileSync(FICHA, 'utf8');
    const trecho = "\\App\\adms\\Models\\helper\\AdmsTipoInstituicao::ehEscola() ? '/vinculoEscola.php' : '/vinculoAluno.php'";
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let ach = [];
    try {
      fs.writeFileSync(FICHA, orig.replace(trecho, "'/vinculoAluno.php'"));
      const s = await X.entrar(navegador, 'secretaria.demo', c.painel['secretaria.demo']);
      const f2 = await ficha(s.p, aluno);
      ach = PROIBIDOS.filter((x) => f2.tudo.includes(x));
      await s.ctx.close();
    } finally {
      fs.writeFileSync(FICHA, orig);
    }
    L.ok(ach.length > 0, `sabotagem (a ficha da escola com a aba do ensino superior): a prova reprova — apareceu ${ach.join(', ')}`);
    L.ok(sha(FICHA) === shaFicha, 'o arquivo sabotado voltou igual (sha256)');
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(FICHA) !== shaFicha) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
