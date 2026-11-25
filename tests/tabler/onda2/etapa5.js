/*
 * ONDA 2, ETAPA 5 — a importação de alunos por planilha.
 *   A. permissão nos 3 pontos: o MENU (Educacional → Importar alunos: Administrador, Supervisão, Secretaria; não
 *      Direção, Coordenação, Professor), a ROTA (os outros são recusados) e o BOTÃO (Desfazer só para quem tem ações
 *      sensíveis; a Secretaria nem pela rota);
 *   B. o arquivo fictício de 50 linhas (Windows-1252, cada armadilha de propósito): a conferência linha a linha, com o
 *      motivo; o de-para dos valores; a correção na tela; aplicar recusado com erro pendente; aplicar (secretaria);
 *      as contagens antes e depois; o que foi gravado é o que a tela grava;
 *   C. reimportar o mesmo arquivo não duplica (todos pulados);
 *   D. desfazer: recusado enquanto um aluno do lote tem uso (diz qual); desfeito pelo Administrador; as contagens
 *      voltam; o que existia antes continua intacto (a régua do começo da prova, IDÊNTICA fora do lote);
 *   E. os cabeçalhos em 4 grafias; a coluna não reconhecida, o ajuste manual e a memória para o arquivo igual;
 *   F. sabotagem: sem a conferência do RA repetido no arquivo, a prova reprova.
 *
 *   node tests/tabler/onda2/etapa5.js     (depois do ciclo, com a demonstração)
 */
const fs = require('fs');
const path = require('path');
const { execFileSync, spawnSync } = require('child_process');
const L = require('../lib');
const P = require('./planilha_alunos');

const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste2?$/.test(BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }
const MYSQL = process.env.VCOSTAEDU_MYSQL || 'C:/xampp/mysql/bin/mysql.exe';
const PHP = process.env.VCOSTAEDU_PHP || 'C:/xampp/php/php.exe';
const sql = (q) => execFileSync(MYSQL, ['-u', 'root', '-N', '--default-character-set=utf8mb4', BANCO, '-e', q]).toString().replace(/\r/g, '').trim();
const um = (q) => +sql(q);
const cred = JSON.parse(fs.readFileSync(path.join(__dirname, '..', 'saida', 'credenciais.json'), 'utf8'));
const ETAPA = 'onda2/etapa5';
const MODELO = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'importacao', 'AdmsImportacaoAlunos.php');
const REGUA_ANTES = 'tests/tabler/saida/regua-etapa5-antes.json';

const contagens = () => ({
  alunos: um('SELECT COUNT(*) FROM col_usuarios WHERE col_niveis_acesso_id = 1'),
  info: um('SELECT COUNT(*) FROM col_usuarios_informacoes'),
  vinculos: um('SELECT COUNT(*) FROM rm_matriculas_curso'),
  posicoes: um('SELECT COUNT(*) FROM rm_matriculas_ensino'),
});

