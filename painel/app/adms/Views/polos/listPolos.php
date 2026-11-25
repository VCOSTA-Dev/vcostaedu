<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
$polos = $this->dados['polos'] ?? [];
$botao = $this->dados['botao'] ?? [];
$e = fn($t) => htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Polos<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php
      if (isset($_SESSION['msg'])) {
        echo $_SESSION['msg'];
        unset($_SESSION['msg']);
      }
      ?>
      <div class="card shadow">
        <div class="card-header">
          <h3 class="card-title"><?= count($polos) ?> polo(s)</h3>
          <?php if (!empty($botao['cadastrar'])) : ?>
            <div class="card-actions"><a href="<?= URLADM . 'polos/cadastrar' ?>" class="btn btn-sm btn-success"><i class="fas fa-plus"></i> Novo polo</a></div>
          <?php endif; ?>
        </div>
        <div class="card-body table-responsive">
          <table id="tabelaPolos" class="table table-sm table-striped table-hover table-compacta lista-dados" data-titulo="Polos" data-ordem='[[0,"asc"]]'>
            <thead><tr><th>Polo</th><th>Cidade/UF</th><th>Instituição parceira</th><th class="num">Cursos</th><th class="num">Ofertas</th><th>Situação</th><th class="nao-exportar nao-ordenar text-end">Ações</th></tr></thead>
            <tbody>
              <?php foreach ($polos as $p) : ?>
                <tr>
                  <td><b><?= $e($p['nome']) ?></b><br><small class="text-muted"><?= $e($p['endereco']) ?></small></td>
                  <td><?= $e(trim($p['cidade'] . ($p['uf'] ? '/' . $p['uf'] : ''), '/')) ?></td>
                  <td><?= $e($p['instituicao_parceira']) ?></td>
                  <td class="num"><?= htmlspecialchars((string) ((int) $p['cursos']), ENT_QUOTES, 'UTF-8') ?></td>
                  <td class="num"><?= htmlspecialchars((string) ((int) $p['ofertas']), ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= (int) $p['situacao'] === 1 ? '<span class="badge bg-success-lt">Ativo</span>' : '<span class="badge bg-secondary-lt">Inativo</span>' ?></td>
                  <td class="acoes-linha">
                    <?php if (!empty($botao['editar'])) : ?><?= \App\adms\Models\helper\AdmsUi::acao('editar', URLADM . 'polos/editar/' . (int) $p['id'], 'Editar o polo') ?><?php endif; ?>
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
