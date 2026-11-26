/*
 * PROVA DO HISTÓRICO ESCOLAR, PARTE F — a demonstração do colégio com o histórico (D-115). APAGA o banco de prova.
 *   1. a demonstração montada pela tela traz o histórico pronto: os níveis e as séries, as duas escolas fictícias, os
 *      textos e o logo do Fundamental, as matrizes de 2025 (travada) e 2026, e a vida escolar de três alunos (o da casa,
 *      o concluinte e o que veio de outra escola), cada série com a origem certa;
 *   2. um histórico já emitido (o do concluinte): na lista de documentos emitidos e na conferência pública pelo código;
 *   3. pela tela, sem preparar nada: a secretaria acha o aluno da casa, abre a prévia (nenhum bloqueio) e emite; o fecho é
 *      a declaração (retido no 9º ano); o do concluinte é o certificado; o do que veio de outra escola tem a escola de
 *      origem nos estudos realizados e as notas trazidas marcadas com "*" no bloco da transferência;
 *   4. o cartão "Conheça o sistema" manda a secretaria emitir o histórico escolar;
 *   5. sabotagem: a remoção que esquece a vida escolar — sobra a vida escolar dos alunos fictícios (a prova reprova); o
 *      arquivo volta igual;
 *   6. montada de novo (pela linha de comando, numa instalação nova), a remoção pela tela apaga todo o histórico da
 *      demonstração (as tabelas do histórico vazias e as emissões dos alunos fictícios fora).
 *   (A régua da montagem pela tela e pela linha de comando é a da Onda 4, etapa 4, que compara as duas inteiras.)
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { spawnSync } = require('child_process');
const L = require('../lib');
const X = require('../onda8/_escola');

const ETAPA = 'historico/parteF';
const SEED = path.join(L.RAIZ, 'database', 'seed_demo.sql');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const shaB = (b) => crypto.createHash('sha256').update(b).digest('hex');
const { sql, um } = X;
const texto = async (p, sel) => ((await p.textContent(sel).catch(() => '')) || '').replace(/\s+/g, ' ').trim();
const paginas = (b) => (b.toString('latin1').match(/\/Type\s*\/Page(?![a-zA-Z])/g) || []).length;
const PHP = process.env.VCOSTAEDU_PHP || 'php';
const SENHA = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
function instalarEscola() {
  spawnSync(PHP, [path.join(L.RAIZ, 'tests', 'tabler', 'reset.php')], { encoding: 'utf8', cwd: L.RAIZ });
  const r = spawnSync('node', [path.join(L.RAIZ, 'tests', 'tabler', 'instalar.js'), 'normal'], { encoding: 'utf8', cwd: L.RAIZ, env: { ...process.env, VCOSTAEDU_TIPO: 'escola', VCOSTAEDU_ETAPA_PROVA: ETAPA }, maxBuffer: 1 << 26 });
  return r.status === 0;
}
const aluno = (rm) => um(`SELECT id FROM col_usuarios WHERE rm = ${rm}`);
const series = (id) => sql(`SELECT GROUP_CONCAT(CONCAT(serie, ':', ano, ':', origem, ':', resultado) ORDER BY ano, serie SEPARATOR ' ') FROM esc_hist_vida_serie WHERE aluno_id = ${id} AND nivel = 'EF'`);
/* o histórico da demonstração que a remoção tem de levar (as tabelas do histórico, menos o registro do dado de prova) */
const HIST = ['esc_hist_segmento', 'esc_hist_serie', 'esc_hist_texto', 'esc_hist_observacao', 'esc_hist_logo', 'esc_hist_escola', 'esc_hist_aluno', 'esc_hist_alteracao',
  'esc_hist_matriz', 'esc_hist_matriz_item', 'esc_hist_itinerario', 'esc_hist_area', 'esc_hist_disciplina', 'esc_hist_vida_serie', 'esc_hist_vida_nota'];
const contagem = () => HIST.map((t) => `${t}=${um(`SELECT COUNT(*) FROM ${t}`)}`).filter((x) => !/=0$/.test(x));

async function remover(adm) {
  await adm.p.goto(L.BASE + 'painel/demonstracao/instituicao');
  await adm.p.fill('#confirmacao', 'COMEÇAR');
  await adm.p.click('#removerDemonstracao');
  await adm.p.waitForSelector('#progressoRemocao[data-situacao="concluida"], #progressoRemocao[data-situacao="falhou"]', { timeout: 600000 });
  return adm.p.getAttribute('#progressoRemocao', 'data-situacao');
}

