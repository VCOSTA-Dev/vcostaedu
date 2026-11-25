// Guia: Concluir o curso e registrar o TCC (secretaria). A conclusão recusada com o TCC em andamento, o TCC aprovado e a
// matrícula em "Concluído". Aluno fictício João (250102); o banco volta ao retrato no fim.
const RA = '250102';
module.exports = {
  conta: 'secretaria.demo',
  async gravar(v) {
    const alunoId = v.ids.aluno[RA];
    const vinc = '.vinculo[data-matricula="' + v.ids.matricula[RA] + '"]';
    const fv = vinc + ' form.formVinculo';
    const ft = vinc + ' form[action*="tcc/salvar"]';

    v.capitulo('A conclusão sem o TCC aprovado');
    await v.ir(`painel/academicos/visualizar/${alunoId}?tab=tabeMatricula`);
    await v.passo('Ficha do aluno, aba Matrículas. João cursou todas as disciplinas; o curso exige TCC, ainda "Em andamento".');
    await v.destacar(vinc + ' .tcc h6');
    await v.passo('Vamos tentar concluir assim: em Situação, escolha "Concluído" e informe a data de conclusão.');
    await v.escolher(fv + ' [name="situacao_id"]', '2');
    await v.destacar(fv + ' [name="data_conclusao"]');
    await v.page.locator(fv + ' [name="data_conclusao"]').fill('2025-12-15');
    await v.clicarENavegar(fv + ' button[type="submit"]');
    const rec = await v.texto('#conclusaoRecusada');
    v.confere(/RECUSADA/.test(rec) && /TCC/i.test(rec), 'a conclusão deveria ser recusada pelo TCC: ' + (rec || await v.alerta()));
    await v.passo('"Conclusão RECUSADA — falta: …" A lista diz o que falta (aqui, o TCC aprovado). Nada foi salvo.');
    await v.destacar('#conclusaoRecusada');

    v.capitulo('Registrar o TCC');
    await v.rolar(ft);
    await v.passo('No bloco TCC: título, orientador (um professor), nota, data da defesa ou entrega e a situação.');
    await v.destacar(ft + ' [name="titulo"]');
    await v.digitar(ft + ' [name="nota"]', '8,5');
    await v.destacar(ft + ' [name="data_defesa"]');
    await v.page.locator(ft + ' [name="data_defesa"]').fill('2025-12-05');
    await v.escolher(ft + ' [name="situacao"]', '2');
    await v.passo('"Aprovado" exige orientador, data e nota a partir da mínima (7,0). Clique em "Salvar TCC".');
    await v.clicarENavegar(ft + ' button[type="submit"]');
    v.confere(/TCC .* salvo \(Aprovado\)/.test(await v.alerta()), 'o TCC deveria ser salvo como Aprovado: ' + await v.alerta());
    await v.passo('TCC salvo como Aprovado.');
    await v.destacar(vinc + ' .tcc h6');

    v.capitulo('Concluir o curso');
    await v.passo('Agora, de novo: Situação "Concluído", a data de conclusão e "Salvar matrícula".');
    await v.escolher(fv + ' [name="situacao_id"]', '2');
    await v.destacar(fv + ' [name="data_conclusao"]');
    await v.page.locator(fv + ' [name="data_conclusao"]').fill('2025-12-15');
    await v.clicarENavegar(fv + ' button[type="submit"]');
    v.confere(/CONCLU[IÍ]DO em/.test(await v.alerta()), 'a conclusão deveria ser aceita: ' + await v.alerta());
    v.confere(/Conclu/.test(await v.texto(vinc + ' .card-title')), 'a matrícula deveria aparecer Concluída');
    await v.passo('Aceita: "curso CONCLUÍDO em 15/12/2025". A matrícula fica Concluída — isso libera o histórico FINAL.');
    await v.destacar(vinc + ' .card-header');
    v.poster();
    await v.passo('A data de conclusão não pode ser antes do ingresso; com disciplina em curso, reprovada ou não cursada, também é recusada.');
  },
};
