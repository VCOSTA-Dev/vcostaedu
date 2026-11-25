<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
$p = $this->dados['polo'] ?? [];
$id = (int) ($this->dados['id'] ?? 0);
$ufs = $this->dados['ufs'] ?? [];
$v = fn($c) => htmlspecialchars((string) ($p[$c] ?? ''), ENT_QUOTES, 'UTF-8');
?>
<div class="vc-pagina">
  <?php ob_start(); ?><?= $id > 0 ? 'Editar polo' : 'Novo polo' ?><?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php
      if (isset($_SESSION['msg'])) {
        echo $_SESSION['msg'];
        unset($_SESSION['msg']);
      }
      ?>
      <form method="POST" action="" novalidate>
        <div class="card shadow">
          <div class="card-body">
            <div class="row g-2">
              <div class="mb-3 col-md-6"><label class="form-label" for="nome">Nome</label><input type="text" name="nome" id="nome" class="form-control" value="<?= $v('nome') ?>" required></div>
              <div class="mb-3 col-md-6"><label class="form-label" for="instituicao_parceira">Instituição parceira (opcional)</label><input type="text" name="instituicao_parceira" id="instituicao_parceira" class="form-control" value="<?= $v('instituicao_parceira') ?>"></div>
            </div>
            <div class="row g-2">
              <div class="mb-3 col-md-6"><label class="form-label" for="endereco">Endereço</label><input type="text" name="endereco" id="endereco" class="form-control" value="<?= $v('endereco') ?>"></div>
              <div class="mb-3 col-md-4"><label class="form-label" for="cidade">Cidade</label><input type="text" name="cidade" id="cidade" class="form-control" value="<?= $v('cidade') ?>"></div>
              <div class="mb-3 col-md-2"><label class="form-label" for="uf">UF</label>
                <select name="uf" id="uf" class="form-select"><option value=""></option>
                  <?php foreach ($ufs as $u) : ?><option value="<?= htmlspecialchars($u, ENT_QUOTES, 'UTF-8') ?>" <?= htmlspecialchars((string) (($p['uf'] ?? '') === $u ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>><?= htmlspecialchars($u, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="mb-3"><label class="form-label" for="situacao">Situação</label>
              <select name="situacao" id="situacao" class="form-select">
                <option value="1" <?= htmlspecialchars((string) ((int) ($p['situacao'] ?? 1) === 1 ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>>Ativo</option>
                <option value="2" <?= htmlspecialchars((string) ((int) ($p['situacao'] ?? 1) === 2 ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>>Inativo</option>
              </select>
            </div>
          </div>
          <div class="card-footer">
            <input type="submit" name="SalvarPolo" value="Salvar" class="btn btn-primary">
            <a href="<?= URLADM . 'polos/listar' ?>" class="btn btn-secondary">Cancelar</a>
          </div>
        </div>
      </form>
    </div>
  </section>
</div>
