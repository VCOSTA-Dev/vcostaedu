/*
 * ONDA 4, ETAPA 2 — a escola: a estrutura (docs/ESCOLA.md, D-47).
 *   A. no ENSINO SUPERIOR (o banco de prova do ciclo): as telas da escola e as rotas da API de turmas não existem (a rota
 *      recusada, a API responde 404); sabotagem: o servidor da API sem o filtro do módulo;
 *   B. a ESCOLA (uma instalação nova de escola): o segmento, a matriz com as séries e os componentes (pelo banco, como as
 *      telas de sempre gravam); o ano letivo aberto com bimestres pela tela (4 etapas, as datas; sobreposição recusada;
 *      trimestres enquanto não há lançamento; depois, o modelo não muda); a turma criada pela tela gera uma oferta por
 *      componente; a mesma turma duas vezes, recusada;
 *   C. as três portas: a TELA (os alunos marcados, em lote, em passos; a página fechada no meio retoma com Continuar), a
 *      API (a turma nos cadastros; pôr o aluno na turma pela RA, com o limite por assunto) e a PLANILHA (a coluna da
 *      turma); o aluno fica em TODAS as ofertas da turma; a capacidade recusa o excedente; sair tira de todas; trocar de
 *      turma leva a nota e as faltas (a cópia para a oferta do mesmo componente); a regra da frequência gravada; os
 *      Primeiros passos da escola; o professor só nas ofertas dele;
 *   D. o MOODLE (o de teste; ou o servidor falso com VCOSTAEDU_MOODLE_FALSO=1): a turma inteira enturmada vai pela fila e
 *      pelo "Enviar tudo o que falta" (em passos); a disciplina criada no Moodle com a série e a turma no nome; a turma
 *      renomeada, o nome acompanha;
 *   sabotagens: a troca sem levar o lançado; a entrada sem a capacidade — a prova reprova; os arquivos voltam.
 *
 *   node tests/tabler/onda4/etapa2.js   (no fim, o banco de prova é uma instalação de escola: rode o ciclo depois)
 */
const fs = require('fs');
const path = require('path');
const { execFileSync, spawnSync } = require('child_process');
const crypto = require('crypto');
const L = require('../lib');

const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste$/.test(BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
const MYSQL = process.env.VCOSTAEDU_MYSQL || 'C:/xampp/mysql/bin/mysql.exe';
const PHP = process.env.VCOSTAEDU_PHP || 'C:/xampp/php/php.exe';
/* pela entrada padrão: no Windows, o texto com acento passado como argumento chega trocado ("6º" vira "6?") */
const sql = (q) => execFileSync(MYSQL, ['-u', 'root', '-N', '--default-character-set=utf8mb4', BANCO], { input: Buffer.from(q, 'utf8') }).toString().replace(/\r/g, '').trim();
const um = (q) => +sql(q);
const ETAPA = 'onda4/etapa2';
const SENHA = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const ANO = new Date().getFullYear();
const RODADA = Date.now().toString(36);
const API = L.BASE + 'api/v1';
const MODO_FALSO = process.env.VCOSTAEDU_MOODLE_FALSO === '1';
const MURL = MODO_FALSO ? '' : (process.env.VCOSTAEDU_MOODLE_URL || '');
const MCHAVE = MODO_FALSO ? '' : (process.env.VCOSTAEDU_MOODLE_CHAVE || '');
const SERVIDOR = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'api', 'AdmsApiServidor.php');
const ALUNOS = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'escola', 'AdmsTurmaAlunos.php');

