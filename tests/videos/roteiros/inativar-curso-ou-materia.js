// Guia: Inativar curso ou matéria (administrador). Inativa o curso 2 da demonstração (em uso: Apagar desabilitado),
// mostra que ele sai da escolha da nova grade, reativa; depois o mesmo com uma matéria. O banco volta ao retrato no fim.
module.exports = {
  conta: 'administrador',
  async gravar(v) {
    const curso = 'Pós-graduação em Docência no Ensino Superior';
    const materia = 'Tecnologias Educacionais';
    const linhaCurso = () => v.page.locator('#tabelaNivel tbody tr', { hasText: curso }).first();

    v.capitulo('Curso em uso não se apaga');
    await v.ir('painel/cursos/listar');
    await v.passo('Ensino → Cursos. O curso de Docência no Ensino Superior já tem grade e matrícula: o Apagar fica desabilitado.');
    const apagar = linhaCurso().locator('.acao-desabilitada').last();
    const razao = await apagar.evaluate(e => e.getAttribute('title') || e.getAttribute('data-bs-original-title'));
    v.confere(/Em uso/i.test(razao || ''), 'o Apagar deveria estar desabilitado com "Em uso": ' + razao);
    await v.destacar(apagar);
    await v.passo(`A dica diz: "${razao.slice(0, 90)}". É o caso de INATIVAR.`);

    v.capitulo('Inativar o curso');
    await v.passo('Na linha, clique em Inativar (ícone de proibido) e confirme.');
    await v.clicarENavegar(linhaCurso().locator('form[action*="cursos/situacao"] button'));
    v.confere(/inativad/i.test(await v.alerta()), 'o curso deveria ser inativado: ' + await v.alerta());
    v.confere(/Inativo/.test(await linhaCurso().textContent()), 'a situação deveria passar a Inativo');
    v.poster();
    await v.passo('A situação passa a Inativo e, no lugar do botão, aparece Reativar. Ofertas, notas e históricos não mudam.');
    await v.destacar(linhaCurso().locator('.badge'));

    v.capitulo('Fora das listas de escolha');
    await v.ir('painel/matriz/listar');
    await v.passo('Em Ensino → Matriz, o curso inativo aparece com a etiqueta Inativo, e "Nova grade" fica desabilitado.');
    const nova = v.page.locator(`#curso-${v.ids.curso2} .card-actions .acao-desabilitada`).first();
    const dica = await nova.evaluate(e => e.getAttribute('title') || e.getAttribute('data-bs-original-title')).catch(() => '');
    v.confere(/inativo/i.test(dica || '') && /Inativo/.test(await v.texto(`#curso-${v.ids.curso2} .card-title`)), 'Nova grade deveria estar desabilitado no curso inativo: ' + dica);
    await v.destacar(`#curso-${v.ids.curso2} .card-title .badge`);
    await v.destacar(nova);
    await v.passo(`A dica explica: "${dica}". O mesmo vale para nova oferta e vínculo do aluno.`);
    /* 1.18.1 (D-479): na "Nova grade", o curso inativo nem aparece no seletor (e o servidor recusa) */
    await v.ir('painel/matriz/cadastrar-grade');
    const cursosNaEscolha = await v.page.$$eval('#curso_id option', os => os.map(o => o.textContent.trim()));
    v.confere(cursosNaEscolha.length > 1 && !cursosNaEscolha.some(t => /Docência no Ensino Superior/.test(t)), 'o curso inativo não deveria estar no seletor da Nova grade: ' + cursosNaEscolha.join(' | '));
    await v.passo('E na tela "Nova grade", o seletor de curso só traz cursos ativos: o de Docência no Ensino Superior, inativo, não aparece.');
    await v.clicar('#curso_id');
    await v.pausa(1800);
    await v.page.keyboard.press('Escape');

    v.capitulo('Reativar');
    await v.ir('painel/cursos/listar');
    await v.passo('Para voltar a oferecer, clique em Reativar: o curso volta às listas de escolha.');
    await v.clicarENavegar(linhaCurso().locator('form[action*="cursos/situacao"] button'));
    v.confere(/reativad/i.test(await v.alerta()), 'o curso deveria ser reativado: ' + await v.alerta());
    await v.destacar(linhaCurso().locator('.badge'));

    v.capitulo('Matéria: o mesmo caminho');
    await v.ir('painel/materias/listar');
    const linhaMat = () => v.page.locator('#tabelaMaterias tbody tr', { hasText: materia }).first();
    await v.passo('Matéria: Ensino → Matérias (a secretaria também pode). Inativar e confirmar.');
    await v.clicarENavegar(linhaMat().locator('form[action*="materias/situacao"] button'));
    v.confere(/inativad/i.test(await v.alerta()) && /Inativo/.test(await linhaMat().textContent()), 'a matéria deveria ficar Inativa: ' + await v.alerta());
    await v.passo('Inativa, ela sai da matriz e da nova oferta, mas continua aqui, com a etiqueta Inativo.');
    await v.destacar(linhaMat().locator('.badge'));
    await v.clicarENavegar(linhaMat().locator('form[action*="materias/situacao"] button'));
    v.confere(/reativad/i.test(await v.alerta()), 'a matéria deveria ser reativada: ' + await v.alerta());
    await v.passo('Reativar devolve a matéria às listas de escolha.');
    await v.destacar(linhaMat().locator('.badge'));
  },
};
