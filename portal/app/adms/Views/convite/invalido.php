<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* Onda 7 (D-63): o link que não vale (usado, vencido, cancelado, inventado) — a MESMA página para todos os casos, sem
   dizer se a pessoa existe. Com o limite de tentativas atingido, a espera. */
$espera = $this->dados['espera'] ?? null;
?>
<div class="container container-tight py-4">
  <?php include 'app/adms/Views/include_tabler/_acesso_marca.php'; ?>
  <div class="card card-md" id="conviteInvalido">
    <div class="card-body">
      <h2 class="h2 text-center mb-3">Este link não vale mais</h2>
      <?php if ($espera) : ?>
        <div class="alert alert-warning" id="conviteEspera"><?= htmlspecialchars((string) $espera, ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>
      <p>O link do convite vale uma vez só e tem prazo. Ele pode ter sido usado, ter vencido ou ter sido trocado por um convite mais novo.</p>
      <ul class="mb-3">
        <li>Se você já criou a sua senha, entre no portal com o seu e-mail ou CPF.</li>
        <li>Se ainda não criou, peça um convite novo à secretaria da escola.</li>
      </ul>
      <a href="<?= URLACAD ?>login/acesso" class="btn btn-primary w-100">Ir para a entrada do portal</a>
    </div>
  </div>
  <?php include 'app/adms/Views/include_tabler/_acesso_rodape.php'; ?>
</div>
