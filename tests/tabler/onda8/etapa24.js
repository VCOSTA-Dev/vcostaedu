/*
 * PROVA DA ONDA 8, CORREÇÃO, SEGUNDA LEVA, 2.10 — o Nginx e as pastas protegidas (D-96). Usa a instalação que o ciclo
 * deixa (a demonstração do ensino superior); não muda dados.
 *   1. as Verificações de segurança, no Apache (que lê o .htaccess): o item "Pastas protegidas" diz OK (cada pasta
 *      pedida pela web recusa); a linha de comando diz o mesmo (a última conferência da tela);
 *   2. uma pasta protegida que responde (o core/.htaccess tirado, como num servidor que não o lê): o item diz FALHA,
 *      nomeia core/ e diz o que fazer (a configuração do Nginx, o guia); o arquivo volta igual;
 *   3. o exemplo do Nginx (docs/nginx/vcostaedu.conf) nega as mesmas pastas que os .htaccess negam ("Require all
 *      denied" e [F]), os mesmos tipos de arquivo em qualquer pasta, os arquivos dos sites de exemplo, e faz as mesmas
 *      rotas (o painel, o portal e a raiz para index.php?url=, a interface para api/index.php, o instalador para o
 *      index.php dele); o PHP vem depois das recusas; o próprio exemplo não responde pela web no Apache;
 *   4. o guia curto (docs/IMPLANTACAO.md) e o exemplo entram no pacote de instalação;
 *   5. o registro das mudanças diz "O Nginx e as pastas protegidas";
 *   sabotagem: a verificação sem acusar a pasta que responde — com o core/.htaccess tirado, o item diria OK; a prova
 *   acusa; os arquivos voltam iguais.
 *   (O exemplo não é posto para rodar num Nginx: não há Nginx nesta máquina — o retorno registra.)
 * Ambiente: VCOSTAEDU_BASE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { spawnSync } = require('child_process');
const L = require('../lib');

const ETAPA = 'onda8/etapa24';
const VERIF = path.join(L.RAIZ, 'core', 'Verificacoes.php');
const TRAVA = path.join(L.RAIZ, 'core', '.htaccess');
const CONF = path.join(L.RAIZ, 'docs', 'nginx', 'vcostaedu.conf');
const PHP = process.env.VCOSTAEDU_PHP || 'C:/xampp/php/php.exe';
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const ler = (r) => fs.readFileSync(path.join(L.RAIZ, r), 'utf8');

async function item(p) {
  await p.goto(L.BASE + 'painel/verificacoes-seguranca/index');
  return p.$eval('#itensSeguranca [data-codigo="pastas"]', (x) => ({ situacao: x.dataset.situacao, texto: x.innerText.replace(/\s+/g, ' ').trim() })).catch(() => null);
}
function cli() {
  const r = spawnSync(PHP, [path.join(L.RAIZ, 'database', 'ferramentas', 'implantacao', 'verificar_seguranca.php')], { cwd: L.RAIZ, encoding: 'utf8' });
  return ((r.stdout || '') + (r.stderr || '')).split('\n').find((l) => /Pastas protegidas:/.test(l)) || '';
}

(async () => {
  const { navegador } = await L.abrir();
  const shaVerif = sha(VERIF);
  const trava = fs.readFileSync(TRAVA);
  try {
    const ctx = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' });
    const p = await L.pagina(ctx);
    await p.goto(L.BASE + 'painel/login/acesso');
    await p.fill('input[name="usuario"]', 'administrador');
    await p.fill('input[name="senha"]', process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123');
    await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);

    /* 1. no Apache */
    const ok = await item(p);
    await L.print(p, ETAPA, 'verificacoes-pastas-protegidas');
    const linha = cli();
    L.ok(ok && ok.situacao === 'OK' && /pastas protegidas pedidas pela web recusam/.test(ok.texto) && /OK/.test(linha) && /última conferência pela tela/.test(linha),
      `no Apache, "Pastas protegidas" OK ("${ok ? ok.texto.slice(0, 110) : '—'}…"); a linha de comando, a mesma conferência ("${linha.trim().slice(0, 80)}…")`);

    /* 2. uma pasta que responde */
    let aberta = null;
    try {
      fs.unlinkSync(TRAVA);
      aberta = await item(p);
    } finally {
      fs.writeFileSync(TRAVA, trava);
    }
    L.ok(aberta && aberta.situacao === 'FALHA' && /core\//.test(aberta.texto) && /docs\/nginx\/vcostaedu\.conf/.test(aberta.texto) && /IMPLANTACAO/.test(aberta.texto) && fs.readFileSync(TRAVA).equals(trava),
      `sem o core/.htaccess (a pasta responde): FALHA, nomeia core/ e diz o que fazer ("${aberta ? aberta.texto.slice(0, 140) : '—'}…"); o arquivo voltou`);
    await item(p); // a conferência guardada volta a ser a do servidor protegido

    /* 3. o exemplo do Nginx contra os .htaccess */
    const conf = fs.readFileSync(CONF, 'utf8');
    const semComentario = conf.split('\n').map((l) => l.replace(/#.*$/, '')).join('\n');
    const pastasNegadas = spawnSync('git', ['ls-files', '*.htaccess', '.htaccess'], { cwd: L.RAIZ, encoding: 'utf8' }).stdout.split('\n').filter(Boolean)
      .filter((f) => /^\s*Require all denied\s*$/m.test(ler(f)) && !/<Files/i.test(ler(f))).map((f) => f.replace(/\.htaccess$/, ''));
    pastasNegadas.push('storage/'); // criada pelo instalador, com o .htaccess dela (fora do repositório)
    const faltaPasta = pastasNegadas.filter((d) => !new RegExp(`location \\^~ /${d.replace(/\//g, '\\/')} \\{ deny all; \\}`).test(semComentario));
    const ext = (ler('.htaccess').match(/<FilesMatch "\(\?i\)\(\\\.\(([^)]+)\)/) || [])[1];
    const temExt = !!ext && semComentario.includes(`\\.(${ext})`);
    const fx = ['/(painel|portal)/(app|core|vendor)(/|$)', '/painel/assets/docs/alunos/', '/\\.git(/|$)', '/assets/.*\\.(php[0-9]?|phtml|phar|pht|phps|cgi|pl|py)$',
      'config\\.local\\.php|config\\.exemplo\\.php|lib\\.php|LEIA-ME\\.md|FONTES\\.md', '/exemplos/curso-livre/dados(/|$)'];
    const faltaFx = fx.filter((x) => !semComentario.includes(x));
    const rotas = ['/painel/index.php?url=$1', '/portal/index.php?url=$1', '/index.php?url=$1', 'rewrite ^ /api/index.php', '$document_root/instalar/index.php'];
    const faltaRota = rotas.filter((r) => !semComentario.includes(r));
    const posPhp = semComentario.indexOf('location ~ \\.php$');
    const ultimaRecusa = Math.max(...[...semComentario.matchAll(/location ~\*? [^\n]*\{ (deny all|return 404); \}/g)].map((m) => m.index));
    const exemploFechado = await p.evaluate(async (u) => (await fetch(u)).status, L.BASE + 'docs/nginx/vcostaedu.conf');
    L.ok(pastasNegadas.length >= 6 && faltaPasta.length === 0 && temExt && faltaFx.length === 0 && faltaRota.length === 0 && posPhp > ultimaRecusa && [403, 404].includes(exemploFechado),
      `o exemplo do Nginx nega as ${pastasNegadas.length} pastas dos .htaccess (${pastasNegadas.join(', ')}), os tipos de arquivo (${ext}) e os dos exemplos; faz as ${rotas.length} rotas; o PHP depois das recusas; o exemplo não responde no Apache (${exemploFechado})`
      + (faltaPasta.length + faltaFx.length + faltaRota.length ? ` — faltam: ${[...faltaPasta, ...faltaFx, ...faltaRota].join('; ')}` : ''));

    /* 4. no pacote */
    const reDocs = (ler('database/ferramentas/implantacao/gerar_pacote.php').match(/'#\^docs\/\(\?!([^']+)\)#'/) || [])[1];
    const foraDoPacote = (f) => new RegExp(`^docs/(?!${reDocs})`).test(f);
    L.ok(!!reDocs && !foraDoPacote('docs/IMPLANTACAO.md') && !foraDoPacote('docs/nginx/vcostaedu.conf') && !foraDoPacote('docs/nginx/.htaccess') && foraDoPacote('docs/DECISIONS.md') && /Nginx/.test(ler('docs/IMPLANTACAO.md')),
      'o guia curto (docs/IMPLANTACAO.md) e o exemplo (docs/nginx/) entram no pacote; o resto de docs/ continua fora');

    /* 5. o registro das mudanças */
    await p.goto(L.BASE + 'painel/novidades/listar');
    L.ok(/O Nginx e as pastas protegidas/.test(await p.evaluate(() => document.body.innerText)), 'o registro das mudanças diz "O Nginx e as pastas protegidas"');

    /* sabotagem: a verificação sem acusar a pasta que responde */
    const orig = fs.readFileSync(VERIF, 'utf8');
    const trecho = 'if ($codigo >= 200 && $codigo < 300) {';
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let s = null;
    try {
      fs.writeFileSync(VERIF, orig.replace(trecho, 'if (false) {'));
      fs.unlinkSync(TRAVA);
      s = await item(p);
    } finally {
      fs.writeFileSync(TRAVA, trava);
      fs.writeFileSync(VERIF, orig);
    }
    await item(p);
    L.ok(s && s.situacao !== 'FALHA', `sabotagem (a verificação sem acusar a pasta que responde): a prova reprova — com core/ aberta, o item diria ${s ? s.situacao : '?'}`);
    L.ok(sha(VERIF) === shaVerif && fs.readFileSync(TRAVA).equals(trava), 'os arquivos sabotados voltaram iguais (sha256)');
    await ctx.close();
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (!fs.existsSync(TRAVA) || !fs.readFileSync(TRAVA).equals(trava)) { fs.writeFileSync(TRAVA, trava); console.log('  [AVISO] o core/.htaccess foi restaurado no fim'); }
    if (sha(VERIF) !== shaVerif) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
