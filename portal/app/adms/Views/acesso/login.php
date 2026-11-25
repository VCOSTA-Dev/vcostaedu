<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* VCOSTAEDU (Etapa 6): convertida para Tabler (sign-in centrado), como o login do painel. Ficam os nomes dos campos
   (usuario, senha, SendLogin), o rótulo do campo de entrada pelo parâmetro, as mensagens do servidor e, inteiros, o
   PLANO B do iframe: #planoB, #abrirNovaAba, #cartaoLogin e o script da sonda, no fim. */
if (isset($this->dados['form'])) {
  $valorForm = $this->dados['form'];
}
$rotuloEntrada = \App\adms\Models\helper\AdmsParametro::rotuloCampoLogin(\App\adms\Models\helper\AdmsParametro::campoLoginPortal());
require_once dirname(__DIR__, 5) . '/core/ContaFamilia.php';
$familiaLigada = \VcContaFamilia::ligada(); /* Onda 7: na escola, o responsável entra aqui também */
if ($familiaLigada) {
  $rotuloEntrada = 'Login'; /* Onda 8 (D-67): a matrícula do aluno ou o login do responsável */
}
/* Fechamento (D-107): no ensino superior e no curso livre, o e-mail do cadastro ou a matrícula (o RA, ou o CPF, se a instituição escolheu) */
$entradaEmail = !\App\adms\Models\helper\AdmsParametro::naEscola();
if ($entradaEmail) {
  $campoNum = \App\adms\Models\helper\AdmsParametro::campoLoginPortal();
  $rotuloEntrada = 'E-mail ou ' . (\App\adms\Models\helper\AdmsParametro::noLivre() ? 'matrícula' : ($campoNum === 'cpf' ? 'CPF' : 'RA'));
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
  <?php /* E7 — PLANO B: dentro do Moodle (iframe), se o navegador não guarda a sessão (cookie de terceiros
     bloqueado, ex.: Safari/ITP), o formulário some e aparece o botão para abrir o portal em nova aba. Sem
     redirecionamento automático: não há laço de login. */ ?>
  <div class="alert alert-warning" id="planoB" hidden>
    <b>O seu navegador bloqueou o portal dentro do Moodle.</b> Alguns navegadores não guardam o acesso de um site aberto dentro de outro. Abra o portal numa aba própria:
    <a href="<?= URLACAD ?>login/acesso" target="_blank" rel="noopener" class="btn btn-warning w-100 mt-2" id="abrirNovaAba">Abrir o portal em nova aba</a>
  </div>
  <div id="cartaoLogin">
    <div class="card card-md">
      <div class="card-body">
        <h2 class="h2 text-center mb-1"><?= \App\adms\Models\helper\AdmsParametro::naEscola() ? 'Portal do aluno e do responsável' : 'Portal do aluno' /* 2.5 (D-91) */ ?></h2>
        <p class="text-secondary text-center mb-4"><?= $familiaLigada ? 'Área restrita para alunos e responsáveis' : 'Área restrita para alunos' ?></p>
        <form action="" method="POST" novalidate>
          <div class="mb-3">
            <label class="form-label" for="usuario"><?= htmlspecialchars($rotuloEntrada) ?></label>
            <div class="input-group input-group-flat">
              <input type="text" name="usuario" id="usuario" class="form-control" placeholder="<?= htmlspecialchars($rotuloEntrada) ?>" aria-label="<?= htmlspecialchars($rotuloEntrada) ?>" autocomplete="username" value="<?= htmlspecialchars((string) ($valorForm['usuario'] ?? '')); ?>" required>
              <span class="input-group-text"><i class="fas fa-user-graduate" aria-hidden="true"></i></span>
            </div>
            <?php if ($entradaEmail) : ?><div class="form-hint" id="dicaEntrada">O e-mail do seu cadastro ou o seu número de matrícula.</div><?php endif; ?>
            <?php if ($familiaLigada) : ?><div class="form-hint" id="dicaResponsavel">Aluno: o número de matrícula. Responsável: o CPF ou o e-mail que você escolheu no convite.</div><?php endif; ?>
          </div>
          <div class="mb-3">
            <label class="form-label" for="senha">Senha</label>
            <div class="input-group input-group-flat">
              <input type="password" name="senha" id="senha" class="form-control" placeholder="Senha" required<?= \VcSenha::atributos('atual') ?>>
              <span class="input-group-text"><i class="fas fa-lock" aria-hidden="true"></i></span>
            </div>
          </div>
          <div class="form-footer">
            <input type="submit" class="btn btn-primary w-100 botaoEnvia" name="SendLogin" value="Acessar">
          </div>
        </form>
      </div>
    </div>
    <div class="text-center text-secondary mt-3">
      <a href="<?= URLACAD . "login/esqueceu-senha" ?>" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'">Esqueci a senha</a>
    </div>
  </div>
  <?php include 'app/adms/Views/include_tabler/_acesso_rodape.php'; ?>
</div>
<script>
  /* E7 — sonda da sessão dentro do iframe (plano B) */
  (function () {
    if (window.self === window.top || !window.fetch) { return; }
    var base = '<?= URLACAD ?>login/sonda', planoB = function () { document.getElementById('planoB').hidden = false; var c = document.getElementById('cartaoLogin'); if (c) { c.hidden = true; } };
    fetch(base, { credentials: 'include', cache: 'no-store' }).then(function (r) { return r.json(); })
      .then(function (a) { return fetch(base + '?n=' + encodeURIComponent(a.n), { credentials: 'include', cache: 'no-store' }); })
      .then(function (r) { return r.json(); }).then(function (b) { if (!b.ok) { planoB(); } }).catch(planoB);
  })();
</script>
