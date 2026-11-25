<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Configuração de E-mail<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php
      if (isset($_SESSION['msg'])) {
        echo $_SESSION['msg'];
        unset($_SESSION['msg']);
      }
      ?>
      <div class="row">
        <div class="col-md-12">
          <div class="card">
            <div class="card-body">
            <?php
              if ($this->dados['botao']['cad_email']) {
              ?>
              <a href="<?= URLADM . "configuracao-email/cadastrar" ?>" class="btn btn-outline-success" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'"><i class="far fa-keyboard"></i> Cadastrar</a>
              <?php
              }
              /* Onda 2, Etapa 4: o envio de teste, com a configuração gravada, para o seu e-mail */
              if (!empty($this->dados['botao']['testar']) && !empty($this->dados['listEmails'])) {
              ?>
              <form method="POST" action="<?= URLADM ?>configuracao-email/testar" class="d-inline">
                <button type="submit" class="btn btn-outline-primary" id="testarEnvio"><i class="fas fa-paper-plane"></i> Enviar um e-mail de teste</button>
              </form>
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
            <div class="card-body">
              <table id="tabelaConfigEmail" class="table table-bordered table-sm table-hover table-striped table-compacta lista-dados" data-ordem='[]'>
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Título</th>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th class="text-center">Ações</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  if (!empty($this->dados['listEmails'])) {
                    foreach ($this->dados['listEmails'] as $info) {
                  ?>
                      <tr>
                        <td><?= htmlspecialchars((string) ($info['id']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($info['titulo']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($info['nome']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($info['email']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-center">
                          <?php
                          if ($this->dados['botao']['vis_email']) {
                          ?>
                            <a href="<?= htmlspecialchars((string) (URLADM . "configuracao-email/visualizar/{$info['id']}"), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-primary" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'"><i class="far fa-eye"></i> Visualizar</a>
                          <?php
                          }
                          if ($this->dados['botao']['edit_email']) {
                          ?>
                            <a href="<?= htmlspecialchars((string) (URLADM . "configuracao-email/editar/{$info['id']}"), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-warning" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'"><i class="far fa-edit"></i> Editar</a>
                          <?php
                          }
                          if ($this->dados['botao']['del_email']) {
                          ?>
                          <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#apagarItem" data-titulo="EXCLUIR - CONFIG. DE E-MAIL" data-confirm="Tem certeza de que deseja excluir a configuração: <?= htmlspecialchars((string) ($info['nome']), ENT_QUOTES, 'UTF-8') ?> ?" data-identidade="<?= htmlspecialchars((string) ($info['id']), ENT_QUOTES, 'UTF-8') ?>"><i class="far fa-trash-alt"></i> Apagar</button>
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
        <h5 class="modal-title" id="cabecalhoItem">EXCLUIR CONFIG. DE E-MAIL</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="<?= URLADM . "configuracao-email/apagar"; ?>" enctype="multipart/form-data" novalidate>
        <div class="modal-body">
          <div class="mb-3">
            <input type="hidden" name="id" class="form-control" value required>
          </div>
          <p class="corpoItem"></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <input type="submit" name="ApagarConfigEmail" class="btn btn-danger botaoEnvia" value="Apagar">
        </div>
      </form>
    </div>
  </div>
</div>