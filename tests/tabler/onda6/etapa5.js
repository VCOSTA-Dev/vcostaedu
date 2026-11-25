/*
 * PROVA DA ONDA 6, ETAPA 5 — a escola: o que ficou para trás.
 *   A. no ENSINO SUPERIOR (o ciclo de antes): os relatórios não têm a turma nem a etapa;
 *   B. SÉRIES LIVRES: uma instalação nova de escola com um segmento dos anos iniciais e o 3º ano (dois componentes), o
 *      ano em bimestres, a turma "3º ano A", três alunos enturmados, uma prova por bimestre, as notas e o boletim com a
 *      média anual; a tela da estrutura diz que vale qualquer série e que a avaliação é por nota;
 *   C. a CHAMADA POR AULA: em Regras da escola, "De onde vêm as faltas" = a chamada (os componentes abertos sem falta
 *      passam); a chamada do dia no componente vira as faltas da etapa da data (a soma), o total do componente é a soma
 *      das etapas e os encontros continuam as horas da matriz; o lançamento direto recusado (e a tela das etapas sem o
 *      campo); a etapa fechada recusa a chamada com data nela (gravar e excluir); sabotagem: sem a consolidação da escola,
 *      a prova reprova;
 *   D. os RELATÓRIOS da escola: cada um, com os filtros que tem (guardado em saida/ para o retorno); a turma recorta (a
 *      turma B, com um aluno); a etapa mostra só as atividades dela e a nota da etapa; sabotagem: a etapa ignorada;
 *   E. o E-MAIL MONTADO no Moodle (um Moodle falso da prova). Desde a Onda 8, Etapa 2 (D-68), os três modos: o padrão na
 *      escola é "sempre o e-mail da escola", e sem o domínio o aluno fica "sem domínio" (nenhuma conta criada); o modo
 *      "o e-mail do aluno, ou o da escola" (a opção desta etapa, ligada) em Conectar ao Moodle (o domínio conferido): o
 *      aluno vai com RA@domínio; o e-mail verdadeiro substitui o montado no envio seguinte; sabotagem: sem o montado, a
 *      prova reprova.
 * No fim, o banco de prova é uma instalação de escola: a bateria roda o ciclo depois. Cada arquivo sabotado volta igual.
 */
const fs = require('fs');
const path = require('path');
const http = require('http');
const crypto = require('crypto');
const { execFileSync, spawnSync } = require('child_process');
const L = require('../lib');

const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste$/.test(BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
const MYSQL = process.env.VCOSTAEDU_MYSQL || 'C:/xampp/mysql/bin/mysql.exe';
const PHP = process.env.VCOSTAEDU_PHP || 'C:/xampp/php/php.exe';
const sql = (q) => execFileSync(MYSQL, ['-u', 'root', '-N', '--default-character-set=utf8mb4', BANCO], { input: Buffer.from(q, 'utf8') }).toString().replace(/\r/g, '').trim();
const um = (q) => +sql(q);
const ETAPA = 'onda6/etapa5';
const SENHA = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const ANO = new Date().getFullYear();
const API = L.BASE + 'api/v1';
const SAIDA = path.join(__dirname, '..', 'saida');
const CHAMADA = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'faltas', 'AdmsChamada.php');
const FONTE = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'relatorios', 'AdmsRelatorioFonte.php');
const MOODLE = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'moodle', 'AdmsMoodle.php');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const runner = (args) => { const r = spawnSync(PHP, [path.join(__dirname, 'etapa4_runner.php'), ...args], { encoding: 'utf8', cwd: L.RAIZ }); try { return JSON.parse((r.stdout || '').trim().split('\n').pop()); } catch (e) { return { erro: (r.stderr || r.stdout || '').slice(-300) }; } };
/* assíncrono: o Moodle falso da prova roda neste mesmo processo e precisa responder enquanto o comando espera */
const moodleCli = (...args) => new Promise((ok) => {
  const c = require('child_process').spawn(PHP, [path.join(L.RAIZ, 'tests', 'tabler', 'onda3', 'moodle_cli.php'), ...args], { cwd: L.RAIZ });
  let out = '';
  c.stdout.on('data', (d) => { out += d; });
  c.stderr.on('data', (d) => { out += d; });
  c.on('close', () => { try { ok(JSON.parse(out.trim().split('\n').pop())); } catch (e) { ok({ erro: out.slice(-300) }); } });
});

