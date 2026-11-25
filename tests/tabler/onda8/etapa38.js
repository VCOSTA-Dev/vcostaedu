/*
 * PROVA DA ONDA 8, FINANCEIRO REFEITO, PARTE 1 — o recibo no portal, as listas em Configurações e a página pública (D-99).
 * APAGA o banco de prova.
 *   1. a demonstração do colégio; a aluna da ficha tem um responsável financeiro em 2025 e outro em 2026;
 *   2. o portal: o financeiro de 2026 vê a ficha só de leitura com o botão do recibo nos títulos recebidos de 2026, e não no
 *      de 2025; o recibo abre; o de 2025, pedido direto, é recusado (403); a financeira de 2025 abre o de 2025;
 *   3. Configurações: o motivo novo do Desfazer recebimento aparece na tela do desfazer; fora de uso, some; a bandeira
 *      nova aparece no cartão do Receber;
 *   4. a página inicial pública não mostra a versão (D-40); o painel, depois de entrar, mostra;
 *   sabotagem: o recibo do portal sem a conferência do financeiro do ano — a prova acusa; o arquivo volta igual.
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const L = require('../lib');
const X = require('./_escola');

const ETAPA = 'onda8/etapa38';
const CONTROLE = path.join(L.RAIZ, 'portal', 'app', 'adms', 'Controllers', 'Familia.php');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');

(async () => {
  const { navegador } = await L.abrir();
  const shaControle = sha(CONTROLE);
  try {
    L.ok(X.montar('onda8-etapa38').status === 0, 'a demonstração do colégio montada pela tela, numa instalação nova');
    const c = X.cred();
    const id = X.um("SELECT id FROM col_usuarios WHERE rm = '265022'");
    const email = (ano) => X.sql(`SELECT r.email FROM esc_responsavel_designacoes d JOIN esc_responsaveis r ON r.id = d.responsavel_id WHERE d.aluno_id = ${id} AND d.ano = ${ano} AND d.papel = 'financeiro' AND d.fim IS NULL`);
    const [e26, e25] = [email(2026), email(2025)];
    const rec25 = X.um(`SELECT id FROM fin_recibos WHERE aluno_id = ${id} AND ano = 2025 AND situacao = 'valido'`);
    L.ok(e26 && e25 && e26 !== e25 && rec25 > 0, 'o arranjo da demonstração: a aluna da ficha com um financeiro em 2025 e outro em 2026, e um recibo de 2025');

    /* 2. o portal */
    const f26 = await X.entrar(navegador, e26, c.familia[e26], 'portal', 1280);
    await f26.p.goto(L.BASE + `portal/familia/financeiro/${id}?ano=2026`);
    await L.print(f26.p, ETAPA, 'o-portal-com-o-recibo');
    const cartoes = await f26.p.$$eval('.vc-titulo-portal', (l) => l.map((x) => ({ texto: x.innerText, recibo: (x.querySelector('.vc-recibo-portal') || {}).href || '' })));
    const com26 = cartoes.filter((x) => x.recibo && /Anuidade/.test(x.texto));
    const de25 = cartoes.find((x) => /Mensalidade de dezembro/.test(x.texto)) || {};
    const r1 = await f26.p.goto(com26[0] ? com26[0].recibo : L.BASE + `portal/familia/recibo/${id}?recibo=0`);
    const abriu = r1.status() === 200 && !!(await f26.p.$('#recibo .via'));
    const r2 = await f26.p.goto(L.BASE + `portal/familia/recibo/${id}?recibo=${rec25}`);
    const f25 = await X.entrar(navegador, e25, c.familia[e25], 'portal', 1280);
    const r3 = await f25.p.goto(L.BASE + `portal/familia/recibo/${id}?ano=2025&recibo=${rec25}`);
    await L.print(f25.p, ETAPA, 'o-recibo-de-2025-no-portal');
    await f25.ctx.close();
    L.ok(com26.length >= 5 && de25.texto && !de25.recibo && abriu && r2.status() === 403 && r3.status() === 200,
      `o portal: o financeiro de 2026 vê o botão do recibo em ${com26.length} títulos de 2026 e não no de 2025; o recibo abre; o de 2025 pedido direto: ${r2.status()}; a financeira de 2025 o abre: ${r3.status()}`);

    /* 3. Configurações: os motivos e as bandeiras */
    const sec = await X.entrar(navegador, 'secretaria.demo', c.painel['secretaria.demo'], 'painel', 1366);
    await sec.p.goto(L.BASE + 'painel/fin-motivos/listar');
    await sec.p.selectOption('#novaOperacao', 'desfazer_recebimento');
    await sec.p.fill('#novoNome', 'Motivo criado pela prova');
    await Promise.all([sec.p.waitForNavigation(), sec.p.click('#incluirNaLista')]);
    const mid = X.um("SELECT id FROM fin_motivos WHERE nome = 'Motivo criado pela prova'");
    const sup = await X.entrar(navegador, 'supervisao.demo', c.painel['supervisao.demo'], 'painel', 1366);
    const recIris = X.um(`SELECT id FROM fin_recibos WHERE aluno_id = ${id} AND ano = 2026 AND situacao = 'valido' LIMIT 1`);
    await sup.p.goto(L.BASE + `painel/financeiro/desfazer-recebimento/${recIris}`);
    const temNovo = await sup.p.$$eval('#motivoDesfazer option', (l) => l.map((o) => o.textContent.trim()).includes('Motivo criado pela prova'));
    await sec.p.goto(L.BASE + 'painel/fin-motivos/listar');
    await sec.p.selectOption(`tr[data-id="${mid}"] select[name="situacao"]`, '2');
    await Promise.all([sec.p.waitForNavigation(), sec.p.click(`tr[data-id="${mid}"] button[type="submit"]`)]);
    await sup.p.goto(L.BASE + `painel/financeiro/desfazer-recebimento/${recIris}`);
    const sumiu = !(await sup.p.$$eval('#motivoDesfazer option', (l) => l.map((o) => o.textContent.trim()).includes('Motivo criado pela prova')));
    await sup.ctx.close();
    await sec.p.goto(L.BASE + 'painel/fin-bandeiras/listar');
    await sec.p.fill('#novoNome', 'Bandeira da prova');
    await Promise.all([sec.p.waitForNavigation(), sec.p.click('#incluirNaLista')]);
    await L.print(sec.p, ETAPA, 'as-bandeiras');
    const aberto = X.um(`SELECT id FROM fin_titulos WHERE aluno_id = ${id} AND situacao = 'aberto' AND ano = 2026 ORDER BY vencimento LIMIT 1`);
    await sec.p.goto(L.BASE + `painel/financeiro/receber-pagamento/${id}?titulos=${aberto}`);
    await sec.p.check('.vc-escolhe-forma[value="cartao"]', { force: true });
    const bandeiras = await sec.p.$$eval('#formaUnica [name="formas[0][bandeira_id]"] option', (l) => l.map((o) => o.textContent.trim()));
    L.ok(temNovo && sumiu && bandeiras.includes('Bandeira da prova') && bandeiras.includes('Visa'),
      `Configurações: o motivo novo aparece no Desfazer recebimento (${temNovo}) e some fora de uso (${sumiu}); a bandeira nova no cartão do Receber (${bandeiras.length - 1} bandeiras)`);

    /* 3b. o responsável do recibo (D-99): todos os títulos do mesmo ano, o financeiro daquele ano; de anos diferentes, o
       do ano letivo corrente, e a tela avisa; o título de um ano sem matrícula mostra o traço no lugar da série */
    const t25 = X.um(`SELECT id FROM fin_titulos WHERE aluno_id = ${id} AND ano = 2025 AND situacao = 'aberto'`);
    const t26 = X.um(`SELECT id FROM fin_titulos WHERE aluno_id = ${id} AND ano = 2026 AND situacao = 'aberto' ORDER BY vencimento LIMIT 1`);
    const nome = (ano) => X.sql(`SELECT r.nome FROM esc_responsavel_designacoes d JOIN esc_responsaveis r ON r.id = d.responsavel_id WHERE d.aluno_id = ${id} AND d.ano = ${ano} AND d.papel = 'financeiro' AND d.fim IS NULL`);
    await sec.p.goto(L.BASE + `painel/financeiro/receber-pagamento/${id}?titulos=${t25}`);
    const so25 = { pagador: (await sec.p.textContent('#pagador')).trim(), serie: (await sec.p.textContent('#serieDoAno')).trim(), aviso: !!(await sec.p.$('#avisoAnosReceber')) };
    await L.semErros(sec.p, 'o Receber de um título de 2025 (o ano sem matrícula)');
    await sec.p.goto(L.BASE + `painel/financeiro/receber-pagamento/${id}?titulos=${t25},${t26}`);
    const misto = { pagador: (await sec.p.textContent('#pagador')).trim(), aviso: (await sec.p.textContent('#avisoAnosReceber').catch(() => '')).trim() };
    await L.print(sec.p, ETAPA, 'o-receber-de-dois-anos');
    await sec.p.goto(L.BASE + `painel/financeiro/ficha/${id}?modo=receber`);
    await sec.p.check(`#tabelaTitulos input.vc-marca[value="${t25}"]`);
    await sec.p.check(`#tabelaTitulos input.vc-marca[value="${t26}"]`);
    const loteMisto = { aviso: await sec.p.isVisible('#avisoAnos'), livre: !(await sec.p.isDisabled('#continuarLote')) };
    L.ok(so25.pagador === nome(2025) && so25.serie === '—' && !so25.aviso && misto.pagador === nome(2026) && /anos letivos diferentes \(2025 e 2026\).*2026, o ano letivo corrente/.test(misto.aviso)
      && loteMisto.aviso && loteMisto.livre,
      `o responsável do recibo: só 2025, o financeiro de 2025 (${so25.pagador}), com o traço no lugar da série; 2025 e 2026 juntos, o de 2026 (${misto.pagador}) e o aviso "${misto.aviso.slice(0, 70)}…"; no modo lote, o aviso e o Continuar livre`);

    /* 3c. nenhum aviso do PHP em tela nenhuma do financeiro */
    const recIris2 = X.um(`SELECT id FROM fin_recibos WHERE aluno_id = ${id} LIMIT 1`);
    const estIris = X.um(`SELECT id FROM fin_recibos WHERE aluno_id = ${id} AND situacao = 'estornado' LIMIT 1`);
    const telas = [`financeiro/ficha/${id}`, `financeiro/ficha/${id}?ano=2025`, `financeiro/ficha/${id}?aba=pendencias`, `financeiro/ficha/${id}?aba=anteriores`,
      `financeiro/ficha/${id}?ver=renegociacoes`, `financeiro/ficha/${id}?ver=historico`, `financeiro/ficha/${id}?modo=baixar`, `financeiro/ficha/${id}?campo=receb&periodo=estemes`,
      `financeiro/receber-pagamento/${id}?titulos=${t26}`, `financeiro/recibo/${recIris2}`, `financeiro/recibo/${estIris}`, `financeiro/titulo/${t25}`,
      'fin-motivos/listar', 'fin-bandeiras/listar', 'fin-regras/index', 'fin-planos/listar', 'relatorios/caixa', 'relatorios/recebimentos'];
    const avisos = [];
    for (const t of telas) {
      await sec.p.goto(L.BASE + 'painel/' + t);
      const txt = await sec.p.evaluate(() => document.body.innerText);
      if (/(Warning|Notice|Deprecated|Fatal error|Undefined (array key|variable|index))/.test(txt)) { avisos.push(t); }
    }
    L.ok(avisos.length === 0, `nenhum aviso do PHP nas ${telas.length} telas do financeiro${avisos.length ? ' — com aviso: ' + avisos.join(', ') : ''}`);

    /* 4. a página pública sem a versão; o painel com */
    const pub = await (await navegador.newContext()).newPage();
    await pub.goto(L.BASE);
    const textoPub = await pub.evaluate(() => document.body.innerText);
    await sec.p.goto(L.BASE + 'painel/home/index');
    const rodape = await sec.p.$eval('.vc-legal-versao', (e) => e.textContent.trim()).catch(() => '');
    const versao = X.sql("SELECT numero FROM adms_versoes WHERE situacao = 1 ORDER BY id DESC LIMIT 1");
    L.ok(!/Vers[ãa]o/i.test(textoPub) && !/1\.18\.4/.test(textoPub) && rodape === versao,
      `a página inicial pública sem a versão (D-40); o painel, depois de entrar, com a versão ${rodape}`);
    await sec.ctx.close();

    /* sabotagem: o recibo do portal sem conferir o financeiro do ano */
    const orig = fs.readFileSync(CONTROLE, 'utf8');
    const trecho = "if (!$r || !$this->acesso()->podeVerFinanceiro($resp, (int) $a['id'], (int) $r['ano'])) {";
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let st = 0;
    try {
      fs.writeFileSync(CONTROLE, orig.replace(trecho, 'if (!$r) {'));
      st = (await f26.p.goto(L.BASE + `portal/familia/recibo/${id}?recibo=${rec25}`)).status();
    } finally {
      fs.writeFileSync(CONTROLE, orig);
    }
    L.ok(st !== 403, `sabotagem (o recibo do portal sem conferir o financeiro do ano): a prova reprova — o recibo de 2025 abriu para o financeiro de 2026 (${st})`);
    L.ok(sha(CONTROLE) === shaControle, 'o arquivo sabotado voltou igual (sha256)');
    await f26.ctx.close();
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(CONTROLE) !== shaControle) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
