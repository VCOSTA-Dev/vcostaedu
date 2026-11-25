/*
 * PROVA DA ONDA 8, ETAPA 2 — o e-mail do aluno no Moodle, em três modos (D-68), contra o Moodle de teste.
 *   A. no ensino superior (o banco que o ciclo deixa): o padrão é "só o e-mail do aluno" (como sempre foi), e a tela
 *      salva sem domínio;
 *   B. na escola (a demonstração do colégio, montada pela tela; APAGA o banco de prova):
 *      1. Conectar ao Moodle: o padrão é "sempre o e-mail da escola"; a tela mostra os três modos, o domínio, um exemplo,
 *         e diz que o endereço não recebe mensagens, que o "Esqueci a senha" não serve para ele e que a entrada é pelo
 *         portal; salvar sem o domínio é recusado;
 *      2. modo 1: dois irmãos sem e-mail vão com endereços diferentes (a matrícula de cada um no domínio); o aluno com
 *         e-mail próprio vai com o da escola também; o cadastro deles continua sem e-mail;
 *      3. a conta que já existia no Moodle (o mesmo idnumber, outro e-mail): o aluno é ligado a ela, e o e-mail dela não
 *         muda — nem no envio do aluno, nem na troca do domínio;
 *      4. modo 2: o aluno com e-mail próprio passa a ir com ele (a conta criada pelo sistema acompanha); dois irmãos com
 *         o MESMO e-mail no cadastro vão com o da escola, cada um com a própria conta;
 *      5. modo 3: o aluno sem e-mail e o aluno com o e-mail repetido ficam fora, com o motivo na tela Envios ao Moodle;
 *      6. a troca do domínio (de volta ao modo 1): as contas que o sistema criou vão com o endereço novo; a que já
 *         existia, não;
 *   sabotagens: (a) o e-mail atualizado em toda conta ligada — a prova acusa (o e-mail da conta que já existia muda);
 *   (b) o e-mail repetido aceito como próprio — a prova acusa (o irmão vai ao Moodle com o e-mail da família). Os
 *   arquivos voltam iguais (sha256).
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE, VCOSTAEDU_MOODLE_URL, VCOSTAEDU_MOODLE_CHAVE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { spawnSync, execFileSync } = require('child_process');
const L = require('../lib');

const ETAPA = 'onda8/etapa2';
const SAIDA = path.join(__dirname, '..', 'saida');
const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste$/.test(BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
const SENHA = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const MURL = process.env.VCOSTAEDU_MOODLE_URL || '';
const MCHAVE = process.env.VCOSTAEDU_MOODLE_CHAVE || '';
const ENVIO = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'moodle', 'AdmsMoodleEnvio.php');
const MOODLE = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'moodle', 'AdmsMoodle.php');
const API = L.BASE + 'api/v1';
const D1 = 'alunos.escola-prova.invalid';
const D2 = 'estudantes.escola-prova.invalid';
/* a matrícula de cada rodada (o Moodle de teste guarda as contas das rodadas anteriores) */
const R = String(Date.now()).slice(-6);
const RA = (i) => `8${R}${i}`;
const FAMILIA = `familia.o8e2.${R}@exemplo.invalid`;
const PROPRIO = `aluna.propria.${R}@exemplo.invalid`;
const ANTIGO = `conta.antiga.${R}@exemplo.invalid`;

const sql = (q) => execFileSync('C:/xampp/mysql/bin/mysql.exe', ['-uroot', '--default-character-set=utf8mb4', '-N', '-B', BANCO, '-e', q], { encoding: 'utf8' }).trim();
const um = (q) => +(sql(q).split('\n')[0] || 0);
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const idAluno = (ra) => um(`SELECT id FROM col_usuarios WHERE rm = '${ra}'`);
function cli(...args) {
  const r = spawnSync('C:/xampp/php/php.exe', [path.join(L.RAIZ, 'tests', 'tabler', 'onda3', 'moodle_cli.php'), ...args], { cwd: L.RAIZ, encoding: 'utf8', env: process.env });
  try { return JSON.parse((r.stdout || '').trim().split('\n').pop()); } catch (e) { return { erro: (r.stderr || r.stdout || '').slice(0, 200) }; }
}
const enviarAluno = (ra) => cli('enviar', 'aluno', String(idAluno(ra)), '-');
/** O id da conta do Moodle pelo idnumber de prova (0 = não existe). */
function contaMoodle(ra) {
  const r = cli('chamar', 'core_user_get_users_by_field', JSON.stringify({ field: 'idnumber', values: ['prova-' + ra] }));
  const u = r.ok ? (r.dados || [])[0] : null;
  return u ? +u.id : 0;
}
/**
 * O e-mail de uma conta no Moodle. O serviço do Moodle de teste não mostra o e-mail das contas (e a configuração dele não
 * muda); a conferência é pela busca do plugin, ao contrário: as contas que têm este e-mail.
 */
