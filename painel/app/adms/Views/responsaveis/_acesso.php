<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* O acesso ao portal, na ficha do responsável (Onda 7, Etapa 3; D-63): gerar o convite e, logo depois de gerado, enviar
   por e-mail, copiar o link ou abrir o WhatsApp com a mensagem pronta; cancelar o convite; bloquear e desbloquear. O
   link aparece só para quem acabou de gerar (o banco guarda só o resumo do código). Incluída por ver.php. */
$C = \App\adms\Models\escola\AdmsResponsaveisConvite::class;
$podeAc = (new \App\adms\Models\AdmsBotao())->valBotao([
  'convidar' => ['menu_controller' => 'responsaveis', 'menu_metodo' => 'convidar'],
  'enviar' => ['menu_controller' => 'responsaveis', 'menu_metodo' => 'enviar-convite'],
  'cancelar' => ['menu_controller' => 'responsaveis', 'menu_metodo' => 'cancelar-convite'],
  'bloquear' => ['menu_controller' => 'responsaveis', 'menu_metodo' => 'bloquear'],
  'login' => ['menu_controller' => 'responsaveis', 'menu_metodo' => 'login'],
]);
/* Onda 8 (D-67): o login do portal da pessoa (o CPF ou o e-mail escolhido no convite) e as escolhas para trocar */
$loginAtual = (string) ($p['login'] ?? '');
$opcoesLogin = $loginAtual !== '' ? $C::conta()->opcoesLogin((int) $p['id']) : [];
$conv = $d['convite'] ?? null;
$g = $_SESSION['vc_convite_gerado'] ?? null;
$gerado = ($g && (int) $g['pessoa'] === (int) $p['id'] && $conv && (int) $conv['id'] === (int) $g['id']) ? $g : null;
$link = $gerado ? $C::link((string) $gerado['codigo']) : '';
$texto = $gerado ? $C::mensagem($p, $link, (string) $conv['expira_em']) : '';
$wa = $gerado ? $C::whatsapp($p, $texto) : null;
$temEmail = filter_var((string) $p['email'], FILTER_VALIDATE_EMAIL) !== false;
$envio = $C::envioConfigurado();
$campo = fn() => '<input type="hidden" name="responsavel_id" value="' . (int) $p['id'] . '">';
?>
<div class="card mt-3" id="acessoPortal">
  <div class="card-header"><h3 class="card-title">Acesso ao portal</h3></div>
  <div class="card-body">
    <?php if (!empty($p['bloqueado'])) : ?>
      <div class="alert alert-danger" id="acessoBloqueado">Acesso bloqueado<?= $p['bloqueado_em'] ? ' em ' . $h(date('d/m/Y', strtotime((string) $p['bloqueado_em']))) : '' ?>. Motivo: <?= $h($p['bloqueado_motivo'] ?: '—') ?></div>
    <?php elseif (!empty($p['senha'])) : ?>
      <p class="mb-2" id="acessoAtivo">Já criou a senha<?= $p['ativado_em'] ? ' em ' . $h(date('d/m/Y', strtotime((string) $p['ativado_em']))) : '' ?><?= $p['ultimo_acesso'] ? '; último acesso em ' . $h(date('d/m/Y H:i', strtotime((string) $p['ultimo_acesso']))) : '' ?>.
        Se esqueceu a senha, a pessoa usa "Esqueci a senha" no portal (com e-mail) ou recebe um convite novo, que cria uma senha nova.</p>
    <?php elseif ($conv) : ?>
      <p class="mb-2" id="conviteAberto">Convite em aberto, válido até <b><?= $h(date('d/m/Y H:i', strtotime((string) $conv['expira_em']))) ?></b><?= $conv['enviado_em'] ? '; enviado por e-mail em ' . $h(date('d/m/Y H:i', strtotime((string) $conv['enviado_em']))) : '' ?>.</p>
    <?php else : ?>
      <p class="mb-2 text-secondary" id="semConvite">Ainda não recebeu convite. O convite é um link de uso único, que vale <?= $h($C::dias()) ?> dia(s): por ele a pessoa confere o nome, cria a senha e entra.</p>
    <?php endif; ?>

    <?php if ($loginAtual !== '') : ?>
      <p class="mb-2" id="loginPortal" data-login="<?= $h($loginAtual) ?>">Login do portal: <b><?= $h(\VcLoginPortal::mostrar($loginAtual)) ?></b> (só ele entra, com a senha da pessoa).</p>
      <?php $outras = array_values(array_filter($opcoesLogin, fn($o) => $o['valor'] !== $loginAtual)); ?>
      <?php if ($outras && !empty($podeAc['login'])) : ?>
        <details class="mb-3" id="blocoTrocarLogin"><summary>Trocar o login</summary>
          <form method="post" action="<?= URLADM ?>responsaveis/login" class="mt-2"><?= $campo() ?>
            <?php foreach ($outras as $o) : ?>
              <label class="form-check"><input class="form-check-input" type="radio" name="login_tipo" value="<?= $h($o['tipo']) ?>"<?= $o['livre'] ? '' : ' disabled' ?>>
                <span class="form-check-label">Usar <?= $h($o['rotulo']) ?><?= $o['livre'] ? '' : ' — já é o login de outra conta' ?></span></label>
            <?php endforeach; ?>
            <button type="submit" class="btn btn-outline-primary mt-2" id="trocarLogin">Trocar o login</button>
            <div class="form-hint">O login é o e-mail ou o CPF do cadastro. Para usar um e-mail ou um CPF novo, edite antes o cadastro. A senha continua a mesma.</div>
          </form>
        </details>
      <?php endif; ?>
    <?php elseif (empty($p['senha'])) : ?>
      <p class="mb-2 text-secondary small" id="loginNoConvite">O login do portal (o e-mail ou o CPF do cadastro) é escolhido pela pessoa no convite.</p>
    <?php endif; ?>

    <?php if ($gerado) : ?>
      <div class="border rounded p-3 mb-3 bg-light" id="conviteGerado">
        <div class="fw-bold mb-2">O convite que você acabou de gerar</div>
        <label class="form-label" for="linkConvite">O link (uso único)</label>
        <div class="input-group mb-2">
          <input type="text" class="form-control" id="linkConvite" value="<?= $h($link) ?>" readonly>
          <button type="button" class="btn btn-outline-primary" id="copiarLink" data-copiar="#linkConvite"><i class="far fa-copy"></i> Copiar o link</button>
        </div>
        <label class="form-label" for="textoConvite">A mensagem (ajuste o texto em Regras da escola)</label>
        <textarea class="form-control mb-2" id="textoConvite" rows="4" readonly><?= $h($texto) ?></textarea>
        <div class="d-flex flex-wrap gap-2">
          <button type="button" class="btn btn-outline-secondary" id="copiarMensagem" data-copiar="#textoConvite"><i class="far fa-copy"></i> Copiar a mensagem</button>
          <?php if ($wa) : ?><a class="btn btn-success" id="abrirWhatsapp" href="<?= $h($wa) ?>" target="_blank" rel="noopener"><i class="fab fa-whatsapp"></i> Abrir no WhatsApp</a>
          <?php else : ?><span class="text-secondary small align-self-center" id="semCelular">Sem celular no cadastro: o WhatsApp não está disponível.</span><?php endif; ?>
          <?php if (!empty($podeAc['enviar'])) : ?>
            <?php if ($temEmail && $envio) : ?>
              <form method="post" action="<?= URLADM ?>responsaveis/enviar-convite" class="d-inline"><?= $campo() ?><button type="submit" class="btn btn-primary" id="enviarEmail"><i class="far fa-envelope"></i> Enviar por e-mail</button></form>
            <?php else : ?>
              <span class="text-secondary small align-self-center" id="semEnvio"><?= $temEmail ? 'O envio de e-mail não está configurado (Configurações de e-mail).' : 'Sem e-mail no cadastro.' ?> Copie o link ou use o WhatsApp.</span>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <div class="d-flex flex-wrap gap-2">
      <?php if (empty($p['bloqueado']) && !empty($podeAc['convidar'])) : ?>
        <form method="post" action="<?= URLADM ?>responsaveis/convidar" class="d-inline"<?= $conv ? ' data-vc-confirma="O convite anterior deixa de valer. Gerar outro?"' : '' ?>><?= $campo() ?>
          <button type="submit" class="btn btn-primary" id="gerarConvite"><i class="fas fa-paper-plane"></i> <?= !empty($p['senha']) ? 'Gerar convite (senha nova)' : ($conv ? 'Gerar outro convite' : 'Gerar convite') ?></button></form>
      <?php endif; ?>
      <?php if ($conv && !empty($podeAc['cancelar'])) : ?>
        <form method="post" action="<?= URLADM ?>responsaveis/cancelar-convite" class="d-inline"><?= $campo() ?><button type="submit" class="btn btn-outline-secondary" id="cancelarConvite">Cancelar o convite</button></form>
      <?php endif; ?>
    </div>

    <?php if (!empty($podeAc['bloquear'])) : ?>
      <?php if (empty($p['bloqueado'])) : ?>
        <details class="mt-3" id="blocoBloquear"><summary class="text-danger">Bloquear o acesso</summary>
          <form method="post" action="<?= URLADM ?>responsaveis/bloquear" class="mt-2"><?= $campo() ?><input type="hidden" name="bloquear" value="1">
            <label class="form-label" for="motivoBloqueio">Motivo</label>
            <input type="text" class="form-control mb-2" id="motivoBloqueio" name="motivo" maxlength="255" required>
            <button type="submit" class="btn btn-danger" id="bloquearAcesso">Bloquear</button>
            <div class="form-hint">A pessoa não entra mais no portal, e o convite em aberto é cancelado. Os papéis dela continuam registrados.</div>
          </form>
        </details>
      <?php else : ?>
        <form method="post" action="<?= URLADM ?>responsaveis/bloquear" class="mt-3"><?= $campo() ?><input type="hidden" name="bloquear" value="0"><button type="submit" class="btn btn-outline-success" id="desbloquearAcesso">Desbloquear o acesso</button></form>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
<script>
  document.querySelectorAll('#acessoPortal [data-copiar]').forEach(function (b) {
    b.addEventListener('click', function () {
      var c = document.querySelector(b.getAttribute('data-copiar'));
      c.select();
      var feito = function () { var t = b.innerHTML; b.textContent = 'Copiado'; setTimeout(function () { b.innerHTML = t; }, 1500); };
      if (navigator.clipboard && window.isSecureContext) { navigator.clipboard.writeText(c.value).then(feito, function () { document.execCommand('copy'); feito(); }); } else { document.execCommand('copy'); feito(); }
    });
  });
</script>
