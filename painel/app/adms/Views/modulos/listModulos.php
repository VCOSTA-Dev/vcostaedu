<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
$modulos = $this->dados['modulos'] ?? [];
$grades = $this->dados['grades'] ?? [];
$gradeId = (int) ($this->dados['gradeId'] ?? 0);
$botao = $this->dados['botao'] ?? [];
$e = fn($t) => htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Módulos<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>

  <section class="page-body">
    <div class="container-xl">
      <?php
      if (isset($_SESSION['msg'])) {
        echo $_SESSION['msg'];
        unset($_SESSION['msg']);
      }
      ?>
      <div class="alert alert-info">
        <p class="mb-0">O módulo pertence a uma <b>grade</b> de um curso. A carga horária do módulo é a
          soma das disciplinas dele na matriz; a ordem é a posição do módulo na grade.</p>
      </div>

      <div class="card shadow">
        <div class="card-header">
          <form class="d-flex flex-wrap align-items-center gap-2" method="GET" action="<?= URLADM . 'modulos/listar' ?>">
            <label class="me-2" for="grade">Grade</label>
            <select name="grade" id="grade" class="form-select form-select-sm me-2">
              <option value="">Todas</option>
              <?php foreach ($grades as $g) : ?>
                <option value="<?= htmlspecialchars((string) ((int) $g['id']), ENT_QUOTES, 'UTF-8') ?>" <?= htmlspecialchars((string) ((int) $g['id'] === $gradeId ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>>
                  <?= $e($g['curso']) ?> — grade <?= htmlspecialchars((string) ((int) $g['versao']), ENT_QUOTES, 'UTF-8') ?>
                </option>
              <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-sm btn-outline-primary">Filtrar</button>
          </form>
          <?php if (!empty($botao['cadastrar'])) : ?>
            <div class="card-actions">
              <a href="<?= URLADM . 'modulos/cadastrar' . ($gradeId ? '?grade=' . $gradeId : '') ?>" class="btn btn-sm btn-success">
                <i class="fas fa-plus"></i> Novo módulo
              </a>
            </div>
          <?php endif; ?>
        </div>
        <div class="card-body table-responsive">
          <?php $Ui = \App\adms\Models\helper\AdmsUi::class; /* E8c (R2/R3/R4) */ ?>
          <table class="table table-hover table-sm table-compacta lista-dados" id="tabelaModulos" data-titulo="Módulos" data-ordem='[[0,"asc"],[1,"asc"],[2,"asc"]]'>
            <thead>
              <tr>
                <th>Curso</th>
                <th>Grade</th>
                <th class="num" style="width:70px">Ordem</th>
                <th>Módulo</th>
                <th class="num">Disciplinas</th>
                <th class="num">CH</th>
                <th>Situação</th>
                <th class="nao-exportar nao-ordenar text-end">Ações</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($modulos as $m) :
                $trava = (int) $m['situacaoGrade'] === 1 ? null : 'Grade «' . $m['nomeSituacaoGrade'] . '»: só uma grade Aberta muda'; ?>
                <tr>
                  <td><?= $e($m['curso']) ?></td>
                  <td data-order="<?= htmlspecialchars((string) ((int) $m['grade']), ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars((string) ((int) $m['grade']), ENT_QUOTES, 'UTF-8') ?>
                    <span class="badge bg-<?= $e($m['corSituacaoGrade'] ?: 'secondary') ?>-lt"><?= $e($m['nomeSituacaoGrade']) ?></span>
                  </td>
                  <td class="num"><b><?= htmlspecialchars((string) ((int) $m['ordem']), ENT_QUOTES, 'UTF-8') ?></b></td>
                  <td><?= $e($m['codnome']) ?><br><small class="text-muted"><?= $e($m['descricao']) ?></small></td>
                  <td class="num"><?= htmlspecialchars((string) ((int) $m['disciplinas']), ENT_QUOTES, 'UTF-8') ?></td>
                  <td class="num"><?= htmlspecialchars((string) ((int) $m['ch']), ENT_QUOTES, 'UTF-8') ?> h</td>
                  <td>
                    <?= (int) $m['situacao'] === 1 ? '<span class="badge bg-success-lt">Ativo</span>' : '<span class="badge bg-secondary-lt">Inativo</span>' ?>
                  </td>
                  <td class="acoes-linha">
                    <?php if (!empty($botao['matriz'])) : ?>
                      <?= $Ui::acao('ver', URLADM . 'matriz/visualizar/' . (int) $m['grade_id'], 'Ver a matriz da grade', null, ['icone' => 'fas fa-sitemap']) ?>
                    <?php endif; ?>
                    <?php if (!empty($botao['editar'])) : ?>
                      <?= $Ui::acao('editar', URLADM . 'modulos/editar/' . (int) $m['id'], 'Editar o módulo', $trava) ?>
                    <?php endif; ?>
                    <?php if (!empty($botao['apagar'])) : ?>
                      <?= $Ui::acaoPost('excluir', URLADM . 'modulos/apagar/' . (int) $m['id'], [], 'Apagar o módulo',
                        $trava ?? ((int) $m['disciplinas'] > 0 ? 'Tem ' . (int) $m['disciplinas'] . ' disciplina(s): remova-as antes de apagar' : null),
                        ['confirmar' => 'Apagar o módulo ' . $m['codnome'] . '? Só módulo sem disciplina, sem matrícula e sem oferta pode ser apagado.']) ?>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>
</div>
