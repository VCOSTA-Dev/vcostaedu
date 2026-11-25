<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
if (isset($this->dados['form'])) {
  $valorForm = $this->dados['form'];
}
if (isset($this->dados['form'][0])) {
  $valorForm = $this->dados['form'][0];
}
?>
<div class="vc-pagina">
  <?php ob_start(); ?><a href="<?= htmlspecialchars((string) (URLADM . "usuarios/visualizar/{$valorForm['id']}"), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary rounded-circle" title="Voltar para o usuário"><i class="fas fa-arrow-left"></i></a> <?= htmlspecialchars((string) ($valorForm['nome']), ENT_QUOTES, 'UTF-8') ?><?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php
      if (isset($_SESSION['msg'])) {
        echo $_SESSION['msg'];
        unset($_SESSION['msg']);
      }
      ?>
      <div class="row">
        <div class="col-md-12">
          <div class="card">
            <div class="card-body">
              <a href="<?= URLADM . "usuarios/listar" ?>" class="btn btn-outline-info" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'"><i class="fas fa-list-ol"></i> Usuários</a>
              <?php
              if ($this->dados['botao']['vis_user']) {
              ?>
              <?php
              }
              if ($this->dados['botao']['edit_user']) {
              ?>
                <a href="<?= htmlspecialchars((string) (URLADM . "usuarios/editar/{$valorForm['id']}"), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-warning" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'"><i class="fas fa-user-edit"></i> Editar</a>
              <?php
              }
              ?>
            </div>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-12">
          <div class="card card-default">
            <form action="" method="post" novalidate>
              <input type="hidden" name="id" value="<?= htmlspecialchars((string) ((isset($valorForm['id']) ? $valorForm['id'] : '')), ENT_QUOTES, 'UTF-8') ?>">
              <div class="card-body">
                <div class="row g-2">
                  <div class="mb-3 col-md-12">
                    <label class="form-label" for="nome">Senha</label>
                    <input type="password" class="form-control" name="senha" id="senha" placeholder="Senha" required<?= \VcSenha::atributos('nova', ['nome' => (string) ($valorForm['nome'] ?? ''), 'usuario' => (string) ($valorForm['usuario'] ?? ''), 'email' => (string) ($valorForm['email'] ?? '')]) ?>>
                    <div class="form-hint"><?= htmlspecialchars(\VcSenha::ajuda(), ENT_QUOTES, 'UTF-8') ?></div>
                  </div>
                </div>
              </div>
              <div class="card-footer">
                <input type="submit" class="btn btn-primary botaoEnvia" name="EditSenhaUsuario" value="Salvar">
                <a href="<?= htmlspecialchars((string) (URLADM . "usuarios/visualizar/{$valorForm['id']}"), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secondary ms-2" onclick='this.style.pointerEvents = "none"; this.textContent = "Processando..."'>Cancelar</a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>