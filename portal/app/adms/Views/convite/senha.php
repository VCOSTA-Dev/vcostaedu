<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* Onda 7 (D-63): o convite que vale — a pessoa confere o próprio nome, vê de quais alunos é responsável (só o primeiro
   nome de cada um) e cria a senha. O código vai no corpo do POST, nunca na URL. O mesmo formulário serve ao link de
   senha nova (o "Esqueci a senha" do responsável). */
$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$c = $this->dados['convite'];
$novaSenha = ($c['tipo'] ?? '') === 'senha';
$alunos = \VcContaFamilia::lista($this->dados['alunos'] ?? []);
/* Onda 8 (D-67): o login da conta — o que ela já tem, ou a escolha entre o e-mail e o CPF do cadastro */
$loginAtual = (string) ($this->dados['loginAtual'] ?? '');
$opcoes = $this->dados['opcoesLogin'] ?? [];
$livres = array_values(array_filter($opcoes, fn($o) => $o['livre']));
$escolha = (string) ($this->dados['escolha'] ?? '');
if (!in_array($escolha, array_column($livres, 'tipo'), true)) {
  $escolha = $livres[0]['tipo'] ?? '';
}
?>
<div class="container container-tight py-4">
  <?php include 'app/adms/Views/include_tabler/_acesso_marca.php'; ?>
  <div class="card card-md" id="conviteValido">
    <div class="card-body">
      <h2 class="h2 text-center mb-3"><?= $novaSenha ? 'Senha nova' : 'Boas-vindas ao portal' ?></h2>
      <p class="mb-1">Olá, <b id="nomeConvidado"><?= $h($c['nome']) ?></b>.</p>
      <?php if ($alunos !== '') : ?><p class="mb-3" id="alunosConvite">Você acompanha <b><?= $h($alunos) ?></b> no portal da escola.</p><?php endif; ?>
      <p class="text-secondary small mb-3">Não é você? Não continue: avise a secretaria da escola.</p>
      <?php if (!empty($this->dados['erro'])) : ?><div class="alert alert-danger" id="erroSenha"><?= $h($this->dados['erro']) ?></div><?php endif; ?>
      <form action="<?= URLACAD ?>convite/definir" method="POST" novalidate id="formSenhaConvite">
        <input type="hidden" name="codigo" value="<?= $h($this->dados['codigo']) ?>">
        <div class="mb-3">
          <label class="form-label" for="senha"><?= $novaSenha ? 'Senha nova' : 'Crie a sua senha' ?></label>
          <input type="password" id="senha" name="senha" class="form-control" required<?= \VcSenha::atributos('nova', ['nome' => (string) $c['nome'], 'email' => (string) $c['email']]) ?>>
          <div class="form-hint"><?= $h(\VcSenha::ajuda()) ?></div>
        </div>
        <div class="mb-3">
          <label class="form-label" for="senha_repetido">Repita a senha</label>
          <input type="password" id="senha_repetido" name="senha_repetido" class="form-control" required<?= \VcSenha::atributos('confirmar', [], '', '#senha') ?>>
        </div>
        <?php if ($loginAtual !== '') : ?>
          <p class="small text-secondary" id="loginDaConta">Para entrar nas próximas vezes, use o seu login, <b><?= $h(\VcLoginPortal::mostrar($loginAtual)) ?></b>, e esta senha.</p>
        <?php elseif (count($opcoes) === 1) : ?>
          <input type="hidden" name="login_tipo" value="<?= $h($opcoes[0]['tipo']) ?>">
          <p class="small text-secondary" id="loginDaConta">O seu login no portal será <?= $h($opcoes[0]['rotulo']) ?>. Para entrar nas próximas vezes, use esse login e esta senha.</p>
        <?php elseif ($opcoes) : ?>
          <fieldset class="mb-3" id="escolhaLogin">
            <legend class="form-label">Com o que você quer entrar no portal?</legend>
            <?php foreach ($opcoes as $o) : ?>
              <label class="form-check">
                <input class="form-check-input" type="radio" name="login_tipo" value="<?= $h($o['tipo']) ?>"<?= $o['tipo'] === $escolha ? ' checked' : '' ?><?= $o['livre'] ? '' : ' disabled' ?>>
                <span class="form-check-label"><?= $h(ucfirst($o['rotulo'])) ?><?= $o['livre'] ? '' : ' — já é o login de outra conta' ?></span>
              </label>
            <?php endforeach; ?>
            <div class="form-hint">É o seu login: só ele entra, com esta senha. A secretaria da escola pode trocá-lo depois.</div>
          </fieldset>
        <?php endif; ?>
        <div class="form-footer">
          <button type="submit" class="btn btn-primary w-100" id="criarSenha"><?= $novaSenha ? 'Gravar a senha e entrar' : 'Criar a senha e entrar' ?></button>
        </div>
      </form>
    </div>
  </div>
  <?php include 'app/adms/Views/include_tabler/_acesso_rodape.php'; ?>
</div>