async function entrar(navegador, usuario = 'administrador', senha = SENHA) {
  const ctx = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' });
  const p = await L.pagina(ctx);
  await p.goto(L.BASE + 'painel/login/acesso');
  await p.fill('input[name="usuario"]', usuario);
  await p.fill('input[name="senha"]', senha);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
  return { ctx, p };
}
async function abre(p, rota) {
  await p.goto(L.BASE + 'painel/' + rota);
  return p.url().includes('/painel/' + rota) && !(await p.$('input[name="SendLogin"]')) && !(await p.$('#semAcesso'));
}
async function sabotar(nome, arquivo, de, para, conferir) {
  const orig = fs.readFileSync(arquivo, 'utf8');
  let reprovou = false;
  try {
    if (!orig.includes(de)) { throw new Error('sabotagem sem o trecho: ' + nome); }
    fs.writeFileSync(arquivo, orig.replace(de, para));
    reprovou = !(await conferir());
  } finally { fs.writeFileSync(arquivo, orig); }
  L.ok(reprovou && fs.readFileSync(arquivo, 'utf8') === orig, `sabotagem (${nome}): a prova reprova o defeito; o arquivo voltou`);
}
function criarChave(permissoes) {
  const chave = 'vce_' + crypto.randomBytes(20).toString('hex');
  sql(`INSERT INTO adms_api_chaves (nome, prefixo, hash, escopo, permissoes, situacao, usuario_id) VALUES ('Prova escola', '${chave.slice(0, 12)}', '${crypto.createHash('sha256').update(chave).digest('hex')}', 'escrita', '${permissoes}', 1, 1)`);
  return chave;
}
async function api(metodo, rota, chave, corpo) {
  const r = await fetch(API + rota, { method: metodo, headers: { Authorization: 'Bearer ' + chave, 'Content-Type': 'application/json' }, body: corpo ? JSON.stringify(corpo) : undefined });
  return { status: r.status, json: await r.json().catch(() => null) };
}
const naTurma = (turma) => sql(`SELECT rm FROM esc_turma_alunos WHERE turma_id = ${turma} AND saida IS NULL ORDER BY rm`).split('\n').filter(Boolean);
/** Em quantas ofertas da turma o aluno está. */
const ofertasDoAluno = (turma, rm) => um(`SELECT COUNT(*) FROM portal_disciplinas_alunos e INNER JOIN esc_turma_ofertas o ON o.oferta_id = e.rm_prof_mat_id WHERE o.turma_id = ${turma} AND e.rm = '${rm}'`);
function phpHash(senha) {
  return spawnSync(PHP, ['-r', 'echo password_hash($argv[1], PASSWORD_DEFAULT);', senha], { encoding: 'utf8' }).stdout.trim();
}
function cli(...args) {
  const r = spawnSync(PHP, [path.join(L.RAIZ, 'tests', 'tabler', 'onda3', 'moodle_cli.php'), ...args], { cwd: L.RAIZ, encoding: 'utf8', env: process.env });
  try { return JSON.parse((r.stdout || '').trim().split('\n').pop()); } catch (e) { return { erro: (r.stderr || r.stdout || '').slice(0, 300) }; }
}
const moodle = (funcao, params = {}) => cli('chamar', funcao, JSON.stringify(params));
const cursoMoodle = (ofertaId) => { const r = moodle('core_course_get_courses_by_field', { field: 'idnumber', value: 'prova-oferta-' + ofertaId }); return r.ok ? ((r.dados.courses || [])[0] || null) : null; };

/** O segmento, a matriz, as séries (6º e 7º ano) e os componentes, e o ano letivo — como as telas de sempre gravam. */
function estrutura() {
  sql(`INSERT INTO adms_periodos_letivos (ano, situacaodisciplina, situacaomodelo, situacao, usuario_cre) VALUES (${ANO}, 1, 1, 1, 1)`);
  const sede = um("SELECT id FROM rm_polos WHERE nome = 'Sede' LIMIT 1");
  sql(`INSERT INTO rm_cursos (nome, abreviatura, periodo, localidade, filial, situacao, usuario_cre) VALUES ('Ensino Fundamental — anos finais (prova)', 'EFAF', 1, ${sede}, ${sede}, 1, 1)`);
  const curso = um("SELECT MAX(id) FROM rm_cursos");
  sql(`INSERT INTO rm_cursos_grades (curso_id, versao, situacao, usuario_cre) VALUES (${curso}, 1, 1, 1)`);
  const grade = um('SELECT MAX(id) FROM rm_cursos_grades');
  const series = {};
  [['6º ano', 1], ['7º ano', 2]].forEach(([nome, ordem]) => {
    sql(`INSERT INTO rm_cursos_turma_series (curso_id, grade_id, periodo, codnome, ordem, situacao, usuario_cre) VALUES (${curso}, ${grade}, ${ordem}, '${nome}', ${ordem}, 1, 1)`);
    series[nome] = um('SELECT MAX(id) FROM rm_cursos_turma_series');
  });
  const comps = [['Língua Portuguesa', 200], ['Matemática', 200], ['Ciências', 120]];
  comps.forEach(([nome, ch], i) => {
    sql(`INSERT INTO rm_materias (nome, abreviacao, carga_horaria, situacao, usuario_cre) VALUES ('${nome}', 'C${i}', '${ch}', 1, 1)`);
    const mat = um('SELECT MAX(id) FROM rm_materias');
    for (const s of Object.values(series)) {
      sql(`INSERT INTO uni_rh_cursos_disciplinas_grade (grade_id, modulo_id, materia_id, cargahoraria, ordem, usuario_cre) VALUES (${grade}, ${s}, ${mat}, ${ch}, ${i + 1}, 1)`);
    }
  });
  return { curso, grade, series, componentes: comps.length };
}

