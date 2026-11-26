/*
 * PROVA DA ONDA 8, CORREÇÃO, SEGUNDA LEVA, 2.7 — o limite da API em Parâmetros (D-93). Usa a instalação que o ciclo
 * deixa (a demonstração do ensino superior); muda só o parâmetro e o devolve no fim.
 *   1. o administrador abre Parâmetros: o campo "Chamadas por minuto, por chave" com o valor guardado (120) e a
 *      explicação de quando aumentar (o site de inscrições, a importação grande, as respostas 429);
 *   2. grava 15 pela tela: o parâmetro fica 15, e a API passa a responder 429 na 16ª chamada do minuto de uma chave;
 *   3. um valor fora da faixa (5) é recusado ("Nada foi salvo") e o parâmetro continua 15;
 *   4. o registro das mudanças diz "O limite da API em Parâmetros";
 *   sabotagem: o modelo sem gravar o parâmetro — a prova acusa; o arquivo volta igual.
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const L = require('../lib');
const X = require('./_escola');

const ETAPA = 'onda8/etapa21';
const MODELO = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'parametros', 'AdmsParametrosInstituicao.php');
const API = L.BASE + 'api/v1';
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const valor = () => X.sql("SELECT valor FROM adms_parametros WHERE chave = 'api.limite_por_minuto'");

function criarChave() {
  const chave = 'vce_' + crypto.randomBytes(20).toString('hex');
  X.sql(`INSERT INTO adms_api_chaves (nome, prefixo, hash, escopo, permissoes, situacao, usuario_id) VALUES ('Prova limite', '${chave.slice(0, 12)}', '${crypto.createHash('sha256').update(chave).digest('hex')}', 'leitura', 'cadastros:ler', 1, 1)`);
  return chave;
}
/** n chamadas de leitura com a chave: os códigos de resposta, na ordem. */
async function chamar(chave, n) {
  const out = [];
  for (let i = 0; i < n; i++) {
    const r = await fetch(API + '/cursos', { headers: { Authorization: 'Bearer ' + chave } });
    out.push(r.status);
  }
  return out;
}
async function gravar(p, v) {
  await p.goto(L.BASE + 'painel/parametros/listar');
  /* o campo tem a faixa (min e max) e o navegador barra o valor fora dela: tirá-la é o envio direto, que o servidor
     tem de recusar sozinho */
  await p.$eval('#api_limite_por_minuto', (i) => { i.removeAttribute('min'); i.removeAttribute('max'); });
  await p.fill('#api_limite_por_minuto', String(v));
  await Promise.all([p.waitForNavigation(), p.click('input[name="SalvarParametros"]')]);
  return p.evaluate(() => (document.querySelector('.alert') || {}).textContent || '');
}

(async () => {
  const { navegador } = await L.abrir();
  const shaModelo = sha(MODELO);
  const original = valor();
  try {
    const ctx = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' });
    const p = await L.pagina(ctx);
    await p.goto(L.BASE + 'painel/login/acesso');
    await p.fill('input[name="usuario"]', 'administrador');
    await p.fill('input[name="senha"]', process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123');
    await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);

    /* 1. a tela */
    await p.goto(L.BASE + 'painel/parametros/listar');
    const campo = await p.$eval('#api_limite_por_minuto', (i) => (i.min === '10' && i.max === '10000' ? i.value : `faixa ${i.min}–${i.max}`)).catch(() => null);
    const quando = await p.$eval('#limiteApiQuando', (e) => e.textContent).catch(() => '');
    await p.$eval('#limiteApi', (e) => e.scrollIntoView()).catch(() => null);
    await L.print(p, ETAPA, 'parametros-limite-da-api');
    L.ok(campo === original && /Quando aumentar/.test(quando) && /inscri/.test(quando) && /429/.test(quando),
      `Parâmetros mostra o limite da API (${campo}; guardado ${original}) e quando aumentar ("${quando.replace(/\s+/g, ' ').slice(0, 90)}…")`);

    /* 2. 15 pela tela; a API responde 429 na 16ª chamada */
    const aviso = await gravar(p, 15);
    const k = criarChave();
    const codigos = await chamar(k, 16);
    L.ok(valor() === '15' && /salvas/i.test(aviso) && codigos.slice(0, 15).every((c) => c === 200) && codigos[15] === 429,
      `gravado 15 pela tela (parâmetro ${valor()}): as 15 primeiras chamadas ${[...new Set(codigos.slice(0, 15))].join('/')}, a 16ª ${codigos[15]}`);

    /* 3. fora da faixa */
    const recusa = await gravar(p, 5);
    L.ok(valor() === '15' && /Nada foi salvo/.test(recusa) && /10 a 10000/.test(recusa), `5 é recusado ("${recusa.replace(/\s+/g, ' ').trim().slice(0, 100)}…"); o parâmetro continua ${valor()}`);

    /* 4. o registro das mudanças */
    require('./_escola').sql("DELETE FROM adms_instalacao_estado WHERE chave = 'novidades.desde'"); // D-124 (1.102.1): as Novidades com o histórico, como numa instalação que já existia (a nova começa do zero)
    await p.goto(L.BASE + 'painel/novidades/listar');
    L.ok(/O limite da API em Parâmetros/.test(await p.evaluate(() => document.body.innerText)), 'o registro das mudanças diz "O limite da API em Parâmetros"');

    /* sabotagem: sem gravar o parâmetro */
    const orig = fs.readFileSync(MODELO, 'utf8');
    const trecho = "$grava('api.limite_por_minuto', $apiLimite);";
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let s = null;
    try {
      fs.writeFileSync(MODELO, orig.replace(trecho, '/* sabotagem */'));
      await gravar(p, 40);
      s = valor();
    } finally {
      fs.writeFileSync(MODELO, orig);
    }
    L.ok(s !== null && s !== '40', `sabotagem (o modelo sem gravar o parâmetro): a prova reprova — o parâmetro ficou ${s}, não 40`);
    L.ok(sha(MODELO) === shaModelo, 'o arquivo sabotado voltou igual (sha256)');
    await ctx.close();
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(MODELO) !== shaModelo) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    X.sql(`UPDATE adms_parametros SET valor = '${original || 120}' WHERE chave = 'api.limite_por_minuto'`);
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
