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
  <?php ob_start(); ?><a href="<?= URLADM . "salas-de-aula/listar" ?>" class="btn btn-outline-primary rounded-circle" title="Voltar para a lista de salas"><i class="fas fa-arrow-left"></i></a> Cadastro de Salas<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
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
            <form action="" method="POST" enctype="multipart/form-data" novalidate>
              <div class="card-body">
                <div class="row g-2">
                  <div class="mb-3 col-md-6">
                    <label class="form-label" for="codfilial">Polo</label>
                    <select name="codfilial" id="codfilial" class="form-select">
                      <?php foreach (\App\adms\Models\polos\AdmsListarPolos::ativos() as $polo) : ?>
                        <option value="<?= htmlspecialchars((string) ((int) $polo['id']), ENT_QUOTES, 'UTF-8') ?>" <?= htmlspecialchars((string) ((int) ($valorForm['codfilial'] ?? 0) === (int) $polo['id'] ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>><?= htmlspecialchars($polo['nome'], ENT_QUOTES, 'UTF-8') ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <div class="row g-2">
                  <div class="mb-3 col-md-12">
                    <label class="form-label" for="nome">Nome</label>
                    <input type="text" class="form-control" name="nome" id="nome" placeholder="Sala" value="<?= htmlspecialchars((string) ((isset($valorForm['nome']) ? $valorForm['nome'] : '')), ENT_QUOTES, 'UTF-8') ?>" required>
                  </div>
                </div>
                <div class="row g-2">
                  <div class="mb-3 col-md-6">
                    <label class="form-label" for="capacidade_max">Capacidade</label>
                    <input type="number" class="form-control" name="capacidade_max" id="capacidade_max" value="<?= htmlspecialchars((string) ((isset($valorForm['capacidade_max']) ? $valorForm['capacidade_max'] : '')), ENT_QUOTES, 'UTF-8') ?>">
                  </div>
                </div>
                <div class="row g-2">
                  <div class="mb-3 col-md-12">
                    <label class="form-label" for="descricao">Descrição</label>
                    <textarea name="descricao" id="descricao" class="form-control" rows="5"><?= htmlspecialchars((string) ((isset($valorForm['descricao']) ? $valorForm['descricao'] : '' )), ENT_QUOTES, 'UTF-8') ?></textarea>
                  </div>
                </div>
                <div class="row g-2">
                  <div class="mb-3 col-md-12">
                    <label class="form-label" for="observacao">Observação</label>
                    <textarea name="observacao" id="observacao" class="form-control" rows="5"><?= htmlspecialchars((string) ((isset($valorForm['observacao']) ? $valorForm['observacao'] : '' )), ENT_QUOTES, 'UTF-8') ?></textarea>
                  </div>
                </div>
              </div>
              <div class="card-footer">
                <input type="submit" class="btn btn-primary botaoEnvia" name="CadSalasAula" value="Salvar">
                <a href="<?= URLADM . "salas-de-aula/listar" ?>" class="btn btn-secondary" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'">Cancelar</a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>