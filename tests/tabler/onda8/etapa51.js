/*
 * PROVA DA 1.102.1, C5 — o professor entra no Moodle também na escola (D-126). APAGA o banco de prova (a escola do zero,
 * com a demonstração pela tela). Usa o Moodle de TESTE (VCOSTAEDU_MOODLE_URL e VCOSTAEDU_MOODLE_CHAVE): os usuários de
 * prova ficam suspensos no fim.
 *   1. sem o Moodle, a Página Inicial do professor da escola não mostra o cartão do Moodle (nada de botão sem destino);
 *   2. o Moodle de teste conectado pela tela e "Enviar tudo o que falta": o professor criado e inscrito; o cartão do
 *      Moodle na Página Inicial dele, com "Entrar no Moodle" e "Abrir no Moodle" nas disciplinas enviadas; o servidor pede
 *      o link e redireciona para o Moodle (303), sem segunda senha;
 *   sabotagem: o cartão de volta só no curso livre — o botão some na escola: a prova reprova; o arquivo volta igual.
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE, VCOSTAEDU_MOODLE_URL, VCOSTAEDU_MOODLE_CHAVE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { spawnSync } = require('child_process');
const L = require('../lib');
const X = require('./_escola');

const ETAPA = 'onda8/etapa51';
const PHP = process.env.VCOSTAEDU_PHP || 'C:/xampp/php/php.exe';
const SENHA_ADM = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const MURL = (process.env.VCOSTAEDU_MOODLE_URL || '').replace(/\/+$/, '');
const MCHAVE = process.env.VCOSTAEDU_MOODLE_CHAVE || '';
const CARTAO = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Views', 'home', '_livreProfessor.php');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const { sql, um } = X;
const cli = (...args) => {
  const r = spawnSync(PHP, [path.join(L.RAIZ, 'tests', 'tabler', 'onda3', 'moodle_cli.php'), ...args], { cwd: L.RAIZ, encoding: 'utf8', env: process.env });
  try { return JSON.parse((r.stdout || '').trim().split('\n').pop()); } catch (e) { return {}; }
};
async function print(p, nome) {
  await p.evaluate(() => { const u = document.querySelector('#url'); if (u) { u.value = 'https://moodle.exemplo.invalid'; } });
  await L.print(p, ETAPA, nome);
}

(async () => {
  const { navegador } = await L.abrir();
  const shaC = sha(CARTAO);
  let moodleUsado = false;
  try {
    if (!MURL || !MCHAVE) { throw new Error('sem o Moodle de teste (VCOSTAEDU_MOODLE_URL e VCOSTAEDU_MOODLE_CHAVE)'); }
    L.ok(X.montar('onda8-etapa51').status === 0, 'a escola instalada do zero, com a demonstração do colégio pela tela');
    const c = X.cred();
    const prof = 'prof.marcos.demo';
    const pid = um(`SELECT id FROM adms_usuarios WHERE usuario = '${prof}'`);
    let sp = await X.entrar(navegador, prof, c.painel[prof], 'painel', 1366);
    await sp.p.goto(L.BASE + 'painel/home/index');
    const semMoodle = !(await sp.p.$('#cartaoMoodleProfessor'));
    await sp.ctx.close();
    L.ok(semMoodle, '1. sem o Moodle, a Página Inicial do professor da escola não mostra o cartão do Moodle');

    /* 2. o Moodle de teste */
    moodleUsado = true;
    sql("UPDATE adms_parametros SET valor = 'prova-' WHERE chave = 'moodle.prefixo'");
    sql("UPDATE adms_parametros SET valor = 'alunos.escola.exemplo.invalid' WHERE chave = 'moodle.email_dominio'");
    const adm = await X.entrar(navegador, 'administrador', SENHA_ADM, 'painel', 1366);
    const salvar = async (campos) => {
      await adm.p.goto(L.BASE + 'painel/moodle/conexao');
      if (campos.url) { await adm.p.fill('#url', campos.url); }
      if (campos.chave) { await adm.p.fill('#chave', campos.chave); }
      for (const [n, v] of Object.entries(campos.radios || {})) { await adm.p.check(`input[name="${n}"][value="${v}"]`).catch(() => {}); }
      if (campos.categoria) { await adm.p.selectOption('#categoria_id', { label: campos.categoria }); }
      if (campos.categoriaNova) { await adm.p.fill('#categoria_nova', campos.categoriaNova); }
      if (campos.dominio && (await adm.p.$('#emailDominio'))) { await adm.p.fill('#emailDominio', campos.dominio); }
      await Promise.all([adm.p.waitForNavigation(), adm.p.click('#salvarMoodle')]);
    };
    await salvar({ url: MURL, chave: MCHAVE });
    await adm.p.goto(L.BASE + 'painel/moodle/conexao');
    await Promise.all([adm.p.waitForNavigation(), adm.p.click('#testarMoodle')]);
    const testeOk = !!(await adm.p.$('#testeOk'));
    const temCat = await adm.p.$$eval('#categoria_id option', (os) => os.some((o) => o.textContent.trim() === 'VCOSTAEDU provas')).catch(() => false);
    const radios = { disciplina_modo: 'criar', trancamento: 'suspender', professor_enviar: '1', professor_papel: 'edicao', envio: 'automatico' };
    await salvar(temCat ? { categoria: 'VCOSTAEDU provas', radios, dominio: 'alunos.escola.exemplo.invalid' } : { categoriaNova: 'VCOSTAEDU provas', radios, dominio: 'alunos.escola.exemplo.invalid' });
    const ras = sql('SELECT rm FROM col_usuarios WHERE col_niveis_acesso_id = 1').split('\n').filter(Boolean);
    cli('preparar', ...ras.map((r) => 'prova-' + r), ...sql('SELECT id FROM adms_usuarios').split('\n').map((i) => 'prova-prof-' + i));
    await adm.p.goto(L.BASE + 'painel/moodle-envios/listar');
    await adm.p.click('#enviarTudo');
    await adm.p.waitForSelector('#progressoEnvios[data-situacao="concluida"], #progressoEnvios[data-situacao="falhou"]', { timeout: 600000 });
    await print(adm.p, 'enviar-tudo');
    await adm.ctx.close();
    const profMoodle = um(`SELECT IFNULL(MAX(moodle_id), 0) FROM adms_moodle_vinculos WHERE tipo = 'professor' AND local_id = ${pid}`);
    const cursos = cli('chamar', 'core_enrol_get_users_courses', JSON.stringify({ userid: profMoodle }));
    const nCursos = Array.isArray(cursos.dados) ? cursos.dados.length : 0;

    sp = await X.entrar(navegador, prof, c.painel[prof], 'painel', 1366);
    await sp.p.goto(L.BASE + 'painel/home/index');
    const botao = await sp.p.getAttribute('#cartaoMoodleProfessor #entrarMoodleProfessor', 'href').catch(() => null);
    const abrir = (await sp.p.$$('#cartaoMoodleProfessor .vc-abrir-moodle-prof')).length;
    await L.print(sp.p, ETAPA, 'escola-professor-com-moodle');
    let ida = { st: 0, host: '' };
    if (botao) {
      const r = await sp.p.request.get(botao, { maxRedirects: 0 });
      const loc = r.headers()['location'] || '';
      ida = { st: r.status(), host: loc ? new URL(loc).host : '' };
    }
    L.ok(testeOk && profMoodle > 0 && nCursos > 0 && !!botao && abrir > 0 && ida.st === 303 && ida.host === new URL(MURL).host,
      `2. com o Moodle de teste: o professor da escola inscrito em ${nCursos} curso(s); o cartão do Moodle na Página Inicial (${abrir} disciplina(s) para abrir); o redirecionamento ${ida.st} para o Moodle, sem segunda senha`);

    /* sabotagem: o cartão de volta só no curso livre */
    const orig = fs.readFileSync(CARTAO, 'utf8');
    const trecho = "if (!\\App\\adms\\Models\\helper\\AdmsNivel::ehPapel('professor')) {";
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let sab = null;
    try {
      fs.writeFileSync(CARTAO, orig.replace(trecho, "if (!\\App\\adms\\Models\\helper\\AdmsTipoInstituicao::ehLivre() || !\\App\\adms\\Models\\helper\\AdmsNivel::ehPapel('professor')) {"));
      await sp.p.goto(L.BASE + 'painel/home/index');
      sab = !!(await sp.p.$('#entrarMoodleProfessor'));
    } finally {
      fs.writeFileSync(CARTAO, orig);
    }
    await sp.ctx.close();
    L.ok(sab === false, 'sabotagem (o cartão só no curso livre): o botão some na escola — a prova reprova');
    L.ok(sha(CARTAO) === shaC, 'o arquivo sabotado voltou igual (sha256)');
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(CARTAO) !== shaC) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    if (moodleUsado) { const lim = cli('limpar'); console.log(`  (limpeza do Moodle de teste: ${JSON.stringify(lim)})`); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
