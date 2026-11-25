// Bloco 5 — Ofertas e enturmação: docente fictício (com titulação), oferta A por CHAMADA com 6 encontros previstos,
// ofertas B e C por LANÇAMENTO de faltas, os 5 alunos enturmados nas três, a troca da fonte recusada depois do primeiro
// registro, e a sugestão da previsão de conclusão (que agora existe: as ofertas têm data de fim).
const J = require('./lib');
const { ALUNOS } = require('./dados');
const DISC = { A: 'Disciplina jornada A (jornada — apagar)', B: 'Disciplina jornada B (jornada — apagar)', C: 'Disciplina jornada C (jornada — apagar)' };
const PROF = 'Professor Inventado (jornada — apagar)';

async function criarOferta(page, chave, encontros, prof) {
  await J.ir(page, 'painel/disciplinas/cadastrar');
  const lv = await page.$$eval('#matriz_id option', (os, n) => (os.find(o => o.textContent.includes(n)) || {}).value, DISC[chave]);
  await J.escolher(page, '#matriz_id', lv);
  await page.$eval('#matriz_id', s => s.dispatchEvent(new Event('change', { bubbles: true })));
  if (encontros) await J.digitar(page, '#encontros', String(encontros)); else await page.fill('#encontros', '');
  await J.digitar(page, '#codtur', 'JORN');
  if (await page.$('#ano')) await page.fill('#ano', '2026').catch(async () => page.selectOption('#ano', '2026'));
  const sede = await page.$$eval('#codfilial option', os => (os.find(o => /sede/i.test(o.textContent)) || os[0]).value);
  await J.escolher(page, '#codfilial', sede);
  await page.fill('#data_inicio', '2026-08-03'); await page.fill('#data_fim', '2026-12-11');
  await page.selectOption('#professores', [String(prof)]);
  await page.$eval('#professores', s => s.dispatchEvent(new Event('change', { bubbles: true })));
  await J.clicarENavegar(page, 'input[name="CadDisciplina"]');
  const link = page.locator('tr', { hasText: DISC[chave] }).filter({ hasText: 'JORN' }).locator('a[href*="disciplinas/visualizar"]').first();
  const id = (await link.count()) ? parseInt(((await link.getAttribute('href')).match(/visualizar\/(\d+)/) || [])[1], 10) : 0;
  J.ok(id > 0, `oferta ${chave} criada (id ${id}): ` + (await J.alerta(page)).slice(0, 70));
  return id;
}

