/*
 * ONDA 3, ETAPA 11 — "Atualizar o sistema" em passos. O banco de quem instalou numa versão antiga (as migrations do
 * commit de antes da segunda onda, a 1.18.4, aplicadas pelo mesmo runner num banco de prova à parte — banco_antigo.php),
 * com os arquivos novos:
 *   A. pela TELA: a janela de progresso, UMA etapa por chamada (cada passo aplica exatamente uma), o resultado no fim, a
 *      tabela do lote apagada; a régua do banco atualizado pela tela IDÊNTICA à do banco atualizado pelo COMANDO, que
 *      segue aplicando tudo em sequência;
 *   B. a etapa forçada a falhar no meio: o lote para nela, o que passou continua, a tela diz qual falhou e por quê (sem o
 *      erro do banco); corrigida a causa, "Continuar a atualização" segue por ela até o fim;
 *   C. a página fechada no meio: reaberta, a tela diz que não terminou e continua de onde parou, sem repetir etapa;
 *   D. duas ao mesmo tempo: com o lote de uma pessoa andando, a outra sessão vê "em andamento" e é recusada, e o comando
 *      também recusa;
 *   E. sem JavaScript: o formulário aplica uma etapa por envio;
 *   Sabotagem: o passo que aplica tudo de uma vez — a prova reprova.
 *
 *   node tests/tabler/onda3/etapa11.js     (troca a Config do painel desta pasta para o banco de prova de atualização e a devolve)
 */
const fs = require('fs');
const path = require('path');
const { execFileSync, spawnSync } = require('child_process');
const L = require('../lib');

const BASE_BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste$/.test(BASE_BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
const BANCO = BASE_BANCO + '2';
const MYSQL = process.env.VCOSTAEDU_MYSQL || 'C:/xampp/mysql/bin/mysql.exe';
const PHP = process.env.VCOSTAEDU_PHP || 'C:/xampp/php/php.exe';
const ANTERIOR = '79c4b52'; // o último commit antes da segunda onda (1.18.4)
const sql = (q) => execFileSync(MYSQL, ['-u', 'root', '-N', '--default-character-set=utf8mb4', BANCO, '-e', q]).toString().replace(/\r/g, '').trim();
const um = (q) => +sql(q);
const ETAPA = 'onda3/etapa11';
const SENHA = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const CONFIG = path.join(L.RAIZ, 'painel', 'core', 'Config.php');
const MIGRATIONS = path.join(L.RAIZ, 'database', 'migrations');
const FALHA = path.join(MIGRATIONS, '9998_prova_falha.sql');
const ATUALIZACAO = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'helper', 'AdmsAtualizacao.php');
const TOTAL = fs.readdirSync(MIGRATIONS).filter((f) => /^\d{4}_.*\.sql$/.test(f)).length;
const SAIDA = path.join(L.PROVAS, ETAPA);
const REGUA_CLI = path.join(SAIDA, 'regua-pelo-comando.json');

const bancoAntigo = () => {
  const r = spawnSync(PHP, [path.join(L.RAIZ, 'tests', 'tabler', 'onda2', 'banco_antigo.php'), ANTERIOR], { encoding: 'utf8', env: process.env });
  if (r.status !== 0) { throw new Error('banco antigo: ' + (r.stderr || r.stdout)); }
  return JSON.parse(r.stdout.trim());
};
const migrar = (...a) => spawnSync(PHP, [path.join(L.RAIZ, 'bin', 'migrar.php'), ...a], { encoding: 'utf8', cwd: L.RAIZ });
const regua = (modo) => spawnSync(PHP, [path.join(L.RAIZ, 'tests', 'tabler', 'regua.php'), modo, REGUA_CLI], { encoding: 'utf8', cwd: L.RAIZ, maxBuffer: 1 << 26 });
const aplicadas = () => um('SELECT COUNT(*) FROM adms_migrations');
const temLote = () => um(`SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '${BANCO}' AND table_name = 'adms_atualizacao_lote'`) > 0;

