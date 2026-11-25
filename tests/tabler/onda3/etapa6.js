/*
 * ONDA 3, ETAPA 6 — Sobre, Avisos do sistema, a versão nova, as Bibliotecas e as Verificações de segurança.
 *   A. Manutenção (só o Administrador): Avisos do sistema, Verificações de segurança, Bibliotecas e Atualizar o sistema —
 *      o grupo deixa de ter um item só; os outros perfis não abrem as três telas;
 *   B. Avisos do sistema: as versões (a dos arquivos = a do pacote; a do banco), as atualizações por aplicar (com uma
 *      pendente de prova), a versão nova desligada (o endereço vazio), o resumo da segurança, o copyright, sem os links
 *      do projeto enquanto as constantes estão vazias;
 *   C. Verificações de segurança: cada item com a situação e, quando não é OK, o que fazer; a tela e a linha de comando
 *      dizem o mesmo (a mesma implementação); nada executa programa;
 *   D. a versão nova: no máximo uma consulta por dia, tempo curto, nenhuma identificação no pedido, só um JSON pequeno,
 *      nunca baixa nada, desligável pelo parâmetro;
 *   E. Sobre: pública; para quem não entrou, sem a versão e sem a lista das bibliotecas; para quem entrou, com as duas;
 *      a licença e a marca (o TRADEMARK) para todos;
 *   F. Bibliotecas: a mesma lista do Sobre.
 *   Sabotagens: o instalador sem a trava (a tela e a linha de comando acusam); a consulta sem o limite de um dia; o
 *   Sobre mostrando a versão a quem não entrou.
 *
 *   node tests/tabler/onda3/etapa6.js     (depois do ciclo, com a demonstração)
 */
const fs = require('fs');
const path = require('path');
const { execFileSync, spawnSync, spawn } = require('child_process');
const L = require('../lib');

