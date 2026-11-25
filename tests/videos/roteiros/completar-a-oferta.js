// Guia: Completar a oferta (secretaria). Uma oferta recém-gerada (inventada, turma PGP-2026-B, sem período nem
// professor): a recusa do período invertido, os encontros e o período, e o professor. O retrato desfaz tudo.
const OF = 9001, TURMA = 'PGP-2026-B';
module.exports = {
  conta: 'secretaria.demo',
  sql: ids => `
    INSERT INTO rm_professores_materias (id, curso_id, grade, periodo, codmat, codtur, materia, ch_considerada, ano, bimestre, tipo, codfilial, situacao,
      usuario_cre, created, matriz_id, idMoodle, modalidade_id, encontros, frequencia_fonte)
    SELECT ${OF}, curso_id, grade, periodo, codmat, '${TURMA}', materia, ch_considerada, ano, bimestre, tipo, codfilial, 1, 5, NOW(), matriz_id, NULL, modalidade_id, NULL, 'lancamento'
      FROM rm_professores_materias WHERE id = ${ids.oferta.STF};`,
  async gravar(v) {
    const linha = () => v.page.locator('#tabelaDisciplinas tbody tr', { hasText: TURMA }).first();

    v.capitulo('Editar a oferta');
    await v.ir('painel/disciplinas/listar');
    await v.digitar('#tabelaDisciplinas_filter input', TURMA);
    await v.pausa(500);
    await v.passo('Ensino → Todas as Disciplinas. A oferta recém-gerada do TCC (turma PGP-2026-B) ainda está "sem professor".');
    await v.destacar(linha());
    await v.passo('Clique em Editar (lápis) na linha da disciplina.');
    await v.clicarENavegar(linha().locator(`a[href*="disciplinas/editar/${OF}"]`));

    v.capitulo('Período: o fim não vem antes do início');
    await v.passo('Preencha o total de encontros e o período de realização (início e fim: saem no histórico).');
    await v.digitar('#encontros', '15');
    await v.destacar('#data_inicio'); await v.page.fill('#data_inicio', '2026-11-30');
    await v.destacar('#data_fim'); await v.page.fill('#data_fim', '2026-08-03');
    await v.passo('Datas trocadas por engano: o fim ficou antes do início. Salve para ver a recusa.');
    await v.clicarENavegar('input[name="EditDisciplina"]');
    const rec = await v.alerta();
    v.confere(/fim do período da oferta não pode ser antes do início/i.test(rec), 'o período invertido deveria ser recusado: ' + rec);
    await v.destacar('.alert');
    await v.passo('Recusado: "O fim do período da oferta não pode ser antes do início." Corrija as datas e salve.');
    await v.digitar('#encontros', '15');
    await v.destacar('#data_inicio'); await v.page.fill('#data_inicio', '2026-08-03');
    await v.destacar('#data_fim'); await v.page.fill('#data_fim', '2026-11-30');
    await v.clicarENavegar('input[name="EditDisciplina"]');
    v.confere(/sucesso/i.test(await v.alerta()), 'a oferta deveria ser salva: ' + await v.alerta());
    await v.passo('Oferta salva, com os encontros e o período.');

    v.capitulo('O professor');
    await v.digitar('#tabelaDisciplinas_filter input', TURMA);
    await v.pausa(500);
    await v.passo('Na mesma lista, o botão verde com o ícone de pessoa: escolha o professor e salve.');
    await v.clicar(linha().locator('button[data-bs-target="#adicionarProfessor"]'));
    await v.page.waitForSelector('#adicionarProfessor.show');
    await v.pausa(600);
    const prof = await v.page.$$eval('#professores option', os => (os.find(o => /Laura/i.test(o.textContent)) || {}).value);
    v.confere(prof, 'a professora Laura deveria estar na lista');
    await v.destacar('#adicionarProfessor .select2-container');
    await v.page.evaluate(id => window.jQuery('#professores').val([id]).trigger('change'), prof);
    await v.pausa(800);
    await v.clicarENavegar('#adicionarProfessor input[name="AdicionaProfessor"]');
    v.confere(/Professor/i.test(await v.alerta()) && !/Erro/i.test(await v.alerta()), 'o professor deveria ser vinculado: ' + await v.alerta());
    /* 1.18.1 (D-483): depois de salvar o professor, o filtro de ano continua no ano da lista (antes, "Selecione o Ano") */
    const anoFiltro = await v.page.$eval('#pesqAno', s => s.value).catch(() => '');
    v.confere(anoFiltro === '2026', 'o filtro de ano deveria continuar em 2026: "' + anoFiltro + '"');
    await v.destacar('#pesqAno + .select2-container, #pesqAno');
    await v.digitar('#tabelaDisciplinas_filter input', TURMA);
    await v.pausa(500);
    v.confere(/Laura/i.test(await linha().textContent()), 'a linha deveria mostrar a professora');
    v.poster();
    await v.passo('Pronto: a professora passa a ver a oferta, a frequência tem base e o histórico tem o período e o docente.');
    await v.destacar(linha());
  },
};
