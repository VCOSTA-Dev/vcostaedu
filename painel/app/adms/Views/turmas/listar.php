<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* As turmas do ano (escola). */
$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$d = $this->dados;
$pode = (new \App\adms\Models\AdmsBotao())->valBotao(['cadastrar' => ['menu_controller' => 'turmas', 'menu_metodo' => 'cadastrar']]);
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Turmas<?php $paginaTitulo = ob_get_clean();
  $paginaAcoesHtml = !empty($pode['cadastrar']) ? '<a href="' . URLADM . 'turmas/cadastrar" class="btn btn-primary" id="novaTurma"><i class="fas fa-plus"></i> Nova turma</a>' : '';
  include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <form method="get" class="mb-3 d-flex gap-2 align-items-end" id="filtroAno">
        <div><label class="form-label" for="ano">Ano letivo</label>
          <select name="ano" id="ano" class="form-select" onchange="this.form.submit()">
            <?php foreach ($d['anos'] as $a) : ?><option value="<?= (int) $a ?>"<?= htmlspecialchars((string) ((int) $a === (int) $d['ano'] ? ' selected' : ''), ENT_QUOTES, 'UTF-8') ?>><?= (int) $a ?></option><?php endforeach; ?>
          </select></div>
      </form>
      <div class="card">
        <?php if (!$d['turmas']) : ?>
          <div class="card-body text-secondary" id="semTurmas">Nenhuma turma em <?= htmlspecialchars((string) ((int) $d['ano']), ENT_QUOTES, 'UTF-8') ?>. <?= \App\adms\Models\helper\AdmsTipoInstituicao::ehLivre() ? 'Uma turma é de um curso; ao criá-la, o sistema põe nela as disciplinas do curso.' : 'Uma turma é de uma série; ao criá-la, o sistema gera as ofertas da série, uma por disciplina.' ?></div>
        <?php else : ?>
        <div class="table-responsive">
          <table class="table table-vcenter card-table" id="tabelaTurmas">
            <thead><tr><th>Turma</th><th><?= \App\adms\Models\helper\AdmsTipoInstituicao::ehLivre() ? 'Curso' : 'Segmento' /* D-106 */ ?></th><th>Turno</th><th>Sala</th><th class="text-end">Alunos</th><th class="text-end">Disciplinas</th></tr></thead>
            <tbody>
            <?php foreach ($d['turmas'] as $t) : ?>
              <tr data-turma="<?= htmlspecialchars((string) ((int) $t['id']), ENT_QUOTES, 'UTF-8') ?>">
                <td><a href="<?= URLADM ?>turmas/ver/<?= htmlspecialchars((string) ((int) $t['id']), ENT_QUOTES, 'UTF-8') ?>"><?= $h(\App\adms\Models\escola\AdmsTurmas::codtur((string) $t['serie'], (string) $t['nome'])) ?></a></td>
                <td><?= $h($t['segmento']) ?></td>
                <td><?= $h($t['turno_nome'] ?? '') ?></td>
                <td><?= $h($t['sala'] ?? '') ?></td>
                <td class="text-end"><?= htmlspecialchars((string) ((int) $t['alunos']), ENT_QUOTES, 'UTF-8') ?><?= htmlspecialchars((string) ($t['capacidade'] !== null ? ' / ' . (int) $t['capacidade'] : ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td class="text-end"><?= htmlspecialchars((string) ((int) $t['ofertas']), ENT_QUOTES, 'UTF-8') ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
</div>
