/*
 * ONDA 2, ETAPA 7 — a tela de atualização e a tela "Versão".
 *   O banco de quem instalou na versão de antes desta onda (as migrations do commit ANTERIOR, aplicadas pelo mesmo runner
 *   num banco de prova à parte — banco_antigo.php) com os arquivos novos:
 *   A. o aviso de "atualização pendente" leva o Administrador à tela; a tela abre ANTES da migration que cria a página
 *      dela (a dependência recursiva); a lista das etapas pendentes; a "Versão" diz o que falta;
 *   B. as recusas: sem a confirmação do backup; duas ao mesmo tempo (a trava do banco: a tela e o comando recusam a
 *      segunda). Onda 3, Etapa 11: saiu o "grande demais para o tempo do servidor" — a tela aplica em passos;
 *   C. atualizar pela TELA (a janela de progresso, em passos — a prova inteira dos passos é a tests/tabler/onda3/etapa11.js):
 *      tudo aplicado, a versão nova, o aviso some;
 *   D. a falha no meio: anunciada, com o que passou antes dela e o que ficou pendente;
 *   E. atualizar pelo COMANDO, num banco antigo de novo;
 *   F. sabotagem: sem a exceção da dependência recursiva, a tela some do banco antigo — a prova reprova.
 *
 *   node tests/tabler/onda2/etapa7.js     (troca a Config do painel para o banco de prova de atualização e a devolve)
 */
const fs = require('fs');
const path = require('path');
const { execFileSync, spawnSync, spawn } = require('child_process');
const L = require('../lib');

const BASE_BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste$/.test(BASE_BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
const BANCO = BASE_BANCO + '2';
const MYSQL = process.env.VCOSTAEDU_MYSQL || 'C:/xampp/mysql/bin/mysql.exe';
const PHP = process.env.VCOSTAEDU_PHP || 'C:/xampp/php/php.exe';
const ANTERIOR = '79c4b52'; // o último commit antes da segunda onda (1.18.4)
const sql = (q) => execFileSync(MYSQL, ['-u', 'root', '-N', '--default-character-set=utf8mb4', BANCO, '-e', q]).toString().replace(/\r/g, '').trim();
const um = (q) => +sql(q);
const ETAPA = 'onda2/etapa7';
const SENHA = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const CONFIG = path.join(L.RAIZ, 'painel', 'core', 'Config.php');
const MIGRATIONS = path.join(L.RAIZ, 'database', 'migrations');
const FALHA = path.join(MIGRATIONS, '0099_prova_falha.sql');
const PAGINAS = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'AdmsPaginas.php');
const TOTAL = fs.readdirSync(MIGRATIONS).filter((f) => /^\d{4}_.*\.sql$/.test(f)).length;

const bancoAntigo = () => {
  const r = spawnSync(PHP, [path.join(L.RAIZ, 'tests', 'tabler', 'onda2', 'banco_antigo.php'), ANTERIOR], { encoding: 'utf8', env: process.env });
  if (r.status !== 0) { throw new Error('banco antigo: ' + (r.stderr || r.stdout)); }
  return JSON.parse(r.stdout.trim());
};
const migrar = (...a) => spawnSync(PHP, [path.join(L.RAIZ, 'bin', 'migrar.php'), ...a], { encoding: 'utf8', cwd: L.RAIZ });
const aplicadas = () => um('SELECT COUNT(*) FROM adms_migrations');

