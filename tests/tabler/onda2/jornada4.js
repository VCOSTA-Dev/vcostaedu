/*
 * ONDA 2, ETAPA 4 — conhecer primeiro, configurar por fases, com "Faça comigo". Uma JORNADA do zero (apaga e
 * reinstala o banco de prova: roda depois da bateria):
 *   A. instala pelo instalador (a última tela traz a escolha do começo);
 *   B. CONHECER: monta a demonstração pela tela (a senha confirmada; sem editar arquivo) — a faixa em toda tela, o
 *      cartão "Conheça o sistema", a régua do acadêmico contra a montagem das provas, a tarja nos documentos, o
 *      atestado emitido no portal e conferido na verificação pública;
 *   C. a RECUSA com dado real (a instituição configurada pela tela) e com a frase errada;
 *   D. COMEÇAR COM OS DADOS DA INSTITUIÇÃO: as contagens antes e depois, sem reinstalar, e os Primeiros passos;
 *   E. as 8 FASES pelo "Faça comigo", cada roteiro percorrido pelo Playwright a partir do MESMO arquivo do motor;
 *      cada fase se marca sozinha e anuncia a próxima; pular e retomar; o que a fase gravou é o que a tela grava;
 *   F. "Faça comigo" e "Ver o vídeo" no guia (o vídeo só quando existe);
 *   G. sabotagem: um roteiro com um seletor que não existe é reprovado.
 *
 *   node tests/tabler/onda2/jornada4.js
 */
const fs = require('fs');
const path = require('path');
const { execFileSync, spawnSync } = require('child_process');
const L = require('../lib');

