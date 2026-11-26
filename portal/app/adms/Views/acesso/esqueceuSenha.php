<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* VCOSTAEDU (Etapa 6): convertida para Tabler. O campo identificador (RA, CPF ou e-mail, pelo parâmetro) e o botão
   RecupLogin como antes. */
$campoLogin = $this->dados['campoLogin'] ?? 'ra';
$rotulo = \App\adms\Models\helper\AdmsParametro::rotuloCampoLogin($campoLogin);
require_once dirname(__DIR__, 5) . '/core/ContaFamilia.php';
$familiaLigada = \VcContaFamilia::ligada(); /* Onda 7: o responsável com e-mail também pede a senha nova aqui */
if ($familiaLigada) {
  $rotulo = 'login'; /* Onda 8 (D-67): a matrícula do aluno ou o login do responsável */
}
?>
<div class="container container-tight py-4">
  <?php include 'app/adms/Views/include_tabler/_acesso_marca.php'; ?>
  <?php
  if (isset($_SESSION['msg'])) {
    echo $_SESSION['msg'];
    unset($_SESSION['msg']);
  }
  ?>
  <div class="card card-md">
    <div class="card-body">
      <h2 class="h2 text-center mb-3">Esqueci a senha</h2>
      <p class="text-secondary mb-4">Informe seu <?= htmlspecialchars($rotulo) ?> para receber, no e-mail cadastrado, um link para definir a senha.</p>
      <form action="" method="POST" novalidate>
        <div class="mb-3">
          <label class="form-label" for="identificador"><?= htmlspecialchars($rotulo) ?></label>
          <div class="input-group input-group-flat">
            <input type="<?= $campoLogin === 'email' && !$familiaLigada ? 'email' : 'text' ?>" name="identificador" id="identificador" class="form-control" placeholder="<?= htmlspecialchars($rotulo) ?>" value="" autocomplete="username" required>
            <span class="input-group-text"><i class="far fa-envelope" aria-hidden="true"></i></span>
          </div>
          <?php if ($familiaLigada) : ?><div class="form-hint" id="dicaResponsavelSenha">Aluno: <?= \App\adms\Models\helper\AdmsTipoPortal::atual() === \VcVocabulario::ESCOLA ? 'o RM' : 'o número de matrícula' /* D-125 (1.102.1, C3.4) */ ?>. Responsável: o seu login (o CPF ou o e-mail); o link vai para o e-mail do cadastro. Sem e-mail cadastrado, peça um novo convite à secretaria da escola.</div><?php endif; ?>
        </div>
        <div class="form-footer">
          <input type="submit" class="btn btn-primary w-100 botaoEnvia" name="RecupLogin" value="Recuperar">
        </div>
      </form>
    </div>
  </div>
  <div class="text-center text-secondary mt-3">
    <a href="<?= URLACAD . "login/acesso/" ?>" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'">Lembrei a senha</a>
  </div>
  <?php include 'app/adms/Views/include_tabler/_acesso_rodape.php'; ?>
</div>
