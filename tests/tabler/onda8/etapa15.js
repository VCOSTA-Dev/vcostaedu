/*
 * PROVA DA ONDA 8, CORREÇÃO, SEGUNDA LEVA, 2.1 — os documentos na escola: a secretaria emite o atestado (D-87). APAGA o
 * banco de prova.
 *   1. a demonstração do colégio, montada pela tela numa instalação nova: o atestado da demonstração é emitido pela
 *      secretaria, pelo painel (a origem "secretaria");
 *   2. a secretaria, na lista de alunos, clica em "Emitir atestado de matrícula" de outro aluno: a tela de emissão abre
 *      (não a de login), emite, e a emissão tem o código de verificação e o PDF; a verificação pública confere o código;
 *   3. o mesmo modelo do portal: o aluno emite o dele pelo portal; o modelo e o formato do código são os mesmos;
 *   4. na escola, a emissão pelo painel é só do atestado: o pedido de um histórico (parcial ou final) não grava nada;
 *   5. na ficha do aluno (todas as abas) e na lista de alunos, nenhum botão nem link leva à tela de login nem à página sem
 *      acesso; os botões "Prévia do histórico", "Emitir parcial" e "Emitir final" não aparecem;
 *   sabotagem: os botões sem conferir o módulo da página — a prova acusa; o arquivo volta igual.
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const L = require('../lib');
const X = require('./_escola');

const ETAPA = 'onda8/etapa15';
const BOTAO = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'AdmsBotao.php');
const FICHA = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Views', 'academicos', 'verAcademico.php');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');

/** Os links e botões da página: aonde cada um leva (o GET, seguindo os redirecionamentos). */
async function destinos(p) {
  const hrefs = await p.$$eval('.page-body a[href]', (l) => [...new Set(l.map((a) => a.href).filter((h) => /^https?:/.test(h) && !/\/(sair|logout)|apagar|excluir|remover|desvincular|cancelar|trancar|reabrir/.test(h) && !/#/.test(h.split('/').pop() || '')))]);
  const ruins = [];
  for (const h of hrefs) {
    const r = await p.evaluate(async (u) => {
      const x = await fetch(u, { credentials: 'same-origin' });
      const tipo = x.headers.get('content-type') || '';
      const t = /html/.test(tipo) ? await x.text() : '';
      return { st: x.status, url: x.url, login: /name="SendLogin"/.test(t), sem: /id="semAcesso"/.test(t) };
    }, h);
    if (r.login || r.sem || r.st === 403 || /login\/acesso/.test(r.url)) { ruins.push(h.replace(L.BASE, '') + (r.login ? ' (login)' : r.sem ? ' (sem acesso)' : ` (${r.st})`)); }
  }
  return { total: hrefs.length, ruins };
}
const textos = (p) => p.evaluate(() => document.body.innerText);

(async () => {
  const { navegador } = await L.abrir();
  const shaBotao = sha(BOTAO);
  const shaFicha = sha(FICHA);
  try {
    L.ok(X.montar('onda8-etapa15').status === 0, 'a demonstração do colégio montada pela tela, numa instalação nova');
    const c = X.cred();
    const daDemo = X.sql("SELECT CONCAT(e.origem, '|', u.rm) FROM rm_historico_emissoes e JOIN col_usuarios u ON u.id = e.aluno_id WHERE e.tipo = 'ATESTADO' ORDER BY e.id LIMIT 1");
    L.ok(/^secretaria\|265001$/.test(daDemo), `o atestado da demonstração emitido pela secretaria, pelo painel (${daDemo})`);

    /* 2. pela lista de alunos */
    const sec = await X.entrar(navegador, 'secretaria.demo', c.painel['secretaria.demo']);
    const ra = '265002';
    const aluno = X.um(`SELECT id FROM col_usuarios WHERE rm = '${ra}'`);
    await sec.p.goto(L.BASE + `painel/academicos/listar?busca=${ra}`);
    const botao = await sec.p.$(`a[href*="historico-emissoes/emitir/"][href*="ATESTADO"]`);
    if (!botao) { throw new Error('o botão "Emitir atestado de matrícula" não está na lista de alunos'); }
    await Promise.all([sec.p.waitForNavigation(), botao.click()]);
    const naEmissao = /historico-emissoes\/emitir/.test(sec.p.url()) && !(await sec.p.$('[name="SendLogin"]'));
    await L.print(sec.p, ETAPA, 'emitir-atestado-secretaria');
    await Promise.all([sec.p.waitForNavigation(), sec.p.click('input[name="EmitirHistorico"]')]);
    const em = X.sql(`SELECT CONCAT(id, '|', codigo, '|', modelo_id, '|', origem) FROM rm_historico_emissoes WHERE aluno_id = ${aluno} AND tipo = 'ATESTADO' ORDER BY id DESC LIMIT 1`).split('|');
    const pdf = await sec.p.evaluate(async (u) => { const x = await fetch(u, { credentials: 'same-origin' }); return [x.status, x.headers.get('content-type')]; }, L.BASE + `painel/historico-emissoes/pdf/${em[0]}`);
    const verif = await sec.p.evaluate(async (u) => (await (await fetch(u)).text()), L.BASE + `verificar/${em[1]}`);
    L.ok(naEmissao && em[3] === 'secretaria' && /emitido/i.test(await textos(sec.p)) && pdf[0] === 200 && /pdf/.test(pdf[1] || '') && verif.includes(em[1]) && !/não encontrad/i.test(verif),
      `a secretaria emite pela lista de alunos: a tela de emissão abriu (não o login), a emissão ${em[1]} com o PDF (${pdf[0]}) e a verificação pública confere`);

    /* 3. o mesmo modelo do portal */
    const ra3 = '265003';
    const al = await X.entrar(navegador, ra3, c.portal[ra3], 'portal');
    await al.p.goto(L.BASE + 'portal/atestado/index');
    await Promise.all([al.p.waitForNavigation(), al.p.click('button[name="EmitirAtestado"]')]);
    await al.ctx.close();
    const pt = X.sql(`SELECT CONCAT(e.codigo, '|', e.modelo_id, '|', e.origem) FROM rm_historico_emissoes e JOIN col_usuarios u ON u.id = e.aluno_id WHERE u.rm = '${ra3}' AND e.tipo = 'ATESTADO' ORDER BY e.id DESC LIMIT 1`).split('|');
    /* o código é aleatório (letras e algarismos misturados): o mesmo desenho, três grupos de 4 de A-Z e 0-9 */
    const formato = (s) => (/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/.test(s || '') ? 'XXXX-XXXX-XXXX' : `fora do desenho (${s})`);
    L.ok(pt[2] === 'portal' && pt[1] === em[2] && formato(pt[0]) === formato(em[1]),
      `o mesmo modelo e o mesmo código do portal: o modelo ${pt[1]} (portal) e ${em[2]} (secretaria); o código ${formato(pt[0])} e ${formato(em[1])}; a origem do portal "${pt[2]}"`);

    /* 4. só o atestado na escola */
    const mc = X.um(`SELECT id FROM rm_matriculas_curso WHERE usuario_id = ${aluno} AND situacao_id = 1 ORDER BY id DESC LIMIT 1`);
    for (const tipo of ['PARCIAL', 'FINAL']) {
      await sec.p.evaluate(async ([u, t]) => fetch(u, { method: 'POST', credentials: 'same-origin', body: new URLSearchParams({ tipo: t, modelo: '0', EmitirHistorico: '1', confirmo_alertas: '1' }) }), [L.BASE + `painel/historico-emissoes/emitir/${mc}`, tipo]);
    }
    const historicos = X.um(`SELECT COUNT(*) FROM rm_historico_emissoes WHERE aluno_id = ${aluno} AND tipo <> 'ATESTADO'`);
    await sec.p.goto(L.BASE + `painel/historico-emissoes/emitir/${mc}?tipo=FINAL`);
    const telaFinal = await textos(sec.p);
    L.ok(historicos === 0 && /atestado/i.test(telaFinal) && !/histórico FINAL/i.test(telaFinal), `na escola, o painel emite só o atestado: o pedido de histórico parcial ou final não gravou nada (${historicos}) e a tela mostra o atestado`);

    /* 5. nenhum botão leva ao login nem à página sem acesso */
    const ruins = [];
    let total = 0;
    let proibidos = [];
    for (const rota of [`painel/academicos/listar?busca=${ra}`, `painel/academicos/visualizar/${aluno}`]) {
      await sec.p.goto(L.BASE + rota);
      const d = await destinos(sec.p);
      total += d.total;
      ruins.push(...d.ruins);
      const t = await textos(sec.p);
      proibidos = proibidos.concat(['Prévia do histórico', 'Emitir parcial', 'Emitir final'].filter((x) => t.includes(x)));
    }
    await L.print(sec.p, ETAPA, 'ficha-do-aluno-escola');
    L.ok(ruins.length === 0 && proibidos.length === 0, `na lista e na ficha do aluno, ${total} links e botões: nenhum leva ao login nem à página sem acesso${ruins.length ? ' — ' + ruins.join('; ') : ''}; sem prévia, parcial e final${proibidos.length ? ' — apareceu: ' + proibidos.join(', ') : ''}`);
    await sec.ctx.close();

    /* sabotagem: os botões sem o módulo da página. Desde a 1.75.0 a ficha da escola usa o cartão de matrícula da escola
       (vinculoEscola.php), que não tem os botões do ensino superior (a prévia do histórico, o TCC): para a trava do botão
       ficar à vista, a ficha passa a usar o cartão do ensino superior. (a) com a trava, nenhum botão leva à página sem
       acesso; (b) sem ela, a prévia aparece e leva — a prova reprova. */
    const ficha = fs.readFileSync(FICHA, 'utf8');
    const orig = fs.readFileSync(BOTAO, 'utf8');
    const deFicha = "\\App\\adms\\Models\\helper\\AdmsTipoInstituicao::porTurma() ? '/vinculoEscola.php' : '/vinculoAluno.php'";
    const trecho = '|| !\\App\\adms\\Models\\helper\\AdmsTipoInstituicao::paginaLigada((int) $achou[0][\'id_pg\'])';
    if (ficha.split(deFicha).length !== 2) { throw new Error('a sabotagem não achou o trecho da ficha'); }
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    const visita = async () => {
      const s = await X.entrar(navegador, 'secretaria.demo', c.painel['secretaria.demo']);
      await s.p.goto(L.BASE + `painel/academicos/visualizar/${aluno}`);
      const d = await destinos(s.p);
      await s.ctx.close();
      return d;
    };
    let comTrava = { ruins: ['(não rodou)'] };
    let r = { ruins: [] };
    try {
      fs.writeFileSync(FICHA, ficha.replace(deFicha, "'/vinculoAluno.php'"));
      comTrava = await visita();
      fs.writeFileSync(BOTAO, orig.replace(trecho, ''));
      r = await visita();
    } finally {
      fs.writeFileSync(BOTAO, orig);
      fs.writeFileSync(FICHA, ficha);
    }
    L.ok(comTrava.ruins.length === 0 && r.ruins.length > 0, `sabotagem (a ficha com o cartão do ensino superior; os botões sem conferir o módulo da página): com a trava, nenhum leva à página sem acesso${comTrava.ruins.length ? ' — ' + comTrava.ruins.join('; ') : ''}; sem ela, a prova reprova — ${r.ruins.join('; ')}`);
    L.ok(sha(BOTAO) === shaBotao && sha(FICHA) === shaFicha, 'os arquivos sabotados voltaram iguais (sha256)');
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(BOTAO) !== shaBotao || sha(FICHA) !== shaFicha) { console.log('  [AVISO] um arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
