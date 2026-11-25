/*
 * ONDA 2, ETAPA 8 — A PROVA FINAL DO ZERO, de ponta a ponta, pelo navegador e pela API:
 *   1. a jornada da Etapa 4 (jornada4.js, que recria o banco de prova vazio): instalar, conhecer com a demonstração,
 *      trocar para os dados da instituição, percorrer as 8 fases pelo "Faça comigo";
 *   2. sobre a instituição que as fases criaram: importar a planilha (3 alunos, já vinculados ao curso das fases);
 *      reimportar não duplica;
 *   3. criar uma chave pela tela e, pela API, um aluno e a matrícula dele;
 *   4. atualizar: a etapa de banco da versão nova tirada do registro, o aviso, a tela de atualização, a versão em dia;
 *   5. sabotagem: sem a conferência do aluno que já existe, reimportar duplicaria — a prova reprova.
 *
 *   node tests/tabler/onda2/jornada8.js     (DESTRÓI e recria o banco de prova, como a jornada4)
 */
const fs = require('fs');
const path = require('path');
const { execFileSync, spawnSync } = require('child_process');
const L = require('../lib');

const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste$/.test(BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
const MYSQL = process.env.VCOSTAEDU_MYSQL || 'C:/xampp/mysql/bin/mysql.exe';
const sql = (q) => execFileSync(MYSQL, ['-u', 'root', '-N', '--default-character-set=utf8mb4', BANCO, '-e', q]).toString().replace(/\r/g, '').trim();
const um = (q) => +sql(q);
const ETAPA = 'onda2/etapa8';
const SENHA = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const ANO = new Date().getFullYear();
const MODELO = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'importacao', 'AdmsImportacaoAlunos.php');
const ULTIMA = fs.readdirSync(path.join(L.RAIZ, 'database', 'migrations')).filter((f) => /^\d{4}_.*\.sql$/.test(f)).sort().pop();

async function enviar(p, nome, buffer) {
  await p.goto(L.BASE + 'painel/importacao-alunos/listar');
  await p.setInputFiles('#arquivoImportacao', { name: nome, mimeType: 'text/csv', buffer });
  await Promise.all([p.waitForNavigation(), p.click('#enviarImportacao')]);
  const m = /importacao-alunos\/conferir\/(\d+)/.exec(p.url());
  return m ? +m[1] : 0;
}
const contadores = (p) => p.$$eval('[data-contagem]', (es) => Object.fromEntries(es.map((e) => [e.dataset.contagem, +e.textContent])));
async function api(metodo, rota, chave, corpo) {
  const r = await fetch(L.BASE + 'api/v1' + rota, { method: metodo, headers: { Authorization: 'Bearer ' + chave, 'Content-Type': 'application/json' }, body: corpo ? JSON.stringify(corpo) : undefined });
  return { status: r.status, json: await r.json().catch(() => null) };
}

