/*
 * PROVA DA ONDA 8, CORREÇÃO, PARTE 1.2 — o caminho do professor até as etapas do componente (D-72). APAGA o banco de
 * prova.
 *   1. a demonstração do colégio, montada pela tela numa instalação nova;
 *   2. a professora de demonstração, só clicando (sem digitar endereço): a Página Inicial → o menu → "Minhas
 *      disciplinas" → a ação "Etapas" da linha de um componente → a tela das etapas, com a barra do componente e a aba
 *      "Etapas" marcada;
 *   3. pela tela do componente: "Abrir a disciplina" → a aba "Etapas" da barra → a mesma tela;
 *   4. ela lança a recuperação de um aluno na etapa aberta e a nota fica gravada;
 *   5. no ensino superior a barra não ganha a aba (a ação é da escola) — conferido no código da barra, sem montar a
 *      outra demonstração: a aba só entra com a instalação do tipo escola;
 *   sabotagem: a barra sem a aba "Etapas" — a prova acusa; o arquivo volta igual (sha256).
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { spawnSync, execFileSync } = require('child_process');
const L = require('../lib');

const ETAPA = 'onda8/etapa6';
const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste$/.test(BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
const UI = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'helper', 'AdmsUi.php');

/* o mysql do Windows termina as linhas com CR: cada linha sai aparada */
const sql = (q) => execFileSync('C:/xampp/mysql/bin/mysql.exe', ['-uroot', '--default-character-set=utf8mb4', '-N', '-B', BANCO, '-e', q], { encoding: 'utf8' }).split('\n').map((l) => l.trim()).filter(Boolean).join('\n');
const um = (q) => +(sql(q).split('\n')[0] || 0);
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const montar = (rotulo) => spawnSync(process.execPath, [path.join(__dirname, '..', 'onda6', 'medir_demo.js'), 'escola', rotulo], { encoding: 'utf8', maxBuffer: 1 << 24 });
async function entrar(navegador, usuario, senha) {
  const ctx = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' });
  const p = await L.pagina(ctx);
  await p.goto(L.BASE + 'painel/login/acesso');
  await p.fill('input[name="usuario"]', usuario);
  await p.fill('input[name="senha"]', senha);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
  return { ctx, p };
}
/** Da Página Inicial até a tela das etapas, só clicando. @return {url, aba, atual} */
async function caminho(p) {
  await p.goto(L.BASE + 'painel/home/index');
  const menu = await p.$('a[href*="minhas-disciplinas/listar"]');
  if (!menu) { return { url: '', semMenu: true }; }
  await Promise.all([p.waitForNavigation(), p.evaluate((a) => a.click(), menu)]);
  const linha = await p.$('#tabelaDisciplinas tbody tr:has(a:text-is("Matemática")) .vc-etapas-componente');
  if (!linha) { return { url: p.url(), semAcao: true }; }
  await Promise.all([p.waitForNavigation(), linha.click()]);
  const url = p.url();
  const atual = await p.$eval('.vc-abas-lista .nav-link.active', (e) => e.textContent.trim()).catch(() => '');
  /* pela tela do componente: abrir a disciplina e clicar na aba */
  await p.goBack();
  await Promise.all([p.waitForNavigation(), p.click('#tabelaDisciplinas tbody tr:has(a:text-is("Matemática")) a[href*="disciplinas/visualizar/"] >> nth=0')]);
  const aba = await p.$('.vc-abas-lista a.nav-link[href*="escola-notas/etapas/"]');
  let url2 = '';
  if (aba) {
    await Promise.all([p.waitForNavigation(), aba.click()]);
    url2 = p.url();
  }
  return { url, atual, url2, temAba: !!aba };
}

/**
 * Arranjo da prova: na demonstração, a recuperação só acontece no 1º e no 2º bimestre, já fechados. Um aluno passa a
 * ficar em recuperação na etapa aberta (o 3º bimestre): a nota dele nas avaliações desse bimestre vira 3,0, pela
 * gravação da própria tela da atividade (atividades/edit-notas, com a sessão da professora). @return o RA
 */
async function emRecuperacao(p, oferta) {
  const linhas = sql(`SELECT n.id, n.rm FROM rm_disciplina_atividade_notas n JOIN rm_disciplina_atividade a ON a.id = n.atividade_id
    WHERE n.rm_prof_mat_id = ${oferta} AND a.nomeAtividade LIKE '%3_ bimestre%' ORDER BY n.rm, n.id`).split('\n').filter(Boolean).map((l) => l.split('\t'));
  if (!linhas.length) { return ''; }
  const rm = linhas[0][1];
  for (const [id, r] of linhas) {
    if (r !== rm) { continue; }
    await p.evaluate(async ([u, i]) => {
      await fetch(u, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams({ id: i, nota: '3,0', action: 'edit' }).toString() });
    }, [L.BASE + 'painel/atividades/edit-notas', id]);
  }
  return rm;
}

