/*
 * PROVA DA ONDA 8, CORREÇÃO, PARTE 4 — o dia a dia do professor na escola (D-82). APAGA o banco de prova.
 *   1. a demonstração do colégio, montada pela tela numa instalação nova;
 *   2. a Página Inicial da professora mostra "O meu dia": os componentes dela no ano, cada um com Chamada, Notas e
 *      Etapas, e cada atalho abre a tela certa do componente;
 *   3. "Minhas disciplinas" na escola: o segmento no lugar do curso, "Abrir a disciplina", a primeira aba "Disciplina"
 *      e nenhum "componente" na tela (D-85: na escola, o termo é "disciplina");
 *   4. a secretaria (que não é professora) não vê "O meu dia";
 *   5. o portal do responsável: o Resumo do filho com dados de verdade (a média geral oculta até a liberação, D-100; as faltas,
 *      a turma com o turno e a sala, as disciplinas com o professor de cada uma — um diferente por disciplina, na
 *      demonstração), sem o botão "Turma" (o endereço antigo leva ao Resumo) e sem "componente"; o horário em cartões por
 *      dia, com o filtro;
 *   sabotagem: o cartão sem os componentes — a prova acusa; o arquivo volta igual (sha256).
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { spawnSync, execFileSync } = require('child_process');
const L = require('../lib');

const ETAPA = 'onda8/etapa14';
const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste$/.test(BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
const CARTAO = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Views', 'home', '_diaProfessor.php');

const sql = (q) => execFileSync('C:/xampp/mysql/bin/mysql.exe', ['-uroot', '--default-character-set=utf8mb4', '-N', '-B', BANCO, '-e', q], { encoding: 'utf8' }).split('\n').map((l) => l.trim()).filter(Boolean).join('\n');
const um = (q) => +(sql(q).split('\n')[0] || 0);
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const montar = (rotulo) => spawnSync(process.execPath, [path.join(__dirname, '..', 'onda6', 'medir_demo.js'), 'escola', rotulo], { encoding: 'utf8', maxBuffer: 1 << 24 });
async function entrar(navegador, usuario, senha) {
  const ctx = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' });
  const p = await L.pagina(ctx);
  await p.goto(L.BASE + 'painel/login/acesso');
  await p.fill('input[name="usuario"]', usuario);
  await p.fill('input[name="senha"]', senha);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
  return { ctx, p };
}
const cartao = (p) => p.$$eval('#diaProfessor tbody tr', (l) => l.map((t) => ({ oferta: t.dataset.oferta, chamada: !!t.querySelector('.vc-dia-chamada'), faltas: !!t.querySelector('.vc-dia-faltas'), notas: !!t.querySelector('.vc-dia-notas'), etapas: !!t.querySelector('.vc-dia-etapas') }))).catch(() => []);

(async () => {
  const { navegador } = await L.abrir();
  const shaCartao = sha(CARTAO);
  try {
    const r = montar('onda8-etapa14');
    L.ok(r.status === 0, 'a demonstração do colégio montada pela tela, numa instalação nova');
    const cred = JSON.parse(sql("SELECT valor FROM adms_instalacao_estado WHERE chave = 'demonstracao.credenciais'"));
    const senhaProf = (cred.painel || {})['prof.laura.demo'] || '';
    const uid = um("SELECT id FROM adms_usuarios WHERE usuario = 'prof.laura.demo'");
    const dela = um(`SELECT COUNT(*) FROM rm_professores_mat_liga l JOIN rm_professores_materias o ON o.id = l.rm_prof_mat_id JOIN esc_turma_ofertas x ON x.oferta_id = o.id WHERE l.usuario_id = ${uid} AND l.situacao = 1 AND o.ano = 2026 AND o.encerrada_em IS NULL`);

    /* 2. O meu dia */
    const prof = await entrar(navegador, 'prof.laura.demo', senhaProf);
    await prof.p.goto(L.BASE + 'painel/home/index');
    const linhas = await cartao(prof.p);
    await L.print(prof.p, ETAPA, 'pagina-inicial-professora');
    const destinos = {};
    /* D-101 (B8): o atalho da frequência é "Chamada" ou, com a falta lançada por etapa, "Faltas" (para as etapas) */
    for (const [cls, rota] of [['vc-dia-chamada', 'chamada/listar/'], ['vc-dia-faltas', 'escola-notas/etapas/'], ['vc-dia-notas', 'atividades/listar/'], ['vc-dia-etapas', 'escola-notas/etapas/']]) {
      const i = linhas.findIndex((x) => x[{ 'vc-dia-chamada': 'chamada', 'vc-dia-faltas': 'faltas', 'vc-dia-notas': 'notas', 'vc-dia-etapas': 'etapas' }[cls]]);
      if (i < 0) { continue; }
      await prof.p.goto(L.BASE + 'painel/home/index');
      await Promise.all([prof.p.waitForNavigation(), prof.p.click(`#diaProfessor tbody tr >> nth=${i} >> .${cls}`)]);
      destinos[cls] = prof.p.url().includes(rota + linhas[i].oferta) && !(await prof.p.$('#semAcesso')) && !(await prof.p.$('input[name="SendLogin"]'));
    }
    L.ok(linhas.length === dela && dela > 0 && linhas.every((x) => (x.chamada !== x.faltas) && x.notas && x.etapas) && Object.values(destinos).every(Boolean),
      `"O meu dia" na Página Inicial da professora: os ${linhas.length} componentes dela em 2026, cada um com Chamada (ou Faltas, com a falta lançada por etapa), Notas e Etapas; os três atalhos abrem a tela do componente (${JSON.stringify(destinos)})`);

    /* 3. o vocabulário */
    await prof.p.goto(L.BASE + 'painel/minhas-disciplinas/listar');
    const titulo = ((await prof.p.$eval('.page-title, h2', (e) => e.textContent).catch(() => '')) || '').trim();
    const cols = await prof.p.$$eval('#tabelaDisciplinas thead th', (l) => l.map((t) => t.textContent.trim()));
    const semComponente = !/omponente/.test(await prof.p.textContent('.page-body'));
    const abrir = await prof.p.$eval('#tabelaDisciplinas tbody tr .btn-acao[title], #tabelaDisciplinas tbody tr a[title]', (a) => a.getAttribute('title')).catch(() => '');
    await prof.p.click('#tabelaDisciplinas tbody tr >> nth=0 >> a[href*="disciplinas/visualizar/"] >> nth=0');
    await prof.p.waitForLoadState();
    const primeiraAba = ((await prof.p.$eval('.vc-abas-lista .nav-item:first-child', (e) => e.textContent).catch(() => '')) || '').trim();
    const semComponenteNaDisciplina = !/omponente curricular/.test(await prof.p.textContent('.page-body'));
    await prof.ctx.close();
    L.ok(/Minhas disciplinas/i.test(titulo) && cols.includes('Segmento') && !cols.includes('Curso') && /Abrir a disciplina/.test(abrir) && primeiraAba === 'Disciplina' && semComponente && semComponenteNaDisciplina,
      `o vocabulário da escola (D-85: "disciplina", e o segmento no lugar do curso): "${titulo}"; as colunas ${cols.slice(1, 3).join(' e ')}; "${abrir}"; a primeira aba "${primeiraAba}"; nenhum "componente" na tela`);

    /* 4. a secretaria não vê o cartão */
    const sec = await entrar(navegador, 'secretaria.demo', (cred.painel || {})['secretaria.demo'] || '');
    await sec.p.goto(L.BASE + 'painel/home/index');
    const naSec = !!(await sec.p.$('#diaProfessor'));
    await sec.ctx.close();
    L.ok(!naSec, 'a secretaria (que não é professora) não vê "O meu dia"');

    /* 5. o portal do responsável: o Resumo com dados de verdade, a turma nele, o horário em cartões por dia com filtro */
    const H = 'helena.ribeiro@exemplo.invalid';
    const filho = um(`SELECT ra.aluno_id FROM esc_responsavel_alunos ra JOIN col_usuarios u ON u.id = ra.aluno_id AND u.rm LIKE '265%' JOIN esc_responsaveis r ON r.id = ra.responsavel_id WHERE r.email = '${H}' ORDER BY ra.aluno_id LIMIT 1`);
    const fctx = await navegador.newContext({ viewport: { width: 390, height: 844 }, locale: 'pt-BR' });
    const fp = await L.pagina(fctx);
    await fp.goto(L.BASE + 'portal/login/acesso');
    await fp.fill('input[name="usuario"]', H);
    await fp.fill('input[name="senha"]', (cred.familia || {})[H] || '');
    await Promise.all([fp.waitForNavigation(), fp.click('[name="SendLogin"]')]);
    await fp.goto(L.BASE + `portal/familia/filho/${filho}?ano=2026`);
    const resumo = await fp.evaluate(() => ({
      media: (document.querySelector('#mediaGeral') || {}).textContent || '', legenda: (document.querySelector('#legendaMedia') || {}).textContent || '',
      turma: (document.querySelector('#dadosTurma') || {}).textContent || '', faltas: (document.querySelector('#faltasAno') || {}).textContent || '',
      linhas: [...document.querySelectorAll('#componentesTurma tbody tr')].map((t) => [...t.cells].map((c) => c.textContent.trim())),
      abaTurma: [...document.querySelectorAll('#cabecalhoFilho a, #atalhosFilho a')].some((a) => /familia\/turma\//.test(a.getAttribute('href') || '')),
      componente: /omponente/.test(document.querySelector('.page-body').textContent),
    }));
    await L.print(fp, ETAPA, 'portal-resumo-do-filho');
    const profsDistintos = new Set(resumo.linhas.map((l) => l[1]).filter((x) => x && x !== '—')).size;
    /* D-100: a demonstração não libera a média final do ano corrente — o Resumo não mostra média anual, e diz quando aparece */
    L.ok(resumo.media.trim() === '—' && /quando a escola liberar/.test(resumo.legenda) && /Turno/.test(resumo.turma) && /Sala/.test(resumo.turma)
      && resumo.linhas.length >= 9 && profsDistintos === resumo.linhas.length && /^\d+$/.test(resumo.faltas.trim()) && !resumo.abaTurma && !resumo.componente,
      `o Resumo do filho com dados de verdade: a média ${resumo.media.trim()} ("${resumo.legenda.trim()}"), ${resumo.faltas.trim()} falta(s), a turma (o turno e a sala), ${resumo.linhas.length} disciplinas com ${profsDistintos} professores diferentes; sem o botão "Turma"; nenhum "componente" na tela`);
    await fp.goto(L.BASE + `portal/familia/turma/${filho}?ano=2026`);
    const redireciona = new RegExp(`familia/filho/${filho}`).test(fp.url());
    await fp.goto(L.BASE + `portal/familia/horario/${filho}?ano=2026`);
    const cartoes = await fp.$$eval('#semanaHorario .vc-dia-semana', (l) => l.length);
    await fp.selectOption('#filtroSemana', '3');
    const soTerca = await fp.$$eval('#semanaHorario .vc-dia-semana', (l) => l.filter((c) => !c.hidden).map((c) => c.dataset.dia));
    await fp.selectOption('#filtroSemana', 'semana');
    const todos = await fp.$$eval('#semanaHorario .vc-dia-semana', (l) => l.filter((c) => !c.hidden).length);
    const largo = await fp.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1);
    await L.print(fp, ETAPA, 'portal-horario-cartoes');
    await fctx.close();
    L.ok(redireciona && cartoes === 5 && soTerca.join() === '3' && todos === 5 && largo,
      `o endereço antigo da turma leva ao Resumo (${redireciona}); o horário em cartões por dia (${cartoes}), o filtro mostra só a terça (${soTerca.join()}) ou a semana (${todos}); sem rolagem lateral no celular`);

    /* sabotagem: o cartão sem os componentes */
    const orig = fs.readFileSync(CARTAO, 'utf8');
    const trecho = '$compsDia = $leDia->getResultado() ?: [];';
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let s = null;
    try {
      fs.writeFileSync(CARTAO, orig.replace(trecho, '$compsDia = [];'));
      const ps = await entrar(navegador, 'prof.laura.demo', senhaProf);
      await ps.p.goto(L.BASE + 'painel/home/index');
      s = await cartao(ps.p);
      await ps.ctx.close();
    } finally {
      fs.writeFileSync(CARTAO, orig);
    }
    L.ok(s && s.length === 0, 'sabotagem (o cartão sem os componentes): a prova reprova — "O meu dia" some');
    L.ok(sha(CARTAO) === shaCartao, 'o arquivo sabotado voltou igual (sha256)');
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(CARTAO) !== shaCartao) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
