<?php
if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}
/* Onda 2, Etapa 1: a página nova chegou antes da migration dela (AdmsAtualizacaoPendente::responderPendente). */
?>
<div class="page-body">
  <div class="container-xl">
    <div class="empty">
      <div class="empty-icon"><i class="fas fa-arrows-rotate fa-2x text-warning" aria-hidden="true"></i></div>
      <p class="empty-title" id="atualizacaoPendente">Atualização pendente</p>
      <p class="empty-subtitle text-secondary">
        Esta tela faz parte de uma versão nova do sistema e só abre depois que o banco de dados for atualizado.
        O restante do sistema continua funcionando normalmente.
      </p>
      <?php if (\App\adms\Models\helper\AdmsNivel::ehAdministrador()): ?>
      <p class="text-secondary" id="comoAtualizar">Para atualizar, abra a tela <a href="<?= URLADM ?>atualizar/index" id="irParaAtualizacao">Atualizar o sistema</a>
        (ela pede a confirmação da cópia do banco de dados), ou rode <code>php bin/migrar.php aplicar</code> na pasta do sistema.</p>
      <?php else: ?>
      <p class="text-secondary">Avise o administrador do sistema.</p>
      <?php endif; ?>
      <div class="empty-action">
        <a href="<?= URLADM ?>home/index" class="btn btn-primary">Ir para a Página Inicial</a>
      </div>
    </div>
  </div>
</div>
