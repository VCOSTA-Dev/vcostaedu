// Guia: Situação da grade (administrador). Uma grade 2 inventada, que a secretaria mandou para "Em análise": finalizar,
// a recusa de reabrir sem justificativa, reabrir com justificativa, desativar (não gera oferta). O retrato desfaz tudo.
const G = 9001, MOD = 9001;
module.exports = {
  conta: 'administrador',
  sql: ids => `
    INSERT INTO rm_cursos_grades (id, curso_id, versao, descricao, situacao, usuario_cre, created) VALUES (${G}, ${ids.curso}, 2, 'Grade 2027 (vídeo)', 2, 5, NOW());
    INSERT INTO rm_cursos_turma_series (id, curso_id, grade_id, periodo, codnome, descricao, ordem, situacao, usuario_cre, created)
      VALUES (${MOD}, ${ids.curso}, ${G}, 1, 'Geral', 'Módulo interno da grade.', 1, 1, 5, NOW());
    INSERT INTO uni_rh_cursos_disciplinas_grade (grade_id, modulo_id, materia_id, cargahoraria, ordem, usuario_cre, created) VALUES
      (${G}, ${MOD}, ${ids.materia.FAP}, 60, 1, 5, NOW()), (${G}, ${MOD}, ${ids.materia.OFP}, 60, 2, 5, NOW()), (${G}, ${MOD}, ${ids.materia.GOC}, 60, 3, 5, NOW());
    INSERT INTO uni_rh_cursos_gradeamento_obs (grade_id, alteracao, observacao, usuario_cre, created) VALUES
      (${G}, 1, 'Grade criada.', 5, NOW() - INTERVAL 1 DAY), (${G}, 2, 'Grade 2027 pronta para conferência.', 5, NOW() - INTERVAL 1 HOUR);`,
  async gravar(v) {
    const situacao = async (id, just) => {
      await v.escolher('#situacao', String(id));
      if (just !== undefined) await v.digitar('#observacao', just);
      await v.clicarENavegar('#quadroSituacao button[type="submit"]');
    };

    v.capitulo('A grade em análise');
    await v.ir('painel/matriz/listar');
    await v.passo('Ensino → Matriz: a secretaria montou a grade 2 e a mandou para "Em análise". Abra a grade.');
    await v.clicarENavegar(`a[href$="matriz/visualizar/${G}"]`);
    v.confere(await v.page.$('#faixaTrava'), 'a grade em análise deveria mostrar a faixa de trava');
    await v.passo('Travada, a grade mostra uma faixa com o motivo e o botão "Ir para a situação da grade".');
    await v.clicar('#faixaTrava a');
    await v.pausa(800);

    v.capitulo('Finalizar');
    await v.passo('Em Situação da grade, escolha "Finalizada". A justificativa é opcional aqui. Salve.');
    await situacao(3, 'Matriz conferida para a turma 2027.');
    v.confere(/Finalizada/.test(await v.alerta()), 'a grade deveria ficar Finalizada: ' + await v.alerta());
    await v.rolar('#registroAlteracoes');
    await v.passo('Finalizada: é a matriz oficial da turma. A troca fica no Registro de alterações, com quem mudou e quando.');
    await v.destacar('#registroAlteracoes');

    v.capitulo('Reabrir pede justificativa');
    await v.rolar('#quadroSituacao');
    await v.passo('Para mudar a grade de novo, reabra (Aberta). Vamos tentar sem a justificativa.');
    await situacao(1, '');
    const rec = await v.alerta();
    v.confere(/justificativa/i.test(rec), 'reabrir sem justificativa deveria ser recusado: ' + rec);
    await v.destacar('.alert');
    await v.passo('Recusado: "Para reabrir uma grade «Finalizada», escreva a justificativa (ao menos 5 caracteres)."');
    await v.rolar('#quadroSituacao');
    await situacao(1, 'Incluir a disciplina de TCC pedida pela coordenação.');
    v.confere(/Aberta/.test(await v.alerta()), 'a grade deveria ser reaberta: ' + await v.alerta());
    await v.rolar('#registroAlteracoes');
    v.poster();
    await v.passo('Reaberta. O registro guarda a justificativa de quem reabriu.');
    await v.destacar('#registroAlteracoes li:first-child');

    v.capitulo('Desativada');
    await v.rolar('#quadroSituacao');
    await v.passo('Desativada: a grade não aceita aluno nem oferta nova.');
    await situacao(4, 'Grade substituída (vídeo).');
    v.confere(/Desativada/.test(await v.alerta()), 'a grade deveria ficar Desativada: ' + await v.alerta());
    await v.rolar(0 - 2000);
    const gerar = v.page.locator('.acao-desabilitada[title*="Desativada"], .acao-desabilitada[data-bs-original-title*="Desativada"], .acao-desabilitada[aria-label*="Desativada"]').first();
    v.confere(await gerar.count(), '"Gerar oferta do ano" deveria ficar desabilitado');
    await v.destacar(gerar);
    await v.passo('O "Gerar oferta do ano" fica desabilitado: "Grade Desativada: não gera oferta". Para usar de novo, reabra.');
  },
};
