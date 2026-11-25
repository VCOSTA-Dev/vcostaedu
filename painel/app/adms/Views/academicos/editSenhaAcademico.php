<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
if (!empty($this->dados['info_user']['info'])) {
  $info = $this->dados['info_user'];
}
if (isset($this->dados['form'])) {
  $valorForm = $this->dados['form'];
}
if (isset($this->dados['form'][0])) {
  $valorForm = $this->dados['form'][0];
}
?>
<div class="vc-pagina">
  <?php ob_start(); ?><a href="<?= htmlspecialchars((string) (URLADM . "academicos/visualizar/{$valorForm['id']}"), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary rounded-circle" title="Voltar para o usuário"><i class="fas fa-arrow-left"></i></a> <?= htmlspecialchars((string) ($valorForm['nome']), ENT_QUOTES, 'UTF-8') ?><?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
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
                <img class="profile-user-img img-fluid rounded-circle" src="<?= htmlspecialchars((string) (URLACAD . "assets/img/academicos/" . (!empty($valorForm['imagem']) ? "{$valorForm['id']}/{$valorForm['imagem']}" : "icone_usuario.png")), ENT_QUOTES, 'UTF-8') ?>" alt="User profile picture">
              </div>
              <h3 class="profile-username text-center"><?= htmlspecialchars((string) ($valorForm['nome']), ENT_QUOTES, 'UTF-8') ?></h3>
              <p class="text-muted text-center"><?= htmlspecialchars((string) ($info['info'][0]['nivelAcesso']), ENT_QUOTES, 'UTF-8') ?></p>
              <hr>
              <?php
              if ($this->dados['botao']['vis_user']) {
              ?>
                <a href="<?= htmlspecialchars((string) (URLADM . "academicos/visualizar/{$valorForm['id']}"), ENT_QUOTES, 'UTF-8') ?>" class="btn w-100 btn-outline-primary" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'"><?= (isset($info['info'][0]['acessoId']) ? ($info['info'][0]['acessoId'] == 1 ? "<i class='fas fa-user-graduate'></i>" : "<i class='fas fa-user-tie'></i>") : "<i class='fas fa-user-graduate'></i>") ?> Visualizar</a>
              <?php
              }
              if ($this->dados['botao']['edit_user']) {
              ?>
                <a href="<?= htmlspecialchars((string) (URLADM . "academicos/editar/{$info['info'][0]['id']}"), ENT_QUOTES, 'UTF-8') ?>" class="btn w-100 btn-outline-warning" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'"><i class="fas fa-user-edit"></i> Editar</a>
              <?php
              }
              if ($this->dados['botao']['edit_senha']) {
              ?>
                <span class="btn w-100 btn-danger" style="pointer-events: none;"><i class="fas fa-lock-open"></i> Editar Senha</span>
              <?php
              }
              ?>
            </div>
          </div>

          <div class="card shadow">
            <div class="card-body">
              <strong><i class="fas fa-book me-1"></i> Inserido no sistema</strong>
              <p class="text-muted"><?= htmlspecialchars((string) ($info['info'][0]['criacao']), ENT_QUOTES, 'UTF-8') ?></p>
              <hr>
              <strong><i class="fas fa-map-marker-alt me-1"></i> Alterado</strong>
              <p class="text-muted"><?= htmlspecialchars((string) ($info['info'][0]['modificado']), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
          </div>
        </div>
        <div class="col-md-9">
          <div class="card shadow">
            <form action="" method="POST" enctype="multipart/form-data" novalidate>
              <input type="hidden" name="id" value="<?= htmlspecialchars((string) ((isset($valorForm['id']) ? $valorForm['id'] : '')), ENT_QUOTES, 'UTF-8') ?>">
              <div class="card-body">
                <div class="row g-2">
                  <div class="mb-3 col-12">
                    <label class="form-label" for="usuario">Usuário</label>
                    <input type="text" class="form-control" name="usuario" id="usuario" placeholder="Usuário" value="<?= htmlspecialchars((string) ((isset($valorForm['usuario']) && ($valorForm['usuario']) ? $valorForm['usuario'] : '')), ENT_QUOTES, 'UTF-8') ?>" required>
                  </div>
                </div>
                <div class="row g-2">
                  <div class="mb-3 col-md-12">
                    <label class="form-label" for="senha">Senha</label>
                    <input type="password" class="form-control" name="senha" id="senha" placeholder="Senha" required<?= \VcSenha::atributos('nova', ['nome' => (string) ($valorForm['nome'] ?? ''), 'email' => (string) ($valorForm['email'] ?? '')], '#usuario') ?>>
                    <div class="form-hint"><?= htmlspecialchars(\VcSenha::ajuda(), ENT_QUOTES, 'UTF-8') ?></div>
                  </div>
                </div>
              </div>
              <div class="card-footer">
                <input type="submit" class="btn btn-primary botaoEnvia" name="EditSenhaAcad" value="Salvar">
                <a href="<?= htmlspecialchars((string) (URLADM . "academicos/visualizar/{$valorForm['id']}"), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secondary" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'">Cancelar</a>
              </div>
            </form>
          </div>
        </div>
      </div>
  </section>
</div>