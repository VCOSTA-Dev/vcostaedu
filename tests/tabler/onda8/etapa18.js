/*
 * PROVA DA ONDA 8, CORREÇÃO, SEGUNDA LEVA, 2.4 — a Página Inicial de cada perfil na escola (D-90). APAGA o banco de
 * prova.
 *   1. a demonstração do colégio, montada pela tela numa instalação nova;
 *   2. cada perfil entra e a Página Inicial mostra os atalhos dele: a direção, o painel acadêmico, os relatórios e as
 *      turmas; a secretaria e a supervisão, os alunos, as turmas, os responsáveis e a importação de alunos; a
 *      coordenação, as turmas e os relatórios; o professor segue com "O meu dia";
 *   3. cada atalho abre a tela dele (nenhum leva ao login nem à página sem acesso);
 *   sabotagem: a Página Inicial sem o perfil — a prova acusa; o arquivo volta igual.
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const L = require('../lib');
const X = require('./_escola');

const ETAPA = 'onda8/etapa18';
const HOME = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Views', 'home', 'home.php');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const ESPERADO = {
  'direcao.demo': ['relatorios/painel-academico', 'relatorios/listar', 'turmas/listar'],
  'secretaria.demo': ['academicos/listar', 'turmas/listar', 'responsaveis/listar', 'importacao-alunos/listar', 'fin-planos/listar', 'relatorios/caixa'], // e o financeiro (D-98: os planos, a ficha do caixa)
  'supervisao.demo': ['academicos/listar', 'turmas/listar', 'responsaveis/listar', 'importacao-alunos/listar', 'fin-planos/listar', 'relatorios/caixa'], // e o financeiro (D-98: os planos, a ficha do caixa)
  'coord.demo': ['turmas/listar', 'relatorios/listar'],
};

/** Os atalhos da Página Inicial (a rota de cada um) e aonde cada um leva. */
async function inicial(p) {
  await p.goto(L.BASE + 'painel/home/index');
  const rotas = await p.$$eval('a.atalho-rapido', (l) => l.map((a) => a.getAttribute('href').replace(/^.*\/painel\//, '').replace(/\/$/, '')));
  const dia = !!(await p.$('#diaProfessor'));
  const ruins = [];
  for (const r of rotas) {
    const x = await p.evaluate(async (u) => { const q = await fetch(u, { credentials: 'same-origin' }); const t = await q.text(); return { st: q.status, login: /name="SendLogin"/.test(t), sem: /id="semAcesso"/.test(t) }; }, L.BASE + 'painel/' + r);
    if (x.st !== 200 || x.login || x.sem) { ruins.push(`${r} (${x.login ? 'login' : x.sem ? 'sem acesso' : x.st})`); }
  }
  return { rotas, dia, ruins };
}

(async () => {
  const { navegador } = await L.abrir();
  const shaHome = sha(HOME);
  try {
    L.ok(X.montar('onda8-etapa18').status === 0, 'a demonstração do colégio montada pela tela, numa instalação nova');
    const c = X.cred();
    for (const [login, esperado] of Object.entries(ESPERADO)) {
      const s = await X.entrar(navegador, login, c.painel[login]);
      const h = await inicial(s.p);
      await L.print(s.p, ETAPA, `inicial-${login.split('.')[0]}`);
      await s.ctx.close();
      L.ok(JSON.stringify(h.rotas) === JSON.stringify(esperado) && h.ruins.length === 0,
        `${login}: a Página Inicial com ${h.rotas.join(', ') || '(nada)'}${h.ruins.length ? ' — não abriu: ' + h.ruins.join('; ') : '; cada atalho abre a tela'}`);
    }
    const prof = await X.entrar(navegador, 'prof.laura.demo', c.painel['prof.laura.demo']);
    const hp = await inicial(prof.p);
    await prof.ctx.close();
    L.ok(hp.dia && hp.ruins.length === 0, `o professor segue com "O meu dia" na Página Inicial${hp.ruins.length ? ' — não abriu: ' + hp.ruins.join('; ') : ''}`);

    /* sabotagem: a Página Inicial sem o perfil */
    const orig = fs.readFileSync(HOME, 'utf8');
    const trecho = '$papelHome = \\App\\adms\\Models\\helper\\AdmsTipoInstituicao::ehEscola()';
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let r = null;
    try {
      fs.writeFileSync(HOME, orig.replace(trecho, '$papelHome = null && \\App\\adms\\Models\\helper\\AdmsTipoInstituicao::ehEscola()'));
      const s = await X.entrar(navegador, 'direcao.demo', c.painel['direcao.demo']);
      r = await inicial(s.p);
      await s.ctx.close();
    } finally {
      fs.writeFileSync(HOME, orig);
    }
    L.ok(r && JSON.stringify(r.rotas) !== JSON.stringify(ESPERADO['direcao.demo']), `sabotagem (a Página Inicial sem o perfil): a prova reprova — a direção viu ${r ? r.rotas.join(', ') : '?'}`);
    L.ok(sha(HOME) === shaHome, 'o arquivo sabotado voltou igual (sha256)');
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(HOME) !== shaHome) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
