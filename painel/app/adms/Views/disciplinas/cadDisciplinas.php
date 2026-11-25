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
  <?php ob_start(); ?><a href="<?= URLADM . "disciplinas/listar" ?>" class="btn btn-outline-primary rounded-circle" title="Voltar para a lista de disciplinas"><i class="fas fa-arrow-left"></i></a> Cadastrar disciplina<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
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
                <?php include __DIR__ . '/camposMatriz.php'; ?>
                <div class="row g-2">
                  <div class="mb-3 col-md-4">
                    <label class="form-label" for="ano">Ano</label>
                    <input type="text" name="ano" id="ano" class="form-control" value="<?= htmlspecialchars((string) ((isset($valorForm['ano']) ? $valorForm['ano'] : '2025')), ENT_QUOTES, 'UTF-8') ?>" required>
                  </div>
                  <div class="mb-3 col-md-8"><label class="form-label">Etapa</label><p class="form-control-plaintext text-muted mb-0">Etapa única; média = média simples das atividades (E4).</p></div>
                </div>
                <div class="row g-2">
                  <div class="mb-3 col-md-4">
                    <label class="form-label" for="professores">Professor</label>
                    <select name="professores[]" id="professores" class="form-select select2" multiple="multiple">
                      <option value="">Escolha o(s) Professor(es)</option>
                      <?php
                      foreach ($this->dados['select']['usuarios'] as $prof) {
                        if (isset($valorForm['professores'])) {
                          if (in_array(strval($prof['id']), $valorForm['professores'], true)) {
                            echo "<option value='{$prof['id']}' selected>{$prof['nome']}</option>";
                          } else {
                            echo "<option value='{$prof['id']}'>{$prof['nome']}</option>";
                          }
                        } else {
                            echo "<option value='{$prof['id']}'>{$prof['nome']}</option>";
                        }
                      }
                      ?>
                    </select>
                  </div>
                </div>
              </div>
              <div class="card-footer">
                <input type="submit" class="btn btn-primary botaoEnvia" name="CadDisciplina" value="Salvar">
                <a href="<?= URLADM . "disciplinas/listar" ?>" class="btn btn-secondary" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'">Cancelar</a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>