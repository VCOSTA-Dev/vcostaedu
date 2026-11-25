// Guia: Cancelar uma emissão (administrador). A recusa sem motivo, o cancelamento com motivo e a verificação pública
// passando a dizer "cancelado". Cancela o PARCIAL fictício do Diego; o banco volta ao retrato no fim.
module.exports = {
  conta: 'administrador',
  async gravar(v) {
    const em = v.ids.emissao.find(e => e.ra === '260104' && e.tipo === 'PARCIAL');
    v.capitulo('Abrir a emissão');
    await v.ir('painel/historico-emissoes/listar');
    await v.passo('Educacional → Históricos emitidos: a lista de todos os documentos emitidos, com a situação de cada um.');
    await v.destacar(`#listaEmissoes tr[data-codigo="${em.codigo}"]`);
    await v.passo(`Vamos cancelar o histórico PARCIAL ${em.codigo}. Clique em "Ver" na linha da emissão.`);
    await v.clicarENavegar(`#listaEmissoes tr[data-codigo="${em.codigo}"] a[href$="historico-emissoes/ver/${em.id}"]`);
    v.confere(/Válida/.test(await v.texto('#situacaoEmissao')), 'a emissão deveria estar válida');

    v.capitulo('O motivo é obrigatório');
    await v.passo('À direita, o quadro "Cancelar esta emissão" (só o administrador o vê). Sem motivo, o cancelamento é recusado.');
    await v.destacar('textarea[name="motivo"]');
    await v.clicarENavegar('input[name="CancelarEmissao"]');
    v.confere(/motivo/i.test(await v.texto('#cancelamentoRecusado')), 'sem motivo, deveria recusar: ' + await v.alerta());
    await v.passo('Recusado: "Informe o motivo do cancelamento (de 5 a 500 caracteres)". Nada mudou: a emissão continua válida.');
    await v.destacar('#cancelamentoRecusado');

    v.capitulo('Cancelar com motivo');
    await v.passo('Escreva o motivo (ele fica gravado) e clique em "Cancelar emissão". O sistema pede confirmação.');
    await v.digitar('textarea[name="motivo"]', 'Emitido por engano: o aluno pediu o documento de outro curso.');
    await v.clicarENavegar('input[name="CancelarEmissao"]');
    v.confere(await v.page.$('#cancelada'), 'a tela deveria confirmar o cancelamento: ' + await v.alerta());
    v.confere(/Cancelada/.test(await v.texto('#situacaoEmissao')), 'a situação deveria ser Cancelada');
    await v.passo('Cancelada. O documento NÃO é apagado: a situação mostra a data, quem cancelou e o motivo.');
    await v.destacar('#dadosEmissao tr:first-child');
    v.poster();
    await v.passo('O quadro de cancelamento e a 2ª via somem: cancelada não volta a valer. Para corrigir, emita de novo (novo código).');

    v.capitulo('A verificação pública');
    await v.passo('Abra o link da verificação pública desta emissão.');
    await v.abrirAqui('#dadosEmissao a[target="_blank"]');
    const sit = await v.texto('#situacaoVerificacao');
    v.confere(/cancelad/i.test(sit), 'a verificação deveria mostrar cancelado: ' + sit);
    await v.passo('Quem conferir o código agora vê "Documento cancelado", com a data do cancelamento.');
    await v.destacar('#resultadoVerificacao');
  },
};
