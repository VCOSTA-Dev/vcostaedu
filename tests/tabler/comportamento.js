/*
 * A PROVA DO COMPORTAMENTO das telas convertidas: o que a tela fazia continua fazendo.
 *
 *   node tests/tabler/comportamento.js <cenarios.json> <etapa>
 *
 * Tipos de cenário (todos com "rota" e "perfil"):
 *   lista       a lista (DataTables) pesquisa, ordena, pagina e exporta — o Excel, o CSV e o PDF são BAIXADOS e
 *               abertos (o arquivo tem de ter o texto esperado); campos: tabela, busca, esperaTexto, coluna (índice
 *               do cabeçalho para ordenar)
 *   form        o formulário grava e a mensagem de retorno aparece e FECHA no ×; campos: preencher {seletor: valor},
 *               enviar (seletor do botão), mensagem (texto esperado na mensagem)
 *   modal       o modal abre pelo botão, preenchido; campos: botao, modal, contem (texto que o modal tem de mostrar)
 *   desabilitada  a ação desabilitada mostra a razão numa dica de verdade (D-11); campo: seletor
 *   nav         só abre a rota e confere um texto; campo: contem
 * Credenciais da demonstração em tests/tabler/saida/credenciais.json.
 */
const fs = require('fs');
const path = require('path');
const os = require('os');
const L = require('./lib');
const { execFileSync } = require('child_process');
/* Onda 8, correção: o passo { banco } confere o valor gravado no banco de prova (só o de prova: termina em _teste) */
const BANCO = process.env.VCOSTAEDU_BANCO_TESTE || '';
const sqlProva = (q) => {
  if (!/_teste$/.test(BANCO)) { throw new Error('o passo "banco" só roda no banco de prova (VCOSTAEDU_BANCO_TESTE)'); }
  return execFileSync('C:/xampp/mysql/bin/mysql.exe', ['-uroot', '--default-character-set=utf8mb4', '-N', '-B', BANCO, '-e', q], { encoding: 'utf8' }).trim();
};

const [arq, etapa] = process.argv.slice(2);
const cenarios = JSON.parse(fs.readFileSync(arq, 'utf8'));
const cred = JSON.parse(fs.readFileSync(path.join(__dirname, 'saida', 'credenciais.json'), 'utf8'));

async function entrar(ctx, app, perfil) {
  const p = await L.pagina(ctx);
  await p.goto(L.BASE + app + '/login/acesso');
  await p.fill('input[name="usuario"]', perfil);
  await p.fill('input[name="senha"]', app === 'portal' ? cred.portal[perfil] : cred.painel[perfil]);
  await Promise.all([p.waitForNavigation(), p.click('[name="SendLogin"]')]);
  return p;
}

async function baixar(p, seletor) {
  const [d] = await Promise.all([p.waitForEvent('download', { timeout: 20000 }), p.click(seletor)]);
  const destino = path.join(os.tmpdir(), 'vc-' + Date.now() + '-' + d.suggestedFilename());
  await d.saveAs(destino);
  const bytes = fs.readFileSync(destino);
  fs.unlinkSync(destino);
  return { nome: d.suggestedFilename(), bytes };
}

