/*
 * PROVA DA CORREÇÃO DO ENSAIO, B2 e B3 — o curso livre com o professor e o aluno no Moodle pela Página Inicial (D-121) e
 * os ajustes pequenos (D-122). APAGA o banco de prova (instala o curso livre do zero). Usa o Moodle de TESTE
 * (VCOSTAEDU_MOODLE_URL e VCOSTAEDU_MOODLE_CHAVE): os usuários de prova ficam suspensos no fim.
 *   A. ensino superior (o banco do ciclo): Vídeos fora do menu Ajuda no painel e no portal e fora da Central de ajuda (a
 *      página continua abrindo); o texto do tipo Escola ("Ensino Fundamental 1 e 2 e Ensino Médio") no guia; Minhas
 *      Disciplinas do professor abre em Ativas, com Encerradas e Todas; o professor abre os dois relatórios novos
 *      (Pendências de lançamento e Abaixo ou perto do mínimo), só com as disciplinas dele;
 *   B. curso livre do zero, com a demonstração pela tela, SEM o Moodle: a Página Inicial do professor com as disciplinas
 *      dele e a frase do Moodle; a do aluno no portal com o curso e a frase do Moodle;
 *   C. o Moodle de teste conectado pela tela e "Enviar tudo o que falta": o professor é inscrito no curso com o papel de
 *      professor e ganha "Entrar no Moodle" na Página Inicial — o servidor pede o link e redireciona (303) para o Moodle,
 *      sem segunda senha; o aluno também;
 *   sabotagens: (a) o filtro de Minhas Disciplinas desligado — a disciplina encerrada aparece em Ativas; (b) a entrada do
 *   professor desligada — o botão some. Os arquivos voltam iguais (sha256).
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE, VCOSTAEDU_MOODLE_URL, VCOSTAEDU_MOODLE_CHAVE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { spawnSync } = require('child_process');
const L = require('../lib');
const X = require('./_escola');
const P = require('./_passeio_tipo');

const ETAPA = 'onda8/etapa48';
const PHP = process.env.VCOSTAEDU_PHP || 'C:/xampp/php/php.exe';
const SENHA_ADM = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const MURL = (process.env.VCOSTAEDU_MOODLE_URL || '').replace(/\/+$/, '');
const MCHAVE = process.env.VCOSTAEDU_MOODLE_CHAVE || '';
const FILTRO = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Controllers', 'MinhasDisciplinas.php');
const ENTRADA = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'moodle', 'AdmsMoodleProfessor.php');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const { sql, um } = X;
const texto = async (p, sel) => ((await p.textContent(sel).catch(() => '')) || '').replace(/\s+/g, ' ').trim();
const cli = (...args) => {
  const r = spawnSync(PHP, [path.join(L.RAIZ, 'tests', 'tabler', 'onda3', 'moodle_cli.php'), ...args], { cwd: L.RAIZ, encoding: 'utf8', env: process.env });
  try { return JSON.parse((r.stdout || '').trim().split('\n').pop()); } catch (e) { return {}; }
};
/* o print nunca mostra o endereço do Moodle de teste */
async function print(p, nome) {
  await p.evaluate(() => { const u = document.querySelector('#url'); if (u) { u.value = 'https://moodle.exemplo.invalid'; } });
  await L.print(p, ETAPA, nome);
}
function montar(tipo, rotulo) {
  const env = { ...process.env };
  delete env.VCOSTAEDU_RECURSOS;
  const r = spawnSync(process.execPath, [path.join(__dirname, '..', 'onda6', 'medir_demo.js'), tipo, rotulo], { encoding: 'utf8', env, maxBuffer: 1 << 24 });
  if (r.status !== 0) console.log((r.stdout || '') + (r.stderr || ''));
  return r.status;
}
/** As linhas de Minhas Disciplinas, com a situação de cada uma. */
async function minhas(p, situacao) {
  await p.goto(L.BASE + 'painel/minhas-disciplinas/listar' + (situacao ? '?situacao=' + situacao : ''));
  return p.$$eval('#tabelaDisciplinas tbody tr[data-oferta]', (l) => l.map((tr) => ({ id: tr.getAttribute('data-oferta'), encerrada: /Encerrada/.test(tr.textContent) })));
}