J.rodar(5, 'Ofertas e enturmação', async ({ page }) => {
  page.on('dialog', d => d.accept()); // as confirmações do sistema (confirm) são aceitas pela jornada
  await require('../tabler/lib').confirmarSozinho(page); // D-100: a confirmação é a janela do sistema

  await J.ir(page, 'painel/usuarios/cadastrar');
  await J.passo(page, 'Primeiro, o DOCENTE (Equipe → Usuários): um professor fictício, com titulação — o histórico exige a titulação de quem deu a disciplina.');
  await J.digitar(page, '#nome', PROF);
  await J.escolher(page, '#genero_id', '3');
  await J.digitar(page, '#email', 'professor.jornada@prova.invalid');
  await J.digitar(page, '#usuario', 'professor.jornada');
  await J.digitar(page, '#senha', 'Jornada' + Math.random().toString(36).slice(2, 10) + '9');
  const nivelProf = await page.$$eval('#adms_niveis_acesso_id option', os => (os.find(o => /professor/i.test(o.textContent)) || {}).value);
  await J.escolher(page, '#adms_niveis_acesso_id', nivelProf);
  await J.escolher(page, '#adms_sits_usuario_id', '1');
  const tit = await page.$$eval('#titulacao_id option', os => (os.find(o => /mestre/i.test(o.textContent)) || os.find(o => o.value) || {}).value);
  await J.escolher(page, '#titulacao_id', tit);
  await J.clicarENavegar(page, 'input[name="CadUsuario"]');
  const linkProf = page.locator('tr', { hasText: PROF }).locator('a[href*="usuarios/visualizar"]').first();
  const prof = (await linkProf.count()) ? parseInt(((await linkProf.getAttribute('href')).match(/visualizar\/(\d+)/) || [])[1], 10) : 0;
  J.ok(prof > 0, `docente fictício criado (id ${prof}): ` + (await J.alerta(page)).slice(0, 70));
  J.guardar('professor', prof);

  await J.passo(page, 'Oferta A (Disciplina A) — vai ser por CHAMADA.\nPreencho turma, ano 2026, polo, o período (início e fim) e o docente. O total de encontros fica vazio: na chamada, ele vem dos encontros registrados.');
  const A = await criarOferta(page, 'A', null, prof);
  await J.passo(page, 'Ofertas B e C por LANÇAMENTO de faltas, com 20 encontros. (A C existe para o histórico final do bloco 9: o curso tem 3 disciplinas.)');
  const B = await criarOferta(page, 'B', 20, prof);
  const C = await criarOferta(page, 'C', 20, prof);
  J.guardar('ofertas', { A, B, C });

  await J.ir(page, `painel/chamada/listar/${A}`);
  await J.passo(page, 'Oferta A → Chamada: troco a fonte da frequência para "Chamada por encontro".\nConfira: o sistema pede confirmação; com a chamada, a tela de Faltas fica só para leitura.');
  await J.clicarENavegar(page, 'form[action*="chamada/fonte/"] button');
  J.ok(/Chamada/i.test(await J.alerta(page)) && (await page.$eval('#fonteFrequencia', d => d.dataset.fonte).catch(() => '')) === 'chamada', 'oferta A: ' + (await J.alerta(page)).slice(0, 80));

  await J.ir(page, `painel/disciplinas/editar/${A}`);
  await J.passo(page, 'Editar a oferta A: agora que ela é por chamada, aparece o campo "Encontros previstos". Informo 6.\nConfira: o total de encontros fica só para leitura (vem da chamada).');
  await J.digitar(page, '#encontros_previstos', '6');
  await J.clicarENavegar(page, 'input[name="EditDisciplina"]');
  J.ok(/sucesso|atualizad/i.test(await J.alerta(page)), 'oferta A com 6 encontros previstos: ' + (await J.alerta(page)).slice(0, 60));

  for (const [i, a] of ALUNOS.entries()) {
    await J.ir(page, `painel/portal-alunos/visualizar/${a.rm}?ano=2026`);
    if (i === 0) await J.passo(page, 'Enturmação (ficha do aluno → Enturmação): a matriz sugere as ofertas do ano para o curso e a grade do aluno.\nMarco as 3 ofertas da jornada e confirmo — o mesmo para os 5 alunos.');
    for (const o of [A, B, C]) { const cb = page.locator(`input[name="ofertas[]"][value="${o}"]`).first(); if (await cb.count() && !(await cb.isChecked())) { await J.destacar(page, cb); await cb.check(); } }
    await J.clicarENavegar(page, 'button:has-text("Confirmar enturmação")');
    J.ok(/confirmada/i.test(await J.alerta(page)), `${a.rm} enturmado: ` + (await J.alerta(page)).slice(0, 70));
  }

  await J.ir(page, `painel/chamada/encontro/${A}`);
  await J.passo(page, 'A troca da fonte é RECUSADA depois do primeiro registro.\nRegistro um encontro na A e depois tento voltar para "lançamento".');
  await page.fill('#data', '2026-08-03'); await J.digitar(page, '#titulo', 'Encontro de teste da fonte (jornada — apagar)');
  await J.clicarENavegar(page, '#salvarChamada');
  await J.ir(page, `painel/chamada/listar/${A}`);
  await J.clicarENavegar(page, 'form[action*="chamada/fonte/"] button');
  const recusa = await J.alerta(page);
  J.ok(/não mudou|encontro registrado|só é possível/i.test(recusa), 'troca da fonte com encontro registrado: RECUSADA — "' + recusa.slice(0, 140) + '"');
  await J.passo(page, 'Recusada: "' + recusa.slice(0, 150) + '"\nApago esse encontro de teste (lixeira), para a chamada do bloco 7 começar do zero.');
  const lixeira = page.locator('form[action*="chamada/excluir/"] button').first();
  if (await lixeira.count()) await J.clicarENavegar(page, lixeira);
  J.ok(!!(await page.$('#semEncontros')), 'encontro de teste excluído; a A continua por chamada, sem encontro');

  const aluno = J.estado().alunos[ALUNOS[0].rm];
  await J.ir(page, `painel/academicos/visualizar/${aluno}`);
  await J.clicar(page, '#tabeMatricula-tab'); await page.waitForTimeout(400);
  await J.passo(page, 'A SUGESTÃO da previsão de conclusão: agora as ofertas da grade têm data de fim.\nConfira: no formulário da matrícula, "Usar a sugestão" preenche a previsão com a maior data de fim (11/12/2026). Salvo.');
  const sug = page.locator('.usarSugestao').first();
  if (await sug.count()) {
    await J.clicar(page, sug);
    const prev = await page.locator('form.formVinculo .campoPrevisao').first().inputValue();
    J.ok(prev === '2026-12-11', `"Usar a sugestão" preencheu ${prev}`);
    await J.clicarENavegar(page, 'form.formVinculo button[type="submit"]');
    J.ok(/dezembro de 2026/.test(await J.texto(page, '.previsaoMesAno')), 'previsão salva: ' + (await J.texto(page, '.previsaoMesAno')));
  } else J.ok(false, 'o botão "Usar a sugestão" não apareceu');
  return `Docente fictício; ofertas A (chamada, 6 previstos), B e C (lançamento); 5 alunos enturmados; troca da fonte recusada; sugestão da previsão usada.`;
});
