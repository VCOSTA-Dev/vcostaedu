/*
 * ONDA 4, ETAPA 1 — o tipo de instituição (docs/ESCOLA.md, D-45).
 *   A. ENSINO SUPERIOR — a instalação da versão anterior atualizada (o banco de prova com a demonstração, levado à versão
 *      nova pelo comando): é ensino superior; a régua IDÊNTICA à da 1.37.0 nos dados acadêmicos (só as tabelas de
 *      configuração da etapa diferem, de propósito); o menu sem a estrutura da escola e com as palavras de sempre; a rota
 *      da escola recusada; o tipo em Avisos do sistema e no Sobre; com a demonstração montada, o tipo não troca (a tela
 *      diz por quê) e o POST forjado é recusado;
 *   B. ESCOLA — a instalação nova escolhendo "escola" no instalador: o tipo gravado; o menu no vocabulário da escola
 *      (Segmentos, Componentes…), com a estrutura da escola e sem as telas do superior (atos legais, histórico, TCC e os
 *      relatórios de conclusão e coorte), que a rota também recusa; o título das telas no vocabulário; o tipo em Avisos e
 *      no Sobre; a demonstração recusada (ainda não há a da escola); sem dado próprio, o tipo troca para superior e volta;
 *      com dado próprio, a troca é recusada, com a razão, pela tela e pelo POST forjado;
 *   C. sabotagens: o roteador sem o filtro do módulo (a rota do superior abre na escola) e a troca sem a conferência do
 *      dado próprio — a prova reprova as duas; os arquivos voltam.
 *
 *   node tests/tabler/onda4/etapa1.js   (no fim, o banco de prova é uma instalação NOVA de escola: rode o ciclo depois)
 */
const fs = require('fs');
const path = require('path');
const { execFileSync, spawnSync } = require('child_process');
const L = require('../lib');

const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste$/.test(BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
const MYSQL = process.env.VCOSTAEDU_MYSQL || 'C:/xampp/mysql/bin/mysql.exe';
const PHP = process.env.VCOSTAEDU_PHP || 'C:/xampp/php/php.exe';
const sql = (q) => execFileSync(MYSQL, ['-u', 'root', '-N', '--default-character-set=utf8mb4', BANCO, '-e', q]).toString().replace(/\r/g, '').trim();
const ETAPA = 'onda4/etapa1';
const SENHA = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const PAGINAS = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'AdmsPaginas.php');
const DEMO = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Controllers', 'Demonstracao.php');
const REGUA_ANTERIOR = path.join(L.RAIZ, 'tests', 'tabler', 'saida', 'regua-onda3-etapa11.json');
/* as tabelas de configuração que a onda muda de propósito (D-46): as desta etapa e as das seguintes */
const CONFIG_DA_ETAPA = 'adms_versoes,adms_novidades,adms_paginas,adms_nivacs_pgs,adms_acoes,adms_instalacao_estado,adms_paginas_modulo,adms_fases,adms_paginas_recurso,col_paginas_modulo,rm_media_regras,rm_media_regra_versoes,rm_media_regra_componentes,rm_media_regra_acrescimo,adms_parametros,col_niveis_acessos,col_paginas,col_nivacs_pgs'; // + as regras prontas do catálogo (a 0029)
const SO_SUPERIOR = ['atos-legais/listar', 'historico-emissoes/listar', 'historico-modelos/listar', 'relatorios/coorte-ingresso', 'relatorios/aptos-concluir'];

