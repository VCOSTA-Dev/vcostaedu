<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* Onda 8, correção, Parte 3.1 (D-76): a página que o perfil não pode abrir. A sessão continua; o caminho de volta é a
   página de onde a pessoa veio (quando é deste sistema) ou a Página Inicial. Antes, a pessoa via a tela de entrada, como
   se tivesse sido desconectada. */
$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$d = $this->dados;
?>
<div class="vc-pagina">
  <?php $finDesl = !empty($d['financeiroDesligado']); /* 1.103.1 (D-134): o financeiro desligado diz o motivo */ ?>
  <?php ob_start(); ?><?= $finDesl ? 'O financeiro está desligado' : 'Você não tem acesso a esta tela' ?><?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <div class="card" id="semAcesso" data-tela="<?= $h($d['tela'] ?? '') ?>">
        <div class="card-body">
          <div class="d-flex align-items-start gap-3">
            <div class="text-warning fs-1" aria-hidden="true"><i class="fas fa-lock"></i></div>
            <div>
              <?php if ($finDesl) : ?>
              <p class="mb-2" id="financeiroDesligado">O financeiro está desligado nesta instalação.</p>
              <?php if (!empty($d['podeLigar'])) : ?><p class="text-secondary mb-3" id="ondeLigar">Ligue em Configurações → Recursos da instituição.</p><?php else : ?><div class="mb-3"></div><?php endif; ?>
              <?php else : ?>
              <p class="mb-2">O seu perfil (<strong><?= $h($d['perfil'] ?? '') ?></strong>) não abre <strong><?= $h($d['nomeTela'] ?? 'esta tela') ?></strong>. Você continua conectado.</p>
              <p class="text-secondary mb-3">Se precisa desta tela no seu trabalho, peça ao administrador para liberá-la no seu perfil (Equipe → Perfis de acesso).</p>
              <?php endif; ?>
              <div class="d-flex flex-wrap gap-2">
                <?php if (!empty($d['voltar'])) : ?><a href="<?= $h($d['voltar']) ?>" class="btn btn-primary" id="voltarDeOnde"><i class="fas fa-arrow-left"></i> Voltar</a><?php endif; ?>
                <a href="<?= URLADM ?>home/index" class="btn <?= !empty($d['voltar']) ? 'btn-outline-secondary' : 'btn-primary' ?>" id="irPaginaInicial"><i class="fas fa-house"></i> Página Inicial</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
