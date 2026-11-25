/*
 * PROVA DA ONDA 8, CORREÇÃO, ACRÉSCIMOS A (itens 3 a 5), B E C — os relatórios na escola, as atividades na etapa e os
 * boletins da turma (D-79, D-81, D-80). APAGA o banco de prova.
 *   1. a demonstração do colégio, montada pela tela numa instalação nova;
 *   2. A — a tabela dos relatórios da escola: cada relatório do menu aberto (com o filtro que ele pede, da
 *      demonstração), classificado em "com números", "vazio" ou "pede filtro"; os vazios são só os que dependem de um
 *      evento que a demonstração não tem (reaberturas, trocas de regra, exportações); os que não se aplicam à escola
 *      (TCC, conclusão do curso) não estão no menu e, pelo endereço, dão a página sem acesso. A tabela fica em
 *      saida/onda8-etapa13-relatorios.json;
 *   3. A — o vocabulário: o desempenho do aluno com a série e a disciplina (D-85: "disciplina" na escola também); o mapa de notas com a nota e a recuperação de
 *      cada etapa, a média anual e a situação (sem a "média se encerrasse");
 *   4. A — o relatório de documentos mostra o atestado emitido pela montagem;
 *   5. B — a tela da etapa mostra as atividades da etapa, uma coluna por atividade, com as notas, e o nome leva a lançar;
 *   6. C — "Imprimir os boletins da turma", na tela da turma e na do conselho: um PDF só, um aluno por página;
 *   sabotagem: o PDF da turma sem a quebra de página — a prova acusa (menos páginas que alunos); o arquivo volta igual.
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { spawnSync, execFileSync } = require('child_process');
const L = require('../lib');

const ETAPA = 'onda8/etapa13';
const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste$/.test(BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
const SENHA = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const PDF = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'academicos', 'AdmsGerarBoletimPdf.php');
const SAIDA = path.join(__dirname, '..', 'saida');
const SO_SUPERIOR = ['situacao-tcc', 'aptos-concluir', 'concluintes', 'coorte-ingresso', 'previsao-conclusao'];
const VAZIOS_ESPERADOS = ['reaberturas', 'trocas-regra', 'exportacoes'];

const sql = (q) => execFileSync('C:/xampp/mysql/bin/mysql.exe', ['-uroot', '--default-character-set=utf8mb4', '-N', '-B', BANCO, '-e', q], { encoding: 'utf8' }).split('\n').map((l) => l.trim()).filter(Boolean).join('\n');
const um = (q) => +(sql(q).split('\n')[0] || 0);
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const montar = (rotulo) => spawnSync(process.execPath, [path.join(__dirname, '..', 'onda6', 'medir_demo.js'), 'escola', rotulo], { encoding: 'utf8', maxBuffer: 1 << 24 });
/** O PDF pelo navegador (a sessão): o tamanho e as páginas (os objetos /Type /Page). */
const pdfDe = (p, url) => p.evaluate(async (u) => {
  const x = await fetch(u, { credentials: 'same-origin' });
  const b = new Uint8Array(await x.arrayBuffer());
  const t = new TextDecoder('latin1').decode(b);
  return { tipo: x.headers.get('content-type') || '', bytes: b.length, paginas: (t.match(/\/Type\s*\/Page(?!s)/g) || []).length };
}, url);
/** Abre um relatório e o classifica. */
async function classificar(p, slug, filtro) {
  await p.goto(L.BASE + `painel/relatorios/${slug}${filtro ? '?' + filtro : ''}`, { timeout: 120000 });
  return p.evaluate(() => {
    if (document.querySelector('#semAcesso')) { return 'sem acesso'; }
    if (/Escolha:/.test((document.querySelector('.page-body .alert') || { textContent: '' }).textContent)) { return 'pede filtro'; }
    const linhas = document.querySelectorAll('table.lista-dados tbody tr').length;
    const numeros = [...document.querySelectorAll('.vc-cartao-rel .h1')].some((h) => /[1-9]/.test(h.textContent));
    return linhas > 0 || numeros ? 'com números' : 'vazio';
  });
}

