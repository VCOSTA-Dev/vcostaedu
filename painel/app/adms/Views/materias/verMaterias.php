<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
if (!empty($this->dados['info_dado'][0])) {
  $info = $this->dados['info_dado'][0];
?>
  <div class="vc-pagina">
    <?php ob_start(); ?><a href="<?= URLADM . "materias/listar" ?>" class="btn btn-outline-primary rounded-circle" title="Voltar para Menu"><i class="fas fa-arrow-left"></i></a> <?= htmlspecialchars((string) ($info['nome']), ENT_QUOTES, 'UTF-8') ?><?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
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
            <div class="card shadow">
              <div class="card-body">
                <?php
                if ($this->dados['botao']['edit_mat']) {
                ?>
                  <a href="<?= htmlspecialchars((string) (URLADM . "materias/editar/{$info['id']}"), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-warning me-1" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'"><i class="far fa-edit"></i> Editar</a>
                <?php
                }
                ?>
              </div>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-12">
            <div class="card shadow">
              <div class="card-body">
                <dl class="row">
                  <dt class="col-sm-4">Matéria</dt>
                  <dd class="col-sm-8"><?= htmlspecialchars((string) ($info['nome']), ENT_QUOTES, 'UTF-8') ?></dd>
                  <dt class="col-sm-4">Código / abreviação</dt>
                  <dd class="col-sm-8"><?= htmlspecialchars((string) ($info['abreviacao'] ?? '')) ?: '—' ?></dd>
                  <dt class="col-sm-4">Carga Horária</dt>
                  <dd class="col-sm-8"><?= htmlspecialchars((string) ($info['carga_horaria']), ENT_QUOTES, 'UTF-8') ?>h</dd>
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
  $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-hidden='true'></button>Erro: Não foi possível encontrar a matéria!</div>";
  $urlDestino = URLADM . 'materias/listar';
  header("Location: $urlDestino");
}
?>