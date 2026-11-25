/*
 * ONDA 3, ETAPA 9 — o limite por chave da API e o portal de matrícula de exemplo.
 *   A. o limite: cada chave, assunto por assunto (consultar ou gravar); o padrão mais restrito (nada marcado, a criação é
 *      recusada); a recusa clara (403 com o que a chave pode); a tela e a documentação mostram o limite; as chaves que
 *      já existiam continuam fazendo o que faziam;
 *   B. o exemplo (exemplos/portal-matricula, fora do produto): uma inscrição faz o aluno aparecer no painel, matriculado
 *      e enturmado, chegar ao Moodle e entrar na disciplina pelo portal sem senha do Moodle; a chave nunca no navegador;
 *   C. o exemplo com uma chave sem o limite certo: a recusa aparece no andamento;
 *   D. o instalador, a verificação do ambiente e o pacote não dependem da pasta;
 *   Sabotagem: o limite deixa de ser conferido.
 *
 *   node tests/tabler/onda3/etapa9.js   (o Moodle de teste em VCOSTAEDU_MOODLE_URL/VCOSTAEDU_MOODLE_CHAVE, ou
 *                                        VCOSTAEDU_MOODLE_FALSO=1 para o servidor falso)
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { execFileSync, spawnSync, spawn } = require('child_process');
const L = require('../lib');

const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste$/.test(BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
const MODO_FALSO = process.env.VCOSTAEDU_MOODLE_FALSO === '1';
const PORTA = 8766;
const MURL = MODO_FALSO ? `http://127.0.0.1:${PORTA}/contrato` : (process.env.VCOSTAEDU_MOODLE_URL || '').trim();
const MCHAVE = MODO_FALSO ? crypto.randomBytes(16).toString('hex') : (process.env.VCOSTAEDU_MOODLE_CHAVE || '').trim();
if (!MURL || !MCHAVE) { console.error('Defina VCOSTAEDU_MOODLE_URL e VCOSTAEDU_MOODLE_CHAVE, ou VCOSTAEDU_MOODLE_FALSO=1'); process.exit(2); }
const MYSQL = process.env.VCOSTAEDU_MYSQL || 'C:/xampp/mysql/bin/mysql.exe';
const PHP = process.env.VCOSTAEDU_PHP || 'C:/xampp/php/php.exe';
const sql = (q) => execFileSync(MYSQL, ['-u', 'root', '-N', '--default-character-set=utf8mb4', BANCO, '-e', q]).toString().replace(/\r/g, '').trim();
const um = (q) => +sql(q);
const ETAPA = 'onda3/etapa9';
const SENHA = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const API = L.BASE + 'api/v1';
const EXEMPLO = L.BASE + 'exemplos/portal-matricula/';
const CONFIG_EXEMPLO = path.join(L.RAIZ, 'exemplos', 'portal-matricula', 'config.local.php');
const SERVIDOR = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'api', 'AdmsApiServidor.php');
const FALSO = path.join(L.PROVAS, ETAPA, 'moodle-falso.json');
const { cpf } = require('../onda2/planilha_alunos');
let cred = JSON.parse(fs.readFileSync(path.join(__dirname, '..', 'saida', 'credenciais.json'), 'utf8'));
const REGUA_ATUAL = L.reguaAtual(); // o retrato mais novo, de qualquer onda (lib.js)

function cli(...args) {
  const r = spawnSync(PHP, [path.join(__dirname, 'moodle_cli.php'), ...args], { cwd: L.RAIZ, encoding: 'utf8', env: process.env });
  try { return JSON.parse((r.stdout || '').trim().split('\n').pop()); } catch (e) { return {}; }
}
const moodle = (f, p = {}) => cli('chamar', f, JSON.stringify(p));
async function entrar(navegador, usuario, senha, app = 'painel') {
  const ctx = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' });
  const p = await L.pagina(ctx);
  await p.goto(L.BASE + app + '/login/acesso');
  await p.fill('input[name="usuario"]', usuario);
  await p.fill('input[name="senha"]', senha);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
  return { ctx, p };
}
async function api(metodo, rota, chave, corpo) {
  const r = await fetch(API + rota, { method: metodo, headers: { Authorization: 'Bearer ' + chave, 'Content-Type': 'application/json' }, body: corpo ? JSON.stringify(corpo) : undefined });
  let json = null; try { json = await r.json(); } catch (e) { /* não JSON */ }
  return { status: r.status, json };
}
/* cria a chave pela tela; permissoes = { alunos: 'gravar', … }; devolve a chave (ou '' com a recusa em .recusa) */
async function criarChave(p, nome, permissoes) {
  await p.goto(L.BASE + 'painel/integracoes/chaves');
  await p.fill('#nomeChave', nome);
  for (const [a, v] of Object.entries(permissoes)) { await p.check(`input[name="permissao[${a}]"][value="${v}"]`); }
  await Promise.all([p.waitForNavigation(), p.click('#criarChave')]);
  const k = await p.$eval('#textoChaveNova', (e) => e.textContent.trim()).catch(() => '');
  criarChave.recusa = k ? '' : (await p.textContent('.alert').catch(() => '')).replace(/\s+/g, ' ').trim();
  return k;
}
function configurarExemplo(chave, curso, forma) {
  const dados = JSON.stringify({ api: API, chave, instituicao: 'Faculdade Horizonte (fictícia)', cursos: [{ id: curso.id, nome: curso.nome, ano: 2026, forma_ingresso_id: forma }], ra_inicio: 970100, ra_fim: 970999 });
  const php = spawnSync(PHP, ['-r', "echo '<?php return ', var_export(json_decode(getenv('VC_CFG'), true), true), ';', PHP_EOL;"], { encoding: 'utf8', env: { ...process.env, VC_CFG: dados } }).stdout;
  fs.writeFileSync(CONFIG_EXEMPLO, php);
}
let emSabotagem = false;
async function sabotar(nome, arquivo, de, para, conferir) {
  const orig = fs.readFileSync(arquivo, 'utf8');
  let reprovou = false;
  try {
    if (!orig.includes(de)) { throw new Error('sabotagem sem o trecho: ' + nome); }
    fs.writeFileSync(arquivo, orig.replace(de, para)); emSabotagem = true;
    reprovou = !(await conferir());
  } finally { fs.writeFileSync(arquivo, orig); emSabotagem = false; }
  L.ok(reprovou && fs.readFileSync(arquivo, 'utf8') === orig, `sabotagem (${nome}): a prova reprova o defeito; o arquivo voltou`);
}