async function entrar(navegador, ctx) {
  ctx = ctx || await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' });
  const p = await L.pagina(ctx);
  await p.goto(L.BASE + 'painel/login/acesso');
  await p.fill('input[name="usuario"]', 'administrador');
  await p.fill('input[name="senha"]', SENHA);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
  return { ctx, p };
}
/** Os passos que a janela pediu: a resposta de cada fase=passo (e quantas etapas o banco tinha ao responder). */
function vigiar(p) {
  const passos = [];
  p.on('response', async (r) => {
    try {
      if (/atualizar\/aplicar/.test(r.url()) && /fase=passo/.test(r.request().postData() || '')) {
        const j = await r.json();
        if (!j.ocupado) { passos.push({ passo: j.passo, concluida: !!j.concluida, falhou: !!j.falhou, banco: aplicadas() }); }
      }
    } catch (e) { /* a página fechou no meio */ }
  });
  return passos;
}
async function comecar(p) {
  await p.goto(L.BASE + 'painel/atualizar/index');
  await p.check('#fizBackup');
  await p.click('#aplicarAtualizacao');
}
const fim = (p, t = 300000) => p.waitForSelector('#progressoAtualizacao[data-situacao="concluida"], #progressoAtualizacao[data-situacao="falhou"]', { timeout: t })
  .then(() => p.getAttribute('#progressoAtualizacao', 'data-situacao'));
/* um passo aplica exatamente UMA etapa: o banco cresce de um em um, e o número de passos é o de etapas */
const umPorPasso = (passos, inicio) => passos.length > 1 && passos.every((x, i) => x.banco === inicio + i + 1);

