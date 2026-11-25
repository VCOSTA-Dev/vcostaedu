/*
 * ONDA 4, ETAPA 3 — a escola: a avaliação (docs/ESCOLA.md, D-48).
 *   A. a função pura (\Core\MediaEscola, tests/tabler/onda4/media_escola.php): 4,0 com recuperação 5,0 → 5,0; 4,5 com
 *      recuperação 4,0 → 4,5; a média das duas; a maior com teto na média mínima; a média anual simples e com pesos;
 *      reprovado por falta com nota suficiente; reprovado por nota; o conselho aprovando (a nota não muda; a memória
 *      mostra); a etapa sem nota no fechamento do ano (pendente); a frequência no total de horas do ano;
 *   B. no ENSINO SUPERIOR: as telas das etapas e do conselho não existem;
 *   C. a ESCOLA, ponta a ponta (uma instalação nova): a turma com 4 alunos e Matemática com uma prova por bimestre; a
 *      recuperação pela tela (só quem está abaixo da média; os exemplos do pedido); a regra da recuperação e os pesos
 *      trocados nas Regras da escola mudam o número; as faltas da etapa (o total do componente é a soma) e a reprovação
 *      por falta com nota suficiente (a frequência no total de horas do ano); fechar as etapas na ordem; a etapa fechada
 *      recusa nota, falta e recuperação (com a razão); reabrir com motivo guarda o substituído; o ano só fecha com as
 *      etapas fechadas; o conselho aprova o reprovado por nota (observação obrigatória; a nota não muda); a auditoria com
 *      a etapa; o professor só nas turmas dele; o MESMO número na tela das etapas, no boletim do painel, no PDF e no
 *      portal; o portal mostra só as etapas liberadas;
 *   sabotagens: a recuperação que não sobe a nota; a trava da etapa fechada desligada — a prova reprova; o arquivo volta.
 *
 *   node tests/tabler/onda4/etapa3.js   (no fim, o banco de prova é uma instalação de escola: rode o ciclo depois)
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
/* pela entrada padrão: no Windows, o texto com acento passado como argumento chega trocado */
const sql = (q) => execFileSync(MYSQL, ['-u', 'root', '-N', '--default-character-set=utf8mb4', BANCO], { input: Buffer.from(q, 'utf8') }).toString().replace(/\r/g, '').trim();
const um = (q) => +sql(q);
const ETAPA = 'onda4/etapa3';
const SENHA = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const ANO = new Date().getFullYear();
const API = L.BASE + 'api/v1';
const MEDIA = path.join(L.RAIZ, 'core', 'MediaEscola.php');
const NOTAS = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'escola', 'AdmsEtapaNotas.php');

