/*
 * PROVA DO HISTÓRICO ESCOLAR, PARTE A — os cadastros e os textos (D-110). APAGA o banco de prova (monta a demonstração
 * do colégio numa instalação nova de escola).
 *   1. os segmentos e as séries: o nível (o Fundamental junta os segmentos marcados) e o número de cada série, propostos
 *      pelo nome e gravados pela tela; o número fora do nível é recusado;
 *   2. os textos por nível e vigência: duas vigências; "qual texto vale na data" escolhe a certa; o marcador que não
 *      existe é recusado; a correção grava no registro SÓ o campo que mudou ("de → para"); salvar igual não grava nada;
 *   3. as observações (padrão e selecionável; a chave repetida recusada), o logo por nível (PNG aceito, texto recusado,
 *      voltar ao da instituição), as escolas (UF obrigatória; "esta escola" é uma só), o nome e a ordem das disciplinas;
 *   4. o aluno de prova (criado por script, marcado como prova): o RA estadual e a certidão gravados fora da tabela do
 *      aluno (a linha dele em col_usuarios não muda); o selo PROVA;
 *   5. a direção abre e não grava (a tela sem o botão; o POST direto recusado, nenhuma linha nova); o professor não abre;
 *   sabotagem: o registro que grava mesmo sem mudança — a prova reprova; o arquivo volta igual.
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { execFileSync } = require('child_process');
const L = require('../lib');
const X = require('../onda8/_escola');

const ETAPA = 'historico/parteA';
const BASEPHP = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'historicoEscola', 'AdmsHistBase.php');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const { sql, um } = X;
const prova = (...a) => execFileSync('php', [path.join(__dirname, 'prova.php'), ...a], { encoding: 'utf8' });
const postar = (p, url, campos) => p.evaluate(async ([u, c]) => {
  const x = await fetch(u, { method: 'POST', credentials: 'same-origin', body: new URLSearchParams(c) });
  return { st: x.status, t: await x.text(), url: x.url };
}, [L.BASE + 'painel/' + url, campos]);
const texto = async (p, sel) => ((await p.textContent(sel).catch(() => '')) || '').replace(/\s+/g, ' ').trim();
const registros = () => um('SELECT COUNT(*) FROM esc_hist_alteracao');
const ultimo = () => sql('SELECT texto FROM esc_hist_alteracao ORDER BY id DESC LIMIT 1');
const PNG = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', 'base64');

/** abre a aba Textos (EF) e salva o formulário com os campos dados (os outros como estão) */
async function salvarTexto(p, { vigencia = null, nova = false, nivel = 'EF', campos = {} }) {
  await p.goto(L.BASE + `painel/historico-escola/cadastros?aba=textos&nivel=${nivel}` + (nova ? '&nova=1' : '') + (vigencia ? `&vigencia=${vigencia}` : ''));
  for (const [k, v] of Object.entries(campos)) {
    const sel = `#formTexto [name="${k}"]`;
    if ((await p.$eval(sel, (e) => e.tagName)) === 'SELECT') { await p.selectOption(sel, v); } else { await p.fill(sel, v); }
  }
  await Promise.all([p.waitForNavigation(), p.click('#salvarTexto')]);
  return (await texto(p, '.alert'));
}

