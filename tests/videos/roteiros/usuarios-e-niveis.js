// Guia: Usuários da equipe e titulação (administrador). Cadastra uma professora inventada com a titulação, mostra a
// recusa do e-mail já usado, o pedido de atualização dos dados e quem altera quem (o próprio cadastro fica no
// Perfil, fora da lista). O banco volta ao retrato no fim.
const NOME = 'Renata Docente Vídeo (fictícia)';
const SENHA = 'Demo' + Math.random().toString(36).slice(2, 10) + '7'; // gerada a cada gravação (fictícia; o banco volta ao retrato)
module.exports = {
  conta: 'administrador',
  async gravar(v) {
    v.capitulo('A lista da equipe');
    await v.ir('painel/home/index');
    await v.passo('Usuários, no menu: quem tem acesso ao painel (secretaria e professores).');
    await v.menu('usuarios/listar/');
    v.confere(/usuarios\/listar/.test(v.page.url()), 'a lista de usuários deveria abrir');
    await v.destacar('table tbody');
    await v.clicarENavegar('a[href$="usuarios/cadastrar"]');

    v.capitulo('Cadastrar uma professora');
    await v.passo('Nome, e-mail, sexo, usuário e a senha inicial. Vamos usar, por engano, um e-mail que já existe.');
    await v.digitar('#nome', NOME);
    await v.digitar('#email', 'prof.laura.demo@exemplo.invalid');
    await v.escolher('#genero_id', '1');
    await v.digitar('#usuario', 'prof.renata.video');
    await v.digitar('#senha', SENHA);
    /* 1.18.3 (D-497): a escolha do nível oferece só os níveis abaixo do seu */
    const niveis = await v.page.$$eval('#adms_niveis_acesso_id option', o => o.map(x => x.textContent.trim()).filter(Boolean));
    v.confere(!niveis.some(n => /Super Administrador|^Administrador$|VCOSTA|Suporte/.test(n)), 'o cadastro só deveria oferecer níveis abaixo: ' + niveis.join(', '));
    await v.passo('O nível de acesso: a lista só oferece níveis ABAIXO do seu. Para professor, a titulação: ela sai no histórico.');
    await v.escolher('#adms_niveis_acesso_id', '6');
    await v.escolher('#adms_sits_usuario_id', '1');
    await v.escolher('#titulacao_id', '2');
    await v.clicarENavegar('input[name="CadUsuario"]');
    const erro = await v.alerta();
    v.confere(/Email já está cadastrado/.test(erro), 'o e-mail repetido deveria ser recusado: ' + erro);
    await v.passo('Recusado: o e-mail já é de outra pessoa. Usuário e e-mail não podem se repetir.');
    await v.destacar(v.page.locator('.alert-danger').first());
    /* 1.18.2 (D-489): os dados voltam preenchidos, MENOS a senha — ela não vai para o HTML */
    const html = await v.page.content();
    v.confere((await v.page.inputValue('#senha')) === '' && !html.includes(SENHA), 'a senha não deveria voltar preenchida');
    await v.destacar('#senha');
    await v.passo('Os dados voltam preenchidos, menos a senha: por segurança, ela nunca volta. Digite de novo.');

    v.capitulo('Cadastro certo');
    await v.passo('Corrija o e-mail e salve de novo.');
    await v.digitar('#email', 'prof.renata.video@exemplo.invalid');
    await v.digitar('#senha', SENHA);
    v.confere((await v.page.inputValue('#titulacao_id')) === '2', 'a titulação escolhida deveria continuar no formulário');
    await v.clicarENavegar('input[name="CadUsuario"]');
    v.confere(/cadastrado com sucesso/.test(await v.alerta()), 'o usuário deveria ser cadastrado: ' + await v.alerta());
    if (!/usuarios\/listar/.test(v.page.url())) await v.ir('painel/usuarios/listar');
    const linha = v.page.locator('table tbody tr', { hasText: NOME }).first();
    v.confere(await linha.count(), 'a professora nova deveria estar na lista');
    await v.passo('Cadastrada, como Professor. Entregue a senha inicial por um canal seguro e peça a troca no 1º acesso.');
    v.poster();
    await v.destacar(linha);

    v.capitulo('Pedir a atualização dos dados');
    /* 1.18.4 (D-503): a situação "Atualizar Dados" não bloqueia: a pessoa entra e atualiza o cadastro antes de usar o sistema */
    await v.clicarENavegar(linha.locator('a[href*="usuarios/editar/"]').first());
    await v.passo('Para pedir que a pessoa confira o cadastro, abra Editar e escolha a situação "Atualizar Dados".');
    await v.escolher('#adms_sits_usuario_id', '2');
    await v.destacar('#ajudaSituacao');
    await v.passo('Ela entra e é levada a atualizar os dados antes de usar o sistema. Inativo e Bloqueado não entram.');
    await v.clicarENavegar('input[name="EditUsuario"]');
    v.confere(/atualizado com sucesso|editado com sucesso/.test(await v.alerta()), 'a situação deveria ser gravada: ' + await v.alerta());
    await v.passo('Gravado. Ao salvar os dados, a situação volta sozinha para Ativo.');
    await v.ir('painel/usuarios/listar');

    v.capitulo('Quem altera quem');
    /* só se altera quem é de nível abaixo; o próprio cadastro não está na lista (fica no Perfil) */
    v.confere(!(await v.page.locator('table tbody tr', { hasText: 'Alice Administradora' }).count()), 'o próprio usuário não deveria estar na lista');
    await v.passo('Você só altera quem é de nível ABAIXO do seu. Quem é do seu nível ou de um nível acima aparece como "Protegido", sem editar nem apagar.');
    await v.destacar('table tbody');
    await v.passo('Você não aparece na lista: o seu cadastro e a sua senha ficam no Perfil (clique no seu nome, embaixo do menu).');
    await v.destacar('#railMenu .vc-side-rodape').catch(() => {});
  },
};
