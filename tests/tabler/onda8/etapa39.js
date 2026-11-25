/*
 * PROVA DA CORREÇÃO DA ESCOLA, PARTE 1 — a etapa: fechar, gravar, travar, reabrir; o relatório de reaberturas; o conselho
 * de classe (D-100; os achados B3, B4, B5, B6, B10, B15, C5 e C6 do teste pelo navegador de 22/11). APAGA o banco de prova.
 *   1. a demonstração do colégio; uma disciplina de um professor de demonstração no ano corrente, com a etapa aberta mais
 *      antiga; uma atividade da etapa com um aluno abaixo da média (fica em recuperação);
 *   2. a recuperação e as faltas por um botão só ("Gravar"): as duas gravam juntas; com a recuperação inválida, nada se
 *      perde — as faltas digitadas voltam aos campos, com o motivo (B3);
 *   3. o professor fecha a etapa da disciplina dele, pela janela de confirmação do sistema (nenhuma janela do navegador)
 *      (B5); a etapa fechada aparece "fechada" na aba; a nota, a falta e a recuperação ficam travadas, com o aviso no alto,
 *      na tela da etapa e na tela de notas da atividade (B6); o servidor continua recusando; o texto concorda ("O 3º
 *      bimestre está fechado") e "Feche antes o …" (C6);
 *   4. o professor não reabre (nem o botão, nem o endereço); a direção acadêmica reabre, com o motivo; depois da
 *      reabertura a supervisão de secretaria lança a recuperação (B4);
 *   5. o relatório de reaberturas mostra a reabertura da etapa com o motivo, para a supervisão, sem nome de tabela nem
 *      código interno (B10, C5);
 *   6. o conselho de classe abre na etapa em andamento; a observação é corrigida pela direção e retirada pela autora; quem
 *      não é a autora nem a direção não muda; o histórico guarda o texto de antes (B15);
 *   sabotagem: a trava da etapa desligada (AdmsEtapaNotas::trava devolve null) — a prova acusa; o arquivo volta igual.
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const L = require('../lib');
const X = require('./_escola');

const ETAPA = 'onda8/etapa39';
const MODELO = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'escola', 'AdmsEtapaNotas.php');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const { sql, um } = X;
const postar = (p, url, campos) => p.evaluate(async ([u, c]) => {
  const x = await fetch(u, { method: 'POST', credentials: 'same-origin', body: new URLSearchParams(c) });
  return { st: x.status, t: await x.text() };
}, [L.BASE + 'painel/' + url, campos]);
const aviso = async (p) => ((await p.textContent('.alert-success, .alert-danger, .alert-info').catch(() => '')) || '').replace(/\s+/g, ' ').trim();
/* confirma a janela do sistema (a do navegador não pode abrir) */
const clicar = (p, sel) => p.click(sel, { timeout: 10000 }).catch(async (e) => { await L.print(p, ETAPA, 'falha-clique').catch(() => {}); throw new Error(`o clique em ${sel} (${p.url().replace(/^https?:\/\/[^/]+/, '')}): ${e.message.split('\n')[0]}`); });
const confirmar = async (p, seletor) => {
  await clicar(p, seletor);
  await p.waitForSelector('#vcConfirmar.show', { timeout: 8000 });
  const texto = (await p.textContent('#vcConfirmar .modal-body')).replace(/\s+/g, ' ').trim();
  await Promise.all([p.waitForNavigation(), p.click('#vcConfirmarSim')]);
  return texto;
};

