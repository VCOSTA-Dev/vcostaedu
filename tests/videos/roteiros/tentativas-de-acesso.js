// Guia: Tentativas de acesso e limites (administrador). Um usuário inventado erra a senha 5 vezes e cai na espera; o
// administrador vê a linha em espera, libera pelo usuário e vê a recusa de um limite por IP fora da faixa.
const INVENTADO = 'carlos.inventado';
const grupo = (v, nome) => v.page.locator('#railMenu button.vc-side-grupo-toggle', { hasText: nome }).first();
async function peloMenu(v, nome, rota) {
  await v.menu(`${rota}/`);
}
module.exports = {
  contas: ['administrador'],
  async gravar(v) {
    v.capitulo('Cinco senhas erradas');
    await v.ir('painel/login/acesso');
    await v.passo('Alguém tenta entrar como "carlos.inventado" (um usuário inventado) e erra a senha cinco vezes.');
    await v.digitar('input[name="usuario"]', INVENTADO);
    for (let i = 1; i <= 5; i++) {
      await v.digitar('input[name="senha"]', 'chute' + i);
      await v.clicarENavegar('input[name="SendLogin"]');
    }
    v.confere(/Muitas tentativas/.test(await v.alerta()), 'o 5º erro deveria pedir a espera: ' + await v.alerta());
    await v.passo('No 5º erro: "Muitas tentativas. Aguarde…". A espera começa em 30 s e dobra a cada nova falha, até 15 min.');
    await v.destacar('#loginFalha');

    v.capitulo('Quem está em espera');
    await v.passo('O administrador entra no painel…');
    await v.entrar('administrador');
    await peloMenu(v, 'Configurações', 'tentativas-acesso/listar');
    v.confere(/tentativas-acesso\/listar/.test(v.page.url()), 'a tela Tentativas de acesso deveria abrir');
    const linha = v.page.locator('#listaTentativas tbody tr.table-warning', { hasText: 'Login do painel' }).first();
    v.confere(await linha.count(), 'deveria haver uma linha em espera (amarela)');
    await v.passo('…e abre Configurações → Tentativas de acesso. O quadro azul resume os limites em vigor.');
    await v.destacar('.alert-info');
    v.poster();
    await v.passo('A linha amarela está em espera agora: 5 falhas, por usuário, com a hora em que a espera acaba.');
    await v.destacar(linha);
    await v.passo('A tabela guarda só uma dica do usuário (as 2 primeiras letras e o tamanho), nunca a senha.');
    await v.destacar(linha.locator('td.text-monospace'));

    v.capitulo('Liberar antes do fim');
    await v.passo('Para liberar: digite o usuário como a pessoa digitou na entrada e clique em Liberar (ou use o botão da linha).');
    await v.digitar('input[name="chave"]', INVENTADO);
    await v.clicarENavegar(v.page.locator('form[action$="tentativas-acesso/liberar"] button', { hasText: 'Liberar' }).first());
    v.confere(/Contagem apagada/.test(await v.alerta()), 'a contagem deveria ser apagada: ' + await v.alerta());
    v.confere(!(await v.page.locator('#listaTentativas tbody tr.table-warning', { hasText: 'Login do painel' }).count()), 'a linha em espera deveria sumir');
    await v.passo('"Contagem apagada": a pessoa pode tentar de novo. A linha do IP fica — na dúvida, não libere o IP.');
    await v.destacar('#liberado');

    v.capitulo('Os limites por IP');
    await v.passo('Os limites por IP ficam em Configurações → Instituição, no quadro "Limite de tentativas por IP".');
    await peloMenu(v, 'Configurações', 'parametros/listar');
    await v.rolar('#limitesIp');
    await v.destacar('#limitesIp');
    await v.passo('Um valor fora da faixa é recusado. Ex.: 5 falhas por hora no login — a faixa é de 10 a 1000.');
    await v.digitar('#limite_ip_login', '5');
    await v.clicar('input[name="SalvarParametros"]');
    await v.pausa(700);
    const msg = await v.texto('.vc-aviso-validacao');
    v.confere(/falhas por hora/.test(msg) && /parametros\/listar/.test(v.page.url()), 'o limite fora da faixa deveria ser recusado: ' + msg);
    await v.passo('Recusado, e nada foi salvo: o campo fora da faixa fica marcado. Use um número dentro dela e salve de novo.');
    await v.destacar('.vc-aviso-validacao');
    await v.destacar('#limite_ip_login');
  },
};
