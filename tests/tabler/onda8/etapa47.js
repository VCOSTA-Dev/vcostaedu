/*
 * PROVA DA CORREÇÃO DO ENSAIO, B1 — os defeitos (D-120 a D-122). APAGA o banco de prova (instala de novo duas vezes).
 *   A. ensino superior (o banco do ciclo, com a demonstração da faculdade): a ficha financeira de um aluno abre (não dá
 *      mais 500; o próprio aluno é o pagador); o professor (prof.laura.demo) baixa o modelo de notas pelo link da tela de
 *      importar as notas (o link leva a oferta da atividade; o arquivo é o CSV, sem "Esta disciplina não é sua") e o de
 *      faltas pelo link da tela de faltas; o registro da instalação leva os módulos como valores simples, sem repetição;
 *   B. escola (a demonstração do colégio, pela tela): a ficha abre; o professor baixa os dois modelos;
 *   C. curso livre (a demonstração, pela tela, com o financeiro ligado pela tela dos recursos): a ficha abre; as telas de
 *      notas e de faltas não existem no curso livre (D-106) — o modelo é recusado pelo endereço;
 *   sabotagens: (a) a ficha de volta com o CPF em col_usuarios — a ficha do superior dá erro; (b) o recorte da oferta de
 *   volta no modelo (o banco) — o professor é recusado. O arquivo volta igual (sha256) e o recorte volta ao vazio.
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { spawnSync } = require('child_process');
const L = require('../lib');
const X = require('./_escola');

const ETAPA = 'onda8/etapa47';
const PHP = process.env.VCOSTAEDU_PHP || 'C:/xampp/php/php.exe';
const SENHA_ADM = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const FICHA = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'financeiro', 'AdmsFinFicha.php');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const { sql, um } = X;

function montar(tipo, rotulo) {
  const env = { ...process.env };
  delete env.VCOSTAEDU_RECURSOS;
  const r = spawnSync(process.execPath, [path.join(__dirname, '..', 'onda6', 'medir_demo.js'), tipo, rotulo], { encoding: 'utf8', env, maxBuffer: 1 << 24 });
  if (r.status !== 0) console.log((r.stdout || '') + (r.stderr || ''));
  return r.status;
}

/** A ficha financeira do aluno pelo administrador: {st, pagador, erro}. */
async function ficha(navegador, aluno) {
  const s = await X.entrar(navegador, 'administrador', SENHA_ADM, 'painel', 1366);
  const r = await s.p.goto(L.BASE + `painel/financeiro/ficha/${aluno}`);
  const t = ((await s.p.textContent('body').catch(() => '')) || '').replace(/\s+/g, ' ');
  await L.print(s.p, ETAPA, `ficha-${aluno}`);
  await s.ctx.close();
  return { st: r ? r.status() : 0, pagador: /O próprio aluno \(pagador\)|Responsáve/.test(t), erro: /Fatal error|Uncaught|SQLSTATE/.test(t) };
}

