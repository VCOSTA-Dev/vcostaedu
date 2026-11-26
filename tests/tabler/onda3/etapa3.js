/*
 * ONDA 3, ETAPA 3 — o pente fino (docs/PENTE_FINO.md): as telas de programação saem, a permissão vira AÇÃO com teto,
 * a tela "Perfis de acesso", a varredura do jargão e os complementos.
 *   A. toda rota que saiu é recusada para os 7 perfis; a conferência "telas sem perfil" e "telas sem ação" está limpa;
 *   B. o TETO: a matriz que dá uma ação a um perfil abaixo do teto é recusada nos 3 pontos (rota, menu, botão), e a
 *      conferência do instalador e a verificação do ambiente reprovam;
 *   C. a tela "Perfis de acesso" é a matriz (cada cruzamento confere com o banco), só do Administrador (3 pontos);
 *   D. o menu: sem "Dashboard", Manutenção só com Atualizar, Perfis de acesso em Equipe; nenhuma tela que saiu;
 *   E. os complementos: o nome do perfil relido a cada requisição; o curso (a ajuda do TCC só com "Sim", um caminho de
 *      volta, "curso" no lugar de "Ensino"); a frase dos Primeiros passos uma vez; a documentação da API por assunto,
 *      com Modalidades;
 *   F. a atualização de uma instalação 1.26.0 com um nível criado pela tela e uma pessoa nele, PELA TELA: a pessoa
 *      continua entrando; a permissão dela na tela de níveis saiu; a tela do resultado não mostra a faixa de pendente;
 *      a coluna "Tempo" em ms;
 *   G. sabotagens: sem o teto no motor, sem o teto na conferência, o jargão na tela, o nome do perfil da sessão, a faixa
 *      depois de aplicar.
 *
 *   node tests/tabler/onda3/etapa3.js     (depois do ciclo, com a demonstração)
 */
const fs = require('fs');
const path = require('path');
const { execFileSync, spawnSync } = require('child_process');
const L = require('../lib');

const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste$/.test(BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
const BANCO2 = BANCO + '2';
const MYSQL = process.env.VCOSTAEDU_MYSQL || 'C:/xampp/mysql/bin/mysql.exe';
const PHP = process.env.VCOSTAEDU_PHP || 'C:/xampp/php/php.exe';
const consulta = (banco) => (q) => execFileSync(MYSQL, ['-u', 'root', '-N', '--default-character-set=utf8mb4', banco, '-e', q]).toString().replace(/\r/g, '').trim();
const sql = consulta(BANCO);
const sql2 = consulta(BANCO2);
const um = (q) => +sql(q);
const cred = JSON.parse(fs.readFileSync(path.join(__dirname, '..', 'saida', 'credenciais.json'), 'utf8'));
const ETAPA = 'onda3/etapa3';
const SENHA = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const ANTERIOR = '5751701'; // o último commit da segunda onda (1.26.0)
const CONFIG = path.join(L.RAIZ, 'painel', 'core', 'Config.php');
const NIVEL = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'helper', 'AdmsNivel.php');
const TETO = path.join(L.RAIZ, 'core', 'Teto.php');
const PAGINAS = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'AdmsPaginas.php');
/* com a demonstração ligada (a bateria), o aviso de atualização vai dentro da faixa dela (Onda 3, Etapa 5) */
const AVISO = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Views', 'include_tabler', '_faixa_demo.php');
const PERFIS = ['administrador', 'direcao.demo', 'supervisao.demo', 'secretaria.demo', 'coord.demo', 'prof.laura.demo', 'prof.marcos.demo'];
const SAIRAM = ['NivelAcesso', 'Paginas', 'PaginasAcademico', 'Menu', 'AcessoAcademico', 'Liberar', 'LiberarAcademico'];
const kebab = (s) => s.replace(/([a-z0-9])([A-Z])/g, '$1-$2').toLowerCase();

