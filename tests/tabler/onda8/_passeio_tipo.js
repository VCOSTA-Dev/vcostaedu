/*
 * O PASSEIO PELO MENU DE UM TIPO (Fechamento; D-106): ajuda das provas do tipo de instituição. Para cada perfil, os
 * itens do menu do painel (o rótulo e o endereço) e, abrindo cada um, se a tela abre (sem erro do PHP, sem "não
 * permitido"); no portal, o mesmo para o aluno. Não muda nada no banco.
 */
const L = require('../lib');
const X = require('./_escola');

/** Os itens do menu da página aberta: [{rotulo, href}] (o rail do painel e do portal têm o mesmo desenho). */
async function itensDoMenu(p) {
  return p.$$eval('#railMenu a.nav-link[href], #railMenu a.dropdown-item[href]', (as) => as
    .map((a) => ({ rotulo: (a.getAttribute('title') || a.textContent || '').trim().replace(/\s+/g, ' '), href: a.href }))
    .filter((x) => x.href && !x.href.endsWith('#') && !/logout/.test(x.href) && !x.href.startsWith('javascript')));
}

/** Abre cada item: {rotulo, href, ok, motivo}. */
async function abrirCada(p, itens) {
  const r = [];
  for (const it of itens) {
    let motivo = '';
    try {
      const resp = await p.goto(it.href, { waitUntil: 'domcontentloaded' });
      const html = await p.content();
      const st = resp ? resp.status() : 0;
      if (st >= 400) motivo = `HTTP ${st}`;
      else if (/(Warning|Notice|Fatal error|Deprecated|Uncaught)\b[^<]{0,80}(on line|in )/.test(html)) motivo = 'erro do PHP';
      else if (/id="naoPermitido"|Página não permitida|não tem permissão/i.test(html)) motivo = 'não permitido';
      else if (/name="SendLogin"/.test(html)) motivo = 'caiu na entrada';
    } catch (e) { motivo = 'falhou: ' + e.message.slice(0, 80); }
    r.push({ ...it, ok: motivo === '', motivo, url: p.url() });
  }
  return r;
}

async function painel(navegador, usuario, senha) {
  const s = await X.entrar(navegador, usuario, senha, 'painel', 1366);
  await s.p.goto(L.BASE + 'painel/home/index');
  const itens = await itensDoMenu(s.p);
  const abertos = await abrirCada(s.p, itens);
  await s.ctx.close();
  return abertos;
}

async function portal(navegador, usuario, senha) {
  const s = await X.entrar(navegador, usuario, senha, 'portal', 1366);
  const entrou = !/login\/acesso/.test(s.p.url());
  await s.p.goto(L.BASE + 'portal/home/index');
  const itens = await itensDoMenu(s.p);
  const abertos = await abrirCada(s.p, itens);
  return { entrou, itens: abertos, s };
}

module.exports = { itensDoMenu, abrirCada, painel, portal };

if (require.main === module) {
  (async () => {
    const { navegador } = await L.abrir();
    const c = X.cred();
    for (const u of ['administrador', 'secretaria.demo', 'direcao.demo', 'coord.demo', 'prof.marcos.demo']) {
      const senha = u === 'administrador' ? (process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123') : c.painel[u];
      if (!senha) continue;
      const r = await painel(navegador, u, senha);
      console.log(`\n== ${u}: ${r.length} itens`);
      r.forEach((x) => console.log(`  ${x.ok ? 'ok ' : 'XX '} ${x.rotulo.padEnd(44)} ${x.href.replace(L.BASE, '')}${x.ok ? '' : '  <- ' + x.motivo + ' ' + x.url.replace(L.BASE, '')}`));
    }
    const ra = Object.keys(c.portal || {})[0];
    if (ra) {
      const r = await portal(navegador, ra, c.portal[ra]);
      console.log(`\n== portal ${ra}: entrou=${r.entrou}, ${r.itens.length} itens`);
      r.itens.forEach((x) => console.log(`  ${x.ok ? 'ok ' : 'XX '} ${x.rotulo.padEnd(44)} ${x.href.replace(L.BASE, '')}${x.ok ? '' : '  <- ' + x.motivo}`));
      await r.s.ctx.close();
    }
    await navegador.close();
  })().catch((e) => { console.error(e); process.exit(1); });
}
