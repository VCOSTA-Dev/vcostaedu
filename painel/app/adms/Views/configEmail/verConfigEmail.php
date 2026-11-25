<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
if (!empty($this->dados['info_pagina'][0])) {
  $info = $this->dados['info_pagina'][0];
?>
  <div class="vc-pagina">
    <?php ob_start(); ?><a href="<?= URLADM . "configuracao-email/listar" ?>" class="btn btn-outline-primary rounded-circle shadow" title="Voltar"><i class="fas fa-arrow-left"></i></a> Ver Configuração de E-mail<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
    <section class="page-body">
      <div class="container-xl">
        <?php
        if (isset($_SESSION['msg'])) {
          echo $_SESSION['msg'];
          unset($_SESSION['msg']);
        }
        ?>
        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-body">
                <?php
                if ($this->dados['botao']['edit_email']) {
                ?>
                  <a href="<?= htmlspecialchars((string) (URLADM . "configuracao-email/editar/{$info['id']}"), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-warning" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'"><i class="far fa-edit"></i> Editar</a>
                <?php
                }
                ?>
              </div>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-body">
                <dl class="row">
                  <dt class="col-sm-4">Título</dt>
                  <dd class="col-sm-8"><?= htmlspecialchars((string) ($info['titulo']), ENT_QUOTES, 'UTF-8') ?></dd>
                  <dt class="col-sm-4">Nome</dt>
                  <dd class="col-sm-8"><?= htmlspecialchars((string) ($info['nome']), ENT_QUOTES, 'UTF-8') ?></dd>
                  <dt class="col-sm-4">E-mail</dt>
                  <dd class="col-sm-8"><?= htmlspecialchars((string) ($info['email']), ENT_QUOTES, 'UTF-8') ?></dd>
                  <dt class="col-sm-4">Data de Criação</dt>
                  <dd class="col-sm-8"><?= htmlspecialchars((string) ($info['criacao']), ENT_QUOTES, 'UTF-8') ?></dd>
                  <dt class="col-sm-4">Data de Modificação</dt>
                  <dd class="col-sm-8"><?= htmlspecialchars((string) ($info['modificado']), ENT_QUOTES, 'UTF-8') ?></dd>
                </dl>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
<?php
} else {
  $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-hidden='true'></button>Erro: Página de configuração não encontrada!</div>";
  $urlDestino = URLADM . 'configuracao-email/listar';
  header("Location: $urlDestino");
}
?>