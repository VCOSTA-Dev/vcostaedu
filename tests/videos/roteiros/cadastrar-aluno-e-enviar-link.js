// Guia: Cadastrar aluno e enviar o link (secretaria). Mostra a recusa sem o sexo, o cadastro certo e o link de acesso
// (sem SMTP, a tela mostra o link para a secretaria entregar). O aluno é inventado; o banco volta ao retrato no fim.
const RA = '260191';
module.exports = {
  conta: 'secretaria.demo',
  async gravar(v) {
    v.capitulo('Abrir o cadastro');
    await v.ir('painel/academicos/listar');
    await v.passo('Educacional → Usuários Acadêmico: a lista de alunos. Clique em "Cadastrar aluno".');
    await v.clicarENavegar('a[href$="academicos/cadastrar"]');

    v.capitulo('O sexo é obrigatório');
    await v.passo('Preencha o RA (só números, único) e o nome. Vamos tentar salvar sem escolher o sexo.');
    await v.digitar('#rm', RA);
    await v.digitar('#nome', 'Paula Demonstração Vídeo');
    await v.clicar('input[name="CadUsuarioAcad"]');
    const msg = await v.page.$eval('#genero_id', el => el.validationMessage).catch(() => '');
    v.confere(!!msg, 'o navegador deveria barrar o envio sem o sexo');
    await v.passo('Recusado: o campo Sexo é obrigatório — os documentos concordam com ele (ALUNA/ALUNO).');
    await v.destacar('#genero_id');

    v.capitulo('Cadastro certo');
    await v.passo('Escolha o sexo. CPF e e-mail são opcionais, mas precisam ser válidos e únicos.');
    await v.escolher('#genero_id', '2');
    await v.digitar('#email', 'paula.video@exemplo.invalid');
    await v.clicarENavegar('input[name="CadUsuarioAcad"]');
    v.confere(/sucesso|cadastrad/i.test(await v.alerta()), 'o aluno deveria ser cadastrado: ' + await v.alerta());
    await v.passo('Aluno cadastrado. Ele nasce SEM senha: quem define a senha é o próprio aluno, pelo link.');

    v.capitulo('Enviar o link de acesso');
    v.confere(/academicos\/visualizar\/\d+/.test(v.page.url()), 'depois de salvar, o sistema abre a ficha do aluno');
    await v.passo('Na ficha do aluno, clique em "Enviar link de acesso".');
    await v.clicarENavegar('button[name="EnviarLink"]');
    v.confere(await v.page.$('#linkAcessoAluno'), 'a tela deveria mostrar o link');
    v.poster();
    await v.passo('Sem e-mail configurado, a tela avisa "O e-mail NÃO foi enviado" e mostra o link para você entregar por um canal seguro.');
    await v.destacar('#linkAcessoAluno');
    await v.passo('O link vale por 48 horas e uma vez só; um link novo invalida o anterior.');
  },
};
