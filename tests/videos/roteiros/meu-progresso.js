// Guia: Meu progresso no curso (aluno). A carga horária cumprida, as disciplinas aprovadas, a média final e a grade
// inteira com a situação de cada disciplina. Aluna inventada da demonstração.
const RA = '260107';
module.exports = {
  conta: RA,
  async gravar(v) {
    v.capitulo('Abrir o progresso');
    await v.ir('portal/home/index');
    await v.passo('No menu, Acadêmico → Meu progresso: quanto falta para concluir o curso, disciplina por disciplina.');
    await v.menu("progresso/index");
    v.confere(await v.page.$('#tabelaProgresso'), 'a tela deveria mostrar a grade');
    if (await v.page.$('#matricula')) {
      await v.passo('Com mais de um curso, escolha o curso no topo.');
      await v.destacar('#matricula');
    } else {
      await v.passo('Você vê só as suas matrículas. Com mais de um curso, um seletor no topo escolhe o curso.');
    }

    v.capitulo('Os números do curso');
    await v.passo('A carga horária cumprida, sobre a carga horária total da grade do curso.');
    await v.destacar('#chCumprida');
    await v.passo('Quantas disciplinas você já aprovou, de quantas tem a grade.');
    await v.destacar(v.page.locator('.card-sm', { hasText: 'Disciplinas aprovadas' }).first());
    await v.passo('A média final: só das disciplinas encerradas.');
    await v.destacar('#mediaFinal');
    v.poster();

    v.capitulo('A grade, disciplina por disciplina');
    await v.rolar('#tabelaProgresso');
    await v.passo('Na tabela, cada disciplina da grade: Aprovado, Reprovado, Em curso ou Não cursada, com a média e a frequência.');
    await v.destacar('#tabelaProgresso');
    const linha = s => v.page.locator('#tabelaProgresso tbody tr', { has: v.page.locator('.badge', { hasText: s }) }).first();
    const aprov = linha('Aprovado');
    v.confere(await aprov.count(), 'deveria haver disciplina aprovada');
    await v.destacar(aprov);
    const emCurso = linha('Em curso');
    v.confere(await emCurso.count(), 'deveria haver disciplina em curso');
    await v.passo('Em curso: a média é parcial (só das atividades com nota) e pode mudar até o encerramento.');
    await v.destacar(emCurso.locator('td.media'));
    const nao = linha('Não cursada');
    if (await nao.count()) {
      await v.passo('"Não cursada", mas você está na turma? Fale com a secretaria: pode faltar a enturmação.');
      await v.destacar(nao);
    } else {
      await v.passo('Uma disciplina aparece "Não cursada", mas você está na turma? Fale com a secretaria: pode faltar a enturmação.');
    }
  },
};