(async () => {
  const { navegador } = await L.abrir();
  const shaSeed = sha(SEED);
  try {
    L.ok(X.montar('historico-parteF').status === 0, 'a demonstração do colégio montada pela tela');
    const c = X.cred();

    /* 1. o histórico pronto */
    const [rui, paulo, vitor] = [255003, 255001, 265044].map(aluno);
    const cad = sql(`SELECT CONCAT((SELECT GROUP_CONCAT(nivel ORDER BY nivel) FROM esc_hist_segmento WHERE nivel <> ''), '|', (SELECT COUNT(*) FROM esc_hist_escola), '|',
      (SELECT COUNT(*) FROM esc_hist_escola WHERE esta_escola = 1 AND nome LIKE 'Col_gio Horizonte (fict_cio)'), '|', (SELECT COUNT(*) FROM esc_hist_texto WHERE nivel = 'EF'), '|',
      (SELECT COUNT(*) FROM esc_hist_logo WHERE nivel = 'EF'), '|', (SELECT GROUP_CONCAT(CONCAT(ano, ':', situacao) ORDER BY ano) FROM esc_hist_matriz), '|',
      (SELECT COUNT(*) FROM esc_hist_aluno WHERE ra LIKE '000.000.%'))`);
    L.ok(cad === 'EF,EM|2|1|1|1|2025:travada,2026:rascunho|3',
      `os cadastros: os níveis, as duas escolas ("esta escola" é o colégio), os textos e o logo do Fundamental, as matrizes (2025 travada), o RA fictício de três alunos (${cad})`);
    const sRui = series(rui);
    const sPaulo = series(paulo);
    const sVitor = series(vitor);
    L.ok(sRui === '6:2022:digitado:aprovado 7:2023:digitado:aprovado 8:2024:digitado:aprovado 9:2025:sistema:retido'
      && sPaulo === '6:2022:digitado:aprovado 7:2023:digitado:aprovado 8:2024:digitado:aprovado 9:2025:sistema:aprovado'
      && sVitor === '6:2024:digitado:aprovado 7:2025:digitado:aprovado 8:2026:sistema:em_curso',
      `a vida escolar dos três casos — o da casa: ${sRui}; o concluinte: ${sPaulo}; o que veio de outra escola: ${sVitor}`);
    const origemVitor = sql(`SELECT GROUP_CONCAT(DISTINCT e.nome) FROM esc_hist_vida_serie s JOIN esc_hist_escola e ON e.id = s.escola_id WHERE s.aluno_id = ${vitor} AND s.origem = 'digitado'`);
    const notasDig = um(`SELECT COUNT(*) FROM esc_hist_vida_nota n JOIN esc_hist_vida_serie s ON s.id = n.vida_serie_id WHERE s.aluno_id IN (${rui}, ${paulo}, ${vitor}) AND n.origem = 'digitado'`);
    L.ok(origemVitor === 'Escola Municipal Aurora (fictícia)' && notasDig >= 7 * 8,
      `os anos de outra escola com a escola de origem (${origemVitor}); as médias digitadas dos anos de antes (${notasDig})`);

    /* 2. o histórico já emitido */
    const [idP, codP, sitP] = sql(`SELECT CONCAT(id, '|', codigo, '|', situacao) FROM rm_historico_emissoes WHERE aluno_id = ${paulo} AND tipo = 'ESC_EF'`).split('|');
    const adm = await X.entrar(navegador, 'administrador', SENHA);
    await adm.p.goto(L.BASE + `painel/historico-emissoes/ver/${idP}`);
    const naLista = await texto(adm.p, 'body');
    const pub = await navegador.newContext();
    const pp = await pub.newPage();
    await pp.goto(L.BASE + 'verificar/' + codP);
    const ver = { estado: await pp.getAttribute('#resultadoVerificacao', 'data-estado'), tipo: await texto(pp, '#tipoDocumento'), aluno: await texto(pp, '#alunoVerificado') };
    await L.print(pp, ETAPA, 'conferencia-publica');
    await pub.close();
    L.ok(sitP === '1' && /Histórico escolar — Ensino Fundamental/.test(naLista) && ver.estado === 'valido' && ver.tipo === 'Histórico escolar — Ensino Fundamental' && ver.aluno === 'Paulo Hipotético Lima',
      `o histórico emitido na montagem: na lista de documentos emitidos e na conferência pública pelo código ${codP} (${ver.estado}, ${ver.aluno})`);

    /* 3. pela tela, sem preparar nada */
    const sec = await X.entrar(navegador, 'secretaria.demo', c.painel['secretaria.demo']);
    const p = sec.p;
    await p.goto(L.BASE + 'painel/historico-escola-vida/buscar');
    await p.fill('input[name="q"]', 'Rui');
    await Promise.all([p.waitForNavigation(), p.press('input[name="q"]', 'Enter')]);
    const achou = await texto(p, 'body');
    await L.print(p, ETAPA, 'busca');
    await p.goto(L.BASE + `painel/historico-escola-emissao/previa/${rui}?nivel=EF`);
    const bloq = await texto(p, '#bloqueios');
    await L.print(p, ETAPA, 'previa-aluno-da-casa');
    const n0 = um(`SELECT COUNT(*) FROM rm_historico_emissoes WHERE aluno_id = ${rui}`);
    if (await p.$('#confirmoAvisos')) { await p.check('#confirmoAvisos'); }
    await Promise.all([p.waitForNavigation(), p.click('#emitirHistorico')]);
    const emitiu = await texto(p, '#historicoEmitido');
    const [idR, hashR] = sql(`SELECT CONCAT(id, '|', hash_pdf) FROM rm_historico_emissoes WHERE aluno_id = ${rui} ORDER BY id DESC LIMIT 1`).split('|');
    const snapR = JSON.parse(sql(`SELECT snapshot FROM rm_historico_emissoes WHERE id = ${idR}`));
    const bR = await (await p.request.get(L.BASE + `painel/historico-escola-emissao/pdf/${idR}`)).body();
    fs.mkdirSync(path.join(L.PROVAS, ETAPA), { recursive: true });
    fs.writeFileSync(path.join(L.PROVAS, ETAPA, 'aluno-da-casa.pdf'), bR);
    L.ok(/Rui Fictício Moura/.test(achou) && !bloq && /emitido/.test(emitiu) && um(`SELECT COUNT(*) FROM rm_historico_emissoes WHERE aluno_id = ${rui}`) === n0 + 1
      && snapR.historico.fecho.tipo === 'declaracao' && shaB(bR) === hashR && paginas(bR) === 2,
      `pela tela, sem preparar nada: a busca acha o aluno da casa, a prévia sem bloqueio, a 1ª via emitida; o fecho é a ${snapR.historico.fecho.tipo} (retido no 9º ano); o PDF de ${paginas(bR)} páginas confere com o sha256`);
    const snapP = JSON.parse(sql(`SELECT snapshot FROM rm_historico_emissoes WHERE id = ${idP}`));
    await p.goto(L.BASE + `painel/historico-escola-emissao/previa-pdf/${vitor}?nivel=EF&formato=html`);
    const htmlV = await p.content();
    L.ok(snapP.historico.fecho.tipo === 'certificado' && /Escola Municipal Aurora \(fictícia\)/.test(htmlV) && /\d,\d\*/.test(htmlV),
      `o concluinte tem o ${snapP.historico.fecho.tipo}; o que veio de outra escola, a escola de origem nos estudos realizados e as notas trazidas com "*" no bloco da transferência`);

    /* 4. o cartão "Conheça o sistema" */
    await adm.p.goto(L.BASE + 'painel/home/index');
    const cartao = await texto(adm.p, '#tabelaPerfisDemo');
    L.ok(/histórico escolar/i.test(cartao), 'o cartão "Conheça o sistema" manda a secretaria emitir o histórico escolar');
    await sec.ctx.close();

    /* 5. sabotagem: a remoção que esquece a vida escolar */
    const orig = fs.readFileSync(SEED, 'utf8');
    const trecho = "SET @q := IF(@hist = 1, 'DELETE FROM esc_hist_vida_serie WHERE aluno_id IN (SELECT id FROM demo_alunos)', 'DO 0');";
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let sobraSab = [];
    try {
      fs.writeFileSync(SEED, orig.replace(trecho, "SET @q := 'DO 0';"));
      await remover(adm);
      sobraSab = contagem();
    } finally {
      fs.writeFileSync(SEED, orig);
    }
    L.ok(sobraSab.some((x) => x.startsWith('esc_hist_vida_serie=')), `sabotagem (a remoção sem a vida escolar): a prova reprova — sobrou ${sobraSab.join(', ') || 'nada'}`);
    L.ok(sha(SEED) === shaSeed, 'o arquivo sabotado voltou igual (sha256)');
    await adm.ctx.close();

    /* 6. montada de novo (linha de comando, instalação nova), a remoção pela tela leva todo o histórico */
    const inst = instalarEscola();
    const senha = SENHA;
    const cli = spawnSync(PHP, [path.join(L.RAIZ, 'database', 'ferramentas', 'demonstracao.php'), 'montar', L.BASE], { encoding: 'utf8', cwd: L.RAIZ, env: process.env, input: `administrador\n${senha}\n`, maxBuffer: 1 << 26 });
    const antes = contagem();
    const antesEmis = um("SELECT COUNT(*) FROM rm_historico_emissoes WHERE tipo LIKE 'ESC%'");
    const adm2 = await X.entrar(navegador, 'administrador', senha);
    const sit = await remover(adm2);
    const sobra = contagem();
    const emisAntes = antesEmis;
    const emis = um("SELECT COUNT(*) FROM rm_historico_emissoes WHERE tipo LIKE 'ESC%'");
    L.ok(inst && cli.status === 0 && antes.length >= 10 && sit === 'concluida' && sobra.length === 0 && emisAntes === 1 && emis === 0,
      `a remoção pela tela leva todo o histórico da demonstração (antes: ${antes.length} tabelas com linhas; depois: ${sobra.join(', ') || 'nenhuma'}; emissões do histórico escolar: ${emisAntes} → ${emis})`);
    await adm2.ctx.close();
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(SEED) !== shaSeed) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