async function entrar(navegador, usuario) {
  const ctx = await navegador.newContext({ viewport: { width: 1366, height: 900 }, locale: 'pt-BR' });
  const p = await L.pagina(ctx);
  await p.goto(L.BASE + 'painel/login/acesso');
  await p.fill('input[name="usuario"]', usuario);
  await p.fill('input[name="senha"]', cred.painel[usuario]);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
  return { ctx, p };
}
const postar = (p, rota, form) => p.evaluate(async ([u, f]) => {
  const r = await fetch(u, { method: 'POST', body: new URLSearchParams(f), credentials: 'same-origin' });
  return r.status;
}, [L.BASE + rota, form]);
const abriu = async (p, rota) => {
  await p.goto(L.BASE + rota);
  return decodeURIComponent(p.url()).replace(L.BASE, '').replace(/[?#].*$/, '') === rota && !(await p.$('input[name="SendLogin"]')) && !(await p.$('#semAcesso'));
};

/** Envia pela tela (o campo de arquivo de verdade) e devolve o id do lote criado. */
async function enviar(p, nome, buffer) {
  await p.goto(L.BASE + 'painel/importacao-alunos/listar');
  await p.setInputFiles('#arquivoImportacao', { name: nome, mimeType: 'text/csv', buffer });
  await Promise.all([p.waitForNavigation(), p.click('#enviarImportacao')]);
  const m = /importacao-alunos\/conferir\/(\d+)/.exec(p.url());
  return m ? +m[1] : 0;
}
const linha = async (p, n) => ({
  res: await p.getAttribute(`#linha-${n}`, 'data-resultado').catch(() => null),
  msg: await p.$eval(`#linha-${n} td:nth-child(6)`, (e) => e.textContent).catch(() => ''),
});
const NUM = { pronta: '1', aviso: '2', erro: '3', pulada: '4', aplicada: '5' };

async function sabotar(nome, estragar, consertar, conferir) {
  let reprovou = false;
  try { await estragar(); reprovou = !(await conferir()); } finally { await consertar(); }
  L.ok(reprovou, `sabotagem (${nome}): a prova reprova o defeito`);
}

(async () => {
  const { navegador } = await L.abrir();
  try {
    console.log('\n== Onda 2, Etapa 5');
    /* a régua do começo: o que existia antes da importação */
    const g = spawnSync(PHP, ['tests/tabler/regua.php', 'gravar', REGUA_ANTES], { cwd: L.RAIZ, encoding: 'utf8' });
    L.ok(g.status === 0, 'a régua do começo da prova gravada');
    const ref = {
      curso: sql('SELECT nome FROM rm_cursos ORDER BY id LIMIT 1'),
      ano: um('SELECT MAX(ano) FROM adms_periodos_letivos'),
      polo: sql('SELECT nome FROM rm_polos WHERE situacao = 1 ORDER BY id LIMIT 1'),
      raExistente: sql('SELECT rm FROM col_usuarios WHERE col_niveis_acesso_id = 1 ORDER BY id LIMIT 1'),
    };

    /* A. permissão: o menu e a rota */
    const quem = { administrador: true, 'supervisao.demo': true, 'secretaria.demo': true, 'direcao.demo': false, 'coord.demo': false, 'prof.laura.demo': false };
    const sessoes = {};
    for (const [u, pode] of Object.entries(quem)) {
      const s = await entrar(navegador, u);
      sessoes[u] = s;
      const noMenu = !!(await s.p.$('#railMenu a[href*="importacao-alunos/listar"]'));
      const abre = await abriu(s.p, 'painel/importacao-alunos/listar');
      L.ok(noMenu === pode && abre === pode, `${u}: ${pode ? 'o item no menu Educacional e a tela abre' : 'sem o item no menu e a rota recusada'}`);
    }
    const sec = sessoes['secretaria.demo'];
    const adm = sessoes.administrador;
    const sup = sessoes['supervisao.demo'];
    for (const u of ['direcao.demo', 'coord.demo', 'prof.laura.demo']) { await sessoes[u].ctx.close(); }

    /* B. o arquivo de 50 linhas, pela Secretaria */
    const antes = contagens();
    const plan = P.principal(ref);
    const lote = await enviar(sec.p, plan.nome, plan.buffer);
    L.ok(lote > 0, `o arquivo enviado pela tela abre a conferência (lote ${lote})`);
    const lido = await sec.p.$eval('#arquivoLido', (e) => e.textContent).catch(() => '');
    L.ok(/50 linha/.test(lido) && /Windows-1252/.test(lido) && /";"/.test(lido) && /Nada foi gravado/.test(lido), `a codificação e o separador detectados ("${lido.replace(/\s+/g, ' ').trim().slice(0, 80)}…")`);
    L.ok(JSON.stringify(contagens()) === JSON.stringify(antes), 'enviar não grava aluno nenhum');
    let erradas = [];
    for (const [n, e] of Object.entries(plan.esperado)) {
      const r = await linha(sec.p, n);
      if (r.res !== NUM[e.res] || (e.motivo && !e.motivo.test(r.msg))) { erradas.push(`${n}: ${r.res} "${r.msg.trim().slice(0, 60)}"`); }
    }
    L.ok(erradas.length === 0, `as 50 linhas conferidas, cada uma com o resultado e o motivo esperados${erradas.length ? ' — diferentes: ' + erradas.slice(0, 4).join(' | ') : ''}`);
    const cont = async () => Object.fromEntries(await sec.p.$$eval('[data-contagem]', (es) => es.map((e) => [e.dataset.contagem, +e.textContent])));
    let c = await cont();
    L.ok(c.total === 50 && c.prontas === 36 && c.avisos === 2 && c.erros === 11 && c.puladas === 1, `os contadores: ${JSON.stringify(c)}`);
    const nomeAcento = await sec.p.$eval('#linha-2 td:nth-child(3)', (e) => e.textContent.trim()).catch(() => '');
    L.ok(nomeAcento === 'Ana Júlia Conceição Exemplo', `o acento do Windows-1252 chega certo ("${nomeAcento}")`);
    const naoRec = await sec.p.$$eval('#deParaValores tr.table-danger td:first-child', (t) => t.map((x) => x.textContent.trim()));
    const sexoTab = await sec.p.$$eval('#deParaValores table[data-campo="sexo"] tbody tr', (t) => t.map((x) => [...x.cells].map((c) => c.textContent.trim()).join(' ')));
    L.ok(naoRec.includes('X') && naoRec.includes('Por correspondência') && sexoTab.some((t) => /^fem Feminino/.test(t)) && sexoTab.some((t) => /^MASC Masculino/.test(t)),
      `o de-para dos valores: cada valor distinto e o que virou; não reconhecidos: ${naoRec.join(', ')}`);
    await L.print(sec.p, ETAPA, 'conferencia');

    /* a correção na tela: a data impossível da linha 44 */
    await sec.p.click('#linha-44 .corrigirLinha');
    await sec.p.waitForSelector('#modalCorrigir.show');
    L.ok((await sec.p.inputValue('#corrigir_nascimento')) === '31/02/2001', 'o modal da correção traz os valores da linha');
    await L.print(sec.p, ETAPA, 'corrigir-linha');
    await sec.p.fill('#corrigir_nascimento', '28/02/2001');
    await Promise.all([sec.p.waitForNavigation(), sec.p.click('#salvarCorrecao')]);
    const r44 = await linha(sec.p, 44);
    c = await cont();
    L.ok(r44.res === '1' && c.erros === 10 && c.prontas === 37 && um(`SELECT COUNT(*) FROM adms_importacoes_linhas WHERE importacao_id = ${lote} AND numero = 44 AND correcao LIKE '%28/02/2001%'`) === 1,
      'corrigida na tela, sem reenviar: a linha 44 fica pronta (e a correção guardada à parte do arquivo)');

    /* aplicar com erro pendente: recusado */
    await sec.p.selectOption('#formaIngresso', { index: 1 });
    await Promise.all([sec.p.waitForNavigation(), sec.p.click('#aplicarLote')]);
    L.ok(await sec.p.isVisible('#aplicarRecusado') && JSON.stringify(contagens()) === JSON.stringify(antes), 'aplicar com linhas com erro, sem marcar "aplicar sem elas": recusado, nada gravado');

    /* aplicar */
    await sec.p.selectOption('#formaIngresso', { index: 1 });
    await sec.p.check('#aceitarErros');
    await Promise.all([sec.p.waitForNavigation({ timeout: 120000 }), sec.p.click('#aplicarLote')]);
    const depois = contagens();
    const msgAplicar = await sec.p.$eval('.alert', (e) => e.textContent.replace(/\s+/g, ' ').trim()).catch(() => '');
    const vinc = Object.entries(plan.esperado).filter(([n, e]) => e.vinculo && e.res !== 'erro').length;
    L.ok(await sec.p.isVisible('#loteAplicado') && depois.alunos === antes.alunos + 39 && depois.info === antes.info + 39 && depois.vinculos === antes.vinculos + vinc && depois.posicoes === antes.posicoes + vinc,
      `aplicado: alunos ${antes.alunos} → ${depois.alunos} (+39), vínculos ${antes.vinculos} → ${depois.vinculos} (+${vinc}), posições no ano +${depois.posicoes - antes.posicoes}${depois.alunos === antes.alunos ? ' — a tela: ' + msgAplicar.slice(0, 200) : ''}`);
    L.ok(um(`SELECT COUNT(*) FROM adms_importacoes WHERE id = ${lote} AND situacao = 2 AND aplicadas = 39 AND vinculos = ${vinc} AND aplicada_por IS NOT NULL AND aplicada_em IS NOT NULL`) === 1,
      'o lote identificado: quem aplicou, quando e quantos');
    const a1 = sql("SELECT CONCAT_WS('|', u.nome, u.genero_id, IFNULL(u.genero_confirmado_em,'(vazio)'), u.senha, u.usuario, u.email, i.cpf, i.nasc_data) FROM col_usuarios u JOIN col_usuarios_informacoes i ON i.col_user_id = u.id WHERE u.rm = '900004'").split('|');
    L.ok(a1[0] === 'Débora Inês Peçanha Exemplo' && a1[3] === '-inexistente' && a1[4] === '900004' && a1[5] === 'aluno900004@exemplo.invalid' && /^\d{11}$/.test(a1[6]) && a1[7] === '1984-05-05',
      'o aluno gravado como a tela grava (o nome com acento, o RA como usuário, a senha só pelo link, o CPF só com dígitos, a data)');
    const semSexo = sql("SELECT CONCAT_WS('|', IFNULL(genero_id,'(vazio)'), IFNULL(genero_confirmado_em,'(vazio)')) FROM col_usuarios WHERE rm = '900012'");
    L.ok(semSexo === '3|(vazio)' && um("SELECT COUNT(*) FROM col_usuarios u WHERE u.rm = '900012' AND u.genero_confirmado_em IS NULL AND u.genero_id NOT IN (1, 2)") === 1, 'sem o sexo no arquivo: sem a confirmação do sexo, na lista "Alunos sem sexo informado" (como os alunos de antes da regra)');
    const mc = sql("SELECT CONCAT_WS('|', mc.curso_id, mc.grade_id, mc.situacao_id, mc.forma_ingresso_id, me.ano) FROM rm_matriculas_curso mc JOIN col_usuarios u ON u.id = mc.usuario_id JOIN rm_matriculas_ensino me ON me.matricula_curso_id = mc.id WHERE u.rm = '900004'").split('|');
    L.ok(mc.length === 5 && +mc[4] === ref.ano && mc[2] === '1', `o vínculo pela função da tela (curso ${mc[0]}, grade ${mc[1]}, cursando, ano ${mc[4]})`);
    await L.print(sec.p, ETAPA, 'aplicada');

    /* C. reimportar o mesmo arquivo: nada duplica */
    const lote2 = await enviar(sec.p, plan.nome, plan.buffer);
    c = await cont();
    L.ok(lote2 > lote && c.puladas === 40 && c.prontas === 0 && JSON.stringify(contagens()) === JSON.stringify(depois), `reimportar o mesmo arquivo: os 39 gravados e o que já existia pulados (${c.puladas}), com aviso; nada duplica`);
    L.ok(/Já cadastrado: RA 900004/.test((await linha(sec.p, 5)).msg), 'o aviso diz quem já estava cadastrado');

    /* A (o botão e a rota da ação sensível) */
    await sec.p.goto(L.BASE + 'painel/importacao-alunos/conferir/' + lote);
    const botaoSec = !!(await sec.p.$('#desfazerLote'));
    await postar(sec.p, 'painel/importacao-alunos/desfazer/' + lote, { motivo: 'tentativa da secretaria pela rota' });
    L.ok(!botaoSec && um(`SELECT situacao FROM adms_importacoes WHERE id = ${lote}`) === 2 && contagens().alunos === depois.alunos, 'a Secretaria não vê "Desfazer" e não desfaz nem pela rota (ação sensível)');
    await sup.p.goto(L.BASE + 'painel/importacao-alunos/conferir/' + lote);
    L.ok(await sup.p.isVisible('#desfazerLote'), 'a Supervisão de secretaria vê "Desfazer"');

    /* D. desfazer recusado enquanto um aluno tem uso */
    sql("UPDATE col_usuarios SET senha = '$2y$10$abcdefghijklmnopqrstuuJ0ZqWl9yWQ7f2v3l3yT6m0aZ9nN1K2S' WHERE rm = '900007'");
    await adm.p.goto(L.BASE + 'painel/importacao-alunos/conferir/' + lote);
    const imp = await adm.p.$eval('#impedimentosDesfazer', (e) => e.textContent).catch(() => '');
    await postar(adm.p, 'painel/importacao-alunos/desfazer/' + lote, { motivo: 'prova: desfazer com aluno em uso' });
    L.ok(/RA 900007/.test(imp) && /senha do portal/.test(imp) && !(await adm.p.$('#desfazerLote')) && um(`SELECT situacao FROM adms_importacoes WHERE id = ${lote}`) === 2 && contagens().alunos === depois.alunos,
      'com um aluno do lote em uso (a senha do portal criada), desfazer é recusado e a tela diz qual');
    await L.print(adm.p, ETAPA, 'desfazer-recusado');
    sql("UPDATE col_usuarios SET senha = '-inexistente' WHERE rm = '900007'");

    /* desfazer pelo Administrador */
    await adm.p.goto(L.BASE + 'painel/importacao-alunos/conferir/' + lote);
    await adm.p.fill('#motivoDesfazer', 'Prova da Etapa 5: o arquivo fictício.');
    adm.p.once('dialog', (d) => d.accept());
    await L.confirmarSozinho(adm.p); // D-100: a confirmação é a janela do sistema
    await Promise.all([adm.p.waitForNavigation({ timeout: 120000 }), adm.p.click('#desfazerLote')]);
    const volta = contagens();
    L.ok(await adm.p.isVisible('#loteDesfeito') && JSON.stringify(volta) === JSON.stringify(antes), `desfeito: alunos ${depois.alunos} → ${volta.alunos}, vínculos ${depois.vinculos} → ${volta.vinculos} (as contagens de antes)`);
    L.ok(um(`SELECT COUNT(*) FROM adms_importacoes WHERE id = ${lote} AND situacao = 3 AND desfeita_por IS NOT NULL AND desfeita_motivo LIKE 'Prova da Etapa 5%'`) === 1, 'o lote fica como desfeito, com quem, quando e o motivo');
    await L.print(adm.p, ETAPA, 'desfeita');
    const reg = spawnSync(PHP, ['tests/tabler/regua.php', 'comparar', REGUA_ANTES], { cwd: L.RAIZ, encoding: 'utf8' });
    const difs = [...new Set((reg.stdout.match(/^\s+tabelas\/([a-z_0-9]+)/gm) || []).map((x) => x.trim().replace('tabelas/', '')))];
    const fora = difs.filter((t) => !['adms_importacoes', 'adms_importacoes_linhas'].includes(t));
    L.ok(fora.length === 0 && !/vidas\/|boletins\//.test(reg.stdout), `o que existia antes continua intacto: a régua IDÊNTICA à do começo da prova${difs.length ? ' (difere só o registro dos lotes: ' + difs.join(', ') + ')' : ''}`);

    /* E. os cabeçalhos em 4 grafias */
    for (const [nome, buf, iRa, iNome] of P.grafias()) {
      const id = await enviar(adm.p, nome, buf);
      const vRa = await adm.p.$eval(`select.vc-mapa[data-coluna="${iRa}"]`, (s) => s.value).catch(() => '');
      const vNome = await adm.p.$eval(`select.vc-mapa[data-coluna="${iNome}"]`, (s) => s.value).catch(() => '');
      const r = await linha(adm.p, 2);
      L.ok(id > 0 && vRa === 'ra' && vNome === 'nome' && ['1', '2'].includes(r.res), `cabeçalho "${((buf.toString('utf8').includes('�') ? buf.toString('latin1') : buf.toString('utf8')).split(/\r?\n/)[0]).replace(/^\W+/, '').slice(0, 60)}": RA e nome reconhecidos, a linha pronta (ou só com o aviso do sexo)`);
    }
    await L.print(adm.p, ETAPA, 'colunas');
    const [nSem, bSem] = P.semRa(String(Date.now()));
    const idSem = await enviar(adm.p, nSem, bSem);
    const semColuna = await linha(adm.p, 2);
    L.ok(/não tem a coluna do RA/.test(semColuna.msg) && (await adm.p.$eval('select.vc-mapa[data-coluna="0"]', (s) => s.value)) === '', 'a coluna "Código" não é adivinhada: as linhas acusam a falta do RA');
    await adm.p.selectOption('select.vc-mapa[data-coluna="0"]', 'ra');
    await Promise.all([adm.p.waitForNavigation(), adm.p.click('#salvarMapa')]);
    L.ok((await linha(adm.p, 2)).res === '1' && (await linha(adm.p, 3)).res === '1', 'o ajuste manual da coluna: as linhas ficam prontas');
    const idSem2 = await enviar(adm.p, nSem, bSem);
    L.ok(idSem2 > idSem && (await adm.p.$eval('select.vc-mapa[data-coluna="0"]', (s) => s.value)) === 'ra' && (await linha(adm.p, 2)).res === '1', 'a memória: o mesmo arquivo de novo já vem com o ajuste');

    /* o modelo para baixar */
    const modelo = await adm.p.evaluate(async (u) => { const r = await fetch(u, { credentials: 'same-origin' }); return [r.status, r.headers.get('content-disposition') || '', await r.text()]; }, L.BASE + 'painel/importacao-alunos/modelo');
    L.ok(modelo[0] === 200 && /modelo-importacao-alunos\.csv/.test(modelo[1]) && /^\uFEFF?RA;Nome;CPF;E-mail;Nascimento;Sexo;Telefone;Situação;Curso;Grade;Ano;Polo;Modalidade/.test(modelo[2]), 'o modelo da planilha para baixar, com as colunas');
    await adm.p.goto(L.BASE + 'painel/importacao-alunos/listar');
    await L.print(adm.p, ETAPA, 'lotes');

    /* F. sabotagem: sem a conferência do RA repetido */
    const orig = fs.readFileSync(MODELO, 'utf8');
    await sabotar('o RA repetido no arquivo deixa de ser conferido',
      () => fs.writeFileSync(MODELO, orig.replace("if (count($vistos['ra'][preg_replace('/\\s+/', '', $v['ra'])] ?? []) > 1) {", 'if (false) {')),
      () => fs.writeFileSync(MODELO, orig),
      async () => { await enviar(adm.p, plan.nome, plan.buffer); const r = await linha(adm.p, 42); return r.res === '3' && /repetido/.test(r.msg); });
    L.ok(fs.readFileSync(MODELO, 'utf8') === orig, 'o modelo voltou ao original depois da sabotagem');

    for (const s of [sec, adm, sup]) { await s.ctx.close(); }
  } catch (e) {
    L.ok(false, 'erro na prova: ' + e.message.split('\n')[0]);
  } finally {
    await navegador.close();
    process.exit(L.resumo('onda 2, etapa 5') ? 0 : 1);
  }
})();