(async () => {
  console.log('\n== Onda 2, Etapa 8 (a prova final do zero)');
  /* 1. a jornada da Etapa 4 */
  const j4 = spawnSync(process.execPath, [path.join(__dirname, 'jornada4.js')], { encoding: 'utf8', env: process.env, timeout: 1200000 });
  const resumo4 = (j4.stdout.match(/onda 2, etapa 4: .*/) || [''])[0];
  L.ok(j4.status === 0, `instalar, conhecer com a demonstração, trocar para os dados da instituição e as 8 fases (a jornada da Etapa 4: ${resumo4})`);
  if (j4.status !== 0) {
    console.log(j4.stdout.split('\n').filter((l) => /FALHA/.test(l)).join('\n'));
    process.exit(L.resumo('onda 2, etapa 8') ? 0 : 1);
  }

  const { navegador } = await L.abrir();
  try {
    const ctx = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' });
    const p = await L.pagina(ctx);
    await p.goto(L.BASE + 'painel/login/acesso');
    await p.fill('input[name="usuario"]', 'administrador');
    await p.fill('input[name="senha"]', SENHA);
    await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);

    /* 2. a planilha, sobre a instituição das fases */
    const curso = sql('SELECT nome FROM rm_cursos ORDER BY id LIMIT 1');
    const antes = { alunos: um('SELECT COUNT(*) FROM col_usuarios WHERE col_niveis_acesso_id = 1'), vinculos: um('SELECT COUNT(*) FROM rm_matriculas_curso') };
    const csv = Buffer.from(['RA;Nome completo;Sexo;Data de nascimento;Curso;Grade;Ano',
      `940001;Aluna Final Exemplo Um;F;10/01/2001;${curso};1;${ANO}`,
      `940002;Aluno Final Exemplo Dois;M;11/02/2002;${curso};1;${ANO}`,
      `940003;Aluna Final Exemplo Três;Feminino;12/03/2003;${curso};1;${ANO}`].join('\r\n') + '\r\n', 'latin1');
    const lote = await enviar(p, 'final.csv', csv);
    let c = await contadores(p);
    L.ok(lote > 0 && c.total === 3 && c.prontas === 3, `a planilha conferida: 3 linhas prontas, já com o curso das fases (${curso})`);
    await p.selectOption('#formaIngresso', { index: 1 });
    await Promise.all([p.waitForNavigation({ timeout: 120000 }), p.click('#aplicarLote')]);
    const depois = { alunos: um('SELECT COUNT(*) FROM col_usuarios WHERE col_niveis_acesso_id = 1'), vinculos: um('SELECT COUNT(*) FROM rm_matriculas_curso') };
    L.ok(await p.isVisible('#loteAplicado') && depois.alunos === antes.alunos + 3 && depois.vinculos === antes.vinculos + 3, `importada: alunos ${antes.alunos} → ${depois.alunos}, vínculos ${antes.vinculos} → ${depois.vinculos}`);
    await L.print(p, ETAPA, 'importacao-final');
    await enviar(p, 'final.csv', csv);
    c = await contadores(p);
    L.ok(c.puladas === 3 && c.prontas === 0 && um('SELECT COUNT(*) FROM col_usuarios WHERE col_niveis_acesso_id = 1') === depois.alunos, 'reimportar a mesma planilha: os 3 pulados, nada duplica');

    /* 3. a API */
    await p.goto(L.BASE + 'painel/integracoes/chaves');
    await p.fill('#nomeChave', 'Prova final');
    /* Onda 3, Etapa 9: o limite por assunto (o padrão é nada); a jornada grava alunos e matrículas */
    await p.check('input[name="permissao[alunos]"][value="gravar"]');
    await p.check('input[name="permissao[matriculas]"][value="gravar"]');
    await Promise.all([p.waitForNavigation(), p.click('#criarChave')]);
    const chave = await p.$eval('#textoChaveNova', (e) => e.textContent.trim());
    const cursoId = um('SELECT id FROM rm_cursos ORDER BY id LIMIT 1');
    const forma = um('SELECT id FROM rm_matricula_tipos WHERE situacao = 1 ORDER BY id LIMIT 1');
    const a = await api('POST', '/alunos', chave, { ra: '940010', nome: 'Aluno Via API Final Exemplo', sexo: 'masculino', nascimento: '2000-05-20' });
    const m = await api('POST', '/matriculas', chave, { ra: '940010', curso_id: cursoId, ano: ANO, forma_ingresso_id: forma, data_ingresso: `${ANO}-03-01` });
    const v = await api('GET', '/alunos/940010', chave);
    L.ok(a.status === 201 && m.status === 201 && v.status === 200 && v.json.dados.nome === 'Aluno Via API Final Exemplo' && um("SELECT COUNT(*) FROM rm_matriculas_curso mc JOIN col_usuarios u ON u.id = mc.usuario_id WHERE u.rm = '940010'") === 1,
      'pela API: o aluno criado, a matrícula no curso das fases, e a consulta');

    /* 4. atualizar */
    const versao = sql(`SELECT numero FROM adms_versoes WHERE situacao = 1`);
    sql(`DELETE FROM adms_migrations WHERE arquivo = '${ULTIMA}'`);
    await p.goto(L.BASE + 'painel/home/index');
    const faixa = await p.isVisible('#avisoAtualizacao');
    await Promise.all([p.waitForNavigation(), p.click('#irParaAtualizacao')]);
    const pend = await p.$$eval('#listaPendentes li', (l) => l.map((x) => x.textContent.trim()));
    await p.check('#fizBackup');
    await p.click('#aplicarAtualizacao'); // Onda 3, Etapa 11: a janela de progresso, uma etapa por chamada
    await p.waitForSelector('#progressoAtualizacao[data-situacao="concluida"], #progressoAtualizacao[data-situacao="falhou"]', { timeout: 300000 });
    await p.goto(L.BASE + 'painel/atualizar/index');
    L.ok(faixa && pend.length === 1 && pend[0] === ULTIMA && await p.isVisible('#atualizacaoOk') && um(`SELECT COUNT(*) FROM adms_migrations WHERE arquivo = '${ULTIMA}'`) === 1,
      `atualizar: com a etapa ${ULTIMA} pendente, o aviso leva à tela, que a aplica`);
    await L.print(p, ETAPA, 'atualizacao-final');
    await p.goto(L.BASE + 'painel/versao/index');
    L.ok((await p.getAttribute('#situacaoAtualizacao', 'data-em-dia')) === '1' && (await p.textContent('#versaoInstalada')).trim() === versao, `a versão ${versao}, em dia`);
    await L.print(p, ETAPA, 'versao-final');

    /* 5. sabotagem */
    const orig = fs.readFileSync(MODELO, 'utf8');
    let reprovou = false;
    try {
      fs.writeFileSync(MODELO, orig.replace('$pulada = true;', '$pulada = false;'));
      await enviar(p, 'final.csv', csv);
      c = await contadores(p);
      reprovou = !(c.puladas === 3 && c.prontas === 0);
    } finally {
      fs.writeFileSync(MODELO, orig);
    }
    L.ok(reprovou && fs.readFileSync(MODELO, 'utf8') === orig, 'sabotagem (sem a conferência do aluno que já existe, reimportar duplicaria): a prova reprova o defeito; o modelo voltou');
    await ctx.close();
  } catch (e) {
    L.ok(false, 'erro na prova: ' + e.message.split('\n')[0]);
  } finally {
    await navegador.close();
    process.exit(L.resumo('onda 2, etapa 8') ? 0 : 1);
  }
})();
