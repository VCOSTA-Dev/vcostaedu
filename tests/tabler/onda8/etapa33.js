/*
 * PROVA DA ONDA 8, FINANCEIRO, PARTE 8 — o financeiro na demonstração do colégio (D-98). APAGA o banco de prova.
 *   1. a demonstração do colégio, montada pela tela numa instalação nova: as regras do padrão (2%, 1% ao mês, sem
 *      desconto), um plano por segmento e as parcelas de cada aluno de 2026, tudo gravado pela secretaria;
 *   2. a maioria das parcelas vencidas recebida, algumas com atraso (com os encargos), algumas vencidas em aberto; uma
 *      baixa, um cancelamento e uma renegociação vigente;
 *   3. poucos atendimentos (dois ou três, o pedido do responsável), cada um num aluno diferente;
 *   4. a ficha financeira de uma aluna, aberta da ficha dela pela secretaria, mostra os títulos e os vencidos;
 *   5. o registro das mudanças diz "O financeiro na demonstração";
 *   sabotagem: a montagem sem o limite dos atendimentos — a prova acusa (mais de três); o arquivo volta igual.
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const L = require('../lib');
const X = require('./_escola');

const ETAPA = 'onda8/etapa33';
const DEMO = path.join(L.RAIZ, 'database', 'ferramentas', 'demonstracao_escola.php');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');

/** O financeiro que a montagem deixou. */
function retrato() {
  const n = (q) => X.um(q);
  return {
    planos: n('SELECT COUNT(*) FROM fin_planos'),
    alunos: n("SELECT COUNT(DISTINCT t.aluno_id) FROM fin_titulos t JOIN col_usuarios u ON u.id = t.aluno_id WHERE u.rm LIKE '2650%'"),
    titulos: n('SELECT COUNT(*) FROM fin_titulos'),
    vencidas: n("SELECT COUNT(*) FROM fin_titulos WHERE vencimento < CURDATE() AND renegociacao_id IS NULL"),
    recebidas: n("SELECT COUNT(*) FROM fin_titulos WHERE situacao = 'recebido'"),
    comAtraso: n("SELECT COUNT(*) FROM fin_operacoes WHERE tipo = 'receber' AND desfeita_em IS NULL AND (multa_centavos > 0 OR juros_centavos > 0)"),
    vencidasAbertas: n("SELECT COUNT(*) FROM fin_titulos WHERE situacao = 'aberto' AND vencimento < CURDATE()"),
    baixadas: n("SELECT COUNT(*) FROM fin_titulos WHERE situacao = 'baixado'"),
    canceladas: n("SELECT COUNT(*) FROM fin_titulos WHERE situacao = 'cancelado'"),
    renegociacoes: n("SELECT COUNT(*) FROM fin_renegociacoes WHERE situacao = 'vigente'"),
    atendimentos: n("SELECT COUNT(*) FROM fin_operacoes WHERE tipo = 'atendimento'"),
    alunosAtendidos: n("SELECT COUNT(DISTINCT aluno_id) FROM fin_operacoes WHERE tipo = 'atendimento'"),
    regras: X.sql("SELECT CONCAT(multa_bp, '|', juros_mes_bp, '|', desconto_bp) FROM fin_regras WHERE id = 1"),
    porOutro: n("SELECT COUNT(*) FROM fin_operacoes o WHERE o.tipo <> 'desfazer_recebimento' AND o.usuario_id NOT IN (SELECT id FROM adms_usuarios WHERE usuario = 'secretaria.demo')"), // D-99: o desfazer da aluna da ficha é da supervisão
  };
}

(async () => {
  const { navegador } = await L.abrir();
  const shaDemo = sha(DEMO);
  try {
    L.ok(X.montar('onda8-etapa33').status === 0, 'a demonstração do colégio montada pela tela, numa instalação nova');
    const r = retrato();
    L.ok(r.regras === '200|100|0' && r.planos === 2 && r.alunos >= 10 && r.titulos >= 12 * r.alunos && r.porOutro === 0,
      `as regras do padrão (${r.regras}), ${r.planos} planos e as parcelas de ${r.alunos} alunos de 2026 (${r.titulos} títulos), tudo pela secretaria`);
    L.ok(r.recebidas > r.vencidasAbertas && r.comAtraso >= 2 && r.vencidasAbertas >= 2 && r.baixadas === 2 && r.canceladas >= 2 && r.renegociacoes === 2, /* D-99: a aluna da ficha soma uma baixa, um cancelamento e uma renegociação */
      `as parcelas vencidas: ${r.recebidas} recebidas (${r.comAtraso} com atraso e encargos), ${r.vencidasAbertas} em aberto; ${r.baixadas} baixas, ${r.canceladas} cancelada(s), ${r.renegociacoes} renegociações vigentes`);
    L.ok(r.atendimentos >= 2 && r.atendimentos <= 3 && r.alunosAtendidos === r.atendimentos, `poucos atendimentos: ${r.atendimentos}, cada um num aluno`);

    /* 4. a ficha, aberta da ficha da aluna */
    const c = X.cred();
    const sec = await X.entrar(navegador, 'secretaria.demo', c.painel['secretaria.demo']);
    const aluna = X.um("SELECT id FROM col_usuarios WHERE rm = '265002'");
    await sec.p.goto(L.BASE + `painel/academicos/visualizar/${aluna}`);
    await Promise.all([sec.p.waitForNavigation(), sec.p.click('#abrirFichaFinanceira')]);
    const linhas = await sec.p.$$eval('#tabelaTitulos tbody tr[data-titulo][data-situacao]', (t) => t.length).catch(() => 0);
    const vencidos = await sec.p.$$eval('#tabelaTitulos tbody tr[data-situacao="vencido"]', (t) => t.length).catch(() => 0);
    await L.print(sec.p, ETAPA, 'ficha-financeira-da-demonstracao');
    L.ok(/financeiro\/ficha\//.test(sec.p.url()) && linhas >= 12 && vencidos >= 1, `a ficha financeira da aluna 265002, aberta da ficha dela: ${linhas} títulos, ${vencidos} vencido(s)`);

    /* 5. o registro das mudanças */
    require('./_escola').sql("DELETE FROM adms_instalacao_estado WHERE chave = 'novidades.desde'"); // D-124 (1.102.1): as Novidades com o histórico, como numa instalação que já existia (a nova começa do zero)
    await sec.p.goto(L.BASE + 'painel/novidades/listar');
    L.ok(/O financeiro na demonstração/.test(await sec.p.evaluate(() => document.body.innerText)), 'o registro das mudanças diz "O financeiro na demonstração"');
    await sec.ctx.close();

    /* sabotagem: sem o limite dos atendimentos */
    const orig = fs.readFileSync(DEMO, 'utf8');
    const trecho = `if ($deve && (int) $c->um("SELECT COUNT(*) FROM fin_operacoes WHERE tipo = 'atendimento'") < 2`;
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let s = null;
    try {
      fs.writeFileSync(DEMO, orig.replace(trecho, 'if ($deve && true'));
      X.montar('onda8-etapa33-sabotagem');
      s = retrato().atendimentos;
    } finally {
      fs.writeFileSync(DEMO, orig);
    }
    L.ok(s !== null && s > 3, `sabotagem (a montagem sem o limite dos atendimentos): a prova reprova — ${s} atendimentos`);
    L.ok(sha(DEMO) === shaDemo, 'o arquivo sabotado voltou igual (sha256)');
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(DEMO) !== shaDemo) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
