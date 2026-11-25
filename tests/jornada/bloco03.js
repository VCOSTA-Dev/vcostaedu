// Bloco 3 — Curso, grade e matriz: curso da jornada (nível, sigla, linha do cabeçalho), 3 disciplinas na grade, mudar a
// ordem arrastando, aplicar a regra da jornada em lote, e a regra do TCC e dos documentos com o aviso de divergência.
const J = require('./lib');
const CURSO = 'Curso jornada (jornada — apagar)';
const DISC = ['Disciplina jornada A (jornada — apagar)', 'Disciplina jornada B (jornada — apagar)', 'Disciplina jornada C (jornada — apagar)'];
const REGRA6 = 'Regra jornada 6,0 (jornada — apagar)';

async function arrastar(page, de, para) {
  const h1 = page.locator(`tbody.ordenavel tr.linhaMatriz:nth-child(${de}) .alca-ordem`), h2 = page.locator(`tbody.ordenavel tr.linhaMatriz:nth-child(${para})`);
  await J.destacar(page, h1);
  const a = await h1.boundingBox(), c = await h2.boundingBox();
  await page.mouse.move(a.x + a.width / 2, a.y + a.height / 2); await page.mouse.down();
  for (let i = 1; i <= 12; i++) await page.mouse.move(a.x + a.width / 2, a.y + a.height / 2 + (c.y - a.y - 4) * i / 12);
  await page.mouse.up(); await page.waitForTimeout(400);
}

