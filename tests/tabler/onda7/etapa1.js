/*
 * PROVA DA ONDA 7, ETAPA 1 — os acertos da demonstração da escola.
 *   A. o nome do curso sem a versão da grade, no ensino superior (a demonstração da faculdade, no banco que o ciclo
 *      deixa): o boletim na tela, no PDF e no portal; com uma segunda grade no curso, a versão aparece num campo
 *      próprio, com o rótulo "Grade" (e sai de novo quando a grade extra sai);
 *   B. a demonstração da escola montada PELA TELA, do zero (medir_demo.js): nenhum passo acima de 30 s (o limite medido
 *      na Onda 6, Etapa 2) e o total ao lado do da Onda 6;
 *   C. a demonstração da escola: toda turma com turno, sala e capacidade; todo componente com o professor fictício (o
 *      envio ao Moodle leva o professor da mesma ligação); numa turma, dois alunos com a média da etapa abaixo da mínima e
 *      a recuperação lançada com nota (um que supera a mínima e um que não supera); nenhum aluno só com zeros;
 *   D. as telas da escola: o "R" no boletim com a média e a recuperação; a conta de cada aluno na tela das etapas; o
 *      "Como ler" com a mínima da escola; o curso sem a grade na tela, no PDF e no portal;
 *   E. o percurso da palestra (o roteiro que cria o 7º ano C) preenche turno, sala e capacidade e põe o professor;
 *   sabotagem: a faixa do boletim volta a escrever "— grade" — a prova acusa; o arquivo volta igual (sha256).
 * APAGA o banco de prova na parte B (a bateria roda o ciclo antes desta prova). Ambiente: VCOSTAEDU_BASE,
 * VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { spawnSync, execFileSync } = require('child_process');
const L = require('../lib');

const ETAPA = 'onda7/etapa1';
const SAIDA = path.join(__dirname, '..', 'saida');
const PHP = process.env.VCOSTAEDU_PHP || 'C:/xampp/php/php.exe';
const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste$/.test(BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
const SENHA = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const LIMITE = 30;
const ONDA6_TOTAL = 160.5; // a montagem da escola medida na Onda 6 (saida/onda6-demo-escola-gravar.json)
const BOLETIM = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'notas', 'AdmsBoletimCurso.php');
const BOLETIM_PORTAL = path.join(L.RAIZ, 'portal', 'app', 'adms', 'Models', 'notas', 'AdmsBoletimCurso.php');

const sql = (q) => execFileSync('C:/xampp/mysql/bin/mysql.exe', ['-uroot', '--default-character-set=utf8mb4', '-N', '-B', BANCO, '-e', q], { encoding: 'utf8' }).trim();
const um = (q) => +(sql(q).split('\n')[0] || 0);
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const phpHash = (s) => execFileSync(PHP, ['-r', 'echo password_hash($argv[1], PASSWORD_DEFAULT);', s], { encoding: 'utf8' }).trim();
const pdfTexto = (buf) => {
  const arq = path.join(SAIDA, 'onda7-etapa1-boletim.pdf');
  fs.writeFileSync(arq, buf);
  const r = spawnSync('pdftotext', ['-enc', 'UTF-8', '-layout', arq, '-'], { encoding: 'utf8' });
  return r.status === 0 ? r.stdout : null;
};

async function entrar(navegador, app, usuario, senha) {
  const ctx = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' });
  const p = await L.pagina(ctx);
  await p.goto(L.BASE + app + '/login/acesso');
  await p.fill('input[name="usuario"]', usuario);
  await p.fill('input[name="senha"]', senha);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
  return { ctx, p };
}
const faixa = (p) => p.$$eval('.faixa-aluno', (l) => l.map((e) => e.innerText.replace(/\s+/g, ' ')).join(' | ')).catch(() => '');
const pdfDe = async (p, rota) => { const r = await p.request.get(L.BASE + rota); return pdfTexto(await r.body()); };

/** A parte A (o ensino superior), que a sabotagem roda de novo. Devolve o que achou. */
async function superior(navegador, cred) {
  const rm = '260103';
  const adm = await entrar(navegador, 'painel', 'administrador', cred.painel.administrador);
  await adm.p.goto(L.BASE + `painel/academicos/boletim/${rm}`);
  const tela = await faixa(adm.p);
  const seletor = await adm.p.$$eval('#filtroCurso option', (l) => l.map((o) => o.textContent).join(' | ')).catch(() => '');
  const pdf = await pdfDe(adm.p, `painel/academicos/imprimir-boletim/${rm}`);
  const alu = await entrar(navegador, 'portal', rm, cred.portal[rm]);
  await alu.p.goto(L.BASE + 'portal/home/boletim-aluno');
  const portal = await faixa(alu.p);
  /* uma segunda grade no curso do aluno: a versão volta, num campo próprio */
  const curso = um(`SELECT mc.curso_id FROM rm_matriculas_curso mc INNER JOIN col_usuarios u ON u.id = mc.usuario_id WHERE u.rm = '${rm}' LIMIT 1`);
  sql(`INSERT INTO rm_cursos_grades (curso_id, versao, descricao, situacao, usuario_cre) VALUES (${curso}, 99, 'grade da prova da Onda 7, Etapa 1', 1, 1)`);
  await adm.p.goto(L.BASE + `painel/academicos/boletim/${rm}`);
  const comDuas = await adm.p.$$eval('.faixa-aluno td', (l) => l.map((td) => td.innerText.replace(/\s+/g, ' ').trim())).catch(() => []);
  sql(`DELETE FROM rm_cursos_grades WHERE curso_id = ${curso} AND versao = 99`);
  await adm.ctx.close();
  await alu.ctx.close();
  return { tela, seletor, pdf, portal, comDuas };
}

