/*
 * PROVA DA CORREÇÃO DA ESCOLA, PARTE 5 — a demonstração do colégio (D-104; os achados D1, D2, D3 e C7 do teste pelo
 * navegador de 22/11). APAGA o banco de prova.
 *   1. a demonstração do colégio, montada pela tela;
 *   2. D1: a turma do 8º ano A, com os alunos e as nove disciplinas, cada uma com as notas do 1º ao 3º bimestre, e o 1º e o
 *      2º fechados — vista pela tela da turma;
 *   3. D2: cada responsável designado (e cada mãe da ficha financeira) tem o sobrenome do aluno;
 *   4. D3: a Matemática de cada turma da professora de demonstração na chamada, com aulas lançadas; em "O meu dia", o
 *      atalho "Chamada" leva à chamada; o cartão "Conheça o sistema" fala da chamada de uma aula;
 *   5. o aluno que entrou no meio do ano: a matrícula no início do 3º bimestre, as notas de transferência do 1º e do 2º
 *      bimestre (o "T" no boletim do portal) e as notas do 3º;
 *   6. a liberação: as etapas fechadas no portal, a média final e a situação final do ano ocultas;
 *   7. C7: as etapas batem com o guia (o 1º e o 2º fechados, o 3º aberto, com as notas);
 *   sabotagem: a montagem sem as notas de transferência — a prova acusa (o boletim sem o "T"); o arquivo volta igual.
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const L = require('../lib');
const X = require('./_escola');

const ETAPA = 'onda8/etapa44';
const CENARIO = path.join(L.RAIZ, 'database', 'ferramentas', 'demonstracao_escola.php');
const GUIA = path.join(L.RAIZ, 'docs', 'guia', 'tarefas', 'conhecer-com-a-demonstracao.md');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const { sql, um } = X;
const SENHA_ADM = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const ANO = 2026;
const MEIO = '265044';

/** O boletim do aluno no portal: as marcas "T" e "—" (antes da matrícula). */
async function boletim(navegador, ra, senha) {
  const a = await X.entrar(navegador, ra, senha, 'portal', 1366);
  await a.p.goto(L.BASE + 'portal/home/boletim-aluno');
  const r = await a.p.evaluate(() => ({ t: document.querySelectorAll('.vc-nota-transferencia').length, antes: document.querySelectorAll('.vc-antes-matricula').length }));
  await L.print(a.p, ETAPA, 'boletim-aluno-meio-do-ano');
  await a.ctx.close();
  return r;
}

