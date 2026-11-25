// Guia: Gerar a oferta do ano (secretaria). Uma turma nova inventada (PGP-2026-B) do curso principal: as recusas sem
// disciplina e com encontros inválidos, a geração, a repetição que não duplica e a lista. O retrato desfaz tudo.
const TURMA = 'PGP-2026-B';
module.exports = {
  conta: 'secretaria.demo',
  async gravar(v) {
    const marcas = 'input[name="linhas[]"]';
    const preencher = async encontros => {
      await v.escolher('#ano', '2026');
      await v.digitar('#codtur', TURMA);
      await v.digitar('#encontros', encontros);
    };

    v.capitulo('Escolher a grade');
    await v.ir('painel/disciplinas/listar');
    await v.passo('Ensino → Todas as Disciplinas: clique em "Gerar oferta do ano" (o mesmo botão está na tela da grade).');
    await v.clicarENavegar('a[href$="disciplinas/gerar-oferta"]');
    await v.passo('Escolha o curso e a grade. Grade Desativada não aparece: ela não gera oferta.');
    await v.escolher('#grade', String(v.ids.grade));
    await v.clicarENavegar('form[method="GET"] button[type="submit"]');
    v.confere(await v.page.locator(marcas).count() === 6, 'a grade deveria trazer as 6 disciplinas');
    await v.passo('As disciplinas da grade vêm todas marcadas: desmarque as que não serão oferecidas agora.');
    await v.destacar('#disciplinasDaGrade');

    v.capitulo('As recusas');
    await v.passo('Se todas ficarem desmarcadas, a geração é recusada.');
    for (const c of await v.page.locator(marcas).all()) await c.uncheck();
    await preencher('15');
    await v.clicarENavegar('button[name="GerarOferta"]');
    v.confere(/Escolha ao menos uma disciplina/i.test(await v.alerta()), 'sem disciplina deveria recusar: ' + await v.alerta());
    await v.destacar('.alert');
    await v.passo('"Escolha ao menos uma disciplina." Agora com as disciplinas, mas 0 encontros:');
    await preencher('0');
    await v.clicarENavegar('button[name="GerarOferta"]');
    v.confere(/encontros deve ser um número inteiro de 1 a 999/i.test(await v.alerta()), 'encontros 0 deveria recusar: ' + await v.alerta());
    await v.destacar('.alert');
    await v.passo('"O total de encontros deve ser um número inteiro de 1 a 999 (ou vazio)." Ele é a base da frequência.');

    v.capitulo('Gerar');
    await v.passo('Desmarque o Trabalho de Conclusão de Curso (será ofertado depois). Ano, turma, polo, modalidade (vazio = a do curso) e encontros.');
    const stf = v.page.locator('#disciplinasDaGrade .form-check', { hasText: 'Trabalho de Conclusão' }).locator('input');
    await v.destacar(stf);
    await stf.uncheck();
    await preencher('15');
    await v.destacar('#codfilial');
    await v.destacar('#modalidade_id');
    await v.clicarENavegar('button[name="GerarOferta"]');
    const ok = await v.alerta();
    v.confere(/5<?\/?b?>? ?criada|5 criada/i.test(ok.replace(/\s+/g, ' ')), 'deveriam sair 5 ofertas: ' + ok);
    await v.destacar('.alert');
    await v.passo('Sai uma oferta por disciplina marcada: 5 criadas. Os professores vêm depois, em cada oferta.');

    v.capitulo('Repetir não duplica');
    await v.passo('Gerar de novo com a mesma turma (o TCC de novo desmarcado) não duplica: a oferta que já existe é pulada.');
    const stf2 = v.page.locator('#disciplinasDaGrade .form-check', { hasText: 'Trabalho de Conclusão' }).locator('input');
    await v.destacar(stf2); await stf2.uncheck();
    await preencher('15');
    await v.clicarENavegar('button[name="GerarOferta"]');
    v.confere(/0 criada.*5 já existia/i.test((await v.alerta()).replace(/\s+/g, ' ')), 'a repetição deveria pular as 5: ' + await v.alerta());
    await v.destacar('.alert');
    await v.passo('"0 criada(s), 5 já existia(m)": nada foi duplicado.');

    v.capitulo('Conferir a lista');
    await v.ir('painel/disciplinas/listar');
    await v.digitar('#tabelaDisciplinas_filter input', TURMA);
    await v.pausa(600);
    const n = await v.page.locator('#tabelaDisciplinas tbody tr', { hasText: TURMA }).count();
    v.confere(n === 5, 'a lista deveria mostrar as 5 ofertas da turma nova: ' + n);
    v.poster();
    await v.passo('Em Todas as Disciplinas, as 5 ofertas da turma nova, Abertas e sem professor. Agora, complete cada oferta.');
    await v.destacar('#tabelaDisciplinas tbody');
  },
};
