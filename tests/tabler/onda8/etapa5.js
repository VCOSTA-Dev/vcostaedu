/*
 * PROVA DA ONDA 8, CORREÇÃO, PARTE 1.1 — o horário de aulas completo (D-71). APAGA o banco de prova.
 *   1. a demonstração do colégio, montada pela tela numa instalação nova: o horário padrão por turno (editável) e a
 *      semana das quatro turmas de 2026 montada (todas as aulas do turno, de segunda a sexta, cada componente com aula);
 *   2. a secretaria, pela tela "Horários de aula": edita uma aula, cadastra uma nova, e a sobreposição é recusada com o
 *      motivo;
 *   3. a secretaria chega ao horário da turma pelo botão "Horário" da tela da turma (sem digitar endereço), escolhe o
 *      componente de uma aula e ele grava na hora: o ícone pequeno de confirmado, com o título "Gravado", que some
 *      sozinho; nenhuma caixa de mensagem; o registro de uso guarda o antes e o depois;
 *   4. o erro: uma aula desativada noutra aba, a escolha na grade é recusada — o ícone de alerta fica, com o motivo ao
 *      lado, e a escolha volta ao gravado;
 *   5. o aluno vê a semana no portal (o componente e o professor), a coluna de hoje em destaque;
 *   6. o responsável vê a semana do filho no portal da família;
 *   7. a mensagem honesta: o aluno do ano anterior, sem horário, lê "Ainda não há horário cadastrado";
 *   sabotagem: a gravação do horário da turma sem a linha nova (a escolha não fica) — a prova acusa; o arquivo volta
 *   igual (sha256).
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { spawnSync, execFileSync } = require('child_process');
const L = require('../lib');

const ETAPA = 'onda8/etapa5';
const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste$/.test(BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
const MODELO = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'escola', 'AdmsHorarioTurma.php');
const ANO = 2026;

/* o mysql do Windows termina as linhas com CR: cada linha sai aparada */
const sql = (q) => execFileSync('C:/xampp/mysql/bin/mysql.exe', ['-uroot', '--default-character-set=utf8mb4', '-N', '-B', BANCO, '-e', q], { encoding: 'utf8' }).split('\n').map((l) => l.trim()).filter(Boolean).join('\n');
const um = (q) => +(sql(q).split('\n')[0] || 0);
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const montar = (rotulo) => spawnSync(process.execPath, [path.join(__dirname, '..', 'onda6', 'medir_demo.js'), 'escola', rotulo], { encoding: 'utf8', maxBuffer: 1 << 24 });
async function entrar(navegador, lado, usuario, senha, largura = 1366) {
  const ctx = await navegador.newContext({ viewport: { width: largura, height: 900 }, locale: 'pt-BR' });
  const p = await L.pagina(ctx);
  await p.goto(L.BASE + lado + '/login/acesso');
  await p.fill('input[name="usuario"]', usuario);
  await p.fill('input[name="senha"]', senha);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
  return { ctx, p };
}
/** A célula da grade (dia, aula) e o que acontece ao escolher: o sinal ao lado e o valor que fica. */
async function escolher(p, dia, aula, oferta) {
  const sel = `select.vc-aula-turma[data-dia="${dia}"][data-aula="${aula}"]`;
  await p.selectOption(sel, String(oferta));
  await p.waitForFunction((s) => { const e = document.querySelector(s); const n = e && e.nextElementSibling; return n && n.classList.contains('vc-sinal') && n.querySelector('.vc-sinal-ok, .vc-sinal-erro'); }, sel, { timeout: 15000 });
  return p.$eval(sel, (e) => {
    const n = e.nextElementSibling;
    const ok = n.querySelector('.vc-sinal-ok');
    const er = n.querySelector('.vc-sinal-erro');
    return { ok: !!ok, titulo: ok ? ok.title : '', erro: !!er, motivo: (n.querySelector('.vc-sinal-motivo') || { textContent: '' }).textContent, valor: e.value, gravado: e.dataset.gravado,
      caixas: document.querySelectorAll('.alert-success, .alert-danger, .toast').length };
  });
}

