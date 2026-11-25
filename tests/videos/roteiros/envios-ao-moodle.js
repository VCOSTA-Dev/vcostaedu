// Guia: Envios ao Moodle (administrador, secretaria). Os contadores, o filtro, a linha com o que fazer e "Enviar tudo o
// que falta". Pede a demonstração conectada ao Moodle de teste (o percurso da palestra a deixa assim).
// O roteiro é o do "Faça comigo" (painel/app/adms/Roteiros/envios-ao-moodle.json), no próprio motor do produto: o mesmo arquivo
// é a prova, este vídeo e o botão do guia. O banco volta ao retrato.
module.exports = {
  conta: 'administrador',
  async gravar(v) {
    v.capitulo('Envios ao Moodle');
    await v.ir('painel/moodle-envios/listar');
    v.confere(!(await v.page.$('#enviosSemConexao')), 'a demonstração deveria estar conectada ao Moodle');
    await v.facaComigo('envios-ao-moodle');
    v.poster();
    if (await v.page.$('#enviarTudo')) {
      v.capitulo('Enviar tudo o que falta');
      await v.passo('"Enviar tudo o que falta" manda a fila inteira em passos curtos, com a janela de progresso.', { t: 'Enviar tudo', aviso: 'Clique em **Enviar tudo o que falta**.' });
      await v.clicar('#enviarTudo');
      await v.acelerar(() => v.page.waitForSelector('#progressoEnvios[data-situacao="concluida"], #progressoEnvios[data-situacao="falhou"]', { timeout: 300000 }), 3000);
      await v.feito('A fila foi enviada.');
    }
  },
};
