/*
 * PROVA DA ONDA 7, ETAPA 3 — o convite e o primeiro acesso do responsável (D-63).
 *   A. no ensino superior (o banco que o ciclo deixa): a página do convite não abre (volta à entrada), a entrada do portal
 *      não fala de responsável e as rotas do convite no painel não existem;
 *   B. na escola (a demonstração do colégio, montada pela tela; APAGA o banco de prova):
 *      1. gerar o convite pela ficha: o link aparece uma vez, para quem gerou; no banco, só o resumo (sha256), o código
 *         em lugar nenhum (nem na auditoria); a mensagem pronta e o WhatsApp para o celular do cadastro; sem envio de
 *         e-mail configurado, a tela diz isso;
 *      2. gerar de novo: o link anterior deixa de valer;
 *      3. pelo link: a pessoa confere o nome, vê só o PRIMEIRO nome do aluno, a senha fraca é recusada, a boa cria a
 *         senha e entra (os filhos); o mesmo link usado de novo, o vencido e o inventado mostram a MESMA página, sem
 *         dizer se a pessoa existe;
 *      4. a entrada: pelo e-mail e pelo CPF, com a senha; a senha errada, a mensagem genérica; o responsável que abre a
 *         página do aluno volta aos filhos;
 *      5. cancelar o convite (o link deixa de valer) e bloquear o acesso (não entra; a sessão aberta cai na próxima
 *         página); desbloquear;
 *      6. "Esqueci a senha": sem o envio configurado, nada é gerado e a resposta é a mesma; a dica para quem não tem
 *         e-mail; o link de senha nova (tipo "senha") abre a mesma página e troca a senha;
 *      7. os convites de uma turma, em passos: cada pendente ganha o convite; sem e-mail configurado, todos na lista
 *         do envio manual, com o link;
 *      8. o registro: gerado, usado, cancelado, bloqueio, na auditoria; o limite de tentativas no uso do convite;
 *      9. gravação só por POST: o GET de "convidar" não gera nada;
 *   sabotagem: o convite deixa de conferir se já foi usado — a prova acusa (o link usado volta a valer); o arquivo
 *   volta igual (sha256).
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { spawnSync, execFileSync } = require('child_process');
const L = require('../lib');

const ETAPA = 'onda7/etapa3';
const SAIDA = path.join(__dirname, '..', 'saida');
const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste$/.test(BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
const SENHA = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const CONTA = path.join(L.RAIZ, 'core', 'ContaFamilia.php');
const ANO = 2026;
const SENHA_BOA = 'girassol no quintal azul de domingo'; // sem pedaço do nome, do e-mail ou do CPF (a regra das senhas recusa)
const SENHA_NOVA = 'outra frase longa para o portal';

const sql = (q) => execFileSync('C:/xampp/mysql/bin/mysql.exe', ['-uroot', '--default-character-set=utf8mb4', '-N', '-B', BANCO, '-e', q], { encoding: 'utf8' }).trim();
const um = (q) => +(sql(q).split('\n')[0] || 0);
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const idAluno = (ra) => um(`SELECT id FROM col_usuarios WHERE rm = '${ra}'`);
const texto = (p) => p.evaluate(() => document.body.innerText.replace(/\s+/g, ' '));

async function entrarPainel(navegador) {
  const ctx = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' });
  const p = await L.pagina(ctx);
  p.on('dialog', (d) => d.accept().catch(() => {})); // "O convite anterior deixa de valer. Gerar outro?"
  await L.confirmarSozinho(p); // D-100: a confirmação é a janela do sistema
  await p.goto(L.BASE + 'painel/login/acesso');
  await p.fill('input[name="usuario"]', 'administrador');
  await p.fill('input[name="senha"]', process.env.SENHA_ADM || SENHA);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
  return { ctx, p };
}
/** POST como o formulário da tela (mesma origem). */
async function postar(p, rota, campos, voltar) {
  await p.evaluate(async ([u, c]) => { const f = new URLSearchParams(); for (const [k, v] of Object.entries(c)) { f.append(k, v); }
    await fetch(u, { method: 'POST', body: f, credentials: 'same-origin', redirect: 'manual' }); }, [L.BASE + 'painel/' + rota, campos]);
  await p.goto(L.BASE + 'painel/' + voltar);
  return p.$$eval('.alert', (l) => l.map((e) => e.innerText.replace(/\s+/g, ' ').trim()).join(' | ')).catch(() => '');
}
/** Gera o convite pela ficha (o botão) e devolve o link que a tela mostra. */
async function gerar(p, pessoa) {
  await p.goto(L.BASE + 'painel/responsaveis/ver/' + pessoa);
  await Promise.all([p.waitForNavigation(), p.click('#gerarConvite')]);
  return p.inputValue('#linkConvite').catch(() => '');
}
/** Um navegador novo (sem sessão) abre o link do convite: devolve a página. */
async function abrirLink(navegador, link, largura = 390) {
  const ctx = await navegador.newContext({ viewport: { width: largura, height: 844 }, locale: 'pt-BR' });
  const p = await L.pagina(ctx);
  await p.goto(link);
  await p.waitForSelector('#conviteValido, #conviteInvalido', { timeout: 15000 }).catch(() => {});
  return { ctx, p };
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
/** O texto da página do link que não vale (sem o que muda de uma vez para outra). */
const paginaInvalida = async (p) => (await p.$('#conviteInvalido')) ? (await p.innerText('#conviteInvalido')).replace(/\s+/g, ' ') : '';

(async () => {
  const { navegador } = await L.abrir();
  const shaConta = sha(CONTA);
  try {
    console.log('\n== Onda 7, Etapa 3 — o convite e o primeiro acesso do responsável');
    /* A. o ensino superior */
    const cred = JSON.parse(fs.readFileSync(path.join(SAIDA, 'credenciais.json'), 'utf8'));
    process.env.SENHA_ADM = cred.painel.administrador;
    let s = await entrarPainel(navegador);
    const vis = await navegador.newContext({ viewport: { width: 390, height: 844 }, locale: 'pt-BR' });
    const pv = await L.pagina(vis);
    await pv.goto(L.BASE + 'portal/convite/abrir#c=' + 'a'.repeat(48));
    const urlSup = pv.url();
    await pv.goto(L.BASE + 'portal/login/acesso');
    const dicaSup = !!(await pv.$('#dicaResponsavel'));
    await vis.close();
    await s.p.goto(L.BASE + 'painel/responsaveis/convidar-turma');
    const turmaSup = !!(await s.p.$('#escolherTurma'));
    L.ok(/login\/acesso/.test(urlSup) && !dicaSup && !turmaSup, 'no ensino superior: a página do convite volta à entrada, a entrada não fala de responsável, e o convite da turma não existe');
    await s.ctx.close();
    delete process.env.SENHA_ADM;

    /* B. a escola */
    const r = spawnSync(process.execPath, [path.join(__dirname, '..', 'onda6', 'medir_demo.js'), 'escola', 'onda7-etapa3'], { encoding: 'utf8', maxBuffer: 1 << 24 });
    require('./_antes_d99')(); // D-99: os responsáveis como antes do financeiro refeito (os alunos sem responsável, as pessoas sem celular)
    L.ok(r.status === 0 && um("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'esc_responsavel_convites'") === 1,
      'a demonstração do colégio montada pela tela, com a tabela dos convites');
    sql("DELETE FROM adms_confs_emails"); // a prova roda sem envio de e-mail configurado (o caso de quem ainda não configurou)
    s = await entrarPainel(navegador);
    const p = s.p;
    const a1 = idAluno('265001');
    const nomeA1 = sql(`SELECT IFNULL(NULLIF(nome_social, ''), nome) FROM col_usuarios WHERE id = ${a1}`);
    const primeiro = nomeA1.split(' ')[0];
    const sobrenome = nomeA1.split(' ').slice(-1)[0];

    /* a pessoa e o papel (pela tela da Etapa 2) */
    await postar(p, 'responsaveis/cadastrar', { nome: 'Mãe Convite Exemplo', email: 'mae.convite@exemplo.invalid', celular: '(11) 98888-0101', cpf: '' }, 'responsaveis/listar');
    const pA = um("SELECT id FROM esc_responsaveis WHERE email = 'mae.convite@exemplo.invalid'");
    await postar(p, 'responsaveis/designar', { aluno: String(a1), ano: String(ANO), papel: 'financeiro', responsavel_id: String(pA), parentesco: 'Mãe', data: `${ANO}-02-01` }, `responsaveis/aluno/${a1}?ano=${ANO}`);

    /* 9. o GET não grava */
    await p.goto(L.BASE + 'painel/responsaveis/convidar?responsavel_id=' + pA);
    L.ok(um(`SELECT COUNT(*) FROM esc_responsavel_convites WHERE responsavel_id = ${pA}`) === 0, 'gravação só por POST: abrir "convidar" pelo endereço não gera convite');

    /* 1. gerar */
    const link1 = await gerar(p, pA);
    const cod1 = (/#c=([a-f0-9]{48})$/.exec(link1) || [])[1] || '';
    const wa = await p.getAttribute('#abrirWhatsapp', 'href').catch(() => '');
    const msg = await p.inputValue('#textoConvite').catch(() => '');
    const semEnvio = await p.textContent('#semEnvio').catch(() => '');
    const noBanco = sql(`SELECT CONCAT_WS('|', resumo, tipo, expira_em) FROM esc_responsavel_convites WHERE responsavel_id = ${pA}`);
    const vazou = um(`SELECT (SELECT COUNT(*) FROM esc_responsavel_convites WHERE resumo = '${cod1}' OR CONCAT_WS('|', tipo, criado_em) LIKE '%${cod1}%')
                      + (SELECT COUNT(*) FROM esc_responsavel_auditoria WHERE CONCAT_WS('|', depois, antes, motivo) LIKE '%${cod1}%')`);
    const dias = Math.round((new Date(sql(`SELECT expira_em FROM esc_responsavel_convites WHERE responsavel_id = ${pA}`).replace(' ', 'T')) - Date.now()) / 86400000);
    L.ok(cod1 && noBanco.startsWith(crypto.createHash('sha256').update(cod1).digest('hex')) && vazou === 0 && dias === 7,
      'gerar: o link de uso único aparece na ficha; no banco só o resumo (sha256) e a validade de 7 dias; o código em lugar nenhum, nem na auditoria');
    L.ok(/^https:\/\/wa\.me\/5511988880101\?text=/.test(wa) && msg.includes(link1) && /Mãe,|Olá, Mãe/.test(msg) && msg.includes(primeiro) && !msg.includes(sobrenome)
      && /não está configurado/.test(semEnvio),
      `a mensagem pronta (o primeiro nome, o aluno, o link) e o WhatsApp para o celular do cadastro; sem o envio de e-mail, a tela diz ("${semEnvio.trim().slice(0, 60)}")`);
    await L.print(p, ETAPA, 'convite-gerado');
    await p.goto(L.BASE + 'painel/responsaveis/ver/' + pA);
    const situacao = await p.getAttribute('#situacaoAcesso', 'data-situacao');

    /* 2. gerar de novo */
    const link2 = await gerar(p, pA);
    let v = await abrirLink(navegador, link1);
    const velho = await paginaInvalida(v.p);
    await v.ctx.close();
    L.ok(situacao === 'convite' && link2 && link2 !== link1 && /não vale mais/.test(velho) && um(`SELECT COUNT(*) FROM esc_responsavel_convites WHERE responsavel_id = ${pA} AND cancelado_em IS NULL AND usado_em IS NULL`) === 1,
      'gerar de novo: o link anterior deixa de valer (a página do link que não vale); um só em aberto');

    /* 3. pelo link */
    v = await abrirLink(navegador, link2);
    const tela = await texto(v.p);
    const urlLimpa = !/#c=/.test(v.p.url());
    await L.print(v.p, ETAPA, 'convite-celular');
    await v.p.fill('#senha', 'curta');
    await v.p.fill('#senha_repetido', 'curta');
    await Promise.all([v.p.waitForNavigation(), v.p.click('#criarSenha')]);
    const fraca = await v.p.textContent('#erroSenha').catch(() => '');
    await v.p.fill('#senha', SENHA_BOA);
    await v.p.fill('#senha_repetido', SENHA_BOA);
    await Promise.all([v.p.waitForNavigation(), v.p.click('#criarSenha')]);
    const filhos = await v.p.$$eval("#listaFilhos [data-aluno], #cabecalhoFilho[data-aluno]", (l) => l.map((x) => +x.getAttribute('data-aluno'))).catch(() => []);
    const ativo = um(`SELECT COUNT(*) FROM esc_responsaveis WHERE id = ${pA} AND senha IS NOT NULL AND ativado_em IS NOT NULL`);
    L.ok(/Mãe Convite Exemplo/.test(tela) && tela.includes(primeiro) && !tela.includes(sobrenome) && urlLimpa,
      `pelo link: a pessoa confere o próprio nome e vê só o primeiro nome do aluno ("${primeiro}"); o código sai da barra de endereço`);
    L.ok(/pelo menos 12/.test(fraca) && ativo === 1 && /familia\/(index|filho)/.test(v.p.url()) && filhos.includes(a1) && !!(await v.p.$('#senhaCriada')),
      'a senha fraca é recusada; a boa cria a senha e a pessoa entra: os filhos, com o aluno dela');
    await L.print(v.p, ETAPA, 'familia-entrou');
    await v.ctx.close();
    v = await abrirLink(navegador, link2);
    const usado = await paginaInvalida(v.p);
    await v.ctx.close();
    sql(`UPDATE esc_responsavel_convites SET expira_em = NOW() - INTERVAL 1 MINUTE WHERE responsavel_id = ${pA} AND usado_em IS NULL`);
    v = await abrirLink(navegador, L.BASE + 'portal/convite/abrir#c=' + crypto.randomBytes(24).toString('hex'));
    const inventado = await paginaInvalida(v.p);
    await v.ctx.close();
    L.ok(usado && usado === velho && usado === inventado, 'o link usado, o trocado e o inventado mostram a MESMA página, sem dizer se a pessoa existe');

    /* sabotagem: o convite deixa de conferir o uso */
    const orig = fs.readFileSync(CONTA, 'utf8');
    const ponto = 'WHERE c.resumo = ? AND c.usado_em IS NULL AND ';
    if (!orig.includes(ponto)) throw new Error('a sabotagem não achou o ponto do convite');
    fs.writeFileSync(CONTA, orig.replace(ponto, 'WHERE c.resumo = ? AND '));
    let sab = '';
    try { v = await abrirLink(navegador, link2); sab = (await v.p.$('#conviteValido')) ? 'valeu' : 'recusou'; await v.ctx.close(); } finally { fs.writeFileSync(CONTA, orig); }
    L.ok(sab === 'valeu', 'sabotagem (o convite sem conferir o uso): a prova acusa — o link usado voltou a valer');
    L.ok(sha(CONTA) === shaConta, 'o arquivo sabotado voltou igual (sha256)');

    /* 4. a entrada */
    sql(`UPDATE esc_responsaveis SET cpf = '00093010117' WHERE id = ${pA}`);
    let e = await entrarPortal(navegador, 'mae.convite@exemplo.invalid', SENHA_BOA);
    const porEmail = /familia\/(index|filho)/.test(e.p.url());
    const nivel = await e.p.textContent('#perfil-logado').catch(() => '');
    await e.p.goto(L.BASE + 'portal/home/index');
    const voltou = /familia\/(index|filho)/.test(e.p.url());
    const semPerfil = !(await e.p.$('#railMenu a[href*="perfil/visualizar"]'));
    await e.ctx.close();
    e = await entrarPortal(navegador, '000.930.101-17', SENHA_BOA);
    const porCpf = /familia\/(index|filho)/.test(e.p.url());
    await e.ctx.close();
    e = await entrarPortal(navegador, 'mae.convite@exemplo.invalid', 'senha errada de proposito');
    const errada = await e.p.textContent('#loginFalha').catch(() => '');
    await e.ctx.close();
    /* Onda 8 (D-67): a conta tem UM login (o e-mail, escolhido no convite); o CPF do cadastro, que não é o login, não entra */
    L.ok(porEmail && !porCpf && /Responsável/.test(nivel) && voltou && semPerfil && /Usuário ou senha incorretos/.test(errada),
      'a entrada: pelo login (o e-mail escolhido no convite); o CPF do cadastro, que não é o login, não entra (D-67); a senha errada, a mensagem genérica; a página do aluno leva de volta aos filhos; sem o perfil do aluno no menu');

    /* 5. cancelar e bloquear */
    await postar(p, 'responsaveis/cadastrar', { nome: 'Avó Convite Exemplo', email: '', celular: '', cpf: '000.930.102-06' }, 'responsaveis/listar');
    const pC = um("SELECT id FROM esc_responsaveis WHERE cpf = '00093010206'");
    await postar(p, 'responsaveis/designar', { aluno: String(a1), ano: String(ANO), papel: 'titular', responsavel_id: String(pC), parentesco: 'Avó', data: `${ANO}-02-01` }, `responsaveis/aluno/${a1}?ano=${ANO}`);
    const linkC = await gerar(p, pC);
    const semCelular = !!(await p.$('#semCelular'));
    await Promise.all([p.waitForNavigation(), p.click('#cancelarConvite')]);
    v = await abrirLink(navegador, linkC);
    const cancelado = await paginaInvalida(v.p);
    await v.ctx.close();
    L.ok(linkC && semCelular && /não vale mais/.test(cancelado) && um(`SELECT COUNT(*) FROM esc_responsavel_convites WHERE responsavel_id = ${pC} AND cancelado_em IS NOT NULL`) === 1,
      'cancelar o convite: o link deixa de valer (a pessoa só com CPF, sem celular: a tela diz que o WhatsApp não está disponível)');
    const aberta = await entrarPortal(navegador, 'mae.convite@exemplo.invalid', SENHA_BOA);
    await p.goto(L.BASE + 'painel/responsaveis/ver/' + pA);
    await p.click('#blocoBloquear summary');
    await p.fill('#motivoBloqueio', 'Pedido da família (prova)');
    await Promise.all([p.waitForNavigation(), p.click('#bloquearAcesso')]);
    await aberta.p.goto(L.BASE + 'portal/familia/index');
    const caiu = /login\/acesso/.test(aberta.p.url());
    await aberta.ctx.close();
    e = await entrarPortal(navegador, 'mae.convite@exemplo.invalid', SENHA_BOA);
    const bloq = await e.p.textContent('#responsavelBloqueado').catch(() => '');
    await e.ctx.close();
    L.ok(caiu && /bloqueado/.test(bloq) && um(`SELECT bloqueado FROM esc_responsaveis WHERE id = ${pA}`) === 1,
      'bloquear: a sessão aberta cai na próxima página; a entrada é recusada, com o aviso de procurar a secretaria');
    await Promise.all([p.waitForNavigation(), p.click('#desbloquearAcesso')]);
    e = await entrarPortal(navegador, 'mae.convite@exemplo.invalid', SENHA_BOA);
    L.ok(/familia\/(index|filho)/.test(e.p.url()), 'desbloquear: a pessoa entra de novo');
    await e.ctx.close();

    /* 6. Esqueci a senha */
    const antes = um(`SELECT COUNT(*) FROM esc_responsavel_convites WHERE responsavel_id = ${pA}`);
    const ve = await navegador.newContext({ viewport: { width: 390, height: 844 }, locale: 'pt-BR' });
    const pe = await L.pagina(ve);
    await pe.goto(L.BASE + 'portal/login/esqueceu-senha');
    const dicaSemEmail = await pe.textContent('#dicaResponsavelSenha').catch(() => '');
    await pe.fill('#identificador', 'mae.convite@exemplo.invalid');
    await Promise.all([pe.waitForNavigation(), pe.click('[name="RecupLogin"]')]);
    const resposta1 = await pe.textContent('#esqueciEnviado').catch(() => '');
    await pe.goto(L.BASE + 'portal/login/esqueceu-senha');
    await pe.fill('#identificador', 'ninguem.existe@exemplo.invalid');
    await Promise.all([pe.waitForNavigation(), pe.click('[name="RecupLogin"]')]);
    const resposta2 = await pe.textContent('#esqueciEnviado').catch(() => '');
    await ve.close();
    L.ok(um(`SELECT COUNT(*) FROM esc_responsavel_convites WHERE responsavel_id = ${pA}`) === antes && resposta1 && resposta1 === resposta2
      && /secretaria/.test(dicaSemEmail) && /responsável/.test(resposta1),
      '"Esqueci a senha": sem o envio de e-mail configurado, nada é gerado; a resposta é a mesma para quem existe e para quem não existe; a dica para o responsável sem e-mail');
    const codS = crypto.randomBytes(24).toString('hex');
    sql(`INSERT INTO esc_responsavel_convites (responsavel_id, tipo, resumo, criado_em, expira_em) VALUES (${pA}, 'senha', '${crypto.createHash('sha256').update(codS).digest('hex')}', NOW(), NOW() + INTERVAL 2 HOUR)`);
    v = await abrirLink(navegador, L.BASE + 'portal/convite/abrir#c=' + codS);
    const titulo = await v.p.textContent('#conviteValido h2').catch(() => '');
    await v.p.fill('#senha', SENHA_NOVA);
    await v.p.fill('#senha_repetido', SENHA_NOVA);
    await Promise.all([v.p.waitForNavigation(), v.p.click('#criarSenha')]);
    await v.ctx.close();
    e = await entrarPortal(navegador, 'mae.convite@exemplo.invalid', SENHA_NOVA);
    const comNova = /familia\/(index|filho)/.test(e.p.url());
    await e.ctx.close();
    L.ok(/Senha nova/.test(titulo) && comNova && um(`SELECT COUNT(*) FROM esc_responsavel_auditoria WHERE responsavel_id = ${pA} AND acao = 'senha_trocada'`) === 1,
      'o link de senha nova abre a mesma página ("Senha nova") e troca a senha: a pessoa entra com a nova');

    /* 7. a turma */
    const turma = um(`SELECT a.turma_id FROM esc_turma_alunos a INNER JOIN esc_turmas t ON t.id = a.turma_id WHERE a.rm = '265001' AND t.ano = ${ANO} AND a.saida IS NULL LIMIT 1`);
    const a2 = um(`SELECT u.id FROM esc_turma_alunos a INNER JOIN col_usuarios u ON u.rm = a.rm WHERE a.turma_id = ${turma} AND a.saida IS NULL AND u.id <> ${a1} ORDER BY u.id LIMIT 1`);
    await postar(p, 'responsaveis/cadastrar', { nome: 'Pai Turma Exemplo', email: 'pai.turma@exemplo.invalid', celular: '11988880303', cpf: '' }, 'responsaveis/listar');
    const pD = um("SELECT id FROM esc_responsaveis WHERE email = 'pai.turma@exemplo.invalid'");
    await postar(p, 'responsaveis/designar', { aluno: String(a2), ano: String(ANO), papel: 'financeiro', responsavel_id: String(pD), parentesco: 'Pai', data: `${ANO}-02-01` }, `responsaveis/aluno/${a2}?ano=${ANO}`);
    await p.goto(L.BASE + `painel/responsaveis/convidar-turma?ano=${ANO}&turma=${turma}`);
    const pendentes = await p.$$eval('#pendentesTurma tr[data-responsavel]', (l) => l.map((x) => +x.getAttribute('data-responsavel')));
    await p.click('#gerarConvitesTurma');
    await p.waitForSelector('#progressoConvites[data-situacao="concluida"]', { timeout: 60000 });
    const manuais = await p.$$eval('#listaManual .vc-manual', (l) => l.map((x) => ({ id: +x.getAttribute('data-responsavel'), link: x.querySelector('.vc-link').value })));
    await L.print(p, ETAPA, 'convites-turma');
    L.ok(pendentes.includes(pC) && pendentes.includes(pD) && !pendentes.includes(pA) && manuais.length === pendentes.length && manuais.every((m) => /convite\/abrir#c=[a-f0-9]{48}$/.test(m.link))
      && pendentes.every((id) => um(`SELECT COUNT(*) FROM esc_responsavel_convites WHERE responsavel_id = ${id} AND usado_em IS NULL AND cancelado_em IS NULL`) === 1),
      `os convites da turma, em passos: os ${pendentes.length} sem acesso (não quem já entrou) ganharam o convite; sem e-mail configurado, todos na lista do envio manual, com o link`);
    v = await abrirLink(navegador, manuais.find((m) => m.id === pD).link);
    const doLote = !!(await v.p.$('#conviteValido'));
    await v.ctx.close();
    L.ok(doLote, 'o link da lista do envio manual abre o convite');

    /* 8. o registro e o limite */
    const acoes = sql(`SELECT GROUP_CONCAT(DISTINCT acao ORDER BY acao) FROM esc_responsavel_auditoria WHERE responsavel_id IN (${pA}, ${pC})`);
    L.ok(['convite_gerado', 'convite_usado', 'convite_cancelado', 'bloquear', 'desbloquear'].every((x) => acoes.includes(x)), `o registro: ${acoes}`);
    sql("DELETE FROM seg_tentativas WHERE escopo = 'convite_portal'");
    sql("INSERT INTO adms_parametros (chave, valor) VALUES ('limite.ip.verificacao', '10') ON DUPLICATE KEY UPDATE valor = '10'");
    const vl = await navegador.newContext({ locale: 'pt-BR' });
    const pl = await L.pagina(vl);
    let espera = '';
    for (let i = 0; i < 12 && !espera; i++) {
      await pl.goto(L.BASE + 'portal/convite/abrir#c=' + crypto.randomBytes(24).toString('hex'));
      await pl.waitForSelector('#conviteInvalido', { timeout: 15000 }).catch(() => {});
      espera = await pl.textContent('#conviteEspera').catch(() => '');
    }
    await vl.close();
    sql("DELETE FROM seg_tentativas WHERE escopo = 'convite_portal'");
    sql("DELETE FROM adms_parametros WHERE chave = 'limite.ip.verificacao'");
    L.ok(/aguarde|tente|minuto|segundo/i.test(espera), `o limite de tentativas no uso do convite: depois de 10 links que não valem, a espera ("${espera.trim().slice(0, 70)}")`);
  } catch (err) {
    L.ok(false, 'a prova parou: ' + (err.stack || err.message).slice(0, 500));
  } finally {
    if (sha(CONTA) !== shaConta) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
