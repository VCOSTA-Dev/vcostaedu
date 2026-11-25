/*
 * Chamado por capturar.php (que prepara as contas e restaura as senhas no fim).
 * Tira os prints dos guias com o Chromium do Playwright (E8; VCOSTAEDU_CANAL=chrome usa o Chrome instalado), 1440×900, em
 * docs/guia/img/<perfil>_<nn>_<nome>.png. Nada aqui grava senha ou token.
 */
const { chromium } = require('playwright-core');
const path = require('path');

const BASE = process.env.VCOSTAEDU_BASE;
const SAIDA = process.env.VCOSTAEDU_SAIDA;
const IDS = JSON.parse(process.env.VCOSTAEDU_IDS);
const CONTAS = JSON.parse(process.env.VCOSTAEDU_CONTAS);
const P = BASE + 'painel/';
const A = BASE + 'portal/';
const of = IDS.oferta;
const at = IDS.atividade;
const al = IDS.aluno;
const mc = IDS.matricula;
const em = IDS.emissao;

const feitos = [];
/* Onda 5: a mesma máscara dos vídeos (endereço local, IP, porta, caminho, chave, os segredos do ambiente) e o texto de
   cada print guardado fora do git (VCOSTAEDU_TELAS), para a varredura automática (tests/videos/varrer.js) cobrir os prints */
const fs = require('fs');
const { mascaraNaPagina } = require(path.join(__dirname, '..', '..', '..', 'tests', 'videos', 'narrador.js'));
const SEGREDOS = [process.env.VCOSTAEDU_MOODLE_URL, process.env.VCOSTAEDU_MOODLE_CHAVE].map((s) => (s || '').trim()).filter(Boolean);
try { SEGREDOS.push(new URL(process.env.VCOSTAEDU_MOODLE_URL || '').host); } catch (e) { /* sem Moodle */ }
const TELAS = process.env.VCOSTAEDU_TELAS || '';

async function limpar(page) {
  // sem o preloader do AdminLTE e sem animação: o print sai estável
  await page.addStyleTag({ content: '.preloader{display:none!important} *{transition:none!important;animation:none!important}' }).catch(() => {});
  // barra lateral aberta, com os nomes dos menus (o painel abre recolhido nesta largura)
  await page.evaluate(() => { document.body.classList.remove('sidebar-collapse'); document.body.classList.add('sidebar-open'); }).catch(() => {});
  await page.mouse.move(1000, 500);
  // E8c (D-267): a demonstração tem CPF e RG gerados na hora; nenhum print mostra o número (texto e campos de formulário)
  await page.evaluate(() => {
    const cpf = /\b\d{3}\.?\d{3}\.?\d{3}-?\d{2}\b/g, rg = /\b\d{7}\b/g;
    const w = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
    for (let n = w.nextNode(); n; n = w.nextNode()) {
      if (cpf.test(n.nodeValue) || rg.test(n.nodeValue)) n.nodeValue = n.nodeValue.replace(cpf, '•••.•••.•••-••').replace(rg, '•••••••');
      cpf.lastIndex = 0; rg.lastIndex = 0;
    }
    document.querySelectorAll('input').forEach((i) => {
      if (/cpf|rg/i.test(i.name || i.id || '') && i.value) i.value = i.value.replace(/\d/g, '•');
    });
  }).catch(() => {});
  await page.evaluate(mascaraNaPagina, SEGREDOS).catch(() => {});
  await page.waitForTimeout(500);
}

async function print(page, nome, opcoes = {}) {
  await limpar(page);
  const arquivo = path.join(SAIDA, nome + '.png');
  await page.screenshot({ path: arquivo, fullPage: opcoes.inteira !== false, animations: 'disabled' });
  const texto = await page.evaluate(() => document.body ? document.body.innerText : '');
  if (TELAS) fs.writeFileSync(path.join(TELAS, 'print-' + nome + '.tela.txt'), texto);
  if (/Warning|Notice|Fatal error|Deprecated|Uncaught/.test(texto)) throw new Error(nome + ': a tela tem mensagem de erro do PHP');
  feitos.push(nome);
  console.log('  ' + nome + '.png');
}

