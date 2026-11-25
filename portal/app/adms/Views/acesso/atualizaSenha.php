<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* VCOSTAEDU (Etapa 6): convertida para Tabler. O token no corpo do POST (E7), os campos senha e senha_repetido, o
   #msgError (as duas senhas diferentes, pelo funcaologin.js) e o botão AtualizaSenha como antes. */
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
      <h2 class="h2 text-center mb-3">Nova senha</h2>
      <p class="text-secondary mb-4">Olá, <?= htmlspecialchars((string) ($this->dados['info']['nome'] ?? '')) ?>. Defina a sua senha do portal.</p>
      <form action="<?= URLACAD ?>login/atualizar-senha" method="POST" novalidate>
        <input type="hidden" name="token" value="<?= htmlspecialchars((string) ($this->dados['token'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?php /* E7: o token vai no corpo do POST, não na URL */ ?>
        <span id="msgError"></span>
        <div class="mb-3">
          <label class="form-label" for="senha">Nova senha</label>
          <input type="password" id="senha" name="senha" class="form-control" placeholder="Nova Senha" required<?= \VcSenha::atributos('nova', ['nome' => (string) ($this->dados['info']['nome'] ?? '')]) ?>>
          <div class="form-hint"><?= htmlspecialchars(\VcSenha::ajuda(), ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <div class="mb-3">
          <label class="form-label" for="senha_repetido">Repetir a senha</label>
          <input type="password" id="senha_repetido" name="senha_repetido" class="form-control" placeholder="Repetir a Senha" required<?= \VcSenha::atributos('confirmar', [], '', '#senha') ?>>
        </div>
        <div class="form-footer">
          <input type="submit" id="btnAtualizaSenha" class="btn btn-primary w-100" name="AtualizaSenha" value="Atualizar Senha">
        </div>
      </form>
    </div>
  </div>
  <div class="text-center text-secondary mt-3">
    <a href="<?= URLACAD . "login/acesso/" ?>" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'">Lembrei a senha</a>
  </div>
  <?php include 'app/adms/Views/include_tabler/_acesso_rodape.php'; ?>
</div>
