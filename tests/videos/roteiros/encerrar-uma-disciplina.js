// Guia: Encerrar uma disciplina (secretaria). O encerramento recusado por pendência (falta a Prova obrigatória) e o
// encerramento certo: prévia, regra que vale, confirmação e o fechamento gravado. Dados fictícios; o banco volta ao retrato.
let OK;        // GOC: notas completas (o sql abaixo lança as do Trabalho final)
let PENDENTE;  // STF: a regra da disciplina exige uma Prova, que ainda não foi cadastrada
module.exports = {
  conta: 'secretaria.demo',
  sql: ids => `
    INSERT INTO rm_media_regras (id, nome, descricao, padrao, escopo, escopo_id, versao_atual, situacao, created)
      VALUES (90, 'Atividades 40% · Prova 60%', 'Regra fictícia do vídeo', 0, 'catalogo', 0, 1, 1, NOW());
    INSERT INTO rm_media_regra_versoes (id, regra_id, versao, casas, arredondamento, momento, sem_nota, componente_vazio, nota_minima, created)
      VALUES (90, 90, 1, 1, 'meio_para_cima', 'so_final', 'zero', 'redistribuir', 7.00, NOW());
    INSERT INTO rm_media_regra_componentes (versao_id, codigo, nome, peso, modo, ordem, min_atividades, max_atividades) VALUES
      (90, 'atividades', 'Atividades', 40, 'aritmetica', 1, 0, NULL), (90, 'prova', 'Prova', 60, 'aritmetica', 2, 1, 1);
    UPDATE uni_rh_cursos_disciplinas_grade SET regra_media_id = 90 WHERE id = ${ids.linha.STF};
    UPDATE rm_disciplina_atividade SET componente_codigo = 'atividades' WHERE rm_prof_mat_id = ${ids.oferta.STF};
    UPDATE rm_disciplina_atividade_notas SET nota = ELT(ordenacao, 8.0, 7.5, 6.0, 7.0, 9.0, 8.0, 9.5, 5.0), data_alt = NOW(), usuario_alt = ${ids.usuario['prof.laura.demo']}
      WHERE atividade_id = ${ids.atividade.GOC[1]};`,
  async gravar(v) {
    OK = v.ids.oferta.GOC; PENDENTE = v.ids.oferta.STF; // 1.18.2: da demonstração em uso, sem id fixo
    v.capitulo('Com pendência, não encerra');
    await v.ir(`painel/disciplinas/encerrar/${PENDENTE}`);
    await v.passo('Encerramento de "Trabalho de Conclusão de Curso": a regra da disciplina exige uma Prova, e ela não foi cadastrada.');
    const imp = await v.texto('#impedimentos');
    v.confere(/Falta cadastrar: Prova/.test(imp), 'a pendência deveria aparecer: ' + imp);
    v.confere(!(await v.page.$('[name="ConfirmarEncerramento"]')), 'sem o botão de confirmar');
    await v.destacar('#impedimentos');
    const res = await v.texto('#tabelaPrevia tbody tr:first-child td.resultado');
    v.confere(/Pendente.*falta Prova/.test(res), 'a prévia deveria mostrar "Pendente — falta Prova": ' + res);
    await v.passo('Recusado: "Falta cadastrar: Prova (mínimo 1)". Na prévia, cada aluno fica "Pendente — falta Prova", sem botão de encerrar.');
    await v.destacar('#tabelaPrevia tbody tr:first-child td.resultado');

    v.capitulo('A prévia');
    await v.ir('painel/disciplinas/listar');
    await v.passo('Outra disciplina, com todas as notas e faltas lançadas: Todas as Disciplinas → ver.');
    await v.clicarENavegar(`a[href$="disciplinas/visualizar/${OK}"]`);
    await v.passo('Na barra do topo da disciplina, clique em Encerramento.');
    await v.clicarENavegar(`a[href$="disciplinas/encerrar/${OK}"]`);
    const prev = await v.texto('.alert-info');
    v.confere(/Prévia do encerramento/.test(prev) && /Padrão da instituição/.test(prev), 'a prévia deveria dizer a regra: ' + prev);
    await v.passo('A prévia diz qual regra de média vai valer, pelo nome e de onde vem (aqui, o padrão da instituição).');
    await v.destacar('.alert-info');
    await v.passo('Para cada aluno: a conta, a média final, as faltas, a frequência e o resultado. "Imprimir a prévia" imprime a conferência.');
    await v.rolar('#tabelaPrevia');
    await v.destacar('#tabelaPrevia tbody tr[data-rm="260108"]');
    await v.destacar('button[title="Imprimir a prévia"]');

    v.capitulo('Confirmar');
    await v.passo('Conferido, clique em "Confirmar encerramento".');
    await v.clicarENavegar('[name="ConfirmarEncerramento"]');
    v.confere(/encerrada/i.test(await v.alerta()), 'a disciplina deveria ser encerrada: ' + await v.alerta());
    await v.passo('Encerrada: abre Notas e resultado com os valores GRAVADOS (a regra usada e a memória de cálculo); notas e faltas travam.');
    v.confere(/encerrada/i.test(await v.texto('#situacaoOferta')), 'Notas e resultado deveria mostrar a faixa de encerrada');
    await v.destacar('#situacaoOferta');
    await v.destacar('#tabelaNotas');
    v.poster();
    await v.passo('Em Encerramento, o quadro Fechamentos lista o vigente e os anteriores. Encerrou com erro? Só o administrador reabre.');
    await v.clicarENavegar(`a[href$="disciplinas/encerrar/${OK}"]`);
    await v.rolar('#fechamentos');
    const fx = await v.page.$$eval('#fechamentos tr[data-numero]', t => t.map(x => x.dataset.situacao));
    v.confere(fx.includes('vigente'), 'o quadro Fechamentos deveria ter o fechamento vigente');
    await v.destacar('#fechamentos');
  },
};
