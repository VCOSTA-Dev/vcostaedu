/*
 * PROVA DO HISTÓRICO ESCOLAR, PARTE C — a vida escolar (D-112). APAGA o banco de prova.
 *   0. o cenário: os segmentos e as séries, as escolas e a matriz de 2025 pela tela; os alunos de prova criados por
 *      script (clones fictícios de alunos da demonstração, marcados como prova): ex-alunos do 9º ano de 2025 (o ano
 *      encerrado) e um aluno do 6º ano de 2026 (o ano aberto);
 *   1. a busca acha ativos e ex-alunos com a situação real e o selo PROVA;
 *   2. a proposta pelas turmas: o 9º de 2025 "aprovado" (o ano encerrado), o 6º de 2026 "em curso";
 *   3. a média é LIDA da função única: a de cada disciplina é igual à do boletim do aluno; nada é copiado (as tabelas
 *      de nota não mudam e a vida escolar não grava média do sistema);
 *   4. confirmar (origem "do sistema", no registro) e tirar (volta a ser proposta);
 *   5. sobrepor uma média: registrada com a do sistema e a nova; a do sistema continua a mesma; vazia volta a valer a do
 *      sistema;
 *   6. a disciplina com nota fora da matriz do ano: aviso, e fora das linhas;
 *   7. digitar anos cursados em outra escola: a escola obrigatória; a disciplina do cadastro e a criada na hora (base
 *      comum ou parte diversificada), com a média e a carga; a média inválida recusada; F e NF aceitos;
 *   8. a retenção: o 9º digitado em 2024 (retido) e o 9º de 2025 — a série repetida marcada; o conselho e as observações
 *      da série gravados e no registro;
 *   9. a direção abre e não grava;
 *   sabotagem: a média do histórico truncada (diferente da do boletim) — a prova reprova; o arquivo volta igual.
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { execFileSync } = require('child_process');
const L = require('../lib');
const X = require('../onda8/_escola');

const ETAPA = 'historico/parteC';
const VIDA = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'historicoEscola', 'AdmsHistVida.php');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const { sql, um } = X;
const prova = (...a) => execFileSync('php', [path.join(__dirname, 'prova.php'), ...a], { encoding: 'utf8' });
const postar = (p, url, campos) => p.evaluate(async ([u, c]) => {
  const x = await fetch(u, { method: 'POST', credentials: 'same-origin', body: new URLSearchParams(c) });
  return { st: x.status };
}, [L.BASE + 'painel/' + url, campos]);
const texto = async (p, sel) => ((await p.textContent(sel).catch(() => '')) || '').replace(/\s+/g, ' ').trim();
const hashNotas = () => sql("SELECT CONCAT(COUNT(*), ':', IFNULL(SUM(CRC32(CONCAT(rm, atividade_id, nota))), 0)) FROM rm_disciplina_atividade_notas")
  + '|' + sql("SELECT CONCAT(COUNT(*), ':', IFNULL(SUM(CRC32(CONCAT(rm, fechamento_id, IFNULL(nota, '')))), 0)) FROM esc_fechamento_notas");

/** as médias da tela de notas da série: {disciplina: média do sistema} */
const mediasNotas = (p) => p.$$eval('#tabelaNotas tbody tr[data-materia]', (l) => Object.fromEntries(l.map((tr) => [tr.cells[0].textContent.replace('Ensino Religioso', '').trim().replace(/\s+/g, ' '), tr.querySelector('.vc-media-sistema') ? tr.querySelector('.vc-media-sistema').textContent.trim() : tr.cells[2].querySelector('input').value])));
/** as médias anuais do boletim (a 6ª coluna) */
const mediasBoletim = (p) => p.$$eval('table tbody tr', (l) => Object.fromEntries(l.filter((tr) => tr.cells.length >= 7).map((tr) => [tr.cells[0].textContent.trim(), tr.cells[5].textContent.trim()])));

