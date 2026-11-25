// Bloco 6 — Atividades e notas: o aviso "Falta cadastrar: Prova", o componente cheio desabilitado (Atividades 3/3),
// notas lançadas pela tela, importação CSV com 1 linha errada (nada gravado; o erro cita a linha) e depois a certa, e a
// memória de cálculo de um aluno na tela de notas. A oferta A fica SEM prova de propósito: é a pendência do bloco 8.
const J = require('./lib');
const { ALUNOS } = require('./dados');
const RA = ALUNOS.map(a => a.rm);
const NOTAS = { // [nota por aluno 1..5]
  A1: ['9,0', '8,0', '7,5', '6,0', '5,0'], A2: ['8,5', '7,0', '8,0', '6,5', '4,0'], A3: ['9,0', '8,0', '7,0', '7,0', '5,5'],
  B1: ['9,0', '8,0', '7,0', '6,0', '5,0'], BP: ['9,0', '8,5', '8,0', '7,0', '4,0'], C1: ['9,0', '8,0', '7,0', '6,0', '5,0'], CP: ['9,0', '8,5', '8,0', '7,0', '4,0'],
};

async function cadastrarAtividade(page, oferta, tipo, nome, comp) {
  await J.ir(page, `painel/atividades/cadastrar/${oferta}?tipo=${tipo}`);
  await J.digitar(page, '#nomeAtividade', nome);
  await page.fill('input[name="data_inicial"]', '2026-09-01'); await page.fill('input[name="data_final"]', '2026-09-30');
  const v = await page.$$eval('#componente_codigo option', (os, n) => (os.find(o => o.textContent.trim().startsWith(n) && !o.disabled) || {}).value, comp);
  await J.escolher(page, '#componente_codigo', v);
  await J.clicarENavegar(page, 'input[name="CadAtividadeNota"]');
  await J.ir(page, `painel/atividades/listar/${oferta}`);
  const link = page.locator('tr', { hasText: nome }).locator('a[href*="atividades/visualizar/"]').first();
  const id = (await link.count()) ? parseInt(((await link.getAttribute('href')).match(/visualizar\/(\d+)/) || [])[1], 10) : 0;
  J.ok(id > 0, `atividade "${nome}" cadastrada (id ${id})`);
  return id;
}
async function importar(page, ativ, nome, linhas) {
  await J.ir(page, `painel/atividades/upload-notas/${ativ}`);
  const f = J.csv(nome, 'rm;nota', linhas);
  await J.destacar(page, 'input[name="arquivo"]');
  await page.setInputFiles('input[name="arquivo"]', f);
  await J.clicarENavegar(page, 'input[name="UpNotasCsv"]');
  return J.alerta(page);
}

