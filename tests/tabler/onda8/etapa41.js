/*
 * PROVA DA CORREÇÃO DA ESCOLA, PARTE 2 — lançar a nota e a chamada (D-101; os achados B1, B2, B8, B9, B12, B14 e D3 do
 * teste pelo navegador de 22/11). APAGA o banco de prova.
 *   1. a demonstração do colégio; um professor de demonstração e uma disciplina dele com uma atividade aberta;
 *   2. B1: com o servidor de propósito lento (1,5 s por nota), "4", Enter, "11", Enter: o cursor desce na hora, o "11"
 *      fica no segundo campo (nunca "411" no primeiro), e o sinal de gravado aparece no primeiro quando o servidor
 *      responde; digitar rápido em vários campos grava cada nota no aluno dela;
 *   3. B2: a nota fora da escala ("11") segura o cursor no campo, com o motivo, e nada é mandado;
 *   4. B8/D3: na disciplina com lançamento por etapa e sem falta lançada, o atalho "Chamada" de "O meu dia" leva à
 *      chamada, que explica e oferece a troca num passo (pela janela do sistema) — e depois dela há "Novo encontro"; na
 *      disciplina com falta já lançada, o atalho é "Faltas" e leva à tela das etapas;
 *   5. B9: com falta lançada só por etapa, a troca para a chamada é recusada, com o motivo (a chamada recalcularia a
 *      falta de cada etapa pelas aulas);
 *   6. B14: a chamada num domingo avisa ao lado da data e pede a confirmação; "Voltar" não grava; num dia de semana grava
 *      sem perguntar;
 *   7. B12: enturmar um aluno: no fim, a janela mostra o concluído em todos os grupos, sem nada girando;
 *   sabotagem: o Enter volta a esperar o servidor para descer — a prova acusa ("411"); o arquivo volta igual.
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const L = require('../lib');
const X = require('./_escola');

const ETAPA = 'onda8/etapa41';
const TELA = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Views', 'atividades', 'visAtividades.php');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const { sql, um } = X;
const postar = (p, url, campos) => p.evaluate(async ([u, c]) => {
  const x = await fetch(u, { method: 'POST', credentials: 'same-origin', body: new URLSearchParams(c) });
  return { st: x.status, t: await x.text() };
}, [L.BASE + 'painel/' + url, campos]);
const lento = async (p, ms) => p.route('**/atividades/edit-notas**', async (r) => { await new Promise((ok) => setTimeout(ok, ms)); await r.continue(); });

/* "4", Enter, "11", Enter, rápido como uma pessoa digita, a partir do primeiro campo */
async function digitarRapido(p) {
  const campos = await p.$$('#tabelaLancaNotas input.vc-nota');
  await campos[0].click();
  await campos[0].fill('');
  await p.keyboard.type('4', { delay: 30 });
  await p.keyboard.press('Enter');
  await p.keyboard.type('11', { delay: 30 });
  await p.keyboard.press('Enter');
  const v = await p.$$eval('#tabelaLancaNotas input.vc-nota', (l) => l.slice(0, 2).map((i) => i.value));
  const foco = await p.evaluate(() => { const l = Array.from(document.querySelectorAll('#tabelaLancaNotas input.vc-nota')); return l.indexOf(document.activeElement); });
  return { v, foco };
}