async function entrar(navegador, usuario = 'administrador', senha = SENHA) {
  const ctx = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' });
  const p = await L.pagina(ctx);
  p.on('dialog', (d) => d.accept());
  await L.confirmarSozinho(p); // D-100: a confirmação é a janela do sistema
  await p.goto(L.BASE + 'painel/login/acesso');
  await p.fill('input[name="usuario"]', usuario);
  await p.fill('input[name="senha"]', senha);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
  return { ctx, p };
}
async function postar(p, rota, campos) {
  return p.evaluate(async ([u, c]) => { const f = new URLSearchParams(); for (const [k, v] of Object.entries(c)) { f.append(k, v); }
    const r = await fetch(u, { method: 'POST', body: f, credentials: 'same-origin' }); return r.text(); }, [L.BASE + 'painel/' + rota, campos]);
}
const texto = (p) => p.$eval('.page-body', (el) => el.innerText.replace(/\s+/g, ' ')).catch(() => '');
function sabotar(arquivo, de, para) {
  const orig = fs.readFileSync(arquivo, 'utf8');
  if (!orig.includes(de)) { throw new Error('a sabotagem não achou o trecho em ' + path.basename(arquivo)); }
  fs.writeFileSync(arquivo, orig.replace(de, para));
  return () => fs.writeFileSync(arquivo, orig);
}
function criarChave() {
  const chave = 'vce_' + crypto.randomBytes(20).toString('hex');
  sql(`INSERT INTO adms_api_chaves (nome, prefixo, hash, escopo, permissoes, situacao, usuario_id) VALUES ('Prova escola', '${chave.slice(0, 12)}', '${crypto.createHash('sha256').update(chave).digest('hex')}', 'escrita', 'alunos:gravar matriculas:gravar enturmacao:gravar cadastros:ler', 1, 1)`);
  return chave;
}
async function api(metodo, rota, chave, corpo) {
  const r = await fetch(API + rota, { method: metodo, headers: { Authorization: 'Bearer ' + chave, 'Content-Type': 'application/json' }, body: corpo ? JSON.stringify(corpo) : undefined });
  return { status: r.status, json: await r.json().catch(() => null) };
}
const REGRAS = { media_minima: '6', recuperacao_regra: 'maior', anual_composicao: 'simples', anual_pesos: '', frequencia_base: 'ano', frequencia_minima: '75' };