(async () => {
  const { navegador } = await L.abrir();
  try {
    console.log('\n== Onda 4, Etapa 2 — a escola: a estrutura');

    /* A. no ensino superior */
    let adm = await entrar(navegador);
    const telas = [];
    for (const r of ['turmas/listar', 'escola-anos/index', 'escola-regras/index']) { if (await abre(adm.p, r)) { telas.push(r); } }
    const chaveSup = criarChave('alunos:gravar matriculas:gravar enturmacao:gravar cadastros:ler');
    const apiSup = await api('GET', '/turmas', chaveSup);
    L.ok(telas.length === 0 && apiSup.status === 404, `superior: as telas da escola recusadas${telas.length ? ' (abriram: ' + telas.join(', ') + ')' : ''} e a API de turmas não existe (${apiSup.status})`);
    await sabotar('o servidor da API sem o filtro do módulo', SERVIDOR, "if (isset($r[5]) && !\\App\\adms\\Models\\helper\\AdmsTipoInstituicao::moduloLigado($r[5])) {", 'if (false) {',
      async () => (await api('GET', '/turmas', chaveSup)).status === 404);
    await adm.ctx.close();

    /* B. a escola: a instalação nova */
    spawnSync(PHP, [path.join(L.RAIZ, 'tests', 'tabler', 'reset.php')], { encoding: 'utf8', cwd: L.RAIZ });
    const inst = spawnSync('node', [path.join(L.RAIZ, 'tests', 'tabler', 'instalar.js'), 'normal'], { encoding: 'utf8', cwd: L.RAIZ, env: { ...process.env, VCOSTAEDU_TIPO: 'escola', VCOSTAEDU_ETAPA_PROVA: ETAPA }, maxBuffer: 1 << 26 });
    L.ok(inst.status === 0 && sql("SELECT valor FROM adms_instalacao_estado WHERE chave = 'instituicao.tipo'") === 'escola', 'a instalação nova de escola');
    const E = estrutura();
    adm = await entrar(navegador);
    /* o ano letivo e as etapas, pela tela */
    await adm.p.goto(L.BASE + 'painel/escola-anos/index');
    await L.semErros(adm.p, 'Ano letivo e etapas');
    await Promise.all([adm.p.waitForNavigation(), adm.p.click(`.vc-ano[data-ano="${ANO}"] .vc-abrir button[type="submit"]`)]);
    const etapas = sql(`SELECT CONCAT(ordem, '|', nome, '|', inicio, '|', fim, '|', IFNULL(conselho, '')) FROM esc_etapas WHERE ano = ${ANO} ORDER BY ordem`).split('\n');
    L.ok(etapas.length === 4 && /^1\|1º bimestre\|\d{4}-02-01\|/.test(etapas[0]) && /^4\|4º bimestre\|.*\|\d{4}-12-15\|\d{4}-12-22$/.test(etapas[3]),
      `o ano aberto com o padrão: 4 bimestres, com as datas e o conselho (${etapas[0]} … ${etapas[3]})`);
    await L.print(adm.p, ETAPA, 'ano-letivo-e-etapas');
    await adm.p.fill(`.vc-ano[data-ano="${ANO}"] input[name="etapas[2][inicio]"]`, `${ANO}-03-01`);
    await Promise.all([adm.p.waitForNavigation(), adm.p.click(`.vc-ano[data-ano="${ANO}"] .vc-etapas button.vc-salvar-etapas`)]);
    const sobreposta = /não se sobrepõem/.test(await adm.p.textContent('.alert').catch(() => ''));
    await adm.p.selectOption(`#modelo${ANO}`, 'trimestre');
    await Promise.all([adm.p.waitForNavigation(), adm.p.click(`.vc-ano[data-ano="${ANO}"] .vc-abrir button[type="submit"]`)]);
    const tri = um(`SELECT COUNT(*) FROM esc_etapas WHERE ano = ${ANO}`);
    await adm.p.selectOption(`#modelo${ANO}`, 'bimestre');
    await Promise.all([adm.p.waitForNavigation(), adm.p.click(`.vc-ano[data-ano="${ANO}"] .vc-abrir button[type="submit"]`)]);
    L.ok(sobreposta && tri === 3 && um(`SELECT COUNT(*) FROM esc_etapas WHERE ano = ${ANO}`) === 4, 'as etapas: a sobreposta é recusada; sem lançamento, o modelo troca (3 trimestres) e volta (4 bimestres)');

    /* a turma, pela tela */
    const criarTurma = async (serie, nome, capacidade = '') => {
      await adm.p.goto(L.BASE + 'painel/turmas/cadastrar');
      await adm.p.selectOption('#ano', String(ANO));
      await adm.p.selectOption('#modulo_id', String(E.series[serie]));
      await adm.p.fill('#nome', nome);
      if (capacidade) { await adm.p.fill('#capacidade', capacidade); }
      await Promise.all([adm.p.waitForNavigation(), adm.p.click('#salvarTurma')]);
      return um(`SELECT IFNULL(MAX(id), 0) FROM esc_turmas WHERE ano = ${ANO} AND modulo_id = ${E.series[serie]} AND nome = '${nome}'`);
    };
    const tA = await criarTurma('6º ano', 'A', '4');
    await L.semErros(adm.p, 'a turma criada');
    const ofA = sql(`SELECT o.codtur FROM esc_turma_ofertas x INNER JOIN rm_professores_materias o ON o.id = x.oferta_id WHERE x.turma_id = ${tA}`).split('\n');
    L.ok(tA > 0 && ofA.length === E.componentes && ofA.every((c) => c === '6º ano A') && /6º ano A/.test(await adm.p.textContent('.page-title')),
      `a turma 6º ano A gera ${ofA.length} ofertas, uma por componente curricular, com "6º ano A" como a turma de cada uma`);
    await L.print(adm.p, ETAPA, 'turma-criada');
    const tB = await criarTurma('6º ano', 'B');
    const tM = await criarTurma('7º ano', 'M');
    await adm.p.goto(L.BASE + 'painel/turmas/cadastrar');
    await adm.p.selectOption('#ano', String(ANO));
    await adm.p.selectOption('#modulo_id', String(E.series['6º ano']));
    await adm.p.fill('#nome', 'A');
    await Promise.all([adm.p.waitForNavigation(), adm.p.click('#salvarTurma')]);
    L.ok(/Já existe a turma 6º ano A/.test(await adm.p.textContent('.alert').catch(() => '')) && um(`SELECT COUNT(*) FROM esc_turmas WHERE nome = 'A'`) === 1, 'a mesma turma duas vezes: recusada');

    /* os alunos e os vínculos: pela API (o aluno e a matrícula) */
    const chave = criarChave('alunos:gravar matriculas:gravar enturmacao:gravar cadastros:ler');
    const forma = um('SELECT id FROM rm_matricula_tipos WHERE situacao = 1 ORDER BY id LIMIT 1');
    const ras = ['941001', '941002', '941003', '941004', '941005', '941006', '941007', '941008'];
    let vinculados = 0;
    for (const [i, ra] of ras.entries()) {
      /* o e-mail único a cada rodada: o Moodle exige e-mail, e um e-mail de rodada anterior acharia a conta antiga */
      const a = await api('POST', '/alunos', chave, { ra, nome: `Aluno Escola ${i + 1} Exemplo`, sexo: i % 2 ? 'feminino' : 'masculino', nascimento: '2014-03-10', email: `prova.escola.${ra}.${RODADA}@exemplo.invalid` });
      const m = await api('POST', '/matriculas', chave, { ra, curso_id: E.curso, ano: ANO, forma_ingresso_id: forma, data_ingresso: `${ANO}-02-01` });
      if (a.status === 201 && m.status === 201) { vinculados++; }
    }
    L.ok(vinculados === ras.length, `${vinculados} alunos com matrícula no segmento, pela API`);

    /* C1. a TELA: em lote, em passos; a página fechada no meio retoma */
    const uid = (ra) => um(`SELECT id FROM col_usuarios WHERE rm = '${ra}'`);
    await adm.p.goto(L.BASE + `painel/turmas/ver/${tA}`);
    await L.semErros(adm.p, 'a turma');
    const inicio = await adm.p.evaluate(async ([u, ids]) => { const f = new URLSearchParams(); f.append('fase', 'iniciar'); f.append('alunos', ids);
      return (await fetch(u, { method: 'POST', body: f, credentials: 'same-origin' })).json(); }, [L.BASE + `painel/turmas/enturmar/${tA}`, [ras[0], ras[1], ras[2]].map(uid).join(',')]);
    await adm.p.goto(L.BASE + `painel/turmas/ver/${tA}`); // a página "fechou" antes do primeiro passo
    const interrompido = await adm.p.isVisible('#loteInterrompido');
    await L.print(adm.p, ETAPA, 'lote-interrompido');
    await adm.p.click('#continuarLote');
    await adm.p.waitForSelector('#progressoTurma[data-situacao="concluida"]', { timeout: 120000 });
    await adm.p.goto(L.BASE + `painel/turmas/ver/${tA}`);
    const tres = [ras[0], ras[1], ras[2]];
    L.ok(inicio.total === 3 && interrompido && await adm.p.isVisible('#loteConcluido') && JSON.stringify(naTurma(tA)) === JSON.stringify(tres) && tres.every((ra) => ofertasDoAluno(tA, ra) === E.componentes),
      `a tela: 3 alunos em lote; a página fechada no meio, "Continuar" retoma; cada um em todas as ${E.componentes} ofertas da turma`);
    await L.print(adm.p, ETAPA, 'lote-concluido');

    /* C2. a API: a turma nos cadastros; pôr o aluno na turma */
    const lista = await api('GET', `/turmas?ano=${ANO}`, chave);
    const pela = await api('POST', `/turmas/${tB}/alunos`, chave, { ra: ras[3] });
    const soLeitura = criarChave('cadastros:ler');
    const negada = await api('POST', `/turmas/${tB}/alunos`, soLeitura, { ra: ras[4] });
    L.ok(lista.status === 200 && lista.json.dados.some((t) => t.turma === '6º ano B' && t.ofertas.length === E.componentes) && pela.status === 200 && ofertasDoAluno(tB, ras[3]) === E.componentes
      && negada.status === 403 && naTurma(tB).length === 1,
      `a API: as turmas nos cadastros de apoio; o aluno posto na turma pela RA (${pela.status}); a chave sem "enturmação" recusada (${negada.status})`);

    /* C3. a PLANILHA, com a coluna da turma */
    const csv = Buffer.from(['RA;Nome completo;Sexo;Data de nascimento;Curso;Grade;Ano;Turma',
      `941009;Aluno Planilha Escola Exemplo;M;10/01/2014;Ensino Fundamental — anos finais (prova);1;${ANO};6º ano A`].join('\r\n') + '\r\n', 'utf8');
    await adm.p.goto(L.BASE + 'painel/importacao-alunos/listar');
    await adm.p.setInputFiles('#arquivoImportacao', { name: 'turma.csv', mimeType: 'text/csv', buffer: csv });
    await Promise.all([adm.p.waitForNavigation(), adm.p.click('#enviarImportacao')]);
    const mapa = await adm.p.$$eval('select.vc-mapa', (l) => l.map((x) => x.value)).catch(() => []);
    const temTurma = mapa.includes('turma');
    const contagem = await adm.p.$$eval('[data-contagem]', (es) => Object.fromEntries(es.map((e) => [e.dataset.contagem, +e.textContent]))).catch(() => ({}));
    await adm.p.selectOption('#formaIngresso', { index: 1 }).catch(() => {});
    await Promise.all([adm.p.waitForNavigation({ timeout: 120000 }), adm.p.click('#aplicarLote')]).catch(() => {});
    L.ok(temTurma && contagem.prontas === 1 && await adm.p.isVisible('#loteAplicado') && naTurma(tA).includes('941009') && ofertasDoAluno(tA, '941009') === E.componentes,
      `a planilha: a coluna "Turma" reconhecida; o aluno vinculado e posto na turma 6º ano A (${naTurma(tA).length} na turma)`);
    await L.print(adm.p, ETAPA, 'planilha-com-turma');

    /* a capacidade (4) */
    const lotado = await adm.p.evaluate(async ([u, ids]) => { const f = new URLSearchParams(); f.append('fase', 'iniciar'); f.append('alunos', ids);
      await fetch(u, { method: 'POST', body: f, credentials: 'same-origin' }); const g = new URLSearchParams(); g.append('fase', 'passo');
      return (await fetch(u, { method: 'POST', body: g, credentials: 'same-origin' })).json(); }, [L.BASE + `painel/turmas/enturmar/${tA}`, String(uid(ras[5]))]);
    await adm.p.goto(L.BASE + `painel/turmas/ver/${tA}`);
    L.ok(lotado.entraram === 0 && /está completa/.test((lotado.recusas || [])[0] || '') && naTurma(tA).length === 4, `a capacidade: a turma com 4 recusa o quinto ("${(lotado.recusas || [''])[0]}")`);

    /* sair: tira de todas as ofertas */
    await adm.p.evaluate(async ([u, id]) => { const f = new URLSearchParams(); f.append('usuario_id', id); await fetch(u, { method: 'POST', body: f, credentials: 'same-origin' }); }, [L.BASE + `painel/turmas/sair/${tA}`, String(uid(ras[2]))]);
    L.ok(!naTurma(tA).includes(ras[2]) && ofertasDoAluno(tA, ras[2]) === 0, 'sair da turma: o aluno sai de todas as ofertas dela');

    /* trocar de turma: a nota e as faltas vão junto */
    const mat = (t) => um(`SELECT o.id FROM esc_turma_ofertas x INNER JOIN rm_professores_materias o ON o.id = x.oferta_id WHERE x.turma_id = ${t} AND o.materia = 'Matemática'`);
    sql(`INSERT INTO rm_disciplina_atividade (nomeAtividade, tipo, entrega_arquivo, rm_prof_mat_id, situacao, usuario_cre, data_final, peso) VALUES ('Prova 1 (prova)', 1, 0, ${mat(tA)}, 1, 1, '${ANO}-03-10', 10)`);
    const ativ = um('SELECT MAX(id) FROM rm_disciplina_atividade');
    sql(`INSERT INTO rm_disciplina_atividade_notas (rm, atividade_id, rm_prof_mat_id, nota) VALUES ('${ras[0]}', ${ativ}, ${mat(tA)}, '7.5')`);
    sql(`INSERT INTO rm_disciplina_faltas (rm, rm_prof_mat_id, faltas, faltas_compensadas, usuario_cre) VALUES ('${ras[0]}', ${mat(tA)}, 3, 0, 1)`);
    const trocar = async () => {
      await adm.p.goto(L.BASE + `painel/turmas/ver/${tA}`);
      await Promise.all([adm.p.waitForNavigation(), adm.p.click(`#alunosTurma tr[data-aluno="${uid(ras[0])}"] .vc-trocar button`)]);
      const t = sql(`SELECT CONCAT(IFNULL(oferta_destino_id, 0), '|', faltas, '|', notas) FROM esc_lancamentos_trazidos WHERE rm = '${ras[0]}' AND oferta_origem_id = ${mat(tA)} ORDER BY id DESC LIMIT 1`);
      return { trazido: t, naB: naTurma(tB).includes(ras[0]) && ofertasDoAluno(tB, ras[0]) === E.componentes, foraA: !naTurma(tA).includes(ras[0]) && ofertasDoAluno(tA, ras[0]) === 0 };
    };
    const tr = await trocar();
    L.ok(tr.naB && tr.foraA && tr.trazido.startsWith(`${mat(tB)}|3|`) && /"nota":"7\.5"/.test(tr.trazido),
      `trocar de turma (6º ano A → B): o aluno sai de todas as ofertas da A e entra em todas da B; a nota (7,5) e as 3 faltas de Matemática vão para a Matemática da B`);
    await L.print(adm.p, ETAPA, 'troca-de-turma');
    /* depois do lançamento, o modelo das etapas não muda */
    await adm.p.goto(L.BASE + 'painel/escola-anos/index');
    L.ok(!(await adm.p.$(`.vc-ano[data-ano="${ANO}"] .vc-abrir`)) && /com lançamentos/.test(await adm.p.textContent(`.vc-ano[data-ano="${ANO}"] .card-actions`)), 'com nota lançada no ano, o modelo das etapas não muda (a tela não oferece a troca)');

    /* sabotagens: a troca sem levar o lançado; a entrada sem a capacidade */
    await sabotar('a troca sem levar o lançado', ALUNOS, 'if ($comNota || $r[\'faltas\'] > 0) {', 'if (false) {', async () => {
      await adm.p.evaluate(async ([u, id, d]) => { const f = new URLSearchParams(); f.append('usuario_id', id); f.append('destino_id', d); await fetch(u, { method: 'POST', body: f, credentials: 'same-origin' }); },
        [L.BASE + `painel/turmas/trocar/${tB}`, String(uid(ras[0])), String(tA)]);
      return um(`SELECT COUNT(*) FROM esc_lancamentos_trazidos WHERE rm = '${ras[0]}' AND oferta_origem_id = ${mat(tB)}`) > 0;
    });
    const cheia = naTurma(tA).length;
    sql(`UPDATE esc_turmas SET capacidade = ${cheia} WHERE id = ${tA}`); // a turma cheia
    await sabotar('a entrada sem a capacidade', ALUNOS, "if ($t['capacidade'] !== null && count(self::alunos($turmaId)) >= (int) $t['capacidade']) {", 'if (false) {', async () => {
      const r = await adm.p.evaluate(async ([u, ids]) => { const f = new URLSearchParams(); f.append('fase', 'iniciar'); f.append('alunos', ids);
        await fetch(u, { method: 'POST', body: f, credentials: 'same-origin' }); const g = new URLSearchParams(); g.append('fase', 'passo');
        return (await fetch(u, { method: 'POST', body: g, credentials: 'same-origin' })).json(); }, [L.BASE + `painel/turmas/enturmar/${tA}`, String(uid(ras[5]))]);
      return naTurma(tA).length <= cheia;
    });

    /* as regras da escola: a frequência */
    await adm.p.goto(L.BASE + 'painel/escola-regras/index');
    await L.semErros(adm.p, 'Regras da escola');
    const padrao = await adm.p.isChecked('#frequencia_ano') && (await adm.p.inputValue('#frequencia_minima')) === '75';
    await adm.p.check('#frequencia_componente');
    await adm.p.fill('#frequencia_minima', '80');
    await Promise.all([adm.p.waitForNavigation(), adm.p.click('#salvarRegrasEscola')]);
    const gravou = sql("SELECT GROUP_CONCAT(CONCAT(chave, '=', valor) ORDER BY chave) FROM esc_configuracao");
    await adm.p.check('#frequencia_ano');
    await adm.p.fill('#frequencia_minima', '75');
    await Promise.all([adm.p.waitForNavigation(), adm.p.click('#salvarRegrasEscola')]);
    L.ok(padrao && gravou === 'frequencia.base=componente,frequencia.minima=80.00' && um('SELECT COUNT(*) FROM esc_configuracao') === 0,
      `as regras da escola: o padrão preenchido (no ano, 75%); o ajuste gravado (${gravou}); de volta ao padrão, nada fica gravado`);

    /* os Primeiros passos da escola */
    await adm.p.goto(L.BASE + 'painel/primeiros-passos/index');
    const pp = await adm.p.textContent('.page-body');
    L.ok(/O ano letivo e as etapas/.test(pp) && /As turmas/.test(pp) && /Enturmar/.test(pp) && !/A primeira oferta e o professor/.test(pp), 'os Primeiros passos da escola: ano letivo e etapas, séries e componentes, turmas, professores, alunos, enturmar (sem as fases do ensino superior)');
    await L.print(adm.p, ETAPA, 'primeiros-passos-escola');

    /* o professor só nas ofertas dele */
    const nivelProf = um("SELECT id FROM adms_niveis_acessos WHERE papel = 'professor' LIMIT 1");
    sql(`INSERT INTO adms_usuarios (nome, genero_id, usuario, senha, email, adms_niveis_acesso_id, adms_sits_usuario_id, usuario_cre) VALUES ('Professora Escola (fictícia)', 2, 'prof.escola.prova', '${phpHash(SENHA)}', 'prof.escola@exemplo.invalid', ${nivelProf}, 1, 1)`);
    const prof = um("SELECT id FROM adms_usuarios WHERE usuario = 'prof.escola.prova'");
    sql(`INSERT INTO rm_professores_mat_liga (usuario_id, rm_prof_mat_id, acesso, situacao, usuario_cre) VALUES (${prof}, ${mat(tA)}, 1, 1, 1)`);
    const pr = await entrar(navegador, 'prof.escola.prova', SENHA);
    const minha = await abre(pr.p, `disciplinas/visualizar/${mat(tA)}`);
    const outra = await abre(pr.p, `disciplinas/visualizar/${mat(tB)}`);
    L.ok(minha && !outra, 'o professor abre a Matemática da turma dele e não a de outra turma');
    await pr.ctx.close();

    /* D. o Moodle */
    if (MODO_FALSO || !MURL || !MCHAVE) {
      L.ok(false, 'o Moodle de teste: esta prova roda contra o Moodle de teste (VCOSTAEDU_MOODLE_URL e VCOSTAEDU_MOODLE_CHAVE); o servidor falso não cobre a criação e a renomeação em lote');
    } else {
      sql("UPDATE adms_parametros SET valor = 'prova-' WHERE chave = 'moodle.prefixo'");
      await adm.p.goto(L.BASE + 'painel/moodle/conexao');
      await adm.p.fill('#url', MURL);
      await adm.p.fill('#chave', MCHAVE);
      /* Onda 8, Etapa 2 (D-68): na escola, o padrão é o e-mail da escola, que pede o domínio (fictício) */
      await adm.p.fill('#emailDominio', 'alunos.escola-prova.invalid');
      await Promise.all([adm.p.waitForNavigation(), adm.p.click('#salvarMoodle')]);
      await adm.p.goto(L.BASE + 'painel/moodle/conexao');
      await Promise.all([adm.p.waitForNavigation(), adm.p.click('#testarMoodle')]);
      const testou = !!(await adm.p.$('#testeOk'));
      await adm.p.goto(L.BASE + 'painel/moodle/conexao');
      const temCat = await adm.p.$$eval('#categoria_id option', (os) => os.some((o) => o.textContent.trim() === 'VCOSTAEDU provas'));
      if (temCat) { await adm.p.selectOption('#categoria_id', { label: 'VCOSTAEDU provas' }); } else { await adm.p.fill('#categoria_nova', 'VCOSTAEDU provas'); }
      /* email_modo "aluno": esta prova manda os e-mails da rodada, como antes da Onda 8 */
      for (const [n, v] of Object.entries({ disciplina_modo: 'criar', trancamento: 'suspender', professor_enviar: '0', envio: 'botao', email_modo: 'aluno' })) { await adm.p.check(`input[name="${n}"][value="${v}"]`).catch(() => {}); }
      await Promise.all([adm.p.waitForNavigation(), adm.p.click('#salvarMoodle')]);
      const conectado = testou && um('SELECT IFNULL(categoria_id, 0) FROM adms_moodle_conexao WHERE id = 1') > 0;
      const rasM = ['941005', '941007', '941008']; // sem turma no ano (a 941006 entrou na A pela sabotagem da capacidade)
      cli('preparar', ...rasM.map((r) => 'prova-' + r));
      /* a turma 7º ano M precisa dos alunos no 7º ano: a matrícula é a mesma (o segmento); a turma põe a série */
      await adm.p.goto(L.BASE + `painel/turmas/ver/${tM}`);
      await adm.p.evaluate(async ([u, ids]) => { const f = new URLSearchParams(); f.append('fase', 'iniciar'); f.append('alunos', ids); await fetch(u, { method: 'POST', body: f, credentials: 'same-origin' }); },
        [L.BASE + `painel/turmas/enturmar/${tM}`, rasM.map(uid).join(',')]);
      await adm.p.goto(L.BASE + `painel/turmas/ver/${tM}`);
      await adm.p.click('#continuarLote');
      await adm.p.waitForSelector('#progressoTurma[data-situacao="concluida"]', { timeout: 120000 });
      const naFila = um("SELECT COUNT(*) FROM adms_moodle_envios WHERE situacao <> 'enviado'");
      /* Envios ao Moodle → Enviar tudo o que falta (em passos) */
      await adm.p.goto(L.BASE + 'painel/moodle-envios/listar');
      await adm.p.click('#enviarTudo');
      await adm.p.waitForSelector('#progressoEnvios[data-situacao="concluida"], #progressoEnvios[data-situacao="falhou"]', { timeout: 300000 });
      const ofertasM = sql(`SELECT oferta_id FROM esc_turma_ofertas WHERE turma_id = ${tM} ORDER BY oferta_id`).split('\n').map(Number);
      const cursos = ofertasM.map(cursoMoodle);
      const nomes = cursos.map((c) => (c ? c.fullname : ''));
      const inscritos = cursos.map((c) => { if (!c) { return 0; } const r = moodle('core_enrol_get_enrolled_users', { courseid: c.id }); return r.ok ? r.dados.filter((u) => /^prova-9410/.test(u.idnumber || '')).length : 0; });
      L.ok(conectado && naFila > 0 && cursos.every(Boolean) && nomes.every((n) => /7º ano M/.test(n)) && inscritos.every((n) => n === 3),
        `o Moodle: a turma inteira (${rasM.length} alunos) foi pela fila e pelo "Enviar tudo o que falta"; ${cursos.filter(Boolean).length} disciplinas criadas com a série e a turma no nome ("${nomes[0]}"), cada uma com os ${rasM.length} alunos — conferido no Moodle [conectado ${conectado}, na fila ${naFila}, inscritos ${inscritos.join('/')}]`);
      await L.print(adm.p, ETAPA, 'moodle-turma-enviada');
      /* renomear a turma: o nome acompanha */
      await adm.p.goto(L.BASE + `painel/turmas/editar/${tM}`);
      await adm.p.fill('#nome', 'N');
      await Promise.all([adm.p.waitForNavigation(), adm.p.click('#salvarTurma')]);
      await adm.p.goto(L.BASE + 'painel/moodle-envios/listar');
      await adm.p.click('#enviarTudo');
      await adm.p.waitForSelector('#progressoEnvios[data-situacao="concluida"], #progressoEnvios[data-situacao="falhou"]', { timeout: 300000 });
      const renomeados = ofertasM.map(cursoMoodle).map((c) => (c ? c.fullname : ''));
      L.ok(renomeados.every((n) => /7º ano N/.test(n) && !/7º ano M/.test(n)), `a turma renomeada (M → N): o nome das disciplinas no Moodle acompanha ("${renomeados[0]}")`);
      const lim = cli('limpar');
      console.log(`  (no Moodle de teste ficaram: ${ofertasM.length} disciplina(s) de prova na categoria "VCOSTAEDU provas" e os usuários de prova — ${lim.suspensos ?? '?'} suspenso(s), ${lim.desenturmados ?? '?'} inscrição(ões) tirada(s))`);
    }
    await adm.ctx.close();
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message);
  } finally {
    await navegador.close();
  }
  process.exit(L.resumo('onda4/etapa2') ? 0 : 1);
})();