(async () => {
  const { navegador } = await L.abrir({ contexto: { acceptDownloads: true } });
  const contextos = {};
  try {
    for (const c of cenarios) {
      const app = c.rota.startsWith('portal/') ? 'portal' : 'painel';
      const chave = c.perfil + '@' + app;
      if (!contextos[chave]) {
        contextos[chave] = await navegador.newContext({ viewport: { width: 1366, height: 900 }, acceptDownloads: true });
        (await entrar(contextos[chave], app, c.perfil)).close();
      }
      const p = await L.pagina(contextos[chave]);
      await p.goto(L.BASE + c.rota);
      await p.waitForLoadState('networkidle').catch(() => {});
      const onde = `${c.nome}`;

      if (c.tipo === 'lista') {
        const t = c.tabela || 'table.lista-dados';
        await p.waitForSelector(t + '_wrapper, ' + t.replace(/^table/, '') + ' tbody tr', { timeout: 10000 }).catch(() => {});
        const linhas = async () => p.$$eval(t + ' tbody tr', (trs) => trs.filter((tr) => !tr.querySelector('td.dataTables_empty')).length);
        const total = await linhas();
        const busca = await p.$('.dataTables_filter input');
        L.ok(!!busca, `${onde}: a busca existe`);
        if (busca && c.busca) {
          await busca.fill(c.busca);
          await p.waitForTimeout(400);
          const achou = await p.$eval(t + ' tbody', (tb) => tb.innerText);
          L.ok((await linhas()) <= total && achou.includes(c.esperaTexto || c.busca), `${onde}: pesquisa "${c.busca}" filtra (${total} → ${await linhas()})`);
          await busca.fill('');
          await p.waitForTimeout(300);
        }
        const th = await p.$(t + ` thead th:nth-child(${(c.coluna || 1) + 1})`);
        if (th) {
          const antes = await p.$eval(t + ' tbody', (tb) => tb.innerText);
          await th.click();
          await p.waitForTimeout(300);
          await th.click();
          await p.waitForTimeout(300);
          const depois = await p.$eval(t + ' tbody', (tb) => tb.innerText);
          L.ok(total < 2 || antes !== depois || (await th.getAttribute('class')).includes('sorting_'), `${onde}: ordena pela coluna`);
        }
        L.ok(!!(await p.$('.dataTables_paginate')) || c.semPaginacao, `${onde}: tem paginação`);
        const csv = await baixar(p, '.dt-buttons .buttons-csv');
        L.ok(csv.bytes.length > 20 && csv.bytes.toString('utf8').includes(c.esperaTexto || ''), `${onde}: CSV baixado e aberto (${csv.nome}, ${csv.bytes.length} bytes, com "${c.esperaTexto || ''}")`);
        const xls = await baixar(p, '.dt-buttons .buttons-excel');
        L.ok(xls.bytes.length > 1000 && xls.bytes.slice(0, 2).toString() === 'PK', `${onde}: Excel baixado e aberto (${xls.nome}, xlsx válido, ${xls.bytes.length} bytes)`);
        const pdf = await baixar(p, '.dt-buttons .buttons-pdf');
        L.ok(pdf.bytes.length > 500 && pdf.bytes.slice(0, 4).toString() === '%PDF', `${onde}: PDF baixado e aberto (${pdf.nome}, ${pdf.bytes.length} bytes)`);
        const [janela] = await Promise.all([p.context().waitForEvent('page', { timeout: 10000 }).catch(() => null), p.click('.dt-buttons .buttons-print')]);
        L.ok(!!janela, `${onde}: Imprimir abre a versão de impressão`);
        if (janela) { await janela.close().catch(() => {}); }
      } else if (c.tipo === 'form') {
        for (const [sel, val] of Object.entries(c.preencher || {})) {
          const el = await p.$(sel);
          const tag = el ? await el.evaluate((e) => e.tagName) : '';
          if (tag === 'SELECT') { await p.selectOption(sel, val); } else { await p.fill(sel, val); }
        }
        await Promise.all([p.waitForNavigation({ timeout: 20000 }).catch(() => {}), p.click(c.enviar)]);
        await p.waitForLoadState('networkidle').catch(() => {});
        const msg = await p.$('.alert');
        const texto = msg ? await msg.innerText() : '';
        L.ok(!!msg && texto.includes(c.mensagem), `${onde}: gravou e a mensagem aparece ("${texto.replace(/\s+/g, ' ').trim().slice(0, 80)}")`);
        const fechar = msg ? await msg.$('.btn-close') : null;
        /* Onda 2, Etapa 1: toda mensagem de sucesso tem o × (antes, algumas não tinham, como na origem). */
        if (fechar) {
          await fechar.click();
          await p.waitForTimeout(500);
          L.ok(!(await msg.isVisible().catch(() => false)), `${onde}: a mensagem fecha no ×`);
        } else {
          L.ok(false, `${onde}: a mensagem tem o botão de fechar`);
        }
        await L.semErros(p, onde);
      } else if (c.tipo === 'modal') {
        await p.click(c.botao);
        await p.waitForSelector(c.modal + '.show', { timeout: 5000 }).catch(() => {});
        const visivel = await p.isVisible(c.modal);
        L.ok(visivel, `${onde}: o modal abre`);
        if (visivel && c.contem) {
          const conteudo = await p.$eval(c.modal, (m) => m.innerText + ' ' + [...m.querySelectorAll('input')].map((i) => i.value).join(' '));
          L.ok(conteudo.toLowerCase().includes(c.contem.toLowerCase()), `${onde}: o modal abre preenchido ("${c.contem}")`);
        }
        await L.print(p, path.join(etapa, 'depois'), c.nome);
        await L.semErros(p, onde);
      } else if (c.tipo === 'desabilitada') {
        const el = await p.$(c.seletor);
        L.ok(!!el, `${onde}: há ação desabilitada`);
        if (el) {
          await el.hover();
          await p.waitForSelector('.tooltip.show', { timeout: 3000 }).catch(() => {});
          const dica = await p.$('.tooltip.show');
          L.ok(!!dica && (await dica.innerText()).trim().length > 3, `${onde}: a razão aparece numa dica ("${dica ? (await dica.innerText()).trim().slice(0, 70) : ''}")`);
        }
      } else if (c.tipo === 'editor') {
        /* o editor padrão (Summernote bs5): a barra monta inteira, o menu Estilo abre, uma variável entra no cursor,
           as abas trocam, e salvar grava com a mensagem */
        await p.waitForSelector('.note-editor', { timeout: 10000 });
        const botoes = await p.$$eval('.note-toolbar .note-btn', (bs) => bs.length);
        L.ok(botoes >= 8, `${onde}: a barra do editor montou (${botoes} botões)`);
        await p.click('.note-toolbar .note-btn.dropdown-toggle');
        await p.waitForTimeout(300);
        L.ok(await p.$eval('.note-toolbar', (t) => !!t.querySelector('.dropdown-menu.show, .note-dropdown-menu.show')), `${onde}: o menu Estilo abre (Bootstrap 5)`);
        await p.keyboard.press('Escape');
        await p.click('.note-editable');
        await p.keyboard.press('End');
        const antes = await p.$eval('.note-editable', (e) => e.innerText.length);
        await p.click(c.variavel || '.btn-var');
        await p.waitForTimeout(300);
        const depois = await p.$eval('.note-editable', (e) => e.innerText.length);
        L.ok(depois > antes, `${onde}: a variável (ou o bloco) entra no ponto do cursor (${antes} → ${depois} caracteres)`);
        const abas = await p.$$('[data-bs-toggle="tab"]');
        if (abas.length > 1) {
          const alvo = await abas[1].getAttribute('href') || await abas[1].getAttribute('data-bs-target');
          await abas[1].click();
          await p.waitForTimeout(300);
          L.ok(await p.isVisible(alvo), `${onde}: a aba ${alvo} abre`);
        }
        await Promise.all([p.waitForNavigation({ timeout: 20000 }).catch(() => {}), p.click(c.enviar)]);
        const msg = await p.$('.alert');
        L.ok(!!msg && (await msg.innerText()).includes(c.mensagem || ''), `${onde}: salvar grava e a mensagem aparece ("${msg ? (await msg.innerText()).replace(/\s+/g, ' ').trim().slice(0, 80) : ''}")`);
        await L.semErros(p, onde);
      } else if (c.tipo === 'passos') {
        /* uma sequência de gestos, cada um conferido: { ir }, { clicar }, { clicarConfirmando }, { preencher: {sel: val} },
           { escolher: [sel, rótulo] }, { arrastar: [de, para] }, { tecla }, { aguardar: ms },
           { enviar, mensagem }, { espera: texto }, { esperaSel }, { confere: [sel, regex] }, { url: regex }, { guardar: [sel, nome] } (depois "{nome}" no ir), { habilitado },
           { guardarAttr: [sel, atributo, nome] }, { banco: [sql com "{nome}" (só dígitos), regex] }, { confereVisivel: [sel, regex] } (Onda 8: visível na tela E com o texto ou o valor) */
        const guardados = {};
        for (const passo of c.passos) {
          const [k] = Object.keys(passo);
          const v = passo[k];
          if (k === 'ir') { await p.goto(L.BASE + v.replace(/\{(\w+)\}/g, (_, n) => encodeURIComponent(guardados[n] || ''))); await p.waitForLoadState('networkidle').catch(() => {}); }
          else if (k === 'guardar') { guardados[v[1]] = ((await p.$eval(v[0], (e) => e.innerText || e.value || '').catch(() => '')) || '').trim(); L.ok(guardados[v[1]] !== '', `${onde}: leu ${v[1]} da tela ("${guardados[v[1]]}")`); }
          else if (k === 'guardarAttr') { guardados[v[2]] = ((await p.getAttribute(v[0], v[1]).catch(() => '')) || '').trim(); L.ok(guardados[v[2]] !== '', `${onde}: leu ${v[2]} da tela ("${guardados[v[2]]}")`); }
          else if (k === 'banco') {
            const q = v[0].replace(/\{(\w+)\}/g, (_, n) => String(guardados[n] || '').replace(/\D/g, '') || '0');
            const t = sqlProva(q);
            L.ok(new RegExp(v[1]).test(t), `${onde}: ${passo.rotulo || 'no banco'} ("${t.slice(0, 60)}")`);
          }
          else if (k === 'confereVisivel') {
            const vis = await p.isVisible(v[0]).catch(() => false);
            const t = vis ? await p.$eval(v[0], (e) => (e.matches('input, select, textarea') ? e.value : e.innerText) || '') : '';
            L.ok(vis && new RegExp(v[1]).test(t), `${onde}: ${passo.rotulo || v[0]} visível com "${v[1]}" (${vis ? 'visível' : 'NÃO visível'}: "${t.trim().slice(0, 60)}")`);
          }
          else if (k === 'clicar') { await p.click(v); await p.waitForTimeout(300); }
          else if (k === 'clicarConfirmando') {
            p.once('dialog', (d) => d.accept());
            await L.confirmarSozinho(p); // D-100: a confirmação é a janela do sistema
            await Promise.all([p.waitForNavigation({ timeout: 15000 }).catch(() => {}), p.click(v)]);
          }
          else if (k === 'preencher') { for (const [s, val] of Object.entries(v)) { await p.fill(s, val); } }
          else if (k === 'preencherTodos') { for (const el of await p.$$(v[0])) { await el.fill(v[1]); } }
          else if (k === 'escolher') { await p.selectOption(v[0], { label: v[1] }); }
          else if (k === 'tecla') { await p.keyboard.press(v); await p.waitForTimeout(800); }
          else if (k === 'aguardar') { await p.waitForTimeout(v); }
          else if (k === 'arrastar') {
            const a = await p.$(v[0]); const b = await p.$(v[1]);
            const ba = await a.boundingBox(); const bb = await b.boundingBox();
            await p.mouse.move(ba.x + ba.width / 2, ba.y + ba.height / 2);
            await p.mouse.down();
            await p.mouse.move(ba.x + ba.width / 2, ba.y + ba.height / 2 + 8, { steps: 4 });
            await p.mouse.move(bb.x + bb.width / 2, bb.y + bb.height + 6, { steps: 12 });
            await p.mouse.up();
            await p.waitForTimeout(400);
          }
          else if (k === 'enviar') {
            await Promise.all([p.waitForNavigation({ timeout: 20000 }).catch(() => {}), p.click(v)]);
            await p.waitForLoadState('networkidle').catch(() => {});
            const msg = await p.$('.alert');
            const t = msg ? (await msg.innerText()).replace(/\s+/g, ' ').trim() : '';
            L.ok(!!msg && t.includes(passo.mensagem || ''), `${onde}: ${passo.rotulo || 'enviar'} grava e a mensagem aparece ("${t.slice(0, 90)}")`);
          }
          else if (k === 'espera') { L.ok((await p.content()).includes(v), `${onde}: ${passo.rotulo || 'mostra'} "${v}"`); }
          else if (k === 'esperaSel') { L.ok(await p.isVisible(v), `${onde}: ${passo.rotulo || 'aparece'} ${v}`); }
          else if (k === 'confere') { const t = await p.$eval(v[0], (e) => e.innerText || e.value || ''); L.ok(new RegExp(v[1]).test(t), `${onde}: ${passo.rotulo || v[0]} mostra "${v[1]}" ("${t.trim().slice(0, 60)}")`); }
          else if (k === 'url') { L.ok(new RegExp(v).test(decodeURIComponent(p.url())), `${onde}: ${passo.rotulo || 'endereço'} (${decodeURIComponent(p.url()).replace(L.BASE, '')})`); }
          else if (k === 'habilitado') { L.ok(await p.isEnabled(v), `${onde}: ${passo.rotulo || 'habilitado'} ${v}`); }
        }
        await L.print(p, path.join(etapa, 'depois'), c.nome);
        await L.semErros(p, onde, c.ignorarConsole ? new RegExp(c.ignorarConsole) : null);
      } else if (c.tipo === 'nav') {
        L.ok((await p.content()).includes(c.contem), `${onde}: mostra "${c.contem}"`);
        await L.semErros(p, onde);
      }
      await p.close();
    }
  } catch (e) {
    L.ok(false, 'erro na prova: ' + e.message.split('\n')[0]);
  } finally {
    await navegador.close();
    process.exit(L.resumo(`comportamento ${etapa}`) ? 0 : 1);
  }
})();