(async () => {
  const { navegador } = await L.abrir();
  const shas = Object.fromEntries([CHAMADA, FONTE, MOODLE].map((f) => [f, sha(f)]));
  let servidor = null;
  try {
    console.log('\n== Onda 6, Etapa 5 — a escola: o que ficou para trás');
    /* A. no ensino superior: nenhum relatório com a turma ou a etapa */
    const cred = JSON.parse(fs.readFileSync(path.join(SAIDA, 'credenciais.json'), 'utf8'));
    let s = await entrar(navegador, 'administrador', cred.painel.administrador);
    let semFiltro = true;
    for (const slug of ['painel-academico', 'resultado-disciplina', 'frequencia-minimo', 'alunos-risco']) {
      await s.p.goto(L.BASE + 'painel/relatorios/' + slug);
      if (await s.p.$('#filtrosRelatorio select[name="turma"], #filtrosRelatorio select[name="etapa"]')) { semFiltro = false; }
    }
    L.ok(semFiltro, 'no ensino superior, os relatórios não têm a turma nem a etapa');
    await s.ctx.close();

    /* B. séries livres: uma escola nova, o 3º ano */
    spawnSync(PHP, [path.join(L.RAIZ, 'tests', 'tabler', 'reset.php')], { encoding: 'utf8', cwd: L.RAIZ });
    const inst = spawnSync('node', [path.join(L.RAIZ, 'tests', 'tabler', 'instalar.js'), 'normal'], { encoding: 'utf8', cwd: L.RAIZ, env: { ...process.env, VCOSTAEDU_TIPO: 'escola', VCOSTAEDU_ETAPA_PROVA: ETAPA }, maxBuffer: 1 << 26 });
    L.ok(inst.status === 0, 'a instalação nova de escola');
    sql(`INSERT INTO adms_periodos_letivos (ano, situacaodisciplina, situacaomodelo, situacao, usuario_cre) VALUES (${ANO}, 1, 1, 1, 1)`);
    const sede = um("SELECT id FROM rm_polos WHERE nome = 'Sede' LIMIT 1");
    sql(`INSERT INTO rm_cursos (nome, abreviatura, periodo, localidade, filial, situacao, usuario_cre) VALUES ('Ensino Fundamental — anos iniciais (prova)', 'EFAI', 1, ${sede}, ${sede}, 1, 1)`);
    const curso = um('SELECT MAX(id) FROM rm_cursos');
    sql(`INSERT INTO rm_cursos_grades (curso_id, versao, situacao, usuario_cre) VALUES (${curso}, 1, 1, 1)`);
    const grade = um('SELECT MAX(id) FROM rm_cursos_grades');
    sql(`INSERT INTO rm_cursos_turma_series (curso_id, grade_id, periodo, codnome, ordem, situacao, usuario_cre) VALUES (${curso}, ${grade}, 1, '3º ano', 1, 1, 1)`);
    const serie = um('SELECT MAX(id) FROM rm_cursos_turma_series');
    for (const [i, [nome, ch]] of [['Matemática', 200], ['Língua Portuguesa', 240]].entries()) {
      sql(`INSERT INTO rm_materias (nome, abreviacao, carga_horaria, situacao, usuario_cre) VALUES ('${nome}', 'C${i}', '${ch}', 1, 1)`);
      sql(`INSERT INTO uni_rh_cursos_disciplinas_grade (grade_id, modulo_id, materia_id, cargahoraria, ordem, usuario_cre) VALUES (${grade}, ${serie}, (SELECT MAX(id) FROM rm_materias), ${ch}, ${i + 1}, 1)`);
    }
    s = await entrar(navegador);
    await s.p.goto(L.BASE + 'painel/escola/index');
    const estrutura = await texto(s.p);
    L.ok(/3º ano/.test(estrutura) && /qualquer série, do 1º ano do Ensino Fundamental/.test(estrutura) && /avaliação é por nota/.test(estrutura),
      'a estrutura da escola mostra o 3º ano e diz que vale qualquer série e que a avaliação é por nota');
    await postar(s.p, 'escola-anos/abrir', { ano: ANO, modelo: 'bimestre' });
    const etapas = sql(`SELECT CONCAT(id, '|', ordem, '|', inicio) FROM esc_etapas WHERE ano = ${ANO} ORDER BY ordem`).split('\n').map((l) => { const [id, ordem, inicio] = l.split('|'); return { id: +id, ordem: +ordem, inicio }; });
    const dia = (e, mais) => new Date(new Date(e.inicio + 'T12:00:00Z').getTime() + mais * 86400000).toISOString().slice(0, 10);
    await postar(s.p, 'turmas/cadastrar', { ano: ANO, modulo_id: serie, nome: 'A' });
    const turma = um('SELECT MAX(id) FROM esc_turmas');
    await postar(s.p, 'turmas/cadastrar', { ano: ANO, modulo_id: serie, nome: 'B' });
    const turmaB = um('SELECT MAX(id) FROM esc_turmas');
    const of = (t, m) => um(`SELECT o.id FROM esc_turma_ofertas x INNER JOIN rm_professores_materias o ON o.id = x.oferta_id WHERE x.turma_id = ${t} AND o.materia ${m}`);
    const mat = of(turma, "= 'Matemática'");
    const lp = of(turma, "<> 'Matemática'");
    const chave = criarChave();
    const forma = um('SELECT id FROM rm_matricula_tipos WHERE situacao = 1 ORDER BY id LIMIT 1');
    const RA = ['953001', '953002', '953003'];
    const RAB = '953004';
    for (const [i, ra] of [...RA, RAB].entries()) {
      await api('POST', '/alunos', chave, { ra, nome: `Aluno Terceiro Ano ${i + 1} Exemplo`, sexo: 'feminino', nascimento: '2018-04-12' });
      await api('POST', '/matriculas', chave, { ra, curso_id: curso, ano: ANO, forma_ingresso_id: forma, data_ingresso: `${ANO}-02-01` });
    }
    const enturmar = async (t, ras) => {
      const ids = ras.map((ra) => um(`SELECT id FROM col_usuarios WHERE rm = '${ra}'`)).join(',');
      await s.p.goto(L.BASE + `painel/turmas/ver/${t}`);
      await s.p.evaluate(async ([u2, x]) => { const f = new URLSearchParams(); f.append('fase', 'iniciar'); f.append('alunos', x); await fetch(u2, { method: 'POST', body: f, credentials: 'same-origin' });
        for (let i = 0; i < 5; i++) { const g = new URLSearchParams(); g.append('fase', 'passo'); const r = await (await fetch(u2, { method: 'POST', body: g, credentials: 'same-origin' })).json(); if (r.concluida) { break; } } }, [L.BASE + `painel/turmas/enturmar/${t}`, ids]);
    };
    await enturmar(turma, RA);
    await enturmar(turmaB, [RAB]);
    const NOTAS = { [RA[0]]: ['7.0', '8.0', '6.0', '9.0'], [RA[1]]: ['5.0', '6.0', '7.0', '8.0'], [RA[2]]: ['9.0', '9.0', '8.0', '10.0'] };
    for (const o of [mat, lp]) {
      for (const e of etapas) {
        sql(`INSERT INTO rm_disciplina_atividade (nomeAtividade, tipo, entrega_arquivo, rm_prof_mat_id, situacao, usuario_cre, data_inicial, data_final, peso) VALUES ('Prova do ${e.ordem}º bimestre', 1, 0, ${o}, 1, 1, '${dia(e, 5)}', '${dia(e, 5)}', 10)`);
        const at = um('SELECT MAX(id) FROM rm_disciplina_atividade');
        for (const ra of RA) { sql(`INSERT INTO rm_disciplina_atividade_notas (rm, atividade_id, rm_prof_mat_id, nota) VALUES ('${ra}', ${at}, ${o}, '${o === mat ? NOTAS[ra][e.ordem - 1] : '7.0'}')`); }
      }
    }
    await s.p.goto(L.BASE + `painel/academicos/boletim/${RA[0]}`);
    await L.semErros(s.p, 'o boletim do 3º ano');
    const linha = await s.p.$eval(`.tabela-boletim-escola tr[data-oferta="${mat}"]`, (tr) => tr.textContent.replace(/\s+/g, ' ').trim()).catch(() => '');
    const bol = await texto(s.p);
    L.ok(um(`SELECT COUNT(*) FROM esc_turma_alunos WHERE turma_id = ${turma} AND saida IS NULL`) === 3 && /7,5/.test(linha) && /3º ano/.test(bol),
      `o 3º ano: a turma "3º ano A" com 3 alunos, as notas por bimestre e o boletim com a média anual (7, 8, 6, 9 → 7,5: "${linha.slice(0, 80)}")`);

    /* C. a chamada por aula */
    const regrasTxt = await postar(s.p, 'escola-regras/salvar', { ...REGRAS, frequencia_fonte: 'chamada' }); // a resposta é a tela das regras, com a mensagem
    const fontes = sql(`SELECT GROUP_CONCAT(DISTINCT IFNULL(o.frequencia_fonte, 'lancamento')) FROM rm_professores_materias o INNER JOIN esc_turma_ofertas x ON x.oferta_id = o.id`);
    L.ok(fontes === 'chamada' && sql("SELECT valor FROM esc_configuracao WHERE chave = 'frequencia.fonte'") === 'chamada' && /As faltas vêm agora de: a chamada por aula/.test(regrasTxt),
      `Regras da escola, "De onde vêm as faltas" = a chamada: os componentes abertos sem falta passaram (${fontes}); a mensagem diz o que mudou`);
    const encAntes = um(`SELECT encontros FROM rm_professores_materias WHERE id = ${mat}`);
    const aula = async (o, data, ausentes) => postar(s.p, `chamada/encontro/${o}`, { SalvarChamada: '1', data, ...Object.fromEntries(ausentes.map((ra) => [`ausente[${ra}]`, '1'])) });
    await aula(mat, dia(etapas[0], 1), [RA[0]]);
    await aula(mat, dia(etapas[0], 2), [RA[0], RA[1]]);
    await aula(mat, dia(etapas[1], 1), [RA[0]]);
    const fe = (o, ra, e) => um(`SELECT IFNULL(MAX(faltas), -1) FROM esc_faltas_etapa WHERE oferta_id = ${o} AND rm = '${ra}' AND etapa_id = ${e.id}`);
    const total = (o, ra) => um(`SELECT IFNULL(MAX(faltas), -1) FROM rm_disciplina_faltas WHERE rm_prof_mat_id = ${o} AND rm = '${ra}'`);
    L.ok(fe(mat, RA[0], etapas[0]) === 2 && fe(mat, RA[0], etapas[1]) === 1 && fe(mat, RA[0], etapas[2]) === 0 && fe(mat, RA[1], etapas[0]) === 1 && total(mat, RA[0]) === 3 && total(mat, RA[2]) === 0
      && um(`SELECT encontros FROM rm_professores_materias WHERE id = ${mat}`) === encAntes && encAntes === 200,
      `a chamada do dia vira a falta da etapa da data: ${RA[0]} faltou 2 aulas no 1º bimestre e 1 no 2º → ${fe(mat, RA[0], etapas[0])} e ${fe(mat, RA[0], etapas[1])}, total ${total(mat, RA[0])}; os encontros continuam as horas da matriz (${encAntes})`);
    const direto = await postar(s.p, `escola-notas/faltas/${mat}`, { etapa_id: etapas[0].id, [`faltas[${RA[0]}]`]: '9' });
    await s.p.goto(L.BASE + `painel/escola-notas/etapas/${mat}?etapa=${etapas[0].id}`);
    const campoDesligado = await s.p.$eval(`input[name="faltas[${RA[0]}]"]`, (i) => i.disabled).catch(() => false);
    L.ok(fe(mat, RA[0], etapas[0]) === 2 && !!(await s.p.$('#faltasDaChamada')) && campoDesligado && !/faltas/.test(await s.p.textContent('#legendaGravar').catch(() => '')), // D-100: o Gravar só da recuperação
      'o lançamento direto da falta da etapa é recusado (uma fonte só); a tela das etapas mostra que as faltas vêm da chamada, sem o campo');
    await postar(s.p, `escola-notas/fechar/${mat}`, { etapa_id: etapas[0].id });
    const encs = um(`SELECT COUNT(*) FROM rm_oferta_encontros WHERE rm_prof_mat_id = ${mat}`);
    await aula(mat, dia(etapas[0], 3), [RA[2]]);
    const umaDoFechado = um(`SELECT MIN(id) FROM rm_oferta_encontros WHERE rm_prof_mat_id = ${mat}`);
    await postar(s.p, `chamada/excluir/${umaDoFechado}`, { ExcluirEncontro: '1' });
    L.ok(um(`SELECT COUNT(*) FROM esc_fechamentos WHERE oferta_id = ${mat} AND situacao = 'vigente'`) === 1 && um(`SELECT COUNT(*) FROM rm_oferta_encontros WHERE rm_prof_mat_id = ${mat}`) === encs && fe(mat, RA[2], etapas[0]) === 0,
      'a etapa fechada recusa a chamada com data nela: nem aula nova, nem excluir uma de antes');
    /* sabotagem: sem a consolidação da escola (a do ensino superior: os encontros viram as aulas e a etapa não recebe nada) */
    const volta1 = sabotar(CHAMADA, '        if (Calc::turmaDaOferta($ofertaId)) {\n            self::consolidarEscola($ofertaId);\n            return;\n        }\n', '');
    try {
      await aula(lp, dia(etapas[1], 1), [RA[1]]);
      const pegou = !(fe(lp, RA[1], etapas[1]) === 1 && um(`SELECT encontros FROM rm_professores_materias WHERE id = ${lp}`) === 240);
      L.ok(pegou, `sabotagem (sem a consolidação da escola): a prova reprova — a falta da etapa ${fe(lp, RA[1], etapas[1])}, os encontros ${um(`SELECT encontros FROM rm_professores_materias WHERE id = ${lp}`)}`);
    } finally { volta1(); }
    L.ok(sha(CHAMADA) === shas[CHAMADA], 'a chamada voltou igual (sha256)');

    /* D. os relatórios da escola */
    const lista = ['painel-academico', 'alunos-situacao', 'lista-turma', 'qualidade-cadastro', 'mapa-notas', 'resultado-disciplina', 'distribuicao-notas', 'pendencias-lancamento',
      'desempenho-aluno', 'frequencia-oferta', 'frequencia-minimo', 'alunos-risco', 'documentos-emitidos', 'reaberturas', 'trocas-regra', 'exportacoes', 'usuarios-acesso',
      'previsao-conclusao', 'aptos-concluir', 'situacao-tcc', 'coorte-ingresso', 'concluintes'];
    const quadro = [];
    for (const slug of lista) {
      await s.p.goto(L.BASE + 'painel/relatorios/' + slug);
      const abre = s.p.url().includes('/relatorios/' + slug) && !!(await s.p.$('#filtrosRelatorio'));
      const filtros = abre ? await s.p.$$eval('#filtrosRelatorio select[name], #filtrosRelatorio input[name]', (l) => l.map((x) => x.name).filter((n) => !/^(colunas|salvo|detalhe)$/.test(n))) : [];
      quadro.push({ slug, naEscola: abre, filtros: [...new Set(filtros)] });
    }
    fs.writeFileSync(path.join(SAIDA, 'onda6-etapa5-relatorios.json'), JSON.stringify(quadro, null, 1));
    const com = (sl, k) => (quadro.find((x) => x.slug === sl) || { filtros: [] }).filtros.includes(k);
    L.ok(com('painel-academico', 'turma') && com('painel-academico', 'etapa') && com('resultado-disciplina', 'turma') && com('mapa-notas', 'etapa') && com('frequencia-minimo', 'etapa')
      && com('alunos-risco', 'turma') && com('alunos-situacao', 'turma') && !quadro.find((x) => x.slug === 'concluintes').naEscola,
      `os relatórios da escola: ${quadro.filter((x) => x.naEscola).length} aparecem; ${quadro.filter((x) => x.filtros.includes('turma')).length} com a turma, ${quadro.filter((x) => x.filtros.includes('etapa')).length} com a etapa (a lista vai para o retorno)`);
    const ativos = async (t) => { await s.p.goto(L.BASE + `painel/relatorios/painel-academico?turma=${t}`); return (await texto(s.p)).match(/(\d+) Alunos ativos/); };
    const ativosA = await ativos(turma);
    const ativosB = await ativos(turmaB);
    L.ok(ativosA && +ativosA[1] === 3 && ativosB && +ativosB[1] === 1, `a turma recorta: o painel acadêmico da turma A, ${ativosA && ativosA[1]} alunos ativos; da turma B, ${ativosB && ativosB[1]}`);
    const mapa = async (q) => { await s.p.goto(L.BASE + `painel/relatorios/mapa-notas?oferta=${mat}${q}`); return texto(s.p); };
    const semEtapa = await mapa('');
    const naEtapa = await mapa(`&etapa=${etapas[0].id}`);
    const naEtapa2 = await mapa(`&etapa=${etapas[3].id}`);
    L.ok(/Prova do 2º bimestre/i.test(semEtapa) && /Prova do 1º bimestre/i.test(naEtapa) && !/Prova do 2º bimestre/i.test(naEtapa) && /Prova do 4º bimestre/i.test(naEtapa2) && !/Prova do 1º bimestre/i.test(naEtapa2),
      'a etapa: o mapa de notas mostra só as atividades da etapa escolhida (sem a etapa, todas)');
    const volta2 = sabotar(FONTE, "        if (self::$etapa === null || empty($calc['escola'])", "        if (true || self::$etapa === null || empty($calc['escola'])");
    let sab2 = '';
    try { sab2 = await mapa(`&etapa=${etapas[0].id}`); } finally { volta2(); }
    L.ok(/Prova do 2º bimestre/i.test(sab2), 'sabotagem (a etapa ignorada no cálculo dos relatórios): a prova reprova — o mapa da etapa traz as outras');
    L.ok(sha(FONTE) === shas[FONTE], 'a Fonte dos relatórios voltou igual (sha256)');

    /* E. o e-mail montado no Moodle (o Moodle falso) */
    const chamadas = [];
    servidor = http.createServer((rq, rs) => {
      let corpo = '';
      rq.on('data', (c) => { corpo += c; });
      rq.on('end', () => {
        const q = new URLSearchParams(corpo);
        const fn = q.get('wsfunction') || '';
        chamadas.push({ fn, corpo: decodeURIComponent(corpo) });
        let r = { exception: 'moodle_exception', errorcode: 'provafalsa', message: 'Moodle falso da prova' };
        if (fn === 'core_user_get_users_by_field' || fn === 'local_vcostaedu_find_users_by_email') { r = []; }
        if (fn === 'core_user_create_users') { r = [{ id: 900 + chamadas.length, username: 'prova' }]; }
        if (fn === 'core_user_update_users') { r = null; }
        rs.writeHead(200, { 'Content-Type': 'application/json' });
        rs.end(JSON.stringify(r));
      });
    });
    await new Promise((ok) => servidor.listen(0, '127.0.0.1', ok));
    const url = `http://localhost:${servidor.address().port}`;
    runner(['moodle-falso', url]);
    const aluno = (ra) => um(`SELECT id FROM col_usuarios WHERE rm = '${ra}'`);
    const criados = () => chamadas.filter((c) => c.fn === 'core_user_create_users');
    /* Onda 8, Etapa 2 (D-68): sem escolha gravada, o padrão da escola ("sempre o e-mail da escola"); sem o domínio, nada vai */
    const padrao = sql("SELECT valor FROM adms_parametros WHERE chave = 'moodle.email_modo'");
    const r0 = await moodleCli('enviar', 'aluno', String(aluno(RA[0])), '-');
    L.ok(padrao === '' && r0.codigo === 'sem_dominio' && criados().length === 0, `o padrão na escola (o e-mail da escola), sem o domínio: o aluno fica "sem domínio" e nenhuma conta é criada (${r0.situacao}/${r0.codigo})`);
    const salvar = (campos) => postar(s.p, 'moodle/salvar', { url, chave: '', disciplina_modo: 'criar', trancamento: 'suspender', professor_enviar: '1', professor_papel: 'edicao', envio: 'automatico', ...campos });
    await salvar({ email_modo: 'aluno_ou_escola', email_dominio: 'sem ponto' });
    const recusou = sql("SELECT valor FROM adms_parametros WHERE chave = 'moodle.email_modo'") === '';
    await salvar({ email_modo: 'aluno_ou_escola', email_dominio: 'Alunos.Escola-Prova.invalid' });
    await s.p.goto(L.BASE + 'painel/moodle/conexao');
    const tela = !!(await s.p.$('#emailModo #emailModoAlunoOuEscola:checked')) && (await s.p.inputValue('#emailDominio')) === 'alunos.escola-prova.invalid';
    const r1 = await moodleCli('enviar', 'aluno', String(aluno(RA[0])), '-');
    const criou = criados().pop();
    const montado = `${RA[0]}@alunos.escola-prova.invalid`;
    L.ok(recusou && tela && r1.situacao === 'enviado' && criou && criou.corpo.includes(`[email]=${montado}`),
      `ligado em Conectar ao Moodle (o domínio sem ponto recusado): o aluno sem e-mail vai com ${montado} (${r1.situacao})`);
    sql(`UPDATE col_usuarios SET email = 'aluna.verdadeira.${RA[0]}@exemplo.invalid' WHERE rm = '${RA[0]}'`);
    await moodleCli('enviar', 'aluno', String(aluno(RA[0])), '-');
    const atualizou = chamadas.filter((c) => c.fn === 'core_user_update_users').pop();
    L.ok(atualizou && atualizou.corpo.includes(`aluna.verdadeira.${RA[0]}@exemplo.invalid`), 'o e-mail verdadeiro, cadastrado depois, substitui o montado no envio seguinte do aluno');
    const volta3 = sabotar(MOODLE, '        $montado = self::emailMontado($rm);', "        $montado = '';");
    let r3 = {};
    const antesSab = criados().length;
    try { r3 = await moodleCli('enviar', 'aluno', String(aluno(RA[1])), '-'); } finally { volta3(); }
    L.ok(r3.codigo === 'sem_dominio' && criados().length === antesSab, `sabotagem (sem o e-mail montado): a prova reprova — o aluno ficou "${r3.codigo}"`);
    L.ok(sha(MOODLE) === shas[MOODLE], 'o Moodle voltou igual (sha256)');
    await s.ctx.close();
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    for (const [f, h] of Object.entries(shas)) { if (sha(f) !== h) { console.log(`  [ALERTA] ${path.basename(f)} não voltou igual`); } }
    if (servidor) { runner(['moodle-voltar']); servidor.close(); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
