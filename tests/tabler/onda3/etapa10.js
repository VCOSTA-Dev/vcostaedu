/*
 * ONDA 3, ETAPA 10 — o tema claro e escuro.
 *   A. a escolha no menu do usuário (painel e portal): muda na hora, grava na conta, vale depois de recarregar; as telas
 *      de acesso seguem a última escolha (o cookie); começa claro; "automático" segue o aparelho, ao vivo;
 *   B. a versão só para quem entrou (as telas de acesso e o instalador sem ela);
 *   C. documentos claros: a impressão, a verificação de documento, a Ajuda e a documentação da API;
 *   D. os componentes no escuro: a caixa de seleção com pesquisa aberta, a janela de progresso, o editor, o gráfico,
 *      as marcas do rodapé (as de letra branca, com as letras da altura do texto) e o portal de exemplo;
 *   E. o contraste mínimo de 4,5:1 em todas as telas das listas, nos dois temas (varrer_contraste.js);
 *   Sabotagem: sem o script do tema no painel.
 *
 *   node tests/tabler/onda3/etapa10.js     (depois do ciclo, com a demonstração)
 */
const fs = require('fs');
const path = require('path');
const { execFileSync, spawnSync } = require('child_process');
const L = require('../lib');
const { contraste } = require('./contraste');
const { medir } = require('./medir_rodape');

