<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* VCOSTAEDU (Etapa 3): convertida para Tabler. Campo email e botão RecupLogin como antes. */
if (isset($this->dados['form'])) {
  $valorForm = $this->dados['form'];
}
?>
<div class="container container-tight py-4">
  <?php include 'app/adms/Views/include_tabler/_acesso_marca.php'; ?>
  <?php
  if (isset($_SESSION['msg'])) {
    echo $_SESSION['msg'];
    unset($_SESSION['msg']);
  }
  ?>
  <div class="card card-md">
    <div class="card-body">
      <h2 class="h2 text-center mb-1">Esqueci a senha</h2>
      <p class="text-secondary text-center mb-4">Informe o seu e-mail: o sistema manda um link para você escolher uma senha nova.</p>
      <form action="" method="POST" novalidate>
        <div class="mb-3">
          <label class="form-label" for="email">E-mail</label>
          <div class="input-group input-group-flat">
            <input type="email" name="email" id="email" class="form-control" placeholder="E-mail para recuperar" value="<?= htmlspecialchars((string) ((isset($valorForm['email']) ? $valorForm['email'] : '')), ENT_QUOTES, 'UTF-8') ?>" required>
            <span class="input-group-text"><i class="far fa-envelope" aria-hidden="true"></i></span>
          </div>
        </div>
        <div class="form-footer">
          <input type="submit" class="btn btn-primary w-100 botaoEnvia" name="RecupLogin" value="Recuperar">
        </div>
      </form>
    </div>
  </div>
  <div class="text-center text-secondary mt-3">
    <a href="<?= URLADM . "login/acesso" ?>" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'">Lembrei a senha</a>
  </div>
  <?php include 'app/adms/Views/include_tabler/_acesso_rodape.php'; ?>
</div>
