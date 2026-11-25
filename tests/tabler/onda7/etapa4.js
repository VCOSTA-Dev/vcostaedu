/*
 * PROVA DA ONDA 7, ETAPA 4 — o portal do responsável (D-64).
 *   A. no ensino superior (o banco que o ciclo deixa): as páginas da família não abrem (voltam à entrada);
 *   B. na escola (a demonstração do colégio, montada pela tela; APAGA o banco de prova):
 *      1. o responsável de dois filhos vê os dois; escolhe um; o seletor troca de filho;
 *      2. o seletor de ano mostra só os anos em que ele tem papel naquele filho;
 *      3. o boletim do filho é o MESMO que o aluno vê (a mesma regra de liberação); as notas, a frequência, o horário e
 *         a turma (o turno, a sala, os componentes e os professores), só para leitura;
 *      4. o atestado de matrícula: emitido pelo responsável, o PDF abre;
 *      5. com um filho só, entra direto nele;
 *      6. o celular: nenhuma tela da família com rolagem para o lado;
 *      7. nada do financeiro em tela nenhuma (nem no menu);
 *      8. a barreira a cada requisição: o aluno de outra família e o ano sem papel, recusados (403), sem dizer se o
 *         aluno existe;
 *   sabotagem: a página do filho deixa de perguntar à barreira — a prova acusa (abre o aluno de outra família); o
 *   arquivo volta igual (sha256).
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { spawnSync, execFileSync } = require('child_process');
const L = require('../lib');

const ETAPA = 'onda7/etapa4';
const SAIDA = path.join(__dirname, '..', 'saida');
const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste$/.test(BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
const SENHA = process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123';
const CTRL = path.join(L.RAIZ, 'portal', 'app', 'adms', 'Controllers', 'Familia.php');
const ANO = 2026;
const ANTERIOR = 2025;
const SENHA_R = 'girassol no quintal azul de domingo';
const SENHA_ALUNO = 'jabuticaba madura no pe de casa';

const sql = (q) => execFileSync('C:/xampp/mysql/bin/mysql.exe', ['-uroot', '--default-character-set=utf8mb4', '-N', '-B', BANCO, '-e', q], { encoding: 'utf8' }).trim();
const um = (q) => +(sql(q).split('\n')[0] || 0);
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const hash = (s) => execFileSync('C:/xampp/php/php.exe', ['-r', 'echo password_hash($argv[1], PASSWORD_DEFAULT);', s], { encoding: 'utf8' }).trim();
const idAluno = (ra) => um(`SELECT id FROM col_usuarios WHERE rm = '${ra}'`);

async function entrarPainel(navegador) {
  const ctx = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' });
  const p = await L.pagina(ctx);
  await p.goto(L.BASE + 'painel/login/acesso');
  await p.fill('input[name="usuario"]', 'administrador');
  await p.fill('input[name="senha"]', process.env.SENHA_ADM || SENHA);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
  return { ctx, p };
}
async function postar(p, rota, campos) {
  await p.evaluate(async ([u, c]) => { const f = new URLSearchParams(); for (const [k, v] of Object.entries(c)) { f.append(k, v); }
    await fetch(u, { method: 'POST', body: f, credentials: 'same-origin', redirect: 'manual' }); }, [L.BASE + 'painel/' + rota, campos]);
}
async function entrarPortal(navegador, usuario, senha, largura = 390) {
  const ctx = await navegador.newContext({ viewport: { width: largura, height: 844 }, locale: 'pt-BR' });
  const p = await L.pagina(ctx);
  await p.goto(L.BASE + 'portal/login/acesso');
  await p.fill('input[name="usuario"]', usuario);
  await p.fill('input[name="senha"]', senha);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
  return { ctx, p };
}
/** Abre a página e devolve o status, a largura (rolagem para o lado) e o texto. */
async function abre(p, rota) {
  const r = await p.goto(L.BASE + 'portal/' + rota);
  const larg = await p.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
  /* Onda 8, financeiro (D-98): o cartão "Ficha financeira" do responsável financeiro é o único financeiro das telas da
     família; fica fora do texto conferido, e onde ele aparece é conferido à parte */
  const t = await p.evaluate(() => { const c = document.body.cloneNode(true); c.querySelectorAll('.vc-cartao-financeiro').forEach((x) => x.remove()); document.body.appendChild(c); c.style.display = 'block'; const s = c.innerText; c.remove(); return s.replace(/\s+/g, ' '); });
  return { status: r ? r.status() : 0, larg, texto: t, url: p.url(), cartao: !!(await p.$('.vc-cartao-financeiro')) };
}
const tabelas = (p) => p.$$eval('.boletimCurso table', (l) => l.map((t) => t.innerText.replace(/\s+/g, ' ').trim()).join(' || ')).catch(() => '');

