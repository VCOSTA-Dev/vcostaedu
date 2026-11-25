/*
 * O TEMA (Onda 3, Etapa 10): a escolha no menu do usuário muda a tela na hora (window.vcTema, do core/Tema.php) e grava
 * na conta em segundo plano (POST tema=claro|escuro|auto no endereço do seletor).
 */
(function () {
  'use strict';
  document.addEventListener('click', function (ev) {
    var b = ev.target.closest ? ev.target.closest('.vc-tema-opcao') : null;
    if (!b) { return; }
    var caixa = b.closest('.vc-tema-seletor');
    var t = b.getAttribute('data-vc-tema');
    if (window.vcTema) { window.vcTema(t); }
    caixa.querySelectorAll('.vc-tema-opcao').forEach(function (o) {
      var sim = o === b;
      o.classList.toggle('active', sim);
      o.setAttribute('aria-pressed', sim ? 'true' : 'false');
    });
    var corpo = new URLSearchParams(); corpo.append('tema', t);
    fetch(caixa.getAttribute('data-url'), { method: 'POST', body: corpo, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (r) { caixa.setAttribute('data-gravado', r.ok ? t : 'erro'); })
      .catch(function () { caixa.setAttribute('data-gravado', 'erro'); });
  });
})();
