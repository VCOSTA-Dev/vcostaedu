/*
 * PROVA DO HISTÓRICO ESCOLAR, PARTE E — a prévia, a emissão, as vias e o cancelamento (D-114). APAGA o banco de prova.
 *   0. o cenário (os cadastros pela tela; o aluno A de prova por script);
 *   1. com avisos, emitir sem confirmar é recusado (nada gravado); confirmando, a 1ª via: a emissão guardada de sempre
 *      com o tipo próprio da escola (ESC_EF), o código, o conteúdo (o histórico inteiro) e o sha256 do PDF; o PDF
 *      entregue confere com o sha256 e tem duas páginas;
 *   2. a 2ª via (a reimpressão): o mesmo código, o arquivo novo com o hash dele; também pela lista de documentos emitidos;
 *   3. a conferência pública acha o histórico (o nome do documento, o aluno) e confere o PDF enviado (o original e a via);
 *   4. cancelar sem motivo é recusado; com o motivo, cancelado (a cópia fica guardada; a verificação diz "cancelado");
 *      a 2ª via do cancelado é recusada;
 *   5. a direção emite e cancela (a decisão 4); o professor não abre a prévia; o ensino superior continua fechado na
 *      escola (o histórico FINAL pedido pela tela de sempre é recusado, nenhuma linha nova);
 *   6. o arquivo guardado alterado não é entregue (o sha256 não confere);
 *   sabotagem: a entrega sem conferir o sha256 — a prova reprova; o arquivo volta igual.
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const L = require('../lib');
const X = require('../onda8/_escola');
const K = require('./_cenario');

const ETAPA = 'historico/parteE';
const EMI = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Models', 'historicoEscola', 'AdmsHistEmissao.php');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const shaB = (b) => crypto.createHash('sha256').update(b).digest('hex');
const { sql, um } = X;
const texto = async (p, sel) => ((await p.textContent(sel).catch(() => '')) || '').replace(/\s+/g, ' ').trim();
const paginas = (b) => (b.toString('latin1').match(/\/Type\s*\/Page(?![a-zA-Z])/g) || []).length;
const postar = (p, url, campos) => p.evaluate(async ([u, c]) => {
  const x = await fetch(u, { method: 'POST', credentials: 'same-origin', body: new URLSearchParams(c) });
  return { st: x.status, t: await x.text() };
}, [L.BASE + 'painel/' + url, campos]);
const pasta = () => path.join(L.RAIZ, 'storage', 'historico', 'emissoes');

(async () => {
  const { navegador } = await L.abrir();
  const shaE = sha(EMI);
  try {
    L.ok(X.montar('historico-parteE').status === 0, 'a demonstração do colégio montada pela tela');
    K.prova('sem-historico-demo'); // D-115: o cenário da prova parte do histórico vazio
    const c = X.cred();
    const sec = await X.entrar(navegador, 'secretaria.demo', c.painel['secretaria.demo']);
    const p = sec.p;
    const origem = await K.cadastros(p);
    const A = K.alunoA(origem);
    L.ok(A > 0, 'o cenário: os cadastros pela tela; o aluno de prova por script');

    /* 1. emitir */
    await p.goto(L.BASE + `painel/historico-escola-emissao/previa/${A}?nivel=EF`);
    const avisos = await texto(p, '#avisos');
    await L.print(p, ETAPA, 'previa');
    const n0 = um('SELECT COUNT(*) FROM rm_historico_emissoes');
    await p.fill('#ePub', 'PUB-0001');
    await Promise.all([p.waitForNavigation(), p.click('#atualizarPrevia')]);
    await Promise.all([p.waitForNavigation(), p.click('#emitirHistorico')]);
    const semConfirmar = await texto(p, '#emissaoRecusada');
    L.ok(/aviso/.test(avisos) && /confirme que os leu/.test(semConfirmar) && um('SELECT COUNT(*) FROM rm_historico_emissoes') === n0,
      `com avisos, emitir sem confirmar é recusado e nada é gravado ("${semConfirmar.slice(0, 80)}")`);
    await p.check('#confirmoAvisos');
    await Promise.all([p.waitForNavigation(), p.click('#emitirHistorico')]);
    const ok1 = await texto(p, '#historicoEmitido');
    const e1 = sql("SELECT CONCAT(id, '|', codigo, '|', tipo, '|', hash_pdf, '|', pdf_arquivo, '|', situacao) FROM rm_historico_emissoes ORDER BY id DESC LIMIT 1").split('|');
    const [id1, cod1, tipo1, hash1, arq1] = e1;
    const snap = JSON.parse(sql(`SELECT snapshot FROM rm_historico_emissoes WHERE id = ${id1}`));
    const r1 = await p.request.get(L.BASE + `painel/historico-escola-emissao/pdf/${id1}`);
    const b1 = await r1.body();
    fs.mkdirSync(path.join(L.PROVAS, ETAPA), { recursive: true });
    fs.writeFileSync(path.join(L.PROVAS, ETAPA, 'emitido-1a-via.pdf'), b1);
    L.ok(/emitido/.test(ok1) && tipo1 === 'ESC_EF' && /^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/.test(cod1) && shaB(b1) === hash1 && paginas(b1) === 2
      && snap.historico.fecho.tipo === 'certificado' && snap.historico.publicacao === 'PUB-0001' && snap.historico.aluno.ra === '' && snap.emissao.via === 1 && snap.emissao.avisos.length > 0,
      `a 1ª via: a emissão guardada (${tipo1}, código ${cod1}), o conteúdo com o histórico inteiro (o fecho, a publicação, os avisos aceitos), o PDF de duas páginas que confere com o sha256`);

    /* 2. a 2ª via */
    await Promise.all([p.waitForNavigation(), p.click(`#tabelaEmissoes tr[data-emissao="${id1}"] .vc-segunda-via`)]);
    const via2 = await texto(p, '#viaGerada');
    const v2 = sql(`SELECT CONCAT(via, '|', hash_pdf) FROM rm_historico_emissao_vias WHERE emissao_id = ${id1}`).split('|');
    const r2 = await p.request.get(L.BASE + `painel/historico-escola-emissao/pdf/${id1}?via=2`);
    const b2 = await r2.body();
    L.ok(/2ª via gerada/.test(via2) && v2[0] === '2' && shaB(b2) === v2[1] && shaB(b2) !== hash1 && paginas(b2) === 2,
      'a 2ª via: o mesmo documento e o mesmo código, o arquivo novo com o hash dele');
    await p.goto(L.BASE + `painel/historico-emissoes/ver/${id1}`);
    const naLista = await texto(p, 'body');
    const viaVelha = await postar(p, `historico-emissoes/segunda-via/${id1}`, { SegundaVia: '1' });
    const v3 = um(`SELECT COUNT(*) FROM rm_historico_emissao_vias WHERE emissao_id = ${id1} AND via = 3`);
    L.ok(/Histórico escolar — Ensino Fundamental/.test(naLista) && v3 === 1, `a lista de documentos emitidos mostra o histórico da escola, e a 2ª via de lá é a do histórico escolar (via 3; ${viaVelha.st})`);

    /* 3. a conferência pública */
    const pub = await navegador.newContext();
    const pp = await pub.newPage();
    await pp.goto(L.BASE + 'verificar/' + cod1);
    const ver = { estado: await pp.getAttribute('#resultadoVerificacao', 'data-estado'), tipo: await texto(pp, '#tipoDocumento'), aluno: await texto(pp, '#alunoVerificado') };
    const enviar = async (buf) => {
      const r = await pp.request.post(L.BASE + 'painel/verificar/codigo/' + cod1, { multipart: { pdf: { name: 'h.pdf', mimeType: 'application/pdf', buffer: buf } } });
      return (await r.text()).replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ');
    };
    const conf1 = await enviar(b1);
    const conf2 = await enviar(b2);
    const confX = await enviar(Buffer.concat([b1, Buffer.from('x')]));
    L.ok(ver.estado === 'valido' && ver.tipo === 'Histórico escolar — Ensino Fundamental' && ver.aluno === 'PROVA HIST A CONCLUIU'
      && /idêntico ao documento original/.test(conf1) && /idêntico à 2ª via/.test(conf2) && /NÃO é idêntico/.test(confX),
      `a conferência pública: "${ver.tipo}", ${ver.aluno}, válido; o PDF original e a 2ª via conferem; o alterado não`);
    await L.print(pp, ETAPA, 'verificacao-publica');

    /* 4. cancelar */
    await p.goto(L.BASE + `painel/historico-escola-emissao/previa/${A}?nivel=EF`);
    await Promise.all([p.waitForNavigation(), p.click(`#tabelaEmissoes tr[data-emissao="${id1}"] .vc-cancelar`)]);
    const semMotivo = await texto(p, '#cancelamentoRecusado');
    await p.fill(`#tabelaEmissoes tr[data-emissao="${id1}"] .vc-motivo`, 'Emitido com dado errado (prova)');
    await Promise.all([p.waitForNavigation(), p.click(`#tabelaEmissoes tr[data-emissao="${id1}"] .vc-cancelar`)]);
    const cancelado = await texto(p, '#historicoCancelado');
    const de2 = await postar(p, `historico-escola-emissao/cancelar/${A}`, { emissao: id1, motivo: 'De novo (prova)', nivel: 'EF' });
    const via2c = await postar(p, `historico-escola-emissao/segunda-via/${A}`, { emissao: id1, nivel: 'EF' });
    await pp.goto(L.BASE + 'verificar/' + cod1);
    const estadoCanc = await pp.getAttribute('#resultadoVerificacao', 'data-estado');
    L.ok(/Informe o motivo/.test(semMotivo) && /cancelado/.test(cancelado) && sql(`SELECT CONCAT(situacao, '|', cancelamento_motivo) FROM rm_historico_emissoes WHERE id = ${id1}`) === '2|Emitido com dado errado (prova)'
      && /já está cancelada/.test(de2.t.replace(/\s+/g, ' ')) && /cancelado não tem 2ª via/.test(via2c.t) && estadoCanc === 'cancelado' && fs.existsSync(path.join(pasta(), arq1)),
      'cancelar: sem motivo recusado; com o motivo, cancelado e guardado; de novo, recusado; a 2ª via do cancelado, recusada; a verificação diz "cancelado"');
    await L.print(p, ETAPA, 'vias-cancelada');
    await pub.close();

    /* 5. a direção emite e cancela; o professor não abre; o superior continua fechado */
    await sec.ctx.close();
    const dir = await X.entrar(navegador, 'direcao.demo', c.painel['direcao.demo']);
    await dir.p.goto(L.BASE + `painel/historico-escola-emissao/previa/${A}?nivel=EF`);
    await dir.p.check('#confirmoAvisos');
    await Promise.all([dir.p.waitForNavigation(), dir.p.click('#emitirHistorico')]);
    const idDir = um("SELECT MAX(id) FROM rm_historico_emissoes WHERE tipo = 'ESC_EF'");
    await dir.p.fill(`#tabelaEmissoes tr[data-emissao="${idDir}"] .vc-motivo`, 'Cancelado pela direção (prova)');
    await Promise.all([dir.p.waitForNavigation(), dir.p.click(`#tabelaEmissoes tr[data-emissao="${idDir}"] .vc-cancelar`)]);
    const porDir = sql(`SELECT CONCAT(e.situacao, '|', u.adms_niveis_acesso_id) FROM rm_historico_emissoes e JOIN adms_usuarios u ON u.id = e.emitido_por WHERE e.id = ${idDir}`);
    L.ok(idDir > +id1 && porDir === '2|3', `a direção emite e cancela (a decisão 4): ${porDir}`);
    await dir.ctx.close();
    const prof = await X.entrar(navegador, 'prof.laura.demo', c.painel['prof.laura.demo']);
    const rp = await prof.p.goto(L.BASE + `painel/historico-escola-emissao/previa/${A}`);
    await prof.ctx.close();
    const s2 = await X.entrar(navegador, 'secretaria.demo', c.painel['secretaria.demo']);
    const mc = um(`SELECT MAX(id) FROM rm_matriculas_curso WHERE usuario_id = ${A}`);
    const nSup = um("SELECT COUNT(*) FROM rm_historico_emissoes WHERE tipo IN ('PARCIAL', 'FINAL')");
    await postar(s2.p, `historico-emissoes/emitir/${mc}`, { tipo: 'FINAL', EmitirHistorico: '1', confirmo_alertas: '1' });
    L.ok(rp.status() === 403 && um("SELECT COUNT(*) FROM rm_historico_emissoes WHERE tipo IN ('PARCIAL', 'FINAL')") === nSup,
      `o professor não abre a prévia (${rp.status()}); o histórico do superior continua fechado na escola (o FINAL pedido pela tela de sempre não grava)`);

    /* 6. o arquivo alterado não é entregue */
    await s2.p.goto(L.BASE + `painel/historico-escola-emissao/previa/${A}?nivel=EF`);
    await s2.p.check('#confirmoAvisos');
    await Promise.all([s2.p.waitForNavigation(), s2.p.click('#emitirHistorico')]);
    const [id3, arq3] = sql("SELECT CONCAT(id, '|', pdf_arquivo) FROM rm_historico_emissoes ORDER BY id DESC LIMIT 1").split('|');
    const caminho = path.join(pasta(), arq3);
    const original = fs.readFileSync(caminho);
    let st = 0;
    let stSab = 0;
    try {
      fs.writeFileSync(caminho, Buffer.concat([original, Buffer.from('%alterado')]));
      st = (await s2.p.request.get(L.BASE + `painel/historico-escola-emissao/pdf/${id3}`)).status();
      /* sabotagem: a entrega sem conferir o sha256 */
      const orig = fs.readFileSync(EMI, 'utf8');
      const trecho = "        return hash_equals($esperado, hash('sha256', $bin)) ? $bin : null;";
      if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
      try {
        fs.writeFileSync(EMI, orig.replace(trecho, '        return $bin;'));
        stSab = (await s2.p.request.get(L.BASE + `painel/historico-escola-emissao/pdf/${id3}`)).status();
      } finally {
        fs.writeFileSync(EMI, orig);
      }
    } finally {
      fs.writeFileSync(caminho, original);
    }
    const stDepois = (await s2.p.request.get(L.BASE + `painel/historico-escola-emissao/pdf/${id3}`)).status();
    L.ok(st === 404 && stDepois === 200, `o arquivo guardado alterado não é entregue (${st}); o certo, sim (${stDepois})`);
    L.ok(stSab === 200, `sabotagem (a entrega sem conferir o sha256): a conferência pega — o alterado saiu (${stSab})`);
    L.ok(sha(EMI) === shaE, 'o arquivo sabotado voltou igual (sha256)');
    await s2.ctx.close();
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(EMI) !== shaE) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
