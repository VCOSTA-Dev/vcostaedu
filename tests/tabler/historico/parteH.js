/*
 * PROVA DO HISTÓRICO ESCOLAR, PARTE H — a saída no meio do ano e o ano letivo fechado (D-117). APAGA o banco de prova.
 *   1. a saída da turma com a data DIGITADA: a data fora do ano ou no futuro é recusada (o aluno fica); a data de junho
 *      fica gravada (não a hora do clique);
 *   2. o cálculo do aluno que saiu: as etapas até a saída com nota (o ano parcial legível); a que começou depois da saída
 *      fora da conta, sem nota (antes de sair, ela tinha nota);
 *   3. a vida escolar propõe "Transferido" com a data; confirmada, o histórico tem o bloco de transferência preenchido até
 *      a data (as notas das etapas cursadas);
 *   4. o ano letivo: fechar pela secretaria (a vida escolar de outro aluno deixa de propor "em curso"); reabrir sem o motivo
 *      é recusado; com o motivo, reaberto, e o histórico do marcador tem as duas ações; a direção fecha; o professor não
 *      (POST direto, o marcador não muda);
 *   sabotagem: a etapa depois da saída volta para a conta — a prova reprova; o arquivo volta igual.
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { execFileSync } = require('child_process');
const L = require('../lib');
const X = require('../onda8/_escola');

const ETAPA = 'historico/parteH';
const MEDIA = path.join(L.RAIZ, 'core', 'MediaEscola.php');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const { sql, um } = X;
const texto = async (p, sel) => ((await p.textContent(sel).catch(() => '')) || '').replace(/\s+/g, ' ').trim();
const postar = (p, url, campos) => p.evaluate(async ([u, c]) => {
  const x = await fetch(u, { method: 'POST', credentials: 'same-origin', body: new URLSearchParams(c) });
  return { st: x.status, t: await x.text() };
}, [L.BASE + 'painel/' + url, campos]);
const calc = (oferta, rm) => JSON.parse(execFileSync('php', [path.join(L.RAIZ, 'tests', 'tabler', 'historico', 'calc.php'), String(oferta), String(rm)], { encoding: 'utf8' }));
const etapa = (c, ordem) => (c && c.etapas || []).find((e) => e.ordem === ordem) || {};
const RM = '265005';
const SAIDA = '2026-06-15';

(async () => {
  const { navegador } = await L.abrir();
  const shaM = sha(MEDIA);
  try {
    L.ok(X.montar('historico-parteH').status === 0, 'a demonstração do colégio montada pela tela');
    const c = X.cred();
    const uid = um(`SELECT id FROM col_usuarios WHERE rm = '${RM}'`);
    const turma = um(`SELECT turma_id FROM esc_turma_alunos WHERE rm = '${RM}' AND saida IS NULL`);
    const oferta = um(`SELECT x.oferta_id FROM esc_turma_ofertas x JOIN rm_disciplina_atividade_notas n ON n.rm_prof_mat_id = x.oferta_id AND n.rm = '${RM}' WHERE x.turma_id = ${turma} ORDER BY x.oferta_id LIMIT 1`);
    const antes = calc(oferta, RM);
    const sec = await X.entrar(navegador, 'secretaria.demo', c.painel['secretaria.demo']);
    const p = sec.p;

    /* 1. a saída com a data digitada */
    await p.goto(L.BASE + `painel/turmas/ver/${turma}`);
    const campo = await p.$(`form.vc-sair:has(input[name="usuario_id"][value="${uid}"]) input[name="data_saida"]`);
    await L.print(p, ETAPA, 'turma');
    await postar(p, `turmas/sair/${turma}`, { usuario_id: String(uid), data_saida: '2025-12-01' });
    await postar(p, `turmas/sair/${turma}`, { usuario_id: String(uid), data_saida: '2099-01-01' });
    const ficou = um(`SELECT COUNT(*) FROM esc_turma_alunos WHERE rm = '${RM}' AND turma_id = ${turma} AND saida IS NULL`);
    await postar(p, `turmas/sair/${turma}`, { usuario_id: String(uid), data_saida: SAIDA });
    await p.goto(L.BASE + `painel/turmas/ver/${turma}`);
    const msg = await texto(p, '.alert');
    const gravada = sql(`SELECT DATE(saida) FROM esc_turma_alunos WHERE rm = '${RM}' AND turma_id = ${turma} AND saida IS NOT NULL ORDER BY id DESC LIMIT 1`);
    L.ok(!!campo && ficou === 1 && gravada === SAIDA,
      `a saída da turma com a data digitada (o campo na tela: ${!!campo}): fora do ano e no futuro, recusada (o aluno ficou: ${ficou}); a de junho gravada (${gravada}) — "${msg.slice(0, 70)}"`);

    /* 2. o cálculo do aluno que saiu */
    const depois = calc(oferta, RM);
    const e1 = etapa(depois, 1); const e2 = etapa(depois, 2); const e3 = etapa(depois, 3); const e4 = etapa(depois, 4);
    L.ok(etapa(antes, 3).nota !== null && etapa(antes, 3).nota !== undefined
      && e1.nota !== null && e2.nota !== null && e3.nota === null && e3.depois === true && e4.depois === true,
      `o ano parcial: as etapas até a saída com nota (${e1.nota}, ${e2.nota}); a 3ª, que começou depois, fora da conta (${e3.nota}; antes de sair: ${etapa(antes, 3).nota}); média ${depois && depois.media}`);

    /* 3. a vida escolar e o bloco de transferência */
    await p.goto(L.BASE + `painel/historico-escola-vida/aluno/${uid}?nivel=EF`);
    const prop = await texto(p, '#tabelaVida tr[data-ano="2026"] .vc-resultado');
    await L.print(p, ETAPA, 'vida-transferido');
    await Promise.all([p.waitForNavigation(), p.click('#tabelaVida tr[data-ano="2026"] .vc-confirmar')]);
    const serie = sql(`SELECT CONCAT(resultado, '|', IFNULL(data_saida, '')) FROM esc_hist_vida_serie WHERE aluno_id = ${uid} AND ano = 2026`);
    const r = await p.request.get(L.BASE + `painel/historico-escola-emissao/previa-pdf/${uid}?nivel=EF&formato=html`);
    const html = await r.text();
    fs.mkdirSync(path.join(L.PROVAS, ETAPA), { recursive: true });
    fs.writeFileSync(path.join(L.PROVAS, ETAPA, 'transferido.html'), html);
    const i0 = html.indexOf('TRANSFERÊNCIA DURANTE O PERÍODO LETIVO');
    const bloco = i0 >= 0 ? html.slice(i0, html.indexOf('ESCALA AVALIAT', i0)) : '';
    const notasBloco = (bloco.match(/<td class="c">\d+(,\d+)?\*?<\/td>/g) || []).length;
    L.ok(/Transferido/.test(prop) && /15\/06\/2026/.test(prop) && serie === `transferido|${SAIDA}` && bloco.includes('até <b>15/06/2026</b>') && notasBloco > 0,
      `a vida escolar propõe "${prop.slice(0, 70)}"; confirmada (${serie}); o bloco de transferência até 15/06/2026 com ${notasBloco} nota(s) das etapas cursadas`);

    /* 4. o ano letivo fechado */
    const outro = um("SELECT x.usuario_id FROM esc_turma_alunos x WHERE x.rm = '265001' AND x.saida IS NULL");
    await p.goto(L.BASE + `painel/historico-escola-vida/aluno/${outro}?nivel=EF`);
    const antesFechar = await texto(p, '#tabelaVida tr[data-ano="2026"] .vc-resultado');
    await p.goto(L.BASE + 'painel/historico-escola/ano-letivo');
    const aberto = await p.getAttribute('#anosLetivos tr[data-ano="2026"]', 'data-situacao');
    p.once('dialog', (d) => d.accept());
    await postar(p, 'historico-escola/fechar-ano', { ano: '2026' });
    await p.goto(L.BASE + 'painel/historico-escola/ano-letivo');
    const fechado = await p.getAttribute('#anosLetivos tr[data-ano="2026"]', 'data-situacao');
    await L.print(p, ETAPA, 'ano-fechado');
    await p.goto(L.BASE + `painel/historico-escola-vida/aluno/${outro}?nivel=EF`);
    const depoisFechar = await texto(p, '#tabelaVida tr[data-ano="2026"] .vc-resultado');
    L.ok(aberto === 'aberto' && fechado === 'fechado' && /Em curso/.test(antesFechar) && !/Em curso/.test(depoisFechar),
      `fechar o ano letivo de 2026 (${aberto} → ${fechado}): a vida escolar de outro aluno deixa de propor "em curso" ("${antesFechar.slice(0, 30)}" → "${depoisFechar.slice(0, 60)}")`);
    await postar(p, 'historico-escola/reabrir-ano', { ano: '2026', motivo: '' });
    const semMotivo = sql("SELECT situacao FROM esc_ano_letivo_fechamento WHERE ano = 2026");
    await postar(p, 'historico-escola/reabrir-ano', { ano: '2026', motivo: 'Correção de nota pedida pelo conselho (prova)' });
    await p.goto(L.BASE + 'painel/historico-escola/ano-letivo');
    const reaberto = await p.getAttribute('#anosLetivos tr[data-ano="2026"]', 'data-situacao');
    const hist = await texto(p, '#historicoAnoLetivo tbody');
    L.ok(semMotivo === 'fechado' && reaberto === 'aberto' && sql("SELECT GROUP_CONCAT(acao ORDER BY id) FROM esc_ano_letivo_fechamento_hist WHERE ano = 2026") === 'fechar,reabrir'
      && hist.includes('Correção de nota pedida pelo conselho (prova)'),
      `reabrir sem o motivo é recusado (${semMotivo}); com o motivo, reaberto (${reaberto}); o histórico do marcador: "${hist.slice(0, 90)}"`);
    await sec.ctx.close();
    const dir = await X.entrar(navegador, 'direcao.demo', c.painel['direcao.demo']);
    await postar(dir.p, 'historico-escola/fechar-ano', { ano: '2026' });
    const porDir = sql("SELECT CONCAT(f.situacao, '|', u.adms_niveis_acesso_id) FROM esc_ano_letivo_fechamento f JOIN adms_usuarios u ON u.id = f.por WHERE f.ano = 2026");
    await dir.ctx.close();
    const prof = await X.entrar(navegador, 'prof.laura.demo', c.painel['prof.laura.demo']);
    const rProf = await postar(prof.p, 'historico-escola/reabrir-ano', { ano: '2026', motivo: 'Tentativa do professor (prova)' });
    const rTela = await prof.p.goto(L.BASE + 'painel/historico-escola/ano-letivo');
    await prof.ctx.close();
    L.ok(porDir === 'fechado|3' && sql("SELECT situacao FROM esc_ano_letivo_fechamento WHERE ano = 2026") === 'fechado' && rTela.status() === 403,
      `a direção fecha (${porDir}); o professor não reabre por POST direto (${rProf.st}; o marcador continua fechado) nem abre a tela (${rTela.status()})`);

    /* sabotagem: a etapa depois da saída volta para a conta */
    const orig = fs.readFileSync(MEDIA, 'utf8');
    const trecho = "if (!empty($e['antesDaMatricula']) || !empty($e['depoisDaSaida'])) {";
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let sab = null;
    try {
      fs.writeFileSync(MEDIA, orig.replace(trecho, "if (!empty($e['antesDaMatricula'])) {"));
      sab = calc(oferta, RM);
    } finally {
      fs.writeFileSync(MEDIA, orig);
    }
    L.ok(sab && etapa(sab, 3).nota !== null, `sabotagem (a etapa depois da saída na conta): a 3ª etapa ganha nota (${sab && etapa(sab, 3).nota}) — a prova reprova`);
    L.ok(sha(MEDIA) === shaM, 'o arquivo sabotado voltou igual (sha256)');
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(MEDIA) !== shaM) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
