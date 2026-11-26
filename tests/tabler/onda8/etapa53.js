/*
 * PROVA DA 1.103.0, C7 — a compensação de ausência liberável ao professor (D-130). APAGA o banco de prova (a escola do
 * zero, com a demonstração pela tela).
 *   1. de fábrica, o professor não lança compensação (o POST direto é recusado; nada gravado);
 *   2. a ação "Lançar compensação de ausência" aparece em Perfis de acesso; ligada para o perfil do professor, ele lança na
 *      disciplina dele e é recusado na de outro professor;
 *   sabotagem: o professor liberado em qualquer disciplina — a compensação na de outro passa: a prova reprova; o arquivo
 *   volta igual (sha256).
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const L = require('../lib');
const X = require('./_escola');

const ETAPA = 'onda8/etapa53';
const SENHA_ADM = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const COMP = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'faltas', 'AdmsCompensacoes.php');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const { sql, um } = X;
const texto = async (p, sel) => ((await p.textContent(sel).catch(() => '')) || '').replace(/\s+/g, ' ').trim();

(async () => {
  const { navegador } = await L.abrir();
  const shaCOMP = sha(COMP);
  try {
    L.ok(X.montar('onda8-etapa53').status === 0, 'a escola instalada do zero, com a demonstração do colégio pela tela');
    const c = X.cred();
    /* ===== C7 (D-130): a compensação de ausência liberável ao professor ===== */
    const profC = 'prof.marcos.demo';
    const pc = um(`SELECT id FROM adms_usuarios WHERE usuario = '${profC}'`);
    const nivelProf = um(`SELECT adms_niveis_acesso_id FROM adms_usuarios WHERE id = ${pc}`);
    /* uma oferta aberta dele com um aluno com falta, e uma de outro professor (a mesma situação) */
    const comFalta = (cond) => sql(`SELECT CONCAT(o.id, '|', f.rm) FROM rm_professores_materias o JOIN rm_disciplina_faltas f ON f.rm_prof_mat_id = o.id
       WHERE o.encerrada_em IS NULL AND IFNULL(o.frequencia_fonte, 'lancamento') <> 'chamada' AND f.faltas >= 1 AND ${cond} ORDER BY o.id LIMIT 1`).split('|');
    const [ofDele, rmDele] = comFalta(`o.id IN (SELECT rm_prof_mat_id FROM rm_professores_mat_liga WHERE usuario_id = ${pc} AND situacao = 1)`);
    const [ofOutra, rmOutra] = comFalta(`o.id NOT IN (SELECT rm_prof_mat_id FROM rm_professores_mat_liga WHERE usuario_id = ${pc})`);
    const comps = () => um('SELECT COUNT(*) FROM rm_faltas_compensacoes');
    const hoje = X.hoje();
    const compensar = async (pg, of, rmA) => pg.evaluate(async ([u, c]) => {
      const r = await fetch(u, { method: 'POST', credentials: 'same-origin', body: new URLSearchParams(c) }); return r.status;
    }, [L.BASE + 'painel/faltas/compensar/' + of, { rm: rmA, quantidade: '1', tipo: 'atestado', justificativa: 'Atestado médico de prova', inicio: hoje, fim: hoje }]);
    const pcs = await X.entrar(navegador, profC, c.painel[profC], 'painel', 1366);
    let n0 = comps();
    await compensar(pcs.p, ofDele, rmDele);
    await pcs.p.goto(L.BASE + 'painel/faltas/visualizar/' + ofDele);
    const semBotao = (await pcs.p.$$('[data-bs-target*="ompens"], .vc-compensar, button[title*="ompens"]')).length;
    L.ok(+ofDele > 0 && +ofOutra > 0 && comps() === n0, `C7: de fábrica, o professor não lança compensação (o POST direto é recusado; nada gravado)`);
    /* a instituição liga a ação para o perfil do professor (a matriz das permissões, que a tela Perfis de acesso grava) */
    await pcs.ctx.close();
    const a2 = await X.entrar(navegador, 'administrador', SENHA_ADM, 'painel', 1366);
    await a2.p.goto(L.BASE + 'painel/perfis-acesso/index');
    const telaPerfis = await texto(a2.p, 'body');
    await a2.ctx.close();
    sql(`UPDATE adms_nivacs_pgs SET permissao = 1 WHERE adms_pagina_id IN (410, 411) AND adms_niveis_acesso_id = ${nivelProf}`);
    const pc2 = await X.entrar(navegador, profC, c.painel[profC], 'painel', 1366);
    n0 = comps();
    await compensar(pc2.p, ofDele, rmDele);
    const naDele = comps() === n0 + 1;
    n0 = comps();
    await compensar(pc2.p, ofOutra, rmOutra);
    const naOutra = comps() === n0;
    await pc2.p.goto(L.BASE + 'painel/faltas/visualizar/' + ofDele);
    await L.print(pc2.p, ETAPA, 'compensacao-professor');
    L.ok(/Lançar compensação de ausência/.test(telaPerfis) && naDele && naOutra,
      `C7: a ação "Lançar compensação de ausência" em Perfis de acesso; ligada para o professor, ele lança na disciplina dele (${naDele}) e é recusado na de outro professor (${naOutra})`);
    /* sabotagem: o professor liberado em qualquer disciplina */
    const shaC = sha(COMP);
    const origC = fs.readFileSync(COMP, 'utf8');
    const trechoC = "return !self::professorLiberado() || \\App\\adms\\Models\\helper\\AdmsEscopo::podeOferta($ofertaId, true);";
    if (!origC.includes(trechoC)) { throw new Error('a sabotagem (C7) não achou o trecho'); }
    let sabC = false;
    try {
      fs.writeFileSync(COMP, origC.replace(trechoC, 'return true;'));
      n0 = comps();
      await compensar(pc2.p, ofOutra, rmOutra);
      sabC = comps() === n0 + 1;
    } finally {
      fs.writeFileSync(COMP, origC);
    }
    await pc2.ctx.close();
    sql(`UPDATE adms_nivacs_pgs SET permissao = 2 WHERE adms_pagina_id IN (410, 411) AND adms_niveis_acesso_id = ${nivelProf}`);
    L.ok(sabC, 'sabotagem (C7: o professor liberado em qualquer disciplina): a compensação na disciplina de outro professor passa — a prova reprova');
    L.ok(sha(COMP) === shaC, 'o arquivo sabotado (C7) voltou igual (sha256)');
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split(String.fromCharCode(10))[0]);
  } finally {
    if (sha(COMP) !== shaCOMP) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
