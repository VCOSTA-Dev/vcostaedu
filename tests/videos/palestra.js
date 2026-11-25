// O PERCURSO DA PALESTRA (um vídeo contínuo e um por capítulo), no ensino superior ou na escola:
//   1. instalar pelo navegador, do zero, escolhendo o tipo de instituição;   2. conhecer com os dados de demonstração;
//   3. conectar ao Moodle;   4. criar uma disciplina (na escola, a turma) e ver que ela foi ao Moodle;
//   5. cadastrar um aluno e enturmar (na escola, a turma inteira);   6. acompanhar em Envios ao Moodle;
//   7. o aluno no portal abre a disciplina no Moodle sem senha;   8. o portal de matrícula de exemplo.
//
//   node tests/videos/palestra.js superior|escola
//
// Grava na instalação da gravação (VCOSTAEDU_VIDEOS_RAIZ / VCOSTAEDU_VIDEOS_BASE, um worktree à parte, com o banco
// VCOSTAEDU_VIDEOS_BANCO terminado em "_demo"): zera a pasta e o banco (preparar.php) e instala pela tela. O Moodle é o
// de teste (VCOSTAEDU_MOODLE_URL e VCOSTAEDU_MOODLE_CHAVE, lidos do arquivo fora do repositório por quem chama): só pelo
// serviço web e como aluno; tudo o que vai para lá começa por "prova-", na categoria "VCOSTAEDU provas". O endereço e a
// chave nunca aparecem: a máscara da gravação os troca por "•••" (e o campo da chave é de senha).
// O roteiro é a prova: qualquer passo que não aconteça, erro no console ou aviso do PHP recusa o vídeo.
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { execFileSync } = require('child_process');
const { chromium } = require('playwright-core');
const { C, Gravacao } = require('./lib');

const TIPO = process.argv[2] === 'escola' ? 'escola' : 'superior';
const SO_MONTAR = process.argv.includes('--so-montar'); // só os capítulos 1 e 2, sem vídeo: deixa a demonstração limpa
const DEPURAR = process.argv.includes('--do-capitulo-3'); // só para acertar o roteiro: parte da instalação já montada (o vídeo final é sempre o inteiro)
const BANCO = process.env.VCOSTAEDU_VIDEOS_BANCO || '';
const MURL = (process.env.VCOSTAEDU_MOODLE_URL || '').trim();
const MCHAVE = (process.env.VCOSTAEDU_MOODLE_CHAVE || '').trim();
if (!/_demo$/.test(BANCO) || !MURL || !MCHAVE) { console.error('VCOSTAEDU_VIDEOS_BANCO (…_demo), VCOSTAEDU_MOODLE_URL e VCOSTAEDU_MOODLE_CHAVE'); process.exit(2); }
const ADMIN = 'administrador';
const SENHA = process.env.VCOSTAEDU_VIDEOS_SENHA || ('Vc' + crypto.randomBytes(9).toString('base64url') + '#7'); // só desta gravação
const ANO = String(new Date().getFullYear());
const SLUG = 'palestra-' + TIPO;
const MYSQL = process.env.VCOSTAEDU_MYSQL || 'C:/xampp/mysql/bin/mysql.exe';
const sql = (q) => execFileSync(MYSQL, ['-u', 'root', '-N', '--default-character-set=utf8mb4', BANCO], { input: q }).toString().replace(/\r/g, '').trim();
const um = (q) => sql(q).split('\n')[0] || '';
const ESC = TIPO === 'escola';
const NOME_ALUNO = ESC ? 'Rafael Novo Exemplo' : 'Bruna Nova Exemplo';
const RA = ESC ? '265190' : '260190';
const HOST = (() => { try { return new URL(MURL).host; } catch (e) { return ''; } })();

function cartela() {
  const titulo = ESC ? 'Do zero à sala de aula — escola' : 'Do zero à sala de aula — ensino superior';
  return `<html><head><meta charset="utf-8"><style>body{margin:0;height:100vh;display:flex;flex-direction:column;justify-content:center;align-items:center;
    background:linear-gradient(135deg,#0d3b66,#145c9e);color:#fff;font-family:Inter,Segoe UI,Arial,sans-serif}.r{font-size:22px;letter-spacing:.2em;text-transform:uppercase;opacity:.75}
    .t{font-size:50px;font-weight:700;margin:18px 40px;text-align:center}.v{font-size:20px;opacity:.85}</style></head><body><div class="r">VCOSTAEDU</div>
    <div class="t">${titulo}</div><div class="v">instalar, conhecer, ligar ao Moodle e receber o primeiro aluno · demonstração fictícia</div></body></html>`;
}

