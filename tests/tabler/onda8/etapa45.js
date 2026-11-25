/*
 * PROVA DO FINANCEIRO REFEITO, PARTE 2 — Renegociar, no desenho do sistema de origem (D-105). APAGA o banco de prova.
 *   1. a demonstração do colégio, com o acordo parcelado (a entrada recebida) e a proposta salva, pela tela; as regras do
 *      padrão (2%, 1% ao mês); um plano de quatro parcelas vencidas aplicado a um aluno;
 *   2. a data da renegociação: dez dias antes, a tela mostra a conta do servidor para aquela data (a conta daqui); a tela
 *      mexida (o valor que ela mostrou não é o do servidor) é recusada, e nada é gravado;
 *   3. a parcelada: a entrada de 15%, três parcelas no dia 10 (uma com o vencimento mudado na tela): a entrada é a 01, as
 *      parcelas vencem nos meses seguintes, a última leva os centavos, tudo soma o valor a renegociar; o acordo tem o
 *      número seguinte e os títulos no padrão de lá ("Acordo Parcelado de … - n. X", "01.04");
 *   4. a parcela do acordo vencida paga juros e não paga multa (o título normal paga os dois);
 *   5. a à vista, com 10% de desconto: um título só ("01.01"), o valor menos o desconto;
 *   6. salvar e efetivar depois: a proposta salva (na aba, "Salvo"); as regras mudam; efetivada pela aba, os valores
 *      são os da proposta (a conta não é refeita); a proposta fica formalizada, ligada ao acordo;
 *   7. Desfazer renegociação: sem a observação, recusado; com o motivo e a observação, feito; a proposta vencida (a
 *      data passou) aparece "Salvo vencido", sem o Efetivar, e o endereço dele recusa; a proposta nova dos mesmos
 *      títulos substitui a salva ("Substituído");
 *   8. o clique duplo: o mesmo envio do Efetivar, duas vezes ao mesmo tempo, dá um acordo só;
 *   9. os três impressos em PDF: a proposta, o acordo efetivado e o termo de confissão de dívida;
 *  10. as permissões: o professor não abre a tela; quem opera o financeiro sem o "Desfazer no financeiro" não vê o
 *      Desfazer e o endereço recusa;
 *  11. o histórico registra salvar, efetivar e desfazer; o portal da família mostra o original "renegociado no acordo";
 *   sabotagem: a parcela do acordo volta a pagar multa — a prova acusa; o arquivo volta igual.
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { spawnSync } = require('child_process');
const L = require('../lib');
const X = require('./_escola');

const ETAPA = 'onda8/etapa45';
const MODELO = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'financeiro', 'AdmsFinanceiro.php');
const SAIDA = path.join(__dirname, '..', 'saida');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const brl = (c) => (c / 100).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
const mult = (a, b, d) => Math.floor((2 * a * b + d) / (2 * d));
/** A conta, feita aqui de novo: a multa (2%, uma vez) e os juros (1% ao mês, por dia), o meio para cima. */
function encargos(v, venc, data, multaBp = 200, jurosBp = 100) {
  const dias = Math.round((Date.parse(data + 'T00:00:00Z') - Date.parse(venc + 'T00:00:00Z')) / 86400000);
  if (dias <= 0) { return v; }
  return v + mult(v, multaBp, 10000) + mult(v, jurosBp * dias, 30 * 10000);
}
const pdfTexto = (buf, nome) => {
  const arq = path.join(SAIDA, `onda8-etapa45-${nome}.pdf`);
  fs.mkdirSync(SAIDA, { recursive: true });
  fs.writeFileSync(arq, buf);
  const r = spawnSync('pdftotext', ['-enc', 'UTF-8', '-layout', arq, '-'], { encoding: 'utf8' });
  return r.status === 0 ? r.stdout.replace(/\s+/g, ' ') : null;
};
/* o POST direto, sem seguir o redirecionamento (a mensagem da recusa fica na sessão para a próxima tela) */
const postar = (p, url, campos) => p.evaluate(async ([u, c]) => (await fetch(u, { method: 'POST', credentials: 'same-origin', redirect: 'manual', body: new URLSearchParams(c) })).type, [url, campos]);
const regras = async (p, juros) => {
  await p.goto(L.BASE + 'painel/fin-regras/index');
  await p.fill('#multa', '2'); await p.fill('#juros', juros); await p.fill('#desconto', '0');
  await Promise.all([p.waitForNavigation(), p.click('#salvarRegras')]);
};
const PERFIL_LOGIN = { 3: 'direcao.demo', 4: 'secretaria.demo', 5: 'coord.demo', 10: 'supervisao.demo' };

