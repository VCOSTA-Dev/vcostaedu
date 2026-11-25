// Guia: Relatórios B — secretaria e cadastro. Alunos por curso e situação, a lista de turma (PDF para o diário), a previsão
// de conclusão (com a linha "sem previsão") e a qualidade do cadastro. A preparação dá previsões inventadas; o retrato desfaz.
const fs = require('fs');
const path = require('path');

async function baixar(v, sel) {
  const pasta = path.join(__dirname, '..', 'saida'); fs.mkdirSync(pasta, { recursive: true });
  const [d] = await Promise.all([v.page.waitForEvent('download', { timeout: 30000 }), v.clicar(sel)]);
  await d.saveAs(path.join(pasta, d.suggestedFilename()));
  return d.suggestedFilename();
}

module.exports = {
  conta: 'secretaria.demo',
  sql: ids => {
    const m = ids.matricula;
    return `UPDATE rm_matriculas_curso SET previsao_conclusao = '2027-02-26' WHERE id IN (${m['260101']}, ${m['260102']}, ${m['260103']}, ${m['260104']});
UPDATE rm_matriculas_curso SET previsao_conclusao = '2027-03-31' WHERE id IN (${m['260105']}, ${m['260106']});
UPDATE rm_matriculas_curso SET previsao_conclusao = '2026-12-18' WHERE id = ${m['250102']};`;
  },
  async gravar(v) {
    const curso = String(v.ids.curso);
    const filtrar = () => v.clicarENavegar('#filtrosRelatorio button[type="submit"]');

    v.capitulo('Alunos por curso e situação');
    await v.ir('painel/relatorios/alunos-situacao');
    await v.passo('Alunos por curso e situação: uma linha por matrícula. Filtre por curso, grade, situação, polo ou ingresso.');
    await v.escolher('#f_curso', curso);
    await filtrar();
    const linhas = await v.page.locator('table.lista-dados tbody tr').count();
    v.confere(linhas >= 8, 'o curso deveria listar as matrículas: ' + linhas);
    await v.passo('Os cartões contam cada situação. O nome do aluno abre o desempenho dele.');
    await v.destacar('table.lista-dados tbody tr:first-child td:nth-child(2) a');

    v.capitulo('Lista de turma');
    await v.ir('painel/relatorios/lista-turma');
    await v.passo('Lista de turma: escolha a oferta e clique em Filtrar.');
    await v.escolher('#f_oferta', String(v.ids.oferta.GOC));
    await filtrar();
    const ths = await v.page.$$eval('table.lista-dados thead th', t => t.map(x => x.textContent.trim()));
    v.confere(ths.includes('Assinatura'), 'a lista de turma deveria ter a coluna Assinatura: ' + ths.join(', '));
    v.poster();
    await v.passo('Os enturmados da oferta, numerados. A coluna Assinatura vai em branco, para o diário de papel.');
    await v.destacar('table.lista-dados thead th:has-text("Assinatura")');
    await v.passo('Use PDF ou Imprimir para tirar o diário. O PDF é baixado (e a exportação fica registrada).');
    const arq = await baixar(v, 'button.buttons-pdf');
    v.confere(/\.pdf$/i.test(arq), 'o PDF deveria ser baixado: ' + arq);

    v.capitulo('Previsão de conclusão');
    await v.ir('painel/relatorios/previsao-conclusao');
    await v.passo('Previsão de conclusão: as matrículas em Cursando por mês da previsão, em gráfico e em tabela.');
    v.confere(await v.page.$('#graficoRelatorio'), 'o gráfico deveria aparecer');
    const sem = v.page.locator('table.lista-dados tbody tr', { hasText: 'sem previsão' }).first();
    v.confere(await sem.count(), 'a linha "sem previsão" deveria aparecer');
    await v.rolar(sem);
    await v.passo('A linha "sem previsão" mostra quem ainda não tem a data. Clique no número para ver quem é.');
    await v.clicarENavegar(sem.locator('a').first());
    v.confere(/detalhe=mes_sem/.test(v.page.url()), 'a lista "sem previsão" deveria abrir');
    await v.pausa(1500);

    v.capitulo('Qualidade do cadastro');
    await v.ir('painel/relatorios/qualidade-cadastro');
    await v.passo('Qualidade do cadastro: o que falta em cada matrícula para emitir documentos.');
    await v.escolher('#f_curso', curso);
    await filtrar();
    const porTipo = v.page.locator('.card:has(.card-title:has-text("Por tipo de falta"))');
    v.confere(await porTipo.count(), 'a tabela "Por tipo de falta" deveria aparecer');
    await v.rolar(porTipo);
    await v.passo('A contagem por tipo: sexo, CPF, nascimento, naturalidade, RG, filiação, previsão, enturmação…');
    await v.destacar(porTipo);
    const editar = v.page.locator('.card:has(.card-title:has-text("Matrículas com falta")) a:has-text("Editar")').first();
    if (await editar.count()) {
      await v.rolar(editar);
      await v.passo('Em "Matrículas com falta", o que falta a cada uma; "Editar" abre a ficha do aluno para completar.');
      await v.destacar(editar);
    } else {
      await v.passo('Nenhuma matrícula com falta: o cadastro deste curso está completo.');
    }
  },
};
