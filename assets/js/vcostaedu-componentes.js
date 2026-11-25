/*
 * COMPONENTES COMUNS DO VCOSTAEDU — instalador, telas de acesso, painel e portal. Sem biblioteca e sem rede.
 *
 * 1. COPIAR: todo <pre data-vc-copiar> ganha o botão "Copiar"; ao copiar, o botão diz "Copiado" (e o leitor de tela
 *    também, pelo aria-live).
 *
 * 2. SENHA: os campos marcados com data-vc-senha ganham o botão de revelar e ocultar.
 *      data-vc-senha="nova"       + o medidor de força (premia o COMPRIMENTO; recusa a lista local de senhas comuns e
 *                                   a que contém o nome, o usuário ou o e-mail)
 *      data-vc-senha="confirmar"  + o aviso de que as duas não conferem (data-vc-senha-de="#idDaNova")
 *      data-vc-senha="atual"      só o botão
 *    Dados pessoais da conferência: data-vc-senha-campos="#nome,#usuario,#email" (campos do mesmo formulário) e/ou
 *    data-vc-senha-valores='{"nome":"…","email":"…"}' (quando o formulário não os tem).
 *    As regras são as do servidor (core/Senha.php), que é quem decide; aqui elas só avisam antes de enviar.
 *    Colar e o gerenciador de senhas continuam funcionando: nada bloqueia o evento de colar nem troca o name do campo.
 */
