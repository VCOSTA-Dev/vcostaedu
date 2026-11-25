/*
 * PROVA DA ONDA 7, ETAPA 6 — a demonstração, os primeiros passos e a ajuda dos responsáveis.
 *   A. no ensino superior (o banco que o ciclo deixa): sem a fase, sem os dois relatórios, sem os responsáveis no cartão;
 *   B. na escola (a demonstração do colégio, montada pela tela; APAGA o banco de prova):
 *      1. os responsáveis fictícios cobrem os casos (dois filhos; um aluno com o financeiro e o titular; o adicional com o
 *         motivo; o financeiro que mudou de 2025 para 2026; um sem convite; um com o convite pendente);
 *      2. a Página Inicial lista os usuários de responsável, com a senha só ao clicar em "Mostrar"; a senha entra no portal;
 *      3. os Primeiros passos: a fase dos responsáveis e do convite, marcada sozinha (pendente sem papel, concluída com ele);
 *      4. os guias da ajuda abrem;
 *      5. os dois relatórios: os alunos sem o financeiro ou sem o titular, por turma; os adicionais em vigor, com o motivo e
 *         quem liberou — sabotagem: o relatório dos adicionais sem a data do fim (a liberação encerrada apareceria);
 *      6. o registro da instalação não envia nada de responsável — sabotagem: a contagem dos responsáveis no conteúdo;
 *      7. "Começar com os dados da minha instituição": os responsáveis da demonstração saem com ela — sabotagem: a limpeza
 *         sem o trecho dos responsáveis (sobrariam linhas).
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { spawnSync, execFileSync } = require('child_process');
const L = require('../lib');

const ETAPA = 'onda7/etapa6';
const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste$/.test(BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
const SENHA = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const REL_AD = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'relatorios', 'RelAcessosAdicionais.php');
const REGISTRO = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'manutencao', 'AdmsRegistroInstalacao.php');
const SEED = path.join(L.RAIZ, 'database', 'seed_demo.sql');
const GUIAS = ['cadastrar-um-responsavel', 'designar-o-financeiro-e-o-titular', 'liberar-um-acesso-academico-adicional', 'trocar-o-responsavel-financeiro',
  'copiar-do-ano-anterior', 'convidar-um-responsavel', 'convidar-os-responsaveis-de-uma-turma', 'o-que-cada-papel-ve', 'bloquear-o-acesso-de-um-responsavel'];
const TABELAS = ['esc_responsaveis', 'esc_responsavel_alunos', 'esc_responsavel_designacoes', 'esc_responsavel_contatos', 'esc_responsavel_auditoria', 'esc_responsavel_convites'];

const sql = (q) => execFileSync('C:/xampp/mysql/bin/mysql.exe', ['-uroot', '--default-character-set=utf8mb4', '-N', '-B', BANCO, '-e', q], { encoding: 'utf8', stdio: ['pipe', 'pipe', 'pipe'] }).trim();
const um = (q) => +(sql(q).split('\n')[0] || 0);
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const pessoa = (email) => um(`SELECT id FROM esc_responsaveis WHERE email = '${email}'`);
const aluno = (ra) => um(`SELECT id FROM col_usuarios WHERE rm = '${ra}'`);
const papel = (email, ra, ano, p) => um(`SELECT COUNT(*) FROM esc_responsavel_designacoes WHERE responsavel_id = ${pessoa(email)} AND aluno_id = ${aluno(ra)} AND ano = ${ano} AND papel = '${p}' AND fim IS NULL`);

async function sabotar(arq, de, para, fn) {
  const orig = fs.readFileSync(arq, 'utf8');
  if (!orig.includes(de)) { throw new Error('a sabotagem não achou o ponto em ' + path.basename(arq)); }
  fs.writeFileSync(arq, orig.replace(de, para));
  try { return await fn(); } finally { fs.writeFileSync(arq, orig); }
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
/** As linhas da tabela do relatório (o texto de cada linha). */
const linhasRel = (p) => p.$$eval('table[data-relatorio] tbody tr', (l) => l.map((t) => t.innerText.replace(/\s+/g, ' ').trim())).catch(() => []);

