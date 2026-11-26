/*
 * PROVA DO HISTÓRICO ESCOLAR, PARTE B — a matriz curricular por ano (D-111; a decisão 12). APAGA o banco de prova.
 *   1. os segmentos marcados (a Parte A) e a matriz nova do Fundamental de 2026: as aulas propostas pela carga da grade;
 *      a correção de uma linha leva a série a 25 aulas semanais → 1.000 aulas anuais → 833,3 horas (sempre calculadas);
 *      o registro grava só a linha que mudou; a carga em horas da grade (a da frequência) não muda;
 *   2. copiar 2026 para 2025: as mesmas aulas, a origem "cópia"; a de 2025 muda sem mexer na de 2026;
 *   3. travar: a correção sem motivo é recusada (nada muda); com o motivo, grava, e o registro leva o motivo;
 *   4. a folha da matriz em PDF (uma página, paisagem no Fundamental) e a do Médio (retrato), com a área e os itinerários;
 *      o item de itinerário formativo sem o itinerário cadastrado é recusado;
 *   5. a direção abre e não grava (o POST direto recusado, nada muda);
 *   sabotagem: as horas calculadas com a aula de 60 minutos — a prova reprova; o arquivo volta igual.
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const L = require('../lib');
const X = require('../onda8/_escola');

const ETAPA = 'historico/parteB';
const MATRIZ = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'historicoEscola', 'AdmsHistMatriz.php');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const { sql, um } = X;
const postar = (p, url, campos) => p.evaluate(async ([u, c]) => {
  const x = await fetch(u, { method: 'POST', credentials: 'same-origin', body: new URLSearchParams(c) });
  return { st: x.status, t: await x.text() };
}, [L.BASE + 'painel/' + url, campos]);
const texto = async (p, sel) => ((await p.textContent(sel).catch(() => '')) || '').replace(/\s+/g, ' ').trim();

async function criar(p, grade, ano, copiar = '0') {
  await p.goto(L.BASE + 'painel/historico-escola-matriz/listar');
  await p.selectOption('#nGrade', String(grade));
  await p.fill('#nAno', String(ano));
  await p.selectOption('#nCopiar', String(copiar));
  await Promise.all([p.waitForNavigation(), p.click('#criarMatriz')]);
  return +p.url().split('/').pop();
}
async function salvar(p) {
  await Promise.all([p.waitForNavigation(), p.click('#salvarMatriz')]);
  return texto(p, '.alert');
}
const linha = (matriz, serie, materia) => um(`SELECT l.id FROM uni_rh_cursos_disciplinas_grade l JOIN esc_hist_matriz m ON m.grade_id = l.grade_id JOIN rm_materias t ON t.id = l.materia_id
  WHERE m.id = ${matriz} AND l.modulo_id = ${serie} AND t.nome LIKE '${materia}' LIMIT 1`);

(async () => {
  const { navegador } = await L.abrir();
  const shaM = sha(MATRIZ);
  try {
    L.ok(X.montar('historico-parteB').status === 0, 'a demonstração do colégio montada pela tela');
    require('child_process').execFileSync('php', [path.join(__dirname, 'prova.php'), 'sem-historico-demo']); // D-115: o cenário da prova parte do histórico vazio
    const c = X.cred();
    const sec = await X.entrar(navegador, 'secretaria.demo', c.painel['secretaria.demo']);
    const p = sec.p;
    await p.goto(L.BASE + 'painel/historico-escola/cadastros?aba=series');
    await p.selectOption('#tabelaSeries tr[data-segmento="3"] select', '');
    await Promise.all([p.waitForNavigation(), p.click('#salvarSeries')]);
    const cargaGrade = sql('SELECT SUM(cargahoraria) FROM uni_rh_cursos_disciplinas_grade');

    /* 1. a matriz nova do Fundamental de 2026 */
    const m26 = await criar(p, 1, 2026);
    const prop6 = await texto(p, '#total-1');
    const mat = linha(m26, 1, 'Matem%tica');
    await p.fill(`tr[data-linha="${mat}"] .vc-aulas`, '6');
    const aoVivo = await texto(p, '#total-1');
    const r1 = await salvar(p);
    await L.print(p, ETAPA, 'matriz-fundamental-2026');
    const t6 = await texto(p, '#total-1');
    const regs = sql(`SELECT texto FROM esc_hist_alteracao WHERE tabela = 'esc_hist_matriz_item' AND registro_id = ${m26}`).split('\n').filter(Boolean);
    L.ok(/24 aulas semanais · 960 aulas anuais · 800 horas/.test(prop6) && /25 aulas semanais · 1\.000 aulas anuais · 833,3 horas/.test(aoVivo)
      && /gravada/.test(r1) && /25 aulas semanais · 1\.000 aulas anuais · 833,3 horas/.test(t6),
      `as aulas propostas pela carga da grade (${prop6}); a correção leva o 6º ano a 25 semanais, 1.000 anuais e 833,3 horas, ao vivo e gravado (${t6})`);
    L.ok(regs.length === 1 && /6º ano · Matem.tica — Aulas semanais: "5" → "6"/.test(regs[0]) && sql('SELECT SUM(cargahoraria) FROM uni_rh_cursos_disciplinas_grade') === cargaGrade
      && um(`SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'esc_hist_matriz_item' AND column_name LIKE '%anua%'`) === 0,
      `só a linha que mudou foi para o registro ("${(regs[0] || '').slice(0, 110)}"); a carga em horas da grade não mudou; nenhuma coluna de total gravada`);

    /* 2. copiar para 2025 */
    const m25 = await criar(p, 1, 2025, m26);
    const t25 = await texto(p, '#total-1');
    const iguais = um(`SELECT COUNT(*) FROM esc_hist_matriz_item a JOIN esc_hist_matriz_item b ON b.linha_id = a.linha_id AND b.matriz_id = ${m25} WHERE a.matriz_id = ${m26} AND a.aulas_semanais = b.aulas_semanais AND a.grupo = b.grupo`);
    const total = um(`SELECT COUNT(*) FROM esc_hist_matriz_item WHERE matriz_id = ${m26}`);
    await p.fill(`tr[data-linha="${linha(m25, 2, 'Arte')}"] .vc-aulas`, '2');
    await salvar(p);
    L.ok(/25 aulas semanais/.test(t25) && iguais === total && total === 36 && sql(`SELECT origem FROM esc_hist_matriz WHERE id = ${m25}`) === 'copia'
      && um(`SELECT aulas_semanais FROM esc_hist_matriz_item WHERE matriz_id = ${m26} AND linha_id = ${linha(m26, 2, 'Arte')}`) === 1
      && um(`SELECT aulas_semanais FROM esc_hist_matriz_item WHERE matriz_id = ${m25} AND linha_id = ${linha(m25, 2, 'Arte')}`) === 2,
      `copiar 2026 para 2025: as ${iguais} linhas iguais, a origem "cópia"; mudar a de 2025 não mexe na de 2026`);
    const repetida = await criar(p, 1, 2025).catch(() => 0);
    L.ok(/Já existe a matriz/.test(await texto(p, '#matrizRecusada')), 'a mesma grade no mesmo ano é recusada');

    /* 3. travar */
    await p.goto(L.BASE + `painel/historico-escola-matriz/editar/${m26}`);
    await Promise.all([p.waitForNavigation(), p.click('#travarMatriz')]);
    const geo = linha(m26, 1, 'Geografia');
    await p.fill(`tr[data-linha="${geo}"] .vc-aulas`, '3');
    const semMotivo = await salvar(p);
    const naoMudou = um(`SELECT aulas_semanais FROM esc_hist_matriz_item WHERE matriz_id = ${m26} AND linha_id = ${geo}`) === 2;
    await p.fill(`tr[data-linha="${geo}"] .vc-aulas`, '3');
    await p.fill('#mMotivo', 'Correção da matriz de prova');
    const comMotivo = await salvar(p);
    const reg = sql(`SELECT texto FROM esc_hist_alteracao WHERE tabela = 'esc_hist_matriz_item' AND registro_id = ${m26} ORDER BY id DESC LIMIT 1`);
    L.ok(/travada: informe o motivo/.test(semMotivo) && naoMudou && /gravada/.test(comMotivo) && /Geografia — Aulas semanais: "2" → "3" \(motivo: Correção da matriz de prova\)/.test(reg)
      && sql(`SELECT situacao FROM esc_hist_matriz WHERE id = ${m26}`) === 'travada',
      `travada: sem motivo recusada e nada muda; com o motivo grava, e o registro o leva ("${reg.slice(-90)}")`);
    await L.print(p, ETAPA, 'matriz-travada');

    /* 4. as folhas; o Médio com área e itinerários */
    const folha = await p.request.get(L.BASE + `painel/historico-escola-matriz/folha/${m26}`);
    const pdf = (await folha.body()).toString('latin1');
    fs.mkdirSync(path.join(L.PROVAS, ETAPA), { recursive: true });
    fs.writeFileSync(path.join(L.PROVAS, ETAPA, 'folha-fundamental.pdf'), await folha.body());
    const paginas = (pdf.match(/\/Type\s*\/Page(?![a-zA-Z])/g) || []).length;
    L.ok(folha.status() === 200 && /application\/pdf/.test(folha.headers()['content-type']) && paginas === 1 && /\/MediaBox \[0\.000 0\.000 841\.89/.test(pdf),
      `a folha do Fundamental em PDF: uma página, paisagem (${paginas} página)`);
    const mEm = await criar(p, 2, 2026);
    await p.fill('#aNome', 'Ciências da Natureza e suas Tecnologias');
    await Promise.all([p.waitForNavigation(), p.click('#salvarArea')]);
    const area = um("SELECT id FROM esc_hist_area LIMIT 1");
    const proj = linha(mEm, 6, 'Itiner%rio: Projeto%');
    await p.selectOption(`tr[data-linha="${proj}"] .vc-grupo`, 'IT_FORMATIVO');
    await p.fill(`tr[data-linha="${proj}"] .vc-it`, '1');
    const semIt = await salvar(p);
    await p.fill('#itinerarios input[name="it_numero[0]"]', '1');
    await p.fill('#itinerarios input[name="it_nome[0]"]', 'Itinerário de prova 1');
    await p.selectOption(`tr[data-linha="${proj}"] .vc-grupo`, 'IT_FORMATIVO');
    await p.fill(`tr[data-linha="${proj}"] .vc-it`, '1');
    await p.selectOption(`tr[data-linha="${linha(mEm, 6, 'Biologia')}"] select[name^="area"]`, String(area));
    const comIt = await salvar(p);
    const folhaEm = await p.request.get(L.BASE + `painel/historico-escola-matriz/folha/${mEm}`);
    const pdfEm = (await folhaEm.body()).toString('latin1');
    L.ok(/itinerário formativo cadastrado/.test(semIt) && /gravada/.test(comIt) && sql(`SELECT CONCAT(grupo, itinerario) FROM esc_hist_matriz_item WHERE matriz_id = ${mEm} AND linha_id = ${proj}`) === 'IT_FORMATIVO1'
      && folhaEm.status() === 200 && /\/MediaBox \[0\.000 0\.000 595\.28/.test(pdfEm),
      'o Médio: o itinerário formativo sem o itinerário cadastrado é recusado; com ele, grava; a área; a folha em retrato');

    /* 5. a direção */
    await sec.ctx.close();
    const dir = await X.entrar(navegador, 'direcao.demo', c.painel['direcao.demo']);
    await dir.p.goto(L.BASE + `painel/historico-escola-matriz/editar/${m25}`);
    const semBotao = !(await dir.p.$('#salvarMatriz')) && !(await dir.p.$('#travarMatriz')) && !!(await dir.p.$('#formMatriz'));
    const antes = sql(`SELECT GROUP_CONCAT(aulas_semanais ORDER BY id) FROM esc_hist_matriz_item WHERE matriz_id = ${m25}`);
    const nM = um('SELECT COUNT(*) FROM esc_hist_matriz');
    const pr = await postar(dir.p, `historico-escola-matriz/salvar/${m25}`, { semanas: '40', minutos: '50', [`aulas[${linha(m25, 1, 'Arte')}]`]: '9' });
    const pt = await postar(dir.p, `historico-escola-matriz/travar/${m25}`, {});
    const pc = await postar(dir.p, 'historico-escola-matriz/criar', { grade_id: '1', ano: '2024', copiar_de: '0' });
    L.ok(semBotao && sql(`SELECT GROUP_CONCAT(aulas_semanais ORDER BY id) FROM esc_hist_matriz_item WHERE matriz_id = ${m25}`) === antes && um('SELECT COUNT(*) FROM esc_hist_matriz') === nM
      && sql(`SELECT situacao FROM esc_hist_matriz WHERE id = ${m25}`) === 'rascunho',
      `a direção abre a matriz sem os botões; os POSTs diretos (${pr.st}, ${pt.st}, ${pc.st}) não mudam nada`);
    await dir.ctx.close();

    /* sabotagem: a hora de 60 minutos */
    const orig = fs.readFileSync(MATRIZ, 'utf8');
    const trecho = '            $r[$mid] = [\'codnome\' => $s[\'codnome\'], \'semanais\' => $sem, \'anuais\' => $anuais, \'horas\' => self::horasTexto($anuais, (int) $m[\'minutos\'])];';
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let visto = '';
    try {
      fs.writeFileSync(MATRIZ, orig.replace(trecho, trecho.replace("(int) $m['minutos']", '60')));
      const s2 = await X.entrar(navegador, 'secretaria.demo', c.painel['secretaria.demo']);
      await s2.p.goto(L.BASE + `painel/historico-escola-matriz/editar/${m26}`);
      visto = await texto(s2.p, '#total-1');
      await s2.ctx.close();
    } finally {
      fs.writeFileSync(MATRIZ, orig);
    }
    L.ok(!/833,3 horas/.test(visto), `sabotagem (a aula de 60 minutos): a conferência das horas pega — a tela mostrou "${visto}"`);
    L.ok(sha(MATRIZ) === shaM, 'o arquivo sabotado voltou igual (sha256)');
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(MATRIZ) !== shaM) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
