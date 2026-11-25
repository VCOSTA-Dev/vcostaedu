<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* VCOSTAEDU (Etapa 3): convertida para Tabler. O token oculto (recuperar_senha), o campo senha e o botão
   AtualizaSenha como antes. */
if (isset($this->dados['form'])) {
  $valorForm = $this->dados['form'];
}
?>
<div class="container container-tight py-4">
  <?php include 'app/adms/Views/include_tabler/_acesso_marca.php'; ?>
  <div class="card card-md">
    <div class="card-body">
      <h2 class="h2 text-center mb-4">Nova senha</h2>
      <?php
      if (isset($_SESSION['msg'])) {
        echo $_SESSION['msg'];
        unset($_SESSION['msg']);
      }
      ?>
      <form action="<?= URLADM ?>login/atualizar-senha" method="POST" novalidate>
        <input type="hidden" name="recuperar_senha" value="<?= htmlspecialchars((string) $this->dados['info'][0]['recuperar_senha'], ENT_QUOTES, 'UTF-8') ?>">
        <div class="mb-3">
          <label class="form-label" for="senha">Nova senha</label>
          <input type="password" name="senha" id="senha" class="form-control" placeholder="Nova Senha" required<?= \VcSenha::atributos('nova') ?>>
          <div class="form-hint"><?= htmlspecialchars(\VcSenha::ajuda(), ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <div class="form-footer">
          <input type="submit" class="btn btn-primary w-100 botaoEnvia" name="AtualizaSenha" value="Atualizar Senha">
        </div>
      </form>
    </div>
  </div>
  <div class="text-center text-secondary mt-3">
    <a href="<?= URLADM . "login/acesso" ?>">Lembrei a senha</a>
  </div>
  <?php include 'app/adms/Views/include_tabler/_acesso_rodape.php'; ?>
</div>
