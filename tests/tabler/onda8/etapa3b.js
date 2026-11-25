/*
 * PROVA DA ONDA 8, ETAPA NOVA (3b) — do site da instituição ao Moodle numa chamada só (D-70), contra o Moodle de teste.
 *   A. no ensino superior (o banco que o ciclo deixa): POST /inscricoes com o curso, o ano e as ofertas; a mesma
 *      matrícula de novo não duplica (diz o que já existia); a chave sem Enturmação é recusada;
 *   B. na escola (a demonstração do colégio, montada pela tela; APAGA o banco de prova):
 *      1. a demonstração tem o curso livre: a turma aberta, os quatro módulos, vagas livres;
 *      2. o Moodle de teste conectado (o e-mail da escola, a categoria de prova, o envio automático);
 *      3. a página de venda: a promessa, os quatro módulos, o botão no alto e no fim, o rodapé de demonstração; sem
 *         rolagem para o lado em 360, 390, 768 e 1366 px; o envio SEM JavaScript chega à confirmação;
 *      4. uma inscrição pelo site: a pessoa existe, matriculada, na turma, com usuário no Moodle e inscrita nos quatro
 *         módulos; o tempo até "Tudo pronto"; a confirmação sem chave, sem endereço e sem identificador do Moodle;
 *      5. o clique duplo no site e a mesma chamada duas vezes ao mesmo tempo: uma pessoa só (a mesma resposta);
 *      6. a turma completa, a turma que não existe e a matrícula que já está noutra turma: nada pela metade, e a razão;
 *      7. uma turma de 10+ componentes pela operação única: o tempo da chamada; o que não coube termina sozinho, sem
 *         clique (o tempo);
 *      8. a pessoa sem e-mail, no modo "e-mail da escola", vai ao Moodle;
 *      9. o Moodle fora do ar na hora: os dados ficam, a resposta é normal, e o envio se completa sozinho quando ele volta;
 *     10. o e-mail corrigido depois (no modo "só o e-mail do aluno"): o usuário e as inscrições vão sozinhos;
 *     11. o limite por endereço (o teto pequeno recusa; o X-Forwarded-For forjado não escapa) e 150 inscrições rápidas do
 *         mesmo endereço com o teto em 300: todas criadas, todas no Moodle, nenhuma duplicada;
 *     12. "Entrar no curso", com o botão "Criar minha senha agora" ligado: a senha criada pelo link, o mesmo link aberto
 *         de novo (a mensagem "a senha já foi criada" e o caminho da entrada), a entrada no portal e no Moodle, na
 *         disciplina; com o botão desligado (o padrão), a confirmação não mostra o link;
 *   sabotagens: (a) a retomada desligada — o envio não termina sozinho; (b) as inscrições não voltam à fila — o e-mail
 *   corrigido não leva as inscrições; (c) sem a trava da chave de repetição — as duas chamadas juntas não devolvem a
 *   mesma resposta; (d) o link usado sem a mensagem — volta o erro de link inválido. Os arquivos voltam iguais (sha256).
 *   Os tempos ficam em saida/onda8-etapa3b-tempos.json.
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE, VCOSTAEDU_MOODLE_URL, VCOSTAEDU_MOODLE_CHAVE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { spawnSync, execFileSync } = require('child_process');
const L = require('../lib');

const ETAPA = 'onda8/etapa3b';
const SAIDA = path.join(__dirname, '..', 'saida');
const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste$/.test(BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
const SENHA = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const MURL = process.env.VCOSTAEDU_MOODLE_URL || '';
const MCHAVE = process.env.VCOSTAEDU_MOODLE_CHAVE || '';
const MOODLE = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'moodle', 'AdmsMoodle.php');
const SERVIDOR = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'api', 'AdmsApiServidor.php');
const LOGIN = path.join(L.RAIZ, 'portal', 'app', 'adms', 'Controllers', 'Login.php');
const SITE_DIR = path.join(L.RAIZ, 'exemplos', 'curso-livre');
const SITE_CFG = path.join(SITE_DIR, 'config.local.php');
const SITE = L.BASE + 'exemplos/curso-livre/';
const API = L.BASE + 'api/v1';
const DOMINIO = 'alunos.escola-prova.invalid';
const ANO = 2026;
/* a matrícula de cada rodada (o Moodle de teste guarda as contas das rodadas anteriores): 9 dígitos */
const R = String(Date.now()).slice(-5);
const BASE_RA = +`9${R}000`;
let proximoRa = BASE_RA + 900; // as inscrições feitas direto pela interface (o site usa o contador, a partir de BASE_RA)
const novoRa = () => String(proximoRa++);
const tempos = {};

