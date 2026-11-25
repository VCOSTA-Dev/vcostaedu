// Guia: Auditoria de notas e faltas (administrador, professor; aqui, o administrador). O histórico de uma nota, na
// janela do relógio, e o relatório com os filtros e "Só alterações e exclusões". Só leitura.
module.exports = {
  conta: 'administrador',
  async gravar(v) {
    v.capitulo('O histórico de uma nota');
    const ativ = v.ids.atividade.GOC[0];
    await v.ir(`painel/atividades/visualizar/${ativ}`);
    await v.passo('Na avaliação, a coluna Histórico: o relógio na linha do aluno abre cada mudança daquela nota.', { t: 'Histórico da nota', aviso: 'Clique no **relógio** na linha do aluno.' });
    const relogio = v.page.locator('.vc-historico-nota').first();
    v.confere(await relogio.count(), 'a avaliação deveria ter o relógio do histórico');
    await v.clicar(relogio);
    await v.page.waitForSelector('#historicoNota, #semHistorico', { state: 'visible', timeout: 15000 });
    await v.pausa(800);
    await v.passo('Quando, a ação, o valor antes e depois, quem fez e a origem. Ninguém apaga este registro.', { t: 'Cada mudança' });
    await v.destacar('#historicoNota, #semHistorico');
    await v.page.keyboard.press('Escape');
    await v.pausa(500);

    v.capitulo('O relatório');
    await v.passo('Para tudo: Relatórios → Auditoria de notas e faltas.', { t: 'Auditoria de notas e faltas' });
    await v.menu('auditoria/listar');
    await v.passo('Filtre por curso, disciplina, aluno, quem lançou, o quê e o período. "Só alterações e exclusões" deixa só o que mudou depois de lançado.', { t: 'Os filtros', aviso: 'Marque **Só alterações e exclusões** e clique em **Filtrar**.' });
    await v.destacar('#filtrosAuditoria');
    await v.clicar('#soAlteracoes');
    await v.clicarENavegar('#filtrar');
    v.confere(await v.page.$('#tabelaAuditoria, #totalAuditoria'), 'o relatório deveria mostrar o resultado');
    await v.destacar('#totalAuditoria').catch(() => {});
    v.poster();
    await v.passo('Para guardar o resultado, Excel, CSV ou PDF, acima da tabela.', { t: 'Exportar' });
    await v.destacar('.dt-buttons').catch(() => v.destacar('#tabelaAuditoria'));
  },
};
