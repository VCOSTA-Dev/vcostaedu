/*
 * PROVA DA ONDA 6, ETAPA 3 — o banco e a base.
 *   1. ONLY_FULL_GROUP_BY no modo do sistema: a conexão do painel o tem; a lista da enturmação (Educacional → Enturmação)
 *      abre sem erro, com uma linha por aluno e a nota de que mostra a mais recente;
 *      sabotagem: a consulta antiga (GROUP BY com colunas soltas) volta — o modo a recusa e a lista fica vazia;
 *   2. a etapa do banco que só tem consulta é aceita pelo runner (o mesmo do comando e do instalador) e PELA TELA
 *      ("Atualizar o sistema", uma etapa posta na pasta das migrations e tirada no fim);
 *      sabotagem: o runner volta ao exec() — a etapa só de consulta é recusada (o erro 2014);
 *   3. a 0001 e a 0002 com o resumo antigo ficam aplicadas; um resumo qualquer, não;
 *      sabotagem: a lista dos resumos antigos esvaziada — a 0001 e a 0002 antigas viram "alteradas".
 *   A atualização de uma instalação antiga inteira (a da 1.37.0, com a 0001 e a 0002 do texto antigo) é a prova
 *   onda4/etapa6, que a bateria roda.
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE; a demonstração do ensino superior montada (o ciclo).
 */
const { execFileSync, spawnSync } = require('child_process');
const crypto = require('crypto');
const fs = require('fs');
const path = require('path');
const L = require('../lib');

const raiz = L.RAIZ;
const PHP = process.env.VCOSTAEDU_PHP || 'C:/xampp/php/php.exe';
const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
const sql = (q) => execFileSync('C:/xampp/mysql/bin/mysql.exe', ['-u', 'root', '-N', BANCO, '-e', q]).toString().trim();
const cred = JSON.parse(fs.readFileSync(path.join(__dirname, '..', 'saida', 'credenciais.json'), 'utf8'));
const SENHA = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const runner = (acao) => {
  const r = spawnSync(PHP, [path.join(__dirname, 'etapa3_runner.php'), acao], { encoding: 'utf8', cwd: raiz });
  const ult = (r.stdout || '').trim().split('\n').pop();
  try { return JSON.parse(ult); } catch (e) { return { ok: false, detalhe: (r.stderr || r.stdout || '').slice(-200) }; }
};
/** troca um trecho de um arquivo, roda fn e devolve o arquivo; confere o sha256 */
async function sabotar(arq, de, para, fn) {
  const orig = fs.readFileSync(arq, 'utf8');
  const antes = sha(arq);
  if (!orig.includes(de)) throw new Error('a sabotagem não achou o trecho em ' + path.basename(arq));
  try { fs.writeFileSync(arq, orig.replace(de, para)); return await fn(); } finally { fs.writeFileSync(arq, orig); L.ok(sha(arq) === antes, `${path.basename(arq)} voltou igual (sha256)`); }
}

