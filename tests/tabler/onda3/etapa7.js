/*
 * ONDA 3, ETAPA 7 — os ajustes de tela.
 *   A. o rodapé: as letras das duas marcas da altura da maiúscula da fonte, na linha de base do texto, o capelo acima
 *      dela; sem o nome escrito ao lado da marca; nos dois temas, no painel, no portal, nas telas de acesso, no
 *      instalador e no Sobre (a medida em px e o print ampliado);
 *   B. a instalação que veio por atualização (os arquivos novos sobre um banco da 1.28.0, atualizado PELA TELA):
 *      com a demonstração montada pelo código da 1.28.0 antes da atualização, e sem ela, com os Primeiros passos em
 *      zero — Dados da instituição abre, mostra as opções novas, salva, e o topo muda;
 *   C. os Primeiros passos: "Depois do passo N" no lugar de "Depende de outra fase"; "Abrir…" e "Faça comigo"
 *      apagados com o motivo (ao passar o mouse e ao tocar); "Pular por agora" continua; feito o passo anterior, o selo
 *      e os botões mudam sozinhos;
 *   D. o Sobre: a marca formatada (títulos, listas, negrito) a partir do TRADEMARK.md da raiz.
 *   Sabotagens: a marca de volta à altura da imagem; o número do passo trocado; a marca crua no Sobre.
 *
 *   node tests/tabler/onda3/etapa7.js     (depois do ciclo; troca a Config do painel para o banco de prova de
 *                                          atualização e a devolve; monta e apaga a pasta da versão antiga)
 */
const fs = require('fs');
const path = require('path');
const { execFileSync, spawnSync } = require('child_process');
const L = require('../lib');
const { medir } = require('./medir_rodape');

const BASE_BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste$/.test(BASE_BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
const BANCO2 = BASE_BANCO + '2';
const MYSQL = process.env.VCOSTAEDU_MYSQL || 'C:/xampp/mysql/bin/mysql.exe';
const PHP = process.env.VCOSTAEDU_PHP || 'C:/xampp/php/php.exe';
const sql = (q, banco = BASE_BANCO) => execFileSync(MYSQL, ['-u', 'root', '-N', '--default-character-set=utf8mb4', banco, '-e', q]).toString().replace(/\r/g, '').trim();
const sql2 = (q) => sql(q, BANCO2);
let cred = JSON.parse(fs.readFileSync(path.join(__dirname, '..', 'saida', 'credenciais.json'), 'utf8'));
const ETAPA = 'onda3/etapa7';
const SENHA = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const ANTIGA = '55b0125'; // a 1.28.0 (Onda 3, Etapa 2)
const CONFIG = path.join(L.RAIZ, 'painel', 'core', 'Config.php');
const CSS = path.join(L.RAIZ, 'assets', 'css', 'vcostaedu-componentes.css');
const MODELO_PASSOS = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'helper', 'AdmsPrimeirosPassos.php');
const SOBRE = path.join(L.RAIZ, 'sobre.php');
const CNPJ = '11.222.333/0001-81'; // o CNPJ de exemplo dos manuais (fictício, com os dígitos certos)
const TOL = 0.6; // px

/* o retrato atual da onda (o da etapa mais nova): o banco montado pelo ciclo tem todas as migrations */
const REGUA_ATUAL = L.reguaAtual(); // o retrato mais novo, de qualquer onda (lib.js)

async function entrar(navegador, app, usuario, senha, extra = {}) {
  const ctx = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR', ...extra });
  const p = await L.pagina(ctx);
  await p.goto(L.BASE + app + '/login/acesso');
  await p.fill('input[name="usuario"]', usuario);
  await p.fill('input[name="senha"]', senha);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
  return { ctx, p };
}
async function sabotar(nome, arquivo, de, para, conferir) {
  const orig = fs.readFileSync(arquivo, 'utf8');
  let reprovou = false;
  try {
    if (!orig.includes(de)) { throw new Error('sabotagem sem o trecho: ' + nome); }
    fs.writeFileSync(arquivo, orig.replace(de, para));
    reprovou = !(await conferir());
  } finally { fs.writeFileSync(arquivo, orig); }
  L.ok(reprovou && fs.readFileSync(arquivo, 'utf8') === orig, `sabotagem (${nome}): a prova reprova o defeito; o arquivo voltou`);
}
const php = (args, entrada) => spawnSync(PHP, args, { cwd: L.RAIZ, encoding: 'utf8', env: process.env, input: entrada, maxBuffer: 1 << 26 });
const certa = (m) => m && Math.abs(m.edu - m.cap) <= TOL && Math.abs(m.vcosta - m.cap) <= TOL && Math.abs(m.baseEdu) <= TOL && Math.abs(m.baseVcosta) <= TOL && m.capelo > m.cap * 1.3;
const descreve = (m) => m ? `maiúscula do texto ${m.cap} px; letras VCOSTAEDU ${m.edu} px, VCOSTA ${m.vcosta} px; pé das letras a ${m.baseEdu} e ${m.baseVcosta} px da linha de base` : 'sem rodapé';
async function escuro(p, ligar) { await p.evaluate((l) => { if (l) { document.documentElement.setAttribute('data-bs-theme', 'dark'); } else { document.documentElement.removeAttribute('data-bs-theme'); } }, ligar); }
async function printRodape(p, nome) {
  await p.locator('.vc-legal:visible').first().screenshot({ path: path.join(L.PROVAS, ETAPA, nome + '.png') });
}

