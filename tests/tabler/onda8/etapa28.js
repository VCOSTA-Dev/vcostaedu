/*
 * PROVA DA ONDA 8, FINANCEIRO, PARTE 3 — a ficha financeira no portal, só de leitura (D-98). APAGA o banco de prova.
 *   1. a demonstração do colégio; um plano de três parcelas (fevereiro a abril de 2026, todas vencidas hoje) aplicado ao
 *      filho de uma família com o responsável financeiro; a secretaria recebe uma: ficam 2 vencidas;
 *   2. o portal do aluno: o cartão "Ficha financeira" na página inicial ("2 títulos vencidos", "Consultar"); a ficha com
 *      os filtros e as contagens, os títulos e nenhum botão de ação (nem formulário);
 *   3. o portal do responsável financeiro: o cartão no Resumo do filho e a ficha; o filtro "Pagos" mostra o recebido;
 *   4. o responsável que NÃO é o financeiro daquele filho não tem o cartão, e a ficha dele responde 403 (a página sem
 *      acesso); o filho de outra família, 403;
 *   sabotagem: a ficha da família sem a conferência do financeiro — a prova acusa; o arquivo volta igual.
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const L = require('../lib');
const X = require('./_escola');

const ETAPA = 'onda8/etapa28';
const CONTROLE = path.join(L.RAIZ, 'portal', 'app', 'adms', 'Controllers', 'Familia.php');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');

async function portal(navegador, usuario, senha, largura = 390) {
  const ctx = await navegador.newContext({ viewport: { width: largura, height: 844 }, locale: 'pt-BR' });
  const p = await L.pagina(ctx);
  await p.goto(L.BASE + 'portal/login/acesso');
  await p.fill('input[name="usuario"]', usuario);
  await p.fill('input[name="senha"]', senha);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
  return { ctx, p };
}
/** O que a ficha do portal mostra: as contagens, os títulos e se há ação. */
const ler = (p) => p.evaluate(() => ({
  ficha: !!document.querySelector('#fichaFinanceira'),
  contagem: Object.fromEntries([...document.querySelectorAll('#filtrosFicha [data-filtro]')].map((a) => [a.dataset.filtro, +(a.querySelector('.vc-contagem') || {}).textContent])),
  titulos: [...document.querySelectorAll('.vc-titulo-portal')].map((t) => t.dataset.situacao),
  acoes: document.querySelectorAll('#fichaFinanceira form, #fichaFinanceira button, .vc-abrir-acao').length,
}));
const status = (p, url) => p.evaluate(async (u) => (await fetch(u, { credentials: 'same-origin' })).status, url);

