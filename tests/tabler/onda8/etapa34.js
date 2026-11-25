/*
 * PROVA DA ONDA 8, FINANCEIRO REFEITO, PARTE 1 — a ficha financeira no desenho do sistema de origem (D-99). APAGA o banco
 * de prova.
 *   1. a demonstração do colégio, montada pela tela numa instalação nova; a aluna da ficha (títulos em todas as situações,
 *      em dois anos letivos, com o responsável financeiro de cada ano);
 *   2. os três totais (em atraso, a vencer com o próximo vencimento, pago no ano) e o resumo (inadimplente, o último
 *      pagamento) batem com o banco;
 *   3. a contagem de cada situação bate com o banco; cada filtro de situação mostra só a dela;
 *   4. as abas: Pendências (tudo em aberto, de todos os anos), Anos anteriores (um ano por vez), Atual (o ano escolhido,
 *      os seguintes e o em aberto de antes, com a linha do atraso de ano anterior marcada);
 *   5. o período: por recebimento, personalizado — só os recebidos no intervalo;
 *   6. a troca de ano: o cabeçalho, os totais, os responsáveis e a tabela mudam para o ano escolhido;
 *   7. o modo lote: a seleção conta e soma; anos diferentes no Receber são avisados (o recibo sai no responsável do ano
 *      letivo corrente); Continuar leva à tela de
 *      Receber com os títulos marcados;
 *   8. os rótulos: "RM" na escola; "Atrasado" em vermelho; os responsáveis com o CPF, o WhatsApp (só o link) e o endereço;
 *   sabotagem: o total "Em atraso" somando também os a vencer — a prova acusa; o arquivo volta igual.
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const L = require('../lib');
const X = require('./_escola');

const ETAPA = 'onda8/etapa34';
const MODELO = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'financeiro', 'AdmsFinFicha.php');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const centavos = (t) => +String(t).replace(/[^\d,]/g, '').replace(',', '') || 0;
const linhas = (p) => p.$$eval('#tabelaTitulos tbody tr[data-titulo][data-situacao]', (l) => l.map((t) => ({ id: +t.dataset.titulo, chave: t.dataset.chave, ano: +t.dataset.ano,
  laranja: t.classList.contains('vc-atraso-anterior'), situacao: t.querySelector('.vc-situacao-titulo').textContent.trim(), receb: t.querySelector('.vc-col-data-receb').dataset.order || '' })));
const contagens = (p) => p.$$eval('#filtroSituacao .vc-chip', (l) => Object.fromEntries(l.map((a) => [a.dataset.situacao, +a.querySelector('.vc-contagem').textContent])));
const totais = (p) => p.evaluate(() => Object.fromEntries(['totalAtraso', 'totalAVencer', 'totalPago'].map((id) => [id, document.querySelector('#' + id + ' .vc-total').textContent.trim()])));

(async () => {
  const { navegador } = await L.abrir();
  const shaModelo = sha(MODELO);
  try {
    L.ok(X.montar('onda8-etapa34').status === 0, 'a demonstração do colégio montada pela tela, numa instalação nova');
    const c = X.cred();
    const sec = await X.entrar(navegador, 'secretaria.demo', c.painel['secretaria.demo'], 'painel', 1536);
    const p = sec.p;
    const id = X.um("SELECT id FROM col_usuarios WHERE rm = '265022'");
    const hoje = X.hoje();
    const base = L.BASE + `painel/financeiro/ficha/${id}`;
    await p.goto(base);
    await L.print(p, ETAPA, 'a-ficha');

    /* 2. os totais e o resumo, contra o banco (o recorte da aba Atual do ano escolhido, 2026) */
    const esc = `t.aluno_id = ${id} AND (t.ano >= 2026 OR t.situacao = 'aberto')`;
    const bAtraso = X.um(`SELECT COALESCE(SUM(valor_centavos), 0) FROM fin_titulos t WHERE ${esc} AND situacao = 'aberto' AND vencimento < '${hoje}'`);
    const bAVencer = X.um(`SELECT COALESCE(SUM(valor_centavos), 0) FROM fin_titulos t WHERE ${esc} AND situacao = 'aberto' AND vencimento >= '${hoje}'`);
    const bPago = X.um(`SELECT COALESCE(SUM(l.recebido_centavos), 0) FROM fin_titulos t JOIN fin_recibo_titulos l ON l.titulo_id = t.id AND l.situacao = 'valido' WHERE t.aluno_id = ${id} AND t.ano = 2026 AND t.situacao = 'recebido'`);
    const proximo = X.sql(`SELECT DATE_FORMAT(MIN(vencimento), '%d/%m/%Y') FROM fin_titulos t WHERE ${esc} AND situacao = 'aberto' AND vencimento >= '${hoje}'`);
    const ult = X.sql(`SELECT CONCAT(DATE_FORMAT(data_recebimento, '%d/%m/%Y'), '|', SUM(total_centavos)) FROM fin_recibos WHERE aluno_id = ${id} AND situacao = 'valido' GROUP BY data_recebimento ORDER BY data_recebimento DESC LIMIT 1`).split('|');
    const t = await totais(p);
    const avTexto = (await p.textContent('#totalAVencer')).replace(/\s+/g, ' ');
    const selo = (await p.textContent('#seloSituacao')).trim();
    const ultTela = (await p.textContent('#ultimoPagamento')).replace(/\s+/g, ' ');
    L.ok(centavos(t.totalAtraso) === bAtraso && centavos(t.totalAVencer) === bAVencer && centavos(t.totalPago) === bPago && avTexto.includes('próximo ' + proximo)
      && selo === 'Inadimplente' && ultTela.includes(ult[0]) && centavos((ultTela.match(/R\$ ([\d.,]+)/) || [])[1]) === +ult[1],
      `os totais batem com o banco: em atraso ${t.totalAtraso} (${bAtraso}), a vencer ${t.totalAVencer} com o próximo ${proximo}, pago em 2026 ${t.totalPago} (${bPago}); ${selo}; "${ultTela.trim()}"`);

    /* 3. as contagens e os filtros de situação */
    const cont = await contagens(p);
    const sitB = (w) => X.um(`SELECT COUNT(*) FROM fin_titulos t WHERE ${esc} AND ${w}`);
    const esperado = { todos: sitB('1 = 1'), avencer: sitB(`situacao = 'aberto' AND vencimento >= '${hoje}'`), atrasados: sitB(`situacao = 'aberto' AND vencimento < '${hoje}'`),
      abertos: sitB("situacao = 'aberto'"), recebidos: sitB("situacao = 'recebido'"), baixados: sitB("situacao = 'baixado'"), cancelados: sitB("situacao = 'cancelado'"),
      prescritos: sitB("situacao = 'prescrito'"), renegociados: sitB("situacao = 'renegociado'") };
    const filtrosOk = [];
    for (const k of ['atrasados', 'recebidos', 'renegociados', 'abertos']) {
      await p.goto(base + `?situacao=${k}`);
      const l = await linhas(p);
      filtrosOk.push(l.length === esperado[k] && l.every((x) => (k === 'abertos' ? ['atrasados', 'avencer'].includes(x.chave) : x.chave === k)));
    }
    L.ok(JSON.stringify(cont) === JSON.stringify(esperado) && filtrosOk.every(Boolean) && esperado.recebidos > 0 && esperado.renegociados > 0 && esperado.baixados > 0 && esperado.cancelados > 0,
      `as contagens batem com o banco (${JSON.stringify(cont)}); cada filtro (atrasados, recebidos, renegociados, abertos) mostra só a dele: ${filtrosOk.join(', ')}`);

    /* 4. as abas */
    await p.goto(base + '?aba=pendencias');
    const pend = await linhas(p);
    const nPend = X.um(`SELECT COUNT(*) FROM fin_titulos WHERE aluno_id = ${id} AND situacao = 'aberto'`);
    const contPend = +(await p.textContent('#abasLista a[data-aba="pendencias"] .vc-contagem-aba'));
    await L.print(p, ETAPA, 'a-aba-pendencias');
    await p.goto(base + '?aba=anteriores');
    const ant = await linhas(p);
    const anoAnt = await p.$eval('#anoAnterior', (s) => s.value).catch(() => '');
    await p.goto(base);
    const atual = await linhas(p);
    const laranjas = atual.filter((x) => x.laranja);
    L.ok(pend.length === nPend && contPend === nPend && pend.every((x) => ['atrasados', 'avencer'].includes(x.chave)) && pend.some((x) => x.ano === 2025)
      && anoAnt === '2025' && ant.length === 2 && ant.every((x) => x.ano === 2025)
      && atual.some((x) => x.ano === 2025 && x.chave === 'atrasados') && !atual.some((x) => x.ano === 2025 && x.chave === 'recebidos') && laranjas.length === 1 && laranjas[0].ano === 2025,
      `as abas: Pendências ${pend.length} (tudo em aberto, de 2025 e 2026); Anos anteriores ${anoAnt}, ${ant.length} títulos; Atual com o em aberto de 2025 (a linha laranja) e sem o recebido de 2025`);

    /* 5. o período por recebimento, personalizado */
    await p.goto(base + '?campo=receb&periodo=personalizado&de=2026-09-01&ate=2026-09-30');
    const set = await linhas(p);
    const nSet = X.um(`SELECT COUNT(*) FROM fin_titulos t JOIN fin_recibo_titulos l ON l.titulo_id = t.id AND l.situacao = 'valido' JOIN fin_recibos r ON r.id = l.recibo_id
      WHERE ${esc} AND r.data_recebimento BETWEEN '2026-09-01' AND '2026-09-30'`);
    L.ok(set.length === nSet && nSet >= 2 && set.every((x) => x.receb >= '2026-09-01' && x.receb <= '2026-09-30'),
      `o período por recebimento (setembro de 2026): ${set.length} títulos, todos recebidos no intervalo (o banco: ${nSet})`);

    /* 6. a troca de ano */
    await p.goto(base);
    await Promise.all([p.waitForNavigation(), p.click('#fichaAnos a.vc-pilula-ano[data-ano="2025"]')]);
    await L.print(p, ETAPA, 'a-troca-de-ano');
    const ano = (await p.textContent('#anoEscolhido')).trim();
    const pago25 = (await p.textContent('#totalPago')).replace(/\s+/g, ' ');
    const resp25 = await p.$$eval('.vc-responsavel', (l) => l.map((r) => r.innerText.replace(/\s+/g, ' ')));
    const nome25 = X.sql(`SELECT r.nome FROM esc_responsavel_designacoes d JOIN esc_responsaveis r ON r.id = d.responsavel_id WHERE d.aluno_id = ${id} AND d.ano = 2025 AND d.papel = 'financeiro' AND d.fim IS NULL`);
    const pagoB25 = X.um(`SELECT COALESCE(SUM(l.recebido_centavos), 0) FROM fin_titulos t JOIN fin_recibo_titulos l ON l.titulo_id = t.id AND l.situacao = 'valido' WHERE t.aluno_id = ${id} AND t.ano = 2025`);
    const tab25 = await linhas(p);
    L.ok(ano === '2025' && /Pago em 2025/.test(pago25) && centavos((await totais(p)).totalPago) === pagoB25 && resp25.length === 1 && resp25[0].includes(nome25) && tab25.some((x) => x.ano === 2025 && x.chave === 'recebidos'),
      `a troca de ano: o cabeçalho em ${ano}, "${pago25.trim()}" (${pagoB25}), os responsáveis de 2025 (${nome25}), a tabela com o recebido de 2025`);

    /* 7. o modo lote */
    await p.goto(base + '?modo=receber');
    const abertos26 = (await linhas(p)).filter((x) => x.ano === 2026 && x.chave === 'atrasados').map((x) => x.id);
    const mat25 = (await linhas(p)).find((x) => x.ano === 2025).id;
    await p.check(`#tabelaTitulos input.vc-marca[value="${abertos26[0]}"]`);
    await p.check(`#tabelaTitulos input.vc-marca[value="${abertos26[1]}"]`);
    const soma = await p.$$eval('#tabelaTitulos input.vc-marca:checked', (l) => l.reduce((s, c) => s + +c.dataset.valor, 0));
    const resumo = (await p.textContent('#resumoSelecao')).trim();
    await L.print(p, ETAPA, 'o-modo-lote');
    await p.check(`#tabelaTitulos input.vc-marca[value="${mat25}"]`);
    const misturado = { aviso: await p.isVisible('#avisoAnos'), livre: !(await p.isDisabled('#continuarLote')) }; // D-99: no Receber, anos diferentes avisam e não travam
    await p.uncheck(`#tabelaTitulos input.vc-marca[value="${mat25}"]`);
    await Promise.all([p.waitForNavigation(), p.click('#continuarLote')]);
    const destino = p.url();
    L.ok(/^2 selecionado\(s\)/.test(resumo) && centavos(resumo.split('·')[1]) === soma && misturado.aviso && misturado.livre
      && destino.includes(`receber-pagamento/${id}?titulos=${abertos26[0]},${abertos26[1]}`),
      `o modo lote: "${resumo}"; com um título de 2025 junto, o aviso dos anos (o recibo no responsável do ano corrente) e o Continuar livre; Continuar leva ao Receber com os dois (${destino.split('painel/')[1]})`);

    /* 8. os rótulos e os responsáveis */
    await p.goto(base);
    const numero = (await p.textContent('#fichaNumeroNome')).trim();
    const atrasados = await p.$$eval('#tabelaTitulos tr[data-chave="atrasados"] .vc-situacao-titulo', (l) => l.map((b) => [b.textContent.trim(), b.className]));
    const r1 = await p.$eval('.vc-responsavel[data-financeiro="1"]', (r) => ({ cpf: (r.querySelector('.vc-cpf') || {}).textContent || '', wa: (r.querySelector('.vc-whatsapp') || {}).href || '',
      end: !!r.querySelector('.vc-ver-endereco') }));
    await p.click('.vc-responsavel[data-financeiro="1"] .vc-ver-endereco');
    await p.waitForTimeout(400);
    const endereco = await p.$eval('.vc-responsavel[data-financeiro="1"] .vc-endereco', (e) => (e.offsetParent ? e.textContent : ''));
    L.ok(numero.startsWith('RM: 265022') && atrasados.length > 0 && atrasados.every(([txt, cls]) => txt === 'Atrasado' && /bg-red/.test(cls))
      && /^\d{3}\.\d{3}\.\d{3}-\d{2}$/.test(r1.cpf) && /^https:\/\/wa\.me\/55\d{10,11}$/.test(r1.wa) && /Rua dos Exemplos/.test(endereco),
      `os rótulos: "${numero.slice(0, 20)}…" (RM na escola); o atrasado "Atrasado" em vermelho; o responsável financeiro com o CPF ${r1.cpf}, o WhatsApp (${r1.wa}) e o endereço ("${endereco.trim().slice(0, 40)}…")`);
    await L.semErros(p, 'a ficha financeira');

    /* sabotagem: o "Em atraso" somando também os a vencer */
    const orig = fs.readFileSync(MODELO, 'utf8');
    const trecho = "if ($naAtual($t) && $t['chave'] === 'atrasados') {";
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let s = 0;
    try {
      fs.writeFileSync(MODELO, orig.replace(trecho, "if ($naAtual($t) && $t['situacao'] === 'aberto') {"));
      await p.goto(base);
      s = centavos((await totais(p)).totalAtraso);
    } finally {
      fs.writeFileSync(MODELO, orig);
    }
    L.ok(s !== bAtraso, `sabotagem (o "Em atraso" com os a vencer): a prova reprova — a tela mostrou ${s}, o banco diz ${bAtraso}`);
    L.ok(sha(MODELO) === shaModelo, 'o arquivo sabotado voltou igual (sha256)');
    await sec.ctx.close();
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(MODELO) !== shaModelo) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
