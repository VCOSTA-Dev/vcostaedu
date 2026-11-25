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
  <?php ob_start(); ?><a href="<?= URLADM . "configuracao-email/listar" ?>" class="btn btn-outline-primary rounded-circle shadow" title="Voltar"><i class="fas fa-arrow-left"></i></a> Editar de Config. e-mail<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
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
          <div class="card">
            <div class="card-body">
              <?php
              if ($this->dados['botao']['vis_email']) {
              ?>
                <a href="<?= htmlspecialchars((string) (URLADM . "configuracao-email/visualizar/{$valorForm['id']}"), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'"><i class="far fa-eye"></i> Visualizar</a>
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
            <form action="" method="POST" novalidate>
              <input type="hidden" name="id" value="<?= htmlspecialchars((string) ((isset($valorForm['id']) ? $valorForm['id'] : '')), ENT_QUOTES, 'UTF-8') ?>">
              <div class="card-body">
                <div class="row g-2">
                  <div class="mb-3 col-md-12">
                    <label class="form-label" for="titulo">Título</label>
                    <input type="text" class="form-control" name="titulo" id="titulo" placeholder="Título da config. do e-mail" value="<?= htmlspecialchars((string) ((isset($valorForm['titulo']) ? $valorForm['titulo'] : '')), ENT_QUOTES, 'UTF-8') ?>" required>
                  </div>
                </div>
                <div class="row g-2">
                  <div class="mb-3 col-md-12">
                    <label class="form-label" for="nome">Nome (irá aparecer no email)</label>
                    <input type="text" class="form-control" name="nome" id="nome" placeholder="Nome" value="<?= htmlspecialchars((string) ((isset($valorForm['nome']) ? $valorForm['nome'] : '')), ENT_QUOTES, 'UTF-8') ?>" required>
                  </div>
                </div>
                <div class="row g-2">
                  <div class="mb-3 col-md-6">
                    <label class="form-label" for="host">Host</label>
                    <input type="text" class="form-control" name="host" id="host" placeholder="Host" value="<?= htmlspecialchars((string) ((isset($valorForm['host']) ? $valorForm['host'] : '')), ENT_QUOTES, 'UTF-8') ?>" required>
                  </div>
                </div>
                <div class="row g-2">
                  <div class="mb-3 col-md-6">
                    <label class="form-label" for="email">E-mail</label>
                    <input type="text" class="form-control" name="email" id="email" placeholder="E-mail para disparo" value="<?= htmlspecialchars((string) ((isset($valorForm['email']) ? $valorForm['email'] : '')), ENT_QUOTES, 'UTF-8') ?>" required>
                  </div>
                  <div class="mb-3 col-md-6">
                    <label class="form-label" for="username">Username</label>
                    <input type="text" class="form-control" name="username" id="username" placeholder="Username" value="<?= htmlspecialchars((string) ((isset($valorForm['username']) ? $valorForm['username'] : '')), ENT_QUOTES, 'UTF-8') ?>" required>
                  </div>
                </div>
                <div class="row g-2">
                  <div class="mb-3 col-md-4">
                    <label class="form-label" for="senhas">Senha</label>
                    <?php /* 1.18.2 (D-489): a senha gravada NUNCA vai para o HTML (antes, a edição a mostrava no value). Em branco
                       = manter a senha atual; preencha só para trocar. */ ?>
                    <input type="password" class="form-control" name="senhas" id="senhas" placeholder="Em branco: mantém a senha atual" value="" autocomplete="new-password">
                    <small class="form-text text-muted">A senha gravada não é mostrada. Deixe em branco para manter a atual.</small>
                  </div>
                  <div class="mb-3 col-md-4">
                    <label class="form-label" for="smtpsecure">Smtp Secure</label>
                    <input type="text" class="form-control" name="smtpsecure" id="smtpsecure" placeholder="smtp" value="<?= htmlspecialchars((string) ((isset($valorForm['smtpsecure']) ? $valorForm['smtpsecure'] : '')), ENT_QUOTES, 'UTF-8') ?>" required>
                  </div>
                  <div class="mb-3 col-md-4">
                    <label class="form-label" for="porta">Porta</label>
                    <input type="text" class="form-control" name="porta" id="porta" placeholder="porta" value="<?= htmlspecialchars((string) ((isset($valorForm['porta']) ? $valorForm['porta'] : '')), ENT_QUOTES, 'UTF-8') ?>" required>
                  </div>
                </div>
              </div>
              <div class="card-footer">
                <input type="submit" class="btn btn-primary botaoEnvia" name="EditConfigEmail" value="Salvar">
                <a href="<?= URLADM . "configuracao-email/listar" ?>" class="btn btn-secondary" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'">Cancelar</a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>