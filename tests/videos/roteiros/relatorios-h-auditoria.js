// Guia: Relatórios H — auditoria e governança (administrador). Reaberturas de oferta, trocas de regra, exportações (com o
// link dos filtros) e o último acesso dos usuários. A preparação inventa uma reabertura e uma troca de regra; o retrato desfaz.
const fs = require('fs');
const path = require('path');

async function baixar(v, sel) {
  const pasta = path.join(__dirname, '..', 'saida'); fs.mkdirSync(pasta, { recursive: true });
  const [d] = await Promise.all([v.page.waitForEvent('download', { timeout: 30000 }), v.clicar(sel)]);
  await d.saveAs(path.join(pasta, d.suggestedFilename()));
  return d.suggestedFilename();
}

module.exports = {
  conta: 'administrador',
  sql: ids => {
    const fap = ids.oferta.FAP, goc = ids.oferta.GOC, sec = ids.usuario['secretaria.demo'], adm = ids.usuario['administrador'];
    return `SET @fechou = (SELECT fechado_em FROM rm_media_fechamentos WHERE rm_prof_mat_id = ${fap} AND numero = 1);
INSERT INTO rm_media_fechamentos (rm_prof_mat_id, numero, regra_versao_id, regra_origem, regra_json, regra_hash, situacao, fechado_por, fechado_em)
SELECT rm_prof_mat_id, 2, regra_versao_id, regra_origem, regra_json, regra_hash, 'vigente', fechado_por, @fechou + INTERVAL 20 MINUTE FROM rm_media_fechamentos WHERE rm_prof_mat_id = ${fap} AND numero = 1;
UPDATE rm_media_fechamentos SET situacao = 'substituido', reaberto_por = ${sec}, reaberto_em = @fechou + INTERVAL 10 MINUTE,
  justificativa_reabertura = 'Nota da atividade 2 lançada errada para um aluno (demonstração).' WHERE rm_prof_mat_id = ${fap} AND numero = 1;
INSERT INTO rm_professores_materias_encerramentos (rm_prof_mat_id, acao, observacao, usuario_id, created) VALUES
  (${fap}, 'reabrir', 'Nota da atividade 2 lançada errada para um aluno (demonstração).', ${sec}, @fechou + INTERVAL 10 MINUTE),
  (${fap}, 'encerrar', NULL, ${sec}, @fechou + INTERVAL 20 MINUTE);
INSERT INTO rm_media_regra_trocas (rm_prof_mat_id, regra_anterior_id, regra_nova_id, justificativa, usuario_id, created)
  VALUES (${goc}, NULL, 1, 'A turma segue a regra padrão da instituição (demonstração).', ${adm}, NOW() - INTERVAL 30 MINUTE);
UPDATE rm_professores_materias SET regra_media_id = 1 WHERE id = ${goc};`;
  },
  async gravar(v) {
    const curso = String(v.ids.curso);
    const filtrar = () => v.clicarENavegar('#filtrosRelatorio button[type="submit"]');

    v.capitulo('Reaberturas de oferta');
    await v.ir('painel/relatorios/listar');
    await v.passo('Relatórios → H. Auditoria: quem mudou o quê, quando e por quê. Só o administrador vê estes relatórios.');
    await v.destacar('.page-wrapper .card:has(a[href$="relatorios/reaberturas"])');
    await v.clicarENavegar('.page-wrapper a[href$="relatorios/reaberturas"]');
    await v.escolher('#f_curso', curso);
    await filtrar();
    const reab = v.page.locator('table.lista-dados tbody tr').first();
    v.confere(/demonstração/.test(await reab.textContent()), 'a reabertura deveria aparecer com a justificativa');
    v.poster();
    await v.passo('Reaberturas: cada fechamento substituído, com quem encerrou, quem reabriu, quando e a justificativa.');
    await v.destacar(reab);
    await v.passo('Toda tabela de relatório exporta. Exporto esta em CSV: a exportação fica registrada.');
    const arq = await baixar(v, 'button.buttons-csv');
    v.confere(/\.csv$/.test(arq), 'o CSV deveria ser baixado: ' + arq);

    v.capitulo('Trocas e edições de regra');
    await v.ir('painel/relatorios/trocas-regra');
    const troca = v.page.locator('.card:has(.card-title:has-text("Trocas de regra por oferta")) table.lista-dados tbody tr').first();
    v.confere(/Padrão da instituição/.test(await troca.textContent()), 'a troca de regra deveria aparecer');
    await v.passo('Trocas de regra por oferta (anterior, nova, justificativa, quem) e as criações e edições de regra no catálogo.');
    await v.destacar(troca);

    v.capitulo('Exportações');
    await v.ir('painel/relatorios/exportacoes');
    const exp = v.page.locator('table.lista-dados tbody tr', { hasText: 'R-AUD-01' }).first();
    v.confere(await exp.count() && /csv/.test(await exp.textContent()), 'a exportação CSV deveria estar registrada');
    await v.passo('Exportações: cada Excel, CSV, PDF ou impressão, com quem, quando, o relatório, os filtros e quantas linhas.');
    await v.destacar(exp);
    await v.passo('O link dos filtros abre o relatório como foi exportado.');
    await v.clicarENavegar(exp.locator('a[href*="relatorios/reaberturas?"]'));
    v.confere(/reaberturas\?.*curso=/.test(v.page.url()) && await v.page.$eval('#f_curso', s => s.value) === curso, 'o link deveria abrir as reaberturas com o filtro de curso');
    await v.pausa(1500);

    v.capitulo('Usuários e último acesso');
    await v.ir('painel/relatorios/usuarios-acesso');
    const adm = v.page.locator('table.lista-dados tbody tr', { hasText: 'administrador' }).first();
    v.confere(await adm.count() && !/nunca/.test(await adm.textContent()), 'o administrador deveria ter o último acesso registrado');
    await v.passo('Usuários e último acesso: usuários por perfil, o último login e quem, ativo, não entra há mais de N dias.');
    await v.destacar(adm);
    await v.passo('O padrão é 90 dias; mude em "Sem acesso há mais de (dias)". Use para inativar acessos parados.');
    await v.destacar('#f_dias');
    await v.destacar('.vc-cartao-rel:has(a[href*="detalhe=parados"]), .vc-cartao-rel:has(.text-secondary:has-text("Ativos sem acesso"))');
  },
};
