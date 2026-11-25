/*
 * PROVA DA ONDA 6, ETAPA 7 — o registro da instalação e a distribuição (D-59; docs/VERSAO_JSON.md).
 * O endereço do projeto é trocado, só na Config de prova, por um servidor falso da prova (o lado que recebe não existe
 * neste repositório); a Config volta igual no fim.
 *   A. a tela "Registrar a instalação": o que vai, exatamente, antes de enviar; nenhum dado de aluno nem de professor;
 *   B. o envio: o servidor falso recebe o MESMO conteúdo da tela (com o identificador aleatório);
 *   C. o endereço fora do ar: "pendente de envio", sem erro na tela; o reenvio periódico, pelos Avisos, envia quando
 *      volta;
 *   D. o que a pessoa escolheu mandar mudou (o nome da instituição): o reenvio espera a confirmação;
 *   E. cancelar o registro: o servidor falso é avisado; nada mais é enviado;
 *   F. o convite: o instalador o traz desmarcado; marcado, o painel convida até a pessoa abrir a tela;
 *   G. a versão nova que corrige falha de segurança, em destaque; os links do projeto só com o site no ar;
 *   H. o pacote: as notas da versão, o código de conferência e o versao.json;
 *   sabotagem: um nome de aluno no conteúdo — a prova reprova.
 */
const fs = require('fs');
const os = require('os');
const path = require('path');
const http = require('http');
const crypto = require('crypto');
const { execFileSync, spawnSync } = require('child_process');
const L = require('../lib');

const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste$/.test(BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
const MYSQL = process.env.VCOSTAEDU_MYSQL || 'C:/xampp/mysql/bin/mysql.exe';
const PHP = process.env.VCOSTAEDU_PHP || 'C:/xampp/php/php.exe';
const sql = (q) => execFileSync(MYSQL, ['-u', 'root', '-N', '--default-character-set=utf8mb4', BANCO], { input: Buffer.from(q, 'utf8') }).toString().replace(/\r/g, '').trim();
const ETAPA = 'onda6/etapa7';
const cred = JSON.parse(fs.readFileSync(path.join(__dirname, '..', 'saida', 'credenciais.json'), 'utf8'));
const CONFIG = path.join(L.RAIZ, 'painel', 'core', 'Config.php');
const MODELO = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'manutencao', 'AdmsRegistroInstalacao.php');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const estado = () => { try { return JSON.parse(sql("SELECT valor FROM adms_instalacao_estado WHERE chave = 'registro.instalacao'") || '{}'); } catch (e) { return {}; } };
const envelhecer = (campos) => { const e = estado(); Object.assign(e, campos); sql(`UPDATE adms_instalacao_estado SET valor = '${JSON.stringify(e).replace(/'/g, "''")}' WHERE chave = 'registro.instalacao'`); };