async function entrar(navegador) {
  const ctx = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' });
  const p = await L.pagina(ctx);
  await p.goto(L.BASE + 'painel/login/acesso');
  await p.fill('input[name="usuario"]', 'administrador');
  await p.fill('input[name="senha"]', SENHA);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
  return { ctx, p };
}
/** A rota abre? (a recusa mostra, no mesmo endereço, a página padrão: o formulário de entrada) */
async function abre(p, rota) {
  const antes = p._erros.length;
  const resp = await p.goto(L.BASE + 'painel/' + rota);
  const abriu = p.url().includes('/painel/' + rota) && !(await p.$('input[name="SendLogin"]')) && !(await p.$('#semAcesso'));
  /* Onda 8, correção (D-86): desde a 1.66.0 (D-76) a rota recusada responde 403 com a página sem acesso, e o navegador
     anota no console o 403 DESSE documento ("Failed to load resource … 403"). Esse aviso é a própria recusa que a prova
     espera: sai da conta só ele, só nesta navegação, e só quando a recusa veio com o 403 e a página sem acesso; qualquer
     outro erro fica para o semErros seguinte. */
  if (!abriu && resp && resp.status() === 403 && (await p.$('#semAcesso'))) {
    const novos = p._erros.splice(antes);
    let tirou = false;
    p._erros.push(...novos.filter((x) => { if (!tirou && /Failed to load resource: .*status of 403/.test(x)) { tirou = true; return false; } return true; }));
  }
  return abriu;
}
const menu = async (p) => (await L.textoDaTela(p)).menu;
/** POST forjado (sem a tela) para gravar o tipo, com a sessão da página. */
async function forjarTipo(p, tipo) {
  return p.evaluate(async ([u, t]) => {
    const f = new FormData(); f.append('tipo', t);
    await fetch(u, { method: 'POST', body: f, credentials: 'same-origin' });
  }, [L.BASE + 'painel/demonstracao/tipo', tipo]);
}
const tipoNoBanco = () => sql("SELECT valor FROM adms_instalacao_estado WHERE chave = 'instituicao.tipo'");
async function sabotar(nome, arquivo, de, para, conferir) {
  const orig = fs.readFileSync(arquivo, 'utf8');
  let reprovou = false;
  try {
    if (!orig.includes(de)) { throw new Error('sabotagem sem o trecho: ' + nome); }
    fs.writeFileSync(arquivo, orig.replace(de, para));
    reprovou = !(await conferir());
  } finally { fs.writeFileSync(arquivo, orig); }
  L.ok(reprovou && fs.readFileSync(arquivo, 'utf8') === orig, `sabotagem (${nome}): a prova reprova o defeito; o arquivo voltou`);
}

