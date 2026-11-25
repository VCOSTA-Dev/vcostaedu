// Guia: Avaliar na escola (administrador, secretaria, professor; só na escola). As etapas do componente (a média de
// cada aluno, a recuperação e as faltas da etapa), a etapa fechada (travada), o conselho de classe, a liberação do
// boletim por etapa e o boletim — na tela, no PDF e no portal do aluno. Na demonstração do colégio. O banco volta ao
// retrato.
const RA = '265002'; // um aluno fictício do colégio: o da recuperação que salvou a etapa (Onda 7, Etapa 1: o "R" no boletim)
module.exports = {
  conta: 'administrador',
  contas: [RA],
  async gravar(v) {
    v.capitulo('As etapas do componente');
    await v.ir('painel/turmas/listar');
    await v.clicarENavegar('#tabelaTurmas a[href*="turmas/ver/"]');
    const turma = (v.page.url().match(/turmas\/ver\/(\d+)/) || [])[1];
    await v.passo('Na tela da turma, cada componente tem "Etapas e notas": a média de cada aluno em cada etapa.', { t: 'A turma', aviso: 'Clique em **Etapas e notas** num componente.' });
    await v.clicarENavegar('#ofertasTurma a[href*="escola-notas/etapas/"]');
    v.confere(!!(await v.page.$('#abasEtapas')), 'as etapas do componente deveriam abrir');
    await v.passo('Uma aba por etapa. Na etapa: a média de cada aluno pela regra do componente, a recuperação (para quem ficou abaixo da média) e as faltas.', { t: 'As etapas', aviso: 'Olhe a tabela da etapa.' });
    await v.destacar('#abasEtapas');
    await v.destacar('#tabelaEtapa');
    v.poster();
    if (await v.page.$('#etapaTravada')) {
      await v.passo('A etapa fechada fica travada: nota, falta e recuperação não mudam, e a tela diz por quê. Reabrir pede o motivo e guarda o que foi substituído.', { t: 'A etapa fechada' });
      await v.destacar('#etapaTravada');
    }
    const aberta = v.page.locator('#abasEtapas a:not(.active)').last();
    if (await aberta.count()) {
      await v.clicarENavegar(aberta);
      await v.passo('Numa etapa aberta, os campos da recuperação e das faltas. "Fechar a etapa" guarda as notas da etapa de cada aluno, com a regra copiada.', { t: 'A etapa aberta' });
      if (await v.page.$('#fecharEtapa')) await v.destacar('#fecharEtapa');
    }
    await v.destacar('#resumoAno').catch(() => {});

    v.capitulo('O conselho de classe');
    await v.ir(`painel/escola-conselho/turma/${turma}`);
    await v.passo('O conselho de classe: cada aluno com a média anual e a situação de cada componente. No fim do ano, o conselho pode aprovar, com a observação obrigatória; a nota não muda.', { t: 'Conselho de classe' });
    await v.destacar('.vc-aluno-conselho').catch(() => {});

    v.capitulo('Liberar o boletim');
    await v.ir('painel/escola-anos/index');
    await v.passo('Ano letivo e etapas: a coluna do portal libera o boletim de cada etapa para a escola inteira. A média anual e a situação aparecem com todas liberadas.', { t: 'Liberar no portal' });
    await v.destacar('.vc-liberado').catch(() => {});

    v.capitulo('O boletim');
    await v.ir(`painel/academicos/boletim/${RA}`);
    await v.passo('O boletim do aluno: as etapas em colunas (com "R" quando houve recuperação), a média anual, as faltas, a frequência e a situação.', { t: 'O boletim na tela' });
    await v.rolar(300);
    const pdf = v.page.locator('a[href*="pdf"], a[href*="imprimir"]').first();
    if (await pdf.count()) {
      await v.passo('O mesmo boletim em PDF, com o mesmo cálculo.', { t: 'O boletim em PDF' });
      await v.pdf(pdf, 5).catch(() => {});
    }
    await v.ctx.clearCookies();
    await v.entrar(RA);
    await v.ir('portal/home/boletim-aluno');
    await v.passo('No portal, o aluno vê só as etapas liberadas.', { t: 'O boletim no portal' });
    await v.rolar(300);
  },
};
