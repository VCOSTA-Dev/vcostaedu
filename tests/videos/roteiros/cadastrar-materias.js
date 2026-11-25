// Guia: Cadastrar matérias (secretaria). O catálogo, o cadastro de uma matéria inventada com o código do histórico e a
// matéria na lista com o código. O banco volta ao retrato no fim.
const NOME = 'Ética no Serviço Público (vídeo)';
module.exports = {
  conta: 'secretaria.demo',
  async gravar(v) {
    v.capitulo('O catálogo de matérias');
    await v.ir('painel/materias/listar');
    await v.passo('Ensino → Matérias: o catálogo de disciplinas. A matriz de cada curso é montada com elas.');
    await v.destacar('#tabelaMaterias thead th:nth-child(2)');
    await v.passo('Cada matéria tem o seu código (a coluna "Código / abreviação"): é ele que sai no histórico.');
    await v.clicarENavegar('a[href$="materias/cadastrar"]');

    v.capitulo('Cadastrar');
    await v.passo('Código / abreviação: até 20 caracteres. Em branco, o histórico mostra o número interno da matéria.');
    await v.digitar('#abreviacao', 'ESP101');
    /* 1.18.1 (D-481): nome e carga horária obrigatórios — a tela barra o envio sem eles (e o servidor recusa sem gravar) */
    await v.passo('Nome e carga horária são obrigatórios: sem eles, a tela não deixa salvar.');
    await v.clicar('input[name="CadMaterias"]');
    const aviso = await v.page.$eval('#nome', el => el.validationMessage).catch(() => '');
    v.confere(aviso !== '' && /materias\/cadastrar/.test(v.page.url()), 'salvar sem o nome deveria ser barrado: ' + aviso);
    await v.pausa(1500);
    await v.passo(`Recusado: "${aviso}" — e, se o pedido chegasse assim mesmo, o sistema recusaria sem gravar nada.`);
    await v.passo('Agora o nome, a carga horária padrão (horas, de 1 a 9999) e uma descrição curta.');
    await v.digitar('#nome', NOME);
    await v.digitar('#carga_horaria', '30');
    await v.digitar('#descricao', 'Princípios éticos e conduta do agente público.');
    await v.clicarENavegar('input[name="CadMaterias"]');
    v.confere(/Matéria criada!/.test(await v.alerta()), 'a matéria deveria ser criada ("Matéria criada!"): ' + await v.alerta());
    v.confere(/materias\/listar/.test(v.page.url()), 'depois de salvar, volta à lista');

    v.capitulo('A matéria na lista');
    await v.passo('Salvo: "Matéria criada!". A matéria nova aparece na lista, com o código ESP101.');
    const linha = v.page.locator('#tabelaMaterias tbody tr', { hasText: NOME }).first();
    v.confere(await linha.count(), 'a matéria nova deveria estar na lista');
    v.confere(/ESP101/.test(await linha.textContent()), 'a linha deveria mostrar o código');
    await v.rolar('#tabelaMaterias tbody tr:has-text("' + NOME + '")');
    v.poster();
    await v.destacar(linha);
    await v.passo('Apagar matéria é do administrador; a secretaria cadastra e edita (lápis). Matéria que não será oferecida: inative-a.');
    await v.destacar(linha.locator('a[href*="materias/editar"]'));
  },
};
