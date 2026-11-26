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