const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste$/.test(BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
const MYSQL = process.env.VCOSTAEDU_MYSQL || 'C:/xampp/mysql/bin/mysql.exe';
const PHP = process.env.VCOSTAEDU_PHP || 'C:/xampp/php/php.exe';
const sql = (q) => execFileSync(MYSQL, ['-u', 'root', '-N', '--default-character-set=utf8mb4', BANCO, '-e', q]).toString().replace(/\r/g, '').trim();
const ETAPA = 'onda3/etapa10';
const SENHA = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const HEAD = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Views', 'include_tabler', 'head.php');
let cred = JSON.parse(fs.readFileSync(path.join(__dirname, '..', 'saida', 'credenciais.json'), 'utf8'));
const REGUA_ATUAL = L.reguaAtual(); // o retrato mais novo, de qualquer onda (lib.js)

const tema = (p) => p.evaluate(() => document.documentElement.getAttribute('data-bs-theme'));
async function contexto(navegador, extra = {}) {
  const ctx = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR', ...extra });
  return { ctx, p: await L.pagina(ctx) };
}
async function entrar(o, app, usuario, senha) {
  await o.p.goto(L.BASE + app + '/login/acesso');
  await o.p.fill('input[name="usuario"]', usuario);
  await o.p.fill('input[name="senha"]', senha);
  await Promise.all([o.p.waitForNavigation(), o.p.click('[name="SendLogin"]')]);
}
async function escolher(p, t) {
  await p.click('#vc-side-alca').catch(() => {});
  await p.waitForTimeout(200);
  await p.click(`#vcTemaSeletor .vc-tema-opcao[data-vc-tema="${t}"]`);
  await p.waitForFunction((x) => document.querySelector('#vcTemaSeletor').getAttribute('data-gravado') === x, t, { timeout: 5000 }).catch(() => {});
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

(async () => {
  if (sql("SELECT valor FROM adms_instalacao_estado WHERE chave = 'demonstracao.ativa'") !== '1') {
    const c = spawnSync(PHP, [path.join(L.RAIZ, 'tests', 'tabler', 'ciclo.php'), 'comparar', REGUA_ATUAL], { encoding: 'utf8', cwd: L.RAIZ, env: process.env, maxBuffer: 1 << 26 });
    if (c.status !== 0) { console.error('o ciclo não refez a demonstração'); process.exit(1); }
    cred = JSON.parse(fs.readFileSync(path.join(__dirname, '..', 'saida', 'credenciais.json'), 'utf8'));
  }
  sql("UPDATE adms_usuarios SET tema = 'claro'; UPDATE col_usuarios SET tema = 'claro'");
  const { navegador } = await L.abrir();
  fs.mkdirSync(path.join(L.PROVAS, ETAPA), { recursive: true });
  try {
    console.log('\n== Onda 3, Etapa 10');

    /* ===== A. a escolha ===== */
    const sec = await contexto(navegador, { colorScheme: 'dark' });
    await sec.p.goto(L.BASE + 'painel/login/acesso');
    const acessoInicial = await tema(sec.p);
    await entrar(sec, 'painel', 'secretaria.demo', cred.painel['secretaria.demo']);
    L.ok(acessoInicial === 'light' && (await tema(sec.p)) === 'light', 'começa claro: a entrada e o painel claros, mesmo com o aparelho no escuro (ninguém escolheu ainda)');
    const adm = await contexto(navegador);
    await entrar(adm, 'painel', 'administrador', SENHA);
    const opcoes = await adm.p.$$eval('#vcTemaSeletor .vc-tema-opcao', (b) => b.map((x) => x.dataset.vcTema + (x.classList.contains('active') ? '*' : '')));
    await escolher(adm.p, 'escuro');
    const naHora = await tema(adm.p);
    const gravado = sql("SELECT tema FROM adms_usuarios WHERE usuario = 'administrador'");
    await adm.p.reload();
    const depois = await tema(adm.p);
    L.ok(opcoes.join(',') === 'claro*,escuro,auto' && naHora === 'dark' && gravado === 'escuro' && depois === 'dark',
      `no menu do usuário, Claro, Escuro e Automático (${opcoes.join(', ')}): Escuro muda a tela na hora, grava na conta e vale depois de recarregar`);
    await L.print(adm.p, ETAPA, 'painel-escuro');
    await adm.p.goto(L.BASE + 'painel/login/logout');
    await adm.p.goto(L.BASE + 'painel/login/acesso');
    L.ok((await tema(adm.p)) === 'dark', 'a tela de acesso segue a última escolha deste navegador (escuro)');
    await L.print(adm.p, ETAPA, 'acesso-escuro');
    await entrar(adm, 'painel', 'administrador', SENHA);
    /* automático: o do aparelho, ao vivo */
    const aut = await contexto(navegador, { colorScheme: 'dark' });
    await entrar(aut, 'painel', 'direcao.demo', cred.painel['direcao.demo']);
    await escolher(aut.p, 'auto');
    const autEscuro = await tema(aut.p);
    await aut.p.emulateMedia({ colorScheme: 'light' });
    await aut.p.waitForFunction(() => document.documentElement.getAttribute('data-bs-theme') === 'light', null, { timeout: 3000 }).catch(() => {});
    const autClaro = await tema(aut.p);
    const autGravado = sql("SELECT tema FROM adms_usuarios WHERE usuario = 'direcao.demo'");
    L.ok(autEscuro === 'dark' && autClaro === 'light' && autGravado === 'auto', `Automático segue o aparelho, ao vivo: escuro com o aparelho escuro, claro quando o aparelho muda (${autEscuro} → ${autClaro}; na conta: ${autGravado})`);
    await aut.ctx.close();
    /* o portal */
    const alu = await contexto(navegador);
    await entrar(alu, 'portal', '260101', cred.portal['260101']);
    await escolher(alu.p, 'escuro');
    await alu.p.reload();
    L.ok((await tema(alu.p)) === 'dark' && sql("SELECT tema FROM col_usuarios WHERE rm = '260101'") === 'escuro', 'o portal: o aluno escolhe o escuro no menu do usuário; grava na conta dele e vale depois de recarregar');
    await L.print(alu.p, ETAPA, 'portal-escuro');

    /* ===== B. a versão só para quem entrou ===== */
    const versao = sql("SELECT numero FROM adms_versoes WHERE situacao = 1");
    const anon = await contexto(navegador);
    const semVersao = [];
    for (const r of ['painel/login/acesso', 'portal/login/acesso']) {
      await anon.p.goto(L.BASE + r);
      const rod = await anon.p.$eval('.vc-legal', (e) => e.textContent).catch(() => '');
      if (rod && !rod.includes(versao)) { semVersao.push(r); }
    }
    const comVersao = (await adm.p.$eval('.vc-legal', (e) => e.textContent).catch(() => '')).includes(versao);
    L.ok(semVersao.length === 2 && comVersao, `a versão (${versao}) só para quem entrou: fora do rodapé da entrada do painel e da do portal; no rodapé de dentro, sim (o instalador continua mostrando a versão que instala)`);

    /* ===== C. documentos claros ===== */
    await adm.p.goto(L.BASE + 'painel/home/index');
    await adm.p.evaluate(() => window.dispatchEvent(new Event('beforeprint')));
    const naImpressao = await tema(adm.p);
    await adm.p.evaluate(() => window.dispatchEvent(new Event('afterprint')));
    const depoisImpressao = await tema(adm.p);
    await anon.p.goto(L.BASE + 'painel/login/acesso');
    await anon.p.evaluate(() => { document.cookie = 'vc_tema=escuro;path=/'; });
    const docOk = await anon.p.goto(L.BASE + 'verificar/ABCD-EFGH').then(async () => (await tema(anon.p)) !== 'dark').catch(() => false);
    await adm.p.goto(L.BASE + 'painel/ajuda/guia/primeiros-passos');
    const ajuda = await adm.p.$eval('.guia-conteudo', (e) => getComputedStyle(e).backgroundColor).catch(() => '');
    await adm.p.goto(L.BASE + 'painel/integracoes/documentacao');
    await adm.p.waitForSelector('#swaggerUi[data-pronto="1"]', { timeout: 15000 }).catch(() => {});
    const swagger = await adm.p.$eval('#swaggerUi', (e) => getComputedStyle(e).backgroundColor).catch(() => '');
    L.ok(naImpressao === 'light' && depoisImpressao === 'dark' && docOk && ajuda === 'rgb(255, 255, 255)' && swagger === 'rgb(255, 255, 255)',
      'documento é claro: a impressão sai clara (e a tela volta ao escuro), a verificação de documento não usa o tema, e a Ajuda e a documentação da API são ilhas claras no escuro');

    /* ===== D. os componentes no escuro ===== */
    const comp = [];
    /* a caixa de seleção com pesquisa, aberta */
    await adm.p.goto(L.BASE + 'painel/cursos/listar');
    const s2 = await adm.p.evaluate(() => { const sel = document.querySelector('select.select2'); if (!sel || !window.jQuery) { return false; } const m = sel.closest('.modal'); if (m && window.bootstrap) { new bootstrap.Modal(m).show(); } window.jQuery(sel).select2('open'); return true; });
    await adm.p.waitForTimeout(500);
    const vS2 = s2 ? await contraste(adm.p) : null;
    comp.push(['a caixa de seleção com pesquisa aberta, numa janela', s2 && vS2.length === 0, vS2]);
    await L.print(adm.p, ETAPA, 'select2-escuro');
    /* a janela de progresso */
    await adm.p.goto(L.BASE + 'painel/moodle-envios/listar');
    await adm.p.evaluate(() => { const j = document.querySelector('.vc-progresso'); if (j) { j.hidden = false; } });
    const vProg = await contraste(adm.p);
    comp.push(['a janela de progresso', vProg.length === 0, vProg]);
    /* o editor (Summernote) */
    await adm.p.goto(L.BASE + 'painel/historico-modelos/editar/1');
    await adm.p.waitForSelector('.note-editor', { timeout: 10000 }).catch(() => {});
    const vEd = await contraste(adm.p);
    comp.push(['o editor de texto (a barra e a área)', !!(await adm.p.$('.note-editor')) && vEd.length === 0, vEd]);
    await L.print(adm.p, ETAPA, 'editor-escuro');
    /* o gráfico: um relatório com gráfico */
    await adm.p.goto(L.BASE + 'painel/relatorios/listar');
    const rels = await adm.p.$$eval('a[href*="relatorios/"]', (as) => [...new Set(as.map((a) => a.href))].filter((h) => !/listar|salvos/.test(h)));
    let corGrafico = null;
    for (const h of rels) {
      await adm.p.goto(h);
      if (await adm.p.$('#graficoRelatorio')) { corGrafico = await adm.p.evaluate(() => window.Chart && window.Chart.defaults.global.defaultFontColor); await L.print(adm.p, ETAPA, 'grafico-escuro'); break; }
    }
    comp.push(['o gráfico de um relatório (o texto do gráfico claro sobre o escuro)', corGrafico === '#C3CAD5' || corGrafico === null, corGrafico === null ? ['nenhum relatório com gráfico sem filtro'] : []]);
    /* as marcas do rodapé */
    await adm.p.goto(L.BASE + 'painel/home/index');
    const m = await medir(adm.p);
    comp.push(['as marcas do rodapé: as de letra branca, com as letras da altura do texto', !!m && /-branco/.test(m.arquivoEdu) && /-branco/.test(m.arquivoVcosta) && Math.abs(m.edu - m.cap) <= 0.6 && Math.abs(m.vcosta - m.cap) <= 0.6, []]);
    /* o portal de exemplo, no aparelho escuro */
    const ex = await contexto(navegador, { colorScheme: 'dark' });
    await ex.p.goto(L.BASE + 'exemplos/portal-matricula/');
    const vEx = await contraste(ex.p);
    comp.push(['o portal de matrícula de exemplo (no aparelho escuro)', vEx.length === 0 && (await ex.p.evaluate(() => getComputedStyle(document.body).backgroundColor)) !== 'rgb(244, 246, 248)', vEx]);
    await L.print(ex.p, ETAPA, 'exemplo-escuro');
    await ex.ctx.close();
    for (const [nome, ok, v] of comp) {
      L.ok(ok, `no escuro, ${nome}${v && v.length ? ' — ' + v.slice(0, 2).map((x) => typeof x === 'string' ? x : `${x.onde} ${x.razao}:1`).join('; ') : ''}`);
    }

    /* ===== E. o contraste em todas as telas, nos dois temas ===== */
    const varr = spawnSync('node', [path.join(__dirname, 'varrer_contraste.js'), 'ambos'], { encoding: 'utf8', cwd: L.RAIZ, env: process.env, maxBuffer: 1 << 26, timeout: 1800000 });
    const resumo = JSON.parse(fs.readFileSync(path.join(L.PROVAS, 'onda3', 'etapa10', 'contraste-resumo.json'), 'utf8'));
    L.ok(varr.status === 0 && resumo.claro.comProblema === 0 && resumo.escuro.comProblema === 0 && resumo.claro.erros === 0 && resumo.escuro.erros === 0,
      `o contraste mínimo (4,5:1; texto grande, 3:1) em todas as ${resumo.claro.telas} telas das listas, nos dois temas: claro ${resumo.claro.comProblema} tela(s) com problema, escuro ${resumo.escuro.comProblema}`);

    /* ===== a sabotagem ===== */
    await sabotar('sem o script do tema no painel', HEAD, '<?= \\App\\adms\\Models\\helper\\AdmsTema::script() ?>', '',
      async () => { await adm.p.goto(L.BASE + 'painel/home/index'); return (await tema(adm.p)) === 'dark'; });
    await Promise.all([sec.ctx.close(), adm.ctx.close(), alu.ctx.close(), anon.ctx.close()]);
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message);
  } finally {
    sql("UPDATE adms_usuarios SET tema = 'claro'; UPDATE col_usuarios SET tema = 'claro'"); /* as outras provas começam no claro */
    await navegador.close();
  }
  process.exit(L.resumo('onda3/etapa10') ? 0 : 1);
})();
