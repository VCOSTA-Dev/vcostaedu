/*
 * A MEDIÇÃO DAS TELAS (Onda 6, Etapa 1): dez telas de uso comum, no painel e no portal, com a demonstração do ensino
 * superior. Para cada uma: o tempo do pedido (a mediana de 5, sem desenhar a página) e quantas conexões ao banco o
 * pedido abriu (o contador "Connections" do próprio servidor de banco, antes e depois; a leitura do contador abre uma
 * conexão, que é descontada).
 *
 *   node tests/tabler/onda6/medir_telas.js <rótulo>     grava saida/onda6-telas-<rótulo>.json e imprime o quadro
 *   node tests/tabler/onda6/medir_telas.js comparar <antes> <depois>
 *
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE; credenciais da demonstração em tests/tabler/saida/credenciais.json.
 */
const { execFileSync } = require('child_process');
const fs = require('fs');
const path = require('path');
const L = require('../lib');

const SAIDA = path.join(__dirname, '..', 'saida');
const arq = (r) => path.join(SAIDA, `onda6-telas-${r}.json`);

if (process.argv[2] === 'comparar') {
  const a = JSON.parse(fs.readFileSync(arq(process.argv[3]), 'utf8'));
  const d = JSON.parse(fs.readFileSync(arq(process.argv[4]), 'utf8'));
  console.log('| tela | antes (ms) | depois (ms) | conexões antes | conexões depois |');
  console.log('|---|---|---|---|---|');
  let ta = 0, td = 0;
  for (const t of a.telas) {
    const x = d.telas.find((y) => y.nome === t.nome) || {};
    ta += t.ms; td += x.ms || 0;
    console.log(`| ${t.nome} | ${t.ms} | ${x.ms} | ${t.conexoes} | ${x.conexoes} |`);
  }
  console.log(`| **total** | ${ta} | ${td} | | |`);
  process.exit(0);
}

const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste$/.test(BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
const MYSQL = process.env.VCOSTAEDU_MYSQL || 'C:/xampp/mysql/bin/mysql.exe';
const sql = (q) => execFileSync(MYSQL, ['-u', 'root', '-N', BANCO, '-e', q]).toString().trim();
const conexoes = () => +sql("SHOW GLOBAL STATUS LIKE 'Connections'").split(/\s+/)[1];
const cred = JSON.parse(fs.readFileSync(path.join(SAIDA, 'credenciais.json'), 'utf8'));
const rotulo = process.argv[2] || 'medida';

async function entrar(ctx, app, usuario, senha) {
  const p = await ctx.newPage();
  await p.goto(L.BASE + app + '/login/acesso');
  await p.fill('input[name="usuario"]', usuario);
  await p.fill('input[name="senha"]', senha);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
  return p;
}

(async () => {
  const curso = sql("SELECT id FROM rm_cursos WHERE nome LIKE 'P%gradua%o em Gest%o de Projetos'");
  const oferta = sql(`SELECT id FROM rm_professores_materias WHERE curso_id = ${curso} AND materia LIKE 'Lideran%a de Equipes'`);
  const ativ = sql(`SELECT id FROM rm_disciplina_atividade WHERE rm_prof_mat_id = ${oferta} ORDER BY data_inicial, id LIMIT 1`);
  const aluno = sql("SELECT id FROM col_usuarios WHERE rm = '260104'");
  const grade = sql(`SELECT id FROM rm_cursos_grades WHERE curso_id = ${curso} LIMIT 1`);
  const telas = [
    ['secretaria', 'Página Inicial', 'painel/home/index'],
    ['secretaria', 'Alunos', 'painel/academicos/listar'],
    ['secretaria', 'Ficha do aluno', `painel/academicos/visualizar/${aluno}`],
    ['secretaria', 'Todas as disciplinas', 'painel/disciplinas/listar'],
    ['secretaria', 'A grade', `painel/matriz/visualizar/${grade}`],
    ['secretaria', 'Relatórios', 'painel/relatorios/listar'],
    ['professor', 'As notas de uma atividade', `painel/atividades/visualizar/${ativ}`],
    ['professor', 'Notas e resultado', `painel/medias/final/${oferta}`],
    ['aluno', 'Portal: boletim', 'portal/home/boletim-aluno'],
    ['aluno', 'Portal: notas das atividades', 'portal/home/notas-atividades'],
  ];
  const { navegador, contexto } = await L.abrir();
  const ctx = { secretaria: await entrar(contexto, 'painel', 'secretaria.demo', cred.painel['secretaria.demo']) };
  const ctxProf = await navegador.newContext(); ctx.professor = await entrar(ctxProf, 'painel', 'prof.laura.demo', cred.painel['prof.laura.demo']);
  const ctxAluno = await navegador.newContext(); ctx.aluno = await entrar(ctxAluno, 'portal', '260104', cred.portal['260104']);
  const res = [];
  for (const [quem, nome, rota] of telas) {
    const pg = ctx[quem];
    await pg.request.get(L.BASE + rota); // aquece
    const tempos = [];
    let conx = Infinity;
    for (let i = 0; i < 5; i++) {
      const c0 = conexoes();
      const t0 = process.hrtime.bigint();
      const r = await pg.request.get(L.BASE + rota);
      const ms = Number(process.hrtime.bigint() - t0) / 1e6;
      const c1 = conexoes();
      if (r.status() !== 200 || /login\/acesso/.test(r.url())) throw new Error(`${nome}: ${r.status()} ${r.url()}`);
      tempos.push(ms); conx = Math.min(conx, c1 - c0 - 1); // o mínimo: o contador é do servidor inteiro, e outro processo pode abrir conexões no mesmo instante
    }
    tempos.sort((a, b) => a - b);
    res.push({ nome, rota, ms: Math.round(tempos[2]), conexoes: conx });
    console.log(`  ${nome.padEnd(30)} ${String(Math.round(tempos[2])).padStart(6)} ms   ${conx} conexão(ões) ao banco`);
  }
  await navegador.close();
  fs.writeFileSync(arq(rotulo), JSON.stringify({ rotulo, quando: new Date().toISOString(), telas: res }, null, 1));
  console.log(`total: ${res.reduce((s, x) => s + x.ms, 0)} ms; gravado em saida/onda6-telas-${rotulo}.json`);
})().catch((e) => { console.error('FALHA: ' + e.message); process.exit(1); });
