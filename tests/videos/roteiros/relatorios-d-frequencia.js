// Guia: Relatórios D — frequência (secretaria). A frequência da oferta e "Abaixo ou perto do mínimo": abre nos alertas,
// os quatro cartões, "Mostrar todos", a ordem por "Faltas que ainda pode ter" e a exportação. As faltas extras são inventadas
// (um aluno abaixo do mínimo, outro no limite); o retrato desfaz.
const fs = require('fs');
const path = require('path');

async function baixar(v, sel) {
  const pasta = path.join(__dirname, '..', 'saida'); fs.mkdirSync(pasta, { recursive: true });
  const [d] = await Promise.all([v.page.waitForEvent('download', { timeout: 30000 }), v.clicar(sel)]);
  await d.saveAs(path.join(pasta, d.suggestedFilename()));
  return d.suggestedFilename();
}

module.exports = {
  conta: 'secretaria.demo',
  sql: ids => `UPDATE rm_disciplina_faltas SET faltas = 4 WHERE rm = '260104' AND rm_prof_mat_id = ${ids.oferta.GOC};
UPDATE rm_disciplina_faltas SET faltas = 3 WHERE rm = '260106' AND rm_prof_mat_id = ${ids.oferta.STF};`,
  async gravar(v) {
    const curso = String(v.ids.curso);
    const filtrar = () => v.clicarENavegar('#filtrosRelatorio button[type="submit"]');
    const cartao = rot => v.page.locator('.vc-cartao-rel', { has: v.page.locator('.text-secondary', { hasText: rot }) }).first();
    const valor = async rot => (await cartao(rot).locator('.h1').textContent()).trim();

    v.capitulo('Frequência da oferta');
    await v.ir('painel/relatorios/frequencia-oferta');
    await v.passo('Frequência da oferta: escolha a oferta. Com lançamento, as faltas lançadas; com chamada, uma coluna por encontro.');
    await v.escolher('#f_oferta', String(v.ids.oferta.GOC));
    await filtrar();
    const c = await v.page.$$eval('table.lista-dados thead th', t => t.map(x => x.textContent.trim()));
    v.confere(c.some(t => /Compensadas/i.test(t)) && c.includes('FS/FI'), 'a frequência da oferta deveria ter compensadas e FS/FI: ' + c.join(' | '));
    await v.passo('Faltas, compensadas, a frequência e FS/FI: 1 − (faltas − compensadas) ÷ encontros, a mesma conta do boletim.');
    await v.destacar('table.lista-dados');

    v.capitulo('Abaixo ou perto do mínimo');
    await v.ir('painel/relatorios/frequencia-minimo');
    await v.escolher('#f_curso', curso);
    await filtrar();
    v.confere(await v.page.$eval('#f_mostrar', s => s.value) === 'alertas', 'o relatório deveria abrir em "Só os alertas"');
    const n = { abaixo: await valor('Abaixo do mínimo'), limite: await valor('No limite'), perto: await valor('Perto do mínimo'), sem: await valor('Sem alerta') };
    v.confere(n.abaixo === '1' && n.limite === '1' && +n.perto > 0 && +n.sem > 0, 'os quatro grupos deveriam ter alunos: ' + JSON.stringify(n));
    const alertas = await v.page.locator('table.lista-dados tbody tr').count();
    v.confere(alertas === +n.abaixo + +n.limite + +n.perto, `a tabela (${alertas}) deveria ter só os alertas`);
    v.poster();
    await v.passo('Todas as ofertas abertas. Abre em "Só os alertas": abaixo do mínimo, no limite e perto.');
    await v.destacar('#f_mostrar');
    await v.passo('Os cartões mostram sempre as quatro contagens, qualquer que seja o filtro.');
    await v.destacar(cartao('Abaixo do mínimo'));
    await v.destacar(cartao('No limite'));
    await v.destacar(cartao('Perto do mínimo'));
    await v.destacar(cartao('Sem alerta'));
    await v.passo('Abaixo: já passou do mínimo. No limite: não pode faltar mais. Perto: tem falta e pode faltar até a margem.');
    await v.clicarENavegar(cartao('No limite').locator('a.card-footer'));
    v.confere(/detalhe=limite/.test(v.page.url()), 'a lista "No limite" deveria abrir');
    await v.passo('Clique num cartão para ver a lista daquele grupo.');
    await v.clicarENavegar('a:has-text("Voltar ao relatório")');

    v.capitulo('Mostrar todos');
    await v.passo('Para ver também os "Sem alerta", escolha Mostrar todos e clique em Filtrar. O filtro fica no link.');
    await v.escolher('#f_mostrar', 'todos');
    await filtrar();
    const todos = await v.page.locator('table.lista-dados tbody tr').count();
    v.confere(/mostrar=todos/.test(v.page.url()) && todos === alertas + +n.sem, `com "Mostrar todos", a tabela (${todos}) deveria somar os quatro grupos`);
    await v.passo('Ordene por "Faltas que ainda pode ter" para ver primeiro quem está pior.');
    const th = 'table.lista-dados thead th:has-text("Faltas que ainda pode ter")';
    await v.clicar(th);
    await v.pausa(400);
    await v.clicar(th);
    await v.pausa(600);
    await v.rolar('table.lista-dados');
    v.confere(/Abaixo do mínimo/.test(await v.texto('table.lista-dados tbody tr:first-child')), 'na ordem crescente, quem está abaixo do mínimo vem primeiro');
    await v.destacar('table.lista-dados tbody tr:first-child');
    await v.passo('A exportação (Excel, CSV, PDF, Imprimir) leva só o que a tabela mostra, com o filtro no cabeçalho.');
    const arq = await baixar(v, 'button.buttons-excel');
    v.confere(/\.xlsx$/.test(arq), 'o Excel deveria ser baixado: ' + arq);
  },
};
