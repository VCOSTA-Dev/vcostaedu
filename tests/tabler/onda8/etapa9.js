/*
 * PROVA DA ONDA 8, CORREÇÃO, PARTE 2.2 — a chamada a cada clique (D-75). APAGA o banco de prova.
 *   1. a demonstração do colégio, montada pela tela numa instalação nova;
 *   2. a professora, pela tela (Minhas disciplinas → o componente → a aba Chamada), põe a frequência por chamada e cria
 *      um encontro começando com TODOS AUSENTES: o encontro nasce com todos ausentes e abre já gravado;
 *   3. um clique num aluno grava na hora (sem botão): o ícone "Gravado"; a presença no banco; a auditoria (o antes e o
 *      depois); as faltas da etapa consolidadas no mesmo clique (quem ficou ausente tem a falta, quem virou presente não);
 *   4. a escolha fica lembrada: o encontro seguinte já abre em "todos ausentes"; ela escolhe "todos presentes", e o
 *      encontro nasce com todos presentes;
 *   5. a falta aparece na tela das etapas e no boletim do aluno;
 *   6. a etapa fechada recusa o clique: o alerta fica no botão, com o motivo, e nada muda no banco;
 *   sabotagem: o clique que grava a presença sem consolidar as faltas — a prova acusa; o arquivo volta igual (sha256).
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { spawnSync, execFileSync } = require('child_process');
const L = require('../lib');

const ETAPA = 'onda8/etapa9';
const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste$/.test(BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
const MODELO = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'faltas', 'AdmsChamada.php');

const sql = (q) => execFileSync('C:/xampp/mysql/bin/mysql.exe', ['-uroot', '--default-character-set=utf8mb4', '-N', '-B', BANCO, '-e', q], { encoding: 'utf8' }).split('\n').map((l) => l.trim()).filter(Boolean).join('\n');
const um = (q) => +(sql(q).split('\n')[0] || 0);
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const montar = (rotulo) => spawnSync(process.execPath, [path.join(__dirname, '..', 'onda6', 'medir_demo.js'), 'escola', rotulo], { encoding: 'utf8', maxBuffer: 1 << 24 });
async function entrar(navegador, usuario, senha) {
  const ctx = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' });
  const p = await L.pagina(ctx);
  p.on('dialog', (d) => d.accept());
  await L.confirmarSozinho(p); // D-100: a confirmação é a janela do sistema
  await p.goto(L.BASE + 'painel/login/acesso');
  await p.fill('input[name="usuario"]', usuario);
  await p.fill('input[name="senha"]', senha);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
  return { ctx, p };
}
/** Um clique no botão de presença da linha i; espera o sinal. */
async function clicar(p, i) {
  const sel = `#tabelaPresenca tbody tr >> nth=${i}`;
  const rm = await p.getAttribute(sel, 'data-rm');
  await p.click(`${sel} >> .botao-presenca`);
  await p.waitForFunction((r) => { const b = document.querySelector(`#tabelaPresenca tr[data-rm="${r}"] .botao-presenca`); const s = b && b.nextElementSibling; return s && s.classList.contains('vc-sinal') && s.querySelector('.vc-sinal-ok, .vc-sinal-erro'); }, rm, { timeout: 15000 });
  return p.evaluate((r) => {
    const b = document.querySelector(`#tabelaPresenca tr[data-rm="${r}"] .botao-presenca`);
    const s = b.nextElementSibling;
    const ok = s.querySelector('.vc-sinal-ok');
    return { rm: r, texto: b.textContent.trim(), ok: !!ok, titulo: ok ? ok.title : '', erro: !!s.querySelector('.vc-sinal-erro'), motivo: (s.querySelector('.vc-sinal-motivo') || { textContent: '' }).textContent,
      caixas: document.querySelectorAll('.alert-danger, .modal.show, .toast').length };
  }, rm);
}
/** Novo encontro: abre o formulário pela lista da chamada, escolhe o começo e salva. @return o id do encontro */
async function novoEncontro(p, oferta, inicio) {
  await p.goto(L.BASE + `painel/chamada/listar/${oferta}`);
  await Promise.all([p.waitForNavigation(), p.click('#novoEncontro')]);
  const marcado = await p.$eval('#inicioChamada input:checked', (e) => e.value).catch(() => '');
  if (inicio) { await p.click(`#inicioChamada label[for="${inicio === 'ausentes' ? 'inicioAusentes' : 'inicioPresentes'}"]`); }
  const botoes = await p.$$eval('#tabelaPresenca .botao-presenca', (l) => l.map((b) => b.textContent.trim()));
  await Promise.all([p.waitForNavigation(), p.click('#salvarChamada')]);
  return { id: +(new URL(p.url()).searchParams.get('id') || 0), marcado, botoes };
}
const faltasEtapa = (oferta, rm) => um(`SELECT IFNULL(SUM(faltas), 0) FROM esc_faltas_etapa WHERE oferta_id = ${oferta} AND rm = '${rm}'`);

