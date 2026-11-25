<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/*
 * O SINAL DE "GRAVADO" (Onda 8, correção, D-71): o que grava na hora, campo a campo (o horário da turma, a nota, a
 * chamada), mostra só um ícone pequeno ao lado do campo. Gravou: o ícone de confirmado, com "Gravado" ao passar o mouse,
 * some sozinho em poucos segundos. Não gravou: o ícone de alerta, que fica até a pessoa corrigir, com o motivo escrito
 * junto do campo. Sem caixa de mensagem, sem faixa no alto e sem a linha piscando.
 *
 *   vcGravado.gravando(alvo)   enquanto a gravação vai (o ícone de espera, discreto);
 *   vcGravado.ok(alvo)         gravou;
 *   vcGravado.erro(alvo, txt)  não gravou: o motivo;
 *   vcGravado.limpar(alvo)     tira o sinal.
 * "alvo" é o elemento que recebe o sinal ao lado (o campo); o sinal fica num <span class="vc-sinal"> logo depois dele.
 * Incluído uma vez por página.
 */
if (!empty($GLOBALS['vcGravadoIncluido'])) {
  return;
}
$GLOBALS['vcGravadoIncluido'] = true;
?>
<style>
  .vc-sinal { display: inline-flex; align-items: center; gap: .25rem; min-width: 1rem; margin-left: .35rem; font-size: .8rem; vertical-align: middle; }
  .vc-sinal .vc-sinal-ok { color: var(--tblr-success, #2fb344); transition: opacity .6s ease; }
  .vc-sinal .vc-sinal-ok.vc-sumindo { opacity: 0; }
  .vc-sinal .vc-sinal-erro { color: var(--tblr-danger, #d63939); }
  .vc-sinal .vc-sinal-espera { color: var(--tblr-secondary, #667382); }
  .vc-sinal .vc-sinal-motivo { color: var(--tblr-danger, #d63939); font-size: .75rem; white-space: normal; }
</style>
<script>
window.vcGravado = (function () {
  var TEMPO = 2500;
  function sinal(alvo) {
    var s = alvo.nextElementSibling;
    if (!s || !s.classList || !s.classList.contains('vc-sinal')) {
      s = document.createElement('span');
      s.className = 'vc-sinal';
      s.setAttribute('aria-live', 'polite');
      alvo.insertAdjacentElement('afterend', s);
    }
    clearTimeout(s._vcTempo);
    return s;
  }
  function icone(classe, titulo) {
    var i = document.createElement('i');
    i.className = classe;
    i.title = titulo;
    i.setAttribute('aria-label', titulo);
    return i;
  }
  return {
    gravando: function (alvo) {
      var s = sinal(alvo);
      if (s.querySelector('.vc-sinal-erro')) { return; } /* o alerta fica até gravar */
      s.replaceChildren(icone('fas fa-circle-notch fa-spin vc-sinal-espera', 'Gravando'));
    },
    ok: function (alvo) {
      var s = sinal(alvo);
      alvo.classList.remove('is-invalid');
      alvo.removeAttribute('aria-invalid');
      var i = icone('fas fa-check vc-sinal-ok', 'Gravado');
      s.replaceChildren(i);
      s.dataset.estado = 'gravado';
      s._vcTempo = setTimeout(function () {
        i.classList.add('vc-sumindo');
        s._vcTempo = setTimeout(function () { if (s.dataset.estado === 'gravado') { s.replaceChildren(); delete s.dataset.estado; } }, 700);
      }, TEMPO);
    },
    erro: function (alvo, motivo) {
      var s = sinal(alvo);
      alvo.classList.add('is-invalid');
      alvo.setAttribute('aria-invalid', 'true');
      var m = document.createElement('span');
      m.className = 'vc-sinal-motivo';
      m.textContent = motivo || 'Não foi possível gravar.';
      s.replaceChildren(icone('fas fa-triangle-exclamation vc-sinal-erro', 'Não gravou'), m);
      s.dataset.estado = 'erro';
    },
    limpar: function (alvo) {
      var s = sinal(alvo);
      alvo.classList.remove('is-invalid');
      alvo.removeAttribute('aria-invalid');
      s.replaceChildren();
      delete s.dataset.estado;
    }
  };
})();
</script>