(async () => {
  console.log(`\n== O percurso da palestra (${TIPO})`);
  if (!DEPURAR) execFileSync(C.php, [path.join(__dirname, 'preparar.php'), 'zerar'], { env: { ...process.env, VCOSTAEDU_VIDEOS_RAIZ: C.raiz }, stdio: 'inherit' });
  const browser = await chromium.launch();
  const v = new Gravacao({ browser, slug: SLUG, roteiro: { titulo: 'O percurso' }, ids: {}, senhas: { [ADMIN]: SENHA }, versao: '', segredos: [MURL, HOST, MCHAVE], cartela: cartela() });
  const p = () => v.page;
  const janela = (t, aviso, extra = {}) => ({ t, aviso, guia: v.guiaJanela, ...extra });
  try {
    await v.abrir();

    if (DEPURAR) {
      await v.ir('painel/login/acesso');
      await p().fill('input[name="usuario"]', ADMIN); await p().fill('input[name="senha"]', SENHA);
      await Promise.all([p().waitForNavigation(), p().click('[name="SendLogin"]')]);
    }
    if (!DEPURAR) {
    /* ---------------------------------------------------------------- 1. instalar */
    v.capitulo('1. Instalar pelo navegador');
    v.guiaJanela = 'Instalar o VCOSTAEDU';
    await v.ir('');
    v.confere(/\/instalar\//.test(p().url()), 'a raiz sem configuração deveria abrir o instalador');
    await v.passo('O endereço do sistema, sem nada configurado, abre o instalador. Primeiro, ele confere o servidor.', janela('Requisitos do servidor', 'Confira e clique em **Continuar para o banco de dados**.'));
    await v.clicarENavegar('a.btn:has-text("Continuar para o banco de dados")');
    await p().evaluate(() => ['#host', '#usuario'].forEach((s) => { const e = document.querySelector(s); if (e) e.setAttribute('data-vc-gravar-mascarar', '1'); }));
    await v.passo('O banco de dados vazio que a hospedagem criou: o servidor, o nome, o usuário e a senha.', janela('O banco de dados', 'Preencha os quatro campos e clique em **Testar a conexão**.'));
    await v.digitar('#host', 'localhost');
    await v.digitar('#dbname', BANCO);
    await v.digitar('#usuario', 'root');
    await v.clicarENavegar('button:has-text("Testar a conexão")');
    await v.feito('A conexão com o banco deu certo.');
    await v.passo('Com a conexão testada, o instalador grava a configuração e confere que ela não fica à mostra na internet.', janela('A configuração', 'Clique em **Gravar a configuração**.'));
    await v.clicarENavegar('button:has-text("Gravar a configuração")');
    await v.clicarENavegar('a.btn:has-text("Criar as tabelas")');
    await v.passo('As tabelas do sistema são criadas de uma vez, numa tela só.', janela('As tabelas', 'Clique em **Criar as tabelas**.'));
    await v.acelerar(() => v.clicarENavegar('button:has-text("Criar as tabelas")'), 3000);
    v.confere(await p().$$eval('#etapas tr[data-situacao]', (e) => e.length && e.every((x) => x.getAttribute('data-situacao') === 'APLICADA')), 'as tabelas deveriam ser criadas');
    await v.feito('As tabelas foram criadas.');
    await v.clicarENavegar('a.btn:has-text("Criar a conta de administrador")');
    await v.passo('A conta de quem administra o sistema: o usuário, o nome, o e-mail e uma senha forte.', janela('A conta de administrador', 'Preencha e clique em **Criar a conta e concluir**.'));
    await v.digitar('#usuario', ADMIN);
    await v.digitar('#nome', 'Alice Administradora (fictícia)');
    await v.digitar('#email', 'administracao@exemplo.invalid');
    await v.digitar('#senha', SENHA);
    await v.digitar('#confirmacao', SENHA);
    await v.clicarENavegar('button:has-text("Criar a conta e concluir")');
    await v.passo(ESC ? 'Cada instalação é de um tipo. Aqui, uma escola: a educação básica, com séries, turmas e etapas.' : 'Cada instalação é de um tipo. Aqui, o ensino superior: cursos, módulos e disciplinas.',
      janela('O tipo de instituição', `Marque **${ESC ? 'Escola' : 'Ensino superior'}** e confirme.`));
    await v.clicar(`label:has(#tipo_${TIPO})`).catch(async () => { await p().check('#tipo_' + TIPO, { force: true }); });
    await p().check('#tipo_' + TIPO, { force: true });
    await v.clicarENavegar('#confirmarTipo');
    v.confere((await v.texto('#tipoEscolhido')).includes(ESC ? 'Escola' : 'Ensino superior'), 'a tela final deveria dizer o tipo');
    await v.feito(`Instalado, como ${ESC ? 'escola' : 'ensino superior'}.`);

    /* ---------------------------------------------------------------- 2. conhecer com a demonstração */
    v.capitulo('2. Conhecer com os dados de demonstração');
    v.guiaJanela = 'Conhecer com a demonstração';
    await v.passo('Para conhecer antes de cadastrar a própria instituição, o sistema monta uma demonstração com dados fictícios.', janela('Como começar', 'Clique em **Conhecer com dados de demonstração**.'));
    await v.clicarENavegar('#conhecerDemonstracao');
    await v.digitar('input[name="usuario"]', ADMIN);
    await v.digitar('input[name="senha"]', SENHA);
    await v.clicarENavegar('[name="SendLogin"]');
    if (!(await p().$('#montarDemonstracao'))) { await v.ir('painel/demonstracao/comecar'); }
    await v.passo(ESC ? 'O Colégio Horizonte, fictício: séries, turmas, professores, alunos, notas por etapa e boletins.' : 'A Faculdade Horizonte, fictícia: cursos, disciplinas, professores, alunos, notas e documentos.',
      janela('A demonstração', 'Clique em **Montar a demonstração**.'));
    await v.clicar('#montarDemonstracao');
    await p().waitForSelector('#progressoMontagem', { timeout: 20000 });
    await v.passo('A montagem vai em passos curtos, numa janela que mostra o andamento. Se a página fechar, ela continua de onde parou.', janela('A montagem em passos', 'Espere a janela chegar ao fim.'));
    await v.acelerar(() => p().waitForFunction(() => ['concluida', 'falhou'].includes(document.getElementById('progressoMontagem').getAttribute('data-situacao')), null, { timeout: 1200000 }), 5000);
    v.confere((await p().getAttribute('#progressoMontagem', 'data-situacao')) === 'concluida', 'a montagem deveria terminar');
    await v.feito('A demonstração foi montada.');
    await v.clicarENavegar('#progressoMontagem .vc-progresso-fim');
    await v.passo('Pronto. Uma faixa no alto lembra que os dados são fictícios, e cada perfil tem a sua conta para conhecer.', janela('Demonstração ligada', ''));
    if (await p().$('#tabelaPerfisDemo')) await v.destacar('#tabelaPerfisDemo');
    await v.destacar('#faixaDemonstracao');
    v.poster();
    }
    if (SO_MONTAR) { // a instalação da gravação recém-montada (a demonstração limpa), para regravar os guias; sem vídeo
      await v.cdp.send('Page.stopScreencast').catch(() => {}); await v.ctx.close().catch(() => {});
      fs.rmSync(v.pastaQuadros, { recursive: true, force: true });
      console.log(`onda5/palestra-${TIPO}: montada (sem vídeo)`); await browser.close(); return;
    }

    /* ---------------------------------------------------------------- 3. conectar ao Moodle */
    v.capitulo('3. Conectar ao Moodle');
    v.guiaJanela = 'Conectar ao Moodle';
    sql("UPDATE adms_parametros SET valor = 'prova-' WHERE chave = 'moodle.prefixo'"); // o Moodle de teste: tudo o que vai para lá começa por "prova-"
    await v.facaComigo('conectar-moodle', { valores: { '#url': MURL, '#chave': MCHAVE, '#salvarMoodle': true, '#testarMoodle': true } });
    const testeOk = await p().$('#testeOk');
    v.confere(!!testeOk, 'o teste da conexão deveria dar certo: ' + (await v.texto('#testeFalhou')).slice(0, 120));
    await v.passo('O teste deu certo: o nome do site e as funções do serviço. Falta a categoria onde as disciplinas são criadas.', janela('A categoria', 'Escolha a **categoria** e clique em **Salvar**.'));
    await v.destacar('#testeOk');
    const temCat = await p().$$eval('#categoria_id option', (os) => os.some((o) => o.textContent.trim() === 'VCOSTAEDU provas'));
    if (temCat) { await v.destacar('#categoria_id'); await p().selectOption('#categoria_id', { label: 'VCOSTAEDU provas' }); } else { await v.digitar('#categoria_nova', 'VCOSTAEDU provas'); }
    await v.clicarENavegar('#salvarMoodle');
    v.confere(um('SELECT IFNULL(categoria_id, 0) FROM adms_moodle_conexao WHERE id = 1') !== '0', 'a categoria deveria ficar gravada');
    await v.feito('Conectado ao Moodle, com a categoria das disciplinas.');

    /* ---------------------------------------------------------------- 4. a disciplina (na escola, a turma) */
    let oferta = 0, turma = 0;
    if (!ESC) {
      v.capitulo('4. Criar uma disciplina e vê-la no Moodle');
      v.guiaJanela = 'Criar uma disciplina';
      await v.ir('painel/disciplinas/cadastrar');
      await v.passo('Uma disciplina nova do ano: a linha da matriz, a turma, o polo, as datas e o professor.', janela('Nova disciplina', 'Preencha e clique em **Cadastrar**.'));
      const linha = await p().$$eval('#matriz_id option', (os) => (os.find((o) => /Gestão de Projetos/.test(o.textContent)) || os.find((o) => o.value) || {}).value);
      await v.escolher('#matriz_id', linha);
      await v.digitar('#encontros', '20');
      await v.digitar('#codtur', 'B');
      await v.escolher('#codfilial', '1');
      await v.destacar('#data_inicio'); await p().fill('#data_inicio', `${ANO}-08-03`);
      await v.destacar('#data_fim'); await p().fill('#data_fim', `${ANO}-11-30`);
      await v.digitar('#ano', ANO);
      const prof = await p().$$eval('#professores option', (os) => (os.find((o) => /Laura/.test(o.textContent)) || os.find((o) => o.value) || {}).value);
      await p().evaluate((val) => { const e = document.getElementById('professores'); if (e.tomselect) e.tomselect.setValue([val]); else { for (const o of e.options) o.selected = o.value === val; e.dispatchEvent(new Event('change', { bubbles: true })); } if (window.jQuery) window.jQuery(e).trigger('change'); }, prof);
      await v.destacar('#professores').catch(() => {});
      await v.clicarENavegar('input[name="CadDisciplina"]');
      oferta = +um(`SELECT IFNULL(MAX(id), 0) FROM rm_professores_materias WHERE codtur = 'B' AND ano = ${ANO}`);
      v.confere(oferta > 0, 'a disciplina deveria ser criada: ' + (await v.alerta()));
      await v.feito('A disciplina foi criada.');
      await v.ir(`painel/disciplinas/visualizar/${oferta}`);
      await p().waitForTimeout(1500);
      await v.ir(`painel/disciplinas/visualizar/${oferta}`);
      const est = await p().getAttribute('#estadoMoodle', 'data-estado');
      v.confere(est === 'ligada', 'a disciplina deveria estar ligada ao Moodle: ' + est);
      await v.passo('Com o professor, a disciplina já foi criada no Moodle, na categoria escolhida: "Ligada ao Moodle".', janela('A disciplina no Moodle', ''));
      await v.destacar('#estadoMoodle');
    } else {
      v.capitulo('4. Criar a turma e ver as disciplinas no Moodle');
      v.guiaJanela = 'Criar a turma';
      await v.ir('painel/turmas/cadastrar');
      await v.passo('A turma é da série e do ano. Ao salvar, ela gera uma disciplina para cada componente curricular da série.', janela('Nova turma', 'Escolha o ano e a série, dê o nome e clique em **Salvar**.'));
      await v.escolher('#ano', ANO);
      const serie = await p().$$eval('#modulo_id option', (os) => (os.find((o) => /7º ano/.test(o.textContent)) || os.find((o) => o.value) || {}).value);
      await v.escolher('#modulo_id', serie);
      await v.digitar('#nome', 'C');
      /* Onda 7, Etapa 1: toda turma da demonstração tem turno, sala e capacidade */
      await v.escolher('#turno_id', '1');
      await v.digitar('#sala', 'Sala 21');
      await v.digitar('#capacidade', '35');
      await v.clicarENavegar('#salvarTurma');
      turma = +um(`SELECT IFNULL(MAX(id), 0) FROM esc_turmas WHERE ano = ${ANO} AND nome = 'C'`);
      v.confere(turma > 0, 'a turma deveria ser criada: ' + (await v.alerta()));
      await v.feito('A turma foi criada, com uma disciplina por componente.');
      /* Onda 7, Etapa 1: o professor fictício da demonstração em cada componente da turma nova (pela mesma tela de
         "Adicionar professor", fora da gravação), antes do envio ao Moodle — a turma não aparece "sem professor" */
      const profDemo = +um("SELECT id FROM adms_usuarios WHERE usuario = 'prof.laura.demo'");
      v.confere(profDemo > 0, 'a professora da demonstração deveria existir');
      for (const of of um(`SELECT GROUP_CONCAT(oferta_id) FROM esc_turma_ofertas WHERE turma_id = ${turma}`).split(',').filter(Boolean)) {
        await p().evaluate(async ([u, of, prof]) => { const f = new URLSearchParams(); f.append('rm_prof_mat_id', of); f.append('professores[]', prof); f.append('AdicionaProfessor', 'Salvar');
          await fetch(u, { method: 'POST', body: f, credentials: 'same-origin' }); }, [`${C.base}painel/disciplinas/adiciona-professor/${ANO}`, of, String(profDemo)]);
      }
      const semProf = +um(`SELECT COUNT(*) FROM esc_turma_ofertas x WHERE x.turma_id = ${turma} AND NOT EXISTS (SELECT 1 FROM rm_professores_mat_liga l WHERE l.rm_prof_mat_id = x.oferta_id AND l.situacao = 1)`);
      v.confere(semProf === 0, `todos os componentes da turma nova deveriam ter professor (faltam ${semProf})`);
      await v.ir(`painel/turmas/ver/${turma}`);
      await v.passo('As disciplinas da turma, cada uma com o seu professor. No Moodle, cada uma leva a série e a turma no nome.', janela('As disciplinas da turma', ''));
      await v.destacar('#ofertasTurma');
    }

    /* ---------------------------------------------------------------- 5. o aluno e a enturmação */
    v.capitulo(ESC ? '5. Cadastrar um aluno e enturmar a turma' : '5. Cadastrar um aluno e enturmar');
    v.guiaJanela = 'Cadastrar e enturmar';
    sql(`DELETE FROM col_usuarios WHERE rm = '${RA}'`);
    await v.ir('painel/academicos/cadastrar');
    await v.passo('O aluno novo: o número de matrícula, o nome, o sexo e o e-mail (é por ele que o aluno chega ao Moodle).', janela('Cadastrar o aluno', 'Preencha e clique em **Cadastrar**.'));
    await v.digitar('#rm', RA);
    await v.digitar('#nome', NOME_ALUNO);
    await v.escolher('#genero_id', ESC ? '1' : '2'); // Onda 7, Etapa 1: o gênero coerente com o nome (Rafael; Bruna)
    const email = `${ESC ? 'rafael' : 'bruna'}.nova.${Date.now().toString(36)}@exemplo.invalid`; // o Moodle exige o e-mail único a cada gravação
    await v.digitar('#email', email);
    await v.clicarENavegar('input[name="CadUsuarioAcad"]');
    const aid = +um(`SELECT IFNULL(MAX(id), 0) FROM col_usuarios WHERE rm = '${RA}'`);
    v.confere(aid > 0, 'o aluno deveria ser cadastrado: ' + (await v.alerta()));
    await v.feito('O aluno foi cadastrado.');
    await v.ir(`painel/academicos/visualizar/${aid}`);
    await v.passo(ESC ? 'Na ficha, a matrícula no segmento do ano: é ela que deixa o aluno entrar numa turma.' : 'Na ficha, a matrícula no curso: o ano, o curso e a forma de ingresso.', janela('A matrícula', 'Abra **Matrículas**, escolha e clique em **Vincular**.'));
    await v.clicar('#tabeMatricula-tab');
    await p().waitForSelector('#vModulo', { state: 'visible', timeout: 8000 });
    await v.escolher('#vAno', ANO);
    const mod = await p().$$eval('#vModulo option', (os, esc) => (os.find((o) => (esc ? /Fundamental|anos finais/i : /Gestão de Projetos/).test(o.textContent)) || os.find((o) => o.value) || {}).value, ESC);
    await v.escolher('#vModulo', mod);
    await p().fill('#vData', `${ANO}-02-02`).catch(() => {});
    await v.clicarENavegar('form[action*="academicos/vincular/"] [type="submit"]');
    v.confere(+um(`SELECT COUNT(*) FROM rm_matriculas_curso WHERE usuario_id = ${aid}`) > 0, 'o aluno deveria ficar matriculado: ' + (await v.alerta()));
    await v.feito('O aluno foi matriculado.');
    if (!ESC) {
      await v.ir(`painel/portal-alunos/visualizar/${RA}?ano=${ANO}`);
      await v.passo('A enturmação: as disciplinas abertas do ano. Aqui, a disciplina nova.', janela('Enturmar', 'Marque a disciplina e clique em **Confirmar enturmação**.'));
      await p().$$eval('input[name="ofertas[]"]', (cs) => cs.forEach((c) => { if (!c.disabled) c.checked = false; }));
      const cx = `input[name="ofertas[]"][value="${oferta}"]`;
      const id = await p().getAttribute(cx, 'id');
      if (id && await p().isVisible(`label[for="${id}"]`)) await v.clicar(`label[for="${id}"]`); else await p().check(cx, { force: true });
      if (!(await p().isChecked(cx))) await p().check(cx, { force: true });
      await v.clicarENavegar('form[action*="confirmar-enturmacao/"] [type="submit"]');
      v.confere(+um(`SELECT COUNT(*) FROM portal_disciplinas_alunos WHERE rm = '${RA}' AND rm_prof_mat_id = ${oferta}`) > 0, 'o aluno deveria ficar na disciplina');
      await v.feito('O aluno está na disciplina, e o pedido de inscrição foi ao Moodle.');
    } else {
      await v.ir(`painel/turmas/ver/${turma}`);
      await v.passo('Na turma, os alunos matriculados no ano e sem turma. Enturmar põe o aluno em todas as disciplinas da turma de uma vez.', janela('Enturmar a turma', 'Marque os alunos e clique em **Enturmar os marcados**.'));
      if (await p().$('#marcarTodos')) await v.clicar('#marcarTodos');
      await v.clicar('#enturmarMarcados');
      await v.acelerar(() => p().waitForSelector('#progressoTurma[data-situacao="concluida"], #progressoTurma[data-situacao="falhou"]', { timeout: 300000 }), 3000);
      v.confere((await p().getAttribute('#progressoTurma', 'data-situacao')) === 'concluida', 'a enturmação deveria terminar');
      await v.feito('A turma foi enturmada, em passos.');
      await v.ir(`painel/turmas/ver/${turma}`);
      await v.destacar('#alunosTurma');
    }

    /* ---------------------------------------------------------------- 6. Envios ao Moodle */
    v.capitulo('6. Acompanhar em Envios ao Moodle');
    v.guiaJanela = 'Envios ao Moodle';
    await v.ir('painel/moodle-envios/listar');
    if (await p().$('#enviarTudo')) {
      await v.passo('O que ainda falta ir ao Moodle vai em passos, num botão.', janela('Enviar o que falta', 'Clique em **Enviar tudo o que falta**.'));
      await v.clicar('#enviarTudo');
      await v.acelerar(() => p().waitForSelector('#progressoEnvios[data-situacao="concluida"], #progressoEnvios[data-situacao="falhou"]', { timeout: 300000 }), 3000);
      await v.ir('painel/moodle-envios/listar');
    }
    await v.passo('Cada envio, com a situação: enviado, na fila ou com falha. A falha diz o que fazer e tem "Tentar de novo".', janela('Os envios', ''));
    await v.destacar('table, .card').catch(() => {});
    v.confere(+um(`SELECT COUNT(*) FROM adms_moodle_envios WHERE situacao = 'enviado'`) > 0, 'deveria haver envios feitos');
    await v.feito('O aluno e a disciplina chegaram ao Moodle.');

    /* ---------------------------------------------------------------- 7. o portal e o Moodle sem senha */
    v.capitulo('7. O aluno abre a disciplina no Moodle, sem senha');
    v.guiaJanela = 'O portal do aluno';
    /* a senha do PORTAL é dado de gravação (no uso, ela chega pelo link do e-mail) */
    const SENHA_PORTAL = 'Vc' + crypto.randomBytes(8).toString('base64url') + '#4';
    const hash = execFileSync(C.php, ['-r', 'echo password_hash(getenv("VC_S"), PASSWORD_DEFAULT);'], { env: { ...process.env, VC_S: SENHA_PORTAL } }).toString().trim();
    sql(`UPDATE col_usuarios SET senha = '${hash}' WHERE id = ${aid}`);
    v.senhas[RA] = SENHA_PORTAL;
    await v.ctx.clearCookies();
    await v.ir('portal/login/acesso');
    await v.passo('O aluno entra no portal com o número de matrícula e a senha que escolheu pelo link do e-mail.', janela('Entrar no portal', 'Digite e clique em **Entrar**.'));
    await v.digitar('input[name="usuario"]', RA);
    await v.digitar('input[name="senha"]', SENHA_PORTAL);
    await v.clicarENavegar('input[name="SendLogin"], [name="SendLogin"]');
    const abrir = await p().$('.vc-abrir-moodle');
    v.confere(!!abrir, 'o portal deveria mostrar a disciplina com o botão do Moodle');
    await v.passo('No portal, as disciplinas do aluno. O botão do Moodle abre a disciplina lá, sem pedir outra senha.', janela('A disciplina no Moodle', 'Clique em **abrir no Moodle**.'));
    await v.clicarENavegar('.vc-abrir-moodle');
    await p().waitForLoadState('load').catch(() => {});
    const dentro = await p().evaluate(() => !document.querySelector('#login, form#login') && /course\/view\.php/.test(location.href));
    v.confere(dentro, 'o aluno deveria cair dentro da disciplina no Moodle');
    await v.passo('Dentro do Moodle, na disciplina, já identificado. Nenhuma senha do Moodle foi digitada.', janela('No Moodle', ''));
    await p().waitForTimeout(2500);

    /* ---------------------------------------------------------------- 8. o portal de matrícula de exemplo */
    v.capitulo('8. O portal de matrícula de exemplo');
    v.guiaJanela = 'O portal de matrícula de exemplo';
    await v.ctx.clearCookies();
    await v.ir('painel/login/acesso');
    await v.digitar('input[name="usuario"]', ADMIN);
    await v.digitar('input[name="senha"]', SENHA);
    await v.clicarENavegar('[name="SendLogin"]');
    await v.ir('painel/integracoes/chaves');
    await v.passo('O site de inscrições da instituição fala com o VCOSTAEDU por uma chave de API, com o limite do que ela pode fazer.', janela('A chave do site de inscrições', 'Dê um nome, marque o que ela pode gravar e clique em **Criar**.'));
    await v.digitar('#nomeChave', 'Site de inscrições (exemplo)');
    for (const a of ['alunos', 'matriculas', 'enturmacao']) {
      const sel = `input[name="permissao[${a}]"][value="gravar"]`;
      await v.destacar(sel).catch(() => {});
      await p().$eval(sel, (e) => { e.checked = true; e.dispatchEvent(new Event('change', { bubbles: true })); });
    }
    v.confere(await p().$$eval('input[type="radio"][value="gravar"]:checked', (e) => e.length) >= 3, 'as três permissões de gravar deveriam ficar marcadas');
    let chaveNova = '';
    const ouvir = async (r) => { try { if (/integracoes\/(criar|chaves)/.test(r.url())) { const m = (await r.text()).match(/vce_[0-9A-Za-z]{20,}/); if (m) chaveNova = m[0]; } } catch (e) { /* já foi */ } };
    p().on('response', ouvir);
    await v.clicarENavegar('#criarChave');
    p().off('response', ouvir);
    v.confere(/^vce_/.test(chaveNova), 'a chave deveria ser criada');
    await v.feito('A chave foi criada. Ela aparece uma vez só e vai para o servidor do site de inscrições, nunca para o navegador.');
    /* o exemplo, configurado como a instituição o configura (o arquivo local, fora do git) */
    const cursoEx = ESC ? um("SELECT id FROM rm_cursos ORDER BY id LIMIT 1") : um("SELECT id FROM rm_cursos WHERE nome LIKE '%Gestão de Projetos%' LIMIT 1");
    const nomeCurso = um(`SELECT nome FROM rm_cursos WHERE id = ${+cursoEx}`);
    const forma = um('SELECT id FROM rm_matricula_tipos WHERE situacao = 1 ORDER BY id LIMIT 1');
    const cfg = JSON.stringify({ api: C.base + 'api/v1', chave: chaveNova, instituicao: ESC ? 'Colégio Horizonte (fictício)' : 'Faculdade Horizonte (fictícia)', cursos: [{ id: +cursoEx, nome: nomeCurso, ano: +ANO, forma_ingresso_id: +forma }], ra_inicio: 970100, ra_fim: 970999 });
    const php = execFileSync(C.php, ['-r', "echo '<?php return ', var_export(json_decode(getenv('VC_CFG'), true), true), ';', PHP_EOL;"], { env: { ...process.env, VC_CFG: cfg } }).toString();
    fs.writeFileSync(path.join(C.raiz, 'exemplos', 'portal-matricula', 'config.local.php'), php);
    await v.ctx.clearCookies();
    await v.ir('exemplos/portal-matricula/');
    await v.passo('O site de inscrições de exemplo: quem é de fora preenche o nome, o e-mail e o CPF.', janela('A inscrição', 'Preencha e clique em **Enviar inscrição**.'));
    await v.digitar('#nome', ESC ? 'Lívia Inscrita Exemplo' : 'Caio Inscrito Exemplo');
    await v.digitar('#email', `inscricao.${Date.now().toString(36)}@exemplo.invalid`);
    const n = String(970000 + (Date.now() % 900)).padStart(9, '0');
    const dv = (b) => { let s = 0; for (let i = 0; i < b.length; i++) s += +b[i] * (b.length + 1 - i); const r = (s * 10) % 11; return r === 10 ? 0 : r; };
    const d1 = dv(n), d2 = dv(n + d1);
    await v.digitar('#cpf', n + d1 + d2);
    await v.escolher('#sexo', ESC ? 'feminino' : 'masculino');
    await v.acelerar(() => v.clicarENavegar('#enviarInscricao'), 4000);
    const raEx = (await v.texto('#raInscricao')).trim();
    const passos = await p().$$eval('#andamento li', (ls) => ls.map((l) => l.dataset.situacao));
    v.confere(/^97\d{4}$/.test(raEx) && passos.length && passos.every((x) => x === 'feito'), 'a inscrição deveria chegar ao fim: ' + passos.join(','));
    await v.passo('O andamento: o aluno no sistema, a matrícula, a enturmação, o envio ao Moodle e o acesso pronto.', janela('O andamento', ''));
    await v.destacar('#andamento');
    await v.feito(`Inscrito, com a matrícula nº ${raEx}.`);
    await v.ir('painel/login/acesso');
    await v.digitar('input[name="usuario"]', ADMIN);
    await v.digitar('input[name="senha"]', SENHA);
    await v.clicarENavegar('[name="SendLogin"]');
    const aidEx = +um(`SELECT IFNULL(MAX(id), 0) FROM col_usuarios WHERE rm = '${raEx}'`);
    await v.ir(`painel/academicos/visualizar/${aidEx}`);
    await v.passo('No painel, o aluno que se inscreveu de fora já tem a ficha, a matrícula e as disciplinas.', janela('O aluno no painel', ''));
    await v.ir('painel/moodle-envios/listar');
    await v.passo('E em Envios ao Moodle, a conta dele e as inscrições nas disciplinas, enviadas.', janela('O aluno no Moodle', ''));
    await v.destacar('table, .card').catch(() => {});
    await v.semJanela();

    const r = await v.fechar({ separar: true });
    console.log(`\n${SLUG}: ${r.duracao} s, ${(r.tamanho / 1e6).toFixed(1)} MB, ${r.partes.length} capítulos`);
    /* o manifesto do percurso (fora do videos.json, que é o dos guias da Ajuda) */
    const man = path.join(__dirname, '..', '..', 'docs', 'guia', 'palestra.json');
    const m = (() => { try { return JSON.parse(fs.readFileSync(man, 'utf8')); } catch (e) { return {}; } })();
    m[TIPO] = { titulo: ESC ? 'Do zero à sala de aula — escola' : 'Do zero à sala de aula — ensino superior', arquivo: SLUG + '.mp4', poster: SLUG + '.jpg', legenda: SLUG + '.vtt',
      capitulos_vtt: SLUG + '.capitulos.vtt', duracao: r.duracao, tamanho: r.tamanho, gravado_em: new Date().toISOString().slice(0, 10), capitulos: r.capitulos,
      partes: r.partes.map((x) => ({ titulo: x.titulo, arquivo: x.arquivo, poster: x.poster, legenda: x.legenda, duracao: x.duracao, tamanho: x.tamanho })), transcricao: r.transcricao };
    fs.writeFileSync(man, JSON.stringify(m, null, 2) + '\n');
    console.log(`onda5/palestra-${TIPO}: OK`);
    await browser.close();
  } catch (e) {
    console.error(`onda5/palestra-${TIPO}: FALHA — ${e.message}`);
    try { await p().screenshot({ path: path.join(C.trabalho, SLUG + '-falha.png') }); } catch (x) { /* fechada */ }
    await browser.close();
    process.exit(1);
  }
})();
