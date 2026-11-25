<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* VCOSTAEDU (Etapa 3): convertida para Tabler (sign-in centrado). Os nomes dos campos (usuario, senha, SendLogin),
   o id #usuario e as mensagens (#loginFalha, #loginEspera, que vêm do servidor) são os de sempre. */
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
      <h2 class="h2 text-center mb-1">Painel</h2>
      <p class="text-secondary text-center mb-4">Área restrita para professores e equipe</p>
      <form action="" method="POST" novalidate>
        <div class="mb-3">
          <label class="form-label" for="usuario">Usuário</label>
          <div class="input-group input-group-flat">
            <input type="text" name="usuario" id="usuario" class="form-control" placeholder="Usuário ou e-mail" autocomplete="username" value="<?= htmlspecialchars((string) ((isset($valorForm['usuario']) ? $valorForm['usuario'] : '')), ENT_QUOTES, 'UTF-8') ?>" required>
            <span class="input-group-text"><i class="fas fa-user" aria-hidden="true"></i></span>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label" for="senha">Senha</label>
          <div class="input-group input-group-flat">
            <input type="password" name="senha" id="senha" class="form-control" placeholder="Senha" required<?= \VcSenha::atributos('atual') ?>>
            <span class="input-group-text"><i class="fas fa-lock" aria-hidden="true"></i></span>
          </div>
        </div>
        <div class="form-footer">
          <input type="submit" class="btn btn-primary w-100 botaoEnvia" name="SendLogin" value="Acessar">
        </div>
      </form>
    </div>
  </div>
  <div class="text-center text-secondary mt-3">
    <a href="<?= URLADM . "login/esqueceu-senha" ?>" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'">Esqueci a senha</a>
  </div>
  <?php include 'app/adms/Views/include_tabler/_acesso_rodape.php'; ?>
</div>
