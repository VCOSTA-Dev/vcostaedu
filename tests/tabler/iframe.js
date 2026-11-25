/*
 * A PROVA DO PORTAL DENTRO DE UM IFRAME (Etapa 6): o portal aberto num iframe HTTPS de OUTRA origem — como no Moodle —
 * com 600 px de largura, logado e navegando; o plano B quando o navegador não guarda a sessão; o painel recusado.
 *
 *   node tests/tabler/iframe.js
 *
 * Precisa de:
 *   - o portal em HTTPS (VCOSTAEDU_BASE_HTTPS; padrão: o VCOSTAEDU_BASE com https://);
 *   - a origem de teste em ORIGENS_PERMITIDAS_IFRAME do portal/core/Config.php (fora do git) — o script diz qual é
 *     e falha se o cabeçalho frame-ancestors não a traz.
 * A página "do Moodle" é TEMPORÁRIA: escrita na pasta temporária do sistema (fora do repositório), servida pelo
 * próprio navegador de teste (interceptação, nenhum servidor) na origem https://127.0.0.1:9443 — outro SITE, não só
 * outra origem, em relação a https://localhost — e apagada no fim. O certificado local é autoassinado: o contexto de
 * teste o aceita.
 */
const fs = require('fs');
const os = require('os');
const path = require('path');
const L = require('./lib');

const ORIGEM = 'https://127.0.0.1:9443';
const BASE_HTTPS = process.env.VCOSTAEDU_BASE_HTTPS || L.BASE.replace(/^http:/, 'https:');
const cred = JSON.parse(fs.readFileSync(path.join(__dirname, 'saida', 'credenciais.json'), 'utf8'));
const RA = '260101';
const temporaria = path.join(os.tmpdir(), `vcostaedu-iframe-${Date.now()}.html`);

const pagina = (src) => `<!doctype html><html lang="pt-br"><head><meta charset="utf-8"><title>Curso (simulação)</title>
<style>body{margin:0;font-family:system-ui,sans-serif;background:#f4f5f7}header{background:#3a4a5c;color:#fff;padding:10px 16px}
main{padding:12px 16px}iframe{width:600px;height:820px;border:1px solid #ccd;background:#fff}</style></head>
<body><header>Página de curso simulada (teste do iframe) — origem ${ORIGEM}</header>
<main><iframe id="portal" src="${src}"></iframe></main></body></html>`;

async function abrirCurso(contexto, src, simularBloqueio = false) {
  fs.writeFileSync(temporaria, pagina(src));
  await contexto.route(ORIGEM + '/**', (r) => r.fulfill({ contentType: 'text/html; charset=utf-8', body: fs.readFileSync(temporaria, 'utf8') }));
  if (simularBloqueio) {
    /* O navegador que não guarda o cookie dentro do iframe: a segunda chamada da sonda não reconhece a primeira. */
    await contexto.route('**/portal/login/sonda?n=*', (r) => r.fulfill({ contentType: 'application/json', body: '{"ok":false}' }));
  }
  const p = await L.pagina(contexto);
  await p.goto(ORIGEM + '/curso.html');
  await p.waitForTimeout(1500);
  return p;
}