(async () => {
  const { navegador } = await L.abrir();
  const shaUi = sha(UI);
  try {
    const r = montar('onda8-etapa6');
    L.ok(r.status === 0, 'a demonstração do colégio montada pela tela, numa instalação nova');
    const cred = JSON.parse(sql("SELECT valor FROM adms_instalacao_estado WHERE chave = 'demonstracao.credenciais'"));
    const senhaProf = (cred.painel || {})['prof.laura.demo'] || '';

    /* 2 e 3. o caminho, só clicando */
    const prof = await entrar(navegador, 'prof.laura.demo', senhaProf);
    const c = await caminho(prof.p);
    L.ok(/escola-notas\/etapas\/\d+$/.test(c.url || '') && /Etapas/.test(c.atual || ''),
      `a professora chega às etapas pela ação "Etapas" de "Minhas disciplinas", partindo da Página Inicial e do menu (${(c.url || '').replace(L.BASE, '')}; aba marcada: "${c.atual || '—'}")${c.semMenu ? ' — o menu não leva à lista' : ''}${c.semAcao ? ' — a linha não tem a ação' : ''}`);
    L.ok(c.temAba && c.url2 === c.url, `pela tela do componente: a aba "Etapas" da barra leva à mesma tela (${(c.url2 || '—').replace(L.BASE, '')})`);

    /* 4. a recuperação, lançada pela professora na etapa aberta que já tem nota */
    const oferta = +(c.url.match(/(\d+)$/) || [0, 0])[1];
    const rmArranjo = await emRecuperacao(prof.p, oferta);
    await prof.p.reload();
    /* as abas das etapas abertas, uma a uma, até a que tem aluno em recuperação (clicando, como a professora) */
    let etapa = 0;
    let rm = '';
    for (const aba of await prof.p.$$eval('#abasEtapas a[data-fechada="0"]', (l) => l.map((a) => a.dataset.etapa))) {
      const el = await prof.p.$(`#abasEtapas a[data-etapa="${aba}"]`);
      if (!(await el.evaluate((a) => a.classList.contains('active')))) { await Promise.all([prof.p.waitForNavigation(), el.click()]); }
      rm = await prof.p.$eval('#formEtapa tr[data-em-recuperacao="1"]', (t) => t.dataset.rm).catch(() => '');
      if (rm) { etapa = +aba; break; }
    }
    let gravou = 0;
    if (rm) {
      await prof.p.fill(`input[name="recuperacao[${rm}]"]`, '7,5');
      await Promise.all([prof.p.waitForNavigation(), prof.p.click('#gravarEtapa')]); // D-100: um botão só (Gravar)
      gravou = um(`SELECT COUNT(*) FROM esc_recuperacoes WHERE oferta_id = ${oferta} AND etapa_id = ${etapa} AND rm = '${rm}' AND nota = 7.5`);
    }
    await L.print(prof.p, ETAPA, 'etapas-pela-professora');
    await prof.ctx.close();
    L.ok(rm !== '' && gravou === 1, `a professora lança a recuperação na etapa aberta (aluno ${rm || '—'}: 7,5; gravada: ${gravou})`);

    /* 5. só na escola */
    L.ok(/AdmsTipoInstituicao::ehEscola\(\)\) \{\s+\$itens = array_slice\(\$itens, 0, 5, true\) \+ \['etapas'/.test(fs.readFileSync(UI, 'utf8')),
      'a aba "Etapas" entra na barra só na instalação do tipo escola');

    /* sabotagem: a barra sem a aba */
    const orig = fs.readFileSync(UI, 'utf8');
    const trecho = "if (\\App\\adms\\Models\\helper\\AdmsTipoInstituicao::ehEscola()) {";
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let s = null;
    try {
      fs.writeFileSync(UI, orig.replace(trecho, 'if (false) {'));
      const pr = await entrar(navegador, 'prof.laura.demo', senhaProf);
      s = await caminho(pr.p);
      await pr.ctx.close();
    } finally {
      fs.writeFileSync(UI, orig);
    }
    L.ok(s && !s.temAba, 'sabotagem (a barra sem a aba "Etapas"): a prova reprova — a tela do componente não leva às etapas');
    L.ok(sha(UI) === shaUi, 'o arquivo sabotado voltou igual (sha256)');
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(UI) !== shaUi) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
