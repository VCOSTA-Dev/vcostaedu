/*
 * PROVA DO HISTÓRICO ESCOLAR, PARTE J — as permissões finas (D-119). APAGA o banco de prova.
 *   1. as ações: emitir (com a 2ª via) e cancelar são ações próprias (131 e 133), as duas na tela "Perfis de acesso";
 *   2. por envio direto (POST) de cada perfil: a secretaria e a direção emitem e cancelam (de fábrica); a supervisão
 *      de secretaria também (o perfil acima da secretaria); a coordenação e o professor não — nada gravado;
 *   3. a ação de cancelar tirada da direção (a linha da página na matriz): a direção continua emitindo, o cancelar dela é
 *      recusado e a tela não mostra o botão;
 *   sabotagem: a tela que decide o botão de cancelar pela permissão de emitir — a prova reprova; o arquivo volta igual.
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const L = require('../lib');
const X = require('../onda8/_escola');

const ETAPA = 'historico/parteJ';
const CTL = path.join(L.RAIZ, 'painel', 'app', 'adms', 'Controllers', 'HistoricoEscolaEmissao.php');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const { sql, um } = X;
const postar = (p, url, campos) => p.evaluate(async ([u, c]) => {
  const x = await fetch(u, { method: 'POST', credentials: 'same-origin', body: new URLSearchParams(c) });
  return { st: x.status, t: await x.text() };
}, [L.BASE + 'painel/' + url, campos]);
const valida = (a) => um(`SELECT COUNT(*) FROM rm_historico_emissoes WHERE aluno_id = ${a} AND tipo LIKE 'ESC%' AND situacao = 1`);
const total = (a) => um(`SELECT COUNT(*) FROM rm_historico_emissoes WHERE aluno_id = ${a} AND tipo LIKE 'ESC%'`);
const ultima = (a) => um(`SELECT MAX(id) FROM rm_historico_emissoes WHERE aluno_id = ${a} AND tipo LIKE 'ESC%' AND situacao = 1`);

(async () => {
  const { navegador } = await L.abrir();
  const shaC = sha(CTL);
  try {
    L.ok(X.montar('historico-parteJ').status === 0, 'a demonstração do colégio montada pela tela (com o histórico do concluinte emitido)');
    const c = X.cred();
    const A = um("SELECT id FROM col_usuarios WHERE rm = '255001'");

    /* 1. as ações */
    const acoes = sql("SELECT CONCAT((SELECT acao_id FROM adms_paginas WHERE id = 652), '|', (SELECT acao_id FROM adms_paginas WHERE id = 653), '|', (SELECT acao_id FROM adms_paginas WHERE id = 654))");
    const adm = await X.entrar(navegador, 'administrador', process.env.VCOSTAEDU_ADMIN_SENHA || 'chave-somente-local-123');
    await adm.p.goto(L.BASE + 'painel/perfis-acesso/index');
    const tela = ((await adm.p.textContent('body').catch(() => '')) || '').replace(/\s+/g, ' ');
    await L.print(adm.p, ETAPA, 'perfis');
    await adm.ctx.close();
    L.ok(acoes === '131|131|133' && tela.includes('Emitir o histórico escolar (e a 2ª via)') && tela.includes('Cancelar o histórico escolar emitido'),
      `emitir e cancelar como ações próprias (as páginas emitir, 2ª via, cancelar: ${acoes}), as duas na tela dos perfis`);

    /* 2. por POST direto de cada perfil */
    const res = {};
    for (const login of ['secretaria.demo', 'direcao.demo', 'supervisao.demo', 'coord.demo', 'prof.laura.demo']) {
      const s = await X.entrar(navegador, login, c.painel[login]);
      const t0 = total(A);
      await postar(s.p, `historico-escola-emissao/emitir/${A}?nivel=EF`, { confirmo: '1', publicacao: `PUB-${login.slice(0, 4)}` });
      const emitiu = total(A) === t0 + 1;
      const alvo = ultima(A);
      const v0 = valida(A);
      await postar(s.p, `historico-escola-emissao/cancelar/${A}?nivel=EF`, { emissao: String(alvo), motivo: `Cancelamento de prova (${login})` });
      const cancelou = valida(A) === v0 - 1;
      res[login] = `${emitiu ? 'emite' : 'não emite'}/${cancelou ? 'cancela' : 'não cancela'}`;
      await s.ctx.close();
    }
    L.ok(res['secretaria.demo'] === 'emite/cancela' && res['direcao.demo'] === 'emite/cancela' && res['supervisao.demo'] === 'emite/cancela'
      && res['coord.demo'] === 'não emite/não cancela' && res['prof.laura.demo'] === 'não emite/não cancela',
      `por POST direto: ${Object.entries(res).map(([k, v]) => `${k} ${v}`).join('; ')}`);

    /* 3. o cancelar tirado da direção */
    sql('UPDATE adms_nivacs_pgs SET permissao = 2 WHERE adms_pagina_id = 654 AND adms_niveis_acesso_id = 3');
    const dir = await X.entrar(navegador, 'direcao.demo', c.painel['direcao.demo']);
    const t0 = total(A);
    await postar(dir.p, `historico-escola-emissao/emitir/${A}?nivel=EF`, { confirmo: '1' });
    const emite = total(A) === t0 + 1;
    const v0 = valida(A);
    await postar(dir.p, `historico-escola-emissao/cancelar/${A}?nivel=EF`, { emissao: String(ultima(A)), motivo: 'A direção sem a ação de cancelar (prova)' });
    const naoCancela = valida(A) === v0;
    await dir.p.goto(L.BASE + `painel/historico-escola-emissao/previa/${A}?nivel=EF`);
    const botoes = (await dir.p.$$('.vc-cancelar')).length;
    const vias = (await dir.p.$$('.vc-via, [action*="segunda-via"]')).length;
    await L.print(dir.p, ETAPA, 'direcao-sem-cancelar');
    L.ok(emite && naoCancela && botoes === 0 && vias > 0,
      `sem a ação de cancelar, a direção emite (${emite}), o cancelar por POST é recusado (${naoCancela}) e a tela não mostra o botão (${botoes}; a 2ª via, sim: ${vias})`);

    /* sabotagem: o botão de cancelar pela permissão de emitir */
    const orig = fs.readFileSync(CTL, 'utf8');
    const trecho = "'cancelar' => HistoricoEscola::pode('cancelar', 'historico-escola-emissao')";
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let sab = -1;
    try {
      fs.writeFileSync(CTL, orig.replace(trecho, "'cancelar' => HistoricoEscola::pode('emitir', 'historico-escola-emissao')"));
      await dir.p.goto(L.BASE + `painel/historico-escola-emissao/previa/${A}?nivel=EF`);
      sab = (await dir.p.$$('.vc-cancelar')).length;
    } finally {
      fs.writeFileSync(CTL, orig);
    }
    await dir.ctx.close();
    sql('UPDATE adms_nivacs_pgs SET permissao = 1 WHERE adms_pagina_id = 654 AND adms_niveis_acesso_id = 3');
    L.ok(sab > 0, `sabotagem (o botão de cancelar pela permissão de emitir): a direção sem a ação vê ${sab} botão(ões) de cancelar — a prova reprova`);
    L.ok(sha(CTL) === shaC, 'o arquivo sabotado voltou igual (sha256)');
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split('\n')[0]);
  } finally {
    if (sha(CTL) !== shaC) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
    await navegador.close();
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
