/*
 * PROVA DA CORREÇÃO DA ESCOLA, PARTE 4 — os textos e as telas (D-103; os achados B7, B11, B17, B18 e C1 a C7 do teste
 * pelo navegador de 22/11). APAGA o banco de prova.
 *   1. a demonstração do colégio;
 *   2. C1: "RM" em toda tela da escola (a lista e a ficha do aluno, a turma, a chamada, o conselho, as notas e resultado,
 *      as faltas, um relatório), por um rótulo só; nenhum "RA" à vista;
 *   3. C2: na escola, sem as palavras do superior — o cartão da disciplina sem o polo, a modalidade e a realização; a
 *      chamada com "aula"; a ficha sem "curso"; a matrícula sem "Trancado";
 *   4. C4/C5/C7: as quantidades sem "(s)"; a prévia do encerramento sem o texto de sistema; as abas da ficha na mesma
 *      caixa; "Definir uma senha"; com todas as etapas fechadas, "Notas e resultado" não diz "Disciplina aberta"; o
 *      "Cadastrar atividade" com um tipo só vai direto;
 *   5. B7: salvar os Parâmetros sem mexer não muda o que estava gravado (a matriz em módulos, a média mínima);
 *   6. B18: o aluno no endereço do portal da família recebe "não permitido" (403);
 *   7. B11 e B17: remover a demonstração pela tela tira também os planos de pagamento e o financeiro dela, e a Página
 *      Inicial e os Primeiros passos levam ao "Como você quer começar?";
 *   sabotagem: o rótulo do número volta a "RA" na escola — a prova acusa; o arquivo volta igual.
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const L = require('../lib');
const X = require('./_escola');

const ETAPA = 'onda8/etapa43';
const ROTULO = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'helper', 'AdmsTipoInstituicao.php');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const { sql, um } = X;
const SENHA_ADM = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const corpo = async (p) => (await p.evaluate(() => { const b = document.querySelector('.page-body') || document.body; return b.innerText; })).replace(/\s+/g, ' ');
const temRA = (t) => /(^|[^A-Za-zÀ-ú])RA([^A-Za-zÀ-ú]|$)/.test(t);
const temS = (t) => /[a-zà-ú]\(s\)/i.test(t);

(async () => {
  const { navegador } = await L.abrir();
  const shaRotulo = sha(ROTULO);
  try {
    L.ok(X.montar('onda8-etapa43').status === 0, 'a demonstração do colégio montada pela tela, numa instalação nova');
    const c = X.cred();
    const ano = new Date().getFullYear();
    /* o administrador abre todas as telas da escola (a secretaria não abre as de notas: a frase "não abre" não provaria nada) */
    const sec = await X.entrar(navegador, 'administrador', SENHA_ADM, 'painel', 1366);
    const p = sec.p;
    const aluno = um("SELECT id FROM col_usuarios WHERE rm = '265001'");
    const turma = um(`SELECT turma_id FROM esc_turma_alunos WHERE usuario_id = ${aluno} AND saida IS NULL ORDER BY id DESC LIMIT 1`);
    const of = um(`SELECT oferta_id FROM esc_turma_ofertas WHERE turma_id = ${turma} ORDER BY oferta_id LIMIT 1`);
    const ofCham = um(`SELECT o.id FROM rm_professores_materias o INNER JOIN esc_turma_ofertas x ON x.oferta_id = o.id WHERE o.ano = ${ano} AND o.frequencia_fonte = 'chamada' ORDER BY o.id LIMIT 1`) || of;
    const enc = um(`SELECT id FROM rm_oferta_encontros WHERE rm_prof_mat_id = ${ofCham} ORDER BY id LIMIT 1`);

    /* 2. C1 e C4: as telas da escola */
    const telas = [
      ['a lista dos alunos', 'academicos/listar'], ['a ficha do aluno', `academicos/visualizar/${aluno}`], ['o cadastro', 'academicos/cadastrar'],
      ['a turma', `turmas/ver/${turma}`], ['o conselho de classe', `escola-conselho/turma/${turma}`], ['as notas e resultado', `medias/final/${of}`],
      ['as faltas', `faltas/visualizar/${of}`], ['a chamada', `chamada/listar/${ofCham}`], ['as atividades da disciplina', `atividades/listar/${of}`], ['a lista de turma (relatório)', 'relatorios/lista-turma'],
    ].concat(enc ? [['a aula da chamada', `chamada/encontro/${ofCham}?id=${enc}`]] : []);
    const ruins = [];
    for (const [nome, rota] of telas) {
      await p.goto(L.BASE + 'painel/' + rota);
      const t = await corpo(p);
      if (temRA(t)) { ruins.push(`${nome}: "RA"`); }
      if (temS(t)) { ruins.push(`${nome}: "${(t.match(/\S*\(s\)\S*/i) || [''])[0]}"`); }
    }
    await p.goto(L.BASE + `painel/academicos/visualizar/${aluno}`);
    const temRM = /(^|\s)RM(\s|$)/.test(await corpo(p));
    L.ok(ruins.length === 0 && temRM, `C1/C4: "RM" em toda tela da escola e nenhuma quantidade com "(s)", em ${telas.length} telas${ruins.length ? ' — fora: ' + ruins.join('; ') : ''}`);

    /* 3. C2 */
    await p.goto(L.BASE + `painel/atividades/listar/${of}`);
    const cartao = await p.textContent('#dadosDisciplina').catch(() => '');
    await p.goto(L.BASE + `painel/chamada/encontro/${ofCham}`);
    const aula = await corpo(p);
    await p.goto(L.BASE + `painel/chamada/listar/${ofCham}`);
    const lista = await corpo(p);
    const porChamada = sql(`SELECT frequencia_fonte FROM rm_professores_materias WHERE id = ${ofCham}`) === 'chamada';
    await p.goto(L.BASE + `painel/academicos/visualizar/${aluno}?tab=tabeMatricula`);
    const situacoes = await p.$$eval('.formVinculo select[name="situacao_id"] option', (l) => l.map((o) => o.textContent.trim()));
    L.ok(!/Polo|Modalidade|Realização/.test(cartao) && /Segmento/.test(cartao) && /Data da aula/.test(aula) && !/encontro/i.test(aula) && !/encontro/i.test(lista) && (!porChamada || /Nova aula/.test(lista))
      && situacoes.length > 0 && !situacoes.includes('Trancado'),
      `C2: o cartão da disciplina sem o polo, a modalidade e a realização; a chamada com "aula" (a aula e a lista${porChamada ? ', com "Nova aula"' : ', com a troca "Chamada por aula"'}); a matrícula sem "Trancado" (${situacoes.join(', ')})`);

    /* 4. C5 e C7 */
    /* a prévia só aparece sem impedimento: uma disciplina do ano passado (completa), reaberta no banco */
    const ofPrev = um(`SELECT o.id FROM rm_professores_materias o INNER JOIN esc_turma_ofertas x ON x.oferta_id = o.id WHERE o.ano = ${ano - 1} AND o.encerrada_em IS NOT NULL ORDER BY o.id LIMIT 1`);
    sql(`UPDATE rm_professores_materias SET encerrada_em = NULL WHERE id = ${ofPrev}`);
    await p.goto(L.BASE + `painel/disciplinas/encerrar/${ofPrev}`);
    const previa = await corpo(p);
    await p.goto(L.BASE + `painel/academicos/visualizar/${aluno}`);
    const abas = await p.$$eval('.nav-tabs .nav-link', (l) => l.map((a) => a.textContent.trim()));
    const senha = await corpo(p);
    L.ok(/Prévia do encerramento/.test(previa) && !/GUARDADA|componente\(s\)/.test(previa) && /o arredondamento é/.test(previa) && abas.every((a) => a !== a.toUpperCase() || a.length < 3) && /Definir uma senha/.test(senha),
      `C5/C7: a prévia do encerramento sem o texto de sistema; as abas da ficha na mesma caixa (${abas.join(', ')}); "Definir uma senha"`);
    const ofFech = um(`SELECT o.id FROM rm_professores_materias o INNER JOIN esc_turma_ofertas x ON x.oferta_id = o.id WHERE o.ano = ${ano} AND o.encerrada_em IS NULL ORDER BY o.id LIMIT 1`);
    const etapas = sql(`SELECT id FROM esc_etapas WHERE ano = ${ano} ORDER BY ordem`).split('\n').map(Number);
    for (const e of etapas) {
      sql(`INSERT INTO esc_fechamentos (oferta_id, etapa_id, numero, regra_json, regra_hash, situacao, fechado_por, fechado_em) SELECT ${ofFech}, ${e}, 1, '{}', REPEAT('0', 64), 'vigente', 1, NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM esc_fechamentos WHERE oferta_id = ${ofFech} AND etapa_id = ${e} AND situacao = 'vigente')`);
    }
    await p.goto(L.BASE + `painel/medias/final/${ofFech}`);
    const sit = (await p.textContent('#situacaoOferta').catch(() => '')).trim();
    const tipos = um("SELECT COUNT(*) FROM rm_disciplina_atividade_tipo WHERE situacao = 1 AND grupo IN (2, 3)") || 0;
    await p.goto(L.BASE + `painel/atividades/listar/${of}`);
    const direto = await p.getAttribute('#botaoCadastrarAtividade', 'href').catch(() => null);
    L.ok(/Todas as etapas estão fechadas/.test(sit) && !/Disciplina aberta/.test(sit) && (tipos !== 1 || /atividades\/cadastrar\//.test(direto || '')),
      `C7: com as etapas fechadas, "${sit.slice(0, 60)}…"; o "Cadastrar atividade" com um tipo só vai direto (${direto || 'janela de escolha'})`);

    /* 5. B7: os Parâmetros */
    sql("UPDATE adms_parametros SET valor = '0' WHERE chave = 'matriz.modulos'");
    sql("UPDATE adms_parametros SET valor = '7.0' WHERE chave = 'nota.media_minima'");
    await sec.ctx.close();
    const adm = await X.entrar(navegador, 'administrador', SENHA_ADM, 'painel', 1366);
    await adm.p.goto(L.BASE + 'painel/parametros/listar');
    const semCampoModulos = !(await adm.p.$('#matriz_modulos'));
    const salvar = await adm.p.$('input[name="SalvarParametros"]');
    await Promise.all([adm.p.waitForNavigation(), salvar.click()]);
    const depois = sql("SELECT CONCAT((SELECT valor FROM adms_parametros WHERE chave = 'matriz.modulos'), '|', (SELECT valor FROM adms_parametros WHERE chave = 'nota.media_minima'))");
    L.ok(semCampoModulos && depois === '0|7.0', `B7: salvar os Parâmetros sem mexer não muda o gravado (a matriz em módulos e a média mínima: ${depois}); na escola o campo dos módulos não aparece`);

    /* 6. B18: o aluno no endereço da família */
    const ra = Object.keys(c.portal || {}).find((k) => /^265/.test(k));
    const al = await X.entrar(navegador, ra, c.portal[ra], 'portal', 1366);
    const resp = await al.p.goto(L.BASE + `portal/familia/filho/${aluno}`);
    const st = resp ? resp.status() : 0;
    const naoPerm = !!(await al.p.$('#naoPermitido')) && !(await al.p.$('input[name="SendLogin"]'));
    await al.ctx.close();
    L.ok(st === 403 && naoPerm, `B18: o aluno que abre um endereço do portal da família recebe "não permitido" (HTTP ${st}), não a tela de entrada`);

    /* 7. B11 e B17: a remoção pela tela */
    sql("INSERT INTO fin_planos (nome, parcelas, valor_centavos, dia_vencimento, mes_primeira, situacao) VALUES ('Plano feito a mao (prova 43)', 2, 10000, 10, 2, 1)");
    await adm.p.goto(L.BASE + 'painel/demonstracao/instituicao');
    await adm.p.fill('#confirmacao', 'COMEÇAR');
    await adm.p.click('#removerDemonstracao');
    await adm.p.waitForSelector('#progressoRemocao[data-situacao="concluida"], #progressoRemocao[data-situacao="falhou"]', { timeout: 600000 });
    const fin = sql("SELECT CONCAT((SELECT COUNT(*) FROM fin_planos), '|', (SELECT COUNT(*) FROM fin_titulos), '|', (SELECT COUNT(*) FROM fin_recibos), '|', (SELECT COUNT(*) FROM fin_operacoes))");
    await adm.p.goto(L.BASE + 'painel/home/index');
    const cartaoComecar = await adm.p.isVisible('#cartaoComecar');
    await adm.p.goto(L.BASE + 'painel/primeiros-passos/index');
    const link = await adm.p.getAttribute('#primeirosComecar a', 'href').catch(() => '');
    L.ok(fin === '0|0|0|0' && cartaoComecar && /demonstracao\/comecar/.test(link),
      `B11/B17: remover a demonstração tira os planos (também o feito à mão) e o financeiro dela (planos|títulos|recibos|operações = ${fin}); a Página Inicial mostra "Como você quer começar?" e os Primeiros passos levam a ele`);
    await adm.ctx.close();

    /* sabotagem: o rótulo volta a "RA" na escola (a demonstração removida: monta de novo) */
    const orig = fs.readFileSync(ROTULO, 'utf8');
    const trecho = "        return self::ehEscola() ? 'RM' : (self::ehLivre() ? 'Matrícula' : 'RA');"; // D-106: o curso livre tem o seu
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let sab = false;
    try {
      fs.writeFileSync(ROTULO, orig.replace(trecho, "        return 'RA';"));
      X.montar('onda8-etapa43-sab');
      const s2 = await X.entrar(navegador, 'secretaria.demo', X.cred().painel['secretaria.demo'], 'painel', 1366);
      await s2.p.goto(L.BASE + 'painel/academicos/listar');
      sab = temRA(await corpo(s2.p));
      await s2.ctx.close();
    } finally {
      fs.writeFileSync(ROTULO, orig);
    }
    L.ok(sab, 'sabotagem (o rótulo do número volta a "RA" na escola): a prova reprova — a lista dos alunos mostra "RA"');
    L.ok(sha(ROTULO) === shaRotulo, 'o arquivo sabotado voltou igual (sha256)');
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(ROTULO) !== shaRotulo) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
