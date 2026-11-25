<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
$g = $this->dados['grade'] ?? [];
$id = (int) ($this->dados['id'] ?? 0);
$cursos = $this->dados['cursos'] ?? [];
$v = fn($c) => htmlspecialchars((string) ($g[$c] ?? ''), ENT_QUOTES, 'UTF-8');
?>
<div class="vc-pagina">
  <?php ob_start(); ?><?= $id > 0 ? 'Editar grade' : 'Nova grade' ?><?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>

  <section class="page-body">
    <div class="container-xl">
      <?php
      if (isset($_SESSION['msg'])) {
        echo $_SESSION['msg'];
        unset($_SESSION['msg']);
      }
      ?>
      <form method="POST" action="" novalidate>
        <div class="row">
          <div class="col-lg-7">
            <div class="card shadow">
              <div class="card-body">
                <div class="mb-3">
                  <label class="form-label" for="curso_id">Curso</label>
                  <select name="curso_id" id="curso_id" class="form-select" <?= $id > 0 ? 'disabled' : '' ?> required>
                    <option value="">Selecione</option>
                    <?php foreach ($cursos as $c) : ?>
                      <option value="<?= htmlspecialchars((string) ((int) $c['id']), ENT_QUOTES, 'UTF-8') ?>" <?= htmlspecialchars((string) ((int) $c['id'] === (int) ($g['curso_id'] ?? 0) ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>>
                        <?= htmlspecialchars($c['nome'], ENT_QUOTES, 'UTF-8') ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label" for="versao">Número da grade</label>
                  <input type="number" min="1" max="9999" name="versao" id="versao" class="form-control" value="<?= $v('versao') ?>" required>
                  <small class="form-text text-muted">O número digitado é o que fica. Não se repete no mesmo curso.</small>
                </div>
                <div class="mb-3">
                  <label class="form-label" for="descricao">Descrição</label>
                  <textarea name="descricao" id="descricao" class="form-control" rows="2"><?= $v('descricao') ?></textarea>
                </div>
              </div>
              <div class="card-footer">
                <input type="submit" name="SalvarGrade" value="Salvar" class="btn btn-primary">
                <a href="<?= URLADM . ($id > 0 ? 'matriz/visualizar/' . $id : 'matriz/listar') ?>" class="btn btn-secondary">Cancelar</a>
              </div>
            </div>
          </div>
          <div class="col-lg-5">
            <div class="alert alert-info">
              <?php $semMod = \App\adms\Models\helper\AdmsModuloInterno::ativo(); /* E8c (D-268) */ ?>
              <p>A grade nasce <b>Aberta</b>: é quando aceita <?= $semMod ? 'disciplinas' : 'módulos e disciplinas' ?>.</p>
              <p class="mb-0">A carga horária não é digitada: é a soma <?= $semMod ? 'das disciplinas' : 'dos módulos' ?>.</p>
            </div>
          </div>
        </div>
      </form>
    </div>
  </section>
</div>
