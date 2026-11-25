<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
$a = $this->dados['ano'] ?? null;
$bandeiras = $this->dados['bandeiras'] ?? [];
$novo = empty($a['id']);
?>
<div class="vc-pagina">
  <?php ob_start(); ?><?= htmlspecialchars((string) ($novo ? 'Novo ano letivo' : 'Editar ano letivo ' . (int) $a['ano']), ENT_QUOTES, 'UTF-8') ?><?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>

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
                <div class="mb-3 col-md-4 ps-0">
                  <label class="form-label" for="ano">Ano</label>
                  <input type="number" class="form-control" name="ano" id="ano" required
                         min="2000" max="2999" placeholder="<?= date('Y') + 1 ?>"
                         value="<?= htmlspecialchars((string) ((int) ($a['ano'] ?? (date('Y') + 1))), ENT_QUOTES, 'UTF-8') ?>">
                  <div class="invalid-feedback">Preencha este campo para continuar.</div>
                </div>

                <hr>

                <?php foreach ($bandeiras as $campo => $b) : ?>
                  <div class="mb-3">
                    <div class="form-check form-switch">
                      <input type="checkbox" class="form-check-input" name="<?= htmlspecialchars((string) ($campo), ENT_QUOTES, 'UTF-8') ?>"
                             id="<?= htmlspecialchars((string) ($campo), ENT_QUOTES, 'UTF-8') ?>" value="1"
                             <?= htmlspecialchars((string) ((int) ($a[$campo] ?? 0) === 1 ? 'checked' : ''), ENT_QUOTES, 'UTF-8') ?>>
                      <label class="form-check-label" for="<?= htmlspecialchars((string) ($campo), ENT_QUOTES, 'UTF-8') ?>">
                        <b><?= htmlspecialchars($b['rotulo'], ENT_QUOTES, 'UTF-8') ?></b>
                      </label>
                    </div>
                    <small class="form-text text-muted ms-4">
                      <?= htmlspecialchars($b['ajuda'], ENT_QUOTES, 'UTF-8') ?>
                      <?php if ($campo !== 'situacao') : ?>
                        <br>⚠️ <b>É um só</b> — marcar aqui desmarca o ano que estiver marcado hoje.
                      <?php endif; ?>
                    </small>
                  </div>
                <?php endforeach; ?>
              </div>
              <div class="card-footer">
                <a href="<?= URLADM . 'anos-letivos/listar' ?>" class="btn btn-warning">Voltar</a>
                <input type="submit" class="btn btn-primary botaoEnvia" name="SalvarAnoLetivo" value="Salvar">
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>
  </section>
</div>