(async () => {
  const { navegador } = await L.abrir();
  try {
    console.log('\n== Onda 4, Etapa 1 — o tipo de instituição');

    /* A. o ensino superior: a instalação anterior, atualizada */
    const migrar = spawnSync(PHP, [path.join(L.RAIZ, 'bin', 'migrar.php'), 'aplicar', '--sim'], { encoding: 'utf8', cwd: L.RAIZ });
    L.ok(migrar.status === 0 && tipoNoBanco() === 'superior', `a instalação anterior, atualizada pelo comando, é de ensino superior (${tipoNoBanco()})`);
    const r = spawnSync(PHP, [path.join(L.RAIZ, 'tests', 'tabler', 'regua.php'), 'comparar', REGUA_ANTERIOR, '--config=' + CONFIG_DA_ETAPA], { encoding: 'utf8', cwd: L.RAIZ, maxBuffer: 1 << 26 });
    const saidaRegua = (r.stdout || '').trim().split('\n').slice(-3).join(' | ');
    L.ok(r.status === 0 && /IDÊNTICA/.test(r.stdout), `a régua do ensino superior, contra a da 1.37.0: ${saidaRegua.slice(0, 400)}`);

    let adm = await entrar(navegador);
    let m = await menu(adm.p);
    L.ok(!/Estrutura da escola/.test(m) && /Cursos/.test(m) && /Atos legais/.test(m) && !/Segmentos|Componentes curriculares/.test(m),
      'superior: o menu com as palavras de sempre (Cursos, Atos legais), sem a estrutura da escola');
    L.ok(!(await abre(adm.p, 'escola/index')), 'superior: a rota da escola é recusada');
    await adm.p.goto(L.BASE + 'painel/avisos-sistema/index');
    await L.semErros(adm.p, 'superior: Avisos do sistema');
    L.ok((await adm.p.textContent('#tipoInstituicao').catch(() => '')).trim() === 'Ensino superior', 'superior: Avisos do sistema diz o tipo');
    await adm.p.goto(L.BASE + 'sobre.php');
    L.ok((await adm.p.textContent('#tipoInstituicao').catch(() => '')).trim() === 'Ensino superior', 'superior: o Sobre, para quem entrou, diz o tipo');
    await adm.p.goto(L.BASE + 'painel/demonstracao/instituicao');
    await L.semErros(adm.p, 'superior: Começar com os dados da minha instituição');
    const podeDemo = await adm.p.getAttribute('#cartaoTipo', 'data-pode');
    const motivoDemo = (await adm.p.textContent('#tipoNaoMuda').catch(() => '')).trim();
    await L.print(adm.p, ETAPA, 'superior-tipo-com-demonstracao');
    await forjarTipo(adm.p, 'escola');
    L.ok(podeDemo === '0' && /Remova a demonstração/.test(motivoDemo) && tipoNoBanco() === 'superior',
      `superior com a demonstração: o tipo não troca ("${motivoDemo.slice(0, 70)}…"), e o POST forjado é recusado`);
    await adm.ctx.close();

    /* B. a escola: a instalação nova */
    const reset = spawnSync(PHP, [path.join(L.RAIZ, 'tests', 'tabler', 'reset.php')], { encoding: 'utf8', cwd: L.RAIZ });
    const inst = spawnSync('node', [path.join(L.RAIZ, 'tests', 'tabler', 'instalar.js'), 'normal'], { encoding: 'utf8', cwd: L.RAIZ,
      env: { ...process.env, VCOSTAEDU_TIPO: 'escola', VCOSTAEDU_ETAPA_PROVA: ETAPA }, maxBuffer: 1 << 26 });
    const resumoInst = (inst.stdout || '').split('\n').filter((l) => /OK, \d+ FALHA|\[FALHA\]/.test(l)).join(' | ');
    L.ok(reset.status === 0 && inst.status === 0 && tipoNoBanco() === 'escola', `a instalação nova, escolhendo "escola" no instalador (${resumoInst.slice(0, 200)}): o tipo gravado é ${tipoNoBanco()}`);

    adm = await entrar(navegador);
    m = await menu(adm.p);
    const semSuperior = !/Atos legais|Históricos emitidos|Modelos de histórico|Coorte de ingresso|Aptos a concluir/.test(m);
    L.ok(/Estrutura da escola/.test(m) && /Segmentos/.test(m) && !/\bCursos\b/.test(m) && semSuperior,
      `escola: o menu no vocabulário da escola (Segmentos), com a Estrutura da escola, sem as telas do ensino superior`);
    await L.print(adm.p, ETAPA, 'escola-inicio');
    const abriram = [];
    for (const rota of SO_SUPERIOR) { if (await abre(adm.p, rota)) { abriram.push(rota); } }
    L.ok(abriram.length === 0, `escola: as rotas só do ensino superior são recusadas (${SO_SUPERIOR.length})${abriram.length ? ' — abriram: ' + abriram.join(', ') : ''}`);
    L.ok(await abre(adm.p, 'escola/index') && await adm.p.isVisible('#semSegmentos'), 'escola: a Estrutura da escola abre (ainda sem segmento)');
    await L.semErros(adm.p, 'escola: Estrutura da escola');
    await L.print(adm.p, ETAPA, 'escola-estrutura');
    await adm.p.goto(L.BASE + 'painel/cursos/listar');
    await L.semErros(adm.p, 'escola: Segmentos');
    const titulo = (await adm.p.textContent('.page-title')).trim();
    L.ok(/Segmentos/.test(titulo) && !/Cursos/.test(titulo), `escola: o título da tela no vocabulário da escola ("${titulo}")`);
    await adm.p.goto(L.BASE + 'painel/relatorios/listar');
    const hub = await adm.p.textContent('.page-body');
    L.ok(!/Coorte de ingresso|Aptos a concluir|Situação do TCC|Previsão de conclusão|Concluintes/.test(hub) && /Mapa de notas/.test(hub), 'escola: os relatórios só do ensino superior não aparecem; os outros, sim');
    await adm.p.goto(L.BASE + 'painel/avisos-sistema/index');
    L.ok((await adm.p.textContent('#tipoInstituicao').catch(() => '')).trim() === 'Escola (educação básica)', 'escola: Avisos do sistema diz o tipo');
    await adm.p.goto(L.BASE + 'sobre.php');
    L.ok((await adm.p.textContent('#tipoInstituicao').catch(() => '')).trim() === 'Escola (educação básica)', 'escola: o Sobre, para quem entrou, diz o tipo');
    /* a demonstração: desde a Onda 4, Etapa 4, a escola tem a dela (o colégio fictício; a prova da montagem é a etapa4.js) */
    await adm.p.goto(L.BASE + 'painel/demonstracao/comecar');
    L.ok(await adm.p.isVisible('#montarDemonstracao') && !(await adm.p.$('#semDemonstracaoEscola')), 'escola: "Conhecer com dados de demonstração" está disponível (o colégio fictício)');
    /* a troca, sem dado próprio */
    await adm.p.goto(L.BASE + 'painel/demonstracao/instituicao');
    await L.semErros(adm.p, 'escola: o tipo, sem dado próprio');
    const pode = await adm.p.getAttribute('#cartaoTipo', 'data-pode');
    await L.print(adm.p, ETAPA, 'escola-tipo-sem-dados');
    await adm.p.click('label:has(#tipo_superior)');
    await Promise.all([adm.p.waitForNavigation(), adm.p.click('#gravarTipo')]);
    const virouSuperior = tipoNoBanco() === 'superior' && await adm.p.isVisible('#tipoGravado') && /Atos legais/.test(await menu(adm.p));
    await adm.p.click('label:has(#tipo_escola)');
    await Promise.all([adm.p.waitForNavigation(), adm.p.click('#gravarTipo')]);
    L.ok(pode === '1' && virouSuperior && tipoNoBanco() === 'escola' && /Estrutura da escola/.test(await menu(adm.p)),
      'sem dado próprio, o tipo troca pela tela: para ensino superior (o menu muda) e de volta para escola');
    /* com dado próprio: recusada */
    sql("INSERT INTO rm_materias (nome, abreviacao, carga_horaria, situacao, usuario_cre) VALUES ('Matemática (prova)', 'MAT', '160', 1, 1)");
    await adm.p.goto(L.BASE + 'painel/demonstracao/instituicao');
    const podeDepois = await adm.p.getAttribute('#cartaoTipo', 'data-pode');
    const motivo = (await adm.p.textContent('#tipoNaoMuda').catch(() => '')).trim();
    await L.print(adm.p, ETAPA, 'escola-tipo-com-dados');
    await forjarTipo(adm.p, 'superior');
    const recusadaForjada = tipoNoBanco() === 'escola';
    L.ok(podeDepois === '0' && /dados próprios/.test(motivo) && recusadaForjada, `com dado próprio, o tipo não troca: a tela diz por quê ("${motivo.slice(0, 60)}…") e o POST forjado é recusado`);

    /* C. sabotagens */
    await sabotar('o roteador sem o filtro do módulo', PAGINAS,
      "if ($this->resultado && !\\App\\adms\\Models\\helper\\AdmsTipoInstituicao::paginaLigada((int) $this->resultado[0]['id'])) {",
      'if (false) {', async () => !(await abre(adm.p, 'atos-legais/listar')));
    await sabotar('a troca do tipo sem a conferência do dado próprio', DEMO, "} elseif (!$troca['pode']) {", '} elseif (false) {',
      async () => { await forjarTipo(adm.p, 'superior'); const ficou = tipoNoBanco() === 'escola'; sql("UPDATE adms_instalacao_estado SET valor = 'escola' WHERE chave = 'instituicao.tipo'"); return ficou; });
    sql("DELETE FROM rm_materias WHERE nome = 'Matemática (prova)'");
    await adm.ctx.close();
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message);
  } finally {
    await navegador.close();
  }
  process.exit(L.resumo('onda4/etapa1') ? 0 : 1);
})();