J.rodar(3, 'Curso, grade e matriz', async ({ page }) => {
  const E = J.estado();
  await J.ir(page, 'painel/cursos/cadastrar');
  await J.passo(page, 'Novo curso.\nPreencho o nome, o polo padrão e o "como o curso sai nos documentos": nível, sigla, nome por extenso e a linha do cabeçalho.');
  await J.digitar(page, '#nome', CURSO);
  const sede = await page.$$eval('#localidade option', os => (os.find(o => /sede/i.test(o.textContent)) || os[0]).value);
  await J.escolher(page, '#localidade', sede);
  await J.escolher(page, '#exige_tcc', '1');
  await J.digitar(page, '#nivel', 'Especialização');
  await J.digitar(page, '#abreviatura', 'JRN');
  await J.digitar(page, '#nome_extenso', 'Curso jornada (jornada — apagar)');
  await J.digitar(page, '#cabecalho_documento', 'Curso jornada de validação (jornada — apagar)');
  await J.clicarENavegar(page, 'input[name="CadCurso"]');
  const link = page.locator('tr', { hasText: CURSO }).locator('a[href*="cursos/visualizar"]').first();
  const curso = (await link.count()) ? parseInt(((await link.getAttribute('href')).match(/visualizar\/(\d+)/) || [])[1], 10) : 0;
  J.ok(curso > 0, `curso criado pela tela (id ${curso}): ` + (await J.alerta(page)).slice(0, 80));
  J.guardar('curso', curso); J.guardar('cursoNome', CURSO);

  await J.passo(page, 'As 3 disciplinas entram primeiro no catálogo de matérias (Ensino → Matérias), com a carga horária.');
  for (const [i, d] of DISC.entries()) {
    await J.ir(page, 'painel/materias/cadastrar');
    await J.digitar(page, '#nome', d);
    await J.digitar(page, '#carga_horaria', String([30, 45, 60][i]));
    await J.clicarENavegar(page, 'input[name="CadMaterias"]');
  }
  J.ok(true, '3 matérias da jornada no catálogo');

  await J.passo(page, 'Grade do curso (Ensino → Matriz): grade 1 e as 3 disciplinas, cada uma entra no fim da lista.');
  await J.ir(page, 'painel/matriz/cadastrar-grade');
  const cv = await page.$$eval('#curso_id option', (os, n) => (os.find(o => o.textContent.trim().startsWith(n)) || {}).value, CURSO);
  await J.escolher(page, '#curso_id', cv);
  await J.digitar(page, 'input[name="versao"]', '1');
  await J.clicarENavegar(page, 'input[name="SalvarGrade"]');
  const grade = parseInt((page.url().match(/matriz\/visualizar\/(\d+)/) || [])[1] || '0', 10);
  J.ok(grade > 0, `grade 1 criada (matriz/visualizar/${grade})`);
  J.guardar('grade', grade);
  for (const d of DISC) {
    const mod = await page.$$eval('#modulo_id option', os => (os.find(o => o.value) || {}).value);
    if (mod) await J.escolher(page, '#modulo_id', mod);
    const mv = await page.$$eval('#materia_id option', (os, n) => (os.find(o => o.textContent.trim().startsWith(n)) || {}).value, d);
    await J.escolher(page, '#materia_id', mv);
    await J.clicarENavegar(page, `form[action*="adicionar-disciplina"] button[type="submit"]`);
  }
  let ordem = await page.$$eval('tbody tr.linhaMatriz', trs => trs.map(t => t.textContent.replace(/\s+/g, ' ').trim()));
  J.ok(ordem.length === 3 && DISC.every((d, i) => ordem[i].includes(d)), 'a grade mostra A, B, C na ordem de inclusão');

  await J.passo(page, 'Mudar a ordem ARRASTANDO: vou levar a C para o topo e salvar.\nConfira: "Salvar a ordem" liga depois de arrastar, e a nova ordem fica C, A, B.');
  await arrastar(page, 3, 1);
  J.ok(!(await page.isDisabled('#btnSalvarOrdem')), 'depois de arrastar, "Salvar a ordem" fica ativo');
  await J.clicarENavegar(page, '#btnSalvarOrdem');
  ordem = await page.$$eval('tbody tr.linhaMatriz', trs => trs.map(t => t.textContent.replace(/\s+/g, ' ').trim()));
  J.ok(ordem[0].includes(DISC[2]) && /Ordem/.test(await J.alerta(page)), 'ordem salva: ' + ordem.map(x => x.match(/jornada ([ABC])/)[1]).join(', ') + ' — ' + (await J.alerta(page)).slice(0, 60));
  const linhas = await page.$$eval('tbody tr.linhaMatriz', trs => trs.map(t => +t.dataset.linha));
  J.guardar('linhas', linhas);

  await J.passo(page, 'Aplicar a regra da jornada em LOTE ("Aplicar a disciplinas…").\nMarco as 3 disciplinas do curso. Confira: antes de aplicar, a tela lista o que muda; depois, confirmo.');
  await J.ir(page, `painel/regra-media/aplicar/${E.regraId}?curso=${curso}`);
  for (const lid of linhas) { const c = page.locator(`#tabelaLote tr[data-linha="${lid}"] .marcaLinha`); if (await c.count()) { await J.destacar(page, c); await c.check(); } }
  await J.clicarENavegar(page, '#aplicarBtn');
  const muda = await page.$$eval('#listaMuda li', l => l.map(x => x.textContent.trim()));
  J.ok(muda.length === 3, `confirmação: ${muda.length} disciplina(s) mudam — ${muda.map(x => x.replace(/\(jornada — apagar\)/g, '')).join(' | ').slice(0, 200)}`);
  await J.passo(page, 'A lista do que muda está na tela. Confirmo a aplicação.');
  await J.clicarENavegar(page, '#aplicarBtn');
  J.ok(/aplicada/i.test(await J.alerta(page)), 'lote: ' + (await J.alerta(page)).slice(0, 80));

  await J.passo(page, 'Para mostrar a DIVERGÊNCIA, crio uma 2ª regra igual à padrão, mas com nota mínima 6,0.');
  await J.ir(page, 'painel/regra-media/cadastrar');
  await J.digitar(page, '#rNome', REGRA6);
  await J.digitar(page, '#rMinima', '6,0');
  await page.waitForTimeout(300);
  await J.clicarENavegar(page, '#salvarRegra');
  const r6 = parseInt((page.url().match(/editar\/(\d+)/) || [])[1] || '0', 10);
  J.ok(r6 > 0, `regra 6,0 criada (id ${r6})`); J.guardar('regra6', r6);

  await J.passo(page, 'Tela do curso → "Regra de média do TCC e dos documentos": escolho a regra 6,0.\nConfira: ANTES de salvar, o aviso diz que as disciplinas exigem outra nota mínima (a legenda dos documentos diria 6,0).');
  await J.ir(page, `painel/cursos/visualizar/${curso}`);
  await J.escolher(page, '#regraCurso', String(r6));
  await J.clicarENavegar(page, '#salvarRegraCurso');
  const conf = await J.texto(page, '#confirmarRegraCurso');
  J.ok(/exige|divergen|legenda/i.test(conf), 'aviso antes de confirmar: "' + conf.slice(0, 180) + '"');
  if (await page.$('#confirmarRegraCursoBtn')) {
    await J.passo(page, 'O aviso está na tela. Confirmo assim mesmo, para ver o aviso permanente no curso.');
    await J.clicarENavegar(page, '#confirmarRegraCursoBtn');
  }
  const aviso = await J.texto(page, '#avisoDivergencia');
  J.ok(/7,0|exige/i.test(aviso), 'tela do curso com o aviso de divergência: "' + aviso.slice(0, 160) + '"');

  await J.passo(page, 'ALINHAR: escolho para o curso a própria regra da jornada (a mesma nota mínima das disciplinas).\nConfira: salva sem aviso e o aviso some da tela do curso.');
  await J.escolher(page, '#regraCurso', String(E.regraId));
  await J.clicarENavegar(page, '#salvarRegraCurso');
  if (await page.$('#confirmarRegraCursoBtn')) await J.clicarENavegar(page, '#confirmarRegraCursoBtn');
  J.ok(!(await page.$('#avisoDivergencia')), 'alinhado: sem aviso de divergência — ' + (await J.alerta(page)).slice(0, 80));
  return `Curso ${curso}, grade ${grade} com C, A, B; regra em lote nas 3; divergência mostrada e alinhada.`;
});
