// Prova do MENU ATIVO (1.18.1, D-485), na demonstração: com cada perfil (administrador, secretaria e professor no painel;
// o aluno no portal), abre CADA item do menu lateral e confere que o item ativo é exatamente ele — um só, e nenhum outro.
// Também abre páginas de fora do menu (ex.: um guia) e confere que nenhum item de outra rota fica marcado por engano.
//   node provar_menu.js
const { chromium } = require('playwright-core');
const L = require('./lib');

const res = [];
const ok = (c, m) => { res.push(!!c); console.log(`${c ? 'OK   ' : 'FALHA'} ${m}`); };
const rota = h => (h || '').replace(/^https?:\/\/[^/]+/, '').replace(/\/+$/, '').toLowerCase();

(async () => {
  try { L.banco('guarda'); } catch (e) { process.stderr.write(String(e.stderr || e.message)); process.exit(9); }
  const contas = ['admin.demo', 'secretaria.demo', 'prof.laura.demo', '260104'];
  await L.comTrava(() => L.comRetrato(contas, async senhas => {
    const b = await chromium.launch({ channel: 'chromium', args: ['--lang=pt-BR'] });
    for (const conta of contas) {
      const app = /^\d+$/.test(conta) ? 'portal' : 'painel';
      const ctx = await b.newContext(); const p = await ctx.newPage();
      p.on('dialog', d => d.dismiss());
      await p.goto(L.C.base + app + '/login/acesso');
      await p.fill('input[name="usuario"]', conta); await p.fill('input[name="senha"]', senhas[conta]);
      await Promise.all([p.waitForNavigation({ waitUntil: 'load' }), p.click('input[name="SendLogin"]')]);
      await p.waitForSelector('.nav-sidebar a.nav-link', { timeout: 30000 });
      const itens = await p.$$eval('.nav-sidebar a.nav-link', as => as.map(a => ({ href: a.href, texto: a.innerText.trim() })).filter(x => x.href && !x.href.endsWith('#') && !/logout|sair/i.test(x.href + x.texto)));
      let certos = 0; const erros = [];
      for (const it of itens) {
        await p.goto(it.href, { waitUntil: 'load' }).catch(() => {});
        const ativos = await p.$$eval('.nav-sidebar a.nav-link.active', as => as.map(a => ({ href: a.href, texto: a.innerText.trim() })));
        if (ativos.length === 1 && rota(ativos[0].href) === rota(it.href)) certos++;
        else erros.push(`${it.texto} → ${ativos.map(a => a.texto).join(' + ') || 'nenhum'}`);
      }
      ok(itens.length > 0 && certos === itens.length, `${app} (${conta}): ${certos} de ${itens.length} itens do menu marcam a si mesmos e só a si${erros.length ? ' — ' + erros.join('; ') : ''}`);
      const nomes = itens.map(i => i.texto);
      if (app === 'portal') ok(['Boletim', 'Notas das Atividades'].every(n => nomes.some(x => x === n)), 'portal: Boletim e Notas das Atividades estão entre os itens conferidos');
      /* uma página fora do menu (o guia) não marca um item de outra rota */
      await p.goto(L.C.base + app + (app === 'portal' ? '/home/guia/usar-a-ajuda' : '/ajuda/guia/usar-a-ajuda'), { waitUntil: 'load' });
      const fora = await p.$$eval('.nav-sidebar a.nav-link.active', as => as.map(a => a.innerText.trim()));
      ok(fora.length <= 1 && (fora.length === 0 || /ajuda/i.test(fora[0])), `${app} (${conta}): no guia (fora do menu), o ativo é ${fora.join(' + ') || 'nenhum'} (o item da Ajuda)`);
      await ctx.close();
    }
    await b.close();
  }));
  console.log(`\n${res.filter(Boolean).length} OK, ${res.filter(x => !x).length} FALHA`);
  process.exitCode = res.every(Boolean) ? 0 : 1;
})();