const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste$/.test(BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
const MYSQL = process.env.VCOSTAEDU_MYSQL || 'C:/xampp/mysql/bin/mysql.exe';
const PHP = process.env.VCOSTAEDU_PHP || 'C:/xampp/php/php.exe';
const sql = (q) => execFileSync(MYSQL, ['-u', 'root', '-N', '--default-character-set=utf8mb4', BANCO, '-e', q]).toString().replace(/\r/g, '').trim();
let cred = JSON.parse(fs.readFileSync(path.join(__dirname, '..', 'saida', 'credenciais.json'), 'utf8'));
const ETAPA = 'onda3/etapa6';
const SENHA = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const PENDENTE = path.join(L.RAIZ, 'database', 'migrations', '0098_prova_pendente.sql');
const TRAVA = path.join(L.RAIZ, 'instalar', '.htaccess');
const VERSAO_NOVA = path.join(L.RAIZ, 'core', 'VersaoNova.php');
const SOBRE = path.join(L.RAIZ, 'sobre.php');
const PORTA = 8765;
const LOG = path.join(L.PROVAS, ETAPA, 'pedidos-versao.log');

/* a versão do pacote: a maior das migrations (como core/Legal.php::versaoDoPacote) */
const versaoPacote = (() => {
  let maior = '0.0.0';
  for (const f of fs.readdirSync(path.join(L.RAIZ, 'database', 'migrations')).filter((x) => x.endsWith('.sql'))) {
    const s = fs.readFileSync(path.join(L.RAIZ, 'database', 'migrations', f), 'utf8');
    for (const m of s.matchAll(/INSERT INTO `adms_versoes`[^;]*?(?:VALUES\s*\(|SELECT\s+)\d+\s*,\s*'(\d+\.\d+\.\d+)'/g)) {
      const a = m[1].split('.').map(Number); const b = maior.split('.').map(Number);
      if (a[0] > b[0] || (a[0] === b[0] && (a[1] > b[1] || (a[1] === b[1] && a[2] > b[2])))) { maior = m[1]; }
    }
  }
  return maior;
})();

/* o retrato atual da onda (o da etapa mais nova): o banco montado pelo ciclo tem todas as migrations */
const REGUA_ATUAL = L.reguaAtual(); // o retrato mais novo, de qualquer onda (lib.js)
async function entrar(navegador, usuario, senha) {
  const ctx = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' });
  const p = await L.pagina(ctx);
  await p.goto(L.BASE + 'painel/login/acesso');
  await p.fill('input[name="usuario"]', usuario);
  await p.fill('input[name="senha"]', senha || cred.painel[usuario]);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
  return { ctx, p };
}
async function sabotar(nome, arquivo, mudar, conferir) {
  const orig = fs.readFileSync(arquivo, 'utf8');
  let reprovou = false;
  try {
    const novo = mudar(orig);
    if (novo === orig) { throw new Error('sabotagem sem efeito: ' + nome); }
    fs.writeFileSync(arquivo, novo);
    reprovou = !(await conferir());
  } finally { fs.writeFileSync(arquivo, orig); }
  L.ok(reprovou && fs.readFileSync(arquivo, 'utf8') === orig, `sabotagem (${nome}): a prova reprova o defeito; o arquivo voltou`);
}
/* a linha de comando: [{titulo, situacao}] */
const cli = () => {
  const r = spawnSync(PHP, [path.join(L.RAIZ, 'database', 'ferramentas', 'implantacao', 'verificar_seguranca.php')], { encoding: 'utf8', cwd: L.RAIZ });
  return [...(r.stdout || '').matchAll(/^\s+\[(OK|ALERTA|FALHA)\]\s+([^:]+):/gm)].map((m) => ({ titulo: m[2].trim(), situacao: m[1] }));
};
const tela = (p) => p.$$eval('#itensSeguranca [data-codigo]', (xs) => xs.map((x) => ({ codigo: x.dataset.codigo, situacao: x.dataset.situacao,
  titulo: x.querySelector('.fw-semibold').innerText.trim(), fazer: /O que fazer:/.test(x.innerText) })));
/* o aviso de versão nova, como a tela chama: {ligado, novo, erro, ms} */
const consultar = (rota, mais = 0) => JSON.parse(execFileSync(PHP, [path.join(__dirname, 'versao_nova.php'), `http://127.0.0.1:${PORTA}/${rota}`, String(mais), versaoPacote],
  { cwd: L.RAIZ, encoding: 'utf8' }).trim().split('\n').pop());
const pedidos = () => (fs.existsSync(LOG) ? fs.readFileSync(LOG, 'utf8').trim().split('\n').filter(Boolean).map((l) => JSON.parse(l)) : []);
const limparCache = () => sql("DELETE FROM adms_instalacao_estado WHERE chave = 'versao_nova.consulta'");

(async () => {
  /* a prova pede a demonstração montada pelo ciclo (os ids da régua): uma prova anterior que a removeu (a jornada 4)
     não a deixa sem ela — o banco é refeito do zero */
  if (sql("SELECT valor FROM adms_instalacao_estado WHERE chave = 'demonstracao.ativa'") !== '1') {
    const c = spawnSync(PHP, [path.join(L.RAIZ, 'tests', 'tabler', 'ciclo.php'), 'comparar', REGUA_ATUAL], { encoding: 'utf8', cwd: L.RAIZ, env: process.env, maxBuffer: 1 << 26 });
    if (c.status !== 0) { console.error('o ciclo não refez a demonstração'); process.exit(1); }
    cred = JSON.parse(fs.readFileSync(path.join(__dirname, '..', 'saida', 'credenciais.json'), 'utf8')); // as senhas novas do ciclo
  }
  const { navegador } = await L.abrir();
  fs.mkdirSync(path.join(L.PROVAS, ETAPA), { recursive: true });
  let servidor = null;
  try {
    console.log('\n== Onda 3, Etapa 6');
    const { ctx, p } = await entrar(navegador, 'administrador', SENHA);

    /* A. o grupo Manutenção */
    const grupo = await p.evaluate(() => {
      const t = [...document.querySelectorAll('#railMenu .vc-side-grupo-toggle')].find((x) => /Manutenção/.test(x.getAttribute('title') || x.textContent));
      const ul = t && document.getElementById(t.getAttribute('aria-controls'));
      return ul ? [...ul.querySelectorAll('a.nav-link')].map((a) => a.textContent.replace(/\s+/g, ' ').trim()) : [];
    });
    L.ok(['Avisos do sistema', 'Verificações de segurança', 'Bibliotecas'].every((n) => grupo.includes(n)) && grupo.length >= 4,
      `Manutenção, para o Administrador: ${grupo.join(', ')} (o grupo deixou de ter um item só)`);
    for (const perfil of ['direcao.demo', 'secretaria.demo', 'prof.laura.demo']) {
      const o = await entrar(navegador, perfil);
      const abre = [];
      for (const r of ['avisos-sistema/index', 'verificacoes-seguranca/index', 'bibliotecas/index']) {
        await o.p.goto(L.BASE + 'painel/' + r);
        if (await o.p.$('#cartaoVersoes, #itensSeguranca, #tabelaBibliotecas')) { abre.push(r); }
      }
      const noMenu = await o.p.$$eval('#railMenu a.nav-link[href]', (as) => as.filter((a) => /avisos-sistema|verificacoes-seguranca|bibliotecas/.test(a.href)).length);
      L.ok(abre.length === 0 && noMenu === 0, `${perfil}: não abre as três telas e não as vê no menu${abre.length ? ' — abriu ' + abre.join(', ') : ''}`);
      await o.ctx.close();
    }

    /* B. Avisos do sistema */
    await p.goto(L.BASE + 'painel/avisos-sistema/index');
    await L.semErros(p, 'avisos do sistema');
    const av = await p.evaluate(() => ({ arq: (document.querySelector('#versaoArquivos') || {}).innerText, banco: (document.querySelector('#versaoBanco') || {}).innerText,
      sem: !!document.querySelector('#semPendentes'), desligada: !!document.querySelector('#versaoNovaDesligada'), seg: !!document.querySelector('#irVerificacoes'),
      projeto: (document.querySelector('#cartaoProjeto') || {}).innerText || '', links: document.querySelectorAll('#linkApoie, #linkServicos').length,
      respondeu: !!document.querySelector('#versaoNovaEmDia, #versaoNovaDisponivel'), semResposta: !!document.querySelector('#versaoNovaSemResposta') }));
    L.ok(av.arq === versaoPacote && av.banco === versaoPacote && av.sem, `as versões: arquivos ${av.arq}, banco ${av.banco} (o pacote: ${versaoPacote}); nenhuma atualização por aplicar`);
    /* Onda 6, Etapa 7 (D-59): o endereço do projeto vem preenchido; enquanto o site não responder, nada aparece como novidade */
    L.ok(!av.desligada && (av.respondeu || av.semResposta), `a versão nova: consulta o endereço fixo do projeto e mostra o resultado sem erro (${av.respondeu ? 'o site respondeu' : 'o site ainda não responde: nada aparece como versão nova'})`);
    L.ok(av.seg && /Copyright © 2019-\d{4} VCOSTA/.test(av.projeto) && /AGPL-3\.0/.test(av.projeto) && (av.respondeu || av.links === 0),
      'o resumo da segurança, o copyright e a licença; sem os links Apoie e Serviços enquanto as constantes estão vazias');
    await p.screenshot({ path: path.join(L.PROVAS, ETAPA, 'avisos-do-sistema.png'), fullPage: true });
    fs.writeFileSync(PENDENTE, "-- prova da Etapa 6: uma atualização por aplicar\nSELECT 1;\n");
    try {
      await p.goto(L.BASE + 'painel/avisos-sistema/index');
      const pend = await p.$eval('#avisoPendentes', (e) => e.innerText).catch(() => '');
      L.ok(/1 atualização/.test(pend) && /Atualizar o sistema/.test(pend), `com uma atualização por aplicar: "${pend.trim()}"`);
      await p.screenshot({ path: path.join(L.PROVAS, ETAPA, 'avisos-com-pendente.png'), fullPage: true });
    } finally { fs.unlinkSync(PENDENTE); }

    /* C. Verificações de segurança: a tela e a linha de comando */
    await p.goto(L.BASE + 'painel/verificacoes-seguranca/index');
    await L.semErros(p, 'verificações de segurança');
    const t1 = await tela(p);
    L.ok(t1.length >= 8 && t1.every((x) => ['OK', 'ALERTA', 'FALHA'].includes(x.situacao)) && t1.every((x) => x.situacao === 'OK' || x.fazer),
      `a tela: ${t1.length} itens (${t1.map((x) => `${x.titulo} ${x.situacao}`).join('; ')}); todo item fora de OK diz o que fazer`);
    await p.screenshot({ path: path.join(L.PROVAS, ETAPA, 'verificacoes-de-seguranca.png'), fullPage: true });
    /* o HTTPS (a tela julga a requisição; a linha de comando, a configuração) e os erros do PHP (o PHP do servidor e o
       da linha de comando têm php.ini próprios) podem divergir de propósito; os outros itens, iguais */
    const proprio = (x) => !['HTTPS', 'Erros do PHP'].includes(x.titulo);
    const iguais = (a, b) => JSON.stringify(a.filter(proprio).map((x) => x.titulo + ':' + x.situacao)) === JSON.stringify(b.filter(proprio).map((x) => x.titulo + ':' + x.situacao));
    const c1 = cli();
    L.ok(c1.length === t1.length && c1.map((x) => x.titulo).join('|') === t1.map((x) => x.titulo).join('|') && iguais(t1, c1),
      `a linha de comando diz o mesmo que a tela (${c1.length} itens, na mesma ordem; HTTPS e erros do PHP julgados cada um no seu PHP)`);
    const fonte = fs.readFileSync(path.join(L.RAIZ, 'core', 'Verificacoes.php'), 'utf8').replace(/\/\*[\s\S]*?\*\/|\/\/.*$/gm, '');
    L.ok(!/\b(exec|shell_exec|system|passthru|proc_open|popen|pcntl_exec)\s*\(|`/.test(fonte), 'as verificações não executam programa (nem exec, nem shell, nem crase)');
    /* sabotagem: o instalador sem a trava — a tela e a linha de comando acusam FALHA */
    const trava = fs.readFileSync(TRAVA);
    let acusou = false;
    try {
      fs.unlinkSync(TRAVA);
      await p.goto(L.BASE + 'painel/verificacoes-seguranca/index');
      const t2 = await tela(p); const c2 = cli();
      acusou = (t2.find((x) => x.codigo === 'instalador') || {}).situacao === 'FALHA' && (c2.find((x) => x.titulo === 'Instalador') || {}).situacao === 'FALHA'
        && await p.$eval('#resumoSeguranca [data-situacao="FALHA"]', (e) => /^[1-9]/.test(e.innerText.trim()));
    } finally { fs.writeFileSync(TRAVA, trava); }
    L.ok(acusou && fs.readFileSync(TRAVA).equals(trava), 'sabotagem (o instalador sem a trava): a tela e a linha de comando dizem FALHA, com o que fazer; o arquivo voltou');

    /* D. a versão nova, contra um endereço falso desta máquina */
    if (fs.existsSync(LOG)) { fs.unlinkSync(LOG); }
    servidor = spawn(PHP, ['-S', `127.0.0.1:${PORTA}`, path.join(__dirname, 'versao_falsa.php')], { env: { ...process.env, VC_VERSAO_LOG: LOG }, stdio: 'ignore' });
    await p.waitForTimeout(800);
    const param = sql("SELECT valor FROM adms_parametros WHERE chave = 'sistema.verificar_versao_nova'");
    limparCache();
    const v1 = consultar('versao'); const v2 = consultar('versao', 3600); const v3 = consultar('versao', 90000);
    const n3 = pedidos().length;
    L.ok(v1.novo === '9.9.9' && v2.novo === '9.9.9' && n3 === 2, `no máximo uma consulta por dia: três aberturas (agora, 1 hora depois, 25 horas depois) = ${n3} pedidos; "a versão 9.9.9 está disponível"`);
    const ped = pedidos()[0] || { cabecalhos: {} };
    const cab = Object.keys(ped.cabecalhos).map((k) => k.toLowerCase()).sort();
    L.ok(ped.metodo === 'GET' && !/\?/.test(ped.uri) && ped.corpo === '' && !cab.includes('cookie') && !cab.includes('referer') && ped.cabecalhos['User-Agent'] === 'VCOSTAEDU'
      && !JSON.stringify(ped).includes(versaoPacote) && !/localhost\/|vcostaedu-|painel/i.test(JSON.stringify(ped.cabecalhos)),
      `nenhuma identificação no pedido: GET ${ped.uri}, sem parâmetro, sem corpo, sem cookie, sem a versão instalada (cabeçalhos: ${cab.join(', ')})`);
    limparCache();
    const vg = consultar('grande');
    L.ok(!vg.novo && vg.erro, `só um JSON pequeno: a resposta de 64 KB é recusada ("${vg.erro}")`);
    limparCache();
    const vd = consultar('devagar');
    L.ok(!vd.novo && vd.erro && vd.ms < 5000, `tempo curto: o endereço que demora 10 s é abandonado em ${vd.ms} ms ("${vd.erro}"); a tela segue`);
    const fv = fs.readFileSync(VERSAO_NOVA, 'utf8').replace(/\/\*[\s\S]*?\*\/|\/\/.*$/gm, '');
    L.ok(!/file_put_contents|fopen|fwrite|copy\s*\(|ZipArchive|PharData|move_uploaded_file|curl_exec|unlink/.test(fv) && /self::LIMITE \+ 1/.test(fv),
      'nunca baixa nada: o código só lê a resposta (até 16 KB) e grava só o resultado no banco');
    limparCache();
    sql("UPDATE adms_parametros SET valor = '0' WHERE chave = 'sistema.verificar_versao_nova'");
    const antes = pedidos().length;
    let vx;
    try { vx = consultar('versao'); } finally { sql(`UPDATE adms_parametros SET valor = '${param.replace(/'/g, '')}' WHERE chave = 'sistema.verificar_versao_nova'`); }
    L.ok(!vx.ligado && pedidos().length === antes, 'o parâmetro "Consultar se há uma versão nova" = 0: nenhum pedido; o aviso some');
    limparCache();
    await sabotar('a consulta sem o limite de um dia', VERSAO_NOVA, (s) => s.replace('VALIDADE = 86400', 'VALIDADE = 0'), async () => {
      limparCache(); const n0 = pedidos().length; consultar('versao'); consultar('versao', 60); return pedidos().length - n0 === 1;
    });
    limparCache();

    /* E. Sobre: quem não entrou e quem entrou */
    const anon = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' });
    const pa = await L.pagina(anon);
    const sobreAnon = async () => {
      await pa.goto(L.BASE + 'sobre.php');
      return pa.evaluate((v) => ({ versao: !!document.querySelector('#versao'), tabela: !!document.querySelector('#creditos table'), temVersao: document.body.innerText.includes(v),
        marca: /TRADEMARK|marca/i.test((document.querySelector('#marca') || {}).innerText || ''), licenca: ((document.querySelector('#licenca') || {}).innerText || '').length > 1000 }), versaoPacote);
    };
    const sa = await sobreAnon();
    L.ok(!sa.versao && !sa.tabela && !sa.temVersao && sa.marca && sa.licenca, 'Sobre, para quem não entrou: sem a versão (em lugar nenhum da página) e sem a lista das bibliotecas; a licença e a marca');
    await pa.screenshot({ path: path.join(L.PROVAS, ETAPA, 'sobre-sem-entrar.png'), fullPage: true });
    await sabotar('o Sobre mostrando a versão a quem não entrou', SOBRE, (s) => s.replace("$versao = $entrou ? VcLegal::versaoDoPacote(__DIR__) : '';", '$versao = VcLegal::versaoDoPacote(__DIR__);'),
      async () => { const s = await sobreAnon(); return !s.temVersao; });
    await anon.close();
    await p.goto(L.BASE + 'sobre.php');
    const sl = await p.evaluate(() => ({ versao: ((document.querySelector('#versao') || {}).innerText || '').trim(), linhas: document.querySelectorAll('#creditos tbody tr').length,
      nomes: [...document.querySelectorAll('#creditos tbody tr td:first-child')].map((td) => td.innerText.trim()) }));
    L.ok(sl.versao === versaoPacote && sl.linhas > 5, `Sobre, para quem entrou: a versão ${sl.versao} e as ${sl.linhas} bibliotecas`);
    await p.screenshot({ path: path.join(L.PROVAS, ETAPA, 'sobre-dentro.png'), fullPage: true });

    /* F. Bibliotecas: a mesma lista */
    await p.goto(L.BASE + 'painel/bibliotecas/index');
    await L.semErros(p, 'bibliotecas');
    const bib = await p.$$eval('#tabelaBibliotecas tbody tr td:first-child', (tds) => tds.map((td) => td.innerText.trim()));
    L.ok(bib.length === sl.linhas && bib.join('|') === sl.nomes.join('|'), `Manutenção → Bibliotecas: as mesmas ${bib.length} bibliotecas do Sobre, com a versão e a licença`);
    await p.screenshot({ path: path.join(L.PROVAS, ETAPA, 'bibliotecas.png'), fullPage: true });
    await ctx.close();
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message);
  } finally {
    if (servidor) { servidor.kill(); }
    if (fs.existsSync(PENDENTE)) { fs.unlinkSync(PENDENTE); }
    try { limparCache(); } catch (e) { /* o banco */ }
    await navegador.close();
  }
  process.exit(L.resumo('onda3/etapa6') ? 0 : 1);
})();