(async () => {
  const { navegador } = await L.abrir();
  /* A página de teste vem da interceptação, sem endereço: o Chromium a trata como pública e barra que ela embuta um
     endereço local (Local Network Access). Entre um Moodle e um portal publicados isso não existe; aqui a permissão
     é dada à origem de teste, e só a ela. */
  const novoContexto = async () => {
    const c = await navegador.newContext({ viewport: { width: 900, height: 950 }, locale: 'pt-BR', ignoreHTTPSErrors: true });
    await c.grantPermissions(['local-network-access'], { origin: ORIGEM }).catch((e) => console.log('  (permissão local-network-access: ' + e.message.split('\n')[0] + ')'));
    return c;
  };
  try {
    /* 0. O cabeçalho do portal deixa a origem de teste embutir. */
    const c0 = await novoContexto();
    const r0 = await c0.request.get(BASE_HTTPS + 'portal/login/acesso');
    const csp = r0.headers()['content-security-policy'] || '';
    L.ok(csp.includes(ORIGEM), `o portal em HTTPS permite a origem ${ORIGEM} (frame-ancestors: "${csp.slice(0, 120)}")`);
    await c0.close();

    /* 1. Logado e navegando, a 600 px. */
    const c1 = await novoContexto();
    const p = await abrirCurso(c1, BASE_HTTPS + 'portal/login/acesso');
    const f = p.frameLocator('#portal');
    await f.locator('input[name="usuario"]').waitFor({ timeout: 20000 });
    L.ok(await f.locator('#cartaoLogin').isVisible(), 'dentro do iframe: o login aparece (#cartaoLogin)');
    L.ok(await f.locator('#planoB').isHidden(), 'dentro do iframe: a sonda achou a sessão e o plano B fica escondido');
    await L.print(p, path.join('etapa6', 'depois'), 'iframe-1-login').catch(() => {});
    await f.locator('input[name="usuario"]').fill(RA);
    await f.locator('input[name="senha"]').fill(cred.portal[RA]);
    await f.locator('input[name="SendLogin"]').click();
    await f.locator('aside.vc-side').waitFor({ timeout: 20000 });
    const quadro = p.frames().find((x) => x !== p.mainFrame());
    L.ok(/\/portal\/home\/index/.test(quadro.url()), `entrou sem sair do curso (${quadro.url().replace(BASE_HTTPS, '')})`);
    const cookie = (await c1.cookies(BASE_HTTPS)).find((k) => k.name === 'VCOSTAEDU_PORTAL');
    L.ok(cookie && cookie.sameSite === 'None' && cookie.secure, `o cookie da sessão é SameSite=None e Secure (${cookie ? cookie.sameSite + (cookie.secure ? ', Secure' : '') : 'sem cookie'})`);
    const largura = await quadro.evaluate(() => [window.innerWidth, document.documentElement.scrollWidth]);
    L.ok(largura[0] <= 600 && largura[1] <= largura[0], `a 600 px nada passa da largura (janela ${largura[0]}, conteúdo ${largura[1]})`);
    await L.print(p, path.join('etapa6', 'depois'), 'iframe-2-inicio').catch(() => {});

    /* navegar pelo menu do telefone (a 600 px o rail vira a barra com o botão) */
    await f.locator('.navbar-toggler').click();
    await f.locator('.vc-side-grupo-toggle[title="Acadêmico"]').click();
    await f.locator('.vc-side-grupo-itens.aberta a[href*="boletim-aluno"]').click();
    await f.locator('.boletimCurso').first().waitFor({ timeout: 20000 });
    L.ok(true, 'pelo menu: o Boletim abre dentro do iframe, sem novo login');
    const largura2 = await quadro.evaluate(() => [window.innerWidth, document.documentElement.scrollWidth]);
    L.ok(largura2[1] <= largura2[0], `o boletim cabe nos 600 px (conteúdo ${largura2[1]})`);
    await L.print(p, path.join('etapa6', 'depois'), 'iframe-3-boletim').catch(() => {});
    await f.locator('.navbar-toggler').click();
    await f.locator('.vc-side-grupo-toggle[title="Acadêmico"]').click().catch(() => {});
    await f.locator('a[href*="notas-atividades"]:visible').first().click();
    await f.locator('#accordionNotas, .alert-info').first().waitFor({ timeout: 20000 });
    L.ok(true, 'pelo menu: as Notas das Atividades abrem');
    await f.locator('.navbar-toggler').click();
    await f.locator('#vc-side-alca').click();
    await f.locator('#vc-side-acoes a[href*="perfil/visualizar"]').click();
    await f.locator('text=Ana Beatriz Exemplo').first().waitFor({ timeout: 20000 });
    L.ok(true, 'pela alça: Meu perfil abre');
    await L.semErros(p, 'iframe logado');
    await c1.close();

    /* 2. O plano B: o navegador não guarda a sessão dentro do iframe. */
    const c2 = await novoContexto();
    const p2 = await abrirCurso(c2, BASE_HTTPS + 'portal/login/acesso', true);
    const f2 = p2.frameLocator('#portal');
    await f2.locator('#planoB').waitFor({ state: 'visible', timeout: 20000 }).catch(() => {});
    L.ok(await f2.locator('#planoB').isVisible(), 'sessão bloqueada: o plano B aparece');
    L.ok(await f2.locator('#cartaoLogin').isHidden(), 'sessão bloqueada: o formulário some (#cartaoLogin)');
    L.ok((await f2.locator('#abrirNovaAba').getAttribute('target')) === '_blank', 'o botão abre o portal numa aba própria');
    await L.print(p2, path.join('etapa6', 'depois'), 'iframe-4-plano-b').catch(() => {});
    await c2.close();

    /* 3. O painel nunca abre num iframe. */
    const c3 = await novoContexto();
    const p3 = await abrirCurso(c3, BASE_HTTPS + 'painel/login/acesso');
    const q3 = p3.frames().find((x) => x !== p3.mainFrame());
    const abriu = q3 ? !!(await q3.$('input[name="SendLogin"]').catch(() => null)) : false;
    L.ok(!abriu, 'o painel é recusado dentro do iframe');
    await c3.close();
  } catch (e) {
    L.ok(false, 'erro na prova: ' + e.message.split('\n')[0]);
  } finally {
    if (fs.existsSync(temporaria)) { fs.unlinkSync(temporaria); }
    L.ok(!fs.existsSync(temporaria), 'a página temporária foi apagada');
    await navegador.close();
    process.exit(L.resumo('iframe (etapa 6)') ? 0 : 1);
  }
})();
