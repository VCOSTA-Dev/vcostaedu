// Guia: Como usar os vídeos da Ajuda (todos os perfis; aqui, a secretaria). Mostra o player no topo de um guia (legenda,
// capítulo, avanço), a transcrição e a página Vídeos com a busca. Grave DEPOIS dos outros vídeos (o índice precisa deles).
module.exports = {
  conta: 'secretaria.demo',
  async gravar(v) {
    v.capitulo('A página Vídeos');
    await v.ir('painel/ajuda/index');
    await v.passo('Na Central de ajuda, o botão "Vídeos" abre todos os vídeos do seu perfil.');
    await v.clicarENavegar('#linkVideos');
    const n = await v.page.$$eval('#gradeVideos .item-video', x => x.length);
    v.confere(n > 3, 'a página Vídeos deveria listar os vídeos da secretaria');
    await v.passo(`Cada vídeo com o poster e a duração. Busque por uma palavra do título ou do que é dito no vídeo.`);
    await v.digitar('#buscaVideos', 'link de acesso');
    const vis = await v.page.$$eval('#gradeVideos .item-video', x => x.filter(e => !e.hidden).length);
    v.confere(vis >= 1 && vis < n, 'a busca deveria filtrar');
    await v.passo('A busca encontra o guia "Cadastrar aluno e enviar o link". Clique para abrir o guia com o vídeo.');
    await v.clicarENavegar('#gradeVideos .item-video:not([hidden]) a');

    v.capitulo('O player no guia');
    v.confere(await v.page.$('#videoGuia'), 'o guia deveria mostrar o player no topo');
    await v.passo('O vídeo fica no topo do guia, antes do texto. Ele só carrega quando você manda tocar.');
    await v.clicar('#playerGuia');
    await v.page.$eval('#playerGuia', el => el.play()).catch(() => {});
    await v.page.waitForFunction(() => document.getElementById('playerGuia').currentTime > 1, null, { timeout: 20000 });
    v.poster();
    await v.passo('A legenda já vem ligada: é a narração de cada passo. Velocidade e tela cheia ficam nos controles do vídeo.');
    await v.pausa(2500);

    v.capitulo('Capítulos e transcrição');
    await v.passo('Ao lado, os capítulos: clique em um para pular direto para aquele trecho. O capítulo atual fica marcado.');
    const caps = v.page.locator('#capitulosGuia .capitulo');
    await v.clicar(caps.nth(Math.max(0, (await caps.count()) - 1)));
    await v.pausa(2500);
    v.confere(await v.page.$('#capitulosGuia .capitulo.atual'), 'o capítulo atual deveria ficar marcado');
    await v.page.$eval('#playerGuia', el => el.pause());
    await v.passo('Abaixo do vídeo, "Ler a transcrição" mostra todo o texto narrado.');
    await v.clicar('#transcricaoGuia summary');
    await v.rolar('#transcricaoGuia');
    await v.passo('Guia sem vídeo no servidor? O texto continua valendo — o player simplesmente não aparece.');
  },
};