(async () => {
  const { navegador } = await L.abrir();
  const shaCenario = sha(CENARIO);
  try {
    L.ok(X.montar('onda8-etapa44').status === 0, 'a demonstração do colégio montada pela tela, numa instalação nova');
    const c = X.cred();

    /* 2. D1 */
    const t8 = um(`SELECT t.id FROM esc_turmas t JOIN rm_cursos_turma_series s ON s.id = t.modulo_id WHERE t.ano = ${ANO} AND s.codnome LIKE '8%ano' AND t.nome = 'A'`);
    const alunos8 = um(`SELECT COUNT(*) FROM esc_turma_alunos WHERE turma_id = ${t8} AND saida IS NULL`);
    const ofertas8 = um(`SELECT COUNT(*) FROM esc_turma_ofertas WHERE turma_id = ${t8}`);
    const comNotas = um(`SELECT COUNT(DISTINCT a.rm_prof_mat_id, a.nomeAtividade) FROM rm_disciplina_atividade a JOIN esc_turma_ofertas x ON x.oferta_id = a.rm_prof_mat_id
      WHERE x.turma_id = ${t8} AND EXISTS (SELECT 1 FROM rm_disciplina_atividade_notas n WHERE n.atividade_id = a.id AND n.nota IS NOT NULL)`);
    const fechadas = um(`SELECT COUNT(*) FROM esc_fechamentos f JOIN esc_turma_ofertas x ON x.oferta_id = f.oferta_id JOIN esc_etapas e ON e.id = f.etapa_id
      WHERE x.turma_id = ${t8} AND f.situacao = 'vigente' AND e.ordem IN (1, 2)`);
    const sec = await X.entrar(navegador, 'secretaria.demo', c.painel['secretaria.demo'], 'painel', 1366);
    await sec.p.goto(L.BASE + `painel/turmas/ver/${t8}`);
    const tela8 = (await sec.p.textContent('body')).replace(/\s+/g, ' ');
    await L.print(sec.p, ETAPA, 'turma-8-ano');
    L.ok(t8 > 0 && alunos8 === 4 && ofertas8 === 9 && comNotas === 27 && fechadas === 18 && /8º ano/.test(tela8) && /Rita Exemplo Campos/.test(tela8),
      `D1: a turma do 8º ano A, com ${alunos8} alunos e ${ofertas8} disciplinas; ${comNotas} avaliações com nota (9 × 3 bimestres) e ${fechadas} etapas fechadas (9 × 2); a tela da turma mostra a série e os alunos`);

    /* 3. D2 */
    const pares = sql(`SELECT CONCAT(r.nome, '|', u.nome) FROM esc_responsavel_designacoes d JOIN esc_responsaveis r ON r.id = d.responsavel_id JOIN col_usuarios u ON u.id = d.aluno_id
      WHERE d.fim IS NULL AND d.ano = ${ANO}`).split('\n').filter(Boolean).map((l) => l.split('|'));
    const sobrenome = (n) => n.replace(/\s*\(.*\)$/, '').trim().split(/\s+/).pop();
    const ruins = pares.filter(([r, a]) => !a.split(/\s+/).slice(1).includes(sobrenome(r)));
    L.ok(pares.length >= 8 && ruins.length === 0, `D2: os ${pares.length} responsáveis designados em ${ANO} têm o sobrenome do aluno${ruins.length ? ' — fora: ' + ruins.map((x) => x.join(' → ')).join('; ') : ''}`);

    /* 4. D3 */
    const mats = sql(`SELECT CONCAT(o.id, '|', o.frequencia_fonte, '|', (SELECT COUNT(*) FROM rm_oferta_encontros e WHERE e.rm_prof_mat_id = o.id)) FROM rm_professores_materias o
      JOIN esc_turma_ofertas x ON x.oferta_id = o.id JOIN esc_turmas t ON t.id = x.turma_id JOIN rm_cursos cu ON cu.id = t.curso_id
      WHERE t.ano = ${ANO} AND o.materia LIKE 'Matem%tica' AND cu.nome LIKE 'Ensino Fundamental%'`).split('\n').filter(Boolean).map((l) => l.split('|'));
    const prof = await X.entrar(navegador, 'prof.laura.demo', c.painel['prof.laura.demo'], 'painel', 1366);
    await prof.p.goto(L.BASE + 'painel/home/index');
    const atalhos = await prof.p.$$eval('a.vc-dia-chamada', (l) => l.map((a) => a.getAttribute('href')));
    await L.print(prof.p, ETAPA, 'o-meu-dia-professora');
    let abriu = '';
    if (atalhos[0]) {
      await prof.p.goto(atalhos[0].startsWith('http') ? atalhos[0] : new URL(atalhos[0], L.BASE).href);
      abriu = (await prof.p.textContent('body')).replace(/\s+/g, ' ');
    }
    await prof.ctx.close();
    const adm = await X.entrar(navegador, 'administrador', SENHA_ADM, 'painel', 1366);
    await adm.p.goto(L.BASE + 'painel/home/index');
    const cartao = (await adm.p.textContent('#cartaoConheca').catch(() => '')).replace(/\s+/g, ' ');
    await adm.ctx.close();
    L.ok(mats.length === 4 && mats.every(([, f, n]) => f === 'chamada' && +n >= 1) && atalhos.length >= 4 && /Nova aula/.test(abriu) && /chamada de uma aula/.test(cartao),
      `D3: a Matemática das ${mats.length} turmas do fundamental na chamada (${mats.map(([, f, n]) => f + ', ' + n + ' aulas').join('; ')}); "O meu dia" com ${atalhos.length} atalhos de chamada, e o primeiro abre a chamada; o cartão fala da chamada de uma aula`);

    /* 5. o aluno que entrou no meio do ano */
    const ingresso = sql(`SELECT mc.data_ingresso FROM rm_matriculas_curso mc JOIN col_usuarios u ON u.id = mc.usuario_id WHERE u.rm = '${MEIO}' ORDER BY mc.id DESC LIMIT 1`);
    const inicio3 = sql(`SELECT inicio FROM esc_etapas WHERE ano = ${ANO} AND ordem = 3`);
    const transf = um(`SELECT COUNT(*) FROM esc_notas_transferencia n JOIN col_usuarios u ON u.id = n.aluno_id WHERE u.rm = '${MEIO}' AND n.nota IS NOT NULL`);
    const b = await boletim(navegador, MEIO, c.portal[MEIO]);
    L.ok(ingresso.slice(0, 10) === inicio3 && transf === 18 && b.t >= 18,
      `o aluno que entrou no meio do ano: a matrícula em ${ingresso.slice(0, 10)} (o início do 3º bimestre), ${transf} notas de transferência (9 disciplinas × 2 bimestres), ${b.t} marcadas "T" no boletim do portal`);

    /* 6. a liberação */
    const lib = sql(`SELECT GROUP_CONCAT(CONCAT(item, ':', etapa_id, '=', exibir) ORDER BY item, etapa_id) FROM esc_boletim_liberacao WHERE ano = ${ANO}`);
    const etapasFechadas = sql(`SELECT GROUP_CONCAT(id ORDER BY ordem) FROM esc_etapas WHERE ano = ${ANO} AND ordem <= 2`).split(',');
    const liberadas = etapasFechadas.every((id) => lib.includes(`etapa:${id}=1`));
    const finaisOcultos = !/media_final:0=1|situacao_final:0=1/.test(lib);
    L.ok(liberadas && finaisOcultos, `a liberação de ${ANO}: as etapas fechadas no portal e a média e a situação finais ocultas (${lib})`);

    /* 7. C7: as etapas e o guia */
    const guia = fs.readFileSync(GUIA, 'utf8');
    const abertas3 = um(`SELECT COUNT(*) FROM esc_fechamentos f JOIN esc_etapas e ON e.id = f.etapa_id WHERE e.ano = ${ANO} AND e.ordem >= 3 AND f.situacao = 'vigente'`);
    const notas3 = um(`SELECT COUNT(*) FROM rm_disciplina_atividade WHERE nomeAtividade LIKE 'Avalia%o do 3%bimestre'`);
    L.ok(/o 1º e o 2º bimestre fechados e o 3º aberto, com as notas/.test(guia) && abertas3 === 0 && notas3 > 0,
      `C7: o guia diz "o 1º e o 2º bimestre fechados e o 3º aberto, com as notas", e a demonstração é assim (${abertas3} etapas a partir do 3º fechadas; ${notas3} avaliações do 3º bimestre)`);
    await sec.ctx.close();

    /* sabotagem: a montagem sem as notas de transferência */
    const orig = fs.readFileSync(CENARIO, 'utf8');
    const trecho = "                if ($perfil !== 'meio_ano') {\n                    continue;\n                }\n                $aluno = self::um($c, 'SELECT id FROM col_usuarios WHERE rm = ?', [$ra], \"o aluno {$ra}\");\n                if ($c->um('SELECT COUNT(*) FROM esc_notas_transferencia";
    if (orig.split(trecho).length !== 2) { throw new Error('a sabotagem não achou o trecho'); }
    let sab = { t: -1 };
    try {
      fs.writeFileSync(CENARIO, orig.replace(trecho, trecho.replace("if ($perfil !== 'meio_ano') {", 'if (true) {')));
      const r = X.montar('onda8-etapa44-sabotagem');
      if (r.status === 0) { sab = await boletim(navegador, MEIO, X.cred().portal[MEIO]); }
    } finally {
      fs.writeFileSync(CENARIO, orig);
    }
    L.ok(sab.t === 0, `sabotagem (a montagem sem as notas de transferência): a prova reprova — o boletim do aluno sem o "T" (${sab.t})`);
    L.ok(sha(CENARIO) === shaCenario, 'o arquivo sabotado voltou igual (sha256)');
    L.ok(X.montar('onda8-etapa44-de-volta').status === 0, 'a demonstração do colégio montada de novo, sem a sabotagem');
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(CENARIO) !== shaCenario) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
