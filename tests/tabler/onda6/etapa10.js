/*
 * ONDA 6, ETAPA 10 — o fechamento: uma instalação da 1.42.0 (o fim da onda 4) atualizada PELA TELA, em passos, até a
 * versão de agora, em cada tipo de instituição, com a régua IDÊNTICA (a mesma prova da Onda 4, Etapa 6, da 1.37.0).
 *   node tests/tabler/onda6/etapa10.js superior|escola
 *   1. o banco da 1.42.0 (as migrations daquele commit, pelo mesmo runner) e, na escola, o tipo "escola" no banco antes
 *      da demonstração; a demonstração montada pelas telas do código da 1.42.0 (servido à parte — o cenário pelo tipo);
 *      o retrato tirado pela régua DAQUELE código;
 *   2. os arquivos de agora sobre esse banco: "Atualizar o sistema", uma etapa por chamada, até o fim;
 *   3. o tipo continua o mesmo; a régua de agora contra o retrato de antes: IDÊNTICA nos dados acadêmicos, diferente só
 *      nas tabelas de configuração; no superior, as tabelas da escola vazias e o menu sem elas; na escola, as turmas;
 *   sabotagem: o arredondamento do motor mudado — a régua acusa; o arquivo volta.
 * Troca a Config do painel desta pasta para o banco de prova de atualização e a devolve; monta e apaga a pasta antiga.
 */
const fs = require('fs');
const path = require('path');
const { execFileSync, spawnSync } = require('child_process');
const L = require('../lib');

