/*
 * PROVA DO FECHAMENTO, PARTE 1 — o tipo de instituição na instalação, com o curso livre, e a entrada do aluno por tipo
 * (D-106, D-107). APAGA o banco de prova (instala de novo três vezes).
 *   A. ensino superior (o banco do ciclo): o menu do painel e do portal como sempre (sem as telas da escola e do curso
 *      livre); o aluno entra pela matrícula (o RA) e pelo e-mail do cadastro; a senha errada e o e-mail que não existe
 *      dão a mesma falha; o mesmo e-mail em dois cadastros, com a senha de um deles, avisa e pede a matrícula;
 *   B. escola (a demonstração do colégio, pela tela): o menu da escola como sempre; o aluno entra pelo RM e NUNCA pelo
 *      e-mail (a senha certa com o e-mail dá a falha de sempre); o responsável entra pelo e-mail;
 *   C. curso livre instalado do zero com o financeiro ligado e a matrícula desligada (as caixas do instalador): o estado
 *      gravado, o menu com o financeiro e sem a importação, a tela de cadastrar aluno recusada;
 *   D. curso livre do zero com o padrão (o financeiro desligado, a matrícula ligada) e a demonstração pela tela: o menu de
 *      cada perfil só com o que vale no curso livre (cada item abre), as telas da escola e do superior recusadas pelo
 *      endereço; os recursos ligados e desligados em Configurações → Recursos do curso livre; o portal com a Página
 *      Inicial, o Moodle e a Ajuda, o endereço do boletim levando à Página Inicial; a entrada pelo e-mail e pela matrícula,
 *      as recusas e o e-mail repetido;
 *   sabotagens: (a) a marca do tipo ignorada (moduloNoTipo sempre verdadeiro) — o portal do curso livre mostra o boletim:
 *   a prova acusa; (b) a entrada sem o aviso do e-mail repetido — a prova acusa. Os arquivos voltam iguais (sha256).
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { spawnSync } = require('child_process');
const L = require('../lib');
const X = require('./_escola');
const P = require('./_passeio_tipo');

const ETAPA = 'onda8/etapa46';
const PHP = process.env.VCOSTAEDU_PHP || 'C:/xampp/php/php.exe';
const SENHA_ADM = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const VOCAB = path.join(L.RAIZ, 'core', 'Vocabulario.php');
const LOGIN = path.join(L.RAIZ, 'portal', 'app', 'adms', 'Models', 'acesso', 'AdmsLogin.php');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const { sql, um } = X;
const SENHA_A = 'Prova-Entrada-Aluno-46a';
const SENHA_B = 'Prova-Entrada-Aluno-46b';
const hash = (s) => spawnSync(PHP, ['-r', 'echo password_hash($argv[1], PASSWORD_BCRYPT);', s], { encoding: 'utf8' }).stdout.trim();

/** Instala do zero no tipo (e, no curso livre, com os recursos marcados): o status do instalador. */
function instalar(tipo, recursos) {
  spawnSync(PHP, [path.join(L.RAIZ, 'tests', 'tabler', 'reset.php')], { encoding: 'utf8', cwd: L.RAIZ });
  const env = { ...process.env, VCOSTAEDU_TIPO: tipo, VCOSTAEDU_ETAPA_PROVA: ETAPA };
  if (recursos !== undefined) env.VCOSTAEDU_RECURSOS = recursos; else delete env.VCOSTAEDU_RECURSOS;
  const r = spawnSync('node', [path.join(L.RAIZ, 'tests', 'tabler', 'instalar.js'), 'normal'], { encoding: 'utf8', cwd: L.RAIZ, env, maxBuffer: 1 << 26 });
  if (r.status !== 0) console.log((r.stdout || '').split('\n').filter((l) => /FALHA/.test(l)).join('\n'));
  return r.status;
}

/** Instala do zero e monta a demonstração pela tela (a janela dos passos): o status. */
function montar(tipo, rotulo) {
  const env = { ...process.env };
  delete env.VCOSTAEDU_RECURSOS;
  const r = spawnSync(process.execPath, [path.join(__dirname, '..', 'onda6', 'medir_demo.js'), tipo, rotulo], { encoding: 'utf8', env, maxBuffer: 1 << 24 });
  if (r.status !== 0) console.log((r.stdout || '') + (r.stderr || ''));
  return r.status;
}