(async () => {
  const { navegador } = await L.abrir();
  const shaF = sha(FILTRO);
  const shaE = sha(ENTRADA);
  let moodleUsado = false;
  try {
    /* ===== A. o ensino superior: os ajustes pequenos ===== */
    L.ok(sql("SELECT valor FROM adms_instalacao_estado WHERE chave = 'instituicao.tipo'") === 'superior', 'A. o banco do ciclo é de ensino superior, com a demonstração da faculdade');
    const credS = JSON.parse(fs.readFileSync(path.join(L.RAIZ, 'tests', 'tabler', 'saida', 'credenciais.json'), 'utf8'));
    const adm = await X.entrar(navegador, 'administrador', SENHA_ADM, 'painel', 1366);
    await adm.p.goto(L.BASE + 'painel/home/index');
    const menuAdm = (await P.itensDoMenu(adm.p)).map((x) => x.rotulo);
    await adm.p.goto(L.BASE + 'painel/ajuda/index');
    const linkVideos = !!(await adm.p.$('#linkVideos'));
    const central = !(await adm.p.$('#semAcesso'));
    await adm.p.goto(L.BASE + 'painel/ajuda/videos');
    const paginaVideos = !(await adm.p.$('#semAcesso')) && !(await adm.p.$('[name="SendLogin"]'));
    await adm.p.goto(L.BASE + 'painel/ajuda/guia/o-tipo-de-instituicao');
    const guiaTipo = await texto(adm.p, 'body');
    await adm.p.goto(L.BASE + 'painel/demonstracao/instituicao');
    const comecar = await texto(adm.p, 'body');
    await adm.ctx.close();
    const alunoS = Object.keys(credS.portal)[0];
    const al = await X.entrar(navegador, alunoS, credS.portal[alunoS], 'portal', 1366);
    const menuAluno = (await P.itensDoMenu(al.p)).map((x) => x.rotulo);
    await al.ctx.close();
    L.ok(!menuAdm.includes('Vídeos') && !menuAluno.includes('Vídeos') && !linkVideos && central && paginaVideos,
      `Vídeos fora do menu Ajuda (painel e portal) e fora da Central de ajuda; a Central continua; a página de vídeos continua abrindo pelo endereço`);
    const NOVO = 'Ensino Fundamental 1 e 2 e Ensino Médio';
    L.ok(guiaTipo.includes(NOVO) && !guiaTipo.includes('Ensino Fundamental (anos finais) e Ensino Médio') && (!/Escola \(educação básica\)/.test(comecar) || comecar.includes(NOVO)),
      `o tipo Escola diz "${NOVO}" no guia "O tipo de instituição"${/Escola \(educação básica\)/.test(comecar) ? ' e na tela "Começar com os dados da minha instituição"' : ''}`);

    /* Minhas Disciplinas: Ativas, Encerradas e Todas (na demonstração da faculdade, quase todas as disciplinas da professora
       estão encerradas: uma delas é reaberta para a prova, para haver das duas situações) */
    const prof = um("SELECT id FROM adms_usuarios WHERE usuario = 'prof.laura.demo'");
    /* a disciplina reaberta é uma das que a própria tela mostra em Encerradas (o recorte do ano é o da tela) */
    const pr = await X.entrar(navegador, 'prof.laura.demo', credS.painel['prof.laura.demo'], 'painel', 1366);
    const antes = await minhas(pr.p, 'encerradas');
    const reaberta = antes.length ? +antes[0].id : 0;
    if (!reaberta) { throw new Error('a professora da demonstração não tem disciplina encerrada na lista'); }
    sql(`UPDATE rm_professores_materias SET encerrada_em = NULL WHERE id = ${reaberta}`);
    const ativas = await minhas(pr.p, '');
    const botaoAtivo = await pr.p.getAttribute('#filtroSituacao .active', 'data-situacao').catch(() => '');
    await L.print(pr.p, ETAPA, 'minhas-ativas');
    const encerradas = await minhas(pr.p, 'encerradas');
    const todas = await minhas(pr.p, 'todas');
    L.ok(ativas.length > 0 && botaoAtivo === 'ativas' && ativas.every((x) => !x.encerrada) && encerradas.length > 0 && encerradas.every((x) => x.encerrada)
      && ativas.some((x) => +x.id === reaberta) && todas.length === ativas.length + encerradas.length,
      `Minhas Disciplinas abre em Ativas (${ativas.length}), com Encerradas (${encerradas.length}) e Todas (${todas.length})`);

    /* os dois relatórios novos do professor, só com as disciplinas dele */
    await pr.p.goto(L.BASE + 'painel/relatorios/listar');
    const catalogo = await texto(pr.p, 'body');
    const abre = async (rota) => { await pr.p.goto(L.BASE + 'painel/relatorios/' + rota); return { ok: !(await pr.p.$('#semAcesso')), t: await texto(pr.p, 'body') }; };
    const pend = await abre('pendencias-lancamento');
    await L.print(pr.p, ETAPA, 'pendencias-professor');
    const freq = await abre('frequencia-minimo');
    const abertasDele = um(`SELECT COUNT(DISTINCT o.id) FROM rm_professores_mat_liga l JOIN rm_professores_materias o ON o.id = l.rm_prof_mat_id WHERE l.usuario_id = ${prof} AND l.situacao = 1 AND o.encerrada_em IS NULL`);
    const abertasTodas = um('SELECT COUNT(*) FROM rm_professores_materias WHERE encerrada_em IS NULL');
    const mostradas = +((pend.t.match(/Ofertas abertas\s*(\d+)/) || pend.t.match(/(\d+)\s*Ofertas abertas/) || [])[1] ?? -1);
    L.ok(/Pendências de lançamento/.test(catalogo) && /Abaixo ou perto do mínimo/.test(catalogo) && pend.ok && freq.ok && abertasDele > 0 && mostradas === abertasDele && abertasDele < abertasTodas,
      `o professor abre "Pendências de lançamento" e "Abaixo ou perto do mínimo", só com as disciplinas dele (${mostradas} oferta(s) aberta(s) dele, de ${abertasTodas} na faculdade)`);

    /* sabotagem (a): o filtro desligado */
    const origF = fs.readFileSync(FILTRO, 'utf8');
    const trechoF = "if ($situacao !== 'todas') {";
    if (!origF.includes(trechoF)) { throw new Error('a sabotagem (a) não achou o trecho'); }
    let sabA = [];
    try {
      fs.writeFileSync(FILTRO, origF.replace(trechoF, 'if (false) {'));
      sabA = await minhas(pr.p, '');
    } finally {
      fs.writeFileSync(FILTRO, origF);
    }
    await pr.ctx.close();
    L.ok(sabA.some((x) => x.encerrada), `sabotagem (a) (o filtro desligado): a disciplina encerrada aparece em Ativas — a prova reprova`);
    L.ok(sha(FILTRO) === shaF, 'o arquivo sabotado (a) voltou igual (sha256)');

    /* ===== B. o curso livre, sem o Moodle ===== */
    L.ok(montar('livre', 'onda8-etapa48') === 0, 'B. o curso livre instalado do zero, com a demonstração pela tela');
    const cL = X.cred();
    const profL = 'prof.marcos.demo';
    const profLid = um(`SELECT id FROM adms_usuarios WHERE usuario = '${profL}'`);
    const dele = um(`SELECT COUNT(DISTINCT o.id) FROM rm_professores_mat_liga l JOIN rm_professores_materias o ON o.id = l.rm_prof_mat_id WHERE l.usuario_id = ${profLid} AND l.acesso = 1 AND l.situacao = 1 AND o.encerrada_em IS NULL`);
    let sp = await X.entrar(navegador, profL, cL.painel[profL], 'painel', 1366);
    await sp.p.goto(L.BASE + 'painel/home/index');
    const linhasProf = (await sp.p.$$('#disciplinasProfessorLivre [data-oferta]')).length;
    const fraseProf = !!(await sp.p.$('#moodleProfessorIndisponivel'));
    await L.print(sp.p, ETAPA, 'livre-professor-sem-moodle');
    await sp.ctx.close();
    L.ok(dele > 0 && linhasProf === dele && fraseProf, `curso livre: a Página Inicial do professor mostra as disciplinas dele (${linhasProf} de ${dele}) e diz quando o Moodle aparece`);
    const raL = Object.keys(cL.portal)[0];
    let sa = await X.entrar(navegador, raL, cL.portal[raL], 'portal', 1366);
    await sa.p.goto(L.BASE + 'portal/home/index');
    const cursoAluno = await texto(sa.p, '#cartaoCursoLivre');
    const fraseAluno = !!(await sa.p.$('#moodleAlunoIndisponivel'));
    await L.print(sa.p, ETAPA, 'livre-aluno-sem-moodle');
    await sa.ctx.close();
    L.ok(/IA na prática para professores/.test(cursoAluno) && /Turma/.test(cursoAluno) && fraseAluno, `curso livre: a Página Inicial do aluno mostra o curso ("${cursoAluno.slice(0, 90)}") e diz quando o Moodle aparece`);

    /* ===== C. o Moodle de teste ===== */
    if (!MURL || !MCHAVE) { throw new Error('sem o Moodle de teste (VCOSTAEDU_MOODLE_URL e VCOSTAEDU_MOODLE_CHAVE)'); }
    moodleUsado = true;
    sql("UPDATE adms_parametros SET valor = 'prova-' WHERE chave = 'moodle.prefixo'");
    const a2 = await X.entrar(navegador, 'administrador', SENHA_ADM, 'painel', 1366);
    const salvar = async (campos) => {
      await a2.p.goto(L.BASE + 'painel/moodle/conexao');
      if (campos.url) { await a2.p.fill('#url', campos.url); }
      if (campos.chave) { await a2.p.fill('#chave', campos.chave); }
      for (const [n, v] of Object.entries(campos.radios || {})) { await a2.p.check(`input[name="${n}"][value="${v}"]`).catch(() => {}); }
      if (campos.categoria) { await a2.p.selectOption('#categoria_id', { label: campos.categoria }); }
      if (campos.categoriaNova) { await a2.p.fill('#categoria_nova', campos.categoriaNova); }
      await Promise.all([a2.p.waitForNavigation(), a2.p.click('#salvarMoodle')]);
    };
    await salvar({ url: MURL, chave: MCHAVE });
    await a2.p.goto(L.BASE + 'painel/moodle/conexao');
    await Promise.all([a2.p.waitForNavigation(), a2.p.click('#testarMoodle')]);
    const testeOk = !!(await a2.p.$('#testeOk'));
    const temCat = await a2.p.$$eval('#categoria_id option', (os) => os.some((o) => o.textContent.trim() === 'VCOSTAEDU provas')).catch(() => false);
    const radios = { disciplina_modo: 'criar', trancamento: 'suspender', professor_enviar: '1', professor_papel: 'edicao', envio: 'automatico' };
    await salvar(temCat ? { categoria: 'VCOSTAEDU provas', radios } : { categoriaNova: 'VCOSTAEDU provas', radios });
    const ras = sql('SELECT rm FROM col_usuarios WHERE col_niveis_acesso_id = 1').split('\n').filter(Boolean);
    cli('preparar', ...ras.map((r) => 'prova-' + r), ...sql('SELECT id FROM adms_usuarios').split('\n').map((i) => 'prova-prof-' + i));
    await a2.p.goto(L.BASE + 'painel/moodle-envios/listar');
    await a2.p.click('#enviarTudo');
    await a2.p.waitForSelector('#progressoEnvios[data-situacao="concluida"], #progressoEnvios[data-situacao="falhou"]', { timeout: 300000 });
    const fim = await a2.p.getAttribute('#progressoEnvios', 'data-situacao');
    await print(a2.p, 'enviar-tudo');
    await a2.ctx.close();
    const profMoodle = um(`SELECT IFNULL(MAX(moodle_id), 0) FROM adms_moodle_vinculos WHERE tipo = 'professor' AND local_id = ${profLid}`);
    const usuarioProf = sql(`SELECT IFNULL(MAX(usuario), '') FROM adms_moodle_vinculos WHERE tipo = 'professor' AND local_id = ${profLid}`);
    const cursos = cli('chamar', 'core_enrol_get_users_courses', JSON.stringify({ userid: profMoodle }));
    const nCursos = Array.isArray(cursos.dados) ? cursos.dados.length : (Array.isArray(cursos) ? cursos.length : 0);
    L.ok(testeOk && fim === 'concluida' && profMoodle > 0 && usuarioProf !== '' && nCursos > 0,
      `o Moodle de teste conectado pela tela e "Enviar tudo": o professor criado no Moodle (o nome de usuário no vínculo) e inscrito em ${nCursos} curso(s) da disciplina`);

    sp = await X.entrar(navegador, profL, cL.painel[profL], 'painel', 1366);
    await sp.p.goto(L.BASE + 'painel/home/index');
    const botao = await sp.p.getAttribute('#entrarMoodleProfessor', 'href').catch(() => null);
    const abrir = (await sp.p.$$('.vc-abrir-moodle-prof')).length;
    await L.print(sp.p, ETAPA, 'livre-professor-com-moodle');
    let ida = { st: 0, host: '' };
    if (botao) {
      const r = await sp.p.request.get(botao, { maxRedirects: 0 });
      const loc = r.headers()['location'] || '';
      ida = { st: r.status(), host: loc ? new URL(loc).host : '' };
    }
    L.ok(!!botao && abrir > 0 && ida.st === 303 && ida.host === new URL(MURL).host,
      `o professor ganha "Entrar no Moodle" e "Abrir no Moodle" (${abrir}) na Página Inicial; o servidor pede o link e redireciona para o Moodle (${ida.st}), sem segunda senha`);

    /* sabotagem (b): a entrada do professor desligada */
    const origE = fs.readFileSync(ENTRADA, 'utf8');
    const trechoE = "return ['entrar' => true, 'disciplinas' => $enviadas];";
    if (!origE.includes(trechoE)) { throw new Error('a sabotagem (b) não achou o trecho'); }
    let sabB = null;
    try {
      fs.writeFileSync(ENTRADA, origE.replace(trechoE, "return ['entrar' => false, 'disciplinas' => $enviadas];"));
      await sp.p.goto(L.BASE + 'painel/home/index');
      sabB = !!(await sp.p.$('#entrarMoodleProfessor'));
    } finally {
      fs.writeFileSync(ENTRADA, origE);
    }
    await sp.ctx.close();
    L.ok(sabB === false, 'sabotagem (b) (a entrada do professor desligada): o botão some — a prova reprova');
    L.ok(sha(ENTRADA) === shaE, 'o arquivo sabotado (b) voltou igual (sha256)');

    sa = await X.entrar(navegador, raL, cL.portal[raL], 'portal', 1366);
    await sa.p.goto(L.BASE + 'portal/home/index');
    const botaoAluno = !!(await sa.p.$('#entrarMoodle'));
    await L.print(sa.p, ETAPA, 'livre-aluno-com-moodle');
    await sa.ctx.close();
    L.ok(botaoAluno, 'o aluno do curso livre também tem "Entrar no Moodle" na Página Inicial, ao lado do curso');
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(FILTRO) !== shaF) { console.log('  [AVISO] o arquivo sabotado (a) não voltou igual'); }
    if (sha(ENTRADA) !== shaE) { console.log('  [AVISO] o arquivo sabotado (b) não voltou igual'); }
    if (moodleUsado) { const lim = cli('limpar'); console.log(`  (limpeza do Moodle de teste: ${JSON.stringify(lim)})`); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
