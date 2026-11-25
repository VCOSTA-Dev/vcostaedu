/*
 * ONDA 4, ETAPA 4 — a demonstração da escola: o Colégio Horizonte (fictício) (docs/ESCOLA.md, seção 3).
 *   A. PELA TELA, numa instalação nova de escola: "Conhecer com dados de demonstração" monta em passos (a janela), cada
 *      passo abaixo do tempo dos de hoje (120 s); um passo forçado a falhar para a janela nele, e "Tentar de novo" segue
 *      dali até o fim; a régua da escola (o retrato da demonstração montada pela tela);
 *   B. o conteúdo: a matriz de exemplo pela Base (os componentes e as horas de cada segmento; o itinerário como
 *      componentes comuns), o aviso de que a matriz é um exemplo; as turmas (4 no ano corrente, 1 do ano anterior); o
 *      ano corrente com 2 bimestres fechados e o 3º com nota; os casos: aprovado direto, a recuperação que salvou e a
 *      que não salvou, reprovado por falta com nota, aprovado pelo conselho, o aluno que trocou de turma; os nomes
 *      marcados;
 *   C. a REMOÇÃO pela tela: nada da demonstração fica (os segmentos, os alunos, as turmas, as tabelas da escola);
 *   D. PELA LINHA DE COMANDO, numa instalação nova de escola: a régua IDÊNTICA à da tela;
 *   sabotagem: o passo que esquece o conselho — a prova reprova (a régua da linha de comando difere da da tela).
 *
 *   node tests/tabler/onda4/etapa4.js   (no fim, o banco de prova é uma escola com a demonstração: rode o ciclo depois)
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
const ETAPA = 'onda4/etapa4';
const SENHA = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const SAIDA = path.join(L.RAIZ, 'tests', 'tabler', 'saida');
const REGUA_TELA = path.join(SAIDA, 'regua-escola-onda4-etapa4.json');
const REGUA_CLI = path.join(SAIDA, 'regua-escola-onda4-etapa4-cli.json');
const CENARIO = path.join(L.RAIZ, 'database', 'ferramentas', 'demonstracao_escola.php');

function instalarEscola() {
  spawnSync(PHP, [path.join(L.RAIZ, 'tests', 'tabler', 'reset.php')], { encoding: 'utf8', cwd: L.RAIZ });
  const r = spawnSync('node', [path.join(L.RAIZ, 'tests', 'tabler', 'instalar.js'), 'normal'], { encoding: 'utf8', cwd: L.RAIZ, env: { ...process.env, VCOSTAEDU_TIPO: 'escola', VCOSTAEDU_ETAPA_PROVA: ETAPA }, maxBuffer: 1 << 26 });
  return r.status === 0;
}
async function entrar(navegador) {
  const ctx = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' });
  const p = await L.pagina(ctx);
  await p.goto(L.BASE + 'painel/login/acesso');
  await p.fill('input[name="usuario"]', 'administrador');
  await p.fill('input[name="senha"]', SENHA);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
  return { ctx, p };
}
const regua = (modo, arq) => spawnSync(PHP, [path.join(L.RAIZ, 'tests', 'tabler', 'regua.php'), modo, arq], { encoding: 'utf8', cwd: L.RAIZ, maxBuffer: 1 << 26 });
/** A linha do aluno no resumo do ano de um componente (as etapas, a média anual, a frequência, a situação). */
async function resumo(p, oferta, rm) {
  await p.goto(L.BASE + `painel/escola-notas/etapas/${oferta}`);
  return p.$eval(`#resumoAno tr[data-rm="${rm}"]`, (tr) => Array.from(tr.querySelectorAll('td')).map((td) => td.textContent.replace(/\s+/g, ' ').trim())).catch(() => []);
}
const ofertaDe = (turma, materia) => um(`SELECT o.id FROM esc_turmas t INNER JOIN rm_cursos_turma_series s ON s.id = t.modulo_id INNER JOIN esc_turma_ofertas x ON x.turma_id = t.id
  INNER JOIN rm_professores_materias o ON o.id = x.oferta_id WHERE CONCAT(t.ano, '-', LEFT(s.codnome, 1), t.nome) COLLATE utf8mb4_unicode_ci = '${turma}' AND o.materia = '${materia}'`);

