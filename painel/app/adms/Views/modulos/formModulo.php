<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
$m = $this->dados['modulo'] ?? [];
$id = (int) ($this->dados['id'] ?? 0);
$grades = $this->dados['grades'] ?? [];
$gradeSel = (int) ($m['grade_id'] ?? 0);
$v = fn($c, $p = '') => htmlspecialchars((string) ($m[$c] ?? $p), ENT_QUOTES, 'UTF-8');
?>
<div class="vc-pagina">
  <?php ob_start(); ?><?= $id > 0 ? 'Editar módulo' : 'Novo módulo' ?><?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>

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
                  <label class="form-label" for="grade_id">Curso e grade</label>
                  <select name="grade_id" id="grade_id" class="form-select" <?= $id > 0 ? 'disabled' : '' ?> required>
                    <option value="">Selecione</option>
                    <?php foreach ($grades as $g) : ?>
                      <option value="<?= htmlspecialchars((string) ((int) $g['id']), ENT_QUOTES, 'UTF-8') ?>" <?= htmlspecialchars((string) ((int) $g['id'] === $gradeSel ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>>
                        <?= htmlspecialchars($g['curso'], ENT_QUOTES, 'UTF-8') ?> — grade <?= htmlspecialchars((string) ((int) $g['versao']), ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars((string) $g['nomeSituacao'], ENT_QUOTES, 'UTF-8') ?>)
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <?php if ($id > 0) : ?>
                    <small class="form-text text-muted">A grade de um módulo não muda.</small>
                  <?php endif; ?>
                </div>
                <div class="row g-2">
                  <div class="mb-3 col-8">
                    <label class="form-label" for="codnome">Nome do módulo</label>
                    <input type="text" class="form-control" name="codnome" id="codnome" placeholder="Módulo I" value="<?= $v('codnome') ?>" required>
                  </div>
                  <div class="mb-3 col-4">
                    <label class="form-label" for="ordem">Ordem na grade</label>
                    <input type="number" class="form-control" name="ordem" id="ordem" min="1" value="<?= $v('ordem') ?>" required>
                  </div>
                </div>
                <div class="mb-3">
                  <label class="form-label" for="descricao">Descrição</label>
                  <input type="text" class="form-control" name="descricao" id="descricao" value="<?= $v('descricao') ?>">
                </div>
                <div class="mb-3">
                  <label class="form-label" for="observacao">Observação</label>
                  <textarea class="form-control" name="observacao" id="observacao" rows="2"><?= $v('observacao') ?></textarea>
                </div>
                <div class="mb-3">
                  <label class="form-label" for="situacao">Situação</label>
                  <select name="situacao" id="situacao" class="form-select">
                    <option value="1" <?= htmlspecialchars((string) ((int) ($m['situacao'] ?? 1) === 1 ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>>Ativo</option>
                    <option value="2" <?= htmlspecialchars((string) ((int) ($m['situacao'] ?? 1) === 2 ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>>Inativo</option>
                  </select>
                </div>
              </div>
              <div class="card-footer">
                <input type="submit" name="SalvarModulo" value="Salvar" class="btn btn-primary">
                <a href="<?= URLADM . ($gradeSel ? 'matriz/visualizar/' . $gradeSel : 'modulos/listar') ?>" class="btn btn-secondary">Cancelar</a>
              </div>
            </div>
          </div>
          <div class="col-lg-5">
            <div class="alert alert-info">
              <p>A carga horária do módulo não é digitada: é a soma das disciplinas que ele recebe na matriz.</p>
              <p class="mb-0">Só uma grade <b>Aberta</b> aceita módulo novo ou alterado.</p>
            </div>
          </div>
        </div>
      </form>
    </div>
  </section>
</div>
