// Guia: Entrar no portal (aluno). O login pelo RA, a senha errada, a espera depois de 5 erros (nem a senha certa entra
// durante ela), a entrada depois da espera e o menu do portal. O aluno é da demonstração; o banco volta ao retrato.
const RA = '260104';
const grupo = (v, nome) => v.page.locator('#railMenu button.vc-side-grupo-toggle', { hasText: nome }).first();
module.exports = {
  contas: [RA],
  async gravar(v) {
    v.capitulo('A tela de entrada');
    await v.ir('portal/login/acesso');
    await v.passo('Na tela de entrada, o usuário é o RA (ou CPF ou e-mail, se a instituição escolheu): a própria tela diz qual.');
    await v.destacar('#usuario');
    await v.digitar('#usuario', RA);
    await v.digitar('input[name="senha"]', 'errei1');
    await v.clicarENavegar('input[name="SendLogin"]');
    v.confere(/Usuário ou senha incorretos/.test(await v.alerta()), 'a senha errada deveria ser recusada: ' + await v.alerta());
    await v.passo('Senha errada: "Usuário ou senha incorretos." A mensagem é a mesma se o usuário não existir.');
    await v.destacar('#loginFalha');

    v.capitulo('A espera depois de 5 erros');
    await v.passo('Errando 5 vezes seguidas…');
    for (let i = 2; i <= 5; i++) {
      await v.digitar('input[name="senha"]', 'errei' + i);
      await v.clicarENavegar('input[name="SendLogin"]');
    }
    const t5 = Date.now();
    v.confere(/Muitas tentativas/.test(await v.alerta()), 'o 5º erro deveria pedir a espera: ' + await v.alerta());
    await v.passo('…a entrada pede uma espera: "Muitas tentativas. Aguarde…". Começa em 30 s e dobra a cada novo erro.');
    await v.destacar('#loginFalha');
    await v.passo('Durante a espera, nem a senha certa entra:');
    await v.digitar('input[name="senha"]', v.senhas[RA]);
    await v.clicarENavegar('input[name="SendLogin"]');
    v.confere(/Muitas tentativas/.test(await v.alerta()) && await v.page.$('input[name="SendLogin"]'), 'durante a espera, a senha certa não deveria entrar');
    await v.destacar('#loginEspera');
    v.poster();
    await v.passo('Espere o tempo indicado (aqui, 30 segundos). Esqueceu a senha? Use "Esqueci a senha", abaixo do botão.');
    await v.destacar('a[href$="login/esqueceu-senha"]');
    await v.passo('Depois da espera, a senha certa entra e zera a contagem de erros.');
    const falta = 31500 - (Date.now() - t5);
    if (falta > 0) await v.pausa(falta);

    v.capitulo('Entrar');
    await v.digitar('#usuario', RA);
    await v.digitar('input[name="senha"]', v.senhas[RA]);
    await v.clicarENavegar('input[name="SendLogin"]');
    v.confere(/home\/index/.test(v.page.url()) && !(await v.page.$('input[name="SendLogin"]')), 'depois da espera, a senha certa deveria entrar: ' + await v.alerta());
    await v.passo('Entrou. O menu traz o grupo Acadêmico (Horário, Boletim, Notas das Atividades e outros)…');
    await v.clicar(grupo(v, 'Acadêmico'));
    await v.pausa(700);
    await v.destacar(grupo(v, 'Acadêmico').locator('xpath=..'));
    await v.passo('…e o grupo Ajuda, com a Central de ajuda e as Novidades.');
    await v.clicar(grupo(v, 'Ajuda'));
    await v.pausa(700);
    await v.destacar(grupo(v, 'Ajuda').locator('xpath=..'));
    await v.passo('Portal dentro do Moodle com o botão "Abrir o portal em nova aba"? O navegador bloqueou; use o botão.');
  },
};