/** Os dois modelos pelo professor, pelos links das telas: {notas: {st, tipo, csv, href}, faltas: {...}}. */
async function modelos(navegador, login, senha) {
  const prof = um(`SELECT id FROM adms_usuarios WHERE usuario = '${login}'`);
  /* uma atividade de uma oferta aberta, com as faltas lançadas à mão (a da chamada não tem a importação de faltas) */
  const ativ = um(`SELECT a.id FROM rm_disciplina_atividade a JOIN rm_professores_mat_liga l ON l.rm_prof_mat_id = a.rm_prof_mat_id
    JOIN rm_professores_materias o ON o.id = a.rm_prof_mat_id
    WHERE l.usuario_id = ${prof} AND l.acesso = 1 AND l.situacao = 1 AND o.encerrada_em IS NULL AND IFNULL(o.frequencia_fonte, 'lancamento') <> 'chamada'
    ORDER BY a.id DESC LIMIT 1`);
  const oferta = um(`SELECT rm_prof_mat_id FROM rm_disciplina_atividade WHERE id = ${ativ}`);
  const s = await X.entrar(navegador, login, senha, 'painel', 1366);
  const baixar = async (rota, sel) => {
    await s.p.goto(L.BASE + 'painel/' + rota);
    const href = await s.p.getAttribute(sel, 'href').catch(() => null);
    if (!href) return { st: 0, href: '', csv: false, tipo: '' };
    const r = await s.p.request.get(href.startsWith('http') ? href : L.BASE + href.replace(/^\//, ''), { maxRedirects: 0 });
    const corpo = (await r.text()).replace(/^\uFEFF/, '');
    return { st: r.status(), href, tipo: r.headers()['content-type'] || '', csv: /text\/csv/.test(r.headers()['content-type'] || '') && corpo.split('\n').length >= 2 };
  };
  const notas = await baixar(`atividades/upload-notas/${ativ}`, '#modeloNotas');
  const faltas = await baixar(`faltas/visualizar/${oferta}`, '#modeloFaltas');
  await L.print(s.p, ETAPA, `faltas-${login}`);
  await s.ctx.close();
  return { notas, faltas, oferta, ativ };
}
const baixou = (m) => m.st === 200 && m.csv;

(async () => {
  const { navegador } = await L.abrir();
  const shaF = sha(FICHA);
  try {
    /* A. o ensino superior */
    L.ok(sql("SELECT valor FROM adms_instalacao_estado WHERE chave = 'instituicao.tipo'") === 'superior', 'A. o banco do ciclo é de ensino superior, com a demonstração da faculdade');
    const credS = JSON.parse(fs.readFileSync(path.join(L.RAIZ, 'tests', 'tabler', 'saida', 'credenciais.json'), 'utf8'));
    const alunoS = um("SELECT id FROM col_usuarios WHERE col_niveis_acesso_id = 1 AND rm LIKE '2601%' ORDER BY id LIMIT 1");
    const fS = await ficha(navegador, alunoS);
    L.ok(fS.st === 200 && fS.pagador && !fS.erro, `superior: a ficha financeira do aluno ${alunoS} abre (HTTP ${fS.st}; o próprio aluno, o pagador)`);
    const mS = await modelos(navegador, 'prof.laura.demo', credS.painel['prof.laura.demo']);
    L.ok(baixou(mS.notas) && mS.notas.href.includes(`faltas/modelo/${mS.oferta}?tipo=notas`) && baixou(mS.faltas),
      `superior: o professor baixa o modelo de notas (o link leva a oferta ${mS.oferta}: ${mS.notas.st}, ${mS.notas.tipo}) e o de faltas (${mS.faltas.st})`);
    const sr = await X.entrar(navegador, 'administrador', SENHA_ADM, 'painel', 1366);
    await sr.p.goto(L.BASE + 'painel/registro-instalacao/index');
    const mods = await sr.p.textContent('#conteudoRegistro').then((t) => JSON.parse(t).modulos).catch(() => null);
    await L.print(sr.p, ETAPA, 'registro');
    await sr.ctx.close();
    L.ok(Array.isArray(mods) && mods.length > 0 && mods.every((m) => /^[a-z]+$/.test(m)) && new Set(mods).size === mods.length,
      `o registro da instalação leva os módulos como valores simples, sem repetição (${JSON.stringify(mods)})`);

    /* sabotagem (a): o CPF de volta em col_usuarios */
    const orig = fs.readFileSync(FICHA, 'utf8');
    const trecho = 'u.celular, i.cpf, u.email';
    if (!orig.includes(trecho)) { throw new Error('a sabotagem (a) não achou o trecho'); }
    let sabA = null;
    try {
      fs.writeFileSync(FICHA, orig.replace(trecho, 'u.celular, u.cpf, u.email'));
      sabA = await ficha(navegador, alunoS);
    } finally {
      fs.writeFileSync(FICHA, orig);
    }
    L.ok(sabA && (sabA.st >= 500 || sabA.erro || !sabA.pagador), `sabotagem (a) (o CPF em col_usuarios): a ficha do superior quebra (HTTP ${sabA && sabA.st}) — a prova reprova`);
    L.ok(sha(FICHA) === shaF, 'o arquivo sabotado voltou igual (sha256)');

    /* sabotagem (b): o recorte da oferta de volta no modelo */
    sql("UPDATE adms_paginas SET escopo = 'oferta' WHERE controller = 'Faltas' AND metodo = 'modelo'");
    let sabB = null;
    try {
      const s = await X.entrar(navegador, 'prof.laura.demo', credS.painel['prof.laura.demo'], 'painel', 1366);
      await s.p.goto(L.BASE + 'painel/faltas/modelo/0?tipo=notas');
      sabB = { recusado: !!(await s.p.$('#foraDoEscopo')) };
      await s.ctx.close();
    } finally {
      sql("UPDATE adms_paginas SET escopo = NULL WHERE controller = 'Faltas' AND metodo = 'modelo'");
    }
    L.ok(sabB && sabB.recusado && sql("SELECT IFNULL(escopo, '') FROM adms_paginas WHERE controller = 'Faltas' AND metodo = 'modelo'") === '',
      'sabotagem (b) (o recorte da oferta de volta no modelo): o professor é recusado ("Esta disciplina não é sua") — a prova reprova; o recorte voltou ao vazio');

    /* B. a escola */
    L.ok(montar('escola', 'onda8-etapa47') === 0, 'B. a escola instalada do zero, com a demonstração do colégio pela tela');
    const cE = X.cred();
    const alunoE = um("SELECT id FROM col_usuarios WHERE rm = '265001'");
    const fE = await ficha(navegador, alunoE);
    L.ok(fE.st === 200 && !fE.erro, `escola: a ficha financeira do aluno abre (HTTP ${fE.st})`);
    /* o professor da demonstração com uma oferta aberta de faltas lançadas à mão (a Matemática do fundamental está na chamada) */
    const temOferta = (login) => um(`SELECT COUNT(*) FROM rm_disciplina_atividade a JOIN rm_professores_mat_liga l ON l.rm_prof_mat_id = a.rm_prof_mat_id
      JOIN rm_professores_materias o ON o.id = a.rm_prof_mat_id JOIN adms_usuarios u ON u.id = l.usuario_id
      WHERE u.usuario = '${login}' AND l.acesso = 1 AND l.situacao = 1 AND o.encerrada_em IS NULL AND IFNULL(o.frequencia_fonte, 'lancamento') <> 'chamada'`) > 0;
    const profE = Object.keys(cE.painel).find((k) => /^prof\./.test(k) && temOferta(k));
    const mE = await modelos(navegador, profE, cE.painel[profE]);
    L.ok(baixou(mE.notas) && mE.notas.href.includes(`faltas/modelo/${mE.oferta}?tipo=notas`) && baixou(mE.faltas),
      `escola: o professor (${profE}) baixa o modelo de notas (oferta ${mE.oferta}: ${mE.notas.st}) e o de faltas (${mE.faltas.st})`);

    /* C. o curso livre, com o financeiro ligado */
    L.ok(montar('livre', 'onda8-etapa47-livre') === 0, 'C. o curso livre instalado do zero, com a demonstração pela tela');
    const adm = await X.entrar(navegador, 'administrador', SENHA_ADM, 'painel', 1366);
    await adm.p.goto(L.BASE + 'painel/recursos-livre/index');
    await adm.p.check('#recurso_financeiro');
    await Promise.all([adm.p.waitForNavigation(), adm.p.click('#salvarRecursos')]);
    await adm.ctx.close();
    L.ok(sql("SELECT valor FROM adms_instalacao_estado WHERE chave = 'livre.financeiro'") === '1', 'o financeiro ligado pela tela dos recursos do curso livre');
    const cL = X.cred();
    const alunoL = um(`SELECT id FROM col_usuarios WHERE col_niveis_acesso_id = 1 ORDER BY id LIMIT 1`);
    const fL = await ficha(navegador, alunoL);
    L.ok(fL.st === 200 && fL.pagador && !fL.erro, `curso livre: a ficha financeira do aluno abre (HTTP ${fL.st}; o próprio aluno, o pagador)`);
    const profL = Object.keys(cL.painel || {}).find((k) => /^prof/.test(k));
    let livre = 'sem professor na demonstração';
    if (profL) {
      const s = await X.entrar(navegador, profL, cL.painel[profL], 'painel', 1366);
      await s.p.goto(L.BASE + 'painel/faltas/modelo/1?tipo=notas');
      livre = (await s.p.$('#semAcesso')) ? 'recusado' : 'abriu';
      await s.ctx.close();
    }
    L.ok(livre === 'recusado', `curso livre: as telas de notas e de faltas não existem (D-106) — o modelo é recusado pelo endereço (${livre})`);
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(FICHA) !== shaF) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    sql("UPDATE adms_paginas SET escopo = NULL WHERE controller = 'Faltas' AND metodo = 'modelo'");
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
