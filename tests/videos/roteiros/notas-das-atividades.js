// Guia: Ver as notas das atividades (aluno). Abre a tela, escolhe uma disciplina e mostra cada atividade com a sua nota,
// inclusive a que ainda não tem nota. Aluno inventado da demonstração.
const RA = '260104';
const DISC = 'Liderança de Equipes';
module.exports = {
  conta: RA,
  async gravar(v) {
    v.capitulo('Abrir as notas');
    await v.ir('portal/home/index');
    await v.passo('No menu, Acadêmico → Notas das Atividades: cada nota como o professor lançou, antes de virar média.');
    await v.menu("home/notas-atividades");
    v.confere(await v.page.$('#disciplinas'), 'a tela deveria ter o filtro de disciplinas');
    /* 1.18.1 (D-485): o menu marca o item da página — aqui, Notas das Atividades (antes, a Página Inicial) */
    const ativos = await v.page.$$eval('#railMenu a.nav-link.active', as => as.map(a => a.innerText.trim()));
    v.confere(ativos.length === 1 && ativos[0] === 'Notas das Atividades', 'o menu deveria marcar só "Notas das Atividades": ' + ativos.join(' + '));
    await v.destacar('#railMenu a.nav-link.active');
    await v.passo('Aqui, todas as disciplinas do ano, uma abaixo da outra, com as atividades e as notas. No menu, o item fica marcado.');
    await v.destacar('#accordionNotas');

    v.capitulo('Escolher a disciplina');
    await v.passo('Para ver uma só, escolha a disciplina no filtro e clique em Pesquisar.');
    const todas = v.page.locator('#disciplinas + .select2-container .select2-selection__choice', { hasText: 'Todas' }).locator('.select2-selection__choice__remove').first();
    if (await todas.count()) { await v.clicar(todas); await v.pausa(400); await v.page.keyboard.press('Escape'); }
    await v.clicar('#disciplinas + .select2-container .select2-search__field');
    await v.pausa(500);
    await v.page.keyboard.type('Lideran', { delay: 90 });
    await v.pausa(900);
    await v.page.keyboard.press('Enter');
    await v.pausa(700);
    await v.page.keyboard.press('Escape');
    await v.pausa(300);
    const escolhidas = await v.page.$eval('#disciplinas', s => [...s.selectedOptions].map(o => o.textContent.trim()));
    v.confere(escolhidas.length === 1 && escolhidas[0] === DISC, 'o filtro deveria ficar só com a disciplina: ' + escolhidas.join(', '));
    await v.clicarENavegar(v.page.locator('form button[type="submit"]', { hasText: 'Pesquisar' }).first());
    const cartoes = v.page.locator('#accordionNotas > .card');
    v.confere((await cartoes.count()) === 1 && /Liderança de Equipes/.test(await cartoes.first().textContent()), 'o filtro deveria mostrar só a disciplina escolhida');
    await v.passo('Aparece cada atividade da disciplina, com a sua nota.');
    v.poster();
    await v.destacar(cartoes.first());
    const semNota = cartoes.first().locator('li', { hasText: /:\s*-\s*$/ }).first();
    v.confere(await semNota.count(), 'a disciplina aberta deveria ter uma atividade sem nota');
    await v.passo('"-" é atividade sem nota: o professor ainda não lançou. No encerramento, ela conta 0.');
    await v.destacar(semNota);
    await v.passo('Nota errada? Fale com o professor. A média e o resultado ficam no Boletim.');
  },
};
