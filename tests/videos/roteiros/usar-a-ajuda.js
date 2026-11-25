// Guia: Como usar a ajuda e as novidades (visão geral, todos os perfis; aqui, a secretaria). O aviso da página inicial,
// a Central de ajuda, o "Comece por aqui", o filtro, o ícone ? de cada tela, os Vídeos, as Novidades e a recusa de guia.
const grupo = (v, nome) => v.page.locator('#railMenu button.vc-side-grupo-toggle', { hasText: nome }).first();
module.exports = {
  conta: 'secretaria.demo',
  async gravar(v) {
    v.capitulo('O aviso da página inicial');
    await v.ir('painel/home/index');
    v.confere(await v.page.$('#avisoNovidades'), 'a página inicial deveria mostrar o aviso');
    await v.passo('No primeiro acesso, a página inicial mostra o aviso de boas-vindas, com o link para o "Comece por aqui".');
    await v.destacar('#avisoNovidades');
    await v.passo('Ele fica aqui, toda vez que a página abre, até você clicar em Entendi (ou no ×). Vamos deixá-lo para depois.');

    v.capitulo('A Central de ajuda');
    await v.passo('No menu da esquerda, abra o grupo Ajuda e clique em Central de ajuda.');
    await v.menu('ajuda/index/');
    v.confere(await v.page.$('#comecePorAqui'), 'a Central deveria mostrar o "Comece por aqui"');
    await v.passo('No topo, "Comece por aqui": o caminho do seu perfil, na ordem em que as coisas acontecem no ano.');
    await v.destacar('#comecePorAqui');
    v.poster();
    await v.clicarENavegar(v.page.locator('#comecePorAqui a.guia-link').nth(1));
    v.confere(await v.page.$('#conteudoGuia'), 'o guia deveria abrir');
    await v.passo('Cada guia: para que serve, antes de começar, o passo a passo e o que pode dar errado.');
    if (await v.page.$('#videoGuia')) {
      await v.passo('No topo do guia, o vídeo da tarefa, com legenda e capítulos.');
      await v.destacar('#videoGuia');
    }
    const proximo = v.page.locator('a.btn-primary', { hasText: 'Próximo passo' }).first();
    v.confere(await proximo.count(), 'o guia do "Comece por aqui" deveria ter o botão Próximo passo');
    await v.rolar(proximo);
    await v.passo('No fim de cada guia, o botão "Próximo passo" leva ao seguinte do caminho.');
    await v.destacar(proximo);
    await v.clicarENavegar('a.btn:has-text("Voltar ao índice")');

    v.capitulo('Achar um guia');
    await v.rolar('#filtroGuias');
    await v.passo('Abaixo, todos os guias do seu perfil. Digite parte do título no campo Filtrar.');
    await v.digitar('#filtroGuias', 'boletim');
    await v.pausa(800);
    const vis = await v.page.$$eval('.guia-item', a => a.filter(x => !x.hidden).length);
    v.confere(vis > 0 && vis < await v.page.$$eval('.guia-item', a => a.length), 'o filtro deveria reduzir a lista');
    await v.destacar('#listaGuias');

    v.capitulo('O ícone ? de cada tela');
    await v.passo('Em várias telas, o ícone ? no alto da página abre direto o guia daquela tela. Ex.: a lista de alunos.');
    await v.ir('painel/academicos/listar');
    await v.clicarENavegar('#ajudaDaTela');
    v.confere(/ajuda\/guia\//.test(v.page.url()), 'o ? deveria abrir o guia da tela');
    await v.passo('Abriu o guia "' + await v.texto('.page-title') + '".');
    await v.destacar('.page-title');

    v.capitulo('Vídeos e Novidades');
    await v.clicarENavegar('a.btn:has-text("Voltar ao índice")');
    if (await v.page.$('#linkVideos')) {
      await v.passo('Ajuda → Vídeos (no menu, ou o botão Vídeos na Central) reúne os vídeos dos guias do seu perfil.');
      const menuVideos = v.page.locator('#railMenu a[href*="ajuda/videos"]').first();
      if (await menuVideos.isVisible()) await v.destacar(menuVideos);
      await v.clicarENavegar('#linkVideos');
      v.confere(/Vídeos/.test(await v.texto('.page-title')), 'a página Vídeos deveria abrir');
      await v.passo('Clique num vídeo para abrir o guia com ele; a busca procura no título e no que é dito no vídeo.');
      await v.destacar((await v.page.$('#gradeVideos')) ? '#gradeVideos' : '#semVideos');
      await v.menu('ajuda/index');
    } else v.confere(false, 'a Central deveria ter o botão Vídeos (#linkVideos)');
    await v.passo('Novidades (o botão aqui ou Ajuda → Novidades): o que mudou em cada versão, com o link para o guia.');
    await v.clicarENavegar('#linkNovidades');
    v.confere(/novidades\/listar/.test(v.page.url()), 'a tela Novidades deveria abrir');
    await v.rolar(200);
    await v.pausa(1200);

    v.capitulo('Guia de outro perfil');
    await v.passo('Um link para um guia de outro perfil (ex.: o do e-mail SMTP, que é do administrador)…');
    await v.ir('painel/ajuda/guia/email-smtp');
    const msg = await v.alerta();
    v.confere(/não é do seu perfil/.test(msg), 'o guia de outro perfil deveria ser recusado: ' + msg);
    await v.passo('…é recusado: "Este guia não é do seu perfil (ou não existe)." A Central mostra os que você pode abrir.');
    await v.destacar(v.page.locator('.alert', { hasText: 'não é do seu perfil' }).first());

    v.capitulo('Marcar o aviso como lido');
    await v.ir('painel/home/index');
    await v.passo('De volta à página inicial: o aviso continua lá. Clique em Entendi.');
    await v.clicarENavegar('#botaoEntendi');
    v.confere(!(await v.page.$('#avisoNovidades')), 'depois do Entendi, o aviso não volta');
    await v.passo('Pronto: o aviso não volta mais. Sair e entrar de novo não conta como lido; só o Entendi (ou o ×).');
  },
};