(async () => {
  const { navegador } = await L.abrir();
  const shaPdf = sha(PDF);
  try {
    const r = montar('onda8-etapa13');
    L.ok(r.status === 0, 'a demonstração do colégio montada pela tela, numa instalação nova');
    const ctx = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' });
    const p = await L.pagina(ctx);
    await p.goto(L.BASE + 'painel/login/acesso');
    await p.fill('input[name="usuario"]', 'administrador');
    await p.fill('input[name="senha"]', SENHA);
    await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);

    /* os filtros da demonstração: uma oferta da Matemática do 6º ano A, uma matrícula, uma turma */
    const oferta = um("SELECT o.id FROM rm_professores_materias o JOIN esc_turma_ofertas x ON x.oferta_id = o.id JOIN esc_turmas t ON t.id = x.turma_id WHERE t.ano = 2026 AND t.nome = 'A' AND o.codtur LIKE '6%' AND o.materia LIKE 'Matem_tica'");
    const turma = um("SELECT x.turma_id FROM esc_turma_ofertas x WHERE x.oferta_id = " + oferta);
    const mc = um("SELECT mc.id FROM rm_matriculas_curso mc JOIN col_usuarios u ON u.id = mc.usuario_id WHERE u.rm = '265001' ORDER BY mc.id LIMIT 1");
    const filtros = { 'mapa-notas': `oferta=${oferta}`, 'frequencia-oferta': `oferta=${oferta}`, 'desempenho-aluno': `matricula=${mc}`, 'lista-turma': `oferta=${oferta}` };

    /* 2. a tabela dos relatórios */
    await p.goto(L.BASE + 'painel/relatorios/listar');
    const menu = await p.$$eval('a[href*="/relatorios/"]', (l) => [...new Set(l.map((a) => (a.getAttribute('href') || '').split('/relatorios/')[1]).filter((s) => s && /^[a-z-]+$/.test(s) && !['listar', 'parametros'].includes(s)))]);
    const tabela = {};
    for (const slug of menu) {
      let c = await classificar(p, slug, '');
      if (c === 'pede filtro' && filtros[slug]) { c = (await classificar(p, slug, filtros[slug])) + ' (com o filtro)'; }
      tabela[slug] = c;
    }
    for (const slug of SO_SUPERIOR) { tabela[slug] = 'fora do menu da escola; pelo endereço: ' + (await classificar(p, slug, '')); }
    fs.writeFileSync(path.join(SAIDA, 'onda8-etapa13-relatorios.json'), JSON.stringify(tabela, null, 2));
    const vazios = Object.keys(tabela).filter((s) => /^vazio/.test(tabela[s]));
    const foraDoMenu = SO_SUPERIOR.every((s) => !menu.includes(s) && /sem acesso/.test(tabela[s]));
    L.ok(menu.length >= 17 && vazios.every((s) => VAZIOS_ESPERADOS.includes(s)) && foraDoMenu,
      `os ${menu.length} relatórios do menu da escola: ${Object.entries(tabela).filter(([s]) => menu.includes(s)).map(([s, c]) => `${s} ${c}`).join('; ')}; vazios só os de evento (${vazios.join(', ') || 'nenhum'}); os ${SO_SUPERIOR.length} do ensino superior fora do menu e sem acesso pelo endereço`);

    /* 3. o vocabulário */
    await p.goto(L.BASE + `painel/relatorios/desempenho-aluno?matricula=${mc}`);
    const colDes = await p.$$eval('table.lista-dados thead th', (l) => l.map((t) => t.textContent.trim()));
    await p.goto(L.BASE + `painel/relatorios/mapa-notas?oferta=${oferta}`);
    const colMapa = await p.$$eval('table.lista-dados thead th', (l) => l.map((t) => t.textContent.trim()));
    await L.print(p, ETAPA, 'mapa-notas-escola');
    L.ok(colDes.includes('Série') && colDes.includes('Disciplina') && !colDes.includes('Módulo') && !colDes.some((t) => /omponente/.test(t)) /* D-85: na escola, "disciplina" */
      && colMapa.some((t) => /^Nota — /.test(t)) && colMapa.some((t) => /^Recuperação — /.test(t)) && colMapa.some((t) => /^Média anual/.test(t)) && colMapa.includes('Situação')
      && !colMapa.some((t) => /encerrasse/.test(t)),
      `o vocabulário da escola: desempenho do aluno (${colDes.slice(0, 2).join(', ')}; a disciplina, como no superior — D-85); mapa de notas (${colMapa.filter((t) => /—|anual|Situação/.test(t)).slice(0, 4).join(', ')}…)`);

    /* 4. o documento da montagem */
    await p.goto(L.BASE + 'painel/relatorios/documentos-emitidos');
    const docs = await p.$$eval('table.lista-dados tbody tr', (l) => l.map((t) => t.textContent.replace(/\s+/g, ' '))).catch(() => []);
    L.ok(docs.some((t) => /Atestado/.test(t)), `o relatório de documentos mostra o atestado emitido pela montagem (${docs.length} linha(s))`);

    /* 5. B — as atividades na tela da etapa */
    await p.goto(L.BASE + `painel/escola-notas/etapas/${oferta}`);
    let col = null;
    for (const aba of await p.$$eval('#abasEtapas a[data-etapa]', (l) => l.map((a) => a.dataset.etapa))) {
      await p.goto(L.BASE + `painel/escola-notas/etapas/${oferta}?etapa=${aba}`);
      col = await p.$$eval('#tabelaEtapa th.vc-atividade-etapa', (l) => l.map((t) => ({ nome: t.textContent.trim(), href: (t.querySelector('a') || {}).href || '' }))).catch(() => []);
      if (col.length) { break; }
    }
    const notas = await p.$$eval('#tabelaEtapa td.vc-nota-atividade', (l) => l.map((t) => t.textContent.trim()).filter((x) => /\d/.test(x)).length).catch(() => 0);
    await L.print(p, ETAPA, 'etapa-com-atividades');
    let abriu = '';
    if (col && col[0]) { await Promise.all([p.waitForNavigation(), p.click('#tabelaEtapa th.vc-atividade-etapa a >> nth=0')]); abriu = p.url().replace(L.BASE, ''); }
    L.ok(col && col.length >= 1 && notas > 0 && /atividades\/visualizar\/\d+/.test(abriu),
      `a tela da etapa mostra as atividades (${(col || []).map((x) => x.nome).join(', ')}), com ${notas} nota(s), e o nome leva a lançar (${abriu})`);

    /* 6. C — os boletins da turma */
    await p.goto(L.BASE + `painel/turmas/ver/${turma}`);
    const naTurma = await p.getAttribute('#boletinsTurma', 'href').catch(() => '');
    await p.goto(L.BASE + `painel/escola-conselho/turma/${turma}`);
    const noConselho = await p.getAttribute('#boletinsConselho', 'href').catch(() => '');
    const alunos = um(`SELECT COUNT(*) FROM esc_turma_alunos WHERE turma_id = ${turma} AND saida IS NULL`);
    const b = naTurma ? await pdfDe(p, naTurma) : { tipo: '', bytes: 0, paginas: 0 };
    L.ok(/turmas\/boletins\//.test(naTurma) && naTurma === noConselho && /pdf/.test(b.tipo) && b.paginas >= alunos && alunos > 1, /* um aluno por página, ao menos (um boletim longo ocupa duas) */
      `"Imprimir os boletins da turma" na tela da turma e na do conselho: um PDF só (${b.bytes} bytes), ${b.paginas} página(s) para ${alunos} aluno(s)`);

    /* sabotagem: sem a quebra de página */
    const orig = fs.readFileSync(PDF, 'utf8');
    const trecho = '.pagina{page-break-after:always}';
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let s = null;
    try {
      fs.writeFileSync(PDF, orig.replace(trecho, '.pagina{}'));
      s = await pdfDe(p, naTurma);
    } finally {
      fs.writeFileSync(PDF, orig);
    }
    await ctx.close();
    L.ok(s && s.paginas < alunos, `sabotagem (o PDF sem a quebra de página): a prova reprova — ${s ? s.paginas : '—'} página(s) para ${alunos} aluno(s)`);
    L.ok(sha(PDF) === shaPdf, 'o arquivo sabotado voltou igual (sha256)');
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(PDF) !== shaPdf) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
