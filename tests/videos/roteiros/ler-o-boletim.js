// Guia: Ler o boletim (aluno). A faixa do aluno, como ler média parcial e final, faltas e compensadas, frequência e
// resultado, o filtro de curso e ano, a impressão (sem o menu) e o "Como foi calculado". Aluna inventada da demonstração.
const RA = '260105';
module.exports = {
  conta: RA,
  async gravar(v) {
    v.capitulo('Abrir o boletim');
    await v.ir('portal/home/index');
    await v.passo('No menu, Acadêmico → Boletim.');
    await v.menu("home/boletim-aluno");
    v.confere(await v.page.$('table.tabela-boletim tr.linhaBoletim'), 'o boletim deveria listar as disciplinas');
    /* 1.18.1 (D-485): o menu marca o item da página — aqui, Boletim (antes, a Página Inicial) */
    const ativos = await v.page.$$eval('#railMenu a.nav-link.active', as => as.map(a => a.innerText.trim()));
    v.confere(ativos.length === 1 && ativos[0] === 'Boletim', 'o menu deveria marcar só "Boletim": ' + ativos.join(' + '));
    await v.destacar('#railMenu a.nav-link.active');
    await v.passo('No topo, uma faixa com o seu nome, RA, curso e situação; abaixo, as disciplinas do curso. No menu, "Boletim" fica marcado.');
    await v.destacar('.faixa-aluno');

    v.capitulo('Como ler');
    const linhas = v.page.locator('table.tabela-boletim tr.linhaBoletim');
    const parcial = linhas.filter({ has: v.page.locator('.etiqueta-parcial') }).first();
    if (await parcial.count()) {
      await v.passo('Média com a etiqueta "parcial": a disciplina está aberta; é a média só das atividades com nota e pode mudar.');
      await v.destacar(parcial.locator('td.media'));
    } else {
      await v.passo('Disciplina aberta com "—" na média: a instituição mostra a média só no encerramento.');
      await v.destacar(linhas.filter({ hasText: 'Em curso' }).first().locator('td.media'));
    }
    const final = linhas.filter({ hasNot: v.page.locator('.etiqueta-parcial') }).filter({ has: v.page.locator('.badge.bg-success-lt, .badge.bg-danger-lt') }).first();
    v.confere(await final.count(), 'deveria haver uma disciplina encerrada');
    await v.passo('Média sem etiqueta: a disciplina foi encerrada; é a média final.');
    await v.destacar(final.locator('td.media'));
    const iComp = await linhas.evaluateAll(trs => trs.findIndex(tr => Number(tr.querySelector('td.compensadas').textContent) > 0));
    v.confere(iComp >= 0, 'a aluna deveria ter falta compensada');
    const comp = linhas.nth(iComp);
    await v.passo('Faltas e Comp. (faltas compensadas, como atestados): as compensadas não contam contra você.');
    await v.destacar(comp.locator('td.faltas'));
    await v.destacar(comp.locator('td.compensadas'));
    v.poster();
    await v.passo('Frequência = 1 − (faltas − compensadas) ÷ total de encontros. O que está em vermelho está abaixo do mínimo.');
    await v.destacar(comp.locator('td.frequencia'));
    await v.passo('Resultado: Em curso, Aprovado ou Reprovado (por nota, por frequência ou pelos dois).');
    await v.destacar('table.tabela-boletim thead');
    for (const r of await linhas.locator('td.resultado').all()) { await r.evaluate(el => { el.style.outline = '3px solid #ff1744'; }); }
    await v.pausa(1800);
    for (const r of await linhas.locator('td.resultado').all()) { await r.evaluate(el => { el.style.outline = ''; }); }

    v.capitulo('Curso, ano e imprimir');
    await v.rolar('#seletorBoletim');
    await v.passo('Para ver só um ano ou um curso, use os seletores Curso e Ano, no topo.');
    const anos = await v.page.$$eval('#filtroAno option', o => o.map(x => x.value).filter(x => x !== '0'));
    v.confere(anos.length, 'o seletor de ano deveria ter opções');
    await Promise.all([v.page.waitForNavigation({ waitUntil: 'load' }), v.escolher('#filtroAno', anos[anos.length - 1])]);
    await v.page.waitForLoadState('networkidle').catch(() => {});
    await v.destacar('#seletorBoletim');
    await v.passo('"Imprimir" tira o menu e imprime só o boletim. Veja como fica a página impressa:');
    await v.destacar('#imprimirBoletim');
    await v.page.emulateMedia({ media: 'print' });
    await v.pausa(3500);
    await v.page.emulateMedia({ media: 'screen' });

    v.capitulo('Como foi calculado');
    const como = v.page.locator('#comoFoiCalculado summary').first();
    v.confere(await como.count(), 'o boletim deveria ter o "Como foi calculado"');
    await v.rolar(como);
    await v.passo('"Como foi calculado", embaixo do curso: clique para abrir a conta de cada disciplina.');
    await v.clicar(como);
    await v.pausa(700);
    await v.rolar('#comoFoiCalculado');
    await v.passo('As notas de cada componente, os pesos, o arredondamento e a frequência, conforme a regra de média.');
    await v.destacar('#comoFoiCalculado');
    await v.passo('Nota ou falta errada? Fale com o professor ou a secretaria.');
  },
};
