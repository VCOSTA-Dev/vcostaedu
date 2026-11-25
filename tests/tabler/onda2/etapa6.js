/*
 * ONDA 2, ETAPA 6 — a API de integração (/api/v1).
 *   A. permissão nos 3 pontos: o menu Integrações e as telas só para o Administrador (a rota recusada aos outros);
 *      o botão "Criar" e "Revogar"; na API, a chave de leitura não grava;
 *   B. as chaves pela tela: a chave aparece UMA vez e fica só como hash; revogar; vencida; ausente;
 *   C. cada operação por HTTP real (as 20 da especificação), com chave de leitura e de escrita; RA repetido devolve o
 *      existente; Idempotency-Key repetida (a mesma resposta, nada gravado de novo; outro corpo, 409); o limite por
 *      minuto (429); o erro num formato só, sem caminho de servidor nem SQL; sem CORS;
 *   D. o que a API grava é o que a tela grava (o mesmo aluno pela tela e pela API: as mesmas colunas);
 *   E. o registro de cada chamada e a tela dele; a especificação e o servidor: toda rota de um está no outro, e toda
 *      rota da especificação responde; a documentação (Swagger UI) servida do próprio sistema, sem pedido para fora;
 *   F. sabotagem: sem a conferência do escopo, a chave de leitura grava — a prova reprova.
 *
 *   node tests/tabler/onda2/etapa6.js     (depois do ciclo, com a demonstração)
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { execFileSync } = require('child_process');
const L = require('../lib');

const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste2?$/.test(BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
const MYSQL = process.env.VCOSTAEDU_MYSQL || 'C:/xampp/mysql/bin/mysql.exe';
const PHP = process.env.VCOSTAEDU_PHP || 'C:/xampp/php/php.exe';
const sql = (q) => execFileSync(MYSQL, ['-u', 'root', '-N', '--default-character-set=utf8mb4', BANCO, '-e', q]).toString().replace(/\r/g, '').trim();
const um = (q) => +sql(q);
const cred = JSON.parse(fs.readFileSync(path.join(__dirname, '..', 'saida', 'credenciais.json'), 'utf8'));
const ETAPA = 'onda2/etapa6';
const API = L.BASE + 'api/v1';
const SERVIDOR = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'api', 'AdmsApiServidor.php');
const ANO = new Date().getFullYear();

async function api(metodo, rota, { chave = null, corpo = undefined, idem = null, cab = {} } = {}) {
  const h = { ...cab };
  if (chave) { h.Authorization = 'Bearer ' + chave; }
  if (idem) { h['Idempotency-Key'] = idem; }
  if (corpo !== undefined) { h['Content-Type'] = 'application/json'; }
  const r = await fetch(API + rota, { method: metodo, headers: h, body: corpo === undefined ? undefined : (typeof corpo === 'string' ? corpo : JSON.stringify(corpo)) });
  const texto = await r.text();
  let json = null;
  try { json = JSON.parse(texto); } catch (e) { /* fica o texto */ }
  return { status: r.status, json, texto, h: r.headers };
}
const formatoErro = (r, codigo) => r.json && r.json.erro && r.json.erro.codigo === codigo && typeof r.json.erro.mensagem === 'string' && 'campo' in r.json.erro;