(async () => {
  const { navegador } = await L.abrir();
  const shaTela = sha(TELA);
  try {
    L.ok(X.montar('onda8-etapa41').status === 0, 'a demonstração do colégio montada pela tela, numa instalação nova');
    const c = X.cred();
    const ano = new Date().getFullYear();
    /* um professor e as disciplinas dele com o lançamento por etapa, no ano corrente */
    const [profU, ofTxt] = sql(`SELECT CONCAT(u.usuario, '|', GROUP_CONCAT(l.rm_prof_mat_id ORDER BY l.rm_prof_mat_id)) FROM rm_professores_mat_liga l INNER JOIN adms_usuarios u ON u.id = l.usuario_id
      INNER JOIN esc_turma_ofertas x ON x.oferta_id = l.rm_prof_mat_id INNER JOIN rm_professores_materias o ON o.id = l.rm_prof_mat_id
      WHERE u.usuario LIKE 'prof.%.demo' AND l.situacao = 1 AND o.ano = ${ano} AND o.encerrada_em IS NULL AND IFNULL(o.frequencia_fonte, 'lancamento') = 'lancamento'
      GROUP BY u.usuario HAVING COUNT(*) >= 2 ORDER BY u.usuario LIMIT 1`).split('|');
    const ofertas = ofTxt.split(',').map(Number);
    const [ofA, ofB] = ofertas;
    const etapa = um(`SELECT e.id FROM esc_etapas e WHERE e.ano = ${ano} AND NOT EXISTS (SELECT 1 FROM esc_fechamentos f WHERE f.oferta_id = ${ofA} AND f.etapa_id = e.id AND f.situacao = 'vigente') ORDER BY e.ordem LIMIT 1`);
    const inicio = sql(`SELECT inicio FROM esc_etapas WHERE id = ${etapa}`);
    sql(`INSERT INTO rm_disciplina_atividade (nomeAtividade, tipo, entrega_arquivo, rm_prof_mat_id, situacao, usuario_cre, data_inicial, data_final, peso) VALUES ('Prova rapida (prova 41)', 1, 0, ${ofA}, 1, 1, '${inicio}', '${inicio}', 10)`);
    const ativ = um('SELECT MAX(id) FROM rm_disciplina_atividade');
    sql(`INSERT INTO rm_disciplina_atividade_notas (ordenacao, rm, nome_aluno, atividade_id, rm_prof_mat_id, tipo, nota, alteracao, situacao, usuario_cre)
      SELECT d.ordem, d.rm, d.nome_aluno, ${ativ}, ${ofA}, 1, NULL, 1, 1, 1 FROM portal_disciplinas_alunos d WHERE d.rm_prof_mat_id = ${ofA}`);
    const ids = sql(`SELECT id FROM rm_disciplina_atividade_notas WHERE atividade_id = ${ativ} ORDER BY ordenacao, id`).split('\n').map(Number);
    L.ok(ofertas.length >= 2 && etapa > 0 && ids.length >= 3, `as disciplinas ${ofertas.join(', ')} de ${profU} (lançamento por etapa); a atividade ${ativ}, com ${ids.length} alunos`);

    const prof = await X.entrar(navegador, profU, c.painel[profU], 'painel', 1366);
    const p = prof.p;
    let janelas = 0;
    p.on('dialog', () => { janelas++; });

    /* 2 e 3. B1 e B2, com o servidor lento */
    await lento(p, 1500);
    await p.goto(L.BASE + `painel/atividades/visualizar/${ativ}`);
    await L.semErros(p, 'as notas da atividade');
    const r1 = await digitarRapido(p);
    const motivo = (await p.textContent('#tabelaLancaNotas tbody tr:nth-child(2) .vc-sinal-motivo').catch(() => '')).trim();
    await p.waitForSelector('#tabelaLancaNotas tbody tr:nth-child(1) .vc-sinal-ok', { timeout: 8000 }).catch(() => {});
    const okNoPrimeiro = !!(await p.$('#tabelaLancaNotas tbody tr:nth-child(1) .vc-sinal-ok'));
    await p.waitForTimeout(300);
    const n1 = sql(`SELECT IFNULL(nota, '') FROM rm_disciplina_atividade_notas WHERE id = ${ids[0]}`);
    const n2 = sql(`SELECT IFNULL(nota, '') FROM rm_disciplina_atividade_notas WHERE id = ${ids[1]}`);
    L.ok(r1.v[0] === '4' && r1.v[1] === '11' && okNoPrimeiro && n1 === '4.0',
      `B1: com o servidor lento, "4", Enter, "11", Enter: o primeiro campo ficou "${r1.v[0]}", o segundo "${r1.v[1]}" (nunca "411"); o sinal de gravado apareceu no primeiro quando o servidor respondeu (gravou ${n1})`);
    L.ok(r1.foco === 1 && /de 0 a 10/.test(motivo) && n2 === '',
      `B2: a nota fora da escala segura o cursor no campo (o cursor no campo ${r1.foco + 1}), com o motivo ("${motivo.slice(0, 60)}"), e nada foi mandado (gravado: "${n2}")`);
    /* digitar rápido em vários campos: cada nota no aluno dela */
    await p.fill('#tabelaLancaNotas tbody tr:nth-child(2) input.vc-nota', '');
    await p.keyboard.type('7', { delay: 20 });
    await p.keyboard.press('Enter');
    await p.keyboard.type('8,5', { delay: 20 });
    await p.keyboard.press('Enter');
    await p.waitForTimeout(4500);
    const tres = [ids[0], ids[1], ids[2]].map((i) => sql(`SELECT IFNULL(nota, '') FROM rm_disciplina_atividade_notas WHERE id = ${i}`));
    L.ok(tres.join('|') === '4.0|7.0|8.5' && janelas === 0, `digitar rápido em três campos grava cada nota no aluno dela (${tres.join(', ')}), com o servidor lento`);
    await L.print(p, ETAPA, 'notas-rapidas');
    await p.unroute('**/atividades/edit-notas**');

    /* 4. B8/D3: a disciplina sem falta lançada (zerada aqui) e a com falta */
    sql(`DELETE FROM esc_faltas_etapa WHERE oferta_id = ${ofB}`);
    sql(`UPDATE rm_disciplina_faltas SET faltas = 0 WHERE rm_prof_mat_id = ${ofB}`);
    const comFalta = um(`SELECT COUNT(*) FROM esc_faltas_etapa WHERE oferta_id = ${ofA} AND faltas > 0`);
    if (!comFalta) {
      const ra = sql(`SELECT rm FROM portal_disciplinas_alunos WHERE rm_prof_mat_id = ${ofA} ORDER BY rm LIMIT 1`);
      await postar(p, `escola-notas/faltas/${ofA}`, { etapa_id: String(etapa), [`faltas[${ra}]`]: '2' });
    }
    await p.goto(L.BASE + 'painel/home/index');
    const linha = async (of) => p.$eval(`#diaProfessor tr[data-oferta="${of}"]`, (tr) => ({ chamada: !!tr.querySelector('.vc-dia-chamada'), faltas: tr.querySelector('.vc-dia-faltas')?.getAttribute('href') || '' })).catch(() => ({}));
    const la = await linha(ofA);
    const lb = await linha(ofB);
    await L.print(p, ETAPA, 'o-meu-dia');
    L.ok(!la.chamada && /escola-notas\/etapas\//.test(la.faltas) && lb.chamada && !lb.faltas,
      `D3: em "O meu dia", a disciplina com falta lançada mostra "Faltas" (para as etapas) e não "Chamada"; a sem falta lançada mostra "Chamada"`);
    await Promise.all([p.waitForNavigation(), p.click(`#diaProfessor tr[data-oferta="${ofB}"] .vc-dia-chamada`)]);
    await L.semErros(p, 'a chamada da disciplina com lançamento');
    const cartao = (await p.textContent('#chamadaLancamento').catch(() => '')).replace(/\s+/g, ' ');
    const semNovoDesligado = !(await p.$('#novoEncontro'));
    await p.click('#chamadaLancamento form button');
    await p.waitForSelector('#vcConfirmar.show', { timeout: 8000 });
    await Promise.all([p.waitForNavigation(), p.click('#vcConfirmarSim')]);
    const virou = sql(`SELECT frequencia_fonte FROM rm_professores_materias WHERE id = ${ofB}`) === 'chamada' && !!(await p.$('#novoEncontro')) && !(await p.$('#chamadaLancamento'));
    L.ok(/lança as faltas por etapa/.test(cartao) && semNovoDesligado && virou && janelas === 0,
      'B8: o atalho leva à chamada, que explica ("lança as faltas por etapa") e oferece a troca num passo, pela janela do sistema; depois dela, "Novo encontro"');
    await L.print(p, ETAPA, 'chamada-depois-da-troca');
    await p.goto(L.BASE + `painel/chamada/listar/${ofA}`);
    const semTroca = (await p.textContent('#semTrocaChamada').catch(() => '')).trim();
    const leva = await p.getAttribute('#irFaltasEtapa', 'href').catch(() => '');
    L.ok(/há falta lançada na etapa/.test(semTroca) && /escola-notas\/etapas\//.test(leva), `na disciplina com falta lançada, a chamada diz por que não troca e leva às faltas da etapa ("${semTroca.slice(0, 70)}…")`);

    /* 5. B9: a falta lançada só por etapa (o total zerado de propósito) também trava a troca */
    sql(`UPDATE rm_disciplina_faltas SET faltas = 0 WHERE rm_prof_mat_id = ${ofA}`);
    const tr = await postar(p, `chamada/fonte/${ofA}`, { fonte: 'chamada' });
    const recusa = ((tr.t.match(/id='fonteRecusada'>([\s\S]*?)<\/div>/) || [])[1] || '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ');
    L.ok(sql(`SELECT IFNULL(frequencia_fonte, 'lancamento') FROM rm_professores_materias WHERE id = ${ofA}`) === 'lancamento' && /recalcula a falta de cada etapa/.test(recusa),
      `B9: com a falta lançada por etapa (e o total zerado), a troca para a chamada é recusada, com o motivo ("${recusa.trim().slice(0, 90)}…")`);

    /* 6. B14: o domingo */
    const fim = sql(`SELECT fim FROM esc_etapas WHERE id = ${etapa}`);
    const d0 = new Date(inicio + 'T12:00:00');
    while (d0.getDay() !== 0) { d0.setDate(d0.getDate() + 1); }
    const domingo = X.ymd(d0);
    const seg = new Date(d0.getTime() + 86400000);
    const segunda = X.ymd(seg);
    await p.goto(L.BASE + `painel/chamada/encontro/${ofB}`);
    await p.fill('#data', domingo);
    await p.dispatchEvent('#data', 'change');
    const avisoDom = (await p.textContent('#avisoFimDeSemana').catch(() => '')).trim();
    const visivel = await p.isVisible('#avisoFimDeSemana');
    await p.click('#salvarChamada');
    await p.waitForSelector('#vcConfirmar.show', { timeout: 8000 });
    const pergunta = (await p.textContent('#vcConfirmarTitulo')).trim();
    await p.click('#vcConfirmarNao');
    await p.waitForTimeout(600);
    const naoGravou = um(`SELECT COUNT(*) FROM rm_oferta_encontros WHERE rm_prof_mat_id = ${ofB} AND data = '${domingo}'`) === 0;
    await p.fill('#data', segunda);
    await p.dispatchEvent('#data', 'change');
    await Promise.all([p.waitForNavigation(), p.click('#salvarChamada')]);
    const gravouSeg = um(`SELECT COUNT(*) FROM rm_oferta_encontros WHERE rm_prof_mat_id = ${ofB} AND data = '${segunda}'`) === 1;
    L.ok(visivel && /domingo/.test(avisoDom) && /domingo/.test(pergunta) && naoGravou && gravouSeg && janelas === 0 && segunda <= fim,
      `B14: a chamada num domingo (${domingo}) avisa ("${avisoDom}") e pergunta ("${pergunta}"); "Voltar" não grava; a segunda-feira grava sem perguntar`);
    await prof.ctx.close();

    /* 7. B12: enturmar, e a janela termina sem nada girando */
    const sec = await X.entrar(navegador, 'secretaria.demo', c.painel['secretaria.demo'], 'painel', 1366);
    /* desde a D-102 (B13), o candidato é só da série e do ano da turma, e a demonstração já põe cada aluno numa turma: um
       aluno sai da turma dele (a saída registrada, como na tela), para voltar a ela pela tela */
    const [saiId, saiTurma] = sql(`SELECT x.id, x.turma_id FROM esc_turma_alunos x JOIN esc_turmas t ON t.id = x.turma_id WHERE t.ano = ${ano} AND x.saida IS NULL ORDER BY x.id LIMIT 1`).split('\n')[0].split('\t').map(Number);
    if (saiId) { sql(`UPDATE esc_turma_alunos SET saida = NOW() WHERE id = ${saiId}`); }
    const turmas = [saiTurma, ...sql(`SELECT id FROM esc_turmas WHERE ano = ${ano} ORDER BY id`).split('\n').map(Number).filter((t) => t !== saiTurma)];
    let enturmou = null;
    for (const t of turmas) {
      await sec.p.goto(L.BASE + `painel/turmas/ver/${t}`);
      const cand = await sec.p.$('.vc-candidato');
      if (!cand) { continue; }
      await cand.check();
      await sec.p.click('#enturmarMarcados');
      await sec.p.waitForSelector('#progressoTurma .vc-progresso-pronto:not([hidden])', { timeout: 60000 });
      await sec.p.waitForTimeout(400);
      enturmou = await sec.p.$eval('#progressoTurma', (el) => ({ girando: el.querySelectorAll('.spinner-border').length, feitos: el.querySelectorAll('[data-grupo].feito').length, grupos: el.querySelectorAll('[data-grupo]').length }));
      await L.print(sec.p, ETAPA, 'enturmar-pronto');
      break;
    }
    L.ok(enturmou && enturmou.girando === 0 && enturmou.feitos === enturmou.grupos,
      `B12: enturmar — no "Pronto", ${enturmou ? enturmou.feitos + ' de ' + enturmou.grupos + ' grupo(s) concluído(s) e ' + enturmou.girando + ' girando' : 'nenhuma turma com candidato'}`);
    await sec.ctx.close();

    /* sabotagem: o Enter volta a esperar o servidor */
    const orig = fs.readFileSync(TELA, 'utf8');
    const trecho = "      abrir(proximo(inp)); /* B1: desce na hora, sem esperar o servidor */\n";
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let sab = null;
    try {
      fs.writeFileSync(TELA, orig.replace(trecho, ''));
      const p2 = await X.entrar(navegador, profU, c.painel[profU], 'painel', 1366);
      sql(`UPDATE rm_disciplina_atividade_notas SET nota = NULL WHERE atividade_id = ${ativ}`);
      await lento(p2.p, 1500);
      await p2.p.goto(L.BASE + `painel/atividades/visualizar/${ativ}`);
      sab = await digitarRapido(p2.p);
      await p2.ctx.close();
    } finally {
      fs.writeFileSync(TELA, orig);
    }
    L.ok(sab && sab.v[0] !== '4', `sabotagem (o Enter sem descer na hora): a prova reprova — o primeiro campo ficou "${sab ? sab.v[0] : '?'}"`);
    L.ok(sha(TELA) === shaTela, 'o arquivo sabotado voltou igual (sha256)');
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(TELA) !== shaTela) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
