/*
 * PROVA DA ONDA 8, CORREÇÃO, PARTE 3.1 — a página sem acesso (D-76). APAGA o banco de prova.
 *   1. a demonstração do colégio, montada pela tela numa instalação nova;
 *   2. a professora abre uma tela que o perfil dela não abre (a lista das turmas): "Você não tem acesso a esta tela",
 *      HTTP 403, o nome da tela e o perfil; o botão "Voltar" leva à página de onde ela veio, e ela continua conectada
 *      (a Página Inicial abre sem pedir a senha);
 *   3. a Direção acadêmica (só consulta) abre uma página que grava (a nova turma): a mesma página, sem a tela de entrada;
 *   4. o pedido em JSON recebe a recusa em JSON (403), não a página;
 *   5. quem não entrou continua indo à tela de entrada;
 *   sabotagem: o roteador sem a página sem acesso — a prova acusa (volta a tela de entrada); o arquivo volta igual.
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { spawnSync, execFileSync } = require('child_process');
const L = require('../lib');

const ETAPA = 'onda8/etapa10';
const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste$/.test(BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
const ROTEADOR = path.join(L.RAIZ, 'painel', 'core', 'ConfigController.php');

const sql = (q) => execFileSync('C:/xampp/mysql/bin/mysql.exe', ['-uroot', '--default-character-set=utf8mb4', '-N', '-B', BANCO, '-e', q], { encoding: 'utf8' }).split('\n').map((l) => l.trim()).filter(Boolean).join('\n');
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
/** Abre a rota vindo de outra página do sistema (o "de onde veio"). */
async function abrir(p, rota, vindoDe) {
  const r = await p.goto(L.BASE + rota, { referer: vindoDe ? L.BASE + vindoDe : undefined });
  return {
    status: r ? r.status() : 0, sem: !!(await p.$('#semAcesso')), login: !!(await p.$('input[name="SendLogin"]')),
    titulo: ((await p.$eval('h2, .page-title', (e) => e.textContent).catch(() => '')) || '').trim(),
    texto: ((await p.textContent('#semAcesso').catch(() => '')) || '').replace(/\s+/g, ' ').trim(),
    voltar: await p.getAttribute('#voltarDeOnde', 'href').catch(() => null),
  };
}

(async () => {
  const { navegador } = await L.abrir();
  const shaRot = sha(ROTEADOR);
  try {
    const r = montar('onda8-etapa10');
    L.ok(r.status === 0, 'a demonstração do colégio montada pela tela, numa instalação nova');
    const cred = JSON.parse(sql("SELECT valor FROM adms_instalacao_estado WHERE chave = 'demonstracao.credenciais'"));

    /* 2. a professora, numa tela que não é do perfil dela */
    const prof = await entrar(navegador, 'prof.laura.demo', (cred.painel || {})['prof.laura.demo'] || '');
    const a = await abrir(prof.p, 'painel/turmas/listar', 'painel/minhas-disciplinas/listar');
    await L.print(prof.p, ETAPA, 'sem-acesso-professora');
    let voltou = '';
    if (a.voltar) { await Promise.all([prof.p.waitForNavigation(), prof.p.click('#voltarDeOnde')]); voltou = prof.p.url().replace(L.BASE, ''); }
    await prof.p.goto(L.BASE + 'painel/home/index');
    const conectada = !(await prof.p.$('input[name="SendLogin"]')) && /home\/index/.test(prof.p.url());
    L.ok(a.status === 403 && a.sem && !a.login && /não tem acesso/i.test(a.titulo + a.texto) && /Professor/.test(a.texto) && /minhas-disciplinas\/listar/.test(voltou) && conectada,
      `a professora numa tela que o perfil não abre: ${a.status}, "Você não tem acesso a esta tela" ("${a.texto.slice(0, 90)}…"); "Voltar" leva a ${voltou || '—'}; continua conectada (${conectada})`);

    /* 4. o pedido em JSON */
    const j = await prof.p.evaluate(async (u) => {
      const x = await fetch(u, { method: 'POST', credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'dia=2&aula=1&oferta=0' });
      return { st: x.status, tipo: x.headers.get('content-type') || '', t: await x.text() };
    }, L.BASE + 'painel/turmas/gravar-horario/1');
    await prof.ctx.close();
    L.ok(j.st === 403 && /json/.test(j.tipo) && /não tem acesso/.test(j.t), `o pedido em JSON recebe a recusa em JSON (${j.st}, ${j.tipo.split(';')[0]}), não a página`);

    /* 3. a Direção (só consulta) numa página que grava */
    const dir = await entrar(navegador, 'direcao.demo', (cred.painel || {})['direcao.demo'] || '');
    const b = await abrir(dir.p, 'painel/turmas/cadastrar', 'painel/turmas/listar');
    await dir.ctx.close();
    L.ok(b.status === 403 && b.sem && !b.login && /Dire/.test(b.texto), `a Direção acadêmica (só consulta) numa página que grava: ${b.status}, a página sem acesso, sem a tela de entrada`);

    /* 5. quem não entrou */
    const ctxN = await navegador.newContext({ locale: 'pt-BR' });
    const pn = await L.pagina(ctxN);
    await pn.goto(L.BASE + 'painel/turmas/listar');
    const loginN = !!(await pn.$('input[name="SendLogin"]')) && !(await pn.$('#semAcesso'));
    await ctxN.close();
    L.ok(loginN, 'quem não entrou continua indo à tela de entrada');

    /* sabotagem: o roteador sem a página sem acesso */
    const orig = fs.readFileSync(ROTEADOR, 'utf8');
    const trecho = '        } elseif ($this->semPermissao()) {\n            $this->responderSemAcesso();\n';
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let s = null;
    try {
      fs.writeFileSync(ROTEADOR, orig.replace(trecho, ''));
      const ps = await entrar(navegador, 'prof.laura.demo', (cred.painel || {})['prof.laura.demo'] || '');
      s = await abrir(ps.p, 'painel/turmas/listar', 'painel/minhas-disciplinas/listar');
      await ps.ctx.close();
    } finally {
      fs.writeFileSync(ROTEADOR, orig);
    }
    L.ok(s && !s.sem, 'sabotagem (o roteador sem a página sem acesso): a prova reprova — a página sem acesso não aparece');
    L.ok(sha(ROTEADOR) === shaRot, 'o arquivo sabotado voltou igual (sha256)');
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(ROTEADOR) !== shaRot) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
