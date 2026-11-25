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
  <?php ob_start(); ?><a href="<?= URLADM . "materias/listar" ?>" class="btn btn-outline-primary rounded-circle" title="Voltar"><i class="fas fa-arrow-left"></i></a> Editar Matéria<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
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
              if ($this->dados['botao']['vis_mat']) {
              ?>
                <a href="<?= htmlspecialchars((string) (URLADM . "materias/visualizar/{$valorForm['id']}"), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-warning me-1" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'"><i class="far fa-eye"></i> Visualizar</a>
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
            <form action="" method="POST" enctype="multipart/form-data"><?php /* 1.18.1 (D-481): sem novalidate — nome e CH obrigatórios também na tela */ ?>
              <input type="hidden" name="id" value="<?= htmlspecialchars((string) ((isset($valorForm['id']) ? $valorForm['id'] : '')), ENT_QUOTES, 'UTF-8') ?>">
              <div class="card-body">
                <div class="row g-2">
                  <div class="mb-3 col-md-4">
                    <label class="form-label" for="abreviacao">Código / abreviação <small class="text-muted">(sai no histórico)</small></label>
                    <input type="text" class="form-control" name="abreviacao" id="abreviacao" maxlength="20" placeholder="ex.: GP101" value="<?= htmlspecialchars((string) ($valorForm['abreviacao'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                  </div>
                  <div class="mb-3 col-md-8">
                    <label class="form-label" for="nome">Nome da matéria *</label>
                    <input type="text" class="form-control" name="nome" id="nome" placeholder="Nome da matéria" value="<?= htmlspecialchars((string) ($valorForm['nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" maxlength="255" required>
                  </div>
                </div>
                <div class="row g-2">
                  <div class="mb-3 col-md-4">
                    <label class="form-label" for="carga_horaria">Carga horária (horas) *</label>
                    <input type="number" class="form-control" name="carga_horaria" id="carga_horaria" placeholder="ex.: 60" min="1" max="9999" step="1" value="<?= htmlspecialchars((string) ($valorForm['carga_horaria'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                  </div>
                </div>
                <div class="row g-2">
                  <div class="mb-3 col-md-12">
                    <label class="form-label" for="descricao">Descrição</label>
                    <textarea name="descricao" id="descricao" rows="5" class="form-control"><?= htmlspecialchars((string) ($valorForm['descricao'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                  </div>
                </div>
              </div>
              <div class="card-footer">
                <input type="submit" class="btn btn-primary botaoEnvia" name="EditMaterias" value="Salvar">
                <a href="<?= htmlspecialchars((string) (URLADM . "materias/visualizar/{$valorForm['id']}"), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secondary" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'">Cancelar</a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>