function contasDoEmail(email) {
  const r = cli('chamar', 'local_vcostaedu_find_users_by_email', JSON.stringify({ emails: [email] }));
  return r.ok ? ((r.dados || [])[0] || {}).userids || [] : null;
}
/** A conta do aluno no Moodle tem este e-mail (e é a única com ele)? */
const comEmail = (ra, email) => { const id = contaMoodle(ra); const ids = contasDoEmail(email); return id > 0 && Array.isArray(ids) && ids.length === 1 && +ids[0] === id; };
function sabotar(arq, de, para) {
  const orig = fs.readFileSync(arq, 'utf8');
  if (!orig.includes(de)) { throw new Error('a sabotagem não achou o trecho em ' + path.basename(arq)); }
  fs.writeFileSync(arq, orig.replace(de, para));
  return () => fs.writeFileSync(arq, orig);
}
function criarChave(permissoes) {
  const chave = 'vce_' + crypto.randomBytes(20).toString('hex');
  sql(`INSERT INTO adms_api_chaves (nome, prefixo, hash, escopo, permissoes, situacao, usuario_id) VALUES ('Prova e-mail', '${chave.slice(0, 12)}', '${crypto.createHash('sha256').update(chave).digest('hex')}', 'escrita', '${permissoes}', 1, 1)`);
  return chave;
}
async function api(metodo, rota, chave, corpo) {
  const r = await fetch(API + rota, { method: metodo, headers: { Authorization: 'Bearer ' + chave, 'Content-Type': 'application/json' }, body: corpo ? JSON.stringify(corpo) : undefined });
  let j = null;
  try { j = await r.json(); } catch (e) { /* */ }
  return { status: r.status, j };
}
async function entrarPainel(navegador) {
  const ctx = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' });
  const p = await L.pagina(ctx);
  await p.goto(L.BASE + 'painel/login/acesso');
  await p.fill('input[name="usuario"]', 'administrador');
  await p.fill('input[name="senha"]', process.env.SENHA_ADM || SENHA);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
  return { ctx, p };
}
/** Conectar ao Moodle pela tela: o modo e o domínio; devolve o aviso que a tela mostrou. */
async function salvarModo(p, modo, dominio) {
  await p.goto(L.BASE + 'painel/moodle/conexao');
  if (modo) { await p.check(`input[name="email_modo"][value="${modo}"]`); }
  if (dominio !== undefined) { await p.fill('#emailDominio', dominio); }
  await Promise.all([p.waitForNavigation({ timeout: 120000 }), p.click('#salvarMoodle')]);
  return ((await p.$$eval('.alert', (l) => l.map((e) => e.innerText.replace(/\s+/g, ' ').trim()).join(' | ')).catch(() => '')) || '');
}

