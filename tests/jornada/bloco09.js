// Bloco 9 — TCC e documentos: TCC do aluno 1; prévia e emissão do histórico PARCIAL (aluno 2); aluno 1 em "Concluído" e o
// histórico FINAL (prévia e emissão); o ATESTADO pela secretaria (aluno 3); a verificação pública de cada um, em nova aba.
const J = require('./lib');
const { ALUNOS } = require('./dados');

async function matricula(page, alunoId) {
  await J.ir(page, `painel/academicos/visualizar/${alunoId}`);
  await J.clicar(page, '#tabeMatricula-tab'); await page.waitForTimeout(400);
  return parseInt(await page.$eval('.vinculo', v => v.dataset.matricula), 10);
}
async function previa(page) {
  const [aba] = await Promise.all([page.context().waitForEvent('page', { timeout: 30000 }), J.clicar(page, '#botaoPrevia')]);
  await aba.waitForLoadState('load').catch(() => {}); await aba.waitForTimeout(3500);
  const tipo = aba.url();
  await aba.close(); await page.bringToFront();
  return tipo;
}
async function emitir(page, mc, tipo) {
  await J.ir(page, `painel/historico-emissoes/emitir/${mc}?tipo=${tipo}`);
  // o modelo genérico ("desenho oficial"), nunca o do cliente
  const geral = await page.$$eval('#modelo option', os => (os.find(o => /desenho oficial/i.test(o.textContent)) || {}).value).catch(() => '');
  if (geral && (await page.inputValue('#modelo')) !== geral) await J.ir(page, `painel/historico-emissoes/emitir/${mc}?tipo=${tipo}&modelo=${geral}`);
  J.ok(/desenho oficial/i.test(await page.$eval('#modelo', s => s.options[s.selectedIndex].textContent).catch(() => '')), `${tipo}: modelo "desenho oficial"`);
  const alertas = await J.texto(page, '#alertasEmissao');
  const bloq = await J.texto(page, '#bloqueios');
  return { alertas, bloq };
}
async function confirmarEmissao(page) {
  // caixa estilizada (custom-control): o input fica escondido; clica-se no rótulo
  if (await page.$('#confirmo_alertas')) { if (!(await page.locator('#confirmo_alertas').isChecked())) await J.clicar(page, 'label[for="confirmo_alertas"]'); }
  await J.clicarENavegar(page, 'input[name="EmitirHistorico"]');
  const codigo = await J.texto(page, '#codigoEmissao');
  return codigo;
}
async function verificar(page, rotulo) {
  const link = page.locator('#dadosEmissao a[target="_blank"]').first();
  const [aba] = await Promise.all([page.context().waitForEvent('page', { timeout: 30000 }), J.clicar(page, link)]);
  await aba.waitForLoadState('load').catch(() => {}); await aba.bringToFront(); await aba.waitForTimeout(2500);
  const sit = ((await aba.textContent('#situacaoVerificacao').catch(() => '')) || '').trim();
  const tipo = ((await aba.textContent('#tipoDocumento').catch(() => '')) || '').trim();
  J.ok(/Documento válido/.test(sit), `verificação pública do ${rotulo}: "${sit}" — ${tipo}`);
  await aba.waitForTimeout(1500); await aba.close(); await page.bringToFront();
  return tipo;
}