/* a Config do painel apontando para o banco de prova de atualização (e de volta) */
const configOriginal = fs.readFileSync(CONFIG, 'utf8');
function apontar(demonstracao) {
  let s = configOriginal.replace(/define\('DBNAME',\s*'[^']*'\)/, `define('DBNAME', '${BANCO2}')`);
  s = demonstracao ? s.replace(/define\('BANCO_DEMONSTRACAO',\s*'[^']*'\)/, `define('BANCO_DEMONSTRACAO', '${BANCO2}')`)
    : s.replace(/define\('AMBIENTE',\s*'[^']*'\);/, "define('AMBIENTE', 'producao');");
  fs.writeFileSync(CONFIG, s);
}

/* atualiza pela tela; devolve a versão nova */
async function atualizarPelaTela(p) {
  await p.goto(L.BASE + 'painel/atualizar/index');
  await p.check('#fizBackup');
  await p.click('#aplicarAtualizacao'); // Onda 3, Etapa 11: a janela de progresso, uma etapa por chamada
  await p.waitForSelector('#progressoAtualizacao[data-situacao="concluida"], #progressoAtualizacao[data-situacao="falhou"]', { timeout: 300000 });
  await p.goto(L.BASE + 'painel/atualizar/index');
  return (await p.textContent('#versaoDepois').catch(() => '')).trim();
}

/* Dados da instituição: abre, as opções novas, salva com o topo escolhido; devolve o topo depois */
async function instituicao(p, topo, preencher) {
  await p.goto(L.BASE + 'painel/dados-instituicao/editar');
  const abre = !!(await p.$('#formInstituicao'));
  const opcoes = { topo: await p.$$eval('#topo_menu option', (o) => o.map((x) => x.value)).catch(() => []), aba: await p.$$eval('#icone_aba option', (o) => o.map((x) => x.value)).catch(() => []) };
  if (preencher) { await preencher(p); }
  await p.selectOption('#topo_menu', topo);
  await Promise.all([p.waitForNavigation(), p.click('[name="SalvarInstituicao"]')]);
  const msg = (await p.textContent('.alert-success').catch(() => '')) || '';
  const t = await p.$eval('#topoMenu', (e) => ({ tipo: e.dataset.tipo, texto: (e.querySelector('.vc-side-nome') || {}).innerText || '', logo: !!e.querySelector('.vc-side-nome img') })).catch(() => ({}));
  return { abre, opcoes, salvo: /salvos/.test(msg), topo: t };
}

(async () => {
  const { navegador } = await L.abrir();
  fs.mkdirSync(path.join(L.PROVAS, ETAPA), { recursive: true });
  try {
    console.log('\n== Onda 3, Etapa 7');

    /* A. o rodapé */
    const ctxA = await navegador.newContext({ viewport: { width: 1366, height: 900 }, deviceScaleFactor: 3, locale: 'pt-BR' });
    const pa = await L.pagina(ctxA);
    const lugares = [['painel/login/acesso', 'a entrada do painel'], ['portal/login/acesso', 'a entrada do portal'], ['sobre.php', 'o Sobre'], ['instalar/', 'o instalador (recusando)']];
    for (const [rota, nome] of lugares) {
      await pa.goto(L.BASE + rota);
      const claro = await medir(pa);
      await escuro(pa, true);
      const esc = await medir(pa);
      const semNome = await pa.$eval('.vc-legal', (e) => !/\bVCOSTAEDU\b/.test(e.innerText)).catch(() => false);
      L.ok(certa(claro) && certa(esc) && /-branco/.test(esc.arquivoEdu) && /-branco/.test(esc.arquivoVcosta) && !/-branco/.test(claro.arquivoEdu) && semNome,
        `${nome}: ${descreve(claro)}; no tema escuro, as versões de letras brancas (${descreve(esc)}); sem o nome escrito ao lado da marca`);
      if (rota === 'painel/login/acesso') { await printRodape(pa, 'rodape-escuro'); await escuro(pa, false); await printRodape(pa, 'rodape-claro'); }
      await escuro(pa, false);
    }
    await ctxA.close();
    for (const [app, usuario, senha] of [['painel', 'administrador', SENHA], ['portal', '260101', cred.portal['260101']]]) {
      const o = await entrar(navegador, app, usuario, senha, { deviceScaleFactor: 3 });
      const m = await medir(o.p);
      await escuro(o.p, true);
      const me = await medir(o.p);
      L.ok(certa(m) && certa(me), `${app === 'painel' ? 'o painel' : 'o portal'}, dentro: ${descreve(m)} (e no escuro: ${me ? me.edu + ' e ' + me.vcosta + ' px' : '—'})`);
      await escuro(o.p, false);
      await printRodape(o.p, `rodape-${app}-dentro`);
      await o.ctx.close();
    }
    await sabotar('a marca de volta à altura da imagem', CSS, 'height: calc(1cap * 1.639);', 'height: 14px;', async () => {
      const c = await navegador.newContext({ viewport: { width: 1366, height: 900 } }); const q = await L.pagina(c);
      await q.goto(L.BASE + 'painel/login/acesso'); const m = await medir(q); await c.close(); return certa(m);
    });

    /* B e C. a instalação que veio por atualização */
    /* B1: com a demonstração montada pelo código da 1.28.0 */
    let r = php([path.join(L.RAIZ, 'tests', 'tabler', 'onda2', 'banco_antigo.php'), ANTIGA]);
    const antigo = JSON.parse((r.stdout || '{}').trim().split('\n').pop() || '{}');
    r = php([path.join(__dirname, 'instalacao_antiga.php'), ANTIGA]);
    const r2 = php([path.join(__dirname, 'instalacao_antiga.php'), '--demo']);
    /* numa atualização de verdade, os arquivos novos chegam à MESMA pasta: o logo gravado pela versão antiga continua
       em assets/instituicao/. Aqui a versão antiga rodou em outra pasta — o logo dela vem para a pasta de trabalho */
    const logosAntigos = [];
    const dirAntigo = L.RAIZ + '-antiga/assets/instituicao';
    for (const f of (fs.existsSync(dirAntigo) ? fs.readdirSync(dirAntigo) : []).filter((x) => /^logo_[a-f0-9]{16}\.png$/.test(x))) {
      fs.copyFileSync(path.join(dirAntigo, f), path.join(L.RAIZ, 'assets', 'instituicao', f)); logosAntigos.push(f);
    }
    const versaoAntes = sql2('SELECT numero FROM adms_versoes WHERE situacao = 1');
    const empresas = +sql2('SELECT COUNT(*) FROM adms_empresas WHERE instituicao = 1');
    L.ok(antigo.admin && r.status === 0 && r2.status === 0 && versaoAntes === '1.28.0' && empresas === 1 && logosAntigos.length >= 1,
      `o banco da 1.28.0 (${antigo.aplicadas} migrations, pelo mesmo runner) com a demonstração montada pelas telas do código da 1.28.0 (servido à parte)`);
    try {
      apontar(true);
      let o = await entrar(navegador, 'painel', 'administrador', SENHA);
      const v1 = await atualizarPelaTela(o.p);
      L.ok(/^1\.\d+\.\d+$/.test(v1) && v1 !== '1.28.0', `com a demonstração: atualizado pela tela, da 1.28.0 à ${v1}`);
      let d = await instituicao(o.p, 'nome');
      L.ok(d.abre && d.opcoes.topo.join(',') === 'nome,logo' && d.opcoes.aba.length === 2 && d.salvo && d.topo.tipo === 'nome' && d.topo.texto.trim().length > 3,
        `com a demonstração: Dados da instituição abre, com "No topo do menu" (${d.opcoes.topo.join(', ')}) e "Ícone da aba" (${d.opcoes.aba.join(', ')}); salva; o topo mostra o nome ("${d.topo.texto.replace(/\s+/g, ' ').trim()}")`);
      await L.print(o.p, ETAPA, 'atualizada-com-demonstracao-nome');
      d = await instituicao(o.p, 'logo');
      L.ok(d.salvo && d.topo.tipo === 'logo' && d.topo.logo, 'com a demonstração: escolhido o logo, o topo mostra o logo da instituição');
      await L.print(o.p, ETAPA, 'atualizada-com-demonstracao-logo');
      await o.ctx.close();

      /* B2: sem a demonstração, os Primeiros passos em zero */
      r = php([path.join(L.RAIZ, 'tests', 'tabler', 'onda2', 'banco_antigo.php'), ANTIGA]);
      apontar(false);
      o = await entrar(navegador, 'painel', 'administrador', SENHA);
      const v2 = await atualizarPelaTela(o.p);
      L.ok(v2 === v1 && +sql2('SELECT COUNT(*) FROM adms_empresas WHERE instituicao = 1') === 0, `sem a demonstração, nada cadastrado: atualizado pela tela, da 1.28.0 à ${v2}`);
      const antes = await o.p.$eval('#topoMenu', (e) => e.dataset.tipo).catch(() => '');
      d = await instituicao(o.p, 'nome', async (q) => {
        await q.fill('#nome', 'Faculdade Exemplo da Prova (fictícia)'); await q.fill('#razao_social', 'Mantenedora Exemplo da Prova (fictícia)'); await q.fill('#cnpj', CNPJ);
      });
      L.ok(antes === 'vcostaedu' && d.abre && d.opcoes.topo.join(',') === 'nome,logo' && d.salvo && d.topo.tipo === 'nome' && /Faculdade Exemplo da Prova/.test(d.topo.texto),
        `sem a demonstração: antes, o topo diz VCOSTAEDU; Dados da instituição abre, salva a primeira vez, e o topo passa ao nome ("${d.topo.texto.replace(/\s+/g, ' ').trim()}")`);
      await L.print(o.p, ETAPA, 'atualizada-sem-demonstracao');

      /* C. os Primeiros passos, em zero (só a instituição feita) */
      const fases = async (q) => { await q.goto(L.BASE + 'painel/primeiros-passos/index'); return q.$$eval('.vc-fase', (fs) => fs.map((f) => ({ codigo: f.dataset.fase, estado: f.dataset.estado,
        selo: f.querySelector('.vc-fase-estado').innerText.trim(), abrirLink: !!f.querySelector('a.vc-fase-abrir'), abrirTravado: !!f.querySelector('.acao-desabilitada button.vc-fase-abrir[disabled]'),
        motivo: (f.querySelector('.vc-fase-abrir-travado') || {}).title || (f.querySelector('.vc-fase-abrir-travado') || { getAttribute: () => '' }).getAttribute('data-bs-original-title') || '',
        facaLink: !!f.querySelector('a.vc-faca-comigo'), facaTravado: !!f.querySelector('.acao-desabilitada button.vc-faca-comigo[disabled]'), pular: !!f.querySelector('.vc-fase-pular') }))); };
      let f = await fases(o.p);
      const porCod = (c) => f.find((x) => x.codigo === c) || {};
      L.ok(porCod('oferta').selo === 'Depois dos passos 4 e 5' && porCod('alunos').selo === 'Depois do passo 5' && porCod('enturmar').selo === 'Depois dos passos 6 e 7',
        `o selo diz de qual passo depende: oferta "${porCod('oferta').selo}", alunos "${porCod('alunos').selo}", enturmar "${porCod('enturmar').selo}"`);
      const travadas = f.filter((x) => x.estado === 'bloqueada');
      L.ok(travadas.length === 3 && travadas.every((x) => !x.abrirLink && x.abrirTravado && x.pular && !x.facaLink),
        `as 3 fases travadas: "Abrir…" apagado (sem link), "Pular por agora" disponível${travadas.some((x) => x.facaTravado) ? '; "Faça comigo" apagado também' : ''}`);
      /* o motivo, ao passar o mouse e ao tocar */
      const alvo = '.vc-fase[data-fase="alunos"] .vc-fase-abrir-travado';
      await o.p.hover(alvo);
      await o.p.waitForSelector('.tooltip.show', { timeout: 3000 }).catch(() => {});
      const dicaMouse = await o.p.$eval('.tooltip.show', (t) => t.innerText.trim()).catch(() => '');
      await o.p.mouse.move(0, 0);
      L.ok(/^Antes, conclua o passo 5 \(/.test(dicaMouse), `ao passar o mouse: "${dicaMouse}"`);
      await L.print(o.p, ETAPA, 'primeiros-passos-travados');
      const toque = await entrar(navegador, 'painel', 'administrador', SENHA, { viewport: { width: 390, height: 844 }, hasTouch: true, isMobile: true });
      await toque.p.goto(L.BASE + 'painel/primeiros-passos/index');
      await toque.p.tap(alvo);
      await toque.p.waitForSelector('.tooltip.show', { timeout: 3000 }).catch(() => {});
      const dicaToque = await toque.p.$eval('.tooltip.show', (t) => t.innerText.trim()).catch(() => '');
      L.ok(/^Antes, conclua o passo 5 \(/.test(dicaToque), `ao tocar (390 px, tela de toque): "${dicaToque}"`);
      await L.print(toque.p, ETAPA, 'primeiros-passos-toque');
      await toque.ctx.close();
      await sabotar('o número do passo trocado', MODELO_PASSOS, '$depois[] = $numero[$d] + 1;', '$depois[] = $numero[$d];', async () => {
        f = await fases(o.p); return (f.find((x) => x.codigo === 'alunos') || {}).selo === 'Depois do passo 5';
      });
      /* feito o passo 4: a oferta passa a depender só do 5; feito o 5: a oferta e os alunos acendem */
      sql2("INSERT INTO adms_periodos_letivos (ano, situacaodisciplina, situacaomodelo, situacao, usuario_cre) VALUES (2026, 1, 1, 1, 1)");
      f = await fases(o.p);
      L.ok(porCod('oferta').selo === 'Depois do passo 5', `feito o passo 4 (o ano letivo), a oferta passa a "${porCod('oferta').selo}", sozinha`);
      sql2('INSERT INTO uni_rh_cursos_disciplinas_grade (grade_id, modulo_id, materia_id, cargahoraria, usuario_cre) VALUES (1, 1, 1, 30, 1)');
      f = await fases(o.p);
      const ac = (c) => /^A (fazer|próxima)$/.test(porCod(c).selo) && porCod(c).abrirLink && !porCod(c).abrirTravado;
      L.ok(ac('oferta') && ac('alunos') && porCod('enturmar').selo === 'Depois dos passos 6 e 7',
        `feito o passo 5 (o primeiro curso), a oferta e os alunos passam a "${porCod('oferta').selo}" e "${porCod('alunos').selo}", com os botões acesos; o enturmar continua "${porCod('enturmar').selo}"`);
      await L.print(o.p, ETAPA, 'primeiros-passos-acesos');
      await o.ctx.close();
    } finally {
      fs.writeFileSync(CONFIG, configOriginal);
      for (const f of logosAntigos) { try { fs.unlinkSync(path.join(L.RAIZ, 'assets', 'instituicao', f)); } catch (e) { /* já saiu */ } }
      php([path.join(__dirname, 'instalacao_antiga.php'), '--apagar']);
    }
    L.ok(fs.readFileSync(CONFIG, 'utf8') === configOriginal, 'a Config do painel voltou ao banco de prova de sempre; a pasta da versão antiga, apagada');

    /* D. o Sobre: a marca formatada */
    const ctxD = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' });
    const pd = await L.pagina(ctxD);
    const sobre = async () => { await pd.goto(L.BASE + 'sobre.php'); return pd.evaluate(() => { const m = document.querySelector('#marca'); return m ? { pre: !!m.querySelector('pre'),
      h: m.querySelectorAll('h2, h3, h4').length, li: m.querySelectorAll('li').length, b: m.querySelectorAll('strong').length, cru: /(^|\s)(#{1,3} |\*\*|^- )/m.test(m.innerText), texto: m.innerText } : null; }); };
    const sd = await sobre();
    L.ok(sd && !sd.pre && sd.h >= 3 && sd.li >= 3 && sd.b >= 1 && !sd.cru, `o Sobre, a marca formatada: ${sd ? sd.h : 0} títulos, ${sd ? sd.li : 0} itens de lista, ${sd ? sd.b : 0} negritos, sem símbolo de formatação à mostra, sem caixa de código`);
    L.ok(sd && /VCOSTAEDU/.test(sd.texto) && !/vcostaedu/.test(sd.texto) && !/adquirente|homologa|contato/i.test(sd.texto), 'o texto novo: "VCOSTAEDU" em maiúsculas; sem a frase do adquirente de pagamentos; sem a seção de contato');
    await pd.screenshot({ path: path.join(L.PROVAS, ETAPA, 'sobre-marca.png'), fullPage: true });
    await sabotar('a marca crua no Sobre', SOBRE, 'VcMarkdown::html(', 'htmlspecialchars(', async () => { const s = await sobre(); return !!s && !s.cru && s.h >= 3; });
    await ctxD.close();
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message);
  } finally {
    if (fs.readFileSync(CONFIG, 'utf8') !== configOriginal) { fs.writeFileSync(CONFIG, configOriginal); }
    await navegador.close();
  }
  process.exit(L.resumo('onda3/etapa7') ? 0 : 1);
})();
