// Guia: Vincular e enturmar (secretaria). Um aluno inventado, sem vínculo: a recusa sem data de ingresso, o vínculo,
// a sugestão da previsão de conclusão e a enturmação sugerida (encerradas bloqueadas). O retrato desfaz tudo.
const AL = 9001, RA = '260191';
module.exports = {
  conta: 'secretaria.demo',
  sql: `INSERT INTO col_usuarios (id, rm, nome, genero_id, genero_confirmado_em, usuario, senha, email, col_niveis_acesso_id, col_sits_usuario_id, usuario_cre, created)
        VALUES (${AL}, '${RA}', 'Renata Ilustrativa Prado', 3, NOW(), '${RA}', '-inexistente', 'renata.video@exemplo.invalid', 1, 1, 5, NOW());`,
  async gravar(v) {
    const aba = async () => { await v.clicar('#tabeMatricula-tab'); await v.pausa(500); };
    const vincular = async data => {
      await v.escolher('#vAno', '2026');
      const g = await v.page.$$eval('#vModulo option', os => (os.find(o => /Gestão de Projetos/.test(o.textContent)) || {}).value);
      await v.escolher('#vModulo', g);
      await v.destacar('#vForma');
      await v.destacar('#vData'); await v.page.fill('#vData', data);
      await v.destacar('#vPolo');
      await v.destacar('#vModalidade');
      await v.clicarENavegar('form[action*="academicos/vincular"] button[type="submit"]');
    };

    v.capitulo('Vincular ao curso');
    await v.ir(`painel/academicos/visualizar/${AL}`);
    await aba();
    await v.passo('Ficha da aluna, aba Matrículas: ela ainda não tem vínculo. Use "Vincular a um curso".');
    await v.destacar('#semVinculo');
    await v.passo('Ano, curso — grade, forma e data de ingresso, polo e modalidade (vazio = os do curso). Sem a data, veja:');
    await vincular('');
    v.confere(/Informe a data de ingresso/i.test(await v.alerta()), 'sem data de ingresso deveria recusar: ' + await v.alerta());
    await v.destacar('.alert');
    await v.passo('Recusado: "Informe a data de ingresso." Preencha e vincule de novo.');
    await aba();
    await vincular('2026-02-02');
    v.confere(/vinculad/i.test(await v.alerta()), 'a aluna deveria ser vinculada: ' + await v.alerta());
    await aba();
    await v.passo('Vinculada. A modalidade da matrícula escolhe o ato legal do histórico.');
    await v.destacar('.vinculo .card-header');

    v.capitulo('Previsão de conclusão');
    const sug = v.page.locator('.vinculo .usarSugestao').first();
    v.confere(await sug.count(), 'a edição da matrícula deveria mostrar "Usar a sugestão"');
    await v.passo('Na edição da matrícula, "Usar a sugestão" preenche a previsão com a maior data de fim das ofertas da grade.');
    await v.clicar(sug);
    await v.clicarENavegar('form.formVinculo button[type="submit"]');
    await aba();
    v.confere(/de 20\d\d/.test(await v.texto('.previsaoMesAno')), 'a previsão deveria sair como mês de ano: ' + await v.texto('.previsaoMesAno'));
    await v.destacar('.previsaoMesAno');
    await v.passo(`Ela sai nos documentos como "${await v.texto('.previsaoMesAno')}".`);

    v.capitulo('Enturmação');
    await v.passo('Clique no botão Enturmação do ano 2026.');
    await v.clicarENavegar('.vinculo tr.posicao[data-ano="2026"] a:has-text("Enturmação")');
    const enc = v.page.locator('tr.ofertaEnturmacao:has(input:disabled)');
    v.confere(await enc.count() >= 1, 'as ofertas encerradas deveriam aparecer bloqueadas');
    v.poster();
    await v.passo('As ofertas abertas da grade vêm sugeridas e marcadas. As já encerradas aparecem bloqueadas: o administrador reabre.');
    await v.destacar(enc.first());
    const stf = v.page.locator('tr.ofertaEnturmacao', { hasText: 'Trabalho de Conclusão' }).locator('input[name="ofertas[]"]');
    v.confere(await stf.isChecked(), 'o TCC (aberto) deveria vir marcado');
    await v.passo('Desmarque o que ela cursará depois (aqui, o TCC) e clique em "Confirmar enturmação". Nada grava antes.');
    await v.destacar(stf); await stf.uncheck();
    await v.clicarENavegar('button:has-text("Confirmar enturmação")');
    v.confere(/confirmada/i.test(await v.alerta()), 'a enturmação deveria ser confirmada: ' + await v.alerta());
    await v.destacar('.alert');
    await v.passo('Confirmada: agora ela aparece para o professor de Liderança de Equipes. Num ano seguinte, use "Registrar no ano".');
  },
};
