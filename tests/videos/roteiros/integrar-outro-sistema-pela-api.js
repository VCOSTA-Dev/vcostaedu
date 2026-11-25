// Guia: Integrar outro sistema pela API (administrador). A chave com o limite por assunto (aparece uma vez — no vídeo,
// mascarada), a documentação e as chamadas.
// O roteiro é o do "Faça comigo" (painel/app/adms/Roteiros/integrar-outro-sistema-pela-api.json), no próprio motor do produto: o mesmo arquivo
// é a prova, este vídeo e o botão do guia. O banco volta ao retrato.
module.exports = {
  conta: 'administrador',
  async gravar(v) {
    v.capitulo('A chave, a documentação e as chamadas');
    await v.facaComigo('integrar-outro-sistema-pela-api');
    v.poster();
  },
};
