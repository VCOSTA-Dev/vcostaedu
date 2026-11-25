<?php
if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}
/* Onda 2, Etapa 4: a faixa discreta da demonstração, em toda tela do portal. */
if (\App\adms\Models\helper\AdmsAmbiente::demonstracaoAtiva()):
?>
<div class="vc-faixa-demo d-print-none" role="status" id="faixaDemonstracao">
  <div class="container-xl"><i class="fas fa-flask me-1" aria-hidden="true"></i><strong>Demonstração:</strong> os dados que você vê são fictícios.</div>
</div>
<?php endif; ?>