(async () => {
  const { navegador, contexto } = await L.abrir();
  const cfgOrig = fs.readFileSync(CONFIG, 'utf8');
  const shas = { [CONFIG]: sha(CONFIG), [MODELO]: sha(MODELO) };
  const recebidos = [];
  let versaoJson = null;
  const servidor = http.createServer((rq, rs) => {
    let corpo = '';
    rq.on('data', (c) => { corpo += c; });
    rq.on('end', () => {
      if (rq.url.startsWith('/versao.json')) { rs.writeHead(200, { 'Content-Type': 'application/json' }); rs.end(JSON.stringify(versaoJson)); return; }
      try { recebidos.push(JSON.parse(corpo)); } catch (e) { recebidos.push({ invalido: corpo }); }
      rs.writeHead(200, { 'Content-Type': 'application/json' });
      rs.end('{"ok":true}');
    });
  });
  await new Promise((ok) => servidor.listen(0, '127.0.0.1', ok));
  const base = `http://127.0.0.1:${servidor.address().port}`;
  const comEnderecos = (registro, versao) => cfgOrig.replace(/(\n\s*define\('EXIGIR_HTTPS',)/, `\n        define('URL_REGISTRO', '${registro}');\n        define('URL_VERSAO_NOVA', '${versao}');$1`);
  const p = await L.pagina(contexto);
  p.on('dialog', (d) => d.accept());
  await L.confirmarSozinho(p); // D-100: a confirmação é a janela do sistema
  try {
    console.log('\n== Onda 6, Etapa 7 — o registro da instalação e a distribuição');
    fs.writeFileSync(CONFIG, comEnderecos(`${base}/registro`, `${base}/versao.json`));
    sql("DELETE FROM adms_instalacao_estado WHERE chave IN ('registro.instalacao', 'versao_nova.consulta')");
    await p.goto(L.BASE + 'painel/login/acesso?registrar=1');
    await p.fill('input[name="usuario"]', 'administrador');
    await p.fill('input[name="senha"]', cred.painel.administrador);
    await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
    await p.goto(L.BASE + 'painel/home/index');
    const convidou = !!(await p.$('#conviteRegistro'));

    /* A. a tela: o que vai, antes de enviar */
    await p.goto(L.BASE + 'painel/registro-instalacao/index');
    await L.semErros(p, 'Registrar a instalação');
    await p.goto(L.BASE + 'painel/home/index');
    const convitePassou = !(await p.$('#conviteRegistro'));
    await p.goto(L.BASE + 'painel/registro-instalacao/index');
    const previa = JSON.parse(await p.textContent('#conteudoRegistro'));
    const sempre = ['identificador', 'versao', 'tipo_instituicao', 'pais', 'uf', 'modulos', 'contagens', 'aparecer_na_lista'];
    const alunos = sql('SELECT CONCAT_WS(\'|\', rm, nome, IFNULL(email, \'\')) FROM col_usuarios WHERE col_niveis_acesso_id = 1').split('\n').filter(Boolean).flatMap((l) => l.split('|')).filter((x) => x.length >= 4);
    const profs = sql("SELECT CONCAT_WS('|', nome, IFNULL(email, '')) FROM adms_usuarios").split('\n').filter(Boolean).flatMap((l) => l.split('|')).filter((x) => x.length >= 4);
    const semPessoa = (obj) => { const t = JSON.stringify(obj); return ![...alunos, ...profs].some((x) => t.includes(x)); };
    L.ok(convidou && convitePassou && Object.keys(previa).sort().join() === sempre.slice().sort().join() && previa.pais === 'BR' && typeof previa.contagens.alunos === 'number' && semPessoa(previa),
      `a tela mostra o que vai: ${Object.keys(previa).join(', ')} — sem o nome, o site e o e-mail (não marcados) e sem nenhum dado de aluno ou de professor; o convite marcado no instalador aparece até a tela ser aberta`);

    /* B. o envio, com o nome e o e-mail */
    await p.check('#registroNome');
    await p.fill('#registroEmail', 'ti.prova@exemplo.invalid');
    await Promise.all([p.waitForNavigation(), p.click('#atualizarPrevia')]);
    const previa2 = JSON.parse(await p.textContent('#conteudoRegistro'));
    await Promise.all([p.waitForNavigation(), p.click('#enviarRegistro')]);
    const r1 = recebidos.pop() || {};
    const igual = Object.keys(previa2).filter((k) => k !== 'identificador').every((k) => JSON.stringify(previa2[k]) === JSON.stringify(r1[k]));
    L.ok(estado().situacao === 'enviado' && r1.acao === 'registrar' && /^[0-9a-f]{32}$/.test(r1.identificador || '') && igual && r1.nome_instituicao && r1.email_seguranca === 'ti.prova@exemplo.invalid' && semPessoa(r1),
      `o envio: o servidor falso recebeu o mesmo conteúdo da tela (com o nome e o e-mail marcados e o identificador ${String(r1.identificador).slice(0, 8)}…); a situação, "enviado"`);

    /* C. o endereço fora do ar: pendente, sem erro; o reenvio pelos Avisos */
    fs.writeFileSync(CONFIG, comEnderecos('http://127.0.0.1:9/registro', `${base}/versao.json`));
    await p.goto(L.BASE + 'painel/registro-instalacao/index');
    await Promise.all([p.waitForNavigation(), p.click('#enviarRegistro')]);
    const pendente = estado().situacao === 'pendente' && !(await p.$('#registroRecusado')) && /pendente de envio/.test(await p.textContent('#situacaoRegistro'));
    fs.writeFileSync(CONFIG, comEnderecos(`${base}/registro`, `${base}/versao.json`));
    const antes = recebidos.length;
    envelhecer({ tentativa_em: '2020-01-01 00:00:00' });
    await p.goto(L.BASE + 'painel/avisos-sistema/index');
    L.ok(pendente && estado().situacao === 'enviado' && recebidos.length === antes + 1, 'o endereço fora do ar: "pendente de envio", sem erro na tela; quando ele volta, o reenvio periódico (pelos Avisos do sistema) envia');

    /* D. o nome mudou: espera a confirmação */
    const nomeAntes = sql('SELECT nome FROM adms_empresas ORDER BY id LIMIT 1');
    sql(`UPDATE adms_empresas SET nome = 'Instituição Renomeada (prova)' ORDER BY id LIMIT 1`);
    envelhecer({ tentativa_em: '2020-01-01 00:00:00', enviado_em: '2020-01-01 00:00:00' });
    const antesD = recebidos.length;
    await p.goto(L.BASE + 'painel/avisos-sistema/index');
    const esperou = estado().situacao === 'confirmar' && recebidos.length === antesD;
    await p.goto(L.BASE + 'painel/registro-instalacao/index');
    await Promise.all([p.waitForNavigation(), p.click('#enviarRegistro')]);
    const r2 = recebidos.pop() || {};
    sql(`UPDATE adms_empresas SET nome = '${nomeAntes.replace(/'/g, "''")}' ORDER BY id LIMIT 1`);
    L.ok(esperou && r2.nome_instituicao === 'Instituição Renomeada (prova)' && estado().situacao === 'enviado',
      'o nome da instituição mudou: o reenvio do mês espera a confirmação (nada foi enviado); confirmado, vai o nome novo');

    /* E. cancelar */
    await p.goto(L.BASE + 'painel/registro-instalacao/index');
    await Promise.all([p.waitForNavigation(), p.click('#cancelarRegistro')]);
    const r3 = recebidos.pop() || {};
    envelhecer({ tentativa_em: '2020-01-01 00:00:00' });
    const antesE = recebidos.length;
    await p.goto(L.BASE + 'painel/avisos-sistema/index');
    L.ok(r3.acao === 'cancelar' && r3.identificador === r1.identificador && estado().situacao === 'cancelado' && recebidos.length === antesE,
      'cancelar o registro: o servidor falso é avisado (o mesmo identificador); depois, nada mais é enviado');

    /* F. o convite do instalador: desmarcado */
    const inst = fs.readFileSync(path.join(L.RAIZ, 'instalar', 'index.php'), 'utf8');
    const caixa = (inst.match(/<input[^>]*id="registrarInstalacao"[^>]*>/) || [''])[0];
    L.ok(caixa && !/checked/.test(caixa) && /searchParams\.set\('registrar', '1'\)/.test(inst), 'o instalador traz o convite do registro desmarcado; marcado, o começo no painel leva o pedido');

    /* G. a versão nova que corrige falha de segurança; os links do projeto */
    versaoJson = { versao: '9.0.0', notas: 'https://vcostaedu.com/versoes/9.0.0', seguranca: true };
    sql("DELETE FROM adms_instalacao_estado WHERE chave = 'versao_nova.consulta'");
    await p.goto(L.BASE + 'painel/avisos-sistema/index');
    const destaque = !!(await p.$('#versaoNovaSeguranca')) && !!(await p.$('#linkApoie')) && !!(await p.$('#linkServicos'));
    fs.writeFileSync(CONFIG, comEnderecos(`${base}/registro`, 'http://127.0.0.1:9/versao.json'));
    sql("DELETE FROM adms_instalacao_estado WHERE chave = 'versao_nova.consulta'");
    await p.goto(L.BASE + 'painel/avisos-sistema/index');
    const semSite = !(await p.$('#linkApoie')) && !(await p.$('#linkServicos')) && !(await p.$('#versaoNovaDisponivel'));
    L.ok(destaque && semSite, 'a versão nova que corrige falha de segurança aparece em destaque; com o site do projeto fora do ar, nada aparece como versão nova e os links Apoie e Serviços somem');

    /* H. o pacote */
    const saida = fs.mkdtempSync(path.join(os.tmpdir(), 'vcostaedu-pacote-'));
    const g = spawnSync(PHP, [path.join(L.RAIZ, 'database', 'ferramentas', 'implantacao', 'gerar_pacote.php'), `--saida=${saida}`, '--permitir-sujo', '--seguranca'], { encoding: 'utf8', cwd: L.RAIZ, maxBuffer: 1 << 26 });
    const arq = fs.readdirSync(saida);
    const vj = arq.find((x) => x.endsWith('.versao.json'));
    const j = vj ? JSON.parse(fs.readFileSync(path.join(saida, vj), 'utf8')) : {};
    const zipNome = arq.find((x) => x.endsWith('.zip'));
    const hz = zipNome ? crypto.createHash('sha256').update(fs.readFileSync(path.join(saida, zipNome))).digest('hex') : '';
    const notas = arq.find((x) => /^NOTAS-.*\.md$/.test(x));
    const nt = notas ? fs.readFileSync(path.join(saida, notas), 'utf8') : '';
    fs.rmSync(saida, { recursive: true, force: true });
    L.ok(g.status === 0 && /^\d+\.\d+\.\d+$/.test(j.versao || '') && j.sha256 === hz && j.seguranca === true && /## O que muda/.test(nt) && nt.includes(hz),
      `o pacote ${j.versao}: as notas da versão (com o código de conferência), o versao.json com o sha256 do zip e a marca de segurança`);

    /* sabotagem: um nome de aluno no conteúdo */
    const volta = (() => { const orig = fs.readFileSync(MODELO, 'utf8'); const de = "        $c['aparecer_na_lista'] = !empty($escolhas['listar']);"; if (!orig.includes(de)) { throw new Error('sabotagem sem o trecho'); }
      fs.writeFileSync(MODELO, orig.replace(de, de + "\n        $c['alunos_prova'] = $pdo->query('SELECT GROUP_CONCAT(nome) FROM col_usuarios WHERE col_niveis_acesso_id = 1')->fetchColumn(); // sabotagem da prova da Onda 6, Etapa 7"));
      return () => fs.writeFileSync(MODELO, orig); })();
    let sab = {};
    try { await p.goto(L.BASE + 'painel/registro-instalacao/index'); sab = JSON.parse(await p.textContent('#conteudoRegistro')); } finally { volta(); }
    L.ok(!semPessoa(sab), 'sabotagem (um nome de aluno no conteúdo do registro): a prova reprova');
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    fs.writeFileSync(CONFIG, cfgOrig);
    sql("DELETE FROM adms_instalacao_estado WHERE chave IN ('registro.instalacao', 'versao_nova.consulta')");
    servidor.close();
    await navegador.close();
  }
  L.ok(sha(CONFIG) === shas[CONFIG] && sha(MODELO) === shas[MODELO], 'a Config de prova e o model do registro voltaram iguais (sha256)');
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
