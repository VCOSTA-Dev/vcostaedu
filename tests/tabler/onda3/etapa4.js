/*
 * ONDA 3, ETAPA 4 — a demonstração em passos curtos e a tela final do instalador.
 *   A. o caminho da tela não executa programa: nenhum proc_open, shell_exec, exec, passthru, system, popen nem o cliente
 *      mysql nos arquivos dele (com sabotagem);
 *   B. a LINHA DE COMANDO, do zero: monta (database/ferramentas/demonstracao.php montar) e grava o retrato; remove
 *      (… limpar) e grava o retrato;
 *   C. a TELA, do zero: sem pedir a senha logo depois de instalar; a janela com os passos; um passo FORÇADO a falhar
 *      (o banco recusa a matéria) mostra o motivo e "Tentar de novo", que retoma; a página FECHADA no meio, e a tela
 *      mostra "interrompida no passo X" e Continuar; o tempo de cada passo; a régua IDÊNTICA à da linha de comando;
 *      depois a remoção pela tela, IDÊNTICA à remoção pela linha de comando;
 *   D. a senha volta a ser pedida quando a instalação tem dado próprio;
 *   E. a verificação do servidor para a tela: sem nomes de constante nem opção de linha de comando; HTTPS e a pasta dos
 *      arquivos em endereço local como aviso; a escrita testada; os vídeos que não vêm no pacote sem alerta (com sabotagem).
 *
 *   node tests/tabler/onda3/etapa4.js     (reinstala o banco de prova do zero, duas vezes)
 */
const fs = require('fs');
const path = require('path');
const { execFileSync, spawnSync } = require('child_process');
const L = require('../lib');

