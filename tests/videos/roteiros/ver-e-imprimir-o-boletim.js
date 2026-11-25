// Guia: Ver e imprimir o boletim (secretaria). Da lista de alunos ao boletim por curso: a média parcial das abertas, a
// frequência, o filtro de curso e ano, Imprimir e o PDF. Aluno fictício Diego (260104).
const RA = '260104';
module.exports = {
  conta: 'secretaria.demo',
  async gravar(v) {
    v.capitulo('Abrir o boletim');
    await v.ir('painel/academicos/listar');
    await v.passo('Educacional → Alunos. Na linha do aluno, o botão "Boletim" (também está em Notas e resultado da disciplina).');
    const bt = `#tabelaAlunos a[href$="academicos/boletim/${RA}"]`;
    await v.rolar(bt);
    await v.abrirAqui(bt);
    v.confere(await v.page.$('.boletimCurso'), 'o boletim do aluno deveria abrir');

    v.capitulo('Ler o boletim');
    await v.passo('Uma faixa com o aluno e o curso; depois, cada disciplina: média, faltas e compensadas, frequência e resultado.');
    await v.destacar('.boletimCurso');
    v.poster();
    const tab = (await v.texto('.boletimCurso')).slice(0, 300);
    console.log('    (boletim: ' + tab + ')');
    const parcial = v.page.locator('.boletimCurso td', { hasText: 'parcial' }).first();
    v.confere(await parcial.count(), 'o boletim deveria ter média parcial');
    await v.passo('A etiqueta "parcial": a disciplina está aberta e a média ainda pode mudar até o encerramento.');
    await v.destacar(parcial);
    await v.passo('Em vermelho, o que está abaixo do mínimo. Abaixo da tabela, "Como ler": a média e a frequência mínimas.');
    await v.destacar('.regras-boletim');

    v.capitulo('Filtros, Imprimir e PDF');
    await v.rolar('#seletorBoletim');
    await v.passo('No topo, Curso e Ano filtram o boletim. Vamos escolher um ano.');
    const ano = await v.page.$$eval('#filtroAno option', os => (os.find(o => o.value !== '0') || {}).value);
    v.confere(ano, 'o filtro de ano deveria ter opções');
    await v.destacar('#filtroAno');
    await Promise.all([v.page.waitForNavigation({ waitUntil: 'load' }), v.page.selectOption('#filtroAno', ano)]);
    v.confere(new RegExp('filtro_ano=' + ano).test(v.page.url()), 'o filtro deveria ir para o endereço');
    await v.passo(`O boletim de ${ano}. Sem nada com o filtro, a tela avisa: volte o filtro para "Todos".`);
    await v.destacar('#filtroAno');
    await v.passo('"Imprimir" imprime a página sem o menu. "PDF" gera o mesmo boletim, com o mesmo filtro:');
    await v.destacar('.barra-boletim button[onclick*="print"]');
    await v.pdf('.barra-boletim a[href*="imprimir-boletim"]', 6);
    await v.passo('Aluno em dois cursos tem dois boletins, um abaixo do outro, cada um com a sua faixa.');
  },
};
