/*
 * PROVA DA ONDA 6, ETAPA 4 — as operações longas, em passos (docs/OPERACOES_LONGAS.md).
 *   A. a importação de alunos com 5.000 linhas, pela tela: em passos (nenhum acima do limite), o mesmo resultado da
 *      aplicação de uma vez (pela linha de comando, num passo só), o tudo ou nada (uma linha que falha no meio — um
 *      gatilho posto pela prova no banco — e nada fica), a retomada (a página fechada no meio e o Continuar), o desfazer
 *      de 5.000 numa página; sabotagem: sem a volta das linhas já gravadas, a prova reprova;
 *   B. os relatórios que calculam as notas: com o limite em 0, cada um calcula em passos e abre no fim com o MESMO texto
 *      do de uma vez; sabotagem: um cálculo guardado estragado, a prova reprova;
 *   C. confirmar a enturmação com o envio automático (um Moodle falso da prova, que responde devagar): a página da
 *      confirmação não chama o Moodle; a janela da tela do aluno envia, em passos; sabotagem: o envio de volta dentro da
 *      página, a prova reprova;
 *   D. encerrar uma oferta com 5.000 alunos: numa página só, abaixo do limite, o gravado igual ao calculado; sabotagem:
 *      um bloco de alunos a menos, a prova reprova.
 * Depois do ciclo (a demonstração do ensino superior). Cada arquivo sabotado volta igual (sha256).
 */
const fs = require('fs');
const path = require('path');
const http = require('http');
const crypto = require('crypto');
const { execFileSync, spawnSync } = require('child_process');
const L = require('../lib');
const G = require('./planilha_grande');

const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste$/.test(BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
const MYSQL = process.env.VCOSTAEDU_MYSQL || 'C:/xampp/mysql/bin/mysql.exe';
const PHP = process.env.VCOSTAEDU_PHP || 'C:/xampp/php/php.exe';
const sql = (q, entrada) => execFileSync(MYSQL, ['-u', 'root', '-N', '--default-character-set=utf8mb4', BANCO, ...(entrada ? [] : ['-e', q])], entrada ? { input: entrada } : {}).toString().replace(/\r/g, '').trim();
const um = (q) => +sql(q);
const runner = (args) => { const r = spawnSync(PHP, [path.join(__dirname, 'etapa4_runner.php'), ...args], { encoding: 'utf8', cwd: L.RAIZ, maxBuffer: 1 << 24 }); try { return JSON.parse((r.stdout || '').trim().split('\n').pop()); } catch (e) { return { erro: (r.stderr || r.stdout || '').slice(-300) }; } };
const oferta = (args) => { const r = spawnSync(PHP, [path.join(__dirname, 'oferta_grande.php'), ...args], { encoding: 'utf8', cwd: L.RAIZ, maxBuffer: 1 << 24 }); try { return JSON.parse((r.stdout || '').trim().split('\n').pop()); } catch (e) { return { erro: (r.stderr || r.stdout || '').slice(-300) }; } };
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const cred = JSON.parse(fs.readFileSync(path.join(__dirname, '..', 'saida', 'credenciais.json'), 'utf8'));
const LIMITE_PASSO = 10000; // ms: nenhum passo acima disso (o orçamento é de 4 s; a folga é a carga da máquina)
const MODELO_IMP = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'importacao', 'AdmsImportacaoAlunos.php');
const CALCULO = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'relatorios', 'AdmsRelatorioCalculo.php');
const CTRL_PORTAL = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Controllers', 'PortalAlunos.php');
const ENCERRAR = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'notas', 'AdmsEncerrarOferta.php');

