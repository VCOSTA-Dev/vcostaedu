// Bloco 10 — Portal do aluno: a secretaria emite o link de acesso do aluno 2; numa OUTRA janela (outro contexto, sem a
// sessão do painel) o aluno define a senha pelo link, entra, vê o boletim com "Como foi calculado", o "Meu progresso" e
// emite o próprio atestado — com o texto de emissão eletrônica no PDF.
const fs = require('fs');
const path = require('path');
const J = require('./lib');
const { ALUNOS } = require('./dados');

J.rodar(10, 'Portal do aluno', async ({ browser, page }) => {
  const E = J.estado();
  const aluno = ALUNOS[1];
  const id = E.alunos[aluno.rm];

  await J.ir(page, `painel/academicos/visualizar/${id}`);
  await J.passo(page, `Painel, ficha do aluno 2 (${aluno.rm}): "Enviar link de acesso".\nConfira: esta base não tem SMTP, então a tela diz com honestidade que o e-mail NÃO foi enviado e mostra o link para a secretaria copiar.`);
  await J.clicarENavegar(page, 'button[name="EnviarLink"]');
  const aviso = await J.alerta(page);
  const link = await page.inputValue('#linkAcessoAluno').catch(() => '');
  J.ok(!!link && /definir-senha#t=/.test(link), 'link emitido (o token não vai para o registro); aviso: "' + aviso.slice(0, 110) + '"');
  if (!link) throw new Error('o link de acesso não apareceu na tela');
  await J.destacar(page, '#linkAcessoAluno');

  await J.passo(page, 'Abro uma OUTRA janela, sem a sessão do painel — como o aluno, no computador dele — e abro o link.');
  const ctx = await browser.newContext({ viewport: null });
  await ctx.addInitScript(J.PAINEL);
  const p = await ctx.newPage();
  const grav = await new J.Gravador(p, 'bloco10_portal').iniciar();
  p.on('dialog', d => d.accept());
  await require('../tabler/lib').confirmarSozinho(p); // D-100: a confirmação é a janela do sistema
  await p.goto(link, { waitUntil: 'load' });
  await p.waitForSelector('#senha', { timeout: 30000 });
  await J.passo(p, 'O link abre "Definir senha" (o token vai no corpo do pedido, não no endereço). O aluno escolhe a senha e repete.');
  const senha = 'Aluno' + Math.random().toString(36).slice(2, 10) + '7!';
  J.guardar('senhaAluno2', senha); // fica só em saida/ (fora do git), para o responsável pelo projeto explorar o portal
  await J.digitar(p, '#senha', senha);
  await J.digitar(p, '#senha_repetido', senha);
  await J.clicarENavegar(p, '#btnAtualizaSenha');
  J.ok(/senha/i.test(await J.alerta(p)) && !/inválido|não conferem/i.test(await J.alerta(p)), 'senha definida: "' + (await J.alerta(p)).slice(0, 90) + '"');

  if (!(await p.$('#usuario'))) await J.ir(p, 'portal/login/acesso');
  await J.passo(p, `Login no portal com o RA (${aluno.rm}) e a senha que o aluno acabou de definir.`);
  await J.digitar(p, '#usuario', aluno.rm);
  await J.digitar(p, 'input[name="senha"]', senha);
  await J.clicarENavegar(p, 'input[name="SendLogin"]');
  J.ok(/home/.test(p.url()), 'aluno no portal: ' + p.url().replace(J.C.base, ''));

  await J.ir(p, 'portal/home/boletim-aluno');
  await J.passo(p, 'BOLETIM do aluno: as 3 disciplinas da jornada, encerradas.\nAbro "Como foi calculado": a conta de cada disciplina — notas por componente, pesos, arredondamento e frequência.');
  const como = p.locator('#comoFoiCalculado summary').first();
  if (await como.count()) {
    await J.clicar(p, como); await p.waitForTimeout(600);
    await J.destacar(p, '#comoFoiCalculado');
    const t = (await p.locator('#comoFoiCalculado').first().innerText()).replace(/\s+/g, ' ');
    J.ok(/Disciplina jornada/.test(t), '"Como foi calculado": "' + t.slice(0, 220) + '"');
  } else J.ok(false, '"Como foi calculado" não apareceu no boletim');

  await J.ir(p, 'portal/progresso/index');
  await J.passo(p, '"MEU PROGRESSO": a carga horária cumprida, a média final das disciplinas encerradas e a tabela da grade.');
  await J.destacar(p, '#chCumprida');
  J.ok(true, `progresso: CH ${await J.texto(p, '#chCumprida')}; média ${await J.texto(p, '#mediaFinal')}; linhas ${await p.$$eval('#tabelaProgresso tbody tr', t => t.length).catch(() => 0)}`);

  await J.ir(p, 'portal/atestado/index');
  await J.passo(p, 'ATESTADO pelo portal: o próprio aluno emite (a matrícula está em curso). Emito e abro o PDF.\nConfira no rodapé do PDF: "Documento emitido eletronicamente em … Confira a autenticidade em … com o código …".');
  await J.clicarENavegar(p, '.botao-atestado');
  J.ok(!!(await p.$('#atestadoEmitido')), 'atestado pelo portal: "' + (await J.alerta(p)).slice(0, 90) + '"');
  const pdfLink = p.locator('#atestadosEmitidos a[href*="atestado/pdf/"]').first();
  const href = await pdfLink.getAttribute('href');
  const codigo = await J.texto(p, '#atestadosEmitidos tbody tr td.text-monospace');
  const [aba] = await Promise.all([ctx.waitForEvent('page', { timeout: 30000 }), J.clicar(p, pdfLink)]);
  await aba.waitForTimeout(6000);
  const r = await ctx.request.get(href);
  const arq = path.join(J.C.saida, 'atestado_portal.pdf');
  fs.writeFileSync(arq, await r.body());
  let txt = '';
  if (J.C.pdftotext) { try { txt = J.execFileSync(J.C.pdftotext, ['-enc', 'UTF-8', arq, '-'], { encoding: 'utf8' }).replace(/\s+/g, ' '); } catch (e) { txt = ''; } }
  fs.unlinkSync(arq); // export não fica guardado
  if (txt) J.ok(/emitido eletronicamente/i.test(txt) && txt.includes(codigo), 'texto do PDF: "' + (txt.match(/Documento emitido eletronicamente[^.]*\.[^.]*\.[^.]*\./i) || [txt.slice(-200)])[0] + '"');
  else J.ok(false, 'não foi possível ler o texto do PDF (pdftotext)');
  await aba.close(); await p.bringToFront();

  await J.passo(p, 'Fim do portal. O aluno sai e esta janela fecha; volto ao painel.');
  await J.ir(p, 'portal/login/logout');
  if (grav) await grav.parar();
  await ctx.close();
  await page.bringToFront();
  J.guardar('atestadoPortal', codigo);
  return `Link emitido (sem SMTP: mostrado à secretaria); senha definida pelo link; login; "Como foi calculado"; "Meu progresso"; atestado pelo portal ${codigo}.`;
});
