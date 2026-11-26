/*
 * PROVA DA 1.103.0, C4 — o financeiro de ligar e desligar nos três tipos (D-129). Usa o banco do ciclo (o ensino
 * superior, instalado do zero pelo instalador da tela, com a demonstração).
 *   1. de partida, ligado no ensino superior: o menu do administrador e da secretaria com o financeiro; a ficha financeira
 *      abre sem erro para um aluno COM lançamentos e para um SEM lançamentos; o aluno com título vê o cartão no portal;
 *   2. o administrador desliga em Configurações → Recursos da instituição (a tela só com o financeiro fora do curso livre):
 *      o menu, os relatórios do financeiro, o botão da ficha na ficha do aluno e os atalhos somem para os perfis; o
 *      endereço direto é recusado como página que não existe para o tipo; no portal, o cartão some e a ficha não abre;
 *      nenhum dado é apagado (os títulos contados antes e depois);
 *   3. ligando de novo, tudo volta;
 *   4. a instalação: o instalador tem a escolha (ligada de partida na escola e no ensino superior; no curso livre, a de
 *      sempre, desligada);
 *   sabotagem: a chave ignorada fora do curso livre — desligado, o financeiro continua no menu: a prova reprova; o
 *   arquivo volta igual (sha256).
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const L = require('../lib');
const X = require('./_escola');

const ETAPA = 'onda8/etapa56';
const SENHA_ADM = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const TIPO = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'helper', 'AdmsTipoInstituicao.php');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const { sql, um } = X;
const texto = async (p, sel) => ((await p.textContent(sel).catch(() => '')) || '').replace(/\s+/g, ' ').trim();
const titulos = () => um('SELECT COUNT(*) FROM fin_titulos');

/** O que um perfil do painel vê do financeiro: o menu, a ficha pelo endereço, os relatórios e o botão na ficha do aluno. */
async function painel(navegador, usuario, senha, comTitulo, semTitulo) {
  const s = await X.entrar(navegador, usuario, senha, 'painel', 1366);
  await s.p.goto(L.BASE + 'painel/home/index');
  /* o menu: os links do financeiro (a ficha do caixa, as listas e os planos) entre os itens dele */
  const menu = (await s.p.$$eval('a[href]', (l) => l.map((a) => a.getAttribute('href')))).join(' ');
  const fichas = [];
  for (const id of [comTitulo, semTitulo]) {
    await s.p.goto(L.BASE + 'painel/financeiro/ficha/' + id);
    fichas.push({ abriu: !!(await s.p.$('#fichaFinanceira')), recusada: !!(await s.p.$('#semAcesso')), erro: /Fatal error|Warning:|Exception/.test(await s.p.content()) });
  }
  await s.p.goto(L.BASE + 'painel/relatorios/listar').catch(() => {});
  const rel = await texto(s.p, 'body');
  await s.p.goto(L.BASE + 'painel/academicos/visualizar/' + comTitulo).catch(() => {});
  const botaoFicha = !!(await s.p.$('#abrirFichaFinanceira'));
  await s.ctx.close();
  return { menuFin: /\/(fin-planos|fin-regras|fin-motivos|fin-bandeiras|financeiro)\//.test(menu), fichas, relFin: /Caixa|financeiro/i.test(rel), botaoFicha };
}
async function portal(navegador, usuario, senha) {
  const s = await X.entrar(navegador, usuario, senha, 'portal', 1366);
  await s.p.goto(L.BASE + 'portal/home/index');
  const cartao = !!(await s.p.$('.vc-cartao-financeiro'));
  await s.p.goto(L.BASE + 'portal/home/financeiro-aluno');
  const ficha = !!(await s.p.$('#fichaFinanceira'));
  await s.ctx.close();
  return { cartao, ficha };
}
async function gravar(navegador, ligado) {
  const a = await X.entrar(navegador, 'administrador', SENHA_ADM, 'painel', 1366);
  await a.p.goto(L.BASE + 'painel/recursos-livre/index');
  const caixas = await a.p.$$eval('#formRecursosLivre input[type="checkbox"]', (l) => l.map((x) => x.name));
  const titulo = await texto(a.p, 'body');
  if (ligado) { await a.p.check('#recurso_financeiro'); } else { await a.p.uncheck('#recurso_financeiro'); }
  await Promise.all([a.p.waitForNavigation(), a.p.click('#salvarRecursos')]);
  const ok = !!(await a.p.$('#recursosGravados'));
  await L.print(a.p, ETAPA, ligado ? 'recursos-ligado' : 'recursos-desligado');
  await a.ctx.close();
  return { caixas, titulo, ok };
}

(async () => {
  const { navegador } = await L.abrir();
  const shaT = sha(TIPO);
  try {
    L.ok(sql("SELECT valor FROM adms_instalacao_estado WHERE chave = 'instituicao.tipo'") === 'superior', 'o banco do ciclo: o ensino superior, instalado do zero pelo instalador');
    const c = JSON.parse(fs.readFileSync(path.join(L.RAIZ, 'tests', 'tabler', 'saida', 'credenciais.json'), 'utf8'));
    const portalUsers = Object.keys(c.portal);
    const lista = portalUsers.map((u) => `'${u.replace(/'/g, '')}'`).join(',');
    /* a demonstração da faculdade não traz títulos: um título avulso pela ficha, num aluno que entra no portal */
    const comTitulo = um(`SELECT u.id FROM col_usuarios u WHERE u.col_niveis_acesso_id = 1 AND u.rm IN (${lista}) ORDER BY u.id LIMIT 1`);
    if (!um(`SELECT COUNT(*) FROM fin_titulos WHERE aluno_id = ${comTitulo}`)) {
      const a0 = await X.entrar(navegador, 'administrador', SENHA_ADM, 'painel', 1366);
      await a0.p.goto(L.BASE + 'painel/financeiro/ficha/' + comTitulo);
      await a0.p.click('#botaoNovoTitulo');
      await a0.p.waitForSelector('#modalNovoTitulo.show', { timeout: 5000 });
      await a0.p.fill('#avDesc', 'Material didático de exemplo');
      await a0.p.fill('#avValor', '150,00');
      await a0.p.fill('#avVenc', X.hoje());
      await Promise.all([a0.p.waitForNavigation(), a0.p.click('#lancarAvulso')]);
      await a0.ctx.close();
    }
    const semTitulo = um('SELECT u.id FROM col_usuarios u WHERE u.col_niveis_acesso_id = 1 AND NOT EXISTS (SELECT 1 FROM fin_titulos t WHERE t.aluno_id = u.id) ORDER BY u.id LIMIT 1');
    const rmCom = sql(`SELECT rm FROM col_usuarios WHERE id = ${comTitulo}`);
    const sec = Object.keys(c.painel).find((u) => /secretaria/.test(u)) || 'administrador';
    const t0 = titulos();

    /* 1. de partida, ligado */
    const a1 = await painel(navegador, 'administrador', SENHA_ADM, comTitulo, semTitulo);
    const s1 = await painel(navegador, sec, c.painel[sec] || SENHA_ADM, comTitulo, semTitulo);
    const p1 = c.portal[rmCom] ? await portal(navegador, rmCom, c.portal[rmCom]) : null;
    L.ok(comTitulo > 0 && semTitulo > 0 && a1.menuFin && s1.menuFin && a1.fichas.every((f) => f.abriu && !f.erro),
      `1. de partida, ligado no ensino superior: o financeiro no menu (administrador e ${sec}); a ficha abre sem erro com lançamentos e sem lançamentos`);
    L.ok(!p1 || (p1.cartao && p1.ficha), `1. no portal, o aluno com título vê o cartão e a ficha${p1 ? '' : ' (sem a senha do aluno com título: conferido só no painel)'}`);

    /* 2. desligado */
    const g0 = await gravar(navegador, false);
    const chave = sql("SELECT valor FROM adms_instalacao_estado WHERE chave = 'financeiro.ligado'");
    const a2 = await painel(navegador, 'administrador', SENHA_ADM, comTitulo, semTitulo);
    const s2 = await painel(navegador, sec, c.painel[sec] || SENHA_ADM, comTitulo, semTitulo);
    const p2 = c.portal[rmCom] ? await portal(navegador, rmCom, c.portal[rmCom]) : null;
    L.ok(g0.ok && chave === '0' && g0.caixas.length === 1 && g0.caixas[0] === 'recurso[financeiro]' && /Recursos da instituição/.test(g0.titulo),
      `2. a tela "Recursos da instituição" (só o financeiro, fora do curso livre) desliga e grava (financeiro.ligado = ${chave})`);
    L.ok(!a2.menuFin && !s2.menuFin && a2.fichas.every((f) => !f.abriu && f.recusada) && s2.fichas.every((f) => !f.abriu) && !a2.botaoFicha,
      '2. desligado: sem o financeiro no menu (administrador e secretaria), sem o botão da ficha na ficha do aluno; o endereço direto é recusado como página que não existe');
    L.ok(!p2 || (!p2.cartao && !p2.ficha), '2. desligado, no portal: sem o cartão e sem a ficha do financeiro');
    L.ok(titulos() === t0 && t0 > 0, `2. nenhum dado apagado (${titulos()} títulos, como antes)`);

    /* 3. ligado de novo */
    const g1 = await gravar(navegador, true);
    const a3 = await painel(navegador, 'administrador', SENHA_ADM, comTitulo, semTitulo);
    const p3 = c.portal[rmCom] ? await portal(navegador, rmCom, c.portal[rmCom]) : null;
    L.ok(g1.ok && a3.menuFin && a3.fichas.every((f) => f.abriu) && a3.botaoFicha === a1.botaoFicha && (!p3 || (p3.cartao && p3.ficha)) && titulos() === t0,
      '3. ligando de novo, tudo volta (o menu, a ficha, o botão, o portal) com os mesmos títulos');

    /* 4. a instalação */
    const idx = fs.readFileSync(path.join(L.RAIZ, 'instalar', 'index.php'), 'utf8');
    L.ok(/id="financeiroTipo"/.test(idx) && /name="financeiro" value="1" id="financeiroLigado" checked/.test(idx) && /gravarFinanceiro\(\$conexao\['pdo'\], \$tipo, !empty\(\$_POST\['financeiro'\]\)\)/.test(idx),
      '4. o instalador tem a escolha do financeiro na escola e no ensino superior (ligada de partida); no curso livre, a de sempre');

    /* sabotagem: a chave ignorada fora do curso livre */
    const orig = fs.readFileSync(TIPO, 'utf8');
    const trecho = "return $v === null ? \\VcTipoLivre::financeiroPadrao(self::atual()) : (string) $v === '1';";
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let sab = null;
    try {
      sql("INSERT INTO adms_instalacao_estado (chave, valor) VALUES ('financeiro.ligado', '0') ON DUPLICATE KEY UPDATE valor = '0'");
      fs.writeFileSync(TIPO, orig.replace(trecho, "return self::ehLivre() ? ($v === null ? false : (string) $v === '1') : true;"));
      sab = await painel(navegador, 'administrador', SENHA_ADM, comTitulo, semTitulo);
    } finally {
      fs.writeFileSync(TIPO, orig);
      sql("UPDATE adms_instalacao_estado SET valor = '1' WHERE chave = 'financeiro.ligado'");
    }
    L.ok(sab && sab.menuFin, 'sabotagem (a chave ignorada fora do curso livre): desligado, o financeiro continua no menu — a prova reprova');
    L.ok(sha(TIPO) === shaT, 'o arquivo sabotado voltou igual (sha256)');
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split(String.fromCharCode(10))[0]);
  } finally {
    if (sha(TIPO) !== shaT) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    sql("UPDATE adms_instalacao_estado SET valor = '1' WHERE chave = 'financeiro.ligado'");
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
