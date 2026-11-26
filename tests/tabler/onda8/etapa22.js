/*
 * PROVA DA ONDA 8, CORREÇÃO, SEGUNDA LEVA, 2.8 — "disciplina" nos guias e na documentação da API (D-94). Usa a
 * instalação que o ciclo deixa (a demonstração do ensino superior); não muda dados.
 *   1. a especificação servida pela instalação (Integrações → Especificação) não usa "componente" para a matéria da
 *      turma ("todos os componentes dela", "cada componente da turma"); diz "disciplina";
 *   2. os nomes não mudam: todo caminho, todo método e todo nome de campo da especificação de antes (a da 1.80.0, no
 *      histórico do repositório) continua na de agora, e o campo "componentes" da turma continua nos exemplos;
 *   3. o guia "Conhecer com a demonstração", aberto na Ajuda: "as etapas de uma disciplina", "os quatro módulos como
 *      disciplinas"; nos guias (docs/guia/tarefas), "componente" só nos da regra de média e do que ela calcula;
 *   4. o registro das mudanças diz "Disciplina" nos guias e na documentação da API;
 *   sabotagem: a especificação com o resumo antigo — a prova acusa; o arquivo volta igual.
 * Ambiente: VCOSTAEDU_BASE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { execFileSync } = require('child_process');
const L = require('../lib');

const ETAPA = 'onda8/etapa22';
const SPEC = path.join(L.RAIZ, 'docs', 'api', 'openapi.json');
const TAREFAS = path.join(L.RAIZ, 'docs', 'guia', 'tarefas');
const ANTES = 'dec6554'; // a 1.74.0 (a especificação de antes da 2.8; a 2.2 a 2.7 não a mudam)
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const DA_MATERIA = /todos os componentes dela|cada componente da turma/i;
/* os guias em que "componente" é o da regra de média (a prova, o trabalho) e do que ela calcula */
const DA_REGRA = ['configurar-a-regra-de-media.md', 'encerrar-uma-disciplina.md', 'ler-o-boletim.md', 'relatorios-c-desempenho.md'];

/** Os nomes da especificação: cada caminho com o método, e cada nome de campo (as chaves dos objetos de "properties"). */
function nomes(spec) {
  const out = new Set();
  for (const [p, ms] of Object.entries(spec.paths || {})) for (const m of Object.keys(ms)) out.add(`rota ${m.toUpperCase()} ${p}`);
  const andar = (o) => {
    if (!o || typeof o !== 'object') return;
    if (o.properties && typeof o.properties === 'object') for (const k of Object.keys(o.properties)) out.add(`campo ${k}`);
    for (const v of Object.values(o)) andar(v);
  };
  andar(spec);
  return out;
}
async function especificacao(p) {
  return p.evaluate(async (u) => (await fetch(u, { credentials: 'same-origin' })).text(), L.BASE + 'painel/integracoes/especificacao');
}

(async () => {
  const { navegador } = await L.abrir();
  const shaSpec = sha(SPEC);
  try {
    const ctx = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' });
    const p = await L.pagina(ctx);
    await p.goto(L.BASE + 'painel/login/acesso');
    await p.fill('input[name="usuario"]', 'administrador');
    await p.fill('input[name="senha"]', process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123');
    await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);

    /* 1. a especificação servida */
    const servida = await especificacao(p);
    L.ok(!DA_MATERIA.test(servida) && /todas as disciplinas dela/.test(servida) && /cada disciplina da turma/.test(servida),
      'a especificação servida diz "todas as disciplinas dela" e "cada disciplina da turma" (sem "componente" para a matéria)');

    /* 2. os nomes não mudam */
    const antes = nomes(JSON.parse(execFileSync('git', ['show', `${ANTES}:docs/api/openapi.json`], { cwd: L.RAIZ, encoding: 'utf8', maxBuffer: 1 << 26 })));
    const agora = nomes(JSON.parse(fs.readFileSync(SPEC, 'utf8')));
    const sumiram = [...antes].filter((n) => !agora.has(n));
    L.ok(antes.size > 50 && sumiram.length === 0 && /"componentes"\s*:\s*4/.test(fs.readFileSync(SPEC, 'utf8')),
      `os ${antes.size} nomes da especificação de antes (rotas, métodos e campos) continuam; o campo "componentes" da turma, também${sumiram.length ? ' — sumiram: ' + sumiram.slice(0, 5).join(', ') : ''}`);

    /* 3. o guia, na Ajuda, e os guias */
    await p.goto(L.BASE + 'painel/ajuda/guia/conhecer-com-a-demonstracao');
    const guia = await p.evaluate(() => document.body.innerText);
    await L.print(p, ETAPA, 'guia-conhecer-com-a-demonstracao');
    const comComponente = fs.readdirSync(TAREFAS).filter((f) => f.endsWith('.md') && /componente/i.test(fs.readFileSync(path.join(TAREFAS, f), 'utf8')));
    const fora = comComponente.filter((f) => !DA_REGRA.includes(f));
    L.ok(/as etapas de uma disciplina/.test(guia) && /os quatro módulos como disciplinas/.test(guia) && !/componente/i.test(guia) && fora.length === 0,
      `o guia na Ajuda: "as etapas de uma disciplina", "os quatro módulos como disciplinas"; "componente" só nos guias da regra de média (${comComponente.join(', ')})${fora.length ? ' — fora: ' + fora.join(', ') : ''}`);

    /* 4. o registro das mudanças */
    require('./_escola').sql("DELETE FROM adms_instalacao_estado WHERE chave = 'novidades.desde'"); // D-124 (1.102.1): as Novidades com o histórico, como numa instalação que já existia (a nova começa do zero)
    await p.goto(L.BASE + 'painel/novidades/listar');
    L.ok(/"Disciplina" nos guias e na documentação da API/.test(await p.evaluate(() => document.body.innerText)), 'o registro das mudanças diz "Disciplina" nos guias e na documentação da API');

    /* sabotagem: o resumo antigo */
    const orig = fs.readFileSync(SPEC, 'utf8');
    const trecho = '(ele entra em todas as disciplinas dela)';
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let s = null;
    try {
      fs.writeFileSync(SPEC, orig.replace(trecho, '(ele entra em todos os componentes dela)'));
      s = await especificacao(p);
    } finally {
      fs.writeFileSync(SPEC, orig);
    }
    L.ok(s !== null && DA_MATERIA.test(s), 'sabotagem (a especificação com o resumo antigo): a prova reprova — "todos os componentes dela" voltou à especificação servida');
    L.ok(sha(SPEC) === shaSpec, 'o arquivo sabotado voltou igual (sha256)');
    await ctx.close();
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(SPEC) !== shaSpec) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
