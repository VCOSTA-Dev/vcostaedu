// Guia: Entrar no painel (equipe). A senha errada recusada, a entrada certa, o menu do perfil, o tema (claro, escuro
// ou automático, por pessoa) e o aviso da página inicial. As contas são da demonstração; o banco volta ao retrato.
// (Refeito na versão 1.42: a interface de hoje e o tema.)
module.exports = {
  contas: ['secretaria.demo'],
  async gravar(v) {
    v.capitulo('A tela de entrada');
    await v.ir('painel/login/acesso');
    await v.passo('Abra o endereço do painel e informe o usuário (ou o e-mail) e a senha.', { t: 'Entrar no painel', aviso: 'Digite e clique em **Acessar**.' });
    await v.digitar('#usuario', 'secretaria.demo');
    await v.digitar('#senha', 'senhaErrada1');
    await v.clicarENavegar('input[name="SendLogin"]');
    v.confere(/incorretos/i.test(await v.alerta()), 'a senha errada deveria ser recusada: ' + (await v.alerta()));
    await v.passo('Senha errada: "Usuário ou senha incorretos." A mensagem é a mesma se o usuário não existir. Depois de várias erradas, a entrada pede uma espera.', { t: 'A senha errada' });
    await v.destacar('.alert');

    v.capitulo('Entrar');
    await v.passo('Agora, com a senha certa.', { t: 'A senha certa', aviso: 'Clique em **Acessar**.' });
    await v.digitar('#usuario', 'secretaria.demo');
    await v.digitar('#senha', v.senhas['secretaria.demo']);
    await v.clicarENavegar('input[name="SendLogin"]');
    v.confere(/home\/index/.test(v.page.url()), 'a secretaria deveria entrar na página inicial: ' + v.page.url());
    await v.feito('Entrou, na página inicial.');

    v.capitulo('O menu do seu perfil');
    await v.passo('O menu da esquerda mostra só o que o seu perfil pode abrir, em grupos. A tela em que você está fica destacada.', { t: 'O menu' });
    await v.destacar('#railMenu');
    v.poster();

    v.capitulo('O tema');
    const claro = '.vc-tema-item .vc-tema-opcao[data-vc-tema="claro"]', escuro = '.vc-tema-item .vc-tema-opcao[data-vc-tema="escuro"]';
    const abrir = async () => { if (!(await v.page.isVisible(escuro)) && await v.page.$('#vc-side-alca')) await v.clicar('#vc-side-alca'); };
    const escolher = async (sel) => { await v.destacar(sel); await v.page.locator(sel).first().evaluate((e) => e.click()); }; // o botão do tema, pelo próprio elemento
    const tema = () => v.page.evaluate(() => document.documentElement.getAttribute('data-bs-theme') || '');
    await v.passo('Embaixo do menu, o seu nome abre as suas opções: o tema, o seu perfil e sair.', { t: 'As suas opções', aviso: 'Clique no seu nome.' });
    await abrir();
    v.confere(await v.page.isVisible(escuro), 'o seletor do tema deveria aparecer');
    await v.passo('O tema é de cada pessoa: claro, escuro ou automático (segue o computador). Fica guardado para as próximas vezes.', { t: 'Claro, escuro ou automático', aviso: 'Clique em **Escuro**.' });
    await escolher(escuro);
    await v.pausa(1500);
    v.confere(/dark/.test(await tema()), 'a tela deveria ficar escura: ' + (await tema()));
    await v.feito('O tema escuro, nesta conta.');
    await abrir();
    await escolher(claro);
    await v.pausa(1200);
    v.confere(!/dark/.test(await tema()), 'a tela deveria voltar ao claro');
    await v.passo('Para trocar a senha ou os seus dados: "Meu perfil", no mesmo lugar.', { t: 'O seu perfil' });

    v.capitulo('O aviso da página inicial');
    if (await v.page.$('#botaoEntendi')) {
      await v.passo('O aviso de boas-vindas (ou de novidades) fica aqui até você clicar em Entendi. As novidades continuam em Ajuda → Novidades.', { t: 'O aviso', aviso: 'Clique em **Entendi**.' });
      await v.clicarENavegar('#botaoEntendi');
      await v.feito('Lido: o aviso não volta.');
    }
  },
};