(async () => {
  const { navegador } = await L.abrir();
  const shaModelo = sha(MODELO);
  try {
    const r = montar('onda8-etapa9');
    L.ok(r.status === 0, 'a demonstração do colégio montada pela tela, numa instalação nova');
    const cred = JSON.parse(sql("SELECT valor FROM adms_instalacao_estado WHERE chave = 'demonstracao.credenciais'"));
    const senhaProf = (cred.painel || {})['prof.laura.demo'] || '';
    const uid = um("SELECT id FROM adms_usuarios WHERE usuario = 'prof.laura.demo'");

    /* 2. a chamada pela tela, começando com todos ausentes */
    const prof = await entrar(navegador, 'prof.laura.demo', senhaProf);
    await prof.p.goto(L.BASE + 'painel/home/index');
    await Promise.all([prof.p.waitForNavigation(), prof.p.click('a[href*="minhas-disciplinas/listar"] >> nth=0')]);
    await Promise.all([prof.p.waitForNavigation(), prof.p.click('#tabelaDisciplinas tbody tr:has(a:text-is("Matemática")):has(td:text-is("6º ano A")) a[href*="disciplinas/visualizar/"] >> nth=0')]);
    await Promise.all([prof.p.waitForNavigation(), prof.p.click('.vc-abas-lista a.nav-link[href*="chamada/listar/"]')]);
    const oferta = +prof.p.url().match(/chamada\/listar\/(\d+)/)[1];
    if ((await prof.p.getAttribute('#fonteFrequencia', 'data-fonte')) !== 'chamada') {
      await Promise.all([prof.p.waitForNavigation(), prof.p.click('#fonteFrequencia form button[type="submit"]')]);
    }
    const fonte = await prof.p.getAttribute('#fonteFrequencia', 'data-fonte');
    const e1 = await novoEncontro(prof.p, oferta, 'ausentes');
    const todosAus = um(`SELECT COUNT(*) FROM rm_oferta_presencas WHERE encontro_id = ${e1.id} AND presente = 0`);
    const total = um(`SELECT COUNT(*) FROM rm_oferta_presencas WHERE encontro_id = ${e1.id}`);
    const porClique = !!(await prof.p.$('#avisoPorClique'));
    L.ok(fonte === 'chamada' && e1.id > 0 && total > 1 && todosAus === total && e1.botoes.every((b) => b === 'AUSENTE') && porClique,
      `a professora cria o encontro começando com todos ausentes (pela tela): ${todosAus} de ${total} ausentes no banco; o encontro abre já gravado ("Cada clique grava na hora")`);

    /* 3. um clique grava na hora */
    const c1 = await clicar(prof.p, 0);
    const pres = um(`SELECT presente FROM rm_oferta_presencas WHERE encontro_id = ${e1.id} AND rm = '${c1.rm}'`);
    const aud = um(`SELECT COUNT(*) FROM rm_auditoria_lancamentos WHERE tipo = 'presenca' AND encontro_id = ${e1.id} AND rm = '${c1.rm}' AND valor_anterior = 'ausente' AND valor_novo = 'presente'`);
    const rm2 = await prof.p.getAttribute('#tabelaPresenca tbody tr >> nth=1', 'data-rm');
    const f1 = faltasEtapa(oferta, c1.rm);
    const f2 = faltasEtapa(oferta, rm2);
    await L.print(prof.p, ETAPA, 'chamada-clique-gravado');
    L.ok(c1.ok && c1.titulo === 'Gravado' && c1.texto === 'PRESENTE' && pres === 1 && aud === 1 && f1 === 0 && f2 >= 1 && c1.caixas === 0,
      `um clique grava na hora: o ícone "${c1.titulo}", o botão em ${c1.texto}, a presença no banco (${pres}), a auditoria com o antes e o depois (${aud}), as faltas da etapa no mesmo clique (o presente: ${f1}; o ausente: ${f2}); nenhuma caixa de mensagem`);

    /* 4. a escolha lembrada */
    const lembrada = sql(`SELECT valor FROM adms_instalacao_estado WHERE chave = 'chamada.inicio.${uid}'`);
    const e2 = await novoEncontro(prof.p, oferta, 'presentes');
    const pres2 = um(`SELECT COUNT(*) FROM rm_oferta_presencas WHERE encontro_id = ${e2.id} AND presente = 1`);
    const tot2 = um(`SELECT COUNT(*) FROM rm_oferta_presencas WHERE encontro_id = ${e2.id}`);
    L.ok(lembrada === 'ausentes' && e2.marcado === 'ausentes' && e2.id > 0 && pres2 === tot2 && tot2 > 0,
      `a escolha fica lembrada: o encontro seguinte abriu em "${e2.marcado}"; escolhido "todos presentes", ele nasce com ${pres2} de ${tot2} presentes`);

    /* 5. a falta na etapa e no boletim */
    await prof.p.goto(L.BASE + 'painel/minhas-disciplinas/listar');
    await Promise.all([prof.p.waitForNavigation(), prof.p.click(`#tabelaDisciplinas a.vc-etapas-componente[href$="escola-notas/etapas/${oferta}"]`)]);
    const etapaHoje = um(`SELECT etapa_id FROM esc_faltas_etapa WHERE oferta_id = ${oferta} AND rm = '${rm2}' AND faltas > 0 LIMIT 1`);
    const abaHoje = await prof.p.$(`#abasEtapas a[data-etapa="${etapaHoje}"]`);
    if (abaHoje && !(await abaHoje.evaluate((a) => a.classList.contains('active')))) { await Promise.all([prof.p.waitForNavigation(), abaHoje.click()]); }
    const naEtapa = await prof.p.$eval(`#tabelaEtapa tr[data-rm="${rm2}"] input[name^="faltas["]`, (e) => +e.value).catch(() => -1);
    await prof.ctx.close();
    const adm = await entrar(navegador, 'administrador', process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123');
    await adm.p.goto(L.BASE + `painel/academicos/boletim/${rm2}`);
    const linhaBol = await adm.p.$$eval('tr', (l) => { const t = l.find((x) => /Matemática/.test(x.textContent)); return t ? [...t.cells].map((c) => c.textContent.trim()) : []; });
    /* D-104: a demonstração já faz chamada na Matemática do fundamental; o boletim mostra o total do ano (as faltas de todas as etapas) */
    const daEtapa = um(`SELECT faltas FROM esc_faltas_etapa WHERE oferta_id = ${oferta} AND rm = '${rm2}' AND etapa_id = ${etapaHoje || 0}`);
    L.ok(naEtapa === daEtapa && daEtapa >= 1 && f2 >= daEtapa && linhaBol.includes(String(f2)),
      `a falta aparece na tela das etapas (${naEtapa}, a da etapa: ${daEtapa}) e no boletim do aluno, na coluna das faltas, com o total do ano ${f2} (Matemática: ${linhaBol.join(' | ')})`);

    /* 6. a etapa fechada recusa o clique: o encontro passa para um dia da etapa fechada (arranjo da prova, no banco) */
    const fechada = sql(`SELECT e.inicio FROM esc_etapas e JOIN esc_fechamentos f ON f.etapa_id = e.id AND f.oferta_id = ${oferta} AND f.situacao = 'vigente' ORDER BY e.ordem LIMIT 1`);
    sql(`UPDATE rm_oferta_encontros SET data = '${fechada}' WHERE id = ${e1.id}`);
    const antesFech = um(`SELECT presente FROM rm_oferta_presencas WHERE encontro_id = ${e1.id} AND rm = '${rm2}'`);
    await adm.p.goto(L.BASE + `painel/chamada/encontro/${oferta}?id=${e1.id}`);
    const c3 = await clicar(adm.p, 1);
    const depoisFech = um(`SELECT presente FROM rm_oferta_presencas WHERE encontro_id = ${e1.id} AND rm = '${rm2}'`);
    await L.print(adm.p, ETAPA, 'chamada-etapa-fechada');
    await adm.ctx.close();
    L.ok(fechada !== '' && c3.erro && /fechad/i.test(c3.motivo) && c3.texto === 'AUSENTE' && antesFech === depoisFech,
      `a etapa fechada recusa o clique: o alerta no botão com o motivo ("${c3.motivo.slice(0, 70)}"), o botão continua ${c3.texto}, nada muda no banco`);

    /* a barreira: a professora não grava presença no componente de outro professor (o escopo da oferta) */
    const alheia = um("SELECT o.id FROM rm_professores_materias o JOIN rm_professores_mat_liga l ON l.rm_prof_mat_id = o.id JOIN adms_usuarios u ON u.id = l.usuario_id WHERE u.usuario = 'prof.marcos.demo' AND o.ano = 2026 ORDER BY o.id LIMIT 1");
    const pb = await entrar(navegador, 'prof.laura.demo', senhaProf);
    const resp = await pb.p.evaluate(async ([u]) => {
      const x = await fetch(u, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' }, body: new URLSearchParams({ encontro: '1', rm: '1', ausente: '1' }).toString() });
      return { st: x.status, t: await x.text() };
    }, [L.BASE + `painel/chamada/presenca/${alheia}`]);
    await pb.ctx.close();
    L.ok(alheia > 0 && resp.st === 403 && /não é sua/.test(resp.t), `a professora não grava presença no componente de outro professor: ${resp.st} ("${resp.t.slice(0, 80)}")`);

    /* sabotagem: a presença sem a consolidação */
    const orig = fs.readFileSync(MODELO, 'utf8');
    const trecho = '            self::consolidar($ofertaId, $usuario);\n            $mudou = true;';
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let fSab = -1;
    let fEsperada = -1;
    try {
      fs.writeFileSync(MODELO, orig.replace(trecho, '            $mudou = true;'));
      const ps = await entrar(navegador, 'prof.laura.demo', senhaProf);
      await ps.p.goto(L.BASE + `painel/chamada/encontro/${oferta}?id=${e2.id}`);
      const rm3 = await ps.p.getAttribute('#tabelaPresenca tbody tr >> nth=2', 'data-rm');
      fEsperada = faltasEtapa(oferta, rm3) + 1;
      await clicar(ps.p, 2);
      fSab = faltasEtapa(oferta, rm3);
      await ps.ctx.close();
    } finally {
      fs.writeFileSync(MODELO, orig);
    }
    L.ok(fSab !== fEsperada, `sabotagem (o clique sem consolidar as faltas): a prova reprova — a falta não chegou à etapa (${fSab}, e devia ser ${fEsperada})`);
    L.ok(sha(MODELO) === shaModelo, 'o arquivo sabotado voltou igual (sha256)');
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(MODELO) !== shaModelo) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