J.rodar(9, 'TCC e documentos', async ({ page }) => {
  const E = J.estado();
  const [a1, a2, a3] = ALUNOS.map(a => E.alunos[a.rm]);
  const mc1 = await matricula(page, a1);
  const tccPronto = /Aprovado/.test(await J.texto(page, '.tcc h6'));
  if (tccPronto) J.ok(true, 'retomada: o TCC do aluno 1 já está salvo (Aprovado)');
  else {
  await J.passo(page, 'TCC do aluno 1 (ficha → aba Matrícula → TCC): título, orientador (o docente fictício), nota 9,0, data da defesa e "Aprovado".\nConfira: a nota mínima do TCC é a da regra do TCC e dos documentos do curso (7,0).');
  const f = page.locator('form[action*="tcc/salvar"]').first();
  await J.digitar(page, f.locator('[name="titulo"]'), 'Um estudo inventado sobre jornadas (jornada — apagar)');
  const orient = await f.locator('[name="orientador_id"] option').evaluateAll(os => (os.find(o => /jornada/i.test(o.textContent)) || {}).value);
  J.ok(!!orient, 'orientador fictício na lista: ' + (orient || 'NÃO')); if (orient) await J.escolher(page, f.locator('[name="orientador_id"]'), orient);
  await J.digitar(page, f.locator('[name="nota"]'), '9,0');
  await f.locator('[name="data_defesa"]').fill('2026-12-01');
  await J.escolher(page, f.locator('[name="situacao"]'), '2');
  await J.clicarENavegar(page, f.locator('button[type="submit"]'));
  J.ok(/TCC/i.test(await J.alerta(page)), 'TCC: ' + (await J.alerta(page)).slice(0, 90));
  }

  const mc2 = await matricula(page, a2);
  let e = await emitir(page, mc2, 'PARCIAL');
  await J.passo(page, 'Histórico PARCIAL do aluno 2 (Cursando).\nConfira os alertas (se houver) e abro a PRÉVIA em PDF numa nova aba — a prévia não grava nada.');
  J.ok(true, `PARCIAL: alertas "${e.alertas.slice(0, 120) || 'nenhum'}"`);
  await previa(page);
  await J.passo(page, 'Emito o PARCIAL.' + (e.alertas ? ' Há alertas: para o PARCIAL, basta confirmar que foram vistos.' : ''));
  const codP = await confirmarEmissao(page);
  J.ok(!!codP, `PARCIAL emitido: código ${codP}`);
  await J.passo(page, 'Verificação pública do PARCIAL: abro o link numa nova aba.\nConfira: "Documento válido" e o tipo do documento.');
  await verificar(page, 'PARCIAL');

  await matricula(page, a1);
  await J.passo(page, 'Aluno 1: todas as disciplinas encerradas e o TCC aprovado. Mudo a matrícula para "Concluído", com a data de conclusão.\nConfira: o sistema só aceita "Concluído" com a grade aprovada e o TCC aprovado.');
  const fv = page.locator('form.formVinculo').first();
  const conc = await fv.locator('[name="situacao_id"] option').evaluateAll(os => (os.find(o => /conclu/i.test(o.textContent)) || {}).value);
  await J.escolher(page, fv.locator('[name="situacao_id"]'), conc);
  await fv.locator('[name="data_conclusao"]').fill('2026-12-15');
  await J.clicarENavegar(page, fv.locator('button[type="submit"]'));
  J.ok(/Conclu/i.test(await J.texto(page, '.vinculo')), 'matrícula do aluno 1: ' + (await J.alerta(page)).slice(0, 90));

  e = await emitir(page, mc1, 'FINAL');
  await J.passo(page, 'Histórico FINAL do aluno 1.\nConfira: o FINAL só sai SEM nenhum alerta (sem bloqueio). Abro a prévia.');
  J.ok(!e.bloq, `FINAL: bloqueios "${e.bloq || 'nenhum'}"; alertas "${e.alertas.slice(0, 160) || 'nenhum'}"`);
  await previa(page);
  let codF = '';
  if (!e.bloq) {
    await J.passo(page, 'Emito o FINAL.');
    codF = await confirmarEmissao(page);
    J.ok(!!codF, `FINAL emitido: código ${codF}`);
    await J.passo(page, 'Verificação pública do FINAL, em nova aba.');
    await verificar(page, 'FINAL');
  } else await J.passo(page, 'O FINAL está BLOQUEADO: "' + e.bloq.slice(0, 200) + '"\nRegistro no retorno e sigo.');

  const mc3 = await matricula(page, a3);
  e = await emitir(page, mc3, 'ATESTADO');
  await J.passo(page, 'ATESTADO de matrícula do aluno 3, emitido pela secretaria. Abro a prévia e emito.');
  await previa(page);
  const codA = await confirmarEmissao(page);
  J.ok(!!codA, `atestado emitido: código ${codA}`);
  await J.passo(page, 'Verificação pública do atestado, em nova aba.');
  await verificar(page, 'atestado');
  J.guardar('documentos', { parcial: codP, final: codF, atestado: codA });
  return `TCC; PARCIAL ${codP}; FINAL ${codF || '(bloqueado)'}; atestado ${codA}; verificações públicas abertas.`;
});
