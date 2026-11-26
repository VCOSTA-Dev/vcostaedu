/*
 * PROVA DO HISTÓRICO ESCOLAR, A JUNÇÃO (1.101.0) — o histórico em cada tipo de instituição. APAGA o banco de prova
 * (instala de novo duas vezes).
 *   A. ensino superior (o banco do ciclo): o menu sem o histórico da escola; as telas do histórico da escola recusadas
 *      pelo endereço (o administrador e a secretaria);
 *   B. escola instalada do zero, com a demonstração do colégio pela tela: o menu da secretaria com o histórico escolar;
 *      um histórico do Fundamental emitido de ponta a ponta pela tela (a prévia, a emissão, o PDF que confere com o
 *      sha256, a conferência pública válida, o PDF enviado idêntico);
 *   C. curso livre instalado do zero, com a demonstração pela tela: o menu sem o histórico da escola; as telas
 *      recusadas pelo endereço;
 *   sabotagem: a marca do tipo das páginas ignorada (moduloNoTipo sempre verdadeiro) — no curso livre, a tela do histórico
 *   abre: a prova reprova; o arquivo volta igual (sha256).
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { spawnSync } = require('child_process');
const L = require('../lib');
const X = require('../onda8/_escola');
const P = require('../onda8/_passeio_tipo');

const ETAPA = 'historico/juncao';
const SENHA_ADM = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const VOCAB = path.join(L.RAIZ, 'core', 'Vocabulario.php');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const shaB = (b) => crypto.createHash('sha256').update(b).digest('hex');
const { sql, um } = X;
const texto = async (p, sel) => ((await p.textContent(sel).catch(() => '')) || '').replace(/\s+/g, ' ').trim();
/* os itens do menu do histórico da escola (as páginas 620 a 658) */
const MENU_HIST = ['Cadastros do histórico', 'Matriz do histórico', 'Histórico escolar', 'Prévia e emissão do histórico', 'Ano letivo do histórico'];
const ROTAS = ['historico-escola/cadastros', 'historico-escola-matriz/listar', 'historico-escola-vida/buscar', 'historico-escola/ano-letivo'];

function montar(tipo, rotulo) {
  const env = { ...process.env };
  delete env.VCOSTAEDU_RECURSOS;
  const r = spawnSync(process.execPath, [path.join(__dirname, '..', 'onda6', 'medir_demo.js'), tipo, rotulo], { encoding: 'utf8', env, maxBuffer: 1 << 24 });
  if (r.status !== 0) console.log((r.stdout || '') + (r.stderr || ''));
  return r.status;
}
async function menu(navegador, usuario, senha) {
  const s = await X.entrar(navegador, usuario, senha, 'painel', 1366);
  await s.p.goto(L.BASE + 'painel/home/index');
  const m = (await P.itensDoMenu(s.p)).map((x) => x.rotulo);
  await s.ctx.close();
  return m;
}
/** As rotas do histórico pelo endereço: 'recusada' (#semAcesso), 'entrada' ou 'abriu', uma por rota. */
async function rotas(navegador, usuario, senha, lista = ROTAS) {
  const s = await X.entrar(navegador, usuario, senha, 'painel', 1366);
  const r = [];
  for (const rota of lista) {
    await s.p.goto(L.BASE + 'painel/' + rota);
    r.push((await s.p.$('#semAcesso')) ? 'recusada' : ((await s.p.$('[name="SendLogin"]')) ? 'entrada' : 'abriu'));
  }
  await s.ctx.close();
  return r;
}
const semHist = (m) => MENU_HIST.every((i) => !m.includes(i));

