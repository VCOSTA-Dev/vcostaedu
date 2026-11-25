/*
 * ONDA 4, ETAPA 5 — o motor de médias: o acréscimo e as regras prontas (docs/ESCOLA.md, D-50).
 *   A. a função pura (tests/tabler/onda4/acrescimo.php): sem a opção, o cálculo é o de sempre (a atividade de acréscimo
 *      não muda nada); 5,0 + 1,0 → 6,0; o teto (9,5 + 1,0 → 10,0); o limite (0,5); dois acréscimos somam; antes do
 *      arredondamento; fora dos componentes; na escola, o limite vale por etapa; o limite validado;
 *   B. as regras prontas no catálogo ("Só atividades"; "Prova mensal + prova bimestral + atividades": 30/40 obrigatória/30),
 *      sem trocar a regra de nenhuma disciplina; a regra nova nasce com "Permitir acréscimo" desmarcado;
 *   C. pela tela, no banco de prova (ensino superior): marcar "Permitir acréscimo" com o limite numa regra; o simulador
 *      soma o acréscimo; o tipo "Acréscimo" só aparece na oferta cuja regra o permite (e o cadastro direto é recusado
 *      na outra); o cálculo REAL de um aluno (a fachada) = o simulador, com o limite; desmarcar volta ao de sempre;
 *   D. na escola, os Primeiros passos têm a fase das regras de média;
 *   sabotagem: o limite desligado no motor — a prova reprova; o arquivo volta.
 *
 *   node tests/tabler/onda4/etapa5.js   (mexe na regra de uma oferta do banco de prova: rode o ciclo depois)
 */
const fs = require('fs');
const path = require('path');
const { execFileSync, spawnSync } = require('child_process');
const L = require('../lib');

