// Bloco 12 — Encerramento da jornada: o painel avisa que acabou e o navegador fica aberto para o responsável pelo projeto explorar com o
// usuário da jornada. Nada é limpo aqui: a limpeza só acontece depois de "pode limpar" no chat do agente.
const J = require('./lib');

J.rodar(12, 'Fim da jornada', async ({ page }) => {
  await J.ir(page, 'painel/home/index');
  await J.mostrar(page, 'Jornada concluída',
    'Jornada concluída. O navegador fica aberto para você explorar com o usuário teste.jornada. Quando terminar, escreva pode limpar no chat do agente.', false);
  J.registrar('fim', 'Jornada concluída — aguardando "pode limpar"');
  return null;
});
