// Guia: Organizar a escola (administrador, secretaria; só na escola). A estrutura (os segmentos, as séries e os
// componentes), o ano letivo em etapas, a turma com as ofertas e os alunos, e trocar um aluno de turma (as notas vão
// junto). Na demonstração do colégio. O banco volta ao retrato.
module.exports = {
  conta: 'administrador',
  async gravar(v) {
    const ano = String(new Date().getFullYear());
    v.capitulo('A estrutura');
    await v.ir('painel/escola/index');
    await v.passo('Ensino → Estrutura da escola: os segmentos, as séries de cada um e os componentes curriculares de cada série.', { t: 'Estrutura da escola', aviso: 'Olhe as séries e os componentes.' });
    await v.destacar('#explicacaoEstrutura').catch(() => {});
    await v.rolar(400);
    await v.rolar(-400);

    v.capitulo('O ano letivo e as etapas');
    await v.ir('painel/escola-anos/index');
    const cartao = `.vc-ano[data-ano="${ano}"]`;
    v.confere(!!(await v.page.$(cartao)), 'o ano letivo deveria aparecer');
    await v.passo('Ensino → Ano letivo e etapas: o ano em quatro bimestres (o padrão) ou três trimestres, com as datas e o conselho de cada etapa.', { t: 'Ano letivo e etapas' });
    await v.destacar(cartao);
    await v.destacar(`${cartao} .vc-etapas`).catch(() => {});
    await v.passo('Com nota lançada, o modelo não muda mais. As datas se ajustam na própria tela.', { t: 'As etapas' });
    v.poster();

    v.capitulo('A turma');
    await v.ir('painel/turmas/listar');
    await v.passo('Ensino → Turmas: cada turma do ano, com a série, o turno, a sala e quantos alunos tem.', { t: 'Turmas', aviso: 'Abra uma turma.' });
    await v.destacar('#tabelaTurmas');
    await v.clicarENavegar('#tabelaTurmas a[href*="turmas/ver/"]');
    await v.passo('A turma gera uma oferta por componente da série, cada uma com o seu professor. O professor vê só as turmas e os componentes dele.', { t: 'As ofertas da turma' });
    await v.destacar('#ofertasTurma');
    await v.passo('Os alunos da turma. O cartão "Enturmar alunos" lista os do segmento ainda sem turma: marcar e enturmar põe o aluno em todos os componentes de uma vez.', { t: 'Os alunos' });
    await v.destacar('#alunosTurma');

    v.capitulo('Trocar de turma');
    const linha = v.page.locator('#alunosTurma tr:has(.vc-trocar select[name="destino_id"] option[value]:not([value=""]))').first();
    if (await linha.count()) {
      await v.passo('Para trocar um aluno de turma: escolha a turma de destino, da mesma série, e clique em Trocar de turma. As notas e as faltas vão junto.', { t: 'Trocar de turma', aviso: 'Escolha o destino e clique em **Trocar**.' });
      const sel = linha.locator('.vc-trocar select[name="destino_id"]');
      const destino = await sel.evaluate((s) => [...s.options].find((o) => o.value)?.value);
      await v.escolher(sel, destino);
      await v.clicarENavegar(linha.locator('.vc-trocar button'));
      v.confere(/troca|turma/i.test(await v.alerta()), 'a troca deveria ser confirmada: ' + (await v.alerta()));
      await v.feito('O aluno mudou de turma, com o que já tinha sido lançado.');
    } else {
      await v.passo('A troca aparece quando há outra turma da mesma série no ano.', { t: 'Trocar de turma' });
    }
  },
};
