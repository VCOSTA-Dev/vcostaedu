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
  <?php ob_start(); ?><a href="<?= URLADM . "cursos/listar" ?>" class="btn btn-outline-primary rounded-circle" title="Voltar para Cursos"><i class="fas fa-arrow-left"></i></a> Editar curso<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
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
          <div class="card shadow">
            <div class="card-body">
              <?php
              if ($this->dados['botao']['vis_cur']) {
              ?>
                <a href="<?= htmlspecialchars((string) (URLADM . "cursos/visualizar/{$valorForm['id']}"), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary me-1" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'"><i class="far fa-eye"></i> Visualizar</a>
              <?php
              }
              ?>
            </div>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col-12">
          <div class="card">
            <form action="" method="POST" enctype="multipart/form-data" novalidate>
              <input type="hidden" name="id" value="<?= htmlspecialchars((string) ((isset($valorForm['id']) ? $valorForm['id'] : '')), ENT_QUOTES, 'UTF-8') ?>">
              <div class="card-body">
              <div class="row g-2">
                  <div class="mb-3 col-md-12">
                    <label class="form-label" for="nome">Nome do curso</label>
                    <input type="text" class="form-control" name="nome" id="nome" placeholder="Nome do curso" value="<?= htmlspecialchars((string) ((isset($valorForm['nome']) ? $valorForm['nome'] : '')), ENT_QUOTES, 'UTF-8') ?>" required>
                  </div>
                </div>
                <div class="row g-2">
                  <?php /* E8c (D-269): "Período" aqui é o TURNO do curso, não etapa. E8d (P-2): lista de turnos, ESCONDIDA na
                     modalidade EaD (o turno não se aplica; o servidor grava 1 se vier vazio). */
                  $turnoAtual = (int) ($valorForm['periodo'] ?? 0); $M = \App\adms\Models\modalidades\AdmsModalidades::class; ?>
                  <div class="mb-3 col-md-4" id="grupoTurno" data-ead="<?= htmlspecialchars(json_encode($M::idsEad()), ENT_QUOTES, 'UTF-8') ?>">
                    <label class="form-label" for="periodo">Turno</label>
                    <select class="form-select" name="periodo" id="periodo">
                      <?php foreach ($M::turnos() as $tid => $tnome) : ?>
                        <option value="<?= (int) $tid ?>" <?= $turnoAtual === $tid ? 'selected' : '' ?>><?= htmlspecialchars($tnome, ENT_QUOTES, 'UTF-8') ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="mb-3 col-md-2">
                    <label class="form-label" for="exige_tcc">Exige TCC</label>
                    <select name="exige_tcc" id="exige_tcc" class="form-select">
                      <option value="0" <?= htmlspecialchars((string) ((int) ($valorForm['exige_tcc'] ?? 0) === 0 ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>>Não</option>
                      <option value="1" <?= htmlspecialchars((string) ((int) ($valorForm['exige_tcc'] ?? 0) === 1 ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>>Sim</option>
                    </select>
                    <small class="text-muted" id="ajudaExigeTcc"<?= htmlspecialchars((string) ((int) ($valorForm['exige_tcc'] ?? 0) === 1 ? '' : ' hidden'), ENT_QUOTES, 'UTF-8') ?>>A conclusão exige o TCC aprovado.</small>
                  </div>
                  <div class="mb-3 col-md-4">
                    <label class="form-label" for="modalidade_id">Modalidade padrão</label>
                    <select name="modalidade_id" id="modalidade_id" class="form-select">
                      <?php foreach (\App\adms\Models\modalidades\AdmsModalidades::ativas() as $mod) : ?>
                        <option value="<?= htmlspecialchars((string) ((int) $mod['id']), ENT_QUOTES, 'UTF-8') ?>" <?= htmlspecialchars((string) ((int) ($valorForm['modalidade_id'] ?? 1) === (int) $mod['id'] ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>><?= htmlspecialchars($mod['nome'], ENT_QUOTES, 'UTF-8') ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="mb-3 col-md-4">
                    <label class="form-label" for="localidade">Polo padrão</label>
                    <select name="localidade" id="localidade" class="form-select">
                      <?php foreach (\App\adms\Models\polos\AdmsListarPolos::ativos() as $polo) : ?>
                        <option value="<?= htmlspecialchars((string) ((int) $polo['id']), ENT_QUOTES, 'UTF-8') ?>" <?= htmlspecialchars((string) ((int) ($valorForm['localidade'] ?? 0) === (int) $polo['id'] ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>><?= htmlspecialchars($polo['nome'], ENT_QUOTES, 'UTF-8') ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <?php $valorForm = $valorForm ?? []; include __DIR__ . '/_identidadeCurso.php'; /* E1a */ ?>
                <div class="row g-2">
                  <div class="mb-3 col-md-12">
                    <label class="form-label" for="descricao">Descrição</label>
                    <textarea name="descricao" id="descricao" rows="5" class="form-control"><?= htmlspecialchars((string) ((isset($valorForm['descricao']) ? $valorForm['descricao'] : null)), ENT_QUOTES, 'UTF-8') ?></textarea>
                  </div>
                </div>
              </div>
              <div class="card-footer">
                <input type="submit" class="btn btn-primary botaoEnvia" name="EditCursoNovo" value="Salvar">
                <a href="<?= htmlspecialchars((string) (URLADM . "cursos/visualizar/{$valorForm['id']}"), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secondary" onclick="this.style.pointerEvents = 'none'; this.textContent = 'Processando...'">Cancelar</a>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<script>
  /* E8d (P-2): o turno some quando a modalidade padrão do curso é EaD */
  document.addEventListener('DOMContentLoaded', function () {
    var g = document.getElementById('grupoTurno'), m = document.getElementById('modalidade_id');
    if (!g || !m) { return; }
    var ead = JSON.parse(g.getAttribute('data-ead') || '[]').map(String);
    var ajusta = function () { g.hidden = ead.indexOf(String(m.value)) !== -1; };
    m.addEventListener('change', ajusta); ajusta();
  });
  /* a ajuda do TCC só aparece com "Exige TCC" = Sim */
  document.addEventListener('DOMContentLoaded', function () {
    var s = document.getElementById('exige_tcc'), a = document.getElementById('ajudaExigeTcc');
    if (!s || !a) { return; }
    var ajusta = function () { a.hidden = s.value !== '1'; };
    s.addEventListener('change', ajusta); ajusta();
  });
</script>
