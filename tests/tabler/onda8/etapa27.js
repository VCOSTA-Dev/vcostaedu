/*
 * PROVA DA ONDA 8, FINANCEIRO, PARTE 2 — Receber e Desfazer recebimento (D-98; refeita contra a tela de Receber
 * pagamento e o recibo, D-99). APAGA o banco de prova.
 *   1. a demonstração do colégio, montada pela tela numa instalação nova; as regras (2%, 1% ao mês) e um plano de três
 *      parcelas que vencem no dia 20, aplicado a um aluno (pela tela);
 *   2. Receber pagamento, com a conta na hora: ao escolher a data do dia 18 (antes do vencimento do dia 20), a tela mostra
 *      a multa e os juros zerados; com o dia 21, a multa e os juros de 1 dia; gravado com o dia 21, o servidor recalcula
 *      e grava a multa (2% de 500,00 = 10,00) e os juros (0,17): 510,17;
 *   3. a outra parcela, recebida com a data do dia 18: nada de encargos (500,00);
 *   4. o clique duplo (o mesmo envio duas vezes) grava UM recibo; duas pessoas no mesmo título: a segunda recebe
 *      "este título já está recebido" e nada muda;
 *   5. a secretaria não desfaz (a permissão é outra: o item não aparece no menu e o pedido recebe a página sem acesso); a
 *      supervisão desfaz o recibo, com o motivo da lista e a justificativa (sem o motivo, recusa): o título volta a
 *      aberto (vencido, pela data), o recebimento fica marcado desfeito e o recibo estornado, e o desfazer entra no
 *      histórico;
 *   sabotagem: Receber gravando sem recalcular (o que veio da tela) — a prova acusa; o arquivo volta igual.
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const L = require('../lib');
const X = require('./_escola');

const ETAPA = 'onda8/etapa27';
const MODELO = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'financeiro', 'AdmsFinRecibos.php');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');

/** Escolhe a data na tela de Receber e espera a conta da tela: devolve a multa, os juros e o valor atualizado da linha. */
async function conta(p, data) {
  const resposta = p.waitForResponse((r) => r.url().includes('/financeiro/encargos/') && r.url().includes('data=' + data), { timeout: 10000 });
  await p.fill('#dataRecebimento', data);
  await p.dispatchEvent('#dataRecebimento', 'change');
  await resposta;
  await p.waitForFunction((d) => (document.getElementById('avisoData') || {}).textContent.includes(d.split('-').reverse().join('/')) && /A conta é/.test(document.getElementById('avisoData').textContent), data, { timeout: 10000 });
  return p.$eval('#tabelaReceber tbody tr', (tr) => ({ multa: tr.querySelector('.vc-multa').textContent.trim(), juros: tr.querySelector('.vc-juros').textContent.trim(),
    total: tr.querySelector('.vc-atualizado').textContent.trim() }));
}
/** Um POST da sessão da página (para o envio repetido e a concorrência); $campos: pares [nome, valor]. */
const postar = (p, url, campos) => p.evaluate(async ([u, c]) => {
  const x = await fetch(u, { method: 'POST', credentials: 'same-origin', body: new URLSearchParams(c) });
  return { st: x.status, url: x.url, t: await x.text() };
}, [url, campos]);
const recebimento = (aluno, titulo, data, forma, envio) => [['titulos[]', String(titulo)], ['data', data], ['modo', 'unica'], ['formas[0][forma]', forma], ['formas[0][valor]', ''],
  ['formas[0][documento]', '00026500130'], ['envio', envio]];

