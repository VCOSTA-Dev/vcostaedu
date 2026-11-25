// Guia: Relatórios — visão geral (administrador). Todos os relatórios, filtros no link, "Como é calculado", a lista de um
// número, Colunas, exportar (registrada em Exportações), Salvar com um perfil e o Alerta de risco. O banco volta ao retrato.
const fs = require('fs');
const path = require('path');
const NOME = 'Risco do curso (vídeo)';

async function baixar(v, sel) {
  const pasta = path.join(__dirname, '..', 'saida'); fs.mkdirSync(pasta, { recursive: true });
  const [d] = await Promise.all([v.page.waitForEvent('download', { timeout: 30000 }), v.clicar(sel)]);
  await d.saveAs(path.join(pasta, d.suggestedFilename()));
  return d.suggestedFilename();
}

module.exports = {
  conta: 'administrador',
  async gravar(v) {
    const curso = String(v.ids.curso);
    v.capitulo('Todos os relatórios');
    await v.ir('painel/relatorios/listar');
    const cats = await v.page.$$eval('.page-wrapper .card-header .card-title strong', s => s.map(x => x.textContent.trim()));
    v.confere(cats.join('') === 'A.B.C.D.E.F.G.H.', 'o administrador deveria ver as categorias A a H: ' + cats.join(' '));
    await v.passo('Relatórios → Todos os relatórios: por categoria, de A (Painel gerencial) a H (Auditoria). Cada um responde a uma pergunta.');
    await v.destacar('.page-wrapper .card:has(a[href$="relatorios/alunos-risco"])');
    await v.passo('Cada perfil vê só os relatórios a que tem acesso. Vamos abrir "Alunos em risco".');
    await v.clicarENavegar('.page-wrapper a[href$="relatorios/alunos-risco"]');

    v.capitulo('Filtros no endereço');
    await v.passo('A pergunta fica no topo. Escolha os filtros — aqui, o curso — e clique em Filtrar.');
    await v.escolher('#f_curso', curso);
    await v.clicarENavegar('#filtrosRelatorio button[type="submit"]');
    v.confere(new RegExp('[?&]curso=' + curso).test(v.page.url()), 'o filtro deveria ir para o endereço: ' + v.page.url());
    v.poster();
    await v.passo('Os filtros ficam no endereço: copie o link e quem abrir verá o mesmo relatório (com o que o perfil dele permite).');

    v.capitulo('Como é calculado');
    await v.clicar('button[data-bs-target="#modalFicha"]');
    await v.page.waitForSelector('#modalFicha.show', { timeout: 10000 });
    v.confere(await v.page.locator('#modalFicha li').count() > 0, 'a ficha deveria listar as fórmulas');
    await v.passo('"Como é calculado": a fórmula de cada número, o que entra e o que fica de fora.');
    await v.destacar('#modalFicha .modal-body ul');
    await v.clicar('#modalFicha .btn-close');
    await v.pausa(600);

    v.capitulo('A lista de cada número');
    const rodape = v.page.locator('a.card-footer[href*="detalhe=todos"]');
    v.confere(await rodape.count(), 'o cartão "Matrículas em risco" deveria ter a lista');
    const numero = (await v.texto('.vc-cartao-rel:has(a[href*="detalhe=todos"]) .h1')).trim();
    await v.passo('Os números são clicáveis: clique no número, ou em "Ver a lista", para ver quem compõe o número.');
    await v.clicarENavegar(rodape);
    v.confere(/detalhe=todos/.test(v.page.url()), 'a lista do cartão deveria abrir');
    const n = (await v.texto('.card-header .badge')).trim();
    v.confere(n === numero, `a lista (${n}) deveria ter o tamanho do número (${numero})`);
    await v.passo(`A lista tem sempre o mesmo tamanho do número: ${numero} matrícula(s) em risco, com os sinais de cada uma.`);
    await v.clicarENavegar('a:has-text("Voltar ao relatório")');

    v.capitulo('Colunas');
    await v.passo('O botão Colunas esconde ou mostra colunas. A exportação leva só as visíveis.');
    await v.clicar('button.buttons-colvis');
    await v.page.waitForSelector('.dt-button-collection', { timeout: 10000 });
    await v.clicar('.dt-button-collection .buttons-columnVisibility:has-text("Polo")');
    await v.pausa(500);
    await v.page.keyboard.press('Escape');
    await v.pausa(400);
    v.confere(!(await v.page.$$eval('table.lista-dados thead th', ths => ths.map(t => t.textContent.trim()))).includes('Polo'), 'a coluna Polo deveria sumir');

    v.capitulo('Exportar');
    await v.passo('Exportar: Excel, CSV, PDF ou Imprimir. O PDF, a impressão e o Excel trazem no topo a data, a hora e os filtros.');
    await v.destacar('.dt-buttons');
    const arq = await baixar(v, 'button.buttons-excel');
    v.confere(/\.xlsx$/.test(arq), 'o Excel deveria ser baixado: ' + arq);
    await v.passo('Antes de gerar o arquivo, o sistema registra a exportação. Sem o registro, o arquivo não sai.');
    await v.ir('painel/relatorios/exportacoes');
    const linha = v.page.locator('table.lista-dados tbody tr', { hasText: 'R-EVA-01' }).first();
    v.confere(await linha.count(), 'a exportação deveria aparecer em Exportações');
    await v.passo('Em Relatórios → Exportações: quem, quando, o relatório, os filtros e quantas linhas.');
    await v.destacar(linha);

    v.capitulo('Salvar');
    await v.ir('painel/relatorios/alunos-risco?curso=' + curso);
    await v.passo('Salvar guarda os filtros e as colunas com um nome.');
    await v.clicar('#btnSalvarRelatorio');
    await v.page.waitForSelector('#modalSalvar.show', { timeout: 10000 });
    await v.digitar('#salvarNome', NOME);
    await v.passo('Escolha um perfil para compartilhar: quem é do perfil abre, mas não altera nem apaga.');
    await v.escolher('#salvarPerfil', '4');
    await v.clicarENavegar('#modalSalvar button[type="submit"]');
    v.confere(/salvo/i.test(await v.alerta()) && /salvo=\d+/.test(v.page.url()), 'o relatório deveria ser salvo: ' + await v.alerta());
    await v.ir('painel/relatorios/listar');
    const salvo = v.page.locator('table.lista-dados tbody tr', { hasText: NOME }).first();
    v.confere(await salvo.count(), 'o salvo deveria aparecer em Todos os relatórios');
    await v.rolar(salvo);
    await v.passo('Os salvos aparecem em Todos os relatórios (e no botão Salvos de cada relatório), com o perfil compartilhado.');
    await v.destacar(salvo);

    v.capitulo('Alerta de risco');
    await v.rolar(0 - 3000);
    await v.clicarENavegar('.page-wrapper a[href$="relatorios/parametros"]');
    v.confere(await v.page.$('#risco_faltas_margem'), 'a tela do Alerta de risco deveria abrir');
    await v.passo('Alerta de risco (administrador): os limites dos sinais do relatório Alunos em risco.');
    await v.destacar('#risco_faltas_margem');
  },
};
