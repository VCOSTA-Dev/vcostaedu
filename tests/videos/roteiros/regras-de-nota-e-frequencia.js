// Guia: Regras de nota e frequência (administrador). Os campos da tela de Configurações da instituição, um valor fora
// da faixa recusado e o salvamento certo. Dados fictícios; o banco volta ao retrato no fim.
module.exports = {
  conta: 'administrador',
  async gravar(v) {
    v.capitulo('A tela');
    await v.ir('painel/parametros/listar');
    await v.passo('Configurações → Instituição: as regras que valem para todo o sistema.');
    await v.passo('Portal do aluno: o aluno entra com RA, CPF ou e-mail, e a validade do link de senha (1 a 720 horas).');
    await v.destacar('#portal_login_campo');
    await v.destacar('#portal_senha_link_horas');
    await v.passo('Casas decimais e média mínima ficam na regra de média: a tela mostra os valores e o link.');
    await v.rolar('#regraMediaResumo');
    await v.destacar('#regraMediaResumo');
    await v.passo('Frequência mínima (padrão 75%) e "Mostrar média parcial ao aluno". O curso pode ter a sua própria frequência.');
    await v.destacar('#frequencia_minima');
    await v.destacar('#portal_media_parcial');
    v.poster();
    await v.passo('"Organizar a matriz em módulos" (Sim ou Não), os textos dos documentos e a média final do histórico.');
    await v.destacar('#matriz_modulos');
    await v.rolar('#textosDocumentos');
    await v.destacar('#textosDocumentos');
    await v.destacar('#documento_media_final');
    await v.passo('Os limites de tentativas por IP: login, "Esqueci a senha" e a verificação pública.');
    await v.rolar('#limitesIp');
    await v.destacar('#limitesIp');

    v.capitulo('Fora da faixa: recusado');
    await v.passo('Uma validade de link de 1000 horas, fora da faixa. Salvar:');
    await v.rolar('#portal_senha_link_horas');
    await v.digitar('#portal_senha_link_horas', '1000');
    await v.clicar('input[name="SalvarParametros"]');   // a tela recusa antes de enviar: não há navegação
    await v.pausa(800);
    const r1 = await v.alerta();
    v.confere(/Validade do link/.test(r1) && /720/.test(r1), 'deveria recusar 1000 horas: ' + r1);
    await v.passo('Recusado: a validade do link vai de 1 a 720 horas. Nada foi salvo; a tela aponta o campo.');
    await v.destacar('.alert:visible');

    v.capitulo('Salvar');
    await v.passo('Corrigido para 72 horas, e a frequência mínima em 80%. Salvar.');
    await v.digitar('#portal_senha_link_horas', '72');
    await v.rolar('#frequencia_minima');
    await v.digitar('#frequencia_minima', '80');
    await v.rolar('input[name="SalvarParametros"]');
    await v.clicarENavegar('input[name="SalvarParametros"]');
    const r2 = await v.alerta();
    v.confere(!/deve|RECUSAD|inválid/i.test(r2) && await v.page.inputValue('#portal_senha_link_horas') === '72', 'deveria salvar: ' + r2);
    await v.passo('Salvo. Vale para as disciplinas abertas e os próximos encerramentos; a encerrada guarda as regras do dia.');
    await v.destacar('.alert:visible');
  },
};