/** Entra no portal: {entrou, url, msg (o texto do aviso), id do aviso, menu (os rótulos do rail)}. */
async function entrar(navegador, usuario, senha) {
  const s = await X.entrar(navegador, usuario, senha, 'portal', 1366);
  const entrou = !(await s.p.$('[name="SendLogin"]'));
  const aviso = await s.p.$('.alert');
  const r = { entrou, url: s.p.url(), msg: aviso ? (await aviso.textContent()).replace(/\s+/g, ' ').trim() : '', id: aviso ? await aviso.getAttribute('id') : '', s };
  return r;
}
async function fechar(r) { await r.s.ctx.close(); }
const rotulos = (itens) => itens.map((x) => x.rotulo);

/** O menu do painel de um usuário: os rótulos. */
async function menuPainel(navegador, usuario, senha) {
  const s = await X.entrar(navegador, usuario, senha, 'painel', 1366);
  await s.p.goto(L.BASE + 'painel/home/index');
  const m = rotulos(await P.itensDoMenu(s.p));
  await s.ctx.close();
  return m;
}

/** A tela do painel pelo endereço: 'recusada' (#semAcesso), 'entrada' ou 'abriu'. */
async function abrePainel(navegador, usuario, senha, rota) {
  const s = await X.entrar(navegador, usuario, senha, 'painel', 1366);
  await s.p.goto(L.BASE + 'painel/' + rota);
  const r = (await s.p.$('#semAcesso')) ? 'recusada' : ((await s.p.$('[name="SendLogin"]')) ? 'entrada' : 'abriu');
  await s.ctx.close();
  return r;
}

/** Grava os recursos do curso livre pela tela (o administrador). */
async function recursos(navegador, ligados) {
  const s = await X.entrar(navegador, 'administrador', SENHA_ADM, 'painel', 1366);
  await s.p.goto(L.BASE + 'painel/recursos-livre/index');
  for (const r of ['financeiro', 'matricula']) {
    if (ligados.includes(r)) await s.p.check('#recurso_' + r); else await s.p.uncheck('#recurso_' + r);
  }
  await Promise.all([s.p.waitForNavigation(), s.p.click('#salvarRecursos')]);
  const msg = (await s.p.textContent('#recursosGravados').catch(() => '')).replace(/\s+/g, ' ');
  await s.ctx.close();
  return msg;
}

/** Os casos da entrada pelo e-mail (superior e curso livre): dois alunos, o e-mail de um, depois o mesmo nos dois. */
async function casosDoEmail(navegador, raA, raB, rotuloTipo) {
  const idA = um(`SELECT id FROM col_usuarios WHERE rm = '${raA}'`);
  const idB = um(`SELECT id FROM col_usuarios WHERE rm = '${raB}'`);
  const email = `entrada.${raA}@exemplo.invalid`;
  sql(`UPDATE col_usuarios SET senha = '${hash(SENHA_A)}', email = '${email}', col_sits_usuario_id = 1 WHERE id = ${idA}`);
  sql(`UPDATE col_usuarios SET senha = '${hash(SENHA_B)}', email = 'outro.${raB}@exemplo.invalid', col_sits_usuario_id = 1 WHERE id = ${idB}`);
  sql("DELETE FROM seg_tentativas WHERE 1 = 1");
  const pelaMatricula = await entrar(navegador, raA, SENHA_A); await fechar(pelaMatricula);
  const pelaEmail = await entrar(navegador, email.toUpperCase(), SENHA_A); await fechar(pelaEmail);
  const senhaErrada = await entrar(navegador, email, 'errada-errada-46'); await fechar(senhaErrada);
  const naoExiste = await entrar(navegador, 'ninguem.46@exemplo.invalid', SENHA_A); await fechar(naoExiste);
  sql(`UPDATE col_usuarios SET email = '${email}' WHERE id = ${idB}`);
  sql("DELETE FROM seg_tentativas WHERE 1 = 1");
  const repetido = await entrar(navegador, email, SENHA_A); await fechar(repetido);
  const repetidoErrada = await entrar(navegador, email, 'errada-errada-46'); await fechar(repetidoErrada);
  const repetidoMatricula = await entrar(navegador, raB, SENHA_B); await fechar(repetidoMatricula);
  const FALHA = 'Usuário ou senha incorretos.';
  L.ok(pelaMatricula.entrou && pelaEmail.entrou, `${rotuloTipo}: o aluno entra pela matrícula (${raA}) e pelo e-mail do cadastro (com maiúsculas também)`);
  L.ok(!senhaErrada.entrou && senhaErrada.msg.includes(FALHA) && !naoExiste.entrou && naoExiste.msg === senhaErrada.msg,
    `${rotuloTipo}: a senha errada e o e-mail que não existe dão a mesma falha ("${senhaErrada.msg}")`);
  L.ok(!repetido.entrou && repetido.id === 'emailRepetido' && /mais de um cadastro\. Entre com o número de matrícula\./.test(repetido.msg),
    `${rotuloTipo}: o mesmo e-mail em dois cadastros, com a senha de um deles, avisa e pede a matrícula ("${repetido.msg}")`);
  L.ok(!repetidoErrada.entrou && repetidoErrada.msg.includes(FALHA) && repetidoMatricula.entrou,
    `${rotuloTipo}: com a senha errada, o e-mail repetido dá a falha de sempre (não revela o cadastro); pela matrícula, entra`);
  sql("DELETE FROM seg_tentativas WHERE 1 = 1");
  return repetido;
}

