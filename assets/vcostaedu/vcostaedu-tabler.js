/*
 * VCOSTAEDU — comportamento do layout Tabler (Etapa 3), painel e portal.
 *
 * 1. O RAIL (do rodape.php do vcostaedu): o grupo abre e fecha; o recolher da coluna (« acima de 992 px) guarda a
 *    escolha no navegador (localStorage 'vc-side-mini', em try/catch); a alça do usuário abre a lista de ações e
 *    fecha com clique fora ou Esc.
 * 2. A DICA DE VERDADE (D-11): todo elemento com data-bs-toggle="tooltip" ganha a dica do Tabler — inclusive a
 *    razão da ação desabilitada (AdmsUi::acao), que na origem só aparecia pelo title do navegador.
 *
 * Usa `window.tabler` (o bundle do Tabler não publica `window.bootstrap`).
 */
document.addEventListener('DOMContentLoaded', function () {
  /* 1a. Grupos do rail */
  document.querySelectorAll('.vc-side-grupo-toggle').forEach(function (botao) {
    var lista = document.getElementById(botao.getAttribute('aria-controls'));
    if (!lista) { return; }
    if (lista.classList.contains('aberta')) { lista.style.maxHeight = lista.scrollHeight + 'px'; }
    botao.addEventListener('click', function () {
      var abrir = botao.getAttribute('aria-expanded') !== 'true';
      botao.setAttribute('aria-expanded', abrir ? 'true' : 'false');
      lista.classList.toggle('aberta', abrir);
      lista.style.maxHeight = abrir ? lista.scrollHeight + 'px' : '0px';
    });
  });

  /* 1b. Recolher a coluna */
  var pagina = document.querySelector('.page');
  var recolher = document.getElementById('vc-side-recolher');
  if (recolher && pagina) {
    var rotular = function (mini) {
      recolher.setAttribute('title', mini ? 'Fixar o menu aberto' : 'Recolher o menu');
      recolher.setAttribute('aria-label', mini ? 'Fixar o menu aberto' : 'Recolher o menu');
      recolher.setAttribute('aria-pressed', mini ? 'true' : 'false');
    };
    rotular(pagina.classList.contains('vc-side-mini'));
    recolher.addEventListener('click', function () {
      var mini = pagina.classList.toggle('vc-side-mini');
      rotular(mini);
      try { window.localStorage.setItem('vc-side-mini', mini ? '1' : '0'); } catch (e) { /* modo restrito */ }
    });
  }

  /* 1c. A alça do usuário */
  var alca = document.getElementById('vc-side-alca');
  var acoes = document.getElementById('vc-side-acoes');
  if (alca && acoes) {
    var mostrar = function (aberto) {
      alca.setAttribute('aria-expanded', aberto ? 'true' : 'false');
      acoes.classList.toggle('aberta', aberto);
    };
    mostrar(false);
    alca.addEventListener('click', function (e) { e.stopPropagation(); mostrar(alca.getAttribute('aria-expanded') !== 'true'); });
    document.addEventListener('click', function (e) { if (!acoes.contains(e.target)) { mostrar(false); } });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { mostrar(false); } });
  }

  /* 3. O recolher do cartão (o data-card-widget="collapse" do AdminLTE vira data-vc-recolher no de-para) */
  document.querySelectorAll('[data-vc-recolher]').forEach(function (botao) {
    botao.setAttribute('aria-expanded', 'true');
    botao.addEventListener('click', function () {
      var cartao = botao.closest('.card');
      if (!cartao) { return; }
      var recolher = !cartao.classList.contains('vc-cartao-recolhido');
      cartao.classList.toggle('vc-cartao-recolhido', recolher);
      botao.setAttribute('aria-expanded', recolher ? 'false' : 'true');
      var icone = botao.querySelector('i');
      if (icone) { icone.classList.toggle('fa-minus', !recolher); icone.classList.toggle('fa-plus', recolher); }
    });
  });

  /* 2. Dicas */
  if (window.tabler && typeof window.tabler.Tooltip === 'function') {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
      if (!window.tabler.Tooltip.getInstance(el)) { new window.tabler.Tooltip(el); }
    });
  }
});