async function entrar(navegador, usuario, senha = null) {
  const ctx = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' });
  const p = await L.pagina(ctx);
  await p.goto(L.BASE + 'painel/login/acesso');
  await p.fill('input[name="usuario"]', usuario);
  await p.fill('input[name="senha"]', senha || cred.painel[usuario]);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
  return { ctx, p };
}
const semErroPhp = async (p) => !/<b>(Fatal error|Warning|Notice|Deprecated)<\/b>:|Uncaught |SQLSTATE\[/.test(await p.content());
/** A rota abre a tela dela? (recusada: o roteador volta ao padrão, ou o escopo manda para Minhas disciplinas) */
const abre = async (p, rota) => {
  await p.goto(L.BASE + 'painel/' + rota);
  const u = p.url();
  return u.includes('/painel/' + rota) && !(await p.$('input[name="SendLogin"]')) && !(await p.$('#semAcesso'));
};
const menuTexto = (p) => p.$eval('#railMenu', (e) => e.textContent.replace(/\s+/g, ' ')).catch(() => ''); // o texto, não o innerText: o nome do grupo sai em maiúsculas pelo CSS
const conferirAcesso = () => spawnSync(PHP, [path.join(L.RAIZ, 'tests', 'tabler', 'onda2', 'conferir_acesso.php')], { encoding: 'utf8', cwd: L.RAIZ });
const ambiente = () => spawnSync(PHP, [path.join(L.RAIZ, 'database', 'ferramentas', 'implantacao', 'verificar_ambiente.php')], { encoding: 'utf8', cwd: L.RAIZ }).stdout;
/** Os métodos de cada controller que saiu (do commit anterior a esta etapa), em rotas */
function rotasQueSairam() {
  const rotas = [];
  for (const c of SAIRAM) {
    const codigo = execFileSync('git', ['-C', L.RAIZ, 'show', `${ANTERIOR}:painel/app/adms/Controllers/${c}.php`]).toString();
    for (const m of codigo.matchAll(/public function (\w+)\s*\(/g)) {
      if (m[1] !== '__construct') { rotas.push(`${kebab(c)}/${kebab(m[1])}`); }
    }
  }
  return rotas;
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

/** F. A instalação 1.26.0 com o nível da tela, atualizada pela tela. Devolve o que a prova confere. */
async function atualizarAntiga(navegador, comPrints) {
  const r = spawnSync(PHP, [path.join(L.RAIZ, 'tests', 'tabler', 'onda2', 'banco_antigo.php'), ANTERIOR], { encoding: 'utf8', env: process.env });
  if (r.status !== 0) { throw new Error('banco antigo: ' + (r.stderr || r.stdout)); }
  const antigo = JSON.parse(r.stdout.trim());
  const n = spawnSync(PHP, [path.join(__dirname, 'nivel_da_tela.php')], { encoding: 'utf8', env: process.env });
  if (n.status !== 0) { throw new Error('nível da tela: ' + (n.stderr || n.stdout)); }
  const nivel = JSON.parse(n.stdout.trim());
  const configOriginal = fs.readFileSync(CONFIG, 'utf8');
  const res = { antigo, nivel };
  try {
    fs.writeFileSync(CONFIG, configOriginal.replace(/define\('DBNAME',\s*'[^']*'\)/, `define('DBNAME', '${BANCO2}')`));
    const mon0 = await entrar(navegador, 'monitora.prova', SENHA);
    res.antesEntra = /home\/index/.test(mon0.p.url());
    await mon0.ctx.close();
    const adm = await entrar(navegador, 'administrador', SENHA);
    await adm.p.goto(L.BASE + 'painel/atualizar/index');
    res.pendentes = await adm.p.$$eval('#listaPendentes li', (l) => l.map((x) => x.textContent.trim()));
    await adm.p.check('#fizBackup');
    await adm.p.click('#aplicarAtualizacao'); // Onda 3, Etapa 11: a janela de progresso, uma etapa por chamada
    await adm.p.waitForSelector('#progressoAtualizacao[data-situacao="concluida"], #progressoAtualizacao[data-situacao="falhou"]', { timeout: 300000 });
    await adm.p.goto(L.BASE + 'painel/atualizar/index');
    res.ok = await adm.p.isVisible('#atualizacaoOk');
    res.versao = (await adm.p.textContent('#versaoDepois').catch(() => '')).trim();
    res.faixa = !!(await adm.p.$('#avisoAtualizacao'));
    res.colunas = await adm.p.$$eval('#etapasAplicadas th', (l) => l.map((x) => x.textContent.trim()));
    res.tempos = await adm.p.$$eval('#etapasAplicadas tbody td:last-child', (l) => l.map((x) => x.textContent.trim()));
    res.semErro = await semErroPhp(adm.p);
    if (comPrints) { await L.print(adm.p, ETAPA, 'atualizada-pela-tela'); }
    await adm.ctx.close();
    const mon = await entrar(navegador, 'monitora.prova', SENHA);
    res.depoisEntra = /home\/index/.test(mon.p.url()) && await semErroPhp(mon.p);
    res.perfilNoMenu = (await mon.p.textContent('#perfil-logado').catch(() => '')).trim();
    res.niveisRecusada = !(await abre(mon.p, 'nivel-acesso/listar'));
    res.minhas = await abre(mon.p, 'minhas-disciplinas/listar');
    if (comPrints) { await L.print(mon.p, ETAPA, 'nivel-da-tela-depois'); }
    await mon.ctx.close();
    res.permissoesDepois = +sql2(`SELECT COUNT(*) FROM adms_nivacs_pgs WHERE adms_niveis_acesso_id = ${nivel.nivel} AND permissao = 1`);
    /* as páginas que as etapas depois da 1.26.0 dão a todo nível (desde a 0020: a 497 em diante; o tema, 505, a todos) */
    res.novasDepois = +sql2(`SELECT COUNT(*) FROM adms_nivacs_pgs WHERE adms_niveis_acesso_id = ${nivel.nivel} AND permissao = 1 AND adms_pagina_id >= 497`);
    res.programacaoNoBanco = +sql2(`SELECT COUNT(*) FROM adms_paginas WHERE controller IN (${SAIRAM.map((c) => `'${c}'`).join(',')})`);
    res.versaoBanco = sql2('SELECT numero FROM adms_versoes WHERE situacao = 1');
  } finally {
    fs.writeFileSync(CONFIG, configOriginal);
  }
  return res;
}

(async () => {
  const { navegador } = await L.abrir();
  try {
    console.log('\n== Onda 3, Etapa 3');
    const ctx = {};
    for (const u of PERFIS) { ctx[u] = await entrar(navegador, u); }

    /* A. as rotas que saíram */
    const rotas = rotasQueSairam();
    let abertas = [];
    for (const u of PERFIS) {
      for (const r of rotas) { if (await abre(ctx[u].p, r)) { abertas.push(`${u}: ${r}`); } }
    }
    L.ok(rotas.length >= 20 && abertas.length === 0, `as ${rotas.length} rotas das telas que saíram (${SAIRAM.join(', ')}) são recusadas para os 7 perfis${abertas.length ? ' — abriram: ' + abertas.slice(0, 5).join('; ') : ''}`);
    L.ok(um(`SELECT COUNT(*) FROM adms_paginas WHERE controller IN (${SAIRAM.map((c) => `'${c}'`).join(',')})`) === 0
      && SAIRAM.every((c) => !fs.existsSync(path.join(L.RAIZ, 'painel', 'app', 'adms', 'Controllers', c + '.php'))), 'as páginas delas saíram do banco e os controllers do código');
    const conf = conferirAcesso();
    L.ok(conf.status === 0 && conf.stdout.trim() === 'OK', `a conferência do instalador está limpa: toda tela com perfil e com ação, nenhuma ação acima do perfil (${conf.stdout.trim().slice(0, 120)})`);
    const amb = ambiente();
    L.ok(/\[OK\]\s+nenhum perfil recebe uma ação acima do que pode/.test(amb) && /\[OK\]\s+todas as telas do painel têm a ação delas/.test(amb), 'a verificação do ambiente: nenhuma ação acima do perfil, toda tela com a ação');
    L.ok(um('SELECT COUNT(*) FROM adms_acoes') > 50 && um("SELECT COUNT(*) FROM adms_acoes WHERE teto NOT IN (SELECT papel FROM adms_niveis_acessos WHERE papel IS NOT NULL)") === 0,
      `as ações como dado: ${um('SELECT COUNT(*) FROM adms_acoes')} ações, cada uma com nome, grupo e um teto que é um perfil`);

    /* B. o teto: a matriz dá ao Professor a lista da equipe (teto: Direção acadêmica) */
    const prof = ctx['prof.laura.demo'].p;
    const pgEquipe = um("SELECT id FROM adms_paginas WHERE controller = 'Usuarios' AND metodo = 'listar'");
    const nProf = um("SELECT id FROM adms_niveis_acessos WHERE papel = 'professor'");
    const antes = sql(`SELECT CONCAT(permissao, ',', lib_menu, ',', adms_menu_id) FROM adms_nivacs_pgs WHERE adms_niveis_acesso_id = ${nProf} AND adms_pagina_id = ${pgEquipe}`);
    const tetoProva = async () => {
      sql(`UPDATE adms_nivacs_pgs SET permissao = 1, lib_menu = 1, adms_menu_id = 2 WHERE adms_niveis_acesso_id = ${nProf} AND adms_pagina_id = ${pgEquipe}`);
      try {
        const rota = !(await abre(prof, 'usuarios/listar'));
        await prof.goto(L.BASE + 'painel/home/index');
        const menu = !/Usuários/.test(await menuTexto(prof));
        const c = conferirAcesso();
        const instalador = c.status !== 0 && /acima do perfil/.test(c.stdout) && /Professor: Consultar a equipe \(Usuarios\/listar\)/.test(c.stdout);
        const ambi = /\[FALHA\]\s+\d+ permissão\(ões\) acima do perfil: Professor: Consultar a equipe/.test(ambiente());
        return { rota, menu, instalador, ambi };
      } finally {
        const [pe, lm, mn] = antes.split(',');
        sql(`UPDATE adms_nivacs_pgs SET permissao = ${pe}, lib_menu = ${lm}, adms_menu_id = ${mn} WHERE adms_niveis_acesso_id = ${nProf} AND adms_pagina_id = ${pgEquipe}`);
      }
    };
    const t = await tetoProva();
    L.ok(t.rota && t.menu, 'o teto no motor: a lista da equipe dada ao Professor pela matriz é recusada pela rota e não aparece no menu');
    L.ok(t.instalador && t.ambi, 'o teto na conferência: o instalador e a verificação do ambiente reprovam ("Professor: Consultar a equipe (Usuarios/listar)")');
    L.ok(conferirAcesso().stdout.trim() === 'OK', 'a matriz voltou: a conferência está limpa de novo');

    /* C. Perfis de acesso: a matriz, só do Administrador */
    const adm = ctx.administrador.p;
    await adm.goto(L.BASE + 'painel/perfis-acesso/index');
    const tela = await adm.$$eval('#matrizPerfis tr[data-acao] td[data-perfil]', (l) => l.map((td) => `${td.closest('tr').dataset.acao}|${td.dataset.perfil}|${td.dataset.tem}`));
    const esperado = sql(`SELECT CONCAT(a.id, '|', v.id, '|', IF(EXISTS (SELECT 1 FROM adms_nivacs_pgs n JOIN adms_paginas p ON p.id = n.adms_pagina_id
        WHERE p.acao_id = a.id AND n.adms_niveis_acesso_id = v.id AND n.permissao = 1)
        AND (v.papel IS NULL OR v.papel = '' OR v.papel = 'administrador' OR v.ordem <= (SELECT t.ordem FROM adms_niveis_acessos t WHERE t.papel = a.teto)), 1, 0))
      FROM adms_acoes a CROSS JOIN adms_niveis_acessos v ORDER BY a.ordem, a.id, v.ordem, v.id`).split('\n');
    /* comparado por (ação, perfil): a tela agrupa as ações por área, e duas áreas podem dividir a mesma ordem (a 0020) */
    const naTela = new Set(tela);
    const diferentes = esperado.filter((x) => !naTela.has(x));
    L.ok(tela.length === esperado.length && diferentes.length === 0 && await semErroPhp(adm),
      `a tela Perfis de acesso é a matriz: ${tela.length} cruzamentos (ação × perfil), todos iguais ao banco${diferentes.length ? ' — diferem: ' + diferentes.slice(0, 3).join(', ') : ''}`);
    const lancar = await adm.$$eval('#matrizPerfis tr[data-acao]', (l) => l.filter((tr) => /Lançar notas e atividades/.test(tr.textContent)).map((tr) => tr.textContent.replace(/\s+/g, ' ')));
    L.ok(lancar.length === 1 && /Professor/.test(lancar[0]), 'uma ação com nome que a pessoa entende ("Lançar notas e atividades"), com o limite (Professor)');
    await L.print(adm, ETAPA, 'perfis-de-acesso');
    await adm.goto(L.BASE + 'painel/usuarios/listar');
    const botaoAdm = !!(await adm.$('#verPerfisAcesso'));
    const menuAdm = /Perfis de acesso/.test(await menuTexto(adm));
    const dir = ctx['direcao.demo'].p;
    await dir.goto(L.BASE + 'painel/usuarios/listar');
    const botaoDir = !!(await dir.$('#verPerfisAcesso'));
    const menuDir = /Perfis de acesso/.test(await menuTexto(dir));
    const rotaOutros = [];
    for (const u of PERFIS.slice(1)) { if (await abre(ctx[u].p, 'perfis-acesso/index')) { rotaOutros.push(u); } }
    L.ok(botaoAdm && menuAdm && !botaoDir && !menuDir && rotaOutros.length === 0,
      `só o Administrador, nos 3 pontos: o menu e o botão na lista de usuários (a Direção, que vê a lista, não os vê) e a rota recusada para os outros 6${rotaOutros.length ? ' — abriu: ' + rotaOutros.join(', ') : ''}`);

    /* D. o menu */
    await adm.goto(L.BASE + 'painel/home/index');
    /* o grupo com um item só vira um item solto no menu (o rail): Manutenção, com Atualizar */
    const grupos = await adm.$$eval('#railMenu .vc-side-grupo', (l) => l.map((g) => ({ nome: g.querySelector('.vc-side-grupo-nome').textContent.trim(), itens: Array.from(g.querySelectorAll('.vc-side-grupo-itens a')).map((a) => a.innerText.trim()) })));
    const manut = { itens: sql("SELECT p.menu_pagina FROM adms_nivacs_pgs n JOIN adms_paginas p ON p.id = n.adms_pagina_id JOIN adms_menus m ON m.id = n.adms_menu_id WHERE n.adms_niveis_acesso_id = 1 AND n.permissao = 1 AND n.lib_menu = 1 AND m.nome LIKE 'Manuten%'").split('\n').filter(Boolean) };
    const equipe = grupos.find((g) => /Equipe/.test(g.nome));
    const menuAdmTexto = await menuTexto(adm);
    /* Manutenção: Atualizar o sistema e, desde a Onda 3, Etapa 6, Avisos do sistema, Verificações de segurança e Bibliotecas; desde a Onda 6, Etapa 7, Registrar a instalação — nada de programação */
    const MANUT = ['Atualizar o sistema', 'Avisos do sistema', 'Verificações de segurança', 'Bibliotecas', 'Registrar a instalação'];
    L.ok(!/dashboard/i.test(menuAdmTexto) && /início/i.test(menuAdmTexto) && manut.itens.includes('Atualizar o sistema') && manut.itens.every((i) => MANUT.includes(i)) && /Atualizar o sistema/.test(menuAdmTexto)
      && equipe && equipe.itens.some((i) => /Perfis de acesso/.test(i)) && !/Níveis de acesso|Itens de Menu|Acesso Acadêmico|Páginas Educacional/.test(menuAdmTexto) && /Matérias/.test(menuAdmTexto) && !/Matérias de Ensino/.test(menuAdmTexto),
      `o menu do Administrador: "Início" no lugar de "Dashboard"; Manutenção só com as telas de uso (${manut ? manut.itens.join(', ') : '—'}); Equipe com Perfis de acesso; "Matérias"; nenhuma tela que saiu`);
    await L.print(adm, ETAPA, 'menu-administrador');

    /* E. os complementos */
    const sec = ctx['secretaria.demo'];
    const nSec = um("SELECT id FROM adms_niveis_acessos WHERE papel = 'secretaria'");
    const nomeSec = sql(`SELECT nome FROM adms_niveis_acessos WHERE id = ${nSec}`);
    let rodada = 0;
    const nomeRelido = async () => {
      const novo = 'Secretaria da prova ' + (++rodada); // um nome novo a cada vez: a sessão pode guardar o da vez anterior
      sql(`UPDATE adms_niveis_acessos SET nome = '${novo}' WHERE id = ${nSec}`);
      try {
        await sec.p.goto(L.BASE + 'painel/home/index');
        return (await sec.p.textContent('#perfil-logado')).trim() === novo;
      } finally { sql(`UPDATE adms_niveis_acessos SET nome = '${nomeSec.replace(/'/g, "''")}' WHERE id = ${nSec}`); }
    };
    L.ok(await nomeRelido(), 'o nome do perfil no menu é relido a cada requisição: trocado no banco com a pessoa já dentro, o menu mostra o novo');
    await adm.goto(L.BASE + 'painel/cursos/cadastrar');
    const tccNao = await adm.isHidden('#ajudaExigeTcc');
    await adm.selectOption('#exige_tcc', '1');
    const tccSim = await adm.isVisible('#ajudaExigeTcc');
    const voltasCad = await adm.$$eval('.page-body a[href$="cursos/listar"]', (l) => l.filter((a) => a.textContent.trim() !== 'Cancelar').length);
    const tituloCad = (await adm.textContent('.page-header')).replace(/\s+/g, ' ');
    await L.print(adm, ETAPA, 'curso-cadastrar-tcc-sim');
    const comTcc = um('SELECT id FROM rm_cursos WHERE exige_tcc = 1 ORDER BY id LIMIT 1');
    const semTcc = um('SELECT id FROM rm_cursos WHERE exige_tcc = 0 ORDER BY id LIMIT 1');
    await adm.goto(L.BASE + 'painel/cursos/editar/' + comTcc);
    const editSim = await adm.isVisible('#ajudaExigeTcc');
    const voltasEdit = await adm.$$eval('.page-body a[href$="cursos/listar"]', (l) => l.filter((a) => a.textContent.trim() !== 'Cancelar').length);
    await adm.goto(L.BASE + 'painel/cursos/editar/' + semTcc);
    const editNao = await adm.isHidden('#ajudaExigeTcc');
    await adm.goto(L.BASE + 'painel/cursos/visualizar/' + comTcc);
    const voltasVer = await adm.$$eval('.page-body a[href$="cursos/listar"]', (l) => l.filter((a) => a.textContent.trim() !== 'Cancelar').length);
    L.ok(tccNao && tccSim && editSim && editNao, 'o curso: a ajuda "A conclusão exige o TCC aprovado" só aparece com Exige TCC = Sim (cadastro e edição, com Não e com Sim)');
    L.ok(voltasCad === 0 && voltasEdit === 0 && voltasVer === 0 && /Cadastrar curso/.test(tituloCad), 'o curso: um caminho de volta só (a seta do título), sem o cartão repetido; o título "Cadastrar curso"');
    await adm.goto(L.BASE + 'painel/primeiros-passos/index');
    const pp = await adm.evaluate(() => document.body.innerText);
    L.ok((pp.match(/importar de uma planilha/g) || []).length <= 1 && sql("SELECT texto FROM adms_fases WHERE codigo = 'alunos'") === 'Os alunos e o vínculo deles com o curso.',
      'Primeiros passos, a fase dos alunos: a frase dos três caminhos uma vez só');
    await adm.goto(L.BASE + 'painel/integracoes/documentacao');
    await adm.waitForSelector('.opblock-tag', { timeout: 20000 }).catch(() => {});
    const tags = await adm.$$eval('.opblock-tag', (l) => l.map((x) => x.getAttribute('data-tag')));
    const caminhos = await adm.$$eval('.opblock-summary-path', (l) => l.map((x) => x.getAttribute('data-path') || x.textContent.trim()));
    L.ok(JSON.stringify(tags) === JSON.stringify(['Alunos', 'Matrículas', 'Enturmação', 'Cadastros de apoio']) && caminhos.some((c) => /\/modalidades$/.test(c)) && !tags.includes('default'),
      `a documentação da API por assunto (${tags.join(', ')}), sem "default", com as modalidades`);
    await L.print(adm, ETAPA, 'api-por-assunto');

    /* G1. sabotagens do teto e do jargão */
    await sabotar('sem o teto no motor, o Professor abre a lista da equipe que a matriz lhe deu', NIVEL,
      '        if ($teto === null || $teto === \'\') {\n            return false;\n        }\n        $papel = self::papel($nivel);',
      '        return false;\n        $papel = self::papel($nivel);', async () => (await tetoProva()).rota);
    await sabotar('sem o teto na conferência, a permissão acima do perfil passa', TETO, 'AND (t.id IS NULL OR v.ordem > t.ordem)', 'AND (t.id IS NULL AND v.ordem < 0)',
      async () => { const x = await tetoProva(); return x.instalador && x.ambi; });
    await adm.goto(L.BASE + 'painel/home/index');
    await adm.evaluate(() => {
      const d = document.createElement('div');
      d.innerHTML = '<p>(Onda 2, Etapa 7: a tela das etapas por aplicar)</p><table><tr><th>Ensino</th></tr></table><p>Matérias de Ensino</p>';
      document.querySelector('.page-body').appendChild(d);
      document.querySelector('#railMenu').insertAdjacentHTML('beforeend', '<span>Dashboard</span>');
    });
    const tj = await L.textoDaTela(adm);
    const achou = L.jargao(tj.texto, tj.rotulos, tj.menu);
    L.ok(achou.length === 5, `sabotagem (o jargão posto na tela: a anotação da onda, a etapa numerada, "Ensino" como coluna e no texto, "Dashboard" no menu): a varredura acha os ${achou.length}`);
    const limpo = await (async () => { await adm.goto(L.BASE + 'painel/home/index'); const x = await L.textoDaTela(adm); return L.jargao(x.texto, x.rotulos, x.menu).length === 0; })();
    L.ok(limpo, 'a varredura do jargão na Página Inicial do Administrador, sem sabotagem: limpa');
    await sabotar('o aviso de atualização com a anotação de volta', AVISO, '<span class="d-none d-md-inline"> o sistema</span></a></span>',
      '<span class="d-none d-md-inline"> o sistema</span></a> (Onda 2, Etapa 7: a tela das etapas por aplicar)</span>',
      async () => {
        const f = path.join(L.RAIZ, 'database', 'migrations', '9997_prova_pendente.sql');
        fs.writeFileSync(f, 'SELECT 1;\n');
        try { await adm.goto(L.BASE + 'painel/home/index'); const x = await L.textoDaTela(adm); return L.jargao(x.texto, x.rotulos, x.menu).length === 0; } finally { fs.unlinkSync(f); }
      });
    await sabotar('o nome do perfil fica o do login', PAGINAS, "        $_SESSION['colUsuario_nivel_nome'] = (string) ($conta[0]['nome_nivel'] ?? '');", '', nomeRelido);

    for (const u of PERFIS) { await ctx[u].ctx.close(); }

    /* F. a instalação 1.26.0 com o nível criado pela tela */
    const f = await atualizarAntiga(navegador, true);
    L.ok(f.antigo.admin && f.nivel.permissoes > 10 && f.nivel.naTelaDeNiveis === 1 && f.antesEntra,
      `a instalação 1.26.0 (${ANTERIOR}) com o nível "Monitoria" criado pela tela (${f.nivel.permissoes} permissões, inclusive a tela de níveis) e uma pessoa nele, que entra`);
    /* as etapas depois da 1.26.0 (a 0013 em diante, as do disco) e a versão mais nova (a dos arquivos) */
    const depois = fs.readdirSync(path.join(L.RAIZ, 'database', 'migrations')).filter((x) => /^\d{4}_.*\.sql$/.test(x) && +x.slice(0, 4) >= 13).sort();
    const versaoArquivos = spawnSync(PHP, ['-r', `require '${path.join(L.RAIZ, 'core', 'Legal.php').split(path.sep).join('/')}'; echo VcLegal::versaoDoPacote('${L.RAIZ.split(path.sep).join('/')}');`], { encoding: 'utf8' }).stdout.trim();
    L.ok(f.ok && /^1\.\d+\.\d+$/.test(versaoArquivos) && f.versao === versaoArquivos && f.versaoBanco === versaoArquivos && f.semErro && JSON.stringify(f.pendentes) === JSON.stringify(depois),
      `atualizada PELA TELA: ${f.pendentes.length} etapas (${f.pendentes.join(', ')}), a ${f.versao}, sem erro do PHP`);
    L.ok(!f.faixa, 'a tela do resultado não mostra a faixa de "atualização pendente" (medida de novo depois de aplicar)');
    L.ok(f.colunas[2] === 'Tempo' && f.tempos.length === f.pendentes.length && f.tempos.every((x) => /^\d+ ms$/.test(x)), `a coluna "Tempo", em ms (${f.tempos.join(', ')})`);
    L.ok(f.depoisEntra && f.perfilNoMenu === 'Monitoria' && f.minhas && f.niveisRecusada && f.programacaoNoBanco === 0 && f.permissoesDepois - f.novasDepois === f.nivel.permissoes - 1,
      `a pessoa do nível da tela continua entrando, com o perfil "Monitoria" e as permissões dele (${f.permissoesDepois}: ${f.nivel.permissoes} antes, menos a da tela de níveis, que saiu, mais ${f.novasDepois} das telas novas, como o tema)`);
    /* (A sabotagem "sem medir de novo depois de aplicar" saiu na Onda 3, Etapa 12: desde a Etapa 11 o resultado é sempre
       outra requisição — a janela termina e a tela abre de novo; sem JavaScript, o redirecionamento —, e a medida da faixa
       vale por requisição; o defeito não tem mais como aparecer. Fica a conferência acima: a faixa não aparece no resultado.) */
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message);
  } finally {
    await navegador.close();
  }
  process.exit(L.resumo('onda3/etapa3') ? 0 : 1);
})();
