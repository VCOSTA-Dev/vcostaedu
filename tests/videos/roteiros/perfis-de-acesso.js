// Guia: Perfis de acesso (administrador). O que cada perfil pode, ação por ação (a tela Perfis de acesso), e a
// coordenação por curso (indicar quem coordena). O banco volta ao retrato.
module.exports = {
  conta: 'administrador',
  async gravar(v) {
    v.capitulo('O que cada perfil pode');
    await v.facaComigo('perfis-de-acesso'); // painel/app/adms/Roteiros/perfis-de-acesso.json: o mesmo do botão do guia
    v.poster();

    v.capitulo('A coordenação do curso');
    await v.ir(`painel/cursos/visualizar/${v.ids.curso}`);
    await v.passo('A Coordenação de curso vê só os cursos em que é coordenadora. Quem indica é o Administrador ou a Direção acadêmica, na tela do curso.', { t: 'Quem coordena o curso', aviso: 'Clique em **Adicionar coordenador**.' });
    await v.clicar('[data-bs-target="#addCoordenadorCurso"]');
    await v.page.waitForSelector('#addCoordenadorCurso #usuario_id', { state: 'visible', timeout: 8000 });
    const pessoa = await v.page.$$eval('#addCoordenadorCurso #usuario_id option', (os) => (os.find((o) => /Marcos/.test(o.textContent)) || os.find((o) => o.value) || {}).value);
    v.confere(!!pessoa, 'a lista de pessoas deveria ter opções');
    await v.escolher('#addCoordenadorCurso #usuario_id', pessoa);
    const pos = await v.page.$$eval('#addCoordenadorCurso #posicao_id option', (os) => (os.find((o) => o.value) || {}).value);
    if (pos) await v.escolher('#addCoordenadorCurso #posicao_id', pos);
    await v.passo('Escolha a pessoa e a posição, e salve. A pessoa passa a ver, em Minhas disciplinas e em Relatórios, só os dados deste curso.', { t: 'A pessoa e a posição', aviso: 'Clique em **Salvar**.' });
    await v.clicarENavegar('#addCoordenadorCurso [name="CadCoordenadorCurso"]');
    v.confere(/coordena|sucesso|adicionad/i.test(await v.alerta()) || /Marcos/.test(await v.texto('body')), 'a coordenação deveria ser gravada: ' + (await v.alerta()));
    await v.feito('A coordenação foi indicada.');
  },
};
