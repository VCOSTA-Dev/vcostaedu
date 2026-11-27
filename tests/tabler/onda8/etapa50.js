/*
 * PROVA DA 1.102.1, C3 — os textos e o vocabulário achados no ensaio (D-125). APAGA o banco de prova (a escola do zero,
 * com a demonstração pela tela).
 *   1. o instalador: na tela do tipo de instituição, a régua diz "Tipo de instituição" (passo 6 de 7), não "Sua conta";
 *   2. o cartão "Conheça o sistema" da escola: segmento, coordenação de segmento, as alterações da nota;
 *   3. o boletim do painel na escola: "Segmento" no filtro e no cabeçalho (não "Curso");
 *   4. a dica da entrada do portal na escola: "Aluno: o RM";
 *   5. a chamada na escola: "Aula criada";
 *   6. a tela de enviar notas na escola: "Turma:" (não "Curso:") e a faixa da demonstração;
 *   7. a tela de Faltas na escola: disciplina e aula (sem "oferta" nem "encontro");
 *   8. Minhas Disciplinas: a coluna "Disciplina"; 9. o registro da instalação: "Atualizar a prévia do envio";
 *   sabotagem: a tela de Faltas sem o ramo da escola — volta "oferta" e "encontro": a prova reprova; o arquivo volta igual.
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { spawnSync } = require('child_process');
const L = require('../lib');
const X = require('./_escola');

const ETAPA = 'onda8/etapa50';
const PHP = process.env.VCOSTAEDU_PHP || 'C:/xampp/php/php.exe';
const SENHA_ADM = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const FALTAS = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Views', 'faltas', 'visFaltas.php');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const { sql, um } = X;
const texto = async (p, sel) => ((await p.textContent(sel).catch(() => '')) || '').replace(/\s+/g, ' ').trim();

(async () => {
  const { navegador } = await L.abrir();
  const shaF = sha(FALTAS);
  try {
    /* 1. o instalador (a régua da tela do tipo, desenhada pelo próprio layout do instalador) */
    const reg = spawnSync(PHP, ['-r', "define('VCOSTAEDU_INSTALADOR', true); function raizDoProjeto() { return getcwd(); } function assetUrl($x) { return $x; } "
      + "class VcLegal { static function rodape() { return ''; } static function versaoDoPacote() { return ''; } } require 'instalar/layout.php'; ob_start(); abrirPagina('Tipo de instituição', 'administrador', 'Tipo de instituição'); echo ob_get_clean();"], { cwd: L.RAIZ, encoding: 'utf8' });
    const regua = (reg.stdout || '').replace(/\s+/g, ' ');
    const chamada = fs.readFileSync(path.join(L.RAIZ, 'instalar', 'index.php'), 'utf8').includes("abrirPagina('Tipo de instituição', 'administrador', 'Tipo de instituição')");
    L.ok(/fw-medium">Tipo de instituição</.test(regua) && /Passo 6 de 7/.test(regua) && chamada,
      `1. o instalador: a tela do tipo diz "Tipo de instituição", passo 6 de 7${regua ? '' : ' (' + (reg.stderr || '').slice(0, 120) + ')'}`);

    L.ok(X.montar('onda8-etapa50').status === 0, 'a escola instalada do zero, com a demonstração do colégio pela tela');
    const c = X.cred();
    const adm = await X.entrar(navegador, 'administrador', SENHA_ADM, 'painel', 1366);
    await adm.p.goto(L.BASE + 'painel/home/index');
    const cartao = await texto(adm.p, 'body');
    L.ok(/Coordenação de segmento/.test(cartao) && /todos os segmentos/.test(cartao) && !/Coordenação de curso/.test(cartao) && !/todos os cursos/.test(cartao) && /alterações da nota/.test(cartao),
      '2. o cartão "Conheça o sistema" da escola: segmento, a coordenação de segmento e as alterações da nota');
    const rm = '265001';
    await adm.p.goto(L.BASE + 'painel/academicos/boletim/' + rm);
    const filtro = await texto(adm.p, 'label[for="filtroCurso"]');
    const boletim = await texto(adm.p, 'body');
    await L.print(adm.p, ETAPA, 'boletim-escola');
    L.ok(filtro === 'Segmento' && !/\bCurso\b/.test(boletim.replace(/Cursos? livres?/g, '')), `3. o boletim do painel: "${filtro}" no filtro e nenhum "Curso" na tela`);
    await adm.p.goto(L.BASE + 'painel/registro-instalacao/index');
    const botaoReg = await texto(adm.p, '#atualizarPrevia');
    await adm.ctx.close();

    const pub = await navegador.newContext();
    const pp = await pub.newPage();
    await pp.goto(L.BASE + 'portal/login/acesso');
    const dica = await texto(pp, '#dicaResponsavel');
    await pub.close();
    L.ok(/Aluno: o RM\./.test(dica) && !/número de matrícula/.test(dica), `4. a dica da entrada do portal: "${dica.slice(0, 60)}"`);

    /* 5 a 8: o professor */
    const prof = 'prof.laura.demo';
    const pid = um(`SELECT id FROM adms_usuarios WHERE usuario = '${prof}'`);
    const ofChamada = um(`SELECT o.id FROM rm_professores_mat_liga l JOIN rm_professores_materias o ON o.id = l.rm_prof_mat_id WHERE l.usuario_id = ${pid} AND l.situacao = 1 AND o.frequencia_fonte = 'chamada' AND o.encerrada_em IS NULL ORDER BY o.id LIMIT 1`);
    const s = await X.entrar(navegador, prof, c.painel[prof], 'painel', 1366);
    await s.p.goto(L.BASE + 'painel/chamada/encontro/' + ofChamada);
    if (await s.p.$('#inicioPresentes')) { await s.p.check('#inicioPresentes', { force: true }).catch(() => {}); }
    await Promise.all([s.p.waitForNavigation(), s.p.click('#salvarChamada')]);
    const salva = await texto(s.p, '#chamadaSalva');
    L.ok(/^Aula criada\./.test(salva) || /Aula criada\./.test(salva), `5. a chamada na escola: "${salva.slice(0, 40)}"`);
    /* a primeira atividade cuja tela de enviar abre (a de um bimestre fechado volta para as notas, com o aviso) */
    const ativs = sql(`SELECT a.id FROM rm_disciplina_atividade a JOIN rm_professores_mat_liga l ON l.rm_prof_mat_id = a.rm_prof_mat_id WHERE l.usuario_id = ${pid} AND l.situacao = 1 ORDER BY a.id DESC LIMIT 20`).split(String.fromCharCode(10)).filter(Boolean);
    for (const ativ of ativs) {
      await s.p.goto(L.BASE + 'painel/atividades/upload-notas/' + ativ);
      if (/upload-notas/.test(s.p.url()) && (await s.p.$('input[name="arquivo"]'))) { break; }
    }
    const up = await texto(s.p, 'body');
    const faixa = !!(await s.p.$('#faixaDemonstracao'));
    await L.print(s.p, ETAPA, 'enviar-notas');
    L.ok(/Turma: /.test(up) && !/Curso: /.test(up) && faixa, `6. a tela de enviar notas: "Turma:" e a faixa da demonstração (${faixa})`);
    await s.p.goto(L.BASE + 'painel/faltas/visualizar/' + ofChamada);
    const fal = await texto(s.p, '#faltasDaChamada') + ' ' + await texto(s.p, '#situacaoFaltas');
    await L.print(s.p, ETAPA, 'faltas-escola');
    L.ok(/desta disciplina/.test(fal) && /Total de aulas/.test(fal) && !/oferta|encontro/i.test(fal), `7. a tela de Faltas na escola: disciplina e aula ("${fal.slice(0, 80)}…")`);
    await s.p.goto(L.BASE + 'painel/minhas-disciplinas/listar?situacao=todas');
    const th = await s.p.$$eval('#tabelaDisciplinas thead th', (l) => l.map((x) => x.textContent.trim()));
    L.ok(th.includes('Disciplina') && !th.includes('Matéria') && botaoReg === 'Atualizar a prévia do envio',
      `8 e 9. Minhas Disciplinas com a coluna "Disciplina"; o registro com "${botaoReg}"`);

    /* 1.103.1 (D-137): o filtro por ano de Minhas Disciplinas oferece o ano que a lista mostra, marcado — o
       administrador, a direção, a coordenação e o professor (antes, só "Selecione o Ano", com a lista cheia) */
    const perfisLista = [['administrador', SENHA_ADM]].concat(['dire', 'coord', 'prof'].map((k) => Object.keys(c.painel).find((u) => u.includes(k)))
      .filter(Boolean).map((u) => [u, c.painel[u]]));
    const filtros = [];
    for (const [u, sn] of perfisLista) {
      const r = await X.listasDisciplinas(navegador, u, sn);
      filtros.push({ u, ok: X.filtroAnoOk(r.minhas) && X.filtroAnoOk(r.todas), num: X.colunaNumeroOk(r.minhas) && X.colunaNumeroOk(r.todas), linhas: r.minhas ? r.minhas.linhas.length : 0, opcoes: r.minhas ? r.minhas.opcoes.join('/') : '-', marcado: r.minhas ? r.minhas.marcado : '-' });
    }
    L.ok(perfisLista.length === 4 && filtros.every((f) => f.ok) && filtros.some((f) => f.linhas > 0),
      `10. na escola, o filtro por ano de Minhas Disciplinas com o ano que a lista mostra: ${filtros.map((f) => `${f.u} ${f.linhas} linha(s), ${f.opcoes || 'vazio'} (${f.marcado || 'nada marcado'})`).join('; ')}`);

    /* 1.103.1 (D-138): a primeira coluna "#" com o número da oferta, único (a disciplina que se repete em cada turma tem
       números diferentes); a busca acha pelo número; o número sai no Excel, no CSV, no PDF e na impressão (as colunas que
       a lista exporta); o "#" no cabeçalho da tela da disciplina */
    L.ok(filtros.every((f) => f.num), `11. na escola, a primeira coluna "#" com o número da oferta, único, antes do "Código", em Disciplinas e em Minhas Disciplinas, para os ${filtros.length} perfis`);
    const ad = await X.entrar(navegador, 'administrador', SENHA_ADM, 'painel', 1366);
    await ad.p.goto(L.BASE + 'painel/disciplinas/listar');
    const lista = await ad.p.evaluate(() => {
      const linhas = [...document.querySelectorAll('#tabelaDisciplinas tbody tr')].map((tr) => ({ n: tr.cells[0].textContent.trim(), cod: tr.cells[1].textContent.trim(), disc: tr.cells[2].textContent.trim() }));
      const dt = window.jQuery && window.jQuery('#tabelaDisciplinas').DataTable();
      const exp = dt ? dt.buttons.exportData({ columns: ':visible:not(.nao-exportar)' }) : null;
      return { linhas, cabExport: exp ? exp.header : [], primeiraExport: exp && exp.body[0] ? exp.body[0][0] : '' };
    });
    const mesmoCodigo = lista.linhas.filter((x) => lista.linhas.some((y) => y !== x && y.cod === x.cod && y.disc === x.disc));
    const alvo = lista.linhas[Math.floor(lista.linhas.length / 2)] || { n: '' };
    await ad.p.fill('#tabelaDisciplinas_filter input, .dataTables_filter input, .dt-search input', alvo.n).catch(() => {});
    await ad.p.waitForTimeout(400);
    const achadas = await ad.p.$$eval('#tabelaDisciplinas tbody tr', (l) => l.map((tr) => tr.cells[0].textContent.trim()).filter((x) => /^[0-9]+$/.test(x)));
    await ad.p.goto(L.BASE + 'painel/disciplinas/visualizar/' + alvo.n);
    const cab = await texto(ad.p, '#numeroOferta');
    await L.print(ad.p, ETAPA, 'disciplina-numero');
    await ad.ctx.close();
    L.ok(mesmoCodigo.length >= 2 && new Set(mesmoCodigo.map((x) => x.n)).size === mesmoCodigo.length,
      `11. a mesma disciplina, com o mesmo código, em turmas diferentes: números diferentes (${mesmoCodigo.slice(0, 4).map((x) => '#' + x.n + ' ' + x.cod + ' ' + x.disc).join(', ')})`);
    L.ok(alvo.n && achadas.includes(alvo.n) && achadas.length < lista.linhas.length,
      `11. a busca da lista acha pelo número (#${alvo.n}: ${achadas.length} linha(s))`);
    L.ok(lista.cabExport[0] === '#' && /^[0-9]+$/.test(String(lista.primeiraExport)),
      `11. o "#" sai nas colunas exportadas (Excel, CSV, PDF e impressão usam as mesmas): ${lista.cabExport.slice(0, 3).join(' | ')}`);
    L.ok(cab === '#' + alvo.n, `11. o "#" no cabeçalho da tela da disciplina ("${cab}")`);

    /* sabotagem: a tela de Faltas sem o ramo da escola */
    const orig = fs.readFileSync(FALTAS, 'utf8');
    const trecho = '$escF = \\App\\adms\\Models\\helper\\AdmsTipoInstituicao::ehEscola();';
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let sab = '';
    try {
      fs.writeFileSync(FALTAS, orig.replace(trecho, '$escF = false;'));
      await s.p.goto(L.BASE + 'painel/faltas/visualizar/' + ofChamada);
      sab = await texto(s.p, '#faltasDaChamada');
    } finally {
      fs.writeFileSync(FALTAS, orig);
    }
    await s.ctx.close();
    L.ok(/oferta|encontro/.test(sab), 'sabotagem (a tela de Faltas sem o ramo da escola): voltam "oferta" e "encontro" — a prova reprova');
    L.ok(sha(FALTAS) === shaF, 'o arquivo sabotado voltou igual (sha256)');

  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(FALTAS) !== shaF) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