(async () => {
  const { navegador } = await L.abrir();
  const shaModelo = sha(MODELO);
  try {
    /* 1. a instalação nova com a demonstração do colégio */
    const r = montar('onda8-etapa5');
    L.ok(r.status === 0, 'a demonstração do colégio montada pela tela, numa instalação nova');
    const padrao = sql("SELECT CONCAT(periodo, ':', COUNT(*)) FROM rm_professores_horario_aula WHERE situacao = 1 GROUP BY periodo ORDER BY periodo").split('\n').join(' ');
    L.ok(padrao === '1:6 2:6 3:4', `o horário padrão de cada turno, da migração (turno:aulas = ${padrao})`);
    const turmas = sql(`SELECT CONCAT(s.codnome, ' ', t.nome, '|', t.id) FROM esc_turmas t JOIN rm_cursos_turma_series s ON s.id = t.modulo_id JOIN rm_cursos c ON c.id = s.curso_id
      WHERE t.ano = ${ANO} AND c.nome NOT LIKE 'Curso livre%' ORDER BY s.codnome, t.nome`).split('\n').map((l) => l.split('|'));
    const resumo = turmas.map(([nome, id]) => {
      const aulas = um(`SELECT COUNT(*) FROM rm_professores_horarios h JOIN esc_turma_ofertas x ON x.oferta_id = h.rm_prof_mat_id WHERE x.turma_id = ${id} AND h.situacao = 1`);
      const sem = um(`SELECT COUNT(*) FROM esc_turma_ofertas x WHERE x.turma_id = ${id} AND NOT EXISTS (SELECT 1 FROM rm_professores_horarios h WHERE h.rm_prof_mat_id = x.oferta_id)`);
      return { nome, aulas, sem };
    });
    L.ok(turmas.length === 5 && resumo.every((x) => x.aulas === 30 && x.sem === 0),
      `a semana das cinco turmas de 2026 montada (D-104: com o 8º ano A): ${resumo.map((x) => `${x.nome.trim()} ${x.aulas} aulas${x.sem ? `, ${x.sem} componente(s) sem aula` : ''}`).join('; ')}`);

    /* 2. a secretaria edita, cadastra e tem a sobreposição recusada */
    const cred = JSON.parse(sql("SELECT valor FROM adms_instalacao_estado WHERE chave = 'demonstracao.credenciais'"));
    const senhaSec = (cred.painel || {})['secretaria.demo'] || '';
    const sec = await entrar(navegador, 'painel', 'secretaria.demo', senhaSec);
    await sec.p.goto(L.BASE + 'painel/turmas/listar');
    const id6A = turmas.find(([nome]) => nome.trim() === '6º ano A')[1];
    await Promise.all([sec.p.waitForNavigation(), sec.p.click(`#tabelaTurmas a[href$="turmas/ver/${id6A}"]`)]);
    const turmaUrl = sec.p.url();
    const primeira = um('SELECT id FROM rm_professores_horario_aula WHERE periodo = 1 ORDER BY inicio, id LIMIT 1') /* a 1ª aula (sem acento no SQL: o mysql.exe perde o acento do argumento) */;
    await sec.p.click('#horarioTurma');
    await sec.p.waitForSelector('#gradeTurma');
    await sec.p.click('#editarAulas');
    await sec.p.waitForSelector('#turno1');
    await sec.p.click(`#turno1 tr[data-aula="${primeira}"] .vc-editar-aula`);
    await sec.p.fill('#inicio1', '07:10');
    await Promise.all([sec.p.waitForNavigation(), sec.p.click('#turno1 .vc-salvar-aula')]);
    const editada = sql(`SELECT CONCAT(TIME_FORMAT(inicio, '%H:%i'), '-', TIME_FORMAT(termino, '%H:%i')) FROM rm_professores_horario_aula WHERE id = ${primeira}`);
    await sec.p.fill('#nome1', '7ª aula');
    await sec.p.fill('#inicio1', '12:20');
    await sec.p.fill('#termino1', '13:10');
    await Promise.all([sec.p.waitForNavigation(), sec.p.click('#turno1 .vc-salvar-aula')]);
    const nova = um("SELECT id FROM rm_professores_horario_aula WHERE periodo = 1 AND inicio = '12:20:00' AND termino = '13:10:00' AND situacao = 1");
    await sec.p.fill('#nome1', 'Aula extra');
    await sec.p.fill('#inicio1', '12:30');
    await sec.p.fill('#termino1', '12:50');
    await Promise.all([sec.p.waitForNavigation(), sec.p.click('#turno1 .vc-salvar-aula')]);
    const recusa = (await sec.p.textContent('#horarioRecusado').catch(() => '')) || '';
    await L.print(sec.p, ETAPA, 'horarios-de-aula');
    L.ok(editada === '07:10-07:50' && nova > 0 && /sobrepõe a "7ª aula"/.test(recusa) && um("SELECT COUNT(*) FROM rm_professores_horario_aula WHERE nome = 'Aula extra'") === 0,
      `Horários de aula: a 1ª aula editada (${editada}), a 7ª aula cadastrada, a sobreposição recusada com o motivo ("${recusa.trim().slice(0, 80)}")`);

    /* 3. o horário da turma, pela tela da turma: grava na hora, com o sinal sutil */
    await sec.p.goto(turmaUrl);
    await sec.p.click('#horarioTurma');
    await sec.p.waitForSelector(`select.vc-aula-turma[data-aula="${nova}"][data-dia="2"]`);
    const turma6A = +turmaUrl.split('/').pop();
    const oferta = um(`SELECT o.id FROM esc_turma_ofertas x JOIN rm_professores_materias o ON o.id = x.oferta_id WHERE x.turma_id = ${turma6A} AND o.materia LIKE 'Matem_tica'`);
    const s1 = await escolher(sec.p, 2, nova, oferta);
    const gravou = um(`SELECT COUNT(*) FROM rm_professores_horarios WHERE rm_prof_mat_id = ${oferta} AND dia_semana = 2 AND horario_id = ${nova} AND situacao = 1`);
    await L.print(sec.p, ETAPA, 'horario-turma-gravado');
    await sec.p.waitForTimeout(4500);
    const sumiu = await sec.p.$eval(`select.vc-aula-turma[data-dia="2"][data-aula="${nova}"]`, (e) => !e.nextElementSibling.querySelector('.vc-sinal-ok'));
    const log = um(`SELECT COUNT(*) FROM adms_logs WHERE descricao LIKE '%da turma: turma=${turma6A}; dia=2; aula=${nova}; antes=vaga; depois=Matem%'`);
    L.ok(s1.ok && s1.titulo === 'Gravado' && s1.caixas === 0 && gravou === 1 && sumiu && log === 1,
      `a escolha grava na hora: o ícone de confirmado com o título "${s1.titulo}", nenhuma caixa de mensagem (${s1.caixas}), a linha no banco (${gravou}), o ícone some sozinho (${sumiu}), o registro com o antes e o depois (${log})`);

    /* 4. o erro: a aula desativada noutra aba */
    const outra = await sec.ctx.newPage();
    await outra.goto(L.BASE + 'painel/horarios-aula/listar');
    await Promise.all([outra.waitForNavigation(), outra.click(`#turno1 tr[data-aula="${nova}"] .vc-situacao-aula`)]);
    await outra.close();
    const s2 = await escolher(sec.p, 3, nova, oferta);
    await L.print(sec.p, ETAPA, 'horario-turma-erro');
    await sec.p.waitForTimeout(4000);
    const ficou = await sec.p.$eval(`select.vc-aula-turma[data-dia="3"][data-aula="${nova}"]`, (e) => !!e.nextElementSibling.querySelector('.vc-sinal-erro'));
    L.ok(s2.erro && /não está ativo/.test(s2.motivo) && s2.valor === '0' && ficou && s2.caixas === 0,
      `o erro fica no campo: o ícone de alerta com o motivo ("${s2.motivo}"), a escolha volta ao gravado (${s2.valor}), o alerta continua depois de alguns segundos (${ficou})`);
    await sec.ctx.close();

    /* 5. o aluno vê a semana */
    const ra = '265001';
    const al = await entrar(navegador, 'portal', ra, (cred.portal || {})[ra] || '', 390);
    await al.p.goto(L.BASE + `portal/home/horario-aluno?ano=${ANO}`);
    const semana = await al.p.$eval('#semanaHorario', (e) => ({ dias: [...e.querySelectorAll('.vc-dia-semana .card-title')].map((t) => t.textContent.replace('hoje', '').trim()), aulas: e.querySelectorAll('.vc-aula-semana').length,
      prof: /Profa?\.|Professor/.test(e.textContent), mat: /Matemática/.test(e.textContent) })).catch(() => null);
    const largo = await al.p.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1);
    await L.print(al.p, ETAPA, 'portal-aluno-semana');
    await al.ctx.close();
    L.ok(semana && semana.dias.length === 5 && semana.aulas === 30 && semana.prof && semana.mat && largo,
      `o aluno ${ra} vê a semana no portal: ${semana ? semana.dias.join(', ') : '—'}; ${semana ? semana.aulas : 0} aulas com o componente e o professor (a aula desativada fica fora); sem rolagem lateral da página no celular (${largo})`);

    /* 6. o responsável vê a semana do filho */
    const email = 'helena.ribeiro@exemplo.invalid';
    const filho = um(`SELECT ra.aluno_id FROM esc_responsavel_alunos ra JOIN col_usuarios u ON u.id = ra.aluno_id AND u.rm LIKE '265%' JOIN esc_responsaveis r ON r.id = ra.responsavel_id WHERE r.email = '${email}' ORDER BY ra.aluno_id LIMIT 1`);
    const fam = await entrar(navegador, 'portal', email, (cred.familia || {})[email] || '', 390);
    await fam.p.goto(L.BASE + `portal/familia/horario/${filho}?ano=${ANO}`);
    const semF = await fam.p.$eval('#semanaHorario', (e) => e.querySelectorAll('.vc-aula-semana').length).catch(() => 0);
    await L.print(fam.p, ETAPA, 'portal-familia-semana');
    await fam.ctx.close();
    L.ok(filho > 0 && semF >= 30, `o responsável vê a semana do filho no portal da família (${semF} aulas)`);

    /* 7. a mensagem honesta, sem horário */
    const ra25 = '255001';
    const a25 = await entrar(navegador, 'portal', ra25, (cred.portal || {})[ra25] || '');
    await a25.p.goto(L.BASE + 'portal/home/horario-aluno?ano=2025');
    const honesta = (await a25.p.textContent('#semHorario').catch(() => '')) || '';
    const velha = /não é o ano atual/.test(await a25.p.textContent('body'));
    await L.print(a25.p, ETAPA, 'portal-sem-horario');
    await a25.ctx.close();
    L.ok(/Ainda não há horário cadastrado/.test(honesta) && !velha, `sem horário, a mensagem honesta: "${honesta.trim()}" (e nunca mais "não é o ano atual")`);

    /* sabotagem: a gravação sem a linha nova */
    const orig = fs.readFileSync(MODELO, 'utf8');
    const trecho = "if ($ofertaId) {\n                $pdo->prepare('INSERT INTO rm_professores_horarios";
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let s3 = null;
    let linha = -1;
    try {
      fs.writeFileSync(MODELO, orig.replace(trecho, "if (false) {\n                $pdo->prepare('INSERT INTO rm_professores_horarios"));
      const sb = await entrar(navegador, 'painel', 'secretaria.demo', senhaSec);
      await sb.p.goto(L.BASE + `painel/turmas/horario/${turma6A}`);
      const aula2 = um('SELECT id FROM rm_professores_horario_aula WHERE periodo = 1 ORDER BY inicio, id LIMIT 1 OFFSET 1');
      const outraOf = um(`SELECT o.id FROM esc_turma_ofertas x JOIN rm_professores_materias o ON o.id = x.oferta_id WHERE x.turma_id = ${turma6A} AND o.materia = 'Arte'`);
      s3 = await escolher(sb.p, 4, aula2, outraOf);
      linha = um(`SELECT COUNT(*) FROM rm_professores_horarios WHERE rm_prof_mat_id = ${outraOf} AND dia_semana = 4 AND horario_id = ${aula2}`);
      await sb.ctx.close();
    } finally {
      fs.writeFileSync(MODELO, orig);
    }
    L.ok(s3 && linha === 0, `sabotagem (a gravação sem a linha nova): a prova reprova — a escolha não ficou no banco (${linha} linha), embora a tela tenha dito ${s3 && s3.ok ? '"Gravado"' : 'outra coisa'}`);
    L.ok(sha(MODELO) === shaModelo, 'o arquivo sabotado voltou igual (sha256)');
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(MODELO) !== shaModelo) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