(async () => {
  const { navegador } = await L.abrir();
  const shas = { [ENVIO]: sha(ENVIO), [MOODLE]: sha(MOODLE) };
  try {
    console.log('\n== Onda 8, Etapa 2 — o e-mail do aluno no Moodle, em três modos');
    if (!MURL || !MCHAVE) { throw new Error('esta prova roda contra o Moodle de teste (VCOSTAEDU_MOODLE_URL e VCOSTAEDU_MOODLE_CHAVE)'); }

    /* A. o ensino superior */
    const cred = JSON.parse(fs.readFileSync(path.join(SAIDA, 'credenciais.json'), 'utf8'));
    process.env.SENHA_ADM = cred.painel.administrador;
    let s = await entrarPainel(navegador);
    await s.p.goto(L.BASE + 'painel/moodle/conexao');
    const supModo = await s.p.getAttribute('#emailModo', 'data-modo').catch(() => '');
    const supMarcado = !!(await s.p.$('#emailModoAluno:checked'));
    const modos = await s.p.$$eval('#emailModo input[name="email_modo"]', (l) => l.map((i) => i.value)).catch(() => []);
    await s.ctx.close();
    L.ok(supModo === 'aluno' && supMarcado && modos.join(',') === 'escola,aluno_ou_escola,aluno',
      `no ensino superior, o padrão continua "só o e-mail do aluno" (${supModo}); a tela mostra os três modos`);
    delete process.env.SENHA_ADM;

    /* B. a escola */
    const r = spawnSync(process.execPath, [path.join(__dirname, '..', 'onda6', 'medir_demo.js'), 'escola', 'onda8-etapa2'], { encoding: 'utf8', maxBuffer: 1 << 24 });
    L.ok(r.status === 0, 'a demonstração do colégio montada pela tela');
    s = await entrarPainel(navegador);
    const p = s.p;
    sql("UPDATE adms_parametros SET valor = 'prova-' WHERE chave = 'moodle.prefixo'");

    /* 1. Conectar ao Moodle */
    await p.goto(L.BASE + 'painel/moodle/conexao');
    const padrao = await p.getAttribute('#emailModo', 'data-modo');
    const marcado = !!(await p.$('#emailModoEscola:checked'));
    const aviso = ((await p.textContent('#avisoEmailEscola').catch(() => '')) || '').replace(/\s+/g, ' ');
    const exemplo = ((await p.textContent('#exemploEmail').catch(() => '')) || '').trim();
    await p.fill('#url', MURL);
    await p.fill('#chave', MCHAVE);
    await Promise.all([p.waitForNavigation(), p.click('#salvarMoodle')]);
    const semDominio = !!(await p.$('#moodleRecusado')) && um("SELECT COUNT(*) FROM adms_moodle_conexao WHERE url IS NOT NULL AND url <> ''") === 0;
    await p.goto(L.BASE + 'painel/moodle/conexao');
    await p.fill('#url', MURL);
    await p.fill('#chave', MCHAVE);
    await p.fill('#emailDominio', D1);
    await Promise.all([p.waitForNavigation(), p.click('#salvarMoodle')]);
    await p.goto(L.BASE + 'painel/moodle/conexao');
    await Promise.all([p.waitForNavigation(), p.click('#testarMoodle')]);
    const testou = !!(await p.$('#testeOk'));
    await p.goto(L.BASE + 'painel/moodle/conexao');
    await L.print(p, ETAPA, 'conectar-ao-moodle-email-escola');
    L.ok(padrao === 'escola' && marcado && /não recebe mensagens/.test(aviso) && /Esqueci a senha/.test(aviso) && /pelo portal/.test(aviso) && /^260101@/.test(exemplo) && semDominio && testou,
      `na escola, o padrão é "sempre o e-mail da escola"; a tela explica o endereço (não recebe mensagens; sem "Esqueci a senha"; a entrada pelo portal), com o exemplo (${exemplo}); sem o domínio, salvar é recusado; com ele, a conexão testada`);

    /* os alunos entram pela interface de integração: o envio ao Moodle vai no fim da própria chamada, no modo em vigor */
    const chave = criarChave('alunos:gravar');
    const criar = async (lista) => {
      let n = 0;
      for (const [ra, nome, sexo, email] of lista) {
        const a = await api('POST', '/alunos', chave, { ra, nome, sexo, nascimento: '2014-05-10', ...(email ? { email } : {}) });
        n += a.status === 201 ? 1 : 0;
      }
      return n === lista.length;
    };
    const situacao = (ra) => sql(`SELECT CONCAT(situacao, '/', IFNULL(codigo, '')) FROM adms_moodle_envios WHERE chave = 'aluno:${idAluno(ra)}:' ORDER BY id DESC LIMIT 1`);
    const nenhuma = (email) => { const ids = contasDoEmail(email); return Array.isArray(ids) && ids.length === 0; };

    /* a conta que já existia no Moodle: o mesmo idnumber do aluno que vai entrar, outro e-mail */
    const antiga = cli('chamar', 'core_user_create_users', JSON.stringify({ users: [{ username: `prova-antiga-${R}`, password: 'Vc' + crypto.randomBytes(10).toString('hex') + '#9Z', auth: 'manual', idnumber: 'prova-' + RA(6), firstname: 'Conta', lastname: 'Antiga Exemplo', email: ANTIGO }] }));
    const idAntiga = contaMoodle(RA(6));
    L.ok(antiga.ok && idAntiga > 0 && comEmail(RA(6), ANTIGO), 'no Moodle de teste, uma conta que já existia antes (o mesmo idnumber do aluno que vai entrar, outro e-mail)');

    /* 2. modo 1: sempre o e-mail da escola */
    const ok2 = await criar([[RA(1), 'Irmão Um Prova Exemplo', 'masculino', ''], [RA(2), 'Irmã Dois Prova Exemplo', 'feminino', ''],
      [RA(3), 'Aluna Própria Prova Exemplo', 'feminino', PROPRIO], [RA(6), 'Aluno Conta Antiga Exemplo', 'masculino', '']]);
    const [c1, c2] = [contaMoodle(RA(1)), contaMoodle(RA(2))];
    L.ok(ok2 && situacao(RA(1)) === 'enviado/' && situacao(RA(2)) === 'enviado/' && c1 > 0 && c2 > 0 && c1 !== c2 && comEmail(RA(1), `${RA(1)}@${D1}`) && comEmail(RA(2), `${RA(2)}@${D1}`),
      'modo 1: dois irmãos sem e-mail, criados pela interface, vão ao Moodle na hora com endereços diferentes (a matrícula de cada um no domínio), cada um com a sua conta');
    L.ok(situacao(RA(3)) === 'enviado/' && comEmail(RA(3), `${RA(3)}@${D1}`) && nenhuma(PROPRIO) && sql(`SELECT email FROM col_usuarios WHERE rm = '${RA(3)}'`) === PROPRIO,
      'modo 1: a aluna com e-mail próprio também vai com o da escola (o dela não está em conta nenhuma do Moodle); o cadastro dela fica com o dela');

    /* 3. a conta que já existia */
    const e6b = enviarAluno(RA(6)); // o envio seguinte do aluno (com a atualização do nome)
    const vinc6 = sql(`SELECT CONCAT(moodle_id, ':', criado) FROM adms_moodle_vinculos WHERE tipo = 'aluno' AND local_id = ${idAluno(RA(6))}`);
    L.ok(e6b.situacao === 'enviado' && vinc6 === `${idAntiga}:0` && comEmail(RA(6), ANTIGO) && nenhuma(`${RA(6)}@${D1}`),
      'a conta que já existia no Moodle: o aluno é ligado a ela (marcada como não criada pelo sistema), e o e-mail dela não muda no envio nem no envio seguinte');
    const cadastroVazio = um(`SELECT COUNT(*) FROM col_usuarios WHERE rm IN ('${RA(1)}', '${RA(2)}', '${RA(6)}') AND (email IS NULL OR email = '')`) === 3;
    L.ok(cadastroVazio && um("SELECT COUNT(*) FROM col_usuarios WHERE email LIKE '%escola-prova.invalid'") === 0,
      'o cadastro dos alunos sem e-mail continua sem e-mail: o endereço da escola não é gravado no cadastro');

    /* 4. modo 2: o do aluno, ou o da escola */
    const m2 = await salvarModo(p, 'aluno_ou_escola');
    if (!comEmail(RA(3), PROPRIO)) { enviarAluno(RA(3)); } // o que não coube no orçamento da requisição de salvar
    /* o primeiro irmão chega quando o e-mail da família ainda é só dele: vai com ele; o segundo chega com o mesmo e-mail */
    const ok4a = await criar([[RA(4), 'Irmão Quatro Família Exemplo', 'masculino', FAMILIA]]);
    const antes4 = comEmail(RA(4), FAMILIA);
    const ok4 = ok4a && await criar([[RA(5), 'Irmã Cinco Família Exemplo', 'feminino', FAMILIA]]);
    const voltou4 = situacao(RA(4)) === 'pendente/'; // o primeiro voltou à fila sozinho
    if (voltou4) { enviarAluno(RA(4)); }
    const [c4, c5] = [contaMoodle(RA(4)), contaMoodle(RA(5))];
    L.ok(/salvas/.test(m2) && /criados pelo sistema/.test(m2) && comEmail(RA(3), PROPRIO),
      'modo 2: a aluna com e-mail próprio passa a ir com ele; a conta criada pelo sistema acompanha a troca do modo');
    L.ok(ok4 && antes4 && voltou4 && situacao(RA(5)) === 'enviado/' && c4 > 0 && c5 > 0 && c4 !== c5 && comEmail(RA(4), `${RA(4)}@${D1}`) && comEmail(RA(5), `${RA(5)}@${D1}`) && nenhuma(FAMILIA),
      'modo 2: o primeiro irmão foi com o e-mail da família (ainda só dele); o segundo chega com o mesmo e vai com o da escola, e o primeiro volta à fila sozinho e passa ao da escola — cada um com a própria conta, e o e-mail da família em conta nenhuma');

    /* 5. modo 3: só o do aluno */
    await salvarModo(p, 'aluno');
    const ok5 = await criar([[RA(7), 'Aluna Sem E-mail Exemplo', 'feminino', ''], [RA(8), 'Irmão Oito Família Exemplo', 'masculino', FAMILIA]]);
    const id7 = um(`SELECT id FROM adms_moodle_envios WHERE chave = 'aluno:${idAluno(RA(7))}:' ORDER BY id DESC LIMIT 1`);
    const id8 = um(`SELECT id FROM adms_moodle_envios WHERE chave = 'aluno:${idAluno(RA(8))}:' ORDER BY id DESC LIMIT 1`);
    await p.goto(L.BASE + 'painel/moodle-envios/listar?situacao=falhou');
    const linha = async (id) => ((await p.$eval(`tr[data-envio="${id}"]`, (t) => t.innerText.replace(/\s+/g, ' ')).catch(() => '')) || '');
    const t7 = await linha(id7);
    const t8 = await linha(id8);
    await L.print(p, ETAPA, 'envios-sem-email-e-repetido');
    L.ok(ok5 && situacao(RA(7)) === 'falhou/sem_email' && situacao(RA(8)) === 'falhou/email_repetido' && !contaMoodle(RA(7)) && !contaMoodle(RA(8)) && /não tem e-mail/.test(t7) && /mesmo de outro aluno/.test(t8),
      `modo 3: o aluno sem e-mail e o irmão com o e-mail repetido ficam fora do Moodle, com o motivo em Envios ao Moodle (${situacao(RA(7))}, ${situacao(RA(8))})`);

    /* 6. a troca do domínio, de volta ao modo 1 */
    const m6 = await salvarModo(p, 'escola', D2);
    const criados = [RA(1), RA(2), RA(3), RA(4), RA(5)];
    const naHora = criados.filter((ra) => comEmail(ra, `${ra}@${D2}`)).length;
    for (const ra of criados) { if (!comEmail(ra, `${ra}@${D2}`)) { enviarAluno(ra); } }
    const todos = criados.every((ra) => comEmail(ra, `${ra}@${D2}`));
    L.ok(/criados pelo sistema/.test(m6) && todos && comEmail(RA(6), ANTIGO),
      `a troca do domínio: as ${criados.length} contas que o sistema criou vão com o endereço novo (${naHora} na própria requisição de salvar); a que já existia fica com o e-mail dela`);

    /* sabotagem (a): o e-mail atualizado em toda conta ligada */
    let sabA = false;
    const voltaA = sabotar(ENVIO, '                if ($this->criadaPeloSistema($alunoId)) {', '                if (true) {');
    try { enviarAluno(RA(6)); sabA = !comEmail(RA(6), ANTIGO); } finally { voltaA(); }
    if (sabA) { cli('chamar', 'core_user_update_users', JSON.stringify({ users: [{ id: idAntiga, email: ANTIGO }] })); }
    L.ok(sabA, 'sabotagem (a) (o e-mail atualizado em toda conta ligada): a prova reprova — a conta que já existia teve o e-mail trocado (e voltou ao dela)');

    /* sabotagem (b): o e-mail repetido aceito como próprio (no modo 3) */
    await salvarModo(p, 'aluno');
    let sabB = {};
    const voltaB = sabotar(MOODLE, '            } elseif (self::emailRepetido($alunoId, $proprio)) {', '            } elseif (false) {');
    try { sabB = enviarAluno(RA(8)); } finally { voltaB(); }
    L.ok(sabB.codigo !== 'email_repetido', `sabotagem (b) (o e-mail repetido aceito): a prova reprova — o irmão ficou "${sabB.situacao}/${sabB.codigo || ''}"`);
    L.ok(sha(ENVIO) === shas[ENVIO] && sha(MOODLE) === shas[MOODLE], 'os arquivos sabotados voltaram iguais (sha256)');

    const lim = cli('limpar');
    console.log(`  (no Moodle de teste ficaram os usuários de prova desta rodada, suspensos: ${lim.suspensos ?? '?'})`);
    await s.ctx.close();
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    for (const [f, h] of Object.entries(shas)) { if (sha(f) !== h) { console.log(`  [ALERTA] ${path.basename(f)} não voltou igual`); } }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
