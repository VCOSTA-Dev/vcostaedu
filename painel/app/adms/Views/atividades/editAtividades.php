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
  <?php ob_start(); ?><a href="<?= htmlspecialchars((string) (URLADM . "atividades/listar/{$valorForm['rm_prof_mat_id']}"), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary rounded-circle" title="Voltar"><i class="fas fa-arrow-left"></i></a> Editar - <?= htmlspecialchars((string) ("{$valorForm['nomeAtividade']}"), ENT_QUOTES, 'UTF-8') ?><?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
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
              if ($this->dados['botao']['vis_ativi']) {
              ?>
                <a href="<?= htmlspecialchars((string) (URLADM . "atividades/visualizar/{$valorForm['id']}"), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary me-1" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'"><i class="far fa-eye"></i> Visualizar</a>
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
              <div class="card-body">
                <input type="hidden" name="id" value="<?= htmlspecialchars((string) ((isset($valorForm['id']) ? $valorForm['id'] : '')), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="rm_prof_mat_id" value="<?= htmlspecialchars((string) ($valorForm['rm_prof_mat_id']), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="tipo" value="<?= htmlspecialchars((string) ($valorForm['tipo']), ENT_QUOTES, 'UTF-8') ?>">
                <?php $ofertaCp = (int) $valorForm['rm_prof_mat_id']; $atividadeCp = (int) ($valorForm['id'] ?? 0); $compAtual = $valorForm['componente_codigo'] ?? ''; $pesoAtual = $valorForm['peso'] ?? '10'; include __DIR__ . '/_componentePeso.php'; /* 1.13.0 (D-394) */ ?>
                <div class="row g-2">
                  <div class="mb-3 col-md-12">
                    <label class="form-label" for="nomeAtividade">Nome da atividade</label>
                    <input type="text" class="form-control" name="nomeAtividade" id="nomeAtividade" placeholder="Nome da Atividade" value="<?= htmlspecialchars((string) ((isset($valorForm['nomeAtividade']) ? $valorForm['nomeAtividade'] : $info['exercicios'][0]['nomeAtividade'])), ENT_QUOTES, 'UTF-8') ?>" required>
                  </div>
                </div>
                <div class="row g-2">
                  <div class="mb-3 col-md-12">
                    <label class="form-label" for="tipo">Tipo</label>
                    <select name="tipo" id="tipo" class="form-select select2" required>
                      <option value="">Selecione o Tipo</option>
                      <?php
                      foreach ($this->dados['select']['tipos'] as $niv) {
                        if (isset($valorForm['tipo']) && $valorForm['tipo'] == $niv['id']) {
                          echo "<option value='{$niv['id']}' selected>{$niv['nome']}</option>";
                        } else {
                          echo "<option value='{$niv['id']}'>{$niv['nome']}</option>";
                        }
                      }
                      ?>
                    </select>
                  </div>
                </div>
                <div class="row g-2">
                  <div class="mb-3 col-md-12">
                    <label class="form-label" for="descricao">Descrição da atividade / Conteúdo cobrado: </label>
                    <textarea name="descricao" id="descricao" class="form-control" rows="5"><?= htmlspecialchars((string) ((isset($valorForm['descricao']) ? $valorForm['descricao'] : '')), ENT_QUOTES, 'UTF-8') ?></textarea>
                  </div>
                </div>
                <div class="row g-2">
                  <div class="mb-3 col-md-12">
                    <div class="form-check form-check-inline">
                      <input type="radio" id="entrega2" name="entrega_arquivo" class="form-check-input" value="1" <?= htmlspecialchars((string) (((isset($valorForm['entrega_arquivo']) && !empty($valorForm['entrega_arquivo'])) && $valorForm['entrega_arquivo'] == 1 ? 'checked' : '')), ENT_QUOTES, 'UTF-8') ?>>
                      <label class="form-check-label" for="entrega2">Sem entrega de arquivo</label>
                    </div>
                    <div class="form-check form-check-inline">
                      <input type="radio" id="entrega1" name="entrega_arquivo" class="form-check-input" value="2" <?= htmlspecialchars((string) (((isset($valorForm['entrega_arquivo']) && !empty($valorForm['entrega_arquivo'])) && $valorForm['entrega_arquivo'] == 2 ? 'checked' : '')), ENT_QUOTES, 'UTF-8') ?>>
                      <label class="form-check-label" for="entrega1">Com entrega de arquivo</label>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-4">
                    <div class="mb-3">
                      <label class="form-label"> Data de início</label>
                      <input type="date" name="data_inicial" class="form-control" value="<?= htmlspecialchars((string) ((isset($valorForm['data_inicial']) ? $valorForm['data_inicial'] : '')), ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                  </div>
                  <div class="col-4">
                    <div class="mb-3">
                      <label class="form-label"> Data Final</label>
                      <input type="date" name="data_final" class="form-control" value="<?= htmlspecialchars((string) ((isset($valorForm['data_final']) ? $valorForm['data_final'] : '')), ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                  </div>
                </div>
              </div>
              <div class="card-footer">
                <input type="submit" class="btn btn-primary botaoEnvia" name="EditAtividades" value="Salvar">
                <a href="<?= htmlspecialchars((string) (URLADM . "atividades/visualizar/{$valorForm['id']}"), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secondary" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'">Cancelar</a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>