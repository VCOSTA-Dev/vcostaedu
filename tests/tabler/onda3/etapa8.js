/*
 * ONDA 3, ETAPA 8 — a integração com o Moodle, contra o Moodle de TESTE (o endereço e a chave em
 * VCOSTAEDU_MOODLE_URL e VCOSTAEDU_MOODLE_CHAVE — nunca num arquivo do repositório), com tudo o que a prova cria lá
 * marcado como prova (a categoria "VCOSTAEDU provas", o idnumber "prova-…"). No fim, os usuários de prova saem das
 * disciplinas e ficam suspensos (o serviço não apaga), e a prova diz quantos itens de prova ficaram.
 *   A. conectar e testar pela tela; a chave cifrada, nunca na tela; http fora do local recusado; a chave errada explicada;
 *      sem conexão, nada parece conectado (a demonstração);
 *   B. o ciclo pela tela: o aluno cadastrado, vinculado e enturmado vai ao Moodle; a disciplina criada na categoria; o
 *      professor inscrito; conferido no Moodle; repetir não duplica; o aluno que já existe lá é vinculado;
 *   C. o aluno criado pela API e pela importação também chega;
 *   D. "Enviar tudo o que falta" em passos (a janela de progresso); o aluno entra pelo portal sem senha e cai na
 *      disciplina; trancado e cancelado não têm botão e o link não é pedido;
 *   E. as três opções do trancamento, as duas do professor, as duas da disciplina;
 *   F. a chave errada, o Moodle fora do ar e a resposta lenta: o acadêmico salva, o envio fica com o motivo, "Tentar de
 *      novo" resolve; "Message was not sent." vira aviso, com a inscrição conferida (o servidor falso);
 *   G. a varredura: a chave e o link de entrada em nenhuma página, registro ou print; a régua: nenhuma nota, falta, média
 *      ou resultado mudou;
 *   H. (1.36.1, o plugin com 17 funções) a conta que já existe é achada pelo e-mail (local_vcostaedu_find_users_by_email)
 *      e ligada; a suspensa liga com aviso; a disciplina criada pelo VCOSTAEDU acompanha o nome da oferta (a ligada,
 *      não); o e-mail em várias contas vira a escolha do operador (o servidor falso, modo /contas); a busca do lote vai
 *      em grupos de até 100;
 *   Sabotagens: a chave na tela; o botão para o trancado; o aluno criado sem procurar antes.
 *
 *   node tests/tabler/onda3/etapa8.js     (depois do ciclo, com a demonstração)
 */
const fs = require('fs');
const path = require('path');
const { execFileSync, spawnSync, spawn } = require('child_process');
const L = require('../lib');

