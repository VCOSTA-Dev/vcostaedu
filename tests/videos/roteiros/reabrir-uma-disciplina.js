// Guia: Reabrir uma disciplina encerrada (administrador). Sem justificativa é recusado; com ela, a disciplina reabre e o
// fechamento anterior fica guardado como substituído. Dados fictícios; o banco volta ao retrato no fim.
let OFERTA;   // OFP, encerrada
module.exports = {
  conta: 'administrador',
  async gravar(v) {
    OFERTA = v.ids.oferta.OFP; // 1.18.2: da demonstração em uso, sem id fixo
    v.capitulo('Abrir o encerramento');
    await v.ir(`painel/disciplinas/visualizar/${OFERTA}`);
    await v.passo('Abra a disciplina encerrada e clique em Encerramento, na barra do topo.');
    await v.clicarENavegar(`a[href$="disciplinas/encerrar/${OFERTA}"]`);
    v.confere(await v.page.$('#reabrirOferta'), 'o quadro Reabrir deveria aparecer para o administrador');
    await v.passo('A faixa diz que a disciplina está encerrada. Embaixo, o quadro "Reabrir a disciplina".');
    await v.destacar('#ofertaEncerrada');
    await v.rolar('#reabrirOferta');
    /* 1.18.2 (D-491): a dica do quadro diz o que o sistema faz — não apaga; o fechamento fica guardado como substituído */
    const dica = await v.texto('#dicaReabrir');
    v.confere(/não apaga/.test(dica) && /substituído/.test(dica) && /justificativa/.test(dica), 'a dica de reabrir deveria dizer que não apaga: ' + dica);
    await v.destacar('#dicaReabrir');
    await v.passo('A dica do quadro: reabrir exige a justificativa e NÃO apaga o resultado — o fechamento fica guardado como substituído.');
    await v.destacar('#reabrirOferta');

    v.capitulo('Sem justificativa: recusado');
    await v.passo('Sem a justificativa, clique em Reabrir.');
    await v.clicarENavegar('#reabrirOferta button[type="submit"]');
    const r1 = await v.alerta();
    v.confere(/Escreva a justificativa da reabertura/.test(r1), 'deveria recusar sem justificativa: ' + r1);
    await v.passo('Recusado: "Escreva a justificativa da reabertura (ela fica no registro da disciplina)."');
    await v.destacar('.alert');

    v.capitulo('Com justificativa');
    await v.passo('Escreva o motivo em Justificativa e clique em Reabrir.');
    await v.rolar('#reabrirOferta');
    await v.digitar('#observacao', 'Correção de nota lançada errada (demonstração fictícia).');
    await v.clicarENavegar('#reabrirOferta button[type="submit"]');
    v.confere(/reaberta/i.test(await v.alerta()), 'a disciplina deveria reabrir: ' + await v.alerta());
    await v.passo('Reaberta: notas e faltas voltam a ser lançadas. A prévia do encerramento aparece de novo.');
    await v.destacar('.alert');
    await v.rolar('#fechamentos');
    const fx = await v.page.$$eval('#fechamentos tr[data-numero]', t => t.map(x => x.dataset.situacao));
    v.confere(fx.some(s => s !== 'vigente'), 'o fechamento anterior deveria ficar guardado: ' + fx.join(','));
    v.poster();
    await v.passo('Nada se apaga: o fechamento nº 1 fica "Substituído", com quem reabriu, quando e a justificativa.');
    await v.destacar('#fechamentos');
    await v.passo('O professor corrige; a secretaria encerra de novo (o fechamento nº 2). Históricos já emitidos não mudam.');
  },
};
