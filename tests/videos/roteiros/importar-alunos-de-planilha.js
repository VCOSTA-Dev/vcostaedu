// Guia: Importar alunos de planilha (administrador, secretaria; aqui, a secretaria). Uma planilha fictícia com uma linha
// pronta, uma com erro (sexo não reconhecido) e uma já cadastrada (pulada): enviar e conferir, as colunas, os valores,
// as linhas e aplicar sem as linhas com erro. O banco volta ao retrato no fim.
module.exports = {
  conta: 'secretaria.demo',
  async gravar(v) {
    v.capitulo('Enviar a planilha');
    await v.ir('painel/home/index');
    await v.passo('Educacional → Importar alunos. "Baixar o modelo" traz as colunas; a planilha pode ter outros nomes de coluna.', { t: 'Importar alunos' });
    await v.menu('importacao-alunos/listar');
    await v.destacar('#baixarModelo');
    const urlModelo = await v.page.getAttribute('#baixarModelo', 'href');
    const modelo = (await (await v.page.request.get(new URL(urlModelo, v.page.url()).href)).text()).replace(/^\uFEFF/, '');
    const cab = modelo.split(/\r?\n/)[0];
    const n = cab.split(';').length;
    const linha = (c) => c.concat(Array(n - c.length).fill('')).join(';');
    const csv = [cab,
      linha(['269001', 'Bruna Planilha Exemplo (fictícia)', '', 'bruna.planilha@exemplo.invalid', '10/05/1995', 'Feminino', '', 'Ativo']),
      linha(['269002', 'Caio Planilha Exemplo (fictício)', '', '', '21/08/1992', 'X', '', 'Ativo']),
      linha(['260101', 'Aluno já cadastrado', '', '', '01/01/1990', 'Masculino', '', 'Ativo']),
    ].join('\r\n') + '\r\n';
    await v.passo('Escolha o arquivo (CSV ou planilha) e clique em "Enviar e conferir": nada é gravado ainda.', { t: 'Enviar e conferir', aviso: 'Escolha o arquivo e clique em **Enviar e conferir**.' });
    await v.page.setInputFiles('#arquivoImportacao', { name: 'alunos-exemplo.csv', mimeType: 'text/csv', buffer: Buffer.from('\uFEFF' + csv, 'utf8') });
    await v.destacar('#arquivoImportacao');
    await v.clicarENavegar('#enviarImportacao');
    v.confere(await v.page.$('#tabelaLinhas'), 'a conferência deveria abrir: ' + (await v.alerta()));

    v.capitulo('Conferir');
    await v.passo('As colunas: em que campo cada coluna entra. O que não for reconhecido fica "(não importar)" até você escolher.', { t: 'As colunas' });
    await v.destacar('#cartaoColunas');
    await v.passo('Os valores: cada valor diferente de sexo, situação e modalidade e o que ele virou. Valor não reconhecido não é adivinhado.', { t: 'Os valores' });
    await v.destacar('#deParaValores');
    await v.passo('As linhas: Pronta, Com erro (com o motivo) ou Pulada (o RA já está cadastrado). "Corrigir" acerta uma linha aqui mesmo.', { t: 'As linhas' });
    await v.destacar('#contadores').catch(() => {});
    await v.destacar('#cartaoLinhas');
    v.poster();

    v.capitulo('Aplicar');
    await v.passo('Com linhas ainda com erro, marque "Aplicar sem as linhas com erro" e clique em "Aplicar a importação".', { t: 'Aplicar', aviso: 'Marque **Aplicar sem as linhas com erro** e clique em **Aplicar a importação**.' });
    if (await v.page.$('#aceitarErros')) await v.clicar('#aceitarErros');
    await v.clicarENavegar('#aplicarLote');
    v.confere(await v.page.$('#resumoAplicado'), 'a importação deveria ser aplicada: ' + (await v.alerta()));
    await v.destacar('#resumoAplicado');
    await v.feito('Importado. Os alunos novos recebem o primeiro acesso pelo "Enviar link de acesso", como os cadastrados na tela.');
  },
};