(function () {
  'use strict';

  var MINIMO = 12;

  /* ---------------------------------------------------------------- copiar */
  function copiarTexto(texto) {
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(texto);
    }
    return new Promise(function (ok, falha) {
      var area = document.createElement('textarea');
      area.value = texto;
      area.setAttribute('readonly', '');
      area.style.position = 'fixed';
      area.style.opacity = '0';
      document.body.appendChild(area);
      area.select();
      try { document.execCommand('copy') ? ok() : falha(); } catch (e) { falha(e); }
      document.body.removeChild(area);
    });
  }

  function ligarCopiar(pre) {
    if (pre.dataset.vcCopiarLigado) return;
    pre.dataset.vcCopiarLigado = '1';
    var caixa = document.createElement('div');
    caixa.className = 'vc-copiar-caixa';
    pre.parentNode.insertBefore(caixa, pre);
    caixa.appendChild(pre);
    var botao = document.createElement('button');
    botao.type = 'button';
    botao.className = 'btn btn-sm vc-copiar-botao';
    botao.textContent = 'Copiar';
    botao.setAttribute('aria-label', 'Copiar o comando');
    var aviso = document.createElement('span');
    aviso.className = 'visually-hidden';
    aviso.setAttribute('aria-live', 'polite');
    caixa.appendChild(botao);
    caixa.appendChild(aviso);
    var tempo = null;
    botao.addEventListener('click', function () {
      copiarTexto(pre.innerText.replace(/\s+$/, '')).then(function () {
        botao.textContent = 'Copiado';
        botao.classList.add('vc-copiado');
        aviso.textContent = 'Copiado';
      }, function () {
        botao.textContent = 'Selecione e copie';
        var r = document.createRange();
        r.selectNodeContents(pre);
        var s = window.getSelection();
        s.removeAllRanges();
        s.addRange(r);
      });
      clearTimeout(tempo);
      tempo = setTimeout(function () {
        botao.textContent = 'Copiar';
        botao.classList.remove('vc-copiado');
        aviso.textContent = '';
      }, 2500);
    });
  }

  /* ----------------------------------------------------------------- senha */
  var ACENTOS = { 'á': 'a', 'à': 'a', 'â': 'a', 'ã': 'a', 'ä': 'a', 'é': 'e', 'è': 'e', 'ê': 'e', 'ë': 'e', 'í': 'i',
    'ì': 'i', 'î': 'i', 'ï': 'i', 'ó': 'o', 'ò': 'o', 'ô': 'o', 'õ': 'o', 'ö': 'o', 'ú': 'u', 'ù': 'u', 'û': 'u', 'ü': 'u',
    'ç': 'c', 'ñ': 'n' };

  function normalizar(t) {
    return String(t || '').toLowerCase().replace(/[áàâãäéèêëíìîïóòôõöúùûüçñ]/g, function (c) { return ACENTOS[c]; })
      .replace(/\s+/g, '');
  }

  function pedacosPessoais(valores) {
    var pedacos = [];
    Object.keys(valores).forEach(function (chave) {
      var v = String(valores[chave] || '').trim();
      if (!v) return;
      if (chave === 'email' && v.indexOf('@') > -1) v = v.split('@')[0];
      pedacos.push(normalizar(v));
      v.split(/[\s._\-@]+/).forEach(function (p) { pedacos.push(normalizar(p)); });
    });
    return pedacos.filter(function (p, i) { return p.length >= 4 && pedacos.indexOf(p) === i; });
  }

  /* As mesmas recusas do core/Senha.php. Devolve {nivel 0..4, texto, recusada}. */
  function avaliar(senha, valores) {
    var comuns = window.VC_SENHAS_COMUNS || [];
    var n = normalizar(senha);
    var nucleo = n.replace(/[^a-z]+$/, '');
    var tamanho = Array.from(senha).length;
    if (!senha) return { nivel: 0, texto: '', recusada: true };
    if (comuns.indexOf(n) > -1 || (nucleo.length >= 4 && comuns.indexOf(nucleo) > -1) || /^(.)\1+$/.test(n)) {
      return { nivel: 0, texto: 'Muito comum: fácil de adivinhar. Escolha outra.', recusada: true };
    }
    var pedacos = pedacosPessoais(valores);
    for (var i = 0; i < pedacos.length; i++) {
      if (n.indexOf(pedacos[i]) > -1) {
        return { nivel: 0, texto: 'Não use o seu nome, o seu usuário ou o seu e-mail na senha.', recusada: true };
      }
    }
    if (tamanho < MINIMO) {
      return { nivel: 1, texto: 'Curta demais: faltam ' + (MINIMO - tamanho) + ' caracteres.', recusada: true };
    }
    var distintos = new Set(Array.from(n)).size;
    var nivel = tamanho >= 20 ? 4 : (tamanho >= 16 ? 3 : 2);
    if (distintos < 6 && nivel > 2) nivel = 2;
    return { nivel: nivel, texto: ['', '', 'Aceitável', 'Boa', 'Forte'][nivel], recusada: false };
  }

  function valoresPessoais(campo) {
    var valores = {};
    try { Object.assign(valores, JSON.parse(campo.dataset.vcSenhaValores || '{}')); } catch (e) { /* sem valores */ }
    (campo.dataset.vcSenhaCampos || '').split(',').forEach(function (sel) {
      sel = sel.trim();
      if (!sel) return;
      var el = document.querySelector(sel);
      if (el && el.value) valores[sel] = el.value;
    });
    return valores;
  }

  function botaoRevelar(campo) {
    var grupo = campo.closest('.input-group');
    if (!grupo) {
      grupo = document.createElement('div');
      grupo.className = 'input-group input-group-flat vc-senha-grupo';
      campo.parentNode.insertBefore(grupo, campo);
      grupo.appendChild(campo);
    }
    var lado = document.createElement('span');
    lado.className = 'input-group-text';
    var botao = document.createElement('button');
    botao.type = 'button';
    botao.className = 'btn btn-link link-secondary p-0 vc-senha-revelar';
    botao.textContent = 'Mostrar';
    botao.setAttribute('aria-label', 'Mostrar a senha');
    botao.setAttribute('aria-pressed', 'false');
    lado.appendChild(botao);
    grupo.appendChild(lado);
    botao.addEventListener('click', function () {
      var mostrar = campo.type === 'password';
      campo.type = mostrar ? 'text' : 'password';
      botao.textContent = mostrar ? 'Ocultar' : 'Mostrar';
      botao.setAttribute('aria-label', mostrar ? 'Ocultar a senha' : 'Mostrar a senha');
      botao.setAttribute('aria-pressed', mostrar ? 'true' : 'false');
      campo.focus();
    });
    /* Ao enviar, volta a ser campo de senha: o navegador e o gerenciador de senhas só oferecem guardar assim. */
    if (campo.form) {
      campo.form.addEventListener('submit', function () {
        campo.type = 'password';
        botao.textContent = 'Mostrar';
        botao.setAttribute('aria-pressed', 'false');
      });
    }
    return grupo;
  }

  function ligarSenha(campo) {
    if (campo.dataset.vcSenhaLigado) return;
    campo.dataset.vcSenhaLigado = '1';
    var grupo = botaoRevelar(campo);
    var tipo = campo.dataset.vcSenha;
    if (tipo === 'nova') {
      var medidor = document.createElement('div');
      medidor.className = 'vc-senha-medidor mt-2';
      medidor.innerHTML = '<div class="progress progress-sm" aria-hidden="true"><div class="progress-bar"></div></div>'
        + '<div class="small mt-1 vc-senha-texto" aria-live="polite"></div>';
      grupo.parentNode.insertBefore(medidor, grupo.nextSibling);
      var barra = medidor.querySelector('.progress-bar');
      var texto = medidor.querySelector('.vc-senha-texto');
      var atualizar = function () {
        var r = avaliar(campo.value, valoresPessoais(campo));
        var cores = ['bg-danger', 'bg-danger', 'bg-warning', 'bg-success', 'bg-success'];
        barra.className = 'progress-bar ' + cores[r.nivel];
        barra.style.width = campo.value ? Math.max(8, r.nivel * 25) + '%' : '0%';
        texto.textContent = campo.value ? r.texto : '';
        texto.className = 'small mt-1 vc-senha-texto ' + (r.recusada ? 'text-danger' : 'text-secondary');
        medidor.dataset.nivel = String(r.nivel);
        medidor.dataset.recusada = r.recusada ? '1' : '0';
        campo.dispatchEvent(new CustomEvent('vc-senha-avaliada', { detail: r }));
      };
      ['input', 'change', 'paste', 'keyup'].forEach(function (ev) {
        campo.addEventListener(ev, function () { setTimeout(atualizar, 0); });
      });
      (campo.dataset.vcSenhaCampos || '').split(',').forEach(function (sel) {
        var el = sel.trim() ? document.querySelector(sel.trim()) : null;
        if (el) el.addEventListener('input', atualizar);
      });
      if (campo.value) atualizar();
    } else if (tipo === 'confirmar' && campo.dataset.vcSenhaDe) {
      var nova = document.querySelector(campo.dataset.vcSenhaDe);
      var aviso = document.createElement('div');
      aviso.className = 'small mt-1 text-danger vc-senha-confere';
      aviso.setAttribute('aria-live', 'polite');
      grupo.parentNode.insertBefore(aviso, grupo.nextSibling);
      var conferir = function () {
        aviso.textContent = (campo.value && nova && campo.value !== nova.value) ? 'As duas senhas não são iguais.' : '';
      };
      campo.addEventListener('input', conferir);
      if (nova) nova.addEventListener('input', conferir);
    }
  }

  function ligar(raiz) {
    (raiz || document).querySelectorAll('pre[data-vc-copiar]').forEach(ligarCopiar);
    (raiz || document).querySelectorAll('input[data-vc-senha]').forEach(ligarSenha);
  }

  window.vcComponentes = { ligar: ligar, avaliarSenha: avaliar };
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { ligar(); });
  } else {
    ligar();
  }
})();