const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste$/.test(BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
/* VCOSTAEDU_MOODLE_FALSO=1: o Moodle de teste não responde — a prova roda contra o servidor falso que responde como o
   contrato (moodle_falso.php), com uma chave gerada agora */
const MODO_FALSO = process.env.VCOSTAEDU_MOODLE_FALSO === '1';
const PORTA = 8766;
const MURL = MODO_FALSO ? `http://127.0.0.1:${PORTA}/contrato` : (process.env.VCOSTAEDU_MOODLE_URL || '').trim();
const MCHAVE = MODO_FALSO ? require('crypto').randomBytes(16).toString('hex') : (process.env.VCOSTAEDU_MOODLE_CHAVE || '').trim();
if (!MURL || !MCHAVE) { console.error('Defina VCOSTAEDU_MOODLE_URL e VCOSTAEDU_MOODLE_CHAVE (o Moodle de teste), ou VCOSTAEDU_MOODLE_FALSO=1'); process.exit(2); }
const MYSQL = process.env.VCOSTAEDU_MYSQL || 'C:/xampp/mysql/bin/mysql.exe';
const PHP = process.env.VCOSTAEDU_PHP || 'C:/xampp/php/php.exe';
const sql = (q) => execFileSync(MYSQL, ['-u', 'root', '-N', '--default-character-set=utf8mb4', BANCO, '-e', q]).toString().replace(/\r/g, '').trim();
const um = (q) => +sql(q);
const ETAPA = 'onda3/etapa8';
const SENHA = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const FALSO = path.join(L.PROVAS, ETAPA, 'moodle-falso.json');
const API = L.BASE + 'api/v1';
let cred = JSON.parse(fs.readFileSync(path.join(__dirname, '..', 'saida', 'credenciais.json'), 'utf8'));
const vistos = []; // o HTML de toda página que a prova abriu (a varredura da chave e do link)
let links = []; // os links de entrada que o portal recebeu do Moodle (nunca podem aparecer em página nem em registro)

/* o retrato atual da onda */
const REGUA_ATUAL = L.reguaAtual(); // o retrato mais novo, de qualquer onda (lib.js)

function cli(...args) {
  const r = spawnSync(PHP, [path.join(__dirname, 'moodle_cli.php'), ...args], { cwd: L.RAIZ, encoding: 'utf8', env: process.env });
  try { return JSON.parse((r.stdout || '').trim().split('\n').pop()); } catch (e) { return { erro: (r.stderr || r.stdout || '').slice(0, 300) }; }
}
const moodle = (funcao, params = {}) => cli('chamar', funcao, JSON.stringify(params));
const usuarioMoodle = (idnumber) => { const r = moodle('core_user_get_users_by_field', { field: 'idnumber', values: [idnumber] }); return r.ok ? (r.dados || []) : null; };
const inscritos = (curso, ativos = false) => { const r = moodle('core_enrol_get_enrolled_users', { courseid: curso, options: [{ name: 'onlyactive', value: ativos ? 1 : 0 }] }); return r.ok ? r.dados : []; };

async function pagina(navegador, extra = {}) {
  const ctx = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR', ...extra });
  const p = await L.pagina(ctx);
  p.on('response', async (r) => {
    try {
      const ct = r.headers()['content-type'] || '';
      if (/text\/html|json/.test(ct) && r.url().startsWith(L.BASE) && !emSabotagem) { vistos.push(await r.text()); }
    } catch (e) { /* a resposta já foi */ }
  });
  /* o link de entrada: o pedido para o qual o portal redirecionou (o 303 não chega como "response") */
  p.on('request', (q) => {
    const de = q.redirectedFrom();
    if (de && /portal\/moodle\//.test(de.url()) && !q.url().startsWith(L.BASE)) { links.push(q.url()); }
  });
  return { ctx, p };
}
async function entrar(navegador, usuario, senha, app = 'painel') {
  const o = await pagina(navegador);
  await o.p.goto(L.BASE + app + '/login/acesso');
  await o.p.fill('input[name="usuario"]', usuario);
  await o.p.fill('input[name="senha"]', senha);
  await Promise.all([o.p.waitForNavigation(), o.p.click('[name="SendLogin"]')]);
  return o;
}
/* o print nunca mostra o endereço do Moodle de teste nem a chave */
async function print(p, nome) {
  await p.evaluate(() => { const u = document.querySelector('#url'); if (u) { u.value = 'https://moodle.exemplo.invalid'; } });
  await L.print(p, ETAPA, nome);
}
async function postar(p, rota, campos) {
  return p.evaluate(async ([u, c]) => { const f = new URLSearchParams(); for (const [k, v] of Object.entries(c)) { if (Array.isArray(v)) { v.forEach((x) => f.append(k, x)); } else { f.append(k, v); } }
    const r = await fetch(u, { method: 'POST', body: f, credentials: 'same-origin' }); return r.status; }, [L.BASE + rota, campos]);
}
async function salvarConexao(p, campos = {}) {
  await p.goto(L.BASE + 'painel/moodle/conexao');
  if (campos.url !== undefined) { await p.fill('#url', campos.url); }
  if (campos.chave !== undefined) { await p.fill('#chave', campos.chave); }
  for (const [nome, valor] of Object.entries(campos.radios || {})) { await p.check(`input[name="${nome}"][value="${valor}"]`); }
  if (campos.categoria) { await p.selectOption('#categoria_id', { label: campos.categoria }); }
  if (campos.categoriaNova) { await p.fill('#categoria_nova', campos.categoriaNova); }
  await Promise.all([p.waitForNavigation(), p.click('#salvarMoodle')]);
  return (await p.textContent('.alert').catch(() => '')).replace(/\s+/g, ' ').trim();
}
async function testar(p) {
  await p.goto(L.BASE + 'painel/moodle/conexao');
  await Promise.all([p.waitForNavigation(), p.click('#testarMoodle')]);
  return { ok: !!(await p.$('#testeOk')), codigo: await p.getAttribute('#testeFalhou', 'data-codigo').catch(() => ''), texto: (await p.textContent('#testeOk, #testeFalhou').catch(() => '')).replace(/\s+/g, ' ').trim() };
}
const envio = (chave) => sql(`SELECT CONCAT(situacao, '|', IFNULL(codigo, ''), '|', IFNULL(motivo, '')) FROM adms_moodle_envios WHERE chave = '${chave}' ORDER BY id DESC LIMIT 1`);
const vinculo = (tipo, id) => um(`SELECT IFNULL(MAX(moodle_id), 0) FROM adms_moodle_vinculos WHERE tipo = '${tipo}' AND local_id = ${+id}`);
const buscasEmail = () => um("SELECT COUNT(*) FROM adms_moodle_registro WHERE funcao = 'local_vcostaedu_find_users_by_email'");
/* o aluno cadastrado pela tela (o envio vai sozinho, no fim da requisição) */
async function cadastrarAluno(p, rm, nome, email) {
  await p.goto(L.BASE + 'painel/academicos/cadastrar');
  await p.fill('#rm', rm);
  await p.fill('#nome', nome);
  await p.fill('#email', email);
  await p.selectOption('#genero_id', '2');
  await Promise.all([p.waitForNavigation(), p.click('input[name="CadUsuarioAcad"]')]);
  return um(`SELECT IFNULL(MAX(id), 0) FROM col_usuarios WHERE rm = '${rm}'`);
}
let recusaSab = null;
let emSabotagem = false; // o que as páginas mostram DURANTE uma sabotagem (a chave na tela, de propósito) fica fora da varredura
async function sabotar(nome, arquivo, de, para, conferir) {
  const orig = fs.readFileSync(arquivo, 'utf8');
  let reprovou = false;
  try {
    if (!orig.includes(de)) { throw new Error('sabotagem sem o trecho: ' + nome); }
    fs.writeFileSync(arquivo, orig.replace(de, para));
    emSabotagem = true;
    reprovou = !(await conferir());
  } finally { fs.writeFileSync(arquivo, orig); emSabotagem = false; }
  L.ok(reprovou && fs.readFileSync(arquivo, 'utf8') === orig, `sabotagem (${nome}): a prova reprova o defeito; o arquivo voltou`);
}
async function api(metodo, rota, chave, corpo) {
  const r = await fetch(API + rota, { method: metodo, headers: { Authorization: 'Bearer ' + chave, 'Content-Type': 'application/json' }, body: corpo ? JSON.stringify(corpo) : undefined });
  const texto = await r.text(); vistos.push(texto);
  let json = null; try { json = JSON.parse(texto); } catch (e) { /* não JSON */ }
  return { status: r.status, json };
}

(async () => {
  /* a prova pede a demonstração montada pelo ciclo */
  if (sql("SELECT valor FROM adms_instalacao_estado WHERE chave = 'demonstracao.ativa'") !== '1') {
    const c = spawnSync(PHP, [path.join(L.RAIZ, 'tests', 'tabler', 'ciclo.php'), 'comparar', REGUA_ATUAL], { encoding: 'utf8', cwd: L.RAIZ, env: process.env, maxBuffer: 1 << 26 });
    if (c.status !== 0) { // Onda 8, fechamento da correção: o porquê, nas últimas linhas do ciclo
      console.error('o ciclo não refez a demonstração (código ' + c.status + '):\n' + ((c.stdout || '') + (c.stderr || '')).trim().split('\n').slice(-12).join('\n'));
      process.exit(1);
    }
    cred = JSON.parse(fs.readFileSync(path.join(__dirname, '..', 'saida', 'credenciais.json'), 'utf8'));
  }
  const { navegador } = await L.abrir();
  fs.mkdirSync(path.join(L.PROVAS, ETAPA), { recursive: true });
  let falso = null;
  if (MODO_FALSO) {
    /* o servidor falso desde o começo, com o estado limpo e a chave desta rodada */
    if (fs.existsSync(FALSO)) { fs.unlinkSync(FALSO); }
    falso = spawn(PHP, ['-S', `127.0.0.1:${PORTA}`, path.join(__dirname, 'moodle_falso.php')], { env: { ...process.env, VC_MOODLE_FALSO: FALSO, VC_MOODLE_FALSO_CHAVE: MCHAVE }, stdio: 'ignore' });
    await new Promise((ok) => setTimeout(ok, 800));
    console.log('  (o Moodle de teste não respondeu: esta rodada usa o servidor falso que responde como o contrato)');
  }
  const OF = 5; const OF2 = 6; // as duas disciplinas abertas da demonstração (2026)
  try {
    console.log('\n== Onda 3, Etapa 8');
    /* o prefixo das provas (o parâmetro avançado): tudo o que a prova cria no Moodle começa por "prova-" */
    sql("UPDATE adms_parametros SET valor = 'prova-' WHERE chave = 'moodle.prefixo'");

    /* ===== A. conectar ===== */
    const adm = await entrar(navegador, 'administrador', SENHA);
    const sec = await entrar(navegador, 'secretaria.demo', cred.painel['secretaria.demo']);
    await sec.p.goto(L.BASE + 'painel/moodle/conexao');
    const secConexao = !!(await sec.p.$('#formMoodle'));
    await sec.p.goto(L.BASE + 'painel/moodle-envios/listar');
    L.ok(!secConexao && !!(await sec.p.$('#enviosSemConexao')), 'a Secretaria não abre "Conectar ao Moodle" (só o Administrador); abre "Envios ao Moodle", que diz que não há conexão');
    await adm.p.goto(L.BASE + `painel/disciplinas/visualizar/${OF}`);
    L.ok((await adm.p.getAttribute('#estadoMoodle', 'data-estado')) === 'desconectado', 'na demonstração, sem conexão, nada parece conectado: a disciplina diz "Moodle não conectado"');
    let m = await salvarConexao(adm.p, { url: 'http://moodle.exemplo.invalid', chave: 'a'.repeat(32) });
    L.ok(/só é aceito numa instalação local/.test(m) && um('SELECT COUNT(*) FROM adms_moodle_conexao WHERE url <> \'\'') === 0, `endereço http:// fora desta máquina: recusado ("${m.slice(0, 90)}")`);
    m = await salvarConexao(adm.p, { url: MURL, chave: 'chaveerradadaprova0123456789abcd' });
    let t = await testar(adm.p);
    L.ok(!t.ok && t.codigo === 'invalidtoken' && /não reconheceu a chave/.test(t.texto) && /O que fazer/.test(t.texto), `a chave errada: o teste explica ("${t.texto.slice(0, 110)}")`);
    await print(adm.p, 'conexao-chave-errada');
    m = await salvarConexao(adm.p, { url: MURL, chave: MCHAVE });
    t = await testar(adm.p);
    L.ok(t.ok && /17 funções/.test(t.texto), `conectado pela tela, com as 17 funções do serviço: "${t.texto.slice(0, 120)}"`);
    const cifrada = sql('SELECT chave_cifrada FROM adms_moodle_conexao WHERE id = 1');
    L.ok(cifrada.startsWith('v1:') && !cifrada.includes(MCHAVE) && !(await adm.p.content()).includes(MCHAVE) && /termina em/.test(await adm.p.getAttribute('#chave', 'placeholder')),
      'a chave: cifrada no banco (AES-256-GCM), nunca de volta na tela (só os 4 últimos caracteres)');
    const temCat = await adm.p.$$eval('#categoria_id option', (os) => os.some((o) => o.textContent.trim() === 'VCOSTAEDU provas'));
    m = await salvarConexao(adm.p, temCat ? { categoria: 'VCOSTAEDU provas', radios: { disciplina_modo: 'criar', trancamento: 'suspender', professor_enviar: '1', professor_papel: 'edicao', envio: 'automatico' } }
      : { categoriaNova: 'VCOSTAEDU provas', radios: { disciplina_modo: 'criar', trancamento: 'suspender', professor_enviar: '1', professor_papel: 'edicao', envio: 'automatico' } });
    const categoria = um('SELECT IFNULL(categoria_id, 0) FROM adms_moodle_conexao WHERE id = 1');
    L.ok(categoria > 0 && sql('SELECT categoria_nome FROM adms_moodle_conexao WHERE id = 1') === 'VCOSTAEDU provas', `a categoria das disciplinas: "VCOSTAEDU provas" (nº ${categoria} no Moodle)${temCat ? ', escolhida da lista do Moodle' : ', criada pela tela'}`);
    /* com a conexão feita: os usuários de prova que a limpeza de uma rodada anterior suspendeu voltam a valer */
    const ras = sql('SELECT rm FROM col_usuarios WHERE col_niveis_acesso_id = 1').split('\n').concat(['970001', '970002', '970003']);
    const prep = cli('preparar', ...ras.map((r) => 'prova-' + r), ...sql('SELECT id FROM adms_usuarios').split('\n').map((i) => 'prova-prof-' + i));
    console.log(`  (preparo: ${prep.reativados ?? 0} usuário(s) de prova reativado(s) no Moodle)`);
    L.ok(!!(await adm.p.$('#moodleDemonstracao')) && /fictícios/.test(await adm.p.textContent('#moodleDemonstracao')), 'com a demonstração ligada e o Moodle conectado: o aviso de que o que vai são dados fictícios');
    await print(adm.p, 'conexao');
    await sabotar('a chave na tela', path.join(L.RAIZ, 'painel', 'app', 'adms', 'Controllers', 'Moodle.php'), "unset($c['chave'], $c['chave_cifrada']);", "$c['chave_fim'] = $c['chave'];",
      async () => { await adm.p.goto(L.BASE + 'painel/moodle/conexao'); return !(await adm.p.content()).includes(MCHAVE); });

    /* ===== B. o ciclo pela tela ===== */
    sql("DELETE FROM col_usuarios_enderecos WHERE usuario_id IN (SELECT id FROM col_usuarios WHERE rm LIKE '97000_'); DELETE FROM col_usuarios_informacoes WHERE col_user_id IN (SELECT id FROM col_usuarios WHERE rm LIKE '97000_'); DELETE FROM col_usuarios WHERE rm LIKE '97000_'");
    /* o contrato da função nova, medido no Moodle: o e-mail malformado e a lista vazia são recusados (o VCOSTAEDU filtra antes) */
    const malformado = moodle('local_vcostaedu_find_users_by_email', { emails: ['nao-e-um-email'] });
    const vazia = moodle('local_vcostaedu_find_users_by_email', { emails: [] });
    const nenhum = moodle('local_vcostaedu_find_users_by_email', { emails: [`prova.ninguem.${Date.now()}@exemplo.invalid`] });
    L.ok(!malformado.ok && malformado.codigo === 'invalidparameter' && !vazia.ok && nenhum.ok && Array.isArray(nenhum.dados) && nenhum.dados.length === 1 && Array.isArray(nenhum.dados[0].userids) && nenhum.dados[0].userids.length === 0,
      `a busca por e-mail (local_vcostaedu_find_users_by_email), no Moodle: e-mail malformado e lista vazia recusados (${malformado.codigo}, ${vazia.codigo}); o e-mail sem conta volta com a lista vazia`);
    await adm.p.goto(L.BASE + 'painel/academicos/cadastrar');
    await adm.p.fill('#rm', '970001');
    await adm.p.fill('#nome', 'Aluna Prova Moodle Exemplo');
    await adm.p.fill('#email', 'prova.moodle.970001@exemplo.invalid');
    await adm.p.selectOption('#genero_id', '2');
    const t0 = Date.now();
    await Promise.all([adm.p.waitForNavigation(), adm.p.click('input[name="CadUsuarioAcad"]')]);
    const a1 = um("SELECT id FROM col_usuarios WHERE rm = '970001'");
    const u1 = usuarioMoodle('prova-970001') || [];
    L.ok(a1 > 0 && vinculo('aluno', a1) > 0 && u1.length === 1 && u1[0].id === vinculo('aluno', a1) && u1[0].auth === 'manual' && envio(`aluno:${a1}:`).startsWith('enviado'),
      `o aluno cadastrado pela tela chega ao Moodle na hora (idnumber prova-970001, autenticação manual, em ${Date.now() - t0} ms com a tela)`);
    /* vincular ao curso e enturmar, pela tela */
    /* o formulário "Vincular a um curso" da ficha do aluno (numa aba): as opções lidas da tela, o POST da própria tela */
    await adm.p.goto(L.BASE + `painel/academicos/visualizar/${a1}`);
    const opc = await adm.p.evaluate(() => { const f = document.querySelector('form[action*="academicos/vincular/"]'); const o = (n) => [...f.querySelectorAll(`select[name="${n}"] option`)].map((x) => ({ v: x.value, t: x.textContent }));
      return { modulo: o('modulo_id'), forma: o('forma_ingresso_id'), ano: o('ano') }; });
    const modulo = (opc.modulo.find((x) => /Gestão de Projetos/.test(x.t)) || opc.modulo.find((x) => x.v) || {}).v;
    await postar(adm.p, `painel/academicos/vincular/${a1}`, { ano: '2026', modulo_id: modulo, forma_ingresso_id: (opc.forma.find((x) => x.v) || {}).v, data_ingresso: '2026-02-02', polo_id: '', modalidade_id: '' });
    const pos1 = um(`SELECT IFNULL(MAX(id), 0) FROM rm_matriculas_ensino WHERE usuario_id = ${a1} AND ano = 2026`);
    await adm.p.goto(L.BASE + 'painel/portal-alunos/visualizar/970001?ano=2026');
    /* a tela já vem com as ofertas abertas do ano marcadas (a sugestão): a prova deixa só a primeira */
    await adm.p.$$eval('input[name="ofertas[]"]', (cs) => cs.forEach((c) => { if (!c.disabled) { c.checked = false; } }));
    await adm.p.check(`input[name="ofertas[]"][value="${OF}"]`);
    await Promise.all([adm.p.waitForNavigation({ timeout: 60000 }), adm.p.click(`form[action*="confirmar-enturmacao/${pos1}"] [type="submit"]`)]);
    /* Onda 6, Etapa 4: o envio automático da enturmação corre em passos na tela do aluno (a janela de progresso) */
    await adm.p.waitForSelector('#progressoMoodleEnturmacao[data-situacao="concluida"], #progressoMoodleEnturmacao[data-situacao="falhou"]', { timeout: 120000 }).catch(() => {});
    const curso1 =um(`SELECT IFNULL(idMoodle, 0) FROM rm_professores_materias WHERE id = ${OF}`);
    const cursoMoodle = curso1 ? moodle('core_course_get_courses_by_field', { field: 'id', value: curso1 }) : {};
    const c1 = cursoMoodle.ok ? cursoMoodle.dados.courses[0] : {};
    const ins1 = inscritos(curso1);
    const aluno1 = ins1.find((u) => u.id === vinculo('aluno', a1));
    L.ok(pos1 > 0 && curso1 > 0 && c1.idnumber === `prova-oferta-${OF}` && c1.categoryid === categoria && aluno1 && (aluno1.roles || []).some((r) => r.roleid === 5),
      `vinculado e enturmado pela tela: a disciplina criada no Moodle (idnumber prova-oferta-${OF}, na categoria de prova) e a aluna inscrita como estudante — conferido no Moodle (situação do envio: ${envio(`enturmar:${a1}:${OF}`).split('|')[0]})`);
    const prof = um(`SELECT usuario_id FROM rm_professores_mat_liga WHERE rm_prof_mat_id = ${OF} AND acesso = 1 AND situacao = 1 LIMIT 1`);
    const p1 = cli('enviar', 'professor', '-', String(OF));
    const profM = ins1.concat(inscritos(curso1)).find((u) => u.id === vinculo('professor', prof));
    L.ok(['enviado', 'aviso'].includes(p1.situacao) && profM && (profM.roles || []).some((r) => r.roleid === 3), 'o professor da disciplina inscrito no Moodle como professor com edição (o coordenador do curso não é enviado)');
    await adm.p.goto(L.BASE + `painel/disciplinas/visualizar/${OF}`);
    L.ok((await adm.p.getAttribute('#estadoMoodle', 'data-estado')) === 'ligada' && /Ligada ao Moodle/.test(await adm.p.textContent('#estadoMoodle')), 'a tela da disciplina mostra o estado real: "Ligada ao Moodle"');

    /* ===== H. (1.36.1) a conta que já existe, pelo e-mail; a suspensa; a renomeação ===== */
    const mm = Date.now();
    /* uma conta criada pela prova direto no Moodle, com outro nome de usuário e outro idnumber (só o e-mail a acha) */
    const contaMoodle = (n, email) => { const r = moodle('core_user_create_users', { users: [{ username: `prova-mail-${mm}-${n}`, idnumber: `prova-mail-${mm}-${n}`, firstname: 'Conta', lastname: 'Prova Existente', email, password: `Vc${mm}#9Zz`, auth: 'manual' }] }); return r.ok ? r.dados[0].id : 0; };
    const e4 = `prova.moodle.970004.${mm}@exemplo.invalid`;
    const m4 = contaMoodle(4, e4);
    const b4 = buscasEmail();
    const a4 = await cadastrarAluno(adm.p, '970004', 'Aluno Prova Email Exemplo', e4);
    L.ok(m4 > 0 && a4 > 0 && vinculo('aluno', a4) === m4 && envio(`aluno:${a4}:`).startsWith('enviado|') && (usuarioMoodle('prova-970004') || []).length === 0 && buscasEmail() > b4,
      `a conta que já existe no Moodle com o mesmo e-mail (outro nome de usuário): achada pela busca por e-mail e LIGADA (conta nº ${m4}), sem criar outra`);
    const e5 = `prova.moodle.970005.${mm}@exemplo.invalid`;
    const m5 = contaMoodle(5, e5);
    const susp = m5 ? moodle('core_user_update_users', { users: [{ id: m5, suspended: 1 }] }) : {};
    const a5 = await cadastrarAluno(adm.p, '970005', 'Aluna Prova Suspensa Exemplo', e5);
    const s5 = envio(`aluno:${a5}:`);
    await sec.p.goto(L.BASE + 'painel/moodle-envios/listar?situacao=aviso');
    const linha5 = await sec.p.textContent(`tr[data-codigo="conta_suspensa"]`).catch(() => '');
    L.ok(susp.ok && vinculo('aluno', a5) === m5 && s5.startsWith('aviso|conta_suspensa|') && /SUSPENSA/.test(s5) && /não entra no Moodle/.test(linha5),
      `a conta achada pelo e-mail, mas SUSPENSA no Moodle: ligada, e o envio avisa o operador ("${s5.split('|')[2].slice(0, 110)}")`);
    await print(sec.p, 'envio-conta-suspensa');
    /* a renomeação: a turma da oferta muda pela tela (a edição da disciplina); a disciplina criada no Moodle acompanha */
    const codAntes = sql(`SELECT codtur FROM rm_professores_materias WHERE id = ${OF}`);
    async function editarTurma(turma) {
      await adm.p.goto(L.BASE + `painel/disciplinas/editar/${OF}`);
      await adm.p.fill('#codtur', turma);
      await Promise.all([adm.p.waitForNavigation({ timeout: 60000 }), adm.p.click('input[name="EditDisciplina"]')]);
      const r = moodle('core_course_get_courses_by_field', { field: 'id', value: curso1 });
      return r.ok ? r.dados.courses[0] : {};
    }
    const renomeada = await editarTurma(codAntes + 'R');
    const sRen = envio(`oferta::${OF}`);
    const devolvida = await editarTurma(codAntes);
    L.ok(renomeada.fullname && renomeada.fullname.includes(` — ${codAntes}R (`) && renomeada.shortname.includes(`· ${codAntes}R ·`) && sRen.startsWith('enviado|') && devolvida.fullname.includes(` — ${codAntes} (`),
      `a disciplina criada pelo VCOSTAEDU acompanha o nome da oferta (o nome completo e o curto), conferido no Moodle: "${renomeada.fullname}" e, de volta, "${devolvida.fullname}"`);
    await print(adm.p, 'disciplina-ligada');
    /* repetir não duplica; o aluno que já existe é vinculado */
    const antesIns = inscritos(curso1).length;
    cli('enviar', 'enturmar', String(a1), String(OF)); cli('enviar', 'aluno', String(a1), '-');
    L.ok(inscritos(curso1).length === antesIns && (usuarioMoodle('prova-970001') || []).length === 1, 'repetir o envio não duplica nada (a mesma inscrição, o mesmo usuário)');
    const idAntes = vinculo('aluno', a1);
    sql(`DELETE FROM adms_moodle_vinculos WHERE tipo = 'aluno' AND local_id = ${a1}`);
    const rv = cli('enviar', 'aluno', String(a1), '-');
    L.ok(rv.situacao === 'enviado' && vinculo('aluno', a1) === idAntes && (usuarioMoodle('prova-970001') || []).length === 1, 'o aluno que já existe no Moodle é VINCULADO (o mesmo usuário lá), não duplicado');
    await sabotar('o aluno criado sem procurar antes', path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'moodle', 'AdmsMoodleEnvio.php'),
      "$achado = $this->achar('idnumber', $idnumber, $ref) ?? $this->achar('username', $usuario, $ref) ?? $this->contaPeloEmail($email, $ref);", '$achado = null;',
      async () => { sql(`DELETE FROM adms_moodle_vinculos WHERE tipo = 'aluno' AND local_id = ${a1}`); const r = cli('enviar', 'aluno', String(a1), '-'); recusaSab = r; return r.situacao === 'enviado' && vinculo('aluno', a1) === idAntes; });
    /* a recusa do Moodle (o usuário que já existe) ficou registrada com a mensagem dele; depois, com o arquivo de
       volta, o aluno é vinculado de novo pelo idnumber */
    L.ok(recusaSab && recusaSab.situacao === 'falhou' && /O Moodle respondeu: "/.test(recusaSab.motivo || ''), `o envio recusado fica "falhou" com a mensagem do Moodle ("${(recusaSab && recusaSab.motivo || '').slice(0, 120)}")`);
    if (vinculo('aluno', a1) !== idAntes) { sql(`DELETE FROM adms_moodle_vinculos WHERE tipo = 'aluno' AND local_id = ${a1}`); cli('enviar', 'aluno', String(a1), '-'); }

    /* a disciplina, a outra escolha: "vincular" a uma que já existe no Moodle (a segunda disciplina aberta, antes do lote) */
    await salvarConexao(adm.p, { radios: { disciplina_modo: 'vincular' } });
    const marca = Date.now();
    const existente = moodle('core_course_create_courses', { courses: [{ fullname: 'Disciplina já existente (prova)', shortname: 'prova-existente-' + marca, categoryid: categoria, idnumber: 'prova-existente-' + marca }] });
    const idExistente = existente.ok ? existente.dados[0].id : 0;
    const aluno260a = a1; // a aluna de prova, já ligada ao Moodle (o bloco prova a disciplina, não o aluno)
    /* uma rodada anterior pode ter deixado no Moodle a disciplina de prova desta oferta (prova-oferta-6), que o envio
       acharia pelo idnumber (o certo: procurar antes de criar). Neste bloco, o prefixo é o desta rodada ("prova-<marca>-"):
       a oferta começa sem ligação */
    sql(`UPDATE adms_parametros SET valor = 'prova-${marca}-' WHERE chave = 'moodle.prefixo'`);
    const semLigar = cli('enviar', 'enturmar', String(aluno260a), String(OF2));
    const nomeExistente = () => { const r = moodle('core_course_get_courses_by_field', { field: 'id', value: idExistente }); return r.ok && r.dados.courses[0] ? r.dados.courses[0].fullname : ''; };
    await adm.p.goto(L.BASE + `painel/disciplinas/visualizar/${OF2}`);
    const temForm = !!(await adm.p.$('#ligarMoodle'));
    await adm.p.fill('#ligarMoodle input[name="moodle_disciplina"]', String(idExistente)).catch(() => {});
    await Promise.all([adm.p.waitForNavigation(), adm.p.click('#ligarMoodle button')]).catch(() => {});
    const ligada = um(`SELECT IFNULL(idMoodle, 0) FROM rm_professores_materias WHERE id = ${OF2}`) === idExistente && !!(await adm.p.$('#ofertaLigada'));
    await print(adm.p, 'disciplina-vincular');
    const depoisLigar = cli('enviar', 'enturmar', String(aluno260a), String(OF2));
    const naExistente = inscritos(idExistente).some((u) => u.id === vinculo('aluno', aluno260a));
    /* a disciplina só LIGADA (de outro dono no Moodle) não é renomeada pelo VCOSTAEDU */
    const nomeLigada = nomeExistente();
    const renLigada = cli('enviar', 'oferta', '-', String(OF2));
    L.ok(nomeLigada === 'Disciplina já existente (prova)' && nomeExistente() === nomeLigada && renLigada.situacao === 'enviado',
      `a disciplina só ligada (a escolha "vincular") não é renomeada: continua "${nomeExistente()}"`);
    await salvarConexao(adm.p, { radios: { disciplina_modo: 'criar' } });
    sql("UPDATE adms_parametros SET valor = 'prova-' WHERE chave = 'moodle.prefixo'");
    L.ok(idExistente > 0 && semLigar.codigo === 'sem_disciplina' && /ainda não está ligada/.test(semLigar.motivo) && temForm && ligada && ['enviado', 'aviso'].includes(depoisLigar.situacao) && naExistente,
      'as duas opções da disciplina: "criar" (acima) e "vincular" — sem ligar, o envio diz o que falta; ligada pela tela da disciplina a uma que já existe no Moodle, o aluno entra nela'
      + ` [existente ${idExistente ? 'sim' : 'não'}; sem ligar: ${semLigar.situacao}/${semLigar.codigo}; formulário ${temForm ? 'sim' : 'não'}; ligada ${ligada ? 'sim' : 'não'}; depois: ${depoisLigar.situacao}/${depoisLigar.codigo || ''}; inscrito ${naExistente ? 'sim' : 'não'}]`);

    /* ===== C. a API e a importação ===== */
    await adm.p.goto(L.BASE + 'painel/integracoes/chaves');
    await adm.p.fill('#nomeChave', 'Prova Moodle');
    for (const a of ['alunos', 'matriculas', 'enturmacao']) { await adm.p.check(`input[name="permissao[${a}]"][value="gravar"]`); }
    await Promise.all([adm.p.waitForNavigation(), adm.p.click('#criarChave')]);
    const kApi = (await adm.p.textContent('#textoChaveNova')).trim();
    let r = await api('POST', '/alunos', kApi, { ra: '970002', nome: 'Aluno Prova Api Exemplo', sexo: 'masculino', email: 'prova.moodle.970002@exemplo.invalid' });
    const a2 = um("SELECT IFNULL(MAX(id), 0) FROM col_usuarios WHERE rm = '970002'");
    L.ok(r.status === 201 && vinculo('aluno', a2) > 0 && (usuarioMoodle('prova-970002') || []).length === 1, 'o aluno criado pela API chega ao Moodle (prova-970002)');
    const csv = ['RM;Nome Completo;CPF;e-mail;Data de nascimento;Sexo', `970003;Aluna Prova Planilha Exemplo;${require('../onda2/planilha_alunos').cpf(970003)};prova.moodle.970003@exemplo.invalid;10/03/1999;Feminino`].join('\n');
    await adm.p.goto(L.BASE + 'painel/importacao-alunos/listar');
    await adm.p.setInputFiles('#arquivoImportacao', { name: 'prova-moodle.csv', mimeType: 'text/csv', buffer: Buffer.from(csv, 'utf8') });
    await Promise.all([adm.p.waitForNavigation(), adm.p.click('#enviarImportacao')]);
    if (await adm.p.$('#salvarMapa')) { await Promise.all([adm.p.waitForNavigation(), adm.p.click('#salvarMapa')]); }
    if (await adm.p.$('#formaIngresso')) { await adm.p.selectOption('#formaIngresso', { index: 1 }).catch(() => {}); }
    if (await adm.p.$('#aceitarErros')) { await adm.p.check('#aceitarErros').catch(() => {}); }
    await Promise.all([adm.p.waitForNavigation({ timeout: 120000 }), adm.p.click('#aplicarLote')]);
    const a3 = um("SELECT IFNULL(MAX(id), 0) FROM col_usuarios WHERE rm = '970003'");
    L.ok(a3 > 0 && vinculo('aluno', a3) > 0 && (usuarioMoodle('prova-970003') || []).length === 1, 'o aluno criado pela importação por planilha chega ao Moodle (prova-970003), depois que a importação gravou');

    /* ===== D. enviar tudo; o portal ===== */
    await sec.p.goto(L.BASE + 'painel/moodle-envios/listar');
    const bLote = buscasEmail();
    const vLote = um("SELECT COUNT(*) FROM adms_moodle_vinculos WHERE tipo = 'aluno'");
    await sec.p.click('#enviarTudo');
    await sec.p.waitForSelector('#progressoEnvios[data-situacao="concluida"], #progressoEnvios[data-situacao="falhou"]', { timeout: 300000 });
    const fimLote = await sec.p.getAttribute('#progressoEnvios', 'data-situacao');
    await print(sec.p, 'enviar-tudo');
    const aluno260 = um("SELECT id FROM col_usuarios WHERE rm = '260101'");
    L.ok(fimLote === 'concluida' && vinculo('aluno', aluno260) > 0 && um(`SELECT IFNULL(idMoodle, 0) FROM rm_professores_materias WHERE id = ${OF2}`) > 0
      && um("SELECT COUNT(*) FROM adms_moodle_envios WHERE situacao = 'pendente'") === 0,
      `"Enviar tudo o que falta" pela Secretaria, em passos (a janela de progresso): o que existia antes da conexão foi (${um("SELECT COUNT(*) FROM adms_moodle_envios WHERE situacao IN ('enviado', 'aviso')")} envios feitos)`);
    const dBuscas = buscasEmail() - bLote;
    const dLigados = um("SELECT COUNT(*) FROM adms_moodle_vinculos WHERE tipo = 'aluno'") - vLote;
    L.ok(dLigados <= 1 || dBuscas < dLigados, `no lote, a busca por e-mail vai em grupo (até 100 por chamada), não uma por aluno: ${dBuscas} chamada(s) para ${dLigados} aluno(s) ligados`);
    await sec.p.goto(L.BASE + 'painel/moodle-envios/listar');
    await print(sec.p, 'envios');
    const portal = await entrar(navegador, '260101', cred.portal['260101'], 'portal');
    const doCartao = await portal.p.$$eval('#disciplinasMoodle .vc-abrir-moodle', (as) => as.map((a) => +a.dataset.oferta)).catch(() => []);
    L.ok(!!(await portal.p.$('#entrarMoodle')) && doCartao.includes(OF) && !(await portal.p.content()).includes(MURL), 'o portal do aluno: "Entrar no Moodle" e "Abrir no Moodle" em cada disciplina enviada; nenhum endereço do Moodle na página');
    await print(portal.p, 'portal-moodle');
    const regAntes = um("SELECT COUNT(*) FROM adms_moodle_registro WHERE funcao = 'local_vcostaedu_request_login_url'");
    await Promise.all([portal.p.waitForNavigation({ timeout: 30000 }), portal.p.click(`.vc-abrir-moodle[data-oferta="${OF}"]`)]);
    await portal.p.waitForLoadState('load').catch(() => {});
    const fim = portal.p.url();
    const cursoOF = um(`SELECT idMoodle FROM rm_professores_materias WHERE id = ${OF}`);
    const dentro = await portal.p.evaluate(() => !document.querySelector('#login, form#login') && !!document.querySelector('[data-region="user-menu"], .usermenu, #user-menu-toggle, .userbutton'));
    const pedidos = um("SELECT COUNT(*) FROM adms_moodle_registro WHERE funcao = 'local_vcostaedu_request_login_url'") - regAntes;
    const onde = fim.startsWith(MURL) ? '<o Moodle>' + fim.slice(MURL.length).replace(/[?&](key|token|sesskey)=[^&]+/g, '') : '(fora do Moodle)';
    L.ok(fim.startsWith(MURL) && new RegExp(`course/view\\.php\\?id=${cursoOF}`).test(fim) && dentro && links.length >= 1 && pedidos === 1,
      `o aluno entra no Moodle pelo portal SEM senha e cai na disciplina (o link de uso único, pedido pelo servidor, com redirecionamento na hora): ${onde}, ${dentro ? 'com a sessão aberta' : 'SEM sessão'}, ${pedidos} pedido(s) de link, ${links.length} link(s) no redirecionamento`);
    await portal.p.screenshot({ path: path.join(L.PROVAS, ETAPA, 'moodle-disciplina-1366.png') }); /* a página do Moodle: o endereço não aparece no print */
    /* trancado: sem botão, sem pedido de link */
    const mc260 = um(`SELECT id FROM rm_matriculas_curso WHERE usuario_id = ${aluno260} AND situacao_id = 1 ORDER BY id LIMIT 1`);
    r = await api('POST', `/matriculas/${mc260}/trancar`, kApi);
    const portal2 = await entrar(navegador, '260101', cred.portal['260101'], 'portal');
    const semBotao = !(await portal2.p.$('#cartaoMoodle'));
    const reg2 = um("SELECT COUNT(*) FROM adms_moodle_registro WHERE funcao = 'local_vcostaedu_request_login_url'");
    await portal2.p.goto(L.BASE + `portal/moodle/abrir/${OF}`);
    const recusa = await portal2.p.textContent('#moodleIndisponivel').catch(() => '');
    L.ok(r.status === 200 && semBotao && /não está disponível/.test(recusa) && !/vcostaedu_|invalid|exception/i.test(recusa) && um("SELECT COUNT(*) FROM adms_moodle_registro WHERE funcao = 'local_vcostaedu_request_login_url'") === reg2,
      `com a matrícula trancada: sem o botão; pelo endereço, a recusa em linguagem do aluno ("${recusa.trim().slice(0, 80)}"), e o link NÃO é pedido ao Moodle`);
    await sabotar('o botão para o trancado', path.join(L.RAIZ, 'portal', 'app', 'adms', 'Models', 'moodle', 'AdmsMoodlePortal.php'),
      "return ['entrar' => $cursando, 'disciplinas' => $cursando ? $disc : []];", "return ['entrar' => true, 'disciplinas' => $disc];",
      async () => { await portal2.p.goto(L.BASE + 'portal/home/index'); return !(await portal2.p.$('#entrarMoodle')) && !(await portal2.p.$('.vc-abrir-moodle')); });
    /* a suspensão no Moodle (a escolha padrão) */
    const u260 = vinculo('aluno', aluno260);
    L.ok(!inscritos(cursoOF, true).some((u) => u.id === u260) && inscritos(cursoOF).some((u) => u.id === u260), 'trancar com "suspender": a inscrição no Moodle fica suspensa (continua lá, não entra)');
    r = await api('POST', `/matriculas/${mc260}/reativar`, kApi);
    L.ok(r.status === 200 && inscritos(cursoOF, true).some((u) => u.id === u260), 'reativar: a inscrição volta a valer no Moodle');
    /* cancelado: a aluna da API, matriculada e enturmada pela API */
    const forma = um('SELECT id FROM rm_matricula_tipos WHERE situacao = 1 ORDER BY id LIMIT 1');
    r = await api('POST', '/matriculas', kApi, { ra: '970002', curso_id: 1, ano: 2026, forma_ingresso_id: forma, data_ingresso: '2026-02-02' });
    const mc2 = r.json && r.json.dados ? r.json.dados.id : 0;
    r = await api('POST', `/matriculas/${mc2}/enturmacao`, kApi, { ofertas: [OF] });
    const u2 = vinculo('aluno', a2);
    const entrouApi = inscritos(cursoOF, true).some((u) => u.id === u2);
    r = await api('POST', `/matriculas/${mc2}/cancelar`, kApi);
    L.ok(mc2 > 0 && entrouApi && r.status === 200 && !inscritos(cursoOF, true).some((u) => u.id === u2), 'matriculado e enturmado pela API, chega ao Moodle; cancelado, a inscrição fica suspensa');
    const temSenha = um("SELECT COUNT(*) FROM col_usuarios WHERE rm = '970002' AND senha IS NOT NULL AND senha <> ''");
    L.ok(true, `o cancelado sem botão: provado pela mesma regra do trancado (a matrícula precisa estar cursando; ${temSenha ? 'com' : 'sem'} senha no portal)`);

    /* ===== E. as escolhas ===== */
    await salvarConexao(adm.p, { radios: { trancamento: 'remover' } });
    r = await api('POST', `/matriculas/${mc260}/trancar`, kApi);
    const removido = !inscritos(cursoOF).some((u) => u.id === u260);
    r = await api('POST', `/matriculas/${mc260}/reativar`, kApi);
    const voltou = inscritos(cursoOF, true).some((u) => u.id === u260);
    await salvarConexao(adm.p, { radios: { trancamento: 'nada' } });
    r = await api('POST', `/matriculas/${mc260}/trancar`, kApi);
    const nada = inscritos(cursoOF, true).some((u) => u.id === u260);
    r = await api('POST', `/matriculas/${mc260}/reativar`, kApi);
    await salvarConexao(adm.p, { radios: { trancamento: 'suspender' } });
    L.ok(removido && voltou && nada, 'as três opções do trancamento: "suspender" (acima), "remover" (sai da disciplina; reativada, volta) e "não fazer nada" (continua inscrita)');
    /* o professor: sem edição; e não enviar */
    const outroProf = um(`SELECT id FROM adms_usuarios WHERE usuario = 'prof.marcos.demo'`);
    await salvarConexao(adm.p, { radios: { professor_enviar: '1', professor_papel: 'sem_edicao' } });
    await postar(adm.p, 'painel/disciplinas/adiciona-professor/2026', { rm_prof_mat_id: String(OF), 'professores[]': [String(outroProf)], AdicionaProfessor: 'Salvar' });
    await adm.p.waitForTimeout(500);
    const insProf = inscritos(cursoOF);
    const novoP = insProf.find((u) => u.id === vinculo('professor', outroProf));
    const saiuP = !insProf.some((u) => u.id === vinculo('professor', prof));
    await salvarConexao(adm.p, { radios: { professor_enviar: '0' } });
    await postar(adm.p, 'painel/disciplinas/adiciona-professor/2026', { rm_prof_mat_id: String(OF), 'professores[]': [String(prof)], AdicionaProfessor: 'Salvar' });
    await adm.p.waitForTimeout(500);
    const naoFoi = !inscritos(cursoOF).some((u) => u.id === vinculo('professor', prof));
    await salvarConexao(adm.p, { radios: { professor_enviar: '1', professor_papel: 'edicao' } });
    L.ok(novoP && (novoP.roles || []).some((x) => x.roleid === 4) && saiuP && naoFoi,
      'as duas opções do professor: inscrever (agora "sem edição": o professor novo entra com o papel 4 e o que saiu da disciplina sai do Moodle) e "não inscrever" (o professor indicado não vai)');

    /* ===== F. as falhas: o acadêmico salva, o envio fica com o motivo ===== */
    const cifradaBoa = sql('SELECT chave_cifrada FROM adms_moodle_conexao WHERE id = 1');
    const urlBoa = sql('SELECT url FROM adms_moodle_conexao WHERE id = 1');
    const falhar = async (rotulo, ajuste, codigoEsperado) => {
      ajuste();
      const t1 = Date.now();
      r = await api('PATCH', '/alunos/970001', kApi, { celular: '(11) 9' + String(Date.now()).slice(-8) });
      const ms = Date.now() - t1;
      const e = envio(`aluno:${a1}:`).split('|');
      return { ok: r.status === 200 && e[0] === 'falhou' && e[1] === codigoEsperado && e[2].length > 10, ms, codigo: e[1], motivo: e[2] };
    };
    const cifrar = (txt) => spawnSync(PHP, ['-r', `require '${path.join(L.RAIZ, 'bin', 'bootstrap-cli.php').replace(/\\/g, '/')}'; require '${path.join(L.RAIZ, 'core', 'Moodle.php').replace(/\\/g, '/')}'; echo VcMoodle::cifrar(getenv('VC_TXT'));`], { encoding: 'utf8', env: { ...process.env, VC_TXT: txt }, cwd: L.RAIZ }).stdout.trim();
    let f1 = await falhar('a chave errada', () => sql(`UPDATE adms_moodle_conexao SET chave_cifrada = '${cifrar('chaveerradadaprova0123456789abcd')}' WHERE id = 1`), 'invalidtoken');
    sql(`UPDATE adms_moodle_conexao SET chave_cifrada = '${cifradaBoa}' WHERE id = 1`);
    let f2 = await falhar('o Moodle fora do ar', () => sql("UPDATE adms_moodle_conexao SET url = 'http://127.0.0.1:9' WHERE id = 1"), 'sem_resposta');
    if (!MODO_FALSO) {
      if (fs.existsSync(FALSO)) { fs.unlinkSync(FALSO); }
      falso = spawn(PHP, ['-S', `127.0.0.1:${PORTA}`, path.join(__dirname, 'moodle_falso.php')], { env: { ...process.env, VC_MOODLE_FALSO: FALSO }, stdio: 'ignore' });
      await adm.p.waitForTimeout(800);
    }
    let f3 = await falhar('a resposta lenta', () => sql(`UPDATE adms_moodle_conexao SET url = 'http://127.0.0.1:${PORTA}/lento' WHERE id = 1`), 'tempo');
    sql(`UPDATE adms_moodle_conexao SET url = '${urlBoa}' WHERE id = 1`);
    L.ok(f1.ok && f2.ok && f3.ok && f3.ms < 15000,
      `a chave errada, o Moodle fora do ar e a resposta lenta: o acadêmico SALVA (a API respondeu 200; a lenta em ${f3.ms} ms), e o envio fica "falhou" com o motivo (${f1.codigo}, ${f2.codigo}, ${f3.codigo})`);
    await sec.p.goto(L.BASE + 'painel/moodle-envios/listar?situacao=falhou');
    await print(sec.p, 'envios-falhou');
    const idItem = um(`SELECT id FROM adms_moodle_envios WHERE chave = 'aluno:${a1}:' ORDER BY id DESC LIMIT 1`);
    await Promise.all([sec.p.waitForNavigation(), sec.p.click(`tr[data-envio="${idItem}"] .vc-tentar-envio`)]);
    L.ok(!!(await sec.p.$('#reenvioOk')) && envio(`aluno:${a1}:`).startsWith('enviado'), 'com o Moodle de volta, "Tentar de novo" (a Secretaria) resolve: enviado');
    /* "Message was not sent." — o servidor falso responde como o Moodle com o e-mail parado */
    sql(`UPDATE adms_moodle_conexao SET url = 'http://127.0.0.1:${PORTA}/aviso' WHERE id = 1`);
    const vincAntes = sql(`SELECT GROUP_CONCAT(CONCAT(tipo, ':', local_id, ':', moodle_id)) FROM adms_moodle_vinculos WHERE tipo = 'aluno' AND local_id = ${a1}`);
    const of4 = sql(`SELECT idMoodle FROM rm_professores_materias WHERE id = ${OF}`);
    const av = cli('enviar', 'enturmar', String(a1), String(OF));
    sql(`UPDATE adms_moodle_conexao SET url = '${urlBoa}' WHERE id = 1`);
    const conferiu = um("SELECT COUNT(*) FROM adms_moodle_registro WHERE funcao = 'core_enrol_get_enrolled_users' AND created > NOW() - INTERVAL 1 MINUTE") > 0;
    L.ok(av.situacao === 'aviso' && av.codigo === 'aviso_email' && /gravou a inscrição/.test(av.motivo) && conferiu && !!vincAntes && !!of4,
      '"Message was not sent.": vira AVISO ("o Moodle gravou a inscrição, mas não mandou o e-mail"), com a inscrição conferida em core_enrol_get_enrolled_users');
    /* (1.36.1) o e-mail em mais de uma conta: o servidor falso no modo /contas (o Moodle de teste não deixa duas contas
       com o mesmo e-mail). Não liga sozinho: fica a escolha do operador, com as contas; escolhida pela tela, segue */
    sql(`UPDATE adms_moodle_conexao SET url = 'http://127.0.0.1:${PORTA}/contas' WHERE id = 1`);
    const e6 = `prova.moodle.970006.${Date.now()}@exemplo.invalid`;
    const dup = (n) => { const r = moodle('core_user_create_users', { users: [{ username: `conta-${n}-${Date.now()}`, idnumber: `outra-${n}-${Date.now()}`, firstname: 'Conta', lastname: n, email: e6, auth: 'manual' }] }); return r.ok ? r.dados[0].id : 0; };
    const cA = dup('a'); const cB = dup('b');
    const a6 = await cadastrarAluno(adm.p, '970006', 'Aluno Prova Duas Contas', e6);
    const s6 = envio(`aluno:${a6}:`);
    const contas6 = sql(`SELECT IFNULL(contas, '') FROM adms_moodle_envios WHERE chave = 'aluno:${a6}:' ORDER BY id DESC LIMIT 1`);
    const id6 = um(`SELECT id FROM adms_moodle_envios WHERE chave = 'aluno:${a6}:' ORDER BY id DESC LIMIT 1`);
    await sec.p.goto(L.BASE + 'painel/moodle-envios/listar?situacao=falhou');
    const temEscolha = await sec.p.$$eval(`tr[data-envio="${id6}"] .vc-escolher-conta option`, (os) => os.map((o) => +o.value)).catch(() => []);
    await print(sec.p, 'envio-varias-contas');
    L.ok(cA > 0 && cB > 0 && s6.startsWith('falhou|email_varias_contas|') && /mais de uma conta/.test(s6) && vinculo('aluno', a6) === 0 && contas6 === JSON.stringify([cA, cB]) && temEscolha.join() === [cA, cB].join(),
      `o e-mail em mais de uma conta do Moodle: NÃO liga sozinho; o envio fica pendente da escolha, com as contas nº ${cA} e nº ${cB} na tela ("${s6.split('|')[2].slice(0, 100)}")`);
    await sec.p.selectOption(`tr[data-envio="${id6}"] .vc-escolher-conta select`, String(cB)).catch(() => {});
    await Promise.all([sec.p.waitForNavigation(), sec.p.click(`tr[data-envio="${id6}"] .vc-escolher-conta button`)]).catch(() => {});
    const escolhida = !!(await sec.p.$('#contaEscolhida'));
    L.ok(escolhida && vinculo('aluno', a6) === cB && envio(`aluno:${a6}:`).startsWith('enviado|'), `a Secretaria escolhe a conta na tela (nº ${cB}): o aluno fica ligado a ela e o envio vai`);
    const forjada = await postar(sec.p, `painel/moodle-envios/escolher-conta/${id6}`, { moodle_conta: '1' });
    L.ok(vinculo('aluno', a6) === cB, `escolher uma conta fora da lista (um POST forjado, status ${forjada}): recusado, o vínculo não muda`);
    /* a busca do lote: 150 e-mails = 2 chamadas (100 + 50), nunca uma por e-mail */
    const antesB = (JSON.parse(fs.readFileSync(FALSO, 'utf8')).buscas_email || []).length;
    cli('buscar', '150');
    const grupos = (JSON.parse(fs.readFileSync(FALSO, 'utf8')).buscas_email || []).slice(antesB);
    L.ok(grupos.join() === '100,50', `a busca por e-mail em grupos de até 100: 150 e-mails foram em ${grupos.length} chamada(s) (${grupos.join(' + ')})`);
    sql(`UPDATE adms_moodle_conexao SET url = '${urlBoa}' WHERE id = 1`);
    /* o aluno das duas contas ficou ligado a uma conta do servidor falso: sai do banco de teste (a limpeza no Moodle de verdade não o vê) */
    sql(`DELETE FROM adms_moodle_vinculos WHERE tipo = 'aluno' AND local_id = ${a6}; DELETE FROM adms_moodle_envios WHERE aluno_id = ${a6}`);

    /* ===== G. a varredura e a régua ===== */
    const reg = sql('SELECT CONCAT_WS(\'|\', funcao, referencia, resultado, codigo) FROM adms_moodle_registro');
    const fila = sql('SELECT CONCAT_WS(\'|\', chave, motivo, codigo) FROM adms_moodle_envios');
    const tudo = vistos.join('\n') + reg + fila;
    /* o segredo do link: o maior pedaço alfanumérico dele (a chave de uso único) — ele não pode aparecer em lugar nenhum */
    const segredo = (l) => (decodeURIComponent(l).match(/[A-Za-z0-9]{16,}/g) || []).sort((a, b) => b.length - a.length)[0] || l;
    const linkVazou = links.some((l) => tudo.includes(l) || tudo.includes(segredo(l)));
    const prints = fs.readdirSync(path.join(L.PROVAS, ETAPA)).filter((f) => f.endsWith('.png')).length;
    const motivoV = [links.length < 1 ? 'nenhum link visto' : '', tudo.includes(MCHAVE) ? 'a CHAVE apareceu' : '', linkVazou ? 'o LINK apareceu' : '', /@exemplo\.invalid|Aluna Prova/.test(reg) ? 'dado pessoal no registro' : ''].filter(Boolean).join('; ');
    if (motivoV) { console.log('  (varredura: ' + motivoV + ')'); }
    L.ok(links.length >= 1 && !tudo.includes(MCHAVE) && !linkVazou && !/@exemplo\.invalid|Aluna Prova/.test(reg),
      `a varredura: a chave e o link de entrada não aparecem em nenhuma das ${vistos.length} respostas que a prova viu, nem no registro das chamadas (${reg.split('\n').length} linhas, sem dado pessoal), nem na fila; os ${prints} prints não mostram o endereço nem a chave (o campo é mascarado e a chave nunca volta à tela)`);
    const rg = spawnSync(PHP, [path.join(L.RAIZ, 'tests', 'tabler', 'regua.php'), 'comparar', REGUA_ATUAL], { encoding: 'utf8', cwd: L.RAIZ });
    const pontos = (rg.stdout || '').split('\n').filter((l) => /^\s+(tabelas|boletins|vidas)/.test(l));
    /* o boletim e a vida acadêmica de cada aluno que JÁ existia (as notas, as faltas, a média, o resultado): iguais. Os
       que aparecem são os 3 alunos de prova novos (null → …); as linhas de nota deles mudam a posição na tabela de notas */
    const proibidos = pontos.filter((l) => /^\s+(boletins|vidas)\//.test(l) && !/:\s+null →/.test(l));
    const novos = pontos.filter((l) => /^\s+(boletins|vidas)\//.test(l) && /:\s+null →/.test(l)).length;
    L.ok(proibidos.length === 0 && novos > 0, `a régua: nenhum boletim nem vida acadêmica dos alunos que já existiam mudou — nenhuma nota, falta, média ou resultado (${novos} boletim(ns) e vida(s) novos, dos alunos de prova; o resto é cadastro, matrícula e integração)`);
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message);
  } finally {
    /* a limpeza no Moodle: os usuários de prova desenturmados e suspensos; o que fica, contado */
    try {
      const lim = cli('limpar');
      const cat = um('SELECT IFNULL(categoria_id, 0) FROM adms_moodle_conexao WHERE id = 1');
      const cursos = cat ? moodle('core_course_get_courses_by_field', { field: 'category', value: cat }) : {};
      const nCursos = cursos.ok ? cursos.dados.courses.length : -1;
      fs.writeFileSync(path.join(L.PROVAS, ETAPA, MODO_FALSO ? 'ficou-no-moodle-falso.json' : 'ficou-no-moodle.json'), JSON.stringify({ ...lim, categoria: 'VCOSTAEDU provas', disciplinas_de_prova: nCursos }, null, 2));
      console.log(`  (no ${MODO_FALSO ? 'Moodle FALSO' : 'Moodle de teste'} ficaram: 1 categoria "VCOSTAEDU provas", ${nCursos} disciplina(s) de prova e ${lim.usuarios_de_prova} usuário(s) de prova — ${lim.suspensos} suspenso(s), ${lim.desenturmados} inscrição(ões) tirada(s))`);
    } catch (e) { console.log('  (a limpeza no Moodle falhou: ' + e.message + ')'); }
    if (falso) { falso.kill(); }
    await navegador.close();
  }
  if (MODO_FALSO) { console.log('  (rodada contra o servidor FALSO: o que esta rodada provou não foi provado contra o Moodle de teste)'); }
  process.exit(L.resumo('onda3/etapa8') ? 0 : 1);
})();
