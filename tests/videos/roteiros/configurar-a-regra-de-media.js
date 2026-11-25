// Guia: Configurar a regra de média (administrador). O catálogo com as regras prontas; uma regra pronta aberta (os
// componentes e os pesos); o acréscimo (permitir, o limite) e o simulador, com a memória de cálculo; salvar. O banco
// volta ao retrato. (Refeito na versão 1.42: as regras prontas e o acréscimo.)
async function esperarSim(v) {
  await v.pausa(400);
  await v.page.waitForFunction(() => { const e = document.getElementById('simEspera'); return !e || e.textContent === ''; }, null, { timeout: 15000 }).catch(() => {});
  await v.pausa(300);
}
async function addAtividade(v, nome, comp, nota) {
  await v.clicar('#simAdd');
  const tr = v.page.locator('#simTabela tbody tr').last();
  await v.digitar(tr.locator('.s-nome'), nome);
  const val = await tr.locator('.s-comp option', { hasText: comp }).first().getAttribute('value');
  await v.escolher(tr.locator('.s-comp'), val);
  await v.digitar(tr.locator('.s-nota'), nota);
  await esperarSim(v);
}
module.exports = {
  conta: 'administrador',
  async gravar(v) {
    v.capitulo('O catálogo e as regras prontas');
    await v.ir('painel/regra-media/listar');
    await v.passo('Configurações → Regras de média: cada regra, os componentes, em uso por quantas disciplinas, e as ações.', { t: 'Regras de média', aviso: 'Olhe o catálogo.' });
    await v.destacar('#tabelaRegras');
    const pronta = v.page.locator('#tabelaRegras tr', { hasText: 'Prova mensal' }).first();
    v.confere(await pronta.count() > 0, 'a regra pronta "Prova mensal + prova bimestral + atividades" deveria estar no catálogo');
    await v.passo('Duas regras vêm prontas: "Só atividades" e "Prova mensal + prova bimestral + atividades". Nenhuma muda a regra de uma disciplina sozinha.', { t: 'As regras prontas', aviso: 'Abra a regra pronta para editar.' });
    await v.destacar(pronta);
    await v.clicarENavegar(pronta.locator('a[href*="regra-media/editar"]').first());
    v.confere(/regra-media\/editar\/\d+/.test(v.page.url()), 'a regra pronta deveria abrir');

    v.capitulo('Componentes e pesos');
    await v.passo('Os componentes, cada um com o peso e o modo. A soma dos pesos fica embaixo: fora de 100, o Salvar desliga.', { t: 'Os componentes' });
    await v.destacar('#tabelaComponentes');
    await v.destacar('#somaPesos');
    v.poster();

    v.capitulo('O acréscimo');
    await v.rolar('#blocoAcrescimo');
    await v.passo('Permitir acréscimo (o padrão é não): o professor lança um acréscimo, que soma à média fechada, até o limite.', { t: 'Permitir acréscimo', aviso: 'Marque **Permitir acréscimo** e informe o limite.' });
    if (!(await v.page.isChecked('#rAcrescimo'))) {
      const rot = (await v.page.$('label[for="rAcrescimo"]')) ? 'label[for="rAcrescimo"]' : '#rAcrescimo';
      await v.clicar(rot);
    }
    v.confere(await v.page.isChecked('#rAcrescimo'), 'o acréscimo deveria ficar marcado');
    await v.digitar('#rAcrescimoLimite', '0,5');
    await v.feito('Acréscimo permitido, até 0,5 ponto.');

    v.capitulo('O simulador');
    await v.rolar('#simulador');
    await v.passo('O simulador, ao lado: notas de teste, a média e a memória de cálculo na hora, pela conta do próprio sistema. Nada é gravado.', { t: 'O simulador', aviso: 'Lance notas de teste.' });
    while (await v.page.$('#simTabela tbody tr .s-tirar')) await v.clicar('#simTabela tbody tr .s-tirar');
    if (!(await v.page.isChecked('#simFinal'))) await v.clicar('#simFinal');
    const comps = await v.page.$$eval('#tabelaComponentes tr.linha-comp .c-nome', (is) => is.map((i) => i.value).filter(Boolean));
    let n = 0;
    for (const c of comps) { n++; await addAtividade(v, `${c} ${n}`, c, String(6 + (n % 3))); }
    const antes = await v.texto('#simMedia');
    await v.passo(`Com as notas, a média fica ${antes}. Agora, um acréscimo de 1,0 — maior que o limite.`, { t: 'A média sem o acréscimo', aviso: 'Digite o **Acréscimo**.' });
    await v.digitar('#simAcrescimo', '1');
    await esperarSim(v);
    const depois = await v.texto('#simMedia');
    v.confere(depois !== antes && depois !== '—', `o acréscimo deveria mudar a média (${antes} → ${depois})`);
    await v.passo(`A média vai a ${depois}: o acréscimo soma antes do arredondamento, até o limite de 0,5, e nunca passa de 10.`, { t: 'A média com o acréscimo' });
    await v.destacar('#simMedia');
    await v.destacar('#simMemoria');
    await v.feito('A memória de cálculo mostra a linha do acréscimo.');

    v.capitulo('Salvar');
    await v.rolar('#salvarRegra');
    await v.passo('Salvar a regra. Se ela está em uso, a tela mostra antes as disciplinas abertas que mudam. No lançamento, o tipo Acréscimo só aparece nas disciplinas com esta regra.', { t: 'Salvar', aviso: 'Clique em **Salvar**.' });
    await v.clicarENavegar('#salvarRegra');
    if (await v.page.isVisible('#confirmarSalvar').catch(() => false)) await v.clicarENavegar('#confirmarSalvar');
    v.confere(/salva|alterad|criada/i.test(await v.alerta()), 'a regra deveria ser salva: ' + (await v.alerta()));
    await v.feito('A regra foi salva, com o acréscimo.');
  },
};