(async () => {
  const { navegador } = await L.abrir();
  const shaModelo = sha(MODELO);
  try {
    L.ok(X.montar('onda8-etapa39').status === 0, 'a demonstração do colégio montada pela tela, numa instalação nova');
    const c = X.cred();
    const senha = (u) => c.painel[u];
    const ano = new Date().getFullYear();
    /* um professor de demonstração e uma disciplina dele no ano corrente, com as faltas lançadas por etapa */
    const [profU, ofTxt] = sql(`SELECT CONCAT(u.usuario, '|', l.rm_prof_mat_id) FROM rm_professores_mat_liga l INNER JOIN adms_usuarios u ON u.id = l.usuario_id INNER JOIN esc_turma_ofertas x ON x.oferta_id = l.rm_prof_mat_id
      INNER JOIN rm_professores_materias o ON o.id = l.rm_prof_mat_id WHERE u.usuario LIKE 'prof.%.demo' AND l.situacao = 1 AND o.ano = ${ano} AND o.encerrada_em IS NULL
      AND IFNULL(o.frequencia_fonte, 'lancamento') = 'lancamento' ORDER BY u.usuario, l.rm_prof_mat_id LIMIT 1`).split('|');
    const of = +ofTxt;
    const etapa = um(`SELECT e.id FROM esc_etapas e WHERE e.ano = ${ano} AND NOT EXISTS (SELECT 1 FROM esc_fechamentos f WHERE f.oferta_id = ${of} AND f.etapa_id = e.id AND f.situacao = 'vigente') ORDER BY e.ordem LIMIT 1`);
    const [nomeEtapa, inicio] = sql(`SELECT CONCAT(nome, '|', inicio) FROM esc_etapas WHERE id = ${etapa}`).split('|');
    const depois = um(`SELECT id FROM esc_etapas WHERE ano = ${ano} AND ordem = (SELECT ordem + 1 FROM esc_etapas WHERE id = ${etapa})`);
    const alunos = sql(`SELECT rm FROM portal_disciplinas_alunos WHERE rm_prof_mat_id = ${of} ORDER BY rm`).split('\n');
    L.ok(of > 0 && etapa > 0 && alunos.length >= 3, `a disciplina ${of} do professor de demonstração, no ${nomeEtapa} (aberto), com ${alunos.length} alunos`);
    /* a atividade da etapa: o primeiro aluno abaixo da média (em recuperação), os outros com 8 */
    sql(`INSERT INTO rm_disciplina_atividade (nomeAtividade, tipo, entrega_arquivo, rm_prof_mat_id, situacao, usuario_cre, data_inicial, data_final, peso) VALUES ('Prova da etapa (prova 39)', 1, 0, ${of}, 1, 1, '${inicio}', '${inicio}', 10)`);
    const ativ = um('SELECT MAX(id) FROM rm_disciplina_atividade');
    sql(`DELETE FROM rm_disciplina_atividade_notas WHERE rm_prof_mat_id = ${of} AND atividade_id IN (SELECT id FROM rm_disciplina_atividade WHERE rm_prof_mat_id = ${of} AND IFNULL(data_final, data_inicial) BETWEEN (SELECT inicio FROM esc_etapas WHERE id = ${etapa}) AND (SELECT fim FROM esc_etapas WHERE id = ${etapa}) AND id <> ${ativ})`);
    /* a nota como a tela de lançar grava (a linha do aluno da turma, ativa) */
    alunos.forEach((ra, i) => sql(`INSERT INTO rm_disciplina_atividade_notas (ordenacao, rm, nome_aluno, atividade_id, rm_prof_mat_id, tipo, nota, alteracao, situacao, usuario_cre)
      SELECT d.ordem, d.rm, d.nome_aluno, ${ativ}, ${of}, 1, '${i === 0 ? '4.0' : '8.0'}', 1, 1, 1 FROM portal_disciplinas_alunos d WHERE d.rm_prof_mat_id = ${of} AND d.rm = '${ra}' LIMIT 1`));
    const [ra0, ra1, ra2] = alunos;
    const faltaRa2 = sql(`SELECT IFNULL(MAX(faltas), 'nenhuma') FROM esc_faltas_etapa WHERE oferta_id = ${of} AND etapa_id = ${etapa} AND rm = '${ra2}'`);

    const prof = await X.entrar(navegador, profU, senha(profU), 'painel', 1366);
    let janelas = 0;
    prof.p.on('dialog', () => { janelas++; });
    await prof.p.goto(L.BASE + `painel/escola-notas/etapas/${of}?etapa=${etapa}`);
    await L.semErros(prof.p, 'a tela da etapa (o professor)');
    const umBotao = (await prof.p.$$('#formEtapa button[type="submit"]')).length === 1 && !!(await prof.p.$('#gravarEtapa'))
      && !(await prof.p.$('#gravarRecuperacao')) && !(await prof.p.$('#gravarFaltas'));
    const estadoAberta = (await prof.p.textContent(`#abasEtapas a[data-etapa="${etapa}"] .vc-estado-etapa`).catch(() => '')).trim();

    /* 2. B3: as duas juntas; a recusa de um lado não perde o outro */
    await prof.p.fill(`input[name="recuperacao[${ra0}]"]`, '11');
    await prof.p.fill(`input[name="faltas[${ra1}]"]`, '3');
    await Promise.all([prof.p.waitForNavigation(), prof.p.click('#gravarEtapa')]);
    const msgRecusa = await aviso(prof.p);
    const voltou = await prof.p.inputValue(`input[name="faltas[${ra1}]"]`);
    const voltouRec = await prof.p.inputValue(`input[name="recuperacao[${ra0}]"]`);
    const nadaGravou = um(`SELECT COUNT(*) FROM esc_faltas_etapa WHERE oferta_id = ${of} AND etapa_id = ${etapa} AND rm = '${ra1}' AND faltas = 3`) === 0;
    L.ok(/recuperação não foi gravada/.test(msgRecusa) && /faltas digitadas não foram gravadas/.test(msgRecusa) && voltou === '3' && voltouRec === '11' && nadaGravou,
      `B3: com a recuperação inválida, nada se perde — a tela diz o motivo ("${msgRecusa.slice(0, 90)}…") e as faltas digitadas (3) e a recuperação (11) voltam aos campos`);
    await prof.p.fill(`input[name="recuperacao[${ra0}]"]`, '7,0');
    await Promise.all([prof.p.waitForNavigation(), prof.p.click('#gravarEtapa')]);
    const msgOk = await aviso(prof.p);
    const gravouRec = sql(`SELECT nota FROM esc_recuperacoes WHERE oferta_id = ${of} AND etapa_id = ${etapa} AND rm = '${ra0}'`);
    const gravouFal = um(`SELECT faltas FROM esc_faltas_etapa WHERE oferta_id = ${of} AND etapa_id = ${etapa} AND rm = '${ra1}'`);
    const intocada = sql(`SELECT IFNULL(MAX(faltas), 'nenhuma') FROM esc_faltas_etapa WHERE oferta_id = ${of} AND etapa_id = ${etapa} AND rm = '${ra2}'`) === faltaRa2;
    L.ok(umBotao && /^7(\.0+)?$/.test(gravouRec) && gravouFal === 3 && intocada && /recuperação e as faltas da etapa foram gravadas/.test(msgOk),
      `B3: um botão só ("Gravar") grava a recuperação (${gravouRec}) e as faltas (${gravouFal}) de uma vez ("${msgOk}"); a falta que ninguém tocou fica como estava (${faltaRa2})`);
    await L.print(prof.p, ETAPA, 'etapa-gravada');

    /* 3. o professor fecha, pela janela do sistema; tudo trava, com o aviso */
    const foraDeOrdem = depois ? await postar(prof.p, `escola-notas/fechar/${of}`, { etapa_id: String(depois) }) : { t: '' };
    const msgOrdem = ((foraDeOrdem.t.match(/alert-danger[^>]*>([\s\S]*?)<\/div>/) || [])[1] || '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ');
    await prof.p.goto(L.BASE + `painel/escola-notas/etapas/${of}?etapa=${etapa}`);
    const textoFechar = await confirmar(prof.p, '#fecharEtapa');
    const fechou = um(`SELECT COUNT(*) FROM esc_fechamentos WHERE oferta_id = ${of} AND etapa_id = ${etapa} AND situacao = 'vigente'`) === 1;
    L.ok(fechou && janelas === 0 && /ficam guardadas/.test(textoFechar),
      `B5: o professor fecha a etapa da disciplina dele pela janela do próprio sistema ("${textoFechar.slice(0, 60)}…"); ${janelas} janela(s) do navegador`);
    const avisoEtapa = (await prof.p.textContent('#etapaTravada').catch(() => '')).replace(/\s+/g, ' ').trim();
    const travados = await prof.p.$$eval('#formEtapa input[name^="recuperacao["], #formEtapa input[name^="faltas["]', (l) => l.length > 0 && l.every((i) => i.disabled));
    const semGravar = !(await prof.p.$('#gravarEtapa')) && !(await prof.p.$('#fecharEtapa'));
    const estadoFechada = (await prof.p.textContent(`#abasEtapas a[data-etapa="${etapa}"] .vc-estado-etapa`).catch(() => '')).trim();
    const masc = /bimestre|trimestre/.test(nomeEtapa);
    L.ok(estadoAberta === 'aberta' && estadoFechada === 'fechada' && travados && semGravar
      && new RegExp(`^${masc ? 'O' : 'A'} ${nomeEtapa} está fechad${masc ? 'o' : 'a'} desde`).test(avisoEtapa) && /peça a reabertura/.test(avisoEtapa),
      `B6/C6: na aba, "${estadoAberta}" → "${estadoFechada}"; os campos travados e o aviso no alto: "${avisoEtapa.slice(0, 110)}…"`);
    L.ok(!depois || /Feche antes o |Feche antes a /.test(msgOrdem), `C6: fora de ordem, "${msgOrdem.trim().slice(0, 70)}" (com o artigo)`);
    await L.print(prof.p, ETAPA, 'etapa-fechada');
    await prof.p.goto(L.BASE + `painel/atividades/visualizar/${ativ}`);
    await L.semErros(prof.p, 'as notas da atividade da etapa fechada');
    const notasTravadas = await prof.p.$$eval('#tabelaLancaNotas input.vc-nota-travada', (l) => l.length > 0 && l.every((i) => i.disabled)).catch(() => false);
    const semCampoAberto = (await prof.p.$$('#tabelaLancaNotas input.vc-nota')).length === 0;
    const avisoNotas = (await prof.p.textContent('#etapaFechadaNotas').catch(() => '')).replace(/\s+/g, ' ').trim();
    const notaId = um(`SELECT id FROM rm_disciplina_atividade_notas WHERE atividade_id = ${ativ} AND rm = '${ra1}'`);
    const tenta = await postar(prof.p, 'atividades/edit-notas', { action: 'edit', id: String(notaId), nota: '10' });
    const recusou = sql(`SELECT nota FROM rm_disciplina_atividade_notas WHERE id = ${notaId}`) === '8.0';
    L.ok(notasTravadas && semCampoAberto && /está fechad/.test(avisoNotas) && recusou,
      `B6: na tela de notas da atividade, os campos aparecem travados, com o aviso no alto ("${avisoNotas.slice(0, 60)}…"); o servidor continua recusando (a nota ficou 8,0)`);
    await L.print(prof.p, ETAPA, 'notas-travadas');

    /* 4. o professor não reabre; a direção reabre; a supervisão lança a recuperação */
    await prof.p.goto(L.BASE + `painel/escola-notas/etapas/${of}?etapa=${etapa}`);
    const semReabrir = !(await prof.p.$('#formReabrir'));
    await postar(prof.p, `escola-notas/reabrir/${of}`, { etapa_id: String(etapa), motivo: 'O professor tentando reabrir (prova)' });
    const continuaFechada = um(`SELECT COUNT(*) FROM esc_fechamentos WHERE oferta_id = ${of} AND etapa_id = ${etapa} AND situacao = 'vigente'`) === 1;
    L.ok(semReabrir && continuaFechada, 'o professor não reabre a etapa: nem o botão, nem o endereço (a etapa continua fechada)');
    await prof.ctx.close();
    const dir = await X.entrar(navegador, 'direcao.demo', senha('direcao.demo'), 'painel', 1366);
    dir.p.on('dialog', () => { janelas++; });
    await dir.p.goto(L.BASE + `painel/escola-notas/etapas/${of}?etapa=${etapa}`);
    await dir.p.fill('#motivo', 'Nota da prova lançada no aluno errado (prova 39)');
    const textoReabrir = await confirmar(dir.p, '#reabrirEtapa');
    const reabriu = um(`SELECT COUNT(*) FROM esc_fechamentos WHERE oferta_id = ${of} AND etapa_id = ${etapa} AND situacao = 'substituido' AND motivo LIKE 'Nota da prova%'`) === 1
      && um(`SELECT COUNT(*) FROM esc_fechamentos WHERE oferta_id = ${of} AND etapa_id = ${etapa} AND situacao = 'vigente'`) === 0;
    L.ok(reabriu && janelas === 0 && /voltam a mudar/.test(textoReabrir), `a direção acadêmica reabre a etapa, com o motivo, pela janela do sistema ("${textoReabrir.slice(0, 50)}…")`);
    const sup = await X.entrar(navegador, 'supervisao.demo', senha('supervisao.demo'), 'painel', 1366);
    await sup.p.goto(L.BASE + `painel/escola-notas/etapas/${of}?etapa=${etapa}`);
    const recAberta = await sup.p.$eval(`input[name="recuperacao[${ra0}]"]`, (i) => !i.disabled).catch(() => false);
    await sup.p.fill(`input[name="recuperacao[${ra0}]"]`, '6,5');
    await Promise.all([sup.p.waitForNavigation(), sup.p.click('#gravarEtapa')]);
    const recSup = sql(`SELECT nota FROM esc_recuperacoes WHERE oferta_id = ${of} AND etapa_id = ${etapa} AND rm = '${ra0}'`);
    L.ok(recAberta && /^6\.50?$/.test(recSup), `B4: depois da reabertura, a supervisão de secretaria lança a recuperação (o campo aberto; gravou ${recSup})`);

    /* 5. o relatório de reaberturas: a etapa, com o motivo, para quem reabre */
    await sup.p.goto(L.BASE + 'painel/relatorios/reaberturas');
    await L.semErros(sup.p, 'o relatório de reaberturas (a supervisão)');
    const txt = (await sup.p.textContent('main, .page-wrapper, body')).replace(/\s+/g, ' ');
    L.ok(txt.includes('A etapa: ' + nomeEtapa) && txt.includes('Nota da prova lançada no aluno errado') && !/rm_media_fechamentos|esc_fechamentos|SUBSTITU[ÍI]DO|R-AUD-01/.test(txt),
      'B10/C5: o relatório de reaberturas mostra a reabertura da etapa, com o motivo, para a supervisão — sem nome de tabela nem código interno');
    await L.print(sup.p, ETAPA, 'reaberturas');

    /* 6. o conselho: abre na etapa em andamento; corrigir e retirar */
    const turma = um(`SELECT turma_id FROM esc_turma_ofertas WHERE oferta_id = ${of}`);
    const corrente = um(`SELECT id FROM esc_etapas WHERE ano = ${ano} AND inicio <= CURDATE() ORDER BY ordem DESC LIMIT 1`);
    const ultimaFim = sql(`SELECT MAX(fim) FROM esc_etapas WHERE ano = ${ano}`);
    const sec = await X.entrar(navegador, 'secretaria.demo', senha('secretaria.demo'), 'painel', 1366);
    await sec.p.goto(L.BASE + `painel/escola-conselho/turma/${turma}`);
    const selecionada = await sec.p.$eval('.vc-form-conselho select[name="etapa_id"]', (s) => s.value);
    const esperado = X.hoje() > ultimaFim ? '0' : String(corrente);
    L.ok(selecionada === esperado, `B15: o conselho de classe abre na etapa em andamento (${selecionada === '0' ? 'Fim do ano' : 'etapa ' + selecionada}; esperado ${esperado})`);
    const obs = async (p, ra, texto) => postar(p, `escola-conselho/registrar/${turma}`, { etapa_id: String(corrente), rm: ra, decisao: 'observacao', observacao: texto });
    await obs(sec.p, ra1, 'Participa pouco nas aulas (prova 39)');
    await obs(sec.p, ra2, 'Registrada no aluno errado (prova 39)');
    const id1 = um(`SELECT id FROM esc_conselho WHERE rm = '${ra1}' AND observacao LIKE 'Participa pouco%'`);
    const id2 = um(`SELECT id FROM esc_conselho WHERE rm = '${ra2}' AND observacao LIKE 'Registrada no aluno errado%'`);
    if (!id1 || !id2) {
      const r = await obs(sec.p, ra1, 'Participa pouco nas aulas, segunda tentativa (prova 39)');
      throw new Error(`o conselho não registrou (${id1}, ${id2}): HTTP ${r.st}, "${((r.t.match(/alert-(?:success|danger)[^>]*>([\s\S]{0,200})/) || [])[1] || r.t.slice(0, 200)).replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ')}"`);
    }
    /* quem não é a autora nem a direção não muda */
    await postar(sup.p, `escola-conselho/corrigir/${turma}`, { registro: String(id1), observacao: 'A supervisão tentando corrigir (prova 39)' });
    const naoMudou = sql(`SELECT observacao FROM esc_conselho WHERE id = ${id1}`).startsWith('Participa pouco');
    /* a direção corrige pela tela */
    await dir.p.goto(L.BASE + `painel/escola-conselho/turma/${turma}`);
    const item = `.vc-decisao[data-registro="${id1}"]`;
    await clicar(dir.p, `${item} summary`);
    await dir.p.fill(`${item} .vc-form-corrigir input[name="observacao"]`, 'Participa pouco nas aulas de leitura (prova 39)');
    await Promise.all([dir.p.waitForNavigation(), clicar(dir.p, `${item} .vc-form-corrigir button`)]);
    const corrigida = sql(`SELECT observacao FROM esc_conselho WHERE id = ${id1}`) === 'Participa pouco nas aulas de leitura (prova 39)'
      && sql(`SELECT observacao_antes FROM esc_conselho_hist WHERE conselho_id = ${id1} AND acao = 'corrigiu'`) === 'Participa pouco nas aulas (prova 39)';
    /* a autora retira a outra, pela janela do sistema */
    await sec.p.goto(L.BASE + `painel/escola-conselho/turma/${turma}`);
    const item2 = `.vc-decisao[data-registro="${id2}"]`;
    await clicar(sec.p, `${item2} summary`);
    await clicar(sec.p, `${item2} .vc-form-retirar button`);
    await sec.p.waitForSelector('#vcConfirmar.show', { timeout: 8000 });
    await Promise.all([sec.p.waitForNavigation(), sec.p.click('#vcConfirmarSim')]);
    const retirada = sql(`SELECT situacao FROM esc_conselho WHERE id = ${id2}`) === 'retirada' && um(`SELECT COUNT(*) FROM esc_conselho_hist WHERE conselho_id = ${id2} AND acao = 'retirou'`) === 1
      && !(await sec.p.$(item2));
    L.ok(naoMudou && corrigida && retirada,
      'B15: a direção corrige a observação (o texto de antes fica no histórico); a autora retira a outra (some da tela, fica no histórico); a supervisão, que não é a autora, não muda');
    await L.print(sec.p, ETAPA, 'conselho');
    await sec.ctx.close();
    await sup.ctx.close();

    /* sabotagem: a trava da etapa desligada */
    const orig = fs.readFileSync(MODELO, 'utf8');
    const trecho = "        $f = self::fechamento($ofertaId, $etapaId);\n        if (!$f) {\n            return null;\n        }\n        $e = self::etapa($etapaId);\n        [, $g]";
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let travadoSab = true;
    try {
      fs.writeFileSync(MODELO, orig.replace(trecho, "        $f = null;\n        if (!$f) {\n            return null;\n        }\n        $e = self::etapa($etapaId);\n        [, $g]"));
      await postar(dir.p, `escola-notas/fechar/${of}`, { etapa_id: String(etapa) }).catch(() => null);
      const p2 = await X.entrar(navegador, profU, senha(profU), 'painel', 1366);
      sql(`INSERT INTO esc_fechamentos (oferta_id, etapa_id, numero, regra_json, regra_hash, situacao, fechado_por, fechado_em) SELECT ${of}, ${etapa}, 99, '{}', REPEAT('0', 64), 'vigente', 1, NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM esc_fechamentos WHERE oferta_id = ${of} AND etapa_id = ${etapa} AND situacao = 'vigente')`);
      await p2.p.goto(L.BASE + `painel/escola-notas/etapas/${of}?etapa=${etapa}`);
      travadoSab = await p2.p.$$eval('#formEtapa input[name^="faltas["]', (l) => l.length > 0 && l.every((i) => i.disabled)).catch(() => true);
      await p2.ctx.close();
    } finally {
      fs.writeFileSync(MODELO, orig);
    }
    L.ok(!travadoSab, `sabotagem (a trava da etapa desligada): a prova reprova — com a etapa fechada, os campos ficaram ${travadoSab ? 'travados' : 'abertos'}`);
    L.ok(sha(MODELO) === shaModelo, 'o arquivo sabotado voltou igual (sha256)');
    await dir.ctx.close();
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(MODELO) !== shaModelo) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
