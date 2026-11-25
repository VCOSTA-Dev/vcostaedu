// Guia: Montar a matriz (secretaria). Uma grade nova (2) no curso principal: a recusa do número repetido, as disciplinas,
// a recusa da matéria repetida, a CH, a ordem por arrastar e o envio para "Em análise". O banco volta ao retrato no fim.
module.exports = {
  conta: 'secretaria.demo',
  async gravar(v) {
    const curso = v.ids.curso;
    const linhas = () => v.page.locator('tbody.ordenavel tr.linhaMatriz');
    const incluir = async (sigla, ch) => {
      const val = await v.page.$$eval('#materia_id option', (os, s) => (os.find(o => o.textContent.includes(s)) || {}).value, sigla);
      await v.escolher('#materia_id', val);
      if (ch) await v.digitar('#cargahoraria', ch);
      await v.clicarENavegar('form[action*="adicionar-disciplina"] button[type="submit"]');
    };

    v.capitulo('Nova grade');
    await v.ir('painel/matriz/listar');
    await v.passo('Ensino → Matriz: cada curso com as suas grades. A grade 1 do curso de Gestão de Projetos já está Finalizada.');
    await v.destacar(`#curso-${curso} table`);
    await v.passo('Clique em "Nova grade" no curso. O número é inteiro e não se repete no mesmo curso.');
    await v.clicarENavegar(`#curso-${curso} a[href*="matriz/cadastrar-grade"]`);
    await v.digitar('#versao', '1');
    await v.digitar('#descricao', 'Grade 2027 (vídeo)');
    await v.clicarENavegar('input[name="SalvarGrade"]');
    const recusa = await v.alerta();
    v.confere(/já existe/i.test(recusa), 'o número repetido deveria ser recusado: ' + recusa);
    await v.passo('Recusado: "A grade 1 já existe no curso…". Use outro número: 2.');
    await v.destacar('.alert');
    if (await v.page.$eval('#curso_id', s => !s.value).catch(() => false)) await v.escolher('#curso_id', String(curso));
    await v.digitar('#versao', '2');
    await v.clicarENavegar('input[name="SalvarGrade"]');
    v.confere(/matriz\/visualizar\/\d+/.test(v.page.url()) && /criada/i.test(await v.alerta()), 'a grade 2 deveria ser criada: ' + await v.alerta());
    await v.passo('Grade 2 criada, Aberta. Agora as disciplinas.');

    v.capitulo('Incluir disciplinas');
    await v.passo('Em "Incluir disciplina", escolha a matéria do catálogo e clique em Incluir. CH vazia = a do catálogo.');
    await incluir('Fundamentos de Gestão de Projetos');
    await incluir('Planejamento e Cronograma');
    await incluir('Liderança de Equipes');
    v.confere(await linhas().count() === 3, 'a grade deveria ter 3 disciplinas');
    await v.passo('Cada disciplina nova entra no fim da lista. A CH da grade é a soma, feita pelo sistema.');
    await v.destacar('#chGrade');
    await v.passo('A mesma matéria não entra duas vezes: vamos tentar incluir Liderança de Equipes de novo.');
    await incluir('Liderança de Equipes');
    const rep = await v.alerta();
    v.confere(/já está nesta grade/i.test(rep), 'a matéria repetida deveria ser recusada: ' + rep);
    await v.destacar('.alert');
    await v.passo(`Recusado: "${rep.replace(/^Erro:\s*/, '').slice(0, 80)}"`);

    v.capitulo('Carga horária');
    await v.passo('Para mudar a CH de uma disciplina: digite o número na linha e clique no disquete. Aqui, Planejamento e Cronograma passa a 90 h.');
    const linhaOfp = linhas().filter({ hasText: 'Planejamento' }).first();
    await v.digitar(linhaOfp.locator('input[name="cargahoraria"]'), '90');
    await v.clicarENavegar(linhaOfp.locator('form.formLinhaMatriz button[type="submit"]'));
    v.confere(/210 h/.test(await v.texto('#chGrade')), 'a CH da grade deveria somar 210 h: ' + await v.texto('#chGrade'));
    await v.destacar('#chGrade');
    await v.passo('A CH da grade foi recalculada: 60 + 90 + 60 = 210 h.');

    v.capitulo('A ordem do histórico');
    await v.passo('Ordem do currículo: arraste a linha pela alça (a 1ª coluna). Vamos levar Liderança de Equipes para o topo.');
    const h1 = linhas().nth(2).locator('.alca-ordem'), alvo = linhas().nth(0);
    await v.destacar(h1);
    const a = await h1.boundingBox(), c = await alvo.boundingBox();
    await v.page.mouse.move(a.x + a.width / 2, a.y + a.height / 2); await v.page.mouse.down();
    for (let i = 1; i <= 16; i++) { await v.page.mouse.move(a.x + a.width / 2, a.y + a.height / 2 + (c.y - a.y - 4) * i / 16); await v.pausa(40); }
    await v.page.mouse.up(); await v.pausa(500);
    v.confere(!(await v.page.isDisabled('#btnSalvarOrdem')), 'depois de arrastar, "Salvar a ordem" deveria ligar');
    await v.passo('Depois de arrastar, clique em "Salvar a ordem". É a ordem em que as disciplinas saem no histórico.');
    await v.clicarENavegar('#btnSalvarOrdem');
    v.confere(/Ordem das disciplinas salva/i.test(await v.alerta()) && /Liderança de Equipes/.test(await linhas().first().textContent()), 'a ordem deveria ser salva: ' + await v.alerta());
    v.poster();
    await v.destacar(linhas().first());

    v.capitulo('Mandar para Em análise');
    await v.rolar('#quadroSituacao');
    await v.passo('Pronta, mande a grade para "Em análise" em Situação da grade. Quem finaliza é o administrador.');
    await v.escolher('#situacao', '2');
    await v.digitar('#observacao', 'Grade 2027 pronta para conferência.');
    await v.clicarENavegar('#quadroSituacao button[type="submit"]');
    v.confere(/Em análise/.test(await v.alerta()), 'a grade deveria ir para Em análise: ' + await v.alerta());
    v.confere(await v.page.$('#faixaTrava'), 'travada, a grade deveria mostrar a faixa');
    await v.passo('Em análise, a grade trava: a faixa diz o motivo e o caminho para destravar, e os botões ficam desabilitados.');
    await v.destacar('#faixaTrava');
  },
};
