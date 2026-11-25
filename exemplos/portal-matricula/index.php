<?php
/* A INSCRIÇÃO: nome, e-mail, documento (fictício nas provas), sexo e curso. */
require __DIR__ . '/lib.php';
$c = cfg();
topo('Inscrição');
if (empty($c['chave']) || empty($c['api'])) {
    echo '<div class="erro">O exemplo ainda não foi configurado: copie <code>config.exemplo.php</code> para <code>config.local.php</code> e preencha o endereço da API e a chave.</div>';
    rodape();
    exit;
}
$erro = $_SESSION['erro'] ?? '';
$antes = $_SESSION['antes'] ?? [];
unset($_SESSION['erro'], $_SESSION['antes']);
?>
<?php if ($erro !== '') : ?><div class="erro" id="erroInscricao"><?= h($erro) ?></div><?php endif; ?>
<form method="POST" action="enviar.php" class="cartao" id="formInscricao">
  <input type="hidden" name="token" value="<?= h(token()) ?>">
  <input type="hidden" name="envio" value="<?= h(bin2hex(random_bytes(12))) ?>">
  <label for="nome">Nome completo</label><input id="nome" name="nome" required maxlength="150" value="<?= h($antes['nome'] ?? '') ?>">
  <label for="email">E-mail</label><input id="email" name="email" type="email" required maxlength="150" value="<?= h($antes['email'] ?? '') ?>">
  <label for="cpf">CPF</label><input id="cpf" name="cpf" required inputmode="numeric" maxlength="14" placeholder="000.000.000-00" value="<?= h($antes['cpf'] ?? '') ?>">
  <label for="sexo">Sexo</label>
  <select id="sexo" name="sexo" required><option value="">— escolha —</option><option value="feminino">Feminino</option><option value="masculino">Masculino</option><option value="outros">Outros</option></select>
  <label for="curso">Curso</label>
  <select id="curso" name="curso" required>
    <?php foreach ((array) ($c['cursos'] ?? []) as $i => $cu) : ?><option value="<?= (int) $i ?>"><?= h($cu['nome']) ?> (<?= (int) $cu['ano'] ?>)</option><?php endforeach; ?>
  </select>
  <button type="submit" id="enviarInscricao">Fazer a inscrição</button>
</form>
<?php rodape();