(async () => {
  const { navegador } = await L.abrir();
  const shaModelo = sha(MODELO);
  try {
    L.ok(X.montar('onda8-etapa27').status === 0, 'a demonstração do colégio montada pela tela, numa instalação nova');
    const c = X.cred();
    const sec = await X.entrar(navegador, 'secretaria.demo', c.painel['secretaria.demo']);
    await sec.p.goto(L.BASE + 'painel/fin-regras/index');
    await sec.p.fill('#multa', '2'); await sec.p.fill('#juros', '1'); await sec.p.fill('#desconto', '0');
    await Promise.all([sec.p.waitForNavigation(), sec.p.click('#salvarRegras')]);
    const a = await X.arranjo(sec.p, '265001');
    const [t1, t2, t3] = a.titulos;
    L.ok(a.titulos.length === 3 && t1.vencimento === '2026-02-20', `o arranjo: as regras (2%, 1% ao mês) e as 3 parcelas de R$ 500,00 do aluno 265001, a primeira vencendo em ${t1.vencimento}`);

    /* 2. a conta na hora e o recebimento com atraso */
    await X.abrirAcao(sec.p, t1.id, 'receber');
    const c18 = await conta(sec.p, '2026-02-18');
    const c21 = await conta(sec.p, '2026-02-21');
    await L.print(sec.p, ETAPA, 'receber-conta-na-hora');
    await X.enviar(sec.p, '#formReceber', '#receberFinalizar');
    const g1 = X.sql(`SELECT CONCAT(data_operacao, '|', original_centavos, '|', multa_centavos, '|', juros_centavos, '|', recebido_centavos) FROM fin_operacoes WHERE titulo_id = ${t1.id} AND tipo = 'receber'`);
    L.ok(c18.multa === 'R$ 0,00' && c18.juros === 'R$ 0,00' && c18.total === 'R$ 500,00' && c21.multa === 'R$ 10,00' && c21.juros === 'R$ 0,17' && c21.total === 'R$ 510,17'
      && g1 === '2026-02-21|50000|1000|17|51017' && X.situacao(t1.id) === 'recebido',
      `Receber com a conta na hora: no dia 18, ${JSON.stringify(c18)}; no dia 21, ${JSON.stringify(c21)}; gravado pelo servidor: ${g1}`);

    /* 3. a outra, em dia */
    await X.abrirAcao(sec.p, t2.id, 'receber');
    await conta(sec.p, '2026-03-18');
    await X.enviar(sec.p, '#formReceber', '#receberFinalizar');
    const g2 = X.sql(`SELECT CONCAT(multa_centavos, '|', juros_centavos, '|', recebido_centavos) FROM fin_operacoes WHERE titulo_id = ${t2.id} AND tipo = 'receber'`);
    L.ok(g2 === '0|0|50000', `a parcela que vence no dia 20, recebida no dia 18: sem multa e sem juros (${g2})`);

    /* 4. o clique duplo e duas pessoas */
    const envio = 'prova-' + Date.now();
    const r1 = await postar(sec.p, L.BASE + `painel/financeiro/receber/${a.aluno}`, recebimento(a.aluno, t3.id, X.hoje(), 'dinheiro', envio));
    const r2 = await postar(sec.p, L.BASE + `painel/financeiro/receber/${a.aluno}`, recebimento(a.aluno, t3.id, X.hoje(), 'dinheiro', envio));
    const ops = X.um(`SELECT COUNT(*) FROM fin_operacoes WHERE titulo_id = ${t3.id} AND tipo = 'receber'`);
    const recibos = X.um(`SELECT COUNT(*) FROM fin_recibo_titulos WHERE titulo_id = ${t3.id}`);
    const sup = await X.entrar(navegador, 'supervisao.demo', c.painel['supervisao.demo']);
    await sup.p.goto(L.BASE + `painel/financeiro/ficha/${a.aluno}`);
    const r3 = await postar(sup.p, L.BASE + `painel/financeiro/receber/${a.aluno}`, recebimento(a.aluno, t3.id, X.hoje(), 'pix', 'outra-' + Date.now()));
    /* o fetch segue o redirecionamento até a ficha, que mostra (e consome) o aviso: ele está na resposta */
    const recusa = ((r3.t.match(/id=['"]financeiroRecusado['"][^>]*>([\s\S]*?)<\/div>/) || [])[1] || '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ');
    const ops2 = X.um(`SELECT COUNT(*) FROM fin_operacoes WHERE titulo_id = ${t3.id} AND tipo = 'receber'`);
    L.ok(r1.st === 200 && r2.st === 200 && ops === 1 && recibos === 1 && /já está recebido/.test(recusa) && ops2 === 1,
      `o clique duplo (o mesmo envio duas vezes) grava um recibo só (${recibos}, ${ops} operação); a segunda pessoa no mesmo título: "${recusa.trim()}" (${ops2} operação)`);

    /* 5. o desfazer: a secretaria não; a supervisão sim, com o motivo da lista e a justificativa */
    await sec.p.goto(L.BASE + `painel/financeiro/ficha/${a.aluno}`);
    await sec.p.click(`#tabelaTitulos tr[data-titulo="${t1.id}"][data-situacao] .vc-acoes`);
    const botaoSec = !!(await sec.p.$(`#tabelaTitulos tr[data-titulo="${t1.id}"] .vc-item-desfazer-recebimento`));
    const recibo1 = X.um(`SELECT recibo_id FROM fin_recibo_titulos WHERE titulo_id = ${t1.id}`);
    const tentaSec = await postar(sec.p, L.BASE + `painel/financeiro/desfazer-recebimento/${recibo1}`, [['data', X.hoje()], ['motivo_id', '2'], ['justificativa', 'tentativa da secretaria'], ['envio', 's-' + Date.now()]]);
    const continua = X.situacao(t1.id);
    await X.desfazerRecebimento(sup.p, t1.id, { justificativa: 'Lançado no aluno errado' });
    await sup.p.goto(L.BASE + `painel/financeiro/ficha/${a.aluno}`);
    const depois = await sup.p.$eval(`#tabelaTitulos tr[data-titulo="${t1.id}"][data-situacao]`, (t) => t.dataset.situacao);
    const desfeita = X.um(`SELECT COUNT(*) FROM fin_operacoes WHERE titulo_id = ${t1.id} AND tipo = 'receber' AND desfeita_em IS NOT NULL`);
    const estornado = X.sql(`SELECT situacao FROM fin_recibos WHERE id = ${recibo1}`);
    const hist = X.sql(`SELECT motivo FROM fin_operacoes WHERE titulo_id = ${t1.id} AND tipo = 'desfazer_recebimento'`);
    const recibo2 = X.um(`SELECT recibo_id FROM fin_recibo_titulos WHERE titulo_id = ${t2.id}`);
    await postar(sup.p, L.BASE + `painel/financeiro/desfazer-recebimento/${recibo2}`, [['data', X.hoje()], ['motivo_id', ''], ['justificativa', 'sem o motivo'], ['envio', 'm-' + Date.now()]]);
    await L.print(sup.p, ETAPA, 'desfazer-recebimento');
    await sup.ctx.close();
    L.ok(!botaoSec && /não tem acesso/.test(tentaSec.t) && continua === 'recebido' && depois === 'vencido' && desfeita === 1 && estornado === 'estornado'
      && hist === 'Valor, data ou forma registrados errado: Lançado no aluno errado' && X.situacao(t2.id) === 'recebido',
      `Desfazer recebimento: a secretaria não tem o item e o pedido dela recebe a página sem acesso (o título continua ${continua}); a supervisão desfaz com o motivo e a justificativa: o título volta e aparece ${depois}, o recebimento fica desfeito e o recibo ${estornado}, o motivo no histórico ("${hist}"); sem o motivo, nada muda`);
    await sec.ctx.close();

    /* sabotagem: gravar sem recalcular */
    const orig = fs.readFileSync(MODELO, 'utf8');
    const trecho = "$e = F::encargosTitulo($t, $data);"; // Renegociar (D-105): o Receber usa a conta do título (a parcela do acordo sem multa)
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let g = '';
    try {
      fs.writeFileSync(MODELO, orig.replace(trecho, "$e = ['original' => (int) $t['valor_centavos'], 'multa' => 0, 'juros' => 0, 'desconto' => 0, 'total' => (int) $t['valor_centavos']];"));
      const s = await X.entrar(navegador, 'secretaria.demo', c.painel['secretaria.demo']);
      await X.receber(s.p, [t1.id], { data: '2026-02-21' });
      await s.ctx.close();
      g = X.sql(`SELECT CONCAT(multa_centavos, '|', juros_centavos) FROM fin_operacoes WHERE titulo_id = ${t1.id} AND tipo = 'receber' AND desfeita_em IS NULL`);
    } finally {
      fs.writeFileSync(MODELO, orig);
    }
    L.ok(g !== '1000|17', `sabotagem (Receber sem recalcular os encargos no servidor): a prova reprova — gravou ${g}`);
    L.ok(sha(MODELO) === shaModelo, 'o arquivo sabotado voltou igual (sha256)');
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(MODELO) !== shaModelo) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
