/*
 * ONDA 2, ETAPA 3 — a auditoria de notas e faltas. Por cada porta que existe hoje (a nota na célula, a importação de
 * notas, as faltas na tela, a importação de faltas, a chamada, a compensação, o encerramento e a reabertura):
 * lançar, alterar e apagar, e conferir o registro (valor anterior e novo, quem, perfil, origem, IP, navegador);
 * salvar sem mudar não registra; o "Histórico desta nota" e o relatório (filtros, exportação, quem vê);
 * sabotagens: não há rota que altere a auditoria (nem o banco deixa); o coordenador não vê a de outro curso; sem a
 * chamada da auditoria no ponto que salva a nota, a prova acusa.
 *
 *   node tests/tabler/onda2/etapa3.js     (depois do ciclo)
 */
const fs = require('fs');
const path = require('path');
const { execFileSync } = require('child_process');
const L = require('../lib');

const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste2?$/.test(BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
const MYSQL = process.env.VCOSTAEDU_MYSQL || 'C:/xampp/mysql/bin/mysql.exe';
const sql = (q) => execFileSync(MYSQL, ['-u', 'root', '-N', '--default-character-set=utf8mb4', BANCO, '-e', q]).toString().replace(/\r/g, '').trim();
const sqlErro = (q) => { try { sql(q); return ''; } catch (e) { return String(e.stderr || e.message); } };
const cred = JSON.parse(fs.readFileSync(path.join(__dirname, '..', 'saida', 'credenciais.json'), 'utf8'));
const ETAPA = 'onda2/etapa3';
const contar = (w) => +sql(`SELECT COUNT(*) FROM rm_auditoria_lancamentos WHERE ${w}`);
const ultima = (w) => {
  const r = sql(`SELECT CONCAT_WS('|', tipo, acao, IFNULL(valor_anterior,'(vazio)'), IFNULL(valor_novo,'(vazio)'), usuario_nome, perfil, origem, IFNULL(ip,''), IFNULL(navegador,''), apos_fechamento, IFNULL(data_aula,''), IFNULL(detalhe,''))
    FROM rm_auditoria_lancamentos WHERE ${w} ORDER BY id DESC LIMIT 1`).split('|');
  return { tipo: r[0], acao: r[1], antes: r[2], depois: r[3], quem: r[4], perfil: r[5], origem: r[6], ip: r[7], navegador: r[8], aposFechamento: r[9], dataAula: r[10], detalhe: r[11] };
};

async function entrar(navegador, usuario) {
  const ctx = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' });
  const p = await L.pagina(ctx);
  await p.goto(L.BASE + 'painel/login/acesso');
  await p.fill('input[name="usuario"]', usuario);
  await p.fill('input[name="senha"]', cred.painel[usuario]);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
  return { ctx, p };
}
const postar = (p, rota, form) => p.evaluate(async ([u, f]) => {
  const r = await fetch(u, { method: 'POST', body: new URLSearchParams(f), credentials: 'same-origin' });
  return r.status;
}, [L.BASE + rota, form]);
const enviarCsv = (p, rota, campos, csv) => p.evaluate(async ([u, c, conteudo]) => {
  const fd = new FormData();
  Object.entries(c).forEach(([k, v]) => fd.append(k, v));
  fd.append('arquivo', new Blob([conteudo], { type: 'text/csv' }), 'prova.csv');
  const r = await fetch(u, { method: 'POST', body: fd, credentials: 'same-origin' });
  return r.status;
}, [L.BASE + rota, campos, csv]);
const abriu = async (p, rota) => {
  await p.goto(L.BASE + rota);
  return decodeURIComponent(p.url()).replace(L.BASE, '').replace(/[?#].*$/, '') === rota.replace(/[?#].*$/, '') && !(await p.$('#foraDoEscopo')) && !(await p.$('input[name="SendLogin"]')) && !(await p.$('#semAcesso'));
};
async function sabotar(nome, estragar, consertar, conferir) {
  let reprovou = false;
  try { await estragar(); reprovou = !(await conferir()); } finally { await consertar(); }
  L.ok(reprovou, `sabotagem (${nome}): a prova reprova o defeito`);
}

(async () => {
  const { navegador } = await L.abrir();
  try {
    console.log('\n== Onda 2, Etapa 3');
    const IP = /^(::1|127\.0\.0\.1|[0-9a-f:.]+)$/i;

    /* A. a nota na célula (tela do professor): alterar, salvar igual, apagar, lançar */
    const prof = await entrar(navegador, 'prof.laura.demo');
    const [nId, nAtiv, nRm, nValor] = sql("SELECT CONCAT_WS('|', n.id, n.atividade_id, n.rm, n.nota) FROM rm_disciplina_atividade_notas n WHERE n.rm_prof_mat_id = 5 AND n.nota IS NOT NULL ORDER BY n.id LIMIT 1").split('|');
    const w = `tipo = 'nota' AND atividade_id = ${nAtiv} AND rm = '${nRm}'`;
    const outro = nValor === '5.0' ? '6.5' : '5.0';
    let antes = contar(w);
    await postar(prof.p, 'painel/atividades/edit-notas', { id: nId, nota: outro, action: 'edit' });
    let u = ultima(w);
    L.ok(contar(w) === antes + 1 && u.acao === 'alterou' && u.antes === nValor && u.depois === outro, `nota alterada na célula: registrado (${u.antes} → ${u.depois})`);
    L.ok(u.quem.includes('Laura') && u.perfil === 'Professor' && u.origem === 'professor', `quem, perfil e origem: ${u.quem} / ${u.perfil} / ${u.origem}`);
    L.ok(IP.test(u.ip) && /^(Chrome|Edge|Firefox|Safari)/.test(u.navegador), `IP (${u.ip}) e navegador resumido (${u.navegador})`);
    antes = contar(w);
    await postar(prof.p, 'painel/atividades/edit-notas', { id: nId, nota: outro, action: 'edit' });
    L.ok(contar(w) === antes, 'salvar a mesma nota não gera registro');
    await postar(prof.p, 'painel/atividades/edit-notas', { id: nId, nota: '', action: 'edit' });
    u = ultima(w);
    L.ok(u.acao === 'apagou' && u.antes === outro && u.depois === '(vazio)', 'apagar a nota: registrado como "apagou"');
    await postar(prof.p, 'painel/atividades/edit-notas', { id: nId, nota: nValor, action: 'edit' });
    u = ultima(w);
    L.ok(u.acao === 'lancou' && u.antes === '(vazio)' && u.depois === nValor && sql(`SELECT nota FROM rm_disciplina_atividade_notas WHERE id = ${nId}`) === nValor, `lançar de novo: registrado como "lançou" (e a nota voltou a ${nValor})`);

    /* B. a importação de notas (CSV): origem importação */
    const csvNota = nValor === '7.0' ? '7,5' : '7,0';
    antes = contar(w);
    await enviarCsv(prof.p, `painel/atividades/upload-notas/${nAtiv}`, { UpNotasCsv: 'Salvar' }, `rm;nota\n${nRm};${csvNota}\n`);
    u = ultima(w);
    L.ok(contar(w) === antes + 1 && u.origem === 'importacao' && u.depois === csvNota.replace(',', '.'), `importação de notas: registrado com a origem "importação" (${u.antes} → ${u.depois})`);
    await postar(prof.p, 'painel/atividades/edit-notas', { id: nId, nota: nValor, action: 'edit' });

    /* C. as faltas na tela e D. a importação de faltas */
    const [fRm, fFaltas] = sql("SELECT CONCAT_WS('|', rm, faltas) FROM rm_disciplina_faltas WHERE rm_prof_mat_id = 5 ORDER BY id LIMIT 1").split('|');
    const wf = `tipo = 'falta' AND oferta_id = 5 AND rm = '${fRm}'`;
    antes = contar(wf);
    await postar(prof.p, 'painel/faltas/editar/5', { [`faltas[${fRm}]`]: String(+fFaltas + 1) });
    u = ultima(wf);
    L.ok(contar(wf) === antes + 1 && u.antes === fFaltas && u.depois === String(+fFaltas + 1) && u.origem === 'professor', `faltas na tela: registrado (${u.antes} → ${u.depois})`);
    antes = contar(wf);
    await postar(prof.p, 'painel/faltas/editar/5', { [`faltas[${fRm}]`]: String(+fFaltas + 1) });
    L.ok(contar(wf) === antes, 'salvar as mesmas faltas não gera registro');
    await enviarCsv(prof.p, 'painel/faltas/importar/5', {}, `rm;faltas\n${fRm};${fFaltas}\n`);
    u = ultima(wf);
    L.ok(u.origem === 'importacao' && u.depois === fFaltas, `importação de faltas: registrado com a origem "importação" (${u.antes} → ${u.depois})`);
    await prof.ctx.close();

    /* E. a compensação (secretaria) */
    const sec = await entrar(navegador, 'secretaria.demo');
    const cRm = sql('SELECT rm FROM rm_disciplina_faltas WHERE rm_prof_mat_id = 5 AND faltas > faltas_compensadas ORDER BY id LIMIT 1');
    const wc = `tipo = 'compensacao' AND oferta_id = 5 AND rm = '${cRm}'`;
    antes = contar(wc);
    await postar(sec.p, 'painel/faltas/compensar/5', { rm: cRm, quantidade: '1', tipo: 'atestado', justificativa: 'Atestado fictício da prova', inicio: '2026-03-02', fim: '2026-03-02' });
    u = ultima(wc);
    L.ok(contar(wc) === antes + 1 && u.perfil === 'Secretaria' && u.origem === 'painel', `compensação: registrado (compensadas ${u.antes} → ${u.depois}, ${u.perfil}, tela do painel)`);
    await sec.ctx.close();

    /* F. a chamada (data da aula), a edição do encontro e a exclusão (ação sensível) */
    const marcos = await entrar(navegador, 'prof.marcos.demo');
    const alunos6 = sql("SELECT rm FROM portal_disciplinas_alunos WHERE rm_prof_mat_id = 6 AND situacao = 1 ORDER BY rm").split('\n').filter(Boolean);
    if (sql('SELECT frequencia_fonte FROM rm_professores_materias WHERE id = 6') !== 'chamada') {
      const zeros = {};
      alunos6.forEach((r) => { zeros[`faltas[${r}]`] = '0'; });
      await postar(marcos.p, 'painel/faltas/editar/6', zeros);
      await postar(marcos.p, 'painel/chamada/fonte/6', { fonte: 'chamada' });
    }
    L.ok(sql('SELECT frequencia_fonte FROM rm_professores_materias WHERE id = 6') === 'chamada', 'a oferta 6 está com a frequência pela chamada');
    const ausente = alunos6[0];
    const wp = `tipo = 'presenca' AND oferta_id = 6 AND data_aula = '2026-11-10'`;
    antes = contar(wp);
    await postar(marcos.p, 'painel/chamada/encontro/6', { SalvarChamada: '1', data: '2026-11-10', [`ausente[${ausente}]`]: '1' });
    const enc = sql("SELECT id FROM rm_oferta_encontros WHERE rm_prof_mat_id = 6 AND data = '2026-11-10' ORDER BY id DESC LIMIT 1");
    L.ok(contar(wp) === antes + alunos6.length && ultima(`${wp} AND rm = '${ausente}'`).depois === 'ausente', `chamada: ${alunos6.length} presença(s) registradas com a data da aula (10/11/2026), o ausente como "ausente"`);
    L.ok(ultima(`tipo = 'falta' AND oferta_id = 6 AND rm = '${ausente}'`).depois !== undefined && contar(`tipo = 'falta' AND oferta_id = 6 AND rm = '${ausente}'`) > 0, 'chamada: a falta consolidada do ausente também ficou registrada');
    antes = contar(wp);
    await postar(marcos.p, `painel/chamada/encontro/6?id=${enc}`, { SalvarChamada: '1', data: '2026-11-10' });
    u = ultima(`${wp} AND rm = '${ausente}'`);
    L.ok(contar(wp) === antes + 1 && u.acao === 'alterou' && u.antes === 'ausente' && u.depois === 'presente', 'chamada editada: só a presença que mudou é registrada (ausente → presente)');
    await marcos.ctx.close();
    const sup = await entrar(navegador, 'supervisao.demo');
    antes = contar(`${wp} AND acao = 'apagou'`);
    await postar(sup.p, `painel/chamada/excluir/${enc}`, {});
    L.ok(contar(`${wp} AND acao = 'apagou'`) === antes + alunos6.length, `encontro excluído (pela Supervisão): ${alunos6.length} presença(s) registradas como "apagou"`);
    await sup.ctx.close();

    /* G. a reabertura, a alteração depois do fechamento e o novo encerramento */
    const adm = await entrar(navegador, 'administrador');
    const wr = "tipo = 'fechamento' AND oferta_id = 1";
    antes = contar(wr);
    await postar(adm.p, 'painel/disciplinas/reabrir/1', { observacao: 'Correção de nota fictícia da prova' });
    u = ultima(wr);
    L.ok(contar(wr) === antes + 1 && u.acao === 'reabriu' && u.detalhe === 'Correção de nota fictícia da prova', 'reabertura: registrada, com a justificativa');
    const [n1Id, n1Ativ, n1Rm, n1Valor] = sql("SELECT CONCAT_WS('|', id, atividade_id, rm, nota) FROM rm_disciplina_atividade_notas WHERE rm_prof_mat_id = 1 AND nota IS NOT NULL ORDER BY id LIMIT 1").split('|');
    await postar(adm.p, 'painel/atividades/edit-notas', { id: n1Id, nota: n1Valor === '8.0' ? '8.5' : '8.0', action: 'edit' });
    u = ultima(`tipo = 'nota' AND atividade_id = ${n1Ativ} AND rm = '${n1Rm}'`);
    L.ok(u.aposFechamento === '1' && u.perfil === 'Administrador' && u.origem === 'painel', 'alteração depois do fechamento: marcada "depois do fechamento" (tela do painel, Administrador)');
    await postar(adm.p, 'painel/atividades/edit-notas', { id: n1Id, nota: n1Valor, action: 'edit' });
    await postar(adm.p, 'painel/disciplinas/encerrar/1', { ConfirmarEncerramento: '1' });
    u = ultima(wr);
    L.ok(u.acao === 'encerrou' && u.aposFechamento === '1' && sql('SELECT encerrada_em IS NOT NULL FROM rm_professores_materias WHERE id = 1') === '1', 'novo encerramento: registrado (e a disciplina encerrada de novo)');

    /* H. o "Histórico desta nota" */
    const prof2 = await entrar(navegador, 'prof.laura.demo');
    await prof2.p.goto(L.BASE + `painel/atividades/visualizar/${nAtiv}`);
    await prof2.p.click(`.vc-historico-nota[data-url*="rm=${nRm}"]`);
    await prof2.p.waitForSelector('#historicoNotaCorpo #tabelaHistoricoNota', { timeout: 8000 }).catch(() => {});
    const linhasHist = await prof2.p.$$eval('#historicoNotaCorpo #tabelaHistoricoNota tbody tr', (t) => t.length).catch(() => 0);
    L.ok(linhasHist >= 4, `o "Histórico desta nota" abre ao lado da nota, com ${linhasHist} mudança(s)`);
    await L.semErros(prof2.p, 'notas com o histórico');
    await L.print(prof2.p, ETAPA, 'historico-desta-nota');
    await prof2.ctx.close();

    /* I. o relatório */
    await adm.p.goto(L.BASE + 'painel/auditoria/listar?so_alteracoes=1');
    await L.semErros(adm.p, 'relatório de auditoria');
    const acoes = await adm.p.$$eval('#tabelaAuditoria tbody tr td:nth-child(3)', (t) => t.map((x) => x.textContent.trim()));
    L.ok(acoes.length > 0 && acoes.every((a) => /^(alterou|apagou)/.test(a)), `relatório, "só alterações": ${acoes.length} linha(s), só "alterou" e "apagou"`);
    L.ok(!!(await adm.p.$('.dt-buttons, .buttons-excel')), 'relatório: os botões de exportação (Excel, CSV, PDF)');
    await adm.p.goto(L.BASE + `painel/auditoria/listar?aluno=${nRm}&tipo=nota`);
    const ras = await adm.p.$$eval('#tabelaAuditoria tbody tr td:nth-child(6)', (t) => t.map((x) => x.textContent.trim()));
    L.ok(ras.length > 0 && ras.every((r) => r.startsWith(nRm)), `relatório, filtro por aluno: só o RA ${nRm} (${ras.length} linha(s))`);
    await L.print(adm.p, ETAPA, 'relatorio-auditoria');
    for (const [usuario, pode] of [['direcao.demo', true], ['supervisao.demo', true], ['secretaria.demo', false], ['prof.laura.demo', false]]) {
      const s = await entrar(navegador, usuario);
      L.ok((await abriu(s.p, 'painel/auditoria/listar')) === pode, `${usuario}: o relatório ${pode ? 'abre' : 'não abre'}`);
      await s.ctx.close();
    }

    /* J. o coordenador: só a do curso dele */
    const coord = await entrar(navegador, 'coord.demo');
    await abriu(coord.p, 'painel/auditoria/listar');
    const cursosVistos = parseInt(await coord.p.$eval('#totalAuditoria', (e) => e.textContent), 10);
    const nomeB = sql('SELECT nome FROM rm_cursos WHERE id = 2');
    L.ok(cursosVistos === contar('curso_id = 1') && !(await coord.p.$('#curso option[value="2"]')), `coordenador do curso 1: o relatório tem só as ${cursosVistos} linha(s) do curso 1 (o curso 2 tem ${contar('curso_id = 2')}; nem aparece no filtro)`);
    L.ok(!(await abriu(coord.p, 'painel/auditoria/listar?curso=2')), 'coordenador: o relatório do curso 2 pelo endereço é recusado');
    const ativB = sql('SELECT t.id FROM rm_disciplina_atividade t JOIN rm_professores_materias o ON o.id = t.rm_prof_mat_id WHERE o.curso_id = 2 ORDER BY t.id LIMIT 1');
    L.ok(!(await abriu(coord.p, `painel/auditoria/nota/${ativB}?rm=250101`)), 'coordenador: o histórico de uma nota do curso 2 é recusado');
    await coord.ctx.close();

    /* K. ninguém altera nem apaga a auditoria */
    const rotas = sql("SELECT GROUP_CONCAT(CONCAT(metodo, ':', grava) ORDER BY metodo) FROM adms_paginas WHERE controller = 'Auditoria'");
    L.ok(rotas === 'listar:0,nota:0', `as únicas rotas da auditoria são de leitura (${rotas})`);
    const total = contar('1 = 1');
    await postar(adm.p, 'painel/auditoria/listar', { id: '1', acao: 'apagar', valor_novo: 'x' });
    await postar(adm.p, 'painel/auditoria/apagar/1', {});
    await postar(adm.p, 'painel/auditoria/editar/1', { valor_novo: 'x' });
    L.ok(contar('1 = 1') === total && sql('SELECT IFNULL(valor_novo, "") FROM rm_auditoria_lancamentos WHERE id = 1') !== 'x', 'POST na auditoria (listar, apagar, editar) não muda nada — nem como Administrador');
    const gatilhos = sql("SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE() AND EVENT_OBJECT_TABLE = 'rm_auditoria_lancamentos'");
    if (gatilhos === '2') {
      L.ok(/não pode ser alterada/.test(sqlErro("UPDATE rm_auditoria_lancamentos SET valor_novo = 'x' WHERE id = (SELECT id FROM (SELECT MIN(id) id FROM rm_auditoria_lancamentos) q)")), 'no banco: o gatilho recusa alterar uma linha');
      sql(`INSERT INTO rm_auditoria_lancamentos (tipo, acao, oferta_id, campo, usuario_nome, perfil, origem, ambiente) VALUES ('nota', 'lancou', 5, 'nota', 'prova', 'prova', 'painel', 'producao')`);
      L.ok(/não pode ser apagada/.test(sqlErro("DELETE FROM rm_auditoria_lancamentos WHERE ambiente = 'producao'")), 'no banco: o gatilho recusa apagar uma linha que não é da demonstração');
    } else {
      L.ok(false, `os 2 gatilhos da auditoria não estão no banco (${gatilhos})`);
    }
    await adm.ctx.close();

    /* L. sabotagem: sem a chamada da auditoria no ponto que salva a nota */
    const modelo = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'atividades', 'AdmsEditNotas.php');
    const orig = fs.readFileSync(modelo, 'utf8');
    const p3 = await entrar(navegador, 'prof.laura.demo');
    await sabotar('a nota gravada sem a auditoria', () => fs.writeFileSync(modelo, orig.replace('\\App\\adms\\Models\\helper\\AdmsAuditoria::nota(', '(fn() => null)(')),
      async () => { fs.writeFileSync(modelo, orig); await postar(p3.p, 'painel/atividades/edit-notas', { id: nId, nota: nValor, action: 'edit' }); },
      async () => { const a = contar(w); await postar(p3.p, 'painel/atividades/edit-notas', { id: nId, nota: outro, action: 'edit' }); return contar(w) === a + 1; });
    await p3.ctx.close();
  } catch (e) {
    L.ok(false, 'erro na prova: ' + e.message.split('\n')[0]);
  } finally {
    await navegador.close();
    process.exit(L.resumo('onda 2, etapa 3') ? 0 : 1);
  }
})();