const contagens = () => ({ alunos: um('SELECT COUNT(*) FROM col_usuarios WHERE col_niveis_acesso_id = 1'), vinculos: um('SELECT COUNT(*) FROM rm_matriculas_curso'), posicoes: um('SELECT COUNT(*) FROM rm_matriculas_ensino') });
/* o retrato dos alunos importados (RA 950001 em diante): o cadastro, o vínculo e a posição no ano de cada um */
const retrato = (de, ate) => sql(`SET SESSION group_concat_max_len = 200000000;
  SELECT CONCAT(COUNT(*), ':', SHA2(GROUP_CONCAT(CONCAT_WS('|', u.rm, u.nome, IFNULL(u.email, ''), IFNULL(u.genero_id, ''), u.col_sits_usuario_id, IFNULL(i.cpf, ''), IFNULL(i.nasc_data, ''),
    IFNULL(mc.curso_id, ''), IFNULL(mc.grade_id, ''), IFNULL(mc.situacao_id, ''), IFNULL(mc.forma_ingresso_id, ''), IFNULL(mc.data_ingresso, ''), IFNULL(mc.modalidade_id, ''),
    IFNULL(me.ano, ''), IFNULL(me.modulo_id, '')) ORDER BY u.rm SEPARATOR ';'), 256))
    FROM col_usuarios u LEFT JOIN col_usuarios_informacoes i ON i.col_user_id = u.id LEFT JOIN rm_matriculas_curso mc ON mc.usuario_id = u.id
    LEFT JOIN rm_matriculas_ensino me ON me.matricula_curso_id = mc.id WHERE u.rm BETWEEN '${de}' AND '${ate}'`);

async function enviar(p, plan) {
  await p.goto(L.BASE + 'painel/importacao-alunos/listar');
  await p.setInputFiles('#arquivoImportacao', { name: plan.nome, mimeType: 'text/csv', buffer: plan.buffer });
  await Promise.all([p.waitForNavigation({ timeout: 300000 }), p.click('#enviarImportacao')]);
  const m = /importacao-alunos\/conferir\/(\d+)/.exec(p.url());
  return m ? +m[1] : 0;
}
/* os pedidos da janela: a duração de cada um (o servidor) e o que cada um respondeu */
function escutar(p, trecho) {
  const r = { duracoes: [], respostas: [] };
  const fn1 = (rq) => { if (rq.url().includes(trecho) && rq.method() === 'POST') { r.duracoes.push(Math.round(rq.timing().responseEnd)); } };
  const fn2 = async (rs) => { if (rs.url().includes(trecho) && rs.request().method() === 'POST') { r.respostas.push(await rs.json().catch(() => ({}))); } };
  p.on('requestfinished', fn1);
  p.on('response', fn2);
  r.parar = () => { p.off('requestfinished', fn1); p.off('response', fn2); };
  return r;
}
async function aplicar(p) {
  const e = escutar(p, 'importacao-alunos/aplicar');
  if (await p.$('#formaIngresso')) { await p.selectOption('#formaIngresso', { index: 1 }); }
  if (await p.$('#aceitarErros')) { await p.check('#aceitarErros'); }
  const t = Date.now();
  await Promise.all([p.waitForNavigation({ timeout: 1800000 }), p.click('#aplicarLote')]);
  e.parar();
  e.s = Math.round((Date.now() - t) / 1000);
  e.msg = (await p.$eval('.alert', (x) => x.textContent.replace(/\s+/g, ' ').trim()).catch(() => '')).slice(0, 300);
  return e;
}
async function desfazer(p, lote) {
  await p.goto(L.BASE + 'painel/importacao-alunos/conferir/' + lote);
  await p.fill('#motivoDesfazer', 'prova da onda 6, etapa 4');
  const t = Date.now();
  await Promise.all([p.waitForNavigation({ timeout: 600000 }), p.click('#desfazerLote')]);
  return { s: Math.round((Date.now() - t) / 100) / 10, ok: !!(await p.$('#loteDesfeito')) };
}
const gatilho = (ra) => sql(null, `DROP TRIGGER IF EXISTS prova_o6e4_falha;\nDELIMITER //\nCREATE TRIGGER prova_o6e4_falha BEFORE INSERT ON col_usuarios FOR EACH ROW BEGIN IF NEW.rm = '${ra}' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'falha posta pela prova'; END IF; END//\nDELIMITER ;\n`);
const semGatilho = () => sql('DROP TRIGGER IF EXISTS prova_o6e4_falha');
const limparRa = (de, ate) => sql(`DELETE FROM rm_matriculas_ensino WHERE usuario_id IN (SELECT id FROM col_usuarios WHERE rm BETWEEN '${de}' AND '${ate}');
  DELETE FROM rm_matriculas_curso WHERE usuario_id IN (SELECT id FROM col_usuarios WHERE rm BETWEEN '${de}' AND '${ate}');
  DELETE FROM col_usuarios_enderecos WHERE usuario_id IN (SELECT id FROM col_usuarios WHERE rm BETWEEN '${de}' AND '${ate}');
  DELETE FROM col_usuarios_informacoes WHERE col_user_id IN (SELECT id FROM col_usuarios WHERE rm BETWEEN '${de}' AND '${ate}');
  DELETE FROM col_usuarios WHERE col_niveis_acesso_id = 1 AND rm BETWEEN '${de}' AND '${ate}'`);