(async () => {
  if (sql("SELECT valor FROM adms_instalacao_estado WHERE chave = 'demonstracao.ativa'") !== '1') {
    const c = spawnSync(PHP, [path.join(L.RAIZ, 'tests', 'tabler', 'ciclo.php'), 'comparar', REGUA_ATUAL], { encoding: 'utf8', cwd: L.RAIZ, env: process.env, maxBuffer: 1 << 26 });
    if (c.status !== 0) { console.error('o ciclo não refez a demonstração'); process.exit(1); }
    cred = JSON.parse(fs.readFileSync(path.join(__dirname, '..', 'saida', 'credenciais.json'), 'utf8'));
  }
  const { navegador } = await L.abrir();
  fs.mkdirSync(path.join(L.PROVAS, ETAPA), { recursive: true });
  let falso = null;
  if (MODO_FALSO) {
    if (fs.existsSync(FALSO)) { fs.unlinkSync(FALSO); }
    falso = spawn(PHP, ['-S', `127.0.0.1:${PORTA}`, path.join(__dirname, 'moodle_falso.php')], { env: { ...process.env, VC_MOODLE_FALSO: FALSO, VC_MOODLE_FALSO_CHAVE: MCHAVE }, stdio: 'ignore' });
    await new Promise((ok) => setTimeout(ok, 800));
    console.log('  (o Moodle de teste não respondeu: esta rodada usa o servidor falso que responde como o contrato)');
  }
  try {
    console.log('\n== Onda 3, Etapa 9');
    const adm = await entrar(navegador, 'administrador', SENHA);

    /* ===== A. o limite por chave ===== */
    let k = await criarChave(adm.p, 'Prova sem nada', {});
    L.ok(!k && /Marque o que a chave pode fazer/.test(criarChave.recusa), `o padrão é o mais restrito: sem nada marcado, a chave não nasce ("${criarChave.recusa.slice(0, 90)}")`);
    const semGravarCadastros = await adm.p.$('input[name="permissao[cadastros]"][value="gravar"]');
    const kAlunos = await criarChave(adm.p, 'Prova só alunos', { alunos: 'gravar' });
    const linha = await adm.p.$eval('#tabelaChaves tbody tr:first-child .vc-limite-chave', (e) => e.textContent.trim()).catch(() => '');
    L.ok(!!kAlunos && !semGravarCadastros && linha === 'Alunos: consultar e gravar', `a tela das chaves mostra o limite ("${linha}"); "Cadastros de apoio" só tem consulta`);
    await L.print(adm.p, ETAPA, 'chaves-limite');
    const g = await api('GET', '/alunos?por_pagina=1', kAlunos);
    const m403 = await api('POST', '/matriculas', kAlunos, { ra: '260101', curso_id: 1, ano: 2026, forma_ingresso_id: 1, data_ingresso: '2026-02-02' });
    const c403 = await api('GET', '/cursos', kAlunos);
    const msg = m403.json && m403.json.erro ? m403.json.erro.mensagem : '';
    L.ok(g.status === 200 && m403.status === 403 && m403.json.erro.codigo === 'sem_permissao' && /não pode gravar "Matrículas"/.test(msg) && /Alunos: consultar e gravar/.test(msg) && c403.status === 403,
      `a chave só de alunos: consulta alunos (200); matricular e consultar cursos: 403, com a recusa clara ("${msg.slice(0, 120)}")`);
    /* as chaves antigas: a conversão da migration */
    const conv = fs.readFileSync(path.join(L.RAIZ, 'database', 'migrations', '0021_limite_por_chave.sql'), 'utf8').match(/UPDATE `adms_api_chaves`[\s\S]*?;/g);
    const antigas = {};
    for (const esc of ['leitura', 'escrita']) {
      const txt = 'vce_' + crypto.randomBytes(20).toString('hex');
      const hash = crypto.createHash('sha256').update(txt).digest('hex');
      sql(`INSERT INTO adms_api_chaves (nome, prefixo, hash, escopo, permissoes, situacao, usuario_id) VALUES ('Prova antiga ${esc}', '${txt.slice(0, 12)}', '${hash}', '${esc}', NULL, 1, 1)`);
      antigas[esc] = txt;
    }
    for (const u of conv) { sql(u); }
    const lLe = await api('GET', '/cursos', antigas.leitura);
    const lGrava = await api('POST', '/alunos', antigas.leitura, { ra: '970050', nome: 'Prova Antiga Exemplo', sexo: 'outros' });
    const eGrava = await api('POST', '/alunos', antigas.escrita, { ra: '970051', nome: 'Prova Antiga Escrita Exemplo', sexo: 'outros', email: 'prova.antiga@exemplo.invalid', cpf: cpf(970051) });
    L.ok(conv.length === 2 && lLe.status === 200 && lGrava.status === 403 && [200, 201].includes(eGrava.status),
      'as chaves que já existiam continuam fazendo o que faziam: a de leitura consulta e não grava; a de escrita grava (a conversão da migration 0021)');
    /* a documentação mostra o limite */
    const spec = await adm.p.evaluate(async (u) => (await fetch(u, { credentials: 'same-origin' })).json(), L.BASE + 'painel/integracoes/especificacao');
    const ops = Object.values(spec.paths || {}).flatMap((x) => Object.values(x));
    /* Onda 4: as rotas da escola (x-tipo) também levam o assunto; as do produto de sempre eram 20, e a Onda 8 (D-70) somou a inscrição numa chamada só e o primeiro acesso: 22 */
    L.ok(ops.filter((o) => !o['x-tipo']).length === 22 && ops.every((o) => ['alunos', 'matriculas', 'enturmacao', 'cadastros'].includes(o['x-assunto']) && /Limite da chave:/.test(o.description || '')) && /O limite da chave/.test(spec.info.description),
      `a documentação da API mostra o limite: as ${ops.length} operações dizem o assunto e o que a chave precisa`);
    await sabotar('o limite deixa de ser conferido', SERVIDOR, 'if (!AdmsApiChaves::pode($chave, $rota[4], $rota[3])) {', 'if (false) {',
      async () => (await api('GET', '/cursos', kAlunos)).status === 403);

    /* ===== B. o exemplo ===== */
    /* o Moodle conectado (o envio vai sozinho) */
    sql("UPDATE adms_parametros SET valor = 'prova-' WHERE chave = 'moodle.prefixo'");
    await adm.p.goto(L.BASE + 'painel/moodle/conexao');
    await adm.p.fill('#url', MURL); await adm.p.fill('#chave', MCHAVE);
    await Promise.all([adm.p.waitForNavigation(), adm.p.click('#salvarMoodle')]);
    await Promise.all([adm.p.waitForNavigation(), adm.p.click('#testarMoodle')]);
    const temCat = await adm.p.$$eval('#categoria_id option', (os) => os.some((o) => o.textContent.trim() === 'VCOSTAEDU provas'));
    if (temCat) { await adm.p.selectOption('#categoria_id', { label: 'VCOSTAEDU provas' }); } else { await adm.p.fill('#categoria_nova', 'VCOSTAEDU provas'); }
    await Promise.all([adm.p.waitForNavigation(), adm.p.click('#salvarMoodle')]);
    L.ok(um('SELECT COUNT(*) FROM adms_moodle_conexao WHERE testada_em IS NOT NULL AND categoria_id > 0') === 1, `o Moodle ${MODO_FALSO ? 'FALSO' : 'de teste'} conectado pela tela, para o exemplo ver o aluno chegar lá`);
    const kExemplo = await criarChave(adm.p, 'Portal de matrícula (exemplo)', { alunos: 'gravar', matriculas: 'gravar', enturmacao: 'gravar' });
    const curso = { id: 1, nome: sql('SELECT nome FROM rm_cursos WHERE id = 1') };
    const forma = um('SELECT id FROM rm_matricula_tipos WHERE situacao = 1 ORDER BY id LIMIT 1');
    configurarExemplo(kExemplo, curso, forma);
    const vis = await navegador.newContext({ viewport: { width: 390, height: 844 }, locale: 'pt-BR' });
    const pe = await L.pagina(vis);
    const pedidos = []; const corpos = [];
    pe.on('request', (q) => pedidos.push(q.url()));
    pe.on('response', async (r) => { try { if (/text\/html/.test(r.headers()['content-type'] || '')) { corpos.push(await r.text()); } } catch (e) { /* já foi */ } });
    await pe.goto(EXEMPLO);
    const aviso = await pe.textContent('#avisoExemplo').catch(() => '');
    await pe.fill('#nome', 'Aluna Exemplo do Portal');
    await pe.fill('#email', `prova.portal.${Date.now()}@exemplo.invalid`);
    await pe.fill('#cpf', cpf(970777));
    await pe.selectOption('#sexo', 'feminino');
    await L.print(pe, ETAPA, 'exemplo-inscricao');
    await Promise.all([pe.waitForNavigation({ timeout: 60000 }), pe.click('#enviarInscricao')]);
    const ra = (await pe.textContent('#raInscricao').catch(() => '')).trim();
    const passos = await pe.$$eval('#andamento li', (ls) => ls.map((l) => [l.id, l.dataset.situacao]));
    await L.print(pe, ETAPA, 'exemplo-andamento');
    const aid = um(`SELECT IFNULL(MAX(id), 0) FROM col_usuarios WHERE rm = '${ra || 0}'`);
    const ent = um(`SELECT COUNT(*) FROM portal_disciplinas_alunos WHERE rm = '${ra || 0}'`);
    const mat = um(`SELECT COUNT(*) FROM rm_matriculas_curso WHERE usuario_id = ${aid} AND situacao_id = 1`);
    const todos = Object.fromEntries(passos);
    L.ok(/EXEMPLO/.test(aviso) && /Cada instituição tem o seu/.test(aviso) && /^97\d{4}$/.test(ra) && aid > 0 && mat === 1 && ent >= 1
      && ['passoAluno', 'passoMatricula', 'passoEnturmacao', 'passoMoodle', 'passoAcesso'].every((x) => todos[x] === 'feito'),
      `uma inscrição pelo exemplo (com o aviso de que é um exemplo): o aluno RA ${ra} no painel, matriculado e enturmado em ${ent} disciplina(s), enviado ao Moodle, acesso pronto — o andamento: ${passos.map((x) => x.join('=')).join(', ')}`);
    const naoVazou = !corpos.some((t) => t.includes(kExemplo)) && !pedidos.some((u) => /api\/v1/.test(u));
    L.ok(naoVazou, `a chave nunca vai para o navegador: nenhuma das ${corpos.length} páginas do exemplo a contém, e o navegador não chama a API (as chamadas saem do servidor do exemplo)`);
    await adm.p.goto(L.BASE + `painel/academicos/visualizar/${aid}`);
    L.ok(/Aluna Exemplo do Portal/.test(await adm.p.textContent('body')), 'o aluno aparece no painel (a ficha dele)');
    /* o portal: o aluno entra no Moodle sem a senha do Moodle (a senha do PORTAL é um dado de prova: no uso, ela chega pelo link do e-mail) */
    const hash = spawnSync(PHP, ['-r', "echo password_hash('prova-portal-123', PASSWORD_DEFAULT);"], { encoding: 'utf8' }).stdout.trim();
    sql(`UPDATE col_usuarios SET senha = '${hash}' WHERE id = ${aid}`);
    const portal = await entrar(navegador, ra, 'prova-portal-123', 'portal');
    const oferta = await portal.p.$eval('.vc-abrir-moodle', (a) => +a.dataset.oferta).catch(() => 0);
    let fim = '', dentro = false;
    if (oferta) {
      await Promise.all([portal.p.waitForNavigation({ timeout: 30000 }), portal.p.click(`.vc-abrir-moodle[data-oferta="${oferta}"]`)]);
      await portal.p.waitForLoadState('load').catch(() => {});
      fim = portal.p.url();
      dentro = await portal.p.evaluate(() => !document.querySelector('#login, form#login') && !!document.querySelector('[data-region="user-menu"], .usermenu, #user-menu-toggle, .userbutton'));
    }
    const curM = um(`SELECT IFNULL(idMoodle, 0) FROM rm_professores_materias WHERE id = ${oferta}`);
    L.ok(oferta > 0 && fim.startsWith(MURL) && new RegExp(`course/view\\.php\\?id=${curM}`).test(fim) && dentro,
      'o aluno da inscrição entra no Moodle pelo portal, sem a senha do Moodle, e cai na disciplina');

    /* ===== C. o exemplo com a chave sem o limite certo ===== */
    configurarExemplo(kAlunos, curso, forma);
    await pe.goto(EXEMPLO);
    await pe.fill('#nome', 'Aluno Chave Curta Exemplo'); await pe.fill('#email', `prova.chave.curta.${Date.now()}@exemplo.invalid`); await pe.fill('#cpf', cpf(970778)); await pe.selectOption('#sexo', 'masculino');
    await Promise.all([pe.waitForNavigation({ timeout: 60000 }), pe.click('#enviarInscricao')]);
    const recusa = await pe.$eval('#passoMatricula', (e) => [e.dataset.situacao, e.textContent.trim()]).catch(() => ['', '']);
    await L.print(pe, ETAPA, 'exemplo-chave-sem-limite');
    L.ok(recusa[0] === 'recusado' && /não pode gravar "Matrículas"/.test(recusa[1]), `com uma chave sem o limite certo (só alunos), a matrícula é recusada e o andamento diz por quê ("${recusa[1].slice(0, 110)}")`);
    await vis.close();

    /* ===== D. o produto não depende do exemplo ===== */
    const ref = (rel) => { const a = path.join(L.RAIZ, rel); return fs.existsSync(a) && /exemplos/.test(fs.readFileSync(a, 'utf8')); };
    const instalador = fs.readdirSync(path.join(L.RAIZ, 'instalar')).filter((f) => f.endsWith('.php')).some((f) => ref('instalar/' + f));
    const pacote = fs.readFileSync(path.join(L.RAIZ, 'database', 'ferramentas', 'implantacao', 'gerar_pacote.php'), 'utf8');
    const ignorado = spawnSync('git', ['-C', L.RAIZ, 'check-ignore', '-q', 'exemplos/portal-matricula/config.local.php']).status === 0;
    L.ok(!instalador && !ref('database/ferramentas/implantacao/verificar_ambiente.php') && /'#\^exemplos\/#'/.test(pacote) && ignorado,
      'o instalador e a verificação do ambiente não citam a pasta dos exemplos; o pacote do produto a deixa de fora; a configuração do exemplo (com a chave) fica fora do git');

    /* a régua: nenhum boletim nem vida acadêmica dos alunos que já existiam mudou */
    const rg = spawnSync(PHP, [path.join(L.RAIZ, 'tests', 'tabler', 'regua.php'), 'comparar', REGUA_ATUAL], { encoding: 'utf8', cwd: L.RAIZ });
    const pontos = (rg.stdout || '').split('\n').filter((l) => /^\s+(boletins|vidas)\//.test(l));
    L.ok(pontos.every((l) => /:\s+null →/.test(l)), `a régua: nenhum boletim nem vida acadêmica dos alunos que já existiam mudou (${pontos.length} novos, dos alunos de prova)`);
    await adm.ctx.close();
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message);
  } finally {
    try { fs.unlinkSync(CONFIG_EXEMPLO); } catch (e) { /* já saiu */ }
    try { const lim = cli('limpar'); console.log(`  (no ${MODO_FALSO ? 'Moodle FALSO' : 'Moodle de teste'}: ${lim.usuarios_de_prova} usuário(s) de prova suspensos, ${lim.desenturmados} inscrição(ões) tirada(s))`); } catch (e) { /* a limpeza */ }
    if (falso) { falso.kill(); }
    await navegador.close();
  }
  process.exit(L.resumo('onda3/etapa9') ? 0 : 1);
})();
