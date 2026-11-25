// Guia: Pesquisar, exportar e imprimir listas (administrador). Na lista de matérias: pesquisar, ordenar, exportar o que está
// filtrado (CSV sem a coluna Ações), mostrar todos, Excel e a dica do botão apagado. Só lê: nada muda no banco.
const fs = require('fs');
const path = require('path');

async function baixar(v, sel) {
  const pasta = path.join(__dirname, '..', 'saida'); fs.mkdirSync(pasta, { recursive: true });
  const [d] = await Promise.all([v.page.waitForEvent('download', { timeout: 30000 }), v.clicar(sel)]);
  const arq = path.join(pasta, d.suggestedFilename());
  await d.saveAs(arq);
  return arq;
}

module.exports = {
  conta: 'administrador',
  async gravar(v) {
    const linhas = () => v.page.locator('#tabelaMaterias tbody tr').count();

    v.capitulo('Pesquisar');
    await v.ir('painel/materias/listar');
    const total = await linhas();
    await v.passo('Todas as listas do painel funcionam do mesmo jeito. Aqui, a lista de matérias.');
    await v.passo('Pesquisar: digite no campo à direita. A lista filtra enquanto você digita.');
    await v.digitar('#tabelaMaterias_filter input', 'Gestão');
    await v.pausa(600);
    const filtradas = await linhas();
    v.confere(filtradas > 1 && filtradas < total, `a pesquisa deveria filtrar a lista (${filtradas} de ${total})`);
    await v.destacar('#tabelaMaterias_info');

    v.capitulo('Ordenar');
    await v.passo('Ordenar: clique no título da coluna; clique de novo para inverter.');
    const th = '#tabelaMaterias thead th:has-text("Carga horária")';
    await v.clicar(th);
    await v.pausa(700);
    await v.clicar(th);
    await v.pausa(700);
    v.confere(await v.page.$eval(th, t => t.getAttribute('aria-sort')) === 'descending', 'a coluna deveria estar em ordem decrescente');

    v.capitulo('Exportar');
    await v.passo('Exportar: Excel, CSV (separado por ponto e vírgula, abre direto no Excel), PDF e Imprimir, no topo da lista.');
    await v.destacar('.dt-buttons');
    await v.passo('A exportação leva o que está filtrado na tela e deixa de fora a coluna Ações.');
    const csv = fs.readFileSync(await baixar(v, 'button.buttons-csv'), 'utf8').replace(/^﻿/, '').trim().split(/\r?\n/);
    v.confere(csv.length - 1 === filtradas && !/Ações/.test(csv[0]) && /;/.test(csv[0]), `o CSV deveria ter as ${filtradas} linhas filtradas, sem Ações: ${csv[0]} (${csv.length - 1})`);
    v.poster();
    await v.passo(`O CSV saiu com as ${filtradas} matérias filtradas. Lista curta? Há texto no Pesquisar: limpe e exporte de novo.`);
    await v.digitar('#tabelaMaterias_filter input', '');
    await v.page.locator('#tabelaMaterias_filter input').press('Backspace');
    await v.pausa(500);
    v.confere(await linhas() === Math.min(total, 25), 'sem a pesquisa, a lista volta inteira');

    v.capitulo('Mostrar');
    await v.passo('Mostrar: no pé da lista, escolha 10, 25, 50, 100 ou Todos por página.');
    await v.escolher('select[name="tabelaMaterias_length"]', '-1');
    await v.pausa(500);
    await v.passo('Agora o Excel leva a lista inteira. O PDF sai em paisagem quando a lista tem muitas colunas.');
    const xlsx = await baixar(v, 'button.buttons-excel');
    v.confere(/\.xlsx$/.test(xlsx), 'o Excel deveria ser baixado: ' + xlsx);

    v.capitulo('Ações');
    const apagado = v.page.locator('#tabelaMaterias .acao-desabilitada').first();
    v.confere(await apagado.count(), 'uma matéria em uso deveria ter o Apagar desabilitado');
    await v.passo('Ações: os botões de cada linha (ver, editar, apagar…). Passe o mouse para ver a dica.');
    await v.rolar(apagado);
    await v.destacar(apagado);
    await apagado.hover();
    await v.pausa(800);
    /* a dica é o title do botão (dica nativa do navegador, que a gravação de tela não captura): o texto vai na legenda */
    const dica = ((await apagado.getAttribute('data-original-title')) || (await apagado.evaluate(e => e.getAttribute('title') || e.getAttribute('data-bs-original-title'))) || '').trim();
    v.confere(/Em uso.*Não pode ser apagad/.test(dica), 'a dica do botão apagado deveria dizer por quê: ' + dica);
    await v.passo(`Um botão apagado diz na dica por que não pode ser usado: "${dica.length > 80 ? dica.slice(0, 77) + '…' : dica}"`);
    await v.pausa(1500);
  },
};
