// Guia: Emitir atestado de matrícula (secretaria e aluno). Sem a previsão de conclusão o atestado não sai; a secretaria
// preenche a previsão, emite pela ficha e o próprio aluno emite pelo portal. Aluna fictícia Carla (260103); desde a 1.18.2
// o curso da demonstração já tem nível, nome por extenso e sigla (o seed). O banco volta ao retrato no fim.
const RA = '260103';
module.exports = {
  conta: 'secretaria.demo',
  contas: [RA],
  sql: ids => `UPDATE rm_matriculas_curso SET previsao_conclusao = NULL WHERE id = ${ids.matricula[RA]};`, // a demonstração de hoje já traz a previsão: o vídeo começa sem ela
  async gravar(v) {
    const mc = v.ids.matricula[RA];
    const ficha = `painel/academicos/visualizar/${v.ids.aluno[RA]}`;
    v.capitulo('Sem a previsão de conclusão');
    await v.ir(ficha + '?tab=tabeHistoricos');
    await v.passo('Ficha do aluno, aba Históricos. A matrícula está "Cursando": clique em "Emitir atestado".');
    await v.clicarENavegar(`#historicosAluno a.botao-atestado[href*="emitir/${mc}?tipo=ATESTADO"]`);
    const bloq = await v.texto('#bloqueios');
    v.confere(/previsão de conclusão/.test(bloq), 'sem a previsão, o atestado deveria ser bloqueado: ' + bloq);
    await v.passo('Bloqueado: "Falta a previsão de conclusão da matrícula". Sem ela, a frase do atestado ficaria quebrada.');
    await v.destacar('#bloqueios');

    v.capitulo('Preencher a previsão');
    await v.ir(ficha + '?tab=tabeMatricula');
    const fv = `.vinculo[data-matricula="${mc}"] form.formVinculo`;
    await v.passo('Aba Matrículas: em "Previsão de conclusão", use a sugestão (o fim das ofertas da grade) ou digite a data.');
    await v.clicar(fv + ' .usarSugestao');
    v.confere(await v.page.inputValue(fv + ' [name="previsao_conclusao"]'), 'a previsão deveria ser preenchida');
    await v.clicarENavegar(fv + ' button[type="submit"]');
    v.confere(/atualizada/.test(await v.alerta()), 'a matrícula deveria ser salva: ' + await v.alerta());
    await v.passo('Salvo. A previsão aparece na matrícula como mês e ano — é assim que sai no atestado.');
    await v.destacar(`.vinculo[data-matricula="${mc}"] .previsaoMesAno`);

    v.capitulo('Emitir pela ficha (secretaria)');
    await v.ir(`painel/historico-emissoes/emitir/${mc}?tipo=ATESTADO`);
    v.confere(!(await v.page.$('#bloqueios')), 'agora o atestado não deveria ter bloqueio: ' + await v.texto('#bloqueios'));
    await v.passo('De volta a "Emitir atestado": confira o modelo. "Prévia em PDF" sai com a tarja PRÉVIA; nada é gravado.');
    await v.destacar('#modelo');
    await v.pdf('#botaoPrevia', 6);
    if (await v.page.$('#confirmo_alertas')) await v.clicar('label[for="confirmo_alertas"]');
    await v.passo('Clique em "Emitir atestado de matrícula". A emissão guarda a cópia fiel dos dados do dia, o PDF e o código.');
    await v.clicarENavegar('input[name="EmitirHistorico"]');
    v.confere(await v.page.$('#emitido'), 'o atestado deveria ser emitido: ' + await v.alerta());
    await v.passo(`Emitido, com o código ${await v.texto('#codigoEmissao')}. O PDF:`);
    await v.destacar('#codigoEmissao');
    await v.pdf('#pdfOriginal', 6);

    v.capitulo('Pelo portal (aluno)');
    await v.passo('O próprio aluno também emite: ele entra no portal com o RA e a senha.');
    await v.entrar(RA);
    v.confere(!(await v.page.$('input[name="SendLogin"]')), 'o aluno deveria entrar no portal: ' + v.page.url());
    const menu = v.page.locator('a[href$="atestado/index"]').first();
    await v.passo('Menu Acadêmico → Atestado de matrícula.');
    if (await menu.count() && await menu.isVisible()) await v.clicarENavegar(menu); else await v.ir('portal/atestado/index');
    await v.passo('Na matrícula em curso, "Emitir atestado".');
    await v.clicarENavegar(`.matricula-atestado[data-matricula="${mc}"] .botao-atestado`);
    v.confere(await v.page.$('#atestadoEmitido'), 'o atestado pelo portal deveria ser emitido: ' + await v.alerta());
    const linhas = await v.page.$$eval('#atestadosEmitidos tbody tr', t => t.length);
    v.confere(linhas >= 2, 'a lista deveria trazer o atestado do portal e o da secretaria: ' + linhas);
    await v.passo('Em "Atestados emitidos" ficam os do aluno e também os que a secretaria emitiu para ele.');
    await v.destacar('#atestadosEmitidos');
    v.poster();
    await v.passo('No PDF do portal, no lugar da assinatura: "Documento emitido eletronicamente em … Confira a autenticidade em …".');
    await v.pdf('#atestadosEmitidos tbody tr:first-child a[href*="atestado/pdf/"]', 7);
  },
};
