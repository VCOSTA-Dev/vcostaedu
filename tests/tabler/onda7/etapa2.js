/*
 * PROVA DA ONDA 7, ETAPA 2 — o cadastro do responsável e a designação por ano (D-62).
 *   A. no ensino superior (o banco que o ciclo deixa): as telas dos responsáveis não existem, nem o menu, nem a rota da
 *      interface (404);
 *   B. na escola (a demonstração do colégio, montada pela tela; APAGA o banco de prova):
 *      1. a pessoa: cadastrar; a mesma pessoa (o e-mail, o CPF) não nasce duas vezes; o CPF inválido recusado;
 *      2. o financeiro e o titular do ano pela tela; o segundo financeiro sem motivo recusado; a troca com o motivo
 *         encerra o anterior na data, abre o novo, e nunca há dois em vigor; o histórico guarda o antes e o motivo;
 *      3. o banco recusa, sozinho, o segundo financeiro em vigor (o INSERT direto);
 *      4. o acesso acadêmico adicional: sem motivo, recusado; com o motivo e a referência, liberado; o limite da escola;
 *         com a regra desligada, recusado; encerrar a liberação;
 *      5. copiar do ano anterior: a conferência não grava; confirmada, copia o financeiro e o titular e não o adicional;
 *      6. os contatos; a lista geral (a busca pelo RA do aluno, o papel por ano, a situação do acesso);
 *      7. a planilha: uma linha ruim, nada gravado (tudo ou nada); duas linhas de financeiro para o mesmo aluno,
 *         recusadas; a planilha boa, aplicada (a pessoa que já existe não é criada de novo);
 *      8. a interface de integração: consultar e designar; o adicional recusado; a troca sem motivo recusada;
 *      9. gravação só por POST: o GET das rotas de gravação não grava nada;
 *   sabotagem: a troca deixa de pedir o motivo — a prova acusa; o arquivo volta igual (sha256).
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { spawnSync, execFileSync } = require('child_process');
const L = require('../lib');

const ETAPA = 'onda7/etapa2';
const SAIDA = path.join(__dirname, '..', 'saida');
const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste$/.test(BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
const SENHA = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const MODEL = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'escola', 'AdmsResponsaveis.php');
const API = L.BASE + 'api/v1';
const ANO = 2026;
const ANTERIOR = 2025;

const sql = (q) => execFileSync('C:/xampp/mysql/bin/mysql.exe', ['-uroot', '--default-character-set=utf8mb4', '-N', '-B', BANCO, '-e', q], { encoding: 'utf8' }).trim();
const sqlErro = (q) => { const r = spawnSync('C:/xampp/mysql/bin/mysql.exe', ['-uroot', '--default-character-set=utf8mb4', '-N', '-B', BANCO, '-e', q], { encoding: 'utf8' }); return r.status === 0 ? '' : (r.stderr || 'erro'); };
const um = (q) => +(sql(q).split('\n')[0] || 0);
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const idAluno = (ra) => um(`SELECT id FROM col_usuarios WHERE rm = '${ra}'`);
const abertos = (aluno, ano, papel) => um(`SELECT COUNT(*) FROM esc_responsavel_designacoes WHERE aluno_id = ${aluno} AND ano = ${ano} AND papel = '${papel}' AND fim IS NULL`);

async function entrar(navegador) {
  const ctx = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' });
  const p = await L.pagina(ctx);
  await p.goto(L.BASE + 'painel/login/acesso');
  await p.fill('input[name="usuario"]', 'administrador');
  await p.fill('input[name="senha"]', process.env.SENHA_ADM || SENHA);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
  return { ctx, p };
}
/** POST como o formulário da tela (mesma origem); devolve o aviso que a tela seguinte mostra. */
async function postar(p, rota, campos, voltar) {
  const direto = await p.evaluate(async ([u, c]) => { const f = new URLSearchParams(); for (const [k, v] of Object.entries(c)) { if (Array.isArray(v)) v.forEach((x) => f.append(k, x)); else f.append(k, v); }
    const r = await fetch(u, { method: 'POST', body: f, credentials: 'same-origin', redirect: 'manual' });
    if (r.type === 'opaqueredirect' || r.status >= 300) { return ''; }
    const d = new DOMParser().parseFromString(await r.text(), 'text/html');
    return Array.from(d.querySelectorAll('.alert')).map((e) => e.textContent.replace(/\s+/g, ' ').trim()).join(' | '); }, [L.BASE + 'painel/' + rota, campos]);
  await p.goto(L.BASE + 'painel/' + voltar);
  const depois = await p.$$eval('.alert', (l) => l.map((e) => e.innerText.replace(/\s+/g, ' ').trim()).join(' | ')).catch(() => '');
  return [direto, depois].filter(Boolean).join(' | ');
}
function criarChave(permissoes) {
  const chave = 'vce_' + crypto.randomBytes(20).toString('hex');
  sql(`INSERT INTO adms_api_chaves (nome, prefixo, hash, escopo, permissoes, situacao, usuario_id) VALUES ('Prova responsáveis', '${chave.slice(0, 12)}', '${crypto.createHash('sha256').update(chave).digest('hex')}', 'escrita', '${permissoes}', 1, 1)`);
  return chave;
}
async function api(metodo, rota, chave, corpo) {
  const r = await fetch(API + rota, { method: metodo, headers: { Authorization: 'Bearer ' + chave, 'Content-Type': 'application/json' }, body: corpo ? JSON.stringify(corpo) : undefined });
  let j = null;
  try { j = await r.json(); } catch (e) { /* */ }
  return { status: r.status, j };
}
/** A planilha pela tela: conferir; se der, aplicar. */
async function planilha(p, csv, aplicar) {
  const arq = path.join(SAIDA, 'onda7-etapa2-planilha.csv');
  fs.writeFileSync(arq, '\ufeff' + csv);
  await p.goto(L.BASE + 'painel/responsaveis/importar');
  await p.setInputFiles('#planilha', arq);
  await Promise.all([p.waitForNavigation(), p.click('#conferirPlanilha')]);
  const ok = (await p.getAttribute('#conferenciaPlanilha', 'data-ok').catch(() => null)) === '1';
  const ruins = await p.$$eval('#conferenciaPlanilha tr.table-danger', (l) => l.map((t) => t.innerText.replace(/\s+/g, ' '))).catch(() => []);
  if (ok && aplicar) { await Promise.all([p.waitForNavigation(), p.click('#aplicarPlanilha')]); }
  const aviso = await p.$$eval('.alert', (l) => l.map((e) => e.innerText.replace(/\s+/g, ' ').trim()).join(' | ')).catch(() => '');
  return { ok, ruins, aviso };
}

