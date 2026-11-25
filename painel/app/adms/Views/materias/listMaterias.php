<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Matérias<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
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
              if ($this->dados['botao']['cad_mat']) {
              ?>
                <a href="<?= URLADM . "materias/cadastrar" ?>" class="btn btn-outline-success me-1" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'"><i class="far fa-keyboard"></i> Cadastrar</a>
              <?php
              }
              if ($this->dados['botao']['list_cur']) {
              ?>
              <a href="<?= URLADM . "cursos/listar" ?>" class="btn btn-outline-info me-1" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'"><i class="fas fa-university"></i> Cursos</a>
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
              <?php $Ui = \App\adms\Models\helper\AdmsUi::class; /* E8c (R2/R3/R4) */ ?>
              <table id="tabelaMaterias" class="table table-sm table-striped table-hover table-compacta lista-dados" data-titulo="Matérias" data-ordem='[[2,"asc"]]'>
                <thead>
                  <tr>
                    <th class="num">#</th>
                    <th>Código / abreviação</th>
                    <th>Matéria</th>
                    <th class="num">Carga horária</th>
                    <th>Situação</th>
                    <th class="nao-exportar nao-ordenar text-end">Ações</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  if (!empty($this->dados['listaMaterias'])) {
                    foreach ($this->dados['listaMaterias'] as $mat) {
                  ?>
                    <tr>
                      <td class="num"><?= htmlspecialchars((string) ((int) $mat['id']), ENT_QUOTES, 'UTF-8') ?></td>
                      <td><?= htmlspecialchars((string) ($mat['abreviacao'] ?? '')) ?: '—' ?></td>
                      <td><?= htmlspecialchars((string) ($mat['nome']), ENT_QUOTES, 'UTF-8') ?></td>
                      <td class="num"><?= htmlspecialchars((string) $mat['carga_horaria'], ENT_QUOTES, 'UTF-8') ?> h</td>
                      <td><span class="badge bg-<?= htmlspecialchars((string) ($mat['corSituacao']), ENT_QUOTES, 'UTF-8') ?>-lt"><?= htmlspecialchars((string) ($mat['nomeSituacao']), ENT_QUOTES, 'UTF-8') ?></span></td>
                      <td class="acoes-linha">
                        <?php if ($this->dados['botao']['vis_mat']) : ?>
                          <?= $Ui::acao('ver', URLADM . "materias/visualizar/{$mat['id']}", 'Ver a matéria') ?>
                        <?php endif; ?>
                        <?php if ($this->dados['botao']['edit_mat']) : ?>
                          <?= $Ui::acao('editar', URLADM . "materias/editar/{$mat['id']}", 'Editar a matéria') ?>
                        <?php endif; ?>
                        <?php if (!empty($this->dados['botao']['sit_mat'])) : /* E8d (P-8): inativar/reativar */
                          $inativa = (int) ($mat['situacaoId'] ?? 1) === 2; ?>
                          <?= $inativa
                            ? $Ui::acaoPost('reativar', URLADM . 'materias/situacao/' . (int) $mat['id'], ['situacao' => 1], 'Reativar a matéria: volta às listas de escolha')
                            : $Ui::acaoPost('inativar', URLADM . 'materias/situacao/' . (int) $mat['id'], ['situacao' => 2], 'Inativar a matéria: sai das listas de escolha; o que já foi feito não muda', null, ['confirmar' => 'Inativar a matéria ' . $mat['nome'] . '? Ela sai das listas de escolha (matriz e nova oferta). Ofertas, notas e históricos não mudam.']) ?>
                        <?php endif; ?>
                        <?php if ($this->dados['botao']['del_mat']) : /* E8c (R4, D-270): matéria na matriz ou com oferta não é apagada */ ?>
                          <?= $Ui::acaoModal('excluir', '#apagarItem', ['titulo' => 'EXCLUIR - MATÉRIA', 'confirm' => 'Tem certeza de que deseja excluir a matéria: ' . $mat['nome'] . '?', 'identidade' => (int) $mat['id']], 'Apagar a matéria', \App\adms\Models\helper\AdmsUsos::materia((int) $mat['id'])) ?>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php
                    }
                  }
                  ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<div class="modal fade" id="apagarItem" tabindex="-1" aria-labelledby="apagarItemLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="cabecalhoItem">DESATIVAR EMPRESA</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="<?= URLADM . "materias/apagar"; ?>" enctype="multipart/form-data" novalidate>
        <div class="modal-body">
          <div class="mb-3">
            <input type="text" name="id" class="form-control" value required>
          </div>
          <p class="corpoItem"></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <input type="submit" name="ApagarMateria" class="btn btn-danger botaoEnvia" value="Apagar">
        </div>
      </form>
    </div>
  </div>
</div>
