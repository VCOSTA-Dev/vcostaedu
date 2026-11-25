// Bloco 8 — Encerramento: encerrar a A com a pendência (sem prova) é recusado com o motivo; resolver (prova + notas por
// CSV) e encerrar, com o aviso "Registrados 3 de 6 previstos"; reabrir sem justificativa é recusado; com justificativa,
// reaberta, com o fechamento anterior guardado; encerrar de novo. As ofertas B e C também são encerradas (para o bloco 9).
const J = require('./lib');
const { ALUNOS } = require('./dados');
const RA = ALUNOS.map(a => a.rm);
const PROVA_A = ['9,0', '8,0', '8,0', '7,0', '5,0'];

J.rodar(8, 'Encerramento', async ({ page }) => {
  const { A, B, C } = J.estado().ofertas;
  await J.ir(page, `painel/disciplinas/encerrar/${A}`);
  await J.passo(page, 'Encerrar a oferta A COM uma pendência: ela ainda não tem a Prova obrigatória.\nConfira: a tela mostra o motivo e NÃO oferece o botão de encerrar; na prévia, o resultado é "Pendente — falta Prova".');
  await J.destacar(page, '#impedimentos');
  const imp = await J.texto(page, '#impedimentos');
  J.ok(/Falta cadastrar: Prova/.test(imp) && !(await page.$('[name="ConfirmarEncerramento"]')), 'encerramento impedido: "' + imp.slice(0, 120) + '"');
  const forjado = await page.evaluate(async url => {
    const r = await fetch(url, { method: 'POST', body: new URLSearchParams({ ConfirmarEncerramento: '1' }), credentials: 'same-origin' });
    const h = await r.text(); const m = h.match(/<div class='alert alert-danger[\s\S]*?<\/div>/); return m ? m[0].replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim() : '';
  }, J.C.base + `painel/disciplinas/encerrar/${A}`).catch(() => '');
  J.ok(/RECUSADO/i.test(forjado), 'mesmo pedido direto ao servidor: "' + forjado.replace('×', '').slice(0, 140) + '"');
  await J.ir(page, `painel/disciplinas/encerrar/${A}`);

  await J.passo(page, 'RESOLVER: cadastro a Prova da oferta A e importo as notas dela por CSV.');
  await J.ir(page, `painel/atividades/cadastrar/${A}?tipo=1`);
  await J.digitar(page, '#nomeAtividade', 'Prova 1 (jornada — apagar)');
  await page.fill('input[name="data_inicial"]', '2026-09-01'); await page.fill('input[name="data_final"]', '2026-09-30');
  const v = await page.$$eval('#componente_codigo option', os => (os.find(o => o.textContent.trim().startsWith('Prova') && !o.disabled) || {}).value);
  await J.escolher(page, '#componente_codigo', v);
  await J.clicarENavegar(page, 'input[name="CadAtividadeNota"]');
  await J.ir(page, `painel/atividades/listar/${A}`);
  const link = page.locator('tr', { hasText: 'Prova 1' }).locator('a[href*="atividades/visualizar/"]').first();
  const AP = parseInt(((await link.getAttribute('href')).match(/visualizar\/(\d+)/) || [])[1], 10);
  await J.ir(page, `painel/atividades/upload-notas/${AP}`);
  await page.setInputFiles('input[name="arquivo"]', J.csv('notas_AP.csv', 'rm;nota', RA.map((rm, i) => `${rm};${PROVA_A[i]}`)));
  await J.clicarENavegar(page, 'input[name="UpNotasCsv"]');
  J.ok(/importadas: 5/i.test(await J.alerta(page)), 'prova da A: ' + (await J.alerta(page)).slice(0, 60));

  await J.ir(page, `painel/disciplinas/encerrar/${A}`);
  await J.passo(page, 'Prévia do encerramento da A, sem pendência.\nConfira o aviso "Registrados 3 de 6 previstos": não bloqueia; a frequência gravada é sobre os 3 encontros registrados. Encerro.');
  await J.destacar(page, '#avisoPrevistos');
  J.ok(/Registrados 3 de 6 previstos/.test(await J.texto(page, '#avisoPrevistos')), 'aviso: "' + (await J.texto(page, '#avisoPrevistos')).slice(0, 90) + '"');
  await J.clicarENavegar(page, '[name="ConfirmarEncerramento"]');
  const msg = await J.alerta(page);
  J.ok(/Oferta encerrada/.test(msg) && /Registrados 3 de 6 previstos/.test(msg), 'encerrada: "' + msg.slice(0, 200) + '"');

  await J.ir(page, `painel/disciplinas/encerrar/${A}`);
  await J.passo(page, 'REABRIR sem justificativa (só o administrador reabre).\nConfira: recusado — "Escreva a justificativa da reabertura".');
  await J.destacar(page, '#reabrirOferta');
  await page.fill('#observacao', '');
  await J.clicarENavegar(page, '#reabrirOferta button[type="submit"]');
  const r1 = await J.alerta(page);
  J.ok(/justificativa/i.test(r1), 'reabrir sem justificativa: "' + r1.slice(0, 100) + '"');

  await J.passo(page, 'Agora COM a justificativa.\nConfira: a oferta reabre e o fechamento anterior fica GUARDADO na lista de fechamentos (substituído), com a regra que valeu.');
  await J.digitar(page, '#observacao', 'Correção de nota de demonstração (jornada — apagar)');
  await J.clicarENavegar(page, '#reabrirOferta button[type="submit"]');
  J.ok(/reaberta/i.test(await J.alerta(page)), 'reaberta: ' + (await J.alerta(page)).slice(0, 90));
  await J.destacar(page, '#fechamentos');
  const fx = await page.$$eval('#fechamentos tr[data-numero]', t => t.map(x => x.dataset.numero + ':' + x.dataset.situacao));
  J.ok(fx.some(x => /substituido/.test(x)), 'fechamentos: ' + fx.join(', '));

  await J.passo(page, 'Encerrar de novo a oferta A. Depois, encerro também B e C (o histórico final do bloco 9 precisa das 3 encerradas).');
  await J.clicarENavegar(page, '[name="ConfirmarEncerramento"]');
  J.ok(/Oferta encerrada/.test(await J.alerta(page)), 'A encerrada de novo');
  for (const [k, o] of Object.entries({ B, C })) {
    await J.ir(page, `painel/disciplinas/encerrar/${o}`);
    await J.clicarENavegar(page, '[name="ConfirmarEncerramento"]');
    J.ok(/Oferta encerrada/.test(await J.alerta(page)), `${k} encerrada: ` + (await J.alerta(page)).slice(0, 80));
  }
  await J.ir(page, `painel/medias/final/${A}`);
  await J.passo(page, 'Tela de notas da A, encerrada: médias e resultados GRAVADOS, com FS/FI. Confira o aluno 5.');
  return 'Pendência recusada (tela e servidor); encerrada com o aviso 3 de 6; reabertura sem justificativa recusada e com justificativa aceita (fechamento guardado); encerrada de novo; B e C encerradas.';
});
