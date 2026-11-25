<?php
if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}
/* Onda 2, Etapa 4: a faixa discreta da demonstração, em toda tela do painel. O botão é do Administrador. */
if (\App\adms\Models\helper\AdmsAmbiente::demonstracaoAtiva()):
?>
<div class="vc-faixa-demo d-print-none" role="status" id="faixaDemonstracao">
  <div class="container-xl d-flex flex-nowrap flex-md-wrap align-items-center gap-2">
    <span><i class="fas fa-flask me-1" aria-hidden="true"></i><strong>Demonstração<span class="d-none d-md-inline">:</span></strong><span class="d-none d-md-inline"> os dados que você vê são fictícios.</span></span>
    <?php if (\App\adms\Models\helper\AdmsNivel::ehAdministrador() && \App\adms\Models\helper\AdmsAtualizacaoPendente::haPendente()): /* Onda 3, Etapa 5: a atualização pendente na MESMA faixa (uma linha) */ ?>
      <span class="vc-faixa-atualizacao" id="avisoAtualizacao"><i class="fas fa-triangle-exclamation me-1" aria-hidden="true"></i><span class="d-none d-md-inline">Atualização pendente: </span><a href="<?= URLADM ?>atualizar/index" id="irParaAtualizacao">Atualizar<span class="d-none d-md-inline"> o sistema</span></a></span>
    <?php endif; ?>
    <?php if (\App\adms\Models\helper\AdmsNivel::ehAdministrador()): ?>
      <a href="<?= URLADM ?>demonstracao/instituicao" class="btn btn-sm btn-light ms-auto" id="faixaComecarInstituicao"><span class="d-none d-md-inline">Começar com os dados da minha instituição</span><span class="d-md-none">Minha instituição</span></a>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>
