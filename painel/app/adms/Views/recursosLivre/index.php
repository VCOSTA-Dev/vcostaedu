<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* Fechamento (D-106): os recursos do curso livre — o financeiro e a matrícula pela secretaria, de ligar e desligar.
   D-129 (1.103.0, C4): nos três tipos; na escola e no ensino superior, só o financeiro. */
$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$pode = (new \App\adms\Models\AdmsBotao())->valBotao(['salvar' => ['menu_controller' => 'recursos-livre', 'menu_metodo' => 'salvar']]);
$so = empty($pode['salvar']) ? ' disabled' : '';
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Recursos da instituição<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <p class="text-secondary"><?= !empty($this->dados['livre']) ? 'O curso livre nasce enxuto. ' : '' ?>Ligue o que a sua instituição usa. Desligar não apaga nada: as telas saem do menu e não abrem, para todos os perfis; ligando de novo, tudo volta como estava.</p>
      <form method="post" action="<?= URLADM ?>recursos-livre/salvar" class="card" id="formRecursosLivre" style="max-width: 48rem">
        <div class="card-body">
          <?php foreach ($this->dados['recursos'] as $r => $x) : ?>
            <label class="form-check form-switch mb-3">
              <input class="form-check-input" type="checkbox" name="recurso[<?= $h($r) ?>]" value="1" id="recurso_<?= $h($r) ?>"<?= $x['ligado'] ? ' checked' : '' ?><?= $h($so) ?>>
              <span class="form-check-label"><span class="fw-medium"><?= $h($x['nome']) ?></span>
                <span class="d-block text-secondary small"><?= $h($x['frase']) ?></span></span>
            </label>
          <?php endforeach; ?>
        </div>
        <?php if ($so === '') : ?>
        <div class="card-footer text-end"><button type="submit" class="btn btn-primary" id="salvarRecursos">Salvar</button></div>
        <?php endif; ?>
      </form>
    </div>
  </section>
</div>
