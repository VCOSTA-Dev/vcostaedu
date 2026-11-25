// Guia: O ano letivo, do começo ao fim (secretaria). Visão geral: o guia mestre na Ajuda e uma passada pela tela de cada
// passo, na ordem, com a recusa de quem pula um passo (encerrar uma oferta sem aluno). Oferta vazia inventada; o retrato desfaz.
const OF = 9001;
module.exports = {
  conta: 'secretaria.demo',
  sql: ids => `
    INSERT INTO rm_professores_materias (id, curso_id, grade, periodo, codmat, codtur, materia, ch_considerada, ano, bimestre, tipo, codfilial, situacao,
      usuario_cre, created, matriz_id, idMoodle, modalidade_id, encontros, frequencia_fonte, data_inicio, data_fim)
    SELECT ${OF}, curso_id, grade, periodo, codmat, 'PGP-2026-B', materia, ch_considerada, ano, bimestre, tipo, codfilial, 1, 5, NOW(), matriz_id, NULL, modalidade_id, 15, 'lancamento', data_inicio, data_fim
      FROM rm_professores_materias WHERE id = ${ids.oferta.GOC};`,
  async gravar(v) {
    v.capitulo('O caminho na Ajuda');
    await v.ir('painel/ajuda/index');
    await v.passo('Ajuda → Central de ajuda: o "Comece por aqui" é o caminho do ano, na ordem em que as coisas acontecem.');
    await v.destacar('#comecePorAqui');
    await v.clicarENavegar('#comecePorAqui a[href$="ajuda/guia/ano-letivo-passo-a-passo"]');
    const tabela = v.page.locator('table', { hasText: 'Cadastrar as matérias' }).first();
    v.confere(await tabela.count(), 'o guia deveria mostrar a tabela dos passos');
    await v.rolar(tabela);
    v.poster();
    await v.passo('O guia mestre: cada passo, quem faz e o guia de cada um. Siga a ordem: cada passo depende do anterior.');
    await v.destacar(tabela);

    v.capitulo('1 a 3 — Matérias e matriz');
    await v.ir('painel/materias/listar');
    await v.passo('1. Secretaria: as matérias do catálogo, com o código que sai no histórico (Ensino → Matérias).');
    await v.destacar('#tabelaMaterias');
    await v.ir('painel/matriz/listar');
    await v.passo('2 e 3. A matriz: a grade e as disciplinas (secretaria). Quem finaliza a grade é o administrador.');
    await v.destacar(`#curso-${v.ids.curso} table`);

    v.capitulo('4 — A oferta do ano');
    await v.ir('painel/disciplinas/listar');
    await v.passo('4. Sem matriz não há oferta: gere a oferta do ano e complete cada uma (período, professor).');
    await v.destacar('#tabelaDisciplinas');

    v.capitulo('5 e 6 — Alunos e enturmação');
    await v.ir('painel/academicos/listar');
    await v.passo('5. Cadastre os alunos e envie o link de acesso (Educacional → Usuários Acadêmico).');
    await v.ir(`painel/academicos/visualizar/${v.ids.aluno['260101']}`);
    await v.clicar('#tabeMatricula-tab'); await v.pausa(500);
    await v.passo('6. Na ficha, aba Matrículas: o vínculo ao curso e o botão Enturmação de cada ano.');
    await v.destacar('.vinculo');

    v.capitulo('7 — Notas e faltas');
    await v.ir(`painel/medias/final/${v.ids.oferta.GOC}`);
    await v.passo('7. Sem enturmação não há nota: o professor lança atividades, notas e faltas; a secretaria compensa faltas.');
    await v.destacar('.barra-disciplina');

    v.capitulo('8 — Encerrar: quem pula um passo é recusado');
    await v.ir(`painel/disciplinas/encerrar/${OF}`);
    const imp = await v.texto('#impedimentos');
    v.confere(/não tem aluno enturmado/.test(imp), 'o encerramento da oferta vazia deveria ser recusado: ' + imp);
    await v.passo('8. Encerrar cada disciplina. Pulou um passo? A tela diz o motivo: esta oferta não tem aluno enturmado.');
    await v.destacar('#impedimentos');
    await v.passo('"A oferta não tem aluno enturmado." Volte ao passo que faltou (aqui, a enturmação).');

    v.capitulo('9 e 10 — Boletim e histórico');
    await v.ir(`painel/academicos/boletim/260101`);
    await v.passo('9. O boletim do aluno, para ver e imprimir.');
    await v.ir(`painel/historico/previa/${v.ids.matricula['260101']}`);
    await v.passo('10. Conclusão, TCC e histórico: a prévia avisa o que falta antes de emitir.');
    await v.passo('Regra que parece errada (média, frequência, casas decimais)? É do administrador: Regras de nota e frequência.');
  },
};
