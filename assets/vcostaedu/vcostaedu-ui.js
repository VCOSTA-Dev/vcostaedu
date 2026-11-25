/*
 * VCOSTAEDU — comportamento comum (E8c). Painel e portal.
 * D-262: menu lateral ABERTO em telas >= 1200 px e recolhido abaixo; a escolha do usuário (botão ☰) fica guardada
 * no navegador (localStorage, com try/catch: navegador que bloqueia o armazenamento só perde a lembrança).
 * O estado inicial é aplicado cedo por um script no início do <body> (no <body>), para não piscar.
 */
/* VCOSTAEDU (Etapa 8): a lembrança do menu do AdminLTE (eventos lte.pushmenu) saiu com ele; o rail do Tabler guarda o
   recolhido sozinho (vcostaedu-tabler.js, chave vc-side-mini). */

/*
 * A CONFIRMAÇÃO DO SISTEMA (D-99; em todo o sistema desde a correção da escola de 22/11, D-100): uma janela do próprio
 * sistema no lugar da do navegador (window.confirm), que deixava a tela com cara de travada.
 *   - <form data-vc-confirma="Pergunta"> pede a confirmação ao enviar (data-vc-confirma-extra: o detalhe, em HTML que a
 *     própria tela monta e escapa);
 *   - <button data-vc-confirma="Pergunta"> (com ou sem formaction) pede a confirmação ao ser clicado: a confirmação é só
 *     daquele botão (o "Fechar a etapa" ao lado do "Gravar", por exemplo).
 * Confirmado, o pedido vai uma vez só (os botões travam). A janela abre pelo caminho de dados do Bootstrap (o pacote do
 * Tabler não expõe o objeto "bootstrap" na página).
 */
(function () {
  if (window.vcConfirmaPronto) { return; }
  window.vcConfirmaPronto = true;

  /* abre uma janela (modal) pelo caminho de dados do Bootstrap */
  window.vcAbrirModal = function (id) {
    var b = document.createElement('button');
    b.type = 'button';
    b.hidden = true;
    b.setAttribute('data-bs-toggle', 'modal');
    b.setAttribute('data-bs-target', '#' + id);
    document.body.appendChild(b);
    b.click();
    b.remove();
  };

  var pendente = null;
  function janela() {
    var m = document.getElementById('vcConfirmar');
    if (m) { return m; }
    var d = document.createElement('div');
    d.innerHTML = '<div class="modal modal-blur fade" id="vcConfirmar" tabindex="-1" role="dialog" aria-labelledby="vcConfirmarTitulo" aria-hidden="true">'
      + '<div class="modal-dialog modal-sm modal-dialog-centered" role="document"><div class="modal-content"><div class="modal-status bg-primary"></div>'
      + '<div class="modal-body text-center py-4"><h3 class="mb-2" id="vcConfirmarTitulo">Confirmar</h3><div class="text-secondary" id="vcConfirmarTexto"></div></div>'
      + '<div class="modal-footer"><div class="w-100"><div class="row">'
      + '<div class="col"><button type="button" class="btn w-100" data-bs-dismiss="modal" id="vcConfirmarNao">Voltar</button></div>'
      + '<div class="col"><button type="button" class="btn btn-primary w-100" id="vcConfirmarSim">Confirmar</button></div>'
      + '</div></div></div></div></div></div>';
    m = d.firstChild;
    document.body.appendChild(m);
    document.getElementById('vcConfirmarSim').addEventListener('click', function () {
      if (!pendente) { return; }
      var p = pendente;
      pendente = null;
      this.disabled = true;
      if (p.botao && p.porBotao) { // a confirmação era do botão: ele vai de novo, já confirmado (com o formaction dele)
        p.botao.dataset.vcConfirmado = '1';
        p.botao.click();
        return;
      }
      p.form.dataset.vcConfirmado = '1';
      if (p.form.requestSubmit) { p.form.requestSubmit(p.botao || undefined); } else { p.form.submit(); }
    });
    m.addEventListener('hidden.bs.modal', function () { document.getElementById('vcConfirmarSim').disabled = false; pendente = null; });
    return m;
  }
  function perguntar(titulo, extra, rotulo) {
    janela();
    document.getElementById('vcConfirmarTitulo').textContent = titulo;
    document.getElementById('vcConfirmarTexto').innerHTML = extra || '';
    document.getElementById('vcConfirmarSim').textContent = rotulo || 'Confirmar';
    document.getElementById('vcConfirmarSim').disabled = false;
    window.vcAbrirModal('vcConfirmar');
  }
  /* trava os botões logo depois de o envio começar (dentro do evento, o botão travado sairia do envio, com o valor dele) */
  function travar(form) {
    if (form) { setTimeout(function () { form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(function (b) { b.disabled = true; }); }, 0); }
  }

  /* o botão com a pergunta dele */
  document.addEventListener('click', function (ev) {
    var b = ev.target.closest ? ev.target.closest('button[data-vc-confirma], input[type="submit"][data-vc-confirma]') : null;
    if (!b || b.disabled) { return; }
    if (b.dataset.vcConfirmado === '1') { b.dataset.vcConfirmado = ''; if (b.form) { b.form.dataset.vcConfirmado = '1'; } return; }
    if (b.form && !b.form.checkValidity()) { return; } // o navegador diz o que falta, como sempre
    ev.preventDefault();
    pendente = { form: b.form, botao: b, porBotao: true };
    perguntar(b.dataset.vcConfirma, b.dataset.vcConfirmaExtra, b.dataset.vcRotulo);
  }, true);

  /* o formulário com a pergunta dele (na volta do evento: depois do que a própria tela confere e preenche) */
  document.addEventListener('submit', function (ev) {
    var f = ev.target;
    if (!f.matches || !f.matches('form[data-vc-confirma]')) {
      if (f.dataset && f.dataset.vcConfirmado === '1') { travar(f); }
      return;
    }
    if (f.dataset.vcConfirmado === '1') { travar(f); return; }
    if (ev.defaultPrevented) { return; }
    ev.preventDefault();
    pendente = { form: f, botao: ev.submitter || null, porBotao: false };
    perguntar(f.dataset.vcConfirma, f.dataset.vcConfirmaExtra, ev.submitter && ev.submitter.dataset.vcRotulo);
  });
})();
