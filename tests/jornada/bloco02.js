// Bloco 2 — Regra de média: criar a regra da jornada (Atividades 40 opcional máx. 3 + Prova 60 obrigatória máx. 1),
// pesos somando 90 recusados, e o simulador (sem prova → Pendente; 4ª atividade → "(3/3)" desabilitado; com notas → média,
// resultado e a memória de cálculo).
const J = require('./lib');
const NOME = 'Regra jornada (jornada — apagar)';
const linhas = page => page.$$('#tabelaComponentes tr.linha-comp');

async function esperarSimulacao(page) {
  await page.waitForTimeout(400);
  await page.waitForFunction(() => document.getElementById('simEspera').textContent === '', null, { timeout: 15000 }).catch(() => {});
  await page.waitForTimeout(300);
}
async function addAtividade(page, nome, comp, nota) {
  await J.clicar(page, '#simAdd');
  const tr = page.locator('#simTabela tbody tr').last();
  await J.digitar(page, tr.locator('.s-nome'), nome);
  if (comp) { const v = await tr.locator('.s-comp option', { hasText: comp }).first().getAttribute('value'); await J.escolher(page, tr.locator('.s-comp'), v); }
  if (nota !== undefined) await J.digitar(page, tr.locator('.s-nota'), nota);
  await esperarSimulacao(page);
}

