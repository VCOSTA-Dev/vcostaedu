/*
 * PROVA DO HISTÓRICO ESCOLAR, PARTE D — o modelo do Fundamental, frente e verso (D-113). APAGA o banco de prova.
 *   0. o cenário pela tela (os segmentos, as escolas, as matrizes de 2025 e 2026, os textos do Fundamental com o rótulo
 *      do registro e o fecho com um ponto solto, as observações) e os alunos de prova por script (clones fictícios de
 *      alunos da demonstração, e as séries cursadas fora): A (concluiu o 9º aqui, 6º a 8º fora), G (6º em curso), C
 *      (entrou no 8º no meio do ano, com notas de transferência), E (o pior caso: duas retenções, onze colunas);
 *   1. o desenho: os números soltos 10, 30, 40, 50, 60, 70 (e não 20), os títulos dos blocos, os rótulos verticais;
 *   2. A: as médias do 9º iguais às do boletim; a carga da matriz de 2025; as séries de fora com a escola delas;
 *      CERTIFICADO do 9º ano de 2025; o texto do fecho limpo (o ponto solto colado); o RA;
 *   3. G: a série em curso sem nota e sem carga na frente; o bloco de transferência com as notas das etapas iguais às
 *      do boletim, as faltas, as aulas dadas propostas e os dias letivos; DECLARAÇÃO do 6º ano, anos finais, 2026;
 *   4. C: a nota trazida da escola de origem marcada com "*" no bloco de transferência;
 *   5. E: onze colunas (a série repetida em ordem cronológica), duas páginas e nada além da margem direita;
 *   sabotagem: a série em curso com nota na frente — a prova reprova; o arquivo volta igual.
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { execFileSync } = require('child_process');
const L = require('../lib');
const X = require('../onda8/_escola');

const ETAPA = 'historico/parteD';
const DOC = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'historicoEscola', 'AdmsHistDocumento.php');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const { sql, um } = X;
const prova = (...a) => execFileSync('php', [path.join(__dirname, 'prova.php'), ...a], { encoding: 'utf8' });
const limpo = (s) => s.replace(/<[^>]+>/g, ' ').replace(/&nbsp;/g, ' ').replace(/&[a-z]+;|&#\d+;/g, (e) => ({ '&amp;': '&', '&quot;': '"', '&#039;': "'" }[e] || ' ')).replace(/\s+/g, ' ').trim();
const SAIDA = path.join(L.PROVAS, ETAPA);

/** a prévia em HTML (o degrau escolhido) e os cabeçalhos do encaixe */
async function previa(p, aluno, extra = '') {
  const r = await p.request.get(L.BASE + `painel/historico-escola-emissao/previa-pdf/${aluno}?nivel=EF&formato=html${extra}`);
  const h = r.headers();
  return { html: await r.text(), degrau: +h['x-historico-degrau'], paginas: +h['x-historico-paginas'], largura: +h['x-historico-largura'] };
}
async function pdf(p, aluno, nome) {
  const r = await p.request.get(L.BASE + `painel/historico-escola-emissao/previa-pdf/${aluno}?nivel=EF`);
  fs.writeFileSync(path.join(SAIDA, nome + '.pdf'), await r.body());
}
/** o quadro da frente: {disciplina: [células das colunas]} e a linha dos anos */
function quadro(html) {
  const t = html.split('<!-- 30: os resultados -->')[1].split('<!-- 40:')[0];
  const linhas = [...t.matchAll(/<tr>(.*?)<\/tr>/gs)].map((m) => [...m[1].matchAll(/<td[^>]*>(.*?)<\/td>/gs)].map((c) => limpo(c[1])));
  return linhas;
}
function bloco50(html) {
  const t = html.split('<!-- 50:')[1].split('<!-- 60:')[0];
  return [...t.matchAll(/<tr>(.*?)<\/tr>/gs)].map((m) => [...m[1].matchAll(/<td[^>]*>(.*?)<\/td>/gs)].map((c) => limpo(c[1])));
}
const fecho = (html) => limpo(html.split('<!-- o fecho')[1].split('<!-- a verificação')[0].replace(/^[^>]*-->/, ''));
const boletim = async (p, rm) => {
  await p.goto(L.BASE + 'painel/academicos/boletim/' + rm);
  return p.$$eval('table tbody tr', (l) => Object.fromEntries(l.filter((tr) => tr.cells.length >= 7).map((tr) => [tr.cells[0].textContent.trim(), [...tr.cells].map((c) => c.textContent.trim().replace(/\s*R$/, ''))])));
};

