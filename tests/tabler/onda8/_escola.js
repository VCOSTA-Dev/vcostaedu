/*
 * O que as provas da escola (Onda 8: a segunda leva da correção e o financeiro) usam juntas: o banco de prova, a entrada pela tela, a demonstração do
 * colégio e o arranjo de um aluno com um plano (feito pela tela da secretaria, como uma pessoa faria).
 */
const path = require('path');
const { spawnSync, execFileSync } = require('child_process');
const L = require('../lib');

const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
if (!/_teste$/.test(BANCO)) { console.error('VCOSTAEDU_BANCO_TESTE precisa terminar em _teste'); process.exit(2); }

/* o mysql do Windows termina as linhas com CR: cada linha sai aparada */
const sql = (q) => execFileSync('C:/xampp/mysql/bin/mysql.exe', ['-uroot', '--default-character-set=utf8mb4', '-N', '-B', BANCO, '-e', q], { encoding: 'utf8' }).split('\n').map((l) => l.trim()).filter(Boolean).join('\n');
const um = (q) => +(sql(q).split('\n')[0] || 0);
const montar = (rotulo) => spawnSync(process.execPath, [path.join(__dirname, '..', 'onda6', 'medir_demo.js'), 'escola', rotulo], { encoding: 'utf8', maxBuffer: 1 << 24 });
const cred = () => JSON.parse(sql("SELECT valor FROM adms_instalacao_estado WHERE chave = 'demonstracao.credenciais'"));
const ymd = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
const hoje = () => ymd(new Date());
const diasAntes = (n) => ymd(new Date(Date.now() - n * 86400000));

async function entrar(navegador, usuario, senha, lado = 'painel', largura = 1366) {
  const ctx = await navegador.newContext({ viewport: { width: largura, height: 900 }, locale: 'pt-BR' });
  const p = await L.pagina(ctx);
  p.on('dialog', (d) => d.accept()); // as confirmações ("Receber R$ …?") são aceitas
  await p.goto(L.BASE + lado + '/login/acesso');
  await p.fill('input[name="usuario"]', usuario);
  await p.fill('input[name="senha"]', senha);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
  return { ctx, p };
}

/**
 * 1.103.1 (D-137, D-138): o que um perfil vê nas listas de disciplinas — Minhas Disciplinas (todas as situações) e, se
 * o perfil abre, Disciplinas (todas): o filtro por ano (as opções e o marcado), o cabeçalho e as linhas (a primeira
 * célula e o número da oferta da linha).
 */
