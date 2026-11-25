<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* Verificações de segurança: cada item OK, ALERTA ou FALHA, com o que fazer (core/Verificacoes.php). */
$d = $this->dados;
$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$cor = ['OK' => 'success', 'ALERTA' => 'warning', 'FALHA' => 'danger'];
$rotulo = ['OK' => 'OK', 'ALERTA' => 'Atenção', 'FALHA' => 'Resolver'];
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Verificações de segurança<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <div class="card mb-3"><div class="card-body d-flex flex-wrap gap-3 align-items-center" id="resumoSeguranca">
        <span class="text-secondary">O que a instalação mostra hoje:</span>
        <span class="badge bg-success-lt" data-situacao="OK"><?= htmlspecialchars((string) ((int) $d['resumo']['OK']), ENT_QUOTES, 'UTF-8') ?> OK</span>
        <span class="badge bg-warning-lt" data-situacao="ALERTA"><?= htmlspecialchars((string) ((int) $d['resumo']['ALERTA']), ENT_QUOTES, 'UTF-8') ?> atenção</span>
        <span class="badge bg-danger-lt" data-situacao="FALHA"><?= htmlspecialchars((string) ((int) $d['resumo']['FALHA']), ENT_QUOTES, 'UTF-8') ?> a resolver</span>
      </div></div>
      <div class="card">
        <div class="list-group list-group-flush" id="itensSeguranca">
          <?php foreach ($d['itens'] as $i) : ?>
          <div class="list-group-item" data-codigo="<?= $h($i['codigo']) ?>" data-situacao="<?= $h($i['situacao']) ?>">
            <div class="d-flex align-items-start gap-3">
              <span class="badge bg-<?= htmlspecialchars((string) ($cor[$i['situacao']]), ENT_QUOTES, 'UTF-8') ?>-lt mt-1" style="min-width: 5.5rem"><?= htmlspecialchars((string) ($rotulo[$i['situacao']]), ENT_QUOTES, 'UTF-8') ?></span>
              <div>
                <div class="fw-semibold"><?= $h($i['titulo']) ?></div>
                <div class="text-secondary"><?= $h($i['detalhe']) ?></div>
                <?php if ($i['fazer'] !== '') : ?><div class="mt-1"><strong>O que fazer:</strong> <?= $h($i['fazer']) ?></div><?php endif; ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="card-footer text-secondary small">Quem cuida do servidor pode rodar a mesma conferência pela linha de comando, na pasta do sistema: <code>php database/ferramentas/implantacao/verificar_seguranca.php</code>.</div>
      </div>
    </div>
  </section>
</div>
