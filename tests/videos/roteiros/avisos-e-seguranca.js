// Guia: Avisos do sistema e segurança (administrador). As versões, a versão nova, a segurança, as verificações e as
// bibliotecas, em Manutenção. Só leitura.
// O roteiro é o do "Faça comigo" (painel/app/adms/Roteiros/avisos-e-seguranca.json), no próprio motor do produto: o mesmo arquivo
// é a prova, este vídeo e o botão do guia. O banco volta ao retrato.
module.exports = {
  conta: 'administrador',
  async gravar(v) {
    v.capitulo('Avisos do sistema e segurança');
    await v.facaComigo('avisos-e-seguranca');
    v.poster();
  },
};