async function entrar(navegador, usuario) {
  const ctx = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' });
  const p = await L.pagina(ctx);
  await p.goto(L.BASE + 'painel/login/acesso');
  await p.fill('input[name="usuario"]', usuario);
  await p.fill('input[name="senha"]', cred.painel[usuario]);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
  return { ctx, p };
}
const abriu = async (p, rota) => {
  await p.goto(L.BASE + rota);
  return decodeURIComponent(p.url()).replace(L.BASE, '').replace(/[?#].*$/, '') === rota && !(await p.$('input[name="SendLogin"]')) && !(await p.$('#semAcesso'));
};
/* Onda 3, Etapa 9: o limite por assunto — "leitura" marca consultar em tudo; "escrita", gravar em tudo (o que a
   chave de escrita antiga fazia) */
async function criarChave(p, nome, escopo, validade = '') {
  await p.goto(L.BASE + 'painel/integracoes/chaves');
  await p.fill('#nomeChave', nome);
  for (const a of ['alunos', 'matriculas', 'enturmacao', 'cadastros']) {
    const v = escopo === 'escrita' && a !== 'cadastros' ? 'gravar' : 'ler';
    await p.check(`input[name="permissao[${a}]"][value="${v}"]`);
  }
  if (validade) { await p.fill('#validadeChave', validade); }
  await Promise.all([p.waitForNavigation(), p.click('#criarChave')]);
  return (await p.$eval('#textoChaveNova', (e) => e.textContent.trim()).catch(() => ''));
}
async function sabotar(nome, estragar, consertar, conferir) {
  let reprovou = false;
  try { await estragar(); reprovou = !(await conferir()); } finally { await consertar(); }
  L.ok(reprovou, `sabotagem (${nome}): a prova reprova o defeito`);
}

(async () => {
  const { navegador } = await L.abrir();
  const limiteOriginal = sql("SELECT valor FROM adms_parametros WHERE chave = 'api.limite_por_minuto'");
  try {
    console.log('\n== Onda 2, Etapa 6');
    /* os alunos de prova desta etapa (RA 9300xx) saem antes, para a prova poder rodar de novo */
    const ids = sql("SELECT IFNULL(GROUP_CONCAT(id), '') FROM col_usuarios WHERE rm LIKE '9300__' AND col_niveis_acesso_id = 1");
    if (ids !== '') {
      sql(`DELETE FROM portal_disciplinas_alunos WHERE rm LIKE '9300__'; DELETE FROM rm_disciplina_atividade_notas WHERE rm LIKE '9300__' AND nota IS NULL;
           DELETE FROM rm_disciplina_faltas WHERE rm LIKE '9300__' AND faltas = 0; DELETE FROM rm_matriculas_ensino WHERE usuario_id IN (${ids});
           DELETE FROM rm_matriculas_curso WHERE usuario_id IN (${ids}); DELETE FROM col_usuarios_enderecos WHERE usuario_id IN (${ids});
           DELETE FROM col_usuarios_informacoes WHERE col_user_id IN (${ids}); DELETE FROM col_usuarios WHERE id IN (${ids})`);
    }

    /* A. o menu e a rota das telas */
    const telas = ['painel/integracoes/chaves', 'painel/integracoes/documentacao', 'painel/integracoes/chamadas'];
    const sessoes = {};
    for (const [u, pode] of Object.entries({ administrador: true, 'supervisao.demo': false, 'secretaria.demo': false, 'direcao.demo': false, 'prof.laura.demo': false })) {
      const s = await entrar(navegador, u);
      sessoes[u] = s;
      const noMenu = (await s.p.$$('#railMenu a[href*="integracoes/"]')).length;
      let abre = 0;
      for (const t of telas) { if (await abriu(s.p, t)) { abre++; } }
      L.ok(pode ? noMenu === 3 && abre === 3 : noMenu === 0 && abre === 0, `${u}: ${pode ? 'Integrações no menu (3 itens) e as 3 telas abrem' : 'sem Integrações no menu e as 3 rotas recusadas'}`);
      if (!pode) { await s.ctx.close(); }
    }
    const adm = sessoes.administrador;
    const p = adm.p;

    /* B. as chaves pela tela */
    const chamadasAntes = um('SELECT COUNT(*) FROM adms_api_chamadas');
    const kLeitura = await criarChave(p, 'Prova — leitura', 'leitura');
    L.ok(/^vce_[0-9a-f]{40}$/.test(kLeitura), `a chave criada aparece na tela (${kLeitura.slice(0, 12)}…)`);
    await L.print(p, ETAPA, 'chave-criada');
    await p.reload();
    const hash = crypto.createHash('sha256').update(kLeitura).digest('hex');
    L.ok(!(await p.$('#chaveNova')) && um(`SELECT COUNT(*) FROM adms_api_chaves WHERE hash = '${hash}'`) === 1 && um(`SELECT COUNT(*) FROM adms_api_chaves WHERE hash = '${kLeitura}' OR prefixo = '${kLeitura}'`) === 0,
      'ao recarregar, a chave não aparece de novo; no banco fica só o hash (e o começo, para reconhecê-la)');
    const kEscrita = await criarChave(p, 'Prova — escrita', 'escrita');
    const kRevogar = await criarChave(p, 'Prova — a revogar', 'escrita');
    const kVencer = await criarChave(p, 'Prova — a vencer', 'leitura', new Date().toISOString().slice(0, 10));
    sql(`UPDATE adms_api_chaves SET validade = CURDATE() - INTERVAL 1 DAY WHERE hash = '${crypto.createHash('sha256').update(kVencer).digest('hex')}'`);
    await p.goto(L.BASE + 'painel/integracoes/chaves');
    p.once('dialog', (d) => d.accept());
    await L.confirmarSozinho(p); // D-100: a confirmação é a janela do sistema
    await Promise.all([p.waitForNavigation(), p.click(`#tabelaChaves tr[data-prefixo="${kRevogar.slice(0, 12)}"] .vc-revogar`)]);
    L.ok(await p.isVisible('#chaveRevogada') && /Revogada/.test(await p.$eval(`#tabelaChaves tr[data-prefixo="${kRevogar.slice(0, 12)}"]`, (e) => e.textContent)), 'revogar pela tela');
    await L.print(p, ETAPA, 'chaves');

    /* C. as recusas da chave */
    let r = await api('GET', '/cursos');
    L.ok(r.status === 401 && formatoErro(r, 'nao_autenticado') && /Bearer/.test(r.h.get('www-authenticate') || ''), 'sem chave: 401 (nao_autenticado)');
    r = await api('GET', '/cursos', { chave: 'vce_' + '0'.repeat(40) });
    L.ok(r.status === 401 && formatoErro(r, 'nao_autenticado'), 'chave inexistente: 401');
    r = await api('GET', '/cursos', { chave: kRevogar });
    L.ok(r.status === 401 && formatoErro(r, 'chave_revogada'), 'chave revogada: 401 (chave_revogada)');
    r = await api('GET', '/cursos', { chave: kVencer });
    L.ok(r.status === 401 && formatoErro(r, 'chave_vencida'), 'chave vencida: 401 (chave_vencida)');
    r = await api('POST', '/alunos', { chave: kLeitura, corpo: { ra: '930099', nome: 'Não Deve Gravar', sexo: 'feminino' } });
    L.ok(r.status === 403 && formatoErro(r, 'sem_permissao') && um("SELECT COUNT(*) FROM col_usuarios WHERE rm = '930099'") === 0, 'a chave de leitura tentando gravar: 403 (sem_permissao), nada gravado');

    /* as consultas */
    const curso = um('SELECT id FROM rm_cursos ORDER BY id LIMIT 1');
    for (const [rota, cond] of [
      ['/cursos', (j) => j.dados.length === um('SELECT COUNT(*) FROM rm_cursos')],
      [`/cursos/${curso}/grades`, (j) => j.dados.length >= 1 && 'aceita_vinculo' in j.dados[0]],
      ['/anos-letivos', (j) => j.dados.some((a) => a.ano === ANO)],
      ['/polos', (j) => j.dados.length === um('SELECT COUNT(*) FROM rm_polos')],
      ['/modalidades', (j) => j.dados.length >= 1],
      ['/formas-ingresso', (j) => j.dados.length >= 1],
      ['/disciplinas', (j) => j.dados.length === um('SELECT COUNT(*) FROM rm_materias')],
      [`/ofertas?ano=${ANO}&por_pagina=2`, (j) => j.dados.length <= 2 && j.paginacao.total === um(`SELECT COUNT(*) FROM rm_professores_materias WHERE ano = ${ANO}`)],
      ['/matriculas?por_pagina=3', (j) => j.dados.length === 3 && j.paginacao.total === um('SELECT COUNT(*) FROM rm_matriculas_curso')],
      ['/alunos?por_pagina=4&pagina=2', (j) => j.dados.length === 4 && j.paginacao.pagina === 2 && j.paginacao.total === um('SELECT COUNT(*) FROM col_usuarios WHERE col_niveis_acesso_id = 1')],
    ]) {
      r = await api('GET', rota, { chave: kLeitura });
      L.ok(r.status === 200 && r.json && cond(r.json), `GET ${rota}: 200, conferido com o banco`);
    }
    const raDemo = sql('SELECT rm FROM col_usuarios WHERE col_niveis_acesso_id = 1 ORDER BY id LIMIT 1');
    r = await api('GET', '/alunos/' + raDemo, { chave: kLeitura });
    const r2 = await api('GET', '/alunos?ra=' + raDemo, { chave: kLeitura });
    L.ok(r.status === 200 && r.json.dados.ra === raDemo && r2.json.dados.length === 1, `GET /alunos/${raDemo} e o filtro por RA`);
    r = await api('GET', '/alunos/999999999', { chave: kLeitura });
    L.ok(r.status === 404 && formatoErro(r, 'nao_encontrado') && r.json.erro.campo === 'ra', 'aluno inexistente: 404 (nao_encontrado, campo ra)');

    /* D. criar pela API e pela tela: as mesmas colunas */
    const corpo = { ra: '930001', nome: 'Aluna Via Integração Exemplo', sexo: 'feminino', cpf: '529.982.247-25', email: 'aluna.api@exemplo.invalid', nascimento: '2001-04-09', telefone: '(11) 3333-4444' };
    r = await api('POST', '/alunos', { chave: kEscrita, corpo });
    L.ok(r.status === 201 && r.json.criado === true && r.json.dados.ra === '930001' && r.json.dados.cpf === '52998224725' && /api\/v1\/alunos\/930001$/.test(r.h.get('location') || ''), 'POST /alunos: 201, o aluno criado (e o Location)');
    await p.goto(L.BASE + 'painel/academicos/cadastrar');
    await p.fill('#rm', '930002');
    await p.fill('#nome', corpo.nome);
    await p.selectOption('#genero_id', '1');
    await p.fill('#cpf', '');
    await p.fill('#email', 'aluna.tela@exemplo.invalid');
    await p.fill('#nasc_data', corpo.nascimento);
    await p.fill('#telefone', corpo.telefone);
    await Promise.all([p.waitForNavigation(), p.click('input[name="CadUsuarioAcad"]')]);
    const colunas = (rm) => sql(`SELECT CONCAT_WS('|', u.nome, IFNULL(u.nome_social,'-'), u.genero_id, IF(u.genero_confirmado_em IS NULL,'n','s'), u.senha, u.col_niveis_acesso_id, u.col_sits_usuario_id, IFNULL(u.telefone,'-'), IFNULL(u.celular,'-'), IFNULL(i.nasc_data,'-'), IFNULL(i.rg,'-'), IF(e.id IS NULL,'sem','com'), e.situacao, u.usuario = u.rm)
      FROM col_usuarios u LEFT JOIN col_usuarios_informacoes i ON i.col_user_id = u.id LEFT JOIN col_usuarios_enderecos e ON e.usuario_id = u.id WHERE u.rm = '${rm}'`);
    const viaApi = colunas('930001');
    const viaTela = colunas('930002');
    L.ok(viaApi !== '' && viaApi === viaTela, `o que a API gravou é o que a tela grava, coluna a coluna (${viaApi.split('|').length} colunas iguais)`);

    /* RA repetido: o existente, sem duplicar */
    r = await api('POST', '/alunos', { chave: kEscrita, corpo: { ...corpo, nome: 'Outro Nome Qualquer' } });
    L.ok(r.status === 200 && r.json.criado === false && r.json.dados.nome === corpo.nome && um("SELECT COUNT(*) FROM col_usuarios WHERE rm = '930001'") === 1, 'RA repetido: 200 com o aluno existente (criado: false), sem duplicar nem mudar');
    r = await api('POST', '/alunos', { chave: kEscrita, corpo: { ra: '930004', nome: 'CPF Errado Exemplo', sexo: 'masculino', cpf: '123.456.789-00' } });
    L.ok(r.status === 422 && formatoErro(r, 'validacao') && r.json.erro.campo === 'cpf' && /CPF inválido/.test(r.json.erro.mensagem), 'CPF inválido: 422 com a mensagem da tela e o campo');
    r = await api('POST', '/alunos', { chave: kEscrita, corpo: { ra: '930005', nome: 'Sem Sexo Exemplo' } });
    L.ok(r.status === 422 && r.json.erro.campo === 'sexo', 'sem o sexo: 422 (a mesma regra da tela)');
    r = await api('POST', '/alunos', { chave: kEscrita, corpo: '{"ra": "9300' });
    L.ok(r.status === 400 && formatoErro(r, 'json_invalido'), 'JSON quebrado: 400 (json_invalido)');

    /* Idempotency-Key */
    const idem = 'prova-' + Date.now();
    const novo = { ra: '930003', nome: 'Aluno Idempotente Exemplo', sexo: 'masculino' };
    const i1 = await api('POST', '/alunos', { chave: kEscrita, corpo: novo, idem });
    const i2 = await api('POST', '/alunos', { chave: kEscrita, corpo: novo, idem });
    L.ok(i1.status === 201 && i2.status === 201 && i2.h.get('idempotent-replayed') === 'true' && JSON.stringify(i1.json) === JSON.stringify(i2.json) && um("SELECT COUNT(*) FROM col_usuarios WHERE rm = '930003'") === 1,
      'Idempotency-Key repetida: a mesma resposta (Idempotent-Replayed), gravado uma vez só');
    const i3 = await api('POST', '/alunos', { chave: kEscrita, corpo: { ...novo, ra: '930006' }, idem });
    L.ok(i3.status === 409 && formatoErro(i3, 'idempotencia_conflito') && um("SELECT COUNT(*) FROM col_usuarios WHERE rm = '930006'") === 0, 'a mesma Idempotency-Key com outro corpo: 409, nada gravado');

    /* PATCH */
    r = await api('PATCH', '/alunos/930001', { chave: kEscrita, corpo: { email: 'nova.api@exemplo.invalid', celular: '(11) 98888-7777' } });
    L.ok(r.status === 200 && r.json.dados.email === 'nova.api@exemplo.invalid' && sql("SELECT CONCAT(email, '|', celular, '|', nome) FROM col_usuarios WHERE rm = '930001'") === `nova.api@exemplo.invalid|11988887777|${corpo.nome}`,
      'PATCH /alunos/930001: só os campos enviados mudam');
    r = await api('PATCH', '/alunos/930001', { chave: kEscrita, corpo: { ra: '930777' } });
    L.ok(r.status === 422 && r.json.erro.campo === 'ra', 'o RA não muda pela API (422)');

    /* matrícula, situação, enturmação */
    const forma = um('SELECT id FROM rm_matricula_tipos WHERE situacao = 1 ORDER BY id LIMIT 1');
    r = await api('POST', '/matriculas', { chave: kEscrita, corpo: { ra: '930001', curso_id: curso, ano: ANO, forma_ingresso_id: forma, data_ingresso: `${ANO}-02-02` } });
    const mc = r.json && r.json.dados ? r.json.dados.id : 0;
    L.ok(r.status === 201 && r.json.dados.situacao === 'cursando' && r.json.dados.anos.length === 1 && um(`SELECT COUNT(*) FROM rm_matriculas_ensino WHERE matricula_curso_id = ${mc} AND ano = ${ANO}`) === 1,
      `POST /matriculas: 201, o vínculo e a posição no ano (pela função da tela) — matrícula ${mc}`);
    r = await api('POST', '/matriculas', { chave: kEscrita, corpo: { ra: '930001', curso_id: curso, ano: ANO, forma_ingresso_id: forma, data_ingresso: `${ANO}-02-02` } });
    L.ok(r.status === 409 && formatoErro(r, 'conflito'), 'vincular de novo ao mesmo curso: 409 (a regra da tela)');
    r = await api('GET', `/matriculas/${mc}`, { chave: kLeitura });
    const r3 = await api('GET', '/matriculas?ra=930001', { chave: kLeitura });
    L.ok(r.status === 200 && r.json.dados.ra === '930001' && r3.json.dados.length === 1, 'GET /matriculas/{id} e o filtro por RA');
    r = await api('POST', `/matriculas/${mc}/trancar`, { chave: kEscrita });
    const tr2 = await api('POST', `/matriculas/${mc}/trancar`, { chave: kEscrita });
    L.ok(r.status === 200 && r.json.dados.situacao === 'trancado' && um(`SELECT situacao_id FROM rm_matriculas_curso WHERE id = ${mc}`) === 3 && tr2.status === 409, 'trancar: 200 (e de novo: 409)');
    r = await api('POST', `/matriculas/${mc}/reativar`, { chave: kEscrita });
    L.ok(r.status === 200 && r.json.dados.situacao === 'cursando', 'reativar a trancada: 200');
    r = await api('GET', `/matriculas/${mc}/enturmacao`, { chave: kLeitura });
    const oferta = r.json && r.json.dados ? r.json.dados.ofertas.find((o) => !o.encerrada) : null;
    L.ok(r.status === 200 && r.json.dados.ano === ANO && !!oferta, `GET a enturmação: ${r.json && r.json.dados ? r.json.dados.ofertas.length : 0} oferta(s) do ano`);
    r = await api('POST', `/matriculas/${mc}/enturmacao`, { chave: kEscrita, corpo: { ofertas: [oferta.oferta_id] } });
    L.ok(r.status === 200 && r.json.dados.ofertas.find((o) => o.oferta_id === oferta.oferta_id).enturmado === true && um(`SELECT COUNT(*) FROM portal_disciplinas_alunos WHERE rm = '930001' AND rm_prof_mat_id = ${oferta.oferta_id}`) === 1,
      'POST a enturmação (a função da tela): o aluno na oferta');
    r = await api('POST', `/matriculas/${mc}/cancelar`, { chave: kEscrita });
    L.ok(r.status === 200 && r.json.dados.situacao === 'cancelado', 'cancelar: 200');
    r = await api('POST', `/matriculas/${mc}/reativar`, { chave: kEscrita });
    L.ok(r.status === 409 && formatoErro(r, 'conflito'), 'reativar a cancelada: 409 (só a trancada volta)');

    /* rotas e métodos, e o que a resposta não revela */
    r = await api('GET', '/nao-existe', { chave: kLeitura });
    const r405 = await api('DELETE', '/alunos', { chave: kEscrita });
    L.ok(r.status === 404 && formatoErro(r, 'rota_inexistente') && r405.status === 405 && /GET/.test(r405.h.get('allow') || ''), 'rota inexistente: 404; método errado: 405 com Allow');
    const vaz = [r, r405, await api('POST', '/alunos', { chave: kEscrita, corpo: '[1,2' }), await api('POST', '/matriculas', { chave: kEscrita, corpo: { ra: '930001', curso_id: 'x' } })];
    L.ok(vaz.every((x) => !/[A-Z]:\\|\/xampp|\.php|SQLSTATE|SELECT |Stack|Warning|Fatal/i.test(x.texto)), 'nenhuma resposta revela caminho de servidor, PHP ou SQL');
    const opt = await fetch(API + '/cursos', { method: 'OPTIONS', headers: { Origin: 'https://outro.exemplo.invalid', 'Access-Control-Request-Method': 'GET' } });
    const g = await fetch(API + '/cursos', { headers: { Origin: 'https://outro.exemplo.invalid', Authorization: 'Bearer ' + kLeitura } });
    L.ok(!opt.headers.get('access-control-allow-origin') && !g.headers.get('access-control-allow-origin'), 'sem CORS aberto: nenhum Access-Control-Allow-Origin');

    /* o limite por minuto */
    const kLimite = await criarChave(p, 'Prova — limite', 'leitura');
    const kOutra = await criarChave(p, 'Prova — outra', 'leitura');
    sql("UPDATE adms_parametros SET valor = '5' WHERE chave = 'api.limite_por_minuto'");
    const st = [];
    for (let i = 0; i < 6; i++) { st.push((await api('GET', '/anos-letivos', { chave: kLimite })).status); }
    const r429 = await api('GET', '/anos-letivos', { chave: kLimite });
    const outra = await api('GET', '/anos-letivos', { chave: kOutra });
    sql(`UPDATE adms_parametros SET valor = '${limiteOriginal}' WHERE chave = 'api.limite_por_minuto'`);
    L.ok(st.slice(0, 5).every((s) => s === 200) && st[5] === 429 && formatoErro(r429, 'limite_excedido') && r429.h.get('retry-after') === '60' && outra.status !== 429,
      `o limite por chave (5 por minuto, de prova): ${st.join(', ')} — 429 com Retry-After, e a outra chave segue`);

    /* E. o registro das chamadas */
    const regs = um(`SELECT COUNT(*) FROM adms_api_chamadas WHERE id > 0`) - chamadasAntes;
    const semIp = um("SELECT COUNT(*) FROM adms_api_chamadas WHERE ip IS NULL OR ip = ''");
    const negadas = um("SELECT COUNT(*) FROM adms_api_chamadas WHERE status = 401 AND chave_id IS NULL");
    L.ok(regs >= 50 && semIp === 0 && negadas >= 3 && um(`SELECT COUNT(*) FROM adms_api_chamadas WHERE prefixo = '${kEscrita.slice(0, 12)}' AND operacao = 'alunosCriar' AND status = 201`) >= 2,
      `toda chamada registrada (${regs}): a chave, a operação, o resultado, o IP — inclusive as recusadas sem chave`);
    await p.goto(L.BASE + 'painel/integracoes/chamadas?resultado=erro');
    const linhas = await p.$$eval('#tabelaChamadas tbody tr', (t) => t.map((x) => +x.dataset.status));
    L.ok(linhas.length > 0 && linhas.every((s) => s >= 400), `a tela das chamadas, com o filtro "com erro" (${linhas.length} linhas)`);
    await L.print(p, ETAPA, 'chamadas');
    await p.goto(L.BASE + 'painel/integracoes/chaves');
    L.ok(/\d{2}\/\d{2}\/\d{4}/.test(await p.$eval(`#tabelaChaves tr[data-prefixo="${kEscrita.slice(0, 12)}"] td:nth-child(5)`, (e) => e.textContent)), 'o último uso da chave na tela');

    /* a especificação e o servidor */
    const spec = JSON.parse(fs.readFileSync(path.join(L.RAIZ, 'docs', 'api', 'openapi.json'), 'utf8'));
    const doSpec = Object.entries(spec.paths).flatMap(([c, ops]) => Object.keys(ops).map((m) => `${m.toUpperCase()} ${c}`)).sort();
    const doServidor = JSON.parse(execFileSync(PHP, [path.join(L.RAIZ, 'tests', 'tabler', 'onda2', 'rotas_api.php')]).toString()).map((x) => `${x.metodo} ${x.caminho}`).sort();
    const soSpec = doSpec.filter((x) => !doServidor.includes(x));
    const soServ = doServidor.filter((x) => !doSpec.includes(x));
    L.ok(soSpec.length === 0 && soServ.length === 0, `a especificação e o servidor: as mesmas ${doSpec.length} operações${soSpec.length || soServ.length ? ' — só na especificação: ' + soSpec.join(', ') + '; só no servidor: ' + soServ.join(', ') : ''}`);
    const naoResponde = [];
    /* Onda 4: a rota de outro tipo de instituição (x-tipo: escola, numa instalação de ensino superior) não existe aqui */
    const tipoDe = Object.fromEntries(Object.entries(spec.paths).flatMap(([c, ops]) => Object.entries(ops).map(([m, o]) => [`${m.toUpperCase()} ${c}`, o['x-tipo'] || ''])));
    const deOutroTipo = [];
    for (const op of doSpec) {
      const [m, c] = op.split(' ');
      if (tipoDe[op] === 'escola') {
        const y = await api(m, c.replace('{id}', '1'), { chave: kLeitura, corpo: m === 'GET' ? undefined : {} });
        if (!(y.status === 404 && y.json && y.json.erro.codigo === 'rota_inexistente')) { naoResponde.push(`${op} (só da escola): ${y.status}`); }
        deOutroTipo.push(op);
        continue;
      }
      const rota = c.replace('{ra}', '930001').replace('{id}', c.startsWith('/cursos') ? String(curso) : String(mc));
      const x = await api(m, rota, { chave: kLeitura, corpo: m === 'GET' ? undefined : {} });
      if (x.status === 404 && x.json && x.json.erro.codigo === 'rota_inexistente' || x.status === 405 || x.status >= 500 || (m !== 'GET' && x.status !== 403) || (m === 'GET' && x.status !== 200)) { naoResponde.push(`${op}: ${x.status}`); }
    }
    L.ok(naoResponde.length === 0, `toda rota da especificação responde (as consultas 200; as escritas, com a chave de leitura, 403; as ${deOutroTipo.length} só da escola, inexistentes no ensino superior)${naoResponde.length ? ' — ' + naoResponde.join(', ') : ''}`);

    /* a documentação: o Swagger UI local */
    const externos = [];
    p.on('request', (q) => { if (!q.url().startsWith(L.BASE) && !q.url().startsWith('data:')) { externos.push(q.url()); } });
    await p.goto(L.BASE + 'painel/integracoes/documentacao');
    await p.waitForSelector('#swaggerUi[data-pronto="1"]', { timeout: 20000 });
    await p.waitForTimeout(800);
    const ops = await p.$$eval('#swaggerUi .opblock', (o) => o.length);
    /* Onda 7: no ensino superior, a documentação tira as operações de assunto só da escola (os responsáveis) */
    const soEscola = doSpec.filter((op) => { const [m, c] = op.split(' '); return spec.paths[c][m.toLowerCase()]['x-assunto'] === 'responsaveis'; });
    const esperadas = doSpec.length - soEscola.length;
    L.ok(soEscola.length > 0 && ops === esperadas, `a documentação: o Swagger UI com as ${ops} operações (sem as ${soEscola.length} dos responsáveis, que não existem aqui)`);
    await p.click('#swaggerUi .scheme-container .btn.authorize');
    await p.fill('#auth-bearer-value', kLeitura);
    await p.click('.auth-btn-wrapper button[type="submit"]');
    await p.click('.auth-btn-wrapper .btn-done');
    const OP = '.opblock[id$="-anosListar"]'; // o id leva o grupo ("Cadastros de apoio"): o seletor não depende dele
    await p.click(`${OP} .opblock-summary-control`);
    await p.click(`${OP} .try-out__btn`);
    await p.click(`${OP} .btn.execute`);
    await p.waitForSelector(`${OP} .live-responses-table tbody .response-col_status`, { timeout: 10000 });
    const statusTeste = await p.$eval(`${OP} .live-responses-table tbody .response-col_status`, (e) => e.textContent.trim());
    L.ok(statusTeste === '200', `o teste pela documentação, com a chave colada: GET /anos-letivos → ${statusTeste}`);
    await L.print(p, ETAPA, 'documentacao');
    L.ok(externos.length === 0, `a documentação não pede nada fora do sistema${externos.length ? ' — ' + externos.slice(0, 3).join(', ') : ''}`);

    /* F. sabotagem: sem a conferência do escopo */
    const orig = fs.readFileSync(SERVIDOR, 'utf8');
    await sabotar('a chave de leitura deixa de ser conferida nas escritas',
      () => fs.writeFileSync(SERVIDOR, orig.replace('if (!AdmsApiChaves::pode($chave, $rota[4], $rota[3])) {', 'if (false) {')),
      () => fs.writeFileSync(SERVIDOR, orig),
      async () => { const x = await api('POST', '/alunos', { chave: kLeitura, corpo: { ra: '930098', nome: 'Sabotagem Exemplo', sexo: 'outros' } }); return x.status === 403 && um("SELECT COUNT(*) FROM col_usuarios WHERE rm = '930098'") === 0; });
    L.ok(fs.readFileSync(SERVIDOR, 'utf8') === orig, 'o servidor voltou ao original depois da sabotagem');

    await adm.ctx.close();
  } catch (e) {
    L.ok(false, 'erro na prova: ' + e.message.split('\n')[0]);
  } finally {
    sql(`UPDATE adms_parametros SET valor = '${limiteOriginal || 120}' WHERE chave = 'api.limite_por_minuto'`);
    await navegador.close();
    process.exit(L.resumo('onda 2, etapa 6') ? 0 : 1);
  }
})();