(async () => {
  const { navegador } = await L.abrir();
  const shaBase = sha(BASEPHP);
  try {
    L.ok(X.montar('historico-parteA').status === 0, 'a demonstração do colégio montada pela tela, numa instalação nova de escola');
    prova('sem-historico-demo'); // D-115: o cenário da prova parte do histórico vazio
    const c = X.cred();
    const sec = await X.entrar(navegador, 'secretaria.demo', c.painel['secretaria.demo']);
    const p = sec.p;

    /* 1. os segmentos e as séries */
    await p.goto(L.BASE + 'painel/historico-escola/cadastros?aba=series');
    const propostos = await p.$$eval('#tabelaSeries .badge', (l) => l.length);
    await L.print(p, ETAPA, 'segmentos-e-series');
    await p.selectOption('#tabelaSeries tr[data-segmento="3"] select', '');
    await Promise.all([p.waitForNavigation(), p.click('#salvarSeries')]);
    const seg = sql("SELECT GROUP_CONCAT(CONCAT(curso_id, '=', nivel) ORDER BY curso_id) FROM esc_hist_segmento");
    const num = sql("SELECT GROUP_CONCAT(CONCAT(m.codnome, '=', s.numero) ORDER BY m.id SEPARATOR ', ') FROM esc_hist_serie s JOIN rm_cursos_turma_series m ON m.id = s.modulo_id WHERE s.numero > 0");
    L.ok(propostos >= 7 && seg === '1=EF,2=EM,3=' && /6º ano=6, 7º ano=7, 8º ano=8, 9º ano=9, 1ª série=1, 2ª série=2, 3ª série=3/.test(num),
      `os segmentos e as séries: propostos pelo nome (${propostos} marcas) e gravados pela tela (${seg}; ${num})`);
    const regSeries = um("SELECT COUNT(*) FROM esc_hist_alteracao WHERE tabela IN ('esc_hist_segmento', 'esc_hist_serie')");
    await p.goto(L.BASE + 'painel/historico-escola/cadastros?aba=series');
    await p.fill('#tabelaSeries label[data-serie="5"] input', '4');
    await Promise.all([p.waitForNavigation(), p.click('#salvarSeries')]);
    const recusa = await texto(p, '#seriesRecusadas');
    L.ok(/vai de 1 a 3/.test(recusa) && um('SELECT numero FROM esc_hist_serie WHERE modulo_id = 5') === 1 && regSeries === 10,
      `o número fora do nível é recusado ("${recusa.slice(0, 80)}"); o registro tem uma linha por segmento e por série (${regSeries})`);

    /* 2. os textos por vigência */
    const r1 = await salvarTexto(p, { nova: true, campos: { vigente_desde: '2025-01-01', orgao_linha1: 'ÓRGÃO DE PROVA', cidade: 'Cidade Prova Um', rotulo_registro: 'R.A. Nº', escala: 'Escala de prova: de zero a dez.' } });
    const v1 = um("SELECT id FROM esc_hist_texto WHERE nivel = 'EF' AND vigente_desde = '2025-01-01'");
    const r2 = await salvarTexto(p, { nova: true, campos: { vigente_desde: '2026-02-01', cidade: 'Cidade Prova Dois' } });
    const v2 = um("SELECT id FROM esc_hist_texto WHERE nivel = 'EF' AND vigente_desde = '2026-02-01'");
    const copia = sql(`SELECT CONCAT(orgao_linha1, '|', rotulo_registro, '|', cidade) FROM esc_hist_texto WHERE id = ${v2}`);
    L.ok(/gravados/.test(r1) && /gravados/.test(r2) && v1 > 0 && v2 > 0 && copia === 'ÓRGÃO DE PROVA|R.A. Nº|Cidade Prova Dois',
      `duas vigências do Fundamental; a nova começa da cópia da que vale (${copia})`);
    await p.goto(L.BASE + 'painel/historico-escola/cadastros?aba=textos&nivel=EF&conferir=2025-06-01');
    const c1 = await texto(p, '#vigenciaConferida');
    await p.goto(L.BASE + 'painel/historico-escola/cadastros?aba=textos&nivel=EF&conferir=2026-03-15');
    const c2 = await texto(p, '#vigenciaConferida');
    await p.goto(L.BASE + 'painel/historico-escola/cadastros?aba=textos&nivel=EF&conferir=2024-12-31');
    const c3 = await texto(p, '#vigenciaConferida');
    L.ok(/vigência de 01\/01\/2025/.test(c1) && /vigência de 01\/02\/2026/.test(c2) && /nenhuma vigência vale/.test(c3),
      `a vigência certa por data: "${c1}" / "${c2}" / "${c3.slice(0, 50)}…"`);
    await L.print(p, ETAPA, 'textos-vigencia');
    const rMarc = await salvarTexto(p, { vigencia: v2, campos: { fecho_declaracao: 'Declara que {aluno} {inventado}.' } });
    L.ok(/o marcador \{inventado\} não existe/.test(rMarc), `o marcador que não existe é recusado ("${rMarc.slice(0, 80)}")`);
    let antes = registros();
    await salvarTexto(p, { vigencia: v2, campos: { turno: 'Integral' } });
    const so = ultimo();
    L.ok(registros() === antes + 1 && /Turno: "" → "Integral"/.test(so) && !/Cidade|Órgão|Escala/.test(so),
      `a correção grava no registro só o campo que mudou ("${so.slice(0, 140)}")`);
    antes = registros();
    await salvarTexto(p, { vigencia: v2, campos: {} });
    L.ok(registros() === antes, 'salvar sem mudar nada não grava linha nenhuma no registro');
    await p.goto(L.BASE + 'painel/historico-escola/cadastros?aba=registro');
    const linhasReg = await p.$$eval('#tabelaRegistro tbody tr', (l) => l.map((x) => x.textContent.replace(/\s+/g, ' ')));
    L.ok(linhasReg.some((x) => /Turno: "" → "Integral"/.test(x)) && linhasReg.some((x) => /Sônia|secretaria/i.test(x)), 'o registro de alterações em texto legível, com quem');
    await L.print(p, ETAPA, 'registro');

    /* 3. as observações, o logo, as escolas, as disciplinas */
    const obs = async (campos) => {
      await p.goto(L.BASE + 'painel/historico-escola/cadastros?aba=observacoes&nivel=EF');
      for (const [k, v] of Object.entries(campos)) { if (k === 'tipo') { await p.selectOption('#oTipo', v); } else { await p.fill(`#formObservacao [name="${k}"]`, v); } }
      await Promise.all([p.waitForNavigation(), p.click('#salvarObservacao')]);
      return texto(p, '.alert');
    };
    const o1 = await obs({ chave: 'nove_anos', titulo: 'Fundamental de nove anos', texto: 'Observação padrão de prova.', ordem: '1' });
    const o2 = await obs({ chave: 'conselho', titulo: 'Conselho de classe', texto: 'Promovido pelo conselho de classe no {serie} em {ano}.', tipo: 'selecionavel', ordem: '2' });
    const o3 = await obs({ chave: 'conselho', titulo: 'Repetida', texto: 'x' });
    L.ok(/gravada/.test(o1) && /gravada/.test(o2) && /Já existe uma observação com essa chave/.test(o3)
      && sql("SELECT GROUP_CONCAT(CONCAT(chave, ':', tipo) ORDER BY ordem) FROM esc_hist_observacao WHERE nivel = 'EF'") === 'nove_anos:padrao,conselho:selecionavel',
      'as observações: a padrão e a selecionável gravadas; a chave repetida recusada');
    const arqPng = path.join(L.PROVAS, ETAPA, 'logo.png');
    const arqTxt = path.join(L.PROVAS, ETAPA, 'logo.png.txt');
    fs.mkdirSync(path.dirname(arqPng), { recursive: true });
    fs.writeFileSync(arqPng, PNG);
    fs.writeFileSync(arqTxt, 'não sou imagem');
    await p.goto(L.BASE + 'painel/historico-escola/cadastros?aba=logo');
    await p.setInputFiles('#logo-EF input[type=file]', arqTxt);
    await Promise.all([p.waitForNavigation(), p.click('#enviarLogo-EF')]);
    const lRec = await texto(p, '#logoRecusado');
    await p.setInputFiles('#logo-EF input[type=file]', arqPng);
    await Promise.all([p.waitForNavigation(), p.click('#enviarLogo-EF')]);
    const lOk = await texto(p, '#logoGravado');
    const temLogo = um("SELECT COUNT(*) FROM esc_hist_logo WHERE nivel = 'EF' AND mime = 'image/png' AND largura = 1");
    await L.print(p, ETAPA, 'logo');
    await Promise.all([p.waitForNavigation(), p.click('#retirarLogo-EF')]);
    L.ok(/não é uma imagem/.test(lRec) && /gravado/.test(lOk) && temLogo === 1 && um('SELECT COUNT(*) FROM esc_hist_logo') === 0,
      'o logo por nível: o texto recusado, o PNG gravado, e "voltar ao logo da instituição" o retira');
    const escola = async (campos) => {
      await p.goto(L.BASE + 'painel/historico-escola/cadastros?aba=escolas' + (campos.editar ? '&editar=' + campos.editar : ''));
      for (const [k, v] of Object.entries(campos)) {
        if (k === 'editar') { continue; }
        if (k === 'esta_escola') { await (v ? p.check('#eEsta') : p.uncheck('#eEsta')); } else { await p.fill(`#formEscola [name="${k}"]`, v); }
      }
      await Promise.all([p.waitForNavigation(), p.click('#salvarEscola')]);
      return texto(p, '.alert');
    };
    const e0 = await escola({ nome: 'PROVA Escola Sem UF', municipio: 'Cidade Prova', uf: '' });
    await escola({ nome: 'PROVA Colegio desta escola', municipio: 'Cidade Prova', uf: 'SC', rede: 'particular', esta_escola: true });
    await escola({ nome: 'PROVA Escola de origem', municipio: 'Outra Cidade', uf: 'PR', rede: 'municipal' });
    const idOrigem = um("SELECT id FROM esc_hist_escola WHERE nome = 'PROVA Escola de origem'");
    await escola({ editar: idOrigem, esta_escola: true });
    const estas = sql('SELECT GROUP_CONCAT(nome) FROM esc_hist_escola WHERE esta_escola = 1');
    await escola({ editar: um("SELECT id FROM esc_hist_escola WHERE nome = 'PROVA Colegio desta escola'"), esta_escola: true });
    L.ok(/obrigatórios/.test(e0) && estas === 'PROVA Escola de origem' && sql('SELECT GROUP_CONCAT(nome) FROM esc_hist_escola WHERE esta_escola = 1') === 'PROVA Colegio desta escola',
      `as escolas: sem UF recusada; "esta escola" é uma só (marcar outra desmarca a primeira: ${estas})`);
    await p.goto(L.BASE + 'painel/historico-escola/cadastros?aba=disciplinas');
    const nDisc = await p.$$eval('#tabelaDisciplinas tbody tr[data-disciplina]', (l) => l.length);
    const ingles = sql("SELECT id FROM rm_materias WHERE nome LIKE 'L%ngua Inglesa' LIMIT 1");
    await p.fill(`#tabelaDisciplinas tr[data-disciplina="m${ingles}"] input[name^="nome"]`, 'Língua Estrangeira Moderna — Inglês');
    await p.fill(`#tabelaDisciplinas tr[data-disciplina="m${ingles}"] input[name^="ordem_ef"]`, '8');
    await Promise.all([p.waitForNavigation(), p.click('#salvarDisciplinas')]);
    const gDisc = sql(`SELECT CONCAT(nome, '|', ordem_ef) FROM esc_hist_disciplina WHERE materia_id = ${ingles}`);
    L.ok(nDisc >= 10 && gDisc === 'Língua Estrangeira Moderna — Inglês|8' && um('SELECT COUNT(*) FROM esc_hist_disciplina') === 1,
      `as disciplinas: ${nDisc} das matrizes; só a que mudou ganha a linha do histórico (${gDisc})`);

    /* 4. o aluno de prova */
    const aluno = JSON.parse(prova('aluno', '990001', 'PROVA HIST A')).id;
    const linhaAntes = prova('linha', 'col_usuarios', String(aluno));
    const colunasAntes = sql("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name IN ('col_usuarios', 'col_usuarios_informacoes')");
    await p.goto(L.BASE + `painel/historico-escola/aluno/${aluno}`);
    const selo = await texto(p, '.page-pretitle');
    await p.fill('#hRa', '000.000.001-1');
    await p.fill('#hCertidao', 'Cartório de prova, livro 0, folha 0, termo 01');
    await Promise.all([p.waitForNavigation(), p.click('#salvarAlunoHistorico')]);
    const gravado = sql(`SELECT CONCAT(ra, '|', certidao) FROM esc_hist_aluno WHERE aluno_id = ${aluno}`);
    const regAluno = await texto(p, '#registroAluno');
    L.ok(/PROVA/.test(selo) && gravado === '000.000.001-1|Cartório de prova, livro 0, folha 0, termo 01' && /RA \(registro na rede estadual\): "" → "000.000.001-1"/.test(regAluno)
      && prova('linha', 'col_usuarios', String(aluno)) === linhaAntes && sql("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name IN ('col_usuarios', 'col_usuarios_informacoes')") === colunasAntes,
      `o aluno de prova (selo "${selo}"): o RA estadual e a certidão gravados fora da tabela do aluno (a linha dele não mudou; nenhuma coluna nova)`);
    await L.print(p, ETAPA, 'aluno');
    await sec.ctx.close();

    /* 5. a direção abre e não grava; o professor não abre */
    const dir = await X.entrar(navegador, 'direcao.demo', c.painel['direcao.demo']);
    await dir.p.goto(L.BASE + 'painel/historico-escola/cadastros?aba=textos&nivel=EF');
    const semBotao = !(await dir.p.$('#salvarTexto')) && !!(await dir.p.$('#formTexto'));
    const nTextos = um('SELECT COUNT(*) FROM esc_hist_texto');
    const nReg = registros();
    const pDir = await postar(dir.p, 'historico-escola/salvar-texto', { nivel: 'EF', id: '0', vigente_desde: '2030-01-01', cidade: 'Direção' });
    const pDir2 = await postar(dir.p, 'historico-escola/salvar-escola', { id: '0', nome: 'Direção', municipio: 'X', uf: 'SC' });
    L.ok(semBotao && um('SELECT COUNT(*) FROM esc_hist_texto') === nTextos && registros() === nReg && um("SELECT COUNT(*) FROM esc_hist_escola WHERE nome = 'Direção'") === 0,
      `a direção abre os cadastros sem o botão de salvar; os POSTs diretos são recusados (${pDir.st}, ${pDir2.st}) sem nenhuma linha nova`);
    await dir.ctx.close();
    const prof = await X.entrar(navegador, 'prof.laura.demo', c.painel['prof.laura.demo']);
    const rp = await prof.p.goto(L.BASE + 'painel/historico-escola/cadastros');
    const tp = await L.textoDaTela(prof.p);
    L.ok(rp.status() === 403 || /não tem permissão|sem acesso|não permitid/i.test(tp), `o professor não abre os cadastros (${rp.status()})`);
    await prof.ctx.close();

    /* sabotagem: o registro grava mesmo sem mudança */
    const orig = fs.readFileSync(BASEPHP, 'utf8');
    const trecho = "        if (!$partes && $acao !== 'travar' && $acao !== 'destravar') {";
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let cresceu = false;
    try {
      fs.writeFileSync(BASEPHP, orig.replace(trecho, '        if (false) {'));
      const s2 = await X.entrar(navegador, 'secretaria.demo', c.painel['secretaria.demo']);
      const n0 = registros();
      await salvarTexto(s2.p, { vigencia: v2, campos: {} });
      cresceu = registros() > n0;
      await s2.ctx.close();
    } finally {
      fs.writeFileSync(BASEPHP, orig);
    }
    L.ok(cresceu, 'sabotagem (o registro grava mesmo sem mudança): a conferência "salvar igual não grava" pega — uma linha apareceu');
    L.ok(sha(BASEPHP) === shaBase, 'o arquivo sabotado voltou igual (sha256)');
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(BASEPHP) !== shaBase) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