(async () => {
  const { navegador } = await L.abrir();
  const shaV = sha(VOCAB);
  try {
    /* A. o ensino superior (o banco do ciclo) */
    L.ok(sql("SELECT valor FROM adms_instalacao_estado WHERE chave = 'instituicao.tipo'") === 'superior', 'A. o banco do ciclo é de ensino superior');
    const mSup = await menu(navegador, 'administrador', SENHA_ADM);
    const rSup = await rotas(navegador, 'administrador', SENHA_ADM);
    L.ok(semHist(mSup) && mSup.includes('Prévia do histórico') && rSup.every((x) => x === 'recusada'),
      `superior: o menu sem o histórico da escola (o do superior continua: "Prévia do histórico"); as telas do histórico da escola recusadas pelo endereço (${rSup.join(', ')})`);

    /* B. a escola do zero, com a demonstração pela tela, e um histórico do Fundamental de ponta a ponta */
    L.ok(montar('escola', 'historico-juncao') === 0, 'B. a escola instalada do zero, com a demonstração do colégio pela tela');
    const c = X.cred();
    const mSec = await menu(navegador, 'secretaria.demo', c.painel['secretaria.demo']);
    L.ok(mSec.includes('Histórico escolar') && mSec.includes('Cadastros do histórico'), `escola: o menu da secretaria com o histórico escolar (${MENU_HIST.filter((i) => mSec.includes(i)).join(', ')})`);
    const A = um("SELECT id FROM col_usuarios WHERE rm = '255001'");
    const nome = sql(`SELECT nome FROM col_usuarios WHERE id = ${A}`);
    const sec = await X.entrar(navegador, 'secretaria.demo', c.painel['secretaria.demo']);
    const p = sec.p;
    await p.goto(L.BASE + `painel/historico-escola-emissao/previa/${A}?nivel=EF`);
    await L.print(p, ETAPA, 'previa-escola');
    const n0 = um("SELECT COUNT(*) FROM rm_historico_emissoes WHERE tipo = 'ESC_EF'");
    if (await p.$('#confirmoAvisos')) { await p.check('#confirmoAvisos'); }
    await Promise.all([p.waitForNavigation(), p.click('#emitirHistorico')]);
    const ok1 = await texto(p, '#historicoEmitido');
    const [id1, cod1, tipo1, hash1] = sql("SELECT CONCAT(id, '|', codigo, '|', tipo, '|', hash_pdf) FROM rm_historico_emissoes ORDER BY id DESC LIMIT 1").split('|');
    const b1 = await (await p.request.get(L.BASE + `painel/historico-escola-emissao/pdf/${id1}`)).body();
    fs.mkdirSync(path.join(L.PROVAS, ETAPA), { recursive: true });
    fs.writeFileSync(path.join(L.PROVAS, ETAPA, 'emitido.pdf'), b1);
    await sec.ctx.close();
    L.ok(/emitido/.test(ok1) && tipo1 === 'ESC_EF' && um("SELECT COUNT(*) FROM rm_historico_emissoes WHERE tipo = 'ESC_EF'") === n0 + 1 && shaB(b1) === hash1 && b1.slice(0, 4).toString() === '%PDF',
      `escola: o histórico do Fundamental de ${nome} emitido pela tela (${tipo1}, código ${cod1}); o PDF confere com o sha256 gravado`);
    const pub = await navegador.newContext();
    const pp = await pub.newPage();
    await pp.goto(L.BASE + 'verificar/' + cod1);
    const estado = await pp.getAttribute('#resultadoVerificacao', 'data-estado').catch(() => '');
    const tipoDoc = await texto(pp, '#tipoDocumento');
    const r = await pp.request.post(L.BASE + 'painel/verificar/codigo/' + cod1, { multipart: { pdf: { name: 'h.pdf', mimeType: 'application/pdf', buffer: b1 } } });
    const conf = (await r.text()).replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ');
    await L.print(pp, ETAPA, 'verificacao-publica');
    await pub.close();
    L.ok(estado === 'valido' && tipoDoc === 'Histórico escolar — Ensino Fundamental' && /idêntico ao documento original/.test(conf),
      `escola: a conferência pública pelo código — "${tipoDoc}", ${estado}; o PDF enviado é idêntico ao original`);

    /* C. o curso livre do zero, com a demonstração pela tela */
    L.ok(montar('livre', 'historico-juncao-livre') === 0, 'C. o curso livre instalado do zero, com a demonstração pela tela');
    L.ok(sql("SELECT valor FROM adms_instalacao_estado WHERE chave = 'instituicao.tipo'") === 'livre', 'o banco é de curso livre');
    const mLiv = await menu(navegador, 'administrador', SENHA_ADM);
    const rLiv = await rotas(navegador, 'administrador', SENHA_ADM);
    L.ok(semHist(mLiv) && rLiv.every((x) => x === 'recusada'),
      `curso livre: o menu sem o histórico da escola (${mLiv.length} itens); as telas do histórico recusadas pelo endereço (${rLiv.join(', ')})`);

    /* sabotagem: a marca do tipo das páginas ignorada */
    const orig = fs.readFileSync(VOCAB, 'utf8');
    const trecho = "        if ($modulo === null || trim($modulo) === '') {\n            return true;\n        }\n        return in_array($tipo,";
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let sab = [];
    try {
      fs.writeFileSync(VOCAB, orig.replace(trecho, "        if (true) {\n            return true;\n        }\n        return in_array($tipo,"));
      sab = await rotas(navegador, 'administrador', SENHA_ADM, ['historico-escola/cadastros']);
    } finally {
      fs.writeFileSync(VOCAB, orig);
    }
    L.ok(sab[0] === 'abriu', `sabotagem (a marca do tipo ignorada): no curso livre, a tela do histórico da escola abre (${sab.join(', ')}) — a prova reprova`);
    L.ok(sha(VOCAB) === shaV, 'o arquivo sabotado voltou igual (sha256)');
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(VOCAB) !== shaV) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
