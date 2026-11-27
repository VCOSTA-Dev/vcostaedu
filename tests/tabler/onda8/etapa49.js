/*
 * PROVA DA 1.102.1, C1 e C2 — a higiene dos textos que aparecem na tela (D-123) e as Novidades do zero na instalação
 * nova (D-124). Usa o banco do ciclo (o ensino superior, instalado do zero pelo instalador, com a demonstração).
 *   C1. a versão 1.12.0 e a novidade 79 sem "o cliente"; a descrição do domínio do e-mail da escola e os exemplos da tela
 *       Conectar ao Moodle com o domínio reservado de exemplo (.invalid), sem o domínio que alguém pode registrar;
 *   C2. a instalação nova guarda a versão em que nasceu (novidades.desde = a versão ativa) e a tela de Novidades mostra
 *       "Não há novidades" (nenhuma versão de antes); uma versão nova, depois, aparece; sem a chave (a instalação que já
 *       existia), o histórico inteiro volta;
 *   sabotagem: a lista sem o filtro da versão da instalação — as versões de antes aparecem: a prova reprova; o arquivo
 *   volta igual (sha256). As linhas da versão de prova saem no fim.
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const L = require('../lib');
const X = require('./_escola');

const ETAPA = 'onda8/etapa49';
const SENHA_ADM = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const NOV = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'helper', 'AdmsNovidades.php');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const { sql, um } = X;

async function versoesNaTela(navegador) {
  const s = await X.entrar(navegador, 'administrador', SENHA_ADM, 'painel', 1366);
  await s.p.goto(L.BASE + 'painel/novidades/listar');
  const v = await s.p.$$eval('.versao-novidades', (l) => l.map((x) => x.getAttribute('data-versao')));
  const t = ((await s.p.textContent('body').catch(() => '')) || '').replace(/\s+/g, ' ');
  await L.print(s.p, ETAPA, 'novidades-' + v.length);
  await s.ctx.close();
  return { v, t };
}

(async () => {
  const { navegador } = await L.abrir();
  const shaN = sha(NOV);
  try {
    L.ok(sql("SELECT valor FROM adms_instalacao_estado WHERE chave = 'instituicao.tipo'") === 'superior', 'o banco do ciclo: o ensino superior, instalado do zero pelo instalador');

    /* C1. os textos */
    const v16 = sql('SELECT resumo FROM adms_versoes WHERE id = 16');
    const n79 = sql('SELECT titulo FROM adms_novidades WHERE id = 79');
    const dom = sql("SELECT descricao FROM adms_parametros WHERE chave = 'moodle.email_dominio'");
    const adm = await X.entrar(navegador, 'administrador', SENHA_ADM, 'painel', 1366);
    await adm.p.goto(L.BASE + 'painel/moodle/conexao');
    const conexao = await adm.p.content();
    await adm.ctx.close();
    L.ok(!/cliente/i.test(v16) && !/cliente/i.test(n79) && /documento oficial/.test(n79) && /exemplo\.invalid/.test(dom) && !/suaescola|suainstituicao/.test(dom)
      && /instituicao\.exemplo\.invalid/.test(conexao) && !/suaescola|suainstituicao/.test(conexao),
      `C1: a versão 1.12.0 e a novidade 79 sem "o cliente" ("${n79}"); o domínio de exemplo reservado na descrição e na tela Conectar ao Moodle`);
    /* 1.103.1 (D-136): a demonstração do ensino superior dá aos alunos o CPF de demonstração dos três tipos — 000 + a
       matrícula, com os dois verificadores calculados (o mesmo esquema da escola e do curso livre) */
    const esquema = (ra) => {
      let n = '000' + String(ra).padStart(6, '0');
      for (let t = 9; t < 11; t++) { let s = 0; for (let i = 0; i < t; i++) { s += +n[i] * ((t + 1) - i); } n += ((10 * s) % 11) % 10; }
      return n;
    };
    const cpfs = sql("SELECT u.rm, i.cpf FROM col_usuarios u INNER JOIN col_usuarios_informacoes i ON i.col_user_id = u.id WHERE i.cpf IS NOT NULL AND i.cpf <> ''")
      .split('\n').filter(Boolean).map((l) => l.split('\t'));
    const foraDoEsquema = cpfs.filter(([ra, cpf]) => cpf !== esquema(ra));
    L.ok(cpfs.length > 0 && foraDoEsquema.length === 0,
      `a demonstração do ensino superior: os ${cpfs.length} CPFs dos alunos no esquema de demonstração (000 + a matrícula, com os verificadores)${foraDoEsquema.length ? '; fora: ' + foraDoEsquema.map((x) => x[0]).join(', ') : ''}`);

    /* 1.103.1 (D-137): no ensino superior, o filtro por ano de Minhas Disciplinas com o ano que a lista mostra — o
       administrador, a direção, a coordenação e o professor da demonstração (tests/tabler/saida/credenciais.json) */
    const credSup = JSON.parse(fs.readFileSync(path.join(__dirname, '..', 'saida', 'credenciais.json'), 'utf8')).painel || {};
    const perfisSup = [['administrador', SENHA_ADM]].concat(['direcao.demo', 'coord.demo', 'prof.laura.demo'].filter((u) => credSup[u]).map((u) => [u, credSup[u]]));
    const filtrosSup = [];
    for (const [u, sn] of perfisSup) {
      const r = await X.listasDisciplinas(navegador, u, sn);
      filtrosSup.push({ u, ok: X.filtroAnoOk(r.minhas) && X.filtroAnoOk(r.todas), num: X.colunaNumeroOk(r.minhas) && X.colunaNumeroOk(r.todas), linhas: r.minhas ? r.minhas.linhas.length : 0, opcoes: r.minhas ? r.minhas.opcoes.join('/') : '-', marcado: r.minhas ? r.minhas.marcado : '-' });
    }
    L.ok(perfisSup.length === 4 && filtrosSup.every((f) => f.ok) && filtrosSup.some((f) => f.linhas > 0),
      `no ensino superior, o filtro por ano de Minhas Disciplinas com o ano que a lista mostra: ${filtrosSup.map((f) => `${f.u} ${f.linhas} linha(s), ${f.opcoes || 'vazio'} (${f.marcado || 'nada marcado'})`).join('; ')}`);
    L.ok(filtrosSup.every((f) => f.num), `no ensino superior, a primeira coluna "#" com o número da oferta, único, antes do "Código", nas duas listas, para os ${filtrosSup.length} perfis (D-138)`);

    /* C2. as Novidades do zero */
    const desde = um("SELECT valor FROM adms_instalacao_estado WHERE chave = 'novidades.desde'");
    const ativa = um('SELECT id FROM adms_versoes WHERE situacao = 1 ORDER BY id DESC LIMIT 1');
    const zero = await versoesNaTela(navegador);
    L.ok(desde > 0 && desde === ativa && zero.v.length === 0 && /Não há novidades/.test(zero.t),
      `C2: a instalação nova guardou a versão em que nasceu (${desde}, a ativa) e a tela de Novidades começa vazia (${zero.v.length} versão)`);
    sql(`INSERT INTO adms_versoes (id, numero, major, minor, patch, data_versao, resumo, situacao, created) VALUES (9990, '1.999.0', 1, 999, 0, CURDATE(), 'A versão de prova (etapa 49)', 2, NOW())`);
    sql(`INSERT INTO adms_novidades (id, adms_versao_id, titulo, texto, ordem, perfis, guia, created) VALUES (9990, 9990, 'Novidade de prova', 'Só da prova.', 1, 'administrador', NULL, NOW())`);
    const depois = await versoesNaTela(navegador);
    L.ok(depois.v.length === 1 && depois.v[0] === '1.999.0', `C2: uma versão depois da instalada aparece (${depois.v.join(', ')})`);
    sql("DELETE FROM adms_instalacao_estado WHERE chave = 'novidades.desde'");
    const historico = await versoesNaTela(navegador);
    sql(`INSERT INTO adms_instalacao_estado (chave, valor) VALUES ('novidades.desde', '${desde}')`);
    L.ok(historico.v.length > 20 && historico.v.includes('1.12.0'), `C2: sem a chave (a instalação que já existia), o histórico inteiro, como antes (${historico.v.length} versões)`);

    /* sabotagem: a lista sem o filtro */
    const orig = fs.readFileSync(NOV, 'utf8');
    const trecho = 'AND v.id > :desde';
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let sab = null;
    try {
      fs.writeFileSync(NOV, orig.replace(trecho, 'AND :desde >= 0'));
      sab = await versoesNaTela(navegador);
    } finally {
      fs.writeFileSync(NOV, orig);
    }
    L.ok(sab && sab.v.length > 1, `sabotagem (a lista sem o filtro da versão da instalação): as versões de antes aparecem (${sab && sab.v.length}) — a prova reprova`);
    L.ok(sha(NOV) === shaN, 'o arquivo sabotado voltou igual (sha256)');
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(NOV) !== shaN) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    sql('DELETE FROM adms_novidades WHERE id = 9990');
    sql('DELETE FROM adms_versoes WHERE id = 9990');
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
