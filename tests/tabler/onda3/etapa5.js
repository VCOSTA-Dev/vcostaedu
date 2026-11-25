/*
 * ONDA 3, ETAPA 5 — a marca, os textos, o padrão visual e a demonstração nova.
 *   A. o topo do menu numa linha: o selo do VCOSTAEDU e, ao lado, a instituição (o nome em até duas linhas ou o logo, à
 *      escolha); sem instituição configurada, "VCOSTAEDU"; recolhido, só o selo; o nome longo não empurra o botão;
 *   B. o rodapé: a marca VCOSTAEDU e a palavra VCOSTA em imagem (reduzidas; a de texto branco no tema escuro);
 *   C. as faixas: nunca duas empilhadas (a demonstração e a atualização pendente numa linha só); a da atualização só
 *      para quem atualiza;
 *   D. a tela da disciplina: abas com a atual destacada, um caminho de volta (a seta discreta do título), os atalhos em
 *      cartões (Alunos, Notas e resultado, Atividades, Faltas), sem rótulo vazio, sem o nome solto da instituição;
 *   E. rótulo sem valor não aparece nas telas de detalhe (a varredura);
 *   F. o × em toda mensagem; G. o contraste dos botões verdes e das etiquetas claras;
 *   H. a demonstração: a faculdade fictícia genérica (os números iguais: só texto mudou), o administrador com nome
 *      fictício (e o nome dele de volta na remoção); I. as ações com nomes que dizem o que fazem; J. o polo Sede.
 *   Sabotagens: sem o × da sessão; as faixas empilhadas de novo; o nome longo sem o corte.
 *
 *   node tests/tabler/onda3/etapa5.js     (depois do ciclo, com a demonstração)
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
const um = (q) => +sql(q);
let cred = JSON.parse(fs.readFileSync(path.join(__dirname, '..', 'saida', 'credenciais.json'), 'utf8'));
const ETAPA = 'onda3/etapa5';
const SENHA = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const PENDENTE = path.join(L.RAIZ, 'database', 'migrations', '0098_prova_pendente.sql');
const VIEW = path.join(L.RAIZ, 'painel', 'core', 'ConfigView.php');
const AVISO = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Views', 'include_tabler', '_aviso_atualizacao.php');
const CSS = path.join(L.RAIZ, 'assets', 'css', 'vcostaedu-tabler.css');

/* o retrato atual da onda (o da etapa mais nova): o banco montado pelo ciclo tem todas as migrations */
const REGUA_ATUAL = L.reguaAtual(); // o retrato mais novo, de qualquer onda (lib.js)
async function entrar(navegador, usuario, senha, largura = 1366) {
  const ctx = await navegador.newContext({ viewport: { width: largura, height: 900 }, locale: 'pt-BR' });
  const p = await L.pagina(ctx);
  await p.goto(L.BASE + 'painel/login/acesso');
  await p.fill('input[name="usuario"]', usuario);
  await p.fill('input[name="senha"]', senha || cred.painel[usuario]);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
  return { ctx, p };
}
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
/* o contraste (WCAG) entre duas cores rgb()/rgba() já resolvidas pelo navegador */
const CONTRASTE = `(a, b) => { const c = (s) => s.match(/[\\d.]+/g).slice(0, 3).map(Number).map((v) => { v /= 255; return v <= .03928 ? v / 12.92 : ((v + .055) / 1.055) ** 2.4; });
  const l = (s) => { const [r, g, bb] = c(s); return .2126 * r + .7152 * g + .0722 * bb; }; const x = l(a), y = l(b); return (Math.max(x, y) + .05) / (Math.min(x, y) + .05); }`;
const topo = (p) => p.evaluate(() => {
  const t = document.querySelector('#topoMenu'); const n = t && t.querySelector('.vc-side-nome');
  const r = (e) => e ? e.getBoundingClientRect() : null; const b = r(document.querySelector('#vc-side-recolher')); const nn = r(n);
  return { tipo: t && t.dataset.tipo, texto: n ? n.innerText.trim() : '', title: n ? n.getAttribute('title') : '', logo: !!(n && n.querySelector('img')),
    visivel: !!(nn && nn.width > 0 && getComputedStyle(n).display !== 'none'), linhas: nn ? Math.round(nn.height / parseFloat(getComputedStyle(n).lineHeight || 16)) : 0,
    selo: !!document.querySelector('#topoMenu .vc-side-badge img'), sobrepoe: !!(b && nn && nn.width > 0 && nn.right > b.left + 1), blocoAntigo: !!document.querySelector('.vc-side-inst, .vc-side-instituicao'),
    altura: Math.round(r(document.querySelector('.vc-side-topo')).height) };
});

