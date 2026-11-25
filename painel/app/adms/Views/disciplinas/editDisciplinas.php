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
  <?php ob_start(); ?><a href="<?= URLADM . "disciplinas/listar" ?>" class="btn btn-outline-primary rounded-circle" title="Voltar"><i class="fas fa-arrow-left"></i></a> Editar Disciplina<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
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
              if ($this->dados['botao']['vis_disc']) {
              ?>
                <a href="<?= htmlspecialchars((string) (URLADM . "disciplinas/visualizar/{$valorForm['id']}"), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary me-1" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'"><i class="far fa-eye"></i> Visualizar</a>
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
            <form action="" method="POST" enctype="multipart/form-data" novalidate>
              <input type="hidden" name="id" value="<?= htmlspecialchars((string) ((isset($valorForm['id']) ? $valorForm['id'] : '')), ENT_QUOTES, 'UTF-8') ?>">
              <div class="card-body">
                <?php $travaLinha = !empty($this->dados['travaLinha']); include __DIR__ . '/camposMatriz.php'; ?>
                <div class="row g-2">
                  <div class="mb-3 col-md-4">
                    <label class="form-label" for="ano">Ano</label>
                    <input type="text" name="ano" id="ano" class="form-control" value="<?= htmlspecialchars((string) ((isset($valorForm['ano']) ? $valorForm['ano'] : '2025')), ENT_QUOTES, 'UTF-8') ?>" required>
                  </div>
                  <div class="mb-3 col-md-8"><label class="form-label">Etapa</label><p class="form-control-plaintext text-muted mb-0">Etapa única; média = média simples das atividades (E4).</p></div>
                </div>
              </div>
              <div class="card-footer">
                <input type="submit" class="btn btn-primary botaoEnvia" name="EditDisciplina" value="Salvar">
                <a href="<?= URLADM . "disciplinas/listar" ?>" class="btn btn-secondary" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'">Cancelar</a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>