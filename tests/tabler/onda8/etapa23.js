/*
 * PROVA DA ONDA 8, CORREÇÃO, SEGUNDA LEVA, 2.9 — o nome da disciplina na oferta (D-95). APAGA o banco de prova.
 *   1. a demonstração do colégio, montada pela tela numa instalação nova; o portal de uma aluna mostra a disciplina
 *      "Arte" (a cópia do nome na oferta, rm_professores_materias.materia);
 *   2. o administrador renomeia a disciplina pela tela (Disciplinas → editar): o aviso diz em quantas ofertas o nome
 *      novo passou a valer; nenhuma oferta da disciplina fica com o nome de antes;
 *   3. o portal da aluna mostra o nome novo, e não o de antes;
 *   4. uma oferta com outro nome, dado à mão, fica como está;
 *   5. o registro das mudanças diz "O nome da disciplina nas ofertas";
 *   sabotagem: o modelo sem atualizar as ofertas — o portal volta a mostrar o nome de antes; a prova acusa; o arquivo
 *   volta igual.
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const L = require('../lib');
const X = require('./_escola');

const ETAPA = 'onda8/etapa23';
const MODELO = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'materias', 'AdmsEditarMaterias.php');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const RA = '265001';

/** Quantas ofertas da disciplina (pela matriz) têm o nome dado. */
const ofertasCom = (materia, nome) => X.um(`SELECT COUNT(*) FROM rm_professores_materias pm JOIN uni_rh_cursos_disciplinas_grade g ON g.id = pm.matriz_id WHERE g.materia_id = ${materia} AND pm.materia = '${nome}'`);
async function portal(navegador, c) {
  const a = await X.entrar(navegador, RA, c.portal[RA], 'portal');
  /* as notas das atividades (uma disciplina por bloco) e o boletim: as duas telas do portal que mostram o nome da
     disciplina da oferta */
  let t = '';
  for (const r of ['portal/home/notas-atividades', 'portal/home/boletim-aluno']) {
    await a.p.goto(L.BASE + r);
    t += ' ' + await a.p.evaluate(() => document.body.innerText);
  }
  return { a, t };
}
async function renomear(p, materia, nome) {
  await p.goto(L.BASE + `painel/materias/editar/${materia}`);
  await p.fill('input[name="nome"]', nome);
  await Promise.all([p.waitForNavigation(), p.click('[name="EditMaterias"]')]);
  return p.evaluate(() => (document.querySelector('.alert') || {}).textContent || '');
}

(async () => {
  const { navegador } = await L.abrir();
  const shaModelo = sha(MODELO);
  try {
    L.ok(X.montar('onda8-etapa23').status === 0, 'a demonstração do colégio montada pela tela, numa instalação nova');
    const c = X.cred();
    const arte = X.um("SELECT id FROM rm_materias WHERE nome = 'Arte' LIMIT 1");
    const total = ofertasCom(arte, 'Arte');
    const p1 = await portal(navegador, c);
    L.ok(arte > 0 && total > 0 && /\bArte\b/.test(p1.t), `o portal da aluna ${RA} (as notas e o boletim) mostra "Arte" (a disciplina ${arte}, em ${total} oferta(s))`);
    await p1.a.ctx.close();

    /* 4 (preparo). uma oferta com outro nome, dado à mão */
    const mao = X.um(`SELECT pm.id FROM rm_professores_materias pm JOIN uni_rh_cursos_disciplinas_grade g ON g.id = pm.matriz_id WHERE g.materia_id = ${arte} ORDER BY pm.id DESC LIMIT 1`);
    X.sql(`UPDATE rm_professores_materias SET materia = 'Arte (nome dado a mao)' WHERE id = ${mao}`);

    /* 2. renomear pela tela */
    const ctx = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' });
    const p = await L.pagina(ctx);
    await p.goto(L.BASE + 'painel/login/acesso');
    await p.fill('input[name="usuario"]', 'administrador');
    await p.fill('input[name="senha"]', process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123');
    await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
    const novo = 'Arte e Cultura';
    const aviso = await renomear(p, arte, novo);
    await L.print(p, ETAPA, 'disciplina-renomeada');
    L.ok(/alterada/i.test(aviso) && new RegExp(`${total - 1} oferta`).test(aviso) && ofertasCom(arte, 'Arte') === 0 && ofertasCom(arte, novo) === total - 1,
      `renomeada pela tela: "${aviso.replace(/\s+/g, ' ').trim().slice(0, 110)}"; com o nome de antes, ${ofertasCom(arte, 'Arte')} oferta(s); com o novo, ${ofertasCom(arte, novo)}`);

    /* 3. o portal */
    const p2 = await portal(navegador, c);
    await L.print(p2.a.p, ETAPA, 'portal-com-o-nome-novo');
    L.ok(p2.t.includes(novo) && !/\bArte\b(?! e Cultura| \(nome dado a mao\))/.test(p2.t), `o portal da aluna mostra "${novo}" e não "Arte"`);
    await p2.a.ctx.close();

    /* 4. a oferta com outro nome */
    L.ok(X.sql(`SELECT materia FROM rm_professores_materias WHERE id = ${mao}`) === 'Arte (nome dado a mao)', 'a oferta com outro nome, dado à mão, ficou como estava');

    /* 5. o registro das mudanças */
    require('./_escola').sql("DELETE FROM adms_instalacao_estado WHERE chave = 'novidades.desde'"); // D-124 (1.102.1): as Novidades com o histórico, como numa instalação que já existia (a nova começa do zero)
    await p.goto(L.BASE + 'painel/novidades/listar');
    L.ok(/O nome da disciplina nas ofertas/.test(await p.evaluate(() => document.body.innerText)), 'o registro das mudanças diz "O nome da disciplina nas ofertas"');

    /* sabotagem: sem atualizar as ofertas */
    const orig = fs.readFileSync(MODELO, 'utf8');
    const trecho = '$st->execute([$novo, (int) $this->dados[\'id\'], $nomeAntes]);';
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let s = null;
    try {
      fs.writeFileSync(MODELO, orig.replace(trecho, '/* sabotagem */'));
      await renomear(p, arte, 'Arte da sabotagem');
      const p3 = await portal(navegador, c);
      s = !p3.t.includes('Arte da sabotagem');
      await p3.a.ctx.close();
    } finally {
      fs.writeFileSync(MODELO, orig);
    }
    L.ok(s === true, 'sabotagem (o modelo sem atualizar as ofertas): a prova reprova — o portal não mostra o nome novo');
    L.ok(sha(MODELO) === shaModelo, 'o arquivo sabotado voltou igual (sha256)');
    await ctx.close();
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(MODELO) !== shaModelo) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
