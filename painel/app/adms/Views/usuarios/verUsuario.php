<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
if (!empty($this->dados['info_user'][0])) {
  $info = $this->dados['info_user'][0];
?>
  <div class="vc-pagina">
    <?php ob_start(); ?><a href="<?= URLADM . "usuarios/listar" ?>" class="btn btn-outline-primary rounded-circle" title="Voltar"><i class="fas fa-arrow-left"></i></a> <?= htmlspecialchars((string) ($info['nome']), ENT_QUOTES, 'UTF-8') ?><?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
    <section class="page-body">
      <div class="container-xl">
        <?php
        if (isset($_SESSION['msg'])) {
          echo $_SESSION['msg'];
          unset($_SESSION['msg']);
        }
        ?>
        <div class="row">
          <div class="col-md-3">
            <div class="card shadow">
              <div class="card-body box-profile">
                <div class="text-center">
                  <img class="profile-user-img img-fluid rounded-circle" src="<?= htmlspecialchars((string) (URLADM . "assets/img/usuario/".(!empty($info['imagem']) ? "{$info['id']}/{$info['imagem']}" : "icone_usuario.png" )), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <h3 class="profile-username text-center"><?= htmlspecialchars((string) ($info['nome']), ENT_QUOTES, 'UTF-8') ?></h3>
                <p class="text-muted text-center"><?= htmlspecialchars((string) ($info['nivelAcesso']), ENT_QUOTES, 'UTF-8') ?></p>
                <?php
                if ($this->dados['botao']['edit_user'] && !empty($this->dados['podeAlterar'])) { /* 1.18.3 (D-497) */
                ?>
                  <a href="<?= htmlspecialchars((string) (URLADM . "usuarios/editar/{$info['id']}"), ENT_QUOTES, 'UTF-8') ?>" class="btn w-100 btn-outline-warning" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'"><i class="fas fa-user-edit"></i> Editar</a>
                <?php
                }
                if ($this->dados['botao']['edit_senha'] && !empty($this->dados['podeAlterar'])) {
                ?>
                  <button type="button" class="btn w-100 btn-outline-danger" data-bs-toggle="modal" data-bs-target="#editSenhaUser"><i class="fas fa-lock-open"></i> Editar Senha</button>
                <?php
                }
                if (empty($this->dados['podeAlterar'])) { /* 1.18.3 (D-497) */
                ?>
                  <p class="text-muted small text-center mb-0" id="usuarioProtegido"><i class="fas fa-lock"></i> Protegido: nível igual ou acima do seu. O seu próprio cadastro e a sua senha ficam no Perfil.</p>
                <?php
                }
                ?>
              </div>
            </div>

            <div class="card shadow">
              <div class="card-body">
                <strong><i class="far fa-star me-1"></i> Situação</strong>
                <p class="text-muted"><span class="badge bg-<?= htmlspecialchars((string) ($info['corSituacao']), ENT_QUOTES, 'UTF-8') ?>-lt"><?= htmlspecialchars((string) ($info['nomeSituacao']), ENT_QUOTES, 'UTF-8') ?></span></p>
                <hr>
                <strong><i class="fas fa-book me-1"></i> Inserido no sistema</strong>
                <p class="text-muted"><?= htmlspecialchars((string) ($info['criacao']), ENT_QUOTES, 'UTF-8') ?></p>
                <hr>
                <strong><i class="fas fa-highlighter me-1"></i> Última atualização</strong>
                <p class="text-muted"><?= htmlspecialchars((string) ($info['atualizado']), ENT_QUOTES, 'UTF-8') ?></p>
              </div>
            </div>
          </div>

          <div class="col-md-9">
            <div class="card shadow">
              <div class="card-header">
                <h3 class="card-title">INFORMAÇÕES DO USUÁRIO</h3>
                <div class="card-actions"><button type="button" class="btn btn-action" data-vc-recolher><i class="fas fa-minus"></i></button></div>
              </div>
              <div class="card-body">
                <strong><i class="fas fa-envelope me-1"></i> E-mail</strong>
                <p class="text-muted"><?= htmlspecialchars((string) ($info['email']), ENT_QUOTES, 'UTF-8') ?></p>
                <hr>
                <?php /* Onda 3, Etapa 5: rótulo sem valor não aparece (sem telefone, a linha some; sem endereço, o bloco some) */
                $telU = trim((string) ($info['telefone'] ?? '')); $celU = trim((string) ($info['celular'] ?? ''));
                $endU = implode(' - ', array_filter([trim(implode(', ', array_filter([trim((string) ($info['endereco'] ?? '')), trim((string) ($info['numero'] ?? ''))]))), trim((string) ($info['cidade'] ?? '')), trim((string) ($info['estado'] ?? ''))]));
                $cepU = trim((string) ($info['cep'] ?? '')); ?>
                <?php if ($telU !== '' || $celU !== '') : ?>
                <strong>Telefones de Contato</strong>
                <p class="text-muted">
                  <?php if ($telU !== '') : ?><i class="fas fa-phone-alt"></i> Telefone: <span class="campotelefone"><?= htmlspecialchars((string) ($info['telefone']), ENT_QUOTES, 'UTF-8') ?></span><br><?php endif; ?>
                  <?php if ($celU !== '') : ?><i class="fas fa-mobile-alt"></i> Celular: <span class="campocelular"><?= htmlspecialchars((string) ($info['celular']), ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                </p>
                <hr>
                <?php endif; ?>
                <?php if ($endU !== '' || $cepU !== '') : ?>
                <strong><i class="fas fa-map-marker-alt me-1"></i> Endereço</strong>
                <p class="text-muted"><?= $cepU !== '' ? htmlspecialchars($cepU, ENT_QUOTES, 'UTF-8') . '<br>' : '' ?><?= htmlspecialchars($endU, ENT_QUOTES, 'UTF-8') ?></p>
                <hr>
                <?php endif; ?>
              </div>
            </div>
            <div class="card shadow">
              <div class="card-header">
                <h3 class="card-title">INFORMAÇÕES PROFISSIONAIS</h3>
                <div class="card-actions"><button type="button" class="btn btn-action" data-vc-recolher><i class="fas fa-minus"></i></button></div>
              </div>
              <div class="card-body">
                <strong><i class="far fa-building"></i> Funcionário da Empresa</strong>
                <p class="text-muted"> </p>
                <hr>
                <strong><i class="far fa-id-badge"></i> Setor</strong>
                <p class="text-muted"> </p>
                <hr>
                <strong><i class="fas fa-id-card-alt"></i> Função</strong>
                <p class="text-muted"> </p>
                <hr>
                <strong><i class="far fa-calendar-check"></i> Admissão</strong>
                <p class="text-muted"> </p>
                <hr>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>

  <?php if (!empty($this->dados['podeAlterar'])) : /* 1.18.3 (D-497): sem o formulário de senha para quem está protegido */ ?>
  <div class="modal fade" id="editSenhaUser" tabindex="-1" aria-labelledby="editSenhaUserLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">EDITAR SENHA</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="POST" action="<?= htmlspecialchars((string) (URLADM . "usuarios/editar-senha/{$info['id']}"), ENT_QUOTES, 'UTF-8') ?>" enctype="multipart/form-data" novalidate>
          <div class="modal-body">
            <input type="hidden" name="id" class="form-control" value="<?= htmlspecialchars((string) ($info['id']), ENT_QUOTES, 'UTF-8') ?>" required>
            <div class="mb-3">
              <label class="form-label" for="novaSenhaUsuario">Nova Senha</label>
              <input type="password" name="senha" id="novaSenhaUsuario" class="form-control" required<?= \VcSenha::atributos('nova', ['nome' => (string) ($info['nome'] ?? ''), 'usuario' => (string) ($info['usuario'] ?? ''), 'email' => (string) ($info['email'] ?? '')]) ?>>
              <div class="form-hint"><?= htmlspecialchars(\VcSenha::ajuda(), ENT_QUOTES, 'UTF-8') ?></div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <input type="submit" name="EditSenhaUsuario" class="btn btn-primary botaoEnvia" value="Atualizar">
          </div>
        </form>
      </div>
    </div>
  </div>
  <?php endif; ?>
<?php
} else {
  $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-hidden='true'></button>Erro: Usuário não encontrado!</div>";
  $urlDestino = URLADM . 'usuarios/listar';
  header("Location: $urlDestino");
}
?>