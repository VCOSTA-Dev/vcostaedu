/*
 * PROVA DA ONDA 8, FINANCEIRO, PARTE 1 — a base: as regras, os planos e os títulos (D-98). APAGA o banco de prova.
 *   1. a demonstração do colégio, montada pela tela numa instalação nova;
 *   2. a secretaria grava as regras financeiras pela tela (a multa, os juros ao mês, o desconto até o vencimento), e o
 *      exemplo da tela mostra a conta; o valor fora do limite é recusado com o motivo;
 *   3. ela cadastra um plano de pagamento (o nome, as parcelas, o valor, o dia, o mês da primeira);
 *   4. ao vincular um aluno a um curso com o plano, as parcelas nascem: uma por mês, no dia do plano, a partir do mês
 *      da primeira; aplicar o mesmo plano de novo não duplica;
 *   5. o título avulso (a descrição, o valor, o vencimento) entra na ficha;
 *   6. a ficha mostra a situação pela data (a vencer, vencido) e os filtros com as contagens (deste mês, vencidos,
 *      pagos, a vencer); o dinheiro é gravado em centavos inteiros;
 *   7. a conta dos encargos: o título que vence no dia 20, pago no dia 18, não tem multa nem juros; no dia 21, tem;
 *   sabotagem: a geração das parcelas sem o mês seguinte (todas no mesmo mês) — a prova acusa; o arquivo volta igual.
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { spawnSync, execFileSync } = require('child_process');
const L = require('../lib');

const ETAPA = 'onda8/etapa26';
const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste$/.test(BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
const PHP = 'C:/xampp/php/php.exe';
const MODELO = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'financeiro', 'AdmsFinTitulos.php');

const sql = (q) => execFileSync('C:/xampp/mysql/bin/mysql.exe', ['-uroot', '--default-character-set=utf8mb4', '-N', '-B', BANCO, '-e', q], { encoding: 'utf8' }).split('\n').map((l) => l.trim()).filter(Boolean).join('\n');
const um = (q) => +(sql(q).split('\n')[0] || 0);
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const montar = (rotulo) => spawnSync(process.execPath, [path.join(__dirname, '..', 'onda6', 'medir_demo.js'), 'escola', rotulo], { encoding: 'utf8', maxBuffer: 1 << 24 });
async function entrar(navegador, usuario, senha) {
  const ctx = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' });
  const p = await L.pagina(ctx);
  await p.goto(L.BASE + 'painel/login/acesso');
  await p.fill('input[name="usuario"]', usuario);
  await p.fill('input[name="senha"]', senha);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
  return { ctx, p };
}
/** D-99: a janela "Novo título" da ficha (o título avulso e o plano ficam fora do corpo da ficha). */
async function novoTitulo(p) {
  await p.click('#botaoNovoTitulo');
  await p.waitForSelector('#modalNovoTitulo.show', { timeout: 5000 });
}
/** A conta dos encargos pelo próprio código (o mesmo que a tela e a gravação usam). */
function encargos(valor, venc, data) {
  const php = `define('VCOSTAEDU', true); require '${L.RAIZ.replace(/\\/g, '/')}/painel/app/adms/Models/financeiro/AdmsFinanceiro.php';
    echo json_encode(\\App\\adms\\Models\\financeiro\\AdmsFinanceiro::encargos(${valor}, '${venc}', '${data}', ['multa_bp' => 200, 'juros_mes_bp' => 100, 'desconto_bp' => 0]));`;
  return JSON.parse(execFileSync(PHP, ['-r', php], { encoding: 'utf8' }));
}