(async () => {
  /* a prova pede a demonstração montada pelo ciclo (os ids da régua): uma prova anterior que a removeu (a jornada 4)
     não a deixa sem ela — o banco é refeito do zero */
  if (sql("SELECT valor FROM adms_instalacao_estado WHERE chave = 'demonstracao.ativa'") !== '1') {
    const c = spawnSync(PHP, [path.join(L.RAIZ, 'tests', 'tabler', 'ciclo.php'), 'comparar', REGUA_ATUAL], { encoding: 'utf8', cwd: L.RAIZ, env: process.env, maxBuffer: 1 << 26 });
    if (c.status !== 0) { console.error('o ciclo não refez a demonstração'); process.exit(1); }
    cred = JSON.parse(fs.readFileSync(path.join(__dirname, '..', 'saida', 'credenciais.json'), 'utf8')); // as senhas novas do ciclo
  }
  const { navegador } = await L.abrir();
  const nomeInst = sql("SELECT nome FROM adms_empresas WHERE instituicao = 1 ORDER BY id LIMIT 1");
  try {
    console.log('\n== Onda 3, Etapa 5');
    let { ctx, p } = await entrar(navegador, 'administrador', SENHA);

    /* A. o topo do menu */
    let t = await topo(p);
    L.ok(t.selo && t.tipo === 'nome' && t.texto === nomeInst && t.title === nomeInst && !t.sobrepoe && !t.blocoAntigo && t.linhas <= 2,
      `o topo numa linha: o selo e o nome da instituição ("${t.texto}", ${t.linhas} linha(s), altura ${t.altura} px), sem passar por baixo do botão de recolher, sem o bloco de baixo`);
    await p.screenshot({ path: path.join(L.PROVAS, ETAPA, 'topo-nome-aberto.png'), clip: { x: 0, y: 0, width: 420, height: 90 } }).catch(async () => { fs.mkdirSync(path.join(L.PROVAS, ETAPA), { recursive: true }); await p.screenshot({ path: path.join(L.PROVAS, ETAPA, 'topo-nome-aberto.png'), clip: { x: 0, y: 0, width: 420, height: 90 } }); });
    await p.click('#vc-side-recolher'); await p.mouse.move(900, 500); await p.waitForTimeout(400);
    t = await topo(p);
    L.ok(t.selo && !t.visivel, 'recolhido o menu, só o selo');
    await p.screenshot({ path: path.join(L.PROVAS, ETAPA, 'topo-recolhido.png'), clip: { x: 0, y: 0, width: 420, height: 90 } });
    await p.mouse.move(30, 30); await p.click('#vc-side-recolher'); await p.mouse.move(900, 500); await p.waitForTimeout(300);
    /* o nome muito longo: duas linhas e reticências, sem empurrar o botão */
    /* (sem acento no SQL: o acento não passa pela linha de comando do Windows; o original volta por dentro do banco) */
    const longo = 'Instituto Superior de Ensino com um Nome Realmente Muito Comprido para o Topo do Menu';
    sql('DROP TABLE IF EXISTS prova_etapa5_nome; CREATE TABLE prova_etapa5_nome AS SELECT id, nome FROM adms_empresas WHERE instituicao = 1');
    sql(`UPDATE adms_empresas SET nome = '${longo}' WHERE instituicao = 1`);
    const longoOk = async () => { await p.goto(L.BASE + 'painel/home/index'); const x = await topo(p); return x.title === longo && x.linhas <= 2 && !x.sobrepoe; };
    try {
      L.ok(await longoOk(), 'o nome comprido cabe em até duas linhas (a fonte menor) e corta com reticências, sem empurrar o botão; o nome inteiro fica ao passar o mouse');
      await p.screenshot({ path: path.join(L.PROVAS, ETAPA, 'topo-nome-longo.png'), clip: { x: 0, y: 0, width: 420, height: 90 } });
      await sabotar('o nome longo sem o corte das duas linhas', CSS, 'display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; text-overflow: ellipsis;', 'display: block; -webkit-line-clamp: none; overflow: visible;', longoOk);
    } finally { sql('UPDATE adms_empresas e JOIN prova_etapa5_nome o ON o.id = e.id SET e.nome = o.nome; DROP TABLE prova_etapa5_nome'); }
    /* o logo, à escolha */
    sql("UPDATE adms_empresas SET topo_menu = 'logo' WHERE instituicao = 1");
    try {
      await p.goto(L.BASE + 'painel/home/index');
      t = await topo(p);
      L.ok(t.tipo === 'logo' && t.logo && t.title === nomeInst && !t.sobrepoe, 'com "o logo" escolhido em Dados da instituição, o logo ao lado do selo (o nome no texto alternativo e ao passar o mouse)');
      await p.screenshot({ path: path.join(L.PROVAS, ETAPA, 'topo-logo-aberto.png'), clip: { x: 0, y: 0, width: 420, height: 90 } });
    } finally { sql("UPDATE adms_empresas SET topo_menu = 'nome' WHERE instituicao = 1"); }
    /* sem instituição configurada */
    sql('UPDATE adms_empresas SET situacao = 2 WHERE instituicao = 1');
    try {
      await p.goto(L.BASE + 'painel/home/index');
      t = await topo(p);
      L.ok(t.tipo === 'vcostaedu' && t.texto === 'VCOSTAEDU', 'sem instituição configurada, o selo e "VCOSTAEDU"');
      await p.screenshot({ path: path.join(L.PROVAS, ETAPA, 'topo-sem-instituicao.png'), clip: { x: 0, y: 0, width: 420, height: 90 } });
    } finally { sql('UPDATE adms_empresas SET situacao = 1 WHERE instituicao = 1'); }
    await p.goto(L.BASE + 'painel/dados-instituicao/editar');
    const opcoes = await p.$$eval('#topo_menu option', (o) => o.map((x) => x.value));
    L.ok(JSON.stringify(opcoes) === '["nome","logo"]' && !(await p.$('#logo_compacto')) && !!(await p.$('#icone_aba')),
      'Dados da instituição: a escolha do topo (o nome ou o logo) e do ícone da aba; sem o logo compacto');

    /* B. o rodapé */
    await p.goto(L.BASE + 'painel/home/index');
    const rod = async () => p.evaluate(() => [...document.querySelectorAll('.vc-legal img')].map((i) => ({ src: i.getAttribute('src').split('/').pop(), alt: i.alt, h: Math.round(i.getBoundingClientRect().height), nat: i.naturalHeight, vis: getComputedStyle(i).display !== 'none' })));
    const claro = await rod();
    const vcosta = claro.filter((i) => i.alt === 'VCOSTA');
    L.ok(vcosta.length === 2 && vcosta.find((i) => i.vis).src === 'vcosta-wordmark.png' && vcosta.every((i) => i.nat <= 28) && vcosta.find((i) => i.vis).h <= 14
      && /Copyright © 2019-20\d\d/.test(await p.textContent('.vc-legal')) && claro.some((i) => i.alt === 'VCOSTAEDU' && i.vis),
      `o rodapé: a marca VCOSTAEDU e, no copyright, a palavra VCOSTA em imagem (a de texto preto no tema claro, ${vcosta.find((i) => i.vis).h} px de altura, arquivo reduzido de ${vcosta.map((i) => i.nat).join('/')} px; o de 1400 px não é carregado)`);
    await p.screenshot({ path: path.join(L.PROVAS, ETAPA, 'rodape-claro.png'), clip: { x: 240, y: 850, width: 1126, height: 50 } });
    await p.evaluate(() => document.documentElement.setAttribute('data-bs-theme', 'dark'));
    const escuro = (await rod()).filter((i) => i.alt === 'VCOSTA' && i.vis);
    L.ok(escuro.length === 1 && escuro[0].src === 'vcosta-wordmark-branco.png', 'no tema escuro, a de texto branco');
    await p.screenshot({ path: path.join(L.PROVAS, ETAPA, 'rodape-escuro.png'), clip: { x: 240, y: 850, width: 1126, height: 50 } });
    await p.evaluate(() => document.documentElement.removeAttribute('data-bs-theme'));

    /* C. as faixas */
    const faixas = async (pp) => pp.evaluate(() => ({ n: document.querySelectorAll('.vc-aviso-atualizacao, .vc-faixa-demo').length, atual: !!document.querySelector('#avisoAtualizacao'),
      dentro: !!document.querySelector('#faixaDemonstracao #avisoAtualizacao'), h: Math.round((document.querySelector('#faixaDemonstracao') || { getBoundingClientRect: () => ({ height: 0 }) }).getBoundingClientRect().height) }));
    fs.writeFileSync(PENDENTE, 'SELECT 1;\n');
    try {
      const medidas = [];
      for (const w of [1366, 390]) {
        await p.setViewportSize({ width: w, height: 900 }); await p.goto(L.BASE + 'painel/home/index');
        medidas.push([w, await faixas(p)]);
        await p.screenshot({ path: path.join(L.PROVAS, ETAPA, `faixa-unica-${w}.png`), clip: { x: 0, y: 0, width: w, height: 120 } });
      }
      await p.setViewportSize({ width: 1366, height: 900 });
      L.ok(medidas.every(([, m]) => m.n === 1 && m.dentro && m.h <= 40), `a demonstração e a atualização pendente numa faixa só, de uma linha (${medidas.map(([w, m]) => `${w} px de largura: ${m.h} px de altura`).join('; ')} — antes, 85 e 186 px, duas faixas)`);
      const sec = await entrar(navegador, 'secretaria.demo');
      const fs2 = await faixas(sec.p);
      L.ok(fs2.n === 1 && !fs2.atual, 'a secretaria (que não atualiza o sistema) não vê o aviso da atualização');
      await sec.ctx.close();
      await sabotar('as duas faixas empilhadas de novo', AVISO, '    && !\\App\\adms\\Models\\helper\\AdmsAmbiente::demonstracaoAtiva()):', '):', async () => { await p.goto(L.BASE + 'painel/home/index'); return (await faixas(p)).n === 1; });
    } finally { if (fs.existsSync(PENDENTE)) { fs.unlinkSync(PENDENTE); } }

    /* D. a tela da disciplina */
    await p.goto(L.BASE + 'painel/disciplinas/visualizar/1');
    const d = await p.evaluate(() => ({
      abas: [...document.querySelectorAll('.vc-abas .nav-link')].map((a) => a.innerText.trim()), ativa: (document.querySelector('.vc-abas .nav-link.active') || {}).innerText,
      voltas: [...document.querySelectorAll('.page-body a')].filter((a) => /disciplinas\/listar|minhas-disciplinas\/listar/.test(a.href)).length,
      seta: Math.round(document.querySelector('.page-title > a.btn.rounded-circle').getBoundingClientRect().width),
      cartoes: [...document.querySelectorAll('#atalhosDisciplina .card')].map((c) => c.querySelector('.fw-semibold').innerText.trim()),
      alunos: (document.querySelector('#cartaoAlunos') || {}).innerText || '', faixasAzuis: document.querySelectorAll('.btn-app').length,
      texto: document.querySelector('.page-body').innerText }));
    L.ok(d.abas.length === 7 && d.ativa === 'Disciplina' && d.voltas === 0 && d.seta <= 32,
      `as telas da disciplina em abas (${d.abas.join(' · ')}), a atual destacada; um caminho de volta só, a seta discreta do título (${d.seta} px)`);
    L.ok(JSON.stringify(d.cartoes) === '["Alunos","Notas e resultado","Atividades","Faltas"]' && /\d+ alunos na turma/.test(d.alunos) && d.faixasAzuis === 0,
      `os atalhos em cartões, na mesma linha e no mesmo estilo: ${d.cartoes.join(', ')} ("${d.alunos.replace(/\s+/g, ' ').trim()}"); sem as faixas azuis`);
    L.ok(!/Sala:?\s*\n/.test(d.texto) && !d.texto.includes(nomeInst), 'sem "Sala:" vazio e sem o nome solto da instituição');
    await L.print(p, ETAPA, 'disciplina');

    /* E. rótulo sem valor não aparece */
    const aluno = um('SELECT id FROM col_usuarios ORDER BY id LIMIT 1');
    const usuario = um("SELECT id FROM adms_usuarios WHERE usuario = 'prof.laura.demo'");
    const vazios = [];
    for (const r of ['disciplinas/visualizar/1', 'cursos/visualizar/1', 'materias/visualizar/1', `academicos/visualizar/${aluno}`, `usuarios/visualizar/${usuario}`, 'perfil/visualizar', 'salas-de-aula/visualizar/1', 'atividades/visualizar/1', 'matriz/visualizar/1', 'historico-emissoes/ver/1']) {
      await p.goto(L.BASE + 'painel/' + r);
      const v = await p.evaluate(() => { const a = []; document.querySelectorAll('.page-body dt').forEach((dt) => { const dd = dt.nextElementSibling; if (dd && dd.tagName === 'DD' && dd.innerText.trim() === '') a.push(dt.innerText.trim()); });
        (document.querySelector('.page-body') || document.body).innerText.split('\n').forEach((l) => { if (/^(?!Pesquisar)[A-ZÀ-Úa-zà-ú][^:]{1,40}:\s*$/.test(l.trim())) a.push(l.trim()); }); return a; });
      if (v.length) { vazios.push(`${r}: ${v.join(', ')}`); }
    }
    L.ok(vazios.length === 0, `nenhum rótulo sem valor nas 10 telas de detalhe${vazios.length ? ' — ' + vazios.join('; ') : ''}`);

    /* F. o × em toda mensagem (uma mensagem herdada escrita sem o botão) */
    const fechar = async () => { await p.goto(L.BASE + 'painel/chamada/listar/999999'); return !!(await p.$('.alert .btn-close')); };
    L.ok(await fechar(), 'a mensagem escrita sem o × ("Disciplina não encontrada.") sai com o botão de fechar');
    {
      const orig = fs.readFileSync(VIEW, 'utf8');
      let reprovou = false;
      try { fs.writeFileSync(VIEW, orig.split('\\VcMensagem::daSessao();').join('')); reprovou = !(await fechar()); } finally { fs.writeFileSync(VIEW, orig); }
      L.ok(reprovou && fs.readFileSync(VIEW, 'utf8') === orig, 'sabotagem (a mensagem da sessão sem a conferência do ×): a prova reprova o defeito; o arquivo voltou');
    }

    /* G. o contraste */
    const of = um('SELECT id FROM rm_professores_materias WHERE encerrada_em IS NULL ORDER BY id LIMIT 1');
    await p.goto(L.BASE + 'painel/atividades/listar/' + of);
    const contr = await p.evaluate(`(() => { const C = ${CONTRASTE};
      /* o fundo translúcido das etiquetas claras, composto sobre o branco da tela */
      const sobreBranco = (s) => { const v = s.match(/[\\d.]+/g).map(Number); const a = v.length > 3 ? v[3] : 1; return 'rgb(' + v.slice(0, 3).map((x) => Math.round(x * a + 255 * (1 - a))).join(',') + ')'; };
      const r = []; const b = document.querySelector('#botaoCadastrarAtividade');
      if (b) { const s = getComputedStyle(b); r.push(['botão verde', C(s.color, sobreBranco(s.backgroundColor))]); }
      document.querySelectorAll('.badge[class*="-lt"]').forEach((e) => { const s = getComputedStyle(e); r.push([(e.className.match(/bg-[a-z]+-lt/) || ['etiqueta'])[0], C(s.color, sobreBranco(s.backgroundColor))]); });
      return r; })()`);
    const baixo = contr.filter(([, c]) => c < 4.5);
    L.ok(contr.length >= 2 && baixo.length === 0, `o contraste do botão verde e das etiquetas claras, todos acima de 4,5:1 (${contr.map(([n, c]) => n + ' ' + c.toFixed(1)).join(', ')})`);

    /* H. a demonstração genérica */
    const antigos = sql("SELECT COUNT(*) FROM rm_cursos WHERE nome LIKE '%Gest%o P%blica%' OR nome LIKE '%Dados P%blicos%'") + sql("SELECT COUNT(*) FROM adms_empresas WHERE nome LIKE '%Escola de Governo%'");
    L.ok(antigos === '00' && sql("SELECT nome FROM adms_empresas WHERE instituicao = 1") === 'Faculdade Horizonte (fictícia)' && um("SELECT COUNT(*) FROM rm_cursos WHERE nome LIKE 'P%s-gradua%o em Gest%o de Projetos' OR nome LIKE 'P%s-gradua%o em Doc%ncia no Ensino Superior'") === 2,
      'a demonstração com a Faculdade Horizonte (fictícia), Gestão de Projetos e Docência no Ensino Superior; nenhum nome antigo');
    const soTexto = spawnSync(PHP, [path.join(__dirname, 'so_texto.php'), path.join(L.RAIZ, 'tests', 'tabler', 'saida', 'regua-onda3-etapa4.json'), path.join(L.RAIZ, 'tests', 'tabler', 'saida', 'regua-onda3-etapa5-nomes.json')], { encoding: 'utf8' });
    L.ok(soTexto.status === 0 && /Só texto/.test(soTexto.stdout), `a troca dos nomes mexeu SÓ em texto: os números, as quantidades e os ids da demonstração são os mesmos (${(soTexto.stdout.match(/Campos de TEXTO que mudaram \((\d+)\)/) || [])[1]} campos de texto)`);
    const nomeAdm = (await p.textContent('#usuario-logado').catch(() => '')).trim();
    L.ok(nomeAdm === 'Alice' && /^Alice Administradora \(fict/.test(sql('SELECT nome FROM adms_usuarios WHERE id = 1')) && /"nome"/.test(sql("SELECT valor FROM adms_instalacao_estado WHERE chave = 'demonstracao.admin_nome'")),
      'o administrador da demonstração com nome fictício no canto do usuário (o nome dele guardado para voltar na remoção)');

    /* I. as ações; J. o polo Sede */
    L.ok(um("SELECT COUNT(*) FROM adms_acoes WHERE nome LIKE '%(até %'") === 0 && sql("SELECT a.nome FROM adms_paginas p JOIN adms_acoes a ON a.id = p.acao_id WHERE p.controller = 'Cursos' AND p.metodo = 'cadastrarUsuario'") === 'Indicar a coordenação do curso'
      && sql("SELECT a.nome FROM adms_paginas p JOIN adms_acoes a ON a.id = p.acao_id WHERE p.controller = 'Relatorios' AND p.metodo = 'salvar'") === 'Salvar relatórios',
      'as ações: a coordenação do curso e os relatórios salvos com ação própria; nenhum nome com o limite ("até …")');
    L.ok(um("SELECT COUNT(*) FROM rm_polos WHERE nome = 'Sede'") === 1 && JSON.parse(fs.readFileSync(path.join(L.RAIZ, 'painel', 'app', 'adms', 'Roteiros', 'fase-curso.json'), 'utf8')).inicio === 'materias/cadastrar',
      'a instalação traz o polo Sede; a fase "o primeiro curso" começa pelas matérias');
    await ctx.close();

    /* H'. a remoção devolve o nome do administrador (a limpeza da demonstração; depois, a demonstração de volta) */
    const guardado = JSON.parse(sql("SELECT valor FROM adms_instalacao_estado WHERE chave = 'demonstracao.admin_nome'"));
    const limpa = spawnSync(PHP, [path.join(L.RAIZ, 'database', 'ferramentas', 'limpar_demo.php')], { encoding: 'utf8', cwd: L.RAIZ });
    L.ok(limpa.status === 0 && sql(`SELECT nome FROM adms_usuarios WHERE id = ${+guardado.id}`) === guardado.nome && um("SELECT COUNT(*) FROM adms_instalacao_estado WHERE chave = 'demonstracao.admin_nome'") === 0,
      `a remoção da demonstração devolve ao administrador o nome dele ("${guardado.nome}")`);
    /* o banco refeito do zero (os ids recomeçam, como na régua), para as provas seguintes */
    const mont = spawnSync(PHP, [path.join(L.RAIZ, 'tests', 'tabler', 'ciclo.php'), 'comparar', REGUA_ATUAL], { encoding: 'utf8', cwd: L.RAIZ, env: process.env, maxBuffer: 1 << 26 });
    L.ok(mont.status === 0 && /IDÊNTICA/.test(mont.stdout || ''), 'o banco refeito do zero e a régua idêntica (para as provas seguintes)');
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message);
  } finally {
    if (fs.existsSync(PENDENTE)) { fs.unlinkSync(PENDENTE); }
    await navegador.close();
  }
  process.exit(L.resumo('onda3/etapa5') ? 0 : 1);
})();
