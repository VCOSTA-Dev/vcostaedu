<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* O HISTÓRICO ESCOLAR: a busca (D-112). Quem pede histórico é quase sempre quem já saiu: a busca acha os alunos ativos
   e os ex-alunos, por nome ou RM, e mostra a situação real. */
$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$d = $this->dados;
?>
<div class="vc-pagina" id="historicoBusca">
  <?php $paginaTitulo = 'Histórico escolar';
  $paginaPretitulo = 'Vida escolar e emissão';
  include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <form method="get" class="card card-body mb-3 d-flex flex-row gap-2 align-items-end" id="formBusca">
        <div class="flex-grow-1"><label class="form-label" for="buscaAluno">Aluno ou ex-aluno (nome ou RM)</label><input class="form-control" name="q" id="buscaAluno" value="<?= $h($d['q']) ?>" autofocus></div>
        <button class="btn btn-primary" id="buscar"><i class="fas fa-search me-1"></i> Buscar</button>
      </form>
      <?php if ($d['q'] !== '') : ?>
        <div class="card"><div class="table-responsive"><table class="table table-vcenter card-table" id="resultadoBusca">
          <thead><tr><th>RM</th><th>Nome</th><th>Situação</th><th></th></tr></thead><tbody>
          <?php foreach ($d['alunos'] as $a) : ?>
            <tr data-aluno="<?= (int) $a['id'] ?>"><td><?= $h($a['rm']) ?></td><td><?= $h($a['nome']) ?><?= $a['prova'] ? ' <span class="badge bg-red-lt">PROVA</span>' : '' ?></td><td class="vc-situacao"><?= $h($a['situacao']) ?></td>
              <td><a class="btn btn-sm btn-outline-primary" href="<?= URLADM ?>historico-escola-vida/aluno/<?= (int) $a['id'] ?>">Vida escolar</a></td></tr>
          <?php endforeach; ?>
          <?php if (!$d['alunos']) : ?><tr><td colspan="4" class="text-secondary">Nenhum aluno encontrado (digite ao menos duas letras do nome, ou o RM).</td></tr><?php endif; ?>
          </tbody></table></div></div>
      <?php endif; ?>
    </div>
  </section>
</div>