const sql = (q) => execFileSync('C:/xampp/mysql/bin/mysql.exe', ['-uroot', '--default-character-set=utf8mb4', '-N', '-B', BANCO, '-e', q], { encoding: 'utf8' }).trim().replace(/\r/g, '');
const um = (q) => +(sql(q).split('\n')[0] || 0);
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const espera = (ms) => new Promise((r) => setTimeout(r, ms));
const idAluno = (ra) => um(`SELECT id FROM col_usuarios WHERE rm = '${ra}'`);
function cli(...args) {
  const r = spawnSync('C:/xampp/php/php.exe', [path.join(L.RAIZ, 'tests', 'tabler', 'onda3', 'moodle_cli.php'), ...args], { cwd: L.RAIZ, encoding: 'utf8', env: process.env });
  try { return JSON.parse((r.stdout || '').trim().split('\n').pop()); } catch (e) { return { erro: (r.stderr || r.stdout || '').slice(0, 200) }; }
}
/** O id da conta do Moodle pelo idnumber de prova (0 = não existe). */
function contaMoodle(ra) {
  const r = cli('chamar', 'core_user_get_users_by_field', JSON.stringify({ field: 'idnumber', values: ['prova-' + ra] }));
  const u = r.ok ? (r.dados || []) : [];
  return u.length === 1 ? +u[0].id : (u.length > 1 ? -u.length : 0);
}
/** As disciplinas do Moodle em que a conta está (os ids). */
function cursosNoMoodle(idMoodle) {
  const r = cli('chamar', 'core_enrol_get_users_courses', JSON.stringify({ userid: idMoodle }));
  return r.ok ? (r.dados || []).map((c) => +c.id) : [];
}
/** As contas do Moodle com cada e-mail (a busca do plugin): e-mail => [ids]. */
function contasDosEmails(emails) {
  const out = {};
  for (let i = 0; i < emails.length; i += 50) {
    const r = cli('chamar', 'local_vcostaedu_find_users_by_email', JSON.stringify({ emails: emails.slice(i, i + 50) }));
    for (const x of (r.ok ? r.dados || [] : [])) { out[String(x.email).toLowerCase()] = x.userids || []; }
  }
  return out;
}
function sabotar(arq, de, para) {
  const orig = fs.readFileSync(arq, 'utf8');
  if (!orig.includes(de)) { throw new Error('a sabotagem não achou o trecho em ' + path.basename(arq)); }
  fs.writeFileSync(arq, orig.replace(de, para));
  return () => fs.writeFileSync(arq, orig);
}
function criarChave(permissoes, nome = 'Prova inscrição') {
  const chave = 'vce_' + crypto.randomBytes(20).toString('hex');
  sql(`INSERT INTO adms_api_chaves (nome, prefixo, hash, escopo, permissoes, situacao, usuario_id) VALUES ('${nome}', '${chave.slice(0, 12)}', '${crypto.createHash('sha256').update(chave).digest('hex')}', 'escrita', '${permissoes}', 1, 1)`);
  return chave;
}
async function api(metodo, rota, chave, corpo, repeticao) {
  const h = { Authorization: 'Bearer ' + chave, 'Content-Type': 'application/json' };
  if (repeticao) { h['Idempotency-Key'] = repeticao; }
  const t = Date.now();
  const r = await fetch(API + rota, { method: metodo, headers: h, body: corpo ? JSON.stringify(corpo) : undefined });
  const texto = await r.text();
  let j = null;
  try { j = JSON.parse(texto); } catch (e) { /* */ }
  return { status: r.status, j, texto, ms: Date.now() - t, repetida: r.headers.get('idempotent-replayed') === 'true' };
}
/** Espera o aluno ficar pronto no Moodle (o usuário e todas as disciplinas), só consultando (nenhum clique). */
async function ateOMoodle(chave, ra, total, limiteS) {
  const ini = Date.now();
  let m = {};
  while (Date.now() - ini < limiteS * 1000) {
    const v = await api('GET', '/alunos/' + ra, chave);
    m = (v.j && v.j.dados && v.j.dados.moodle) || {};
    if (m.enviado && m.disciplinas >= total) { return { ok: true, s: (Date.now() - ini) / 1000, m }; }
    await espera(2000);
  }
  return { ok: false, s: (Date.now() - ini) / 1000, m };
}
async function entrarPainel(navegador) {
  const ctx = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' });
  const p = await L.pagina(ctx);
  await p.goto(L.BASE + 'painel/login/acesso');
  await p.fill('input[name="usuario"]', 'administrador');
  await p.fill('input[name="senha"]', process.env.SENHA_ADM || SENHA);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
  return { ctx, p };
}
async function salvarModo(p, modo) {
  await p.goto(L.BASE + 'painel/moodle/conexao');
  await p.check(`input[name="email_modo"][value="${modo}"]`);
  await Promise.all([p.waitForNavigation({ timeout: 120000 }), p.click('#salvarMoodle')]);
}
/** A configuração do site de exemplo (fora do git), apontando para esta instalação. */
function configurarSite(chave, turmaId, extra = {}) {
  const c = { api: API, chave, portal: L.BASE + 'portal/login/acesso', instituicao: 'Instituto Ventoalto', destino: { turma_id: turmaId },
    matricula_inicio: BASE_RA, aviso: '', limite_por_endereco: 300, janela_minutos: 10, proxies_confiaveis: [], botao_criar_senha: false, ...extra };
  fs.writeFileSync(SITE_CFG, '<?php return json_decode(' + JSON.stringify(JSON.stringify(c)) + ', true);\n');
}
/** Uma inscrição pelo site, como um navegador sem JavaScript faria (o GET da página e o POST do formulário). */
async function inscreverPeloSite(nome, email, celular, cab = {}) {
  const g = await fetch(SITE, { headers: cab });
  const cookie = (g.headers.get('set-cookie') || '').split(';')[0];
  const html = await g.text();
  const token = (html.match(/name="token" value="([^"]+)"/) || [])[1] || '';
  const envio = (html.match(/name="envio" value="([^"]+)"/) || [])[1] || '';
  const corpo = new URLSearchParams({ token, envio, nome, email, celular }).toString();
  const enviar = () => fetch(SITE + 'inscrever.php', { method: 'POST', redirect: 'manual', headers: { ...cab, Cookie: cookie, 'Content-Type': 'application/x-www-form-urlencoded' }, body: corpo });
  return { cookie, enviar };
}
function limparSite() {
  try { fs.rmSync(path.join(SITE_DIR, 'dados'), { recursive: true, force: true }); } catch (e) { /* */ }
}