(async () => {
  const { navegador } = await L.abrir();
  const shaCtrl = sha(CTRL);
  try {
    console.log('\n== Onda 7, Etapa 4 — o portal do responsável');
    /* A. o ensino superior */
    const vis = await navegador.newContext({ locale: 'pt-BR' });
    const pv = await L.pagina(vis);
    await pv.goto(L.BASE + 'portal/familia/index');
    const sup1 = await pv.isVisible('[name="SendLogin"]');
    await pv.goto(L.BASE + 'portal/familia/boletim/1?ano=2026');
    L.ok(sup1 && (await pv.isVisible('[name="SendLogin"]')) && !(await pv.$('#cabecalhoFilho')), 'no ensino superior: as páginas da família mostram a entrada');
    await vis.close();

    /* B. a escola */
    const r = spawnSync(process.execPath, [path.join(__dirname, '..', 'onda6', 'medir_demo.js'), 'escola', 'onda7-etapa4'], { encoding: 'utf8', maxBuffer: 1 << 24 });
    require('./_antes_d99')(); // D-99: os responsáveis como antes do financeiro refeito (os alunos sem responsável, as pessoas sem celular)
    L.ok(r.status === 0, 'a demonstração do colégio montada pela tela');
    const s = await entrarPainel(navegador);
    const a1 = idAluno('265001');
    const a25 = idAluno('255001');
    const a3 = idAluno('265003');
    const cad = async (nome, email) => { await postar(s.p, 'responsaveis/cadastrar', { nome, email, celular: '', cpf: '' }); return um(`SELECT id FROM esc_responsaveis WHERE email = '${email}'`); };
    const des = (aluno, ano, papel, pessoa) => postar(s.p, 'responsaveis/designar', { aluno: String(aluno), ano: String(ano), papel, responsavel_id: String(pessoa), parentesco: 'Mãe', data: `${ano}-02-01` });
    const r1 = await cad('Mãe Portal Exemplo', 'mae.portal@exemplo.invalid');
    const r2 = await cad('Pai Portal Exemplo', 'pai.portal@exemplo.invalid');
    await des(a1, ANO, 'financeiro', r1);
    await des(a25, ANTERIOR, 'titular', r1);
    await des(a1, ANO, 'titular', r2);
    sql(`UPDATE esc_responsaveis SET senha = '${hash(SENHA_R)}', ativado_em = NOW(), login = email WHERE id IN (${r1}, ${r2})`); // como o convite faz (Onda 8, D-67: o login, o e-mail)
    sql(`UPDATE col_usuarios SET senha = '${hash(SENHA_ALUNO)}', col_sits_usuario_id = 1 WHERE id = ${a1}`);
    await s.ctx.close();

    /* 1 e 2. os filhos e os anos */
    let e = await entrarPortal(navegador, 'mae.portal@exemplo.invalid', SENHA_R);
    const filhos = await e.p.$$eval('#listaFilhos [data-aluno]', (l) => l.map((x) => +x.getAttribute('data-aluno'))).catch(() => []);
    const semFin = !/Financeiro/i.test(await e.p.innerText('#railMenu').catch(() => ''));
    await L.print(e.p, ETAPA, 'filhos-celular');
    const f1 = await abre(e.p, `familia/filho/${a1}`);
    const anos1 = await e.p.$$eval('#trocarAno option', (l) => l.map((o) => o.textContent.trim())).catch(() => []);
    const troca = await e.p.$$eval('#trocarFilho option', (l) => l.length).catch(() => 0);
    const f25 = await abre(e.p, `familia/filho/${a25}`);
    const ano25 = await e.p.getAttribute('#cabecalhoFilho', 'data-ano').catch(() => '');
    L.ok(filhos.length === 2 && filhos.includes(a1) && filhos.includes(a25) && troca === 2 && f1.status === 200,
      'o responsável de dois filhos vê os dois, escolhe um, e o seletor troca de filho');
    L.ok(anos1.length === 0 && ano25 === String(ANTERIOR) && /2025/.test(f25.texto),
      'o seletor de ano: só os anos em que ele tem papel naquele filho (2026 num, 2025 no outro)');

    /* 3. as telas do filho */
    const bol = await abre(e.p, `familia/boletim/${a1}?ano=${ANO}`);
    const tabResp = await tabelas(e.p);
    const notas = await abre(e.p, `familia/notas/${a1}?ano=${ANO}`);
    const freq = await abre(e.p, `familia/frequencia/${a1}?ano=${ANO}`);
    const hor = await abre(e.p, `familia/horario/${a1}?ano=${ANO}`);
    const tur = await abre(e.p, `familia/turma/${a1}?ano=${ANO}`);
    const profs = await e.p.$$eval('#componentesTurma tbody tr', (l) => l.filter((t) => !/—$/.test(t.innerText.trim())).length).catch(() => 0);
    await L.print(e.p, ETAPA, 'turma-celular');
    const ea = await entrarPortal(navegador, '265001', SENHA_ALUNO);
    await ea.p.goto(L.BASE + `portal/home/boletim-aluno?filtro_ano=${ANO}`);
    const tabAluno = await tabelas(ea.p);
    await ea.ctx.close();
    L.ok(tabResp && tabResp === tabAluno, 'o boletim do filho é o mesmo que o aluno vê no portal dele (a mesma regra de liberação)');
    L.ok([bol, notas, freq, hor, tur].every((x) => x.status === 200) && profs > 0 && /Turno/.test(tur.texto) && /Sala/.test(tur.texto),
      'as notas, a frequência, o horário e a turma (o turno, a sala, os componentes com o professor)');
    const leitura = await e.p.$$eval('form', (l) => l.filter((f) => !/familia\/atestado/.test(f.action)).length);
    L.ok(leitura === 0, 'só para leitura: nenhum formulário nas telas do filho (só o do atestado)');

    /* 4. o atestado */
    /* a demonstração ainda não tem a previsão de conclusão nem o CPF do aluno (sem eles o atestado não sai): primeiro a
       recusa, com a orientação; depois a secretaria preenche (como na ficha do aluno, aqui direto no banco de prova) e o
       responsável emite */
    await abre(e.p, `familia/atestado/${a1}?ano=${ANO}`);
    let semPrev = '';
    if (um(`SELECT COUNT(*) FROM rm_matriculas_curso WHERE usuario_id = ${a1} AND previsao_conclusao IS NULL`) > 0 && await e.p.$('#emitirAtestado')) {
      await Promise.all([e.p.waitForNavigation(), e.p.click('#emitirAtestado')]);
      semPrev = await e.p.textContent('#atestadoErro').catch(() => '');
      sql(`UPDATE rm_matriculas_curso SET previsao_conclusao = '2029-12-15' WHERE usuario_id = ${a1} AND previsao_conclusao IS NULL`);
    }
    sql(`UPDATE col_usuarios_informacoes SET cpf = '529.982.247-25' WHERE col_user_id = ${a1} AND (cpf IS NULL OR cpf = '')`);
    const at = await abre(e.p, `familia/atestado/${a1}?ano=${ANO}`);
    let pdf = 0;
    if (await e.p.$('#emitirAtestado')) {
      await Promise.all([e.p.waitForNavigation(), e.p.click('#emitirAtestado')]);
      const href = await e.p.getAttribute('#atestadosEmitidos a', 'href').catch(() => '');
      if (href) { pdf = await e.p.evaluate(async (u) => { const x = await fetch(u, { credentials: 'same-origin' }); return x.ok && /pdf/.test(x.headers.get('content-type') || '') ? (await x.arrayBuffer()).byteLength : 0; }, href); }
    }
    const aud = um(`SELECT COUNT(*) FROM esc_responsavel_auditoria WHERE acao = 'atestado_emitido' AND responsavel_id = ${r1}`);
    L.ok(at.status === 200 && pdf > 1000 && aud === 1 && (semPrev === '' || /secretaria/.test(semPrev)),
      `o atestado de matrícula: sem a previsão de conclusão, a orientação; com ela, o responsável emite, o PDF abre (${pdf} bytes) e fica no registro`);

    /* 6 e 7. o celular e o financeiro */
    const telas = [f1, f25, bol, notas, freq, hor, tur, at];
    L.ok(telas.every((x) => x.larg <= 1), 'no celular: nenhuma tela da família com rolagem para o lado');
    L.ok(semFin && telas.every((x) => !/financeir|cobran|boleto|mensalidade/i.test(x.texto)) && telas.filter((x) => x.cartao).every((x) => /familia\/filho\//.test(x.url)),
      'nada do financeiro: nem no menu, nem nas telas (desde a Onda 8, D-98, só o cartão "Ficha financeira" do responsável financeiro, no Resumo do filho)');

    /* 8. a barreira */
    const outro = await abre(e.p, `familia/boletim/${a3}?ano=${ANO}`);
    const semAno = await abre(e.p, `familia/boletim/${a1}?ano=${ANTERIOR}`);
    const inexistente = await abre(e.p, `familia/boletim/99999999?ano=${ANO}`);
    L.ok(outro.status === 403 && semAno.status === 403 && inexistente.status === 403 && outro.texto.includes('não está disponível') && !outro.texto.includes(sql(`SELECT nome FROM col_usuarios WHERE id = ${a3}`))
      && outro.texto.replace(/\d+/g, '') === inexistente.texto.replace(/\d+/g, ''),
      'a barreira: o aluno de outra família e o ano sem papel, recusados (403); a recusa é a mesma para o aluno que não existe');

    /* sabotagem */
    const orig = fs.readFileSync(CTRL, 'utf8');
    const ponto = "if (!$anos || !$this->acesso()->pode($resp, $aluno, $ano, \\VcAcessoFamilia::ACADEMICO)) {";
    if (!orig.includes(ponto)) throw new Error('a sabotagem não achou o ponto da barreira');
    fs.writeFileSync(CTRL, orig.replace(ponto, 'if (false) {'));
    let sab;
    try { sab = await abre(e.p, `familia/boletim/${a3}?ano=${ANO}`); } finally { fs.writeFileSync(CTRL, orig); }
    L.ok(sab.status === 200, 'sabotagem (a página do filho sem perguntar à barreira): a prova acusa — o aluno de outra família abriu');
    L.ok(sha(CTRL) === shaCtrl, 'o arquivo sabotado voltou igual (sha256)');
    await e.ctx.close();

    /* 5. um filho só */
    e = await entrarPortal(navegador, 'pai.portal@exemplo.invalid', SENHA_R);
    L.ok(new RegExp(`familia/filho/${a1}`).test(e.p.url()), 'com um filho só, entra direto nele');
    await e.ctx.close();
  } catch (err) {
    L.ok(false, 'a prova parou: ' + (err.stack || err.message).slice(0, 500));
  } finally {
    if (sha(CTRL) !== shaCtrl) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
