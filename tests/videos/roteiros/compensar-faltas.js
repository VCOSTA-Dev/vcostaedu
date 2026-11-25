// Guia: Compensar faltas (secretaria). Mais que as faltas e o período invertido são recusados; a compensação certa
// recalcula a frequência e entra no histórico. Dados fictícios; o banco volta ao retrato no fim.
let OFERTA;   // STF, aberta; o aluno 260106 tem 2 faltas
const RA = '260106';
async function abrirModal(v) {
  await v.clicar(`#tabelaFaltas tr[data-rm="${RA}"] button[data-bs-target="#modalCompensar"]`);
  await v.page.waitForSelector('#modalCompensar.show', { timeout: 10000 });
  await v.pausa(500);
}
module.exports = {
  conta: 'secretaria.demo',
  async gravar(v) {
    OFERTA = v.ids.oferta.STF; // 1.18.2: da demonstração em uso, sem id fixo
    v.capitulo('Abrir as faltas');
    await v.ir(`painel/disciplinas/visualizar/${OFERTA}`);
    await v.passo('Na disciplina, clique em Faltas. As faltas já foram lançadas pelo professor.');
    await v.clicarENavegar(`a[href$="faltas/visualizar/${OFERTA}"]`);
    await v.passo('Na linha do aluno, o botão Compensar. Fábio tem 2 faltas: 86,67% de frequência.');
    v.confere((await v.texto(`#tabelaFaltas tr[data-rm="${RA}"] td.frequencia`)).startsWith('86,67'), 'o aluno deveria começar com 86,67%');
    await v.destacar(`#tabelaFaltas tr[data-rm="${RA}"]`);
    await abrirModal(v);

    v.capitulo('O que é recusado');
    await v.passo('A tela diz até quanto dá para compensar. Mais que as faltas (3) não passa.');
    await v.destacar('#compMax');
    await v.digitar('#compQtd', '3');
    await v.clicar('#formCompensar button[type="submit"]');
    const msg = await v.page.$eval('#compQtd', el => el.validationMessage).catch(() => '');
    v.confere(!!msg, 'o navegador deveria barrar 3 compensadas com 2 faltas');
    await v.destacar('#compQtd');
    await v.passo('Quantidade 2, tipo Atestado médico e a justificativa. Vamos pôr o fim do período antes do início.');
    await v.digitar('#compQtd', '2');
    await v.escolher('#compTipo', 'atestado');
    await v.page.fill('#compIni', '2026-03-20'); await v.destacar('#compIni');
    await v.page.fill('#compFim', '2026-03-18'); await v.destacar('#compFim');
    await v.digitar('#compJust', 'Atestado médico entregue na secretaria (fictício).');
    await v.clicarENavegar('#formCompensar button[type="submit"]');
    const r1 = await v.texto('#avisoCompensacao');
    v.confere(/fim do período não pode ser antes do início/.test(r1), 'o período invertido deveria ser recusado: ' + r1);
    await v.passo('Recusado: "O fim do período não pode ser antes do início." Nada foi salvo.');
    await v.destacar('#avisoCompensacao');

    v.capitulo('A compensação certa');
    await v.passo('De novo, com o período certo. Clique em "Lançar compensação".');
    await abrirModal(v);
    await v.digitar('#compQtd', '2');
    await v.escolher('#compTipo', 'atestado');
    await v.page.fill('#compIni', '2026-03-18'); await v.destacar('#compIni');
    await v.page.fill('#compFim', '2026-03-20'); await v.destacar('#compFim');
    await v.digitar('#compJust', 'Atestado médico entregue na secretaria (fictício).');
    await v.clicarENavegar('#formCompensar button[type="submit"]');
    v.confere(/Compensação lançada/.test(await v.texto('#avisoCompensacao')), 'a compensação deveria ser lançada: ' + await v.alerta());
    const comp = await v.texto(`#tabelaFaltas tr[data-rm="${RA}"] td.compensadas`), fr = await v.texto(`#tabelaFaltas tr[data-rm="${RA}"] td.frequencia`);
    v.confere(comp === '2' && fr.startsWith('100'), `compensadas 2 e frequência 100%: ${comp} / ${fr}`);
    await v.passo('"Compensação lançada." Compensadas: 2. A frequência é recalculada na hora: 100%.');
    await v.destacar(`#tabelaFaltas tr[data-rm="${RA}"]`);
    v.poster();

    await v.passo('O histórico registra quem lançou, quando, o tipo, o período e o motivo. Um lançamento errado sai pela lixeira.');
    await v.rolar('#historicoCompensacoes');
    await v.destacar('#tabelaCompensacoes tbody tr:first-child');
    await v.passo('O aluno (e o professor) vê só a quantidade compensada, nunca o motivo.');
  },
};
