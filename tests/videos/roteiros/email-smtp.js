// Guia: E-mail (SMTP) dos links de acesso (administrador). Preenche a configuração com um servidor INVENTADO (.invalid),
// salva e manda um link de teste: sem servidor de verdade, a tela avisa que o e-mail NÃO foi enviado e mostra o link.
const SENHA = 'Demo' + Math.random().toString(36).slice(2, 10) + '7'; // gerada a cada gravação (fictícia; o banco volta ao retrato)
module.exports = {
  conta: 'administrador',
  async gravar(v) {
    v.capitulo('Abrir a configuração');
    await v.ir('painel/home/index');
    await v.passo('Configurações → E-mail (SMTP): o servidor que envia os links de senha aos alunos.');
    await v.clicar(v.page.locator('#railMenu button.vc-side-grupo-toggle', { hasText: 'Configurações' }).first());
    await v.pausa(600);
    await v.clicarENavegar('aside a[href$="configuracao-email/listar/"]');
    v.confere(/configuracao-email\/listar/.test(v.page.url()), 'a tela de e-mail deveria abrir');
    await v.passo('Nenhuma configuração ainda: sem SMTP, nenhum link sai por e-mail. Clique em Cadastrar.');
    await v.destacar('#tabelaConfigEmail');
    await v.clicarENavegar('a[href$="configuracao-email/cadastrar"]');

    v.capitulo('Preencher os dados do servidor');
    await v.passo('Tenha em mãos servidor, porta, usuário e senha da conta de e-mail da instituição. Aqui, dados inventados.');
    await v.digitar('#titulo', 'Links de acesso do portal');
    await v.digitar('#nome', 'Secretaria Escolar (exemplo)');
    await v.digitar('#host', 'smtp.escola.exemplo.invalid');
    await v.digitar('#email', 'secretaria@escola.exemplo.invalid');
    await v.digitar('#username', 'secretaria@escola.exemplo.invalid');
    await v.passo('Senha da conta de e-mail, Smtp Secure (tls ou ssl) e a porta (587 com tls; 465 com ssl).');
    await v.digitar('#senhas', SENHA);
    await v.digitar('#smtpsecure', 'tls');
    await v.digitar('#porta', '587');
    v.poster();
    await v.clicarENavegar('input[name="CadConfigEmail"]');
    v.confere(/cadastrada com sucesso/.test(await v.alerta()), 'a configuração deveria ser salva: ' + await v.alerta());
    await v.passo('Salvo. A senha do e-mail fica só no banco da instituição: não aparece na lista, em relatório nem em guia.');
    await v.destacar('#tabelaConfigEmail tbody tr');
    /* 1.18.2 (D-489): na edição, a senha gravada NÃO vai para a tela — o campo vem em branco (em branco = manter a atual) */
    const editar = v.page.locator('#tabelaConfigEmail a[href*="configuracao-email/editar"]').first();
    if (await editar.count()) {
      await v.clicarENavegar(editar);
      const html = await v.page.content();
      v.confere((await v.page.inputValue('#senhas')) === '' && !html.includes(SENHA), 'a edição não deveria mostrar a senha gravada');
      await v.destacar('#senhas');
      await v.passo('Ao editar, o campo Senha vem em branco: a senha gravada não aparece. Em branco, ela é mantida; preencha só para trocar.');
      await v.page.goBack({ waitUntil: 'load' }).catch(() => {});
    } else v.confere(false, 'a lista deveria ter o botão de editar a configuração');

    v.capitulo('Mandar um link de teste');
    await v.passo('Agora, um link de teste: a secretaria (ou você) abre a ficha de um aluno e clica em "Enviar link de acesso".');
    await v.ir('painel/academicos/visualizar/' + v.ids.aluno['260108']);
    await v.clicarENavegar('button[name="EnviarLink"]');
    const msg = await v.alerta();
    v.confere(/O e-mail NÃO foi enviado/.test(msg) && await v.page.$('#linkAcessoAluno'), 'com o servidor inventado, o e-mail não deveria sair: ' + msg);
    await v.passo('O servidor inventado não responde: a tela avisa "O e-mail NÃO foi enviado" e diz o motivo.');
    await v.destacar(v.page.locator('.alert-warning', { hasText: 'NÃO foi enviado' }).first());
    await v.passo('Sem SMTP (ou sem e-mail no cadastro), a secretaria copia o link e entrega por um canal seguro.');
    await v.destacar('#linkAcessoAluno');
    await v.passo('Com o servidor certo, o aviso é "Link de acesso enviado para …" e o link vai direto ao e-mail do aluno.');
  },
};
