/*
 * PROVA DA ONDA 8, CORREÇÃO, SEGUNDA LEVA, 2.5 — o vocabulário que sobrou na escola (D-91). APAGA o banco de prova.
 *   1. a demonstração do colégio, montada pela tela numa instalação nova;
 *   2. o relatório "alunos por segmento e situação": os filtros sem "Curso", "Grade", "Polo" e "Coorte de ingresso"
 *      (o segmento, a situação, a turma e o período); a tabela sem a grade e o polo;
 *   3. a lista de disciplinas: as colunas "Disciplina" e "Segmento" (não "Matéria" e "Curso");
 *   4. o portal: a aba do navegador diz "Portal do responsável" para o responsável (não "Portal do aluno"); a tela de
 *      entrada da escola, "Portal do aluno e do responsável";
 *   5. as opções do filtro de segmento são os segmentos da instituição;
 *   sabotagem: o filtro do segmento com o rótulo do ensino superior — a prova acusa; o arquivo volta igual.
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const L = require('../lib');
const X = require('./_escola');

const ETAPA = 'onda8/etapa19';
const BASE_REL = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'relatorios', 'AdmsRelatorioBase.php');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const DO_SUPERIOR = ['Curso', 'Grade', 'Polo', 'Coorte de ingresso'];

/** Os rótulos dos filtros, os títulos da tabela e as opções do segmento do relatório. */
async function relatorio(p) {
  await p.goto(L.BASE + 'painel/relatorios/alunos-situacao');
  return p.evaluate(() => {
    const form = document.querySelector('form') || document.body;
    const rotulos = [...document.querySelectorAll('.page-body form label')].map((l) => l.textContent.trim().replace(/\s+/g, ' ')).filter(Boolean);
    const colunas = [...document.querySelectorAll('.page-body table thead th')].map((t) => t.textContent.trim());
    const seg = document.querySelector('.page-body form select[name="curso"]');
    return { rotulos, colunas: [...new Set(colunas)], segmentos: seg ? [...seg.options].map((o) => o.textContent.trim()).filter((t) => t && !/^(Todos|Todas|—|Selecione)/.test(t)) : [], temForm: !!form };
  });
}

(async () => {
  const { navegador } = await L.abrir();
  const shaBase = sha(BASE_REL);
  try {
    L.ok(X.montar('onda8-etapa19').status === 0, 'a demonstração do colégio montada pela tela, numa instalação nova');
    const c = X.cred();
    const sec = await X.entrar(navegador, 'secretaria.demo', c.painel['secretaria.demo']);

    /* 2. o relatório */
    const r = await relatorio(sec.p);
    await L.print(sec.p, ETAPA, 'alunos-por-segmento');
    const nosFiltros = DO_SUPERIOR.filter((x) => r.rotulos.includes(x));
    const naTabela = r.colunas.filter((x) => ['Curso', 'Grade', 'Polo'].includes(x));
    L.ok(nosFiltros.length === 0 && r.rotulos.includes('Segmento') && naTabela.length === 0,
      `o relatório "alunos por segmento": os filtros ${r.rotulos.join(', ')}${nosFiltros.length ? ' — do superior: ' + nosFiltros.join(', ') : ''}; a tabela sem curso, grade e polo${naTabela.length ? ' — apareceu: ' + naTabela.join(', ') : ''}`);
    const nomes = X.sql('SELECT nome FROM rm_cursos WHERE situacao = 1 ORDER BY nome').split('\n').filter(Boolean);
    L.ok(r.segmentos.length > 0 && r.segmentos.every((s) => nomes.includes(s)), `as opções do segmento são os segmentos da instituição (${r.segmentos.join('; ')})`);

    /* 3. a lista de disciplinas */
    await sec.p.goto(L.BASE + 'painel/disciplinas/listar');
    const th = await sec.p.$$eval('#tabelaDisciplinas thead th', (l) => l.map((t) => t.textContent.trim()));
    await L.print(sec.p, ETAPA, 'lista-de-disciplinas');
    L.ok(th.includes('Disciplina') && th.includes('Segmento') && !th.includes('Matéria') && !th.includes('Curso'), `a lista de disciplinas: ${th.slice(0, 4).join(', ')}…`);
    await sec.ctx.close();

    /* 4. o portal */
    const fams = Object.keys(c.familia || {});
    if (!fams.length) { throw new Error('a demonstração não guardou a senha de nenhum responsável'); }
    const ctx = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' });
    const lp = await L.pagina(ctx);
    await lp.goto(L.BASE + 'portal/login/acesso');
    const tituloEntrada = await lp.title();
    await ctx.close();
    const fam = await X.entrar(navegador, fams[0], c.familia[fams[0]], 'portal');
    const tituloFam = await fam.p.title();
    await fam.ctx.close();
    const ra = Object.keys(c.portal || {}).find((k) => /^265/.test(k));
    const alu = await X.entrar(navegador, ra, c.portal[ra], 'portal');
    const tituloAlu = await alu.p.title();
    await alu.ctx.close();
    L.ok(/Portal do responsável/.test(tituloFam) && !/Portal do aluno/.test(tituloFam) && /Portal do aluno$/.test(tituloAlu) && /Portal do aluno e do responsável/.test(tituloEntrada),
      `a aba do navegador: "${tituloFam}" para o responsável; "${tituloAlu}" para o aluno; "${tituloEntrada}" na entrada`);

    /* sabotagem: o rótulo do superior */
    const orig = fs.readFileSync(BASE_REL, 'utf8');
    const trecho = "$saida['curso']['rotulo'] = 'Segmento';";
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let s = null;
    try {
      fs.writeFileSync(BASE_REL, orig.replace(trecho, "$saida['curso']['rotulo'] = 'Curso';"));
      const s2 = await X.entrar(navegador, 'secretaria.demo', c.painel['secretaria.demo']);
      s = await relatorio(s2.p);
      await s2.ctx.close();
    } finally {
      fs.writeFileSync(BASE_REL, orig);
    }
    L.ok(s && s.rotulos.includes('Curso'), `sabotagem (o filtro do segmento com o rótulo do superior): a prova reprova — os filtros ${s ? s.rotulos.join(', ') : '?'}`);
    L.ok(sha(BASE_REL) === shaBase, 'o arquivo sabotado voltou igual (sha256)');
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(BASE_REL) !== shaBase) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
