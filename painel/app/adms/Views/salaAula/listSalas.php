<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Salas de Aula<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
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
              if ($this->dados['botao']['list_hor']) {
                ?>
                  <a href="<?= URLADM . "horario-aula/listar/" ?>" class="btn btn-outline-primary me-1" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'"><i class="fa-solid fa-stopwatch"></i> Horários aula</a>
                <?php
                }
              if ($this->dados['botao']['cad_sl']) {
              ?>
                <a href="<?= URLADM . "salas-de-aula/cadastrar" ?>" class="btn btn-outline-success" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'"><i class="far fa-keyboard"></i> Cadastrar</a>
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
              <table class="table table-bordered table-hover table-sm table-striped table-compacta lista-dados" data-ordem='[[0,"desc"]]'>
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Nome</th>
                    <th>Capacidade</th>
                    <th>Filial</th>
                    <th class="text-center">Ações</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  if (!empty($this->dados['listSalas'])) {
                    foreach ($this->dados['listSalas'] as $sla) {
                  ?>
                      <tr>
                        <td><?= htmlspecialchars((string) ($sla['id']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($sla['nome']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($sla['capacidade_max']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($sla['filial']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-center">
                          <?php
                          if ($this->dados['botao']['vis_sl']) {
                          ?>
                            <a href="<?= htmlspecialchars((string) (URLADM . "salas-de-aula/visualizar/{$sla['id']}"), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-primary" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'"><i class="far fa-eye"></i> Visualizar</a>
                          <?php
                          }
                          if ($this->dados['botao']['edit_sl']) {
                          ?>
                            <a href="<?= htmlspecialchars((string) (URLADM . "salas-de-aula/editar/{$sla['id']}"), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-warning" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'"><i class="far fa-edit"></i> Editar</a>
                          <?php
                          }
                          if ($this->dados['botao']['del_sl']) {
                            ?>
                              <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#apagarItem" data-titulo="EXCLUIR - SALA DE AULA" data-confirm="Tem certeza de que deseja excluir a sala de aula: <?= htmlspecialchars((string) ($sla['nome']), ENT_QUOTES, 'UTF-8') ?> ?" data-identidade="<?= htmlspecialchars((string) ($sla['id']), ENT_QUOTES, 'UTF-8') ?>"><i class="far fa-trash-alt"></i> Apagar</button>
                            <?php
                            }
                          ?>
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
        <h5 class="modal-title" id="cabecalhoItem">EXCLUIR A SALA DE AULA</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="<?= URLADM . "salas-de-aula/apagar"; ?>" enctype="multipart/form-data" novalidate>
        <div class="modal-body">
          <div class="mb-3">
            <input type="hidden" name="id" class="form-control" value required>
          </div>
          <p class="corpoItem"></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <input type="submit" name="ApagarSalasAula" class="btn btn-danger botaoEnvia" value="Apagar">
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  /* E8c: a ordem da lista está em data-ordem (lista-dados) */
</script>