<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Usuários<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
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
              if ($this->dados['botao']['cad_user']) {
              ?>
                <a href="<?= URLADM . "usuarios/cadastrar" ?>" class="btn btn-outline-success me-1" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'"><i class="far fa-keyboard"></i> Cadastrar</a>
              <?php
              }
              if ($this->dados['botao']['list_niv']) {
              ?>
                <a href="<?= URLADM . "perfis-acesso/index" ?>" class="btn btn-outline-info" id="verPerfisAcesso"><i class="fas fa-key"></i> Perfis de acesso</a>
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
              <?php $Ui = \App\adms\Models\helper\AdmsUi::class; /* E8c (R2/R3) */ ?>
              <table id="tabelaUsuarios" class="table table-sm table-striped table-hover table-compacta lista-dados" data-titulo="Usuários da equipe" data-ordem='[[1,"asc"]]'>
                <thead>
                  <tr>
                    <th class="num">#</th>
                    <th>Nome</th>
                    <th>Nível de acesso</th>
                    <th>E-mail</th>
                    <th>Situação</th>
                    <th class="nao-exportar nao-ordenar text-end">Ações</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  if (!empty($this->dados['listUsers'])) {
                    foreach ($this->dados['listUsers'] as $use) {
                  ?>
                      <tr>
                        <td class="num"><?= htmlspecialchars((string) ((int) $use['id']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($use['nome']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($use['nivelAcesso']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($use['email']), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><span class="badge bg-<?= htmlspecialchars((string) ($use['situacaoCor']), ENT_QUOTES, 'UTF-8') ?>-lt"><?= htmlspecialchars((string) ($use['situacaoUsuario']), ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td class="acoes-linha">
                          <?php if ($this->dados['botao']['vis_user']) : ?>
                            <?= $Ui::acao('ver', URLADM . "usuarios/visualizar/{$use['id']}", 'Ver o usuário') ?>
                          <?php endif; ?>
                          <?php if (empty($use['podeAlterar'])) : /* 1.18.3 (D-497): nível igual ou acima do seu, ou você mesmo */ ?>
                            <span class="badge bg-secondary-lt" title="Nível igual ou acima do seu: só um nível acima dele altera. O seu próprio cadastro e a sua senha ficam no Perfil."><i class="fas fa-lock"></i> Protegido</span>
                          <?php elseif ($this->dados['botao']['edit_user']) : ?>
                            <?= $Ui::acao('editar', URLADM . "usuarios/editar/{$use['id']}", 'Editar o usuário') ?>
                          <?php endif; ?>
                          <?php if (!empty($use['podeAlterar']) && $this->dados['botao']['del_user']) : ?>
                            <?= $Ui::acaoModal('excluir', '#apagarItem', ['titulo' => 'EXCLUIR - USUÁRIO', 'confirm' => 'Tem certeza de que deseja excluir o usuário ' . $use['nome'] . '?', 'identidade' => (int) $use['id']], 'Apagar (desliga o usuário)') ?>
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
        <h5 class="modal-title" id="cabecalhoItem">EXCLUIR USUÁRIO</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="<?= URLADM . "usuarios/apagar"; ?>" enctype="multipart/form-data" novalidate>
        <div class="modal-body">
          <div class="mb-3">
            <input type="hidden" name="id" class="form-control" value required>
          </div>
          <p class="corpoItem"></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <input type="submit" name="ApagarUsuario" class="btn btn-danger botaoEnvia" value="Apagar">
        </div>
      </form>
    </div>
  </div>
</div>