(async () => {
  const { navegador } = await L.abrir();
  const shaModelo = sha(MODELO);
  try {
    const r = montar('onda8-etapa26');
    L.ok(r.status === 0, 'a demonstração do colégio montada pela tela, numa instalação nova');
    const cred = JSON.parse(sql("SELECT valor FROM adms_instalacao_estado WHERE chave = 'demonstracao.credenciais'"));
    const sec = await entrar(navegador, 'secretaria.demo', (cred.painel || {})['secretaria.demo'] || '');
    const p = sec.p;

    /* 2. as regras */
    await p.goto(L.BASE + 'painel/fin-regras/index');
    await p.fill('#multa', '2');
    await p.fill('#juros', '1');
    await p.fill('#desconto', '5');
    await Promise.all([p.waitForNavigation(), p.click('#salvarRegras')]);
    const gravadas = sql('SELECT CONCAT(multa_bp, "|", juros_mes_bp, "|", desconto_bp) FROM fin_regras WHERE id = 1');
    const exemplo = (await p.textContent('#exemploEncargos')).replace(/\s+/g, ' ');
    await p.fill('#multa', '35');
    await Promise.all([p.waitForNavigation(), p.click('#salvarRegras')]);
    const recusa = (await p.textContent('#regrasRecusadas').catch(() => '')) || '';
    L.ok(gravadas === '200|100|500' && /950,00/.test(exemplo) && /1\.023,33/.test(exemplo) && /até 20%/.test(recusa) && sql('SELECT multa_bp FROM fin_regras WHERE id = 1') === '200',
      `as regras financeiras pela tela: 2%, 1% ao mês e 5% até o vencimento (gravadas em centésimos de ponto: ${gravadas}); o exemplo da tela ("${exemplo.slice(0, 140)}"); a multa de 35% recusada, com o motivo`);

    /* 3. o plano */
    await p.goto(L.BASE + 'painel/fin-planos/listar');
    await p.fill('#pNome', 'Anuidade de exemplo em 3 vezes');
    await p.fill('#pParcelas', '3');
    await p.fill('#pValor', '500,00');
    await p.fill('#pDia', '20');
    await p.selectOption('#pMes', '2');
    await Promise.all([p.waitForNavigation(), p.click('#salvarPlano')]);
    const plano = um("SELECT id FROM fin_planos WHERE nome = 'Anuidade de exemplo em 3 vezes' AND parcelas = 3 AND valor_centavos = 50000 AND dia_vencimento = 20 AND mes_primeira = 2");
    L.ok(plano > 0, 'o plano de pagamento cadastrado pela tela (3 parcelas de R$ 500,00, dia 20, a primeira em fevereiro; o valor em centavos: 50000)');

    /* 4. o vínculo com o plano: o aluno do 9º ano de 2025 entra no Ensino Médio em 2026 */
    const aluno = um("SELECT id FROM col_usuarios WHERE rm = '255001'");
    await p.goto(L.BASE + `painel/academicos/visualizar/${aluno}?tab=tabeMatricula`);
    const modulo = await p.$eval('#vModulo', (s) => [...s.options].find((o) => /M[ée]dio/.test(o.textContent) && /1/.test(o.textContent)).value).catch(() => '');
    await p.selectOption('#vAno', '2026');
    await p.selectOption('#vModulo', modulo);
    if (await p.$('#vData')) { await p.fill('#vData', '2026-02-01'); }
    await p.selectOption('#vPlano', String(plano));
    /* D-102 (A1): a tela marca a primeira parcela que ainda não venceu; as três deste plano (fevereiro a abril de 2026) já
       venceram, então a prova escolhe cobrar todas, desde a 1ª */
    if (await p.$('#vParcelaInicial')) { await p.selectOption('#vParcelaInicial', '1'); }
    await Promise.all([p.waitForNavigation(), p.click('#vincularAluno')]);
    const parcelas = sql(`SELECT GROUP_CONCAT(CONCAT(parcela, ':', vencimento, ':', valor_centavos) ORDER BY parcela) FROM fin_titulos WHERE aluno_id = ${aluno} AND plano_id = ${plano}`);
    const mc = um(`SELECT matricula_curso_id FROM fin_titulos WHERE aluno_id = ${aluno} AND plano_id = ${plano} LIMIT 1`);
    await p.goto(L.BASE + `painel/financeiro/ficha/${aluno}`);
    await novoTitulo(p); // D-99: o plano e o avulso na janela "Novo título"
    await p.selectOption('#apMat', String(mc));
    await p.selectOption('#apPlano', String(plano));
    await Promise.all([p.waitForNavigation(), p.click('#aplicarPlano')]);
    const depois = um(`SELECT COUNT(*) FROM fin_titulos WHERE aluno_id = ${aluno} AND plano_id = ${plano}`);
    L.ok(parcelas === '1:2026-02-20:50000,2:2026-03-20:50000,3:2026-04-20:50000' && depois === 3,
      `ao vincular o aluno com o plano, as parcelas nascem (${parcelas}); aplicar o plano de novo não duplica (${depois})`);

    /* 5. o avulso */
    const hoje = new Date();
    const ymd = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    const amanha = new Date(hoje.getTime() + 86400000);
    await novoTitulo(p);
    await p.fill('#avDesc', 'Material didático de exemplo');
    await p.fill('#avValor', '1.234,56');
    await p.fill('#avVenc', ymd(amanha));
    await Promise.all([p.waitForNavigation(), p.click('#lancarAvulso')]);
    const av = sql(`SELECT valor_centavos FROM fin_titulos WHERE aluno_id = ${aluno} AND plano_id IS NULL ORDER BY id DESC LIMIT 1`);
    L.ok(av === '123456', `o título avulso entra na ficha, em centavos inteiros (R$ 1.234,56 = ${av})`);

    /* 6. a situação pela data e os filtros */
    const sit = await p.$$eval('#tabelaTitulos tbody tr[data-titulo][data-situacao]', (l) => l.map((t) => t.dataset.situacao));
    /* D-99: os filtros são as nove situações, com a contagem; o vencido em aberto é "Atrasados" */
    const cont = await p.$$eval('#filtroSituacao .vc-chip', (l) => Object.fromEntries(l.map((a) => [a.dataset.situacao, +a.querySelector('.vc-contagem').textContent])));
    await L.print(p, ETAPA, 'ficha-financeira');
    await p.goto(L.BASE + `painel/financeiro/ficha/${aluno}?situacao=atrasados`);
    const soVencidos = await p.$$eval('#tabelaTitulos tbody tr[data-titulo][data-situacao]', (l) => l.map((t) => t.dataset.situacao));
    L.ok(sit.filter((x) => x === 'vencido').length === 3 && sit.filter((x) => x === 'a_vencer').length === 1 && cont.atrasados === 3 && cont.avencer === 1 && cont.todos === 4
      && soVencidos.length === 3 && soVencidos.every((x) => x === 'vencido'),
      `a ficha: as 3 parcelas de 2026 vencidas e o avulso a vencer, pela data de hoje; as situações com as contagens (${JSON.stringify(cont)}); o filtro "Atrasados" mostra só os vencidos`);
    await sec.ctx.close();

    /* 7. os encargos pela data */
    const e18 = encargos(50000, '2026-02-20', '2026-02-18');
    const e21 = encargos(50000, '2026-02-20', '2026-02-21');
    L.ok(e18.multa === 0 && e18.juros === 0 && e21.multa === 1000 && e21.juros === 17 && e21.total === 51017,
      `os encargos pela data: vence no dia 20 e pago no 18, sem multa e sem juros (${e18.total}); no 21, multa de 1000 e juros de 17 centavos (1 dia de 1% ao mês sobre 500,00): ${e21.total}`);

    /* sabotagem: as parcelas todas no mesmo mês */
    const orig = fs.readFileSync(MODELO, 'utf8');
    const trecho = "$mes = (int) $p['mes_primeira'] + $i - 1;";
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let s = '';
    try {
      fs.writeFileSync(MODELO, orig.replace(trecho, "$mes = (int) $p['mes_primeira'];"));
      sql(`DELETE FROM fin_titulos WHERE aluno_id = ${aluno} AND plano_id = ${plano}`); // o arranjo da sabotagem (o banco de prova)
      const s2 = await entrar(navegador, 'secretaria.demo', (cred.painel || {})['secretaria.demo'] || '');
      await s2.p.goto(L.BASE + `painel/financeiro/ficha/${aluno}`);
      await novoTitulo(s2.p);
      await s2.p.selectOption('#apMat', String(mc));
      await s2.p.selectOption('#apPlano', String(plano));
      await Promise.all([s2.p.waitForNavigation(), s2.p.click('#aplicarPlano')]);
      await s2.ctx.close();
      s = sql(`SELECT GROUP_CONCAT(vencimento ORDER BY parcela) FROM fin_titulos WHERE aluno_id = ${aluno} AND plano_id = ${plano}`);
    } finally {
      fs.writeFileSync(MODELO, orig);
    }
    L.ok(s !== '2026-02-20,2026-03-20,2026-04-20', `sabotagem (as parcelas sem o mês seguinte): a prova reprova — ${s}`);
    L.ok(sha(MODELO) === shaModelo, 'o arquivo sabotado voltou igual (sha256)');
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(MODELO) !== shaModelo) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
