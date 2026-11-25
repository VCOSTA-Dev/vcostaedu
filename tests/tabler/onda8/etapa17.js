/*
 * PROVA DA ONDA 8, CORREÇÃO, SEGUNDA LEVA, 2.3 — a coordenadora da demonstração do colégio ligada a um segmento (D-89).
 * APAGA o banco de prova.
 *   1. a demonstração do colégio, montada pela tela numa instalação nova: a coordenadora fictícia coordena os anos
 *      finais, e só eles;
 *   2. ela vê as turmas dos anos finais (e nenhuma do Ensino Médio), abre uma delas e o horário; a turma do Ensino Médio
 *      é recusada;
 *   3. a estrutura da escola mostra só o segmento dela (as séries e as disciplinas da matriz);
 *   4. os relatórios: o painel acadêmico do segmento dela abre; o do Ensino Médio é recusado; o segmento do Ensino Médio
 *      também;
 *   sabotagem: o recorte do coordenador desligado — a prova acusa; o arquivo volta igual.
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const L = require('../lib');
const X = require('./_escola');

const ETAPA = 'onda8/etapa17';
const ESCOPO = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'helper', 'AdmsEscopo.php');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');



/** O que a coordenadora alcança: as turmas listadas, a turma do outro segmento, a estrutura e os relatórios. */
async function alcance(p, ids) {
  await p.goto(L.BASE + 'painel/turmas/listar?ano=2026');
  const listadas = await p.$$eval('a[href*="/turmas/ver/"]', (l) => [...new Set(l.map((a) => +(a.href.match(/turmas\/ver\/(\d+)/) || [])[1]).filter(Boolean))]);
  await p.goto(L.BASE + `painel/turmas/ver/${ids.turmaEM}`);
  const turmaEM = { url: p.url(), recusa: !!(await p.$('#foraDoEscopo')), abriu: /turmas\/ver\//.test(p.url()) };
  await p.goto(L.BASE + `painel/turmas/ver/${ids.turmaEF}`);
  const turmaEF = /turmas\/ver\//.test(p.url()) && !(await p.$('#semAcesso')) && !(await p.$('[name="SendLogin"]'));
  await p.goto(L.BASE + `painel/turmas/horario/${ids.turmaEF}`);
  const horarioEF = /turmas\/horario\//.test(p.url()) && !(await p.$('#semAcesso'));
  await p.goto(L.BASE + 'painel/escola/index');
  const estrutura = await p.evaluate(() => (document.querySelector('.page-body') || document.body).innerText);
  await p.goto(L.BASE + `painel/relatorios/painel-academico?curso=${ids.ef}`);
  const relEF = /relatorios\/painel-academico/.test(p.url()) && !(await p.$('#semAcesso')) && !(await p.$('#foraDoEscopo'));
  await p.goto(L.BASE + `painel/relatorios/painel-academico?curso=${ids.em}`);
  const relEM = !!(await p.$('#foraDoEscopo')) && !/relatorios\/painel-academico/.test(p.url());
  await p.goto(L.BASE + `painel/cursos/visualizar/${ids.em}`);
  const segEM = !!(await p.$('#foraDoEscopo')) || !!(await p.$('#semAcesso'));
  return { listadas, turmaEM, turmaEF, horarioEF, estrutura, relEF, relEM, segEM };
}

(async () => {
  const { navegador } = await L.abrir();
  const shaEscopo = sha(ESCOPO);
  try {
    L.ok(X.montar('onda8-etapa17').status === 0, 'a demonstração do colégio montada pela tela, numa instalação nova');
    const c = X.cred();
    const ids = {
      /* sem acento no SQL: o argumento do mysql.exe perde os acentos */
      ef: X.um("SELECT id FROM rm_cursos WHERE nome LIKE 'Ensino Fundamental % Anos Finais (demonstra%'"),
      em: X.um("SELECT id FROM rm_cursos WHERE nome LIKE 'Ensino M_dio (demonstra%'"),
    };
    ids.turmaEF = X.um(`SELECT MIN(id) FROM esc_turmas WHERE curso_id = ${ids.ef} AND ano = 2026`);
    ids.turmaEM = X.um(`SELECT MIN(id) FROM esc_turmas WHERE curso_id = ${ids.em} AND ano = 2026`);
    const turmasEF = X.sql(`SELECT id FROM esc_turmas WHERE curso_id = ${ids.ef} AND ano = 2026 ORDER BY id`).split('\n').filter(Boolean).map(Number);
    const coordena = X.sql("SELECT GROUP_CONCAT(k.curso_id ORDER BY k.curso_id) FROM rm_cursos_coordenacao k JOIN adms_usuarios u ON u.id = k.usuario_id WHERE u.usuario = 'coord.demo' AND k.situacao = 1");
    L.ok(coordena === String(ids.ef), `a coordenadora fictícia coordena os anos finais, e só eles (segmentos ${coordena}; anos finais = ${ids.ef}, Ensino Médio = ${ids.em})`);

    const co = await X.entrar(navegador, 'coord.demo', c.painel['coord.demo']);
    const a = await alcance(co.p, ids);
    await L.print(co.p, ETAPA, 'coordenadora-recusada-no-medio');
    L.ok(a.listadas.length === turmasEF.length && a.listadas.every((id) => turmasEF.includes(id)) && !a.listadas.includes(ids.turmaEM),
      `as turmas de 2026 que ela vê: ${a.listadas.length}, todas dos anos finais (${turmasEF.length} no segmento); nenhuma do Ensino Médio`);
    L.ok(a.turmaEF && a.horarioEF && a.turmaEM.recusa && !a.turmaEM.abriu,
      `a turma e o horário dos anos finais abrem; a turma do Ensino Médio é recusada (${a.turmaEM.url.replace(L.BASE, '')})`);
    L.ok(a.estrutura.includes('Anos Finais') && !a.estrutura.includes('Ensino Médio (demonstração)'), 'a estrutura da escola mostra só o segmento dela');
    L.ok(a.relEF && a.relEM && a.segEM, 'os relatórios: o painel acadêmico dos anos finais abre; o do Ensino Médio é recusado; o segmento do Ensino Médio também');
    await co.ctx.close();

    /* sabotagem: o recorte do coordenador desligado */
    const orig = fs.readFileSync(ESCOPO, 'utf8');
    const trecho = 'if (!AdmsNivel::comPapeis() || AdmsNivel::papel() !== AdmsNivel::COORDENACAO) {';
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let s = null;
    try {
      fs.writeFileSync(ESCOPO, orig.replace(trecho, 'if (true) {'));
      const co2 = await X.entrar(navegador, 'coord.demo', c.painel['coord.demo']);
      s = await alcance(co2.p, ids);
      await co2.ctx.close();
    } finally {
      fs.writeFileSync(ESCOPO, orig);
    }
    L.ok(s && (s.listadas.includes(ids.turmaEM) || !s.turmaEM.recusa), `sabotagem (o recorte do coordenador desligado): a prova reprova — ela viu ${s ? s.listadas.length : '?'} turmas, a do Ensino Médio incluída`);
    L.ok(sha(ESCOPO) === shaEscopo, 'o arquivo sabotado voltou igual (sha256)');
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(ESCOPO) !== shaEscopo) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
