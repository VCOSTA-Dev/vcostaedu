/*
 * PROVA DA ONDA 8, FINANCEIRO REFEITO, PARTE 1 — as formas de pagamento, cada uma com os dados que ela pede (D-99).
 * APAGA o banco de prova.
 *   1. a demonstração do colégio; um plano de quatro parcelas a vencer aplicado a um aluno (pela tela);
 *   2. o servidor recusa a forma sem os dados dela, e nada é gravado: o Pix sem o CPF ou o CNPJ de quem pagou (ou com um
 *      número inválido); o cartão sem a bandeira, com o final que não tem 4 números, sem a autorização, com 13 parcelas;
 *      a transferência, que não é mais uma forma do Receber;
 *   3. pela tela, cada forma grava os dados dela: o Pix com o documento; o cartão de crédito em 3 parcelas com a bandeira,
 *      o final, o documento e a autorização; o Pix com um CNPJ; o dinheiro sem nada; no
 *      débito, as parcelas travam em 1;
 *   4. a confirmação é a janela do próprio sistema: nenhuma janela do navegador abre;
 *   sabotagem: o Pix sem conferir o documento de quem pagou — a prova acusa; o arquivo volta igual.
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const L = require('../lib');
const X = require('./_escola');

const ETAPA = 'onda8/etapa36';
const MODELO = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'financeiro', 'AdmsFinRecibos.php');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const postar = (p, url, campos) => p.evaluate(async ([u, c]) => {
  const x = await fetch(u, { method: 'POST', credentials: 'same-origin', body: new URLSearchParams(c) });
  return { st: x.status, t: await x.text() };
}, [url, campos]);
const recusa = (t) => ((t.match(/id=['"]financeiroRecusado['"][^>]*>([\s\S]*?)<\/div>/) || [])[1] || '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
const CPF = '000.265.001-08'; // o CPF de demonstração do aluno 265001 (000 e a matrícula; os verificadores certos)

(async () => {
  const { navegador } = await L.abrir();
  const shaModelo = sha(MODELO);
  try {
    L.ok(X.montar('onda8-etapa36').status === 0, 'a demonstração do colégio montada pela tela, numa instalação nova');
    const c = X.cred();
    const sec = await X.entrar(navegador, 'secretaria.demo', c.painel['secretaria.demo'], 'painel', 1536);
    const p = sec.p;
    let janelas = 0;
    p.on('dialog', () => { janelas++; });
    const a = await X.arranjo(p, '265001', { nome: 'Plano das formas', parcelas: 4, valor: '100,00', mes: 12 });
    const [t1, t2, t3, t4] = a.titulos.map((t) => t.id);
    const url = L.BASE + `painel/financeiro/receber/${a.aluno}`;
    const pedido = (forma, extra) => [['titulos[]', String(t1)], ['data', X.hoje()], ['modo', 'unica'], ['formas[0][forma]', forma], ['formas[0][valor]', ''], ['envio', 'f-' + Math.random()],
      ...Object.entries(extra).map(([k, v]) => [`formas[0][${k}]`, v])];
    const cartao = { cartao_tipo: 'credito', cartao_parcelas: '1', bandeira_id: '1', cartao_final: '1234', cartao_documento: 'DOC9', cartao_autorizacao: 'AUT9' };
    const casos = [
      ['o Pix sem o documento', 'pix', {}, /No Pix, informe o CPF ou o CNPJ/],
      ['o Pix com um CPF inválido', 'pix', { documento: '111.111.111-11' }, /No Pix, informe o CPF ou o CNPJ/],
      ['o cartão sem a bandeira', 'cartao', { ...cartao, bandeira_id: '' }, /escolha a bandeira/],
      ['o cartão com o final de 2 números', 'cartao', { ...cartao, cartao_final: '12' }, /4 últimos números/],
      ['o cartão sem a autorização', 'cartao', { ...cartao, cartao_autorizacao: '' }, /autorização/],
      ['o cartão em 13 parcelas', 'cartao', { ...cartao, cartao_parcelas: '13' }, /parcelas vão de 1 a 12/],
      ['a transferência (não é mais uma forma do Receber)', 'transferencia', {}, /Escolha a forma de pagamento/],
    ];
    const res = [];
    for (const [nome, forma, extra, re] of casos) {
      const r = await postar(p, url, pedido(forma, extra));
      res.push([nome, re.test(recusa(r.t)), recusa(r.t)]);
    }
    const nada = X.um(`SELECT COUNT(*) FROM fin_recibo_titulos WHERE titulo_id = ${t1}`) === 0 && X.situacao(t1) === 'aberto';
    L.ok(res.every((x) => x[1]) && nada, `o servidor recusa a forma sem os dados dela, e nada é gravado: ${res.map((x) => `${x[0]} ${x[1] ? 'recusado' : 'PASSOU (' + x[2] + ')'}`).join('; ')}`);

    /* 3. pela tela, cada forma grava os dados dela */
    await X.receber(p, [t1], { forma: 'pix', campos: { documento: CPF } });
    await p.goto(L.BASE + `painel/financeiro/receber-pagamento/${a.aluno}?titulos=${t2}`);
    await p.check('.vc-escolhe-forma[value="cartao"]', { force: true });
    await p.selectOption('#formaUnica [name="formas[0][cartao_tipo]"]', 'debito');
    const debitoTrava = await p.isDisabled('#formaUnica [name="formas[0][cartao_parcelas]"]');
    await p.selectOption('#formaUnica [name="formas[0][cartao_tipo]"]', 'credito');
    await p.selectOption('#formaUnica [name="formas[0][cartao_parcelas]"]', '3');
    await p.selectOption('#formaUnica [name="formas[0][bandeira_id]"]', '2');
    await p.fill('#formaUnica [name="formas[0][cartao_final]"]', '1234');
    await p.fill('#formaUnica [name="formas[0][cartao_documento]"]', 'NSU-55');
    await p.fill('#formaUnica [name="formas[0][cartao_autorizacao]"]', 'A77');
    await L.print(p, ETAPA, 'o-cartao');
    await X.enviar(p, '#formReceber', '#receberFinalizar');
    await X.receber(p, [t3], { forma: 'pix', campos: { documento: '11.222.333/0001-81' } });
    await X.receber(p, [t4], { forma: 'dinheiro' });
    const f = (t) => X.sql(`SELECT CONCAT_WS('|', f.forma, f.valor_centavos, IFNULL(f.pagador_documento, '-'), IFNULL(f.cartao_tipo, '-'), IFNULL(f.cartao_parcelas, '-'), IFNULL(f.bandeira_id, '-'),
      IFNULL(f.cartao_final, '-'), IFNULL(f.cartao_documento, '-'), IFNULL(f.cartao_autorizacao, '-'))
      FROM fin_recibo_formas f JOIN fin_recibo_titulos l ON l.recibo_id = f.recibo_id WHERE l.titulo_id = ${t}`);
    const g = [f(t1), f(t2), f(t3), f(t4)];
    L.ok(g[0] === 'pix|10000|00026500108|-|-|-|-|-|-' && g[1] === 'cartao|10000|-|credito|3|2|1234|NSU-55|A77' && g[2] === 'pix|10000|11222333000181|-|-|-|-|-|-'
      && g[3] === 'dinheiro|10000|-|-|-|-|-|-|-' && debitoTrava,
      `pela tela, cada forma grava os dados dela: ${g.join(' ; ')}; no débito, as parcelas travam em 1`);

    /* 4. nenhuma janela do navegador */
    L.ok(janelas === 0, `a confirmação é a janela do próprio sistema: ${janelas} janela(s) do navegador`);
    await L.semErros(p, 'a tela de Receber');

    /* 4b. os cartões das formas: o ícone em cima, o nome embaixo, sem sobrepor — em 1536 px e numa tela estreita */
    const b2 = await X.arranjo(p, '265005', { nome: 'Plano dos cartoes', parcelas: 1, valor: '100,00', mes: 12 });
    const cartoes = [];
    for (const largura of [1536, 390]) {
      await p.setViewportSize({ width: largura, height: 900 });
      await p.goto(L.BASE + `painel/financeiro/receber-pagamento/${b2.aluno}?titulos=${b2.titulos[0].id}`);
      const caixas = await p.$$eval('.vc-cartao-forma', (l) => l.map((c) => {
        const i = c.querySelector('.vc-forma-icone').getBoundingClientRect();
        const n = c.querySelector('.vc-forma-nome').getBoundingClientRect();
        return { nome: c.querySelector('.vc-forma-nome').textContent.trim(), emCima: i.bottom <= n.top + 0.5, centro: Math.abs((i.left + i.right) / 2 - (n.left + n.right) / 2) < 6 };
      }));
      cartoes.push({ largura, ok: caixas.length === 4 && caixas.every((x) => x.emCima && x.centro), nomes: caixas.map((x) => x.nome).join(', ') });
      await L.print(p, ETAPA, `os-cartoes-${largura}`);
    }
    await p.setViewportSize({ width: 1536, height: 900 });
    L.ok(cartoes.every((x) => x.ok) && cartoes[0].nomes === 'Dinheiro, Pix manual, Cartão, Múltiplos',
      `os cartões das formas (${cartoes[0].nomes}): o ícone em cima e o nome embaixo, sem sobrepor, em ${cartoes.map((x) => x.largura + ' px ' + (x.ok ? 'certo' : 'SOBREPOSTO')).join(' e ')}`);

    /* sabotagem: o Pix sem conferir o documento */
    const orig = fs.readFileSync(MODELO, 'utf8');
    const trecho = 'if (!F::documentoValido($doc)) {';
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let gravou = 0;
    try {
      fs.writeFileSync(MODELO, orig.replace(trecho, 'if (false) {'));
      const b = await X.arranjo(p, '265003', { nome: 'Plano da sabotagem', parcelas: 1, valor: '100,00', mes: 12 });
      await postar(p, L.BASE + `painel/financeiro/receber/${b.aluno}`, [['titulos[]', String(b.titulos[0].id)], ['data', X.hoje()], ['modo', 'unica'], ['formas[0][forma]', 'pix'], ['formas[0][valor]', ''], ['envio', 'sab-' + Date.now()]]);
      gravou = X.um(`SELECT COUNT(*) FROM fin_recibo_titulos WHERE titulo_id = ${b.titulos[0].id}`);
    } finally {
      fs.writeFileSync(MODELO, orig);
    }
    L.ok(gravou !== 0, `sabotagem (o Pix sem conferir o documento de quem pagou): a prova reprova — sem o documento, ${gravou} recibo gravado`);
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
