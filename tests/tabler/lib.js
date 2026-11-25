/*
 * Biblioteca comum das provas da onda Tabler (Playwright, Chromium), por HTTP real contra o Apache.
 *
 * Configuração SÓ pelo ambiente (nada de endereço ou nome de banco em arquivo versionado):
 *   VCOSTAEDU_BASE           endereço da instalação, com barra no fim (ex.: http://localhost/<pasta>/)
 *   VCOSTAEDU_BANCO_TESTE    o banco descartável (termina em _teste)
 *   VCOSTAEDU_PROVAS         pasta dos prints (padrão: provas/tabler na raiz do projeto, fora do git)
 */
const path = require('path');
const fs = require('fs');
const { chromium } = require('playwright-core');

const RAIZ = path.resolve(__dirname, '..', '..');
const BASE = process.env.VCOSTAEDU_BASE || '';
if (!/^https?:\/\/.+\/$/.test(BASE)) {
  console.error('Defina VCOSTAEDU_BASE com o endereço da instalação, terminado em /.');
  process.exit(2);
}
const PROVAS = process.env.VCOSTAEDU_PROVAS || path.join(RAIZ, 'provas', 'tabler');

/* Falhas acumuladas: a prova não para na primeira; o resumo diz tudo o que falhou. */
const falhas = [];
let oks = 0;
function ok(cond, texto) {
  if (cond) { oks++; console.log('  [OK]    ' + texto); } else { falhas.push(texto); console.log('  [FALHA] ' + texto); }
  return cond;
}
function resumo(nome) {
  console.log(`\n${nome}: ${oks} OK, ${falhas.length} FALHA`);
  if (falhas.length) { console.log(falhas.map((f) => '  - ' + f).join('\n')); }
  return falhas.length === 0;
}

async function abrir(opcoes = {}) {
  const navegador = await chromium.launch(opcoes.lancar || {});
  const contexto = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR', ...(opcoes.contexto || {}) });
  contexto.setDefaultTimeout(20000);
  return { navegador, contexto };
}

/* Uma página que registra erro de console e erro de script. */
async function pagina(contexto) {
  const p = await contexto.newPage();
  p._erros = [];
  p.on('console', (m) => { if (m.type() === 'error') p._erros.push('console: ' + m.text()); });
  p.on('pageerror', (e) => p._erros.push('script: ' + e.message));
  return p;
}

/*
 * O JARGÃO NA TELA (docs/PENTE_FINO.md): o texto que a pessoa lê não traz anotação de desenvolvimento (onda, etapa
 * numerada, decisão D-nn, DUVIDAS), "Ensino" no sentido de curso, nem "Dashboard" no menu. Recebe o texto visível e
 * os rótulos (th, label, option, botão) e o texto do menu; devolve o que achou.
 */
const JARGAO = [
  [/\bOnda \d/, 'anotação de onda'],
  [/\bEtapa \d/, 'etapa numerada'],
  [/\bD-\d{2,3}\b/, 'número de decisão'],
  [/\bDUVIDAS\b/, 'referência a DUVIDAS'],
  [/Cadastro de Ensinos|Nome do Ensino|Matérias de Ensino|Editar Ensino|\b(do|o|no) Ensino\b(?! (Superior|Médio|Fundamental))/, '"Ensino" no lugar de curso'],
];
function jargao(texto, rotulos = [], menu = '') {
  const achou = [];
  for (const [re, nome] of JARGAO) {
    const m = texto.match(re);
    if (m) { achou.push(`${nome}: "${texto.slice(Math.max(0, m.index - 30), m.index + 40).replace(/\s+/g, ' ')}"`); }
  }
  if (rotulos.some((r) => /^(Ensino|Selecione o Ensino|Escolha o Ensino)$/.test(r))) { achou.push('rótulo "Ensino" no lugar de "Curso"'); }
  if (/\bDashboard\b/.test(menu)) { achou.push('"Dashboard" no menu'); }
  return achou;
}
async function textoDaTela(p) {
  return p.evaluate(() => ({
    texto: (document.title || '') + '\n' + (document.body ? document.body.innerText : ''),
    rotulos: Array.from(document.querySelectorAll('th, label, option, button, .btn')).filter((e) => !e.closest('aside, nav, header, .navbar')).map((e) => (e.textContent || '').trim()), // o grupo "Ensino" do menu é a área, não o curso
    menu: Array.from(document.querySelectorAll('aside, .navbar-vertical, #menuLateral')).map((e) => e.textContent || '').join(' '),
  })).catch(() => ({ texto: '', rotulos: [], menu: '' }));
}

