/*
 * PROVA DA ONDA 8, ETAPA 1 — a entrada pelo campo de login (D-67).
 *   A. no ensino superior (o banco que o ciclo deixa): a entrada do aluno não muda — pelo RA (o padrão) e, com o campo
 *      "e-mail" nas Configurações, pelo e-mail do cadastro; a tela das Configurações continua com a escolha;
 *   B. na escola (a demonstração do colégio, montada pela tela; APAGA o banco de prova):
 *      1. a mãe, cujo e-mail também está no cadastro de DOIS irmãos (o mesmo e-mail nos dois): pelo convite, escolhe o
 *         e-mail como login; entra com ele e cai na conta dela; cada irmão entra com a própria matrícula e cai na dele;
 *      2. o e-mail do cadastro do aluno, digitado com a senha do aluno, não abre a conta do aluno;
 *      3. o pai escolhe o CPF no convite: entra com o CPF com e sem a pontuação; o e-mail do cadastro dele (que não é o
 *         login) não entra; "Trocar o login" na ficha: passa a entrar com o e-mail, e o CPF deixa de entrar;
 *      4. a mensagem é a mesma para o login que não existe, para a senha errada e para a matrícula com letras;
 *      5. o login repetido: a mãe troca o e-mail do cadastro (o login fica o antigo); cadastrar outra pessoa com o
 *         e-mail antigo é recusado na tela, na planilha e na interface de integração; o convite de quem só tem
 *         identificadores em uso é recusado;
 *      6. a matrícula que é um CPF válido: recusada na tela, na planilha de alunos e na interface;
 *      7. as Configurações na escola: "o número de matrícula", sem a escolha;
 *   sabotagens: (a) a entrada da escola volta a procurar o aluno pelo e-mail do cadastro — a prova acusa (o e-mail da
 *   mãe abre a conta do aluno); (b) o cadastro deixa de conferir o login de outra conta — a prova acusa (a pessoa com o
 *   e-mail que é o login de outra é cadastrada). Os arquivos voltam iguais (sha256).
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { spawnSync, execFileSync } = require('child_process');
const L = require('../lib');

const ETAPA = 'onda8/etapa1';
const SAIDA = path.join(__dirname, '..', 'saida');
const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste$/.test(BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
const SENHA = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const LOGIN = path.join(L.RAIZ, 'portal', 'app', 'adms', 'Models', 'acesso', 'AdmsLogin.php');
const MODEL = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'escola', 'AdmsResponsaveis.php');
const API = L.BASE + 'api/v1';
const ANO = 2026;
const SENHA_MAE = 'manga verde no quintal da avenida';
const SENHA_PAI = 'caqui maduro na janela de outubro';
const SENHA_ALUNO1 = 'pitanga doce no muro da escola';
const SENHA_ALUNO2 = 'goiaba vermelha no fundo do rio';
const EMAIL_FAMILIA = 'familia.irmaos@exemplo.invalid';
const CPF_PAI = '52998224725';
const CPF_RA = '39053344705'; // um CPF válido, digitado como matrícula

const sql = (q) => execFileSync('C:/xampp/mysql/bin/mysql.exe', ['-uroot', '--default-character-set=utf8mb4', '-N', '-B', BANCO, '-e', q], { encoding: 'utf8' }).trim();
const um = (q) => +(sql(q).split('\n')[0] || 0);
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const hash = (s) => execFileSync('C:/xampp/php/php.exe', ['-r', 'echo password_hash($argv[1], PASSWORD_DEFAULT);', s], { encoding: 'utf8' }).trim();
const idAluno = (ra) => um(`SELECT id FROM col_usuarios WHERE rm = '${ra}'`);
const pontuado = (c) => `${c.slice(0, 3)}.${c.slice(3, 6)}.${c.slice(6, 9)}-${c.slice(9)}`;

async function entrarPainel(navegador) {
  const ctx = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' });
  const p = await L.pagina(ctx);
  await L.confirmarSozinho(p); // D-100: "Gerar outro convite" pergunta pela janela do sistema
  await p.goto(L.BASE + 'painel/login/acesso');
  await p.fill('input[name="usuario"]', 'administrador');
  await p.fill('input[name="senha"]', process.env.SENHA_ADM || SENHA);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
  return { ctx, p };
}
/** POST como o formulário da tela; devolve os avisos da tela seguinte. */
async function postar(p, rota, campos, voltar) {
  const direto = await p.evaluate(async ([u, c]) => { const f = new URLSearchParams(); for (const [k, v] of Object.entries(c)) { f.append(k, v); }
    const r = await fetch(u, { method: 'POST', body: f, credentials: 'same-origin', redirect: 'manual' });
    if (r.type === 'opaqueredirect' || r.status >= 300) { return ''; }
    const d = new DOMParser().parseFromString(await r.text(), 'text/html');
    return Array.from(d.querySelectorAll('.alert')).map((e) => e.textContent.replace(/\s+/g, ' ').trim()).join(' | '); }, [L.BASE + 'painel/' + rota, campos]);
  await p.goto(L.BASE + 'painel/' + voltar);
  const depois = await p.$$eval('.alert', (l) => l.map((e) => e.innerText.replace(/\s+/g, ' ').trim()).join(' | ')).catch(() => '');
  return [direto, depois].filter(Boolean).join(' | ');
}
/** A entrada no portal: devolve a página, o endereço e o aviso de falha. */
async function entrarPortal(navegador, usuario, senha) {
  const ctx = await navegador.newContext({ viewport: { width: 390, height: 844 }, locale: 'pt-BR' });
  const p = await L.pagina(ctx);
  await p.goto(L.BASE + 'portal/login/acesso');
  await p.fill('input[name="usuario"]', usuario);
  await p.fill('input[name="senha"]', senha);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
  const falha = await p.textContent('#loginFalha').catch(() => '');
  return { ctx, p, url: p.url(), falha: (falha || '').replace(/\s+/g, ' ').replace(/×/g, '').trim() };
}
/** Quem entrou: 'familia' (a conta do responsável), o RA do aluno (pelo primeiro nome no menu), ou '' (não entrou). */
async function quem(e) {
  if (/familia\/(index|filho)/.test(e.url)) { return 'familia'; }
  if (await e.p.$('[name="SendLogin"]')) { return ''; }
  const nome = ((await e.p.textContent('#usuario-logado').catch(() => '')) || '').trim();
  for (const ra of ['265001', '265002', '260101']) {
    if (nome && sql(`SELECT nome FROM col_usuarios WHERE rm = '${ra}'`).split(' ')[0] === nome) { return ra; }
  }
  return 'aluno?';
}
async function entrouComo(navegador, usuario, senha) {
  const e = await entrarPortal(navegador, usuario, senha);
  const q = await quem(e);
  await e.ctx.close();
  return { q, falha: e.falha };
}
/** O convite pela ficha (o botão) → o link; um navegador sem sessão o abre, escolhe o login e cria a senha. */
async function convite(navegador, p, pessoa, escolha, senha) {
  await p.goto(L.BASE + 'painel/responsaveis/ver/' + pessoa);
  await Promise.all([p.waitForNavigation(), p.click('#gerarConvite')]);
  const link = await p.inputValue('#linkConvite').catch(() => '');
  const ctx = await navegador.newContext({ viewport: { width: 390, height: 844 }, locale: 'pt-BR' });
  const v = await L.pagina(ctx);
  await v.goto(link);
  await v.waitForSelector('#conviteValido', { timeout: 15000 });
  const opcoes = await v.$$eval('#escolhaLogin input[name="login_tipo"]', (l) => l.map((i) => i.value)).catch(() => []);
  if (escolha) { await v.check(`#escolhaLogin input[value="${escolha}"]`); }
  await v.fill('#senha', senha);
  await v.fill('#senha_repetido', senha);
  await Promise.all([v.waitForNavigation(), v.click('#criarSenha')]);
  const aviso = await v.textContent('#senhaCriada').catch(() => '');
  await ctx.close();
  return { opcoes, aviso: (aviso || '').replace(/\s+/g, ' ') };
}
function criarChave(permissoes) {
  const chave = 'vce_' + crypto.randomBytes(20).toString('hex');
  sql(`INSERT INTO adms_api_chaves (nome, prefixo, hash, escopo, permissoes, situacao, usuario_id) VALUES ('Prova login', '${chave.slice(0, 12)}', '${crypto.createHash('sha256').update(chave).digest('hex')}', 'escrita', '${permissoes}', 1, 1)`);
  return chave;
}
async function api(metodo, rota, chave, corpo) {
  const r = await fetch(API + rota, { method: metodo, headers: { Authorization: 'Bearer ' + chave, 'Content-Type': 'application/json' }, body: corpo ? JSON.stringify(corpo) : undefined });
  let j = null;
  try { j = await r.json(); } catch (e) { /* */ }
  return { status: r.status, j };
}
/** A planilha dos responsáveis, conferida pela tela: as linhas em vermelho. */
async function planilhaResp(p, csv) {
  const arq = path.join(SAIDA, 'onda8-etapa1-responsaveis.csv');
  fs.writeFileSync(arq, '\ufeff' + csv);
  await p.goto(L.BASE + 'painel/responsaveis/importar');
  await p.setInputFiles('#planilha', arq);
  await Promise.all([p.waitForNavigation(), p.click('#conferirPlanilha')]);
  return p.$$eval('#conferenciaPlanilha tr.table-danger', (l) => l.map((t) => t.innerText.replace(/\s+/g, ' '))).catch(() => []);
}
/** A planilha de alunos, enviada pela tela: o resultado e a mensagem da primeira linha de dados (a linha 2 do arquivo). */
async function planilhaAlunos(p, csv) {
  await p.goto(L.BASE + 'painel/importacao-alunos/listar');
  await p.setInputFiles('#arquivoImportacao', { name: 'onda8-alunos.csv', mimeType: 'text/csv', buffer: Buffer.from('\ufeff' + csv, 'utf8') });
  await Promise.all([p.waitForNavigation(), p.click('#enviarImportacao')]);
  return { res: await p.getAttribute('#linha-2', 'data-resultado').catch(() => null), msg: await p.$eval('#linha-2', (e) => e.innerText.replace(/\s+/g, ' ')).catch(() => '') };
}

