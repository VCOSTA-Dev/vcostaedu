/*
 * PROVA DA 1.103.0, C9 — duas migrations com o mesmo número (D-132). Usa o banco do ciclo (o ensino superior, instalado
 * do zero). Duas migrations de mentira, com o mesmo número da faixa das provas (9997), entram na pasta:
 *   1. a linha de comando (bin/migrar.php status) lista o número repetido e os dois arquivos, e sai com erro;
 *   2. aplicar pela linha de comando para antes de tudo, diz quais são, e nada é aplicado (o banco igual);
 *   3. a tela "Atualizar o sistema" para com a mesma mensagem, e nada é aplicado;
 *   4. sem a repetição (um arquivo só), o status volta ao normal;
 *   sabotagem: a conferência desligada — o lote segue e aplica a de mentira: a prova reprova; o arquivo volta igual.
 * Os arquivos de mentira e o registro deles saem no fim.
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { spawnSync } = require('child_process');
const L = require('../lib');
const X = require('./_escola');

const ETAPA = 'onda8/etapa52';
const PHP = process.env.VCOSTAEDU_PHP || 'C:/xampp/php/php.exe';
const SENHA_ADM = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const MIG = path.join(L.RAIZ, 'database', 'migrations');
const A = path.join(MIG, '9997_prova_repetida_a.sql');
const B = path.join(MIG, '9997_prova_repetida_b.sql');
const RUNNER = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'banco', 'AdmsMigrations.php');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const { sql, um } = X;
const migrar = (...a) => spawnSync(PHP, [path.join(L.RAIZ, 'bin', 'migrar.php'), ...a], { encoding: 'utf8', cwd: L.RAIZ });
const aplicadas = () => um('SELECT COUNT(*) FROM adms_migrations');
const marca = () => um("SELECT COUNT(*) FROM adms_instalacao_estado WHERE chave = 'prova.etapa52'");

async function tela(navegador) {
  const s = await X.entrar(navegador, 'administrador', SENHA_ADM, 'painel', 1366);
  await s.p.goto(L.BASE + 'painel/atualizar/index');
  /* como na Onda 2, etapa 7: a caixa do backup e "Aplicar"; a tela anda em passos até concluir ou falhar */
  try {
    await s.p.check('#fizBackup');
    await s.p.click('#aplicarAtualizacao');
    await s.p.waitForSelector('#progressoAtualizacao[data-situacao="concluida"], #progressoAtualizacao[data-situacao="falhou"]', { timeout: 120000 });
  } catch (e) { /* a tela recusou antes do botão: o texto dela é o que conta */ }
  const t = ((await s.p.textContent('body').catch(() => '')) || '').replace(/\s+/g, ' ');
  await L.print(s.p, ETAPA, 'atualizar-repetida');
  await s.ctx.close();
  return t;
}

(async () => {
  const { navegador } = await L.abrir();
  const shaR = sha(RUNNER);
  const corpo = "-- prova da etapa 52: uma migration de mentira\nINSERT INTO adms_instalacao_estado (chave, valor) VALUES ('prova.etapa52', '1') ON DUPLICATE KEY UPDATE valor = '1';\n";
  try {
    L.ok(sql("SELECT valor FROM adms_instalacao_estado WHERE chave = 'instituicao.tipo'") === 'superior', 'o banco do ciclo: o ensino superior, instalado do zero');
    const antes = aplicadas();
    fs.writeFileSync(A, corpo);
    fs.writeFileSync(B, corpo);

    /* 1. o status */
    const st = migrar('status');
    L.ok(st.status !== 0 && /NUMERO REPETIDO/.test(st.stdout) && /9997: 9997_prova_repetida_a\.sql e 9997_prova_repetida_b\.sql/.test(st.stdout),
      `1. o status lista o número repetido e os dois arquivos, e sai com erro (${st.status})`);

    /* 2. aplicar pela linha de comando */
    const ap = migrar('aplicar', '--sim');
    const saida = (ap.stdout || '') + (ap.stderr || '');
    L.ok(ap.status !== 0 && /mesmo número: 9997 \(9997_prova_repetida_a\.sql e 9997_prova_repetida_b\.sql\)/.test(saida) && /Nada foi aplicado/.test(saida)
      && aplicadas() === antes && marca() === 0,
      `2. aplicar pela linha de comando para e diz quais são; nada aplicado (${aplicadas()} etapas no banco, como antes)`);

    /* 3. a tela */
    const t = await tela(navegador);
    L.ok(/mesmo número: 9997/.test(t) && aplicadas() === antes && marca() === 0, '3. a tela "Atualizar o sistema" para com a mesma mensagem; nada aplicado');

    /* 4. sem a repetição */
    fs.unlinkSync(B);
    const st2 = migrar('status');
    L.ok(!/NUMERO REPETIDO/.test(st2.stdout) && /9997_prova_repetida_a\.sql/.test(st2.stdout), '4. com um arquivo só, o status volta ao normal (a de mentira pendente)');
    fs.writeFileSync(B, corpo);

    /* sabotagem: a conferência desligada */
    const orig = fs.readFileSync(RUNNER, 'utf8');
    const trecho = '        $this->recusarRepetidas();\n';
    const trechoCr = trecho.replace('\n', '\r\n');
    const alvo = orig.includes(trechoCr) ? trechoCr : trecho;
    if (!orig.includes(alvo)) { throw new Error('a sabotagem não achou o trecho'); }
    let sab = null;
    try {
      fs.writeFileSync(RUNNER, orig.replace(alvo, ''));
      migrar('aplicar', '--sim');
      sab = marca();
    } finally {
      fs.writeFileSync(RUNNER, orig);
    }
    L.ok(sab === 1, 'sabotagem (a conferência desligada): o lote segue e aplica a de mentira — a prova reprova');
    L.ok(sha(RUNNER) === shaR, 'o arquivo sabotado voltou igual (sha256)');
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(RUNNER) !== shaR) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    for (const f of [A, B]) { try { fs.unlinkSync(f); } catch (e) { /* já saiu */ } }
    sql("DELETE FROM adms_migrations WHERE versao = '9997'");
    sql("DELETE FROM adms_instalacao_estado WHERE chave = 'prova.etapa52'");
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
