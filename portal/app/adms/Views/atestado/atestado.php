<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Pagina nao encontrada!");
}
/* E6 (D-376): o aluno emite o PRÓPRIO atestado de matrícula. O botão só aparece com a matrícula em curso. */
$h = fn($t) => htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
?>
<div class="vc-pagina">
  <?php ob_start(); ?><i class="fas fa-file-signature me-2 text-info"></i>Atestado de matrícula<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <div class="card shadow-sm">
        <div class="card-body">
          <p class="text-muted mb-3">O atestado sai em PDF, com um código para qualquer pessoa conferir a autenticidade no endereço impresso no documento.</p>
          <?php foreach ($this->dados['matriculas'] as $m) : ?>
            <div class="d-flex flex-wrap align-items-center mb-2 matricula-atestado" data-matricula="<?= htmlspecialchars((string) ((int) $m['id']), ENT_QUOTES, 'UTF-8') ?>">
              <span class="me-2"><b><?= $h($m['curso']) ?></b> — <i><?= $h($m['situacao']) ?></i></span>
              <?php if ($m['podeEmitir']) : ?>
                <form method="POST" action="<?= URLACAD . 'atestado/emitir' ?>" class="d-inline">
                  <input type="hidden" name="matricula" value="<?= htmlspecialchars((string) ((int) $m['id']), ENT_QUOTES, 'UTF-8') ?>">
                  <button type="submit" name="EmitirAtestado" value="1" class="btn btn-sm btn-info botao-atestado"><i class="fas fa-file-signature"></i> Emitir atestado</button>
                </form>
              <?php else : ?>
                <small class="text-muted">o atestado só é emitido enquanto a matrícula está em curso</small>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
          <?php if (!$this->dados['matriculas']) : ?><p class="text-muted">Você não tem matrícula em curso.</p><?php endif; ?>
        </div>
      </div>
      <div class="card shadow-sm">
        <div class="card-header"><b>Atestados emitidos</b></div>
        <div class="card-body table-responsive p-0">
          <table class="table table-sm mb-0" id="atestadosEmitidos">
            <thead><tr><th>Código</th><th>Curso</th><th>Emitido em</th><th>Situação</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($this->dados['emissoes'] as $e) : ?>
              <tr><td class="font-monospace"><?= $h($e['codigo']) ?></td><td><?= $h($e['curso']) ?></td><td><?= htmlspecialchars((string) (date('d/m/Y H:i', strtotime($e['emitido_em']))), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= (int) $e['situacao'] === 1 ? '<span class="badge bg-success-lt">Válido</span>' : '<span class="badge bg-danger-lt">Cancelado</span>' ?></td>
                <td class="text-end"><?php if ((int) $e['situacao'] === 1) : ?><a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" href="<?= htmlspecialchars((string) (URLACAD . 'atestado/pdf/' . (int) $e['id']), ENT_QUOTES, 'UTF-8') ?>"><i class="far fa-file-pdf"></i> PDF</a><?php endif; ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$this->dados['emissoes']) : ?><tr><td colspan="5" class="text-muted">Nenhum atestado emitido.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>
</div>
