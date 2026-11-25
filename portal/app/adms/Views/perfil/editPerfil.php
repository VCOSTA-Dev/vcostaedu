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
  <?php ob_start(); ?><a href="<?= URLACAD . "perfil/visualizar" ?>" class="btn btn-outline-primary rounded-circle" title="Voltar"><i class="fas fa-arrow-left"></i></a> <?= htmlspecialchars((string) ($valorForm['nome']), ENT_QUOTES, 'UTF-8') ?><?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
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
              ?>
              <span class="btn w-100 btn-warning" style="pointer-events: none;"><i class="fas fa-user-edit"></i> Editar</span>
              <?php
              if ($this->dados['botao']['edit_senha']) {
              ?>
                <a href="<?= htmlspecialchars((string) (URLACAD . "perfil/editar-senha/{$valorForm['id']}"), ENT_QUOTES, 'UTF-8') ?>" class="btn w-100 btn-outline-danger" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'"><i class="fas fa-lock-open"></i> Editar Senha</a>
              <?php
              }
              ?>
            </div>
          </div>
        </div>

        <div class="col-md-9">
          <div class="card shadow">
            <form method="POST" action="" enctype="multipart/form-data" novalidate>
              <div class="card-body">
                <input type="hidden" name="id" value="<?= htmlspecialchars((string) ($valorForm['id']), ENT_QUOTES, 'UTF-8') ?>" required>
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
                  <div class="mb-3 col-md-12">
                    <?php
                    if (isset($valorForm['imagem']) and !empty($valorForm['imagem'])) {
                      $imagem_antiga = URLACAD . 'assets/img/academicos/' . $valorForm['id'] . '/' . $valorForm['imagem'];
                    } elseif (isset($valorForm['imagem_antiga']) and !empty($valorForm['imagem_antiga'])) {
                      $imagem_antiga = URLACAD . 'assets/img/academicos/' . $valorForm['id'] . '/' . $valorForm['imagem_antiga'];
                    } else {
                      $imagem_antiga = URLACAD . 'assets/img/academicos/preview_img.png';
                    }
                    ?>
                    <img src="<?php echo $imagem_antiga; ?>" alt="Imagem do Usuário" id="preview-user" class="img-thumbnail" style="width: 150px; height: 150px;">
                    <input name="imagem_antiga" type="hidden" value="<?= htmlspecialchars((string) ((isset($valorForm['imagem_antiga']) ? $valorForm['imagem_antiga'] : $valorForm['imagem'])), ENT_QUOTES, 'UTF-8') ?>">
                    <label class="form-label" for="imagem_nova"> Foto (150x150)</label>
                    <input name="imagem_nova" id="imagem_nova" type="file" class="form-control" onchange="previewImagem();">
                  </div>
                </div>
              </div>
              <div class="card-footer">
                <a href="<?= URLACAD . "perfil/visualizar" ?>" class="btn btn-secondary" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'">Cancelar</a>
                <input type="submit" class="btn btn-primary botaoEnvia" name="EditPerfil" value="Atualizar">
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>