function sabotar(arquivo, de, para) {
  const orig = fs.readFileSync(arquivo, 'utf8');
  if (!orig.includes(de)) { throw new Error('a sabotagem não achou o trecho em ' + path.basename(arquivo)); }
  fs.writeFileSync(arquivo, orig.replace(de, para));
  return () => fs.writeFileSync(arquivo, orig);
}

(async () => {
  const { navegador, contexto } = await L.abrir();
  contexto.setDefaultTimeout(60000);
  const p = await L.pagina(contexto);
  p.on('dialog', (d) => d.accept());
  await L.confirmarSozinho(p); // D-100: a confirmação é a janela do sistema
  const shas = Object.fromEntries([MODELO_IMP, CALCULO, CTRL_PORTAL, ENCERRAR].map((f) => [f, sha(f)]));
  let servidor = null;
  try {
    console.log('\n== Onda 6, Etapa 4 — as operações longas, em passos');
    await p.goto(L.BASE + 'painel/login/acesso');
    await p.fill('input[name="usuario"]', 'administrador');
    await p.fill('input[name="senha"]', cred.painel.administrador);
    await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
    const ref = { curso: sql('SELECT nome FROM rm_cursos ORDER BY id LIMIT 1'), ano: um('SELECT MAX(ano) FROM adms_periodos_letivos') };

    /* ===== A. a importação de 5.000 linhas ===== */
    const plan = G.grande(ref, 5000);
    const antes = contagens();
    const lote1 = await enviar(p, plan);
    const a1 = await aplicar(p);
    const depois1 = contagens();
    const r1 = retrato('950001', '955000');
    const maior1 = Math.max(...a1.duracoes);
    L.ok(lote1 > 0 && /Importação aplicada: 5000 aluno/.test(a1.msg) && depois1.alunos === antes.alunos + 5000 && depois1.vinculos === antes.vinculos + plan.comCurso && a1.duracoes.length >= 3 && maior1 <= LIMITE_PASSO,
      `5.000 linhas pela tela, em ${a1.duracoes.length} chamadas (${a1.s} s; a mais longa, ${maior1} ms): alunos +${depois1.alunos - antes.alunos}, vínculos +${depois1.vinculos - antes.vinculos}`);
    const d1 = await desfazer(p, lote1);
    L.ok(d1.ok && JSON.stringify(contagens()) === JSON.stringify(antes) && d1.s < 30, `desfazer os 5.000 numa página só (${d1.s} s): as contagens de antes`);

    const lote2 = await enviar(p, plan);
    const uma = runner(['aplicar-de-uma-vez', String(lote2)]);
    const r2 = retrato('950001', '955000');
    L.ok(uma.ok && uma.passos === 1 && r2 === r1, `o mesmo resultado da aplicação de uma vez (pela linha de comando, ${uma.passos} passo, ${uma.s} s): o retrato dos 5.000 alunos ${r2 === r1 ? 'igual' : 'DIFERENTE'} (${r1.slice(0, 20)}…)`);
    await desfazer(p, lote2);

    /* o tudo ou nada: a linha 4001 (RA 954000) falha no banco, no meio da aplicação */
    gatilho('954000');
    let lote3 = 0;
    try {
      lote3 = await enviar(p, plan);
      const a3 = await aplicar(p);
      const desfez = a3.respostas.some((r) => /desfazendo/.test(r.grupoAtual || ''));
      L.ok(/Nada foi gravado: linha 4001 \(RA 954000\)/.test(a3.msg) && JSON.stringify(contagens()) === JSON.stringify(antes) && um(`SELECT situacao FROM adms_importacoes WHERE id = ${lote3}`) === 1 && Math.max(...a3.duracoes) <= LIMITE_PASSO,
        `tudo ou nada: a linha 4001 falhou no meio; as 4.000 já gravadas foram desfeitas (${desfez ? 'em passos à parte' : 'no mesmo passo da falha'}), a tela diz a linha e o motivo ("${a3.msg.slice(0, 90)}…"), o lote volta a "conferindo"`);
    } finally { semGatilho(); }

    /* a retomada: a página sai no meio; o lote fica "aplicando"; o Continuar termina; o resultado é o mesmo */
    const e5 = escutar(p, 'importacao-alunos/aplicar');
    if (await p.$('#formaIngresso')) { await p.selectOption('#formaIngresso', { index: 1 }); }
    await p.click('#aplicarLote');
    await p.waitForFunction(() => true, null, { timeout: 1000 }).catch(() => {});
    const ate = Date.now() + 120000;
    while (e5.respostas.length < 3 && Date.now() < ate) { await new Promise((ok) => setTimeout(ok, 300)); }
    e5.parar();
    await p.goto(L.BASE + 'painel/importacao-alunos/conferir/' + lote3);
    const parada = um(`SELECT situacao FROM adms_importacoes WHERE id = ${lote3}`) === 4 && !!(await p.$('#aplicacaoParada'));
    const meio = um("SELECT COUNT(*) FROM col_usuarios WHERE rm BETWEEN '950001' AND '955000'");
    await Promise.all([p.waitForNavigation({ timeout: 1800000 }), p.click('#continuarAplicacao')]);
    const r5 = retrato('950001', '955000');
    L.ok(parada && meio > 0 && meio < 5000 && !!(await p.$('#loteAplicado')) && r5 === r1,
      `a retomada: a página saiu com ${meio} de 5.000 gravados, o lote ficou "aplicando" com o aviso e o Continuar; no fim, o retrato igual ao de uma vez`);
    await desfazer(p, lote3);

    /* sabotagem: sem a volta das já gravadas (300 linhas; a 200 falha) */
    const pequena = G.grande(ref, 300);
    gatilho('950200');
    const volta = sabotar(MODELO_IMP, "            if ($e['fase'] === 'desfazendo') {\n", "            if ($e['fase'] === 'desfazendo') {\n                return $this->terminarComFalha($id, $e); // sabotagem da prova da Onda 6, Etapa 4\n");
    try {
      await enviar(p, pequena);
      await aplicar(p);
      const sobra = um("SELECT COUNT(*) FROM col_usuarios WHERE rm BETWEEN '950001' AND '950300'");
      L.ok(sobra > 0, `sabotagem (sem desfazer as linhas já gravadas): a prova reprova — ${sobra} aluno(s) ficaram de uma importação que falhou`);
    } finally { volta(); semGatilho(); limparRa('950001', '950300'); }
    L.ok(sha(MODELO_IMP) === shas[MODELO_IMP] && JSON.stringify(contagens()) === JSON.stringify(antes), 'o model da importação voltou igual (sha256); o banco, às contagens do começo');

    /* ===== B. os relatórios ===== */
    const OF = um('SELECT o.id FROM rm_professores_materias o WHERE EXISTS (SELECT 1 FROM portal_disciplinas_alunos e WHERE e.rm_prof_mat_id = o.id) ORDER BY o.id LIMIT 1');
    const MC = um('SELECT MIN(id) FROM rm_matriculas_curso');
    const casos = [['painel-academico', ''], ['resultado-disciplina', ''], ['distribuicao-notas', ''], ['frequencia-minimo', ''], ['alunos-risco', ''], ['aptos-concluir', ''],
      ['situacao-tcc', ''], ['concluintes', ''], ['coorte-ingresso', ''], ['mapa-notas', `oferta=${OF}`], ['frequencia-oferta', `oferta=${OF}`], ['desempenho-aluno', `matricula=${MC}`]];
    const texto = () => p.$eval('.page-body', (el) => { const c = el.cloneNode(true); c.querySelectorAll('p.small.text-muted').forEach((x) => { if (/Gerado em/.test(x.textContent)) { x.remove(); } }); return c.innerText.replace(/\s+/g, ' ').trim(); }).catch(() => '');
    const relatorios = async () => {
      const r = [];
      for (const [slug, q] of casos) {
        const url = L.BASE + `painel/relatorios/${slug}${q ? '?' + q : ''}`;
        sql("UPDATE adms_parametros SET valor = '20' WHERE chave = 'relatorios.calculo_de_uma_vez'");
        await p.goto(url);
        const deUmaVez = await texto();
        sql("UPDATE adms_parametros SET valor = '0' WHERE chave = 'relatorios.calculo_de_uma_vez'");
        const e = escutar(p, `relatorios/${slug}`);
        await p.goto(url);
        const emPassos = !!(await p.$('#cartaoCalculando'));
        if (emPassos) { await p.waitForURL(/[?&]calc=[0-9a-f]{32}/, { timeout: 300000 }); await p.waitForLoadState('load'); }
        e.parar();
        const calc = (/[?&]calc=([0-9a-f]{32})/.exec(p.url()) || [])[1] || '';
        r.push({ slug, emPassos, igual: (await texto()) === deUmaVez, passos: e.duracoes, marca: !!(await p.$('#calculadoEmPartes')),
          guardados: calc ? um(`SELECT COUNT(*) FROM adms_relatorio_calculos WHERE execucao = '${calc}'`) : 0, recusas: e.respostas.filter((x) => x.erro).length });
      }
      sql("UPDATE adms_parametros SET valor = '20' WHERE chave = 'relatorios.calculo_de_uma_vez'");
      return r;
    };
    const rel = await relatorios();
    const passosRel = rel.flatMap((x) => x.passos);
    const emPassos = rel.filter((x) => x.emPassos);
    L.ok(emPassos.length >= 5 && emPassos.some((x) => x.slug === 'painel-academico') && rel.every((x) => x.igual) && emPassos.every((x) => x.marca && x.guardados > 0 && x.recusas === 0) && Math.max(...passosRel) <= LIMITE_PASSO,
      `os relatórios: ${emPassos.length} de ${rel.length} calcularam em passos (${emPassos.map((x) => x.slug).join(', ')}), todos com o mesmo texto do de uma vez${rel.filter((x) => !x.igual).length ? ' — DIFERENTES: ' + rel.filter((x) => !x.igual).map((x) => x.slug).join(', ') : ''}; o passo mais longo, ${Math.max(...passosRel)} ms; o rodapé diz "calculadas em partes"; ${emPassos.reduce((t, x) => t + x.guardados, 0)} cálculos guardados${emPassos.some((x) => x.recusas || !x.guardados) ? ' — SEM GUARDAR em ' + emPassos.filter((x) => x.recusas || !x.guardados).map((x) => x.slug).join(', ') : ''}`);
    const volta2 = sabotar(CALCULO, "serialize(Fonte::calcular($k))", "serialize((function ($v) { $v['alunos'] = array_slice($v['alunos'] ?? [], 1); return $v; })(Fonte::calcular($k))) /* sabotagem da prova da Onda 6, Etapa 4 */");
    let relS = [];
    try { relS = await relatorios(); } finally { volta2(); }
    const pegos = relS.filter((x) => x.emPassos && !x.igual).map((x) => x.slug);
    L.ok(pegos.length > 0, `sabotagem (um aluno a menos em cada cálculo guardado): a prova reprova — diferentes em ${pegos.join(', ') || 'nenhum'}`);
    L.ok(sha(CALCULO) === shas[CALCULO], 'o cálculo dos relatórios em passos voltou igual (sha256)');

    /* ===== C. confirmar a enturmação, com o envio automático (o Moodle falso da prova) ===== */
    const chamadas = [];
    servidor = http.createServer((rq, rs) => {
      let corpo = '';
      rq.on('data', (c) => { corpo += c; });
      rq.on('end', () => {
        const fn = (new URLSearchParams(corpo)).get('wsfunction') || '';
        chamadas.push({ t: Date.now(), fn });
        setTimeout(() => { rs.writeHead(200, { 'Content-Type': 'application/json' }); rs.end(JSON.stringify({ exception: 'moodle_exception', errorcode: 'provafalsa', message: 'Moodle falso da prova' })); }, 1500);
      });
    });
    await new Promise((ok) => servidor.listen(0, '127.0.0.1', ok));
    const porta = servidor.address().port;
    const ligou = runner(['moodle-falso', `http://localhost:${porta}`]);
    /* duas disciplinas livres: a demonstração enturma todos; a prova tira dois alunos de uma oferta aberta (só no banco de prova; o ciclo refaz) */
    const tirar = sql("SELECT CONCAT(e.rm, ':', e.rm_prof_mat_id) FROM portal_disciplinas_alunos e JOIN rm_professores_materias o ON o.id = e.rm_prof_mat_id WHERE o.encerrada_em IS NULL ORDER BY e.id DESC LIMIT 2").split(/\n/).filter(Boolean);
    for (const x of tirar) {
      const [rm, of] = x.split(':');
      sql(`DELETE FROM rm_disciplina_atividade_notas WHERE rm = '${rm}' AND rm_prof_mat_id = ${of}; DELETE FROM rm_disciplina_faltas WHERE rm = '${rm}' AND rm_prof_mat_id = ${of}; DELETE p FROM rm_oferta_presencas p JOIN rm_oferta_encontros c ON c.id = p.encontro_id WHERE p.rm = '${rm}' AND c.rm_prof_mat_id = ${of}; DELETE FROM portal_disciplinas_alunos WHERE rm = '${rm}' AND rm_prof_mat_id = ${of}`);
    }
    /* os candidatos: os dois alunos tirados, com a posição no ano da oferta (a tela já traz a oferta sugerida marcada) */
    const candidatos = [];
    for (const x of tirar) {
      const [rm, of] = x.split(':');
      const ano = um(`SELECT ano FROM rm_professores_materias WHERE id = ${of}`);
      const pos = um(`SELECT IFNULL(MAX(me.id), 0) FROM rm_matriculas_ensino me JOIN col_usuarios u ON u.id = me.usuario_id WHERE u.rm = '${rm}' AND me.ano = ${ano}`);
      await p.goto(L.BASE + `painel/portal-alunos/visualizar/${rm}?ano=${ano}`);
      if (pos && await p.$(`form[action*="confirmar-enturmacao/${pos}"] input[name="ofertas[]"][value="${of}"]:not([disabled])`)) { candidatos.push({ rm, ano, pos, oferta: of }); }
    }
    const confirmar = async (c) => {
      await p.goto(L.BASE + `painel/portal-alunos/visualizar/${c.rm}?ano=${c.ano}`);
      await p.check(`form[action*="confirmar-enturmacao/${c.pos}"] input[name="ofertas[]"][value="${c.oferta}"]`);
      const n0 = chamadas.length;
      const e = escutar(p, `confirmar-enturmacao/${c.pos}`);
      const t0 = Date.now();
      await Promise.all([p.waitForNavigation({ timeout: 120000 }), p.click(`form[action*="confirmar-enturmacao/${c.pos}"] [type="submit"]`)]);
      const naPagina = chamadas.length - n0;
      const s = Date.now() - t0;
      await p.waitForSelector('#progressoMoodleEnturmacao[data-situacao="concluida"], #progressoMoodleEnturmacao[data-situacao="falhou"]', { timeout: 180000 }).catch(() => {});
      e.parar();
      return { naPagina, naJanela: chamadas.length - n0 - naPagina, s, passos: e.duracoes.slice(1), janela: await p.getAttribute('#progressoMoodleEnturmacao', 'data-situacao').catch(() => null),
        enturmou: um(`SELECT COUNT(*) FROM portal_disciplinas_alunos WHERE rm = '${c.rm}' AND rm_prof_mat_id = ${c.oferta}`) === 1 };
    };
    if (candidatos.length < 2 || !ligou.ok) {
      L.ok(false, `confirmar a enturmação: ${ligou.ok ? 'não há duas posições com disciplina livre na demonstração' : 'o Moodle falso não ligou: ' + JSON.stringify(ligou)}`);
    } else {
      const c1 = await confirmar(candidatos[0]);
      L.ok(c1.enturmou && c1.naPagina === 0 && c1.naJanela > 0 && c1.janela === 'concluida' && Math.max(...c1.passos, 0) <= LIMITE_PASSO,
        `confirmar a enturmação com o envio automático: a página gravou em ${c1.s} ms sem chamar o Moodle (${c1.naPagina} chamada); a janela da tela do aluno enviou em ${c1.passos.length} passo(s) (${c1.naJanela} chamadas ao Moodle falso, que responde em 1,5 s)`);
      const volta3 = sabotar(CTRL_PORTAL, '            $M::soFila(true);\n', '            $M::soFila(false); // sabotagem da prova da Onda 6, Etapa 4\n');
      let c2 = {};
      try { c2 = await confirmar(candidatos[1]); } finally { volta3(); }
      L.ok(c2.naPagina > 0, `sabotagem (o envio de volta dentro da página): a prova reprova — ${c2.naPagina} chamada(s) ao Moodle dentro da página (${c2.s} ms)`);
    }
    L.ok(sha(CTRL_PORTAL) === shas[CTRL_PORTAL], 'o controlador do portal de alunos voltou igual (sha256)');
    runner(['moodle-voltar']);

    /* ===== D. encerrar uma oferta com 5.000 alunos ===== */
    const aberta = um('SELECT IFNULL(MIN(o.id), 0) FROM rm_professores_materias o WHERE o.encerrada_em IS NULL AND EXISTS (SELECT 1 FROM portal_disciplinas_alunos e WHERE e.rm_prof_mat_id = o.id) AND EXISTS (SELECT 1 FROM rm_disciplina_atividade a WHERE a.rm_prof_mat_id = o.id)');
    const m = oferta(['montar', String(aberta), '5000']);
    const enc = oferta(['medir', String(aberta)]);
    oferta(['limpar', String(aberta)]);
    L.ok(aberta > 0 && m.alunos === 5000 && enc.ok && enc.gravados === enc.alunos && enc.igual_ao_calculo && enc.encerrar_s <= 10,
      `encerrar uma oferta com ${enc.alunos} alunos numa página só: ${enc.encerrar_s} s (o cálculo, ${enc.calculo_s} s); o gravado igual ao calculado, aluno a aluno`);
    const aberta2 = um('SELECT IFNULL(MIN(o.id), 0) FROM rm_professores_materias o WHERE o.encerrada_em IS NULL AND EXISTS (SELECT 1 FROM portal_disciplinas_alunos e WHERE e.rm_prof_mat_id = o.id) AND EXISTS (SELECT 1 FROM rm_disciplina_atividade a WHERE a.rm_prof_mat_id = o.id)');
    const volta4 = sabotar(ENCERRAR, '            foreach (array_chunk($linhas, 200) as $parte) {\n', '            foreach (array_slice(array_chunk($linhas, 200), 1) as $parte) { // sabotagem da prova da Onda 6, Etapa 4\n');
    let encS = {};
    try { oferta(['montar', String(aberta2), '1000']); encS = oferta(['medir', String(aberta2)]); oferta(['limpar', String(aberta2)]); } finally { volta4(); }
    L.ok(aberta2 > 0 && !(encS.gravados === encS.alunos && encS.igual_ao_calculo), `sabotagem (um bloco de alunos a menos no encerramento): a prova reprova — ${encS.gravados} gravados para ${encS.alunos} alunos`);
    L.ok(sha(ENCERRAR) === shas[ENCERRAR], 'o encerramento voltou igual (sha256)');
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    try { semGatilho(); } catch (e) { /* já sem */ }
    try { sql("UPDATE adms_parametros SET valor = '20' WHERE chave = 'relatorios.calculo_de_uma_vez'"); } catch (e) { /* */ }
    if (servidor) { runner(['moodle-voltar']); servidor.close(); }
    for (const [f, h] of Object.entries(shas)) { if (sha(f) !== h) { console.log(`  [ALERTA] ${path.basename(f)} não voltou igual`); } }
    await navegador.close();
  }
  process.exit(L.resumo('onda6/etapa4') ? 0 : 1);
})();