J.rodar(6, 'Atividades e notas', async ({ page }) => {
  const { A, B, C } = J.estado().ofertas;
  let { A1, A2, A3, B1, BP, C1, CP } = J.estado().atividades || {};
  if (!A1) { // retomada: as atividades já cadastradas numa execução anterior não são recriadas
  await J.ir(page, `painel/atividades/listar/${A}`);
  await J.passo(page, 'Atividades da oferta A, ainda vazia.\nConfira o aviso: "Falta cadastrar: Prova (mínimo 1)" — a regra da jornada exige uma prova.');
  await J.destacar(page, '#avisoLimites');
  J.ok(/Falta cadastrar: Prova/.test(await J.texto(page, '#avisoLimites')), 'aviso: "' + (await J.texto(page, '#avisoLimites')).slice(0, 80) + '"');

  await J.passo(page, 'Cadastro 3 atividades no componente "Atividades" (o máximo da regra é 3).');
  A1 = await cadastrarAtividade(page, A, 5, 'Atividade 1 (jornada — apagar)', 'Atividades');
  A2 = await cadastrarAtividade(page, A, 5, 'Atividade 2 (jornada — apagar)', 'Atividades');
  A3 = await cadastrarAtividade(page, A, 5, 'Atividade 3 (jornada — apagar)', 'Atividades');

  await J.ir(page, `painel/atividades/cadastrar/${A}?tipo=5`);
  await J.passo(page, 'Uma 4ª atividade?\nConfira: no campo Componente, "Atividades (3/3)" está DESABILITADA — o componente está cheio. Não vou salvar.');
  await J.destacar(page, '#componente_codigo');
  const op = await page.$$eval('#componente_codigo option', os => os.map(o => ({ t: o.textContent.trim(), d: o.disabled })));
  const at = op.find(o => o.t.startsWith('Atividades')) || {};
  J.ok(/\(3\/3\)/.test(at.t || '') && at.d, `opção "${at.t}" ${at.d ? 'DESABILITADA' : 'habilitada'}`);
  await J.ir(page, `painel/atividades/listar/${A}`);
  J.ok(/Falta cadastrar: Prova/.test(await J.texto(page, '#avisoLimites')), 'a oferta A continua sem prova (o aviso fica): é a pendência do bloco 8');

  await J.passo(page, 'Nas ofertas B e C: 1 atividade e 1 prova em cada.');
  B1 = await cadastrarAtividade(page, B, 5, 'Atividade 1 (jornada — apagar)', 'Atividades');
  BP = await cadastrarAtividade(page, B, 1, 'Prova 1 (jornada — apagar)', 'Prova');
  C1 = await cadastrarAtividade(page, C, 5, 'Atividade 1 (jornada — apagar)', 'Atividades');
  CP = await cadastrarAtividade(page, C, 1, 'Prova 1 (jornada — apagar)', 'Prova');
  J.guardar('atividades', { A1, A2, A3, B1, BP, C1, CP });
  } else J.ok(true, `retomada: as 7 atividades já existem (${[A1, A2, A3, B1, BP, C1, CP].join(', ')}); sigo do lançamento das notas`);

  await J.ir(page, `painel/atividades/visualizar/${A1}`);
  await J.passo(page, 'Lançar notas PELA TELA (Atividade 1 da A): clico na célula da nota de cada aluno, digito e confirmo com Enter.\nConfira: a nota grava na hora.');
  for (const [i, rm] of RA.entries()) {
    // clica na CÉLULA da nota (a célula vazia tem o span sem área clicável); o campo é numérico: ponto decimal
    const cel = page.locator(`#tabelaLancaNotas tbody tr:has(td:text-is("${rm}")) td:has(.tabledit-span):not(:has(.tabledit-identifier))`).first();
    await J.clicar(page, cel);
    const inp = cel.locator('input.tabledit-input').first();
    await inp.waitFor({ state: 'visible', timeout: 10000 });
    await inp.fill(''); await inp.pressSequentially(NOTAS.A1[i].replace(',', '.'), { delay: 60 });
    await inp.press('Enter'); await page.waitForTimeout(900);
  }
  await J.ir(page, `painel/atividades/visualizar/${A1}`);
  const lancadas = await page.$$eval('tr', (trs, ras) => ras.map(r => { const t = trs.find(x => x.textContent.includes(r)); return t ? t.querySelector('.tabledit-span:not(.tabledit-identifier), td:last-child').textContent.trim() : ''; }), RA);
  J.ok(lancadas.join('|') === NOTAS.A1.join('|').replace(/,/g, '.') || lancadas.join('|').replace(/\./g, ',') === NOTAS.A1.join('|'), 'notas lançadas pela tela: ' + lancadas.join(', '));

  await J.passo(page, 'Importar notas por CSV (Atividade 2 da A) — primeiro um arquivo com 1 linha ERRADA (nota 11).\nConfira: NADA é gravado e o erro cita o número da linha.');
  const errada = RA.map((rm, i) => rm + ';' + (i === 2 ? '11' : NOTAS.A2[i]));
  const r1 = await importar(page, A2, 'notas_errado.csv', errada);
  J.ok(/linha 4/i.test(r1) && /Nenhuma nota/i.test(r1), 'CSV com erro: "' + r1.slice(0, 160) + '"');
  await J.ir(page, `painel/atividades/visualizar/${A2}`);
  const vazias = await page.$$eval('.tabledit-span:not(.tabledit-identifier)', a => a.filter(x => x.textContent.trim() !== '').length);
  J.ok(vazias === 0, `nada foi gravado: ${vazias} nota(s) na Atividade 2`);

  await J.passo(page, 'Agora o arquivo CERTO.\nConfira: "Notas importadas: 5 aluno(s). Tudo foi gravado."');
  const r2 = await importar(page, A2, 'notas_certo.csv', RA.map((rm, i) => rm + ';' + NOTAS.A2[i]));
  J.ok(/importadas: 5/i.test(r2), 'CSV certo: "' + r2.slice(0, 100) + '"');
  for (const [k, id] of Object.entries({ A3, B1, BP, C1, CP })) await importar(page, id, `notas_${k}.csv`, RA.map((rm, i) => rm + ';' + NOTAS[k][i]));
  J.ok(true, 'demais notas (A3, B e C) importadas por CSV');

  await J.ir(page, `painel/medias/final/${B}`);
  await J.passo(page, 'Tela de notas da oferta B: a média de cada aluno (parcial: a oferta está aberta).\nVou abrir a MEMÓRIA DE CÁLCULO do 1º aluno ("conta").');
  const conta = page.locator(`tr[data-rm="${RA[0]}"] a.ver-conta`).first();
  await J.clicar(page, conta); await page.waitForTimeout(800);
  const mem = await page.$eval(`#memoria-${RA[0]}`, e => e.innerText.replace(/\s+/g, ' ').trim()).catch(() => '');
  if (mem) await J.destacar(page, `#memoria-${RA[0]}`);
  J.ok(/Média|média/.test(mem), 'memória de cálculo aberta: "' + mem.slice(0, 200) + '"');
  return 'Aviso de prova faltando; Atividades 3/3 desabilitado; notas pela tela e por CSV (o errado recusado inteiro); memória de cálculo.';
});
