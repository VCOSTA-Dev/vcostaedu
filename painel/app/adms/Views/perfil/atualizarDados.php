<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* 1.18.4 (D-503): "Atualize seus dados" — os campos do "Meu perfil", já preenchidos; enquanto a marca estiver ligada, o
   resto do painel leva para cá (AdmsAtualizarDados::portao). Valores sempre escapados. */
$f = $this->dados['form'] ?? [];
$h = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
$imgAntiga = (string) ($f['imagem_antiga'] ?? ($f['imagem'] ?? ''));
$foto = $imgAntiga !== '' ? URLADM . 'assets/img/usuario/' . (int) ($f['id'] ?? 0) . '/' . rawurlencode($imgAntiga) : URLADM . 'assets/img/usuario/preview_img.png';
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Atualize seus dados<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php
      if (isset($_SESSION['msg'])) {
        echo $_SESSION['msg'];
        unset($_SESSION['msg']);
      }
      ?>
      <div class="alert alert-warning" id="avisoAtualizarDados">
        <p class="mb-0"><strong>Antes de continuar, confira e atualize seus dados.</strong> Depois de salvar, o sistema fica liberado.</p>
      </div>
      <div class="card shadow">
        <form method="POST" action="<?= URLADM . 'perfil/atualizar-dados' ?>" enctype="multipart/form-data" novalidate id="formAtualizarDados">
          <div class="card-body">
            <div class="row g-2">
              <div class="mb-3 col-md-12">
                <label class="form-label" for="nome">Nome completo</label>
                <input type="text" class="form-control" name="nome" id="nome" value="<?= $h($f['nome'] ?? '') ?>" required>
              </div>
            </div>
            <div class="row g-2">
              <div class="mb-3 col-md-6">
                <label class="form-label" for="email">E-mail</label>
                <input type="email" class="form-control" name="email" id="email" value="<?= $h($f['email'] ?? '') ?>" required>
              </div>
              <div class="mb-3 col-md-6">
                <label class="form-label" for="genero_id">Sexo</label>
                <select name="genero_id" id="genero_id" class="form-select" required>
                  <option value="">Selecione o sexo</option>
                  <?php foreach (($this->dados['select']['generos'] ?? []) as $gen) : ?>
                    <option value="<?= htmlspecialchars((string) ((int) $gen['id']), ENT_QUOTES, 'UTF-8') ?>" <?= htmlspecialchars((string) ((string) ($f['genero_id'] ?? '') === (string) $gen['id'] ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>><?= $h($gen['nome']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="row g-2">
              <div class="mb-3 col-md-6">
                <label class="form-label" for="telefone">Telefone</label>
                <input type="text" class="form-control campotelefone" name="telefone" id="telefone" value="<?= $h($f['telefone'] ?? '') ?>">
              </div>
              <div class="mb-3 col-md-6">
                <label class="form-label" for="celular">Celular</label>
                <input type="text" class="form-control campocelular" name="celular" id="celular" value="<?= $h($f['celular'] ?? '') ?>">
              </div>
            </div>
            <div class="row g-2">
              <div class="mb-3 col-md-6">
                <label class="form-label" for="usuario">Usuário (login)</label>
                <input type="text" class="form-control" name="usuario" id="usuario" value="<?= $h($f['usuario'] ?? '') ?>" required>
              </div>
            </div>
            <div class="row g-2">
              <div class="mb-3 col-md-12">
                <img src="<?= $h($foto) ?>" alt="Foto do usuário" id="preview-user" class="img-thumbnail" style="width: 150px; height: 150px;">
                <input name="imagem_antiga" type="hidden" value="<?= $h($imgAntiga) ?>">
                <label class="form-label" for="imagem_nova">Foto (150x150, opcional)</label>
                <input name="imagem_nova" id="imagem_nova" type="file" onchange="previewImagem();">
              </div>
            </div>
          </div>
          <div class="card-footer">
            <input type="submit" class="btn btn-primary botaoEnvia" name="AtualizarDados" value="Salvar e continuar">
            <a href="<?= URLADM . 'login/logout' ?>" class="btn btn-outline-secondary">Sair</a>
          </div>
        </form>
      </div>
    </div>
  </section>
</div>
