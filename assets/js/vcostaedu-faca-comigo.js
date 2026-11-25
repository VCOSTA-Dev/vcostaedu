/*
 * O "FAÇA COMIGO" (Onda 2, Etapa 4) — o motor do tour guiado de um projeto de referência (a decisão D-291 de lá, que já o
 * portava da VCOSTA AI), trazido para cá com três mudanças: as rotas são `faca-comigo/iniciar` e
 * `faca-comigo/passos`; o "ambiente de treino" daqui é a DEMONSTRAÇÃO (o modo "mostre", que executa as ações do
 * roteiro sozinho — para a gravação do vídeo —, só vale com a demonstração ligada); e os textos falam de "Faça
 * comigo", não de tour.
 *
 * Uma janela de narração no canto: o passo, o texto, o que fazer agora ("Agora: …"). O roteiro atravessa páginas:
 * cada passo diz em que página vive (`p`, regex do caminho DEPOIS da raiz da instalação, que vem do
 * <meta name="vc-urladm">), e o estado fica no sessionStorage (por aba).
 *
 * O ROTEIRO de cada procedimento é UM arquivo de dados (painel/app/adms/Roteiros/<slug>.json): o mesmo serve a este
 * motor, à prova do Playwright (tests/tabler/onda2/etapa4.js) e, mais tarde, à gravação do vídeo.
 *
 * OS DOIS MODOS:
 *   junto   o "Faça comigo": a pessoa faz e o motor CONFERE — a cada 500 ms ele sonda as condições (`confere`) e
 *           avança sozinho quando todas valem; "Conferi, avançar" com uma condição falsa mostra a mensagem e
 *           contorna o elemento em vermelho. Nada é feito sozinho.
 *   mostre  o motor faz: executa as ações (`faz`) e avança sozinho. Só com a demonstração ligada (o servidor manda
 *           as ações só nesse caso — a garantia é dele).
 *
 * UM PASSO: p (regex do caminho), a (seletor do que destacar), t e x (título e texto, **negrito**), aviso ("Agora:
 * …"), atencao (o passo que salva de verdade), faz (as ações: clicar, preencher, escolher, marcar, ir, esperar…),
 * confere ([[tipo, sel, esperado, mensagem], …]: valor, existe, ausente, pagina, marcado), modal, espera,
 * pular_se, encerrar_se.
 *
 * OS SINAIS: a cada passo, o evento `vc-tour-passo` na `window`; no fim, `window.vcTourFim = true` e o
 * sessionStorage `vc-tour-fim`.
 */
