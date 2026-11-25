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
  <?php ob_start(); ?><a href="<?= URLADM . "usuarios/listar" ?>" class="btn btn-outline-primary rounded-circle" title="Voltar para listar usuários"><i class="fas fa-arrow-left"></i></a> Cadastro de Usuário<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
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
          <form method="POST" action="" enctype="multipart/form-data" novalidate>
              <div class="card-body">
                <div class="row g-2">
                  <div class="mb-3 col-md-12">
                    <label class="form-label" for="nome">Nome Completo</label>
                    <input type="text" class="form-control" name="nome" id="nome" placeholder="Nome completo" value="<?= htmlspecialchars((string) ((isset($valorForm['nome']) ? $valorForm['nome'] : '')), ENT_QUOTES, 'UTF-8') ?>" required>
                  </div>
                </div>
                <div class="row g-2">
                  <div class="mb-3 col-md-6">
                    <label class="form-label" for="email">E-mail</label>
                    <input type="email" class="form-control" name="email" id="email" placeholder="E-mail" value="<?= htmlspecialchars((string) ((isset($valorForm['email']) ? $valorForm['email'] : '')), ENT_QUOTES, 'UTF-8') ?>" required>
                  </div>
                  <div class="mb-3 col-md-6">
                    <label class="form-label" for="genero_id">Sexo</label>
                    <select name="genero_id" id="genero_id" class="form-select select2" required>
                      <option value="">Selecione o sexo</option>
                      <?php
                      foreach ($this->dados['select']['generos'] as $gen) {
                        if ((isset($valorForm['genero_id']) && !empty($valorForm['genero_id'])) && $valorForm['genero_id'] == $gen['id']) {
                          echo "<option value='{$gen['id']}' selected>{$gen['nome']}</option>";
                        } else {
                          echo "<option value='{$gen['id']}'>{$gen['nome']}</option>";
                        }
                      }
                      ?>
                    </select>
                  </div>
                </div>
                <div class="row g-2">
                  <div class="mb-3 col-md-6">
                    <label class="form-label" for="usuario">Usuário</label>
                    <input type="text" class="form-control" name="usuario" id="usuario" placeholder="Usuário login" value="<?= htmlspecialchars((string) ((isset($valorForm['usuario']) ? $valorForm['usuario'] : '')), ENT_QUOTES, 'UTF-8') ?>" required>
                  </div>
                  <div class="mb-3 col-md-6">
                    <label class="form-label" for="senha">Senha</label>
                    <?php /* 1.18.2 (D-489): a senha NUNCA volta no HTML — depois de um erro, digite de novo */ ?>
                    <input type="password" class="form-control" name="senha" id="senha" placeholder="Senha" value="" required<?= \VcSenha::atributos('nova', [], '#nome,#usuario,#email') ?>>
                    <div class="form-hint"><?= htmlspecialchars(\VcSenha::ajuda(), ENT_QUOTES, 'UTF-8') ?></div>
                  </div>
                </div>
                <div class="row g-2">
                  <div class="mb-3 col-md-6">
                    <label class="form-label" for="adms_niveis_acesso_id">Nível de acesso</label>
                    <select name="adms_niveis_acesso_id" id="adms_niveis_acesso_id" class="form-select select2" required>
                      <option value="">Selecione o Nível de acesso</option>
                      <?php
                      foreach ($this->dados['select']['niveis_acessos'] as $niv) {
                        if ((isset($valorForm['adms_niveis_acesso_id']) && !empty($valorForm['adms_niveis_acesso_id'])) && $valorForm['adms_niveis_acesso_id'] == $niv['id']) {
                          echo "<option value='{$niv['id']}' selected>{$niv['nome']}</option>";
                        } else {
                          echo "<option value='{$niv['id']}'>{$niv['nome']}</option>";
                        }
                      }
                      ?>
                    </select>
                  </div>
                  <div class="mb-3 col-md-6">
                    <label class="form-label" for="adms_sits_usuario_id">Situação</label>
                    <select name="adms_sits_usuario_id" id="adms_sits_usuario_id" class="form-select select2" required>
                      <option value="">Selecione a situação</option>
                      <?php
                      foreach ($this->dados['select']['situacoes'] as $niv) {
                        if ((isset($valorForm['adms_sits_usuario_id']) && !empty($valorForm['adms_sits_usuario_id'])) && $valorForm['adms_sits_usuario_id'] == $niv['id']) {
                          echo "<option value='{$niv['id']}' selected>{$niv['nome']}</option>";
                        } else {
                          echo "<option value='{$niv['id']}'>{$niv['nome']}</option>";
                        }
                      }
                      ?>
                    </select>
                    <small class="form-text text-muted" id="ajudaSituacao"><strong>Atualizar Dados:</strong> o usuário entra e é levado a atualizar os dados antes de usar o sistema. Inativo e Bloqueado não entram.</small>
                  </div>
                <div class="row g-2">
                  <div class="mb-3 col-md-6">
                    <label class="form-label" for="titulacao_id">Titulação <small class="text-muted">(professores; sai no histórico)</small></label>
                    <select name="titulacao_id" id="titulacao_id" class="form-select">
                      <option value="">Não informada</option>
                      <?php foreach (\App\adms\Models\helper\AdmsTitulacao::ativas() as $tit) : ?><option value="<?= htmlspecialchars((string) ((int) $tit['id']), ENT_QUOTES, 'UTF-8') ?>" <?= htmlspecialchars((string) ((int) ($valorForm['titulacao_id'] ?? 0) === (int) $tit['id'] ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>><?= htmlspecialchars($tit['nome'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
                    </select>
                  </div>
                </div>
                </div>
                <div class="row g-2">
                  <div class="mb-3 col-md-12">
                    <?php
                    if (isset($valorForm['imagem']) and !empty($valorForm['imagem'])) {
                      $imagem_antiga = URLADM . 'assets/img/usuario/' . $valorForm['id'] . '/' . $valorForm['imagem'];
                    } elseif (isset($valorForm['imagem_antiga']) and !empty($valorForm['imagem_antiga'])) {
                      $imagem_antiga = URLADM . 'assets/img/usuario/' . $valorForm['id'] . '/' . $valorForm['imagem_antiga'];
                    } else {
                      $imagem_antiga = URLADM . 'assets/img/usuario/preview_img.png';
                    }
                    ?>
                    <img src="<?php echo $imagem_antiga; ?>" alt="Imagem do Usuário" id="preview-user" class="img-thumbnail" style="width: 150px; height: 150px;">
                    <input name="imagem_antiga" type="hidden" value="<?= htmlspecialchars((string) ((isset($valorForm['imagem_antiga']) ? $valorForm['imagem_antiga'] : (isset($valorForm['imagem']) ? $valorForm['imagem'] : NULL))), ENT_QUOTES, 'UTF-8') ?>">
                    <label class="form-label" for="imagem_nova"> Foto (150x150)</label>
                    <input name="imagem_nova" type="file" onchange="previewImagem();">

                  </div>
                </div>

              </div>
              <div class="card-footer">
                <input type="submit" class="btn btn-primary botaoEnvia" name="CadUsuario" value="Salvar">
                <a href="<?= URLADM . "usuarios/listar" ?>" class="btn btn-secondary" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'">Cancelar</a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>