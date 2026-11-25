/*
 * PROVA DA ONDA 8, CORREÇÃO, PARTE 1.3 — o cartão das senhas de demonstração (D-73). APAGA o banco de prova.
 *   1. a demonstração do colégio, montada pela tela numa instalação nova;
 *   2. no cartão "Conheça o sistema", todas as senhas do mesmo jeito: escondidas, com "Mostrar" e "Copiar" (a equipe,
 *      o aluno e os responsáveis);
 *   3. "Copiar", com a senha ESCONDIDA, põe a senha verdadeira na área de transferência (e não a máscara);
 *   4. um responsável de demonstração entra no portal com a senha copiada pelo botão (os dois filhos);
 *   5. a secretaria entra no painel com a senha copiada do mesmo jeito;
 *   sabotagem: "Copiar" copiando o que está à mostra (a máscara) — a prova acusa (o responsável não entra); o arquivo
 *   volta igual (sha256).
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { spawnSync, execFileSync } = require('child_process');
const L = require('../lib');

const ETAPA = 'onda8/etapa7';
const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste$/.test(BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
const SENHA = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const VIEW = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Views', 'home', '_comecar.php');
const H = 'helena.ribeiro@exemplo.invalid';

const sql = (q) => execFileSync('C:/xampp/mysql/bin/mysql.exe', ['-uroot', '--default-character-set=utf8mb4', '-N', '-B', BANCO, '-e', q], { encoding: 'utf8' }).split('\n').map((l) => l.trim()).filter(Boolean).join('\n');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const montar = (rotulo) => spawnSync(process.execPath, [path.join(__dirname, '..', 'onda6', 'medir_demo.js'), 'escola', rotulo], { encoding: 'utf8', maxBuffer: 1 << 24 });

/** O administrador na Página Inicial, com a área de transferência liberada para a página. */
async function cartao(navegador) {
  const ctx = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' });
  await ctx.grantPermissions(['clipboard-read', 'clipboard-write'], { origin: new URL(L.BASE).origin });
  const p = await L.pagina(ctx);
  await p.goto(L.BASE + 'painel/login/acesso');
  await p.fill('input[name="usuario"]', 'administrador');
  await p.fill('input[name="senha"]', SENHA);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
  await p.goto(L.BASE + 'painel/home/index');
  return { ctx, p };
}
/** Clica em "Copiar" da linha (a senha continua escondida) e devolve o que foi para a área de transferência. */
async function copiar(p, linha) {
  await p.evaluate(() => navigator.clipboard.writeText('(vazio)'));
  await p.click(`${linha} .vc-copiar-senha`);
  await p.waitForFunction((l) => /Copiada|Não copiou/.test(document.querySelector(`${l} .vc-copiar-senha`).textContent), linha, { timeout: 5000 }).catch(() => {});
  return p.evaluate(() => navigator.clipboard.readText());
}
async function entrarPortal(navegador, usuario, senha) {
  const ctx = await navegador.newContext({ viewport: { width: 390, height: 844 }, locale: 'pt-BR' });
  const p = await L.pagina(ctx);
  await p.goto(L.BASE + 'portal/login/acesso');
  await p.fill('input[name="usuario"]', usuario);
  await p.fill('input[name="senha"]', senha);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
  const filhos = await p.$$eval('#listaFilhos [data-aluno]', (l) => l.length).catch(() => 0);
  await ctx.close();
  return filhos;
}

(async () => {
  const { navegador } = await L.abrir();
  const shaView = sha(VIEW);
  try {
    const r = montar('onda8-etapa7');
    L.ok(r.status === 0, 'a demonstração do colégio montada pela tela, numa instalação nova');

    /* 2. todas do mesmo jeito */
    const c = await cartao(navegador);
    const linhas = await c.p.$$eval('#tabelaPerfisDemo tbody tr', (l) => l.map((t) => ({
      perfil: t.cells[0].textContent.trim(),
      mascara: (t.querySelector('.vc-senha-oculta') || { textContent: '' }).textContent.trim(),
      mostrar: !!t.querySelector('.vc-mostrar-senha'), copiar: !!t.querySelector('.vc-copiar-senha'),
      aberta: [...t.querySelectorAll('code')].some((x) => !x.classList.contains('vc-senha-oculta') && x.textContent.trim().length >= 12 && !/[@.]/.test(x.textContent)),
    })));
    const iguais = linhas.length >= 9 && linhas.every((x) => x.mascara === '••••••••' && x.mostrar && x.copiar && !x.aberta);
    await L.print(c.p, ETAPA, 'cartao-senhas');
    L.ok(iguais, `as ${linhas.length} senhas do cartão do mesmo jeito: escondidas, com "Mostrar" e "Copiar" (${[...new Set(linhas.map((x) => x.perfil))].join(', ')})`);

    /* 3. copiar com a senha escondida */
    const cred = JSON.parse(sql("SELECT valor FROM adms_instalacao_estado WHERE chave = 'demonstracao.credenciais'"));
    const linhaH = `.vc-demo-familia[data-usuario="${H}"]`;
    const copiada = await copiar(c.p, linhaH);
    const aindaEscondida = (await c.p.textContent(`${linhaH} .vc-senha-oculta`)).trim() === '••••••••';
    const linhaSec = '#tabelaPerfisDemo tbody tr:has(code:text-is("secretaria.demo"))';
    const copiadaSec = await copiar(c.p, linhaSec);
    await c.ctx.close();
    L.ok(copiada === (cred.familia || {})[H] && aindaEscondida && copiada !== '••••••••',
      `"Copiar" com a senha escondida copia a senha verdadeira (${copiada.length} caracteres, não a máscara; a senha continua escondida na tela)`);

    /* 4. o responsável entra com a senha copiada */
    const filhos = await entrarPortal(navegador, H, copiada);
    L.ok(filhos === 2, `o responsável de demonstração entra no portal com a senha copiada pelo botão (${filhos} filhos)`);

    /* 5. a secretaria entra com a senha copiada */
    const ctxS = await navegador.newContext({ locale: 'pt-BR' });
    const ps = await L.pagina(ctxS);
    await ps.goto(L.BASE + 'painel/login/acesso');
    await ps.fill('input[name="usuario"]', 'secretaria.demo');
    await ps.fill('input[name="senha"]', copiadaSec);
    await Promise.all([ps.waitForNavigation(), ps.click('[name="SendLogin"]')]);
    const dentro = !/login\/acesso/.test(ps.url());
    await ctxS.close();
    L.ok(dentro && copiadaSec === (cred.painel || {})['secretaria.demo'], 'a secretaria entra no painel com a senha copiada do cartão');

    /* sabotagem: "Copiar" copia o que está à mostra */
    const orig = fs.readFileSync(VIEW, 'utf8');
    const trecho = "var senha = b.parentNode.querySelector('.vc-senha-oculta').getAttribute('data-senha');";
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let filhosSab = -1;
    try {
      fs.writeFileSync(VIEW, orig.replace(trecho, "var senha = b.parentNode.querySelector('.vc-senha-oculta').textContent;"));
      const cs = await cartao(navegador);
      const errada = await copiar(cs.p, linhaH);
      await cs.ctx.close();
      filhosSab = await entrarPortal(navegador, H, errada);
    } finally {
      fs.writeFileSync(VIEW, orig);
    }
    L.ok(filhosSab === 0, 'sabotagem ("Copiar" copiando a máscara): a prova reprova — o responsável não entra');
    L.ok(sha(VIEW) === shaView, 'o arquivo sabotado voltou igual (sha256)');
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(VIEW) !== shaView) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
