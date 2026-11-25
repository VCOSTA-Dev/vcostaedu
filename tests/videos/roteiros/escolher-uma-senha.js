// Guia: Escolher uma senha (todos os perfis; aqui, a secretaria, em Meu perfil). O medidor de força, a senha curta
// recusada antes de enviar, a frase longa, o botão Mostrar e o salvar. O botão Mostrar só é apontado (nenhuma senha
// aparece no vídeo). O banco volta ao retrato no fim.
module.exports = {
  conta: 'secretaria.demo',
  async gravar(v) {
    v.capitulo('A força da senha');
    await v.ir('painel/perfil/visualizar');
    await v.passo('Em Meu perfil, "Editar senha". O mesmo campo aparece em toda tela que pede uma senha nova.', { t: 'Editar senha', aviso: 'Clique em **Editar Senha**.' });
    await v.clicar('[data-bs-target="#EditarSenhaPerfil"]');
    await v.page.waitForSelector('#novaSenhaPerfil', { state: 'visible' });
    await v.passo('Digite a senha. Abaixo do campo, a barra mostra a força; uma senha curta é recusada já aqui, antes de enviar.', { t: 'A barra de força' });
    await v.digitar('#novaSenhaPerfil', 'abc12345');
    await v.pausa(600);
    v.confere((await v.page.getAttribute('.vc-senha-medidor', 'data-recusada')) === '1', 'a senha curta deveria ser recusada pelo medidor');
    await v.destacar('.vc-senha-medidor');
    await v.passo('Quanto mais longa, mais forte: uma frase com várias palavras é fácil de lembrar e difícil de adivinhar.', { t: 'Uma frase longa' });
    await v.page.fill('#novaSenhaPerfil', '');
    await v.digitar('#novaSenhaPerfil', 'cafe com leite na varanda azul de domingo');
    await v.pausa(600);
    v.confere((await v.page.getAttribute('.vc-senha-medidor', 'data-recusada')) === '0' && +(await v.page.getAttribute('.vc-senha-medidor', 'data-nivel')) >= 3, 'a frase longa deveria ser Boa ou Forte');
    await v.destacar('.vc-senha-medidor');
    v.poster();

    v.capitulo('Mostrar e ocultar');
    await v.passo('Para conferir o que digitou, clique em Mostrar; Ocultar esconde de novo. Colar e o gerenciador de senhas do navegador funcionam normalmente.', { t: 'Mostrar e ocultar' });
    await v.destacar('#EditarSenhaPerfil .vc-senha-revelar');

    v.capitulo('Salvar');
    await v.passo('Clique em Atualizar. As mesmas regras valem no servidor, que é quem decide.', { t: 'Salvar', aviso: 'Clique em **Atualizar**.' });
    await v.clicarENavegar('#EditarSenhaPerfil input[name="EditSenhaPerfil"]');
    const msg = await v.alerta();
    v.confere(/sucesso|atualizad|alterad/i.test(msg), 'a senha deveria ser salva: ' + msg);
    await v.feito('Senha nova salva.');
  },
};