(async () => {
  const { navegador } = await L.abrir();
  const shas = { [REL_AD]: sha(REL_AD), [REGISTRO]: sha(REGISTRO), [SEED]: sha(SEED) };
  try {
    console.log('\n== Onda 7, Etapa 6 — a demonstração, os primeiros passos e a ajuda dos responsáveis');
    /* A. o ensino superior */
    let s = await entrarPainel(navegador);
    await s.p.goto(L.BASE + 'painel/primeiros-passos/index');
    const faseSup = !!(await s.p.$('[data-fase="esc_responsaveis"]'));
    await s.p.goto(L.BASE + 'painel/relatorios/listar');
    const catSup = await s.p.evaluate(() => document.body.innerText);
    await s.p.goto(L.BASE + 'painel/relatorios/responsaveis-faltando');
    const relSup = !!(await s.p.$('table[data-relatorio="R-SEC-05"]'));
    await s.p.goto(L.BASE + 'painel/home/index');
    const cartaoSup = await s.p.$$eval('.vc-demo-familia', (l) => l.length).catch(() => 0);
    L.ok(!faseSup && !/sem responsável financeiro|adicionais em vigor/i.test(catSup) && !relSup && cartaoSup === 0,
      'no ensino superior: sem a fase dos responsáveis, sem os dois relatórios e sem os responsáveis no cartão da demonstração');
    await s.ctx.close();

    /* B. a escola */
    const r = spawnSync(process.execPath, [path.join(__dirname, '..', 'onda6', 'medir_demo.js'), 'escola', 'onda7-etapa6'], { encoding: 'utf8', maxBuffer: 1 << 24 });
    L.ok(r.status === 0, 'a demonstração do colégio montada pela tela, com os responsáveis');

    /* 1. os casos */
    const H = 'helena.ribeiro@exemplo.invalid';
    const ok1 = papel(H, 265032, 2026, 'financeiro') && papel(H, 265032, 2026, 'titular') && papel(H, 265022, 2026, 'titular') && !papel(H, 265022, 2026, 'financeiro');
    const ok2 = papel('rogerio.sa@exemplo.invalid', 265022, 2026, 'financeiro') === 1;
    const motivo = sql(`SELECT IFNULL(motivo, '') FROM esc_responsavel_designacoes WHERE papel = 'adicional' AND responsavel_id = ${pessoa('dora.ribeiro@exemplo.invalid')} AND fim IS NULL`);
    const ok4 = papel(H, 265022, 2025, 'financeiro') === 1;
    const semConvite = um(`SELECT COUNT(*) FROM esc_responsavel_convites WHERE responsavel_id = ${pessoa('caio.dias@exemplo.invalid')}`) === 0;
    const pendente = um(`SELECT COUNT(*) FROM esc_responsavel_convites WHERE responsavel_id = ${pessoa('lia.dias@exemplo.invalid')} AND usado_em IS NULL AND cancelado_em IS NULL AND expira_em > NOW()`) === 1
      && um(`SELECT COUNT(*) FROM esc_responsaveis WHERE id = ${pessoa('lia.dias@exemplo.invalid')} AND senha IS NULL`) === 1;
    const comSenha = um("SELECT COUNT(*) FROM esc_responsaveis WHERE senha IS NOT NULL AND email IN ('helena.ribeiro@exemplo.invalid', 'rogerio.sa@exemplo.invalid', 'dora.ribeiro@exemplo.invalid')");
    L.ok(ok1 && ok2 && motivo.length > 10 && ok4 && semConvite && pendente && comSenha === 3,
      'os responsáveis fictícios: dois filhos (financeira e titular de um, só titular do outro); um aluno com o financeiro e o titular; o adicional com o motivo; o financeiro que mudou de 2025 para 2026; um sem convite; um com o convite pendente');

    /* 2. a Página Inicial */
    s = await entrarPainel(navegador);
    await s.p.goto(L.BASE + 'painel/home/index');
    const linhas = await s.p.$$eval('.vc-demo-familia', (l) => l.map((t) => ({ u: t.getAttribute('data-usuario'), visivel: t.querySelector('.vc-senha-oculta').textContent.trim() })));
    const oculta = linhas.length === 3 && linhas.every((x) => x.visivel === '••••••••');
    await s.p.click(`.vc-demo-familia[data-usuario="${H}"] .vc-mostrar-senha`);
    const mostrada = (await s.p.textContent(`.vc-demo-familia[data-usuario="${H}"] .vc-senha-oculta`)).trim();
    await L.print(s.p, ETAPA, 'cartao-demonstracao');
    const ctxF = await navegador.newContext({ viewport: { width: 390, height: 844 }, locale: 'pt-BR' });
    const pf = await L.pagina(ctxF);
    await pf.goto(L.BASE + 'portal/login/acesso');
    await pf.fill('input[name="usuario"]', H);
    await pf.fill('input[name="senha"]', mostrada);
    await Promise.all([pf.waitForNavigation(), pf.click('[name="SendLogin"]')]);
    const filhos = await pf.$$eval('#listaFilhos [data-aluno]', (l) => l.length).catch(() => 0);
    await ctxF.close();
    L.ok(oculta && mostrada.length >= 12 && mostrada !== '••••••••' && filhos === 2,
      'a Página Inicial: os 3 usuários de responsável com a senha escondida; "Mostrar" revela, e a senha entra no portal (os dois filhos)');

    /* 3. os Primeiros passos */
    const estadoFase = async () => { await s.p.goto(L.BASE + 'painel/primeiros-passos/index'); return s.p.getAttribute('[data-fase="esc_responsaveis"]', 'data-estado').catch(() => ''); };
    const comDemo = await estadoFase();
    const salvas = sql('SELECT COUNT(*) FROM esc_responsavel_designacoes');
    sql('CREATE TABLE IF NOT EXISTS _prova_desig AS SELECT * FROM esc_responsavel_designacoes');
    sql('DELETE FROM esc_responsavel_designacoes');
    let semPapel;
    try { semPapel = await estadoFase(); } finally {
      sql('INSERT INTO esc_responsavel_designacoes SELECT * FROM _prova_desig');
      sql('DROP TABLE _prova_desig');
    }
    const roteiro = !!(await s.p.$('[data-fase="esc_responsaveis"] .vc-faca-comigo'));
    L.ok(comDemo === 'concluida' && semPapel !== 'concluida' && sql('SELECT COUNT(*) FROM esc_responsavel_designacoes') === salvas && roteiro,
      `os Primeiros passos: a fase dos responsáveis e do convite, marcada sozinha (sem papel: ${semPapel}; com o papel e o convite: ${comDemo}), com o "Faça comigo"`);

    /* 4. os guias */
    const guias = [];
    for (const g of GUIAS) {
      const x = await s.p.goto(L.BASE + 'painel/ajuda/guia/' + g);
      if (!x || x.status() !== 200 || !(await s.p.$('#conteudoGuia'))) { guias.push(g); }
    }
    L.ok(guias.length === 0, `os ${GUIAS.length} guias dos responsáveis abrem na Ajuda${guias.length ? ' — não abriram: ' + guias.join(', ') : ''}`);

    /* 5. os relatórios */
    await s.p.goto(L.BASE + 'painel/relatorios/responsaveis-faltando?ano=2026');
    const falt = await linhasRel(s.p);
    await L.print(s.p, ETAPA, 'relatorio-responsaveis-faltando');
    const nomes = (ra) => sql(`SELECT nome FROM col_usuarios WHERE rm = '${ra}'`);
    L.ok(falt.length > 0 && ![265032, 265022, 265033].some((ra) => falt.some((l) => l.includes(nomes(ra)))) && falt.some((l) => l.includes(nomes(265002)) && /titular/.test(l) && !/financeiro/.test(l)) && falt.every((l) => !/financeiro/.test(l)), // D-99: todo aluno com título tem o financeiro do ano
      `o relatório dos alunos sem o financeiro ou sem o titular em 2026: ${falt.length} aluno(s), por turma; os que têm os dois não aparecem`);
    /* uma liberação encerrada hoje não está "em vigor" */
    const a2 = aluno(265002);
    sql(`INSERT INTO esc_responsavel_designacoes (aluno_id, ano, responsavel_id, papel, inicio, fim, motivo, origem, criado_em) VALUES (${a2}, 2026, ${pessoa('dora.ribeiro@exemplo.invalid')}, 'adicional', '2026-03-01', CURDATE(), 'Encerrada (prova)', 'tela', NOW())`);
    await s.p.goto(L.BASE + 'painel/relatorios/acessos-adicionais?ano=2026');
    const ad = await linhasRel(s.p);
    const sabAd = await sabotar(REL_AD, "'(d.fim IS NULL OR d.fim > CURDATE())'", "'1 = 1'", async () => { await s.p.goto(L.BASE + 'painel/relatorios/acessos-adicionais?ano=2026'); return linhasRel(s.p); });
    sql("DELETE FROM esc_responsavel_designacoes WHERE motivo = 'Encerrada (prova)'");
    const liberou = sql("SELECT nome FROM adms_usuarios WHERE usuario = 'secretaria.demo'");
    L.ok(ad.length === 1 && ad[0].includes('Dora Modelo Ribeiro') && ad[0].includes(motivo.slice(0, 20)) && liberou !== '' && ad[0].includes(liberou),
      `o relatório dos acessos adicionais em vigor: o aluno, a pessoa, o motivo e quem liberou; a liberação encerrada não aparece${ad.length === 1 && ad[0].includes(liberou) ? '' : ' — ' + ad.join(' || ')}`);
    L.ok(sabAd.length === 2, 'sabotagem (o relatório sem a data do fim): a prova acusa — a liberação encerrada apareceu');
    L.ok(sha(REL_AD) === shas[REL_AD], 'o arquivo sabotado voltou igual (sha256)');

    /* 6. o registro da instalação */
    const previa = async () => { await s.p.goto(L.BASE + 'painel/registro-instalacao/index'); return JSON.parse(await s.p.textContent('#conteudoRegistro')); };
    const pv = await previa();
    const daFamilia = sql("SELECT CONCAT_WS('|', nome, IFNULL(email, ''), IFNULL(cpf, '')) FROM esc_responsaveis").split('\n').flatMap((l) => l.split('|')).filter((x) => x.length >= 4);
    const limpo = (o) => Object.keys(o.contagens).sort().join() === 'alunos,cursos,turmas' && !/respons/i.test(JSON.stringify(o)) && !daFamilia.some((x) => JSON.stringify(o).includes(x));
    const sabReg = await sabotar(REGISTRO, "'turmas' => $turmas],", "'turmas' => $turmas, 'responsaveis' => $conta('SELECT COUNT(*) FROM esc_responsaveis')],", previa);
    L.ok(limpo(pv) && !limpo(sabReg), 'o registro da instalação não envia nada de responsável (nem contagem, nem nome, e-mail ou CPF) — sabotagem (a contagem dos responsáveis): a prova acusa');
    L.ok(sha(REGISTRO) === shas[REGISTRO], 'o arquivo sabotado voltou igual (sha256)');

    /* 7. a demonstração removida leva os responsáveis */
    const remover = () => spawnSync('C:/xampp/php/php.exe', [path.join(L.RAIZ, 'database', 'ferramentas', 'demonstracao.php'), 'limpar'], { encoding: 'utf8', env: process.env });
    const sobra = () => TABELAS.reduce((t, x) => t + um(`SELECT COUNT(*) FROM ${x}`), 0);
    const antes = sobra();
    const orig = fs.readFileSync(SEED, 'utf8');
    const ini = orig.indexOf('-- Onda 7, Etapa 6: os responsáveis');
    const fim = orig.lastIndexOf('DROP TEMPORARY TABLE IF EXISTS demo_responsaveis;');
    if (ini < 0 || fim < ini) { throw new Error('o trecho dos responsáveis não está na limpeza'); }
    const trecho = orig.slice(ini, orig.indexOf('\n', fim) + 1);
    /* a sabotagem primeiro: sem o trecho, a limpeza deixa os responsáveis (e o banco de prova é remontado depois) */
    const sabLimpa = await sabotar(SEED, trecho, '', async () => { const x = remover(); return { rc: x.status, sobra: sobra(), saida: (x.stdout + x.stderr).slice(-300) }; });
    L.ok(sabLimpa.sobra > 0, `sabotagem (a limpeza sem o trecho dos responsáveis): a prova acusa — sobraram ${sabLimpa.sobra} linha(s)`);
    L.ok(sha(SEED) === shas[SEED], 'o arquivo sabotado voltou igual (sha256)');
    const r2 = spawnSync(process.execPath, [path.join(__dirname, '..', 'onda6', 'medir_demo.js'), 'escola', 'onda7-etapa6b'], { encoding: 'utf8', maxBuffer: 1 << 24 });
    const deNovo = sobra();
    const x = remover();
    L.ok(antes > 0 && r2.status === 0 && deNovo > 0 && x.status === 0 && sobra() === 0, `"Começar com os dados da minha instituição": os responsáveis da demonstração saem com ela (${deNovo} linha(s) → ${sobra()})`);
    await s.ctx.close();
  } catch (err) {
    L.ok(false, 'a prova parou: ' + (err.stack || err.message).slice(0, 500));
  } finally {
    for (const [f, h] of Object.entries(shas)) { if (sha(f) !== h) { console.log('  [AVISO] o arquivo sabotado não voltou igual: ' + f); } }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