(async () => {
  const { navegador } = await L.abrir();
  const shaVocab = sha(VOCAB);
  const shaLogin = sha(LOGIN);
  try {
    console.log('\n== Fechamento, Parte 1 — o tipo de instituição, o curso livre e a entrada do aluno');
    /* A. o ensino superior (o banco do ciclo: a demonstração pela linha de comando) */
    const credSup = JSON.parse(fs.readFileSync(path.join(L.RAIZ, 'tests', 'tabler', 'saida', 'credenciais.json'), 'utf8'));
    L.ok(sql("SELECT valor FROM adms_instalacao_estado WHERE chave = 'instituicao.tipo'") === 'superior', 'A. o banco do ciclo é de ensino superior');
    const mSup = await menuPainel(navegador, 'administrador', SENHA_ADM);
    L.ok(mSup.includes('Prévia do histórico') && mSup.includes('Polos') && mSup.includes('Regras de média') && !mSup.includes('Turmas') && mSup.includes('Recursos da instituição') && !mSup.includes('Estrutura da escola'),
      `superior: o menu do painel como sempre — o histórico, os polos e as regras de média; sem as turmas da escola e sem os recursos do curso livre (${mSup.length} itens)`);
    /* D-129 (1.103.0, C4): a tela dos recursos vale nos três tipos (no superior, só o financeiro) */
    L.ok(await abrePainel(navegador, 'administrador', SENHA_ADM, 'recursos-livre/index') !== 'recusada', 'superior: a tela Recursos da instituição abre (D-129: o financeiro de ligar e desligar nos três tipos)');
    const supA = await entrar(navegador, '260101', credSup.portal['260101']);
    const mPortalSup = rotulos(await P.itensDoMenu(supA.s.p));
    await fechar(supA);
    L.ok(supA.entrou && mPortalSup.includes('Boletim') && mPortalSup.includes('Notas das Atividades') && !mPortalSup.includes('Entrar no Moodle'),
      `superior: o portal do aluno como sempre — o boletim e as notas, o Moodle na Página Inicial (${mPortalSup.join(', ')})`);
    await casosDoEmail(navegador, '260101', '260102', 'superior');

    /* B. a escola (a demonstração do colégio, pela tela) */
    L.ok(montar('escola', 'onda8-etapa46') === 0, 'B. a escola instalada do zero, com a demonstração do colégio pela tela');
    const cE = X.cred();
    const mEsc = await menuPainel(navegador, 'administrador', SENHA_ADM);
    L.ok(mEsc.includes('Turmas') && mEsc.includes('Estrutura da escola') && mEsc.includes('Liberação do boletim') && mEsc.includes('Responsáveis') && mEsc.includes('Recursos da instituição') && !mEsc.includes('Prévia do histórico'),
      `escola: o menu do painel como sempre — as turmas, a estrutura, a liberação do boletim, os responsáveis; sem o histórico do superior e sem os recursos do curso livre (${mEsc.length} itens)`);
    const ra = Object.keys(cE.portal)[0];
    const emailAlunoE = sql(`SELECT email FROM col_usuarios WHERE rm = '${ra}'`);
    sql("DELETE FROM seg_tentativas WHERE 1 = 1");
    const eRm = await entrar(navegador, ra, cE.portal[ra]);
    const mPortalEsc = rotulos(await P.itensDoMenu(eRm.s.p));
    await fechar(eRm);
    const eEmail = await entrar(navegador, emailAlunoE, cE.portal[ra]); await fechar(eEmail);
    const emailResp = Object.keys(cE.familia || {})[0];
    const eResp = await entrar(navegador, emailResp, cE.familia[emailResp]); await fechar(eResp);
    L.ok(eRm.entrou && mPortalEsc.includes('Boletim') && mPortalEsc.includes('Horário'), `escola: o aluno entra pelo RM (${ra}); o portal como sempre (${mPortalEsc.join(', ')})`);
    L.ok(!eEmail.entrou && eEmail.msg.includes('Usuário ou senha incorretos.'), `escola: o aluno NUNCA entra pelo e-mail — com a senha certa, a falha de sempre ("${eEmail.msg}")`);
    L.ok(eResp.entrou && /familia/.test(eResp.url), `escola: o responsável entra pelo e-mail (${emailResp}) e cai nos filhos`);
    sql("DELETE FROM seg_tentativas WHERE 1 = 1");

    /* C. o curso livre do zero, com o financeiro ligado e a matrícula desligada */
    L.ok(instalar('livre', 'financeiro') === 0, 'C. o curso livre instalado do zero, com o financeiro marcado e a matrícula desmarcada no instalador');
    const estC = sql("SELECT GROUP_CONCAT(CONCAT(chave, '=', valor) ORDER BY chave) FROM adms_instalacao_estado WHERE chave LIKE 'livre.%' OR chave = 'instituicao.tipo'");
    const mC = await menuPainel(navegador, 'administrador', SENHA_ADM);
    const cadC = await abrePainel(navegador, 'administrador', SENHA_ADM, 'academicos/cadastrar');
    L.ok(estC === 'instituicao.tipo=livre,livre.financeiro=1,livre.matricula=0', `o estado gravado: ${estC}`);
    L.ok(mC.includes('Regras financeiras') && mC.includes('Planos de pagamento') && !mC.includes('Importar alunos') && mC.includes('Alunos') && cadC === 'recusada',
      `com o financeiro ligado e a matrícula desligada: o menu tem as regras e os planos do financeiro, os alunos e não a importação; cadastrar aluno pelo endereço: ${cadC}`);

    /* D. o curso livre do zero com o padrão, e a demonstração pela tela */
    L.ok(montar('livre', 'onda8-etapa46') === 0, 'D. o curso livre instalado do zero (o padrão: o financeiro desligado, a matrícula ligada), com a demonstração pela tela');
    const c = X.cred();
    const estD = sql("SELECT GROUP_CONCAT(CONCAT(chave, '=', valor) ORDER BY chave) FROM adms_instalacao_estado WHERE chave LIKE 'livre.%'");
    const curso = sql("SELECT GROUP_CONCAT(c.nome, ' | ', t.nome, ' | ', t.capacidade, ' | ', (SELECT COUNT(*) FROM esc_turma_ofertas x WHERE x.turma_id = t.id)) FROM esc_turmas t JOIN rm_cursos c ON c.id = t.curso_id");
    L.ok(estD === 'livre.financeiro=0,livre.matricula=1' && curso === 'IA na prática para professores (demonstração) | Turma aberta | 500 | 4',
      `o padrão do curso livre (${estD}) e a demonstração: ${curso}`);
    const NAO = ['Regras de média', 'Polos', 'Estrutura da escola', 'Ano letivo e etapas', 'Liberação do boletim', 'Responsáveis', 'Prévia do histórico', 'Históricos emitidos',
      'Atos legais', 'Modelos de histórico', 'Regras da escola', 'Painel acadêmico', 'Auditoria de notas e faltas', 'Minhas Disciplinas', 'Regras financeiras', 'Planos de pagamento', 'Módulos'];
    const SIM = ['Página Inicial', 'Usuários', 'Cursos', 'Disciplinas', 'Matriz', 'Turmas', 'Alunos', 'Importar alunos', 'Recursos da instituição', 'Conectar ao Moodle', 'Chaves de API', 'Central de ajuda'];
    const pAdm = await P.painel(navegador, 'administrador', SENHA_ADM);
    const mAdm = rotulos(pAdm);
    const sobra = NAO.filter((x) => mAdm.includes(x));
    const falta = SIM.filter((x) => !mAdm.includes(x));
    L.ok(!sobra.length && !falta.length, `curso livre: o menu do administrador só com o que vale nele (${mAdm.length} itens; sobrando: ${sobra.join(', ') || 'nada'}; faltando: ${falta.join(', ') || 'nada'})`);
    const quebrados = [];
    for (const u of ['administrador', 'secretaria.demo', 'direcao.demo', 'coord.demo', 'prof.marcos.demo']) {
      const r = u === 'administrador' ? pAdm : await P.painel(navegador, u, c.painel[u]);
      r.filter((x) => !x.ok).forEach((x) => quebrados.push(`${u}: ${x.rotulo} (${x.motivo})`));
    }
    L.ok(!quebrados.length, `curso livre: cada item do menu de cada perfil abre (nenhum leva a tela que não funciona) ${quebrados.join('; ')}`);
    /* 1.103.1 (D-137): no curso livre, o filtro por ano das listas de disciplinas (a que o perfil abre) com o ano que a
       lista mostra — o administrador, a direção, a coordenação e o professor */
    const filtrosLivre = [];
    for (const [u, sn] of [['administrador', SENHA_ADM], ['direcao.demo', c.painel['direcao.demo']], ['coord.demo', c.painel['coord.demo']], ['prof.marcos.demo', c.painel['prof.marcos.demo']]]) {
      const r = await X.listasDisciplinas(navegador, u, sn);
      filtrosLivre.push({ u, ok: X.filtroAnoOk(r.minhas) && X.filtroAnoOk(r.todas), num: X.colunaNumeroOk(r.minhas) && X.colunaNumeroOk(r.todas), abre: (r.minhas ? 'minhas ' + r.minhas.linhas.length : '') + (r.todas ? ' todas ' + r.todas.linhas.length : '') });
    }
    L.ok(filtrosLivre.every((f) => f.num), `curso livre: a primeira coluna "#" com o número da oferta, único, antes do "Código", nas listas que cada perfil abre (D-138)`);
    L.ok(filtrosLivre.every((f) => f.ok), `curso livre: o filtro por ano das listas de disciplinas com o ano que a lista mostra (${filtrosLivre.map((f) => f.u + ': ' + (f.abre.trim() || 'nenhuma')).join('; ')})`);
    const rotas = ['escola-anos/index', 'relatorios/painel-academico', 'regra-media/listar', 'polos/listar', 'historico/previa', 'escola/index', 'fin-regras/index', 'escola-liberacao/index'];
    const abertas = [];
    for (const r of rotas) { if (await abrePainel(navegador, 'administrador', SENHA_ADM, r) !== 'recusada') abertas.push(r); }
    L.ok(!abertas.length, `curso livre: as telas da escola, do superior e do financeiro desligado são recusadas pelo endereço (${rotas.length}; abriram: ${abertas.join(', ') || 'nenhuma'})`);

    /* os recursos, em Configurações → Recursos do curso livre */
    const msgLiga = await recursos(navegador, ['financeiro']);
    const mLiga = await menuPainel(navegador, 'secretaria.demo', c.painel['secretaria.demo']);
    const finLiga = await abrePainel(navegador, 'administrador', SENHA_ADM, 'fin-regras/index');
    const cadLiga = await abrePainel(navegador, 'secretaria.demo', c.painel['secretaria.demo'], 'academicos/cadastrar');
    const msgVolta = await recursos(navegador, ['matricula']);
    const mVolta = await menuPainel(navegador, 'secretaria.demo', c.painel['secretaria.demo']);
    const finVolta = await abrePainel(navegador, 'administrador', SENHA_ADM, 'fin-regras/index');
    L.ok(/Financeiro: ligado; Matrícula pela secretaria: desligado/.test(msgLiga) && finLiga === 'abriu' && cadLiga === 'recusada' && !mLiga.includes('Importar alunos'),
      `os recursos pela tela: o financeiro ligado abre as regras financeiras; a matrícula desligada tira "Importar alunos" do menu da secretaria e recusa o cadastro (${finLiga}, ${cadLiga})`);
    L.ok(/Financeiro: desligado; Matrícula pela secretaria: ligado/.test(msgVolta) && finVolta === 'recusada' && mVolta.includes('Importar alunos'),
      `e de volta: o financeiro desligado recusa as regras financeiras; a matrícula ligada traz a importação (${finVolta})`);

    /* o portal do curso livre */
    const raL = '266001';
    const emailL = sql(`SELECT email FROM col_usuarios WHERE rm = '${raL}'`);
    sql("DELETE FROM seg_tentativas WHERE 1 = 1");
    const pl = await entrar(navegador, emailL, c.portal[raL]);
    const mPortal = rotulos((await P.itensDoMenu(pl.s.p)).filter((x) => !/perfil\/|novidades$/.test(x.href) || /home\/novidades\/$/.test(x.href)));
    const htmlHome = await pl.s.p.content();
    await pl.s.p.goto(L.BASE + 'portal/home/boletim-aluno');
    const urlBoletim = pl.s.p.url();
    await pl.s.p.goto(L.BASE + 'portal/atestado/index');
    const urlAtestado = pl.s.p.url();
    await L.print(pl.s.p, ETAPA, 'portal-curso-livre');
    await fechar(pl);
    L.ok(pl.entrou && mPortal.join('|') === 'Página Inicial|Entrar no Moodle|Central de ajuda|Novidades',
      `curso livre: o aluno entra pelo e-mail; o menu do portal é a Página Inicial, o Moodle e a Ajuda (${mPortal.join(', ')})`);
    L.ok(!/id="selAno"/.test(htmlHome) && !/Notas das Atividades/.test(htmlHome) && /home\/index/.test(urlBoletim) && /home\/index/.test(urlAtestado),
      `curso livre: a Página Inicial sem o ano e sem o cartão Acadêmico; o endereço do boletim e o do atestado levam à Página Inicial (${urlBoletim.replace(L.BASE, '')})`);
    await casosDoEmail(navegador, '266001', '266002', 'curso livre');

    /* sabotagem (a): a marca do tipo ignorada */
    const origV = fs.readFileSync(VOCAB, 'utf8');
    const trechoV = "        if ($modulo === null || trim($modulo) === '') {\n            return true;\n        }\n        return in_array($tipo,";
    if (origV.split(trechoV).length !== 2) throw new Error('a sabotagem (a) não achou o trecho');
    let sabA = null;
    try {
      fs.writeFileSync(VOCAB, origV.replace(trechoV, "        return true;\n" + trechoV));
      const s = await entrar(navegador, raL, SENHA_A); // a senha que os casos do e-mail gravaram
      sabA = rotulos(await P.itensDoMenu(s.s.p));
      await fechar(s);
    } finally { fs.writeFileSync(VOCAB, origV); }
    L.ok(sabA && sabA.includes('Boletim'), `sabotagem (a) (a marca do tipo ignorada): a prova reprova — o portal do curso livre mostra o boletim (${(sabA || []).join(', ')})`);
    /* sabotagem (b): sem o aviso do e-mail repetido */
    const origL = fs.readFileSync(LOGIN, 'utf8');
    const trechoL = '        if (count($ids) > 1) {';
    if (origL.split(trechoL).length !== 2) throw new Error('a sabotagem (b) não achou o trecho');
    let sabB = null;
    try {
      fs.writeFileSync(LOGIN, origL.replace(trechoL, '        if (false) {'));
      const emailRep = sql("SELECT email FROM col_usuarios WHERE rm = '266001'");
      sql(`UPDATE col_usuarios SET email = '${emailRep}' WHERE rm = '266002'`);
      sql("DELETE FROM seg_tentativas WHERE 1 = 1");
      sabB = await entrar(navegador, emailRep, SENHA_A); await fechar(sabB);
    } finally { fs.writeFileSync(LOGIN, origL); }
    L.ok(sabB && sabB.id !== 'emailRepetido', `sabotagem (b) (sem o aviso do e-mail repetido): a prova reprova — a tela dá "${sabB ? sabB.msg : '?'}"`);
    L.ok(sha(VOCAB) === shaVocab && sha(LOGIN) === shaLogin, 'os arquivos sabotados voltaram iguais (sha256)');
    sql("DELETE FROM seg_tentativas WHERE 1 = 1");
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(VOCAB) !== shaVocab || sha(LOGIN) !== shaLogin) { console.log('  [AVISO] um arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
