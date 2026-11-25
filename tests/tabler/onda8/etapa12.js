/*
 * PROVA DA ONDA 8, CORREÇÃO, PARTE 3.3 — as faltas na demonstração do colégio (D-78). APAGA o banco de prova.
 *   1. a demonstração do colégio, montada pela tela numa instalação nova;
 *   2. os alunos de 2026 têm faltas em cada bimestre lançado (0 a 2 por bimestre e componente), sempre as mesmas
 *      (pela matrícula, pelo componente e pela etapa); a Matemática do 6º ano A fica sem falta lançada (a da chamada);
 *   3. ninguém de 2026 fica abaixo da frequência mínima (75%) em componente nenhum;
 *   4. o boletim de um aluno mostra as faltas;
 *   5. a montagem repetida dá as mesmas faltas (a régua da escola as registra);
 *   sabotagem: a montagem sem as faltas do ano corrente — a prova acusa; o arquivo volta igual (sha256).
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { spawnSync, execFileSync } = require('child_process');
const L = require('../lib');

const ETAPA = 'onda8/etapa12';
const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste$/.test(BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
const SENHA = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const CENARIO = path.join(L.RAIZ, 'database', 'ferramentas', 'demonstracao_escola.php');

const sql = (q) => execFileSync('C:/xampp/mysql/bin/mysql.exe', ['-uroot', '--default-character-set=utf8mb4', '-N', '-B', BANCO, '-e', q], { encoding: 'utf8' }).split('\n').map((l) => l.trim()).filter(Boolean).join('\n');
const um = (q) => +(sql(q).split('\n')[0] || 0);
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const montar = (rotulo) => spawnSync(process.execPath, [path.join(__dirname, '..', 'onda6', 'medir_demo.js'), 'escola', rotulo], { encoding: 'utf8', maxBuffer: 1 << 24 });
/* as faltas por etapa dos alunos de 2026 */
const faltas2026 = () => sql(`SELECT f.oferta_id, f.etapa_id, f.rm, f.faltas FROM esc_faltas_etapa f JOIN rm_professores_materias o ON o.id = f.oferta_id
  WHERE o.ano = 2026 AND f.rm LIKE '265%' ORDER BY f.oferta_id, f.etapa_id, f.rm`);

(async () => {
  const { navegador } = await L.abrir();
  const shaCen = sha(CENARIO);
  try {
    const r = montar('onda8-etapa12');
    L.ok(r.status === 0, 'a demonstração do colégio montada pela tela, numa instalação nova');

    /* 2. as faltas de 2026 */
    const linhas = faltas2026().split('\n').filter(Boolean).map((l) => l.split('\t').map(Number));
    const total = linhas.reduce((s, x) => s + x[3], 0);
    const foraDaFaixa = linhas.filter((x) => x[3] < 0 || x[3] > 2).length;
    const comFalta = new Set(linhas.filter((x) => x[3] > 0).map((x) => x[2])).size;
    const mat6A = um(`SELECT COUNT(*) FROM esc_faltas_etapa f JOIN esc_turma_ofertas x ON x.oferta_id = f.oferta_id JOIN esc_turmas t ON t.id = x.turma_id
      JOIN rm_professores_materias o ON o.id = f.oferta_id WHERE t.ano = 2026 AND t.nome = 'A' AND o.codtur LIKE '6%' AND o.materia LIKE 'Matem_tica' AND f.faltas > 0`);
    L.ok(linhas.length > 100 && total > 0 && foraDaFaixa === 0 && comFalta >= 15 && mat6A === 0,
      `os alunos de 2026 têm faltas: ${total} falta(s) em ${linhas.length} lançamento(s) de etapa, todos de 0 a 2; ${comFalta} aluno(s) com falta; a Matemática do 6º ano A sem falta lançada`);

    /* 3. ninguém abaixo do mínimo */
    const abaixo = um(`SELECT COUNT(*) FROM (SELECT f.oferta_id, f.rm, SUM(f.faltas) AS n, o.ch_considerada AS ch FROM esc_faltas_etapa f JOIN rm_professores_materias o ON o.id = f.oferta_id
      WHERE o.ano = 2026 AND f.rm LIKE '265%' GROUP BY f.oferta_id, f.rm, o.ch_considerada) x WHERE x.ch > 0 AND x.n / x.ch > 0.25`);
    const pior = sql(`SELECT ROUND(MAX(x.n / x.ch) * 100, 1) FROM (SELECT f.oferta_id, f.rm, SUM(f.faltas) AS n, o.ch_considerada AS ch FROM esc_faltas_etapa f JOIN rm_professores_materias o ON o.id = f.oferta_id
      WHERE o.ano = 2026 AND f.rm LIKE '265%' GROUP BY f.oferta_id, f.rm, o.ch_considerada) x WHERE x.ch > 0`);
    L.ok(abaixo === 0, `ninguém de 2026 abaixo da frequência mínima (o pior caso: ${pior}% de faltas na carga do componente; o limite é 25%)`);

    /* 4. o boletim mostra as faltas */
    const ra = sql("SELECT f.rm FROM esc_faltas_etapa f WHERE f.rm LIKE '265%' AND f.faltas > 0 ORDER BY f.rm LIMIT 1");
    const ctx = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' });
    const p = await L.pagina(ctx);
    await p.goto(L.BASE + 'painel/login/acesso');
    await p.fill('input[name="usuario"]', 'administrador');
    await p.fill('input[name="senha"]', SENHA);
    await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
    await p.goto(L.BASE + `painel/academicos/boletim/${ra}`);
    const celulas = await p.$$eval('table tbody tr', (l) => l.map((t) => [...t.cells].map((c) => c.textContent.trim())));
    await L.print(p, ETAPA, 'boletim-com-faltas');
    await ctx.close();
    const somaBol = celulas.length;
    L.ok(somaBol > 0 && celulas.some((c) => c.some((x) => /^[1-9]\d*$/.test(x))), `o boletim do aluno ${ra} mostra as faltas (${somaBol} linha(s) de componente)`);

    /* 5. a mesma montagem, as mesmas faltas */
    const antes = faltas2026();
    const r2 = montar('onda8-etapa12-de-novo');
    L.ok(r2.status === 0 && faltas2026() === antes, 'montada de novo, a demonstração tem as mesmas faltas (determinísticas)');

    /* sabotagem: sem as faltas do ano corrente */
    const orig = fs.readFileSync(CENARIO, 'utf8');
    const trecho = "if ($ano === self::ANO && !($k === '2026-6A' && $materia === 'Matemática')";
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let totalSab = -1;
    try {
      fs.writeFileSync(CENARIO, orig.replace(trecho, "if (false && $ano === self::ANO && !($k === '2026-6A' && $materia === 'Matemática')"));
      if (montar('onda8-etapa12-sabotagem').status === 0) {
        totalSab = um("SELECT IFNULL(SUM(f.faltas), 0) FROM esc_faltas_etapa f JOIN rm_professores_materias o ON o.id = f.oferta_id WHERE o.ano = 2026 AND f.rm LIKE '265%'");
      }
    } finally {
      fs.writeFileSync(CENARIO, orig);
    }
    L.ok(totalSab === 0, `sabotagem (a montagem sem as faltas do ano corrente): a prova reprova — ${totalSab} falta(s) em 2026`);
    L.ok(sha(CENARIO) === shaCen, 'o arquivo sabotado voltou igual (sha256)');
    L.ok(montar('onda8-etapa12-de-volta').status === 0, 'a demonstração do colégio montada de novo, sem a sabotagem');
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(CENARIO) !== shaCen) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
