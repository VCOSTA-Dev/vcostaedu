// Guia: Relatórios A — painel acadêmico (secretaria). O filtro por curso, a leitura dos cartões, a lista de um número e de
// um percentual ("Ver a lista") e o período sem oferta encerrada ("—"). Só lê: nada muda no banco.
module.exports = {
  conta: 'secretaria.demo',
  async gravar(v) {
    const curso = String(v.ids.curso);
    v.capitulo('Abrir e filtrar');
    await v.ir('painel/relatorios/listar');
    await v.passo('Relatórios → Painel acadêmico: como está a instituição agora, num relance.');
    await v.clicarENavegar('.page-wrapper a[href$="relatorios/painel-academico"]');
    await v.passo('Filtre pelo curso e, se quiser, pelo período de encerramento. Clique em Filtrar.');
    await v.escolher('#f_curso', curso);
    await v.clicarENavegar('#filtrosRelatorio button[type="submit"]');
    v.confere(new RegExp('[?&]curso=' + curso).test(v.page.url()), 'o filtro de curso deveria ir para o endereço');

    v.capitulo('Os cartões');
    const cartao = rot => v.page.locator('.vc-cartao-rel', { has: v.page.locator('.text-secondary', { hasText: rot }) }).first();
    for (const r of ['Alunos ativos', 'Matrículas: Cursando', 'Ofertas abertas', 'Taxa de aprovação', 'Frequência média', 'Alunos em risco', 'Ofertas atrasadas'])
      v.confere(await cartao(r).count(), 'o cartão "' + r + '" deveria aparecer');
    v.poster();
    await v.passo('Alunos ativos (matrícula em Cursando), matrículas por situação e as ofertas abertas e encerradas.');
    await v.destacar(cartao('Alunos ativos'));
    await v.destacar(cartao('Ofertas abertas'));
    await v.rolar(cartao('Taxa de aprovação'));
    await v.passo('Aprovação e reprovação (por nota, por frequência) e a frequência média usam só resultados gravados de ofertas encerradas.');
    await v.destacar(cartao('Taxa de aprovação'));
    await v.destacar(cartao('Reprovação por frequência'));
    await v.rolar(cartao('Alunos em risco'));
    await v.passo('Alunos em risco, pendentes, ofertas com pendência e atrasadas olham as ofertas abertas.');
    await v.destacar(cartao('Alunos em risco'));
    await v.destacar(cartao('Ofertas atrasadas'));

    v.capitulo('A lista de cada número');
    const ativos = (await v.texto('.vc-cartao-rel:has(a[href*="detalhe=ativos"]) .h1')).trim();
    await v.rolar(-3000);
    await v.passo('Clique no número para ver quem compõe o número.');
    await v.clicarENavegar('.vc-cartao-rel .h1 a[href*="detalhe=ativos"]');
    const n1 = (await v.texto('.card-header .badge')).trim();
    v.confere(/detalhe=ativos/.test(v.page.url()) && n1 === ativos, `a lista de alunos ativos (${n1}) deveria ter ${ativos}`);
    await v.passo(`A lista dos ${ativos} alunos ativos. "Voltar ao relatório" volta aos cartões.`);
    await v.clicarENavegar('a:has-text("Voltar ao relatório")');
    const ap = v.page.locator('a.card-footer[href*="detalhe=aprovados"]');
    v.confere(await ap.count(), 'a taxa de aprovação deveria ter "Ver a lista"');
    await v.rolar(ap);
    await v.passo('Nos percentuais, clique em "Ver a lista (n)": os aprovados que formam a taxa.');
    await v.clicarENavegar(ap);
    v.confere(/detalhe=aprovados/.test(v.page.url()) && /Aprovados/.test(await v.texto('.card-header .card-title')), 'a lista de aprovados deveria abrir');
    await v.passo('Cada linha mostra a oferta, a média, a frequência e o resultado gravado.');
    await v.clicarENavegar('a:has-text("Voltar ao relatório")');

    v.capitulo('Período sem encerramento');
    await v.passo('O período filtra a data do encerramento. Num período sem oferta encerrada, os percentuais aparecem como "—".');
    await v.destacar('#f_de');
    await v.page.fill('#f_de', '2030-01-01');
    await v.clicarENavegar('#filtrosRelatorio button[type="submit"]');
    const taxa = (await v.texto('.vc-cartao-rel:has(.text-secondary:has-text("Taxa de aprovação")) .h1')).trim();
    v.confere(taxa === '—', 'sem oferta encerrada no período, a taxa deveria ser "—": ' + taxa);
    await v.rolar(cartao('Taxa de aprovação'));
    await v.destacar(cartao('Taxa de aprovação'));
    await v.passo('Para ver de novo tudo, limpe o período (ou clique em Limpar).');
  },
};
