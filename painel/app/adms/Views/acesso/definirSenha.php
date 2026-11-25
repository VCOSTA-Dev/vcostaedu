<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* E7 (D-70): o link de senha traz o token no FRAGMENTO (#t=…). O navegador não envia o fragmento ao servidor, então o
   token não aparece em nenhum access log. Aqui o JavaScript o lê, tira da barra de endereço e o envia por POST.
   VCOSTAEDU (Etapa 3): convertida para Tabler; ids #lendoLink, #semJs, #formToken e #tokenLink como antes. */
?>
<div class="container container-tight py-4">
  <?php include 'app/adms/Views/include_tabler/_acesso_marca.php'; ?>
  <div class="card card-md">
    <div class="card-body">
      <h2 class="h2 text-center mb-3">Definir a senha</h2>
      <p class="text-secondary text-center" id="lendoLink">Abrindo o link para trocar a senha…</p>
      <noscript><div class="alert alert-warning" id="semJs">Para definir a senha, o navegador precisa do <b>JavaScript ligado</b>. Ligue o JavaScript e abra o link de novo, ou peça um link novo.</div></noscript>
      <form id="formToken" method="POST" action="<?= URLADM ?>login/atualizar-senha"><input type="hidden" name="recuperar_senha" id="tokenLink" value=""></form>
      <p class="text-center mb-0"><a href="<?= URLADM ?>login/acesso">Voltar à entrada</a></p>
    </div>
  </div>
  <?php include 'app/adms/Views/include_tabler/_acesso_rodape.php'; ?>
</div>
<script>
  (function () {
    var m = /(?:^|[#&])t=([A-Za-z0-9]{16,128})(?:&|$)/.exec(window.location.hash || '');
    if (window.history && history.replaceState) { history.replaceState(null, '', window.location.pathname); }
    if (!m) { document.getElementById('lendoLink').textContent = 'Link incompleto ou inválido: abra o link recebido por inteiro, ou peça um novo.'; return; }
    document.getElementById('tokenLink').value = m[1];
    document.getElementById('formToken').submit();
  })();
</script>
