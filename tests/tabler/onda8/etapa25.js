/*
 * PROVA DA ONDA 8, CORREÇÃO, SEGUNDA LEVA, 2.11 — a pergunta no topo do relatório (D-97). Usa a instalação que o ciclo
 * deixa (a demonstração do ensino superior); não muda dados.
 *   1. cada relatório da lista (Relatórios), aberto: a pergunta a que ele responde está no topo — a mesma da lista,
 *      acima dos filtros e da tabela;
 *   2. o guia dos relatórios diz que a pergunta fica no topo da tela;
 *   3. o registro das mudanças diz "A pergunta no topo do relatório";
 *   sabotagem: a tela sem a pergunta — a prova acusa; o arquivo volta igual.
 * Ambiente: VCOSTAEDU_BASE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const L = require('../lib');

const ETAPA = 'onda8/etapa25';
const VIEW = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Views', 'relatorios', 'relatorio.php');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');

/** Os relatórios da lista: o endereço e a pergunta de cada um. */
async function lista(p) {
  await p.goto(L.BASE + 'painel/relatorios/listar');
  return p.$$eval('.page-body table tr', (trs) => trs.map((tr) => {
    const a = tr.querySelector('td a[href*="/relatorios/"]');
    const tds = tr.querySelectorAll('td');
    return a && tds[1] ? { url: a.href, nome: a.textContent.trim(), pergunta: tds[1].textContent.trim() } : null;
  }).filter(Boolean));
}
/** O relatório aberto: a pergunta do topo e se ela fica acima dos filtros. */
async function topo(p, url) {
  await p.goto(url);
  return p.evaluate(() => {
    const q = document.getElementById('perguntaRelatorio');
    const f = document.getElementById('filtrosRelatorio');
    if (!q) return { texto: null, acima: false };
    const yq = q.getBoundingClientRect().top;
    return { texto: q.textContent.trim(), acima: !f || yq < f.getBoundingClientRect().top, visivel: q.offsetParent !== null };
  });
}

(async () => {
  const { navegador } = await L.abrir();
  const shaView = sha(VIEW);
  try {
    const ctx = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' });
    const p = await L.pagina(ctx);
    await p.goto(L.BASE + 'painel/login/acesso');
    await p.fill('input[name="usuario"]', 'administrador');
    await p.fill('input[name="senha"]', process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123');
    await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);

    /* 1. cada relatório */
    const rels = await lista(p);
    const ruins = [];
    for (const r of rels) {
      const t = await topo(p, r.url);
      if (t.texto !== r.pergunta || !t.acima || !t.visivel) ruins.push(`${r.nome} (${t.texto === null ? 'sem a pergunta' : t.texto !== r.pergunta ? 'outra pergunta' : 'abaixo dos filtros'})`);
    }
    await topo(p, rels[0] ? rels[0].url : L.BASE + 'painel/relatorios/listar');
    await L.print(p, ETAPA, 'relatorio-com-a-pergunta');
    L.ok(rels.length >= 10 && ruins.length === 0, `os ${rels.length} relatórios da lista, abertos: a pergunta no topo, a mesma da lista, acima dos filtros${ruins.length ? ' — fora: ' + ruins.slice(0, 5).join('; ') : ''}`);

    /* 2. o guia */
    await p.goto(L.BASE + 'painel/ajuda/guia/relatorios-visao-geral');
    L.ok(/escrita no topo da tela/.test(await p.evaluate(() => document.body.innerText)), 'o guia dos relatórios diz que a pergunta fica no topo da tela');

    /* 3. o registro das mudanças */
    await p.goto(L.BASE + 'painel/novidades/listar');
    L.ok(/A pergunta no topo do relatório/.test(await p.evaluate(() => document.body.innerText)), 'o registro das mudanças diz "A pergunta no topo do relatório"');

    /* sabotagem: sem a pergunta */
    const orig = fs.readFileSync(VIEW, 'utf8');
    const trecho = '<p class="lead mb-3" id="perguntaRelatorio"><?= $h($def::PERGUNTA) ?></p>';
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let s = null;
    try {
      fs.writeFileSync(VIEW, orig.replace(trecho, ''));
      s = rels[0] ? await topo(p, rels[0].url) : null;
    } finally {
      fs.writeFileSync(VIEW, orig);
    }
    L.ok(s && s.texto === null, 'sabotagem (a tela sem a pergunta): a prova reprova — o relatório abre sem a pergunta no topo');
    L.ok(sha(VIEW) === shaView, 'o arquivo sabotado voltou igual (sha256)');
    await ctx.close();
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(VIEW) !== shaView) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
