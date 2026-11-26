/*
 * PROVA DA ONDA 7, ETAPA 5 — a barreira de acesso (D-65). Na escola (a demonstração do colégio, montada pela tela;
 * APAGA o banco de prova). Cada grupo tem a sabotagem dele, e o arquivo sabotado volta igual (sha256):
 *   1. o identificador do aluno trocado na rota (todas as telas), no formulário (a matrícula do atestado) e no parâmetro
 *      (o PDF de outro aluno), e o ano trocado para um ano sem papel: recusados (403) — sabotagem: a barreira do
 *      controller desligada;
 *   2. "Verificações de segurança": o item do portal do responsável OK; com a barreira desligada, FALHA;
 *   3. acadêmico de um filho e financeiro de outro; o casal separado (o titular recebe "não" para o financeiro por todos
 *      os caminhos); o acesso adicional (o acadêmico no ano da liberação, nunca o financeiro) — sabotagem: o ponto único
 *      responde o financeiro para qualquer papel;
 *   4. a regra do acesso adicional desligada: a liberação recusada pela tela, e a que existia deixa de dar acesso —
 *      sabotagem: o ponto único sem ler a regra;
 *   5. dois financeiros direto no banco: recusado — sabotagem: sem o índice único, o banco aceitaria (o índice volta);
 *      pela tela, pela planilha e pela interface, a prova da Etapa 2;
 *   6. o papel que muda de um ano para outro: o alcance acompanha, ano a ano — sabotagem: o ponto único sem o ano;
 *   7. a troca de financeiro no meio do ano (nunca dois; o anterior perde o alcance na data, o histórico fica), o papel
 *      retirado e a liberação encerrada no meio da sessão (a tela seguinte recusada) — sabotagem: o ponto único sem a
 *      data do fim;
 *   8. o aluno numa tela de responsável e o responsável numa tela só de aluno: recusados — sabotagem: o controller
 *      sem conferir quem é o responsável, e o nível do aluno com a página da família;
 *   9. nenhuma página do portal do responsável com "financeiro", valor em dinheiro ou situação de pagamento, para
 *      nenhum papel — sabotagem: um texto de pagamento no cabeçalho do filho.
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { spawnSync, execFileSync } = require('child_process');
const L = require('../lib');

const ETAPA = 'onda7/etapa5';
const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste$/.test(BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
const SENHA = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const PHP = 'C:/xampp/php/php.exe';
const CTRL = path.join(L.RAIZ, 'portal', 'app', 'adms', 'Controllers', 'Familia.php');
const PONTO = path.join(L.RAIZ, 'core', 'AcessoFamilia.php');
const CAB = path.join(L.RAIZ, 'portal', 'app', 'adms', 'Views', 'familia', '_cabecalho.php');
const ANO = 2026;
const ANTERIOR = 2025;
const SENHA_R = 'girassol no quintal azul de domingo';
const SENHA_ALUNO = 'jabuticaba madura no pe de casa';
const ROTAS = ['filho', 'boletim', 'notas', 'frequencia', 'horario', 'turma', 'atestado', 'atestado-pdf'];
const DINHEIRO = /financeir|cobran|boleto|mensalidade|pagamento|pagar|inadimpl|em atraso|d[ée]bito|R\$\s*\d/i;

const sql = (q) => execFileSync('C:/xampp/mysql/bin/mysql.exe', ['-uroot', '--default-character-set=utf8mb4', '-N', '-B', BANCO, '-e', q], { encoding: 'utf8', stdio: ['pipe', 'pipe', 'pipe'] }).trim();
const sqlErro = (q) => { try { sql(q); return ''; } catch (e) { return String(e.stderr || e.message); } };
const um = (q) => +(sql(q).split('\n')[0] || 0);
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const hash = (s) => execFileSync(PHP, ['-r', 'echo password_hash($argv[1], PASSWORD_DEFAULT);', s], { encoding: 'utf8' }).trim();
const idAluno = (ra) => um(`SELECT id FROM col_usuarios WHERE rm = '${ra}'`);
/** Pergunta ao ponto único (no banco de prova): [[resp, aluno, ano, tipo, data?], ...] → [true|false, ...]. */
const pode = (qs) => JSON.parse(execFileSync(PHP, [path.join(__dirname, 'pode.php')], { input: JSON.stringify(qs), encoding: 'utf8', env: process.env }));

