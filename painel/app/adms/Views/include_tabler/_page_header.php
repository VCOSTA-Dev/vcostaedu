<?php
if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}
/*
 * O CABEÇALHO DA PÁGINA (VCOSTAEDU, Etapa 3) — o page-header do Tabler, no lugar do content-header + breadcrumb
 * do AdminLTE (D-07: o breadcrumb some; o título vem para cá).
 *
 * Variáveis, antes do include:
 *   $paginaTitulo     o título JÁ RENDERIZADO (HTML): o script de conversão o captura do <h1> da view de origem com
 *                     ob_start(), então o que a origem escapava continua escapado do mesmo jeito;
 *   $paginaPretitulo  (opcional) a linha pequena acima do título;
 *   $paginaAcoesHtml  (opcional) as ações da página, à direita.
 *
 * O ÍCONE DE AJUDA DA TELA (E6c da origem) mora aqui, à direita do título: na origem ele estava na barra de cima,
 * que o layout Tabler não tem. A âncora mantém o id #ajudaDaTela.
 */
/* Onda 4, Etapa 1: na escola, o título no vocabulário dela (só o texto; os endereços ficam); no superior, como veio */
$paginaTitulo = \App\adms\Models\helper\AdmsTipoInstituicao::traduzirHtml($paginaTitulo ?? '');
$paginaPretitulo = \App\adms\Models\helper\AdmsTipoInstituicao::traduzirHtml($paginaPretitulo ?? '');
$paginaAcoesHtml = $paginaAcoesHtml ?? '';
$paginaGuia = \App\adms\Models\helper\AdmsCentralGuias::guiaDaTela();
?>
<div class="page-header d-print-none">
  <div class="container-xl">
    <div class="row g-2 align-items-center">
      <div class="col">
        <?php if ($paginaPretitulo !== ''): ?><div class="page-pretitle"><?= $paginaPretitulo ?></div><?php endif; ?>
        <h2 class="page-title"><?= $paginaTitulo ?></h2>
      </div>
      <?php if ($paginaAcoesHtml !== ''): ?>
      <div class="col-12 col-md-auto ms-md-auto">
        <div class="btn-list"><?= $paginaAcoesHtml ?></div>
      </div>
      <?php endif; ?>
      <?php if ($paginaGuia): ?>
      <div class="col-auto<?= $paginaAcoesHtml === '' ? ' ms-auto' : '' ?>">
        <a class="btn btn-ghost-info btn-icon" id="ajudaDaTela" href="<?= URLADM . 'ajuda/guia/' . htmlspecialchars((string) $paginaGuia, ENT_QUOTES, 'UTF-8') ?>"
           title="Ajuda desta tela" aria-label="Ajuda desta tela"><i class="fas fa-question-circle fa-lg" aria-hidden="true"></i></a>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php unset($paginaTitulo, $paginaPretitulo, $paginaAcoesHtml); ?>
