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
  <?php ob_start(); ?><a href="<?= URLADM . "perfil/visualizar" ?>" class="btn btn-outline-primary rounded-circle shadow" title="Voltar"><i class="fas fa-arrow-left"></i></a> Editar Meu Perfil<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
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
                <img class="profile-user-img img-fluid rounded-circle" src="<?= htmlspecialchars((string) (URLADM . (!empty($valorForm['imagem']) ? "assets/img/usuario/{$valorForm['id']}/{$valorForm['imagem']}" : "assets/img/usuario/icone_usuario.png")), ENT_QUOTES, 'UTF-8') ?>" alt="Imagem do Usuário">
              </div>
              <h3 class="profile-username text-center"><?= htmlspecialchars((string) ($valorForm['nome']), ENT_QUOTES, 'UTF-8') ?></h3>
              <p class="text-muted text-center"><?= htmlspecialchars((string) ($valorForm['nivelAcesso']), ENT_QUOTES, 'UTF-8') ?></p>
              <?php
              if ($this->dados['botao']['meu_perfil']) {
              ?>
              <?php
              }
              if ($this->dados['botao']['edit_senha']) {
              ?>
                <button type="button" class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#EditarSenhaPerfil"><i class="fas fa-lock-open"></i> Editar Senha</button>
              <?php
              }
              ?>
            </div>
          </div>
          <div class="card shadow">
            <div class="card-body">
              <strong><i class="far fa-star me-1"></i> Situação</strong>
              <p class="text-muted"><span class="badge bg-<?= htmlspecialchars((string) ($valorForm['corSituacao']), ENT_QUOTES, 'UTF-8') ?>-lt"><?= htmlspecialchars((string) ($valorForm['nomeSituacao']), ENT_QUOTES, 'UTF-8') ?></span></p>
              <hr>
              <strong><i class="fas fa-book me-1"></i> Inserido no sistema</strong>
              <p class="text-muted"><?= htmlspecialchars((string) ($valorForm['criacao']), ENT_QUOTES, 'UTF-8') ?></p>
              <hr>
              <strong><i class="fas fa-map-marker-alt me-1"></i> Alterado</strong>
              <p class="text-muted"><?= htmlspecialchars((string) ($valorForm['modificado']), ENT_QUOTES, 'UTF-8') ?></p>
              <hr>

            </div>
          </div>
        </div>
        <div class="col-md-9">
          <div class="card shadow">
            <form method="POST" action="" enctype="multipart/form-data" novalidate>
              <div class="card-body">
                <div class="row g-2">
                  <div class="mb-3 col-md-12">
                    <label class="form-label" for="nome">Nome Completo</label>
                    <input type="text" class="form-control" name="nome" id="nome" placeholder="Nome completo" value="<?= htmlspecialchars((string) ((isset($valorForm['nome']) ? $valorForm['nome'] : '')), ENT_QUOTES, 'UTF-8') ?>" required>
                  </div>
                </div>
                <div class="row g-2">
                  <div class="mb-3 col-md-6">
                    <label class="form-label" for="email">E-mail</label>
                    <input type="email" class="form-control" name="email" id="email" placeholder="E-mail" value="<?= htmlspecialchars((string) ((isset($valorForm['email']) ? $valorForm['email'] : '')), ENT_QUOTES, 'UTF-8') ?>" required>
                  </div>
                  <div class="mb-3 col-md-6">
                    <label class="form-label" for="genero_id">Sexo</label>
                    <select name="genero_id" id="genero_id" class="form-select select2" required>
                      <option value="">Selecione o sexo</option>
                      <?php
                      foreach ($this->dados['select']['generos'] as $gen) {
                        if ((isset($valorForm['genero_id']) && !empty($valorForm['genero_id'])) && $valorForm['genero_id'] == $gen['id']) {
                          echo "<option value='{$gen['id']}' selected>{$gen['nome']}</option>";
                        } else {
                          echo "<option value='{$gen['id']}'>{$gen['nome']}</option>";
                        }
                      }
                      ?>
                    </select>
                  </div>
                </div>
                <div class="row g-2">
                  <div class="mb-3 col-md-6">
                    <label class="form-label" for="telefone">Telefone</label>
                    <input type="telefone" class="form-control campotelefone" name="telefone" id="telefone" value="<?= htmlspecialchars((string) ((isset($valorForm['telefone']) ? $valorForm['telefone'] : '')), ENT_QUOTES, 'UTF-8') ?>">
                  </div>
                  <div class="mb-3 col-md-6">
                    <label class="form-label" for="celular">Celular</label>
                    <input type="celular" class="form-control campocelular" name="celular" id="celular" value="<?= htmlspecialchars((string) ((isset($valorForm['celular']) ? $valorForm['celular'] : '')), ENT_QUOTES, 'UTF-8') ?>">
                  </div>
                </div>
                <div class="row g-2">
                  <div class="mb-3 col-md-6">
                    <label class="form-label" for="usuario">Usuário</label>
                    <input type="text" class="form-control" name="usuario" id="usuario" placeholder="Usuário login" value="<?= htmlspecialchars((string) ((isset($valorForm['usuario']) ? $valorForm['usuario'] : '')), ENT_QUOTES, 'UTF-8') ?>" required>
                  </div>
                </div>
                <div class="row g-2">
                  <div class="mb-3 col-md-12">
                    <?php
                    if (isset($valorForm['imagem']) and !empty($valorForm['imagem'])) {
                      $imagem_antiga = URLADM . 'assets/img/usuario/' . $valorForm['id'] . '/' . $valorForm['imagem'];
                    } elseif (isset($valorForm['imagem_antiga']) and !empty($valorForm['imagem_antiga'])) {
                      $imagem_antiga = URLADM . 'assets/img/usuario/' . $valorForm['id'] . '/' . $valorForm['imagem_antiga'];
                    } else {
                      $imagem_antiga = URLADM . 'assets/img/usuario/preview_img.png';
                    }
                    ?>
                    <img src="<?php echo $imagem_antiga; ?>" alt="Imagem do Usuário" id="preview-user" class="img-thumbnail" style="width: 150px; height: 150px;">
                    <input name="imagem_antiga" type="hidden" value="<?= htmlspecialchars((string) ((isset($valorForm['imagem_antiga']) ? $valorForm['imagem_antiga'] : $valorForm['imagem'])), ENT_QUOTES, 'UTF-8') ?>">
                    <label class="form-label" for="imagem_nova"> Foto (150x150)</label>
                    <input name="imagem_nova" type="file" onchange="previewImagem();">

                  </div>
                </div>
              </div>
              <div class="card-footer">
                <input type="submit" class="btn btn-primary botaoEnvia" name="EditPerfil" value="Atualizar">
                <a href="<?= URLADM . "perfil/visualizar" ?>" class="btn btn-secondary" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'">Cancelar</a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<div class="modal fade" id="EditarSenhaPerfil" tabindex="-1" aria-labelledby="EditarSenhaPerfilLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">EDITAR SENHA</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="<?= URLADM . "perfil/editar-senha" ?>" enctype="multipart/form-data" novalidate>
        <div class="modal-body">
          <input type="hidden" name="id" value="<?= htmlspecialchars((string) ($valorForm['id']), ENT_QUOTES, 'UTF-8') ?>" required>
          <div class="mb-3">
            <label class="form-label" for="novaSenhaPerfil">Nova Senha</label>
            <input type="password" name="senha" id="novaSenhaPerfil" class="form-control" required<?= \VcSenha::atributos('nova', ['nome' => (string) ($_SESSION['colUsuario_nome'] ?? ''), 'email' => (string) ($_SESSION['colUsuario_email'] ?? '')]) ?>>
            <div class="form-hint"><?= htmlspecialchars(\VcSenha::ajuda(), ENT_QUOTES, 'UTF-8') ?></div>
          </div>
        </div>
        <div class="modal-footer">
          <input type="submit" name="EditSenhaPerfil" class="btn btn-primary botaoEnvia" value="Atualizar">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>