(async () => {
  const { navegador } = await L.abrir();
  const shaD = sha(DOC);
  fs.mkdirSync(SAIDA, { recursive: true });
  try {
    L.ok(X.montar('historico-parteD').status === 0, 'a demonstração do colégio montada pela tela');
    prova('sem-historico-demo'); // D-115: o cenário da prova parte do histórico vazio
    const c = X.cred();
    const sec = await X.entrar(navegador, 'secretaria.demo', c.painel['secretaria.demo']);
    const p = sec.p;

    /* 0. o cenário */
    await p.goto(L.BASE + 'painel/historico-escola/cadastros?aba=series');
    await p.selectOption('#tabelaSeries tr[data-segmento="3"] select', '');
    await Promise.all([p.waitForNavigation(), p.click('#salvarSeries')]);
    for (const [nome, mun, uf, esta] of [['PROVA Colegio desta escola', 'Cidade Prova', 'SC', true], ['PROVA Escola de origem', 'Outra Cidade', 'PR', false]]) {
      await p.goto(L.BASE + 'painel/historico-escola/cadastros?aba=escolas');
      await p.fill('#eNome', nome); await p.fill('#eMunicipio', mun); await p.fill('#eUf', uf);
      if (esta) { await p.check('#eEsta'); }
      await Promise.all([p.waitForNavigation(), p.click('#salvarEscola')]);
    }
    const origem = um("SELECT id FROM esc_hist_escola WHERE nome = 'PROVA Escola de origem'");
    for (const ano of [2025, 2026]) {
      await p.goto(L.BASE + 'painel/historico-escola-matriz/listar');
      await p.selectOption('#nGrade', '1'); await p.fill('#nAno', String(ano));
      await Promise.all([p.waitForNavigation(), p.click('#criarMatriz')]);
    }
    await p.goto(L.BASE + 'painel/historico-escola/cadastros?aba=textos&nivel=EF&nova=1');
    const textos = { vigente_desde: '2020-01-01', orgao_linha1: 'LINHA UM DO ÓRGÃO (PROVA)', ato_escola: 'Ato de prova nº 0', ato_curso: 'Ato do curso de prova', turno: 'Diurno',
      escala: 'Escala de prova: de zero a dez; média mínima seis.', secretaria_nome: 'Secretária de Prova', diretora_nome: 'Diretora de Prova', rotulo_registro: 'R.A. Nº',
      rotulo_publicacao: 'Nº do Registro da Publicação (prova)',
      fecho_declaracao: 'A Direção do {escola} declara que {aluno} R.A. Nº {ra} , está apto a prosseguir o {serie} do Ensino Fundamental {etapa} no ano letivo de {ano} .',
      fecho_certificado: 'A Direção do {escola} CERTIFICA que {aluno} R.A. Nº {ra} concluiu o {serie} do Ensino Fundamental no ano letivo de {ano}.' };
    for (const [k, v] of Object.entries(textos)) { await p.fill(`#formTexto [name="${k}"]`, v); }
    await Promise.all([p.waitForNavigation(), p.click('#salvarTexto')]);
    for (const [ch, tit, tx, tipo] of [['nove_anos', 'Nove anos', 'Observação padrão de prova.', 'padrao'], ['conselho', 'Conselho', 'Promovido pelo conselho no {serie} em {ano}.', 'selecionavel']]) {
      await p.goto(L.BASE + 'painel/historico-escola/cadastros?aba=observacoes&nivel=EF');
      await p.fill('#oChave', ch); await p.fill('#oTitulo', tit); await p.fill('#oTexto', tx); await p.selectOption('#oTipo', tipo);
      await Promise.all([p.waitForNavigation(), p.click('#salvarObservacao')]);
    }
    const A = JSON.parse(prova('clonar', '255001', '995001', 'PROVA HIST A CONCLUIU')).id;
    const G = JSON.parse(prova('clonar', '265001', '996001', 'PROVA HIST G EM CURSO')).id;
    const C = JSON.parse(prova('clonar', '265044', '996044', 'PROVA HIST C ENTROU')).id;
    const E = JSON.parse(prova('clonar', '255003', '995003', 'PROVA HIST E RETIDO')).id;
    const notasFora = ['7,5', '8,0', '6,5', '9,0', '5,5', '7,0', '8,5', '10,0'];
    for (const [s, ano] of [[6, 2022], [7, 2023], [8, 2024]]) { prova('serie', String(A), 'EF', String(s), String(ano), String(origem), 'aprovado', ...notasFora); }
    for (const [s, ano, r] of [[1, 2015, 'aprovado'], [2, 2016, 'aprovado'], [3, 2017, 'aprovado'], [4, 2018, 'aprovado'], [5, 2019, 'aprovado'], [6, 2020, 'retido'], [6, 2021, 'aprovado'], [7, 2022, 'aprovado'], [8, 2023, 'retido'], [8, 2024, 'aprovado']]) {
      prova('serie', String(E), 'EF', String(s), String(ano), String(origem), r, ...notasFora);
    }
    await p.goto(L.BASE + `painel/historico-escola/aluno/${A}`);
    await p.fill('#hRa', '000.000.001-1');
    await Promise.all([p.waitForNavigation(), p.click('#salvarAlunoHistorico')]);
    L.ok(A && G && C && E, 'o cenário: os cadastros pela tela; os quatro alunos de prova por script');

    /* 1. o desenho */
    const pa = await previa(p, A);
    await pdf(p, A, 'aluno-A');
    const nums = [...pa.html.matchAll(/<span class="num">(\d+)<\/span>/g)].map((m) => m[1]);
    const titulos = ['HISTÓRICO ESCOLAR - ENSINO FUNDAMENTAL', 'REGISTRO DE MATRÍCULA', 'RESULTADOS DOS ESTUDOS REALIZADOS NO ENSINO FUNDAMENTAL', 'ESTUDOS REALIZADOS NO ENSINO FUNDAMENTAL',
      'TRANSFERÊNCIA DURANTE O PERÍODO LETIVO', 'ESCALA AVALIATÓRIA', 'OBSERVAÇÕES', 'BASE NACIONAL COMUM - Artº 26 da Lei 9394/96', 'PARTE DIVERSIFICADA', '§ 5º do Artigo 26 da Lei 9394/96'];
    const faltam = titulos.filter((x) => !limpo(pa.html).includes(x));
    L.ok(nums.join(',') === '10,30,40,50,60,70' && !faltam.length && /transform-origin: 0 0/.test(pa.html) && /font-family: Helvetica/.test(pa.html) && pa.paginas === 2,
      `o desenho do modelo: os números ${nums.join(', ')} (sem o 20), os títulos e os rótulos verticais; Helvetica; duas páginas (degrau ${pa.degrau})` + (faltam.length ? ` — faltam: ${faltam.join('; ')}` : ''));

    /* 2. A: o 9º igual ao boletim, a carga, o certificado */
    const qa = quadro(pa.html);
    const anos = qa.find((l) => l[0] === 'CURRICULARES');
    const col9 = anos.indexOf('2025') - 2;
    const bolA = await boletim(p, '995001');
    const nomesBnc = qa.filter((l) => bolA[l[0]] && l.length > 9);
    const iguais = nomesBnc.filter((l) => l[1 + col9] === bolA[l[0]][5]);
    const cargaMatriz = um("SELECT SUM(i.aulas_semanais) * MAX(m.semanas) FROM esc_hist_matriz_item i JOIN esc_hist_matriz m ON m.id = i.matriz_id JOIN uni_rh_cursos_disciplinas_grade l ON l.id = i.linha_id JOIN rm_materias t ON t.id = l.materia_id WHERE m.ano = 2025 AND l.modulo_id = 4 AND t.nome NOT LIKE '%eligios%'");
    const totalBnc = qa.find((l) => /^Total da Carga/.test(l[0]));
    const fa = fecho(pa.html);
    const estudosA = limpo(pa.html.split('<!-- 40:')[1].split('<!-- o verso -->')[0]);
    L.ok(anos.slice(2).join('|') === '|||||2022|2023|2024|2025' && iguais.length === 8 && nomesBnc.length === 8,
      `A: as colunas 6º a 9º (${anos.slice(2).join(' ')}); as médias do 9º iguais às do boletim (${iguais.length} de ${nomesBnc.length})`);
    L.ok(totalBnc[1 + col9] === cargaMatriz.toLocaleString('pt-BR') && /2022 PROVA Escola de origem Outra Cidade PR/.test(estudosA) && /2025 PROVA Colegio desta escola Cidade Prova SC/.test(estudosA),
      `A: a carga do 9º é a da matriz de 2025 (${totalBnc[1 + col9]}); os estudos com a escola de cada série`);
    L.ok(/^CERTIFICADO A Direção do .* CERTIFICA que PROVA HIST A CONCLUIU R\.A\. Nº 000\.000\.001-1 concluiu o 9º ano do Ensino Fundamental no ano letivo de 2025\./.test(fa),
      `A: o CERTIFICADO do 9º ano de 2025, com o RA ("${fa.slice(0, 150)}…")`);

    /* 3. G: em curso; o bloco de transferência */
    const pg = await previa(p, G);
    await pdf(p, G, 'aluno-G');
    const qg = quadro(pg.html);
    const anosG = qg.find((l) => l[0] === 'CURRICULARES');
    const c6 = anosG.indexOf(String(new Date().getFullYear())) - 2;
    const lp = qg.find((l) => l[0] === 'Língua Portuguesa');
    const totG = qg.find((l) => /^CARGA HORÁRIA TOTAL/.test(l[0]));
    const b50 = bloco50(pg.html);
    const bolG = await boletim(p, '996001');
    const linhas50 = b50.filter((l) => bolG[l[0]] || bolG[l[1]]);
    const etapasIguais = linhas50.filter((l) => { const n = bolG[l[0]] ? l : l.slice(1); const b = bolG[n[0]]; return [1, 2, 3].every((i) => n[i] === b[i]); });
    const cab50 = limpo(pg.html.split('<!-- 50:')[1].split('COMPONENTES CURRICULARES')[0]);
    const fg = fecho(pg.html);
    L.ok(c6 >= 0 && lp[1 + c6] === '' && totG[1 + c6] === '', `G: a série em curso (${anosG[c6 + 2]}) sem nota e sem carga na frente`);
    L.ok(linhas50.length >= 8 && etapasIguais.length === linhas50.length && /Rendimento escolar do Aluno no Ano Letivo de \d{4} até \d\d\/\d\d\/\d{4}/.test(cab50) && /Turma 6º ano A/.test(cab50)
      && /FALTAS \d+/.test(limpo(pg.html.split('1° ao 9° ano')[1] || '')) && /DIAS LETIVOS \d+/.test(limpo(pg.html)),
      `G: o bloco 50 com as notas das etapas iguais às do boletim (${etapasIguais.length} de ${linhas50.length}), as faltas, as aulas dadas e os dias letivos ("${cab50.slice(-90)}")`);
    L.ok(/^DECLARAÇÃO .* declara que PROVA HIST G EM CURSO R\.A\. Nº, está apto a prosseguir o 6º ano do Ensino Fundamental anos finais no ano letivo de \d{4}\.$/.test(fg.split(' DATA')[0]),
      `G: a DECLARAÇÃO do 6º ano, anos finais, no mesmo ano; o texto limpo (o ponto solto colado): "${fg.split(' DATA')[0].slice(-110)}"`);
    await p.goto(L.BASE + `painel/historico-escola-emissao/previa/${G}?nivel=EF`);
    const avisosG = await p.textContent('#avisos').catch(() => '');
    await L.print(p, ETAPA, 'previa-G');
    L.ok(/a série ainda é proposta/.test(avisosG) && /R\.A\. Nº do aluno está vazio/.test(avisosG), 'G: os avisos da prévia (a série proposta, o RA vazio)');

    /* 4. C: a nota trazida marcada */
    const pc = await previa(p, C);
    const marcadas = bloco50(pc.html).flat().filter((x) => /^\d+,\d+\*$/.test(x));
    L.ok(marcadas.length > 0 && /Rendimento escolar/.test(pc.html), `C: as notas trazidas da escola de origem marcadas com "*" no bloco de transferência (${marcadas.slice(0, 4).join(' ')})`);

    /* 5. E: o pior caso */
    const pe = await previa(p, E);
    await pdf(p, E, 'aluno-E-pior-caso');
    const anosE = quadro(pe.html).find((l) => l[0] === 'CURRICULARES').slice(2);
    const seriesE = quadro(pe.html).find((l) => l[0] === 'COMPONENTES').slice(2);
    L.ok(anosE.length === 11 && seriesE.join(' ') === '1º 2º 3º 4º 5º 6º 6º 7º 8º 8º 9º' && anosE.join(' ') === '2015 2016 2017 2018 2019 2020 2021 2022 2023 2024 2025'
      && pe.paginas === 2 && pe.largura > 500 && pe.largura <= 559,
      `E (o pior caso): onze colunas, a série repetida em ordem cronológica; ${pe.paginas} páginas no degrau ${pe.degrau}, nada além da margem (${pe.largura} pt de 559)`);
    await sec.ctx.close();

    /* sabotagem: a série em curso com nota na frente */
    const orig = fs.readFileSync(DOC, 'utf8');
    const trecho = "                $semNota = in_array($s['resultado'], ['em_curso', 'transferido'], true);";
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let lpSab = [];
    try {
      fs.writeFileSync(DOC, orig.replace(trecho, '                $semNota = false;'));
      const s2 = await X.entrar(navegador, 'secretaria.demo', c.painel['secretaria.demo']);
      lpSab = quadro((await previa(s2.p, G)).html).find((l) => l[0] === 'Língua Portuguesa');
      await s2.ctx.close();
    } finally {
      fs.writeFileSync(DOC, orig);
    }
    L.ok(lpSab[1 + c6] !== '', `sabotagem (a série em curso com nota): a conferência pega — a coluna mostrou "${lpSab[1 + c6]}"`);
    L.ok(sha(DOC) === shaD, 'o arquivo sabotado voltou igual (sha256)');
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(DOC) !== shaD) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