async function listasDisciplinas(navegador, usuario, senha) {
  const s = await entrar(navegador, usuario, senha, 'painel', 1366);
  const ler = async (url) => {
    await s.p.goto(L.BASE + url);
    if (await s.p.$('#semAcesso')) { return null; }
    return s.p.evaluate(() => {
      const sel = document.querySelector('#pesqAno');
      const opcoes = sel ? [...sel.options].map((o) => o.value).filter(Boolean) : [];
      const marcado = sel && sel.selectedIndex >= 0 ? sel.options[sel.selectedIndex].value : '';
      const th = [...document.querySelectorAll('#tabelaDisciplinas thead th')].map((x) => x.textContent.trim());
      const linhas = [...document.querySelectorAll('#tabelaDisciplinas tbody tr')].filter((tr) => tr.cells.length > 1)
        .map((tr) => ({ primeira: tr.cells[0].textContent.trim(), oferta: tr.getAttribute('data-oferta') || (tr.querySelector('a[href*="disciplinas/visualizar/"]') || { href: '' }).href.replace(/.*visualizar\//, '') }));
      const t = document.querySelector('#tabelaDisciplinas');
      return { opcoes, marcado, th, linhas, ordem: t ? t.getAttribute('data-ordem') : '' };
    });
  };
  const minhas = await ler('painel/minhas-disciplinas/listar?situacao=todas');
  const todas = await ler('painel/disciplinas/listar');
  await s.ctx.close();
  return { minhas, todas };
}

/** O filtro por ano de uma lista oferece o ano que a lista mostra, marcado (D-137); uma lista vazia passa. */
const filtroAnoOk = (l) => !l || l.linhas.length === 0 || (l.opcoes.length > 0 && /^\d{4}$/.test(l.marcado) && l.opcoes.includes(l.marcado));

/** A primeira coluna "#" com o número da oferta, único, antes do "Código"; a ordem de partida pela disciplina (D-138). */
const colunaNumeroOk = (l) => !l || (l.th[0] === '#' && l.th[1] === 'Código' && l.ordem === '[[2,"asc"]]'
  && l.linhas.every((x) => /^[0-9]+$/.test(x.primeira) && x.primeira === x.oferta) && new Set(l.linhas.map((x) => x.primeira)).size === l.linhas.length);

/** O plano (pela tela) e as parcelas dele para o aluno (pela ficha). @return {plano, titulos: [{id, parcela, vencimento}]} */
async function arranjo(p, ra, { nome = 'Plano de prova', parcelas = 3, valor = '500,00', dia = 20, mes = 2 } = {}) {
  await p.goto(L.BASE + 'painel/fin-planos/listar');
  await p.fill('#pNome', nome);
  await p.fill('#pParcelas', String(parcelas));
  await p.fill('#pValor', valor);
  await p.fill('#pDia', String(dia));
  await p.selectOption('#pMes', String(mes));
  await Promise.all([p.waitForNavigation(), p.click('#salvarPlano')]);
  const plano = um(`SELECT MAX(id) FROM fin_planos WHERE nome = '${nome}'`);
  const aluno = um(`SELECT id FROM col_usuarios WHERE rm = '${ra}'`);
  await p.goto(L.BASE + `painel/financeiro/ficha/${aluno}`);
  await novoTitulo(p); // D-99: o plano e o avulso ficam na janela "Novo título", fora do corpo da ficha
  await p.selectOption('#apPlano', String(plano));
  await Promise.all([p.waitForNavigation(), p.click('#aplicarPlano')]);
  const titulos = sql(`SELECT id, parcela, vencimento FROM fin_titulos WHERE aluno_id = ${aluno} AND plano_id = ${plano} ORDER BY parcela`).split('\n').filter(Boolean)
    .map((l) => { const [id, parcela, vencimento] = l.split('\t'); return { id: +id, parcela: +parcela, vencimento }; });
  return { plano, aluno, titulos };
}

/** Abre a janela "Novo título" da ficha (o título avulso e o plano). */
async function novoTitulo(p) {
  await p.click('#botaoNovoTitulo');
  await p.waitForSelector('#modalNovoTitulo.show', { timeout: 5000 });
}

/**
 * Abre a ação do título (D-99, a ficha refeita) e devolve o formulário dela: Receber leva à tela própria (Receber
 * pagamento); Baixar, Cancelar e os desfazer abrem, pelo menu Ações da linha, a janela do motivo.
 */
async function abrirAcao(p, titulo, acao) {
  if (acao === 'receber') {
    await p.goto(L.BASE + `painel/financeiro/receber-pagamento/${um(`SELECT aluno_id FROM fin_titulos WHERE id = ${titulo}`)}?titulos=${titulo}`);
    return '#formReceber';
  }
  const linha = `#tabelaTitulos tr[data-titulo="${titulo}"][data-situacao]`;
  await p.click(`${linha} .vc-acoes`);
  await p.click(`${linha} .vc-item-${acao.replace(/_/g, '-')}`);
  await p.waitForSelector('#modalAcaoTitulo.show', { timeout: 5000 });
  return '#formAcaoTitulo';
}

/** Envia o formulário e confirma na janela do próprio sistema (D-99: sem a janela do navegador); espera a página de depois. */
async function enviar(p, form, botao = 'button[type="submit"]') {
  await p.click(`${form} ${botao}`);
  await p.waitForSelector('#vcConfirmar.show', { timeout: 5000 });
  await Promise.all([p.waitForNavigation(), p.click('#vcConfirmarSim')]);
}

/** Recebe os títulos pela tela de Receber pagamento: a data, uma forma (com os dados dela) e o botão. */
async function receber(p, titulos, { data = null, forma = 'dinheiro', campos = {}, depois = 'ficha' } = {}) {
  const aluno = um(`SELECT aluno_id FROM fin_titulos WHERE id = ${titulos[0]}`);
  await p.goto(L.BASE + `painel/financeiro/receber-pagamento/${aluno}?titulos=${titulos.join(',')}` + (data ? `&data=${data}` : ''));
  await p.check(`.vc-escolhe-forma[value="${forma}"]`, { force: true });
  for (const [k, v] of Object.entries(campos)) {
    const sel = `#formaUnica [name="formas[0][${k}]"]`;
    if ((await p.$eval(sel, (e) => e.tagName)) === 'SELECT') { await p.selectOption(sel, String(v)); } else { await p.fill(sel, String(v)); }
  }
  await enviar(p, '#formReceber', depois === 'recibo' ? '#receberRecibo' : '#receberFinalizar');
}

/** Desfazer recebimento (o recibo inteiro), pela tela dele: a data, o motivo da lista e a justificativa. */
async function desfazerRecebimento(p, titulo, { motivo = 2, justificativa = 'Lançado errado (prova)', data = null } = {}) {
  const recibo = um(`SELECT recibo_id FROM fin_recibo_titulos WHERE titulo_id = ${titulo} AND situacao = 'valido'`);
  await p.goto(L.BASE + `painel/financeiro/desfazer-recebimento/${recibo}`);
  if (data) { await p.fill('#dataDesfazer', data); }
  await p.selectOption('#motivoDesfazer', String(motivo));
  await p.fill('#justificativaDesfazer', justificativa);
  await enviar(p, '#formDesfazerRecebimento');
  return recibo;
}

const situacao = (titulo) => sql(`SELECT situacao FROM fin_titulos WHERE id = ${titulo}`);

/* D-105: o texto do alerta de recusa da tela (vazio sem ele), sem esperar */
async function recusaDaTela(p) {
  const el = await p.$('#financeiroRecusado');
  return el ? (await el.textContent()).trim() : '';
}

/** "AAAA-MM-DD" do mesmo dia, $meses depois (o dia que não existe vira o último do mês) — a regra do servidor. */
function noMes(base, meses, dia) {
  let y = +base.slice(0, 4);
  let m = +base.slice(5, 7) - 1 + meses;
  y += Math.floor(m / 12);
  m = ((m % 12) + 12) % 12;
  const ult = new Date(Date.UTC(y, m + 1, 0)).getUTCDate();
  return `${y}-${String(m + 1).padStart(2, '0')}-${String(Math.min(dia, ult)).padStart(2, '0')}`;
}

/**
 * D-105: Renegociar pela tela própria — a ficha no modo lote (marcar, Continuar) leva a "Renegociar títulos": a data, o
 * acréscimo e o desconto (com a justificativa), as condições, "Gerar proposta", o motivo e o botão (o.acao: salvar, avista
 * ou parcelada), confirmado na janela do sistema. o.antes(p) roda antes do clique (para mexer na tela).
 * @return {erro, tela: {valor, parcelas: [[rótulo, vencimento, centavos]], avista}, recusa, ren, proposta}
 */
async function renegociar(p, aluno, ids, o = {}) {
  await p.goto(L.BASE + `painel/financeiro/ficha/${aluno}?modo=renegociar`);
  for (const id of ids) { await p.check(`#tabelaTitulos input.vc-marca[value="${id}"]`); }
  await Promise.all([p.waitForNavigation(), p.click('#continuarLote')]);
  if (o.data) {
    await p.fill('#dataRenegociacao', o.data);
    const br = o.data.split('-').reverse().join('/');
    await p.waitForFunction((b) => (document.getElementById('avisoData').textContent || '').includes('A conta é a de ' + b), br, { timeout: 15000 });
  }
  const campos = [['#acrescimo', o.acrescimo], ['#desconto', o.desconto], ['#justificativa', o.justificativa], ['#pEntrada', o.entrada], ['#pEntradaVenc', o.entradaVenc],
    ['#pParcelas', o.parcelas], ['#pDia', o.dia], ['#vDesconto', o.vDesconto], ['#vVencimento', o.vVenc]];
  if (o.entradaTipo) { await p.selectOption('#pEntradaTipo', o.entradaTipo); }
  if (o.vDescontoTipo) { await p.selectOption('#vDescontoTipo', o.vDescontoTipo); }
  for (const [sel, v] of campos) {
    if (v !== undefined && v !== null) { await p.fill(sel, String(v)); }
  }
  await p.click('#gerarProposta');
  const erro = (await p.textContent('#erroProposta')).trim();
  if (erro) { return { erro }; }
  const tela = await p.evaluate(() => ({
    valor: +document.getElementById('qRenegociar').dataset.c,
    parcelas: Array.from(document.querySelectorAll('#tabelaParcelada tbody tr')).map((tr) => [tr.cells[0].innerText.trim().split(' ')[0],
      (tr.querySelector('input') || {}).value || tr.cells[1].innerText.trim().split('/').reverse().join('-'), +tr.querySelector('.vc-valor-parcela').dataset.c]),
    avista: +document.querySelector('#tabelaAvista .vc-valor-parcela').dataset.c,
  }));
  for (const [n, v] of Object.entries(o.vencimentos || {})) { await p.fill(`[name="p_venc[${n}]"]`, v); }
  if (o.motivo) { await p.selectOption('#motivoAcordo', String(o.motivo)); }
  if (o.observacao) { await p.fill('#observacaoAcordo', o.observacao); }
  if (o.antes) { await o.antes(p); }
  if (o.acao === 'nada') { return { erro: '', tela }; }
  await p.click({ salvar: '#salvarProposta', avista: '#efetivarAvista', parcelada: '#efetivarParcelada' }[o.acao || 'parcelada']);
  await p.waitForSelector('#vcConfirmar.show', { timeout: 5000 });
  await Promise.all([p.waitForNavigation(), p.click('#vcConfirmarSim')]);
  return { erro: '', tela, recusa: await recusaDaTela(p), ren: um(`SELECT COALESCE(MAX(id), 0) FROM fin_renegociacoes WHERE aluno_id = ${aluno}`),
    proposta: um(`SELECT COALESCE(MAX(id), 0) FROM fin_propostas WHERE aluno_id = ${aluno}`) };
}

/** D-105: Efetivar acordo (a proposta salva), pela aba Renegociações: a condição e a janela do sistema. @return a recusa ('' se gravou) */
async function efetivarProposta(p, aluno, proposta, condicao = 'parcelada') {
  await p.goto(L.BASE + `painel/financeiro/ficha/${aluno}?ver=renegociacoes`);
  const linha = `#tabelaRenegociacoes tr[data-proposta="${proposta}"]`;
  await p.click(`${linha} .vc-acoes-ren`);
  await p.click(`${linha} .vc-item-efetivar`);
  await p.waitForSelector('#modalEfetivar.show', { timeout: 5000 });
  await p.check(`#formEfetivar input[name="condicao"][value="${condicao}"]`);
  await enviar(p, '#formEfetivar');
  return recusaDaTela(p);
}

/** D-105: Desfazer renegociação, pela aba Renegociações: o motivo da lista e a observação. @return a recusa ('' se gravou) */
async function desfazerRenegociacao(p, aluno, ren, { motivo = 81, observacao = 'Pedido da família (prova)' } = {}) {
  await p.goto(L.BASE + `painel/financeiro/ficha/${aluno}?ver=renegociacoes`);
  const linha = `#tabelaRenegociacoes tr[data-renegociacao="${ren}"]`;
  await p.click(`${linha} .vc-acoes-ren`);
  await p.click(`${linha} .vc-item-desfazer-ren`);
  await p.waitForSelector('#modalDesfazerRen.show', { timeout: 5000 });
  await p.selectOption('#motivoDesfazerRen', String(motivo));
  await p.fill('#observacaoDesfazerRen', observacao);
  await enviar(p, '#formDesfazerRen');
  return recusaDaTela(p);
}

module.exports = { sql, um, montar, cred, entrar, arranjo, novoTitulo, abrirAcao, enviar, receber, desfazerRecebimento, situacao, hoje, diasAntes, ymd,
  renegociar, efetivarProposta, desfazerRenegociacao, noMes, recusaDaTela, listasDisciplinas, filtroAnoOk, colunaNumeroOk };