(async () => {
  const { navegador } = await L.abrir();
  const shaControle = sha(CONTROLE);
  try {
    L.ok(X.montar('onda8-etapa28').status === 0, 'a demonstração do colégio montada pela tela, numa instalação nova');
    const c = X.cred();
    /* a família com o financeiro de 2026 (e a senha na demonstração), o filho dela e o filho de outra família */
    const linhas = X.sql(`SELECT r.email, d.aluno_id, u.rm FROM esc_responsavel_designacoes d JOIN esc_responsaveis r ON r.id = d.responsavel_id JOIN col_usuarios u ON u.id = d.aluno_id
      WHERE d.ano = 2026 AND d.papel = 'financeiro' AND d.fim IS NULL ORDER BY d.aluno_id`).split('\n').map((l) => l.split('\t'));
    const [email, filho, ra] = linhas.find((l) => (c.familia || {})[l[0]]) || [];
    const outro = linhas.find((l) => l[0] !== email) || [];
    const naoFin = X.sql(`SELECT r.email FROM esc_responsavel_designacoes d JOIN esc_responsaveis r ON r.id = d.responsavel_id
      WHERE d.ano = 2026 AND d.aluno_id = ${+filho} AND d.papel <> 'financeiro' AND d.fim IS NULL AND r.email <> '${email}' LIMIT 1`).split('\n')[0];
    L.ok(!!email && !!outro[1] && !!(c.portal || {})[ra], `a família ${email.replace(/@.*/, '@…')} é a financeira do aluno ${ra} em 2026; o aluno ${outro[2]} é de outra família`);

    const sec = await X.entrar(navegador, 'secretaria.demo', c.painel['secretaria.demo']);
    /* desde a Parte 8, o aluno da demonstração tem o financeiro da demonstração: a prova começa do zero no financeiro
       dele (o banco de prova), para as contagens serem só as do arranjo */
    const idAluno = X.um(`SELECT id FROM col_usuarios WHERE rm = '${ra}'`);
    X.sql(`DELETE FROM fin_operacoes WHERE aluno_id = ${idAluno}; DELETE FROM fin_titulos WHERE aluno_id = ${idAluno}; DELETE FROM fin_renegociacoes WHERE aluno_id = ${idAluno}`);
    const a = await X.arranjo(sec.p, ra);
    await sec.p.goto(L.BASE + `painel/financeiro/ficha/${a.aluno}`);
    await X.receber(sec.p, [a.titulos[0].id]); // D-99: a tela de Receber pagamento
    await sec.ctx.close();
    L.ok(X.situacao(a.titulos[0].id) === 'recebido', 'o arranjo: 3 parcelas de 2026 (vencidas hoje), a primeira recebida pela secretaria');

    /* 2. o portal do aluno */
    const alu = await portal(navegador, ra, c.portal[ra]);
    await alu.p.goto(L.BASE + 'portal/home/index');
    const cartaoA = (await alu.p.textContent('.vc-cartao-financeiro').catch(() => '')).replace(/\s+/g, ' ');
    await Promise.all([alu.p.waitForNavigation(), alu.p.click('.vc-cartao-financeiro .vc-consultar-financeiro')]);
    const fa = await ler(alu.p);
    await L.print(alu.p, ETAPA, 'portal-aluno-ficha');
    await alu.ctx.close();
    L.ok(/2 títulos vencidos/.test(cartaoA) && /Consultar/.test(cartaoA) && fa.ficha && fa.contagem.vencidos === 2 && fa.contagem.pagos === 1 && fa.acoes === 0,
      `o portal do aluno: o cartão "${cartaoA.trim()}" na página inicial; a ficha com as contagens (${JSON.stringify(fa.contagem)}) e nenhuma ação`);

    /* 3. o responsável financeiro */
    const fam = await portal(navegador, email, c.familia[email]);
    await fam.p.goto(L.BASE + `portal/familia/filho/${filho}?ano=2026`);
    const cartaoF = (await fam.p.textContent('.vc-cartao-financeiro').catch(() => '')).replace(/\s+/g, ' ');
    await Promise.all([fam.p.waitForNavigation(), fam.p.click('.vc-cartao-financeiro .vc-consultar-financeiro')]);
    await Promise.all([fam.p.waitForNavigation(), fam.p.click('#filtrosFicha [data-filtro="pagos"]')]);
    const ff = await ler(fam.p);
    await L.print(fam.p, ETAPA, 'portal-responsavel-pagos');
    const outroSt = await status(fam.p, L.BASE + `portal/familia/financeiro/${outro[1]}?ano=2026`);
    L.ok(/2 títulos vencidos/.test(cartaoF) && ff.titulos.length === 1 && ff.titulos[0] === 'recebido' && ff.acoes === 0 && outroSt === 403,
      `o responsável financeiro: o cartão no Resumo ("${cartaoF.trim()}"), a ficha só de leitura, "Pagos" com o recebido; a ficha do filho de outra família responde ${outroSt}`);
    await fam.ctx.close();

    /* 4. quem não é o financeiro */
    if (naoFin && (c.familia || {})[naoFin]) {
      const nf = await portal(navegador, naoFin, c.familia[naoFin]);
      await nf.p.goto(L.BASE + `portal/familia/filho/${filho}?ano=2026`);
      const temCartao = !!(await nf.p.$('.vc-cartao-financeiro'));
      const st = await status(nf.p, L.BASE + `portal/familia/financeiro/${filho}?ano=2026`);
      await nf.ctx.close();
      L.ok(!temCartao && st === 403, `o responsável que não é o financeiro: sem o cartão no Resumo; a ficha responde ${st}`);
    } else {
      L.ok(false, 'a demonstração não tem um segundo responsável (não financeiro) com senha para este aluno');
    }

    /* sabotagem: sem a conferência do financeiro */
    const orig = fs.readFileSync(CONTROLE, 'utf8');
    const trecho = '!$this->acesso()->podeVerFinanceiro(';
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let st = 0;
    try {
      fs.writeFileSync(CONTROLE, orig.replace(trecho, 'false && !$this->acesso()->podeVerFinanceiro('));
      /* o responsável do mesmo filho que NÃO é o financeiro: só a conferência do financeiro o barra (o filho de outra
         família já é barrado antes, pelo exigir) */
      const s = await portal(navegador, naoFin, c.familia[naoFin]);
      st = await status(s.p, L.BASE + `portal/familia/financeiro/${filho}?ano=2026`);
      await s.ctx.close();
    } finally {
      fs.writeFileSync(CONTROLE, orig);
    }
    L.ok(st !== 403, `sabotagem (a ficha da família sem conferir o financeiro): a prova reprova — a ficha do filho abriu para o responsável que não é o financeiro (${st})`);
    L.ok(sha(CONTROLE) === shaControle, 'o arquivo sabotado voltou igual (sha256)');
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(CONTROLE) !== shaControle) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