const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste2?$/.test(BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
const MYSQL = process.env.VCOSTAEDU_MYSQL || 'C:/xampp/mysql/bin/mysql.exe';
const PHP = process.env.VCOSTAEDU_PHP || 'php';
const sql = (q) => execFileSync(MYSQL, ['-u', 'root', '-N', '--default-character-set=utf8mb4', BANCO, '-e', q]).toString().replace(/\r/g, '').trim();
const um = (q) => +sql(q);
const ETAPA = 'onda2/etapa4';
const ADMIN = 'administrador';
const ADMIN_SENHA = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const ROTEIROS = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Roteiros');
const ANO = String(new Date().getFullYear());

async function entrar(navegador, usuario, senha, app = 'painel') {
  const ctx = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' });
  const p = await L.pagina(ctx);
  await p.goto(L.BASE + app + '/login/acesso');
  await p.fill('input[name="usuario"]', usuario);
  await p.fill('input[name="senha"]', senha);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
  return { ctx, p };
}
const contagens = () => ({
  cursos: um('SELECT COUNT(*) FROM rm_cursos'), alunos: um('SELECT COUNT(*) FROM col_usuarios WHERE col_niveis_acesso_id = 1'),
  notas: um('SELECT COUNT(*) FROM rm_disciplina_atividade_notas WHERE nota IS NOT NULL'), equipe: um('SELECT COUNT(*) FROM adms_usuarios'),
  emissoes: um('SELECT COUNT(*) FROM rm_historico_emissoes'),
});

/* ------------------------------------------------------------------ o "Faça comigo" pelo Playwright */
async function executar(p, faz) {
  const urlInicio = p.url();
  for (const a of faz || []) {
    const [tipo, sel] = a;
    const valor = typeof a[2] === 'string' ? a[2].replace(/\{ano\}/g, ANO) : a[2];
    if (tipo === 'esperar') { await p.waitForTimeout(Math.min(+sel || 500, 1500)); continue; }
    if (tipo === 'ir') { await p.goto(L.BASE + 'painel/' + String(sel).replace(/^\//, '')); await p.waitForLoadState('networkidle').catch(() => {}); continue; }
    const el = await p.waitForSelector(sel, { state: 'attached', timeout: 6000 }).catch(() => null);
    /* como o motor (vcostaedu-faca-comigo.js: sem o alvo, segue): o clique que já não tem alvo porque a página mudou
       (D-103, C7: com um tipo de atividade só, "Cadastrar atividade" vai direto, sem a janela dos tipos) */
    if (!el && tipo === 'clicar' && p.url() !== urlInicio) { continue; }
    if (!el) { throw new Error(`a ação "${tipo}" não achou ${sel}`); }
    if (tipo === 'preencher') {
      await el.evaluate((e, v) => { e.value = v; e.dispatchEvent(new Event('input', { bubbles: true })); e.dispatchEvent(new Event('change', { bubbles: true })); }, valor);
    } else if (tipo === 'escolher') {
      await el.evaluate((e, v) => {
        let alvo = null;
        for (const o of e.options) {
          if (v === '{primeira}' ? (o.value !== '' && o.value !== '0') : (o.value === v || o.text.trim() === v)) { alvo = o; break; }
        }
        if (alvo) { if (e.multiple) { alvo.selected = true; } else { e.value = alvo.value; } }
        e.dispatchEvent(new Event('change', { bubbles: true }));
        if (window.jQuery) { window.jQuery(e).trigger('change'); }
      }, valor);
    } else if (tipo === 'marcar') {
      await el.evaluate((e) => { if (!e.checked) { e.click(); } });
    } else if (tipo === 'anexar') {
      await el.setInputFiles({ name: a[3], mimeType: 'image/png', buffer: Buffer.from(a[2], 'base64') });
    } else if (tipo === 'clicar') {
      await Promise.all([p.waitForLoadState('load').catch(() => {}), el.click()]);
      await p.waitForTimeout(600);
      await p.waitForLoadState('networkidle').catch(() => {});
    } else if (tipo === 'nota') {
      await el.click();
      const inp = await p.waitForSelector('#tabelaLancaNotas input.vc-nota[name="nota"]:visible', { timeout: 5000 });
      await inp.fill(valor);
      await Promise.all([p.waitForResponse((r) => /edit-notas/.test(r.url()), { timeout: 8000 }).catch(() => null), inp.press('Enter')]);
    }
  }
}

/** Percorre o roteiro <slug> no modo "junto": a cada passo que o motor mostra, faz as ações DO ARQUIVO e segue. */
async function percorrer(p, slug, arquivo = null) {
  const r = JSON.parse(fs.readFileSync(arquivo || path.join(ROTEIROS, slug + '.json'), 'utf8'));
  await p.goto(L.BASE + 'painel/faca-comigo/iniciar/' + slug);
  await p.waitForLoadState('networkidle').catch(() => {});
  const feitos = new Set();
  for (let volta = 0; volta < 120; volta++) {
    if (await p.evaluate(() => window.vcTourFim === true).catch(() => false)) { return { ok: true, passos: feitos.size }; }
    const caixa = await p.waitForSelector('#vc-tour[data-passo]', { timeout: 10000 }).catch(() => null);
    if (!caixa) {
      if (await p.evaluate(() => window.vcTourFim === true).catch(() => false)) { return { ok: true, passos: feitos.size }; }
      return { ok: false, motivo: `a janela do Faça comigo não apareceu em ${p.url().replace(L.BASE, '')}` };
    }
    const i = +(await caixa.getAttribute('data-passo'));
    const passo = r.passos[i];
    if (!passo) { return { ok: false, motivo: `passo ${i + 1} fora do roteiro` }; }
    if (passo.a && !(await p.$(passo.a))) { return { ok: false, motivo: `passo ${i + 1} ("${passo.t}"): o alvo ${passo.a} não está na tela` }; }
    /* depois de feito (a página recarregou e o modal fechou), o destaque já não se aplica */
    if (!feitos.has(i) && !(await p.$('.vc-tour-alvo')) && passo.a) { return { ok: false, motivo: `passo ${i + 1}: o motor não destacou o alvo` }; }
    if (!feitos.has(i)) {
      feitos.add(i);
      try { await executar(p, passo.faz); } catch (e) { return { ok: false, motivo: `passo ${i + 1}: ${e.message}` }; }
    }
    await p.waitForTimeout(1300);
    if (await p.evaluate(() => window.vcTourFim === true).catch(() => false)) { return { ok: true, passos: feitos.size }; }
    const ainda = await p.$(`#vc-tour[data-passo="${i}"]`);
    if (ainda) {
      const erro = await p.$eval('#vc-tour .vc-tour-erro-texto:not([hidden])', (e) => e.textContent).catch(() => '');
      if (erro && feitos.has(i) && volta > 2 * r.passos.length) { return { ok: false, motivo: `passo ${i + 1}: ${erro}` }; }
      const btn = await p.$('#vc-tour .vc-tour-avancar');
      if (btn && (await btn.isEnabled())) { await btn.click(); await p.waitForTimeout(700); }
    }
  }
  return { ok: false, motivo: 'o roteiro não terminou' };
}

(async () => {
  const { navegador } = await L.abrir();
  try {
    console.log('\n== Onda 2, Etapa 4 (jornada do zero)');
    /* A. do zero */
    let r = spawnSync(PHP, [path.join(L.RAIZ, 'tests', 'tabler', 'reset.php')], { encoding: 'utf8' });
    L.ok(r.status === 0, 'banco de prova recriado vazio');
    r = spawnSync('node', [path.join(L.RAIZ, 'tests', 'tabler', 'instalar.js'), 'normal'], { encoding: 'utf8', env: { ...process.env, VCOSTAEDU_ETAPA_PROVA: ETAPA + '/instalador' } });
    L.ok(r.status === 0 && /Configurar a minha instituição/.test(r.stdout + fs.readFileSync(path.join(L.RAIZ, 'instalar', 'index.php'), 'utf8')), 'instalado pelo instalador; a última tela traz a escolha do começo (conhecer ou configurar)');
    sql("DELETE FROM adms_instalacao_estado WHERE chave = 'comeco.escolhido'");
    const adm = await entrar(navegador, ADMIN, ADMIN_SENHA);
    L.ok(await adm.p.isVisible('#cartaoComecar'), 'primeiro acesso: o cartão "Como você quer começar?" na Página Inicial');
    await L.print(adm.p, ETAPA, 'inicio-como-comecar');

    /* B. conhecer com a demonstração, pela tela */
    await adm.p.click('#cartaoConhecer');
    await adm.p.waitForSelector('#formDemonstracao');
    await L.print(adm.p, ETAPA, 'como-comecar');
    /* Onda 3, Etapa 4: logo depois de instalar, sem a senha de novo; a montagem em passos, na janela de progresso */
    L.ok(!(await adm.p.$('#senhaDemonstracao')), 'logo depois de instalar, a demonstração não pede a senha de novo');
    const t0 = Date.now();
    await adm.p.click('#montarDemonstracao');
    await adm.p.waitForFunction(() => ['concluida', 'falhou'].includes(document.getElementById('progressoMontagem').getAttribute('data-situacao')), null, { timeout: 600000 });
    await Promise.all([adm.p.waitForNavigation(), adm.p.click('#progressoMontagem .vc-progresso-fim')]);
    L.ok(await adm.p.isVisible('#demonstracaoMontada'), `a demonstração montada pela tela, em passos, sem editar arquivo (${Math.round((Date.now() - t0) / 1000)} s)`);
    L.ok(!/AMBIENTE', 'demonstracao'/.test(fs.readFileSync(path.join(L.RAIZ, 'painel', 'core', 'Config.php'), 'utf8')), 'a Config continua em produção (a demonstração ligada é do banco)');
    L.ok(await adm.p.isVisible('#faixaDemonstracao') && await adm.p.isVisible('#faixaComecarInstituicao'), 'a faixa da demonstração, com o botão do administrador');
    const perfis = await adm.p.$$eval('#tabelaPerfisDemo tbody tr', (t) => t.length).catch(() => 0);
    L.ok(perfis >= 6, `o cartão "Conheça o sistema": ${perfis} perfis com o usuário e a senha fictícios`);
    await L.print(adm.p, ETAPA, 'conheca-o-sistema');
    for (const rota of ['disciplinas/listar', 'relatorios/listar', 'academicos/listar']) {
      await adm.p.goto(L.BASE + 'painel/' + rota);
      if (!(await adm.p.isVisible('#faixaDemonstracao'))) { L.ok(false, `sem a faixa em ${rota}`); }
    }
    L.ok(true, 'a faixa em toda tela do painel (disciplinas, relatórios, alunos)');
    /* contra o retrato mais novo da onda (o da etapa em que a jornada roda: as páginas das etapas seguintes também estão nele) */
    const ordemRetrato = (f) => { const m = /^regua-onda(\d+)-etapa(\d+)\.json$/.exec(f); return +m[1] * 100 + +m[2]; }; // as ondas seguintes também
    const retratos = fs.readdirSync(path.join(L.RAIZ, 'tests', 'tabler', 'saida')).filter((f) => /^regua-onda\d+-etapa\d+\.json$/.test(f))
      .sort((a, b) => ordemRetrato(a) - ordemRetrato(b));
    const reg = spawnSync(PHP, [path.join(L.RAIZ, 'tests', 'tabler', 'regua.php'), 'comparar', 'tests/tabler/saida/' + retratos[retratos.length - 1]], { encoding: 'utf8', cwd: L.RAIZ });
    fs.writeFileSync(path.join(L.RAIZ, 'tests', 'tabler', 'saida', 'jornada4-regua.txt'), (reg.stdout || '') + (reg.stderr || ''));
    const difs = (reg.stdout.match(/^\s+tabelas\/([a-z_]+)/gm) || []).map((x) => x.trim().replace('tabelas/', ''));
    const vidas = /vidas\/|boletins\//.test(reg.stdout);
    const fora = difs.filter((t) => !['adms_instalacao_estado'].includes(t));
    L.ok((reg.status === 0 || difs.length > 0) && fora.length === 0 && !vidas, `régua da demonstração montada pela tela contra a montada pelas ferramentas: acadêmico IDÊNTICO${difs.length ? ' (difere só: ' + difs.join(', ') + ')' : ''}`);
    const cred = JSON.parse(sql("SELECT valor FROM adms_instalacao_estado WHERE chave = 'demonstracao.credenciais'"));
    const ra = Object.keys(cred.portal)[0];
    const alu = await entrar(navegador, ra, cred.portal[ra], 'portal');
    L.ok(await alu.p.isVisible('#faixaDemonstracao'), 'a faixa também no portal do aluno');
    await alu.p.goto(L.BASE + 'portal/atestado/index');
    const emitir = await alu.p.$('button[name="EmitirAtestado"]');
    if (emitir) { await Promise.all([alu.p.waitForNavigation(), emitir.click()]); }
    L.ok(await alu.p.isVisible('#atestadoEmitido'), 'o atestado de matrícula sai (a demonstração ganhou a previsão de conclusão)');
    const em = sql("SELECT CONCAT(codigo, '|', ambiente) FROM rm_historico_emissoes WHERE tipo LIKE '%ATESTADO%' ORDER BY id DESC LIMIT 1").split('|');
    L.ok(em[1] === 'demonstracao', 'o atestado sai com a tarja da demonstração (ambiente da emissão)');
    await L.print(alu.p, ETAPA, 'portal-atestado');
    const pub = await navegador.newPage();
    await pub.goto(L.BASE + 'verificar/' + em[0]);
    L.ok(/válido/i.test(await pub.content()), `o atestado conferido na verificação pública (${em[0].slice(0, 4)}…)`);
    await pub.close();
    await alu.ctx.close();
    /* a recusa com a razão: outra matrícula da demonstração sem a previsão de conclusão (a regra de antes da Etapa 4) */
    const ra2 = Object.keys(cred.portal).find((r) => r !== ra);
    sql(`UPDATE rm_matriculas_curso mc JOIN col_usuarios u ON u.id = mc.usuario_id SET mc.previsao_conclusao = NULL WHERE u.rm = '${ra2}'`);
    const emitidos0 = um("SELECT COUNT(*) FROM rm_historico_emissoes WHERE tipo LIKE '%ATESTADO%'");
    const alu2 = await entrar(navegador, ra2, cred.portal[ra2], 'portal');
    await alu2.p.goto(L.BASE + 'portal/atestado/index');
    const emitir2 = await alu2.p.$('button[name="EmitirAtestado"]');
    if (emitir2) { await Promise.all([alu2.p.waitForNavigation(), emitir2.click()]); }
    L.ok(/Falta a previs/.test(await alu2.p.content()) && um("SELECT COUNT(*) FROM rm_historico_emissoes WHERE tipo LIKE '%ATESTADO%'") === emitidos0,
      'sem a previsão de conclusão, o atestado é recusado com a razão e nada é emitido');
    await alu2.ctx.close();

    /* C. as recusas */
    await adm.p.goto(L.BASE + 'painel/demonstracao/instituicao');
    await L.print(adm.p, ETAPA, 'comecar-com-a-instituicao');
    const recusaDaJanela = async () => {
      await adm.p.waitForFunction(() => document.getElementById('progressoRemocao').getAttribute('data-situacao') === 'falhou', null, { timeout: 60000 });
      const m = (await adm.p.textContent('#progressoRemocao .vc-progresso-falha-motivo')).trim();
      await adm.p.click('#progressoRemocao .vc-progresso-fechar');
      return m;
    };
    await adm.p.fill('#confirmacao', 'comecar');
    await adm.p.click('#removerDemonstracao');
    L.ok(/escreva COMEÇAR/.test(await recusaDaJanela()), 'sem a frase exata, a remoção é recusada');
    sql("UPDATE adms_empresas SET nome = CONCAT('Configurado de verdade: ', nome) WHERE instituicao = 1"); // volta só no SQL (o acento não passa pelo console)
    const antes = contagens();
    await adm.p.fill('#confirmacao', 'COMEÇAR');
    await adm.p.click('#removerDemonstracao');
    const recusa = await recusaDaJanela();
    L.ok(/instituição/i.test(recusa) && contagens().cursos === antes.cursos, `com dado real (a instituição configurada pela tela), a remoção é recusada e diz qual ("${recusa.replace(/\s+/g, ' ').slice(0, 90)}…")`);
    await L.print(adm.p, ETAPA, 'recusa-dado-real');
    sql("UPDATE adms_empresas SET nome = SUBSTRING(nome, CHAR_LENGTH('Configurado de verdade: ') + 1) WHERE instituicao = 1 AND nome LIKE 'Configurado de verdade: %'");

    /* D. começar com os dados da instituição */
    await adm.p.goto(L.BASE + 'painel/demonstracao/instituicao');
    await adm.p.fill('#confirmacao', 'COMEÇAR');
    await adm.p.click('#removerDemonstracao');
    await adm.p.waitForFunction(() => ['concluida', 'falhou'].includes(document.getElementById('progressoRemocao').getAttribute('data-situacao')), null, { timeout: 300000 });
    await Promise.all([adm.p.waitForNavigation(), adm.p.click('#progressoRemocao .vc-progresso-fim')]);
    const depois = contagens();
    L.ok(await adm.p.isVisible('#demonstracaoRemovida') && /primeiros-passos\/index/.test(adm.p.url()), 'a demonstração removida sem reinstalar; abrem os Primeiros passos');
    L.ok(depois.cursos === 0 && depois.alunos === 0 && depois.notas === 0 && depois.equipe === 1,
      `contagens: cursos ${antes.cursos} → ${depois.cursos}, alunos ${antes.alunos} → ${depois.alunos}, notas ${antes.notas} → ${depois.notas}, equipe ${antes.equipe} → ${depois.equipe} (fica o administrador)`);
    L.ok(!(await adm.p.isVisible('#faixaDemonstracao')), 'a faixa saiu');
    const estados0 = await adm.p.$$eval('.vc-fase', (f) => f.map((x) => x.dataset.estado));
    L.ok(estados0.length === 8 && !estados0.includes('concluida'), `os Primeiros passos: 8 fases, nenhuma concluída (${estados0.join(', ')})`);
    await L.print(adm.p, ETAPA, 'primeiros-passos-inicio');
    await adm.p.goto(L.BASE + 'painel/home/index');
    L.ok(await adm.p.isVisible('#cartaoPrimeirosPassos') && !!(await adm.p.$('#railMenu a[href*="primeiros-passos/index"]')), 'o cartão dos Primeiros passos na Página Inicial e o item no menu');

    /* E. as fases pelo Faça comigo */
    await adm.p.goto(L.BASE + 'painel/primeiros-passos/index');
    await Promise.all([adm.p.waitForNavigation(), adm.p.click('.vc-fase[data-fase="email"] .vc-fase-pular')]);
    L.ok((await adm.p.getAttribute('.vc-fase[data-fase="email"]', 'data-estado')) === 'pulada', 'pular uma fase (o e-mail)');
    await Promise.all([adm.p.waitForNavigation(), adm.p.click('.vc-fase[data-fase="email"] .vc-fase-retomar')]);
    L.ok((await adm.p.getAttribute('.vc-fase[data-fase="email"]', 'data-estado')) !== 'pulada', 'retomar a fase pulada');
    const ordem = ['instituicao', 'regras', 'email', 'ano_letivo', 'curso', 'oferta', 'alunos', 'enturmar'];
    for (const codigo of ordem) {
      const slug = sql(`SELECT roteiro FROM adms_fases WHERE codigo = '${codigo}'`);
      const res = await percorrer(adm.p, slug);
      L.ok(res.ok, `Faça comigo "${slug}": ${res.ok ? res.passos + ' passos percorridos a partir do roteiro' : res.motivo}`);
      if (!res.ok) { await L.print(adm.p, ETAPA, 'falha-' + slug); }
      await adm.p.goto(L.BASE + 'painel/primeiros-passos/index');
      const est = await adm.p.getAttribute(`.vc-fase[data-fase="${codigo}"]`, 'data-estado');
      const anuncio = await adm.p.$eval('#faseConcluida', (e) => e.textContent.trim()).catch(() => '');
      const feito = sql(`SELECT feito FROM adms_fases WHERE codigo = '${codigo}'`);
      L.ok(est === 'concluida' && anuncio.includes(feito.slice(0, 25)), `fase "${codigo}" concluída sozinha (medida no banco) e anunciada ("${anuncio.slice(0, 110)}")`);
      if (codigo === 'instituicao') {
        L.ok(await adm.p.isVisible('#previaLogo'), 'a prévia do logo: no menu, na entrada e no cabeçalho do documento');
        await L.print(adm.p, ETAPA, 'fase-instituicao-concluida');
      }
    }
    /* o que as fases gravaram é o que as telas gravam */
    L.ok(sql("SELECT CONCAT_WS('|', nome, razao_social, cnpj, IF(logo IS NULL OR logo = '', 'sem', 'com')) FROM adms_empresas WHERE instituicao = 1") === 'Instituto Exemplo de Educação|Associação Exemplo de Ensino|11.222.333/0001-81|com', 'a instituição gravada pela tela dos dados da instituição');
    L.ok(um(`SELECT COUNT(*) FROM adms_periodos_letivos WHERE ano = ${ANO}`) === 1 && um("SELECT COUNT(*) FROM rm_cursos WHERE nome LIKE 'Especializa% em Educa% de Exemplo'") === 1 /* sem acento: o console do Windows */
      && um('SELECT COUNT(*) FROM uni_rh_cursos_disciplinas_grade') >= 1 && um("SELECT COUNT(*) FROM rm_professores_mat_liga l JOIN adms_usuarios u ON u.id = l.usuario_id WHERE u.usuario = 'professora.exemplo'") >= 1
      && um("SELECT COUNT(*) FROM rm_matriculas_curso mc JOIN col_usuarios u ON u.id = mc.usuario_id WHERE u.rm = '100001'") === 1 && um('SELECT COUNT(*) FROM rm_disciplina_atividade_notas WHERE nota IS NOT NULL') >= 1,
      'o ano letivo, o curso com a grade, a oferta com a professora, a aluna vinculada e a primeira nota: gravados pelas telas de sempre');
    L.ok(um("SELECT COUNT(*) FROM rm_auditoria_lancamentos WHERE tipo = 'nota' AND rm = '100001' AND acao = 'lancou'") === 1, 'a primeira nota também ficou na auditoria');
    const progresso = await adm.p.$eval('#progressoPrimeiros', (e) => e.textContent).catch(() => '');
    L.ok(/8 de 8/.test(progresso), `os Primeiros passos completos (${progresso.trim()})`);
    await L.print(adm.p, ETAPA, 'primeiros-passos-completos');
    await adm.p.goto(L.BASE + 'painel/home/index');
    L.ok(!(await adm.p.isVisible('#cartaoPrimeirosPassos')) && !(await adm.p.$('#railMenu a[href*="primeiros-passos/index"]')), 'concluídos: o cartão e o item do menu saem');

    /* F. o guia: "Faça comigo" e "Ver o vídeo" (só com o vídeo) */
    /* o vídeo real, se a pasta tem (gravado na Onda 5), sai de lado durante a prova e volta no fim, mesmo se ela cair */
    const mp4 = path.join(L.RAIZ, 'assets', 'videos', 'guias', 'regras-de-nota-e-frequencia.mp4');
    const deLado = mp4 + '.prova-jornada4';
    const tinha = fs.existsSync(mp4);
    if (tinha) fs.renameSync(mp4, deLado);
    try {
      await adm.p.goto(L.BASE + 'painel/ajuda/guia/regras-de-nota-e-frequencia');
      L.ok(await adm.p.isVisible('#facaComigoGuia') && !(await adm.p.$('#verVideoGuia')), 'no guia: "Faça comigo"; sem o vídeo, sem "Ver o vídeo"');
      fs.mkdirSync(path.dirname(mp4), { recursive: true });
      fs.writeFileSync(mp4, Buffer.alloc(16));
      await adm.p.reload();
      L.ok(await adm.p.isVisible('#verVideoGuia'), 'com o vídeo na pasta, aparece "Ver o vídeo"');
      await L.print(adm.p, ETAPA, 'guia-faca-comigo-e-video');
    } finally {
      if (fs.existsSync(mp4)) fs.unlinkSync(mp4);
      if (tinha) fs.renameSync(deLado, mp4);
    }
    L.ok(fs.existsSync(mp4) === tinha, 'o vídeo da pasta voltou como estava');

    /* G. sabotagem: um seletor que não existe */
    const sab = path.join(ROTEIROS, 'prova-sabotada.json');
    const base = JSON.parse(fs.readFileSync(path.join(ROTEIROS, 'fase-regras.json'), 'utf8'));
    base.passos[1].a = '#campoQueNaoExiste';
    fs.writeFileSync(sab, JSON.stringify(base));
    try {
      const res = await percorrer(adm.p, 'prova-sabotada', sab);
      L.ok(!res.ok && /campoQueNaoExiste|não está na tela|destacou/.test(res.motivo || ''), `sabotagem (o roteiro aponta para um seletor que não existe): a prova reprova ("${res.motivo}")`);
    } finally { fs.unlinkSync(sab); }
    await adm.ctx.close();
  } catch (e) {
    L.ok(false, 'erro na prova: ' + e.message.split('\n')[0]);
  } finally {
    await navegador.close();
    process.exit(L.resumo('onda 2, etapa 4') ? 0 : 1);
  }
})();
