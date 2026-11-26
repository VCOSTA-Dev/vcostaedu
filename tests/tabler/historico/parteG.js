/*
 * PROVA DO HISTÓRICO ESCOLAR, PARTE G — o modelo do Médio (D-116). APAGA o banco de prova.
 *   1. pela tela da secretaria: os textos do Médio, a matriz do Médio de 2026 com duas áreas de conhecimento e dois
 *      itinerários formativos (as duas disciplinas de itinerário da grade), e o itinerário 2 de um aluno da 1ª série A
 *      gravado na tela da turma (a matrícula do ano); a tela da emissão não diz mais "o modelo do Médio não está pronto";
 *   2. o documento do Médio desse aluno: a frente por área de conhecimento e pelo itinerário que ele cursa (só o 2: a
 *      disciplina do itinerário 1 não sai), com o nome do itinerário da matriz; o verso com o curso anterior, a
 *      transferência por área e itinerário, 60 e 70; o PDF de 2 páginas; emitido (ESC_EM), conferido pelo sha256 e na
 *      conferência pública ("Histórico escolar — Ensino Médio");
 *   3. o pior caso — 5 colunas (duas retenções) e 16 linhas de itinerário — em 2 páginas e dentro da largura (medidos
 *      no PDF: as páginas e o maior x do texto);
 *   4. sabotagem: o documento que não filtra o itinerário do aluno — a disciplina do itinerário 1 aparece (a prova
 *      reprova); o arquivo volta igual.
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const L = require('../lib');
const X = require('../onda8/_escola');
const K = require('./_cenario');

const ETAPA = 'historico/parteG';
const DOC = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'historicoEscola', 'AdmsHistDocumento.php');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const shaB = (b) => crypto.createHash('sha256').update(b).digest('hex');
const { sql, um } = X;
const texto = async (p, sel) => ((await p.textContent(sel).catch(() => '')) || '').replace(/\s+/g, ' ').trim();
const paginas = (b) => (b.toString('latin1').match(/\/Type\s*\/Page(?![a-zA-Z])/g) || []).length;
const IT1 = 'Itinerário: Projeto de Ciência e Tecnologia (exemplo)';
const IT2 = 'Itinerário: Laboratório de Investigação (exemplo)';
const LIMITE = 559.0;

async function htmlDoc(p, aluno) {
  const r = await p.request.get(L.BASE + `painel/historico-escola-emissao/previa-pdf/${aluno}?nivel=EM&formato=html`);
  const h = r.headers();
  return { html: await r.text(), degrau: Number(h['x-historico-degrau']), paginas: Number(h['x-historico-paginas']), largura: Number(h['x-historico-largura']) };
}

(async () => {
  const { navegador } = await L.abrir();
  const shaDoc = sha(DOC);
  try {
    L.ok(X.montar('historico-parteG').status === 0, 'a demonstração do colégio montada pela tela');
    K.prova('sem-historico-demo');
    const c = X.cred();
    const sec = await X.entrar(navegador, 'secretaria.demo', c.painel['secretaria.demo']);
    const p = sec.p;
    const origem = await K.cadastros(p);

    /* 1. o Médio pela tela: os textos, a matriz com as áreas e os itinerários, o itinerário do aluno na turma */
    await p.goto(L.BASE + 'painel/historico-escola/cadastros?aba=textos&nivel=EM&nova=1');
    const textos = { vigente_desde: '2020-01-01', orgao_linha1: 'LINHA UM DO ÓRGÃO (PROVA)', ato_escola: 'Ato de prova nº 0', ato_curso: 'Ato do Médio de prova', turno: 'Diurno',
      escala: 'Escala de prova: de zero a dez; média mínima seis.', secretaria_nome: 'Secretária de Prova', diretora_nome: 'Diretora de Prova', rotulo_registro: 'R.A. Nº',
      rotulo_publicacao: 'Nº da publicação (prova)' };
    for (const [k, v] of Object.entries(textos)) { await p.fill(`#formTexto [name="${k}"]`, v); }
    await Promise.all([p.waitForNavigation(), p.click('#salvarTexto')]);
    const gradeEm = um("SELECT g.id FROM rm_cursos_grades g JOIN esc_hist_segmento s ON s.curso_id = g.curso_id AND s.nivel = 'EM' ORDER BY g.id DESC LIMIT 1");
    await p.goto(L.BASE + 'painel/historico-escola-matriz/listar');
    await p.selectOption('#nGrade', String(gradeEm)); await p.fill('#nAno', '2026');
    await Promise.all([p.waitForNavigation(), p.click('#criarMatriz')]);
    const mz = um(`SELECT id FROM esc_hist_matriz WHERE grade_id = ${gradeEm} AND ano = 2026`);
    for (const [nome, ordem] of [['Linguagens e suas Tecnologias', '1'], ['Ciências da Natureza e suas Tecnologias', '2']]) {
      await p.goto(L.BASE + `painel/historico-escola-matriz/editar/${mz}`);
      await p.fill('#aNome', nome); await p.fill('#aOrdem', ordem);
      await Promise.all([p.waitForNavigation(), p.click('#salvarArea')]);
    }
    const area = (n) => um(`SELECT id FROM esc_hist_area WHERE nome LIKE '${n.replace(/[^ -~]/g, '_')}'`); // o literal com acento quebra no cliente: LIKE em ASCII
    const linhas = JSON.parse(sql(`SET SESSION group_concat_max_len = 200000; SELECT IFNULL(CONCAT('[', GROUP_CONCAT(JSON_OBJECT('l', i.linha_id, 'm', mt.nome)), ']'), '[]') FROM esc_hist_matriz_item i JOIN uni_rh_cursos_disciplinas_grade l ON l.id = i.linha_id
      JOIN rm_materias mt ON mt.id = l.materia_id WHERE i.matriz_id = ${mz} AND i.aulas_semanais > 0`) || '[]');
    await p.goto(L.BASE + `painel/historico-escola-matriz/editar/${mz}`);
    for (const { l, m } of linhas) {
      if (m === IT1 || m === IT2) {
        await p.selectOption(`select[name="grupo[${l}]"]`, 'IT_FORMATIVO');
        await p.fill(`input[name="itinerario[${l}]"]`, m === IT1 ? '1' : '2');
      } else if (/Português|Inglês|Arte|Educação Física|Língua/.test(m)) {
        await p.selectOption(`select[name="area[${l}]"]`, String(area('Linguagens e suas Tecnologias')));
      } else if (/Biologia|Física|Química|Ciências/.test(m)) {
        await p.selectOption(`select[name="area[${l}]"]`, String(area('Ciências da Natureza e suas Tecnologias')));
      }
    }
    await p.fill('input[name="it_numero[0]"]', '1'); await p.fill('input[name="it_nome[0]"]', 'Ciência e Tecnologia (prova)');
    await p.fill('input[name="it_numero[1]"]', '2'); await p.fill('input[name="it_nome[1]"]', 'Investigação (prova)');
    await Promise.all([p.waitForNavigation(), p.click('#salvarMatriz')]);
    const itens = sql(`SELECT CONCAT((SELECT COUNT(*) FROM esc_hist_itinerario WHERE matriz_id = ${mz} AND numero > 0), '|', (SELECT COUNT(*) FROM esc_hist_matriz_item WHERE matriz_id = ${mz} AND grupo = 'IT_FORMATIVO'), '|',
      (SELECT COUNT(*) FROM esc_hist_matriz_item WHERE matriz_id = ${mz} AND area_id > 0))`);
    const turma = um("SELECT t.id FROM esc_turmas t JOIN esc_hist_segmento s ON s.curso_id = t.curso_id AND s.nivel = 'EM' WHERE t.ano = 2026 ORDER BY t.id LIMIT 1");
    const [ta, aluno] = sql(`SELECT CONCAT(x.id, '|', x.usuario_id) FROM esc_turma_alunos x JOIN col_usuarios u ON u.id = x.usuario_id WHERE x.turma_id = ${turma} AND x.saida IS NULL ORDER BY u.rm LIMIT 1`).split('|');
    await p.goto(L.BASE + `painel/turmas/ver/${turma}`);
    const cartao = !!(await p.$('#itinerariosTurma'));
    await p.selectOption(`select[name="itinerario[${ta}]"]`, '2');
    await Promise.all([p.waitForNavigation(), p.click('#salvarItinerarios')]);
    await L.print(p, ETAPA, 'turma-itinerario');
    const itGravado = um(`SELECT itinerario FROM esc_hist_itinerario_aluno WHERE turma_aluno_id = ${ta}`);
    L.ok(itens.split('|')[0] === '2' && Number(itens.split('|')[1]) >= 2 && Number(itens.split('|')[2]) >= 4 && cartao && itGravado === 2,
      `pela tela: a matriz do Médio com as áreas e os itinerários (${itens}); o itinerário 2 do aluno gravado na tela da turma`);

    /* 2. o documento do Médio do aluno */
    await p.goto(L.BASE + `painel/historico-escola-vida/aluno/${aluno}?nivel=EM`);
    if (await p.$('.vc-confirmar')) { await Promise.all([p.waitForNavigation(), p.click('.vc-confirmar')]); }
    await p.goto(L.BASE + `painel/historico-escola-emissao/previa/${aluno}?nivel=EM`);
    const semModelo = !!(await p.$('#semModeloMedio'));
    const bloq = await texto(p, '#bloqueios');
    await L.print(p, ETAPA, 'previa-medio');
    const d1 = await htmlDoc(p, aluno);
    fs.mkdirSync(path.join(L.PROVAS, ETAPA), { recursive: true });
    fs.writeFileSync(path.join(L.PROVAS, ETAPA, 'medio.html'), d1.html);
    const temIt2 = d1.html.includes(IT2.toUpperCase()) && d1.html.includes('INVESTIGAÇÃO (PROVA)') && d1.html.includes('ITINERÁRIO FORMATIVO 2');
    const semIt1 = !d1.html.includes(IT1.toUpperCase());
    const areas = d1.html.includes('LINGUAGENS E SUAS TECNOLOGIAS') && d1.html.includes('CIÊNCIAS DA NATUREZA E SUAS TECNOLOGIAS');
    L.ok(!semModelo && !bloq && /HISTÓRICO ESCOLAR - ENSINO MÉDIO/.test(d1.html) && areas && temIt2 && semIt1 && /CURSO ANTERIOR/.test(d1.html) && d1.paginas === 2 && d1.largura <= LIMITE,
      `o documento do Médio: por área (${areas}), só o itinerário que o aluno cursa (o 2: ${temIt2}; o 1 fora: ${semIt1}), o verso com o curso anterior; ${d1.paginas} páginas, degrau ${d1.degrau}, até ${d1.largura} pt`);
    if (await p.$('#confirmoAvisos')) { await p.check('#confirmoAvisos'); }
    await Promise.all([p.waitForNavigation(), p.click('#emitirHistorico')]);
    const emitiu = await texto(p, '#historicoEmitido');
    const [idM, codM, tipoM, hashM] = sql(`SELECT CONCAT(id, '|', codigo, '|', tipo, '|', hash_pdf) FROM rm_historico_emissoes WHERE aluno_id = ${aluno} ORDER BY id DESC LIMIT 1`).split('|');
    const bM = await (await p.request.get(L.BASE + `painel/historico-escola-emissao/pdf/${idM}`)).body();
    fs.writeFileSync(path.join(L.PROVAS, ETAPA, 'medio.pdf'), bM);
    const pub = await navegador.newContext();
    const pp = await pub.newPage();
    await pp.goto(L.BASE + 'verificar/' + codM);
    const ver = { estado: await pp.getAttribute('#resultadoVerificacao', 'data-estado'), tipo: await texto(pp, '#tipoDocumento') };
    await L.print(pp, ETAPA, 'conferencia-publica');
    await pub.close();
    L.ok(/emitido/.test(emitiu) && tipoM === 'ESC_EM' && shaB(bM) === hashM && paginas(bM) === 2 && ver.estado === 'valido' && ver.tipo === 'Histórico escolar — Ensino Médio',
      `emitido: ${tipoM}, o PDF de ${paginas(bM)} páginas confere com o sha256; a conferência pública: "${ver.tipo}" (${ver.estado})`);

    /* 3. o pior caso: 5 colunas e 16 linhas de itinerário */
    const P = JSON.parse(K.prova('aluno', '996101', 'PROVA HIST MEDIO PIOR CASO')).id;
    const FGB = ['7,5', '8,0', '6,5', '9,0', '5,5', '7,0', '8,5', '10,0', '7,5', '8,0', '6,5', '9,0', '7,0', '8,5', '6,0', '9,5'];
    for (const [s, ano, res] of [[1, 2021, 'retido'], [1, 2022, 'aprovado'], [2, 2023, 'retido'], [2, 2024, 'aprovado'], [3, 2025, 'aprovado']]) {
      K.prova('serie', String(P), 'EM', String(s), String(ano), String(origem), res, ...FGB);
      const vs = um(`SELECT MAX(id) FROM esc_hist_vida_serie WHERE aluno_id = ${P}`);
      K.prova('it-pior', String(vs), '16');
    }
    const d2 = await htmlDoc(p, P);
    fs.writeFileSync(path.join(L.PROVAS, ETAPA, 'pior.html'), d2.html);
    const rP = await p.request.get(L.BASE + `painel/historico-escola-emissao/previa-pdf/${P}?nivel=EM`);
    const bP = await rP.body();
    fs.writeFileSync(path.join(L.PROVAS, ETAPA, 'pior.pdf'), bP);
    const nCols = (d2.html.match(/<td class="c n">20(21|22|23|24|25)<\/td>/g) || []).length;
    const it16 = d2.html.includes('PROVA ITINERÁRIO 16') && !d2.html.includes('PROVA ITINERÁRIO 17');
    L.ok(d2.paginas === 2 && paginas(bP) === 2 && d2.largura > 0 && d2.largura <= LIMITE && nCols >= 10 && it16,
      `o pior caso (5 colunas, 16 linhas de itinerário): ${d2.paginas} páginas, degrau ${d2.degrau}, o maior x do texto ${d2.largura} pt (o limite: ${LIMITE})`);

    /* 4. sabotagem: o documento que não filtra o itinerário do aluno */
    const orig = fs.readFileSync(DOC, 'utf8');
    const trecho = 'if ($itCol > 0 && (int) $l[\'itinerario\'] !== $itCol) {';
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let sab = null;
    try {
      fs.writeFileSync(DOC, orig.replace(trecho, 'if (false) {'));
      sab = await htmlDoc(p, aluno);
    } finally {
      fs.writeFileSync(DOC, orig);
    }
    L.ok(sab && sab.html.includes(IT1.toUpperCase()), 'sabotagem (o documento sem o filtro do itinerário): a disciplina do itinerário 1 aparece — a prova reprova');
    L.ok(sha(DOC) === shaDoc, 'o arquivo sabotado voltou igual (sha256)');
    await sec.ctx.close();
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(DOC) !== shaDoc) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
