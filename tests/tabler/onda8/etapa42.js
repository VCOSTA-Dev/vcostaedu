/*
 * PROVA DA CORREÇÃO DA ESCOLA, PARTE 3 — o aluno novo e o que entra no meio do ano (D-102; a decisão 3 e os achados A1,
 * A2, A3, B13 e C8 do teste pelo navegador de 22/11). APAGA o banco de prova.
 *   1. a demonstração do colégio;
 *   2. o número proposto (decisão 3): o cadastro abre com o próximo número da sequência; duas telas abertas ao mesmo
 *      tempo recebem o mesmo número: a primeira grava, a segunda é avisada ("já foi usado") e o campo traz o seguinte;
 *   3. a matrícula de hoje pela tela (C8, A1): a turma no mesmo passo (só as da série e do ano escolhidos) e o plano com
 *      a pergunta da parcela inicial, marcada na primeira que ainda não venceu: o aluno entra na turma, em todas as
 *      disciplinas, e nenhum título nasce vencido; a lista dos alunos mostra a série e a turma;
 *   4. A2: o aluno que entrou hoje fica SEM nota (não 0,0) nas etapas que terminaram antes da matrícula, e elas não puxam
 *      a média para baixo; as notas de transferência (a secretaria, numa etapa já fechada) contam na média anual e
 *      aparecem marcadas com "T" no boletim; o professor não as lança (a tela recusa); elas vão para a auditoria;
 *   5. A3: o atestado de matrícula da escola sai para o aluno novo sem a previsão de conclusão e sem o CPF, com a série
 *      e o ano letivo; nenhuma mensagem mostra nome de variável;
 *   6. B13: "Enturmar alunos" mostra a série e o ano de cada um e só oferece os matriculados na série e no ano da turma;
 *   7. a planilha dos responsáveis leva o endereço (opcional);
 *   sabotagem: a etapa antes da matrícula volta a contar (MediaEscola ignora a marca) — a prova acusa; o arquivo volta
 *   igual.
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const L = require('../lib');
const X = require('./_escola');

const ETAPA = 'onda8/etapa42';
const MOTOR = path.join(L.RAIZ, 'core', 'MediaEscola.php');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const { sql, um } = X;
const postar = (p, url, campos) => p.evaluate(async ([u, c]) => {
  const x = await fetch(u, { method: 'POST', credentials: 'same-origin', body: new URLSearchParams(c) });
  return { st: x.status, t: await x.text(), url: x.url };
}, [L.BASE + 'painel/' + url, campos]);
const texto = async (p, sel) => ((await p.textContent(sel).catch(() => '')) || '').replace(/\s+/g, ' ').trim();
const SAIDA = path.join(L.PROVAS, ETAPA);

(async () => {
  const { navegador } = await L.abrir();
  const shaMotor = sha(MOTOR);
  fs.mkdirSync(SAIDA, { recursive: true });
  try {
    L.ok(X.montar('onda8-etapa42').status === 0, 'a demonstração do colégio montada pela tela, numa instalação nova');
    const c = X.cred();
    const ano = new Date().getFullYear();
    const hoje = X.hoje();
    const sec = await X.entrar(navegador, 'secretaria.demo', c.painel['secretaria.demo'], 'painel', 1366);
    const p = sec.p;

    /* 2. o número proposto, e a colisão */
    const maior = um("SELECT MAX(CAST(rm AS UNSIGNED)) FROM col_usuarios WHERE col_niveis_acesso_id = 1 AND rm REGEXP '^[0-9]{1,9}$'");
    const outra = await sec.ctx.newPage();
    await p.goto(L.BASE + 'painel/academicos/cadastrar');
    await outra.goto(L.BASE + 'painel/academicos/cadastrar');
    const proposto = await p.inputValue('#rm');
    const propostoOutra = await outra.inputValue('#rm');
    const cadastrar = async (pg, nome) => {
      await pg.fill('#nome', nome).catch(() => pg.fill('input[name="nome"]', nome));
      await pg.fill('input[name="email"]', nome.toLowerCase().replace(/[^a-z]/g, '') + '@exemplo.invalid');
      await pg.selectOption('select[name="genero_id"]', { index: 1 }).catch(() => {});
      await Promise.all([pg.waitForNavigation(), pg.click('[name="CadUsuarioAcad"]')]);
    };
    await cadastrar(p, 'Aluno Novo Prova Um');
    const idNovo = um(`SELECT id FROM col_usuarios WHERE rm = '${proposto}'`);
    await cadastrar(outra, 'Aluno Novo Prova Dois');
    const avisoColisao = await texto(outra, '.alert-danger');
    const seguinte = await outra.inputValue('#rm').catch(() => '');
    L.ok(+proposto === maior + 1 && propostoOutra === proposto && idNovo > 0 && /já foi usado por um cadastro salvo antes/.test(avisoColisao) && +seguinte === maior + 2,
      `decisão 3: o cadastro abre com o próximo número (${proposto}); as duas telas receberam o mesmo; a primeira gravou; a segunda foi avisada ("${avisoColisao.slice(0, 70)}…") e o campo traz o seguinte (${seguinte})`);
    await L.print(outra, ETAPA, 'numero-ja-usado');
    await Promise.all([outra.waitForNavigation(), outra.click('[name="CadUsuarioAcad"]')]);
    const idDois = um(`SELECT id FROM col_usuarios WHERE rm = '${seguinte}'`);
    await outra.close();

    /* 3. a matrícula de hoje pela tela: a turma no mesmo passo e a parcela inicial */
    const turma = sql(`SELECT CONCAT(t.id, '|', t.modulo_id) FROM esc_turmas t WHERE t.ano = ${ano} ORDER BY t.id LIMIT 1`).split('|').map(Number);
    const [turmaId, modulo] = turma;
    const plano = sql("SELECT CONCAT(id, '|', parcelas) FROM fin_planos WHERE situacao = 1 ORDER BY id LIMIT 1").split('|').map(Number);
    await p.goto(L.BASE + `painel/academicos/visualizar/${idNovo}?tab=tabeMatricula`);
    await p.selectOption('#vAno', String(ano));
    await p.selectOption('#vModulo', String(modulo));
    await p.fill('#vData', hoje);
    const turmasVisiveis = await p.$$eval('#vTurma option', (l) => l.filter((o) => o.value && !o.hidden).map((o) => o.value));
    await p.selectOption('#vTurma', String(turmaId));
    await p.selectOption('#vPlano', String(plano[0]));
    const parcelaMarcada = await p.inputValue('#vParcelaInicial');
    const opcoes = await p.$$eval('#vParcelaInicial option', (l) => l.map((o) => o.textContent));
    await L.print(p, ETAPA, 'matricula-turma-e-parcela');
    await Promise.all([p.waitForNavigation(), p.click('#vincularAluno')]);
    const naTurma = um(`SELECT COUNT(*) FROM esc_turma_alunos WHERE turma_id = ${turmaId} AND usuario_id = ${idNovo} AND saida IS NULL`) === 1;
    const ofertas = um(`SELECT COUNT(*) FROM esc_turma_ofertas WHERE turma_id = ${turmaId}`);
    const nasDisc = um(`SELECT COUNT(*) FROM portal_disciplinas_alunos d INNER JOIN esc_turma_ofertas x ON x.oferta_id = d.rm_prof_mat_id WHERE x.turma_id = ${turmaId} AND d.rm = '${proposto}'`);
    const vencidos = um(`SELECT COUNT(*) FROM fin_titulos WHERE aluno_id = ${idNovo} AND vencimento < CURDATE()`);
    const titulos = um(`SELECT COUNT(*) FROM fin_titulos WHERE aluno_id = ${idNovo}`);
    const primeiraNaoVencida = +parcelaMarcada;
    L.ok(turmasVisiveis.includes(String(turmaId)) && turmasVisiveis.every((id) => um(`SELECT COUNT(*) FROM esc_turmas WHERE id = ${id} AND ano = ${ano} AND modulo_id = ${modulo}`) === 1)
      && naTurma && nasDisc === ofertas && ofertas > 0,
      `C8: a turma no mesmo passo da matrícula (só as ${turmasVisiveis.length} da série e do ano escolhidos): o aluno entrou na turma e nas ${nasDisc} disciplinas dela`);
    L.ok(primeiraNaoVencida > 1 && vencidos === 0 && titulos === plano[1] - primeiraNaoVencida + 1 && opcoes.some((o) => /já vencida/.test(o)),
      `A1: o plano pergunta a partir de qual parcela cobrar — marcada a ${primeiraNaoVencida}ª (a primeira que não venceu; as anteriores aparecem "já vencida"); nasceram ${titulos} títulos, nenhum vencido`);
    await p.goto(L.BASE + 'painel/academicos/listar');
    const linhaLista = await p.$eval(`#tabelaAlunos tr:has(td:text-is("${proposto}"))`, (tr) => ({ serie: tr.querySelector('.vc-serie-lista')?.textContent.trim(), turma: tr.querySelector('.vc-turma-lista')?.textContent.trim() })).catch(() => ({}));
    const th = await p.$$eval('#tabelaAlunos thead th', (l) => l.map((t) => t.textContent.trim()));
    L.ok(th.includes('Série') && th.includes('Turma') && /\d/.test(linhaLista.serie || '') && /\d{4}/.test(linhaLista.turma || ''),
      `C8: a lista dos alunos mostra a série (${linhaLista.serie}) e a turma (${linhaLista.turma})`);

    /* 4. A2: sem nota antes da matrícula; as notas de transferência */
    const etapas = sql(`SELECT CONCAT(id, '|', nome, '|', fim) FROM esc_etapas WHERE ano = ${ano} ORDER BY ordem`).split('\n').map((l) => { const [id, nome, fim] = l.split('|'); return { id: +id, nome, fim }; });
    const antes = etapas.filter((e) => e.fim < hoje);
    const of = um(`SELECT oferta_id FROM esc_turma_ofertas WHERE turma_id = ${turmaId} ORDER BY oferta_id LIMIT 1`);
    await p.goto(L.BASE + `painel/academicos/boletim/${proposto}`);
    await L.semErros(p, 'o boletim do aluno novo');
    const linha = async (pg) => pg.$eval(`.tabela-boletim-escola tr[data-oferta="${of}"]`, (tr) => Array.from(tr.querySelectorAll('td')).map((td) => td.textContent.replace(/\s+/g, ' ').trim())).catch(() => []);
    const b1 = await linha(p);
    const antesSemNota = antes.length > 0 && antes.every((e, i) => b1[1 + i] === '—') && !b1.slice(1, 1 + etapas.length).some((x) => /^0,0/.test(x)) && !/^0,0/.test(b1[etapas.length + 1] || '');
    const marcadas = await p.$$eval(`.tabela-boletim-escola tr[data-oferta="${of}"] .vc-antes-matricula`, (l) => l.length);
    L.ok(antesSemNota && marcadas === antes.length,
      `A2: o aluno que entrou hoje fica sem nota nas ${antes.length} etapa(s) que terminaram antes da matrícula (${b1.slice(1, 1 + etapas.length).join(' | ')}) — nada de 0,0 — e a média anual não é puxada para baixo (${b1[etapas.length + 1] || '—'})`);
    await L.print(p, ETAPA, 'boletim-sem-nota-antes');
    /* o professor não lança */
    const profU = sql(`SELECT u.usuario FROM rm_professores_mat_liga l INNER JOIN adms_usuarios u ON u.id = l.usuario_id WHERE l.rm_prof_mat_id = ${of} AND l.situacao = 1 LIMIT 1`);
    const prof = await X.entrar(navegador, profU, c.painel[profU], 'painel', 1366);
    await prof.p.goto(L.BASE + `painel/escola-transferencia/index/${idNovo}`);
    const profSem = !(await prof.p.$('#formTransferencia'));
    const profPost = await postar(prof.p, `escola-transferencia/salvar/${idNovo}`, { ano: String(ano), escola_origem: 'Escola Tentativa', documento: 'Declaracao', [`nota[${of}][${antes[0].id}]`]: '9' });
    await prof.ctx.close();
    const nadaDoProf = um(`SELECT COUNT(*) FROM esc_notas_transferencia WHERE aluno_id = ${idNovo}`) === 0;
    L.ok(profSem && nadaDoProf, `o professor não lança nota de transferência: nem a tela, nem o endereço (resposta ${profPost.st}; nada gravado)`);
    /* a secretaria lança numa etapa já fechada */
    const fechada = antes.find((e) => um(`SELECT COUNT(*) FROM esc_fechamentos WHERE oferta_id = ${of} AND etapa_id = ${e.id} AND situacao = 'vigente'`) > 0) || antes[0];
    await p.goto(L.BASE + `painel/academicos/visualizar/${idNovo}?tab=tabeMatricula`);
    await Promise.all([p.waitForNavigation(), p.click('.atalhoTransferencia')]);
    await L.semErros(p, 'as notas de transferência');
    await p.fill('#escolaOrigem', 'Escola Municipal Exemplo (ficticia)');
    await p.fill('#documentoTransf', 'Declaracao de transferencia');
    await p.fill(`input[name="nota[${of}][${fechada.id}]"]`, '7,5');
    await p.fill(`input[name="faltas[${of}][${fechada.id}]"]`, '3');
    await Promise.all([p.waitForNavigation(), p.click('#salvarTransferencia')]);
    const ok = await texto(p, '.alert-success');
    await L.print(p, ETAPA, 'notas-de-transferencia');
    const gravada = sql(`SELECT CONCAT(nota, '|', faltas) FROM esc_notas_transferencia WHERE aluno_id = ${idNovo} AND oferta_id = ${of} AND etapa_id = ${fechada.id} AND situacao = 'valida'`);
    await p.goto(L.BASE + `painel/academicos/boletim/${proposto}`);
    const b2 = await linha(p);
    const iT = etapas.findIndex((e) => e.id === fechada.id);
    const comT = await p.$eval(`.tabela-boletim-escola tr[data-oferta="${of}"] td[data-etapa="${fechada.id}"] .vc-nota-transferencia`, (e) => e.getAttribute('title')).catch(() => '');
    const auditou = um(`SELECT COUNT(*) FROM rm_auditoria_lancamentos WHERE oferta_id = ${of} AND rm = '${proposto}' AND campo = 'transferencia' AND valor_novo LIKE '7.5%'`);
    L.ok(/gravadas/.test(ok) && gravada === '7.50|3' && /^7,5/.test(b2[1 + iT] || '') && /Escola Municipal Exemplo/.test(comT) && /^\d/.test(b2[etapas.length + 1] || '') && auditou === 1
      && um(`SELECT COUNT(*) FROM esc_fechamentos WHERE oferta_id = ${of} AND etapa_id = ${fechada.id} AND situacao = 'vigente'`) >= 0,
      `A2: a nota de transferência (7,5 no ${fechada.nome}, já fechado) conta na média anual (${b2[etapas.length + 1]}), aparece marcada "T" no boletim ("${comT}") e foi para a auditoria`);

    /* 5. A3: o atestado da escola */
    const mc = um(`SELECT id FROM rm_matriculas_curso WHERE usuario_id = ${idNovo} ORDER BY id DESC LIMIT 1`);
    sql(`UPDATE rm_matriculas_curso SET previsao_conclusao = NULL WHERE id = ${mc}`);
    sql(`UPDATE col_usuarios_informacoes SET cpf = NULL WHERE col_user_id = ${idNovo}`);
    const em = await postar(p, `historico-emissoes/emitir/${mc}`, { tipo: 'ATESTADO', EmitirHistorico: 'Emitir atestado de matrícula' });
    const emitido = sql(`SELECT IFNULL(MAX(id), 0) FROM rm_historico_emissoes WHERE matricula_curso_id = ${mc} AND tipo = 'ATESTADO'`);
    const html = +emitido ? sql(`SELECT html FROM rm_historico_emissoes WHERE id = ${emitido}`) : '';
    const semVariavel = !/\{\{[A-Z_]+\}\}/.test(em.t.replace(/<[^>]+>/g, ''));
    L.ok(+emitido > 0 && /ano letivo de \d{4}/.test(html) && !/CPF/.test(html) && !/previs/i.test(html) && semVariavel,
      `A3: o atestado da escola sai sem a previsão de conclusão e sem o CPF, com a série e o ano letivo; nenhuma mensagem mostra nome de variável (emissão ${emitido})`);

    /* 6. B13: só os da série e do ano da turma */
    const outraSerie = sql(`SELECT CONCAT(t.id, '|', t.modulo_id) FROM esc_turmas t WHERE t.ano = ${ano} AND t.modulo_id <> ${modulo} ORDER BY t.id LIMIT 1`).split('|').map(Number);
    await p.goto(L.BASE + `painel/academicos/visualizar/${idDois}?tab=tabeMatricula`);
    await p.selectOption('#vAno', String(ano));
    await p.selectOption('#vModulo', String(outraSerie[1]));
    await p.fill('#vData', hoje);
    await p.selectOption('#vPlano', '').catch(() => {});
    await Promise.all([p.waitForNavigation(), p.click('#vincularAluno')]);
    await p.goto(L.BASE + `painel/turmas/ver/${turmaId}`);
    const candA = await p.$$eval('.vc-candidato', (l) => l.map((i) => i.value));
    await p.goto(L.BASE + `painel/turmas/ver/${outraSerie[0]}`);
    const candB = await p.$$eval('.vc-candidato', (l) => l.map((i) => ({ id: i.value, txt: i.closest('label').textContent.replace(/\s+/g, ' ') })));
    const dele = candB.find((x) => +x.id === idDois);
    L.ok(!candA.includes(String(idDois)) && dele && new RegExp(`· ${ano}`).test(dele.txt) && candB.every((x) => um(`SELECT COUNT(*) FROM rm_matriculas_ensino me INNER JOIN rm_matriculas_curso mc ON mc.id = me.matricula_curso_id WHERE mc.usuario_id = ${x.id} AND me.ano = ${ano} AND me.modulo_id = ${outraSerie[1]}`) > 0),
      `B13: o aluno matriculado em outra série não aparece para a turma ${turmaId}; aparece para a da série dele, com a série e o ano ("${dele ? dele.txt.trim() : '—'}")`);

    /* 7. a planilha dos responsáveis com o endereço */
    const csv = `ra;ano;papel;nome;email;celular;cpf;parentesco;motivo;endereco;numero_endereco;complemento;bairro;cidade;uf;cep\n${proposto};${ano};titular;Responsavel Endereco Prova;resp.endereco@exemplo.invalid;;;Mae;;Rua das Provas;42;;Centro;Cidade Exemplo;SP;00000-000\n`;
    const arq = path.join(SAIDA, 'responsaveis-endereco.csv');
    fs.writeFileSync(arq, '﻿' + csv);
    await p.goto(L.BASE + 'painel/responsaveis/importar');
    await p.setInputFiles('#planilha', arq);
    await Promise.all([p.waitForNavigation(), p.click('#conferirPlanilha')]);
    if (await p.$('#aplicarPlanilha')) { await Promise.all([p.waitForNavigation(), p.click('#aplicarPlanilha')]); }
    const end = sql("SELECT CONCAT(IFNULL(endereco, ''), '|', IFNULL(numero, ''), '|', IFNULL(cidade, ''), '|', IFNULL(uf, '')) FROM esc_responsaveis WHERE email = 'resp.endereco@exemplo.invalid'");
    L.ok(end === 'Rua das Provas|42|Cidade Exemplo|SP', `a planilha dos responsáveis leva o endereço, opcional (${end})`);
    await sec.ctx.close();

    /* sabotagem: a etapa antes da matrícula volta a contar */
    const orig = fs.readFileSync(MOTOR, 'utf8');
    const trecho = "            if (!empty($e['antesDaMatricula']) || !empty($e['depoisDaSaida'])) {"; // a Parte H do histórico (D-117) juntou a etapa depois da saída
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let sab = [];
    try {
      fs.writeFileSync(MOTOR, orig.replace(trecho, '            if (false) {'));
      const s2 = await X.entrar(navegador, 'secretaria.demo', c.painel['secretaria.demo'], 'painel', 1366);
      await s2.p.goto(L.BASE + `painel/academicos/boletim/${proposto}`);
      sab = await linha(s2.p);
      await s2.ctx.close();
    } finally {
      fs.writeFileSync(MOTOR, orig);
    }
    const iSem = etapas.findIndex((e) => e.fim < hoje && e.id !== fechada.id);
    L.ok(iSem < 0 || sab[1 + iSem] !== '—', `sabotagem (a etapa antes da matrícula volta a contar): a prova reprova — a etapa mostrou "${iSem < 0 ? '?' : sab[1 + iSem]}"`);
    L.ok(sha(MOTOR) === shaMotor, 'o arquivo sabotado voltou igual (sha256)');
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(MOTOR) !== shaMotor) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
