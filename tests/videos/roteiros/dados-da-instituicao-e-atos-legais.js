// Guia: Dados da instituição e atos legais (administrador). O "Faça comigo" dos dados da instituição, no próprio motor do
// produto (painel/app/adms/Roteiros/fase-instituicao.json — o mesmo roteiro da prova e do botão do guia): o nome, a
// mantenedora, o CNPJ, o contato, o logo e salvar; depois, o topo do menu com o nome e o logo. O banco volta ao retrato.
// (Refeito na versão 1.42: o roteiro em arquivo de dados e o topo da instituição.)
module.exports = {
  conta: 'administrador',
  async gravar(v) {
    v.capitulo('Os dados da instituição');
    await v.ir('painel/dados-instituicao/editar');
    await v.passo('Configurações → Instituição: os dados que aparecem no topo do menu, nos documentos e no ícone da aba do navegador.', { t: 'Dados da instituição', aviso: 'Siga o "Faça comigo".' });
    await v.facaComigo('fase-instituicao');
    v.poster();

    v.capitulo('O topo do menu');
    await v.ir('painel/home/index');
    await v.passo('No alto do menu, ao lado do selo do sistema, o logo ou o nome da instituição. O mesmo logo vira o ícone da aba do navegador.', { t: 'O topo da instituição' });
    await v.destacar('.navbar-brand').catch(() => {});
    await v.feito('A instituição aparece no topo de toda tela.');
  },
};
