<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* Onda 2, Etapa 7: a versão instalada e se há etapa pendente. */
$d = $this->dados;
$v = $d['versoes'];
$e = $d['estado'];
$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Versão<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <div class="row row-cards">
        <div class="col-md-6">
          <div class="card"><div class="card-body">
            <div class="text-secondary">Versão instalada (registrada no banco)</div>
            <div class="h1 mb-1" id="versaoInstalada"><?= $h($v['banco'] ?: '—') ?></div>
            <?php if ($v['data']) : ?><div class="text-secondary small">de <?= $h(date('d/m/Y', strtotime($v['data']))) ?></div><?php endif; ?>
            <div class="text-secondary small mt-2">Arquivos do sistema: versão <span id="versaoDosArquivos"><?= $h($v['arquivos'] ?: '—') ?></span></div>
          </div></div>
        </div>
        <div class="col-md-6">
          <div class="card"><div class="card-body" id="situacaoAtualizacao" data-em-dia="<?= htmlspecialchars((string) ($e['ok'] && $e['emDia'] ? '1' : '0'), ENT_QUOTES, 'UTF-8') ?>">
            <?php if (!$e['ok']) : ?>
              <div class="text-danger"><?= $h($e['erro']) ?></div>
            <?php elseif ($e['emDia']) : ?>
              <div class="h3 text-success mb-1"><i class="fas fa-circle-check"></i> Em dia</div>
              <div class="text-secondary">Nenhuma etapa do banco de dados por aplicar (<?= htmlspecialchars((string) ((int) $e['aplicadas']), ENT_QUOTES, 'UTF-8') ?> aplicadas).</div>
            <?php else : ?>
              <div class="h3 text-warning mb-1"><i class="fas fa-triangle-exclamation"></i> <?= htmlspecialchars((string) (count($e['pendentes']) + count($e['divergentes'])), ENT_QUOTES, 'UTF-8') ?> etapa(s) pendente(s)</div>
              <div class="text-secondary mb-2">Os arquivos são de uma versão mais nova que o banco de dados.</div>
              <?php if ($d['administrador']) : ?>
                <a href="<?= URLADM ?>atualizar/index" class="btn btn-warning" id="irAtualizar"><i class="fas fa-arrows-rotate"></i> Atualizar o sistema</a>
              <?php else : ?>
                <div class="text-secondary" id="avisarAdministrador">Avise o administrador do sistema.</div>
              <?php endif; ?>
            <?php endif; ?>
          </div></div>
        </div>
      </div>
      <div class="mt-3"><a href="<?= URL ?>sobre.php" class="btn btn-ghost-secondary">Sobre o sistema e as licenças</a></div>
    </div>
  </section>
</div>