async function entrar(navegador, usuario = 'administrador', senha = SENHA, app = 'painel') {
  const ctx = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' });
  const p = await L.pagina(ctx);
  await p.goto(L.BASE + app + '/login/acesso');
  await p.fill('input[name="usuario"]', usuario);
  await p.fill('input[name="senha"]', senha);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
  return { ctx, p };
}
async function abre(p, rota) {
  await p.goto(L.BASE + 'painel/' + rota);
  return p.url().includes('/painel/' + rota) && !(await p.$('input[name="SendLogin"]')) && !(await p.$('#semAcesso'));
}
async function postar(p, rota, campos) {
  return p.evaluate(async ([u, c]) => { const f = new URLSearchParams(); for (const [k, v] of Object.entries(c)) { f.append(k, v); }
    const r = await fetch(u, { method: 'POST', body: f, credentials: 'same-origin' }); return r.text(); }, [L.BASE + 'painel/' + rota, campos]);
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
function criarChave() {
  const chave = 'vce_' + crypto.randomBytes(20).toString('hex');
  sql(`INSERT INTO adms_api_chaves (nome, prefixo, hash, escopo, permissoes, situacao, usuario_id) VALUES ('Prova avaliação', '${chave.slice(0, 12)}', '${crypto.createHash('sha256').update(chave).digest('hex')}', 'escrita', 'alunos:gravar matriculas:gravar enturmacao:gravar cadastros:ler', 1, 1)`);
  return chave;
}
async function api(metodo, rota, chave, corpo) {
  const r = await fetch(API + rota, { method: metodo, headers: { Authorization: 'Bearer ' + chave, 'Content-Type': 'application/json' }, body: corpo ? JSON.stringify(corpo) : undefined });
  return { status: r.status, json: await r.json().catch(() => null) };
}
const phpHash = (s) => spawnSync(PHP, ['-r', 'echo password_hash($argv[1], PASSWORD_DEFAULT);', s], { encoding: 'utf8' }).stdout.trim();
/** A tela das etapas: a linha do aluno no resumo do ano (as notas das etapas, a média anual, a situação). */
async function resumo(p, oferta, rm) {
  await p.goto(L.BASE + `painel/escola-notas/etapas/${oferta}`);
  return p.$eval(`#resumoAno tr[data-rm="${rm}"]`, (tr) => Array.from(tr.querySelectorAll('td')).map((td) => td.textContent.replace(/\s+/g, ' ').trim())).catch(() => []);
}

(async () => {
  const { navegador } = await L.abrir();
  try {
    console.log('\n== Onda 4, Etapa 3 — a escola: a avaliação');

    /* A. a função pura */
    const u = JSON.parse(execFileSync(PHP, [path.join(__dirname, 'media_escola.php')]).toString().trim());
    L.ok(u.maior_4_5 === '5.0' && u.maior_45_4 === '4.5' && u.em_recuperacao === true, `a recuperação (a maior): 4,0 com recuperação 5,0 → ${u.maior_4_5}; 4,5 com recuperação 4,0 → ${u.maior_45_4}`);
    L.ok(u.media_4_5 === '4.5' && u.teto_4_8 === '6.0' && u.teto_55_9 === '6.0' && u.sem_recuperacao_7 === '7.0',
      `as outras regras: a média das duas (4,0 e 5,0 → ${u.media_4_5}); a maior com teto na média mínima (4,0 e 8,0 → ${u.teto_4_8}; 5,5 e 9,0 → ${u.teto_55_9}); acima da mínima, sem recuperação`);
    L.ok(u.anual_simples === '6.5' && u.anual_pesos === '6.8', `a média anual: simples (5, 6, 7, 8 → ${u.anual_simples}); com pesos 1, 1, 2, 2 (→ ${u.anual_pesos})`);
    L.ok(u.falta_situacao === 'reprovado_falta' && u.falta_media === '8.5' && u.falta_frequencia === '70.00' && u.ano_situacao === 'reprovado_falta',
      `reprovado por falta com nota suficiente (média ${u.falta_media}, frequência ${u.falta_frequencia}%; e no total de horas do ano)`);
    L.ok(u.nota_situacao === 'reprovado_nota' && u.conselho_situacao === 'aprovado_conselho' && u.conselho_media === u.nota_media && u.conselho_resultado === 1 && u.conselho_memoria,
      `o conselho aprova o reprovado por nota: a nota não muda (${u.conselho_media}), a situação, sim; a memória mostra a decisão`);
    L.ok(u.aprovado === 'aprovado' && u.pendente === 'pendente' && u.sem_nota_vale_zero === '0.0', 'aprovado direto; no fechamento do ano, a etapa sem nenhuma atividade deixa pendente (a atividade sem a nota do aluno vale 0)');
    L.ok(u.memoria.length >= 4 && /recuperação 5,0 → a maior das duas → nota da etapa 5,0/.test(u.memoria[0]) && /^Média anual = \(5,0 \+ 6,0\) ÷ 2 = 5,5/.test(u.memoria[2]),
      `a memória de cálculo abre a conta dos dois níveis ("${u.memoria[0].slice(0, 70)}…")`);

    /* B. no ensino superior */
    let adm = await entrar(navegador);
    const ofSup = um('SELECT MIN(id) FROM rm_professores_materias');
    const turmaSup = await abre(adm.p, `escola-notas/etapas/${ofSup}`);
    const conselhoSup = await abre(adm.p, 'escola-conselho/turma/1');
    L.ok(!turmaSup && !conselhoSup, 'superior: as telas das etapas e do conselho não existem (a rota recusa)');
    await adm.ctx.close();

    /* C. a escola */
    spawnSync(PHP, [path.join(L.RAIZ, 'tests', 'tabler', 'reset.php')], { encoding: 'utf8', cwd: L.RAIZ });
    const inst = spawnSync('node', [path.join(L.RAIZ, 'tests', 'tabler', 'instalar.js'), 'normal'], { encoding: 'utf8', cwd: L.RAIZ, env: { ...process.env, VCOSTAEDU_TIPO: 'escola', VCOSTAEDU_ETAPA_PROVA: ETAPA }, maxBuffer: 1 << 26 });
    L.ok(inst.status === 0, 'a instalação nova de escola');
    /* a estrutura: o segmento, a matriz com o 6º ano, dois componentes; o ano letivo */
    sql(`INSERT INTO adms_periodos_letivos (ano, situacaodisciplina, situacaomodelo, situacao, usuario_cre) VALUES (${ANO}, 1, 1, 1, 1)`);
    const sede = um("SELECT id FROM rm_polos WHERE nome = 'Sede' LIMIT 1");
    sql(`INSERT INTO rm_cursos (nome, abreviatura, periodo, localidade, filial, situacao, usuario_cre) VALUES ('Ensino Fundamental — anos finais (prova)', 'EFAF', 1, ${sede}, ${sede}, 1, 1)`);
    const curso = um('SELECT MAX(id) FROM rm_cursos');
    sql(`INSERT INTO rm_cursos_grades (curso_id, versao, situacao, usuario_cre) VALUES (${curso}, 1, 1, 1)`);
    const grade = um('SELECT MAX(id) FROM rm_cursos_grades');
    sql(`INSERT INTO rm_cursos_turma_series (curso_id, grade_id, periodo, codnome, ordem, situacao, usuario_cre) VALUES (${curso}, ${grade}, 1, '6º ano', 1, 1, 1)`);
    const serie = um('SELECT MAX(id) FROM rm_cursos_turma_series');
    for (const [i, [nome, ch]] of [['Matemática', 200], ['Língua Portuguesa', 200]].entries()) {
      sql(`INSERT INTO rm_materias (nome, abreviacao, carga_horaria, situacao, usuario_cre) VALUES ('${nome}', 'C${i}', '${ch}', 1, 1)`);
      sql(`INSERT INTO uni_rh_cursos_disciplinas_grade (grade_id, modulo_id, materia_id, cargahoraria, ordem, usuario_cre) VALUES (${grade}, ${serie}, (SELECT MAX(id) FROM rm_materias), ${ch}, ${i + 1}, 1)`);
    }
    adm = await entrar(navegador);
    await postar(adm.p, 'escola-anos/abrir', { ano: ANO, modelo: 'bimestre' });
    const etapas = sql(`SELECT CONCAT(id, '|', ordem, '|', inicio) FROM esc_etapas WHERE ano = ${ANO} ORDER BY ordem`).split('\n').map((l) => { const [id, ordem, inicio] = l.split('|'); return { id: +id, ordem: +ordem, inicio }; });
    await postar(adm.p, 'turmas/cadastrar', { ano: ANO, modulo_id: serie, nome: 'A' });
    const turma = um(`SELECT MAX(id) FROM esc_turmas`);
    const mat = um(`SELECT o.id FROM esc_turma_ofertas x INNER JOIN rm_professores_materias o ON o.id = x.oferta_id WHERE x.turma_id = ${turma} AND o.materia = 'Matemática'`);
    const lp = um(`SELECT o.id FROM esc_turma_ofertas x INNER JOIN rm_professores_materias o ON o.id = x.oferta_id WHERE x.turma_id = ${turma} AND o.materia <> 'Matemática'`);
    sql(`UPDATE rm_professores_materias SET encontros = 40 WHERE id IN (${mat}, ${lp})`);
    /* os alunos (pela API) e a turma (o lote) */
    const chave = criarChave();
    const forma = um('SELECT id FROM rm_matricula_tipos WHERE situacao = 1 ORDER BY id LIMIT 1');
    const RA = { direto: '951001', recupera: '951002', falta: '951003', conselho: '951004' };
    for (const [i, ra] of Object.values(RA).entries()) {
      await api('POST', '/alunos', chave, { ra, nome: `Aluno Avaliação ${i + 1} Exemplo`, sexo: 'masculino', nascimento: '2014-03-10' });
      await api('POST', '/matriculas', chave, { ra, curso_id: curso, ano: ANO, forma_ingresso_id: forma, data_ingresso: `${ANO}-02-01` });
    }
    const ids = Object.values(RA).map((ra) => um(`SELECT id FROM col_usuarios WHERE rm = '${ra}'`)).join(',');
    await adm.p.goto(L.BASE + `painel/turmas/ver/${turma}`);
    await adm.p.evaluate(async ([u2, x]) => { const f = new URLSearchParams(); f.append('fase', 'iniciar'); f.append('alunos', x); await fetch(u2, { method: 'POST', body: f, credentials: 'same-origin' });
      const g = new URLSearchParams(); g.append('fase', 'passo'); await fetch(u2, { method: 'POST', body: g, credentials: 'same-origin' }); }, [L.BASE + `painel/turmas/enturmar/${turma}`, ids]);
    L.ok(um(`SELECT COUNT(*) FROM esc_turma_alunos WHERE turma_id = ${turma} AND saida IS NULL`) === 4, 'a turma com os 4 alunos');
    /* uma prova por bimestre em cada componente (a data decide a etapa) e as notas */
    const NOTAS_MAT = { direto: ['7.0', '8.0', '7.0', '8.0'], recupera: ['4.0', '4.5', '5.0', '8.0'], falta: ['8.0', '9.0', '8.0', '9.0'], conselho: ['4.0', '5.0', '4.0', '5.0'] };
    const ativ = {};
    for (const of of [mat, lp]) {
      for (const e of etapas) {
        const dia = new Date(new Date(e.inicio + 'T12:00:00Z').getTime() + 5 * 86400000).toISOString().slice(0, 10); // dentro da etapa: o início + 5 dias
        sql(`INSERT INTO rm_disciplina_atividade (nomeAtividade, tipo, entrega_arquivo, rm_prof_mat_id, situacao, usuario_cre, data_inicial, data_final, peso) VALUES ('Prova do ${e.ordem}º bimestre', 1, 0, ${of}, 1, 1, '${dia}', '${dia}', 10)`);
        ativ[`${of}:${e.ordem}`] = um('SELECT MAX(id) FROM rm_disciplina_atividade');
      }
      for (const [k, ra] of Object.entries(RA)) {
        etapas.forEach((e, i) => sql(`INSERT INTO rm_disciplina_atividade_notas (rm, atividade_id, rm_prof_mat_id, nota) VALUES ('${ra}', ${ativ[`${of}:${e.ordem}`]}, ${of}, '${of === mat ? NOTAS_MAT[k][i] : '7.0'}')`));
      }
    }
    /* a recuperação pela tela */
    await adm.p.goto(L.BASE + `painel/escola-notas/etapas/${mat}?etapa=${etapas[0].id}`);
    await L.semErros(adm.p, 'as etapas do componente');
    const emRec = await adm.p.$$eval('#tabelaEtapa tr[data-em-recuperacao="1"]', (l) => l.map((tr) => tr.dataset.rm));
    L.ok(JSON.stringify(emRec.sort()) === JSON.stringify([RA.recupera, RA.conselho].sort()), `no 1º bimestre, ficam em recuperação só os abaixo da média mínima (${emRec.join(', ')})`);
    await adm.p.fill(`input[name="recuperacao[${RA.recupera}]"]`, '5,0');
    await Promise.all([adm.p.waitForNavigation(), adm.p.click('#gravarEtapa')]); // D-100: um botão só (Gravar)
    await L.print(adm.p, ETAPA, 'etapa-recuperacao');
    await postar(adm.p, `escola-notas/recuperacao/${mat}`, { etapa_id: etapas[1].id, [`recuperacao[${RA.recupera}]`]: '4,0' });
    let r = await resumo(adm.p, mat, RA.recupera);
    L.ok(r[1] && r[1].startsWith('5,0') && r[2] && r[2].startsWith('4,5'), `pela tela, os exemplos do pedido: 4,0 com recuperação 5,0 → ${r[1]}; 4,5 com recuperação 4,0 → ${r[2]}`);
    const recusaFora = await postar(adm.p, `escola-notas/recuperacao/${mat}`, { etapa_id: etapas[0].id, [`recuperacao[${RA.direto}]`]: '9,0' });
    L.ok(um(`SELECT COUNT(*) FROM esc_recuperacoes WHERE rm = '${RA.direto}'`) === 0, 'a recuperação de quem não está em recuperação é recusada');
    /* as regras: a média das duas; os pesos */
    await postar(adm.p, 'escola-regras/salvar', { media_minima: '6', recuperacao_regra: 'media', anual_composicao: 'simples', anual_pesos: '', frequencia_base: 'ano', frequencia_minima: '75' });
    r = await resumo(adm.p, mat, RA.recupera);
    const comMedia = r[1];
    await postar(adm.p, 'escola-regras/salvar', { media_minima: '6', recuperacao_regra: 'maior', anual_composicao: 'pesos', anual_pesos: '1,1,2,2', frequencia_base: 'ano', frequencia_minima: '75' });
    r = await resumo(adm.p, mat, RA.recupera);
    const anualPesos = r[5];
    await postar(adm.p, 'escola-regras/salvar', { media_minima: '6', recuperacao_regra: 'maior', anual_composicao: 'simples', anual_pesos: '', frequencia_base: 'ano', frequencia_minima: '75' });
    r = await resumo(adm.p, mat, RA.recupera);
    L.ok(comMedia.startsWith('4,5') && anualPesos.startsWith('5,9') && r[5].startsWith('5,6') && um('SELECT COUNT(*) FROM esc_configuracao') === 0,
      `as Regras da escola mudam o número: a média das duas (4,0 e 5,0 → ${comMedia}); a média anual de 5,0, 4,5, 5,0 e 8,0 com os pesos 1, 1, 2, 2 → ${anualPesos} (simples: ${r[5]}); de volta ao padrão, nada fica gravado`);
    /* as faltas da etapa: o total é a soma; a reprovação por falta com nota suficiente (no total de horas do ano) */
    for (const e of etapas) {
      await postar(adm.p, `escola-notas/faltas/${mat}`, { etapa_id: e.id, [`faltas[${RA.falta}]`]: '6' });
      await postar(adm.p, `escola-notas/faltas/${lp}`, { etapa_id: e.id, [`faltas[${RA.falta}]`]: '6' });
    }
    L.ok(um(`SELECT faltas FROM rm_disciplina_faltas WHERE rm = '${RA.falta}' AND rm_prof_mat_id = ${mat}`) === 24 && um(`SELECT COUNT(*) FROM esc_faltas_etapa WHERE rm = '${RA.falta}'`) === 8,
      'as faltas por etapa: 6 em cada bimestre → 24 no componente (a soma)');
    const aud = sql(`SELECT detalhe FROM rm_auditoria_lancamentos WHERE rm = '${RA.recupera}' AND campo = 'recuperacao' ORDER BY id DESC LIMIT 1`);
    L.ok(/recuperação — etapa: 2º bimestre/.test(aud), `a auditoria registra a recuperação com a etapa ("${aud}")`);
    /* fechar as etapas na ordem */
    let txt = await postar(adm.p, `escola-notas/fechar/${mat}`, { etapa_id: etapas[1].id });
    const foraDeOrdem = um(`SELECT COUNT(*) FROM esc_fechamentos WHERE oferta_id = ${mat}`) === 0;
    for (const e of etapas) { await postar(adm.p, `escola-notas/fechar/${mat}`, { etapa_id: e.id }); }
    sql(`UPDATE rm_professores_materias SET encontros = 40 WHERE id = ${lp}`);
    await postar(adm.p, `disciplinas/encerrar/${lp}`, { ConfirmarEncerramento: '1' });
    const anoAberto = um(`SELECT COUNT(*) FROM rm_disciplina_media_final WHERE rm_prof_mat_id = ${lp}`) === 0 && sql(`SELECT IFNULL(encerrada_em, '') FROM rm_professores_materias WHERE id = ${lp}`) === '';
    L.ok(anoAberto, 'o ano não fecha com etapa aberta (Língua Portuguesa, sem nenhuma etapa fechada: o encerramento é recusado)');
    for (const e of etapas) { await postar(adm.p, `escola-notas/fechar/${lp}`, { etapa_id: e.id }); }
    L.ok(foraDeOrdem && um(`SELECT COUNT(*) FROM esc_fechamentos WHERE oferta_id = ${mat} AND situacao = 'vigente'`) === 4
      && sql(`SELECT nota FROM esc_fechamento_notas n INNER JOIN esc_fechamentos f ON f.id = n.fechamento_id WHERE f.oferta_id = ${mat} AND f.etapa_id = ${etapas[0].id} AND n.rm = '${RA.recupera}'`) === '5.0'
      && sql(`SELECT regra_hash FROM esc_fechamentos WHERE oferta_id = ${mat} LIMIT 1`).length === 64,
      'as etapas fecham na ordem (fora de ordem, recusado); o fechamento guarda a nota da etapa (5,0) e a regra copiada, com o sha256');
    /* a etapa fechada recusa nota, falta e recuperação, com a razão */
    const notaId = um(`SELECT id FROM rm_disciplina_atividade_notas WHERE rm = '${RA.direto}' AND atividade_id = ${ativ[`${mat}:1`]}`);
    const edit = JSON.parse(await postar(adm.p, 'atividades/edit-notas', { action: 'edit', id: notaId, nota: '10' }));
    await postar(adm.p, `escola-notas/faltas/${mat}`, { etapa_id: etapas[0].id, [`faltas[${RA.direto}]`]: '9' });
    await adm.p.goto(L.BASE + `painel/escola-notas/etapas/${mat}?etapa=${etapas[0].id}`);
    const razao = (await adm.p.textContent('#etapaTravada').catch(() => '')).trim();
    L.ok(edit.erro === false && /está fechad[oa]/.test(edit.msg || '') && sql(`SELECT nota FROM rm_disciplina_atividade_notas WHERE id = ${notaId}`) === '7.0'
      && um(`SELECT COUNT(*) FROM esc_faltas_etapa WHERE rm = '${RA.direto}'`) === 0 && /está fechad[oa] desde/.test(razao), // D-100: "O 1º bimestre está fechado" (a concordância)
      `a etapa fechada recusa a nota, a falta e a recuperação, e a tela diz por quê ("${razao.slice(0, 60)}…")`);
    await L.print(adm.p, ETAPA, 'etapa-fechada');
    /* reabrir com motivo guarda o substituído (e volta a fechar) */
    await postar(adm.p, `escola-notas/reabrir/${mat}`, { etapa_id: etapas[3].id, motivo: 'curto' });
    const semMotivo = um(`SELECT COUNT(*) FROM esc_fechamentos WHERE oferta_id = ${mat} AND situacao = 'substituido'`) === 0;
    await postar(adm.p, `escola-notas/reabrir/${mat}`, { etapa_id: etapas[3].id, motivo: 'Correção de nota digitada errado (prova)' });
    await postar(adm.p, `escola-notas/fechar/${mat}`, { etapa_id: etapas[3].id });
    L.ok(semMotivo && um(`SELECT COUNT(*) FROM esc_fechamentos WHERE oferta_id = ${mat} AND etapa_id = ${etapas[3].id}`) === 2
      && um(`SELECT COUNT(*) FROM esc_fechamentos WHERE oferta_id = ${mat} AND etapa_id = ${etapas[3].id} AND situacao = 'substituido' AND motivo LIKE 'Correção%'`) === 1,
      'reabrir pede o motivo; o fechamento substituído fica guardado, com o motivo');
    /* o fechamento do ano; a situação */
    sql(`UPDATE rm_professores_materias SET encontros = 40 WHERE id IN (${mat}, ${lp})`);
    await postar(adm.p, `disciplinas/encerrar/${mat}`, { ConfirmarEncerramento: '1' });
    const encerrou = um(`SELECT COUNT(*) FROM rm_disciplina_media_final WHERE rm_prof_mat_id = ${mat}`);
    L.ok(encerrou === 4, `o ano fechado (o encerramento do componente, com as etapas fechadas): ${encerrou} aluno(s) com a média anual guardada`);
    const sit = (rm) => (resumoCache[rm] || [])[7] || '';
    const resumoCache = {};
    for (const rm of Object.values(RA)) { resumoCache[rm] = await resumo(adm.p, mat, rm); }
    L.ok(/^Aprovado$/.test(sit(RA.direto)) && /^Reprovado por falta$/.test(sit(RA.falta)) && /^Reprovado por nota$/.test(sit(RA.conselho)),
      `a situação do ano: aprovado direto; reprovado por falta com média ${resumoCache[RA.falta][5]} e frequência ${resumoCache[RA.falta][6]} no ano; reprovado por nota (média ${resumoCache[RA.conselho][5]})${encerrou ? ' — o ano fechado e guardado' : ''}`);
    /* o conselho aprova (observação obrigatória); a nota não muda */
    await postar(adm.p, `escola-conselho/registrar/${turma}`, { etapa_id: '0', rm: RA.conselho, decisao: 'aprovar', observacao: 'curta' });
    const semObs = um('SELECT COUNT(*) FROM esc_conselho') === 0;
    await postar(adm.p, `escola-conselho/registrar/${turma}`, { etapa_id: '0', rm: RA.conselho, decisao: 'aprovar', observacao: 'Evolução consistente no segundo semestre (prova)' });
    const negado = await postar(adm.p, `escola-conselho/registrar/${turma}`, { etapa_id: '0', rm: RA.direto, decisao: 'aprovar', observacao: 'Tentativa de aprovar quem já está aprovado (prova)' });
    const depois = await resumo(adm.p, mat, RA.conselho);
    L.ok(semObs && depois[7] === 'Aprovado pelo conselho' && depois[5] === resumoCache[RA.conselho][5] && um(`SELECT COUNT(*) FROM esc_conselho WHERE rm = '${RA.direto}'`) === 0,
      `o conselho de classe: a observação é obrigatória; aprova o reprovado por nota — "${depois[7]}", a média continua ${depois[5]}; quem não está reprovado não recebe a aprovação`);
    await adm.p.goto(L.BASE + `painel/escola-conselho/turma/${turma}`);
    await L.semErros(adm.p, 'o conselho de classe');
    await L.print(adm.p, ETAPA, 'conselho');
    /* o mesmo número: a tela das etapas, o boletim do painel, o PDF e o portal */
    const tela = resumoCache[RA.recupera];
    await adm.p.goto(L.BASE + `painel/academicos/boletim/${RA.recupera}`);
    await L.semErros(adm.p, 'o boletim (painel)');
    const linhaPainel = await adm.p.$eval(`.tabela-boletim-escola tr[data-oferta="${mat}"]`, (tr) => Array.from(tr.querySelectorAll('td')).map((td) => td.textContent.replace(/\s+/g, ' ').trim())).catch(() => []);
    await L.print(adm.p, ETAPA, 'boletim-painel');
    const pdf = execFileSync(PHP, [path.join(__dirname, 'boletim_pdf.php'), RA.recupera], { cwd: L.RAIZ }).toString();
    const m = new RegExp(`data-oferta="${mat}">(.*?)</tr>`).exec(pdf);
    const linhaPdf = m ? (m[1].match(/<td[^>]*>(.*?)<\/td>/g) || []).map((x) => x.replace(/<[^>]+>/g, '').replace(/\s+/g, ' ').trim()) : [];
    sql(`UPDATE col_usuarios SET senha = '${phpHash(SENHA)}' WHERE rm = '${RA.recupera}'`);
    /* D-100: a liberação pela tela Liberação do boletim (a tela inteira do ano: as duas primeiras etapas; a média e a situação finais, ocultas) */
    await postar(adm.p, 'escola-liberacao/salvar', { ano: sql(`SELECT ano FROM esc_etapas WHERE id = ${etapas[0].id}`), ...Object.fromEntries(etapas.slice(0, 2).map((e) => [`item[etapa:${e.id}][exibir]`, '1'])) });
    const al = await entrar(navegador, RA.recupera, SENHA, 'portal');
    await al.p.goto(L.BASE + 'portal/home/boletim-aluno');
    const linhaPortal = await al.p.$eval(`.tabela-boletim-escola tr[data-oferta="${mat}"]`, (tr) => Array.from(tr.querySelectorAll('td')).map((td) => td.textContent.replace(/\s+/g, ' ').trim())).catch(() => []);
    await L.print(al.p, ETAPA, 'boletim-portal-duas-etapas');
    const n1 = (x) => (x || '').replace(/\s*R$/, '').replace(/ parcial$/, '');
    L.ok(linhaPainel.length >= 8 && n1(linhaPainel[1]) === n1(tela[1]) && n1(linhaPainel[2]) === n1(tela[2]) && n1(linhaPainel[5]) === n1(tela[5]) && n1(linhaPdf[5]) === n1(tela[5]) && n1(linhaPdf[1]) === n1(tela[1]),
      `o mesmo número na tela das etapas (${tela.slice(1, 6).join(' | ')}), no boletim do painel (${linhaPainel.slice(1, 6).join(' | ')}) e no PDF (${linhaPdf.slice(1, 6).join(' | ')})`);
    L.ok(n1(linhaPortal[1]) === n1(tela[1]) && n1(linhaPortal[2]) === n1(tela[2]) && linhaPortal[3] === '—' && linhaPortal[4] === '—' && linhaPortal[5] === '—',
      `o portal: as duas etapas liberadas com o mesmo número (${linhaPortal.slice(1, 3).join(' | ')}); as outras, a média anual e a situação, só com tudo liberado`);
    await al.ctx.close();
    /* o professor só nas turmas dele */
    const nivelProf = um("SELECT id FROM adms_niveis_acessos WHERE papel = 'professor' LIMIT 1");
    sql(`INSERT INTO adms_usuarios (nome, genero_id, usuario, senha, email, adms_niveis_acesso_id, adms_sits_usuario_id, usuario_cre) VALUES ('Professor Avaliação (fictício)', 1, 'prof.avaliacao.prova', '${phpHash(SENHA)}', 'prof.avaliacao@exemplo.invalid', ${nivelProf}, 1, 1)`);
    sql(`INSERT INTO rm_professores_mat_liga (usuario_id, rm_prof_mat_id, acesso, situacao, usuario_cre) VALUES ((SELECT id FROM adms_usuarios WHERE usuario = 'prof.avaliacao.prova'), ${mat}, 1, 1, 1)`);
    const pr = await entrar(navegador, 'prof.avaliacao.prova');
    L.ok(await abre(pr.p, `escola-notas/etapas/${mat}`) && !(await abre(pr.p, `escola-notas/etapas/${lp}`)), 'o professor abre as etapas do componente dele, e não as de outro');
    await pr.ctx.close();

    /* sabotagens */
    await sabotar('a recuperação que não sobe a nota', MEDIA, "return [number_format(max($m, $recC) / 100, $casas, '.', ''), 'a maior das duas'];", "return [$media, 'a maior das duas'];", async () => {
      const x = JSON.parse(execFileSync(PHP, [path.join(__dirname, 'media_escola.php')]).toString().trim());
      return x.maior_4_5 === '5.0';
    });
    await sabotar('a trava da etapa fechada desligada', NOTAS, "        $f = self::fechamento($ofertaId, $etapaId);\n        if (!$f) {\n            return null;\n        }\n        $e = self::etapa($etapaId);",
      "        return null;\n        $f = null;\n        $e = null;", async () => {
        /* uma nota de Língua Portuguesa: as etapas fechadas, o ano não (a trava do ano fechado não esconde a da etapa) */
        const notaLp = um(`SELECT id FROM rm_disciplina_atividade_notas WHERE rm = '${RA.direto}' AND atividade_id = ${ativ[`${lp}:1`]}`);
        const x = JSON.parse(await postar(adm.p, 'atividades/edit-notas', { action: 'edit', id: notaLp, nota: '10' }));
        const recusou = x.erro === false;
        sql(`UPDATE rm_disciplina_atividade_notas SET nota = '7.0' WHERE id = ${notaLp}`);
        return recusou;
      });
    await adm.ctx.close();
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message);
  } finally {
    await navegador.close();
  }
  process.exit(L.resumo('onda4/etapa3') ? 0 : 1);
})();
