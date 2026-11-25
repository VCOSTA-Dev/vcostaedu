// Bloco 1 — Entrada e visão geral: login, painel inicial e menu lateral, Dados da instituição (só abrir: cabeçalho e logo).
const J = require('./lib');
J.rodar(1, 'Entrada e visão geral', async ({ page }) => {
  await J.ir(page, 'painel/login/acesso');
  await J.passo(page, 'Tela de entrada do painel.\nConfira: usuário e senha; o login é com o usuário de teste da jornada (administrador).');
  await J.digitar(page, 'input[name="usuario"]', J.C.usuario);
  await J.digitar(page, 'input[name="senha"]', J.C.senha);
  await J.clicarENavegar(page, '[name="SendLogin"]');
  J.ok(!(await page.$('[name="SendLogin"]')), 'login com o usuário de teste: entrou no painel (' + page.url().replace(J.C.base, '') + ')');

  await J.passo(page, 'Painel inicial.\nConfira: os atalhos da Página Inicial saem dos mesmos itens do menu do perfil; pode aparecer o aviso de novidades.');
  const atalhos = await page.$$eval('.atalho-rapido', a => a.length).catch(() => 0);
  J.ok(atalhos > 0, `página inicial com ${atalhos} atalho(s)`);
  const novidade = await page.$('#avisoNovidades, .aviso-novidades, [id*="ovidade"]');
  if (novidade) J.registrar('info', 'aviso de novidades presente na página inicial');

  await J.passo(page, 'Menu lateral.\nConfira os grupos: Configurações, Ensino, Educacional, Relatórios, Ajuda. Vou destacar cada grupo.');
  const grupos = await page.$$eval('aside .nav-sidebar > li.nav-item > a p', a => a.map(x => x.childNodes[0] ? x.childNodes[0].textContent.trim() : x.textContent.trim()));
  for (const g of ['Configurações', 'Ensino', 'Educacional', 'Relatórios', 'Ajuda']) {
    const l = page.locator('aside .nav-sidebar > li.nav-item > a', { hasText: g }).first();
    if (await l.count()) await J.destacar(page, l);
  }
  J.ok(['Configurações', 'Ensino', 'Educacional', 'Relatórios'].every(g => grupos.some(x => x.startsWith(g))), 'menu lateral: ' + grupos.join(' · '));

  await J.passo(page, 'Configurações → Dados da instituição.\nSó ABRIR: nada será alterado nem salvo.');
  const conf = page.locator('aside .nav-sidebar > li.nav-item > a', { hasText: 'Configurações' }).first();
  await J.clicar(page, conf);
  await page.waitForTimeout(500);
  const item = page.locator('aside a[href*="dados-instituicao/editar"]').first();
  if (await item.count()) await J.clicarENavegar(page, item); else await J.ir(page, 'painel/dados-instituicao/editar');
  J.ok(/dados-instituicao\/editar/.test(page.url()) && (await J.texto(page, 'h1')).includes('Dados da instituição'), 'tela "Dados da instituição" aberta');

  await J.passo(page, 'Cabeçalho dos documentos.\nConfira: a prévia repete o desenho do documento — logo à esquerda e as linhas do cabeçalho à direita.');
  await J.destacar(page, '#linhasCabecalho');
  await J.destacar(page, '#previaCabecalho');
  const linhas = await page.$$eval('#previaLinhas > *', a => a.length).catch(() => 0);
  J.ok(linhas > 0, `prévia do cabeçalho com ${linhas} linha(s)`);

  await J.passo(page, 'Logo da instituição.\nConfira: o logo atual e o campo "Trocar o logo" (PNG ou JPEG). Não vamos trocar nada.');
  const logo = page.locator('#previaCabecalho img').first();
  await J.destacar(page, logo);
  const carregou = await logo.evaluate(i => i.complete && i.naturalWidth > 0).catch(() => false);
  J.ok(carregou, 'logo carregado na prévia do cabeçalho');
  await J.destacar(page, '#logo');
  await J.ir(page, 'painel/home/index'); // sai sem salvar
  return 'Entrada, menu e Dados da instituição (só aberto, sem salvar).';
});