const originais = {};
/** Sabotagem: troca um trecho do arquivo, roda, e devolve o arquivo (sempre). */
async function sabotar(arq, de, para, fn) {
  const orig = fs.readFileSync(arq, 'utf8');
  originais[arq] = originais[arq] || sha(arq);
  if (!orig.includes(de)) { throw new Error('a sabotagem não achou o ponto em ' + path.basename(arq)); }
  fs.writeFileSync(arq, orig.replace(de, para));
  try { return await fn(); } finally { fs.writeFileSync(arq, orig); }
}

async function entrarPainel(navegador) {
  const ctx = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' });
  const p = await L.pagina(ctx);
  await p.goto(L.BASE + 'painel/login/acesso');
  await p.fill('input[name="usuario"]', 'administrador');
  await p.fill('input[name="senha"]', process.env.SENHA_ADM || SENHA);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
  return { ctx, p };
}
async function postar(p, rota, campos, voltar) {
  await p.evaluate(async ([u, c]) => { const f = new URLSearchParams(); for (const [k, v] of Object.entries(c)) { f.append(k, v); }
    await fetch(u, { method: 'POST', body: f, credentials: 'same-origin', redirect: 'manual' }); }, [L.BASE + 'painel/' + rota, campos]);
  await p.goto(L.BASE + 'painel/' + voltar);
  return p.$$eval('.alert', (l) => l.map((e) => e.innerText.replace(/\s+/g, ' ').trim()).join(' | ')).catch(() => '');
}
async function entrarPortal(navegador, usuario, senha) {
  const ctx = await navegador.newContext({ viewport: { width: 390, height: 844 }, locale: 'pt-BR' });
  const p = await L.pagina(ctx);
  await p.goto(L.BASE + 'portal/login/acesso');
  await p.fill('input[name="usuario"]', usuario);
  await p.fill('input[name="senha"]', senha);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
  return { ctx, p };
}
async function abre(p, rota) {
  const r = await p.goto(L.BASE + 'portal/' + rota);
  /* Onda 8, financeiro (D-98): o cartão "Ficha financeira" do responsável financeiro fica fora do texto conferido (é a
     única coisa do financeiro nas telas da família; quem o vê é conferido pela prova do financeiro, onda8/etapa28) */
  const t = await p.evaluate(() => { const c = document.body.cloneNode(true); c.querySelectorAll('.vc-cartao-financeiro').forEach((x) => x.remove()); document.body.appendChild(c); c.style.display = 'block'; const s = c.innerText; c.remove(); return s.replace(/\s+/g, ' '); });
  return { status: r ? r.status() : 0, texto: t, url: p.url(), cab: !!(await p.$('#cabecalhoFilho')), cartao: !!(await p.$('.vc-cartao-financeiro')) };
}
/** Todas as rotas do filho, para aquele aluno e ano: os status. */
async function todas(p, aluno, ano, extra = '') {
  const s = [];
  for (const r of ROTAS) { s.push((await abre(p, `familia/${r}/${aluno}?ano=${ano}${r === 'atestado-pdf' ? extra : ''}`)).status); }
  return s;
}
/** O POST do atestado (o formulário) com uma matrícula escolhida. */
async function postarAtestado(p, aluno, ano, matricula) {
  return p.evaluate(async ([u, m]) => { const f = new URLSearchParams(); f.append('matricula', String(m));
    const x = await fetch(u, { method: 'POST', body: f, credentials: 'same-origin', redirect: 'manual' }); return x.status; }, [L.BASE + `portal/familia/atestado/${aluno}?ano=${ano}`, matricula]);
}
const prepararAtestado = (aluno, cpf) => {
  sql(`UPDATE rm_matriculas_curso SET previsao_conclusao = '2029-12-15' WHERE usuario_id = ${aluno} AND previsao_conclusao IS NULL`);
  sql(`UPDATE col_usuarios_informacoes SET cpf = '${cpf}' WHERE col_user_id = ${aluno} AND (cpf IS NULL OR cpf = '')`);
};