const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste$/.test(BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
const MYSQL = process.env.VCOSTAEDU_MYSQL || 'C:/xampp/mysql/bin/mysql.exe';
const PHP = process.env.VCOSTAEDU_PHP || 'C:/xampp/php/php.exe';
const sql = (q) => execFileSync(MYSQL, ['-u', 'root', '-N', '--default-character-set=utf8mb4', BANCO, '-e', q]).toString().replace(/\r/g, '').trim();
const um = (q) => +sql(q);
const ETAPA = 'onda3/etapa4';
const SENHA = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const T = path.join(L.RAIZ, 'tests', 'tabler');
const SAIDA = path.join(T, 'saida');
const RET_CLI = path.join(SAIDA, 'regua-onda3-etapa4-cli.json');
const RET_CLI_REMOVIDA = path.join(SAIDA, 'regua-onda3-etapa4-cli-removida.json');
const AMBIENTE = path.join(L.RAIZ, 'database', 'ferramentas', 'implantacao', 'verificar_ambiente.php');
const CAMINHO_DA_TELA = [
  'painel/app/adms/Controllers/Demonstracao.php',
  'painel/app/adms/Models/demonstracao/AdmsDemonstracaoMontagem.php',
  'database/ferramentas/demonstracao_passos.php',
  'painel/app/adms/Views/demonstracao/comecar.php',
  'painel/app/adms/Views/demonstracao/instituicao.php',
  'painel/app/adms/Views/include_tabler/_progresso.php',
  'assets/js/vcostaedu-progresso.js',
];
const PROGRAMA = /\b(proc_open|shell_exec|passthru|popen|pcntl_exec)\s*\(|(?<![\w>:$])(exec|system)\s*\(|`[^`]*`\s*;|mysql(\.exe)?['"\s]/;

const php = (args, entrada) => spawnSync(PHP, args, { encoding: 'utf8', cwd: L.RAIZ, input: entrada, env: process.env, maxBuffer: 1 << 26 });
function doZero() {
  let r = php([path.join(T, 'reset.php')]);
  if (r.status !== 0) { throw new Error('reset: ' + r.stderr); }
  r = spawnSync('node', [path.join(T, 'instalar.js'), 'normal'], { encoding: 'utf8', env: process.env, maxBuffer: 1 << 26 });
  if (r.status !== 0) { throw new Error('instalador: ' + (r.stdout || '').slice(-600)); }
}
function regua(modo, arq) {
  /* o caminho relativo à raiz (como o ciclo passa): a régua não toma "C:\…" por absoluto */
  if (modo === 'gravar' && fs.existsSync(arq)) { fs.unlinkSync(arq); }
  const r = php([path.join(T, 'regua.php'), modo, path.relative(L.RAIZ, arq).split(path.sep).join('/')]);
  const ok = r.status === 0 && !/Warning|Failed to open/.test(r.stdout + r.stderr) && (modo !== 'gravar' || fs.existsSync(arq));
  return { ok, saida: (r.stdout + r.stderr).trim().split('\n').slice(-12).join(' | ') };
}
async function entrar(navegador) {
  const ctx = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' });
  const p = await L.pagina(ctx);
  await p.goto(L.BASE + 'painel/login/acesso');
  await p.fill('input[name="usuario"]', 'administrador');
  await p.fill('input[name="senha"]', SENHA);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
  return { ctx, p };
}
/** As respostas dos passos (o tempo de cada um vem na última). */
function ouvir(p, guardar) {
  p.on('response', async (r) => {
    if (r.url().includes('demonstracao/montar') && r.request().method() === 'POST') {
      try { guardar(await r.json()); } catch (e) { /* a página fechou no meio */ }
    }
  });
}
const situacao = (p, id) => p.getAttribute('#' + id, 'data-situacao').catch(() => null);
const esperar = async (p, id, quais, ms = 300000) => {
  await p.waitForFunction(([i, q]) => q.includes((document.getElementById(i) || {}).getAttribute?.('data-situacao')), [id, quais], { timeout: ms });
  return situacao(p, id);
};

(async () => {
  const { navegador } = await L.abrir();
  try {
    console.log('\n== Onda 3, Etapa 4');

    /* A. o caminho da tela sem executar programa */
    const achados = CAMINHO_DA_TELA.filter((f) => PROGRAMA.test(fs.readFileSync(path.join(L.RAIZ, f), 'utf8').replace(/\/\*[\s\S]*?\*\/|^\s*\/\/.*$|^\s*\*.*$/gm, '')));
    L.ok(achados.length === 0, `o caminho da tela (${CAMINHO_DA_TELA.length} arquivos) não executa programa: nenhum proc_open, shell_exec, exec, passthru, system, popen nem o cliente mysql${achados.length ? ' — achado em ' + achados.join(', ') : ''}`);
    L.ok(!fs.readFileSync(path.join(L.RAIZ, CAMINHO_DA_TELA[0]), 'utf8').includes('AdmsLinhaDeComando'), 'a tela da demonstração não usa mais o ajudante que roda a linha de comando');
    const sabotado = fs.readFileSync(path.join(L.RAIZ, CAMINHO_DA_TELA[1]), 'utf8').replace('\\VcDemoLimpeza::rodar($this->pdo);', "shell_exec('mysql < seed_demo.sql');");
    L.ok(PROGRAMA.test(sabotado.replace(/\/\*[\s\S]*?\*\/|^\s*\/\/.*$|^\s*\*.*$/gm, '')), 'sabotagem (a limpeza pelo cliente mysql, por shell_exec): a varredura acha');

    /* B. a linha de comando, do zero */
    doZero();
    let t0 = Date.now();
    let r = php([path.join(L.RAIZ, 'database', 'ferramentas', 'demonstracao.php'), 'montar', L.BASE], `administrador\n${SENHA}\n`);
    const tempoCli = Math.round((Date.now() - t0) / 1000);
    L.ok(r.status === 0 && /OK\s*$/.test(r.stdout.trim()), `a linha de comando monta a demonstração (${tempoCli} s, uma requisição só)${r.status ? ' — ' + (r.stdout + r.stderr).slice(-300) : ''}`);
    let g = regua('gravar', RET_CLI);
    L.ok(g.ok, 'o retrato da demonstração montada pela linha de comando');
    r = php([path.join(L.RAIZ, 'database', 'ferramentas', 'demonstracao.php'), 'limpar']);
    L.ok(r.status === 0, 'a linha de comando remove a demonstração');
    g = regua('gravar', RET_CLI_REMOVIDA);
    L.ok(g.ok, 'o retrato da demonstração removida pela linha de comando');

    /* C. a tela, do zero */
    doZero();
    let { ctx, p } = await entrar(navegador);
    let respostas = [];
    ouvir(p, (j) => respostas.push(j));
    await p.goto(L.BASE + 'painel/demonstracao/comecar');
    L.ok(!(await p.$('#senhaDemonstracao')) && !/cerca de dois minutos/.test(await p.textContent('body')) && /btn-primary/.test(await p.getAttribute('#montarDemonstracao', 'class')),
      'logo depois de instalar: a senha não é pedida de novo, sem o "cerca de dois minutos", o botão em destaque');
    await L.semErros(p, 'como você quer começar');
    /* a falha forçada: o banco recusa a matéria (um gatilho de prova) — o passo "Cursos e matriz" para com o motivo */
    sql("DROP TRIGGER IF EXISTS prova_etapa4_materia; CREATE TRIGGER prova_etapa4_materia BEFORE INSERT ON rm_materias FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'prova da etapa 4'");
    try {
      await p.click('#montarDemonstracao');
      await p.waitForFunction(() => /Passo [2-9]/.test((document.querySelector('#progressoMontagem .vc-progresso-conta') || {}).textContent || ''), null, { timeout: 120000 });
      await L.print(p, ETAPA, 'janela-andando');
      const s1 = await esperar(p, 'progressoMontagem', ['falhou', 'concluida']);
      const falha = await p.$eval('#progressoMontagem .vc-progresso-falha', (e) => (e.hidden ? '' : e.textContent.replace(/\s+/g, ' ').trim()));
      L.ok(s1 === 'falhou' && /Parou no passo/.test(falha) && /Cursos e matriz/.test(falha) && /matéria/.test(falha) && await p.isVisible('.vc-progresso-tentar'),
        `um passo forçado a falhar: a janela para, diz o passo e o motivo ("${falha.slice(0, 110)}…") e oferece Tentar de novo`);
      await L.print(p, ETAPA, 'janela-falhou');
    } finally {
      sql('DROP TRIGGER IF EXISTS prova_etapa4_materia');
    }
    await p.click('.vc-progresso-tentar');
    await p.waitForFunction(() => { const m = /Passo (\d+)/.exec((document.querySelector('#progressoMontagem .vc-progresso-conta') || {}).textContent || ''); return m && +m[1] >= 12; }, null, { timeout: 200000 });
    const parouEm = +(await p.$eval('#progressoMontagem .vc-progresso-conta', (e) => /Passo (\d+)/.exec(e.textContent)[1]));
    L.ok(parouEm >= 12, `Tentar de novo retoma do passo que falhou e segue (chegou ao passo ${parouEm})`);
    await ctx.close(); // a página fechada no meio da montagem
    ({ ctx, p } = await entrar(navegador));
    ouvir(p, (j) => respostas.push(j));
    await p.goto(L.BASE + 'painel/demonstracao/comecar');
    const aviso = (await p.textContent('#montagemInterrompida').catch(() => '')).replace(/\s+/g, ' ');
    L.ok(/interrompida no passo \d+ de \d+/.test(aviso) && await p.isVisible('#continuarMontagem'), `a página fechada no meio: ao voltar, "${aviso.slice(0, 90)}…" e o botão Continuar`);
    await L.print(p, ETAPA, 'montagem-interrompida');
    await p.click('#continuarMontagem');
    const s2 = await esperar(p, 'progressoMontagem', ['falhou', 'concluida'], 400000);
    L.ok(s2 === 'concluida' && await p.isVisible('#progressoMontagem .vc-progresso-fim'), 'Continuar termina a montagem; a janela oferece Entrar na demonstração');
    await L.print(p, ETAPA, 'janela-concluida');
    const fim = respostas.filter((j) => j && j.concluida).pop() || {};
    const tempos = fim.tempos || {};
    const pior = Object.entries(tempos).sort((a, b) => b[1] - a[1])[0] || ['—', 0];
    L.ok(Object.keys(tempos).length === (fim.total || -1) && pior[1] < 30,
      `o tempo de cada passo (${Object.keys(tempos).length} passos): o pior, "${pior[0]}", ${pior[1]} s — bem abaixo de 30 s`);
    console.log('  tempos: ' + Object.entries(tempos).map(([k, v]) => `${k} ${v} s`).join(', '));
    fs.mkdirSync(path.join(SAIDA), { recursive: true });
    fs.writeFileSync(path.join(SAIDA, 'etapa4-tempos.json'), JSON.stringify(tempos, null, 1));
    L.ok(um("SELECT COUNT(*) FROM adms_instalacao_estado WHERE chave = 'demonstracao.montagem'") === 0 && sql("SELECT valor FROM adms_instalacao_estado WHERE chave = 'demonstracao.ativa'") === '1',
      'no fim, o estado da montagem sai do banco e a demonstração fica ligada');
    await Promise.all([p.waitForNavigation(), p.click('#progressoMontagem .vc-progresso-fim')]);
    L.ok(/home\/index/.test(p.url()) && await p.isVisible('#demonstracaoMontada'), 'Entrar na demonstração: a Página Inicial com a mensagem');
    await L.semErros(p, 'página inicial da demonstração');
    let c = regua('comparar', RET_CLI);
    L.ok(c.ok && /IDÊNTICA/.test(c.saida), `a régua da demonstração montada PELA TELA, com a falha e a interrupção no meio, é IDÊNTICA à da linha de comando (${c.saida.slice(0, 160)})`);

    /* a remoção pela tela */
    await p.goto(L.BASE + 'painel/demonstracao/instituicao');
    await p.fill('#confirmacao', 'COMEÇAR');
    await p.click('#removerDemonstracao');
    const s3 = await esperar(p, 'progressoRemocao', ['falhou', 'concluida'], 120000);
    L.ok(s3 === 'concluida' && um("SELECT COUNT(*) FROM col_usuarios WHERE rm BETWEEN 250000 AND 269999") === 0 && um("SELECT COUNT(*) FROM adms_instalacao_estado WHERE chave IN ('demonstracao.ativa', 'demonstracao.montagem', 'demonstracao.credenciais')") === 0
      && um("SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE() AND TRIGGER_NAME LIKE 'rm\\_historico%'") === 4,
      'a remoção pela tela, na mesma janela: os dados fictícios saem, a demonstração desliga, os gatilhos do histórico continuam');
    await L.print(p, ETAPA, 'remocao-concluida');
    await Promise.all([p.waitForNavigation(), p.click('#progressoRemocao .vc-progresso-fim')]);
    L.ok(/primeiros-passos\/index/.test(p.url()), 'o fim da remoção leva aos Primeiros passos');
    c = regua('comparar', RET_CLI_REMOVIDA);
    L.ok(c.ok && /IDÊNTICA/.test(c.saida), `a régua da demonstração removida PELA TELA é IDÊNTICA à da remoção pela linha de comando (${c.saida.slice(0, 160)})`);

    /* D. com dado próprio, a senha volta */
    sql("INSERT INTO rm_polos (nome, cidade, uf, situacao) VALUES ('Polo da prova', 'Cidade Exemplo', 'DF', 1)");
    try {
      await p.goto(L.BASE + 'painel/demonstracao/comecar');
      L.ok(await p.isVisible('#senhaDemonstracao'), 'com um dado próprio (um polo), a demonstração volta a pedir a senha');
      await p.fill('#senhaDemonstracao', 'senha errada de prova');
      await p.click('#montarDemonstracao');
      await p.waitForFunction(() => document.getElementById('progressoMontagem').getAttribute('data-situacao') === 'falhou', null, { timeout: 60000 });
      const recusa = (await p.textContent('#progressoMontagem .vc-progresso-falha-motivo')).trim();
      L.ok(/A senha não confere/.test(recusa) && await p.isVisible('#progressoMontagem .vc-progresso-fechar') && um("SELECT COUNT(*) FROM adms_instalacao_estado WHERE chave = 'demonstracao.montagem'") === 0,
        `a senha errada recusa a montagem ("${recusa}"), com o botão Fechar, e nada começa`);
      await L.print(p, ETAPA, 'senha-errada');
      await p.click('#progressoMontagem .vc-progresso-fechar');
      L.ok(await p.isHidden('#progressoMontagem'), 'Fechar fecha a janela');
    } finally { sql("DELETE FROM rm_polos WHERE nome = 'Polo da prova'"); }
    await ctx.close();

    /* E. a verificação do servidor para a tela */
    const amb = php([AMBIENTE, '--tela']).stdout;
    const tecnico = amb.match(/VERSAODEV|PROXIES_CONFIAVEIS|CAMINHO_BASE|REMOTE_ADDR|EXIGIR_HTTPS|PASTA_STORAGE|HOSTS_PERMITIDOS|ORIGENS_PERMITIDAS_IFRAME|COOKIE_PARTICIONADO|AMBIENTE =|--raiz-publica|php bin|\.php\b|session\.save_path|memory_limit/g) || [];
    L.ok(tecnico.length === 0, `a verificação para a tela, sem nome de constante nem opção de linha de comando${tecnico.length ? ' — achou: ' + [...new Set(tecnico)].join(', ') : ''}`);
    L.ok(/\[ALERTA\]\s+o sistema não exige HTTPS: normal em instalação local; resolva antes de publicar/.test(amb) && /\[ALERTA\]\s+a pasta dos arquivos privados está dentro da pasta publicada.*normal em instalação local; resolva antes de publicar/.test(amb) && !/\[FALHA\]/.test(amb),
      'em endereço local, o HTTPS desligado e a pasta dos arquivos dentro da publicada são AVISO ("normal em instalação local; resolva antes de publicar"), sem falha');
    L.ok(/\[OK\]\s+existe e aceita gravação/.test(amb) && /\[OK\]\s+historico\/emissoes\/ existe e aceita gravação/.test(amb) && /\[OK\]\s+historico\/assinaturas\/ existe e aceita gravação/.test(amb),
      'a escrita na pasta dos arquivos e nas duas de dentro, testada gravando e apagando um arquivo: OK');
    L.ok((/\[OK\]\s+os vídeos dos guias não vêm no pacote/.test(amb) || /\[OK\]\s+\d+ vídeo\(s\) listados no índice da Ajuda, todos na pasta/.test(amb)) && !/vídeo\(s\)[^\n]*\n?/.test(amb.split('\n').filter((l) => /ALERTA/.test(l)).join('\n')), 'os vídeos: sem nenhum (o pacote) ou todos na pasta (a gravação da Onda 5), nunca alerta');
    const cli = php([AMBIENTE]).stdout;
    L.ok(/EXIGIR_HTTPS = false/.test(cli) && /--raiz-publica/.test(cli), 'na linha de comando, o texto técnico continua (para quem implanta)');
    const fonte = fs.readFileSync(AMBIENTE, 'utf8');
    try {
      fs.writeFileSync(AMBIENTE, fonte.replace("$local = in_array($hostUrl, ['localhost', '::1'], true)", "$local = false && in_array($hostUrl, ['localhost', '::1'], true)"));
      const sab = php([AMBIENTE, '--tela']).stdout;
      L.ok(/\[FALHA\]\s+o sistema não exige HTTPS/.test(sab), 'sabotagem (sem reconhecer o endereço local): o HTTPS desligado volta a ser FALHA — a prova reprova');
    } finally { fs.writeFileSync(AMBIENTE, fonte); }
    L.ok(fs.readFileSync(AMBIENTE, 'utf8') === fonte, 'a ferramenta voltou');
    const sub = path.join(L.RAIZ, 'storage', 'historico', 'assinaturas');
    const sub2 = sub + '_prova';
    if (fs.existsSync(sub)) {
      fs.renameSync(sub, sub2);
      try {
        L.ok(/\[FALHA\]\s+falta a pasta historico\/assinaturas\/ dentro dela/.test(php([AMBIENTE, '--tela']).stdout), 'sem a pasta das assinaturas, a verificação reprova (a conferência é de verdade)');
      } finally { fs.renameSync(sub2, sub); }
    }
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message);
  } finally {
    try { sql('DROP TRIGGER IF EXISTS prova_etapa4_materia'); } catch (e) { /* o banco pode não existir */ }
    await navegador.close();
  }
  process.exit(L.resumo('onda3/etapa4') ? 0 : 1);
})();
