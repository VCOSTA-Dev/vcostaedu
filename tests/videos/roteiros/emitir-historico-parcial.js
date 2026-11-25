// Guia: Emitir o histórico parcial (secretaria). Da aba Históricos da ficha: o modelo, a prévia, os alertas (a recusa
// sem a confirmação) e a emissão com o código. Aluna fictícia Ana (260101), sem a naturalidade na ficha para haver um alerta;
// o banco volta ao retrato no fim.
const RA = '260101';
module.exports = {
  conta: 'secretaria.demo',
  sql: ids => `UPDATE col_usuarios_informacoes SET nasc_local = NULL WHERE col_user_id = ${ids.aluno[RA]};`,
  async gravar(v) {
    const mc = v.ids.matricula[RA];
    v.capitulo('Abrir a emissão');
    await v.ir(`painel/academicos/visualizar/${v.ids.aluno[RA]}?tab=tabeHistoricos`);
    await v.passo('Ficha do aluno, aba Históricos: cada matrícula tem os botões de emissão. Clique em "Emitir parcial".');
    await v.clicarENavegar(`#historicosAluno a[href*="emitir/${mc}?tipo=PARCIAL"]`);
    v.confere(/tipo=PARCIAL/.test(v.page.url()) && !(await v.page.$('#bloqueios')), 'a tela de emissão do PARCIAL, sem bloqueio');

    v.capitulo('Modelo e prévia');
    await v.passo('Confira o tipo (Parcial) e o modelo: só aparecem os modelos ATIVOS que servem a este curso.');
    await v.destacar('#tipo');
    await v.destacar('#modelo');
    await v.passo('"Prévia em PDF": o documento com a tarja PRÉVIA, nada é gravado. Neste modelo, a disciplina em andamento sai em branco.');
    await v.pdf('#botaoPrevia', 7);

    v.capitulo('Os alertas');
    const alertas = await v.texto('#alertasEmissao');
    console.log('    (alertas: ' + alertas.slice(0, 200) + ')');
    v.confere(await v.page.$('#confirmo_alertas'), 'a Ana deveria ter alerta (falta a naturalidade): ' + alertas);
    await v.passo('Leia os alertas (aqui, falta a naturalidade da aluna). No PARCIAL, dá para emitir assim mesmo, confirmando.');
    await v.destacar('#alertasEmissao');
    await v.passo('Sem marcar a confirmação, a emissão é recusada:');
    await v.clicarENavegar('input[name="EmitirHistorico"]');
    v.confere(/confirme que os leu/.test(await v.texto('#emissaoRecusada')), 'sem a confirmação, deveria recusar: ' + await v.alerta());
    await v.destacar('#emissaoRecusada');
    await v.passo('Marque "Li os alertas acima e emito o documento assim mesmo": os alertas ficam gravados na emissão.');
    await v.clicar('label[for="confirmo_alertas"]');
    v.confere(await v.page.locator('#confirmo_alertas').isChecked(), 'a confirmação deveria ficar marcada');

    v.capitulo('Emitir');
    await v.passo('Clique em "Emitir histórico PARCIAL".');
    await v.clicarENavegar('input[name="EmitirHistorico"]');
    v.confere(await v.page.$('#emitido'), 'o PARCIAL deveria ser emitido: ' + await v.alerta());
    const codigo = await v.texto('#codigoEmissao');
    await v.passo(`Emitido: a emissão guarda a cópia fiel dos dados de hoje, o PDF e o código ${codigo} (impresso no rodapé).`);
    await v.destacar('#codigoEmissao');
    await v.destacar('#alertasAceitos');
    v.poster();
    await v.passo('O PDF emitido. Corrigiu uma nota depois? O documento emitido não muda: emita de novo.');
    await v.pdf('#pdfOriginal', 6);
  },
};
