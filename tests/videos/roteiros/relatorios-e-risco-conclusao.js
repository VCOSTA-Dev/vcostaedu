// Guia: Relatórios E — risco, evasão e conclusão (administrador). Alunos em risco com os sinais e o filtro por sinal, aptos
// a concluir (o atalho do histórico final e "Cursando com pendência"), coorte de ingresso e concluintes. A preparação aprova
// um TCC inventado, para haver um apto; o retrato desfaz.
module.exports = {
  conta: 'administrador',
  sql: () => `UPDATE rm_tcc SET situacao = 2, nota = 8.5, data_defesa = '2026-09-18' WHERE titulo LIKE 'Avaliação formativa em cursos fictícios a distância%';`,
  async gravar(v) {
    const filtrar = () => v.clicarENavegar('#filtrosRelatorio button[type="submit"]');
    const cartao = rot => v.page.locator('.vc-cartao-rel', { has: v.page.locator('.text-secondary', { hasText: rot }) }).first();

    v.capitulo('Alunos em risco');
    await v.ir('painel/relatorios/alunos-risco');
    await v.escolher('#f_curso', String(v.ids.curso));
    await filtrar();
    const total = (await cartao('Matrículas em risco').locator('.h1').textContent()).trim();
    v.confere(+total > 0, 'deveria haver matrículas em risco: ' + total);
    await v.passo('Alunos em risco: matrículas em Cursando com ao menos um sinal. O alerta não dá nota: mostra o sinal e o porquê.');
    await v.destacar(cartao('Matrículas em risco'));
    await v.passo('Os sinais: frequência perto do mínimo, média parcial abaixo da mínima, atividades sem nota, reprovação anterior, sem enturmação.');
    await v.destacar(cartao('Frequência perto do mínimo'));
    await v.destacar(cartao('Reprovação anterior'));
    await v.passo('Frequência: abaixo do mínimo, ou ao menos 1 falta não compensada e só até a margem (padrão 2). Sem falta, não entra.');
    await v.passo('Filtre por sinal para agir em grupo. Aqui, só a frequência.');
    await v.escolher('#f_sinal', 'frequencia');
    await filtrar();
    const n = await v.page.locator('table.lista-dados tbody tr').count();
    const nFreq = (await cartao('Frequência perto do mínimo').locator('.h1').textContent()).trim();
    v.confere(/sinal=frequencia/.test(v.page.url()) && n === +nFreq && n > 0, `a tabela (${n}) deveria ter só o sinal de frequência (${nFreq})`);
    await v.rolar('table.lista-dados');
    await v.destacar('table.lista-dados tbody tr:first-child');

    v.capitulo('Aptos a concluir');
    await v.ir('painel/relatorios/aptos-concluir');
    const aptos = (await cartao('Aptos a concluir').locator('.h1').textContent()).trim();
    v.confere(+aptos >= 1, 'deveria haver um apto a concluir: ' + aptos);
    const atalho = v.page.locator('table.lista-dados a[href*="historico-emissoes/emitir/"]').first();
    v.confere(await atalho.count(), 'o apto deveria ter o atalho "Emitir histórico final"');
    v.poster();
    await v.passo('Aptos a concluir: todas as disciplinas da grade aprovadas e o TCC aprovado, quando o curso exige. A mesma regra do Concluído.');
    await v.destacar('table.lista-dados tbody tr:first-child');
    await v.passo('O atalho abre a emissão do histórico final. Antes, mude a matrícula para Concluído, com a data (o aviso no topo lembra).');
    await v.destacar(atalho);
    await v.destacar('.alert-warning');
    await v.passo('"Cursando com pendência" mostra o que falta aos outros: disciplina não aprovada na tentativa vigente ou TCC.');
    await v.clicarENavegar(cartao('Cursando com pendência').locator('a.card-footer'));
    v.confere(/detalhe=pendentes/.test(v.page.url()), 'a lista "Cursando com pendência" deveria abrir');
    await v.destacar('table.lista-dados thead th:has-text("O que falta")');

    v.capitulo('Coorte de ingresso');
    await v.ir('painel/relatorios/coorte-ingresso');
    v.confere(await v.page.locator('table.lista-dados tbody tr').count() >= 2, 'a coorte deveria ter os semestres de ingresso');
    await v.passo('Coorte de ingresso (administrador e diretoria): por semestre de ingresso, quantos concluíram, seguem, trancaram ou saíram.');
    await v.rolar('table.lista-dados');
    await v.passo('Evasão = cancelados + transferidos. Retenção (atraso no fluxo) = trancados + cursando com a previsão vencida.');
    await v.destacar('table.lista-dados thead');

    v.capitulo('Concluintes');
    await v.ir('painel/relatorios/concluintes');
    v.confere(await v.page.locator('table.lista-dados tbody tr').count() >= 1, 'deveria haver concluintes');
    await v.passo('Concluintes e tempo até a conclusão: por semestre de conclusão, o tempo médio, mediano e máximo, em meses.');
    await v.destacar('table.lista-dados');
  },
};