const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste$/.test(BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
const MYSQL = process.env.VCOSTAEDU_MYSQL || 'C:/xampp/mysql/bin/mysql.exe';
const PHP = process.env.VCOSTAEDU_PHP || 'C:/xampp/php/php.exe';
const sql = (q) => execFileSync(MYSQL, ['-u', 'root', '-N', '--default-character-set=utf8mb4', BANCO], { input: Buffer.from(q, 'utf8') }).toString().replace(/\r/g, '').trim();
const um = (q) => +sql(q);
const ETAPA = 'onda4/etapa5';
const SENHA = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const MOTOR = path.join(L.RAIZ, 'core', 'MotorMedia.php');
const SO = 'Só atividades';
const PROVA = 'Prova mensal + prova bimestral + atividades';

const puro = () => JSON.parse(execFileSync(PHP, [path.join(L.RAIZ, 'tests', 'tabler', 'onda4', 'acrescimo.php')], { encoding: 'utf8' }));
const real = (oferta, rm) => JSON.parse(execFileSync(PHP, [path.join(L.RAIZ, 'tests', 'tabler', 'onda4', 'media_real.php'), String(oferta), String(rm)], { encoding: 'utf8' }));

async function entrar(navegador, usuario = 'administrador', senha = SENHA) {
  const ctx = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' });
  const p = await L.pagina(ctx);
  await p.goto(L.BASE + 'painel/login/acesso');
  await p.fill('input[name="usuario"]', usuario);
  await p.fill('input[name="senha"]', senha);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
  return { ctx, p };
}
async function postar(p, rota, campos) {
  return p.evaluate(async ([u, c]) => { const f = new URLSearchParams(); for (const [k, v] of Object.entries(c)) { f.append(k, v); }
    const r = await fetch(u, { method: 'POST', body: f, credentials: 'same-origin' }); return { url: r.url, texto: await r.text() }; }, [L.BASE + 'painel/' + rota, campos]);
}
function conferePuro(j) {
  return j.sem_opcao_igual === true && j.exemplo === '6.0' && j.teto === '10.0' && j.limite === '5.5' && j.dois_sem_limite === '6.2'
    && j.antes_do_arredondamento === '5.1' && j.escola_etapa1 === '5.5' && j.escola_etapa2 === '7.5' && Array.isArray(j.valida_limite) && j.valida_limite.length > 0
    && j.memoria.length === 1 && /Acréscimo: \+ 1,0/.test(j.memoria[0]);
}
/** O simulador da tela: a média que aparece depois de mexer no formulário. */
async function simulado(p) {
  await p.waitForFunction(() => { const e = document.getElementById('simEspera'); return !e || e.hidden || getComputedStyle(e).display === 'none'; }, null, { timeout: 15000 }).catch(() => {});
  await p.waitForTimeout(1200);
  return (await p.textContent('#simMedia')).trim();
}
async function salvarRegra(p) {
  await Promise.all([p.waitForNavigation(), p.click('#salvarRegra')]);
  if (await p.$('#confirmarSalvar')) { await Promise.all([p.waitForNavigation(), p.click('#salvarRegra')]); }
}

(async () => {
  const { navegador } = await L.abrir();
  try {
    console.log('\n== Onda 4, Etapa 5 — o acréscimo e as regras prontas');

    /* A. a função pura */
    const j = puro();
    L.ok(j.sem_opcao_igual === true && j.sem_opcao_media === '5.0', `sem a opção, a atividade de acréscimo não muda nada (5,0 continua ${j.sem_opcao_media})`);
    L.ok(j.exemplo === '6.0' && j.memoria.length === 1 && /Acréscimo: \+ 1,0/.test(j.memoria[0]), `5,0 + 1,0 → ${j.exemplo}; a memória: "${j.memoria[0]}"`);
    L.ok(j.teto === '10.0', `o teto: 9,5 + 1,0 → ${j.teto}`);
    L.ok(j.limite === '5.5' && j.dois_sem_limite === '6.2', `o limite da regra (0,5; lançado 1,0): 5,0 → ${j.limite}; dois acréscimos (0,6 + 0,6) sem limite: ${j.dois_sem_limite}`);
    L.ok(j.antes_do_arredondamento === '5.1', `antes do arredondamento: 5,04 + 0,04 → ${j.antes_do_arredondamento} (depois daria 5,0)`);
    L.ok(j.lancadas === 1, `fora dos componentes: a atividade de acréscimo não conta como atividade lançada do componente (${j.lancadas})`);
    L.ok(j.escola_etapa1 === '5.5' && j.escola_etapa2 === '7.5', `na escola, o limite vale por etapa: 5,0 + 1,0 → ${j.escola_etapa1} e 7,0 + 1,0 → ${j.escola_etapa2} (limite 0,5 em cada uma); anual ${j.escola_anual}`);
    L.ok(Array.isArray(j.valida_limite) && j.valida_limite.length > 0, `o limite validado: "${(j.valida_limite || [])[0]}"`);

    /* B. as regras prontas */
    const prontas = sql(`SELECT r.nome, r.padrao, GROUP_CONCAT(CONCAT(c.codigo, ':', ROUND(c.peso), ':', c.min_atividades) ORDER BY c.ordem SEPARATOR ',') FROM rm_media_regras r
      INNER JOIN rm_media_regra_versoes v ON v.regra_id = r.id AND v.versao = r.versao_atual INNER JOIN rm_media_regra_componentes c ON c.versao_id = v.id
      WHERE r.nome IN ('${SO}', '${PROVA}') GROUP BY r.id ORDER BY r.nome`).split('\n').map((l) => l.split('\t'));
    L.ok(prontas.length === 2 && prontas.every((x) => x[1] === '0') && prontas.some((x) => x[0] === PROVA && x[2] === 'prova_mensal:30:0,prova_bimestral:40:1,atividades:30:0')
      && prontas.some((x) => x[0] === SO && x[2] === 'atividades:100:0'), `as regras prontas no catálogo, nenhuma como padrão: ${prontas.map((x) => x[0] + ' (' + x[2] + ')').join('; ')}`);
    L.ok(um(`SELECT COUNT(*) FROM uni_rh_cursos_disciplinas_grade l INNER JOIN rm_media_regras r ON r.id = l.regra_media_id WHERE r.nome IN ('${SO}', '${PROVA}')`) === 0
      && um(`SELECT COUNT(*) FROM rm_professores_materias o INNER JOIN rm_media_regras r ON r.id = o.regra_media_id WHERE r.nome IN ('${SO}', '${PROVA}')`) === 0,
      'nenhuma disciplina nem oferta mudou de regra');
    L.ok(um('SELECT COUNT(*) FROM rm_media_regra_acrescimo') === 0, 'nenhuma regra permite o acréscimo (o padrão: não)');
    const adm = await entrar(navegador);
    await adm.p.goto(L.BASE + 'painel/regra-media/listar');
    const lista = await adm.p.content();
    L.ok(lista.includes(SO) && lista.includes(PROVA), 'a lista das regras de média mostra as duas prontas');
    await L.semErros(adm.p, 'as regras de média');
    await adm.p.goto(L.BASE + 'painel/regra-media/cadastrar');
    L.ok(await adm.p.$('#rAcrescimo') !== null && !(await adm.p.isChecked('#rAcrescimo')), 'a regra nova: "Permitir acréscimo" desmarcado');

    /* C. pela tela: a regra "Só atividades" com o acréscimo, limite 0,5 */
    const soId = um(`SELECT id FROM rm_media_regras WHERE nome = '${SO}'`);
    await adm.p.goto(L.BASE + `painel/regra-media/editar/${soId}`);
    await L.semErros(adm.p, 'a regra pronta Só atividades');
    await adm.p.check('#rAcrescimo');
    await adm.p.fill('#rAcrescimoLimite', '0,5');
    /* o simulador: uma atividade com 5,0 e o acréscimo de 1,0 → 5,5 (o limite) */
    await adm.p.$$eval('#simTabela .s-tirar', (bs) => bs.slice(1).forEach((b) => b.click()));
    await adm.p.fill('#simTabela .s-nota', '5');
    await adm.p.fill('#simAcrescimo', '1');
    await adm.p.dispatchEvent('#simAcrescimo', 'input');
    const sim1 = await simulado(adm.p);
    await adm.p.uncheck('#rAcrescimo');
    await adm.p.dispatchEvent('#rAcrescimo', 'change');
    const sim0 = await simulado(adm.p);
    await adm.p.check('#rAcrescimo');
    await adm.p.dispatchEvent('#rAcrescimo', 'change');
    L.ok(sim1 === '5,5' && sim0 === '5,0', `o simulador: 5,0 com o acréscimo 1,0 e o limite 0,5 → ${sim1}; sem "Permitir acréscimo" → ${sim0}`);
    await L.print(adm.p, ETAPA, 'regra-acrescimo');
    await salvarRegra(adm.p);
    const linha = sql(`SELECT a.permitir, a.limite FROM rm_media_regra_acrescimo a INNER JOIN rm_media_regra_versoes v ON v.id = a.versao_id INNER JOIN rm_media_regras r ON r.id = v.regra_id AND v.versao = r.versao_atual WHERE r.id = ${soId}`);
    L.ok(linha === '1\t0.50', `salva: a versão nova da regra guarda o acréscimo (${linha.replace('\t', ', limite ')})`);
    await adm.p.goto(L.BASE + `painel/regra-media/editar/${soId}`);
    L.ok(await adm.p.isChecked('#rAcrescimo') && /^0,50?$/.test(await adm.p.inputValue('#rAcrescimoLimite')), 'a tela abre de novo com o acréscimo marcado e o limite 0,5');

    /* uma oferta aberta do banco de prova, com um aluno que tem nota */
    const [of, rm] = sql(`SELECT o.id, n.rm FROM rm_professores_materias o INNER JOIN rm_disciplina_atividade_notas n ON n.rm_prof_mat_id = o.id AND n.nota IS NOT NULL
      WHERE o.encerrada_em IS NULL ORDER BY o.id, n.rm LIMIT 1`).split('\t');
    const outra = um(`SELECT o.id FROM rm_professores_materias o WHERE o.encerrada_em IS NULL AND o.id <> ${of} ORDER BY o.id LIMIT 1`);
    await adm.p.goto(L.BASE + `painel/atividades/listar/${of}`);
    const antesTipo = await adm.p.$(`a[href*="atividades/cadastrar/${of}?tipo=6"]`);
    const r1 = await postar(adm.p, `regra-media/oferta/${of}`, { acao: 'trocar', regra_media_id: String(soId), justificativa: 'Prova do acréscimo (Onda 4, Etapa 5)' });
    L.ok(um(`SELECT regra_media_id FROM rm_professores_materias WHERE id = ${of}`) === soId, `a oferta ${of} passa à regra "${SO}" (pela tela da oferta)`);
    await adm.p.goto(L.BASE + `painel/atividades/listar/${of}`);
    const depoisTipo = await adm.p.$(`a[href*="atividades/cadastrar/${of}?tipo=6"]`);
    await adm.p.goto(L.BASE + `painel/atividades/listar/${outra}`);
    const outraTipo = await adm.p.$(`a[href*="atividades/cadastrar/${outra}?tipo=6"]`);
    L.ok(!antesTipo && !!depoisTipo && !outraTipo, 'o tipo "Acréscimo" só aparece na oferta cuja regra permite (antes da troca e na outra oferta, não)');
    /* o cadastro: na oferta que permite, entra; na outra, o POST direto é recusado */
    const hoje = new Date().toISOString().slice(0, 10);
    const campos = (o) => ({ rm_prof_mat_id: String(o), tipo: '6', peso: '10', nomeAtividade: 'Acréscimo da prova', descricao: 'prova', entrega_arquivo: '2', data_inicial: hoje, data_final: hoje, CadAtividadeNota: 'Salvar' });
    await postar(adm.p, `atividades/cadastrar/${outra}?tipo=6`, campos(outra));
    L.ok(um(`SELECT COUNT(*) FROM rm_disciplina_atividade WHERE rm_prof_mat_id = ${outra} AND tipo = 6`) === 0, 'na oferta cuja regra não permite, o acréscimo é recusado (também no envio direto)');
    const media0 = real(of, rm).media;
    await postar(adm.p, `atividades/cadastrar/${of}?tipo=6`, campos(of));
    const acrId = um(`SELECT MAX(id) FROM rm_disciplina_atividade WHERE rm_prof_mat_id = ${of} AND tipo = 6`);
    L.ok(acrId > 0 && sql(`SELECT IFNULL(componente_codigo, '-') FROM rm_disciplina_atividade WHERE id = ${acrId}`) === '-', `o professor lança o acréscimo como uma atividade própria, sem componente (a atividade ${acrId})`);
    L.ok(real(of, rm).media === media0, `sem nota de acréscimo, a média do aluno ${rm} não muda (${media0})`);
    if (um(`SELECT COUNT(*) FROM rm_disciplina_atividade_notas WHERE atividade_id = ${acrId} AND rm = '${rm}'`)) {
      sql(`UPDATE rm_disciplina_atividade_notas SET nota = '1.0' WHERE atividade_id = ${acrId} AND rm = '${rm}'`);
    } else {
      sql(`INSERT INTO rm_disciplina_atividade_notas (rm, atividade_id, rm_prof_mat_id, nota) VALUES ('${rm}', ${acrId}, ${of}, '1.0')`);
    }
    const r2 = real(of, rm);
    /* o simulador da regra salva (sem formulário), com as notas do aluno e o acréscimo = o cálculo real */
    const notas = sql(`SELECT n.nota, a.peso FROM rm_disciplina_atividade a LEFT JOIN rm_disciplina_atividade_notas n ON n.atividade_id = a.id AND n.rm = '${rm}'
      WHERE a.rm_prof_mat_id = ${of} AND a.situacao = 1 AND a.tipo <> 6 ORDER BY a.data_inicial, a.id`).split('\n').map((l) => l.split('\t'));
    const corpo = { regra_id: String(soId), simAcrescimo: '1' };
    notas.forEach(([n, p], i) => { corpo[`sim[${i}][nota]`] = n === 'NULL' ? '' : n; corpo[`sim[${i}][peso]`] = p; corpo[`sim[${i}][componente]`] = 'atividades'; });
    const sim = JSON.parse((await postar(adm.p, 'regra-media/simular', corpo)).texto);
    const esperado = (Math.min(10, +media0 + 0.5)).toFixed(1);
    L.ok(r2.media === esperado && sim.media === r2.media && (r2.memoria || []).some((l) => /^Acréscimo: \+ 0,50? \(o limite/.test(l)),
      `o cálculo real (a fachada) do aluno ${rm}: ${media0} + acréscimo 1,0 com limite 0,5 → ${r2.media}; o simulador da regra salva: ${sim.media}; a memória: "${(r2.memoria || []).find((l) => /^Acréscimo/.test(l))}"`);
    await adm.p.goto(L.BASE + `painel/atividades/listar/${of}`);
    await L.semErros(adm.p, 'as atividades com o acréscimo');

    /* sabotagem: o limite desligado no motor */
    const orig = fs.readFileSync(MOTOR, 'utf8');
    let pegou = false;
    try {
      const de = '$vale = $limite === null ? $somaAcr : min($somaAcr, $limite);';
      if (!orig.includes(de)) { throw new Error('sabotagem sem o trecho'); }
      fs.writeFileSync(MOTOR, orig.replace(de, '$vale = $somaAcr;'));
      pegou = !conferePuro(puro()) && real(of, rm).media !== esperado;
    } finally { fs.writeFileSync(MOTOR, orig); }
    L.ok(pegou && fs.readFileSync(MOTOR, 'utf8') === orig && conferePuro(puro()), 'sabotagem (o limite do acréscimo desligado no motor): a prova reprova; o arquivo voltou');

    /* desmarcar: a regra volta ao cálculo de sempre (a nota de acréscimo fica, sem efeito) */
    await adm.p.goto(L.BASE + `painel/regra-media/editar/${soId}`);
    await adm.p.uncheck('#rAcrescimo');
    await salvarRegra(adm.p);
    L.ok(real(of, rm).media === media0, `sem "Permitir acréscimo", a média volta a ${media0}`);

    /* D. a fase dos Primeiros passos da escola */
    L.ok(sql("SELECT CONCAT(modulo, '|', roteiro, '|', rota) FROM adms_fases WHERE codigo = 'esc_regras'") === 'escola|fase-escola-regras|regra-media/listar'
      && fs.existsSync(path.join(L.RAIZ, 'painel', 'app', 'adms', 'Roteiros', 'fase-escola-regras.json')), 'os Primeiros passos da escola: a fase "As regras de média" (manter as prontas ou ajustar), com o roteiro');
    void r1;
    await adm.ctx.close();
  } catch (e) {
    L.ok(false, 'a prova parou: ' + (e && e.message ? e.message.split('\n')[0] : e));
  } finally {
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