(async () => {
  const { navegador } = await L.abrir();
  const shaModelo = sha(MODELO);
  try {
    L.ok(X.montar('onda8-etapa45').status === 0, 'a demonstração do colégio montada pela tela, numa instalação nova');
    const c = X.cred();

    /* 1. a demonstração: o acordo parcelado com a entrada recebida e a proposta salva */
    const a33 = X.um("SELECT id FROM col_usuarios WHERE rm = '265033'");
    const a22 = X.um("SELECT id FROM col_usuarios WHERE rm = '265022'");
    const demo = X.sql(`SELECT CONCAT(r.condicao, '|', r.entrada_centavos > 0, '|', (SELECT situacao FROM fin_titulos WHERE renegociacao_id = r.id AND parcela = 1), '|', (SELECT COUNT(*) FROM fin_titulos WHERE renegociado_por = r.id))
      FROM fin_renegociacoes r WHERE r.aluno_id = ${a33} AND r.situacao = 'vigente'`);
    const propDemo = X.sql(`SELECT CONCAT(situacao, '|', data_renegociacao = CURDATE(), '|', (SELECT COUNT(*) FROM fin_proposta_titulos WHERE proposta_id = p.id)) FROM fin_propostas p WHERE aluno_id = ${a22} AND situacao <> 'formalizada'`); // a aluna da ficha tem também o acordo de antes (a proposta dele, formalizada)
    L.ok(demo === 'parcelada|1|recebido|3' && propDemo === 'salva|1|2',
      `a demonstração, pela tela: o acordo parcelado de 265033 (${demo}: a entrada recebida, três originais) e a proposta salva de 265022 (${propDemo})`);

    const sup = await X.entrar(navegador, 'supervisao.demo', c.painel['supervisao.demo']);
    await regras(sup.p, '1');
    const a = await X.arranjo(sup.p, '265002', { parcelas: 4 });
    const [t1, t2, t3, t4] = a.titulos;
    const hoje = X.hoje();

    /* 2. a data da renegociação e a tela mexida */
    const antes = X.diasAntes(10);
    const contaAntes = encargos(50000, t1.vencimento, antes) + encargos(50000, t2.vencimento, antes);
    const nRen = X.um(`SELECT COUNT(*) FROM fin_renegociacoes WHERE aluno_id = ${a.aluno}`);
    const mexida = await X.renegociar(sup.p, a.aluno, [t1.id, t2.id], { data: antes, entrada: '', parcelas: 2, dia: 10, acao: 'parcelada',
      antes: (p) => p.evaluate(() => { const v = document.getElementById('valorTela'); v.value = String(+v.value + 1); }) });
    await L.print(sup.p, ETAPA, 'a-conta-mudou');
    L.ok(mexida.tela.valor === contaAntes && /A conta mudou/.test(mexida.recusa) && X.um(`SELECT COUNT(*) FROM fin_renegociacoes WHERE aluno_id = ${a.aluno}`) === nRen,
      `a data da renegociação (${antes}): a tela mostrou R$ ${brl(mexida.tela.valor)}, a conta daqui para aquela data (R$ ${brl(contaAntes)}); com o valor da tela mexido, recusado: "${(mexida.recusa || '').slice(0, 90)}"; nada gravado`);

    /* 3. a parcelada: a entrada de 15%, três parcelas no dia 10, a última com os centavos */
    let V = encargos(50000, t1.vencimento, hoje) + encargos(50000, t2.vencimento, hoje);
    let acr = 0;
    while ((V + acr - mult(V + acr, 1500, 10000)) % 3 === 0) { acr += 1; }
    V += acr;
    const entrada = mult(V, 1500, 10000);
    const saldo = V - entrada;
    const vp = Math.floor(saldo / 3);
    const venc3 = X.noMes(hoje, 2, 20);
    const numAntes = X.um("SELECT ultimo FROM fin_sequencias WHERE nome = 'acordo'");
    const r3 = await X.renegociar(sup.p, a.aluno, [t1.id, t2.id], { acrescimo: acr ? brl(acr) : undefined, justificativa: acr ? 'Ajuste de centavos (prova)' : undefined,
      entradaTipo: 'pct', entrada: '15', entradaVenc: hoje, parcelas: 3, dia: 10, vencimentos: { 3: venc3 }, motivo: 41, observacao: 'Acordo da prova', acao: 'parcelada' });
    const esperadas = [['01.04', hoje, entrada], ['02.04', X.noMes(hoje, 1, 10), vp], ['03.04', X.noMes(hoje, 2, 10), vp], ['04.04', X.noMes(hoje, 3, 10), saldo - 2 * vp]];
    const ac3 = X.sql(`SELECT CONCAT(numero, '|', condicao, '|', entrada_centavos, '|', total_centavos - desconto_centavos) FROM fin_renegociacoes WHERE id = ${r3.ren}`);
    const tit3 = X.sql(`SELECT CONCAT(LPAD(parcela, 2, '0'), '.', LPAD(parcelas_total, 2, '0'), '|', vencimento, '|', valor_centavos, '|', sem_multa, '|', descricao) FROM fin_titulos WHERE renegociacao_id = ${r3.ren} ORDER BY parcela`).split('\n');
    const nomeOk = tit3.every((l) => /\|1\|Acordo Parcelado de .+ - n\. \d+$/.test(l) && l.endsWith(` - n. ${numAntes + 1}`));
    const gravadas = tit3.map((l) => l.split('|').slice(0, 3).join('|'));
    const telaOk = JSON.stringify(r3.tela.parcelas) === JSON.stringify(esperadas.map(([n, v, c2], i) => [n, i === 2 ? X.noMes(hoje, 2, 10) : v, c2]));
    const dbEsperado = esperadas.map(([n, v, c2], i) => `${n}|${i === 2 ? venc3 : v}|${c2}`);
    await sup.p.goto(L.BASE + `painel/financeiro/ficha/${a.aluno}`);
    await L.print(sup.p, ETAPA, 'o-acordo-parcelado-na-ficha');
    L.ok(r3.recusa === '' && telaOk && JSON.stringify(gravadas) === JSON.stringify(dbEsperado) && ac3 === `${numAntes + 1}|parcelada|${entrada}|${V}` && nomeOk
      && esperadas.reduce((s, e) => s + e[2], 0) === V && saldo % 3 !== 0,
      `a parcelada: R$ ${brl(V)} = entrada de 15% (R$ ${brl(entrada)}, a 01) + 3 parcelas (R$ ${brl(vp)}, R$ ${brl(vp)} e R$ ${brl(saldo - 2 * vp)}, a última com os centavos); `
      + `o vencimento da 03 mudado na tela (${venc3}); gravado: ${gravadas.join(' · ')}; o acordo nº ${ac3.split('|')[0]} (o seguinte); "${(tit3[0] || '').split('|')[4]}"`);

    /* 4. a parcela do acordo vencida: juros, sem multa */
    const p02 = X.um(`SELECT id FROM fin_titulos WHERE renegociacao_id = ${r3.ren} AND parcela = 2`);
    X.sql(`UPDATE fin_titulos SET vencimento = '${X.diasAntes(10)}' WHERE id = ${p02}`);
    const encAc = () => sup.p.evaluate(async (u) => (await fetch(u, { credentials: 'same-origin' })).json(), L.BASE + `painel/financeiro/encargos/${p02}?data=${hoje}`);
    const encNormal = await sup.p.evaluate(async (u) => (await fetch(u, { credentials: 'same-origin' })).json(), L.BASE + `painel/financeiro/encargos/${t3.id}?data=${hoje}`);
    const e02 = await encAc();
    L.ok(e02.multa === 0 && e02.juros === mult(vp, 100 * 10, 30 * 10000) && e02.juros > 0 && encNormal.multa > 0,
      `a parcela do acordo vencida há 10 dias: juros de R$ ${brl(e02.juros)} e multa de R$ ${brl(e02.multa)}; o título normal paga multa (R$ ${brl(encNormal.multa)})`);

    /* sabotagem: a parcela do acordo volta a pagar a multa */
    const orig = fs.readFileSync(MODELO, 'utf8');
    const trecho = "if (!empty($t['sem_multa'])) {";
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let sab = null;
    try {
      fs.writeFileSync(MODELO, orig.replace(trecho, "if (false && !empty($t['sem_multa'])) {"));
      sab = await encAc();
    } finally {
      fs.writeFileSync(MODELO, orig);
    }
    L.ok(sab && sab.multa > 0, `sabotagem (a parcela do acordo sem a marca "sem multa"): a prova reprova — a multa passou a R$ ${brl(sab ? sab.multa : 0)}`);
    L.ok(sha(MODELO) === shaModelo, 'o arquivo sabotado voltou igual (sha256)');

    /* 5. a à vista, com 10% de desconto */
    const V3 = encargos(50000, t3.vencimento, hoje);
    const r5 = await X.renegociar(sup.p, a.aluno, [t3.id], { vDescontoTipo: 'pct', vDesconto: '10', vVenc: hoje, acao: 'avista' });
    const tit5 = X.sql(`SELECT CONCAT(LPAD(parcela, 2, '0'), '.', LPAD(parcelas_total, 2, '0'), '|', valor_centavos, '|', vencimento, '|', descricao) FROM fin_titulos WHERE renegociacao_id = ${r5.ren}`).split('\n');
    const cond5 = X.sql(`SELECT condicao FROM fin_renegociacoes WHERE id = ${r5.ren}`);
    L.ok(r5.recusa === '' && r5.tela.avista === V3 - mult(V3, 1000, 10000) && tit5.length === 1 && tit5[0].startsWith(`01.01|${V3 - mult(V3, 1000, 10000)}|${hoje}|Acordo à Vista de `) && cond5 === 'avista',
      `a à vista: R$ ${brl(V3)} menos 10% = R$ ${brl(r5.tela.avista)}, num título só: ${tit5.join(' · ')}`);

    /* 6. salvar e efetivar depois, sem refazer a conta */
    const r6 = await X.renegociar(sup.p, a.aluno, [t4.id], { entrada: '100,00', entradaVenc: hoje, parcelas: 2, dia: 25, acao: 'salvar' });
    await sup.p.goto(L.BASE + `painel/financeiro/ficha/${a.aluno}?ver=renegociacoes`);
    const naAba = (await sup.p.textContent(`#tabelaRenegociacoes tr[data-proposta="${r6.proposta}"] .vc-situacao-ren`).catch(() => '')).trim();
    await L.print(sup.p, ETAPA, 'a-proposta-salva');
    const prop6 = X.sql(`SELECT CONCAT(situacao, '|', total_centavos) FROM fin_propostas WHERE id = ${r6.proposta}`);
    const parc6 = X.sql(`SELECT GROUP_CONCAT(CONCAT(numero, ':', valor_centavos) ORDER BY numero) FROM fin_proposta_parcelas WHERE proposta_id = ${r6.proposta} AND condicao = 'parcelada'`);
    await regras(sup.p, '5'); // os juros mudam: a proposta salva não muda
    const ef6 = await X.efetivarProposta(sup.p, a.aluno, r6.proposta, 'parcelada');
    await regras(sup.p, '1');
    const ren6 = X.um(`SELECT renegociacao_id FROM fin_propostas WHERE id = ${r6.proposta}`);
    const depois6 = X.sql(`SELECT CONCAT(p.situacao, '|', r.total_centavos - r.desconto_centavos, '|', (SELECT GROUP_CONCAT(CONCAT(parcela, ':', valor_centavos) ORDER BY parcela) FROM fin_titulos WHERE renegociacao_id = r.id))
      FROM fin_propostas p JOIN fin_renegociacoes r ON r.id = p.renegociacao_id WHERE p.id = ${r6.proposta}`);
    L.ok(prop6 === `salva|${r6.tela.valor}` && naAba === 'Salvo' && ef6 === '' && ren6 > 0 && depois6 === `formalizada|${r6.tela.valor}|${parc6}` && X.situacao(t4.id) === 'renegociado',
      `salvar e efetivar depois: a proposta nº ${r6.proposta} salva (${prop6}; na aba, "${naAba}"); com os juros mudados para 5%, efetivada pela aba com os valores dela: ${depois6} (as parcelas da proposta: ${parc6})`);

    /* 7. desfazer (recusado sem a observação; feito com ela), a vencida e a substituída */
    const sem = await postar(sup.p, L.BASE + `painel/financeiro/desfazer-renegociacao/${r5.ren}`, { motivo_id: '82', observacao: '', envio: 'prova-sem-obs' });
    await sup.p.goto(L.BASE + `painel/financeiro/ficha/${a.aluno}?ver=renegociacoes`);
    const recSem = await sup.p.$eval('#financeiroRecusado', (e) => e.textContent.trim()).catch(() => '');
    const des5 = await X.desfazerRenegociacao(sup.p, a.aluno, r5.ren, { motivo: 82, observacao: 'A família preferiu parcelar (prova)' });
    const fim5 = X.sql(`SELECT CONCAT(situacao, '|', motivo_desfazer) FROM fin_renegociacoes WHERE id = ${r5.ren}`);
    L.ok(sem === 'opaqueredirect' && /observação/.test(recSem) && des5 === '' && fim5 === 'desfeita|Feito por engano: A família preferiu parcelar (prova)' && X.situacao(t3.id) === 'aberto',
      `Desfazer renegociação: sem a observação, recusado ("${recSem.slice(0, 70)}"); com o motivo da lista e a observação, feito (${fim5}); o original voltou a aberto`);
    const r7 = await X.renegociar(sup.p, a.aluno, [t3.id], { entrada: '', parcelas: 2, dia: 10, acao: 'salvar' });
    X.sql(`UPDATE fin_propostas SET data_renegociacao = '${X.diasAntes(1)}' WHERE id = ${r7.proposta}`);
    await sup.p.goto(L.BASE + `painel/financeiro/ficha/${a.aluno}?ver=renegociacoes`);
    const linha7 = `#tabelaRenegociacoes tr[data-proposta="${r7.proposta}"]`;
    const sit7 = (await sup.p.textContent(`${linha7} .vc-situacao-ren`)).trim();
    const temEfetivar = !!(await sup.p.$(`${linha7} .vc-item-efetivar`));
    await L.print(sup.p, ETAPA, 'a-proposta-vencida');
    await postar(sup.p, L.BASE + `painel/financeiro/efetivar-proposta/${r7.proposta}`, { condicao: 'parcelada', envio: 'prova-vencida' });
    await sup.p.goto(L.BASE + `painel/financeiro/ficha/${a.aluno}?ver=renegociacoes`);
    const recVenc = await sup.p.$eval('#financeiroRecusado', (e) => e.textContent.trim()).catch(() => '');
    const r7b = await X.renegociar(sup.p, a.aluno, [t3.id], { entrada: '', parcelas: 3, dia: 10, acao: 'salvar' });
    const subst = X.sql(`SELECT CONCAT(situacao, '|', substituida_por) FROM fin_propostas WHERE id = ${r7.proposta}`);
    L.ok(sit7 === 'Salvo vencido' && !temEfetivar && /venceu/.test(recVenc) && subst === `substituida|${r7b.proposta}` && X.situacao(t3.id) === 'aberto',
      `a proposta vencida: na aba, "${sit7}", sem o Efetivar; pelo endereço, recusada ("${recVenc.slice(0, 80)}"); a proposta nova dos mesmos títulos a substituiu (${subst})`);

    /* 8. o clique duplo no Efetivar */
    const duplo = { condicao: 'avista', envio: 'prova-duplo-' + Date.now() };
    const url8 = L.BASE + `painel/financeiro/efetivar-proposta/${r7b.proposta}`;
    await Promise.all([postar(sup.p, url8, duplo), postar(sup.p, url8, duplo)]);
    await postar(sup.p, url8, duplo);
    const acordos8 = X.um(`SELECT COUNT(*) FROM fin_renegociacoes WHERE proposta_id = ${r7b.proposta}`);
    const tit8 = X.um(`SELECT COUNT(*) FROM fin_titulos t JOIN fin_renegociacoes r ON r.id = t.renegociacao_id WHERE r.proposta_id = ${r7b.proposta}`);
    L.ok(acordos8 === 1 && tit8 === 1 && X.situacao(t3.id) === 'renegociado', `o clique duplo: o mesmo envio, três vezes (duas ao mesmo tempo), deu ${acordos8} acordo e ${tit8} título`);

    /* 9. os três impressos */
    const prop3 = X.um(`SELECT proposta_id FROM fin_renegociacoes WHERE id = ${r3.ren}`);
    const num3 = X.um(`SELECT numero FROM fin_renegociacoes WHERE id = ${r3.ren}`);
    const baixar = async (rota, nome) => { const r = await sup.p.request.get(L.BASE + rota); return { tipo: r.headers()['content-type'] || '', texto: pdfTexto(await r.body(), nome) }; };
    const pdfP = await baixar(`painel/financeiro/impresso-renegociacao/${prop3}?tipo=proposta`, 'proposta');
    const pdfA = await baixar(`painel/financeiro/impresso-renegociacao/${r3.ren}?tipo=acordo`, 'acordo');
    const pdfT = await baixar(`painel/financeiro/impresso-renegociacao/${r3.ren}?tipo=termo`, 'termo');
    const tem = (pdf, ...ts) => pdf.texto !== null && /pdf/.test(pdf.tipo) && ts.every((t) => pdf.texto.includes(t));
    L.ok(tem(pdfP, `Proposta de acordo nº ${prop3}`, 'Condição 1', 'parcelada', 'Condição 2', 'à vista', brl(V), 'Testemunha 1', 'DEMONSTRAÇÃO')
      && tem(pdfA, `Acordo nº ${num3}`, 'Os lançamentos do acordo', '01.04', '04.04', brl(saldo - 2 * vp), 'não paga multa')
      && tem(pdfT, 'Termo de confissão de dívida', 'DEVEDOR', 'CREDORA', 'Cláusula 1ª', brl(V), 'reais', 'Testemunha 2'),
      `os três impressos em PDF (o texto lido pelo pdftotext): a proposta (as duas condições, as testemunhas), o acordo (os lançamentos 01.04 a 04.04) e o termo (as partes, as cláusulas, o total por extenso)`
      + (pdfP.texto === null ? ' — falta o pdftotext' : ''));

    /* 10. as permissões */
    const prof = await X.entrar(navegador, 'prof.laura.demo', c.painel['prof.laura.demo']);
    await prof.p.goto(L.BASE + `painel/financeiro/renegociar-titulos/${a.aluno}?titulos=${t1.id}`);
    const profAbriu = !!(await prof.p.$('#renegociarTitulos'));
    await prof.ctx.close();
    const nivelSemDesfazer = X.um(`SELECT MIN(n.adms_niveis_acesso_id) FROM adms_nivacs_pgs n WHERE n.adms_pagina_id = 575 AND n.permissao = 1 AND n.adms_niveis_acesso_id IN (3, 4, 5, 10)
      AND NOT EXISTS (SELECT 1 FROM adms_nivacs_pgs d WHERE d.adms_pagina_id = 576 AND d.permissao = 1 AND d.adms_niveis_acesso_id = n.adms_niveis_acesso_id)`);
    let semItem = false;
    let recPerm = 'ninguém sem o Desfazer';
    const ren10 = X.um(`SELECT id FROM fin_renegociacoes WHERE aluno_id = ${a.aluno} AND situacao = 'vigente' AND id = ${r3.ren}`);
    if (nivelSemDesfazer && PERFIL_LOGIN[nivelSemDesfazer]) {
      const lg = PERFIL_LOGIN[nivelSemDesfazer];
      const op = await X.entrar(navegador, lg, c.painel[lg]);
      await op.p.goto(L.BASE + `painel/financeiro/ficha/${a.aluno}?ver=renegociacoes`);
      semItem = !!(await op.p.$(`#tabelaRenegociacoes tr[data-renegociacao="${ren10}"]`)) && !(await op.p.$(`#tabelaRenegociacoes tr[data-renegociacao="${ren10}"] .vc-item-desfazer-ren`));
      await postar(op.p, L.BASE + `painel/financeiro/desfazer-renegociacao/${ren10}`, { motivo_id: '81', observacao: 'Tentativa sem a permissão', envio: 'prova-perm' });
      recPerm = X.sql(`SELECT situacao FROM fin_renegociacoes WHERE id = ${ren10}`);
      await op.ctx.close();
    }
    L.ok(!profAbriu && semItem && recPerm === 'vigente',
      `as permissões: o professor não abre "Renegociar títulos"; o ${PERFIL_LOGIN[nivelSemDesfazer] || '—'} (opera o financeiro, sem o "Desfazer no financeiro") vê o acordo sem o Desfazer, e o endereço não desfaz (o acordo continua ${recPerm})`);

    /* 11. o histórico e o portal da família */
    await sup.p.goto(L.BASE + `painel/financeiro/ficha/${a.aluno}?ver=historico`);
    const tipos = await sup.p.$$eval('#tabelaHistorico tbody tr[data-tipo]', (l) => [...new Set(l.map((t) => t.dataset.tipo))]);
    const email = X.sql(`SELECT r.email FROM esc_responsavel_designacoes d JOIN esc_responsaveis r ON r.id = d.responsavel_id WHERE d.aluno_id = ${a22} AND d.ano = 2026 AND d.papel = 'financeiro' AND d.fim IS NULL`);
    const fam = await X.entrar(navegador, email, c.familia[email], 'portal', 1280);
    await fam.p.goto(L.BASE + `portal/familia/financeiro/${a22}?ano=2026`);
    const noPortal = await fam.p.$$eval('.vc-acordo', (l) => l.map((x) => x.textContent.trim()));
    await L.print(fam.p, ETAPA, 'o-portal-com-o-acordo');
    await fam.ctx.close();
    L.ok(['proposta', 'renegociar', 'desfazer_renegociacao', 'gerar'].every((t) => tipos.includes(t)) && noPortal.length === 2 && noPortal.every((t) => /^renegociado no acordo nº \d+$/.test(t)),
      `o histórico registra ${tipos.filter((t) => /proposta|renegociar|desfazer_renegociacao/.test(t)).join(', ')}; o portal da família mostra os dois originais "${noPortal[0] || ''}"`);
    await sup.ctx.close();
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(MODELO) !== shaModelo) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
