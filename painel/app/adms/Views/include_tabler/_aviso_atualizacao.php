<?php
if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}
/* Onda 2, Etapa 1: a faixa do administrador quando há migration pendente (os arquivos chegaram antes do banco). */
/* Onda 3, Etapa 5: só para quem pode atualizar (o Administrador); com a demonstração ligada, o aviso vai DENTRO da faixa
   dela (_faixa_demo.php), numa linha só — nunca duas faixas empilhadas */
if (\App\adms\Models\helper\AdmsNivel::ehAdministrador() && \App\adms\Models\helper\AdmsAtualizacaoPendente::haPendente()
    && !\App\adms\Models\helper\AdmsAmbiente::demonstracaoAtiva()):
?>
<div class="alert alert-warning vc-aviso-atualizacao mb-0 d-print-none" role="status" id="avisoAtualizacao">
  <div class="container-xl">
    <strong>Atualização do banco de dados pendente.</strong>
    O sistema continua funcionando; as telas novas abrem depois da atualização.
    <a href="<?= URLADM ?>atualizar/index" class="alert-link" id="irParaAtualizacao">Atualizar o sistema</a>.
  </div>
</div>
<?php endif; ?>
