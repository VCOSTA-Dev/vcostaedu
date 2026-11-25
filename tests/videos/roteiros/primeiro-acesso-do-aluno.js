// Guia: Primeiro acesso do aluno. A secretaria emite o link de um aluno sem senha (sem SMTP, a tela o mostra); o aluno
// abre o link, vê as recusas (senhas diferentes, senha curta ou comum), define a senha, vê que o link não vale de novo e entra.
const SENHA = 'Demo' + Math.random().toString(36).slice(2, 10) + 'Vid7x'; // gerada a cada gravação (a demonstração volta ao retrato no fim)
module.exports = {
  conta: 'secretaria.demo',
  async gravar(v) {
    v.capitulo('O link de acesso');
    await v.ir('painel/academicos/visualizar/' + v.ids.aluno['260108']);
    await v.passo('Antes: a secretaria cadastrou o aluno e enviou o link de acesso (por e-mail ou entregue pessoalmente).');
    await v.clicarENavegar('button[name="EnviarLink"]');
    const link = await v.page.inputValue('#linkAcessoAluno').catch(() => '');
    v.confere(/definir-senha#t=/.test(link), 'a tela deveria mostrar o link de acesso');
    await v.destacar('#linkAcessoAluno');
    await v.ir('painel/login/logout');

    v.capitulo('Abrir o link');
    await v.passo('Agora o aluno, no computador dele, abre o link recebido.');
    await v.page.goto(link, { waitUntil: 'load' });
    await v.page.waitForSelector('#senha', { timeout: 30000 });
    v.confere(!/#t=/.test(v.page.url()), 'o código do link não deveria ficar na barra de endereço');
    await v.passo('O link abre "Definir a senha". O código do link sai da barra de endereço e não fica gravado no servidor.');
    await v.destacar('.login-box-msg');

    v.capitulo('As recusas');
    await v.passo('A nova senha, duas vezes: pelo menos 12 caracteres. Primeiro, com as duas diferentes…');
    await v.digitar('#senha', SENHA);
    await v.digitar('#senha_repetido', SENHA + 'x');
    await v.clicar('#btnAtualizaSenha');
    await v.pausa(600);
    const dif = await v.texto('#msgError');
    v.confere(/diferentes/.test(dif) && /definir-senha|atualizar-senha/.test(v.page.url()), 'senhas diferentes deveriam ser recusadas: ' + dif);
    await v.passo('Recusado na hora: "Os campos de senha estão diferentes!" — nada é enviado.');
    await v.destacar('#msgError');
    await v.passo('…e com uma senha curta demais.');
    await v.digitar('#senha', 'abc123');
    await v.digitar('#senha_repetido', 'abc123');
    await v.clicarENavegar('#btnAtualizaSenha');
    v.confere(/pelo menos 12 caracteres/.test(await v.alerta()), 'a senha curta deveria ser recusada: ' + await v.alerta());
    await v.passo('Recusada: a senha precisa ter pelo menos 12 caracteres. Senhas muito comuns também são recusadas.');
    await v.destacar('.alert-danger');

    v.capitulo('Definir a senha');
    await v.passo('Agora certo: a mesma senha nas duas caixas. Clique em Atualizar Senha.');
    await v.digitar('#senha', SENHA);
    await v.digitar('#senha_repetido', SENHA);
    await v.clicarENavegar('#btnAtualizaSenha');
    v.confere(/Senha definida/.test(await v.alerta()), 'a senha deveria ser definida: ' + await v.alerta());
    v.poster();
    await v.passo('"Senha definida. Entre com ela no portal." Ninguém da instituição fica sabendo dela.');
    await v.destacar('.alert-success');

    v.capitulo('O link vale uma vez só');
    await v.passo('Abrir o mesmo link de novo não funciona: ele vale uma vez só (e por 48 horas, se a instituição não mudou).');
    await v.page.goto(link, { waitUntil: 'load' });
    await v.page.waitForURL(/login\/acesso/, { timeout: 30000 });
    await v.page.waitForLoadState('networkidle').catch(() => {});
    v.confere(/inválido, já foi usado ou expirou/.test(await v.alerta()), 'o link usado deveria ser recusado: ' + await v.alerta());
    await v.destacar('.alert-danger');

    v.capitulo('Entrar no portal');
    await v.passo('Pronto: o aluno entra no portal com o RA e a senha que acabou de definir.');
    v.senhas['260108'] = SENHA;
    await v.entrar('260108');
    v.confere(/home\/index/.test(v.page.url()) && !(await v.page.$('input[name="SendLogin"]')), 'o aluno deveria entrar no portal');
    await v.passo('Dentro do portal: boletim, notas das atividades, horário e ajuda.');
    await v.destacar('#railItens');
  },
};
