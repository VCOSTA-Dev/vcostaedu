/*
 * PROVA DA ONDA 6, ETAPA 6 — a segurança (docs/SEGURANCA_REVISAO.md).
 *   A. o envio forjado: um POST com o Origin (ou, sem ele, o Referer) de outro site é recusado (403) e nada muda; o do
 *      próprio sistema passa; sabotagem: sem a conferência, o forjado passa;
 *   B. apagar o ano letivo só por POST: o link (GET) não apaga;
 *   C. a foto enviada: o tipo pelo conteúdo e a extensão do tipo — "foto.php" com uma imagem dentro e "foto.jpg" que não é
 *      imagem são recusados, a imagem de verdade passa; nada roda como programa dentro de assets/ (404); sabotagem: sem a
 *      conferência da extensão, a prova reprova (e o arquivo sai);
 *   D. a saída tratada: um nome com código de página aparece como texto nas listas (nenhum alerta dispara, nenhuma marca
 *      crua); sabotagem: uma saída crua de volta, a prova reprova;
 *   E. a sessão sem uso: com o limite em 1 minuto, depois de 65 s a próxima tela pede para entrar de novo, com o motivo;
 *      sabotagem: sem o limite, a sessão continua;
 *   F. os cabeçalhos: Permissions-Policy, nosniff, frame-ancestors;
 *   G. Verificações de segurança: o perfil criado pela tela (sem papel) aparece como atenção.
 * Depois do ciclo (a demonstração do ensino superior). Cada arquivo sabotado volta igual (sha256).
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { execFileSync } = require('child_process');
const L = require('../lib');

const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste$/.test(BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
const MYSQL = process.env.VCOSTAEDU_MYSQL || 'C:/xampp/mysql/bin/mysql.exe';
const sql = (q) => execFileSync(MYSQL, ['-u', 'root', '-N', '--default-character-set=utf8mb4', BANCO], { input: Buffer.from(q, 'utf8') }).toString().replace(/\r/g, '').trim();
const um = (q) => +sql(q);
const ETAPA = 'onda6/etapa6';
const cred = JSON.parse(fs.readFileSync(path.join(__dirname, '..', 'saida', 'credenciais.json'), 'utf8'));
const SEG = path.join(L.RAIZ, 'painel', 'core', 'Seguranca.php');
const UPLOAD = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'helper', 'AdmsUploadImgRed.php');
const LISTA = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Views', 'academicos', 'listAcademicos.php');
const CONFIG = path.join(L.RAIZ, 'painel', 'core', 'Config.php');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
function sabotar(arquivo, de, para) {
  const orig = fs.readFileSync(arquivo, 'utf8');
  if (!orig.includes(de)) { throw new Error('a sabotagem não achou o trecho em ' + path.basename(arquivo)); }
  fs.writeFileSync(arquivo, orig.replace(de, para));
  return () => fs.writeFileSync(arquivo, orig);
}
/* um JPEG de verdade, 2×2 (o PHP faz), e o mesmo com código PHP no fim */
const JPEG = Buffer.from(execFileSync(process.env.VCOSTAEDU_PHP || 'C:/xampp/php/php.exe', ['-r', '$i = imagecreatetruecolor(20, 20); ob_start(); imagejpeg($i); echo base64_encode(ob_get_clean());']).toString().trim(), 'base64');
const COM_CODIGO = Buffer.concat([JPEG, Buffer.from('<?php echo "rodou"; ?>')]);

