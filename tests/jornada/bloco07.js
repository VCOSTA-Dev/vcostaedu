// Bloco 7 — Frequência: chamada de 3 encontros na oferta A (ausências variadas), uma compensação, faltas da oferta B por
// importação CSV, e FS/FI no relatório de frequência da oferta.
const J = require('./lib');
const { ALUNOS } = require('./dados');
const RA = ALUNOS.map(a => a.rm);
// A (chamada, 3 encontros): ausentes por encontro
const AUSENTES = [[RA[2], RA[4]], [RA[4]], [RA[3], RA[4]]];
// B (lançamento, 20 encontros; mínimo 75% = até 5 faltas efetivas)
const FALTAS_B = [0, 2, 1, 3, 8];

J.rodar(7, 'Frequência', async ({ page }) => {
  const { A, B } = J.estado().ofertas;
  for (let e = 0; e < 3; e++) {
    await J.ir(page, `painel/chamada/encontro/${A}`);
    if (e === 0) await J.passo(page, 'Chamada da oferta A, encontro 1.\nTodos começam PRESENTES; clico em quem faltou. Confira o contador de ausentes antes de salvar.');
    else if (e === 1) await J.passo(page, 'Encontros 2 e 3, com outras ausências.');
    await page.fill('#data', ['2026-08-03', '2026-08-10', '2026-08-17'][e]);
    await J.digitar(page, '#titulo', `Encontro ${e + 1} (jornada — apagar)`);
    for (const rm of AUSENTES[e]) await J.clicar(page, `tr[data-rm="${rm}"] .botao-presenca`);
    await J.destacar(page, '#contagemAusentes');
    await J.clicarENavegar(page, '#salvarChamada');
    J.ok(/Chamada salva/i.test(await J.alerta(page)), `encontro ${e + 1}: ${AUSENTES[e].length} ausente(s) — ` + (await J.alerta(page)).slice(0, 60));
  }
  await J.ir(page, `painel/faltas/visualizar/${A}`);
  await J.passo(page, 'Faltas da oferta A: vêm da CHAMADA (só leitura aqui) e o total de encontros é o de encontros registrados (3).\nVou lançar uma COMPENSAÇÃO (atestado) de 1 falta para o aluno 3.');
  await J.destacar(page, '#faltasDaChamada');
  await J.clicar(page, `tr[data-rm="${RA[2]}"] button[data-target="#modalCompensar"]`);
  await page.waitForSelector('#modalCompensar.show', { timeout: 10000 });
  await J.digitar(page, '#compQtd', '1');
  await J.escolher(page, '#compTipo', 'atestado');
  await page.fill('#compIni', '2026-08-03'); await page.fill('#compFim', '2026-08-03');
  await J.digitar(page, '#compJust', 'Atestado médico fictício (jornada — apagar)');
  await J.clicarENavegar(page, '#formCompensar button[type="submit"]');
  J.ok(/Compensação lançada/i.test(await J.alerta(page)), 'compensação: ' + (await J.alerta(page)).slice(0, 70));
  await J.destacar(page, '#historicoCompensacoes');

  await J.ir(page, `painel/faltas/visualizar/${B}`);
  await J.passo(page, 'Oferta B (lançamento): importar as faltas por CSV ("rm;faltas").\nConfira: "Faltas importadas: 5 aluno(s)". O aluno 5 fica com 8 faltas em 20 (abaixo de 75%).');
  const f = J.csv('faltas_B.csv', 'rm;faltas', RA.map((rm, i) => `${rm};${FALTAS_B[i]}`));
  await J.destacar(page, '#importarFaltas input[name="arquivo"]');
  await page.setInputFiles('#importarFaltas input[name="arquivo"]', f);
  await J.clicarENavegar(page, '#botaoImportarFaltas');
  J.ok(/importadas: 5/i.test(await J.alerta(page)), 'faltas B: ' + (await J.alerta(page)).slice(0, 80));

  await J.ir(page, `painel/relatorios/frequencia-oferta?oferta=${B}`);
  await J.passo(page, 'FS/FI (Relatórios → Frequência da oferta B).\nConfira: frequência = 1 − (faltas − compensadas) ÷ encontros; o aluno 5 com 60,00% fica FI; os outros, FS. Com a oferta aberta, o FS/FI é provisório ("prov.").');
  const linhas = await page.$$eval('table.lista-dados tbody tr', trs => trs.map(t => [...t.querySelectorAll('td')].map(d => d.textContent.trim())));
  const fi = linhas.filter(l => l.some(c => /^FI/.test(c))).map(l => l[0]);
  J.ok(fi.length === 1 && fi[0] === RA[4], 'FI só no aluno 5 (' + fi.join(', ') + '); linhas: ' + linhas.map(l => l[0] + ' ' + l.slice(-2).join(' ')).join(' | '));
  await J.ir(page, `painel/relatorios/frequencia-oferta?oferta=${A}`);
  await J.passo(page, 'E a oferta A (chamada): P/F por encontro, com o total de 3 encontros registrados. Confira o aluno 3 (1 falta, 1 compensada) e o aluno 5 (3 faltas).');
  const la = await page.$$eval('table.lista-dados tbody tr', trs => trs.map(t => [...t.querySelectorAll('td')].map(d => d.textContent.trim())));
  J.ok(la.length === 5, 'oferta A: ' + la.map(l => l[0] + ' ' + l.slice(-5).join('/')).join(' | '));

  /* Antecipado do bloco 11: a Frequência perto do mínimo (R-FRE-02) e o Alerta de risco olham as ofertas ABERTAS — depois do
     encerramento (bloco 8) as da jornada saem deles. Por isso, aqui, com as ofertas ainda abertas. */
  const curso = J.estado().curso;
  const linhasFreq = async () => page.$$eval('table.lista-dados tbody tr', trs => trs.map(t => [...t.querySelectorAll('td')].map(d => d.textContent.trim())).filter(l => l.length > 3));
  await J.ir(page, `painel/relatorios/frequencia-minimo?curso=${curso}`);
  await J.passo(page, 'Relatório "Frequência perto do mínimo" (antecipado do bloco 11: ele só olha ofertas ABERTAS, e no bloco 8 elas encerram).\nConfira: abre em "Só os alertas". Na A (6 previstos): o aluno 5 abaixo, o aluno 4 no limite; o aluno 3 (1 falta, 1 compensada) NÃO aparece — sem falta efetiva não há alerta (1.17.3).');
  await J.destacar(page, '#f_mostrar');
  const so = await linhasFreq();
  J.ok(!so.some(l => l[0] === RA[2] && /\bA\b|jornada A/.test(l[2])), 'Só os alertas: ' + so.map(l => l[0] + ' ' + l[2].slice(0, 22) + ' → ' + l[l.length - 1]).join(' | '));
  await J.escolher(page, '#f_mostrar', 'todos');
  await J.clicarENavegar(page, '#filtrosRelatorio button[type="submit"]');
  await J.passo(page, '"Mostrar todos": entram os "Sem alerta". Os cartões de cima contam sempre todos.');
  const todos = await linhasFreq();
  J.ok(todos.length > so.length, `Mostrar todos: ${todos.length} linha(s) contra ${so.length} nos alertas`);
  await J.ir(page, `painel/relatorios/alunos-risco?curso=${curso}`);
  await J.passo(page, 'Relatório "Alunos em risco" (também antecipado): os sinais de cada aluno — frequência, notas abaixo da mínima.\nConfira o aluno 5.');
  const risco = await linhasFreq();
  J.ok(risco.some(l => l[0] === RA[4]), 'risco: ' + risco.map(l => l[0] + ' ' + l.slice(-1)[0].slice(0, 60)).join(' | '));
  return 'Chamada de 3 encontros; 1 compensação; faltas da B por CSV; FS/FI conferido; frequência perto do mínimo (alertas/todos) e risco, com as ofertas abertas.';
});
