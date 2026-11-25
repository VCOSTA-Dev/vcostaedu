// Guia: Emitir a 2ª via (secretaria). Abre o histórico FINAL fictício da Irene, gera a 2ª via (mesmo código, mesmo
// conteúdo, com a marca "2ª VIA") e mostra o PDF da via. O banco e os arquivos voltam ao retrato no fim.
module.exports = {
  conta: 'secretaria.demo',
  async gravar(v) {
    const em = v.ids.emissao.find(e => e.ra === '250101' && e.tipo === 'FINAL');
    v.capitulo('Abrir a emissão');
    await v.ir('painel/historico-emissoes/listar');
    await v.passo('Educacional → Históricos emitidos. A coluna "Vias" conta o original e as reimpressões.');
    await v.destacar(`#listaEmissoes tr[data-codigo="${em.codigo}"]`);
    await v.passo(`O histórico FINAL ${em.codigo} está válido. Clique em "Ver" na linha da emissão.`);
    await v.clicarENavegar(`#listaEmissoes tr[data-codigo="${em.codigo}"] a[href$="historico-emissoes/ver/${em.id}"]`);
    v.confere(/Válida/.test(await v.texto('#situacaoEmissao')), 'a emissão deveria estar válida');

    v.capitulo('Gerar a 2ª via');
    await v.passo('No quadro Documentos, clique em "Gerar 2ª via": reimprime o MESMO conteúdo gravado, com o mesmo código.');
    await v.clicarENavegar('input[name="SegundaVia"]');
    v.confere(await v.page.$('#viaGerada'), 'a 2ª via deveria ser gerada: ' + await v.alerta());
    await v.passo('"2ª via gerada, com o mesmo código e o mesmo conteúdo." Fica registrado quem gerou e quando.');
    await v.destacar('#viaGerada');
    const via = `a[href$="historico-emissoes/pdf/${em.id}?via=2"]`;
    v.confere(await v.page.$(via), 'a lista Documentos deveria trazer a 2ª via');
    await v.passo('A via entra na lista Documentos, ao lado do PDF original. Abra o PDF da 2ª via.');
    await v.destacar(via);
    v.poster();

    v.capitulo('O PDF da via');
    await v.passo(`No topo de cada página, a marca "2ª VIA — reimpressão de …". O código continua ${em.codigo}.`);
    await v.pdf(via, 7);
    await v.destacar('#codigoEmissao');
    await v.passo('Emissão cancelada não tem 2ª via. Precisa de um documento com dados novos? Não é 2ª via: emita de novo.');
  },
};
