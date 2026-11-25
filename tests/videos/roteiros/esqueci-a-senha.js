// Guia: Esqueci a senha (aluno). O pedido do link pelo RA, a resposta sempre igual (exista ou não o cadastro), a espera
// depois de pedidos seguidos e o link inválido. A demonstração não envia e-mail; o banco volta ao retrato no fim.
const ESPERADA = /Se os dados corresponderem a um aluno com e-mail cadastrado/;
async function pedir(v, ra, mostrar) {
  if (mostrar) await v.clicarENavegar('a[href$="login/esqueceu-senha"]');
  else await v.ir('portal/login/esqueceu-senha');
  await v.digitar('#identificador', ra);
  await v.clicarENavegar('input[name="RecupLogin"]');
  return v.alerta();
}
module.exports = {
  async gravar(v) {
    v.capitulo('Pedir o link');
    await v.ir('portal/login/acesso');
    await v.passo('Na tela de entrada do portal, clique em "Esqueci a senha".');
    await v.destacar('a[href$="login/esqueceu-senha"]');
    await v.clicarENavegar('a[href$="login/esqueceu-senha"]');
    await v.passo('Informe o seu usuário (aqui, o RA) e clique em Recuperar.');
    await v.destacar('.login-box-msg');
    await v.digitar('#identificador', '260104');
    await v.clicarENavegar('input[name="RecupLogin"]');
    const r1 = await v.alerta();
    v.confere(ESPERADA.test(r1), 'a tela deveria responder o pedido: ' + r1);
    await v.passo('Se houver e-mail no cadastro, chega um link para definir a nova senha. Ele vale por 48 horas.');
    await v.destacar('.alert-info');
    v.poster();

    v.capitulo('A resposta é sempre a mesma');
    await v.passo('Por segurança, a resposta é a mesma exista ou não o cadastro. Veja com um RA que não existe:');
    const r2 = await pedir(v, '269999', true);
    v.confere(r2 === r1, 'a resposta para um RA inexistente deveria ser idêntica');
    await v.passo('A mesma mensagem: a tela não revela quem tem cadastro.');
    await v.destacar('.alert-info');

    v.capitulo('Muitos pedidos seguidos');
    await v.passo('Pedindo de novo e de novo para o mesmo RA…');
    await pedir(v, '260104', false);
    await pedir(v, '260104', false);
    const r3 = await pedir(v, '260104', false);
    v.confere(/Muitas tentativas/.test(r3), 'pedidos seguidos deveriam pedir uma espera: ' + r3);
    await v.passo('…a tela pede uma espera: começa em 30 segundos e dobra a cada novo pedido, até 15 minutos.');
    await v.destacar('#esqueciEspera');
    await v.passo('Espere e peça de novo, ou procure a secretaria.');

    v.capitulo('O link');
    await v.passo('Com o link do e-mail, você define a nova senha como no primeiro acesso. Esta demonstração não envia e-mail.');
    await v.page.goto(v.page.url().replace(/portal\/.*$/, 'portal/login/definir-senha#t=') + 'Ab12'.repeat(10), { waitUntil: 'load' });
    await v.page.waitForURL(/login\/acesso/, { timeout: 30000 });
    await v.page.waitForLoadState('networkidle').catch(() => {});
    const r4 = await v.alerta();
    v.confere(/inválido, já foi usado ou expirou/.test(r4), 'um link inválido deveria ser recusado: ' + r4);
    await v.passo('Link velho, já usado ou incompleto: "Este link é inválido, já foi usado ou expirou." Peça outro.');
    await v.destacar('.alert-danger');
    await v.passo('O e-mail não chegou? Confira o spam. Sem e-mail no cadastro, a secretaria envia o link pela sua ficha.');
  },
};
