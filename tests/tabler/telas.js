/*
 * A PROVA DAS TELAS (cada etapa da conversão): para cada tela da lista, entra com o perfil certo, abre a tela e
 *   - confere que não há aviso do PHP (Warning, Notice, Deprecated, Fatal) no HTML nem erro no console;
 *   - confere que não sobrou classe ou atributo do dicionário (§2 do LEVANTAMENTO_DEPARA_TABLER.md) no HTML
 *     RENDERIZADO (o DOM, depois do JavaScript da tela);
 *   - confere que a tela está no layout Tabler (o rail e o page-header, ou a página centrada do acesso);
 *   - tira o print em 1366 px e em 390 px.
 *
 *   node tests/tabler/telas.js <lista.json> <etapa> depois     a prova (contra VCOSTAEDU_BASE)
 *   node tests/tabler/telas.js <lista.json> <etapa> antes      só os prints, contra a cópia AdminLTE
 *                                                               (VCOSTAEDU_BASE + 'provas/_antes/')
 *
 * A lista: [{ "nome": "usuarios-listar", "rota": "painel/usuarios/listar", "perfil": "administrador",
 *             "area": "Equipe", "layout": "painel" | "acesso" | "portal" | "portal-acesso" | "publica" | "documento",
 *             "redireciona": true (rota de POST: no GET a origem redireciona) }]
 * O "perfil" é o login no painel (ou o RA no portal); as senhas vêm de tests/tabler/saida/credenciais.json.
 * Rota com {id:<tabela>} usa o menor id da tabela (consulta no banco de prova).
 */
const fs = require('fs');
const path = require('path');
const L = require('./lib');

const [listaArq, etapa, modo = 'depois'] = process.argv.slice(2);
if (!listaArq || !etapa) { console.error('Uso: node tests/tabler/telas.js <lista.json> <etapa> [antes|depois]'); process.exit(2); }
const lista = JSON.parse(fs.readFileSync(listaArq, 'utf8'));
const cred = JSON.parse(fs.readFileSync(path.join(__dirname, 'saida', 'credenciais.json'), 'utf8'));
const BASE = modo === 'antes' ? L.BASE + 'provas/_antes/' : L.BASE;

/* O dicionário, sobre o DOM renderizado: classes e atributos que não podem sobrar numa tela convertida. */
const DICIONARIO = /^(content-wrapper|content-header|main-sidebar|main-header|main-footer|nav-sidebar|nav-treeview|brand-link|user-panel|breadcrumb|info-box.*|small-box.*|callout|callout-.*|card-tools|btn-tool|card-outline|[mp][lr]-(sm-|md-|lg-|xl-)?(auto|n?[0-5])|(float|text)-(sm-|md-|lg-|xl-)?(left|right)|badge-(pill|primary|secondary|success|danger|warning|info|light|dark)|form-group|form-row|custom-(select|control|control-input|control-label|checkbox|radio|switch|file.*)|input-group-(append|prepend)|close|sr-only|no-gutters|font-weight-.*|font-italic|btn-block|thead-(light|dark)|img-circle|elevation-[1-5]|embed-responsive.*|jumbotron|media-body|form-inline|form-control-file|text-monospace)$/;
const ATRIBUTOS = ['data-toggle', 'data-target', 'data-dismiss', 'data-placement', 'data-parent', 'data-widget', 'data-card-widget'];

async function entrar(p, app, perfil) {
  const senha = app === 'portal' ? cred.portal[perfil] : cred.painel[perfil];
  await p.goto(BASE + app + '/login/acesso');
  await p.fill('input[name="usuario"]', perfil);
  await p.fill('input[name="senha"]', senha);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
}

(async () => {
  const { navegador } = await L.abrir();
  const contextos = {};
  const resultado = [];
  try {
    for (const t of lista) {
      const app = t.rota.startsWith('portal/') ? 'portal' : 'painel';
      const chave = (t.perfil || 'anonimo') + '@' + app;
      if (!contextos[chave]) {
        contextos[chave] = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' });
        if (t.perfil) {
          const p0 = await L.pagina(contextos[chave]);
          await entrar(p0, app, t.perfil);
          await p0.close();
        }
      }
      const p = await L.pagina(contextos[chave]);
      const resp = await p.goto(BASE + t.rota);
      await p.waitForLoadState('networkidle').catch(() => {});
      await p.waitForTimeout(300);
      const onde = `${t.nome} (${t.perfil || 'sem login'})`;
      if (modo === 'depois') {
        L.ok(resp && resp.status() < 400, `${onde}: HTTP ${resp ? resp.status() : '?'}`);
        L.ok(!(await p.$('input[name="SendLogin"]')) && !(await p.$('#semAcesso')) || (t.layout || '').includes('acesso'), `${onde}: abriu (não voltou ao login)`);
        const final = decodeURIComponent(p.url()).replace(/[?#].*$/, '').replace(/\/+$/, '');
        L.ok(t.redireciona || final.endsWith(t.rota.replace(/\/+$/, '')), `${onde}: ficou na rota pedida${final.endsWith(t.rota.replace(/\/+$/, '')) ? '' : ' (foi para ' + final.replace(BASE, '') + ')'}`);
        await L.semErros(p, onde, t.ignorarConsole ? new RegExp(t.ignorarConsole) : null);
        const sobra = await p.evaluate(([reFonte, atributos]) => {
          const re = new RegExp(reFonte);
          const achados = new Set();
          document.querySelectorAll('*').forEach((el) => {
            el.classList.forEach((c) => { if (re.test(c)) { achados.add('.' + c); } });
            atributos.forEach((a) => { if (el.hasAttribute(a)) { achados.add('[' + a + ']'); } });
          });
          return [...achados];
        }, [DICIONARIO.source, ATRIBUTOS]);
        L.ok(sobra.length === 0, `${onde}: sem sobra do dicionário${sobra.length ? ' (' + sobra.join(' ') + ')' : ''}`);
        /* "documento": a prévia de um documento (HTML próprio, sem layout); "publica": página avulsa com o CSS do Tabler */
        if ((t.layout || '') !== 'documento') {
          const tabler = (t.layout || 'painel').includes('acesso')
            ? await p.$('.page-center, .page.page-center')
            : (t.layout === 'publica' ? await p.$('link[href*="tabler.min.css"]') : await p.$('.navbar-vertical.vc-side'));
          L.ok(!!tabler, `${onde}: layout Tabler`);
        }
      }
      await L.print(p, path.join(etapa, modo), t.nome);
      resultado.push({ nome: t.nome, area: t.area || '', perfil: t.perfil || '', rota: t.rota });
      await p.close();
    }
  } catch (e) {
    L.ok(false, 'erro na prova: ' + e.message.split('\n')[0]);
  } finally {
    fs.mkdirSync(path.join(L.PROVAS, etapa), { recursive: true });
    fs.writeFileSync(path.join(L.PROVAS, etapa, `telas-${modo}.json`), JSON.stringify(resultado, null, 2));
    await navegador.close();
    process.exit(L.resumo(`telas ${etapa} (${modo})`) ? 0 : 1);
  }
})();