(async () => {
  const { navegador } = await L.abrir();
  try {
    console.log('\n== Onda 4, Etapa 4 — a demonstração da escola');

    /* A. pela tela, com um passo forçado a falhar */
    L.ok(instalarEscola(), 'uma instalação nova de escola');
    let adm = await entrar(navegador);
    await adm.p.goto(L.BASE + 'painel/demonstracao/comecar');
    await L.semErros(adm.p, 'Como você quer começar (escola)');
    const orig = fs.readFileSync(CENARIO, 'utf8');
    const quebra = "    public static function passoTroca(VcDemoContexto $c): void\n    {\n";
    /* o tempo de cada passo: do pedido à resposta, no navegador */
    const tempos = [];
    const inicioDe = new Map();
    adm.p.on('request', (q) => { if (/demonstracao\/montar/.test(q.url()) && /fase=passo/.test(q.postData() || '')) { inicioDe.set(q, Date.now()); } });
    adm.p.on('requestfinished', (q) => { if (inicioDe.has(q)) { tempos.push(Date.now() - inicioDe.get(q)); } });
    let falhou = false;
    let retomou = false;
    const t0 = Date.now();
    try {
      fs.writeFileSync(CENARIO, orig.replace(quebra, quebra + "        VcDemoPassos::falha('falha forçada pela prova');\n"));
      await adm.p.click('#montarDemonstracao');
      await adm.p.waitForSelector('#progressoMontagem[data-situacao="falhou"]', { timeout: 900000 });
      falhou = /falha forçada pela prova/.test(await adm.p.textContent('#progressoMontagem .vc-progresso-falha-motivo'));
      await L.print(adm.p, ETAPA, 'montagem-falhou');
    } finally { fs.writeFileSync(CENARIO, orig); }
    await adm.p.click('#progressoMontagem .vc-progresso-tentar');
    /* logo depois do clique a janela ainda diz "falhou": espera ela voltar a andar */
    await adm.p.waitForFunction(() => document.querySelector('#progressoMontagem').getAttribute('data-situacao') !== 'falhou', null, { timeout: 60000 }).catch(() => {});
    await adm.p.waitForSelector('#progressoMontagem[data-situacao="concluida"], #progressoMontagem[data-situacao="falhou"]', { timeout: 900000 });
    retomou = (await adm.p.getAttribute('#progressoMontagem', 'data-situacao')) === 'concluida';
    const duracao = Math.round((Date.now() - t0) / 1000);
    const maior = Math.max(0, ...tempos);
    L.ok(tempos.length >= 18 && maior < 120000, `cada passo num chamado curto: ${tempos.length} chamados, o mais longo em ${Math.round(maior / 1000)} s (o limite dos passos de hoje: 120 s)`);
    L.ok(falhou && retomou && fs.readFileSync(CENARIO, 'utf8') === orig, `pela tela, em passos: o passo forçado a falhar para a janela nele ("falha forçada"), e "Tentar de novo" segue até o fim (${duracao} s no total); o arquivo voltou`);
    const lento = sql("SELECT valor FROM adms_instalacao_estado WHERE chave = 'demonstracao.montagem'");
    L.ok(um("SELECT COUNT(*) FROM adms_instalacao_estado WHERE chave = 'demonstracao.ativa' AND valor = '1'") === 1 && lento === '', 'a demonstração ligada, e o estado da montagem saiu do banco (montagem concluída)');
    const rTela = regua('gravar', REGUA_TELA);
    L.ok(rTela.status === 0, `a régua da escola: o retrato da demonstração montada pela tela (${(rTela.stdout || '').trim()})`);

    /* B. o conteúdo */
    const ef = sql("SELECT GROUP_CONCAT(m.nome ORDER BY l.ordem SEPARATOR ', '), SUM(l.cargahoraria) FROM uni_rh_cursos_disciplinas_grade l INNER JOIN rm_materias m ON m.id = l.materia_id INNER JOIN rm_cursos_turma_series s ON s.id = l.modulo_id INNER JOIN rm_cursos c ON c.id = s.curso_id WHERE c.nome LIKE 'Ensino Fundamental%' AND s.codnome = '6º ano'").split('\t');
    const em = sql("SELECT COUNT(*), SUM(l.cargahoraria), SUM(IF(m.nome LIKE 'Itinerário%', l.cargahoraria, 0)) FROM uni_rh_cursos_disciplinas_grade l INNER JOIN rm_materias m ON m.id = l.materia_id INNER JOIN rm_cursos_turma_series s ON s.id = l.modulo_id INNER JOIN rm_cursos c ON c.id = s.curso_id WHERE c.nome LIKE 'Ensino Médio%' AND s.codnome = '1ª série'").split('\t');
    L.ok(/Língua Portuguesa, Arte, Educação Física, Língua Inglesa, Matemática, Ciências, Geografia, História, Ensino Religioso/.test(ef[0]) && +ef[1] >= 800,
      `a matriz de exemplo, Ensino Fundamental (6º ano): ${ef[0]} — ${ef[1]} h no ano (mínimo 800)`);
    L.ok(+em[0] === 14 && +em[1] === 1000 && +em[2] === 200, `Ensino Médio (1ª série): ${em[0]} componentes, ${em[1]} h no ano — a formação geral básica (800 h) e o itinerário de exemplo como componentes comuns (${em[2]} h)`);
    await adm.p.goto(L.BASE + 'painel/escola/index');
    L.ok(/exemplo que segue a Base Nacional Comum Curricular/.test(await adm.p.content()), 'a tela da estrutura diz que a matriz é um exemplo que segue a Base');
    await L.print(adm.p, ETAPA, 'estrutura-colegio');
    /* Onda 8 (D-70): a demonstração ganhou o curso livre, com a turma aberta dele; D-104: e o 8º ano A (6 turmas do colégio, 5 de 2026) */
    const turmas = sql("SELECT CONCAT(t.ano, ' ', s.codnome, ' ', t.nome) FROM esc_turmas t INNER JOIN rm_cursos_turma_series s ON s.id = t.modulo_id INNER JOIN rm_cursos c ON c.id = s.curso_id WHERE c.nome NOT LIKE 'Curso livre%' ORDER BY t.ano DESC, s.codnome, t.nome").split('\n');
    const turmaLivre = sql("SELECT CONCAT(t.ano, ' ', s.codnome, ' ', t.nome) FROM esc_turmas t INNER JOIN rm_cursos_turma_series s ON s.id = t.modulo_id INNER JOIN rm_cursos c ON c.id = s.curso_id WHERE c.nome LIKE 'Curso livre%'");
    L.ok(turmas.length === 6 && turmas.filter((x) => x.startsWith('2026')).length === 5 && turmaLivre === '2026 Turma única Turma aberta',
      `as turmas: ${turmas.join('; ')} (e a do curso livre: ${turmaLivre || 'nenhuma'})`);
    const fech = um(`SELECT COUNT(DISTINCT e.ordem) FROM esc_fechamentos f INNER JOIN esc_etapas e ON e.id = f.etapa_id WHERE f.situacao = 'vigente' AND e.ano = 2026`);
    const comNota3 = um("SELECT COUNT(*) FROM rm_disciplina_atividade a WHERE a.nomeAtividade = 'Avaliação do 3º bimestre'");
    L.ok(fech === 2 && comNota3 > 0, `o ano corrente: 2 bimestres fechados; o 3º em andamento, com nota (${comNota3} avaliações)`);
    /* os casos */
    const mat6A = ofertaDe('2026-6A', 'Matemática');
    const salva = await resumo(adm.p, mat6A, '265002');
    const naoSalva = await resumo(adm.p, mat6A, '265003');
    await L.print(adm.p, ETAPA, 'etapas-matematica-6a');
    const mat25 = ofertaDe('2025-9A', 'Matemática');
    const apr = await resumo(adm.p, mat25, '255001');
    const falta = await resumo(adm.p, mat25, '255003');
    const cons = await resumo(adm.p, mat25, '255004');
    L.ok(/^6,5 R/.test(salva[1] || '') && /^4,5 R/.test(naoSalva[1] || '') && /^4,5 R/.test(naoSalva[2] || ''),
      `a recuperação que salvou (4,0 → recuperação 6,5: ${salva[1]}) e a que não salvou (4,0 → 4,5: ${naoSalva[1]}; ${naoSalva[2]})`);
    L.ok(apr[7] === 'Aprovado' && falta[7] === 'Reprovado por falta' && cons[7] === 'Aprovado pelo conselho' && /^[4-5],/.test(cons[5] || ''),
      `o ano anterior: aprovado direto; reprovado por falta com nota (média ${falta[5]}, frequência ${falta[6]}); aprovado pelo conselho (média ${cons[5]}, que não muda)`);
    const trazido = um("SELECT COUNT(*) FROM esc_lancamentos_trazidos WHERE rm = '265004' AND oferta_destino_id IS NOT NULL");
    L.ok(trazido > 0 && um("SELECT COUNT(*) FROM esc_turma_alunos a INNER JOIN esc_turmas t ON t.id = a.turma_id WHERE a.rm = '265004' AND a.saida IS NULL AND t.nome = 'B'") === 1,
      `o aluno que trocou do 6º ano A para o B (as notas do 1º bimestre foram junto: ${trazido} componente(s))`);
    const nomes = sql("SELECT GROUP_CONCAT(nome SEPARATOR '|') FROM col_usuarios WHERE rm BETWEEN 255001 AND 265099");
    L.ok(sql("SELECT nome FROM adms_empresas WHERE instituicao = 1") === 'Colégio Horizonte (fictício)' && nomes.split('|').every((n) => /Exemplo|Fictício|Modelo|Inventado|Amostra|Protótipo|Simulada|Hipotético/.test(n)),
      'os nomes fictícios marcados: o colégio "(fictício)", os segmentos "(demonstração)", os alunos com sobrenome inventado');
    await adm.p.goto(L.BASE + `painel/academicos/boletim/265002`);
    await L.semErros(adm.p, 'o boletim de um aluno da demonstração');
    await L.print(adm.p, ETAPA, 'boletim-265002');

    /* C. a remoção pela tela */
    await adm.p.goto(L.BASE + 'painel/demonstracao/instituicao');
    await adm.p.fill('#confirmacao', 'COMEÇAR');
    await adm.p.click('#removerDemonstracao');
    await adm.p.waitForSelector('#progressoRemocao[data-situacao="concluida"], #progressoRemocao[data-situacao="falhou"]', { timeout: 600000 });
    const sobra = sql(`SELECT CONCAT((SELECT COUNT(*) FROM esc_turmas), '|', (SELECT COUNT(*) FROM esc_turma_alunos), '|', (SELECT COUNT(*) FROM esc_fechamentos), '|', (SELECT COUNT(*) FROM esc_conselho), '|',
      (SELECT COUNT(*) FROM rm_cursos WHERE nome LIKE '%(demonstração)'), '|', (SELECT COUNT(*) FROM col_usuarios WHERE rm BETWEEN 255001 AND 265099), '|', (SELECT COUNT(*) FROM esc_etapas), '|',
      (SELECT COUNT(*) FROM rm_materias WHERE descricao IN ('Disciplina da demonstração', 'Componente curricular da demonstração')), '|', (SELECT COUNT(*) FROM adms_empresas WHERE nome = 'Colégio Horizonte (fictício)'))`);
    L.ok(sobra === '0|0|0|0|0|0|0|0|0' && (await adm.p.getAttribute('#progressoRemocao', 'data-situacao')) === 'concluida',
      `a remoção pela tela: nada da demonstração fica (turmas|alunos na turma|fechamentos|conselho|segmentos|alunos|etapas|componentes|escola = ${sobra})`);
    await adm.ctx.close();

    /* D. pela linha de comando, numa instalação nova: a mesma régua */
    const rodarCli = () => {
      instalarEscola();
      /* o comando do produto (o mesmo que o guia ensina), com o administrador pela entrada padrão */
      return spawnSync(PHP, [path.join(L.RAIZ, 'database', 'ferramentas', 'demonstracao.php'), 'montar', L.BASE], { encoding: 'utf8', cwd: L.RAIZ, env: process.env, input: `administrador\n${SENHA}\n`, maxBuffer: 1 << 26 });
    };
    let cli = rodarCli();
    L.ok(cli.status === 0, `pela linha de comando (${(cli.stdout || '').match(/A demonstração foi montada[^\n]*/) || (cli.stdout || cli.stderr || '').slice(-300)})`);
    let cmp = regua('comparar', REGUA_TELA);
    L.ok(cmp.status === 0 && /IDÊNTICA/.test(cmp.stdout), `a demonstração pela linha de comando: a régua IDÊNTICA à da tela (${(cmp.stdout || '').trim().split('\n').pop()})`);
    /* sabotagem: o passo do conselho esquecido — a régua difere */
    const orig2 = fs.readFileSync(CENARIO, 'utf8');
    let reprovou = false;
    try {
      const de = "    public static function passoConselho(VcDemoContexto $c): void\n    {\n";
      if (!orig2.includes(de)) { throw new Error('sabotagem sem o trecho'); }
      fs.writeFileSync(CENARIO, orig2.replace(de, de + "        return;\n"));
      cli = rodarCli();
      cmp = regua('comparar', REGUA_TELA);
      reprovou = cmp.status !== 0 && /esc_conselho/.test(cmp.stdout);
    } finally { fs.writeFileSync(CENARIO, orig2); }
    L.ok(reprovou && fs.readFileSync(CENARIO, 'utf8') === orig2, 'sabotagem (a montagem que esquece o conselho de classe): a régua da escola reprova; o arquivo voltou');
    cli = rodarCli(); // deixa o banco com a demonstração inteira
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message);
  } finally {
    await navegador.close();
  }
  process.exit(L.resumo('onda4/etapa4') ? 0 : 1);
})();
