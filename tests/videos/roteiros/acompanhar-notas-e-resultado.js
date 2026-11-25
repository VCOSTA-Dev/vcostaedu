// Guia: Acompanhar notas, frequência e resultado (secretaria). A média parcial da disciplina aberta, a conta de um aluno,
// e na prévia do encerramento a atividade sem nota contando 0. Dados fictícios; nada é gravado.
let OFERTA;   // GOC, aberta: o Questionário tem notas; o Trabalho final, ainda não
module.exports = {
  conta: 'secretaria.demo',
  async gravar(v) {
    OFERTA = v.ids.oferta.GOC; // 1.18.2: da demonstração em uso, sem id fixo
    v.capitulo('A média parcial');
    await v.ir(`painel/disciplinas/visualizar/${OFERTA}`);
    await v.passo('Na disciplina, clique em "Notas e resultado", na barra do topo.');
    await v.clicarENavegar(`a[href$="medias/final/${OFERTA}"]`);
    v.confere(/parcial/.test(await v.texto('#situacaoOferta')), 'a tela deveria dizer que a média é parcial');
    await v.passo('Disciplina aberta: a média é PARCIAL — só as atividades que já têm nota entram na conta.');
    await v.destacar('#situacaoOferta');
    const linha = '#tabelaNotas tr[data-rm="260101"]';
    v.confere(/parcial/.test(await v.texto(`${linha} td.media`)), 'a média deveria ter a etiqueta parcial');
    await v.passo('Em cada linha: uma coluna por atividade (— é sem nota), a média com a etiqueta "parcial", faltas e frequência.');
    await v.destacar(linha);
    await v.destacar(`${linha} td.media`);
    await v.passo('Depois, o resultado ("Em curso" enquanto a disciplina está aberta) e o botão Boletim. Abaixo do mínimo, em vermelho.');
    await v.destacar(`${linha} td.resultado`);
    v.poster();
    await v.destacar(`${linha} .acoes-linha`);

    v.capitulo('A conta e as regras');
    await v.passo('"conta", ao lado da média, abre a memória de cálculo do aluno. As regras de cálculo estão no pé da tela.');
    await v.clicar(`${linha} a.ver-conta`);
    await v.pausa(600);
    v.confere(await v.page.$eval('#memoria-260101', d => d.open), 'a memória de cálculo deveria abrir');
    await v.destacar('#memoria-260101');
    await v.destacar('#memoriasCalculo ~ p.small');
    await v.passo('"Atualizar lista": um aluno enturmado depois das atividades ganha as linhas de nota. A enturmação já faz isso.');
    await v.destacar('button[title^="Atualizar a lista"]');

    v.capitulo('No encerramento, sem nota conta 0');
    await v.passo('Antes de encerrar, confira se todas as notas foram lançadas. Veja a prévia do encerramento:');
    await v.clicarENavegar(`a[href$="disciplinas/encerrar/${OFERTA}"]`);
    const conta = await v.texto('#tabelaPrevia tr[data-rm="260101"]');
    v.confere(/4,5/.test(conta), 'na prévia, a média de 260101 deveria cair para 4,5: ' + conta);
    await v.passo('O Trabalho final sem nota entra com 0: a média parcial 9,0 vira 4,5, "Reprovado por nota".');
    await v.destacar('#tabelaPrevia tr[data-rm="260101"]');
    await v.passo('Aprovado exige média E frequência mínimas; senão, reprovado por nota, por frequência ou pelas duas.');
  },
};
