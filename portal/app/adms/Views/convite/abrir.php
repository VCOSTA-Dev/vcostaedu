<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* Onda 7 (D-63): o link do convite traz o código no FRAGMENTO (#c=…). O navegador não envia o fragmento ao servidor, então
   o código não aparece em nenhum registro de acesso. Aqui o JavaScript o lê, tira da barra de endereço e o envia por POST. */
?>
<div class="container container-tight py-4">
  <?php include 'app/adms/Views/include_tabler/_acesso_marca.php'; ?>
  <div class="card card-md">
    <div class="card-body">
      <h2 class="h2 text-center mb-3">Convite para o portal</h2>
      <p class="text-secondary text-center" id="lendoConvite">Abrindo o convite…</p>
      <noscript><div class="alert alert-warning" id="semJs">Para abrir o convite, o navegador precisa do <b>JavaScript ligado</b>. Ligue o JavaScript e abra o link de novo.</div></noscript>
      <form id="formConvite" method="POST" action="<?= URLACAD ?>convite/conferir"><input type="hidden" name="codigo" id="codigoConvite" value=""></form>
      <p class="text-center mb-0"><a href="<?= URLACAD ?>login/acesso">Ir para a entrada do portal</a></p>
    </div>
  </div>
  <?php include 'app/adms/Views/include_tabler/_acesso_rodape.php'; ?>
</div>
<script>
  (function () {
    var m = /(?:^|[#&])c=([A-Fa-f0-9]{48})(?:&|$)/.exec(window.location.hash || '');
    if (window.history && history.replaceState) { history.replaceState(null, '', window.location.pathname); }
    if (!m) { document.getElementById('lendoConvite').textContent = 'Link incompleto: abra o link recebido por inteiro. Se não der certo, peça um novo convite à secretaria da escola.'; return; }
    document.getElementById('codigoConvite').value = m[1];
    document.getElementById('formConvite').submit();
  })();
</script>
