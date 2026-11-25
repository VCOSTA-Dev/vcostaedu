<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* A pessoa responsável: novo ou editar. Antes de criar, o sistema procura a pessoa pelo e-mail e pelo CPF. */
$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$d = $this->dados;
$M = \App\adms\Models\escola\AdmsResponsaveis::class;
$editar = !empty($d['pessoa']);
$f = $d['form'] ?? [];
$aluno = $d['aluno'] ?? null;
$acao = $editar ? URLADM . 'responsaveis/editar/' . (int) $d['pessoa']['id'] : URLADM . 'responsaveis/cadastrar';
?>
<div class="vc-pagina">
  <?php ob_start(); ?><?= $editar ? 'Editar o responsável' : 'Novo responsável' ?><?php $paginaTitulo = ob_get_clean(); $paginaPretitulo = 'Responsáveis';
  include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <?php if (!empty($d['existe'])) : ?>
        <div class="alert alert-info" id="pessoaExistente">Esta pessoa já está cadastrada: <b><?= $h($d['existe']['nome']) ?></b>.
          <a href="<?= URLADM ?>responsaveis/<?= $aluno ? 'aluno/' . (int) $aluno['id'] : 'ver/' . (int) $d['existe']['id'] ?>" class="alert-link"><?= $aluno ? 'Escolha-a nos responsáveis do aluno' : 'Abrir o cadastro' ?></a>.</div>
      <?php endif; ?>
      <form method="post" action="<?= $h($acao) ?>" class="card" id="formResponsavel" style="max-width: 46rem">
        <div class="card-body">
          <?php if ($aluno) : ?><input type="hidden" name="aluno" value="<?= $h((int) $aluno['id']) ?>"><p class="text-secondary">Responsável de <b><?= $h($aluno['nome']) ?></b>.</p><?php endif; ?>
          <div class="mb-3"><label class="form-label required" for="nome">Nome</label><input type="text" name="nome" id="nome" class="form-control" maxlength="150" required value="<?= $h($f['nome'] ?? '') ?>"></div>
          <div class="row g-3 mb-3">
            <div class="col-md-6"><label class="form-label" for="email">E-mail</label><input type="email" name="email" id="email" class="form-control" maxlength="190" value="<?= $h($f['email'] ?? '') ?>"></div>
            <div class="col-md-3"><label class="form-label" for="celular">Celular</label><input type="text" inputmode="tel" name="celular" id="celular" class="form-control" placeholder="(11) 99999-0000" value="<?= $h($M::celularFormatado($f['celular'] ?? '')) ?>"></div>
            <div class="col-md-3"><label class="form-label" for="cpf">CPF (opcional)</label><input type="text" inputmode="numeric" name="cpf" id="cpf" class="form-control" value="<?= $h($M::cpfFormatado($f['cpf'] ?? '') ?: ($f['cpf'] ?? '')) ?>"></div>
          </div>
          <div class="row g-3 mb-3" id="enderecoResponsavel">
            <div class="col-md-6"><label class="form-label" for="endereco">Endereço <small class="text-muted">(opcional)</small></label><input type="text" name="endereco" id="endereco" class="form-control" maxlength="160" placeholder="Rua, avenida…" value="<?= $h($f['endereco'] ?? '') ?>"></div>
            <div class="col-md-2"><label class="form-label" for="numero">Número</label><input type="text" name="numero" id="numero" class="form-control" maxlength="20" value="<?= $h($f['numero'] ?? '') ?>"></div>
            <div class="col-md-4"><label class="form-label" for="complemento">Complemento</label><input type="text" name="complemento" id="complemento" class="form-control" maxlength="80" value="<?= $h($f['complemento'] ?? '') ?>"></div>
            <div class="col-md-4"><label class="form-label" for="bairro">Bairro</label><input type="text" name="bairro" id="bairro" class="form-control" maxlength="80" value="<?= $h($f['bairro'] ?? '') ?>"></div>
            <div class="col-md-4"><label class="form-label" for="cidade">Cidade</label><input type="text" name="cidade" id="cidade" class="form-control" maxlength="80" value="<?= $h($f['cidade'] ?? '') ?>"></div>
            <div class="col-md-1"><label class="form-label" for="uf">UF</label><input type="text" name="uf" id="uf" class="form-control" maxlength="2" value="<?= $h($f['uf'] ?? '') ?>"></div>
            <div class="col-md-3"><label class="form-label" for="cep">CEP</label><input type="text" inputmode="numeric" name="cep" id="cep" class="form-control" maxlength="9" value="<?= $h($f['cep'] ?? '') ?>"></div>
          </div>
          <?php if ($aluno) : ?>
            <div class="mb-3" style="max-width: 16rem"><label class="form-label required" for="parentesco">Parentesco com o aluno</label>
              <select name="parentesco" id="parentesco" class="form-select"><?php foreach ($M::PARENTESCOS as $x) : ?><option<?= ($f['parentesco'] ?? '') === $x ? ' selected' : '' ?>><?= $h($x) ?></option><?php endforeach; ?></select></div>
          <?php endif; ?>
          <div class="form-hint">Para entrar no portal, a pessoa precisa de e-mail ou de CPF: é com um deles que ela entra. Antes de criar, o sistema procura alguém com o mesmo e-mail ou CPF e oferece o cadastro que já existe.</div>
          <div id="avisoExiste" class="alert alert-info mt-3 d-none"></div>
        </div>
        <div class="card-footer"><button type="submit" class="btn btn-primary" id="salvarResponsavel">Salvar</button></div>
      </form>
    </div>
  </section>
</div>
<?php if (!$editar) : ?>
<script>
(function () {
  var aviso = document.getElementById('avisoExiste');
  function procurar() {
    var e = document.getElementById('email').value.trim(), c = document.getElementById('cpf').value.replace(/\D/g, '');
    if (!e && c.length !== 11) { aviso.classList.add('d-none'); return; }
    fetch('<?= URLADM ?>responsaveis/procurar?email=' + encodeURIComponent(e) + '&cpf=' + encodeURIComponent(c), { credentials: 'same-origin' })
      .then(function (r) { return r.json(); }).then(function (j) {
        if (!j.existe) { aviso.classList.add('d-none'); return; }
        aviso.textContent = 'Já existe uma pessoa com este e-mail ou CPF: ' + j.nome + '. Não é preciso cadastrar de novo: use o cadastro que já existe.';
        aviso.classList.remove('d-none');
      }).catch(function () {});
  }
  document.getElementById('email').addEventListener('change', procurar);
  document.getElementById('cpf').addEventListener('change', procurar);
})();
</script>
<?php endif; ?>