const falhas = []; // Onda 5: uma tela que falha não para as outras; todas aparecem no fim (e o código de saída é 1)
async function abrir(page, url, nome, opcoes) {
  try {
    await page.goto(url, { waitUntil: 'networkidle' });
    if (await page.$('input[name="SendLogin"]') && !url.includes('login/')) throw new Error(nome + ': caiu no login (' + url.replace(BASE, '') + ')');
    await print(page, nome, opcoes);
  } catch (e) { falhas.push(String(e.message).split(String.fromCharCode(10))[0]); console.log('  FALHA ' + falhas[falhas.length - 1]); }
}

async function entrar(browser, app, usuario) {
  const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 }, locale: 'pt-BR' });
  const page = await ctx.newPage();
  await page.goto((app === 'portal' ? A : P) + 'login/acesso', { waitUntil: 'networkidle' });
  await page.fill('input[name="usuario"]', usuario);
  await page.fill('input[name="senha"]', CONTAS[usuario]);
  await Promise.all([page.waitForLoadState('networkidle'), page.click('input[name="SendLogin"]')]);
  if (await page.$('input[name="SendLogin"]')) throw new Error('login de ' + usuario + ' não entrou');
  return page;
}

(async () => {
  const browser = await chromium.launch({ headless: true, ...(process.env.VCOSTAEDU_CANAL ? { channel: process.env.VCOSTAEDU_CANAL } : {}) });
  try {
    /* ---------------------------------------------------------------- telas públicas */
    const pub = await (await browser.newContext({ viewport: { width: 1440, height: 900 }, locale: 'pt-BR' })).newPage();
    await abrir(pub, P + 'login/acesso', 'administrador_01_login', { inteira: false });
    await abrir(pub, A + 'login/acesso', 'aluno_01_login', { inteira: false });
    await abrir(pub, A + 'login/esqueceu-senha', 'aluno_03_esqueci_a_senha', { inteira: false });
    await abrir(pub, P + 'verificar/codigo/' + em.codigo_final, 'secretaria_20_verificacao');
    /* E8: 5 senhas erradas de um usuário inventado, para a tela Tentativas de acesso mostrar uma linha em espera
       (capturar.php apaga as contagens no fim) */
    for (let i = 1; i <= 5; i++) {
      await pub.request.post(P + 'login/acesso', { form: { usuario: 'usuario.inventado', senha: 'errada' + i, SendLogin: 'Acessar' } });
    }

    /* ---------------------------------------------------------------- administrador */
    const adm = await entrar(browser, 'painel', 'administrador');
    await abrir(adm, P + 'home/index', 'administrador_02_inicio', { inteira: false });
    await abrir(adm, P + 'polos/listar', 'administrador_03_polos');
    await abrir(adm, P + 'polos/cadastrar', 'administrador_04_polo_cadastrar');
    await abrir(adm, P + 'cursos/listar', 'administrador_05_cursos');
    await abrir(adm, P + 'cursos/cadastrar', 'administrador_06_curso_cadastrar');
    await abrir(adm, P + 'parametros/listar', 'administrador_07_instituicao');
    await abrir(adm, P + 'usuarios/listar', 'administrador_08_usuarios');
    await abrir(adm, P + 'usuarios/cadastrar', 'administrador_09_usuario_cadastrar');
    await abrir(adm, P + 'matriz/visualizar/' + IDS.grade, 'administrador_11_grade_situacao');
    await abrir(adm, P + 'disciplinas/editar/' + of.GOC, 'administrador_12_oferta_editar');
    await abrir(adm, P + 'disciplinas/listar/2026', 'administrador_13_ofertas_professor');
    await abrir(adm, P + 'disciplinas/encerrar/' + of.FAP, 'administrador_14_reabrir_oferta');
    await abrir(adm, P + 'configuracao-email/listar', 'administrador_15_email');
    /* E8: telas da E6 e da E7 */
    await abrir(adm, P + 'historico-emissoes/ver/' + em.parcial, 'administrador_16_emissao_cancelar');
    await abrir(adm, P + 'atos-legais/listar', 'administrador_17_atos_legais');
    await abrir(adm, P + 'historico-modelos/listar', 'administrador_18_modelos');
    await abrir(adm, P + 'historico-modelos/editar/1', 'administrador_19_modelo_editor');
    await abrir(adm, P + 'tentativas-acesso/listar', 'administrador_22_tentativas');

    /* ---------------------------------------------------------------- secretaria */
    const sec = await entrar(browser, 'painel', 'secretaria.demo');
    await abrir(sec, P + 'home/index', 'secretaria_01_inicio', { inteira: false });
    await abrir(sec, P + 'materias/listar', 'secretaria_02_materias');
    await abrir(sec, P + 'materias/cadastrar', 'secretaria_03_materia_cadastrar');
    await abrir(sec, P + 'matriz/listar', 'secretaria_04_matriz');
    await abrir(sec, P + 'matriz/visualizar/' + IDS.grade, 'secretaria_05_grade');
    await abrir(sec, P + 'disciplinas/gerar-oferta?grade=' + IDS.grade, 'secretaria_06_gerar_oferta');
    await abrir(sec, P + 'disciplinas/listar/2026', 'secretaria_07_ofertas');
    await abrir(sec, P + 'academicos/listar', 'secretaria_08_alunos');
    await abrir(sec, P + 'academicos/cadastrar', 'secretaria_09_aluno_cadastrar');
    await abrir(sec, P + 'academicos/visualizar/' + al['260108'], 'secretaria_10_aluno_sem_senha');
    // "Enviar link de acesso": sem SMTP, a tela mostra o link. O print sai com o link MASCARADO;
    // o link verdadeiro só serve para o print do primeiro acesso do aluno e é invalidado no fim.
    await Promise.all([sec.waitForLoadState('networkidle'), sec.click('button[name="EnviarLink"]')]);
    const link = await sec.$eval('#linkAcessoAluno', (e) => e.value);
    await sec.$eval('#linkAcessoAluno', (e) => { e.value = e.value.replace(/(atualizar-senha\/|#t=).*/, '$1••••••••••••••••'); });
    await print(sec, 'secretaria_11_link_de_acesso');
    await abrir(sec, P + 'academicos/visualizar/' + al['260101'] + '?tab=tabeMatricula', 'secretaria_12_vinculo');
    await abrir(sec, P + 'portal-alunos/visualizar/260107?ano=2026', 'secretaria_13_enturmacao');
    await abrir(sec, P + 'disciplinas/visualizar/' + of.GOC, 'secretaria_14_oferta_aberta');
    await abrir(sec, P + 'disciplinas/encerrar/' + of.GOC, 'secretaria_15_previa_encerramento');
    await abrir(sec, P + 'medias/final/' + of.FAP, 'secretaria_16_oferta_encerrada');
    await abrir(sec, P + 'academicos/boletim/260103', 'secretaria_17_boletim');
    /* E8: telas da E6 */
    await abrir(sec, P + 'disciplinas/editar/' + of.GOC, 'secretaria_18_oferta_editar');
    await abrir(sec, P + 'academicos/visualizar/' + al['250101'] + '?tab=tabeMatricula', 'secretaria_19_tcc_conclusao');
    await abrir(sec, P + 'historico-emissoes/ver/' + em.final, 'secretaria_21_emissao_final');
    await abrir(sec, P + 'academicos/visualizar/' + al['260104'] + '?tab=tabeHistoricos', 'secretaria_22_aba_historicos');
    await abrir(sec, P + 'historico-emissoes/emitir/' + mc['260104'] + '?tipo=PARCIAL', 'secretaria_23_emitir_parcial');
    await abrir(sec, P + 'historico/previa/' + mc['250102'], 'secretaria_24_previa');
    await abrir(sec, P + 'ajuda/index', 'secretaria_25_central_ajuda');

    /* primeiro acesso do aluno pelo link (a página, sem enviar nada) */
    const novo = await (await browser.newContext({ viewport: { width: 1440, height: 900 }, locale: 'pt-BR' })).newPage();
    await novo.goto(link, { waitUntil: 'networkidle' });
    await print(novo, 'aluno_02_primeiro_acesso', { inteira: false });

    /* ---------------------------------------------------------------- professora */
    const prof = await entrar(browser, 'painel', 'prof.laura.demo');
    await abrir(prof, P + 'home/index', 'professor_01_inicio', { inteira: false });
    await abrir(prof, P + 'minhas-disciplinas/listar', 'professor_02_minhas_disciplinas');
    await abrir(prof, P + 'disciplinas/visualizar/' + of.GOC, 'professor_03_disciplina');
    await abrir(prof, P + 'atividades/listar/' + of.GOC, 'professor_04_atividades');
    await abrir(prof, P + 'atividades/cadastrar/' + of.GOC + '?tipo=5', 'professor_05_atividade_cadastrar');
    await abrir(prof, P + 'atividades/visualizar/' + at.GOC[0], 'professor_06_notas');
    // nota inválida: a tela recusa e explica (nada é gravado)
    await prof.goto(P + 'atividades/visualizar/' + at.GOC[1], { waitUntil: 'networkidle' });
    await limpar(prof);
    const celula = await prof.$('#tabelaLancaNotas tbody tr td.tabledit-view-mode');
    if (celula) {
      await celula.click();
      const campo = await prof.$('#tabelaLancaNotas tbody tr input.tabledit-input:not([type=hidden])');
      if (campo) {
        await campo.fill('10.5');
        await campo.press('Enter');
        await prof.waitForSelector('#ValorErrado.show', { timeout: 5000 }).catch(() => {});
        await print(prof, 'professor_07_nota_invalida', { inteira: false });
      }
    }
    await abrir(prof, P + 'atividades/upload-notas/' + at.GOC[1], 'professor_08_importar_csv');
    await abrir(prof, P + 'faltas/visualizar/' + of.GOC, 'professor_09_faltas');
    await abrir(prof, P + 'medias/final/' + of.GOC, 'professor_10_notas_e_resultado_parcial');
    await abrir(prof, P + 'atividades/visualizar/' + at.FAP[0], 'professor_11_notas_encerrada');
    await abrir(prof, P + 'faltas/visualizar/' + of.FAP, 'professor_12_faltas_encerrada');

    /* ---------------------------------------------------------------- aluno */
    const alu = await entrar(browser, 'portal', '260104');
    await abrir(alu, A + 'home/index', 'aluno_04_inicio', { inteira: false });
    await abrir(alu, A + 'home/boletim-aluno', 'aluno_05_boletim');
    await abrir(alu, A + 'home/notas-atividades', 'aluno_06_notas_atividades');

    /* ---------------------------------------------------------------- o portal dentro de uma página de curso de TESTE
       (Onda 5): a página é servida pelo próprio navegador da captura, no mesmo endereço do portal (não é o Moodle e não
       existe no servidor); o plano B aparece quando a sonda do cookie falha, o que aqui é simulado */
    const cursoTeste = async (ctx, nome, src) => {
      const url = BASE + 'pagina-de-curso-de-teste.html';
      await ctx.route(url, (r) => r.fulfill({ contentType: 'text/html; charset=utf-8', body: `<!doctype html><html><head><meta charset="utf-8"><style>
        body{margin:0;font-family:"Segoe UI",Arial,sans-serif;background:#f4f5f7;color:#222}header{background:#1f3b5c;color:#fff;padding:12px 24px;font-size:18px}
        main{padding:12px 24px}h2{margin:6px 0 4px;font-size:22px}p{margin:0 0 10px;color:#555}iframe{width:100%;height:700px;border:1px solid #ccd;background:#fff}
        </style></head><body><header>Ambiente virtual de aprendizagem (página de teste)</header><main><h2>Curso de teste — Portal do aluno</h2>
        <p>Recurso "URL" embutido: o portal do aluno aparece dentro do curso.</p><iframe src="${src}"></iframe></main></body></html>` }));
      const pg = await ctx.newPage();
      await pg.setViewportSize({ width: 1440, height: 900 });
      try {
        await pg.goto(url, { waitUntil: 'networkidle' });
        await pg.waitForTimeout(1500);
        await print(pg, nome, { inteira: false });
      } catch (e) { falhas.push(nome + ': ' + String(e.message).split(String.fromCharCode(10))[0]); console.log('  FALHA ' + nome); }
      await pg.close();
    };
    await cursoTeste(alu.context(), 'administrador_20_portal_no_moodle', A + 'home/boletim-aluno');
    const semCookie = await browser.newContext({ viewport: { width: 1440, height: 900 }, locale: 'pt-BR' });
    await semCookie.route('**/login/sonda**', (r) => r.fulfill({ contentType: 'application/json', body: '{"n":"x","ok":false}' }));
    await cursoTeste(semCookie, 'administrador_21_plano_b', A + 'login/acesso');
    await semCookie.close();
  } finally {
    await browser.close();
  }
  console.log(feitos.length + ' prints.');
  if (falhas.length) { console.log(falhas.length + ' falha(s).'); process.exit(1); }
})().catch((e) => { console.error('FALHA: ' + e.message); process.exit(1); });
