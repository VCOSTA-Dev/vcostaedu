// Guia: Primeiros passos (administrador). Sair da demonstração ("Começar com os dados da minha instituição", em passos)
// e os Primeiros passos: as fases na ordem, a próxima em destaque, as que dependem de outra travadas, e o "Faça comigo"
// da primeira fase, no próprio motor do produto. Pede a demonstração recém-montada (sem dado próprio). O banco volta ao
// retrato.
module.exports = {
  conta: 'administrador',
  async gravar(v) {
    v.capitulo('Começar com os dados da instituição');
    await v.ir('painel/demonstracao/instituicao');
    await v.passo('Terminada a visita, a demonstração sai sem reinstalar: os dados fictícios são apagados e fica só a conta do administrador.', { t: 'Começar com os dados da minha instituição', aviso: 'Escreva **COMEÇAR** e clique em **Remover a demonstração**.' });
    await v.digitar('#confirmacao', 'COMEÇAR');
    await v.clicar('#removerDemonstracao');
    await v.page.waitForSelector('#progressoRemocao', { timeout: 20000 });
    await v.acelerar(() => v.page.waitForFunction(() => ['concluida', 'falhou'].includes(document.getElementById('progressoRemocao').getAttribute('data-situacao')), null, { timeout: 600000 }), 3000);
    v.confere((await v.page.getAttribute('#progressoRemocao', 'data-situacao')) === 'concluida', 'a remoção deveria terminar: ' + (await v.texto('#progressoRemocao')));
    await v.feito('A demonstração saiu, em passos.');
    await v.clicarENavegar('#progressoRemocao .vc-progresso-fim');

    v.capitulo('As fases');
    if (!/primeiros-passos/.test(v.page.url())) await v.ir('painel/primeiros-passos/index');
    await v.passo('Os Primeiros passos: as fases na ordem em que a instituição se monta. A próxima fica em destaque.', { t: 'Primeiros passos', aviso: 'Olhe as fases.' });
    await v.destacar('#listaFases');
    const travada = v.page.locator('.vc-fase[data-estado="bloqueada"], .vc-fase[data-estado="travada"]').first();
    if (await travada.count()) {
      await v.passo('A fase que depende de outra fica travada e diz qual falta, até a anterior ser feita.', { t: 'As fases travadas' });
      await v.destacar(travada);
    }
    await v.destacar('#progressoPrimeiros').catch(() => {});
    v.poster();

    v.capitulo('Faça comigo');
    await v.passo('Cada fase tem o "Faça comigo": uma janela diz o que fazer, destaca o campo e confere cada passo.', { t: 'Faça comigo', aviso: 'Clique em **Faça comigo** na primeira fase.' });
    await v.destacar('.vc-fase[data-fase="instituicao"] .vc-faca-comigo');
    await v.facaComigo('fase-instituicao');
    await v.ir('painel/primeiros-passos/index');
    await v.passo('A fase concluída é marcada sozinha, e a próxima passa a ser a da vez.', { t: 'Fase concluída' });
    await v.destacar('.vc-fase[data-fase="instituicao"]');
  },
};
