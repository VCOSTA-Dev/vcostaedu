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
  <?php ob_start(); ?><a href="<?= URLACAD . "perfil/visualizar" ?>" class="btn btn-outline-primary rounded-circle" title="Voltar para listar usuários"><i class="fas fa-arrow-left"></i></a> <?= htmlspecialchars((string) ($valorForm['nome']), ENT_QUOTES, 'UTF-8') ?><?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php
      if (isset($_SESSION['msg'])) {
        echo $_SESSION['msg'];
        unset($_SESSION['msg']);
      }
      ?>
      <div class="row">
        <div class="col-md-3">
          <div class="card shadow">
            <div class="card-body box-profile">
              <div class="text-center">
                <img class="profile-user-img img-fluid rounded-circle" src="<?= htmlspecialchars((string) (URLACAD . "assets/img/academicos/" . (!empty($valorForm['imagem']) ? "{$valorForm['id']}/{$valorForm['imagem']}" : "icone_usuario.png")), ENT_QUOTES, 'UTF-8') ?>" alt="Imagem perfil do usuário">
              </div>
              <h3 class="profile-username text-center"><?= htmlspecialchars((string) ($valorForm['nome']), ENT_QUOTES, 'UTF-8') ?></h3>
              <hr>
              <?php
              if ($this->dados['botao']['vis_perfil']) {
              ?>
                <a href="<?= URLACAD . "perfil/visualizar/"; ?>" class="btn w-100 btn-outline-primary" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'"><?= ($_SESSION['academico_nivel'] == 1 ? "<i class='fas fa-user-graduate'></i>" : "<i class='fas fa-user-tie'></i>")  ?> Visualizar</a>
              <?php
              }
              if ($this->dados['botao']['edit_perfil']) {
              ?>
              <a href="<?= URLACAD . "perfil/editar/"; ?>" class="btn w-100 btn-outline-warning" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'"><i class="fas fa-user-edit"></i> Editar</a>
              <?php
              }
              ?>
              <span class="btn w-100 btn-danger" style="pointer-events: none;"><i class="fas fa-lock-open"></i> Editar Senha</span>
            </div>
          </div>
        </div>
        <div class="col-md-9">
          <div class="card shadow">
            <form method="POST" action="" enctype="multipart/form-data" novalidate>
              <div class="card-body">
                <input type="hidden" name="id" value="<?= htmlspecialchars((string) ($valorForm['id']), ENT_QUOTES, 'UTF-8') ?>" required>
                <div class="row g-2">
                  <div class="mb-3 col-12">
                    <label class="form-label" for="senha">Senha</label>
                    <input type="password" id="senha" name="senha" class="form-control" placeholder="Nova Senha" required<?= \VcSenha::atributos('nova', ['nome' => (string) ($_SESSION['academico_nome'] ?? ''), 'email' => (string) ($_SESSION['academico_email'] ?? '')]) ?>>
                    <div class="form-hint"><?= htmlspecialchars(\VcSenha::ajuda(), ENT_QUOTES, 'UTF-8') ?></div>
                  </div>
                </div>
              </div>
              <div class="card-footer">
                <a href="<?= URLACAD . "perfil/visualizar" ?>" class="btn btn-secondary" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'">Cancelar</a>
                <input type="submit" class="btn btn-primary botaoEnvia" name="EditSenhaPerfil" value="Atualizar">
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>