/** A troca sem motivo (o ponto que a sabotagem mexe): devolve se foi recusada e quantos financeiros ficaram em vigor. */
async function trocaSemMotivo(p, aluno, pessoaB) {
  const aviso = await postar(p, 'responsaveis/designar', { aluno: String(aluno), ano: String(ANO), papel: 'financeiro', responsavel_id: String(pessoaB), data: `${ANO}-05-10`, motivo: '' }, `responsaveis/aluno/${aluno}?ano=${ANO}`);
  return { recusada: /pede o motivo/.test(aviso), financeiros: abertos(aluno, ANO, 'financeiro'), aviso };
}

(async () => {
  const { navegador } = await L.abrir();
  const shaModel = sha(MODEL);
  try {
    console.log('\n== Onda 7, Etapa 2 — o cadastro do responsável e a designação por ano');
    /* A. o ensino superior */
    const cred = JSON.parse(fs.readFileSync(path.join(SAIDA, 'credenciais.json'), 'utf8'));
    process.env.SENHA_ADM = cred.painel.administrador;
    let s = await entrar(navegador);
    await s.p.goto(L.BASE + 'painel/responsaveis/listar');
    const telaSup = !!(await s.p.$('#tabelaResponsaveis, #semResponsaveis'));
    const menuSup = await s.p.$$eval('#railMenu a', (l) => l.map((a) => a.textContent).join(' ')).catch(() => '');
    const chaveSup = criarChave('responsaveis:gravar alunos:gravar');
    const apiSup = await api('GET', '/alunos/260101/responsaveis', chaveSup);
    L.ok(!telaSup && !/Responsáveis/.test(menuSup) && apiSup.status === 404, `no ensino superior: sem a tela, sem o menu e sem a rota da interface (${apiSup.status})`);
    await s.ctx.close();
    delete process.env.SENHA_ADM;

    /* B. a escola */
    const r = spawnSync(process.execPath, [path.join(__dirname, '..', 'onda6', 'medir_demo.js'), 'escola', 'onda7-etapa2'], { encoding: 'utf8', maxBuffer: 1 << 24 });
    require('./_antes_d99')(); // D-99: os responsáveis como antes do financeiro refeito (os alunos sem responsável, as pessoas sem celular)
    L.ok(r.status === 0 && um("SELECT COUNT(*) FROM esc_anos WHERE ano IN (2025, 2026)") === 2, 'a demonstração do colégio montada pela tela, com os anos letivos 2025 e 2026');
    s = await entrar(navegador);
    const p = s.p;
    const a1 = idAluno('265001');
    const a2 = idAluno('265002');
    const a25 = idAluno('255001');

    /* 1. a pessoa */
    let aviso = await postar(p, 'responsaveis/cadastrar', { nome: 'Mãe Prova Exemplo', email: 'mae.prova@exemplo.invalid', celular: '(11) 98888-0001', cpf: '' }, 'responsaveis/listar');
    const pA = um("SELECT id FROM esc_responsaveis WHERE email = 'mae.prova@exemplo.invalid'");
    aviso = await postar(p, 'responsaveis/cadastrar', { nome: 'Outro Nome', email: 'MAE.PROVA@exemplo.invalid', celular: '', cpf: '' }, 'responsaveis/listar');
    const dup = um("SELECT COUNT(*) FROM esc_responsaveis WHERE email = 'mae.prova@exemplo.invalid'");
    const avisoCpf = await postar(p, 'responsaveis/cadastrar', { nome: 'CPF Errado Exemplo', email: '', celular: '', cpf: '111.111.111-11' }, 'responsaveis/listar');
    L.ok(pA > 0 && dup === 1 && /Já existe/.test(aviso) && /CPF não é válido/.test(avisoCpf) && !um("SELECT COUNT(*) FROM esc_responsaveis WHERE nome = 'CPF Errado Exemplo'"),
      'a pessoa: cadastrada; o mesmo e-mail (com outra caixa) não cria outra pessoa; o CPF inválido é recusado');

    /* 2. o financeiro e o titular; a troca */
    await postar(p, 'responsaveis/designar', { aluno: String(a1), ano: String(ANO), papel: 'financeiro', responsavel_id: String(pA), parentesco: 'Mãe', data: `${ANO}-02-01` }, `responsaveis/aluno/${a1}?ano=${ANO}`);
    await postar(p, 'responsaveis/designar', { aluno: String(a1), ano: String(ANO), papel: 'titular', responsavel_id: String(pA), data: `${ANO}-02-01` }, `responsaveis/aluno/${a1}?ano=${ANO}`);
    await postar(p, 'responsaveis/cadastrar?aluno=' + a1, { aluno: String(a1), nome: 'Pai Prova Exemplo', email: 'pai.prova@exemplo.invalid', celular: '11988880002', cpf: '', parentesco: 'Pai' }, `responsaveis/aluno/${a1}?ano=${ANO}`);
    const pB = um("SELECT id FROM esc_responsaveis WHERE email = 'pai.prova@exemplo.invalid'");
    const sem = await trocaSemMotivo(p, a1, pB || 999999);
    L.ok(abertos(a1, ANO, 'financeiro') === 1 && abertos(a1, ANO, 'titular') === 1 && sem.recusada && sem.financeiros === 1
      && um(`SELECT responsavel_id FROM esc_responsavel_designacoes WHERE aluno_id = ${a1} AND ano = ${ANO} AND papel = 'financeiro' AND fim IS NULL`) === pA,
      'o financeiro e o titular (a mesma pessoa) designados; o segundo financeiro sem motivo, recusado pela tela — continua um só');

    /* sabotagem: a troca deixa de pedir o motivo */
    const orig = fs.readFileSync(MODEL, 'utf8');
    const ponto = "                if ($motivo === '') {\n                    $erro = 'A troca do '";
    if (!orig.includes(ponto)) throw new Error('a sabotagem não achou o ponto do motivo');
    fs.writeFileSync(MODEL, orig.replace(ponto, "                if (false && $motivo === '') {\n                    $erro = 'A troca do '"));
    let sab;
    try { sab = await trocaSemMotivo(p, a1, pB); } finally { fs.writeFileSync(MODEL, orig); }
    L.ok(!sab.recusada, 'sabotagem (a troca sem pedir o motivo): a prova acusa — a troca passou sem motivo');
    L.ok(sha(MODEL) === shaModel, 'o arquivo sabotado voltou igual (sha256)');
    /* desfaz o que a sabotagem gravou: o pai volta a não ser o financeiro (direto no banco, só para a prova seguir) */
    sql(`DELETE FROM esc_responsavel_designacoes WHERE aluno_id = ${a1} AND ano = ${ANO} AND papel = 'financeiro' AND responsavel_id = ${pB}`);
    sql(`UPDATE esc_responsavel_designacoes SET fim = NULL, motivo_fim = NULL, encerrado_por = NULL, encerrado_em = NULL WHERE aluno_id = ${a1} AND ano = ${ANO} AND papel = 'financeiro' AND responsavel_id = ${pA}`);

    /* a troca de verdade, com o motivo */
    aviso = await postar(p, 'responsaveis/designar', { aluno: String(a1), ano: String(ANO), papel: 'financeiro', responsavel_id: String(pB), data: `${ANO}-05-10`, motivo: 'Acordo entre os pais (prova)' }, `responsaveis/aluno/${a1}?ano=${ANO}`);
    const antigo = sql(`SELECT CONCAT(fim, '|', motivo_fim) FROM esc_responsavel_designacoes WHERE aluno_id = ${a1} AND ano = ${ANO} AND papel = 'financeiro' AND responsavel_id = ${pA}`);
    const novo = sql(`SELECT CONCAT(inicio, '|', IFNULL(fim, 'aberto')) FROM esc_responsavel_designacoes WHERE aluno_id = ${a1} AND ano = ${ANO} AND papel = 'financeiro' AND responsavel_id = ${pB}`);
    const aud = sql(`SELECT CONCAT(acao, '|', antes, '|', motivo) FROM esc_responsavel_auditoria WHERE aluno_id = ${a1} AND acao = 'trocar' ORDER BY id DESC LIMIT 1`);
    const telaHist = await p.$eval('#historicoAluno', (e) => e.innerText).catch(() => '');
    L.ok(antigo === `${ANO}-05-10|Acordo entre os pais (prova)` && novo === `${ANO}-05-10|aberto` && abertos(a1, ANO, 'financeiro') === 1 && /"responsavel_id":/.test(aud) && /Acordo/.test(aud) && /Troca/.test(telaHist),
      `a troca com o motivo: o anterior até ${ANO}-05-10, o novo desde a mesma data, um só em vigor; o histórico guarda o antes e o motivo`);
    await L.semErros(p, 'os responsáveis do aluno');
    await L.print(p, ETAPA, 'responsaveis-do-aluno');

    /* 3. o banco recusa, sozinho */
    const erroBanco = sqlErro(`INSERT INTO esc_responsavel_designacoes (aluno_id, ano, responsavel_id, papel, inicio, criado_em) VALUES (${a1}, ${ANO}, ${pA}, 'financeiro', '${ANO}-06-01', NOW())`);
    const erroReabrir = sqlErro(`UPDATE esc_responsavel_designacoes SET fim = NULL WHERE aluno_id = ${a1} AND ano = ${ANO} AND papel = 'financeiro' AND responsavel_id = ${pA}`);
    L.ok(/Duplicate|um_financeiro_um_titular/.test(erroBanco) && /Duplicate|um_financeiro_um_titular/.test(erroReabrir) && abertos(a1, ANO, 'financeiro') === 1,
      'o banco recusa o segundo financeiro em vigor, direto (o INSERT e reabrir o anterior), pela restrição própria');

    /* 4. o acesso acadêmico adicional */
    const semMotivo = await postar(p, 'responsaveis/liberar', { aluno: String(a1), ano: String(ANO), responsavel_id: '0', nome: 'Avó Prova Exemplo', email: 'avo.prova@exemplo.invalid', celular: '', cpf: '', parentesco: 'Avó', motivo: '', referencia: '' }, `responsaveis/aluno/${a1}?ano=${ANO}`);
    const lib = await postar(p, 'responsaveis/liberar', { aluno: String(a1), ano: String(ANO), responsavel_id: '0', nome: 'Avó Prova Exemplo', email: 'avo.prova@exemplo.invalid', celular: '', cpf: '', parentesco: 'Avó', motivo: 'A avó busca o neto e acompanha as tarefas', referencia: 'Termo assinado em 10/02' }, `responsaveis/aluno/${a1}?ano=${ANO}`);
    const pC = um("SELECT id FROM esc_responsaveis WHERE email = 'avo.prova@exemplo.invalid'");
    const adic = sql(`SELECT CONCAT(motivo, '|', referencia, '|', criado_por) FROM esc_responsavel_designacoes WHERE aluno_id = ${a1} AND ano = ${ANO} AND papel = 'adicional' AND fim IS NULL`);
    sql("INSERT INTO esc_configuracao (chave, valor) VALUES ('responsaveis.adicional_limite', '1') ON DUPLICATE KEY UPDATE valor = '1'");
    const limite = await postar(p, 'responsaveis/liberar', { aluno: String(a1), ano: String(ANO), responsavel_id: '0', nome: 'Tio Prova Exemplo', email: 'tio.prova@exemplo.invalid', celular: '', cpf: '', parentesco: 'Tio', motivo: 'O tio acompanha às sextas-feiras', referencia: '' }, `responsaveis/aluno/${a1}?ano=${ANO}`);
    sql("DELETE FROM esc_configuracao WHERE chave = 'responsaveis.adicional_limite'");
    sql("INSERT INTO esc_configuracao (chave, valor) VALUES ('responsaveis.adicional', '0') ON DUPLICATE KEY UPDATE valor = '0'");
    const desligado = await postar(p, 'responsaveis/liberar', { aluno: String(a2), ano: String(ANO), responsavel_id: String(pC), parentesco: 'Avó', motivo: 'Com a regra desligada, não pode', referencia: '' }, `responsaveis/aluno/${a1}?ano=${ANO}`);
    const telaDesligada = !!(await p.$('#adicionalDesligado')) && !(await p.$('#liberarAdicional'));
    sql("DELETE FROM esc_configuracao WHERE chave = 'responsaveis.adicional'");
    L.ok(/Informe o motivo/.test(semMotivo) && /liberado/.test(lib) && /^A avó busca o neto e acompanha as tarefas\|Termo assinado em 10\/02\|\d+$/.test(adic) && /limite/.test(limite)
      && /não permite/.test(desligado) && telaDesligada && um(`SELECT COUNT(*) FROM esc_responsavel_designacoes WHERE aluno_id = ${a2} AND papel = 'adicional'`) === 0,
      'o acesso adicional: sem motivo, recusado; com o motivo e a referência, liberado (quem liberou guardado); o limite da escola; com a regra desligada, recusado e sem o formulário');
    const idAdic = um(`SELECT id FROM esc_responsavel_designacoes WHERE aluno_id = ${a1} AND ano = ${ANO} AND papel = 'adicional' AND fim IS NULL`);

    /* 5. copiar do ano anterior (o aluno do 9º ano de 2025 também existe em 2026 na prova: designado em 2025) */
    await postar(p, 'responsaveis/designar', { aluno: String(a25), ano: String(ANTERIOR), papel: 'financeiro', responsavel_id: String(pA), parentesco: 'Mãe' }, `responsaveis/aluno/${a25}?ano=${ANTERIOR}`);
    await postar(p, 'responsaveis/designar', { aluno: String(a25), ano: String(ANTERIOR), papel: 'titular', responsavel_id: String(pB), parentesco: 'Pai' }, `responsaveis/aluno/${a25}?ano=${ANTERIOR}`);
    await postar(p, 'responsaveis/liberar', { aluno: String(a25), ano: String(ANTERIOR), responsavel_id: String(pC), parentesco: 'Avó', motivo: 'A avó acompanhou em 2025', referencia: '' }, `responsaveis/aluno/${a25}?ano=${ANTERIOR}`);
    await p.goto(L.BASE + `painel/responsaveis/aluno/${a25}?ano=${ANO}&copiar=1`);
    const previa = await p.$eval('#previaCopia', (e) => e.innerText).catch(() => '');
    const antesCopia = um(`SELECT COUNT(*) FROM esc_responsavel_designacoes WHERE aluno_id = ${a25} AND ano = ${ANO}`);
    await Promise.all([p.waitForNavigation(), p.click('#confirmarCopia')]);
    const depoisCopia = sql(`SELECT GROUP_CONCAT(CONCAT(papel, ':', responsavel_id) ORDER BY papel) FROM esc_responsavel_designacoes WHERE aluno_id = ${a25} AND ano = ${ANO} AND fim IS NULL`);
    L.ok(/será copiado/.test(previa) && antesCopia === 0 && depoisCopia === `financeiro:${pA},titular:${pB}`,
      `copiar do ano anterior: a conferência não grava nada; confirmada, copia o financeiro e o titular (${depoisCopia}) e não o acesso adicional`);

    /* 6. os contatos e a lista */
    await postar(p, 'responsaveis/contato', { aluno: String(a1), ano: String(ANO), nome: 'Vizinha Prova Exemplo', parentesco: 'Vizinha', telefone: '(11) 3333-0000', observacao: 'Busca em emergência' }, `responsaveis/aluno/${a1}?ano=${ANO}`);
    const contato = um(`SELECT id FROM esc_responsavel_contatos WHERE aluno_id = ${a1}`);
    await postar(p, 'responsaveis/contato', { aluno: String(a1), ano: String(ANO), retirar: String(contato) }, `responsaveis/aluno/${a1}?ano=${ANO}`);
    await p.goto(L.BASE + 'painel/responsaveis/listar?busca=265001');
    const linha = await p.$eval(`#tabelaResponsaveis tr[data-responsavel="${pB}"]`, (e) => e.innerText.replace(/\s+/g, ' ')).catch(() => '');
    const situacao = await p.getAttribute(`#tabelaResponsaveis tr[data-responsavel="${pB}"] .vc-situacao-acesso`, 'data-situacao').catch(() => '');
    L.ok(contato > 0 && um(`SELECT COUNT(*) FROM esc_responsavel_contatos WHERE aluno_id = ${a1}`) === 0 && /2026: financeiro/.test(linha) && situacao === 'sem_convite',
      `os contatos (cadastrar e retirar); a lista pela busca do RA: a pessoa com "${(linha.match(/2026: [^ ]+/) || [''])[0]}" e o acesso "sem convite"`);
    await L.semErros(p, 'a lista dos responsáveis');
    await L.print(p, ETAPA, 'lista');

    /* encerrar a liberação adicional */
    aviso = await postar(p, 'responsaveis/encerrar', { aluno: String(a1), ano: String(ANO), designacao: String(idAdic), data: '', motivo: 'A família pediu (prova)' }, `responsaveis/aluno/${a1}?ano=${ANO}`);
    L.ok(/Encerrado/.test(aviso) && um(`SELECT COUNT(*) FROM esc_responsavel_designacoes WHERE id = ${idAdic} AND fim = CURDATE()`) === 1
      && um(`SELECT COUNT(*) FROM esc_responsavel_auditoria WHERE acao = 'encerrar_liberacao' AND aluno_id = ${a1}`) === 1, 'encerrar a liberação: com a data e o motivo, e o registro');

    /* 7. a planilha */
    const cab = 'ra;ano;papel;nome;email;celular;cpf;parentesco;motivo\n';
    const antesPl = um('SELECT COUNT(*) FROM esc_responsavel_designacoes');
    const ruim = await planilha(p, cab + `265011;${ANO};financeiro;Pessoa Planilha Um;planilha.um@exemplo.invalid;11977770001;;Mãe;\n999999;${ANO};titular;Pessoa Planilha Dois;planilha.dois@exemplo.invalid;;;Pai;\n`, true);
    const dobro = await planilha(p, cab + `265012;${ANO};financeiro;Pessoa Planilha Um;planilha.um@exemplo.invalid;;;Mãe;\n265012;${ANO};financeiro;Pessoa Planilha Dois;planilha.dois@exemplo.invalid;;;Pai;\n`, true);
    const adPl = await planilha(p, cab + `265012;${ANO};adicional;Pessoa Planilha Um;planilha.um@exemplo.invalid;;;Mãe;\n`, true);
    L.ok(!ruim.ok && ruim.ruins.length === 1 && /aluno não encontrado/i.test(ruim.ruins[0]) && !dobro.ok && /um só por papel/i.test(dobro.ruins.join(' ')) && !adPl.ok && /pela tela/i.test(adPl.ruins.join(' '))
      && um('SELECT COUNT(*) FROM esc_responsavel_designacoes') === antesPl,
      'a planilha: uma linha ruim (o RA que não existe) e nada é gravado; duas linhas de financeiro para o mesmo aluno, recusadas; o adicional, recusado');
    const boa = await planilha(p, cab + `265011;${ANO};financeiro;Pessoa Planilha Um;planilha.um@exemplo.invalid;11977770001;;Mãe;\n265011;${ANO};titular;Pessoa Planilha Um;planilha.um@exemplo.invalid;11977770001;;Mãe;\n265012;${ANO};financeiro;Mãe Prova Exemplo;mae.prova@exemplo.invalid;;;Mãe;\n`, true);
    L.ok(boa.ok && /aplicada: 3 linha/.test(boa.aviso) && um("SELECT COUNT(*) FROM esc_responsaveis WHERE email = 'mae.prova@exemplo.invalid'") === 1
      && abertos(idAluno('265011'), ANO, 'financeiro') === 1 && abertos(idAluno('265011'), ANO, 'titular') === 1 && um(`SELECT COUNT(*) FROM esc_responsavel_designacoes WHERE origem = 'planilha'`) === 3,
      `a planilha boa, aplicada: ${boa.aviso.slice(0, 90)} (a pessoa que já existia não foi criada de novo)`);

    /* 8. a interface de integração */
    const chave = criarChave('responsaveis:gravar');
    const v = await api('GET', `/alunos/265011/responsaveis?ano=${ANO}`, chave);
    const d1 = await api('POST', '/alunos/265021/responsaveis', chave, { ano: ANO, papel: 'financeiro', responsavel: { nome: 'Pessoa Interface Exemplo', email: 'interface@exemplo.invalid', parentesco: 'Pai' } });
    const d2 = await api('POST', '/alunos/265021/responsaveis', chave, { ano: ANO, papel: 'financeiro', responsavel: { nome: 'Outra Interface Exemplo', email: 'interface2@exemplo.invalid' } });
    const d3 = await api('POST', '/alunos/265021/responsaveis', chave, { ano: ANO, papel: 'adicional', responsavel: { nome: 'Outra Interface Exemplo', email: 'interface2@exemplo.invalid' } });
    const d4 = await api('POST', '/alunos/265021/responsaveis', chave, { ano: ANO, papel: 'financeiro', motivo: 'Mudança combinada (prova)', responsavel: { nome: 'Outra Interface Exemplo', email: 'interface2@exemplo.invalid' } });
    const a21 = idAluno('265021');
    L.ok(v.status === 200 && v.j.dados.financeiro && v.j.dados.financeiro.nome === 'Pessoa Planilha Um' && d1.status === 200 && d2.status === 422 && /motivo/.test(JSON.stringify(d2.j)) && d3.status === 422
      && d4.status === 200 && d4.j.dados.financeiro.nome === 'Outra Interface Exemplo' && abertos(a21, ANO, 'financeiro') === 1 && um(`SELECT COUNT(*) FROM esc_responsavel_designacoes WHERE aluno_id = ${a21} AND origem = 'api'`) === 2,
      `a interface de integração: consultar (${v.status}); designar (${d1.status}); a troca sem motivo (${d2.status}) e o adicional (${d3.status}) recusados; a troca com motivo (${d4.status}); um financeiro só`);

    /* 9. gravação só por POST */
    const antesGet = um('SELECT COUNT(*) FROM esc_responsavel_designacoes') + um('SELECT COUNT(*) FROM esc_responsavel_contatos') + um('SELECT COUNT(*) FROM esc_responsaveis');
    for (const rota of [`responsaveis/designar?aluno=${a2}&ano=${ANO}&papel=financeiro&responsavel_id=${pA}`, `responsaveis/liberar?aluno=${a2}&ano=${ANO}&responsavel_id=${pA}&motivo=pelo%20GET%20nao`,
      `responsaveis/encerrar?designacao=${idAdic}&motivo=GET`, `responsaveis/contato?aluno=${a2}&nome=GET%20Exemplo&parentesco=Tio&telefone=11999990000`, `responsaveis/copiar?aluno=${a25}&ano=${ANO}`,
      'responsaveis/cadastrar?nome=Pelo%20GET%20Exemplo&email=get@exemplo.invalid']) {
      await p.goto(L.BASE + 'painel/' + rota);
    }
    const depoisGet = um('SELECT COUNT(*) FROM esc_responsavel_designacoes') + um('SELECT COUNT(*) FROM esc_responsavel_contatos') + um('SELECT COUNT(*) FROM esc_responsaveis');
    L.ok(antesGet === depoisGet, 'as rotas de gravação pelo GET (designar, liberar, encerrar, contato, copiar, cadastrar): nada gravado');
    await s.ctx.close();
  } catch (e) {
    L.ok(false, 'a prova parou: ' + (e.stack || e.message).slice(0, 500));
  } finally {
    if (sha(MODEL) !== shaModel) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