(async () => {
  const { navegador } = await L.abrir();
  const shas = { [MOODLE]: sha(MOODLE), [SERVIDOR]: sha(SERVIDOR), [LOGIN]: sha(LOGIN) };
  const cfgAntes = fs.existsSync(SITE_CFG) ? fs.readFileSync(SITE_CFG) : null;
  try {
    console.log('\n== Onda 8, a etapa nova — do site da instituição ao Moodle numa chamada só');
    if (!MURL || !MCHAVE) { throw new Error('esta prova roda contra o Moodle de teste (VCOSTAEDU_MOODLE_URL e VCOSTAEDU_MOODLE_CHAVE)'); }

    /* ---------------------------------------------------------------- A. o ensino superior */
    {
      const k = criarChave('alunos:gravar matriculas:gravar enturmacao:gravar cadastros:ler');
      const kSem = criarChave('alunos:gravar matriculas:gravar');
      /* o curso da faculdade fictícia que tem ofertas abertas no ano */
      const cursos = await api('GET', '/cursos', k);
      const comAbertas = um(`SELECT curso_id FROM rm_professores_materias WHERE ano = ${ANO} AND encerrada_em IS NULL GROUP BY curso_id ORDER BY COUNT(*) DESC LIMIT 1`);
      const curso = ((cursos.j && cursos.j.dados) || []).find((c) => c.id === comAbertas) || {};
      const of = await api('GET', `/ofertas?curso_id=${curso.id}&ano=${ANO}`, k);
      const ofertas = ((of.j && of.j.dados) || []).filter((o) => !o.encerrada).slice(0, 2).map((o) => o.id);
      const ra = novoRa();
      const corpo = { aluno: { ra, nome: 'Pessoa Superior Exemplo', sexo: 'feminino', email: `sup.${R}@exemplo.invalid` }, destino: { curso_id: curso.id, ano: ANO, ofertas } };
      const r1 = await api('POST', '/inscricoes', k, corpo);
      const r2 = await api('POST', '/inscricoes', k, corpo);
      const raSem = novoRa();
      const semEnt = await api('POST', '/inscricoes', kSem, { ...corpo, aluno: { ...corpo.aluno, ra: raSem } });
      const ent = r1.j && r1.j.dados && r1.j.dados.enturmacao;
      const enturmadas = ent ? (ent.ofertas || []).filter((o) => o.enturmado && ofertas.includes(o.oferta_id)).length : 0;
      L.ok(ofertas.length === 2 && r1.status === 201 && r1.j.feito.aluno === 'criado' && r1.j.feito.matricula === 'criada' && r1.j.feito.enturmacao === 'gravada' && enturmadas === 2
        && r2.status === 200 && r2.j.feito.aluno === 'ja_existia' && r2.j.feito.enturmacao === 'ja_estava' && um(`SELECT COUNT(*) FROM col_usuarios WHERE rm = '${ra}'`) === 1
        && semEnt.status === 403 && /Enturmação/.test(semEnt.j.erro.mensagem) && !idAluno(raSem),
        `no ensino superior: a inscrição com o curso, o ano e as ofertas (${r1.status}, ${enturmadas} ofertas); de novo, nada novo (${r2.status}, "${r2.j && r2.j.feito.aluno}"); a chave sem Enturmação, ${semEnt.status}`);
    }

    /* ---------------------------------------------------------------- B. a escola */
    const r = spawnSync(process.execPath, [path.join(__dirname, '..', 'onda6', 'medir_demo.js'), 'escola', 'onda8-etapa3b'], { encoding: 'utf8', maxBuffer: 1 << 24 });
    L.ok(r.status === 0, 'a demonstração do colégio montada pela tela');
    const turmaCL = um("SELECT t.id FROM esc_turmas t JOIN rm_cursos c ON c.id = t.curso_id WHERE c.nome LIKE 'Curso livre:%' AND t.nome = 'Turma aberta'");
    const compsCL = um(`SELECT COUNT(*) FROM esc_turma_ofertas WHERE turma_id = ${turmaCL}`);
    const capCL = um(`SELECT IFNULL(capacidade, 0) FROM esc_turmas WHERE id = ${turmaCL}`);
    L.ok(turmaCL > 0 && compsCL === 4 && capCL >= 300 && um(`SELECT COUNT(*) FROM esc_turma_alunos WHERE turma_id = ${turmaCL}`) === 0,
      `1. a demonstração tem o curso livre: a turma aberta, ${compsCL} módulos como componentes, ${capCL} vagas livres`);

    /* 2. o Moodle de teste */
    let s = await entrarPainel(navegador);
    const p = s.p;
    sql("UPDATE adms_parametros SET valor = 'prova-' WHERE chave = 'moodle.prefixo'");
    await p.goto(L.BASE + 'painel/moodle/conexao');
    await p.fill('#url', MURL);
    await p.fill('#chave', MCHAVE);
    await p.fill('#emailDominio', DOMINIO);
    await Promise.all([p.waitForNavigation(), p.click('#salvarMoodle')]);
    await p.goto(L.BASE + 'painel/moodle/conexao');
    await Promise.all([p.waitForNavigation(), p.click('#testarMoodle')]);
    await p.goto(L.BASE + 'painel/moodle/conexao');
    const temCat = await p.$$eval('#categoria_id option', (os) => os.some((o) => o.textContent.trim() === 'VCOSTAEDU provas'));
    if (temCat) { await p.selectOption('#categoria_id', { label: 'VCOSTAEDU provas' }); } else { await p.fill('#categoria_nova', 'VCOSTAEDU provas'); }
    await Promise.all([p.waitForNavigation({ timeout: 120000 }), p.click('#salvarMoodle')]);
    L.ok(um("SELECT COUNT(*) FROM adms_moodle_conexao WHERE testada_em IS NOT NULL AND categoria_id > 0 AND url <> ''") === 1 && sql("SELECT valor FROM adms_parametros WHERE chave = 'moodle.email_modo'") === 'escola',
      '2. o Moodle de teste conectado pela tela: o e-mail da escola (com o domínio), a categoria de prova, o envio automático');
    const k = criarChave('alunos:gravar matriculas:gravar enturmacao:gravar cadastros:ler');
    /* as 150 inscrições fazem umas 300 chamadas em poucos minutos (a inscrição e o primeiro acesso de cada uma), mais as
       consultas da prova: o limite por minuto da chave (o parâmetro da instalação, 120 de início) sobe, só aqui */
    sql("UPDATE adms_parametros SET valor = '2000' WHERE chave = 'api.limite_por_minuto'");
    limparSite();
    configurarSite(k, turmaCL);

    /* 3. a página de venda */
    const larguras = {};
    for (const w of [360, 390, 768, 1366]) {
      const ctx = await navegador.newContext({ viewport: { width: w, height: 900 }, locale: 'pt-BR' });
      const pg = await ctx.newPage();
      await pg.goto(SITE);
      larguras[w] = await pg.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth);
      if (w === 390 || w === 1366) { await L.print(pg, ETAPA, `pagina-de-venda-${w}`); }
      await ctx.close();
    }
    const ctxV = await navegador.newContext({ viewport: { width: 390, height: 900 }, locale: 'pt-BR' });
    const pv = await ctxV.newPage();
    await pv.goto(SITE);
    const venda = await pv.evaluate(() => ({
      topo: !!document.querySelector('#comecarTopo'), fim: !!document.querySelector('#comecarFim'), modulos: document.querySelectorAll('#modulos ~ ol li, ol.modulos li').length,
      rodape: (document.querySelector('#rodapeDemonstracao') || {}).textContent || '', texto: document.body.innerText, aviso: !!document.querySelector('#avisoLocal'),
    }));
    await ctxV.close();
    const semMarca = !/VCOSTA|vcostaedu/i.test(venda.texto + fs.readFileSync(path.join(SITE_DIR, 'index.php'), 'utf8') + fs.readFileSync(path.join(SITE_DIR, 'lib.php'), 'utf8').replace(/CURSO_LIVRE_EXEMPLO/, ''));
    L.ok(Object.values(larguras).every(Boolean) && venda.topo && venda.fim && venda.modulos === 4 && /demonstração/i.test(venda.rodape) && /fictíci/i.test(venda.rodape) && !venda.aviso && /10 horas/.test(venda.texto) && semMarca,
      `3. a página de venda: sem rolagem para o lado em ${Object.entries(larguras).map(([w, v]) => w + (v ? ' ok' : ' ROLA')).join(', ')}; o botão no alto e no fim, os 4 módulos, as 10 horas, o rodapé de demonstração, nenhum nome de produto, o aviso vazio não aparece`);
    /* o aviso opcional, da configuração */
    configurarSite(k, turmaCL, { aviso: 'Demonstração: os dados são apagados toda noite.' });
    const comAviso = await (await fetch(SITE)).text();
    configurarSite(k, turmaCL);
    L.ok(/id="avisoLocal"[^>]*>[^<]*Demonstração: os dados são apagados toda noite\./.test(comAviso), 'o aviso opcional da configuração aparece perto do formulário');

    /* sem JavaScript: o envio chega à confirmação */
    const ctxN = await navegador.newContext({ viewport: { width: 390, height: 900 }, locale: 'pt-BR', javaScriptEnabled: false });
    const pn = await ctxN.newPage();
    await pn.goto(SITE);
    await pn.fill('#nome', 'Pessoa Sem Script Exemplo');
    await pn.fill('#email', `sem.script.${R}@exemplo.invalid`);
    await pn.fill('#celular', '61 99999-0001');
    await Promise.all([pn.waitForNavigation({ timeout: 60000 }), pn.click('#comecarFim')]);
    const semJs = { url: pn.url(), vaga: !!(await pn.$('#passoVaga')), ra: ((await pn.textContent('#numeroMatricula').catch(() => '')) || '').trim() };
    await ctxN.close();
    L.ok(/confirmacao\.php/.test(semJs.url) && semJs.vaga && idAluno(semJs.ra) > 0, `o envio sem JavaScript chega à confirmação ("Sua vaga está garantida", a matrícula ${semJs.ra})`);

    /* 4. uma inscrição pelo site, no navegador */
    const ctxS = await navegador.newContext({ viewport: { width: 390, height: 900 }, locale: 'pt-BR' });
    const ps = await ctxS.newPage();
    await ps.goto(SITE);
    /* o botão do alto leva ao formulário; o do fim envia */
    await ps.click('#comecarTopo');
    const topoLeva = /#inscricao$/.test(ps.url());
    const emailS = `site.${R}@exemplo.invalid`;
    await ps.fill('#nome', 'Pessoa Do Site Exemplo');
    await ps.fill('#email', emailS);
    await ps.fill('#celular', '61 99999-0002');
    const t0 = Date.now();
    await Promise.all([ps.waitForNavigation({ timeout: 60000 }), ps.click('#comecarFim')]);
    await ps.waitForSelector('#passosInscricao[data-pronto="1"]', { timeout: 180000 }).catch(() => {});
    tempos.site_ate_tudo_pronto_s = (Date.now() - t0) / 1000;
    const raS = ((await ps.textContent('#numeroMatricula').catch(() => '')) || '').trim();
    const htmlS = await ps.content();
    const tempoTela = ((await ps.textContent('#tempoInscricao').catch(() => '')) || '').trim();
    await L.print(ps, ETAPA, 'confirmacao-390');
    const aS = idAluno(raS);
    const mS = contaMoodle(raS);
    const cursosCL = sql(`SELECT o.idMoodle FROM esc_turma_ofertas t JOIN rm_professores_materias o ON o.id = t.oferta_id WHERE t.turma_id = ${turmaCL}`).split('\n').filter(Boolean).map(Number);
    const noMoodle = mS > 0 ? cursosNoMoodle(mS) : [];
    const limpa = !htmlS.includes(k) && !htmlS.includes(MURL) && !/idMoodle|moodle_id|vce_/.test(htmlS);
    const passosTexto = /Sua vaga está garantida/.test(htmlS) && /Você está na turma/.test(htmlS) && /Seu ambiente de estudo está pronto/.test(htmlS);
    L.ok(topoLeva && aS > 0 && um(`SELECT COUNT(*) FROM rm_matriculas_curso WHERE usuario_id = ${aS} AND situacao_id = 1`) === 1 && um(`SELECT COUNT(*) FROM esc_turma_alunos WHERE turma_id = ${turmaCL} AND usuario_id = ${aS} AND saida IS NULL`) === 1
      && mS > 0 && cursosCL.length === 4 && cursosCL.every((c) => c > 0 && noMoodle.includes(c)) && passosTexto && /Tudo pronto em/.test(tempoTela) && limpa,
      `4. uma inscrição pelo site (o botão do alto leva ao formulário): a pessoa ${raS} existe, matriculada, na turma, com usuário no Moodle e inscrita nos 4 módulos; "${tempoTela}" (${tempos.site_ate_tudo_pronto_s.toFixed(1)} s do clique até a tela dizer); a confirmação sem chave, endereço ou identificador do Moodle`);
    tempos.site_tela = tempoTela;

    /* a confirmação com o botão desligado (o padrão): nada do link */
    L.ok(!(await ps.$('#criarSenhaAgora')) && (!!(await ps.$('#emailEnviado')) || !!(await ps.$('#emailNaoEnviado'))) && !/definir-senha/.test(htmlS),
      'com o botão desligado (o padrão), a confirmação não mostra o link: só diz do e-mail de boas-vindas');
    await ctxS.close();

    /* 5. o clique duplo no site e a mesma chamada duas vezes ao mesmo tempo */
    const emailD = `duplo.${R}@exemplo.invalid`;
    const f = await inscreverPeloSite('Pessoa Clique Duplo Exemplo', emailD, '61999990003');
    const [d1, d2] = await Promise.all([f.enviar(), f.enviar()]);
    const pessoasD = um(`SELECT COUNT(*) FROM col_usuarios WHERE email = '${emailD}'`);
    const raDuplo = sql(`SELECT GROUP_CONCAT(rm) FROM col_usuarios WHERE email = '${emailD}'`);
    const idemCorpo = { aluno: { ra: novoRa(), nome: 'Pessoa Repetida Exemplo', email: `repetida.${R}@exemplo.invalid` }, destino: { turma_id: turmaCL } };
    const chaveRep = 'prova-' + crypto.randomBytes(8).toString('hex');
    const [i1, i2] = await Promise.all([api('POST', '/inscricoes', k, idemCorpo, chaveRep), api('POST', '/inscricoes', k, idemCorpo, chaveRep)]);
    const mesma = i1.status === i2.status && JSON.stringify(i1.j) === JSON.stringify(i2.j) && (i1.repetida !== i2.repetida);
    const semChave = await api('POST', '/inscricoes', k, idemCorpo);
    L.ok(d1.status === 303 && d2.status === 303 && pessoasD === 1 && mesma && um(`SELECT COUNT(*) FROM col_usuarios WHERE rm = '${idemCorpo.aluno.ra}'`) === 1
      && semChave.status === 200 && semChave.j.feito.aluno === 'ja_existia' && semChave.j.feito.turma === 'ja_estava',
      `5. o clique duplo no site: uma pessoa só (${pessoasD}, matrícula ${raDuplo}); a mesma chamada duas vezes ao mesmo tempo: a mesma resposta (${i1.status}/${i2.status}, uma repetida); sem a chave, a mesma matrícula diz o que já existia`);

    /* 6. nada pela metade */
    const contar = () => sql('SELECT CONCAT((SELECT COUNT(*) FROM col_usuarios), "/", (SELECT COUNT(*) FROM rm_matriculas_curso), "/", (SELECT COUNT(*) FROM esc_turma_alunos), "/", (SELECT COUNT(*) FROM rm_matriculas_ensino))');
    const turma6B = um("SELECT t.id FROM esc_turmas t JOIN rm_cursos_turma_series m ON m.id = t.modulo_id WHERE t.ano = 2026 AND m.codnome LIKE '6%' AND t.nome = 'B'");
    const cap6B = sql(`SELECT IFNULL(capacidade, 'NULL') FROM esc_turmas WHERE id = ${turma6B}`);
    sql(`UPDATE esc_turmas SET capacidade = (SELECT COUNT(*) FROM esc_turma_alunos WHERE turma_id = ${turma6B} AND saida IS NULL) WHERE id = ${turma6B}`);
    const antes6 = contar();
    const cheia = await api('POST', '/inscricoes', k, { aluno: { ra: novoRa(), nome: 'Pessoa Turma Cheia Exemplo' }, destino: { turma_id: turma6B } });
    sql(`UPDATE esc_turmas SET capacidade = ${cap6B} WHERE id = ${turma6B}`);
    const falta = await api('POST', '/inscricoes', k, { aluno: { ra: novoRa(), nome: 'Pessoa Sem Turma Exemplo' }, destino: { turma_id: 999999 } });
    const outra = await api('POST', '/inscricoes', k, { aluno: { ra: '265001', nome: 'Ana Exemplo Ribeiro' }, destino: { turma_id: turma6B } });
    const depois6 = contar();
    L.ok(cheia.status === 422 && /Nada foi gravado/.test(cheia.j.erro.mensagem) && /completa/.test(cheia.j.erro.mensagem)
      && falta.status === 404 && falta.j.erro.campo === 'destino.turma_id' && outra.status === 409 && /noutra turma/.test(outra.j.erro.mensagem) && antes6 === depois6,
      `6. nada pela metade (as contagens ${antes6} → ${depois6}): a turma completa (${cheia.status}: "${(cheia.j.erro || {}).mensagem}"), a turma que não existe (${falta.status}), a matrícula noutra turma (${outra.status})`);

    /* 7. uma turma de 10+ componentes */
    const turma1A = um("SELECT t.id FROM esc_turmas t JOIN rm_cursos_turma_series m ON m.id = t.modulo_id WHERE t.ano = 2026 AND m.codnome LIKE '1%' AND t.nome = 'A'");
    const comps1A = um(`SELECT COUNT(*) FROM esc_turma_ofertas WHERE turma_id = ${turma1A}`);
    const ra7 = novoRa();
    const g7 = await api('POST', '/inscricoes', k, { aluno: { ra: ra7, nome: 'Pessoa Muitos Componentes Exemplo', email: `muitos.${R}@exemplo.invalid` }, destino: { turma_id: turma1A } });
    tempos.dez_mais_componentes = { componentes: comps1A, chamada_s: g7.ms / 1000, na_resposta: g7.j && g7.j.moodle ? g7.j.moodle.disciplinas : null };
    const fim7 = await ateOMoodle(k, ra7, comps1A, 240);
    tempos.dez_mais_componentes.terminou_sozinho_s = fim7.s;
    L.ok(comps1A >= 10 && g7.status === 201 && g7.j.moodle.disciplinas_total === comps1A && fim7.ok,
      `7. uma turma de ${comps1A} componentes pela operação única: a chamada em ${(g7.ms / 1000).toFixed(1)} s (${tempos.dez_mais_componentes.na_resposta} de ${comps1A} já no Moodle na resposta); o resto terminou sozinho, sem clique, ${fim7.s.toFixed(0)} s depois (${fim7.m.disciplinas}/${comps1A})`);

    /* 8. sem e-mail, no modo "e-mail da escola" */
    const ra8 = novoRa();
    const g8 = await api('POST', '/inscricoes', k, { aluno: { ra: ra8, nome: 'Pessoa Sem Email Exemplo' }, destino: { turma_id: turmaCL } });
    const fim8 = await ateOMoodle(k, ra8, 4, 120);
    const contas8 = contasDosEmails([`${ra8}@${DOMINIO}`]);
    L.ok(g8.status === 201 && fim8.ok && (contas8[`${ra8}@${DOMINIO}`] || []).length === 1 && sql(`SELECT IFNULL(email, '') FROM col_usuarios WHERE rm = '${ra8}'`) === '',
      `8. a pessoa sem e-mail, no modo "e-mail da escola", vai ao Moodle (com ${ra8}@<o domínio da escola>; o cadastro continua sem e-mail)`);

    /* 9. o Moodle fora do ar na hora */
    const urlCerta = sql('SELECT url FROM adms_moodle_conexao WHERE id = 1');
    sql("UPDATE adms_moodle_conexao SET url = 'http://127.0.0.1:9/moodle-fora-do-ar' WHERE id = 1");
    const ra9 = novoRa();
    let g9;
    try {
      g9 = await api('POST', '/inscricoes', k, { aluno: { ra: ra9, nome: 'Pessoa Moodle Fora Exemplo', email: `fora.${R}@exemplo.invalid` }, destino: { turma_id: turmaCL } });
    } finally {
      sql(`UPDATE adms_moodle_conexao SET url = '${urlCerta.replace(/'/g, "''")}' WHERE id = 1`);
    }
    const guardado9 = idAluno(ra9) > 0 && um(`SELECT COUNT(*) FROM esc_turma_alunos WHERE turma_id = ${turmaCL} AND usuario_id = ${idAluno(ra9)}`) === 1;
    const fim9 = await ateOMoodle(k, ra9, 4, 240);
    tempos.moodle_fora_do_ar = { chamada_s: g9.ms / 1000, completou_depois_s: fim9.s };
    L.ok(g9.status === 201 && guardado9 && g9.j.moodle.enviado === false && fim9.ok,
      `9. o Moodle fora do ar na hora: a resposta normal (${g9.status}, em ${(g9.ms / 1000).toFixed(1)} s), os dados gravados; com ele de volta, o envio se completou sozinho ${fim9.s.toFixed(0)} s depois`);

    /* 10. o e-mail corrigido depois (no modo "só o e-mail do aluno") */
    /* (a correção pela interface: quem entrou sem o sexo manda o sexo junto, a regra de sempre do PATCH) */
    await salvarModo(p, 'aluno');
    const ra10 = novoRa();
    const g10 = await api('POST', '/inscricoes', k, { aluno: { ra: ra10, nome: 'Pessoa Email Depois Exemplo' }, destino: { turma_id: turmaCL } });
    await espera(3000);
    const id10 = idAluno(ra10);
    const falhou10 = sql(`SELECT CONCAT(situacao, '/', IFNULL(codigo, '')) FROM adms_moodle_envios WHERE chave = 'aluno:${id10}:' ORDER BY id DESC LIMIT 1`);
    const inscrFalhas = um(`SELECT COUNT(*) FROM adms_moodle_envios WHERE tipo = 'enturmar' AND aluno_id = ${id10} AND situacao = 'falhou' AND codigo = 'sem_email'`);
    const pa = await api('PATCH', '/alunos/' + ra10, k, { email: `depois.${R}@exemplo.invalid`, sexo: 'feminino' });
    const fim10 = await ateOMoodle(k, ra10, 4, 180);
    L.ok(g10.status === 201 && falhou10 === 'falhou/sem_email' && inscrFalhas === 4 && pa.status === 200 && fim10.ok,
      `10. sem e-mail no modo "só o e-mail do aluno": o usuário e as ${inscrFalhas} inscrições ficaram de fora (${falhou10}); o e-mail corrigido, o usuário e as inscrições foram sozinhos (${fim10.s.toFixed(0)} s)`);
    await salvarModo(p, 'escola');

    /* 11. o limite por endereço, e as 150 inscrições */
    limparSite();
    configurarSite(k, turmaCL, { limite_por_endereco: 2 });
    const lim = [];
    for (let i = 0; i < 3; i++) {
      const fx = await inscreverPeloSite(`Pessoa Limite ${i} Exemplo`, `limite.${i}.${R}@exemplo.invalid`, '61999990010', { 'X-Forwarded-For': `203.0.113.${10 + i}` });
      const rx = await fx.enviar();
      lim.push(String(rx.headers.get('location') || ''));
    }
    L.ok(/confirmacao/.test(lim[0]) && /confirmacao/.test(lim[1]) && /index\.php/.test(lim[2]) && um(`SELECT COUNT(*) FROM col_usuarios WHERE email = 'limite.2.${R}@exemplo.invalid'`) === 0,
      'o limite por endereço: com o teto em 2, a terceira inscrição do mesmo endereço é recusada, mesmo com o X-Forwarded-For forjado (sem proxy confiável, o cabeçalho é ignorado)');
    limparSite();
    configurarSite(k, turmaCL, { limite_por_endereco: 300, matricula_inicio: BASE_RA + 100 });
    const N = 150;
    const emails150 = Array.from({ length: N }, (_, i) => `lote.${i}.${R}@exemplo.invalid`);
    const t150 = Date.now();
    const status150 = [];
    for (let i = 0; i < N; i += 5) {
      const grupo = await Promise.all(emails150.slice(i, i + 5).map(async (e, j) => {
        const fx = await inscreverPeloSite(`Pessoa Lote ${i + j} Exemplo`, e, '61999990100');
        const rx = await fx.enviar();
        return String(rx.headers.get('location') || '');
      }));
      status150.push(...grupo);
    }
    tempos.cento_e_cinquenta = { inscricoes_s: (Date.now() - t150) / 1000 };
    const criadas = um(`SELECT COUNT(*) FROM col_usuarios WHERE email LIKE 'lote.%.${R}@exemplo.invalid'`);
    const distintas = um(`SELECT COUNT(DISTINCT email) FROM col_usuarios WHERE email LIKE 'lote.%.${R}@exemplo.invalid'`);
    const naTurma = um(`SELECT COUNT(*) FROM esc_turma_alunos a JOIN col_usuarios u ON u.id = a.usuario_id WHERE a.turma_id = ${turmaCL} AND u.email LIKE 'lote.%.${R}@exemplo.invalid'`);
    /* todas no Moodle: só consultando (cada consulta é uma requisição: a retomada anda nelas) */
    const ids150 = sql(`SELECT id FROM col_usuarios WHERE email LIKE 'lote.%.${R}@exemplo.invalid'`).split('\n').filter(Boolean).join(',') || '0';
    const ini = Date.now();
    let faltam = 1;
    while (Date.now() - ini < 600000) {
      faltam = um(`SELECT COUNT(*) FROM adms_moodle_envios WHERE aluno_id IN (${ids150}) AND tipo IN ('aluno', 'enturmar') AND situacao NOT IN ('enviado', 'aviso')`);
      if (faltam === 0) { break; }
      await api('GET', '/alunos/' + BASE_RA, k);
      await espera(1500);
    }
    tempos.cento_e_cinquenta.moodle_completo_s = (Date.now() - t150) / 1000;
    const enviados150 = um(`SELECT COUNT(DISTINCT aluno_id) FROM adms_moodle_envios WHERE aluno_id IN (${ids150}) AND tipo = 'aluno' AND situacao IN ('enviado', 'aviso')`);
    const inscr150 = um(`SELECT COUNT(*) FROM adms_moodle_envios WHERE aluno_id IN (${ids150}) AND tipo = 'enturmar' AND situacao IN ('enviado', 'aviso')`);
    const contas150 = contasDosEmails(emails150.map((e) => e.toLowerCase()));
    const umaCada = emails150.every((e) => (contas150[e.toLowerCase()] || []).length === 0) ? 'escola' : 'proprio';
    /* no modo "e-mail da escola", a conta vai com a matrícula no domínio: uma conta por pessoa, conferida pelo idnumber */
    const ras150 = sql(`SELECT rm FROM col_usuarios WHERE email LIKE 'lote.%.${R}@exemplo.invalid'`).split('\n').filter(Boolean);
    const contasRa = contasDosEmails(ras150.map((x) => `${x}@${DOMINIO}`));
    const duplicadas = ras150.filter((x) => (contasRa[`${x}@${DOMINIO}`] || []).length !== 1).length;
    L.ok(status150.length === N && status150.every((x) => /confirmacao/.test(x)) && criadas === N && distintas === N && naTurma === N && faltam === 0 && enviados150 === N && inscr150 === 4 * N && duplicadas === 0 && umaCada === 'escola',
      `11. ${N} inscrições rápidas do mesmo endereço, teto em 300: ${criadas} criadas, ${naTurma} na turma, ${enviados150} usuários e ${inscr150} inscrições no Moodle, ${duplicadas} duplicada(s) (as ${N} em ${tempos.cento_e_cinquenta.inscricoes_s.toFixed(0)} s; o Moodle completo em ${tempos.cento_e_cinquenta.moodle_completo_s.toFixed(0)} s)`);

    /* 12. "Entrar no curso", com o botão ligado */
    limparSite();
    configurarSite(k, turmaCL, { botao_criar_senha: true, matricula_inicio: BASE_RA + 400 });
    const ctxE = await navegador.newContext({ viewport: { width: 390, height: 900 }, locale: 'pt-BR' });
    const pe = await ctxE.newPage();
    await pe.goto(SITE);
    await pe.fill('#nome', 'Pessoa Entrar Curso Exemplo');
    await pe.fill('#email', `entrar.${R}@exemplo.invalid`);
    await pe.fill('#celular', '61 99999-0004');
    await Promise.all([pe.waitForNavigation({ timeout: 60000 }), pe.click('#comecarFim')]);
    await pe.waitForSelector('#passosInscricao[data-pronto="1"]', { timeout: 180000 }).catch(() => {});
    const raE = ((await pe.textContent('#numeroMatricula').catch(() => '')) || '').trim();
    const link = await pe.getAttribute('#criarSenhaAgora', 'href').catch(() => '');
    await L.print(pe, ETAPA, 'confirmacao-com-botao-390');
    const senhaE = 'Vc' + crypto.randomBytes(6).toString('hex') + '#7q';
    await Promise.all([pe.waitForNavigation({ timeout: 30000 }), pe.click('#criarSenhaAgora')]);
    await pe.waitForSelector('input[name="senha"]', { timeout: 30000 });
    await pe.fill('input[name="senha"]', senhaE);
    await pe.fill('input[name="senha_repetido"]', senhaE);
    await Promise.all([pe.waitForNavigation({ timeout: 30000 }), pe.click('[name="AtualizaSenha"]')]);
    const criou = /login\/acesso/.test(pe.url()) && /Senha definida/.test(await pe.content());
    /* o mesmo link de novo (o do e-mail, depois do botão; ou o clique duplo) */
    const ctxR = await navegador.newContext({ viewport: { width: 390, height: 900 }, locale: 'pt-BR' });
    const pr = await ctxR.newPage();
    await pr.goto(link);
    await pr.waitForURL(/login\/acesso/, { timeout: 30000 }).catch(() => {});
    const jaCriada = ((await pr.textContent('#senhaJaCriada').catch(() => '')) || '').replace(/\s+/g, ' ');
    const naEntrada = /login\/acesso/.test(pr.url()) && !!(await pr.$('input[name="usuario"]'));
    await L.print(pr, ETAPA, 'link-usado-senha-ja-criada-390');
    await ctxR.close();
    L.ok(/definir-senha#t=/.test(link) && criou && /senha já foi criada/.test(jaCriada) && naEntrada && !/inválido/.test(jaCriada),
      `12. com o botão ligado: a senha criada pelo link; o mesmo link aberto de novo mostra "${jaCriada.trim().slice(0, 60)}…" e fica na entrada do portal`);
    /* a entrada: o portal e o Moodle, na disciplina */
    await pe.goto(SITE + 'confirmacao.php?m=' + encodeURIComponent(raE));
    const destinoEntrar = await pe.getAttribute('#entrarNoCurso', 'href').catch(() => '');
    await pe.goto(destinoEntrar);
    await pe.fill('input[name="usuario"]', raE);
    await pe.fill('input[name="senha"]', senhaE);
    await Promise.all([pe.waitForNavigation(), pe.click('[name="SendLogin"]')]);
    await pe.goto(L.BASE + 'portal/home/index');
    const ofE = await pe.getAttribute('.vc-abrir-moodle', 'data-oferta').catch(() => '');
    let dentro = false;
    let naDisciplina = false;
    if (ofE) {
      await Promise.all([pe.waitForNavigation({ timeout: 30000 }), pe.click(`.vc-abrir-moodle[data-oferta="${ofE}"]`)]);
      await pe.waitForLoadState('load').catch(() => {});
      const cursoE = um(`SELECT idMoodle FROM rm_professores_materias WHERE id = ${+ofE}`);
      naDisciplina = pe.url().startsWith(MURL) && new RegExp(`course/view\\.php\\?id=${cursoE}`).test(pe.url());
      dentro = await pe.evaluate(() => !document.querySelector('#login, form#login') && !!document.querySelector('[data-region="user-menu"], .usermenu, #user-menu-toggle, .userbutton'));
    }
    await ctxE.close();
    L.ok(/login\/acesso/.test(destinoEntrar) && naDisciplina && dentro,
      `"Entrar no curso": a entrada do portal com a matrícula e a senha criada, "Entrar no Moodle" e a disciplina aberta, com a sessão (${naDisciplina ? 'dentro da disciplina' : 'fora'})`);

    /* ---------------------------------------------------------------- as sabotagens */
    /* (a) a retomada desligada: o Moodle volta, mas o que ficou não termina sozinho */
    {
      const volta = sabotar(MOODLE, '            if (self::$gasto > 0) {\n                return; // esta requisição', '            if (true) {\n                return; // esta requisição');
      let fimA;
      try {
        sql("UPDATE adms_moodle_conexao SET url = 'http://127.0.0.1:9/moodle-fora-do-ar' WHERE id = 1");
        const raA = novoRa();
        try {
          await api('POST', '/inscricoes', k, { aluno: { ra: raA, nome: 'Pessoa Sabotagem A Exemplo', email: `sab.a.${R}@exemplo.invalid` }, destino: { turma_id: turmaCL } });
        } finally {
          sql(`UPDATE adms_moodle_conexao SET url = '${urlCerta.replace(/'/g, "''")}' WHERE id = 1`);
        }
        fimA = await ateOMoodle(k, raA, 4, 75);
      } finally {
        volta();
      }
      L.ok(!fimA.ok, 'sabotagem (a) (a retomada desligada): a prova reprova — com o Moodle de volta, o envio não terminou sozinho');
    }
    /* (b) as inscrições não voltam à fila: o e-mail corrigido leva só o usuário */
    {
      await salvarModo(p, 'aluno');
      const volta = sabotar(MOODLE, "            foreach (self::inscricoesDeVolta((int) $item['aluno_id']) as $volta) {", "            foreach ([] as $volta) {");
      let fimB;
      try {
        const raB = novoRa();
        await api('POST', '/inscricoes', k, { aluno: { ra: raB, nome: 'Pessoa Sabotagem B Exemplo' }, destino: { turma_id: turmaCL } });
        await espera(2000);
        await api('PATCH', '/alunos/' + raB, k, { email: `sab.b.${R}@exemplo.invalid`, sexo: 'feminino' });
        fimB = await ateOMoodle(k, raB, 4, 60);
      } finally {
        volta();
      }
      await salvarModo(p, 'escola');
      L.ok(!fimB.ok && fimB.m.enviado === true, `sabotagem (b) (as inscrições não voltam à fila): a prova reprova — o usuário foi, as inscrições não (${fimB.m.disciplinas}/4)`);
    }
    /* (c) sem a trava da chave de repetição */
    {
      const volta = sabotar(SERVIDOR, "            self::travar('idem', (int) $chave['id'] . ':' . $this->idem);", '');
      let c1;
      let c2;
      try {
        const corpoC = { aluno: { ra: novoRa(), nome: 'Pessoa Sabotagem C Exemplo', email: `sab.c.${R}@exemplo.invalid` }, destino: { turma_id: turmaCL } };
        const kc = 'prova-c-' + crypto.randomBytes(8).toString('hex');
        [c1, c2] = await Promise.all([api('POST', '/inscricoes', k, corpoC, kc), api('POST', '/inscricoes', k, corpoC, kc)]);
      } finally {
        volta();
      }
      const igual = c1.status === c2.status && JSON.stringify(c1.j) === JSON.stringify(c2.j) && c1.repetida !== c2.repetida;
      L.ok(!igual, `sabotagem (c) (sem a trava da chave de repetição): a prova reprova — as duas chamadas juntas não devolveram a mesma resposta (${c1.status}/${c2.status}, nenhuma repetida)`);
    }
    /* (d) o link usado sem a mensagem amiga */
    {
      const volta = sabotar(LOGIN, "if (empty($this->dados['info']) && $token !== '' && (new \\App\\adms\\Models\\helper\\AdmsTokenAcesso())->jaCriouASenha($token)) {", 'if (false) {');
      let msg = '';
      try {
        const ctxD = await navegador.newContext({ viewport: { width: 390, height: 900 }, locale: 'pt-BR' });
        const pd = await ctxD.newPage();
        await pd.goto(link);
        await pd.waitForURL(/login\/acesso/, { timeout: 30000 }).catch(() => {});
        msg = ((await pd.textContent('#senhaJaCriada').catch(() => '')) || '') + '|' + (await pd.content());
        await ctxD.close();
      } finally {
        volta();
      }
      L.ok(!/senha já foi criada/.test(msg) && /inválido, já foi usado ou expirou/.test(msg), 'sabotagem (d) (o link usado sem a mensagem): a prova reprova — volta o "link inválido"');
    }
    L.ok(Object.entries(shas).every(([f, h]) => sha(f) === h), 'os arquivos sabotados voltaram iguais (sha256)');
    fs.writeFileSync(path.join(SAIDA, 'onda8-etapa3b-tempos.json'), JSON.stringify(tempos, null, 2));
    console.log('  tempos: ' + JSON.stringify(tempos));
    await s.ctx.close();
    cli('limpar');
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    for (const [f, h] of Object.entries(shas)) { if (sha(f) !== h) { console.log('  [AVISO] o arquivo sabotado não voltou igual: ' + path.basename(f)); } }
    if (cfgAntes) { fs.writeFileSync(SITE_CFG, cfgAntes); } else { try { fs.unlinkSync(SITE_CFG); } catch (e) { /* */ } }
    limparSite();
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
