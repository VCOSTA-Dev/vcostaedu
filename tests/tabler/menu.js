/*
 * Prova do menu lateral (Etapa 3):
 *   - para cada perfil, o CONJUNTO de destinos do rail é igual ao da referência versionada
 *     (tests/tabler/menu-referencia.json). Na primeira onda a comparação era com o menu antigo (AdminLTE, na cópia
 *     provas/_antes/); a cópia saiu no fim da onda, e a referência foi gravada do rail provado igual a ele. Etapa que
 *     muda o menu de propósito grava a referência de novo (`node tests/tabler/menu.js gravar`) e lista a diferença;
 *   - o grupo abre e fecha; o recolher (vc-side-mini) liga, sobrevive à recarga e desliga;
 *   - em 390 px o menu abre pelo botão.
 * Ambiente: VCOSTAEDU_BASE; credenciais em tests/tabler/saida/credenciais.json.
 */
const fs = require('fs');
const path = require('path');
const L = require('./lib');

const cred = JSON.parse(fs.readFileSync(path.join(__dirname, 'saida', 'credenciais.json'), 'utf8'));
const PERFIS = ['administrador', 'direcao.demo', 'supervisao.demo', 'secretaria.demo', 'coord.demo', 'prof.laura.demo'];
const REF = path.join(__dirname, 'menu-referencia.json');
const GRAVAR = process.argv[2] === 'gravar';
const referencia = fs.existsSync(REF) ? JSON.parse(fs.readFileSync(REF, 'utf8')) : {};
const gravada = {};
const normal = (href, base) => href.replace(base + 'painel/', '').replace(/\/+$/, '').toLowerCase();

async function entrar(ctx, base, perfil) {
  const p = await L.pagina(ctx);
  await p.goto(base + 'painel/login/acesso');
  await p.fill('input[name="usuario"]', perfil);
  await p.fill('input[name="senha"]', cred.painel[perfil]);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
  return p;
}

(async () => {
  const { navegador } = await L.abrir();
  try {
    console.log('\n== Menu lateral');
    for (const perfil of PERFIS) {
      const c2 = await navegador.newContext({ viewport: { width: 1366, height: 900 } });
      const p = await entrar(c2, L.BASE, perfil);
      const novo = (await p.$$eval('#railMenu a.nav-link[href]:not([data-vc-fixo])', (as) => as.map((a) => a.href))).map((h) => normal(h, L.BASE));
      const fixos = [];
      gravada[perfil] = [...new Set(novo)].sort();
      const a = [...new Set(referencia[perfil] || [])].sort();
      const b = [...new Set(novo.filter((h) => !fixos.includes(h)))].sort();
      const faltam = a.filter((x) => !b.includes(x));
      const sobram = b.filter((x) => !a.includes(x));
      L.ok(faltam.length === 0 && sobram.length === 0,
        `${perfil}: os ${a.length} itens da referência estão no rail${faltam.length ? ' — faltam ' + faltam.join(', ') : ''}${sobram.length ? ' — a mais ' + sobram.join(', ') : ''}`);

      if (perfil === 'administrador') {
        const grupo = await p.$('.vc-side-grupo-toggle[aria-expanded="false"]');
        const alvo = await grupo.getAttribute('aria-controls');
        await grupo.click();
        await p.waitForTimeout(300);
        L.ok(await p.$eval('#' + alvo, (ul) => ul.classList.contains('aberta') && ul.getBoundingClientRect().height > 20), 'o grupo abre');
        await grupo.click();
        await p.waitForTimeout(400);
        L.ok(await p.$eval('#' + alvo, (ul) => !ul.classList.contains('aberta')), 'o grupo fecha');

        await p.click('#vc-side-recolher');
        L.ok(await p.$eval('.page', (d) => d.classList.contains('vc-side-mini')), 'o recolher liga (coluna de ícones)');
        await L.print(p, 'etapa3/depois', 'menu-recolhido');
        await p.reload();
        L.ok(await p.$eval('.page', (d) => d.classList.contains('vc-side-mini')), 'recolhido continua depois de recarregar (localStorage)');
        await p.hover('#railMenu');
        await p.$eval('#vc-side-recolher', (b) => b.click());
        L.ok(await p.$eval('.page', (d) => !d.classList.contains('vc-side-mini')), 'o recolher desliga');

        await p.click('#vc-side-alca');
        L.ok(await p.$eval('#vc-side-acoes', (u) => u.classList.contains('aberta')), 'a alça do usuário abre (Meu perfil, Sair, versão)');
        await p.keyboard.press('Escape');

        await p.setViewportSize({ width: 390, height: 844 });
        await p.reload();
        L.ok(!(await p.isVisible('#railItens')), '390 px: o menu nasce fechado');
        await p.click('.navbar-toggler');
        await p.waitForTimeout(500);
        L.ok(await p.isVisible('#railItens'), '390 px: o botão abre o menu');
        await L.print(p, 'etapa3/depois', 'menu-celular-aberto');
      }
      await c2.close();
    }
  } catch (e) {
    L.ok(false, 'erro na prova: ' + e.message.split('\n')[0]);
  } finally {
    if (GRAVAR) { fs.writeFileSync(REF, JSON.stringify(gravada, null, 1) + '\n'); console.log('referência gravada: ' + REF); }
    await navegador.close();
    process.exit(L.resumo('menu') ? 0 : 1);
  }
})();
