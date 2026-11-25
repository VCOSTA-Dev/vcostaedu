// Guia: Relatórios F — situação do TCC (secretaria). Os cartões Sem TCC / Em andamento / Aprovado / Reprovado, a lista de
// um cartão, o filtro "Situação do TCC" e a tabela com nota, mínima, orientador e defesa. A preparação faz o curso principal
// exigir TCC (para haver "Sem TCC"); o retrato desfaz.
module.exports = {
  conta: 'secretaria.demo',
  sql: ids => `UPDATE rm_cursos SET exige_tcc = 1 WHERE id = ${ids.curso};`,
  async gravar(v) {
    const filtrar = () => v.clicarENavegar('#filtrosRelatorio button[type="submit"]');
    const cartao = rot => v.page.locator('.vc-cartao-rel', { has: v.page.locator('.text-secondary', { hasText: new RegExp('^' + rot + '$') }) }).first();
    const valor = async rot => (await cartao(rot).locator('.h1').textContent()).trim();

    v.capitulo('Abrir o relatório');
    await v.ir('painel/relatorios/listar');
    await v.passo('Relatórios → Situação do TCC: como estão os TCC de quem vai concluir.');
    await v.clicarENavegar('.page-wrapper a[href$="relatorios/situacao-tcc"]');
    const n = { sem: await valor('Sem TCC'), and: await valor('Em andamento'), ap: await valor('Aprovado'), rep: await valor('Reprovado') };
    v.confere(+n.sem > 0 && +n.and > 0 && +n.ap > 0, 'os cartões deveriam contar Sem TCC, Em andamento e Aprovado: ' + JSON.stringify(n));
    v.poster();
    await v.passo('Entram as matrículas de cursos que exigem TCC e as que têm TCC lançado. Filtre por curso, situação ou previsão.');
    await v.destacar('#filtrosRelatorio');
    await v.passo('Os cartões contam Sem TCC, Em andamento, Aprovado e Reprovado.');
    for (const r of ['Sem TCC', 'Em andamento', 'Aprovado', 'Reprovado']) await v.destacar(cartao(r));

    v.capitulo('A tabela');
    await v.rolar('table.lista-dados');
    await v.passo('A tabela traz a situação do TCC, a nota, a nota mínima da regra do curso, o orientador e a data da defesa.');
    await v.destacar('table.lista-dados thead th:has-text("Nota mínima")');
    const ap = v.page.locator('table.lista-dados tbody tr', { hasText: 'Aprovado' }).first();
    v.confere(/9,0/.test(await ap.textContent()), 'o TCC aprovado deveria mostrar a nota');
    await v.destacar(ap);

    v.capitulo('Cobrar quem não tem TCC');
    await v.rolar(-3000);
    await v.passo('Para cobrar quem ainda não tem TCC: clique no cartão Sem TCC, ou filtre "Situação do TCC: Sem TCC".');
    await v.escolher('#f_tcc', 'sem');
    await filtrar();
    const linhas = await v.page.locator('table.lista-dados tbody tr').count();
    v.confere(/tcc=sem/.test(v.page.url()) && linhas === +n.sem, `o filtro deveria listar os ${n.sem} sem TCC (${linhas})`);
    await v.rolar('table.lista-dados');
    await v.passo('Já defendeu e aparece "Sem TCC"? Lance o TCC na ficha do aluno (aba Matrícula).');
    await v.destacar('table.lista-dados tbody tr:first-child td:nth-child(2) a');
  },
};
