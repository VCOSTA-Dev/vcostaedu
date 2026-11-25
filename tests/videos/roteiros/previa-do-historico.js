// Guia: Conferir a prévia do histórico (secretaria). Busca o aluno, confere aluno, matrícula, ato legal, disciplinas,
// TCC e totais, lê os alertas e abre a prévia em PDF. Aluno fictício João (250102), com o TCC ainda em andamento.
const RA = '250102';
module.exports = {
  conta: 'secretaria.demo',
  async gravar(v) {
    const mc = v.ids.matricula[RA];
    v.capitulo('Buscar o aluno');
    await v.ir('painel/historico/previa');
    await v.passo('Educacional → Prévia do histórico. Busque o aluno pelo RA ou pelo nome.');
    await v.digitar('input[name="aluno"]', RA);
    await v.clicarENavegar('form[action$="historico/previa"] button[type="submit"]');
    await v.passo('Escolha a matrícula e clique em "Ver prévia".');
    await v.clicarENavegar(`#matriculasPrevia a[href$="historico/previa/${mc}"]`);
    v.confere(await v.page.$('#previa'), 'a prévia deveria abrir');

    v.capitulo('Os alertas');
    const al = await v.texto('#alertasPrevia');
    console.log('    (alertas: ' + al.slice(0, 250) + ')');
    v.confere(/alerta\(s\)/.test(al) && /TCC/.test(al), 'a prévia do João deveria alertar o TCC: ' + al);
    await v.passo('No topo, os alertas: o que falta para o histórico. Aqui, o curso exige TCC e o dele ainda não foi aprovado.');
    await v.destacar('#alertasPrevia');
    v.poster();
    await v.passo('O histórico FINAL só sai sem nenhum alerta. Resolva cada um onde o alerta indica.');

    v.capitulo('Conferir os dados');
    await v.passo('Os dados do aluno e a matrícula: polo, modalidade, ingresso e conclusão.');
    await v.rolar('#poloModalidade');
    await v.destacar('#poloModalidade');
    await v.passo('A instituição e o ato legal da modalidade da matrícula, na data de referência.');
    await v.destacar('#atoLegal');
    const disc = v.page.locator('#previa .card.disciplinasGrade, #previa .card.modulo').first();
    await v.rolar(disc);
    await v.passo('As disciplinas com todas as tentativas (a vigente em destaque): docente, titulação, datas, média, frequência, resultado.');
    await v.destacar(disc);
    await v.rolar('#tccPrevia');
    await v.passo('Por fim, o TCC e os totais.');
    await v.destacar('#tccPrevia');
    await v.destacar('#totaisPrevia');

    v.capitulo('A barra do topo');
    await v.rolar('#barraPrevia');
    await v.passo('Na barra do topo: a prévia em PDF (parcial ou final), imprimir a conferência, a ficha do aluno e "Emitir histórico".');
    await v.destacar('#barraPrevia');
    await v.passo('"Prévia em PDF — parcial": o documento com a tarja PRÉVIA. Nada é gravado.');
    await v.pdf(`#barraPrevia a[href*="historico-emissoes/previa/${mc}?tipo=PARCIAL"]`, 6);
    await v.passo('Para corrigir dados pessoais, a matrícula ou o TCC: "Ir para a ficha do aluno".');
    await v.clicarENavegar(`#barraPrevia a[href*="academicos/visualizar/"]`);
    v.confere(/academicos\/visualizar/.test(v.page.url()), 'deveria abrir a ficha do aluno');
  },
};