(async () => {
  const { navegador } = await L.abrir();
  const shaBoletim = sha(BOLETIM);
  const shaBoletimPortal = sha(BOLETIM_PORTAL); // 1.102.1 (D-125): o do portal pega o tipo pelo helper do portal; os dois não são mais iguais
  try {
    console.log('\n== Onda 7, Etapa 1 — os acertos da demonstração da escola');
    const cred = JSON.parse(fs.readFileSync(path.join(SAIDA, 'credenciais.json'), 'utf8'));

    /* A. o ensino superior */
    const sup = await superior(navegador, cred);
    const semGrade = (t) => t !== null && t !== '' && !/grade\s*\d/i.test(t);
    L.ok(semGrade(sup.tela) && semGrade(sup.seletor) && semGrade(sup.portal),
      `ensino superior: o curso sem a versão da grade no boletim da tela ("${sup.tela.slice(0, 90)}…"), no seletor do curso e no portal`);
    L.ok(sup.pdf !== null && semGrade(sup.pdf) && /Gest[aã]o de Projetos/.test(sup.pdf),
      sup.pdf === null ? 'o PDF do boletim: o texto não pôde ser lido (falta o pdftotext)' : 'ensino superior: o PDF do boletim, com o curso e sem a versão da grade');
    const celGrade = sup.comDuas.find((c) => /^GRADE\b|^Grade\b/i.test(c));
    const celCurso = sup.comDuas.find((c) => /^CURSO\b/i.test(c)) || '';
    L.ok(!!celGrade && /\d/.test(celGrade) && !/grade\s*\d/i.test(celCurso),
      `com duas grades no curso: a versão num campo próprio, com rótulo ("${celGrade || 'nenhum'}"), e o nome do curso sem ela`);

    /* sabotagem: a faixa volta a escrever "— grade" */
    const orig = fs.readFileSync(BOLETIM, 'utf8');
    const origPortal = fs.readFileSync(BOLETIM_PORTAL, 'utf8');
    const ponto = "$html .= $cel(self::rotuloCurso(), $m['curso'])"; // 1.102.1 (D-125): o rótulo pelo vocabulário do tipo
    if (!orig.includes(ponto) || !origPortal.includes(ponto)) throw new Error('a sabotagem não achou a faixa do boletim');
    const sabotar = (t) => t.replace(ponto, "$html .= $cel(self::rotuloCurso(), $m['curso'] . ' — grade ' . (int) $m['versao'])");
    let sab;
    try {
      fs.writeFileSync(BOLETIM, sabotar(orig));
      fs.writeFileSync(BOLETIM_PORTAL, sabotar(origPortal));
      sab = await superior(navegador, cred);
    } finally { fs.writeFileSync(BOLETIM, orig); fs.writeFileSync(BOLETIM_PORTAL, origPortal); }
    L.ok(!semGrade(sab.tela) && !semGrade(sab.portal), 'sabotagem (a faixa do boletim com "— grade"): a prova acusa, na tela e no portal');
    L.ok(sha(BOLETIM) === shaBoletim && sha(BOLETIM_PORTAL) === shaBoletimPortal, 'o arquivo sabotado voltou igual (sha256), no painel e no portal');

    /* B. a demonstração da escola, do zero, pela tela */
    const r = spawnSync(process.execPath, [path.join(__dirname, '..', 'onda6', 'medir_demo.js'), 'escola', 'onda7-etapa1'], { encoding: 'utf8', maxBuffer: 1 << 24 });
    let m = null;
    try { m = JSON.parse(fs.readFileSync(path.join(SAIDA, 'onda6-demo-escola-onda7-etapa1.json'), 'utf8')); } catch (e) { /* */ }
    const tempos = m ? Object.entries(m.tempos).sort((a, b) => b[1] - a[1]) : [];
    L.ok(r.status === 0 && tempos.length >= 30 && tempos[0][1] <= LIMITE,
      m ? `a demonstração da escola pela tela: ${tempos.length} passos em ${m.total} s (na Onda 6: ${ONDA6_TOTAL} s); o mais caro, ${tempos[0][0]}, em ${tempos[0][1]} s (o limite: ${LIMITE} s)` : 'a montagem falhou: ' + (r.stderr || r.stdout || '').slice(-300));

    /* C. os dados da demonstração */
    const turmasIncompletas = sql("SELECT IFNULL(GROUP_CONCAT(CONCAT(id, ':', nome)), '') FROM esc_turmas WHERE turno_id IS NULL OR IFNULL(sala, '') = '' OR IFNULL(capacidade, 0) = 0");
    L.ok(um('SELECT COUNT(*) FROM esc_turmas') >= 5 && turmasIncompletas === '', `toda turma com turno, sala e capacidade (${um('SELECT COUNT(*) FROM esc_turmas')} turmas${turmasIncompletas ? '; faltam: ' + turmasIncompletas : ''})`);
    const semProf = um('SELECT COUNT(*) FROM esc_turma_ofertas x WHERE NOT EXISTS (SELECT 1 FROM rm_professores_mat_liga l WHERE l.rm_prof_mat_id = x.oferta_id AND l.situacao = 1)');
    const ofertas = um('SELECT COUNT(*) FROM esc_turma_ofertas');
    const fictícios = sql("SELECT GROUP_CONCAT(DISTINCT u.usuario) FROM rm_professores_mat_liga l INNER JOIN esc_turma_ofertas x ON x.oferta_id = l.rm_prof_mat_id INNER JOIN adms_usuarios u ON u.id = l.usuario_id WHERE l.situacao = 1");
    L.ok(ofertas > 0 && semProf === 0 && /\.demo/.test(fictícios), `todo componente das turmas com o professor fictício (${ofertas} componentes; ${fictícios}); o envio ao Moodle lê a mesma ligação`);
    const minima = sql("SELECT IFNULL((SELECT valor FROM esc_configuracao WHERE chave = 'media.minima'), '6')");
    const rec = sql(`SELECT t.id, fn.rm, fn.media, fn.recuperacao, fn.nota FROM esc_fechamento_notas fn INNER JOIN esc_fechamentos f ON f.id = fn.fechamento_id
      INNER JOIN esc_turma_ofertas x ON x.oferta_id = f.oferta_id INNER JOIN esc_turmas t ON t.id = x.turma_id
      WHERE fn.recuperacao IS NOT NULL AND fn.media < ${+minima}`).split('\n').filter(Boolean).map((l) => l.split('\t'));
    const porTurma = {};
    for (const [t, rm, media, recup, nota] of rec) { (porTurma[t] = porTurma[t] || []).push({ rm, media: +media, recup: +recup, nota: +nota }); }
    const turmaBoa = Object.entries(porTurma).find(([, l]) => l.some((x) => x.nota >= +minima) && l.some((x) => x.nota < +minima));
    const supera = turmaBoa ? turmaBoa[1].find((x) => x.nota >= +minima) : null;
    const naoSupera = turmaBoa ? turmaBoa[1].find((x) => x.nota < +minima) : null;
    L.ok(!!turmaBoa && supera.rm !== naoSupera.rm,
      turmaBoa ? `numa turma, a recuperação lançada com nota: ${supera.rm} (média ${supera.media} → recuperação ${supera.recup} → ${supera.nota}, supera ${minima}) e ${naoSupera.rm} (média ${naoSupera.media} → recuperação ${naoSupera.recup} → ${naoSupera.nota}, não supera)` : 'nenhuma turma com os dois casos da recuperação');
    const soZeros = sql(`SELECT IFNULL(GROUP_CONCAT(rm), '') FROM (SELECT fn.rm, MAX(fn.nota) AS mx FROM esc_fechamento_notas fn GROUP BY fn.rm) q WHERE q.mx = 0`);
    L.ok(soZeros === '', `nenhum aluno só com zeros nas etapas fechadas${soZeros ? ': ' + soZeros : ''}`);

    /* D. as telas da escola */
    const credEsc = await entrar(navegador, 'painel', 'administrador', SENHA);
    const rmS = supera ? supera.rm : '265002';
    const rmN = naoSupera ? naoSupera.rm : '265003';
    await credEsc.p.goto(L.BASE + `painel/academicos/boletim/${rmS}`);
    const rTitulo = await credEsc.p.$eval('.tabela-boletim-escola .badge[title^="Com recuperação"]', (b) => b.getAttribute('title') + ' | ' + b.textContent).catch(() => '');
    const faixaEsc = await faixa(credEsc.p);
    const comoLer = await credEsc.p.$eval('#comoLerEscola', (e) => e.innerText).catch(() => '');
    L.ok(/R$/.test(rTitulo) && /média \d.*recuperação \d/.test(rTitulo), `o "R" no boletim do aluno que superou, com a média e a recuperação (${rTitulo})`);
    L.ok(/a partir de 6,0/.test(comoLer) && !/7,0/.test(comoLer), `o "Como ler" do boletim da escola com a mínima da escola ("${comoLer.slice(0, 70)}…")`);
    await L.semErros(credEsc.p, 'o boletim da escola');
    const pdfEsc = await pdfDe(credEsc.p, `painel/academicos/imprimir-boletim/${rmS}`);
    L.ok(semGrade(faixaEsc) && pdfEsc !== null && semGrade(pdfEsc) && /Fundamental/.test(pdfEsc), 'a escola: o curso sem a versão da grade na tela e no PDF do boletim');
    const oferta = um(`SELECT f.oferta_id FROM esc_fechamento_notas fn INNER JOIN esc_fechamentos f ON f.id = fn.fechamento_id WHERE fn.rm = '${rmS}' AND fn.recuperacao IS NOT NULL LIMIT 1`);
    await credEsc.p.goto(L.BASE + `painel/escola-notas/etapas/${oferta}`);
    const contaS = await credEsc.p.$eval(`#memoria-${rmS}`, (e) => e.textContent.replace(/\s+/g, ' ')).catch(() => '');
    const contaN = await credEsc.p.$eval(`#memoria-${rmN}`, (e) => e.textContent.replace(/\s+/g, ' ')).catch(() => '');
    L.ok(/recuperação \d[\d,]* → nota da etapa/.test(contaS) && /recuperação \d[\d,]* → nota da etapa/.test(contaN) && /Média anual = \(/.test(contaS),
      `a conta de cada aluno na tela das etapas: "${(contaS.match(/[^:]*bimestre: média[^)]*recuperação[^)]*\)/) || [contaS.slice(0, 120)])[0].trim()}"`);
    await L.semErros(credEsc.p, 'a tela das etapas com a conta de cada aluno');
    await L.print(credEsc.p, ETAPA, 'etapas-conta');
    await credEsc.ctx.close();
    /* o portal do aluno que superou: uma senha da prova */
    const senhaAluno = 'senha-da-prova-o7e1';
    sql(`UPDATE col_usuarios SET senha = '${phpHash(senhaAluno)}' WHERE rm = '${rmS}'`);
    const alu = await entrar(navegador, 'portal', rmS, senhaAluno);
    await alu.p.goto(L.BASE + 'portal/home/boletim-aluno');
    const faixaPortal = await faixa(alu.p);
    L.ok(semGrade(faixaPortal) && /Fundamental/.test(faixaPortal), 'a escola: o curso sem a versão da grade no portal do aluno');
    await L.semErros(alu.p, 'o boletim no portal da escola');
    await alu.ctx.close();

    /* E. o percurso da palestra */
    const pal = fs.readFileSync(path.join(L.RAIZ, 'tests', 'videos', 'palestra.js'), 'utf8');
    L.ok(/escolher\('#turno_id'/.test(pal) && /digitar\('#sala'/.test(pal) && /digitar\('#capacidade'/.test(pal) && /adiciona-professor/.test(pal) && /semProf === 0/.test(pal),
      'o percurso da palestra (o 7º ano C): turno, sala e capacidade preenchidos e o professor em cada componente, conferido antes do envio ao Moodle');
  } catch (e) {
    L.ok(false, 'a prova parou: ' + (e.stack || e.message).slice(0, 400));
  } finally {
    if (sha(BOLETIM) !== shaBoletim || sha(BOLETIM_PORTAL) !== shaBoletimPortal) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
