/*
 * ONDA 3, ETAPA 2 — o modo estrito do banco (e, do complemento, o boletim e as disciplinas do professor).
 *   A. o modo fixado em toda porta: a verificação do ambiente o informa; pelo painel, pelo portal e pela API, uma gravação
 *      que só passava SEM o modo estrito (um nome maior que a coluna) é recusada com mensagem clara, sem erro do PHP, e
 *      nada é gravado pela metade;
 *   B. as correções que o modo estrito revelou: o perfil do portal grava (sem pôr NULL no usuário), o perfil do painel
 *      confere o e-mail repetido;
 *   C. o boletim do aluno para o professor: o do aluno dele abre (tela e PDF), o de outro é recusado pelo endereço;
 *   D. "Minhas disciplinas" do professor que coordena: as dele com os atalhos de lançar, as do curso coordenado com a
 *      marca "só consulta" e sem eles;
 *   E. sabotagens: sem o modo estrito, o nome longo grava cortado; sem a regra do boletim, o de outro aluno abre.
 *
 *   node tests/tabler/onda3/etapa2.js     (depois do ciclo, com a demonstração)
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { execFileSync, spawnSync } = require('child_process');
const L = require('../lib');

const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste$/.test(BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
const MYSQL = process.env.VCOSTAEDU_MYSQL || 'C:/xampp/mysql/bin/mysql.exe';
const PHP = process.env.VCOSTAEDU_PHP || 'C:/xampp/php/php.exe';
const sql = (q) => execFileSync(MYSQL, ['-u', 'root', '-N', '--default-character-set=utf8mb4', BANCO, '-e', q]).toString().replace(/\r/g, '').trim();
const um = (q) => +sql(q);
const cred = JSON.parse(fs.readFileSync(path.join(__dirname, '..', 'saida', 'credenciais.json'), 'utf8'));
const ETAPA = 'onda3/etapa2';
const BANCO_PHP = path.join(L.RAIZ, 'core', 'Banco.php');
const ESCOPO = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'helper', 'AdmsEscopo.php');
const LONGO = 'Nome Muito Longo De Prova ' + 'Exemplo '.repeat(40); // mais de 255 caracteres

async function entrar(navegador, usuario, app = 'painel', senha = null) {
  const ctx = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' });
  const p = await L.pagina(ctx);
  await p.goto(L.BASE + app + '/login/acesso');
  await p.fill('input[name="usuario"]', usuario);
  await p.fill('input[name="senha"]', senha || cred[app === 'painel' ? 'painel' : 'portal'][usuario]);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
  return { ctx, p };
}
/* o erro do PHP como ele aparece na página (as bibliotecas de interface têm a palavra "Warning" no código) */
const semErroPhp = async (p) => !/<b>(Fatal error|Warning|Notice|Deprecated)<\/b>:|Uncaught |SQLSTATE\[/.test(await p.content());
const abre = async (p, rota) => {
  await p.goto(L.BASE + 'painel/' + rota);
  return !(await p.$('#foraDoEscopo')) && !/minhas-disciplinas\/listar/.test(p.url()) && !(await p.$('input[name="SendLogin"]')) && !(await p.$('#semAcesso'));
};

/** Cadastra pela tela um aluno com o nome longo; devolve [a mensagem da tela, quantas linhas com o RA] */
async function nomeLongoPelaTela(p, ra) {
  await p.goto(L.BASE + 'painel/academicos/cadastrar');
  await p.fill('#rm', ra);
  await p.evaluate(([n]) => { document.getElementById('nome').value = n; document.getElementById('nome').removeAttribute('maxlength'); }, [LONGO]);
  await p.selectOption('#genero_id', '2');
  await Promise.all([p.waitForNavigation(), p.click('input[name="CadUsuarioAcad"]')]);
  const msg = await p.$eval('.alert', (e) => e.textContent.replace(/\s+/g, ' ').trim()).catch(() => '');
  return [msg, um(`SELECT COUNT(*) FROM col_usuarios WHERE rm = '${ra}'`), await semErroPhp(p)];
}

(async () => {
  const { navegador } = await L.abrir();
  try {
    console.log('\n== Onda 3, Etapa 2');
    sql("DELETE FROM col_usuarios_enderecos WHERE usuario_id IN (SELECT id FROM col_usuarios WHERE rm LIKE '96000_'); DELETE FROM col_usuarios_informacoes WHERE col_user_id IN (SELECT id FROM col_usuarios WHERE rm LIKE '96000_'); DELETE FROM col_usuarios WHERE rm LIKE '96000_'");

    /* A. o modo, informado e em vigor em cada porta */
    const amb = spawnSync(PHP, [path.join(L.RAIZ, 'database', 'ferramentas', 'implantacao', 'verificar_ambiente.php')], { encoding: 'utf8', cwd: L.RAIZ });
    L.ok(/modo do banco estrito, fixado pelo sistema \([^)]*STRICT_TRANS_TABLES/.test(amb.stdout), 'a verificação do ambiente informa o modo do banco (estrito)');
    const adm = await entrar(navegador, 'administrador');
    const [msg, n, limpo] = await nomeLongoPelaTela(adm.p, '960001');
    L.ok(n === 0 && /Nada foi salvo/.test(msg) && /passa do tamanho permitido/.test(msg) && limpo, `pelo painel: o nome maior que a coluna é recusado com mensagem clara ("${msg.slice(0, 70)}…"), sem erro do PHP, nada gravado`);
    await L.print(adm.p, ETAPA, 'painel-recusa-clara');
    const alu = await entrar(navegador, '260101', 'portal');
    await alu.p.goto(L.BASE + 'portal/perfil/editar');
    const nomeAntes = sql("SELECT nome FROM col_usuarios WHERE rm = '260101'");
    await alu.p.evaluate(([n]) => { const c = document.querySelector('input[name="nome"]'); c.removeAttribute('maxlength'); c.value = n; }, [LONGO]);
    await Promise.all([alu.p.waitForNavigation(), alu.p.click('input[name="EditPerfil"]')]);
    const msgPortal = await alu.p.$eval('.alert', (e) => e.textContent.replace(/\s+/g, ' ').trim()).catch(() => '');
    L.ok(sql("SELECT nome FROM col_usuarios WHERE rm = '260101'") === nomeAntes && /Não é possível atualizar/.test(msgPortal) && /passa do tamanho permitido/.test(msgPortal) && await semErroPhp(alu.p),
      `pelo portal: o nome maior que a coluna é recusado com mensagem clara ("${msgPortal.slice(0, 60)}"), sem erro do PHP, o nome de antes fica`);
    const chave = 'vce_' + crypto.randomBytes(20).toString('hex');
    sql(`INSERT INTO adms_api_chaves (nome, prefixo, hash, escopo, permissoes, situacao, usuario_id) VALUES ('Prova estrito', '${chave.slice(0, 12)}', '${crypto.createHash('sha256').update(chave).digest('hex')}', 'escrita', 'alunos:gravar matriculas:gravar enturmacao:gravar cadastros:ler', 1, 1)`);
    const r = await fetch(L.BASE + 'api/v1/alunos', { method: 'POST', headers: { Authorization: 'Bearer ' + chave, 'Content-Type': 'application/json' }, body: JSON.stringify({ ra: '960002', nome: LONGO, sexo: 'feminino' }) });
    const j = await r.json().catch(() => null);
    L.ok(r.status === 422 && j && j.erro && j.erro.codigo === 'validacao' && um("SELECT COUNT(*) FROM col_usuarios WHERE rm = '960002'") === 0 && !/SQLSTATE|Data too long/.test(JSON.stringify(j)),
      `pela API: recusado (${r.status}, ${j && j.erro ? j.erro.codigo : '?'}), sem o erro do banco na resposta, nada gravado`);

    /* B. as correções que o modo estrito revelou */
    await alu.p.goto(L.BASE + 'portal/perfil/editar');
    await alu.p.fill('input[name="nome"]', nomeAntes); // a tela da recusa volta com o nome recusado
    const usuarioAntes = sql("SELECT usuario FROM col_usuarios WHERE rm = '260101'");
    await alu.p.fill('input[name="telefone"]', '(11) 3222-1111');
    await Promise.all([alu.p.waitForNavigation(), alu.p.click('input[name="EditPerfil"]')]);
    L.ok(/atualizado com sucesso/.test(await alu.p.content()) && sql("SELECT CONCAT(telefone, '|', usuario) FROM col_usuarios WHERE rm = '260101'") === `1132221111|${usuarioAntes}` && usuarioAntes !== '',
      'o perfil do portal grava (o telefone), sem pôr vazio no usuário (o que o banco sem o modo estrito fazia)');
    await alu.ctx.close();
    const emailOutro = sql("SELECT email FROM adms_usuarios WHERE usuario = 'secretaria.demo'");
    const emailAdm = sql("SELECT email FROM adms_usuarios WHERE usuario = 'administrador'");
    await adm.p.goto(L.BASE + 'painel/perfil/editar');
    await adm.p.fill('form input[name="email"]', emailOutro);
    await Promise.all([adm.p.waitForNavigation(), adm.p.click('input[name="EditPerfil"]')]);
    L.ok(sql("SELECT email FROM adms_usuarios WHERE usuario = 'administrador'") === emailAdm && await semErroPhp(adm.p),
      'o perfil do painel recusa o e-mail de outro usuário (a conferência falhava e deixava passar)');

    /* C. o boletim para o professor */
    /* na demonstração, todo aluno está numa oferta de cada professor: o "aluno de outro" é um cadastrado agora, sem enturmação */
    await adm.p.goto(L.BASE + 'painel/academicos/cadastrar');
    await adm.p.fill('#rm', '960004');
    await adm.p.fill('#nome', 'Aluno Sem Turma Exemplo');
    await adm.p.selectOption('#genero_id', '2');
    await Promise.all([adm.p.waitForNavigation(), adm.p.click('input[name="CadUsuarioAcad"]')]);
    const laura = um("SELECT id FROM adms_usuarios WHERE usuario = 'prof.laura.demo'");
    const ligadas = `SELECT rm_prof_mat_id FROM rm_professores_mat_liga WHERE usuario_id = ${laura} AND situacao = 1`;
    const meu = sql(`SELECT rm FROM portal_disciplinas_alunos WHERE rm_prof_mat_id IN (${ligadas}) ORDER BY id LIMIT 1`);
    const outro = sql(`SELECT u.rm FROM col_usuarios u WHERE u.col_niveis_acesso_id = 1 AND NOT EXISTS (SELECT 1 FROM portal_disciplinas_alunos p WHERE p.rm = u.rm AND p.rm_prof_mat_id IN (${ligadas})) ORDER BY u.id LIMIT 1`);
    const prof = await entrar(navegador, 'prof.laura.demo');
    const pdf = async (rm) => prof.p.evaluate(async (u) => { const x = await fetch(u, { credentials: 'same-origin' }); return (x.headers.get('content-type') || '').includes('pdf'); }, L.BASE + 'painel/academicos/imprimir-boletim/' + rm);
    const meuAbre = await abre(prof.p, 'academicos/boletim/' + meu);
    await L.print(prof.p, ETAPA, 'boletim-aluno-do-professor');
    const outroAbre = await abre(prof.p, 'academicos/boletim/' + outro);
    L.ok(meu !== '' && outro !== '' && meuAbre && await pdf(meu) && !outroAbre && !(await pdf(outro)),
      `o boletim para o professor: o do aluno dele abre (tela e PDF, RA ${meu}); o de outro aluno é recusado pelo endereço (tela e PDF, RA ${outro})`);

    /* D. "Minhas disciplinas" da professora que coordena um curso */
    const cursoOutro = um(`SELECT curso_id FROM rm_professores_materias WHERE id NOT IN (${ligadas}) ORDER BY id LIMIT 1`);
    sql(`INSERT INTO rm_cursos_coordenacao (curso_id, usuario_id, posicao_id, situacao, usuario_cre) VALUES (${cursoOutro}, ${laura}, 1, 1, 1)`);
    try {
      await prof.p.goto(L.BASE + 'painel/minhas-disciplinas/listar');
      const linhas = await prof.p.$$eval('#tabelaDisciplinas tbody tr', (t) => t.map((x) => ({ so: x.dataset.soConsulta, marca: !!x.querySelector('.vc-so-consulta'), atividades: !!x.querySelector('a[href*="atividades/listar/"]'), faltas: !!x.querySelector('a[href*="faltas/visualizar/"]') })));
      const dela = linhas.filter((x) => x.so === '0');
      const consulta = linhas.filter((x) => x.so === '1');
      L.ok(dela.length > 0 && dela.every((x) => !x.marca && x.atividades && x.faltas) && consulta.length > 0 && consulta.every((x) => x.marca && !x.atividades && !x.faltas),
        `a professora que coordena: ${dela.length} disciplina(s) dela com os atalhos de lançar; ${consulta.length} do curso coordenado com "só consulta" e sem eles`);
      await L.print(prof.p, ETAPA, 'minhas-disciplinas-coordenadora');
      const outroCoord = sql(`SELECT u.rm FROM col_usuarios u JOIN rm_matriculas_curso mc ON mc.usuario_id = u.id WHERE mc.curso_id = ${cursoOutro} AND NOT EXISTS (SELECT 1 FROM portal_disciplinas_alunos p WHERE p.rm = u.rm AND p.rm_prof_mat_id IN (${ligadas})) ORDER BY u.id LIMIT 1`);
      L.ok(outroCoord === '' || await abre(prof.p, 'academicos/boletim/' + outroCoord), 'e abre o boletim dos alunos do curso que coordena');
    } finally {
      sql(`DELETE FROM rm_cursos_coordenacao WHERE curso_id = ${cursoOutro} AND usuario_id = ${laura}`);
    }

    /* E. sabotagens */
    const origEsc = fs.readFileSync(ESCOPO, 'utf8');
    let reprovou = false;
    try {
      fs.writeFileSync(ESCOPO, origEsc.replace('        if (self::professor()) {\n            $ofertas = self::ofertasLigadas();', '        if (false) {\n            $ofertas = self::ofertasLigadas();').replace("    public static function podeAluno(string $rm): bool\n    {\n        if (!self::restrito()) {", "    public static function podeAluno(string $rm): bool\n    {\n        if (true) {"));
      reprovou = await abre(prof.p, 'academicos/boletim/' + outro);
    } finally {
      fs.writeFileSync(ESCOPO, origEsc);
    }
    L.ok(reprovou && fs.readFileSync(ESCOPO, 'utf8') === origEsc, 'sabotagem (sem a regra do boletim no escopo, o de outro aluno abre): a prova reprova o defeito; o escopo voltou');
    await prof.ctx.close();

    const origBanco = fs.readFileSync(BANCO_PHP, 'utf8');
    let cortou = false;
    try {
      fs.writeFileSync(BANCO_PHP, origBanco.replace(/(public const MODO = '[^']*?)STRICT_TRANS_TABLES,/, '$1'));
      const [, n2] = await nomeLongoPelaTela(adm.p, '960003');
      cortou = n2 === 1 && sql("SELECT CHAR_LENGTH(nome) FROM col_usuarios WHERE rm = '960003'") === '255';
    } finally {
      fs.writeFileSync(BANCO_PHP, origBanco);
    }
    L.ok(cortou && fs.readFileSync(BANCO_PHP, 'utf8') === origBanco, 'sabotagem (sem o modo estrito, o nome longo grava CORTADO em 255 caracteres, em silêncio): a prova reprova o defeito; o modo voltou');
    await adm.ctx.close();
  } catch (e) {
    L.ok(false, 'erro na prova: ' + e.message.split('\n')[0]);
  } finally {
    await navegador.close();
    process.exit(L.resumo('onda 3, etapa 2') ? 0 : 1);
  }
})();