(function () {
  'use strict';
  var CHAVE = 'vc-tour', DADOS = 'vc-tour-dados';
  var ss = { ler: function (k) { try { return JSON.parse(sessionStorage.getItem(k) || 'null'); } catch (e) { return null; } },
    gravar: function (k, v) { try { sessionStorage.setItem(k, JSON.stringify(v)); } catch (e) { /* sem storage: o tour vale só nesta página */ } },
    tirar: function (k) { try { sessionStorage.removeItem(k); } catch (e) { /* nada */ } } };
  var esc = function (t) { return String(t == null ? '' : t).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };
  var md = function (t) { return esc(t).replace(/\*\*(.+?)\*\*/g, '<b>$1</b>'); };
  var dormir = function (ms) { return new Promise(function (r) { setTimeout(r, ms); }); };

  /* A raiz da instalação (o treino pode estar noutra pasta): o caminho dos passos é o que vem DEPOIS dela. */
  var metaRaiz = document.querySelector('meta[name="vc-urladm"]');
  var RAIZ = metaRaiz ? metaRaiz.content : (location.origin + '/');
  var baseCaminho = (function () { try { return new URL(RAIZ).pathname; } catch (e) { return '/'; } })();
  var caminho = function () {
    var c = location.pathname;
    return (c.indexOf(baseCaminho) === 0 ? c.slice(baseCaminho.length) : c.replace(/^\//, ''));
  };
  var metaAmb = document.querySelector('meta[name="vc-ambiente"]');
  var AMBIENTE = metaAmb ? metaAmb.content : 'real';

  // Começar: ?tour=<slug>&modo=…
  var q = new URLSearchParams(location.search);
  if (q.get('tour')) {
    var modoPedido = q.get('modo') === 'mostre' ? 'mostre' : 'junto';
    ss.gravar(CHAVE, { guia: q.get('tour'), i: 0, modo: modoPedido,
      rapido: q.get('rapido') === '1', gravar: q.get('gravar') === '1', erros: 0 });
    ss.tirar(DADOS);
    ss.tirar('vc-tour-fim');
    ['tour', 'modo', 'rapido', 'gravar'].forEach(function (k) { q.delete(k); });
    history.replaceState(null, '', location.pathname + (q.toString() ? '?' + q.toString() : '') + location.hash);
  }
  var estado = ss.ler(CHAVE);
  if (!estado) { return; }

  /* O modo local também é conferido (a garantia é do servidor; esta é a cortesia): fora do treino, só o "junto" — e
     um estado antigo, gravado ainda com o "Passo a passo" (`manual`), vira "junto". */
  if ((AMBIENTE !== 'demonstracao' && estado.modo !== 'junto') || (estado.modo !== 'mostre' && estado.modo !== 'junto')) {
    estado.modo = 'junto'; ss.gravar(CHAVE, estado);
  }
  var REAL = AMBIENTE !== 'demonstracao';
  var MOSTRE = function () { return estado.modo === 'mostre'; };
  var JUNTO = function () { return estado.modo === 'junto'; };

  var dados = null, caixa = null, alvo = null, alvoErro = null, atual = -1, ocupado = false, sonda = null;

  /* A PÁGINA JÁ ESTÁ SAINDO (um submit que demora a responder — o aceite gera o contrato, e isso leva segundos): o
     motor não "conclui" nem procura passo nesta página; a próxima carrega e retoma do passo gravado. */
  var saindo = false;
  window.addEventListener('beforeunload', function () { saindo = true; });

  function carregar() {
    var guardado = ss.ler(DADOS);
    if (guardado && guardado.guia === estado.guia) { return Promise.resolve(guardado); }
    return fetch(RAIZ + 'faca-comigo/passos/' + encodeURIComponent(estado.guia) + '?modo=' + encodeURIComponent(estado.modo), { credentials: 'same-origin' })
      .then(function (r) { if (!r.ok) { throw new Error('passos ' + r.status); } return r.json(); })
      .then(function (d) { ss.gravar(DADOS, d); return d; });
  }

  function naPagina(p) { return new RegExp(p.p).test(caminho()); }

  function visivel(el) { return !!el && el.getClientRects().length > 0; }

  function limparDestaque() {
    if (alvo) { alvo.classList.remove('vc-tour-alvo'); alvo = null; }
    if (alvoErro) { alvoErro.classList.remove('vc-tour-erro'); alvoErro = null; }
  }

  function pararSonda() { if (sonda) { clearInterval(sonda); sonda = null; } }

  function terminar(motivo, mensagem) {
    pararSonda();
    limparDestaque();
    ss.tirar(CHAVE);
    ss.tirar(DADOS);
    ss.gravar('vc-tour-fim', '1');
    window.vcTourFim = true;
    window.dispatchEvent(new CustomEvent('vc-tour-fim', { detail: { motivo: motivo } }));
    if (!caixa) { return; }
    if (motivo === 'concluir' && !MOSTRE() && dados) {
      caixa.innerHTML = '<div class="vc-tour-topo"><span class="vc-tour-guia"><i class="fas fa-route me-1"></i>' + esc(dados.titulo) + '</span>'
        + '<button type="button" class="btn-close vc-tour-sair" aria-label="Fechar"></button></div>'
        + '<div class="vc-tour-titulo">Tour concluído</div>'
        + '<div class="vc-tour-texto">' + (mensagem ? esc(mensagem) : dados.ambiente === 'demonstracao'
          ? 'Você está na demonstração: os dados são fictícios.'
          : 'O guia em texto tem o caminho completo, com as regras.') + '</div>'
        + '<div class="vc-tour-botoes"><a class="btn btn-sm" href="' + esc(dados.fim) + '">Ler o guia</a>'
        + '<button type="button" class="btn btn-sm btn-primary vc-tour-sair2">Fechar</button></div>';
      var fechar = function () { if (caixa) { caixa.remove(); caixa = null; } };
      caixa.querySelector('.vc-tour-sair').onclick = fechar;
      caixa.querySelector('.vc-tour-sair2').onclick = fechar;
      return;
    }
    caixa.remove();
    caixa = null;
  }

  function janela() {
    if (caixa) { return caixa; }
    caixa = document.createElement('div');
    caixa.id = 'vc-tour';
    caixa.setAttribute('role', 'dialog');
    caixa.setAttribute('aria-live', 'polite');
    if (estado.gravar) { caixa.classList.add('vc-tour-gravar'); }
    document.body.appendChild(caixa);
    return caixa;
  }

  /*
   * A JANELA FOGE DO ALVO — a mesma lógica da legenda da D-222: o primeiro canto em que a janela não
   * cruza o retângulo do alvo (inferior direito, inferior esquerdo, superior direito, superior esquerdo).
   */
  function fugir() {
    if (!caixa) { return; }
    ['vc-tour-ie', 'vc-tour-sd', 'vc-tour-se'].forEach(function (c) { caixa.classList.remove(c); });
    var el = alvoErro || alvo;
    if (!el) { return; }
    var r = el.getBoundingClientRect();
    var cantos = ['', 'vc-tour-ie', 'vc-tour-sd', 'vc-tour-se'];
    for (var k = 0; k < cantos.length; k++) {
      if (cantos[k]) { caixa.classList.add(cantos[k]); }
      var c = caixa.getBoundingClientRect();
      var cruza = !(c.right < r.left || c.left > r.right || c.bottom < r.top || c.top > r.bottom);
      if (!cruza) { return; }
      if (cantos[k]) { caixa.classList.remove(cantos[k]); }
    }
  }

  function emitirPasso(i) {
    var p = dados.passos[i];
    window.dispatchEvent(new CustomEvent('vc-tour-passo', { detail: {
      guia: dados.guia, titulo: dados.titulo, i: i, n: dados.passos.length, t: p.t, x: p.x, modo: estado.modo } }));
  }

  async function mostrar(i) {
    pararSonda();
    var p = dados.passos[i];
    atual = i;
    estado.i = i;
    ss.gravar(CHAVE, estado);
    limparDestaque();

    /* A espera do modal: o passo vive dentro dele — espera o `.show` (até 6 s) antes de destacar. */
    if (p.modal) {
      for (var w = 0; w < 30 && !document.querySelector(p.modal + '.show'); w++) { await dormir(200); }
      await dormir(250);
    }

    /* pular_se: a condição já vale (o campo veio preenchido, o cliente já existe) — o passo sai do caminho. */
    if (p.pular_se && condicao(p.pular_se).ok) {
      if (i < dados.passos.length - 1 && naPagina(dados.passos[i + 1])) { return mostrar(i + 1); }
    }

    /* encerrar_se (onda 9e): a condição já vale — o caminho de quem chegou aqui acabou. O tour termina com a
       mensagem do quarto item (ex.: quem respondeu "sem demanda" não passa por "Informar os serviços"). */
    if (p.encerrar_se && condicao(p.encerrar_se).ok) {
      janela();
      terminar('concluir', p.encerrar_se[3] || '');
      return;
    }

    /* `lembrar` no passo: a página atual fica guardada (a proposta que o tour acabou de criar) — em
       qualquer modo, sem depender de ação. ['ir', '{lembrado:chave}'] volta a ela depois. */
    if (p.lembrar) {
      estado.lembrado = estado.lembrado || {};
      estado.lembrado[p.lembrar] = caminho() + location.search.replace(/[?&](enviar)=1/, '');
      ss.gravar(CHAVE, estado);
    }

    var el = p.a ? document.querySelector(p.a) : null;
    if (visivel(el)) { // (visível; o menu é fixo, e o fixo não tem offsetParent)
      alvo = el;
      el.classList.add('vc-tour-alvo');
      el.scrollIntoView({ behavior: MOSTRE() ? 'auto' : 'smooth', block: 'center' });
    }
    var ultimo = i === dados.passos.length - 1;
    var anterior = i > 0 && naPagina(dados.passos[i - 1]) && !dados.passos[i - 1].faz;
    var temConfere = JUNTO() && p.confere && p.confere.length;
    /* No "junto", um passo com ação e SEM conferência não tem o que sondar: o "Mostrar como" fica à vista. */
    var mostrarDeSaida = JUNTO() && p.faz && p.faz.length && !temConfere;
    var c = janela();
    c.dataset.passo = String(i);
    c.innerHTML = '<div class="vc-tour-topo"><span class="vc-tour-guia"><i class="fas fa-route me-1"></i>' + esc(dados.titulo) + '</span>'
      + '<span class="vc-tour-n">' + (i + 1) + ' de ' + dados.passos.length + '</span>'
      + '<button type="button" class="btn-close vc-tour-sair" aria-label="Sair do tour"></button></div>'
      + '<div class="vc-tour-titulo">' + md(p.t) + '</div><div class="vc-tour-texto">' + md(p.x) + '</div>'
      + (p.atencao && !MOSTRE() ? '<div class="vc-tour-atencao"><i class="fas fa-triangle-exclamation me-1"></i>' + md(p.atencao) + '</div>' : '')
      + (p.aviso && !MOSTRE() ? '<div class="vc-tour-aviso"><b>Agora:</b> ' + md(p.aviso) + '</div>' : '')
      /* "Prefere treinar sem mexer em dados reais?" — no primeiro passo, só onde o servidor mandou o link (o treino
         configurado; na produção, não). */
      + (i === 0 && dados.treinar ? '<div class="vc-tour-treinar">Prefere treinar sem mexer em dados reais? <a href="'
        + esc(dados.treinar) + '" class="vc-tour-treinar-link">Abrir no ambiente de treino</a></div>' : '')
      + (p.faz && MOSTRE() ? '<div class="vc-tour-aviso vc-tour-fazendo"><i class="fas fa-hand-pointer me-1"></i>' + md(p.aviso || 'O tour faz este passo.') + '</div>' : '')
      + '<div class="vc-tour-erro-texto" hidden></div>'
      + (p.espera ? '<div class="vc-tour-espera" hidden><span class="spinner-border spinner-border-sm me-2"></span>' + esc(p.espera[1]) + '</div>' : '')
      + '<div class="vc-tour-botoes">' + (anterior && !MOSTRE() ? '<button type="button" class="btn btn-sm vc-tour-voltar">Voltar</button>' : '')
      + '<button type="button" class="btn btn-sm vc-tour-mostrar"' + (mostrarDeSaida ? '' : ' hidden') + '>Mostrar como</button>'
      + (MOSTRE() ? '' : '<button type="button" class="btn btn-sm btn-primary vc-tour-avancar">'
        + (ultimo ? 'Concluir' : (temConfere ? 'Conferi, avançar' : (mostrarDeSaida ? 'Já fiz: avançar' : 'Avançar'))) + '</button>') + '</div>';
    c.querySelector('.vc-tour-sair').onclick = function () { terminar('sair'); };
    var v = c.querySelector('.vc-tour-voltar');
    if (v) { v.onclick = function () { mostrar(i - 1); }; }
    var m = c.querySelector('.vc-tour-mostrar');
    m.onclick = function () { mostrarComo(i); };
    var b = c.querySelector('.vc-tour-avancar');
    if (b) { b.onclick = function () { conferirEAvancar(i); }; }
    fugir();
    emitirPasso(i);
    if (p.espera) { esperar(p); }
    /* No real, um valor DIGITADO não é sondado: "preenchido" valeria na primeira tecla e o tour fugiria no meio da
       palavra. A pessoa termina e clica em "Conferi, avançar". (No treino, a conferência é do valor do exemplo.) */
    var digitado = REAL && (p.confere || []).some(function (c) { return c[0] === 'valor'; });
    if (MOSTRE()) { automatico(i); } else if (temConfere && !digitado) { sondar(i); }
    /* O "Conferi" de um passo `recarregar` recarregou a página: confere agora (sem recarregar de novo). */
    if (estado.conferirApos === i) {
      delete estado.conferirApos;
      ss.gravar(CHAVE, estado);
      setTimeout(function () { if (atual === i) { conferirEAvancar(i, true); } }, 700);
    }
  }

  /** Um passo com "espera": o Avançar só liga quando o seletor aparece (a página se atualiza sozinha enquanto isso). */
  function esperar(p) {
    var b = caixa.querySelector('.vc-tour-avancar'), aviso = caixa.querySelector('.vc-tour-espera');
    var pronto = function () { return document.querySelector(p.espera[0]); };
    if (pronto()) { return; }
    if (b) { b.disabled = true; }
    aviso.hidden = false;
    var t = setInterval(function () { if (pronto()) { clearInterval(t); if (b) { b.disabled = false; } aviso.hidden = true; } }, 1000);
  }

  // ------------------------------------------------------------- a conferência

  /** Uma condição: {ok, el, msg}. */
  function condicao(c) {
    var tipo = c[0], sel = c[1], esperado = c[2], msg = c[3] || 'Este passo ainda não foi feito.';
    var el = sel && tipo !== 'pagina' ? document.querySelector(sel) : null;
    var ok = false;
    if (tipo === 'existe') {
      ok = visivel(el);
    } else if (tipo === 'ausente') {
      /* Onda 9c: o contrário de `existe` — para o `pular_se` de um passo que só vale quando o elemento aparece
         (o aviso "no lugar do Financeiro" só existe para quem atua pela exceção). */
      ok = !visivel(el);
    } else if (tipo === 'pagina') {
      ok = new RegExp(sel).test(caminho());
    } else if (tipo === 'valor') {
      var valor = el ? String(el.value == null ? el.textContent : el.value) : '';
      if (esperado == null) { ok = valor.trim() !== ''; } else {
        var mr = /^\/(.*)\/([a-z]*)$/.exec(String(esperado));
        ok = mr ? new RegExp(mr[1], mr[2]).test(valor) : valor.indexOf(String(esperado)) >= 0;
      }
    } else if (tipo === 'marcado') {
      if (el && el.tagName === 'SELECT') {
        /* O select confere pelo valor OU pelo texto da opção escolhida (o id de um segmento muda de banco para banco). */
        var texto = el.selectedIndex >= 0 ? el.options[el.selectedIndex].text.trim() : '';
        var re = /^\/(.*)\/([a-z]*)$/.exec(String(esperado));
        ok = esperado == null ? el.value !== '' : (String(el.value) === String(esperado) || (re ? new RegExp(re[1], re[2]).test(texto) : texto === String(esperado)));
      } else { ok = !!(el && el.checked); }
    }
    return { ok: ok, el: el, msg: msg };
  }

  function primeiraFalsa(p) {
    for (var k = 0; k < (p.confere || []).length; k++) {
      var r = condicao(p.confere[k]);
      if (!r.ok) { return r; }
    }
    return null;
  }

  /** "Junto": sonda a cada 500 ms; tudo valendo, avança sozinho. */
  function sondar(i) {
    pararSonda();
    sonda = setInterval(function () {
      if (atual !== i || ocupado) { return; }
      var p = dados.passos[i];
      if (!primeiraFalsa(p)) {
        pararSonda();
        var t = caixa && caixa.querySelector('.vc-tour-titulo');
        if (t) { t.insertAdjacentHTML('beforeend', ' <span class="badge bg-success text-white ms-1 vc-tour-certo">conferido</span>'); }
        setTimeout(function () { if (atual === i) { avancar(false); } }, 900);
      }
    }, 500);
  }

  function conferirEAvancar(i, recarregado) {
    var p = dados.passos[i];
    if (!(JUNTO() && p.confere && p.confere.length)) { avancar(false); return; }
    var falsa = primeiraFalsa(p);
    if (!falsa) { avancar(false); return; }
    if (p.recarregar && !recarregado) {
      estado.conferirApos = i;
      ss.gravar(CHAVE, estado);
      location.reload();
      return;
    }
    estado.erros = (estado.erros || 0) + 1;
    ss.gravar(CHAVE, estado);
    var caixaErro = caixa.querySelector('.vc-tour-erro-texto');
    caixaErro.innerHTML = '<b>Ainda não:</b> ' + md(falsa.msg);
    caixaErro.hidden = false;
    if (alvoErro) { alvoErro.classList.remove('vc-tour-erro'); }
    if (falsa.el) { alvoErro = falsa.el; falsa.el.classList.add('vc-tour-erro'); falsa.el.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
    /* "Mostrar como" só existe quando o passo tem ações — e o servidor só as manda no treino. */
    if (p.faz && p.faz.length) { caixa.querySelector('.vc-tour-mostrar').hidden = false; }
    fugir();
  }

  async function mostrarComo(i) {
    var p = dados.passos[i];
    /* No sistema real, o tour nunca age (o servidor nem manda o `faz`; isto é a segunda trava). */
    if (!p.faz || REAL) { return; }
    var e = caixa.querySelector('.vc-tour-erro-texto');
    if (e) { e.hidden = true; }
    /* Usado, o botão sai (volta se a conferência falhar de novo): dois cliques não refazem a ação no meio. */
    var bm = caixa.querySelector('.vc-tour-mostrar');
    if (bm) { bm.hidden = true; }
    if (alvoErro) { alvoErro.classList.remove('vc-tour-erro'); alvoErro = null; }
    ocupado = true;
    try {
      var mudou = await fazer(p, i, true);
      if (mudou) { return; }
    } finally { ocupado = false; }
    await dormir(400);
    /* Feito: a conferência roda de novo — valendo, a sonda avança. */
    if (JUNTO()) { sondar(i); } else { avancar(false); }
  }

  // ------------------------------------------------------------- as ações

  function eventos(el) {
    el.dispatchEvent(new Event('input', { bubbles: true }));
    el.dispatchEvent(new Event('change', { bubbles: true }));
    /* A máscara de jQuery já calou listener nativo antes (696ed52): os eventos do jQuery também. */
    if (window.jQuery) { try { window.jQuery(el).trigger('input').trigger('change'); } catch (e) { /* sem jQuery na página */ } }
  }

  async function digitar(el, valor, somar) {
    el.focus();
    if (el.inputmask && typeof el.inputmask.setValue === 'function') {
      /* Campo com máscara: tecla a tecla, a máscara perde caracteres (medido no faturamento, D-285). */
      el.inputmask.setValue((somar ? el.value : '') + valor);
      eventos(el);
      return;
    }
    if (!somar) { el.value = ''; }
    if (MOSTRE() && !estado.rapido && el.tagName !== 'SELECT') {
      for (var k = 0; k < valor.length; k++) { el.value += valor[k]; el.dispatchEvent(new Event('input', { bubbles: true })); await dormir(estado.gravar ? 45 : 18); }
    } else { el.value = (somar ? el.value : '') + valor; }
    eventos(el);
  }

  /*
   * ANEXAR (onda 9c): põe num `<input type="file">` um arquivo de EXEMPLO que o preparo do treino gerou (o conteúdo
   * em base64 vem nos dados do tour) — o navegador não deixa um script escolher arquivo do disco, mas aceita um
   * `File` montado na página. Só existe no treino: no sistema real o tour não tem `faz`.
   */
  function anexar(el, base64, nome) {
    if (!el || !base64 || typeof DataTransfer === 'undefined') { return; }
    try {
      var bin = atob(String(base64));
      var bytes = new Uint8Array(bin.length);
      for (var b = 0; b < bin.length; b++) { bytes[b] = bin.charCodeAt(b); }
      var dt = new DataTransfer();
      dt.items.add(new File([bytes], String(nome || 'comprovante-exemplo.pdf'), { type: /\.png$/i.test(String(nome)) ? 'image/png' : (/\.jpe?g$/i.test(String(nome)) ? 'image/jpeg' : 'application/pdf') }));
      el.files = dt.files;
      el.dispatchEvent(new Event('change', { bubbles: true }));
    } catch (e) { /* sem DataTransfer: o passo segue, e a conferência diz o que falta */ }
  }

  function escolher(el, valor) {
    if (el.tomselect) { el.tomselect.setValue(String(valor)); return; }
    if (el.tagName === 'SELECT') {
      var achou = false;
      /* VCOSTAEDU: '{primeira}' = a primeira opção com valor (a grade, o ano, o curso que acabaram de ser criados) */
      if (String(valor) === '{primeira}') {
        for (var q = 0; q < el.options.length; q++) { if (el.options[q].value !== '' && el.options[q].value !== '0') { valor = el.options[q].value; break; } }
      }
      for (var k = 0; k < el.options.length; k++) {
        if (el.options[k].value === String(valor) || el.options[k].text.trim() === String(valor)) { el.selectedIndex = k; achou = true; break; }
      }
      if (!achou) { el.value = String(valor); }
    } else { el.value = String(valor); }
    eventos(el);
  }

  /** Faz as ações do passo. Grava o próximo passo ANTES (a última ação costuma mudar de página). */
  async function fazer(p, i, soEste) {
    for (var k = 0; k < p.faz.length; k++) {
      var a = p.faz[k];
      if (a[0] === 'esperar') { await dormir(estado.rapido ? Math.min(400, a[1]) : a[1]); continue; }
      /* `chamar`: um GET a uma rota da instalação, sem sair da página (o "a sessão caiu" do tour do autosave). */
      if (a[0] === 'chamar') {
        try { await fetch(RAIZ + String(a[1]).replace(/^\//, ''), { credentials: 'same-origin' }); } catch (e) { /* a rota diz */ }
        continue;
      }
      if (k === p.faz.length - 1 && !soEste) { estado.i = i + 1; ss.gravar(CHAVE, estado); }
      if (a[0] === 'lembrar') {
        estado.lembrado = estado.lembrado || {};
        estado.lembrado[a[1]] = caminho() + location.search;
        ss.gravar(CHAVE, estado);
        continue;
      }
      if (a[0] === 'ir') {
        if (soEste || k === p.faz.length - 1) { estado.i = i + 1; ss.gravar(CHAVE, estado); }
        var rota = String(a[1]).replace(/\{lembrado:([a-z0-9_-]+)\}/gi, function (_, ch) { return (estado.lembrado || {})[ch] || ''; });
        location.href = RAIZ + rota.replace(/^\//, '');
        return true;
      }
      var el = a[1] ? document.querySelector(a[1]) : null;
      for (var w = 0; !el && w < 15; w++) { await dormir(200); el = document.querySelector(a[1]); }
      if (!el) { continue; }
      if (a[0] === 'abrir') {
        estado.i = i + 1;
        ss.gravar(CHAVE, estado);
        location.href = String(el.value || el.href || el.getAttribute('data-url') || '');
        return true;
      }
      if (soEste && k === p.faz.length - 1 && (a[0] === 'clicar')) { estado.i = i + 1; ss.gravar(CHAVE, estado); }
      if (a[0] === 'clicar') { el.click(); }
      else if (a[0] === 'preencher') { await digitar(el, String(a[2]), false); }
      else if (a[0] === 'acrescentar') { await digitar(el, String(a[2]), true); }
      else if (a[0] === 'marcar') { if (!el.checked) { el.click(); } }
      else if (a[0] === 'desmarcar') { if (el.checked) { el.click(); } }
      else if (a[0] === 'escolher') { escolher(el, a[2]); }
      else if (a[0] === 'anexar') { anexar(el, a[2], a[3]); }
      await dormir(MOSTRE() && !estado.rapido ? 500 : 150);
    }
    return false;
  }

  function automatico(i) {
    var p = dados.passos[i];
    var leitura = estado.rapido ? 600 : Math.max(3500, (p.t.length + p.x.length) * 55) + 600; // rapido: as provas
    (async function () {
      await dormir(leitura);
      if (p.espera) { while (!document.querySelector(p.espera[0])) { await dormir(1000); } await dormir(800); }
      if (atual === i) { avancar(true); }
    })();
  }

  async function avancar(comAcoes) {
    if (ocupado) { return; }
    ocupado = true;
    pararSonda();
    var i = atual, p = dados.passos[i];
    try {
      if (comAcoes && p.faz) {
        if (await fazer(p, i, false)) { return; }
        await dormir(700); // se a ação mudou de página, a próxima carrega e retoma daqui
        if (saindo) { return; }
      }
      if (i === dados.passos.length - 1) {
        if (MOSTRE()) { await dormir(estado.rapido ? 800 : 2500); }
        terminar('concluir');
        return;
      }
      var prox = dados.passos[i + 1];
      if (naPagina(prox)) { ocupado = false; mostrar(i + 1); return; }
      estado.i = i + 1;
      ss.gravar(CHAVE, estado);
      /* NO SISTEMA REAL quem muda de tela é a pessoa (não há `faz`): o tour não termina — avisa que o próximo passo é
         noutra tela e espera; aberta a tela, o `retomar` segue dali. */
      if (REAL) { aguardarOutraTela(i + 1); return; }
      // A próxima é noutra página e nada mudou de página (a ação não tinha o que clicar — uma lista vazia, por exemplo):
      // segue para o próximo passo que esteja nesta página; se não há, o tour termina aqui, sem travar.
      await dormir(2500);
      if (saindo) { return; }
      for (var j = i + 1; j < dados.passos.length; j++) { if (naPagina(dados.passos[j])) { ocupado = false; mostrar(j); return; } }
      /* Ainda saindo depois de 2,5 s (o servidor demora): espera mais, até 60 s, antes de desistir da página. */
      for (var w = 0; w < 116 && !saindo && document.readyState !== 'complete'; w++) { await dormir(500); }
      if (saindo) { return; }
      terminar('concluir');
    } finally { ocupado = false; }
  }

  /** No real: o próximo passo vive noutra tela — a janela diz qual, e o tour espera a pessoa chegar lá. */
  function aguardarOutraTela(j) {
    limparDestaque();
    var p = dados.passos[j];
    var c = janela();
    c.dataset.passo = String(j);
    c.innerHTML = '<div class="vc-tour-topo"><span class="vc-tour-guia"><i class="fas fa-route me-1"></i>' + esc(dados.titulo) + '</span>'
      + '<span class="vc-tour-n">' + (j + 1) + ' de ' + dados.passos.length + '</span>'
      + '<button type="button" class="btn-close vc-tour-sair" aria-label="Sair do tour"></button></div>'
      + '<div class="vc-tour-titulo">Próximo: ' + md(p.t) + '</div>'
      + '<div class="vc-tour-texto">Este passo é em outra tela. Abra a tela quando quiser: o tour continua de lá.</div>'
      + '<div class="vc-tour-botoes"><button type="button" class="btn btn-sm btn-primary vc-tour-sair2">Sair do tour</button></div>';
    c.querySelector('.vc-tour-sair').onclick = function () { terminar('sair'); };
    c.querySelector('.vc-tour-sair2').onclick = function () { terminar('sair'); };
    emitirPasso(j);
  }

  function retomar() {
    var i = Math.min(estado.i || 0, dados.passos.length - 1), fim = Math.min(i + 5, dados.passos.length), j;
    // O passo desta página cujo alvo está na tela (a pessoa pode ter feito o passo sozinha e a página mudou por baixo).
    for (j = i; j < fim; j++) {
      var p = dados.passos[j];
      if (naPagina(p) && (!p.a || document.querySelector(p.a))) { mostrar(j); return; }
    }
    for (j = i; j < fim; j++) {
      if (naPagina(dados.passos[j])) { mostrar(j); return; }
    }
    // Fora do caminho do tour: avisa e oferece voltar ao guia.
    var c = janela();
    c.innerHTML = '<div class="vc-tour-topo"><span class="vc-tour-guia"><i class="fas fa-route me-1"></i>' + esc(dados.titulo) + '</span><button type="button" class="btn-close vc-tour-sair" aria-label="Sair do tour"></button></div>'
      + '<div class="vc-tour-texto">O Faça comigo ficou noutra tela.</div><div class="vc-tour-botoes"><a class="btn btn-sm" href="' + esc(dados.fim) + '">Voltar ao guia</a>'
      + '<button type="button" class="btn btn-sm btn-primary vc-tour-sair2">Sair do tour</button></div>';
    c.querySelector('.vc-tour-sair').onclick = function () { terminar('sair'); };
    c.querySelector('.vc-tour-sair2').onclick = function () { terminar('sair'); };
  }

  function falhou(e) {
    ss.tirar(CHAVE);
    ss.tirar(DADOS);
    var c = janela();
    var recusa = /403/.test(String(e && e.message));
    c.innerHTML = '<div class="vc-tour-topo"><span class="vc-tour-guia"><i class="fas fa-route me-1"></i>Faça comigo</span><button type="button" class="btn-close vc-tour-sair" aria-label="Fechar"></button></div>'
      + '<div class="vc-tour-texto">' + (recusa ? 'Este Faça comigo não está disponível para o seu perfil.' : 'Não foi possível abrir o tour.') + '</div>';
    c.querySelector('.vc-tour-sair').onclick = function () { c.remove(); caixa = null; };
    window.vcTourFim = true;
  }

  var iniciar = function () { carregar().then(function (d) { dados = d; retomar(); }).catch(falhou); };
  if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', iniciar); } else { iniciar(); }
  window.addEventListener('resize', fugir);
  window.vcTour = { sair: function () { terminar('sair'); }, estado: function () { return { modo: estado.modo, i: atual, guia: estado.guia }; } };
}());