(async () => {
  const { navegador, contexto } = await L.abrir();
  const shas = Object.fromEntries([SEG, UPLOAD, LISTA, CONFIG].map((f) => [f, sha(f)]));
  const p = await L.pagina(contexto);
  p.on('dialog', (d) => { p._dialogo = (p._dialogo || 0) + 1; d.dismiss(); });
  try {
    console.log('\n== Onda 6, Etapa 6 — a segurança');
    await p.goto(L.BASE + 'painel/login/acesso');
    await p.fill('input[name="usuario"]', 'administrador');
    await p.fill('input[name="senha"]', cred.painel.administrador);
    await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
    const req = contexto.request;
    const host = new URL(L.BASE).host;

    /* A. o envio forjado */
    const ano = 2090;
    sql(`DELETE FROM adms_periodos_letivos WHERE ano = ${ano}; INSERT INTO adms_periodos_letivos (ano, situacaodisciplina, situacaomodelo, situacao, usuario_cre) VALUES (${ano}, 1, 1, 1, 1)`);
    const idAno = um(`SELECT id FROM adms_periodos_letivos WHERE ano = ${ano}`);
    const apagar = (headers) => req.post(L.BASE + `painel/anos-letivos/apagar/${idAno}`, { form: { ApagarAno: '1' }, headers, maxRedirects: 0 });
    const r1 = await apagar({ Origin: 'https://outro-site.exemplo.invalid' });
    const r2 = await apagar({ Referer: 'https://outro-site.exemplo.invalid/pagina' });
    const existe = () => um(`SELECT COUNT(*) FROM adms_periodos_letivos WHERE id = ${idAno}`) === 1;
    L.ok(r1.status() === 403 && r2.status() === 403 && existe(), `o POST de outro site (pelo Origin: ${r1.status()}; pelo Referer: ${r2.status()}) é recusado e o ano continua lá`);
    /* B. o link (GET) não apaga */
    await req.get(L.BASE + `painel/anos-letivos/apagar/${idAno}`, { maxRedirects: 0 });
    const ficouGet = existe();
    const r3 = await apagar({ Origin: `${new URL(L.BASE).protocol}//${host}` });
    L.ok(ficouGet && [200, 302].includes(r3.status()) && !existe(), `apagar o ano letivo só por POST: o link (GET) não apaga; o POST do próprio sistema apaga (${r3.status()})`);
    sql(`INSERT INTO adms_periodos_letivos (ano, situacaodisciplina, situacaomodelo, situacao, usuario_cre) VALUES (${ano}, 1, 1, 1, 1)`);
    const idAno2 = um(`SELECT id FROM adms_periodos_letivos WHERE ano = ${ano}`);
    const volta1 = sabotar(SEG, "        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !self::mesmaOrigem()) {", "        if (false && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !self::mesmaOrigem()) {");
    let rs = null;
    try { rs = await req.post(L.BASE + `painel/anos-letivos/apagar/${idAno2}`, { form: { ApagarAno: '1' }, headers: { Origin: 'https://outro-site.exemplo.invalid' }, maxRedirects: 0 }); } finally { volta1(); }
    L.ok(um(`SELECT COUNT(*) FROM adms_periodos_letivos WHERE ano = ${ano}`) === 0, `sabotagem (sem a conferência da origem): a prova reprova — o POST de outro site apagou o ano (${rs && rs.status()})`);
    sql(`DELETE FROM adms_periodos_letivos WHERE ano = ${ano}`);

    /* C. a foto enviada */
    const uid = um("SELECT id FROM adms_usuarios WHERE usuario = 'administrador'");
    const pasta = path.join(L.RAIZ, 'painel', 'assets', 'img', 'usuario', String(uid));
    const enviarFoto = async (nome, buffer, tipo) => {
      await p.goto(L.BASE + 'painel/perfil/editar');
      await p.setInputFiles('input[name="imagem_nova"]', { name: nome, mimeType: tipo, buffer });
      await Promise.all([p.waitForNavigation(), p.click('input[name="EditPerfil"]')]);
      return fs.existsSync(path.join(pasta, nome));
    };
    const php1 = await enviarFoto('foto-prova.php', COM_CODIGO, 'image/jpeg');
    const falsa = await enviarFoto('foto-prova.jpg', Buffer.from('<?php echo "rodou"; ?>'), 'image/jpeg');
    const boa = await enviarFoto('foto-prova-boa.jpg', JPEG, 'image/jpeg');
    fs.mkdirSync(pasta, { recursive: true });
    fs.writeFileSync(path.join(pasta, 'prova-assets.php'), '<?php echo "rodou";');
    const naoRoda = await req.get(L.BASE + `painel/assets/img/usuario/${uid}/prova-assets.php`);
    const status404 = naoRoda.status();
    fs.unlinkSync(path.join(pasta, 'prova-assets.php'));
    L.ok(!php1 && !falsa && boa && status404 === 404, `a foto: "foto.php" com uma imagem dentro recusado; "foto.jpg" que não é imagem recusado; a imagem de verdade gravada; um programa em assets/ não roda (${status404})`);
    const volta2 = sabotar(UPLOAD, "        if ($tipo === IMAGETYPE_JPEG && in_array($ext, ['jpg', 'jpeg'], true)) {", "        if ($tipo === IMAGETYPE_JPEG) {");
    let sab2 = false;
    try { sab2 = await enviarFoto('foto-prova2.php', COM_CODIGO, 'image/jpeg'); } finally { volta2(); }
    L.ok(sab2, 'sabotagem (sem conferir a extensão): a prova reprova — o "foto.php" foi gravado');
    for (const f of ['foto-prova2.php', 'foto-prova.php']) { try { fs.unlinkSync(path.join(pasta, f)); } catch (e) { /* não havia */ } }

    /* D. a saída tratada */
    const MARCA = '<img src=x onerror=alert(1)>Prova Exemplo';
    const ra = sql('SELECT rm FROM col_usuarios WHERE col_niveis_acesso_id = 1 ORDER BY id LIMIT 1');
    const nomeAntes = sql(`SELECT nome FROM col_usuarios WHERE rm = '${ra}'`);
    sql(`UPDATE col_usuarios SET nome = '${MARCA.replace(/'/g, "''")}' WHERE rm = '${ra}'`);
    const olhar = async (rota) => { p._dialogo = 0; await p.goto(L.BASE + 'painel/' + rota); await p.waitForTimeout(500); const html = await p.content(); return { cru: html.includes('<img src=x onerror'), texto: (await p.textContent('body')).includes('<img src=x onerror=alert(1)>Prova'), alerta: p._dialogo > 0 }; };
    const telas = ['academicos/listar?busca=Prova', `academicos/visualizar/${um(`SELECT id FROM col_usuarios WHERE rm = '${ra}'`)}`, `academicos/boletim/${ra}`];
    const vistos = [];
    for (const t of telas) { vistos.push(await olhar(t)); }
    L.ok(vistos.every((v) => !v.cru && !v.alerta) && vistos.some((v) => v.texto), `um nome com código de página aparece como texto (${telas.length} telas: a lista, a ficha, o boletim); nenhum alerta disparou`);
    const volta3 = sabotar(LISTA, "<td><?= htmlspecialchars((string) $use['nome']) ?>", "<td><?= $use['nome'] ?>");
    let sab3 = {};
    try { sab3 = await olhar('academicos/listar?busca=Prova'); } finally { volta3(); }
    L.ok(sab3.cru || sab3.alerta, 'sabotagem (a saída crua de volta na lista dos alunos): a prova reprova — a marca crua na página');
    sql(`UPDATE col_usuarios SET nome = '${nomeAntes.replace(/'/g, "''")}' WHERE rm = '${ra}'`);

    /* E. a sessão sem uso */
    const cfgOrig = fs.readFileSync(CONFIG, 'utf8');
    const comLimite = (n) => cfgOrig.replace(/define\('SESSAO_INATIVA_MINUTOS', \d+\);/, `define('SESSAO_INATIVA_MINUTOS', ${n});`);
    if (!/define\('SESSAO_INATIVA_MINUTOS', \d+\);/.test(cfgOrig)) { throw new Error('a Config de prova não tem SESSAO_INATIVA_MINUTOS (o ciclo gera do molde)'); }
    const expira = async () => {
      await p.goto(L.BASE + 'painel/home/index');
      await p.waitForTimeout(65000);
      await p.goto(L.BASE + 'painel/academicos/listar');
      return { login: !!(await p.$('input[name="SendLogin"]')), aviso: !!(await p.$('#sessaoExpirada')) };
    };
    let e1 = {};
    let e2 = {};
    try {
      fs.writeFileSync(CONFIG, comLimite(1));
      e1 = await expira();
      L.ok(e1.login && e1.aviso, 'a sessão sem uso por mais de 1 minuto (o limite posto pela prova): a próxima tela pede para entrar de novo, com o motivo');
      await p.fill('input[name="usuario"]', 'administrador');
      await p.fill('input[name="senha"]', cred.painel.administrador);
      await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
      const volta4 = sabotar(SEG, '        self::inatividade();\n', '');
      try { e2 = await expira(); } finally { volta4(); }
    } finally { fs.writeFileSync(CONFIG, cfgOrig); }
    L.ok(!e2.login, 'sabotagem (sem o limite de inatividade): a prova reprova — a sessão continuou');
    L.ok(sha(CONFIG) === shas[CONFIG], 'a Config de prova voltou igual (sha256)');

    /* F. os cabeçalhos */
    const r = await req.get(L.BASE + 'painel/login/acesso');
    const h = r.headers();
    L.ok(/camera=\(\)/.test(h['permissions-policy'] || '') && h['x-content-type-options'] === 'nosniff' && /frame-ancestors 'none'/.test(h['content-security-policy'] || ''),
      `os cabeçalhos: Permissions-Policy (${h['permissions-policy']}), nosniff, frame-ancestors 'none'`);

    /* G. Verificações de segurança: o perfil sem papel */
    await p.goto(L.BASE + 'painel/login/acesso');
    if (await p.$('input[name="SendLogin"]')) {
      await p.fill('input[name="usuario"]', 'administrador');
      await p.fill('input[name="senha"]', cred.painel.administrador);
      await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
    }
    await p.goto(L.BASE + 'painel/verificacoes-seguranca/index');
    const sit0 = await p.getAttribute('[data-codigo="perfis"]', 'data-situacao').catch(() => null);
    sql("INSERT INTO adms_niveis_acessos (nome, ordem, papel, situacao, usuario_cre, created) VALUES ('Perfil da tela (prova)', 99, NULL, 1, 1, NOW())");
    await p.goto(L.BASE + 'painel/verificacoes-seguranca/index');
    const sit1 = await p.getAttribute('[data-codigo="perfis"]', 'data-situacao').catch(() => null);
    const txt1 = await p.textContent('[data-codigo="perfis"]').catch(() => '');
    sql("DELETE FROM adms_niveis_acessos WHERE nome = 'Perfil da tela (prova)'");
    L.ok(sit0 === 'OK' && sit1 === 'ALERTA' && /Perfil da tela \(prova\)/.test(txt1), `Verificações de segurança: o perfil criado pela tela aparece como atenção (${sit0} → ${sit1})`);
    for (const f of [SEG, UPLOAD, LISTA]) { L.ok(sha(f) === shas[f], `${path.basename(f)} voltou igual (sha256)`); }
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    for (const [f, h] of Object.entries(shas)) { if (sha(f) !== h) { console.log(`  [ALERTA] ${path.basename(f)} não voltou igual`); } }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
