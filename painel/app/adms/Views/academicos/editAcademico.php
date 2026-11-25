<?php

if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
$info = $this->dados['info_user'] ?? [];
$valorForm = $this->dados['form'][0] ?? ($this->dados['form'] ?? []);
$idAluno = (int) ($valorForm['id'] ?? 0);
?>
<div class="vc-pagina">
  <?php ob_start(); ?><a href="<?= htmlspecialchars((string) (URLADM . "academicos/visualizar/{$idAluno}"), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary rounded-circle" title="Voltar"><i class="fas fa-arrow-left"></i></a> Editar aluno<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
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
                <img class="profile-user-img img-fluid rounded-circle" src="<?= URLACAD . "assets/img/academicos/" . (!empty($valorForm['imagem']) ? "{$idAluno}/" . htmlspecialchars($valorForm['imagem'], ENT_QUOTES) : "icone_usuario.png") ?>" alt="Foto do aluno">
              </div>
              <h3 class="profile-username text-center"><?= htmlspecialchars((string) ($valorForm['nome'] ?? '')) ?></h3>
              <p class="text-muted text-center"><?= \App\adms\Models\helper\AdmsTipoInstituicao::rotuloNumero() ?> <?= htmlspecialchars((string) ($valorForm['rm'] ?? '')) ?></p>
              <hr>
              <?php if (!empty($this->dados['botao']['vis_user'])) { ?>
                <a href="<?= htmlspecialchars((string) (URLADM . "academicos/visualizar/{$idAluno}"), ENT_QUOTES, 'UTF-8') ?>" class="btn w-100 btn-outline-primary"><i class="fas fa-user-graduate"></i> Visualizar</a>
              <?php } ?>
              <span class="btn w-100 btn-warning" style="pointer-events: none;"><i class="fas fa-user-edit"></i> Editar</span>
            </div>
          </div>
          <div class="card shadow">
            <div class="card-body">
              <strong><i class="fas fa-book me-1"></i> Inserido no sistema</strong>
              <p class="text-muted"><?= htmlspecialchars((string) ($info['info'][0]['criacao'] ?? '')) ?></p>
              <hr>
              <strong><i class="fas fa-map-marker-alt me-1"></i> Alterado</strong>
              <p class="text-muted"><?= htmlspecialchars((string) ($info['info'][0]['modificado'] ?? '')) ?></p>
            </div>
          </div>
        </div>
        <div class="col-md-9">
          <div class="card shadow">
            <form action="" method="POST" enctype="multipart/form-data" novalidate>
              <input type="hidden" name="id" value="<?= htmlspecialchars((string) ($idAluno), ENT_QUOTES, 'UTF-8') ?>">
              <div class="card-body">
                <?php include __DIR__ . '/formAluno.php'; ?>
                <h5 class="text-muted mt-3">Foto</h5>
                <div class="row g-2">
                  <div class="mb-3 col-md-12">
                    <input name="imagem_antiga" type="hidden" value="<?= htmlspecialchars((string) ($valorForm['imagem'] ?? ($valorForm['imagem_antiga'] ?? '')), ENT_QUOTES) ?>">
                    <label class="form-label" for="imagem_nova">Foto (150x150, opcional)</label>
                    <input name="imagem_nova" id="imagem_nova" type="file">
                  </div>
                </div>
              </div>
              <div class="card-footer">
                <input type="submit" class="btn btn-primary botaoEnvia" name="EditUsuarioAcad" value="Salvar">
                <a href="<?= htmlspecialchars((string) (URLADM . "academicos/visualizar/{$idAluno}"), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secondary" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'">Cancelar</a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
