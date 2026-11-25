// Guia: Emitir o histórico final (secretaria). A recusa com a matrícula ainda em curso (João), e a emissão de quem
// concluiu (Irene): prévia, emissão, a tela da emissão, o PDF e a verificação pública. O banco volta ao retrato no fim.
module.exports = {
  conta: 'secretaria.demo',
  async gravar(v) {
    v.capitulo('Só com a matrícula Concluída');
    await v.ir(`painel/academicos/visualizar/${v.ids.aluno['250102']}?tab=tabeHistoricos`);
    await v.passo('Ficha do aluno, aba Históricos. João ainda está "Cursando" (o TCC não foi aprovado). Clique em "Emitir final".');
    await v.clicarENavegar(`#historicosAluno a[href*="emitir/${v.ids.matricula['250102']}?tipo=FINAL"]`);
    const bloq = await v.texto('#bloqueios');
    v.confere(/CONCLU[IÍ]DA/i.test(bloq), 'o FINAL deveria ser bloqueado pela situação: ' + bloq);
    v.confere(await v.page.$('input[name="EmitirHistorico"][disabled]'), 'o botão de emitir deveria estar desligado');
    await v.passo('Bloqueado: "O histórico FINAL só é emitido com a matrícula CONCLUÍDA". O botão fica desligado.');
    await v.destacar('#bloqueios');
    await v.destacar('input[name="EmitirHistorico"]');

    v.capitulo('Prévia de quem concluiu');
    await v.ir(`painel/academicos/visualizar/${v.ids.aluno['250101']}?tab=tabeHistoricos`);
    await v.passo('Irene concluiu o curso. Na aba Históricos dela, "Emitir final".');
    await v.clicarENavegar(`#historicosAluno a[href*="emitir/${v.ids.matricula['250101']}?tipo=FINAL"]`);
    v.confere(!(await v.page.$('#bloqueios')), 'o FINAL da Irene não deveria ter bloqueio: ' + await v.texto('#bloqueios'));
    await v.passo('Situação Concluído e o modelo FINAL. "Nenhum alerta": o FINAL só sai sem nenhum alerta.');
    await v.destacar('#situacaoMatricula');
    await v.destacar('#modelo');
    await v.destacar('#alertasEmissao');
    v.confere(/Nenhum alerta/.test(await v.texto('#alertasEmissao')), 'o FINAL da Irene deveria estar sem alertas: ' + await v.texto('#alertasEmissao'));
    await v.passo('"Prévia em PDF" mostra o documento com a tarja PRÉVIA. Nada é gravado.');
    await v.pdf('#botaoPrevia', 6);

    v.capitulo('Emitir');
    await v.passo('Sem bloqueios, clique em "Emitir histórico FINAL".');
    await v.clicarENavegar('input[name="EmitirHistorico"]');
    v.confere(await v.page.$('#emitido'), 'o FINAL deveria ser emitido: ' + await v.alerta());
    const codigo = await v.texto('#codigoEmissao');
    v.confere(/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/.test(codigo), 'código da emissão: ' + codigo);
    await v.passo(`Emitido. A tela da emissão mostra o código ${codigo}, o modelo e a versão usados e os hashes.`);
    await v.destacar('#codigoEmissao');
    await v.destacar('#dadosEmissao');
    v.poster();
    await v.passo('O PDF emitido, no desenho do modelo escolhido: as disciplinas com a nota final e a frequência, a média, o TCC e a situação.');
    await v.pdf('#pdfOriginal', 7);

    v.capitulo('A verificação pública');
    await v.passo('Por fim, o link da verificação pública: qualquer pessoa confere o documento pelo código.');
    await v.abrirAqui('#dadosEmissao a[target="_blank"]');
    v.confere(/Documento válido/.test(await v.texto('#situacaoVerificacao')), 'a verificação deveria dizer válido');
    await v.passo('"Documento válido", com o tipo, o curso e o nome do aluno.');
    await v.destacar('#resultadoVerificacao');
  },
};
