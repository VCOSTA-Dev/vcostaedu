// Guia: Relatórios G — documentos emitidos (administrador: só ele cancela emissão). Filtros, cartões por tipo, Canceladas,
// "Emitidos antes de reabertura" e o código que abre a emissão. Uma reabertura inventada é preparada; o retrato desfaz.
module.exports = {
  conta: 'administrador',
  sql: ids => `INSERT INTO rm_professores_materias_encerramentos (rm_prof_mat_id, acao, observacao, usuario_id, created)
VALUES (${ids.oferta.FAP}, 'reabrir', 'Correção de nota lançada errada (demonstração).', ${ids.usuario['secretaria.demo']}, NOW());`,
  async gravar(v) {
    const filtrar = () => v.clicarENavegar('#filtrosRelatorio button[type="submit"]');
    const cartao = rot => v.page.locator('.vc-cartao-rel', { has: v.page.locator('.text-secondary', { hasText: rot }) }).first();
    const valor = async rot => (await cartao(rot).locator('.h1').textContent()).trim();

    v.capitulo('Abrir e filtrar');
    await v.ir('painel/relatorios/listar');
    await v.passo('Relatórios → Documentos emitidos: que documentos saíram e qual pode estar desatualizado.');
    await v.clicarENavegar('.page-wrapper a[href$="relatorios/documentos-emitidos"]');
    const total = await v.page.locator('table.lista-dados tbody tr').count();
    v.confere(total >= 3, 'as emissões da demonstração deveriam aparecer: ' + total);
    await v.passo('Cada emissão gravada: histórico parcial, final e atestado, pela secretaria ou pelo portal do aluno.');
    await v.passo('Filtre por curso, período de emissão, tipo, origem ou situação (válida ou cancelada).');
    await v.destacar('#filtrosRelatorio');

    v.capitulo('Os cartões');
    const antes = await valor('Emitidos antes de reabertura');
    v.confere(+antes >= 1, 'deveria haver documento emitido antes de reabertura: ' + antes);
    v.confere(await cartao('Canceladas').count(), 'o cartão Canceladas deveria aparecer');
    v.poster();
    await v.passo('Os cartões contam cada tipo, as Canceladas e os Emitidos antes de reabertura.');
    await v.destacar(cartao('Canceladas'));
    await v.passo('"Emitido antes de reabertura": uma disciplina do aluno foi reaberta depois da emissão. O documento pode estar desatualizado.');
    await v.destacar(cartao('Emitidos antes de reabertura'));
    await v.clicarENavegar(cartao('Emitidos antes de reabertura').locator('a.card-footer'));
    v.confere(/detalhe=d_antes/.test(v.page.url()), 'a lista "Emitidos antes de reabertura" deveria abrir');
    await v.passo('Confira se a reabertura mudou a nota. Se mudou, emita de novo; o antigo, só o administrador cancela.');
    await v.destacar('table.lista-dados thead th:has-text("Atenção")');

    v.capitulo('O código abre a emissão');
    const codigo = v.page.locator('table.lista-dados tbody tr:first-child a[href*="historico-emissoes/ver/"]').first();
    v.confere(await codigo.count(), 'o código deveria ser um link para a emissão');
    await v.passo('O código abre a emissão, com o PDF. O quadro de cancelamento aparece só para o administrador (aqui, a conta é a dele).');
    await v.clicarENavegar(codigo);
    v.confere(await v.page.$('#pdfOriginal') && await v.page.$('input[name="CancelarEmissao"]'), 'a emissão deveria mostrar o PDF e o cancelamento');
    await v.destacar('#pdfOriginal');
    await v.rolar('input[name="CancelarEmissao"]');
    await v.passo('O administrador informa o motivo (obrigatório) e clica em "Cancelar emissão". A secretaria vê a emissão e o PDF e pede a ele.');
    await v.destacar('textarea[name="motivo"]');
  },
};
