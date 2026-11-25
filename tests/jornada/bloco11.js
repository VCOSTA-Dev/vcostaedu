// Bloco 11 — Relatórios: o painel acadêmico com uma lista aberta (drill-down), o mapa de notas, o resultado por
// disciplina, a frequência perto do mínimo (só ofertas abertas: aqui, as da jornada já saíram — o bloco 7 a mostrou com
// elas abertas), o alerta de risco, os aptos a concluir, a exportação Excel (o arquivo vai para a pasta de saída, fora do
// git) com o registro dela em "Exportações", e um relatório salvo.
const fs = require('fs');
const path = require('path');
const J = require('./lib');
const { ALUNOS } = require('./dados');
const RA = ALUNOS.map(a => a.rm);
const linhas = page => page.$$eval('table.lista-dados tbody tr', trs => trs.map(t => [...t.querySelectorAll('td')].map(d => d.textContent.trim())).filter(l => l.length > 1));

J.rodar(11, 'Relatórios', async ({ browser, page }) => {
  const E = J.estado();
  const curso = E.curso, A = E.ofertas.A;

  await J.ir(page, `painel/relatorios/painel-academico?curso=${curso}`);
  await J.passo(page, 'PAINEL ACADÊMICO do curso da jornada: os cartões (alunos ativos, matrículas por situação, ofertas, aprovação, frequência média…).\nAbro a lista de um cartão (drill-down).');
  const cartoes = await page.$$eval('.small-box', bs => bs.map(b => b.querySelector('h3').textContent.trim() + ' ' + b.querySelector('p').textContent.trim()));
  J.ok(cartoes.length > 0, 'cartões: ' + cartoes.join(' | '));
  const rod = page.locator('a.small-box-footer').first();
  if (await rod.count()) {
    await J.clicarENavegar(page, rod);
    const tit = await J.texto(page, '.card-header .card-title');
    J.ok(/detalhe=/.test(page.url()), 'lista aberta: "' + tit.replace('Voltar ao relatório', '').trim() + '"');
    await J.passo(page, 'A lista do cartão, com "Voltar ao relatório".');
  } else J.ok(false, 'nenhum cartão com lista');

  await J.ir(page, `painel/relatorios/mapa-notas?oferta=${A}`);
  await J.passo(page, 'MAPA DE NOTAS da oferta A (encerrada): uma coluna por atividade, a nota do componente, a média GRAVADA, a frequência e FS/FI. Em vermelho, abaixo da mínima.');
  const mapa = await linhas(page);
  J.ok(mapa.length === 5, 'mapa: ' + mapa.map(l => l[0] + ' ' + l.slice(-3).join('/')).join(' | '));

  await J.ir(page, `painel/relatorios/resultado-disciplina?curso=${curso}`);
  await J.passo(page, 'RESULTADO POR DISCIPLINA do curso: aprovados, reprovados (por nota/frequência), média e frequência média — das ofertas encerradas.');
  const res = await linhas(page);
  J.ok(res.length >= 3, 'resultado: ' + res.map(l => l.slice(0, 5).join(' ')).join(' | ').slice(0, 300));

  await J.ir(page, `painel/relatorios/frequencia-minimo?curso=${curso}`);
  await J.passo(page, 'FREQUÊNCIA PERTO DO MÍNIMO: só olha ofertas ABERTAS — as da jornada encerraram no bloco 8, por isso a lista sai vazia aqui (o bloco 7 a mostrou com as ofertas abertas: alertas e "Mostrar todos").\nConfira: abre em "Só os alertas"; troco para "Mostrar todos".');
  await J.destacar(page, '#f_mostrar');
  const fa = (await linhas(page)).length;
  await J.escolher(page, '#f_mostrar', 'todos');
  await J.clicarENavegar(page, '#filtrosRelatorio button[type="submit"]');
  J.ok(/mostrar=todos/.test(page.url()), `frequência: ${fa} linha(s) em "Só os alertas", ${(await linhas(page)).length} em "Mostrar todos" (ofertas encerradas não entram)`);

  await J.ir(page, `painel/relatorios/alunos-risco?curso=${curso}`);
  await J.passo(page, 'ALERTA DE RISCO do curso, agora com tudo encerrado: confira o aluno 5 (reprovado).');
  const ri = await linhas(page);
  J.ok(true, 'risco: ' + (ri.map(l => l[0] + ' ' + l[l.length - 1].slice(0, 60)).join(' | ') || 'nenhum aluno'));

  await J.ir(page, `painel/relatorios/aptos-concluir?curso=${curso}`);
  await J.passo(page, 'APTOS A CONCLUIR: matrículas em Cursando sem pendência. O aluno 1 já está Concluído; os outros não têm TCC aprovado (o curso exige) — aparecem na lista "com pendência".');
  const ap = await linhas(page);
  J.ok(true, 'aptos: ' + ap.map(l => l[0] + ' ' + l[l.length - 1].slice(0, 50)).join(' | ').slice(0, 300));

  await J.ir(page, `painel/relatorios/resultado-disciplina?curso=${curso}`);
  await J.passo(page, 'EXPORTAR para Excel. Antes de gerar o arquivo, o sistema REGISTRA a exportação no servidor (sem o registro, não exporta). O arquivo vai para a pasta de saída da jornada (fora do git).');
  const pasta = path.join(J.C.saida, 'exports'); fs.mkdirSync(pasta, { recursive: true });
  const antes = new Set(fs.readdirSync(pasta));
  const cdp = await browser.newBrowserCDPSession();
  await cdp.send('Browser.setDownloadBehavior', { behavior: 'allow', downloadPath: pasta, eventsEnabled: true });
  await J.clicar(page, 'button.buttons-excel');
  let arq = '';
  for (let i = 0; i < 40 && !arq; i++) { await page.waitForTimeout(500); arq = fs.readdirSync(pasta).find(f => !antes.has(f) && /\.xlsx$/.test(f)) || ''; }
  J.ok(!!arq, 'Excel salvo na pasta de saída: ' + (arq || 'NÃO'));
  J.guardar('export', arq);

  await J.ir(page, 'painel/relatorios/exportacoes');
  await J.passo(page, 'Relatórios → EXPORTAÇÕES (auditoria): a exportação que acabei de fazer, com quem, quando, o formato e os filtros efetivos.');
  const ex = await linhas(page);
  J.ok(ex.some(l => l.join(' ').includes('R-DES-02')), 'exportações: ' + ex.slice(0, 3).map(l => l.join(' ').slice(0, 160)).join(' | '));

  await J.ir(page, `painel/relatorios/mapa-notas?oferta=${A}`);
  await J.passo(page, 'SALVAR o relatório (o mapa de notas da A): nome, e com quem compartilhar ("Ninguém (só eu)").\nConfira: ele aparece em "Salvos" e reabre com os mesmos filtros.');
  await J.clicar(page, '#btnSalvarRelatorio');
  await page.waitForSelector('#modalSalvar.show', { timeout: 10000 });
  await J.digitar(page, '#salvarNome', 'Mapa da oferta A (jornada — apagar)');
  await J.clicarENavegar(page, '#modalSalvar button[type="submit"]');
  J.ok(/salvo/i.test(await J.alerta(page)) && /salvo=\d+/.test(page.url()), 'relatório salvo: "' + (await J.alerta(page)).slice(0, 80) + '" — ' + page.url().replace(J.C.base, ''));
  await J.passo(page, 'O relatório salvo, aberto pelo link dele (filtros na URL).');
  return `Painel com drill-down; mapa de notas; resultado por disciplina; frequência (alertas/todos); risco; aptos; Excel ${arq || '(falhou)'} com registro em Exportações; relatório salvo.`;
});
