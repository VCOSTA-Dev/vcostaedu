<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/*
 * A JANELA DE PROGRESSO (Onda 3, Etapa 4): uma operação longa feita em passos curtos, um chamado do navegador por passo
 * (assets/js/vcostaedu-progresso.js). Variáveis, antes do include:
 *   $progressoId      o id da janela (o botão que a abre diz data-progresso="<id>");
 *   $progressoTitulo  o título ("Montando a demonstração");
 *   $progressoGrupos  os grupos, na ordem (o que a pessoa lê): [nome => quantos passos];
 *   $progressoUrl     o endereço que roda os passos (POST: fase=iniciar|passo);
 *   $progressoFim     [texto do botão, endereço] do fim;
 *   $progressoCampos  (opcional) os campos que vão no começo ([nome => seletor do campo na página]; a caixa de marcar
 *                     desmarcada vai vazia);
 *   $progressoNavegar (opcional) true: no fim — concluída, ou recusada no começo —, a janela abre o endereço do fim
 *                     sozinha (Onda 6, Etapa 4: a mensagem do resultado fica na sessão).
 */
$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
?>
<div class="vc-progresso" id="<?= $h($progressoId) ?>" hidden role="dialog" aria-modal="true" aria-labelledby="<?= $h($progressoId) ?>Titulo"
     data-url="<?= $h($progressoUrl) ?>" data-campos="<?= $h(json_encode($progressoCampos ?? [], JSON_UNESCAPED_UNICODE)) ?>"<?= !empty($progressoNavegar) ? ' data-navegar="1"' : '' ?>>
  <div class="vc-progresso-fundo"></div>
  <div class="card vc-progresso-janela">
    <div class="card-header"><h3 class="card-title" id="<?= $h($progressoId) ?>Titulo"><?= $h($progressoTitulo) ?></h3></div>
    <div class="card-body">
      <div class="progress mb-2" role="progressbar" aria-label="Andamento" aria-valuemin="0" aria-valuemax="100">
        <div class="progress-bar vc-progresso-barra" style="width: 0%"></div>
      </div>
      <div class="small text-secondary mb-3 vc-progresso-conta">Começando…</div>
      <ul class="list-unstyled vc-progresso-grupos mb-3">
        <?php $i = 0; foreach ($progressoGrupos as $nome => $n) : ?>
          <li data-grupo="<?= $h($nome) ?>" data-passos="<?= (int) $n ?>" data-ordem="<?= $i++ ?>">
            <span class="vc-progresso-marca" aria-hidden="true"><i class="far fa-circle"></i></span> <span><?= $h($nome) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
      <div class="alert alert-warning mb-0 vc-progresso-aviso"><i class="fas fa-triangle-exclamation"></i> Não feche esta página até terminar. Se fechar, nada se perde: ao voltar, o botão Continuar retoma de onde parou.</div>
      <div class="alert alert-danger mb-0 vc-progresso-falha" hidden>
        <b>Parou no passo <span class="vc-progresso-falha-passo"></span>.</b> <span class="vc-progresso-falha-motivo"></span>
        <div class="mt-2"><button type="button" class="btn btn-danger vc-progresso-tentar"><i class="fas fa-rotate-right"></i> Tentar de novo</button>
          <button type="button" class="btn btn-outline-secondary vc-progresso-fechar" hidden>Fechar</button></div>
      </div>
      <div class="alert alert-success mb-0 vc-progresso-pronto" hidden><i class="fas fa-circle-check"></i> Pronto.</div>
    </div>
    <div class="card-footer text-end vc-progresso-rodape" hidden>
      <a href="<?= $h($progressoFim[1]) ?>" class="btn btn-primary vc-progresso-fim"><?= $h($progressoFim[0]) ?></a>
    </div>
  </div>
</div>