const BASE_BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste$/.test(BASE_BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
const BANCO2 = BASE_BANCO + '2';
const MYSQL = process.env.VCOSTAEDU_MYSQL || 'C:/xampp/mysql/bin/mysql.exe';
const PHP = process.env.VCOSTAEDU_PHP || 'C:/xampp/php/php.exe';
const sql2 = (q) => execFileSync(MYSQL, ['-u', 'root', '-N', '--default-character-set=utf8mb4', BANCO2], { input: Buffer.from(q, 'utf8') }).toString().replace(/\r/g, '').trim();
const TIPO = process.argv[2] === 'escola' ? 'escola' : 'superior';
const ETAPA = 'onda6/etapa10-' + TIPO;
const SENHA = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const ANTIGA = '4aa061e'; // a 1.42.0 (o fim da onda 4)
const CONFIG = path.join(L.RAIZ, 'painel', 'core', 'Config.php');
const MIGRATIONS = path.join(L.RAIZ, 'database', 'migrations');
const CALCULO = path.join(L.RAIZ, 'core', 'MotorMedia.php');
const SAIDA = path.join(L.PROVAS, ETAPA);
const ANTES = path.join(SAIDA, 'regua-1.42.0.json');
const CONFIG_DA_ONDA = 'adms_versoes,adms_novidades,adms_paginas,adms_nivacs_pgs,adms_acoes,adms_instalacao_estado,adms_paginas_modulo,adms_fases,adms_paginas_recurso,col_paginas_modulo,'
  + 'rm_media_regras,rm_media_regra_versoes,rm_media_regra_componentes,rm_media_regra_acrescimo,adms_parametros,col_niveis_acessos,col_paginas,col_nivacs_pgs';

const php = (args, entrada, cwd = L.RAIZ) => spawnSync(PHP, args, { cwd, encoding: 'utf8', env: process.env, input: entrada, maxBuffer: 1 << 26 });
const configOriginal = fs.readFileSync(CONFIG, 'utf8');
function apontar() {
  let s = configOriginal.replace(/define\('DBNAME',\s*'[^']*'\)/, `define('DBNAME', '${BANCO2}')`);
  s = s.replace(/define\('BANCO_DEMONSTRACAO',\s*'[^']*'\)/, `define('BANCO_DEMONSTRACAO', '${BANCO2}')`);
  fs.writeFileSync(CONFIG, s);
}
async function entrar(navegador) {
  const ctx = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' });
  const p = await L.pagina(ctx);
  await p.goto(L.BASE + 'painel/login/acesso');
  await p.fill('input[name="usuario"]', 'administrador');
  await p.fill('input[name="senha"]', SENHA);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
  return { ctx, p };
}
const comparar = () => php([path.join(L.RAIZ, 'tests', 'tabler', 'regua.php'), 'comparar', ANTES, '--config=' + CONFIG_DA_ONDA]);

(async () => {
  const { navegador } = await L.abrir();
  fs.mkdirSync(SAIDA, { recursive: true });
  try {
    console.log(`\n== Onda 6, Etapa 10 — a 1.42.0 atualizada pela tela (${TIPO})`);

    /* 1. a 1.42.0 com a demonstração, e o retrato tirado pelo código dela */
    let r = php([path.join(L.RAIZ, 'tests', 'tabler', 'onda2', 'banco_antigo.php'), ANTIGA]);
    const antigo = JSON.parse((r.stdout || '{}').trim().split('\n').pop() || '{}');
    if (TIPO === 'escola') { sql2("INSERT INTO adms_instalacao_estado (chave, valor) VALUES ('instituicao.tipo', 'escola') ON DUPLICATE KEY UPDATE valor = 'escola'"); }
    r = php([path.join(L.RAIZ, 'tests', 'tabler', 'onda3', 'instalacao_antiga.php'), ANTIGA]);
    const pasta = JSON.parse((r.stdout || '{}').trim().split('\n').pop() || '{}').pasta || (L.RAIZ + '-antiga');
    const demo = php([path.join(L.RAIZ, 'tests', 'tabler', 'onda3', 'instalacao_antiga.php'), '--demo']);
    const versaoAntes = sql2('SELECT numero FROM adms_versoes WHERE situacao = 1');
    const reguaAntiga = php([path.join(pasta, 'tests', 'tabler', 'regua.php'), 'gravar', ANTES], undefined, pasta);
    L.ok(antigo.admin && demo.status === 0 && versaoAntes === '1.42.0' && reguaAntiga.status === 0 && fs.existsSync(ANTES),
      `a 1.42.0 (${TIPO}; ${antigo.aplicadas} etapas do banco, pelo mesmo runner), com a demonstração montada pelas telas daquela versão; o retrato pela régua daquele código (${(reguaAntiga.stdout || '').trim().split('\n').pop()})`);
    const faltam = fs.readdirSync(MIGRATIONS).filter((f) => /^\d{4}_.*\.sql$/.test(f)).length - +sql2('SELECT COUNT(*) FROM adms_migrations');
    try {
      /* 2. os arquivos de agora: pela tela, uma etapa por chamada */
      apontar();
      const o = await entrar(navegador);
      await o.p.goto(L.BASE + 'painel/atualizar/index');
      await L.semErros(o.p, 'Atualizar o sistema (da 1.42.0)');
      await L.print(o.p, ETAPA, 'atualizar-antes');
      const chamadas = [];
      o.p.on('requestfinished', (q) => { if (/atualizar\/(passo|aplicar)/.test(q.url()) && q.method() === 'POST') { chamadas.push(Date.now()); } });
      /* Onda 7, Etapa 1: quantas etapas cada chamada aplicou sai da RESPOSTA dela ("passo": as feitas até ali), não de uma
         leitura do banco depois dela — a leitura corria com a chamada seguinte e, fora de ordem, contava errado */
      const feitos = [];
      o.p.on('response', async (resp) => {
        if (/atualizar\/(passo|aplicar)/.test(resp.url()) && resp.request().method() === 'POST') {
          try { const j = await resp.json(); if (typeof j.passo === 'number') { feitos.push(j.passo); } } catch (e) { /* */ }
        }
      });
      const aplicadasPorChamada = [];
      await o.p.check('#fizBackup');
      await o.p.click('#aplicarAtualizacao');
      await o.p.waitForSelector('#progressoAtualizacao[data-situacao="concluida"], #progressoAtualizacao[data-situacao="falhou"]', { timeout: 600000 });
      const situacao = await o.p.getAttribute('#progressoAtualizacao', 'data-situacao');
      await L.print(o.p, ETAPA, 'atualizar-concluida');
      await o.p.goto(L.BASE + 'painel/atualizar/index');
      const versaoDepois = (await o.p.textContent('#versaoDepois').catch(() => '')).trim() || sql2('SELECT numero FROM adms_versoes WHERE situacao = 1');
      const ordem = [...feitos].sort((a, b) => a - b);
      ordem.forEach((v, i) => aplicadasPorChamada.push(v - (i ? ordem[i - 1] : 0)));
      const passos = aplicadasPorChamada.filter((n) => n > 0);
      L.ok(situacao === 'concluida' && faltam >= 3 && passos.length === faltam && passos.every((n) => n === 1),
        `pela tela, em passos: ${faltam} etapas do banco faltavam, ${passos.length} chamadas aplicaram uma cada (${aplicadasPorChamada.join(', ')}); da ${versaoAntes} à ${versaoDepois}`);
      L.ok(+sql2('SELECT COUNT(*) FROM adms_migrations') === fs.readdirSync(MIGRATIONS).filter((f) => /^\d{4}_.*\.sql$/.test(f)).length
        && sql2("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'adms_atualizacao_lote'") === '0',
        'todas as etapas do banco aplicadas; a tabela do lote apagada depois do resultado');

      /* 3. continua de ensino superior; a régua idêntica */
      L.ok(sql2("SELECT valor FROM adms_instalacao_estado WHERE chave = 'instituicao.tipo'") === TIPO, `o tipo de instituição continua: ${TIPO} (a instalação que já existia)`);
      const tabelasEscola = sql2("SELECT GROUP_CONCAT(TABLE_NAME) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE 'esc\\_%'").split(',');
      const cheias = tabelasEscola.filter((t) => +sql2(`SELECT COUNT(*) FROM \`${t}\``) > 0);
      L.ok(TIPO === 'escola' ? +sql2('SELECT COUNT(*) FROM esc_turmas') > 0 && +sql2('SELECT COUNT(*) FROM esc_turma_alunos') > 0 : tabelasEscola.length >= 10 && cheias.length === 0,
        TIPO === 'escola' ? `as turmas do colégio fictício seguem lá (${sql2('SELECT COUNT(*) FROM esc_turmas')} turmas)` : `as tabelas da escola existem (${tabelasEscola.length}) e estão vazias`);
      const cmp = comparar();
      const fim = (cmp.stdout || '').trim().split('\n');
      L.ok(cmp.status === 0 && /IDÊNTICA/.test(cmp.stdout), `a régua de agora contra o retrato da 1.42.0: ${fim.filter((l) => /Régua:|IDÊNTICA|DIFERENTE|configuração mudada/.test(l)).join(' | ').slice(0, 600)}`);
      await o.p.goto(L.BASE + 'painel/home/index');
      const menu = await o.p.$$eval('#railMenu a[href]', (as) => as.map((a) => a.getAttribute('href')));
      const temEscola = menu.some((h) => /\/(turmas|escola-anos|escola-regras|escola-notas|escola-conselho)\//.test(h));
      L.ok(TIPO === 'escola' ? temEscola : !temEscola, TIPO === 'escola' ? 'o menu da escola com as telas da escola' : 'o menu do ensino superior sem as telas da escola');
      await L.semErros(o.p, 'o início depois da atualização');

      /* sabotagem: o cálculo do ensino superior mudado — a régua acusa */
      const orig = fs.readFileSync(CALCULO, 'utf8');
      let acusou = false;
      try {
        const de = '$q = intdiv(2 * $num * $esc + $den, 2 * $den);'; // o arredondamento matemático (o padrão)
        if (orig.split(de).length !== 2) { throw new Error('sabotagem sem o trecho'); }
        fs.writeFileSync(CALCULO, orig.replace(de, '$q = intdiv($num * $esc, $den);'));
        const s = comparar();
        acusou = s.status !== 0 || !/IDÊNTICA/.test(s.stdout);
      } finally { fs.writeFileSync(CALCULO, orig); }
      L.ok(acusou && fs.readFileSync(CALCULO, 'utf8') === orig && /IDÊNTICA/.test(comparar().stdout), 'sabotagem (o arredondamento matemático do motor trocado por "para baixo"): a régua acusa a diferença; o arquivo voltou');
      await o.ctx.close();
    } finally {
      fs.writeFileSync(CONFIG, configOriginal);
      php([path.join(L.RAIZ, 'tests', 'tabler', 'onda3', 'instalacao_antiga.php'), '--apagar']);
    }
    L.ok(fs.readFileSync(CONFIG, 'utf8') === configOriginal, 'a Config do painel voltou ao banco de prova de sempre; a pasta da versão antiga, apagada');
  } catch (e) {
    L.ok(false, 'a prova parou: ' + (e && e.message ? e.message.split('\n')[0] : e));
    if (fs.readFileSync(CONFIG, 'utf8') !== configOriginal) { fs.writeFileSync(CONFIG, configOriginal); }
  } finally {
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