(async () => {
  const { navegador } = await L.abrir();
  const shaLogin = sha(LOGIN);
  const shaModel = sha(MODEL);
  try {
    console.log('\n== Onda 8, Etapa 1 — a entrada pelo campo de login');
    /* A. o ensino superior */
    const cred = JSON.parse(fs.readFileSync(path.join(SAIDA, 'credenciais.json'), 'utf8'));
    process.env.SENHA_ADM = cred.painel.administrador;
    const campoAntes = sql("SELECT valor FROM adms_parametros WHERE chave = 'portal.login.campo'");
    const sup = idAluno('260101');
    const emailSup = sql(`SELECT IFNULL(email, '') FROM col_usuarios WHERE id = ${sup}`);
    sql(`UPDATE col_usuarios SET senha = '${hash(SENHA_ALUNO1)}', email = 'aluno.superior@exemplo.invalid' WHERE id = ${sup}`);
    const pelaRa = await entrouComo(navegador, '260101', SENHA_ALUNO1);
    sql("UPDATE adms_parametros SET valor = 'email' WHERE chave = 'portal.login.campo'");
    const e1 = await entrarPortal(navegador, 'aluno.superior@exemplo.invalid', SENHA_ALUNO1);
    const pelaEmail = !(await e1.p.$('[name="SendLogin"]')) && !/familia/.test(e1.url);
    await e1.ctx.close();
    sql(`UPDATE adms_parametros SET valor = '${campoAntes}' WHERE chave = 'portal.login.campo'`);
    sql(`UPDATE col_usuarios SET email = ${emailSup ? `'${emailSup}'` : 'NULL'} WHERE id = ${sup}`);
    let s = await entrarPainel(navegador);
    await s.p.goto(L.BASE + 'painel/parametros/listar');
    const escolhaSup = !!(await s.p.$('#portal_login_campo')) && !(await s.p.$('#loginEscola'));
    await s.ctx.close();
    L.ok(pelaRa.q !== '' && pelaEmail && escolhaSup,
      'no ensino superior, a entrada do aluno não muda: pelo RA; com o campo "e-mail" nas Configurações, pelo e-mail do cadastro; a tela continua com a escolha');
    delete process.env.SENHA_ADM;

    /* B. a escola */
    const r = spawnSync(process.execPath, [path.join(__dirname, '..', 'onda6', 'medir_demo.js'), 'escola', 'onda8-etapa1'], { encoding: 'utf8', maxBuffer: 1 << 24 });
    L.ok(r.status === 0, 'a demonstração do colégio montada pela tela');
    s = await entrarPainel(navegador);
    const p = s.p;
    const a1 = idAluno('265001');
    const a2 = idAluno('265002');
    /* os dois irmãos com o MESMO e-mail no cadastro (o da mãe) e a senha de cada um */
    sql(`UPDATE col_usuarios SET email = '${EMAIL_FAMILIA}', senha = '${hash(SENHA_ALUNO1)}' WHERE id = ${a1}`);
    sql(`UPDATE col_usuarios SET email = '${EMAIL_FAMILIA}', senha = '${hash(SENHA_ALUNO2)}' WHERE id = ${a2}`);
    const irmaos = um(`SELECT COUNT(*) FROM col_usuarios WHERE email = '${EMAIL_FAMILIA}'`);
    /* a mãe (só o e-mail) e o pai (o e-mail e o CPF), titulares dos dois */
    const msgMae = await postar(p, 'responsaveis/cadastrar', { nome: 'Mãe Irmãos Exemplo', email: EMAIL_FAMILIA, celular: '', cpf: '' }, 'responsaveis/listar');
    const msgPai = await postar(p, 'responsaveis/cadastrar', { nome: 'Pai Irmãos Exemplo', email: 'pai.irmaos@exemplo.invalid', celular: '', cpf: pontuado(CPF_PAI) }, 'responsaveis/listar');
    const mae = um(`SELECT id FROM esc_responsaveis WHERE email = '${EMAIL_FAMILIA}'`);
    const pai = um(`SELECT id FROM esc_responsaveis WHERE cpf = '${CPF_PAI}'`);
    if (!mae || !pai) { throw new Error(`o cadastro da mãe ou do pai pela tela foi recusado: ${mae ? '' : 'mãe: ' + msgMae} ${pai ? '' : 'pai: ' + msgPai}`); }
    for (const [aluno, pessoa, papel] of [[a1, mae, 'titular'], [a2, mae, 'titular'], [a1, pai, 'financeiro'], [a2, pai, 'financeiro']]) {
      /* desde a D-99, a demonstração já tem o responsável financeiro dos dois: a troca pede o motivo, e a data dela vem depois do início do atual (02/02) */
      await postar(p, 'responsaveis/designar', { aluno: String(aluno), ano: String(ANO), papel, responsavel_id: String(pessoa), parentesco: pessoa === mae ? 'Mãe' : 'Pai', data: `${ANO}-03-01`, motivo: 'A família trocou o responsável (prova)' }, `responsaveis/aluno/${aluno}?ano=${ANO}`);
    }
    const cm = await convite(navegador, p, mae, '', SENHA_MAE);
    const cp = await convite(navegador, p, pai, 'cpf', SENHA_PAI);
    const loginMae = sql(`SELECT IFNULL(login, '') FROM esc_responsaveis WHERE id = ${mae}`);
    const loginPai = sql(`SELECT IFNULL(login, '') FROM esc_responsaveis WHERE id = ${pai}`);
    L.ok(irmaos === 2 && cm.opcoes.length === 0 && loginMae === EMAIL_FAMILIA && cp.opcoes.join(',') === 'email,cpf' && loginPai === CPF_PAI && /login/.test(cp.aviso),
      `o convite define o login: a mãe (só o e-mail) fica com o e-mail; o pai escolhe entre o e-mail e o CPF e fica com o CPF; os dois irmãos com o mesmo e-mail no cadastro (${irmaos})`);

    /* 1 e 2. quem entra com o quê */
    const m = await entrouComo(navegador, EMAIL_FAMILIA, SENHA_MAE);
    const f1 = await entrouComo(navegador, '265001', SENHA_ALUNO1);
    const f2 = await entrouComo(navegador, '265002', SENHA_ALUNO2);
    const emailAluno = await entrouComo(navegador, EMAIL_FAMILIA, SENHA_ALUNO1);
    L.ok(m.q === 'familia' && f1.q === '265001' && f2.q === '265002',
      `o e-mail da mãe (também no cadastro dos dois irmãos) abre a conta dela (${m.q}); cada irmão entra com a própria matrícula e cai na dele (${f1.q}, ${f2.q})`);
    L.ok(emailAluno.q === '' && /Usuário ou senha incorretos/.test(emailAluno.falha), 'o e-mail do cadastro do aluno, digitado com a senha do aluno, não abre a conta do aluno');

    /* 3. o CPF do pai */
    const cpfLimpo = await entrouComo(navegador, CPF_PAI, SENHA_PAI);
    const cpfPontuado = await entrouComo(navegador, pontuado(CPF_PAI), SENHA_PAI);
    const emailPai = await entrouComo(navegador, 'pai.irmaos@exemplo.invalid', SENHA_PAI);
    L.ok(cpfLimpo.q === 'familia' && cpfPontuado.q === 'familia' && emailPai.q === '',
      'o pai, que escolheu o CPF: entra com o CPF sem e com a pontuação; o e-mail do cadastro dele, que não é o login, não entra');
    await p.goto(L.BASE + 'painel/responsaveis/ver/' + pai);
    const mostrado = await p.textContent('#loginPortal').catch(() => '');
    await L.print(p, ETAPA, 'ficha-login-do-portal');
    const troca = await postar(p, 'responsaveis/login', { responsavel_id: String(pai), login_tipo: 'email' }, 'responsaveis/ver/' + pai);
    const depoisEmail = await entrouComo(navegador, 'pai.irmaos@exemplo.invalid', SENHA_PAI);
    const depoisCpf = await entrouComo(navegador, CPF_PAI, SENHA_PAI);
    L.ok(/529\.982\.247-25/.test(mostrado) && /Login do portal gravado/.test(troca) && depoisEmail.q === 'familia' && depoisCpf.q === ''
      && um(`SELECT COUNT(*) FROM esc_responsavel_auditoria WHERE responsavel_id = ${pai} AND acao = 'login_trocado'`) === 1,
      'a ficha mostra o login (o CPF com a pontuação); "Trocar o login" para o e-mail: entra com o e-mail, o CPF deixa de entrar; a troca fica no registro');

    /* 4. a mesma mensagem */
    const naoExiste = await entrouComo(navegador, '999999', SENHA_ALUNO1);
    const senhaErrada = await entrouComo(navegador, '265001', 'senha errada de proposito aqui');
    const comLetras = await entrouComo(navegador, 'ra265001', SENHA_ALUNO1);
    const cpfSemConta = await entrouComo(navegador, pontuado(CPF_RA), SENHA_ALUNO1);
    const msgs = [naoExiste, senhaErrada, comLetras, cpfSemConta].map((x) => x.falha);
    L.ok(msgs.every((x) => x !== '' && x === msgs[0]) && [naoExiste, senhaErrada, comLetras, cpfSemConta].every((x) => x.q === ''),
      `a mesma mensagem para o login que não existe, a senha errada, a matrícula com letras e o CPF sem conta ("${msgs[0].slice(0, 40)}")`);

    /* 5. o login repetido: a mãe troca o e-mail do cadastro; o antigo continua o login dela */
    await postar(p, 'responsaveis/editar/' + mae, { nome: 'Mãe Irmãos Exemplo', email: 'mae.nova@exemplo.invalid', celular: '', cpf: '' }, 'responsaveis/ver/' + mae);
    const continua = (await entrouComo(navegador, EMAIL_FAMILIA, SENHA_MAE)).q === 'familia';
    const naTela = await postar(p, 'responsaveis/cadastrar', { nome: 'Outra Pessoa Exemplo', email: EMAIL_FAMILIA, celular: '', cpf: '' }, 'responsaveis/listar');
    const naPlanilha = await planilhaResp(p, `ra;ano;papel;nome;email;celular;cpf;parentesco;motivo\n265003;${ANO};titular;Outra Pessoa Exemplo;${EMAIL_FAMILIA};;;Tia;\n`);
    const chave = criarChave('responsaveis:gravar alunos:gravar');
    const naApi = await api('POST', '/alunos/265003/responsaveis', chave, { ano: ANO, papel: 'titular', responsavel: { nome: 'Outra Pessoa Exemplo', email: EMAIL_FAMILIA, parentesco: 'Tia' } });
    const ninguem = um(`SELECT COUNT(*) FROM esc_responsaveis WHERE email = '${EMAIL_FAMILIA}'`);
    L.ok(continua && /já é o login de outro responsável/.test(naTela) && naPlanilha.length === 1 && /já é o login de outro responsável/.test(naPlanilha[0])
      && naApi.status === 422 && /já é o login/.test(JSON.stringify(naApi.j)) && ninguem === 0,
      `o login repetido (o e-mail antigo da mãe, que continua o login dela): recusado na tela, na planilha e na interface (${naApi.status}); ninguém gravado`);
    /* o convite de quem só tem identificadores em uso */
    sql(`INSERT INTO esc_responsaveis (nome, email, criado_em) VALUES ('Pessoa Sem Login Livre', 'livre.nao@exemplo.invalid', NOW())`);
    const semLivre = um("SELECT id FROM esc_responsaveis WHERE email = 'livre.nao@exemplo.invalid'");
    sql(`UPDATE esc_responsaveis SET login = 'livre.nao@exemplo.invalid' WHERE id = ${mae}`);
    sql(`INSERT INTO esc_responsavel_designacoes (aluno_id, ano, responsavel_id, papel, inicio, motivo, origem, criado_em) VALUES (${a2}, ${ANO}, ${semLivre}, 'adicional', '${ANO}-03-01', 'Prova do login', 'tela', NOW())`);
    const convRecusado = await postar(p, 'responsaveis/convidar', { responsavel_id: String(semLivre) }, 'responsaveis/ver/' + semLivre);
    sql(`UPDATE esc_responsaveis SET login = '${EMAIL_FAMILIA}' WHERE id = ${mae}`);
    L.ok(/já são o login de outra conta/.test(convRecusado) && um(`SELECT COUNT(*) FROM esc_responsavel_convites WHERE responsavel_id = ${semLivre}`) === 0,
      'o convite de quem tem o e-mail que já é o login de outra conta: recusado, sem convite gerado');

    /* 6. a matrícula que é um CPF válido */
    await p.goto(L.BASE + 'painel/academicos/cadastrar');
    const limite = await p.getAttribute('#rm', 'maxlength'); // o campo da tela já não aceita mais de 9 dígitos
    await p.evaluate(([v]) => { const c = document.getElementById('rm'); c.removeAttribute('maxlength'); c.removeAttribute('pattern'); c.value = v; }, [CPF_RA]); // o envio forjado
    await p.fill('#nome', 'Aluno Matricula Cpf Exemplo');
    await p.selectOption('#genero_id', '1');
    await Promise.all([p.waitForNavigation(), p.click('input[name="CadUsuarioAcad"]')]);
    const tela = await p.$eval('.alert', (e) => e.textContent.replace(/\s+/g, ' ')).catch(() => '');
    const pl = await planilhaAlunos(p, `RA;Nome;CPF;E-mail;Nascimento;Sexo\n${pontuado(CPF_RA)};Aluno Matricula Cpf Exemplo;;;01/02/2014;M\n`);
    const ap = await api('POST', '/alunos', chave, { ra: CPF_RA, nome: 'Aluno Matricula Cpf Exemplo', sexo: 'masculino' });
    const gravados = um(`SELECT COUNT(*) FROM col_usuarios WHERE nome = 'Aluno Matricula Cpf Exemplo'`);
    L.ok(limite === '9' && /não pode ser um CPF/.test(tela) && pl.res === '3' && /não pode ser um CPF/.test(pl.msg) && ap.status === 422 && /CPF/.test(JSON.stringify(ap.j)) && gravados === 0,
      `a matrícula que é um CPF válido: o campo da tela aceita até ${limite} dígitos; o envio forjado, a planilha de alunos (${pl.res}) e a interface (${ap.status}), recusados com o porquê; nada gravado (${gravados})`
      + (/não pode ser um CPF/.test(tela) ? '' : ` — tela: "${tela.slice(0, 120)}"`) + (/não pode ser um CPF/.test(pl.msg) ? '' : ` — planilha: "${pl.msg.slice(0, 160)}"`) + (/CPF/.test(JSON.stringify(ap.j)) ? "" : ` — api: ${JSON.stringify(ap.j).slice(0, 160)}`));
    /* o e-mail repetido entre irmãos, aceito pela tela da escola */
    await p.goto(L.BASE + 'painel/academicos/cadastrar');
    await p.fill('#rm', '265091');
    await p.fill('#nome', 'Irmao Caçula Exemplo');
    await p.selectOption('#genero_id', '1');
    await p.fill('#email', EMAIL_FAMILIA);
    await Promise.all([p.waitForNavigation(), p.click('input[name="CadUsuarioAcad"]')]);
    L.ok(um(`SELECT COUNT(*) FROM col_usuarios WHERE email = '${EMAIL_FAMILIA}'`) === 3, 'na escola, a tela aceita o terceiro irmão com o mesmo e-mail no cadastro (o e-mail não é login)');

    /* 7. as Configurações na escola */
    await p.goto(L.BASE + 'painel/parametros/listar');
    const fixo = await p.textContent('#loginEscola').catch(() => '');
    L.ok(/número de matrícula/.test(fixo) && !(await p.$('#portal_login_campo')), 'as Configurações na escola: o aluno entra com "o número de matrícula", sem a escolha');
    const ent = await entrarPortal(navegador, '265001', SENHA_ALUNO1);
    await ent.p.goto(L.BASE + 'portal/login/acesso');
    await ent.ctx.close();
    const vis = await navegador.newContext({ viewport: { width: 390, height: 844 }, locale: 'pt-BR' });
    const pv = await L.pagina(vis);
    await pv.goto(L.BASE + 'portal/login/acesso');
    const dica = await pv.textContent('#dicaResponsavel').catch(() => '');
    await L.print(pv, ETAPA, 'entrada-do-portal-escola');
    await vis.close();
    L.ok(/número de matrícula/.test(dica) && /CPF ou o e-mail/.test(dica), `a dica da entrada do portal: "${dica.trim()}"`);

    /* sabotagem (a): a entrada da escola volta a procurar o aluno pelo e-mail do cadastro */
    const origLogin = fs.readFileSync(LOGIN, 'utf8');
    const de = "if (\\VcLoginPortal::comoCpf($digitado) !== null || str_contains($digitado, '@')) {";
    if (origLogin.split(de).length !== 2) { throw new Error('sabotagem (a) sem o trecho'); }
    let sabA = '';
    fs.writeFileSync(LOGIN, origLogin.replace(de, "if (($alunoEmail = AdmsIdentificaAluno::buscar('email', $digitado))) { $this->resultUser = [$alunoEmail]; $this->dadosUsuarios = $alunoEmail; $this->validarSenha(false); return; }\n        " + de));
    try { sabA = (await entrouComo(navegador, EMAIL_FAMILIA, SENHA_ALUNO1)).q; } finally { fs.writeFileSync(LOGIN, origLogin); }
    L.ok(/^26500\d$/.test(sabA), `sabotagem (a entrada procura o aluno pelo e-mail do cadastro): a prova acusa — o e-mail abriu a conta do aluno ${sabA}`);
    /* sabotagem (b): o cadastro deixa de conferir o login de outra conta */
    const origModel = fs.readFileSync(MODEL, 'utf8');
    const de2 = 'if ($recusa = self::loginDeOutro($c, 0)) {';
    if (origModel.split(de2).length !== 2) { throw new Error('sabotagem (b) sem o trecho'); }
    let sabB = 0;
    fs.writeFileSync(MODEL, origModel.replace(de2, 'if (false && ($recusa = self::loginDeOutro($c, 0))) {'));
    try {
      await postar(p, 'responsaveis/cadastrar', { nome: 'Outra Pessoa Exemplo', email: EMAIL_FAMILIA, celular: '', cpf: '' }, 'responsaveis/listar');
      sabB = um(`SELECT COUNT(*) FROM esc_responsaveis WHERE email = '${EMAIL_FAMILIA}'`);
    } finally { fs.writeFileSync(MODEL, origModel); sql(`DELETE FROM esc_responsaveis WHERE email = '${EMAIL_FAMILIA}' AND id <> ${mae}`); }
    L.ok(sabB === 1, 'sabotagem (o cadastro não confere o login de outra conta): a prova acusa — a pessoa com o e-mail que é o login da mãe foi cadastrada');
    L.ok(sha(LOGIN) === shaLogin && sha(MODEL) === shaModel, 'os arquivos sabotados voltaram iguais (sha256)');
    await s.ctx.close();
  } catch (e) {
    L.ok(false, 'a prova parou: ' + (e.stack || e.message).slice(0, 500));
  } finally {
    if (sha(LOGIN) !== shaLogin || sha(MODEL) !== shaModel) { console.error('ATENÇÃO: arquivo sabotado diferente do original'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
