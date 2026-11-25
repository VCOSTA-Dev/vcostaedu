<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
if (!empty($this->dados['info_user'][0])) {
  $info = $this->dados['info_user'][0];
}
?>
<div class="vc-pagina">
  <?php ob_start(); ?><?= htmlspecialchars((string) ($info['nome']), ENT_QUOTES, 'UTF-8') ?><?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
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
                <img class="profile-user-img img-fluid rounded-circle" src="<?= htmlspecialchars((string) (URLACAD . "assets/img/academicos/" . (!empty($info['imagem']) ? "{$info['id']}/{$info['imagem']}" : "icone_usuario.png")), ENT_QUOTES, 'UTF-8') ?>" alt="Imagem perfil do usuário">
              </div>
              <h3 class="profile-username text-center"><?= htmlspecialchars((string) ($info['nome']), ENT_QUOTES, 'UTF-8') ?></h3>
              <p class="text-muted text-center"><?= htmlspecialchars((string) ($info['nivelAcesso']), ENT_QUOTES, 'UTF-8') ?></p>
              <hr>
                <span class="btn w-100 btn-primary" style="pointer-events: none;"><?= (isset($info['acessoId']) ? ($info['acessoId'] == 1 ? "<i class='fas fa-user-graduate'></i>" : "<i class='fas fa-user-tie'></i>") : "<i class='fas fa-user-graduate'></i>") ?> Visualizar</span>
                <?php
                if ($this->dados['botao']['edit_perfil']) {
                ?>
                  <a href="<?= URLACAD . "perfil/editar/"; ?>" class="btn w-100 btn-outline-warning" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'"><i class="fas fa-user-edit"></i> Editar</a>
                <?php
                }
                if ($this->dados['botao']['edit_senha']) {
                ?>
                  <a href="<?= URLACAD . "perfil/editar-senha/"; ?>" class="btn w-100 btn-outline-danger" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'"><i class="fas fa-lock-open"></i> Editar Senha</a>
                  <?php
                }
                ?>
            </div>
          </div>
        </div>

        <div class="col-md-9">
          <?php
          if($info['acessoId'] == 1){
            ?>
          <div class="card shadow">
              <div class="card-header">
                <h3 class="card-title">MATRÍCULAS ATUAL</h3>
                <div class="card-actions"><button type="button" class="btn btn-action" data-vc-recolher><i class="fas fa-minus"></i></button></div>
              </div>
              <div class="card-body">
              </div>
            </div>
            <?php
          }
          ?>
          <div class="card card-tabs shadow">
              <div class="card-header p-0 pt-1 border-bottom-0">
            <ul class="nav nav-tabs">
                <li class="nav-item"><a class="nav-link active" href="#info" data-bs-toggle="tab">INFORMAÇÕES DO USUÁRIO</a></li>
              </ul>
            </div>
            <div class="card-body">
              <div class="tab-content">
                <div class="tab-pane active" id="info">
                  <strong><i class="fas fa-envelope me-1"></i> E-mail</strong>
                  <p class="text-muted"><?= htmlspecialchars((string) ($info['email']), ENT_QUOTES, 'UTF-8') ?></p>
                  <hr>
                  <strong>Telefones de Contato</strong>
                  <p class="text-muted">
                    <i class="fas fa-phone-alt"></i> Telefone: <span class="campotelefone"><?= htmlspecialchars((string) ($info['telefone']), ENT_QUOTES, 'UTF-8') ?></span><br><i class="fas fa-mobile-alt"></i> Celular: <span class="campocelular"><?= htmlspecialchars((string) ($info['celular']), ENT_QUOTES, 'UTF-8') ?></span>
                  </p>
                  <hr>
                  <strong><i class="fas fa-map-marker-alt me-1"></i> Endereço</strong>
                  <p class="text-muted"><?= htmlspecialchars((string) ($info['cep']), ENT_QUOTES, 'UTF-8') ?><br><?= htmlspecialchars((string) ("{$info['endereco']}, {$info['numero']} - {$info['cidade']} - {$info['uf']}"), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <!-- <div class="tab-pane" id="dados">
                </div>
                <div class="tab-pane" id="profissional">
                </div> -->
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>