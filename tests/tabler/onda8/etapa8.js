/*
 * PROVA DA ONDA 8, CORREÇÃO, PARTE 2.1 — a nota com Enter (D-74). APAGA o banco de prova.
 *   1. a demonstração do colégio, montada pela tela numa instalação nova;
 *   2. a professora, pela tela (Minhas disciplinas → Atividades → a avaliação da etapa aberta): digita a nota no
 *      primeiro aluno e aperta Enter — a nota fica gravada, sem botão de salvar; o ícone pequeno "Gravado" aparece ao
 *      lado e some sozinho; o cursor já está no campo do próximo aluno, com o texto selecionado; nenhuma caixa de
 *      mensagem; a auditoria registra o antes e o depois;
 *   3. a nota fora da escala: o alerta fica no campo, com o motivo; o que foi digitado continua lá; o cursor fica;
 *      nada é gravado; o alerta continua depois de alguns segundos; corrigida, grava e o alerta some;
 *   4. sair do campo com o valor mudado (Tab) também grava;
 *   5. a planilha continua: "Atualizar via Planilha" está na tela;
 *   6. na escola, a recuperação da etapa grava com Enter e a nota da etapa da linha se atualiza;
 *   sabotagem: o Enter que grava mas não leva ao próximo aluno — a prova acusa; o arquivo volta igual (sha256).
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { spawnSync, execFileSync } = require('child_process');
const L = require('../lib');

const ETAPA = 'onda8/etapa8';
const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste$/.test(BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
const VIEW = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Views', 'atividades', 'visAtividades.php');

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
/** Da Página Inicial até a avaliação da etapa aberta de Matemática, só clicando. */
async function atividade(p) {
  await p.goto(L.BASE + 'painel/home/index');
  await Promise.all([p.waitForNavigation(), p.click('a[href*="minhas-disciplinas/listar"] >> nth=0')]);
  await Promise.all([p.waitForNavigation(), p.click('#tabelaDisciplinas tbody tr:has(a:text-is("Matemática")) a[href*="atividades/listar/"] >> nth=0')]);
  await Promise.all([p.waitForNavigation(), p.click('a[href*="atividades/visualizar/"]:has-text("3º bimestre") >> nth=0')]);
  await p.waitForSelector('#tabelaLancaNotas input.vc-nota');
}
/** Digita no campo i, aperta a tecla, e espera o sinal. */
async function digitar(p, i, valor, tecla = 'Enter') {
  const sel = `#tabelaLancaNotas input.vc-nota >> nth=${i}`;
  await p.click(sel);
  await p.fill(sel, valor);
  await p.press(sel, tecla);
  await p.waitForFunction((n) => { const e = document.querySelectorAll('#tabelaLancaNotas input.vc-nota')[n]; const s = e && e.nextElementSibling; return e.dataset.enviando !== '1' && s && s.classList.contains('vc-sinal') && s.querySelector('.vc-sinal-ok, .vc-sinal-erro'); }, i, /* a resposta da gravação desta tecla (o alerta de antes não conta) */ { timeout: 15000 });
  return p.evaluate((n) => {
    const l = [...document.querySelectorAll('#tabelaLancaNotas input.vc-nota')];
    const e = l[n];
    const s = e.nextElementSibling;
    const a = document.activeElement;
    const ok = s.querySelector('.vc-sinal-ok');
    return { id: e.dataset.id, valor: e.value, ok: !!ok, titulo: ok ? ok.title : '', erro: !!s.querySelector('.vc-sinal-erro'), motivo: (s.querySelector('.vc-sinal-motivo') || { textContent: '' }).textContent,
      foco: l.indexOf(a), selecionado: a && a.selectionStart === 0 && a.selectionEnd === a.value.length,
      caixas: document.querySelectorAll('.alert-success, .alert-danger, .modal.show, .toast').length };
  }, i);
}
const notaDe = (id) => sql(`SELECT IFNULL(nota, '') FROM rm_disciplina_atividade_notas WHERE id = ${+id}`);

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
  const shaView = sha(VIEW);
  try {
    const r = montar('onda8-etapa8');
    L.ok(r.status === 0, 'a demonstração do colégio montada pela tela, numa instalação nova');
    const cred = JSON.parse(sql("SELECT valor FROM adms_instalacao_estado WHERE chave = 'demonstracao.credenciais'"));
    const senhaProf = (cred.painel || {})['prof.laura.demo'] || '';

    /* 2. Enter grava e leva ao próximo */
    const prof = await entrar(navegador, 'prof.laura.demo', senhaProf);
    await atividade(prof.p);
    const planilha = !!(await prof.p.$('a[href*="atividades/upload-notas/"]'));
    const a1 = await digitar(prof.p, 0, '8,5');
    const gravada = notaDe(a1.id);
    const aud = um(`SELECT COUNT(*) FROM rm_auditoria_lancamentos WHERE tipo = 'nota' AND campo = 'nota' AND valor_novo IN ('8.5', '8,5', '8.50') AND rm = (SELECT rm FROM rm_disciplina_atividade_notas WHERE id = ${+a1.id}) AND usuario_nome LIKE '%Laura%'`);
    await L.print(prof.p, ETAPA, 'nota-gravada');
    await prof.p.waitForTimeout(4000);
    const sumiu = await prof.p.evaluate(() => !document.querySelector('#tabelaLancaNotas input.vc-nota').nextElementSibling.querySelector('.vc-sinal-ok'));
    L.ok(a1.ok && a1.titulo === 'Gravado' && /^8[.,]5/.test(gravada) && a1.foco === 1 && a1.selecionado && a1.caixas === 0 && sumiu && aud >= 1,
      `Enter grava a nota sem botão (gravada: ${gravada}); o ícone "${a1.titulo}" ao lado some sozinho (${sumiu}); o cursor no próximo aluno, com o texto selecionado (campo ${a1.foco}); nenhuma caixa de mensagem (${a1.caixas}); a auditoria registra (${aud})`);

    /* 3. a recusa fica no campo */
    const antes2 = notaDe((await prof.p.$$eval('#tabelaLancaNotas input.vc-nota', (l) => l[1].dataset.id)));
    const a2 = await digitar(prof.p, 1, '11');
    await prof.p.waitForTimeout(3500);
    const ficou = await prof.p.evaluate(() => !!document.querySelectorAll('#tabelaLancaNotas input.vc-nota')[1].nextElementSibling.querySelector('.vc-sinal-erro'));
    const depois2 = notaDe(a2.id);
    await L.print(prof.p, ETAPA, 'nota-recusada');
    L.ok(a2.erro && /Nota inválida/.test(a2.motivo) && a2.valor === '11' && a2.foco === 1 && ficou && depois2 === antes2 && a2.caixas === 0,
      `a nota fora da escala: o alerta no campo com o motivo ("${a2.motivo.slice(0, 60)}"), o "11" digitado continua lá, o cursor fica (campo ${a2.foco}), nada gravado (${antes2 || 'vazio'} → ${depois2 || 'vazio'}), o alerta continua (${ficou}), sem caixa de mensagem`);
    const a3 = await digitar(prof.p, 1, '6');
    L.ok(a3.ok && !a3.erro && /^6/.test(notaDe(a3.id)) && a3.foco === 2, `corrigida, a nota grava e o alerta some (${notaDe(a3.id)}); o cursor vai ao próximo`);

    /* 4. Tab também grava */
    const a4 = await digitar(prof.p, 2, '7,25', 'Tab');
    L.ok(a4.ok && /^7[.,]25/.test(notaDe(a4.id)), `sair do campo com o valor mudado (Tab) também grava (${notaDe(a4.id)})`);
    L.ok(planilha, 'a planilha continua: "Atualizar via Planilha" está na tela da atividade');

    /* 6. a recuperação com Enter, na escola */
    const oferta = um("SELECT o.id FROM rm_professores_materias o JOIN esc_turma_ofertas x ON x.oferta_id = o.id JOIN esc_turmas t ON t.id = x.turma_id WHERE o.materia LIKE 'Matem_tica' AND t.ano = 2026 AND t.nome = 'A' ORDER BY o.id LIMIT 1");
    await emRecuperacao(prof.p, oferta);
    await prof.p.goto(L.BASE + 'painel/minhas-disciplinas/listar');
    await Promise.all([prof.p.waitForNavigation(), prof.p.click(`#tabelaDisciplinas a.vc-etapas-componente[href$="escola-notas/etapas/${oferta}"]`)]);
    let rec = { ok: false };
    let etapaRec = 0;
    for (const aba of await prof.p.$$eval('#abasEtapas a[data-fechada="0"]', (l) => l.map((a) => a.dataset.etapa))) {
      await Promise.all([prof.p.waitForNavigation(), prof.p.click(`#abasEtapas a[data-etapa="${aba}"]`)]);
      if (await prof.p.$('#tabelaEtapa input.vc-rec')) { etapaRec = +aba; break; }
    }
    if (etapaRec) {
      const rm = await prof.p.$eval('#tabelaEtapa input.vc-rec', (e) => e.dataset.rm);
      const antesNota = await prof.p.$eval(`#tabelaEtapa tr[data-rm="${rm}"] .vc-nota-etapa`, (e) => e.textContent.trim());
      await prof.p.fill('#tabelaEtapa input.vc-rec >> nth=0', '9,0');
      await prof.p.press('#tabelaEtapa input.vc-rec >> nth=0', 'Enter');
      await prof.p.waitForFunction(() => { const s = document.querySelector('#tabelaEtapa input.vc-rec').nextElementSibling; return s && s.querySelector('.vc-sinal-ok, .vc-sinal-erro'); }, null, { timeout: 15000 });
      const ok = await prof.p.evaluate(() => !!document.querySelector('#tabelaEtapa input.vc-rec').nextElementSibling.querySelector('.vc-sinal-ok'));
      const depoisNota = await prof.p.$eval(`#tabelaEtapa tr[data-rm="${rm}"] .vc-nota-etapa`, (e) => e.textContent.trim());
      const g = um(`SELECT COUNT(*) FROM esc_recuperacoes WHERE oferta_id = ${oferta} AND etapa_id = ${etapaRec} AND rm = '${rm}' AND nota = 9`);
      rec = { ok: ok && g === 1, rm, antesNota, depoisNota };
      await L.print(prof.p, ETAPA, 'recuperacao-enter');
    }
    await prof.ctx.close();
    L.ok(rec.ok && rec.depoisNota !== rec.antesNota, `na escola, a recuperação grava com Enter (aluno ${rec.rm || '—'}) e a nota da etapa da linha se atualiza (${rec.antesNota || '—'} → ${rec.depoisNota || '—'})`);

    /* sabotagem: Enter grava, mas não leva ao próximo */
    const orig = fs.readFileSync(VIEW, 'utf8');
    /* D-101 (B1): o Enter desce na hora (antes, depois da resposta) — a sabotagem tira o descer */
    const trecho = '      abrir(proximo(inp)); /* B1: desce na hora, sem esperar o servidor */\n';
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let s = null;
    try {
      fs.writeFileSync(VIEW, orig.replace(trecho, ''));
      const ps = await entrar(navegador, 'prof.laura.demo', senhaProf);
      await atividade(ps.p);
      s = await digitar(ps.p, 3, '5,5');
      await ps.ctx.close();
    } finally {
      fs.writeFileSync(VIEW, orig);
    }
    L.ok(s && s.foco !== 4, `sabotagem (o Enter que não leva ao próximo aluno): a prova reprova — o cursor ficou no campo ${s ? s.foco : '—'}`);
    L.ok(sha(VIEW) === shaView, 'o arquivo sabotado voltou igual (sha256)');
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(VIEW) !== shaView) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
