/*
 * A JANELA DE PROGRESSO (Onda 3, Etapa 4): uma operação longa em passos curtos, um chamado por passo.
 * O botão que começa diz data-progresso="<id da janela>" e, opcionalmente, data-fase="iniciar" (começar) ou
 * data-fase="passo" (continuar o que parou). O servidor responde, a cada passo, o estado: passo, total, o grupo atual,
 * "falhou" (com o passo e o motivo) ou "concluida". Sem dependência de biblioteca.
 */
(function () {
  'use strict';

  function json(url, dados) {
    var corpo = new URLSearchParams();
    Object.keys(dados).forEach(function (k) { corpo.append(k, dados[k]); });
    return fetch(url, { method: 'POST', body: corpo, credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
      .then(function (r) {
        return r.json().catch(function () { return { erro: 'O servidor não respondeu como esperado (HTTP ' + r.status + ').' }; });
      })
      .catch(function () { return { erro: 'Sem resposta do servidor. Confira a conexão e clique em Tentar de novo.', rede: true }; });
  }

  function Janela(el) {
    this.el = el;
    this.url = el.getAttribute('data-url');
    this.campos = JSON.parse(el.getAttribute('data-campos') || '{}');
    this.grupos = Array.prototype.slice.call(el.querySelectorAll('[data-grupo]'));
  }

  Janela.prototype.abrir = function () {
    this.el.hidden = false;
    document.body.classList.add('vc-progresso-aberto');
    window.addEventListener('beforeunload', this.segurar = function (e) { e.preventDefault(); e.returnValue = ''; });
  };

  Janela.prototype.soltar = function () {
    if (this.segurar) { window.removeEventListener('beforeunload', this.segurar); this.segurar = null; }
  };

  /* data-navegar: no fim (concluída, ou recusada), abre o endereço do fim — o resultado vem na mensagem da página */
  Janela.prototype.navegar = function (r) {
    var fim = this.el.querySelector('.vc-progresso-fim');
    if (this.el.hasAttribute('data-navegar') && fim && (r.concluida || (r.erro && !r.rede))) { window.location.href = fim.href; }
  };

  Janela.prototype.mostrar = function (r) {
    var el = this.el;
    var total = r.total || 1;
    var feitos = r.concluida ? total : (r.passo || 0);
    el.querySelector('.vc-progresso-barra').style.width = Math.round(100 * feitos / total) + '%';
    el.querySelector('.progress').setAttribute('aria-valuenow', String(Math.round(100 * feitos / total)));
    el.querySelector('.vc-progresso-conta').textContent = r.concluida ? 'Concluído: ' + total + ' de ' + total + ' passos.' : 'Passo ' + Math.min(feitos + 1, total) + ' de ' + total + (r.grupoAtual ? ' — ' + r.grupoAtual : '');
    /* cada grupo: concluído quando todos os passos dele passaram; o atual gira */
    var antes = 0;
    this.grupos.forEach(function (li) {
      var n = parseInt(li.getAttribute('data-passos'), 10) || 1;
      var marca = li.querySelector('.vc-progresso-marca');
      /* concluída, todo grupo está feito (B12, D-101: o grupo de tamanho desconhecido, como o "um a um" do enturmar,
         continuava girando depois do "Pronto") */
      var feito = !!r.concluida || feitos >= antes + n;
      var atual = !feito && feitos >= antes;
      li.classList.toggle('feito', feito);
      li.classList.toggle('atual', atual && !r.falhou);
      li.classList.toggle('falhou', atual && !!r.falhou);
      marca.innerHTML = feito ? '<i class="fas fa-circle-check text-success"></i>' : (atual ? (r.falhou ? '<i class="fas fa-circle-xmark text-danger"></i>' : '<span class="spinner-border spinner-border-sm text-primary"></span>') : '<i class="far fa-circle text-secondary"></i>');
      li.setAttribute('aria-current', atual ? 'step' : 'false');
      antes += n;
    });
    var falha = el.querySelector('.vc-progresso-falha');
    falha.hidden = !(r.falhou || r.erro);
    /* um passo que falhou: Tentar de novo; uma recusa logo no começo (a senha, a frase, uma trava): Fechar */
    el.querySelector('.vc-progresso-tentar').hidden = !!(r.erro && !r.falhou && !r.rede);
    el.querySelector('.vc-progresso-fechar').hidden = !(r.erro && !r.falhou && !r.rede);
    el.querySelector('.vc-progresso-falha b').hidden = !r.falhou;
    if (r.falhou || r.erro) {
      el.querySelector('.vc-progresso-falha-passo').textContent = r.falhou ? String(Math.min(feitos + 1, total)) + ' de ' + total + (r.grupoAtual ? ' (' + r.grupoAtual + ')' : '') : '';
      el.querySelector('.vc-progresso-falha-motivo').textContent = r.falhou ? r.falhou.motivo : r.erro;
    }
    el.querySelector('.vc-progresso-aviso').hidden = !!(r.concluida || r.falhou || r.erro);
    el.querySelector('.vc-progresso-pronto').hidden = !r.concluida;
    el.querySelector('.vc-progresso-rodape').hidden = !r.concluida;
    el.setAttribute('data-situacao', r.concluida ? 'concluida' : (r.falhou || r.erro ? 'falhou' : 'andando'));
    /* Onda 7: a página que precisa do que cada passo respondeu (os convites da turma) ouve este evento */
    el.dispatchEvent(new CustomEvent('vc-progresso-passo', { detail: r }));
  };

  Janela.prototype.seguir = function () {
    var self = this;
    return json(this.url, { fase: 'passo' }).then(function (r) {
      if (r.ocupado) {
        /* o passo anterior ainda roda no servidor (a página foi fechada no meio dele): espera e segue */
        return new Promise(function (ok) { setTimeout(ok, 2000); }).then(function () { return self.seguir(); });
      }
      self.mostrar(r);
      if (r.concluida) { self.soltar(); self.navegar(r); return r; }
      if (r.falhou || r.erro) { self.soltar(); self.navegar(r); return r; }
      return self.seguir();
    });
  };

  Janela.prototype.comecar = function (fase) {
    var self = this;
    var dados = { fase: fase || 'iniciar' };
    Object.keys(this.campos).forEach(function (nome) {
      var c = document.querySelector(self.campos[nome]);
      if (c) { dados[nome] = c.type === 'checkbox' ? (c.checked ? c.value : '') : c.value; }
    });
    this.abrir();
    this.mostrar({ passo: 0, total: this.grupos.length });
    if (dados.fase === 'passo') { return this.seguir(); }
    return json(this.url, dados).then(function (r) {
      if (r.erro) { self.mostrar(r); self.soltar(); self.navegar(r); return r; }
      self.mostrar(r);
      return self.seguir();
    });
  };

  document.addEventListener('click', function (ev) {
    var b = ev.target.closest('[data-progresso]');
    if (b) {
      var form = b.closest('form');
      if (form && !form.reportValidity()) { return; }
      ev.preventDefault();
      var el = document.getElementById(b.getAttribute('data-progresso'));
      if (!el) { return; }
      el._janela = el._janela || new Janela(el);
      el._janela.comecar(b.getAttribute('data-fase') || 'iniciar');
      return;
    }
    var f = ev.target.closest('.vc-progresso-fechar');
    if (f) {
      var jf = f.closest('.vc-progresso');
      jf.hidden = true;
      document.body.classList.remove('vc-progresso-aberto');
      return;
    }
    var t = ev.target.closest('.vc-progresso-tentar');
    if (t) {
      var j = t.closest('.vc-progresso')._janela;
      if (j) { j.abrir(); j.seguir(); }
    }
  });
})();