async function entrar(navegador) {
  const ctx = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' });
  const p = await L.pagina(ctx);
  await p.goto(L.BASE + 'painel/login/acesso');
  await p.fill('input[name="usuario"]', 'administrador');
  await p.fill('input[name="senha"]', SENHA);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
  return { ctx, p };
}
const postar = (p, rota, form) => p.evaluate(async ([u, f]) => {
  /* sem seguir o redirecionamento: a mensagem da recusa fica para a próxima abertura da tela, como no navegador */
  const r = await fetch(u, { method: 'POST', body: new URLSearchParams(f), credentials: 'same-origin', redirect: 'manual' });
  return r.status;
}, [L.BASE + rota, form]);
/** Segura a trava do runner (a mesma: GET_LOCK com o nome do banco) por alguns segundos, como uma atualização em curso. */
function segurarTrava(segundos) {
  const codigo = `$p = new PDO('mysql:host=localhost;dbname=${BANCO}', 'root', '');
    $n = 'vcostaedu_migrar_' . substr(sha1('${BANCO}'), 0, 32);
    $ok = $p->query("SELECT GET_LOCK('$n', 0)")->fetchColumn(); echo $ok ? "TRAVADO\\n" : "OCUPADO\\n"; flush(); sleep(${segundos});`;
  const proc = spawn(PHP, ['-r', codigo]);
  return new Promise((ok) => proc.stdout.once('data', (d) => ok({ proc, saida: String(d).trim() })));
}
/** Atualizar pela tela (Onda 3, Etapa 11): a janela de progresso até o fim (ou a falha) e a tela de novo. */
async function pelaJanela(p) {
  const continuar = await p.$('#continuarAtualizacao');
  if (continuar) {
    await continuar.click();
  } else {
    await p.check('#fizBackup');
    await p.click('#aplicarAtualizacao');
  }
  await p.waitForSelector('#progressoAtualizacao[data-situacao="concluida"], #progressoAtualizacao[data-situacao="falhou"]', { timeout: 300000 });
  await p.goto(L.BASE + 'painel/atualizar/index');
}
async function sabotar(nome, estragar, consertar, conferir) {
  let reprovou = false;
  try { await estragar(); reprovou = !(await conferir()); } finally { await consertar(); }
  L.ok(reprovou, `sabotagem (${nome}): a prova reprova o defeito`);
}

