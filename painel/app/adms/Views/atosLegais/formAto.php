<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
$a = $this->dados['ato'] ?? [];
$id = (int) ($this->dados['id'] ?? 0);
$v = fn($c) => htmlspecialchars((string) ($a[$c] ?? ''), ENT_QUOTES, 'UTF-8');
?>
<div class="vc-pagina">
  <?php ob_start(); ?><?= $id > 0 ? 'Editar ato legal' : 'Novo ato legal' ?><?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php
      if (isset($_SESSION['msg'])) {
        echo $_SESSION['msg'];
        unset($_SESSION['msg']);
      }
      ?>
      <form method="POST" action="" novalidate>
        <div class="card shadow">
          <div class="card-body">
            <div class="row g-2">
              <div class="mb-3 col-md-6"><label class="form-label" for="orgao">Órgão</label><input type="text" name="orgao" id="orgao" class="form-control" value="<?= $v('orgao') ?>" placeholder="ex.: Conselho Estadual de Educação; Ministério da Educação"></div>
              <div class="mb-3 col-md-3"><label class="form-label" for="tipo">Tipo</label>
                <select name="tipo" id="tipo" class="form-select"><option value=""></option>
                  <?php foreach (\App\adms\Models\atosLegais\AdmsAtosLegais::TIPOS as $t) : ?><option value="<?= htmlspecialchars($t, ENT_QUOTES, 'UTF-8') ?>" <?= htmlspecialchars((string) (($a['tipo'] ?? '') === $t ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>><?= htmlspecialchars($t, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
                </select></div>
              <div class="mb-3 col-md-3"><label class="form-label" for="modalidade_id">Modalidade a que se aplica</label>
                <select name="modalidade_id" id="modalidade_id" class="form-select"><option value=""></option>
                  <?php foreach (\App\adms\Models\modalidades\AdmsModalidades::ativas() as $m) : ?><option value="<?= htmlspecialchars((string) ((int) $m['id']), ENT_QUOTES, 'UTF-8') ?>" <?= htmlspecialchars((string) ((int) ($a['modalidade_id'] ?? 0) === (int) $m['id'] ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>><?= htmlspecialchars($m['nome']) ?></option><?php endforeach; ?>
                </select></div>
            </div>
            <div class="row g-2">
              <div class="mb-3 col-md-3"><label class="form-label" for="numero">Número</label><input type="text" name="numero" id="numero" class="form-control" value="<?= $v('numero') ?>"></div>
              <div class="mb-3 col-md-3"><label class="form-label" for="data_ato">Data do ato</label><input type="date" name="data_ato" id="data_ato" class="form-control" value="<?= $v('data_ato') ?>"></div>
              <div class="mb-3 col-md-4"><label class="form-label" for="veiculo_publicacao">Veículo de publicação <small class="text-muted">(opcional)</small></label><input type="text" name="veiculo_publicacao" id="veiculo_publicacao" class="form-control" value="<?= $v('veiculo_publicacao') ?>" placeholder="ex.: Diário Oficial"></div>
              <div class="mb-3 col-md-2"><label class="form-label" for="data_publicacao">Publicado em</label><input type="date" name="data_publicacao" id="data_publicacao" class="form-control" value="<?= $v('data_publicacao') ?>"></div>
            </div>
            <div class="row g-2">
              <div class="mb-3 col-md-3"><label class="form-label" for="vigencia_inicio">Vigência: início</label><input type="date" name="vigencia_inicio" id="vigencia_inicio" class="form-control" value="<?= $v('vigencia_inicio') ?>"></div>
              <div class="mb-3 col-md-3"><label class="form-label" for="vigencia_fim">Vigência: fim <small class="text-muted">(vazio = sem fim)</small></label><input type="date" name="vigencia_fim" id="vigencia_fim" class="form-control" value="<?= $v('vigencia_fim') ?>"></div>
              <div class="mb-3 col-md-2"><label class="form-label" for="situacao">Situação</label>
                <select name="situacao" id="situacao" class="form-select"><option value="1" <?= htmlspecialchars((string) ((int) ($a['situacao'] ?? 1) === 1 ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>>Ativo</option><option value="2" <?= htmlspecialchars((string) ((int) ($a['situacao'] ?? 1) === 2 ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>>Inativo</option></select></div>
              <div class="mb-3 col-md-4"><label class="form-label" for="observacao">Observação</label><input type="text" name="observacao" id="observacao" class="form-control" value="<?= $v('observacao') ?>"></div>
            </div>
          </div>
          <div class="card-footer"><input type="submit" name="SalvarAto" value="Salvar" class="btn btn-primary"> <a href="<?= URLADM . 'atos-legais/listar' ?>" class="btn btn-secondary">Cancelar</a></div>
        </div>
      </form>
    </div>
  </section>
</div>
