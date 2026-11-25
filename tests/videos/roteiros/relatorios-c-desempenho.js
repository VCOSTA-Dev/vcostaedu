// Guia: Relatórios C — desempenho acadêmico (secretaria). O mapa de notas de uma oferta aberta (parcial × "se encerrasse
// hoje"), o resultado por disciplina, a distribuição, as pendências de lançamento e o desempenho do aluno. Só lê.
module.exports = {
  conta: 'secretaria.demo',
  async gravar(v) {
    const curso = String(v.ids.curso);
    const filtrar = () => v.clicarENavegar('#filtrosRelatorio button[type="submit"]');
    const ths = () => v.page.$$eval('table.lista-dados thead th', t => t.map(x => x.textContent.trim()));

    v.capitulo('Mapa de notas da oferta');
    await v.ir('painel/relatorios/mapa-notas');
    await v.passo('Mapa de notas: escolha a oferta. Aqui, uma oferta ainda aberta.');
    await v.escolher('#f_oferta', String(v.ids.oferta.GOC));
    await filtrar();
    const c = await ths();
    v.confere(c.includes('Média parcial') && c.some(t => /se encerrasse hoje/.test(t)) && c.includes('FS/FI'), 'o mapa da oferta aberta deveria ter a média parcial e a "se encerrasse hoje": ' + c.join(' | '));
    await v.rolar('table.lista-dados');
    v.poster();
    await v.passo('Uma coluna por atividade, a média, a frequência, FS/FI e o resultado (ou "Pendente — motivo").');
    await v.destacar('table.lista-dados thead th:has-text("Média parcial")');
    await v.passo('Oferta aberta: a média parcial (a mesma do boletim) e, à parte, a "se encerrasse hoje", provisória.');
    await v.destacar('table.lista-dados thead th:has-text("se encerrasse hoje")');

    v.capitulo('Resultado por disciplina');
    await v.ir('painel/relatorios/resultado-disciplina');
    await v.escolher('#f_curso', curso);
    await filtrar();
    v.confere(await v.page.locator('table.lista-dados tbody tr').count() >= 3, 'o resultado por disciplina deveria listar as encerradas');
    await v.passo('Resultado por disciplina: só as ofertas encerradas — aprovados, reprovados por nota e por frequência, média e desvio.');
    await v.rolar('#graficoRelatorio');
    await v.passo('O gráfico mostra a taxa de reprovação de cada disciplina. Agrupe por oferta no filtro "Agrupar por".');
    await v.destacar('#f_agrupar');

    v.capitulo('Distribuição de notas');
    await v.ir('painel/relatorios/distribuicao-notas');
    await v.escolher('#f_curso', curso);
    await filtrar();
    v.confere(await v.page.$('#graficoRelatorio'), 'o histograma deveria aparecer');
    await v.passo('Distribuição de notas: o histograma das médias em faixas de 1 ponto. Com uma oferta aberta, as médias provisórias.');
    await v.rolar('#graficoRelatorio');

    v.capitulo('Pendências de lançamento');
    await v.ir('painel/relatorios/pendencias-lancamento');
    await v.escolher('#f_curso', curso);
    await filtrar();
    await v.passo('Pendências de lançamento: por oferta aberta, o que falta antes de encerrar (nota, chamada, encontros, atraso).');
    await v.rolar('table.lista-dados');
    await v.destacar('table.lista-dados');

    v.capitulo('Desempenho do aluno');
    await v.ir('painel/academicos/visualizar/' + v.ids.aluno['260104']);
    await v.passo('O desempenho do aluno abre também pelo botão Desempenho, na ficha do aluno (aba Matrículas).');
    if (!(await v.page.locator('#tabeMatricula.active').count())) { await v.clicar('#tabeMatricula-tab'); await v.pausa(500); }
    const botao = v.page.locator('#tabeMatricula a[href*="relatorios/desempenho-aluno?matricula="]').first();
    v.confere(await botao.count(), 'a ficha do aluno deveria ter o botão Desempenho');
    await v.clicarENavegar(botao);
    const d = await ths();
    v.confere(d.includes('Disciplina') && d.includes('Situação'), 'o desempenho deveria listar a grade: ' + d.join(' | '));
    await v.passo('A grade inteira da matrícula: situação, média e frequência de cada disciplina, CH cumprida e a média final do documento.');
    await v.destacar('.vc-cartao-rel:has(.text-secondary:has-text("CH cumprida"))');
    await v.rolar('table.lista-dados');
  },
};
