/*
 * PROVA DA CORREÇÃO DA ESCOLA, PARTE 1 — a liberação do boletim, por ano letivo (D-100; os achados A4 e A5 do teste pelo
 * navegador de 22/11). APAGA o banco de prova.
 *   1. a demonstração do colégio: as etapas fechadas liberadas; a média final e a situação final do ano corrente ocultas;
 *   2. quem libera: a supervisão de secretaria, a direção acadêmica (só consulta, mas libera) e o administrador; a
 *      secretaria não (nem o menu, nem o endereço); a tela tem uma linha por etapa, a "Média final" e a "Situação final",
 *      com "Exibir no portal", "Liberar a partir de", a situação agora e a última alteração; a ação aparece em Perfis de
 *      acesso;
 *   3. o portal obedece: sem a média final liberada, nem o boletim, nem o resumo do responsável ("Média geral"), nem a
 *      tabela das disciplinas mostram média anual; sem a situação final, nem aprovado nem reprovado; o painel mostra tudo;
 *   4. a supervisão libera a média final pela tela: antes de gravar, a janela do sistema diz em palavras o que muda; o
 *      portal passa a mostrar a média anual e a "Média geral"; a situação final "a partir de" amanhã fica programada (o
 *      portal ainda não mostra) e, passada a hora, aparece; liberar uma etapa ainda aberta em disciplinas avisa quantas;
 *      desfazer pede a confirmação e fica no histórico;
 *   5. a tela "Notas" do portal (em tempo real) mostra a nota da recuperação (A5);
 *   6. a tela das etapas da disciplina mostra o estado em palavras e leva à liberação; o "Ano letivo e etapas" só mostra,
 *      com o atalho;
 *   sabotagem: o portal sem conferir a média final (AdmsCalculoNotas::finalLiberado do portal sempre verdadeiro) — a
 *   prova acusa; o arquivo volta igual.
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const L = require('../lib');
const X = require('./_escola');

const ETAPA = 'onda8/etapa40';
const MODELO = path.join(L.RAIZ, 'portal', 'app', 'adms', 'Models', 'notas', 'AdmsCalculoNotas.php');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const { sql, um } = X;
const postar = (p, url, campos) => p.evaluate(async ([u, c]) => {
  const x = await fetch(u, { method: 'POST', credentials: 'same-origin', body: new URLSearchParams(c) });
  return { st: x.status, t: await x.text() };
}, [L.BASE + 'painel/' + url, campos]);
const celulas = (p, sel) => p.$$eval(sel, (l) => l.map((tr) => Array.from(tr.querySelectorAll('td')).map((td) => td.textContent.replace(/\s+/g, ' ').trim())));

(async () => {
  const { navegador } = await L.abrir();
  const shaModelo = sha(MODELO);
  try {
    L.ok(X.montar('onda8-etapa40').status === 0, 'a demonstração do colégio montada pela tela, numa instalação nova');
    const c = X.cred();
    const ano = new Date().getFullYear();
    const etapas = sql(`SELECT CONCAT(id, '|', nome) FROM esc_etapas WHERE ano = ${ano} ORDER BY ordem`).split('\n').map((l) => { const [id, nome] = l.split('|'); return { id: +id, nome }; });
    const liberadas = um(`SELECT COUNT(*) FROM esc_boletim_liberacao WHERE ano = ${ano} AND item = 'etapa' AND exibir = 1`);
    const fechadasDemo = um(`SELECT COUNT(DISTINCT f.etapa_id) FROM esc_fechamentos f INNER JOIN esc_etapas e ON e.id = f.etapa_id WHERE e.ano = ${ano} AND f.situacao = 'vigente'`);
    const finaisOcultos = um(`SELECT COUNT(*) FROM esc_boletim_liberacao WHERE ano = ${ano} AND item IN ('media_final', 'situacao_final') AND exibir = 1`) === 0;
    L.ok(liberadas > 0 && liberadas <= fechadasDemo && finaisOcultos, `a demonstração: ${liberadas} etapa(s) fechada(s) liberada(s) no portal; a média final e a situação final de ${ano}, ocultas`);

    /* 2. quem libera */
    const sec = await X.entrar(navegador, 'secretaria.demo', c.painel['secretaria.demo'], 'painel', 1366);
    await sec.p.goto(L.BASE + 'painel/escola-liberacao/index');
    const secRecusada = !(await sec.p.$('#formLiberacao'));
    const menuSec = (await sec.p.$$('a[href*="escola-liberacao/index"]')).length;
    const secPost = await postar(sec.p, 'escola-liberacao/salvar', { ano: String(ano), [`item[media_final:0][exibir]`]: '1' });
    const secNada = um(`SELECT COUNT(*) FROM esc_boletim_liberacao WHERE ano = ${ano} AND item = 'media_final' AND exibir = 1`) === 0;
    await sec.ctx.close();
    L.ok(secRecusada && menuSec === 0 && secNada, `a secretaria não libera: nem o menu, nem a tela, nem o endereço de gravar (resposta ${secPost.st})`);
    const dir = await X.entrar(navegador, 'direcao.demo', c.painel['direcao.demo'], 'painel', 1366);
    await dir.p.goto(L.BASE + 'painel/escola-liberacao/index');
    const dirAbre = !!(await dir.p.$('#salvarLiberacao'));
    await dir.ctx.close();
    /* Perfis de acesso é do administrador */
    const adm = await X.entrar(navegador, 'administrador', process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123', 'painel', 1366);
    await adm.p.goto(L.BASE + 'painel/perfis-acesso/index');
    const perfis = (await adm.p.textContent('body').catch(() => '')).replace(/\s+/g, ' ');
    const sup = await X.entrar(navegador, 'supervisao.demo', c.painel['supervisao.demo'], 'painel', 1366);
    const p = sup.p;
    let janelas = 0;
    p.on('dialog', () => { janelas++; });
    await p.goto(L.BASE + 'painel/home/index');
    const menuSup = (await p.$$('a[href*="escola-liberacao/index"]')).length;
    await p.goto(L.BASE + `painel/escola-liberacao/index?ano=${ano}`);
    await L.semErros(p, 'a liberação do boletim');
    const linhas = await p.$$eval('#tabelaLiberacao tr.vc-liberacao', (l) => l.map((tr) => ({ nome: tr.dataset.nome, estado: tr.dataset.estado,
      exibir: !!tr.querySelector('.vc-exibir'), apartir: tr.querySelector('.vc-a-partir')?.type, cols: tr.querySelectorAll('td').length })));
    const cab = await p.$$eval('#tabelaLiberacao thead th', (l) => l.map((t) => t.textContent.trim()));
    L.ok(dirAbre && menuSup > 0 && linhas.length === etapas.length + 2 && linhas[etapas.length].nome === 'Média final' && linhas[etapas.length + 1].nome === 'Situação final'
      && linhas.every((x) => x.exibir && x.apartir === 'datetime-local' && x.cols === 5) && cab.join('|') === 'Item|Exibir no portal|Liberar a partir de|Situação agora|Última alteração'
      && (await p.$$('#formLiberacao button[type="submit"]')).length === 1 && /Liberar o boletim no portal/.test(perfis),
      `a tela: ${linhas.map((x) => x.nome + ' (' + x.estado + ')').join(', ')}; um botão Salvar; a supervisão no menu e a direção acadêmica também liberam; a ação aparece em Perfis de acesso`);
    await L.print(p, ETAPA, 'liberacao');

    /* 3. o portal obedece */
    const fams = Object.keys(c.familia || {});
    const fam = fams.find((f) => um(`SELECT COUNT(*) FROM esc_responsavel_alunos ra INNER JOIN esc_responsaveis r ON r.id = ra.responsavel_id WHERE r.email = '${f}'`) > 0) || fams[0];
    const aluno = um(`SELECT ra.aluno_id FROM esc_responsavel_alunos ra INNER JOIN esc_responsaveis r ON r.id = ra.responsavel_id INNER JOIN col_usuarios u ON u.id = ra.aluno_id
      INNER JOIN portal_disciplinas_alunos d ON d.rm = u.rm AND d.ano = ${ano} WHERE r.email = '${fam}' ORDER BY ra.aluno_id LIMIT 1`);
    const rm = sql(`SELECT rm FROM col_usuarios WHERE id = ${aluno}`);
    const fa = await X.entrar(navegador, fam, c.familia[fam], 'portal', 1366);
    const portal = async (rotulo) => {
      await fa.p.goto(L.BASE + `portal/familia/boletim/${aluno}?ano=${ano}`);
      await L.semErros(fa.p, `o boletim no portal da família (${rotulo})`);
      const bol = await celulas(fa.p, '.tabela-boletim-escola tr.linhaBoletim');
      await fa.p.goto(L.BASE + `portal/familia/filho/${aluno}?ano=${ano}`);
      const geral = (await fa.p.textContent('#mediaGeral')).trim();
      const disc = await celulas(fa.p, '#componentesTurma tbody tr');
      const legenda = (await fa.p.textContent('#legendaMedia')).trim();
      return { bol, geral, disc, legenda };
    };
    const n = etapas.length;
    const a1 = await portal('média oculta');
    const semMedia = a1.bol.length > 0 && a1.bol.every((l) => l[n + 1] === '—' && l[n + 4] === 'Em curso') && a1.geral === '—' && a1.disc.every((l) => l[2] === '—') && /quando a escola liberar/.test(a1.legenda);
    L.ok(semMedia, `sem a média final liberada, nenhuma média anual no portal: o boletim (média ${a1.bol[0] ? a1.bol[0][n + 1] : '?'}, situação ${a1.bol[0] ? a1.bol[0][n + 4] : '?'}), o resumo ("Média geral" ${a1.geral}, "${a1.legenda}") e a tabela das disciplinas`);
    await L.print(fa.p, ETAPA, 'resumo-sem-media');
    const painelMostra = async (pp) => {
      await pp.goto(L.BASE + `painel/academicos/boletim/${rm}`);
      return celulas(pp, '.tabela-boletim-escola tr.linhaBoletim');
    };
    const bp = await painelMostra(p);
    L.ok(bp.length > 0 && bp.some((l) => l[n + 1] !== '—'), `o painel mostra tudo o que está fechado: a média anual no boletim do painel (${bp[0] ? bp[0][n + 1] : '?'})`);
    await adm.ctx.close();

    /* 4. a supervisão libera a média final pela tela; a janela diz o que muda */
    await p.goto(L.BASE + `painel/escola-liberacao/index?ano=${ano}`);
    await p.check('#exibir-media_final-0');
    await p.click('#salvarLiberacao');
    await p.waitForSelector('#vcConfirmar.show', { timeout: 8000 });
    const diz = (await p.textContent('#mudancasLiberacao')).replace(/\s+/g, ' ').trim();
    await Promise.all([p.waitForNavigation(), p.click('#vcConfirmarSim')]);
    const a2 = await portal('média liberada');
    const comMedia = a2.bol.every((l) => l[n + 1] !== '—') && a2.geral !== '—' && a2.bol.every((l) => l[n + 4] === 'Em curso');
    L.ok(/passam a ver a média anual/.test(diz) && comMedia && janelas === 0,
      `liberada a média final pela tela — a janela do sistema dizia "${diz}"; o portal mostra a média anual (${a2.bol[0][n + 1]}) e a "Média geral" (${a2.geral}); a situação continua "Em curso"`);
    /* a situação final "a partir de" amanhã, no ano anterior (encerrado: a situação existe; no corrente, é "Em curso" de
       qualquer jeito): programada, o portal mostra "Em curso"; passada a hora, mostra a situação */
    const ra25 = Object.keys(c.portal || {}).find((k) => /^255/.test(k));
    const al25 = await X.entrar(navegador, ra25, c.portal[ra25], 'portal', 1366);
    const sit25 = async () => {
      await al25.p.goto(L.BASE + 'portal/home/boletim-aluno?filtro_ano=' + (ano - 1));
      return (await celulas(al25.p, '.tabela-boletim-escola tr.linhaBoletim')).map((l) => l[l.length - 1]);
    };
    const s0 = await sit25();
    const amanha = new Date(Date.now() + 86400000);
    await p.goto(L.BASE + `painel/escola-liberacao/index?ano=${ano - 1}`);
    await p.check('#exibir-situacao_final-0');
    await p.fill('#apartir-situacao_final-0', `${X.ymd(amanha)}T07:30`);
    await p.click('#salvarLiberacao');
    await p.waitForSelector('#vcConfirmar.show', { timeout: 8000 });
    const diz2 = (await p.textContent('#mudancasLiberacao')).replace(/\s+/g, ' ').trim();
    await Promise.all([p.waitForNavigation(), p.click('#vcConfirmarSim')]);
    const estadoSit = await p.$eval('tr.vc-liberacao[data-chave="situacao_final:0"]', (tr) => tr.dataset.estado);
    const s1 = await sit25();
    sql(`UPDATE esc_boletim_liberacao SET a_partir = NOW() - INTERVAL 1 MINUTE WHERE ano = ${ano - 1} AND item = 'situacao_final'`);
    const s2 = await sit25();
    await al25.ctx.close();
    L.ok(s0.length > 0 && s0.every((x) => x !== 'Em curso') && /em \d\d\/\d\d\/\d{4} às 07:30/.test(diz2) && estadoSit === 'programado' && s1.every((x) => x === 'Em curso') && JSON.stringify(s2) === JSON.stringify(s0),
      `a situação final de ${ano - 1} "a partir de" amanhã às 07:30: a janela dizia "${diz2.slice(0, 110)}"; programada, o portal do aluno mostra "Em curso" (era ${s0[0]}); passada a hora, mostra de novo (${s2[0]})`);
    await L.print(fa.p, ETAPA, 'boletim-portal-liberado');
    /* uma etapa ainda aberta em disciplinas: a tela avisa quantas */
    const aberta = etapas.find((e) => um(`SELECT COUNT(*) FROM esc_boletim_liberacao WHERE etapa_id = ${e.id} AND exibir = 1`) === 0);
    let avisoAberta = '';
    let quantas = 0;
    if (aberta) {
      await p.goto(L.BASE + `painel/escola-liberacao/index?ano=${ano}`);
      quantas = await p.$eval(`tr.vc-liberacao[data-chave="etapa:${aberta.id}"]`, (tr) => +tr.dataset.abertas);
      await p.check(`#exibir-etapa-${aberta.id}`);
      await p.click('#salvarLiberacao');
      await p.waitForSelector('#vcConfirmar.show', { timeout: 8000 });
      avisoAberta = (await p.textContent('#mudancasLiberacao')).replace(/\s+/g, ' ').trim();
      await p.click('#vcConfirmarNao');
      await p.waitForTimeout(400);
    }
    const naoGravou = !aberta || um(`SELECT COUNT(*) FROM esc_boletim_liberacao WHERE etapa_id = ${aberta.id} AND exibir = 1`) === 0;
    L.ok(!aberta || (quantas > 0 && new RegExp(`Atenção: ${quantas} disciplina`).test(avisoAberta) && naoGravou),
      `liberar uma etapa ainda aberta avisa quantas disciplinas estão com ela aberta ("${avisoAberta.slice(-110)}"); "Voltar" não grava nada`);
    /* desfazer a liberação da média final: a confirmação e o histórico */
    await p.goto(L.BASE + `painel/escola-liberacao/index?ano=${ano}`);
    await p.uncheck('#exibir-media_final-0');
    await p.click('#salvarLiberacao');
    await p.waitForSelector('#vcConfirmar.show', { timeout: 8000 });
    const diz3 = (await p.textContent('#mudancasLiberacao')).replace(/\s+/g, ' ').trim();
    await Promise.all([p.waitForNavigation(), p.click('#vcConfirmarSim')]);
    const hist = um(`SELECT COUNT(*) FROM esc_boletim_liberacao_hist WHERE ano = ${ano} AND item = 'media_final' AND exibir_antes = 1 AND exibir = 0`);
    const ultima = (await p.textContent('tr.vc-liberacao[data-chave="media_final:0"] .vc-ultima')).trim();
    const a5 = await portal('média desfeita');
    L.ok(/deixam de ver a média anual/.test(diz3) && hist === 1 && /Supervis|supervis|\d\d\/\d\d\/\d{4}/.test(ultima) && a5.geral === '—' && (await p.$('#historicoLiberacao')) !== null,
      `desfazer a liberação pede a confirmação ("${diz3}") e fica no histórico (a última alteração: ${ultima}); o portal volta a esconder a média`);
    await L.print(p, ETAPA, 'liberacao-historico');

    /* 5. A5: a recuperação na tela "Notas" do portal */
    const ofRec = um(`SELECT d.rm_prof_mat_id FROM portal_disciplinas_alunos d INNER JOIN esc_turma_ofertas x ON x.oferta_id = d.rm_prof_mat_id WHERE d.rm = '${rm}' AND d.ano = ${ano} ORDER BY d.rm_prof_mat_id LIMIT 1`);
    const etRec = etapas[0];
    sql(`INSERT INTO esc_recuperacoes (oferta_id, etapa_id, rm, nota, por, em) VALUES (${ofRec}, ${etRec.id}, '${rm}', 7.00, 1, NOW()) ON DUPLICATE KEY UPDATE nota = 7.00`);
    await fa.p.goto(L.BASE + `portal/familia/notas/${aluno}?ano=${ano}`);
    await L.semErros(fa.p, 'as notas das atividades (a família)');
    const notas = (await fa.p.textContent('main, .page-wrapper, body')).replace(/\s+/g, ' ');
    L.ok(notas.includes(`Recuperação (${etRec.nome})`) && /Recuperação \([^)]*\)\s*7,00?/.test(notas), `A5: a tela "Notas" do portal mostra a nota da recuperação ("Recuperação (${etRec.nome})" 7,0), em tempo real`);
    await L.print(fa.p, ETAPA, 'notas-com-recuperacao');
    const alu = c.portal && c.portal[rm] ? await X.entrar(navegador, rm, c.portal[rm], 'portal', 1366) : null;
    if (alu) {
      await alu.p.goto(L.BASE + 'portal/home/notas-atividades');
      const nA = (await alu.p.textContent('body')).replace(/\s+/g, ' ');
      L.ok(nA.includes(`Recuperação (${etRec.nome})`), 'A5: no portal do aluno, a mesma nota da recuperação');
      await alu.ctx.close();
    }

    /* 6. a tela das etapas e o "Ano letivo e etapas" */
    await p.goto(L.BASE + `painel/escola-notas/etapas/${ofRec}`);
    const estados = await p.$$eval('#abasEtapas .vc-estado-etapa', (l) => l.map((s) => s.textContent.trim()));
    const leva = !!(await p.$('#irLiberacao'));
    await p.goto(L.BASE + 'painel/escola-anos/index');
    const soMostra = (await p.$$('.vc-liberado button')).length === 0 && !!(await p.$('.vc-ir-liberacao'));
    L.ok(estados.length === n && estados.every((s) => ['aberta', 'fechada', 'liberada para a família'].includes(s)) && estados.includes('liberada para a família') && leva && soMostra,
      `a tela das etapas diz o estado de cada etapa (${estados.join(', ')}) e leva à liberação; o "Ano letivo e etapas" só mostra, com o atalho`);
    await L.print(p, ETAPA, 'etapas-estados');

    /* sabotagem: o portal sem conferir a média final */
    const orig = fs.readFileSync(MODELO, 'utf8');
    const trecho = '        $k = $ano . \':\' . $item;\n';
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let sab = null;
    try {
      fs.writeFileSync(MODELO, orig.replace(trecho, trecho + '        return true;\n'));
      sab = await portal('sabotagem');
    } finally {
      fs.writeFileSync(MODELO, orig);
    }
    L.ok(sab && sab.geral !== '—', `sabotagem (o portal sem conferir a média final): a prova reprova — a "Média geral" apareceu (${sab ? sab.geral : '?'}) sem a liberação`);
    L.ok(sha(MODELO) === shaModelo, 'o arquivo sabotado voltou igual (sha256)');
    await fa.ctx.close();
    await sup.ctx.close();
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(MODELO) !== shaModelo) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