(async () => {
  const { navegador } = await L.abrir();
  const shas = { [CTRL]: sha(CTRL), [PONTO]: sha(PONTO), [CAB]: sha(CAB) };
  try {
    console.log('\n== Onda 7, Etapa 5 — a barreira de acesso');
    const r = spawnSync(process.execPath, [path.join(__dirname, '..', 'onda6', 'medir_demo.js'), 'escola', 'onda7-etapa5'], { encoding: 'utf8', maxBuffer: 1 << 24 });
    require('./_antes_d99')(); // D-99: os responsáveis como antes do financeiro refeito (os alunos sem responsável, as pessoas sem celular)
    const a1 = idAluno('265001');
    const a2 = idAluno('265002');
    const a3 = idAluno('265003');
    L.ok(r.status === 0 && a1 > 0 && a2 > 0 && a3 > 0, 'a demonstração do colégio montada pela tela');
    const s = await entrarPainel(navegador);
    const p = s.p;
    const cad = async (nome, email) => { await postar(p, 'responsaveis/cadastrar', { nome, email, celular: '', cpf: '' }, 'responsaveis/listar'); return um(`SELECT id FROM esc_responsaveis WHERE email = '${email}'`); };
    const des = (aluno, ano, papel, pessoa, data = `${ano}-02-01`, motivo = '') => postar(p, 'responsaveis/designar',
      { aluno: String(aluno), ano: String(ano), papel, responsavel_id: String(pessoa), parentesco: 'Mãe', data, motivo }, `responsaveis/aluno/${aluno}?ano=${ano}`);
    const lib = (aluno, ano, pessoa, motivo) => postar(p, 'responsaveis/liberar',
      { aluno: String(aluno), ano: String(ano), responsavel_id: String(pessoa), parentesco: 'Avó', motivo, referencia: 'Termo (prova)' }, `responsaveis/aluno/${aluno}?ano=${ano}`);
    const encerrar = (aluno, ano, id) => postar(p, 'responsaveis/encerrar', { aluno: String(aluno), ano: String(ano), designacao: String(id), data: '', motivo: 'Pedido da família (prova)' }, `responsaveis/aluno/${aluno}?ano=${ano}`);
    const designacao = (pessoa, aluno, ano, papel) => um(`SELECT id FROM esc_responsavel_designacoes WHERE responsavel_id = ${pessoa} AND aluno_id = ${aluno} AND ano = ${ano} AND papel = '${papel}' AND fim IS NULL`);

    /* as famílias da prova: R1 é o financeiro de a1 e o titular de a2; R2 (o casal separado) é o titular de a1 em 2026
       e o financeiro de a1 em 2025; R3, o financeiro de a3 (a outra família); R5, o acesso adicional de a1; R6, o
       adicional de a2; R7, o novo financeiro de a1 (a troca) */
    const R1 = await cad('Mãe Barreira Exemplo', 'mae.barreira@exemplo.invalid');
    const R2 = await cad('Pai Barreira Exemplo', 'pai.barreira@exemplo.invalid');
    const R3 = await cad('Mãe Outra Exemplo', 'mae.outra@exemplo.invalid');
    const R5 = await cad('Avó Barreira Exemplo', 'avo.barreira@exemplo.invalid');
    const R6 = await cad('Tia Barreira Exemplo', 'tia.barreira@exemplo.invalid');
    const R7 = await cad('Madrasta Barreira Exemplo', 'madrasta.barreira@exemplo.invalid');
    await des(a1, ANO, 'financeiro', R1);
    await des(a2, ANO, 'titular', R1);
    await des(a1, ANO, 'titular', R2);
    await des(a1, ANTERIOR, 'financeiro', R2);
    await des(a3, ANO, 'financeiro', R3);
    await lib(a1, ANO, R5, 'A avó acompanha as tarefas (prova)');
    await lib(a2, ANO, R6, 'A tia busca na escola (prova)');
    sql(`UPDATE esc_responsaveis SET senha = '${hash(SENHA_R)}', ativado_em = NOW(), login = email WHERE id IN (${[R1, R2, R3, R5, R6, R7].join(',')})`); // como o convite faz (Onda 8, D-67: o login, o e-mail)
    sql(`UPDATE col_usuarios SET senha = '${hash(SENHA_ALUNO)}', col_sits_usuario_id = 1 WHERE id = ${a1}`);
    prepararAtestado(a1, '000.930.101-17');
    prepararAtestado(a3, '000.930.103-89');
    const montadas = um(`SELECT COUNT(*) FROM esc_responsavel_designacoes WHERE fim IS NULL AND responsavel_id IN (${[R1, R2, R3, R5, R6].join(',')})`);
    L.ok([R1, R2, R3, R5, R6, R7].every((x) => x > 0) && montadas === 7, `as famílias da prova montadas pela tela (${montadas} papéis)`);

    /* a outra família emite um atestado (para o parâmetro trocado) */
    const e3 = await entrarPortal(navegador, 'mae.outra@exemplo.invalid', SENHA_R);
    await abre(e3.p, `familia/atestado/${a3}?ano=${ANO}`);
    if (await e3.p.$('#emitirAtestado')) { await Promise.all([e3.p.waitForNavigation(), e3.p.click('#emitirAtestado')]); }
    await e3.ctx.close();
    const emissao3 = um(`SELECT id FROM rm_historico_emissoes WHERE aluno_id = ${a3} AND tipo = 'ATESTADO' ORDER BY id DESC LIMIT 1`);
    const matricula3 = um(`SELECT id FROM rm_matriculas_curso WHERE usuario_id = ${a3} ORDER BY id DESC LIMIT 1`);

    /* 1. o aluno e o ano trocados */
    let e1 = await entrarPortal(navegador, 'mae.barreira@exemplo.invalid', SENHA_R);
    const proprias = await todas(e1.p, a1, ANO);
    const deOutro = await todas(e1.p, a3, ANO, `&emissao=${emissao3}`);
    const anoSem = await todas(e1.p, a1, ANTERIOR);
    const pdfTrocado = (await abre(e1.p, `familia/atestado-pdf/${a1}?ano=${ANO}&emissao=${emissao3}`)).status;
    const antesForm = um(`SELECT COUNT(*) FROM rm_historico_emissoes WHERE aluno_id = ${a3}`);
    const formOutro = await postarAtestado(e1.p, a3, ANO, matricula3);
    await postarAtestado(e1.p, a1, ANO, matricula3);
    const formTrocado = await e1.p.goto(L.BASE + `portal/familia/atestado/${a1}?ano=${ANO}`).then(() => e1.p.textContent('#atestadoErro').catch(() => ''));
    const depoisForm = um(`SELECT COUNT(*) FROM rm_historico_emissoes WHERE aluno_id = ${a3}`);
    const recusa = await abre(e1.p, `familia/boletim/${a3}?ano=${ANO}`);
    const nome3 = sql(`SELECT nome FROM col_usuarios WHERE id = ${a3}`);
    L.ok(proprias.slice(0, 7).every((x) => x === 200) && deOutro.every((x) => x === 403) && emissao3 > 0 && pdfTrocado === 403,
      `o aluno de outra família na rota: recusado em todas as ${ROTAS.length} telas (403), inclusive o PDF de outro aluno pelo parâmetro`);
    L.ok(formOutro === 403 && /não encontrada/i.test(formTrocado) && depoisForm === antesForm,
      'o formulário: a matrícula de outro aluno no atestado não emite nada (nem pela rota do outro aluno, nem pela do filho)');
    L.ok(anoSem.every((x) => x === 403) && !recusa.texto.includes(nome3), 'o ano sem papel: recusado em todas as telas; a recusa não mostra o aluno');
    const sab1 = await sabotar(CTRL, 'if (!$anos || !$this->acesso()->pode($resp, $aluno, $ano, \\VcAcessoFamilia::ACADEMICO)) {', 'if (false) {', async () => {
      const o = (await abre(e1.p, `familia/boletim/${a3}?ano=${ANO}`)).status;
      const ano = (await abre(e1.p, `familia/notas/${a1}?ano=${ANTERIOR}`)).status;
      await p.goto(L.BASE + 'painel/verificacoes-seguranca/index');
      const item = await p.getAttribute('[data-codigo="familia"]', 'data-situacao').catch(() => '');
      return { o, ano, item };
    });
    L.ok(sab1.o === 200 && sab1.ano === 200, 'sabotagem (a barreira do controller desligada): a prova acusa — o aluno de outra família e o ano sem papel abriram');

    /* 2. Verificações de segurança */
    await p.goto(L.BASE + 'painel/verificacoes-seguranca/index');
    const item = await p.getAttribute('[data-codigo="familia"]', 'data-situacao').catch(() => '');
    L.ok(item === 'OK' && sab1.item === 'FALHA', `"Verificações de segurança": o portal do responsável passa pelo ponto único (${item}); com a barreira desligada, o item acusa (${sab1.item})`);
    L.ok(sha(CTRL) === shas[CTRL], 'o arquivo sabotado voltou igual (sha256)');

    /* 3. os papéis e o financeiro */
    const hoje = sql('SELECT CURDATE()');
    const filhosR1 = pode([['filhos', R1]])[0];
    const bolR1a2 = (await abre(e1.p, `familia/boletim/${a2}?ano=${ANO}`)).status;
    const q3 = [[R1, a1, ANO, 'financeiro'], [R1, a2, ANO, 'financeiro'], [R1, a1, ANO, 'academico'], [R1, a2, ANO, 'academico'],
      [R2, a1, ANO, 'financeiro'], [R2, a1, ANO, 'financeiro_direto'], [R2, a1, ANO, 'financeiro', `${ANO}-03-01`], [R2, a1, ANO, 'financeiro', hoje], [R2, a1, ANO, 'academico'],
      [R5, a1, ANO, 'academico'], [R5, a1, ANO, 'financeiro'], [R5, a1, ANO, 'financeiro_direto'], [R5, a1, ANTERIOR, 'academico']];
    const v3 = pode(q3);
    L.ok(filhosR1.length === 2 && filhosR1.includes(a1) && filhosR1.includes(a2) && bolR1a2 === 200 && v3[0] === true && v3[1] === false && v3[2] && v3[3],
      'acadêmico de um filho e financeiro de outro: vê o acadêmico dos dois; "pode ver o financeiro?" sim só para o segundo');
    const e2 = await entrarPortal(navegador, 'pai.barreira@exemplo.invalid', SENHA_R);
    const telasR2 = [];
    for (const rt of ROTAS.slice(0, 7)) { telasR2.push(await abre(e2.p, `familia/${rt}/${a1}?ano=${ANO}`)); }
    L.ok(v3.slice(4, 8).every((x) => x === false) && v3[8] === true && telasR2.every((x) => x.status === 200 && !DINHEIRO.test(x.texto)),
      'o casal separado: o titular vê o acadêmico e recebe "não" para o financeiro por todos os caminhos (a pergunta, a pergunta direta, qualquer data, nenhuma tela)');
    const e5 = await entrarPortal(navegador, 'avo.barreira@exemplo.invalid', SENHA_R);
    const ad1 = (await abre(e5.p, `familia/boletim/${a1}?ano=${ANO}`)).status;
    const ad2 = (await abre(e5.p, `familia/boletim/${a1}?ano=${ANTERIOR}`)).status;
    L.ok(v3[9] === true && v3[10] === false && v3[11] === false && v3[12] === false && ad1 === 200 && ad2 === 403,
      'o acesso adicional: vê o acadêmico no ano da liberação, recebe "não" para o financeiro e não vê outro ano');
    const sab3 = await sabotar(PONTO, "return in_array('financeiro', $papeis, true); // o titular e o adicional: nunca", 'return $papeis !== [];', async () => pode([[R2, a1, ANO, 'financeiro'], [R5, a1, ANO, 'financeiro'], [R1, a2, ANO, 'financeiro']]));
    L.ok(sab3.every((x) => x === true), 'sabotagem (o financeiro para qualquer papel): a prova acusa — o titular, o adicional e o titular do outro filho receberam "sim"');
    L.ok(sha(PONTO) === shas[PONTO], 'o arquivo sabotado voltou igual (sha256)');

    /* 4. a regra do acesso adicional desligada */
    const e6 = await entrarPortal(navegador, 'tia.barreira@exemplo.invalid', SENHA_R);
    const antesRegra = (await abre(e6.p, `familia/boletim/${a2}?ano=${ANO}`)).status;
    sql("INSERT INTO esc_configuracao (chave, valor) VALUES ('responsaveis.adicional', '0') ON DUPLICATE KEY UPDATE valor = '0'");
    const novaLib = await lib(a3, ANO, R6, 'Com a regra desligada, não pode (prova)');
    const criada = um(`SELECT COUNT(*) FROM esc_responsavel_designacoes WHERE aluno_id = ${a3} AND papel = 'adicional'`);
    const depoisRegra = (await abre(e6.p, `familia/boletim/${a2}?ano=${ANO}`)).status;
    const filhosR6 = pode([['filhos', R6]])[0];
    const sab4 = await sabotar(PONTO, "return $v === false || (string) $v !== '0';", 'return true;', async () => (await abre(e6.p, `familia/boletim/${a2}?ano=${ANO}`)).status);
    sql("DELETE FROM esc_configuracao WHERE chave = 'responsaveis.adicional'");
    const religada = (await abre(e6.p, `familia/boletim/${a2}?ano=${ANO}`)).status;
    await e6.ctx.close();
    L.ok(antesRegra === 200 && /não permite/.test(novaLib) && criada === 0 && depoisRegra === 403 && filhosR6.length === 0 && religada === 200,
      'a regra do adicional desligada: a liberação recusada pela tela (a planilha e a interface nunca liberam, Etapa 2); a que existia deixa de dar acesso (e volta com a regra ligada)');
    L.ok(sab4 === 200, 'sabotagem (o ponto único sem ler a regra): a prova acusa — o adicional abriu com a regra desligada');
    L.ok(sha(PONTO) === shas[PONTO], 'o arquivo sabotado voltou igual (sha256)');

    /* 5. dois financeiros direto no banco */
    const dois = `INSERT INTO esc_responsavel_designacoes (aluno_id, ano, responsavel_id, papel, inicio, criado_em) VALUES (${a1}, ${ANO}, ${R7}, 'financeiro', '${ANO}-06-01', NOW())`;
    const erroBanco = sqlErro(dois);
    const indice = sql("SHOW CREATE TABLE esc_responsavel_designacoes").includes('um_financeiro_um_titular');
    sql('ALTER TABLE esc_responsavel_designacoes DROP INDEX um_financeiro_um_titular');
    let passou = '';
    try { passou = sqlErro(dois) === '' ? 'aceito' : 'recusado'; } finally {
      sql(`DELETE FROM esc_responsavel_designacoes WHERE responsavel_id = ${R7} AND aluno_id = ${a1} AND inicio = '${ANO}-06-01'`);
      sql('ALTER TABLE esc_responsavel_designacoes ADD UNIQUE KEY `um_financeiro_um_titular` (`aluno_id`, `ano`, `vigente_unica`)');
    }
    L.ok(indice && /Duplicate|um_financeiro_um_titular/.test(erroBanco) && um(`SELECT COUNT(*) FROM esc_responsavel_designacoes WHERE aluno_id = ${a1} AND ano = ${ANO} AND papel = 'financeiro' AND fim IS NULL`) === 1,
      'dois financeiros do mesmo aluno no mesmo ano, direto no banco: recusado (o índice único)');
    L.ok(passou === 'aceito' && sql("SHOW CREATE TABLE esc_responsavel_designacoes").includes('um_financeiro_um_titular') && sqlErro(dois) !== '',
      'sabotagem (sem o índice único): a prova acusa — o banco aceitou o segundo; o índice voltou e recusa de novo');

    /* 6. o papel que muda de um ano para outro (R2: o financeiro de a1 em 2025, o titular em 2026) */
    const v6 = pode([[R2, a1, ANTERIOR, 'financeiro'], [R2, a1, ANO, 'financeiro'], [R2, a1, ANTERIOR, 'academico'], [R2, a1, ANO, 'academico'], ['anos', R2, a1]]);
    L.ok(v6[0] === true && v6[1] === false && v6[2] && v6[3] && v6[4].join(',') === `${ANO},${ANTERIOR}`, 'o papel que muda de um ano para outro: o financeiro só em 2025, o acadêmico nos dois anos');
    const sab6 = await sabotar(PONTO, 'WHERE responsavel_id = ? AND aluno_id = ? AND ano = ? AND inicio', 'WHERE responsavel_id = ? AND aluno_id = ? AND (ano = ? OR 1) AND inicio', async () => pode([[R2, a1, ANO, 'financeiro']])[0]);
    L.ok(sab6 === true, 'sabotagem (o ponto único sem o ano): a prova acusa — o financeiro de 2025 valeu em 2026');
    L.ok(sha(PONTO) === shas[PONTO], 'o arquivo sabotado voltou igual (sha256)');

    /* 7. a troca de financeiro, o papel retirado e a liberação encerrada, com a sessão aberta */
    const ontem = sql('SELECT DATE_SUB(CURDATE(), INTERVAL 1 DAY)');
    const antesTroca = (await abre(e1.p, `familia/boletim/${a1}?ano=${ANO}`)).status;
    const avisoTroca = await des(a1, ANO, 'financeiro', R7, hoje, 'Acordo entre os pais (prova)');
    const abertos = um(`SELECT COUNT(*) FROM esc_responsavel_designacoes WHERE aluno_id = ${a1} AND ano = ${ANO} AND papel = 'financeiro' AND fim IS NULL`);
    const historico = sql(`SELECT CONCAT(inicio, '|', fim) FROM esc_responsavel_designacoes WHERE aluno_id = ${a1} AND ano = ${ANO} AND papel = 'financeiro' AND responsavel_id = ${R1}`);
    const v7 = pode([[R1, a1, ANO, 'financeiro', ontem], [R1, a1, ANO, 'financeiro', hoje], [R7, a1, ANO, 'financeiro', hoje], [R7, a1, ANO, 'financeiro', ontem]]);
    const depoisTroca = (await abre(e1.p, `familia/boletim/${a1}?ano=${ANO}`)).status;
    L.ok(abertos === 1 && historico === `${ANO}-02-01|${hoje}` && v7[0] === true && v7[1] === false && v7[2] === true && v7[3] === false && antesTroca === 200 && depoisTroca === 403,
      `a troca de financeiro no meio do ano: nunca dois; o anterior tem o alcance até a véspera e o perde na data da troca (o histórico fica: ${historico}); a tela seguinte dele, recusada`);
    const antesRetirar = (await abre(e2.p, `familia/boletim/${a1}?ano=${ANO}`)).status;
    await encerrar(a1, ANO, designacao(R2, a1, ANO, 'titular'));
    const depoisRetirar = (await abre(e2.p, `familia/boletim/${a1}?ano=${ANO}`)).status;
    await encerrar(a1, ANO, designacao(R5, a1, ANO, 'adicional'));
    const depoisLib = (await abre(e5.p, `familia/boletim/${a1}?ano=${ANO}`)).status;
    L.ok(antesRetirar === 200 && depoisRetirar === 403 && depoisLib === 403, 'o papel retirado e a liberação encerrada com a sessão aberta: a tela seguinte, recusada');
    const sab7 = await sabotar(PONTO, 'AND inicio <= ? AND (fim IS NULL OR fim > ?)', 'AND inicio <= ? AND (fim IS NULL OR fim > ? OR 1)', async () => ({
      troca: pode([[R1, a1, ANO, 'financeiro', hoje]])[0], retirado: (await abre(e2.p, `familia/boletim/${a1}?ano=${ANO}`)).status }));
    L.ok(sab7.troca === true && sab7.retirado === 200, 'sabotagem (o ponto único sem a data do fim): a prova acusa — o anterior continuou financeiro e o papel retirado abriu');
    L.ok(sha(PONTO) === shas[PONTO], 'o arquivo sabotado voltou igual (sha256)');
    await e2.ctx.close();
    await e5.ctx.close();

    /* 8. o aluno numa tela de responsável; o responsável numa tela só de aluno */
    const ea = await entrarPortal(navegador, '265001', SENHA_ALUNO);
    const alunoFam = [await abre(ea.p, 'familia/index'), await abre(ea.p, `familia/boletim/${a1}?ano=${ANO}`)];
    const alunoNega = alunoFam.every((x) => !x.cab && !/Meus filhos|Ainda não há nenhum aluno liberado/.test(x.texto));
    const respAluno = [];
    for (const rt of ['moodle/entrar', 'home/boletim-aluno', 'perfil/alterar-senha', 'atestado/index']) {
      const x = await abre(e1.p, rt);
      respAluno.push(/familia\//.test(x.url) && !/moodle|perfil|atestado\/index|boletim-aluno/.test(x.url));
    }
    L.ok(alunoNega && respAluno.every(Boolean), 'o aluno numa tela de responsável e o responsável numa tela só de aluno (o Moodle, a senha, o boletim e o atestado do aluno): recusados');
    sql(`INSERT INTO col_nivacs_pgs (permissao, ordem, dropdown, lib_menu, adms_menu_id, adms_niveis_acesso_id, adms_pagina_id, created) VALUES (1, 99, 2, 2, 1, 1, 32, NOW())`);
    let sab8;
    try {
      sab8 = await sabotar(CTRL, "if ($id <= 0 || (int) ($_SESSION['academico_nivel'] ?? 0) !== 2 || !$this->acesso()->ativo($id)) {", 'if (false) {', async () => abre(ea.p, 'familia/index'));
    } finally { sql('DELETE FROM col_nivacs_pgs WHERE adms_niveis_acesso_id = 1 AND adms_pagina_id = 32'); }
    await ea.ctx.close();
    L.ok(/familia\/index/.test(sab8.url) && /Meus filhos/.test(sab8.texto), 'sabotagem (o controller sem conferir o responsável, e o nível do aluno com a página): a prova acusa — o aluno abriu a página da família');
    L.ok(sha(CTRL) === shas[CTRL] && um('SELECT COUNT(*) FROM col_nivacs_pgs WHERE adms_niveis_acesso_id = 1 AND adms_pagina_id = 32') === 0, 'o arquivo sabotado voltou igual (sha256), e a permissão sabotada saiu');

    /* 9. nada de financeiro em página nenhuma, para nenhum papel */
    const varrer = async (ses, aluno) => { const t = [await abre(ses.p, 'familia/index')]; for (const rt of ROTAS.slice(0, 7)) { t.push(await abre(ses.p, `familia/${rt}/${aluno}?ano=${ANO}`)); } return t; };
    const e7 = await entrarPortal(navegador, 'madrasta.barreira@exemplo.invalid', SENHA_R);
    const paginas = [...(await varrer(e7, a1)), ...(await varrer(e1, a2))];
    const sujas = paginas.filter((x) => DINHEIRO.test(x.texto)).map((x) => x.url.replace(L.BASE, ''));
    L.ok(paginas.length === 16 && sujas.length === 0, `nenhuma das ${paginas.length} páginas (o financeiro novo e a titular) com "financeiro", valor em dinheiro ou situação de pagamento${sujas.length ? ' — ' + sujas.join(', ') : ''}`);
    const sab9 = await sabotar(CAB, '<div class="text-secondary" id="turmaAnoFilho">', '<div class="small">Mensalidade em atraso: R$ 100,00</div><div class="text-secondary" id="turmaAnoFilho">', async () => DINHEIRO.test((await abre(e7.p, `familia/filho/${a1}?ano=${ANO}`)).texto));
    L.ok(sab9 === true, 'sabotagem (um texto de pagamento no cabeçalho do filho): a prova acusa');
    L.ok(sha(CAB) === shas[CAB], 'o arquivo sabotado voltou igual (sha256)');
    await e7.ctx.close();
    await e1.ctx.close();
    await s.ctx.close();
  } catch (err) {
    L.ok(false, 'a prova parou: ' + (err.stack || err.message).slice(0, 500));
  } finally {
    for (const [f, h] of Object.entries(shas)) { if (sha(f) !== h) { console.log('  [AVISO] o arquivo sabotado não voltou igual: ' + f); } }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
