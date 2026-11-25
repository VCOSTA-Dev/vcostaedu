/*
 * Prova da sessão (Etapa 2): o identificador muda no login (session_regenerate_id) no painel e no portal, e a
 * situação e o nível são relidos a cada requisição no painel — usuário inativado ou com o nível trocado sai na
 * requisição seguinte; quem volta a Ativo entra de novo.
 *
 * A mudança de situação é feita como o administrador faria, mas direto no banco de PROVA (mysql do XAMPP), e
 * desfeita no fim. Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE; credenciais da demonstração em
 * tests/tabler/saida/credenciais.json.
 */
const { execFileSync } = require('child_process');
const fs = require('fs');
const path = require('path');
const L = require('./lib');

const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste$/.test(BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
const MYSQL = process.env.VCOSTAEDU_MYSQL || 'C:/xampp/mysql/bin/mysql.exe';
const sql = (q) => execFileSync(MYSQL, ['-u', 'root', '-N', BANCO, '-e', q]).toString().trim();
const cred = JSON.parse(fs.readFileSync(path.join(__dirname, 'saida', 'credenciais.json'), 'utf8'));

async function entrar(p, app, usuario, senha) {
  await p.goto(L.BASE + app + '/login/acesso');
  await p.fill('input[name="usuario"]', usuario);
  await p.fill('input[name="senha"]', senha);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
}
const cookie = async (ctx, nome) => ((await ctx.cookies()).find((c) => c.name === nome) || {}).value || '';

(async () => {
  const { navegador, contexto } = await L.abrir();
  const p = await L.pagina(contexto);
  try {
    console.log('\n== Sessão');
    await p.goto(L.BASE + 'painel/login/acesso');
    const antes = await cookie(contexto, 'VCOSTAEDU_PAINEL');
    await entrar(p, 'painel', 'secretaria.demo', cred.painel['secretaria.demo']);
    const depois = await cookie(contexto, 'VCOSTAEDU_PAINEL');
    L.ok(antes !== '' && depois !== '' && antes !== depois, 'painel: o identificador da sessão muda no login');
    L.ok(/\/painel\/home\/index/.test(p.url()), 'secretaria entrou');

    await p.goto(L.BASE + 'painel/academicos/listar');
    L.ok(!(await p.isVisible('input[name="SendLogin"]')), 'secretaria abre Alunos');

    sql("UPDATE adms_usuarios SET adms_sits_usuario_id = 3 WHERE usuario = 'secretaria.demo'");
    await p.goto(L.BASE + 'painel/academicos/listar');
    L.ok(await p.isVisible('input[name="SendLogin"]'), 'inativada pelo administrador: a requisição seguinte cai no login');
    sql("UPDATE adms_usuarios SET adms_sits_usuario_id = 1 WHERE usuario = 'secretaria.demo'");

    await entrar(p, 'painel', 'secretaria.demo', cred.painel['secretaria.demo']);
    L.ok(/\/painel\/home\/index/.test(p.url()), 'reativada: entra de novo');
    sql("UPDATE adms_usuarios SET adms_niveis_acesso_id = 6 WHERE usuario = 'secretaria.demo'");
    await p.goto(L.BASE + 'painel/academicos/listar');
    L.ok(await p.isVisible('input[name="SendLogin"]'), 'nível trocado pelo administrador: a requisição seguinte cai no login');
    sql("UPDATE adms_usuarios SET adms_niveis_acesso_id = 4 WHERE usuario = 'secretaria.demo'");

    sql("UPDATE adms_usuarios SET adms_sits_usuario_id = 2 WHERE usuario = 'secretaria.demo'");
    await entrar(p, 'painel', 'secretaria.demo', cred.painel['secretaria.demo']);
    await p.goto(L.BASE + 'painel/academicos/listar');
    L.ok(/perfil\/atualizar-dados|atualizarDados/i.test(p.url()) || (await p.isVisible('text=Atualize seus dados')), '"Atualizar Dados" continua entrando e é levado à tela de atualização (portão da 1.18.4)');
    sql("UPDATE adms_usuarios SET adms_sits_usuario_id = 1 WHERE usuario = 'secretaria.demo'");

    const c2 = await navegador.newContext();
    const a = await c2.newPage();
    await a.goto(L.BASE + 'portal/login/acesso');
    const pa = await cookie(c2, 'VCOSTAEDU_PORTAL');
    await a.fill('input[name="usuario"]', '260101');
    await a.fill('input[name="senha"]', cred.portal['260101']);
    await Promise.all([a.waitForNavigation(), a.click('[name="SendLogin"]')]);
    const pd = await cookie(c2, 'VCOSTAEDU_PORTAL');
    L.ok(/\/portal\/home\/index/.test(a.url()), 'aluno entrou no portal');
    L.ok(pa !== '' && pd !== '' && pa !== pd, 'portal: o identificador da sessão muda no login');
  } catch (e) {
    L.ok(false, 'erro na prova: ' + e.message.split('\n')[0]);
  } finally {
    sql("UPDATE adms_usuarios SET adms_sits_usuario_id = 1, adms_niveis_acesso_id = 4 WHERE usuario = 'secretaria.demo'");
    await navegador.close();
    process.exit(L.resumo('sessão') ? 0 : 1);
  }
})();
