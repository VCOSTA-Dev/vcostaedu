<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
$cursos = $this->dados['cursos'] ?? [];
$botao = $this->dados['botao'] ?? [];
$e = fn($t) => htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Matriz curricular<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>

  <section class="page-body">
    <div class="container-xl">
      <?php
      if (isset($_SESSION['msg'])) {
        echo $_SESSION['msg'];
        unset($_SESSION['msg']);
      }
      ?>
      <div class="alert alert-info">
        <?php $semMod = \App\adms\Models\helper\AdmsModuloInterno::ativo(); /* E8c (D-268) */ ?>
        <?php if ($semMod) : ?>
        <p class="mb-0">Curso → <b>grade</b> → disciplinas. A carga horária da grade é a soma das disciplinas.</p>
        <?php else : ?>
        <p class="mb-0">Curso → <b>grade</b> → <b>módulos</b> → disciplinas. A carga horária do módulo é a soma das
          disciplinas; a da grade, a soma dos módulos.</p>
        <?php endif; ?>
      </div>

      <?php if (!$cursos) : ?>
        <div class="alert alert-info">Nenhum curso cadastrado.</div>
      <?php endif; ?>

      <?php foreach ($cursos as $c) : ?>
        <div class="card shadow" id="curso-<?= htmlspecialchars((string) ((int) $c['id']), ENT_QUOTES, 'UTF-8') ?>">
          <div class="card-header">
            <h3 class="card-title"><?= $e($c['nome']) ?><?= empty($c['ativo']) ? ' <span class="badge bg-secondary-lt ms-1">Inativo</span>' : '' /* E8d (P-8) */ ?></h3>
            <?php if (!empty($botao['cad_grade'])) : ?>
              <div class="card-actions">
                <?= \App\adms\Models\helper\AdmsUi::acao('novo', URLADM . 'matriz/cadastrar-grade?curso=' . (int) $c['id'], 'Criar uma grade nova neste curso', empty($c['ativo']) ? 'Curso inativo: reative-o em Cursos para criar uma grade' : null, ['rotulo' => 'Nova grade']) ?>
              </div>
            <?php endif; ?>
          </div>
          <div class="card-body table-responsive<?= htmlspecialchars((string) ($c['grades'] ? '' : ' p-0'), ENT_QUOTES, 'UTF-8') ?>">
            <?php if (!$c['grades']) : ?>
              <p class="text-muted text-center my-3">Nenhuma grade.</p>
            <?php else : /* E8c (R2/R3/R4): lista padrão; ação que não pode, desabilitada com a razão */ ?>
            <table class="table table-sm table-hover table-compacta lista-dados mb-0" data-titulo="Grades — <?= $e($c['nome']) ?>" data-paginar="nao" data-ordem='[[0,"asc"]]'>
              <thead>
                <tr>
                  <th class="num" style="width:90px">Grade</th>
                  <th>Descrição</th>
                  <th>Situação</th>
                  <?php if (!$semMod) : ?><th class="num">Módulos</th><?php endif; ?>
                  <th class="num">Disciplinas</th>
                  <th class="num">CH total</th>
                  <th class="nao-exportar nao-ordenar text-end">Ações</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($c['grades'] as $g) : ?>
                  <tr>
                    <td class="num"><b><?= htmlspecialchars((string) ((int) $g['versao']), ENT_QUOTES, 'UTF-8') ?></b></td>
                    <td><?= $e($g['descricao']) ?></td>
                    <td><span class="badge bg-<?= $e($g['corSituacao'] ?: 'secondary') ?>-lt"><?= $e($g['nomeSituacao']) ?></span></td>
                    <?php if (!$semMod) : ?><td class="num"><?= htmlspecialchars((string) ((int) $g['modulos']), ENT_QUOTES, 'UTF-8') ?></td><?php endif; ?>
                    <td class="num"><?= htmlspecialchars((string) ((int) $g['disciplinas']), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="num"><?= htmlspecialchars((string) ((int) $g['ch']), ENT_QUOTES, 'UTF-8') ?> h</td>
                    <td class="acoes-linha">
                      <?php if (!empty($botao['ver'])) : ?>
                        <?= \App\adms\Models\helper\AdmsUi::acao('ver', URLADM . 'matriz/visualizar/' . (int) $g['grade_id'], ($semMod ? 'Ver a matriz (disciplinas)' : 'Ver a matriz (módulos e disciplinas)')) ?>
                      <?php endif; ?>
                      <?php if (!empty($botao['edit_grade'])) : ?>
                        <?= \App\adms\Models\helper\AdmsUi::acao('editar', URLADM . 'matriz/editar-grade/' . (int) $g['grade_id'], 'Editar número e descrição da grade', (int) $g['situacao'] === 1 ? null : 'Grade «' . $g['nomeSituacao'] . '»: só uma grade Aberta muda') ?>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
</div>