(async () => {
  const configOriginal = fs.readFileSync(CONFIG, 'utf8');
  const { navegador } = await L.abrir();
  try {
    console.log('\n== Onda 2, Etapa 7');
    const antigo = bancoAntigo();
    L.ok(antigo.aplicadas === 3 && antigo.admin && sql('SELECT numero FROM adms_versoes WHERE situacao = 1') === '1.18.4',
      `o banco da versão de antes desta onda (${ANTERIOR}: ${antigo.migrations.join(', ')}, a 1.18.4), pelo mesmo runner`);
    fs.writeFileSync(CONFIG, configOriginal.replace(/define\('DBNAME',\s*'[^']*'\)/, `define('DBNAME', '${BANCO}')`));
    L.ok(fs.readFileSync(CONFIG, 'utf8').includes(`'${BANCO}'`), 'os arquivos novos sobre o banco antigo (a Config do painel aponta para ele)');

    /* A. o aviso leva à tela; a tela abre antes da página dela existir */
    let { ctx, p } = await entrar(navegador);
    L.ok(await p.isVisible('#avisoAtualizacao') && !!(await p.$('#avisoAtualizacao a#irParaAtualizacao')), 'o aviso de "atualização pendente" no painel, com o caminho para a tela');
    L.ok(um("SELECT COUNT(*) FROM adms_paginas WHERE controller = 'Atualizar'") === 0, 'a página da tela de atualização ainda não existe no banco (nasce na migration que a tela aplica)');
    await Promise.all([p.waitForNavigation(), p.click('#avisoAtualizacao a#irParaAtualizacao')]);
    const pendentes = await p.$$eval('#listaPendentes li', (l) => l.map((x) => x.textContent.trim()));
    L.ok(/atualizar\/index/.test(p.url()) && pendentes.length === TOTAL - 3 && pendentes[0] === '0004_instalador_profissional.sql' && pendentes.some((x) => /^0007_.*TRIGGER/.test(x)),
      `a tela abre assim mesmo (a dependência recursiva) e lista as ${pendentes.length} etapas pendentes`);
    L.ok((await p.textContent('#versaoBanco')).trim() === '1.18.4' && /^1\.\d+\.\d+$/.test((await p.textContent('#versaoArquivos')).trim()), 'a versão do banco e a dos arquivos');
    await L.print(p, ETAPA, 'pendente');

    /* B. as recusas */
    await postar(p, 'painel/atualizar/aplicar', { Atualizar: '1' });
    await p.goto(L.BASE + 'painel/atualizar/index');
    L.ok(await p.isVisible('#atualizacaoSemBackup') && aplicadas() === 3, 'sem a confirmação do backup: recusado, nada aplicado');
    const trava = await segurarTrava(25);
    /* o envio do formulário (sem a janela): o passo encontra a trava tomada e recusa */
    await postar(p, 'painel/atualizar/aplicar', { Atualizar: '1', fiz_backup: '1' });
    await p.goto(L.BASE + 'painel/atualizar/index');
    const naTela = await p.isVisible('#atualizacaoEmAndamento');
    const cli = migrar('aplicar', '--sim');
    trava.proc.kill();
    L.ok(trava.saida === 'TRAVADO' && naTela && cli.status === 1 && /em andamento/.test(cli.stderr) && aplicadas() === 3,
      'duas ao mesmo tempo: com a trava tomada, a tela e o comando recusam a segunda, nada aplicado');
    await new Promise((r) => setTimeout(r, 800));

    /* C. pela tela (o lote que a trava recusou fica parado: a tela oferece continuá-lo) */
    await p.goto(L.BASE + 'painel/atualizar/index');
    await pelaJanela(p);
    const versaoNova = await p.textContent('#versaoDepois').catch(() => '');
    L.ok(await p.isVisible('#atualizacaoOk') && aplicadas() >= TOTAL - 1 && um("SELECT COUNT(*) FROM adms_paginas WHERE controller = 'Atualizar'") >= 2,
      `atualizado pela tela: ${aplicadas()} etapas no banco, a página da tela agora existe, a versão ${versaoNova}`);
    await L.print(p, ETAPA, 'atualizado');
    await p.goto(L.BASE + 'painel/versao/index');
    L.ok((await p.getAttribute('#situacaoAtualizacao', 'data-em-dia')) === '1' && (await p.textContent('#versaoInstalada')).trim() === versaoNova.trim(), 'a tela "Versão": a nova, em dia');
    await L.print(p, ETAPA, 'versao');
    await p.goto(L.BASE + 'painel/home/index');
    L.ok(!(await p.$('#avisoAtualizacao')), 'o aviso some depois da atualização');
    const status = migrar('status');
    L.ok(status.status === 0 && !/PENDENTE/.test(status.stdout.replace(/0003_gatilhos.*|0007_auditoria.*/g, '')), 'o comando vê o mesmo estado: nada pendente');
    await ctx.close();

    /* D. a falha no meio */
    bancoAntigo();
    fs.writeFileSync(FALHA, '-- prova da Etapa 7: uma etapa que falha\nSELECT `coluna` FROM `tabela_que_nao_existe_na_prova`;\n');
    try {
      ({ ctx, p } = await entrar(navegador));
      await p.goto(L.BASE + 'painel/atualizar/index');
      await pelaJanela(p);
      const passaram = await p.$$eval('#etapasAplicadas tbody tr', (t) => t.length).catch(() => 0);
      const ficaram = await p.$$eval('#listaPendentes li', (l) => l.map((x) => x.textContent.trim())).catch(() => []);
      const texto = await p.textContent('#atualizacaoParou').catch(() => '');
      L.ok(/0099_prova_falha/.test(texto) && passaram === TOTAL - 3 && ficaram.includes('0099_prova_falha.sql') && aplicadas() === TOTAL && !/SQLSTATE|tabela_que_nao_existe/.test(texto),
        `a falha no meio anunciada: "${texto.replace(/\s+/g, ' ').trim().slice(0, 70)}…", ${passaram} etapas passaram antes e continuam, a que falhou fica pendente (sem o erro do banco na tela)`);
      await L.print(p, ETAPA, 'parou');
      await ctx.close();
    } finally {
      fs.unlinkSync(FALHA);
    }

    /* E. pelo comando */
    bancoAntigo();
    const semConfirmar = migrar('aplicar');
    const r = migrar('aplicar', '--sim');
    L.ok(semConfirmar.status === 1 && aplicadas() >= TOTAL - 1 && r.status === 0 && /0010_api_integracao/.test(r.stdout), `atualizado pelo comando: sem confirmar recusa; com --sim, ${aplicadas()} etapas`);
    ({ ctx, p } = await entrar(navegador));
    await p.goto(L.BASE + 'painel/versao/index');
    L.ok((await p.getAttribute('#situacaoAtualizacao', 'data-em-dia')) === '1', 'a tela "Versão" vê o que o comando aplicou: em dia');
    await ctx.close();

    /* F. sabotagem: sem a exceção da dependência recursiva */
    bancoAntigo();
    const orig = fs.readFileSync(PAGINAS, 'utf8');
    await sabotar('a tela de atualização sem a exceção da dependência recursiva',
      () => fs.writeFileSync(PAGINAS, orig.replace("self::telaDeAtualizacao($this->urlController)", 'false')),
      () => fs.writeFileSync(PAGINAS, orig),
      async () => { const s = await entrar(navegador); await s.p.goto(L.BASE + 'painel/atualizar/index'); const ok = !!(await s.p.$('#listaPendentes')); await s.ctx.close(); return ok; });
    L.ok(fs.readFileSync(PAGINAS, 'utf8') === orig, 'o roteador voltou ao original depois da sabotagem');

    /* G. a permissão nos 3 pontos, de volta no banco de prova com a demonstração */
    fs.writeFileSync(CONFIG, configOriginal);
    const cred = JSON.parse(fs.readFileSync(path.join(L.RAIZ, 'tests', 'tabler', 'saida', 'credenciais.json'), 'utf8'));
    for (const [u, adm] of [['administrador', true], ['supervisao.demo', false], ['secretaria.demo', false], ['prof.laura.demo', false]]) {
      const c = await navegador.newContext({ viewport: { width: 1366, height: 900 } });
      const q = await L.pagina(c);
      await q.goto(L.BASE + 'painel/login/acesso');
      await q.fill('input[name="usuario"]', u);
      await q.fill('input[name="senha"]', cred.painel[u]);
      await Promise.all([q.waitForNavigation(), q.click('[name="SendLogin"]')]);
      const menuAtualizar = !!(await q.$('#railMenu a[href*="atualizar/index"]'));
      const menuVersao = !!(await q.$('#railMenu a[href*="versao/index"]'));
      await q.goto(L.BASE + 'painel/atualizar/index');
      const abriuTela = /atualizar\/index/.test(q.url()) && !!(await q.$('#versaoBanco'));
      await q.goto(L.BASE + 'painel/versao/index');
      const situacao = await q.$('#situacaoAtualizacao');
      L.ok(menuVersao && !!situacao && menuAtualizar === adm && abriuTela === adm,
        `${u}: "Versão" no menu e na tela; ${adm ? '"Atualizar o sistema" no menu e a tela abre' : 'sem "Atualizar o sistema" no menu, e a rota recusada'}`);
      await c.close();
    }
  } catch (e) {
    L.ok(false, 'erro na prova: ' + e.message.split('\n')[0]);
  } finally {
    fs.writeFileSync(CONFIG, configOriginal);
    if (fs.existsSync(FALHA)) { fs.unlinkSync(FALHA); }
    L.ok(fs.readFileSync(CONFIG, 'utf8') === configOriginal, 'a Config do painel voltou ao banco de prova de sempre');
    await navegador.close();
    process.exit(L.resumo('onda 2, etapa 7') ? 0 : 1);
  }
})();