(async () => {
  const { navegador, contexto } = await L.abrir();
  try {
    console.log('\n== Onda 6, Etapa 3: o banco e a base');
    const p = await L.pagina(contexto);
    await p.goto(L.BASE + 'painel/login/acesso');
    await p.fill('input[name="usuario"]', 'secretaria.demo'); await p.fill('input[name="senha"]', cred.painel['secretaria.demo']);
    await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);

    /* 1. ONLY_FULL_GROUP_BY */
    const modo = spawnSync(PHP, ['-r', "require 'database/ferramentas/comum.php'; $p = vcostaedu_pdo(); echo \\VcBanco::modoEmUso($p);"], { encoding: 'utf8', cwd: raiz }).stdout;
    L.ok(/ONLY_FULL_GROUP_BY/.test(modo), `o modo do banco fixado pelo sistema traz ONLY_FULL_GROUP_BY (${modo.split(',').length} itens)`);
    const ano = sql('SELECT MAX(ano) FROM rm_matriculas_ensino WHERE situacao = 1');
    const esperados = +sql(`SELECT COUNT(DISTINCT usuario_id) FROM rm_matriculas_ensino WHERE ano = ${ano} AND situacao = 1`);
    const lista = async () => {
      await p.goto(L.BASE + `painel/portal-alunos/listar/${ano}`);
      return { linhas: await p.$$eval('table.lista-dados tbody tr', (t) => t.filter((r) => r.querySelectorAll('td').length > 3).length), nota: !!(await p.$('#notaEnturmacaoRecente')) };
    };
    const l1 = await lista();
    await L.semErros(p, 'Enturmação');
    L.ok(esperados > 0 && l1.linhas === esperados && l1.nota, `a enturmação de ${ano}: ${l1.linhas} linha(s), uma por aluno (${esperados} alunos), com a nota "mostra a mais recente"`);
    const modelo = path.join(raiz, 'painel', 'app', 'adms', 'Models', 'portalAlunos', 'AdmsListaPortalAlunos.php');
    await sabotar(modelo, '" AND a.id = (SELECT MAX(a2.id) FROM rm_matriculas_ensino a2 WHERE a2.usuario_id=a.usuario_id AND a2.ano=a.ano AND a2.situacao=a.situacao" . $filtro(\'a2\') . ")"',
      '" GROUP BY a.usuario_id"', async () => {
        const l2 = await lista();
        L.ok(l2.linhas === 0, `sabotagem (a consulta agrupada com colunas soltas): o modo do banco a recusa e a lista fica vazia (${l2.linhas} linha(s))`);
      });
    /* a consulta que a bateria achou recusada no log do servidor (a frequência no total do ano, da escola) */
    const g1 = runner('agrupadas');
    L.ok(g1.ok, g1.detalhe);
    const calc = path.join(raiz, 'painel', 'app', 'adms', 'Models', 'notas', 'AdmsCalculoNotas.php');
    const antiga = execFileSync('git', ['-C', raiz, 'show', '4b54ea9:painel/app/adms/Models/notas/AdmsCalculoNotas.php']).toString();
    const ini = (s) => s.indexOf('            $ler->fullRead("SELECT ', s.indexOf("if ($conf['frequenciaBase'] === 'ano')"));
    const fim = (s) => s.indexOf('\n', s.indexOf(" GROUP BY ", ini(s))) + 1;
    const atual = fs.readFileSync(calc, 'utf8');
    await sabotar(calc, atual.slice(ini(atual), fim(atual)), antiga.slice(ini(antiga), fim(antiga)), async () => {
      const g2 = runner('agrupadas');
      L.ok(!g2.ok && /painel: recusada \(erro 1055/.test(g2.detalhe), 'sabotagem (a consulta de antes no painel): ' + g2.detalhe);
    });

    /* 2. a etapa só de consulta */
    const c1 = runner('consulta');
    L.ok(c1.ok, 'pelo runner do comando e do instalador, a etapa só de consulta: ' + c1.detalhe);
    const am = path.join(raiz, 'painel', 'app', 'adms', 'Models', 'banco', 'AdmsMigrations.php');
    await sabotar(am, '            $comando = $this->conexao->query($sql);', '            $this->conexao->exec($sql); $comando = false;', async () => {
      const c2 = runner('consulta');
      L.ok(!c2.ok, 'sabotagem (o runner com o exec() de antes): a etapa só de consulta é recusada — ' + c2.detalhe.slice(0, 110));
    });
    const extra = path.join(raiz, 'database', 'migrations', '9999_prova_so_consulta.sql');
    const adm = await contexto.browser().newContext();
    try {
      fs.writeFileSync(extra, '-- prova da Onda 6, Etapa 3: uma etapa só de consulta\nSELECT 1 AS um;\nSELECT COUNT(*) AS versoes FROM adms_versoes;\n');
      const a = await adm.newPage();
      await a.goto(L.BASE + 'painel/login/acesso');
      await a.fill('input[name="usuario"]', 'administrador'); await a.fill('input[name="senha"]', SENHA);
      await Promise.all([a.waitForNavigation(), a.click('[name="SendLogin"]')]);
      await a.goto(L.BASE + 'painel/atualizar/index');
      await a.check('#fizBackup');
      await a.click('#aplicarAtualizacao');
      await a.waitForSelector('#progressoAtualizacao[data-situacao="concluida"], #progressoAtualizacao[data-situacao="falhou"]', { timeout: 120000 });
      const sit = await a.getAttribute('#progressoAtualizacao', 'data-situacao');
      L.ok(sit === 'concluida' && sql("SELECT COUNT(*) FROM adms_migrations WHERE versao = '9999'") === '1', `pela tela ("Atualizar o sistema"), a etapa só de consulta: ${sit}`);
    } finally {
      try { fs.unlinkSync(extra); } catch (e) { /* já saiu */ }
      sql("DELETE FROM adms_migrations WHERE versao = '9999'");
      await adm.close();
    }
    L.ok(!fs.existsSync(extra) && sql("SELECT COUNT(*) FROM adms_migrations WHERE versao = '9999'") === '0', 'a etapa da prova saiu da pasta e do banco');

    /* 3. os resumos antigos da 0001 e da 0002 */
    const r1 = runner('resumos');
    L.ok(r1.ok, 'o resumo antigo: ' + r1.detalhe);
    await sabotar(am, "        '0001_estrutura.sql' => '17cfb855", "        '0001_estrutura.sql_' => '17cfb855", async () => {
      const r2 = runner('resumos');
      L.ok(!r2.ok && /0001 DIVERGENTE/.test(r2.detalhe), 'sabotagem (sem o resumo antigo da 0001): ' + r2.detalhe.slice(0, 110));
    });
  } catch (e) {
    L.ok(false, 'erro na prova: ' + e.message.split('\n')[0]);
  } finally {
    await navegador.close();
  }
  L.resumo('onda6/etapa3');
})();
