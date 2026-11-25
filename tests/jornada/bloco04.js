// Bloco 4 — Alunos e matrícula: 5 alunos fictícios (ficha completa), a recusa sem o sexo (navegador e servidor) e o vínculo
// ao curso da jornada, com a previsão de conclusão e o polo Sede.
const J = require('./lib');
const { ALUNOS } = require('./dados');

async function preencher(page, a, comSexo) {
  await J.digitar(page, '#rm', a.rm);
  await J.digitar(page, '#nome', a.nome);
  if (comSexo) await J.escolher(page, '#genero_id', a.sexo);
  await J.digitar(page, '#cpf', a.cpf);
  await J.digitar(page, '#email', a.email);
  await page.fill('#nasc_data', a.nasc);
  await J.digitar(page, '#nasc_local', a.local);
  if (await page.$('select#nasc_uf')) await page.selectOption('#nasc_uf', a.uf).catch(() => {}); else await page.fill('#nasc_uf', a.uf);
  await J.digitar(page, '#nasc_nac', a.nac);
  await J.digitar(page, '#rg', a.rg);
  await page.fill('#rg_org', 'SSP').catch(() => {});
  if (await page.$('select#rg_uf')) await page.selectOption('#rg_uf', a.uf).catch(() => {}); else await page.fill('#rg_uf', a.uf).catch(() => {});
  await J.digitar(page, '#nome_mae', a.mae);
}

J.rodar(4, 'Alunos e matrícula', async ({ page }) => {
  const E = J.estado();
  await J.ir(page, 'painel/academicos/cadastrar');
  await J.passo(page, 'Cadastro do 1º aluno fictício SEM escolher o sexo.\nConfira: o navegador barra o envio ("Sexo" é obrigatório).');
  await preencher(page, ALUNOS[0], false);
  J.ok((await page.$eval('#genero_id', s => s.value)) === '', 'Sexo em "Selecione" (sem padrão)');
  await J.clicar(page, 'input[name="CadUsuarioAcad"]');
  await page.waitForTimeout(800);
  const valido = await page.$eval('#genero_id', s => s.validationMessage).catch(() => '');
  J.ok(/cadastrar/.test(page.url()) && valido !== '', 'o navegador barrou o envio: "' + valido + '"');

  await J.passo(page, 'E se o navegador não barrar? Envio o mesmo formulário sem a trava do navegador.\nConfira: o SERVIDOR recusa — "Escolha o sexo do aluno" — e nada é gravado.');
  await Promise.all([page.waitForNavigation({ waitUntil: 'load' }), page.evaluate(() => {
    const f = document.querySelector('form'); document.getElementById('genero_id').removeAttribute('required');
    const i = document.createElement('input'); i.type = 'hidden'; i.name = 'CadUsuarioAcad'; i.value = 'Salvar'; f.appendChild(i);
    HTMLFormElement.prototype.submit.call(f);
  })]);
  J.ok(/sexo/i.test(await J.alerta(page)), 'o servidor recusou: "' + (await J.alerta(page)).slice(0, 100) + '"');

  const ids = {};
  for (const [i, a] of ALUNOS.entries()) {
    await J.ir(page, 'painel/academicos/cadastrar');
    if (i === 0) await J.passo(page, 'Agora o cadastro certo, COM o sexo, e a ficha completa (nascimento, naturalidade, RG, CPF, filiação) — o histórico final exige esses dados.');
    else if (i === 1) await J.passo(page, 'Os outros 4 alunos fictícios, do mesmo jeito.');
    await preencher(page, a, true);
    await J.clicarENavegar(page, 'input[name="CadUsuarioAcad"]');
    ids[a.rm] = parseInt((page.url().match(/academicos\/visualizar\/(\d+)/) || [])[1] || '0', 10);
    J.ok(ids[a.rm] > 0, `aluno ${a.rm} cadastrado (id ${ids[a.rm]})`);
  }
  J.guardar('alunos', ids);

  for (const [i, a] of ALUNOS.entries()) {
    await J.ir(page, `painel/academicos/visualizar/${ids[a.rm]}`);
    await J.clicar(page, '#tabeMatricula-tab'); await page.waitForTimeout(400);
    if (i === 0) await J.passo(page, 'Vínculo ao curso (aba Matrícula → "Vincular a um curso"): curso e grade, ingresso, previsão de conclusão e o polo Sede.\nConfira: o polo vazio seria "O do curso".');
    await J.escolher(page, '#vAno', '2026');
    const mod = await page.$$eval('#vModulo option', (os, n) => (os.find(o => o.textContent.includes(n)) || {}).value, E.cursoNome);
    await J.escolher(page, '#vModulo', mod);
    await page.fill('#vData', '2026-08-03');
    await page.fill('#vPrevisao', '2027-06-30');
    const sede = await page.$$eval('#vPolo option', os => (os.find(o => /sede/i.test(o.textContent)) || {}).value);
    if (sede) await J.escolher(page, '#vPolo', sede);
    await J.clicarENavegar(page, 'form[action*="academicos/vincular"] button[type="submit"]');
    J.ok(/vinculad/i.test(await J.alerta(page)), `${a.rm}: ` + (await J.alerta(page)).slice(0, 80));
  }
  await J.ir(page, `painel/academicos/visualizar/${ids[ALUNOS[0].rm]}`);
  await J.clicar(page, '#tabeMatricula-tab'); await page.waitForTimeout(400);
  await J.passo(page, 'Ficha do 1º aluno, aba Matrícula.\nConfira: o curso, a grade, o polo Sede e a previsão de conclusão (junho de 2027). A sugestão da previsão vem das ofertas da grade: aparece no bloco 5, depois de criadas as ofertas.');
  await J.destacar(page, '.vinculo');
  J.ok(/junho de 2027/.test(await J.texto(page, '.previsaoMesAno')), 'previsão: ' + (await J.texto(page, '.previsaoMesAno')));
  return '5 alunos fictícios com ficha completa; sexo obrigatório recusado no navegador e no servidor; 5 vínculos com polo Sede.';
});