(async () => {
  const configOriginal = fs.readFileSync(CONFIG, 'utf8');
  const atualizacaoOriginal = fs.readFileSync(ATUALIZACAO, 'utf8');
  fs.mkdirSync(SAIDA, { recursive: true });
  const { navegador } = await L.abrir();
  try {
    console.log('\n== Onda 3, Etapa 11');
    fs.writeFileSync(CONFIG, configOriginal.replace(/define\('DBNAME',\s*'[^']*'\)/, `define('DBNAME', '${BANCO}')`));

    /* o retrato do banco atualizado pelo COMANDO (tudo em sequência), para comparar com o da tela */
    let antigo = bancoAntigo();
    const cli = migrar('aplicar', '--sim');
    const pendentesAntigo = TOTAL - antigo.aplicadas;
    const g = regua('gravar');
    L.ok(antigo.aplicadas === 3 && cli.status === 0 && aplicadas() === TOTAL && g.status === 0,
      `o comando segue aplicando TUDO em sequência: o banco da 1.18.4 (${antigo.aplicadas} etapas) atualizado de uma vez (${aplicadas()} etapas); o retrato gravado`);

    /* ===== A. pela tela, em passos ===== */
    antigo = bancoAntigo();
    let { ctx, p } = await entrar(navegador);
    let passos = vigiar(p);
    await comecar(p);
    const sitA = await fim(p);
    await L.print(p, ETAPA, 'janela-concluida');
    const nPassosA = passos.length;
    const umA = umPorPasso(passos, 3);
    L.ok(sitA === 'concluida' && nPassosA === pendentesAntigo && umA && aplicadas() === TOTAL,
      `pela tela, a janela de progresso: ${nPassosA} chamadas, UMA etapa do banco em cada (o banco foi de 3 a ${aplicadas()}, de um em um)`);
    await Promise.all([p.waitForNavigation(), p.click('#progressoAtualizacao .vc-progresso-rodape a, #progressoAtualizacao .vc-progresso-rodape button')]).catch(() => p.goto(L.BASE + 'painel/atualizar/index'));
    const okA = await p.isVisible('#atualizacaoOk');
    const versao = (await p.textContent('#versaoDepois').catch(() => '')).trim();
    const linhasA = await p.$$eval('#etapasAplicadas tbody tr', (t) => t.length).catch(() => 0);
    await L.print(p, ETAPA, 'resultado');
    await p.reload();
    L.ok(okA && linhasA === pendentesAntigo && /^1\.\d+\.\d+$/.test(versao) && !temLote() && await p.isVisible('#sistemaEmDia'),
      `"Ver o resultado": ${linhasA} etapas aplicadas, a versão ${versao}; a tabela do lote saiu do banco; reaberta, a tela diz "em dia"`);
    const cmp = regua('comparar');
    L.ok(cmp.status === 0 && /IDÊNTICA/.test(cmp.stdout), `a régua do banco atualizado pela TELA é IDÊNTICA à do atualizado pelo COMANDO (${(cmp.stdout.match(/Régua:[^\n]*/) || [''])[0]} ${/IDÊNTICA/.test(cmp.stdout) ? 'IDÊNTICA' : cmp.stdout.slice(-400)})`);
    await ctx.close();

    /* ===== B. a etapa que falha no meio; corrigida, continua ===== */
    antigo = bancoAntigo();
    fs.writeFileSync(FALHA, '-- prova da Etapa 11: uma etapa que falha\nSELECT `coluna` FROM `tabela_que_nao_existe_na_prova`;\n');
    try {
      ({ ctx, p } = await entrar(navegador));
      passos = vigiar(p);
      await comecar(p);
      const sitB = await fim(p);
      const motivo = (await p.textContent('#progressoAtualizacao .vc-progresso-falha-motivo').catch(() => '')).trim();
      await L.print(p, ETAPA, 'janela-falhou');
      const antesDaFalha = aplicadas();
      await p.goto(L.BASE + 'painel/atualizar/index');
      const parou = (await p.textContent('#atualizacaoParou').catch(() => '')).replace(/\s+/g, ' ').trim();
      const temContinuar = !!(await p.$('#continuarAtualizacao'));
      await L.print(p, ETAPA, 'parou');
      L.ok(sitB === 'falhou' && antesDaFalha === TOTAL && /9998_prova_falha/.test(parou) && temContinuar && !/SQLSTATE|tabela_que_nao_existe/.test(parou + motivo) && temLote(),
        `a etapa forçada a falhar: o lote para nela ("${parou.slice(0, 80)}…"), as ${antesDaFalha - 3} de antes continuam aplicadas, sem o erro do banco na tela, e "Continuar a atualização" aparece`);
      /* a causa corrigida (a etapa ainda não aplicada pode mudar): continua pela que falhou */
      fs.writeFileSync(FALHA, '-- prova da Etapa 11: a etapa corrigida\nUPDATE `adms_parametros` SET `valor` = `valor` WHERE 1 = 0;\n');
      passos.length = 0;
      await p.click('#continuarAtualizacao');
      const sitB2 = await fim(p);
      L.ok(sitB2 === 'concluida' && aplicadas() === TOTAL + 1 && passos.length === 1, `corrigida a causa, "Continuar a atualização" segue pela etapa que falhou e termina (${passos.length} passo, ${aplicadas()} etapas no banco)`);
      await p.goto(L.BASE + 'painel/atualizar/index');
      await ctx.close();
    } finally {
      fs.unlinkSync(FALHA);
    }

    /* ===== C. a página fechada no meio; D. duas ao mesmo tempo ===== */
    antigo = bancoAntigo();
    ({ ctx, p } = await entrar(navegador));
    passos = vigiar(p);
    await comecar(p);
    await p.waitForFunction(() => /Passo ([4-9]|\d\d) de/.test((document.querySelector('#progressoAtualizacao .vc-progresso-conta') || {}).textContent || ''), null, { timeout: 120000 });
    await p.close(); // a aba fechada no meio (o navegador e a sessão continuam)
    await new Promise((r) => setTimeout(r, 1500));
    const noFechamento = aplicadas();
    /* D: outra sessão, com o lote vivo */
    const outra = await entrar(navegador);
    await outra.p.goto(L.BASE + 'painel/atualizar/index');
    const outraVe = await outra.p.isVisible('#atualizacaoEmAndamento');
    const outraSemBotao = !(await outra.p.$('#aplicarAtualizacao')) && !(await outra.p.$('#continuarAtualizacao'));
    const outraTenta = await outra.p.evaluate(async (u) => (await (await fetch(u, { method: 'POST', body: new URLSearchParams({ fase: 'iniciar', fiz_backup: '1' }), credentials: 'same-origin' })).json()), L.BASE + 'painel/atualizar/aplicar');
    const outraPassa = await outra.p.evaluate(async (u) => (await (await fetch(u, { method: 'POST', body: new URLSearchParams({ fase: 'passo' }), credentials: 'same-origin' })).json()), L.BASE + 'painel/atualizar/aplicar');
    await L.print(outra.p, ETAPA, 'em-andamento-outra-sessao');
    const cliD = migrar('aplicar', '--sim');
    const depoisDasRecusas = aplicadas();
    L.ok(outraVe && outraSemBotao && /em andamento/.test(outraTenta.erro || '') && /em andamento/.test(outraPassa.erro || '') && cliD.status === 1 && /em andamento/.test(cliD.stderr) && depoisDasRecusas === noFechamento,
      'duas ao mesmo tempo: com o lote de uma sessão andando, a OUTRA sessão vê "em andamento", sem botão, e é recusada (começar e passo); o comando também recusa; nada aplicado por elas');
    await outra.ctx.close();
    /* C: a mesma pessoa reabre a tela */
    const p2 = await L.pagina(ctx);
    passos = vigiar(p2);
    await p2.goto(L.BASE + 'painel/atualizar/index');
    const interrompida = await p2.isVisible('#atualizacaoInterrompida');
    const jaPassaram = await p2.$$eval('#etapasAplicadas tbody tr', (t) => t.length).catch(() => 0);
    await L.print(p2, ETAPA, 'interrompida');
    const antesC = aplicadas();
    await p2.click('#continuarAtualizacao');
    const sitC = await fim(p2);
    L.ok(noFechamento > 3 && noFechamento < TOTAL && interrompida && jaPassaram === antesC - 3 && sitC === 'concluida' && aplicadas() === TOTAL && umPorPasso(passos, antesC) && passos.length === TOTAL - antesC,
      `a página fechada no meio (com ${noFechamento - 3} etapas aplicadas): reaberta, a tela diz que não terminou (${jaPassaram} já passaram) e continua de onde parou — ${passos.length} passos, nenhuma etapa repetida`);
    await p2.goto(L.BASE + 'painel/atualizar/index');
    await ctx.close();

    /* ===== E. sem JavaScript: uma etapa por envio ===== */
    antigo = bancoAntigo();
    const semJs = await navegador.newContext({ viewport: { width: 1366, height: 900 }, javaScriptEnabled: false });
    ({ p } = await entrar(navegador, semJs));
    await p.goto(L.BASE + 'painel/atualizar/index');
    await p.check('#fizBackup');
    await Promise.all([p.waitForNavigation(), p.click('#aplicarAtualizacao')]);
    const umEnvio = aplicadas();
    const continua = !!(await p.$('#continuarAtualizacao'));
    L.ok(umEnvio === 4 && continua, `sem JavaScript, o formulário aplica UMA etapa por envio e volta à tela (${umEnvio - 3} aplicada; "Continuar" para a próxima)`);
    const cliVivo = migrar('aplicar', '--sim'); // o lote acabou de andar: o comando espera
    sql('UPDATE adms_atualizacao_lote SET atualizado_em = NOW() - INTERVAL 5 MINUTE'); // a pessoa largou a tela
    const cliParado = migrar('aplicar', '--sim');
    L.ok(cliVivo.status === 1 && cliParado.status === 0 && aplicadas() === TOTAL && !temLote(),
      'o lote da tela parado há minutos: o comando termina o que ele começou, e a tabela do lote sai (com o lote ainda andando, o comando tinha recusado)');
    await semJs.close();

    /* ===== Sabotagem: o passo que aplica tudo de uma vez ===== */
    antigo = bancoAntigo();
    let reprovou = false;
    try {
      fs.writeFileSync(ATUALIZACAO, atualizacaoOriginal.replace('$r = $this->runner()->aplicarProxima();', '$this->runner()->aplicar(false); $r = $this->runner()->resultados()[0] ?? null;'));
      ({ ctx, p } = await entrar(navegador));
      passos = vigiar(p);
      await comecar(p);
      await fim(p);
      reprovou = !umPorPasso(passos, 3);
      await ctx.close();
    } finally { fs.writeFileSync(ATUALIZACAO, atualizacaoOriginal); }
    L.ok(reprovou && fs.readFileSync(ATUALIZACAO, 'utf8') === atualizacaoOriginal, 'sabotagem (o passo que aplica tudo de uma vez): a prova reprova; o arquivo voltou');
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    fs.writeFileSync(CONFIG, configOriginal);
    fs.writeFileSync(ATUALIZACAO, atualizacaoOriginal);
    if (fs.existsSync(FALHA)) { fs.unlinkSync(FALHA); }
    L.ok(fs.readFileSync(CONFIG, 'utf8') === configOriginal, 'a Config do painel desta pasta voltou ao banco de prova de sempre');
    await navegador.close();
    process.exit(L.resumo('onda3/etapa11') ? 0 : 1);
  }
})();