/* Sem aviso do PHP no HTML e sem erro no console (e sem jargão no texto da tela: só aparece quando acha). */
async function semErros(p, onde, ignorar = null) {
  const html = await p.content();
  const php = html.match(/(Warning|Notice|Deprecated|Fatal error|Parse error|Uncaught)\b[^<]{0,160}/);
  ok(!php, `${onde}: sem aviso do PHP${php ? ' (' + php[0] + ')' : ''}`);
  const t = await textoDaTela(p);
  const j = jargao(t.texto, t.rotulos, t.menu);
  if (j.length) { ok(false, `${onde}: jargão na tela (${j.join('; ')})`); }
  const erros = p._erros.splice(0).filter((x) => !(ignorar && ignorar.test(x)));
  ok(erros.length === 0, `${onde}: sem erro no console${erros.length ? ' (' + erros.join(' | ').slice(0, 300) + ')' : ''}`);
}

/* Print em 1366 px e em 390 px. */
async function print(p, etapa, nome) {
  const dir = path.join(PROVAS, etapa);
  fs.mkdirSync(dir, { recursive: true });
  const original = p.viewportSize();
  await p.setViewportSize({ width: 1366, height: 900 });
  await p.waitForTimeout(200);
  /* o Chromium às vezes recusa o print de página longa ("Unable to capture screenshot"): uma nova tentativa */
  const shot = async (arq) => { try { await p.screenshot({ path: arq, fullPage: true }); } catch (e) { await p.waitForTimeout(500); await p.screenshot({ path: arq, fullPage: true }); } };
  await shot(path.join(dir, `${nome}-1366.png`));
  await p.setViewportSize({ width: 390, height: 844 });
  await p.waitForTimeout(400); // o rail e o .page-wrapper têm transição: sem a espera o print sai no meio dela
  await shot(path.join(dir, `${nome}-390.png`));
  await p.setViewportSize(original);
}

/** O retrato da régua mais novo (regua-ondaN-etapaM.json, a onda e a etapa mais altas), relativo à raiz — o que as provas
    de cada etapa comparam (Onda 4: as ondas seguintes também contam). */
function reguaAtual() {
  const pasta = path.join(__dirname, 'saida');
  const ordem = (f) => { const m = /^regua-onda(\d+)-etapa(\d+)\.json$/.exec(f); return +m[1] * 100 + +m[2]; };
  const lista = fs.readdirSync(pasta).filter((f) => /^regua-onda\d+-etapa\d+\.json$/.test(f)).sort((a, b) => ordem(a) - ordem(b));
  return path.join('tests', 'tabler', 'saida', lista[lista.length - 1]);
}

/**
 * Correção da escola, Parte 1 (D-100): a confirmação do sistema (a janela #vcConfirmar) no lugar da do navegador. As
 * provas que aceitavam a janela do navegador (page.on('dialog')) passam a confirmar a do sistema sozinhas, como a pessoa
 * clicaria em "Confirmar". Vale para a página dada (e para cada endereço que ela abrir depois).
 */
async function confirmarSozinho(p) {
  const instalar = () => {
    if (window.vcConfirmaSozinho) { return; }
    window.vcConfirmaSozinho = true;
    setInterval(() => {
      const m = document.getElementById('vcConfirmar');
      const b = document.getElementById('vcConfirmarSim');
      if (m && b && m.classList.contains('show') && !b.disabled && !b.dataset.vcAuto) {
        b.dataset.vcAuto = '1';
        setTimeout(() => { delete b.dataset.vcAuto; b.click(); }, 60);
      }
    }, 100);
  };
  await p.addInitScript(instalar);
  await p.evaluate(instalar).catch(() => {}); // a página já aberta também
}

module.exports = { BASE, RAIZ, PROVAS, ok, resumo, abrir, pagina, semErros, print, falhas, jargao, textoDaTela, reguaAtual, confirmarSozinho };