(async () => {
  const { navegador } = await L.abrir();
  const shaV = sha(VIDA);
  try {
    L.ok(X.montar('historico-parteC').status === 0, 'a demonstração do colégio montada pela tela');
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
    await p.goto(L.BASE + 'painel/historico-escola-matriz/listar');
    await p.selectOption('#nGrade', '1'); await p.fill('#nAno', '2025');
    await Promise.all([p.waitForNavigation(), p.click('#criarMatriz')]);
    const m25 = +p.url().split('/').pop();
    const linhaArte9 = um(`SELECT l.id FROM uni_rh_cursos_disciplinas_grade l JOIN rm_materias t ON t.id = l.materia_id WHERE l.grade_id = 1 AND l.modulo_id = 4 AND t.nome = 'Arte'`);
    await p.fill(`tr[data-linha="${linhaArte9}"] .vc-aulas`, '0'); // Arte fora da matriz do 9º de 2025
    await Promise.all([p.waitForNavigation(), p.click('#salvarMatriz')]);
    const ida = JSON.parse(prova('clonar', '255001', '995001', 'PROVA HIST C UM')).id;
    const idb = JSON.parse(prova('clonar', '255002', '995002', 'PROVA HIST C DOIS')).id;
    const idg = JSON.parse(prova('clonar', '265001', '996001', 'PROVA HIST C ABERTO')).id;
    L.ok(m25 > 0 && ida > 0 && idb > 0 && idg > 0 && um("SELECT COUNT(*) FROM esc_hist_dado_prova WHERE tabela = 'col_usuarios'") === 3,
      'o cenário: os segmentos, as escolas e a matriz de 2025 pela tela; três alunos de prova criados por script e marcados');

    /* 1. a busca */
    await p.goto(L.BASE + 'painel/historico-escola-vida/buscar?q=PROVA HIST C');
    const busca = await p.$$eval('#resultadoBusca tbody tr[data-aluno]', (l) => l.map((tr) => [...tr.cells].map((td) => td.textContent.replace(/\s+/g, ' ').trim()).join(' ')));
    await L.print(p, ETAPA, 'busca');
    L.ok(busca.length === 3 && busca.some((x) => /C UM PROVA ex-aluno \(último ano 2025\)/.test(x)) && busca.some((x) => new RegExp(`C ABERTO PROVA ativo em ${new Date().getFullYear()}`).test(x)),
      `a busca acha o ex-aluno e o ativo, com a situação real e o selo PROVA (${busca.join(' | ').slice(0, 160)})`);

    /* 2. a proposta */
    await p.goto(L.BASE + `painel/historico-escola-vida/aluno/${ida}?nivel=EF`);
    const linhaA = await texto(p, '#tabelaVida tr[data-ano="2025"]');
    await p.goto(L.BASE + `painel/historico-escola-vida/aluno/${idg}?nivel=EF`);
    const linhaG = await texto(p, '#tabelaVida tbody tr[data-serie="6"]');
    L.ok(/9º ano.*PROVA Colegio desta escola.*Aprovado.*proposta/.test(linhaA) && /6º ano.*Em curso.*proposta/.test(linhaG),
      `a proposta pelas turmas: "${linhaA.slice(0, 80)}" / "${linhaG.slice(0, 60)}"`);

    /* 3. a média lida da função única = a do boletim; nada copiado */
    const antesNotas = hashNotas();
    await p.goto(L.BASE + `painel/historico-escola-vida/notas/${ida}?turma=${um("SELECT id FROM esc_turmas WHERE ano = 2025 LIMIT 1")}`);
    const hist = await mediasNotas(p);
    const avisos = await texto(p, '#avisosSerie');
    await L.print(p, ETAPA, 'notas-proposta');
    await p.goto(L.BASE + 'painel/academicos/boletim/995001');
    const bol = await mediasBoletim(p);
    const comuns = Object.keys(hist).filter((k) => bol[k] !== undefined);
    const iguais = comuns.filter((k) => hist[k] === bol[k]);
    L.ok(comuns.length === 8 && iguais.length === comuns.length && !hist.Arte,
      `a média de cada disciplina é a do boletim (${iguais.length} de ${comuns.length}: ${comuns.map((k) => k + ' ' + hist[k]).join(', ').slice(0, 150)})`);
    L.ok(/Arte: tem nota no sistema e está fora da matriz do ano/.test(avisos), `a disciplina com nota fora da matriz: aviso ("${avisos.slice(0, 90)}") e fora das linhas`);

    /* 4. confirmar e tirar */
    await p.goto(L.BASE + `painel/historico-escola-vida/aluno/${ida}?nivel=EF`);
    await Promise.all([p.waitForNavigation(), p.click('#tabelaVida tr[data-ano="2025"] .vc-confirmar')]);
    const conf = await texto(p, '#tabelaVida tr[data-ano="2025"] .vc-origem');
    const serieA = um(`SELECT id FROM esc_hist_vida_serie WHERE aluno_id = ${ida} AND ano = 2025`);
    await Promise.all([p.waitForNavigation(), p.click('#tabelaVida tr[data-ano="2025"] .vc-tirar')]);
    const tirada = await texto(p, '#tabelaVida tr[data-ano="2025"] .vc-origem');
    const regVida = await texto(p, '#registroVida');
    await Promise.all([p.waitForNavigation(), p.click('#tabelaVida tr[data-ano="2025"] .vc-confirmar')]);
    const serieA2 = um(`SELECT id FROM esc_hist_vida_serie WHERE aluno_id = ${ida} AND ano = 2025`);
    L.ok(conf === 'do sistema' && serieA > 0 && tirada === 'proposta' && /9º ano de 2025 confirmada/.test(regVida) && /tirada \(volta a ser proposta\)/.test(regVida) && serieA2 > 0,
      'confirmar grava "do sistema"; tirar a faz voltar a ser proposta; os dois no registro');

    /* 5. sobrepor */
    await p.goto(L.BASE + `painel/historico-escola-vida/notas/${ida}?serie=${serieA2}`);
    const matSistema = await texto(p, '#tabelaNotas tr:has-text("Matemática") .vc-media-sistema');
    await p.fill('#tabelaNotas tr:has-text("Matemática") input', '9,0');
    await Promise.all([p.waitForNavigation(), p.click('#salvarNotas')]);
    const sobre = sql(`SELECT CONCAT(n.media, '|', n.origem, '|', n.media_sistema) FROM esc_hist_vida_nota n WHERE n.vida_serie_id = ${serieA2}`);
    const regSob = await texto(p, '#registroNotas');
    const matDepois = await texto(p, '#tabelaNotas tr:has-text("Matemática") .vc-media-sistema');
    await L.print(p, ETAPA, 'notas-sobreposta');
    await p.fill('#tabelaNotas tr:has-text("Matemática") input', '');
    await Promise.all([p.waitForNavigation(), p.click('#salvarNotas')]);
    L.ok(sobre === `9,0|sobreposto|${matSistema}` && new RegExp(`Matemática \\(média do sistema: ${matSistema.replace(',', ',')}\\) — Média sobreposta: "a do sistema" → "9,0"`).test(regSob)
      && matDepois === matSistema && um(`SELECT COUNT(*) FROM esc_hist_vida_nota WHERE vida_serie_id = ${serieA2}`) === 0 && hashNotas() === antesNotas,
      `sobrepor a média: registrada com a do sistema (${matSistema}) e a nova; a do sistema não muda; vazia volta a valer a do sistema; as notas da plataforma não mudaram`);

    /* 7. digitar anos de fora */
    const digitar = async (campos) => {
      await p.goto(L.BASE + `painel/historico-escola-vida/aluno/${idb}?nivel=EF&nova=1`);
      await p.selectOption('#sSerie', String(campos.serie));
      await p.fill('#sAno', String(campos.ano));
      if (campos.escola) { await p.selectOption('#sEscola', String(campos.escola)); }
      await p.selectOption('#sResultado', campos.resultado || 'aprovado');
      if (campos.religioso) { await p.fill('#sReligioso', campos.religioso); }
      for (const o of campos.obs || []) { await p.check(`#formSerie input[name="obs[]"][value="${o}"]`); }
      if (campos.conselho) { await p.check('#sConselho'); }
      await Promise.all([p.waitForNavigation(), p.click('#salvarSerie')]);
      return texto(p, '.alert');
    };
    const semEscola = await digitar({ serie: 5, ano: 2020 });
    const d5 = await digitar({ serie: 5, ano: 2020, escola: origem, religioso: 'f' });
    const s5 = um(`SELECT id FROM esc_hist_vida_serie WHERE aluno_id = ${idb} AND serie = 5`);
    await p.goto(L.BASE + `painel/historico-escola-vida/notas/${idb}?serie=${s5}`);
    const lp = um("SELECT id FROM rm_materias WHERE nome LIKE 'L%ngua Portuguesa'");
    await p.selectOption('#nDisc', String(um(`SELECT id FROM esc_hist_disciplina WHERE materia_id = ${lp}`) || 0)).catch(() => {});
    const opcaoLp = await p.$$eval('#nDisc option', (l) => l.map((o) => o.textContent));
    await p.selectOption('#nDisc', '0');
    await p.fill('#nNome', 'PROVA Disciplina criada'); await p.selectOption('#nBase', 'diversificada'); await p.fill('#nMedia', '11'); await p.fill('#nAulas', '80');
    await Promise.all([p.waitForNavigation(), p.click('#salvarNotas')]);
    const invalida = await texto(p, '#notasRecusadas');
    await p.fill('#nNome', 'PROVA Disciplina criada'); await p.selectOption('#nBase', 'diversificada'); await p.fill('#nMedia', '7,5'); await p.fill('#nAulas', '80');
    await Promise.all([p.waitForNavigation(), p.click('#salvarNotas')]);
    await p.fill('#nNome', 'PROVA Outra disciplina'); await p.fill('#nMedia', 'nf');
    await Promise.all([p.waitForNavigation(), p.click('#salvarNotas')]);
    const notas5 = sql(`SELECT GROUP_CONCAT(CONCAT(d.nome, '=', n.media, '/', n.aulas_anuais, '/', d.base) ORDER BY d.nome SEPARATOR '; ') FROM esc_hist_vida_nota n JOIN esc_hist_disciplina d ON d.id = n.disciplina_id WHERE n.vida_serie_id = ${s5}`);
    await L.print(p, ETAPA, 'notas-digitadas');
    L.ok(/precisa da escola/.test(semEscola) && /gravada/.test(d5) && sql(`SELECT CONCAT(origem, '|', religioso, '|', escola_id) FROM esc_hist_vida_serie WHERE id = ${s5}`) === `digitado|F|${origem}`
      && /0 a 10/.test(invalida) && notas5 === 'PROVA Disciplina criada=7,5/80/diversificada; PROVA Outra disciplina=NF/0/comum',
      `digitar o ano de fora: a escola obrigatória; o Ensino Religioso F; a média 11 recusada; a disciplina criada na hora com a média e a carga; NF aceito (${notas5})`);

    /* 8. a retenção, o conselho, as observações */
    await p.goto(L.BASE + 'painel/historico-escola/cadastros?aba=observacoes&nivel=EF');
    await p.fill('#oChave', 'reclassificacao'); await p.fill('#oTitulo', 'Reclassificação'); await p.fill('#oTexto', 'Reclassificado no {serie} em {ano} (prova).'); await p.selectOption('#oTipo', 'selecionavel');
    await Promise.all([p.waitForNavigation(), p.click('#salvarObservacao')]);
    await digitar({ serie: 9, ano: 2024, escola: origem, resultado: 'retido', obs: ['reclassificacao'], conselho: true });
    await p.goto(L.BASE + `painel/historico-escola-vida/aluno/${idb}?nivel=EF`);
    const linhas = await p.$$eval('#tabelaVida tbody tr[data-ano]', (l) => l.map((tr) => tr.dataset.ano + ':' + tr.dataset.serie + ':' + /repetida/.test(tr.textContent)));
    await L.print(p, ETAPA, 'vida-retencao');
    const s9 = sql(`SELECT CONCAT(resultado, '|', conselho, '|', obs_chaves) FROM esc_hist_vida_serie WHERE aluno_id = ${idb} AND ano = 2024`);
    L.ok(linhas.join(',') === '2020:5:false,2024:9:false,2025:9:true' && s9 === 'retido|1|reclassificacao',
      `a retenção: as séries em ordem cronológica e o 9º de 2025 marcado como repetido (${linhas.join(', ')}); o conselho e a observação da série gravados`);

    /* 9. a direção */
    await sec.ctx.close();
    const dir = await X.entrar(navegador, 'direcao.demo', c.painel['direcao.demo']);
    await dir.p.goto(L.BASE + `painel/historico-escola-vida/aluno/${idg}?nivel=EF`);
    const semBotoes = !(await dir.p.$('.vc-confirmar')) && !(await dir.p.$('#novaSerie')) && !!(await dir.p.$('#tabelaVida'));
    const n0 = um('SELECT COUNT(*) FROM esc_hist_vida_serie');
    const pc = await postar(dir.p, `historico-escola-vida/confirmar/${idg}`, { turma_id: String(um('SELECT id FROM esc_turmas WHERE ano = 2026 ORDER BY id LIMIT 1')), nivel: 'EF' });
    const ps = await postar(dir.p, `historico-escola-vida/salvar-serie/${idg}`, { id: '0', nivel: 'EF', serie: '3', ano: '2019', escola_id: String(origem), resultado: 'aprovado' });
    L.ok(semBotoes && um('SELECT COUNT(*) FROM esc_hist_vida_serie') === n0, `a direção abre a vida escolar sem os botões; os POSTs diretos (${pc.st}, ${ps.st}) não gravam`);
    await dir.ctx.close();

    /* sabotagem: a média do histórico truncada */
    const orig = fs.readFileSync(VIDA, 'utf8');
    const trecho = "        $t = rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');";
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let sab = {};
    try {
      fs.writeFileSync(VIDA, orig.replace(trecho, "        $t = (string) floor((float) $v);"));
      const s2 = await X.entrar(navegador, 'secretaria.demo', c.painel['secretaria.demo']);
      await s2.p.goto(L.BASE + `painel/historico-escola-vida/notas/${ida}?serie=${serieA2}`);
      sab = await mediasNotas(s2.p);
      await s2.ctx.close();
    } finally {
      fs.writeFileSync(VIDA, orig);
    }
    const difere = Object.keys(sab).filter((k) => bol[k] !== undefined && sab[k] !== bol[k]).length;
    L.ok(difere > 0, `sabotagem (a média do histórico truncada): a conferência com o boletim pega (${difere} disciplinas diferentes)`);
    L.ok(sha(VIDA) === shaV, 'o arquivo sabotado voltou igual (sha256)');
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(VIDA) !== shaV) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
