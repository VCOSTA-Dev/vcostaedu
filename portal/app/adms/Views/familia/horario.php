<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* O horário do filho no ano. */
$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$d = $this->dados;
?>
<div class="vc-pagina vc-familia">
  <?php ob_start(); ?>Horário<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <?php include __DIR__ . '/_cabecalho.php'; ?>
      <?php /* Onda 8, correção (D-71): a semana (as aulas nas linhas, os dias nas colunas), a mesma do portal do aluno */ ?>
      <?php if (!$d['horario']) : ?><div class="alert alert-info" id="semHorario">Ainda não há horário cadastrado para este ano. Quando a escola montar o horário da turma, ele aparece aqui.</div><?php else : ?>
      <?php $semanaAulas = $d['horario']; include 'app/adms/Views/include_tabler/_semana_horario.php'; ?>
      <?php endif; ?>
    </div>
  </section>
</div>