J.rodar(2, 'Regra de média', async ({ page }) => {
  await J.ir(page, 'painel/regra-media/listar');
  await J.passo(page, 'Configurações → Regras de média: o catálogo.\nConfira: o "Padrão da instituição" e onde cada regra é usada. Vamos criar a regra da jornada.');
  const nova = page.locator('a[href*="regra-media/cadastrar"]').first();
  if (await nova.count()) await J.clicarENavegar(page, nova); else await J.ir(page, 'painel/regra-media/cadastrar');
  J.ok(/regra-media\/cadastrar/.test(page.url()), 'tela de nova regra aberta');

  await J.passo(page, 'Nome e 1º componente: "Atividades", peso 40, média aritmética, OPCIONAL, máximo 3 atividades na disciplina.');
  await J.digitar(page, '#rNome', NOME);
  let l = await linhas(page);
  await J.digitar(page, await l[0].$('.c-nome'), 'Atividades');
  await J.digitar(page, await l[0].$('.c-peso'), '40');
  await J.escolher(page, await l[0].$('.c-modo'), 'aritmetica');
  if (await (await l[0].$('.c-obrig')).isChecked()) await J.clicar(page, await l[0].$('.c-obrig-rotulo'));
  await J.digitar(page, await l[0].$('.c-max'), '3');

  await J.passo(page, '2º componente: "Prova", peso 60, OBRIGATÓRIA (o mínimo vira 1), máximo 1.');
  while ((await linhas(page)).length < 2) await J.clicar(page, '#addComponente');
  l = await linhas(page);
  await J.digitar(page, await l[1].$('.c-nome'), 'Prova');
  await J.digitar(page, await l[1].$('.c-peso'), '60');
  await J.escolher(page, await l[1].$('.c-modo'), 'aritmetica');
  if (!(await (await l[1].$('.c-obrig')).isChecked())) await J.clicar(page, await l[1].$('.c-obrig-rotulo'));
  await J.digitar(page, await l[1].$('.c-max'), '1');
  const min = await (await l[1].$('.c-min')).inputValue();
  J.ok(min === '1', `Prova obrigatória: mínimo ${min}`);

  await J.passo(page, 'Pesos somando 90 são recusados.\nVou trocar o peso da Prova para 50. Confira: a soma mostra 90 e o botão Salvar desliga. Depois, o mesmo formulário enviado direto ao servidor também é recusado.');
  await J.digitar(page, await l[1].$('.c-peso'), '50');
  await page.waitForTimeout(400);
  await J.destacar(page, '#somaPesos');
  const soma = await J.texto(page, '#somaPesos'), desligado = await page.isDisabled('#salvarRegra');
  J.ok(/90/.test(soma) && desligado, `pesos 40 + 50: "${soma}", Salvar desligado: ${desligado}`);
  const recusa = await page.evaluate(async () => {
    const fd = new FormData(document.getElementById('formRegra'));
    const r = await fetch(location.href, { method: 'POST', body: fd, credentials: 'same-origin' });
    const h = await r.text(); const m = h.match(/id='regraRecusada'[\s\S]*?<\/div>/) || h.match(/<div class='alert alert-danger[\s\S]*?<\/div>/);
    return m ? m[0].replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim() : '';
  });
  J.ok(/100/.test(recusa), 'o servidor também recusa a soma 90: "' + recusa.slice(0, 140) + '"');
  await J.passo(page, 'O servidor recusou: "' + recusa.slice(0, 160) + '"\nAgora volto a Prova para 60 (soma 100) e salvo.');
  await J.digitar(page, await l[1].$('.c-peso'), '60');
  await page.waitForTimeout(300);
  await J.clicarENavegar(page, '#salvarRegra');
  const id = parseInt((page.url().match(/editar\/(\d+)/) || [])[1] || '0', 10);
  J.ok(id > 0 && /criada|salva/i.test(await J.alerta(page)), `regra criada pela tela (id ${id}): ` + (await J.alerta(page)).slice(0, 100));
  J.guardar('regraId', id); J.guardar('regraNome', NOME);

  await J.passo(page, 'Simulador, caso 1 — SEM prova.\nDuas atividades com notas 8 e 6, em "Atividades". Confira: o resultado fica "Pendente — falta Prova" (a Prova é obrigatória).');
  await J.destacar(page, '#simulador');
  while (await page.$('#simTabela tbody tr .s-tirar')) await J.clicar(page, '#simTabela tbody tr .s-tirar');
  if (!(await page.isChecked('#simFinal'))) await J.clicar(page, '#simFinal');
  await addAtividade(page, 'Atividade 1', 'Atividades', '8');
  await addAtividade(page, 'Atividade 2', 'Atividades', '6');
  await J.destacar(page, '#simResultado');
  const r1 = await J.texto(page, '#simResultado');
  J.ok(/Pendente.*falta Prova/i.test(r1), `simulador sem prova: "${r1}"`);

  await J.passo(page, 'Simulador, caso 2 — o MÁXIMO de Atividades é 3.\nVou pôr a 3ª atividade e abrir uma 4ª. Confira: na 4ª linha, a opção "Atividades (3/3)" aparece DESABILITADA.');
  await addAtividade(page, 'Atividade 3', 'Atividades', '7');
  await J.clicar(page, '#simAdd');
  await esperarSimulacao(page);
  const ult = page.locator('#simTabela tbody tr').last();
  await J.destacar(page, ult.locator('.s-comp'));
  const op = await ult.locator('.s-comp option').evaluateAll(os => os.map(o => ({ t: o.textContent.trim(), d: o.disabled })));
  const ativ = op.find(o => o.t.startsWith('Atividades')) || {};
  J.ok(/\(3\/3\)/.test(ativ.t || '') && ativ.d, `4ª atividade: a opção "${ativ.t}" está ${ativ.d ? 'DESABILITADA' : 'habilitada'}`);
  await J.clicar(page, ult.locator('.s-tirar'));
  await esperarSimulacao(page);

  await J.passo(page, 'Simulador, caso 3 — COM a prova.\nProva com nota 9. Confira a média e o resultado, e a memória de cálculo logo abaixo: (média das atividades × 40 + prova × 60) ÷ 100.');
  await addAtividade(page, 'Prova 1', 'Prova', '9');
  await J.destacar(page, '#simMedia');
  await J.destacar(page, '#simMemoria');
  const media = await J.texto(page, '#simMedia'), res = await J.texto(page, '#simResultado');
  const mem = await page.$$eval('#simMemoria li', a => a.map(x => x.textContent.trim()));
  J.ok(media !== '—' && /Aprovado|Reprovado/.test(res) && mem.length > 0, `com prova: média ${media}, "${res}"; memória: ${mem.join(' | ').slice(0, 220)}`);
  return `Regra "${NOME}" criada (id ${id}); simulador: Pendente sem prova, "(3/3)" desabilitado, média ${media} com a memória.`;
});
