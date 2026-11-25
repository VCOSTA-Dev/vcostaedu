<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* E6c — UM GUIA CURTO (painel e portal): o conteúdo gerado de docs/guia/tarefas/<slug>.md, os relacionados do perfil e a volta ao índice. */
$h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$r = $this->dados['rotas'];
$m = $this->dados['meta'];
?>
<style>
  .guia-conteudo { font-size: 15px; line-height: 1.6; max-width: 980px; }
  .guia-conteudo h2 { font-size: 20px; margin: 22px 0 8px; padding-top: 6px; border-top: 1px solid #eef1f4; }
  .guia-conteudo h2:first-child { border-top: 0; margin-top: 0; }
  .guia-conteudo h3 { font-size: 17px; margin: 16px 0 6px; }
  .guia-conteudo figure { margin: 12px 0 18px; }
  .guia-conteudo figure img { max-width: 100%; border: 1px solid #cfd6dd; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,.12); }
  .guia-conteudo figcaption { font-size: 13px; color: #52606d; margin-top: 4px; }
  .guia-conteudo blockquote { margin: 12px 0; padding: 10px 14px; background: #eaf4ff; border-start: 4px solid #3c8dbc; }
  .guia-conteudo table { border-collapse: collapse; width: 100%; margin: 10px 0; font-size: 14px; }
  .guia-conteudo th, .guia-conteudo td { border: 1px solid #cfd6dd; padding: 5px 8px; vertical-align: top; }
  .guia-conteudo th { background: #eef1f4; }
  .guia-conteudo code, .guia-conteudo pre { background: #f0f2f5; font-family: Consolas, monospace; }
  .guia-conteudo pre { padding: 8px 10px; }
  .guia-conteudo .print-pendente { border: 2px dashed #adb5bd; background: #f8f9fa; color: #6c757d; padding: 14px; margin: 12px 0; border-radius: 4px; }
  .guia-conteudo .erro-item { margin-bottom: 8px; }
</style>
<div class="vc-pagina">
  <?php ob_start(); ?><?= $h($m['titulo']) ?><?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php $video = \App\adms\Models\helper\AdmsCentralGuias::video((string) $this->dados['slug']); /* 1.18.0: sem vídeo, nada aparece */
      if ($video) { include __DIR__ . '/_video.php'; } ?>
      <div class="card shadow">
        <div class="card-body guia-conteudo" id="conteudoGuia"><?= $this->dados['html'] /* gerado e escapado pelo gerar_guias.php */ ?></div>
        <div class="card-footer">
          <?php if ($this->dados['relacionados']) : ?>
            <p class="mb-1"><b>Relacionados:</b></p>
            <ul class="mb-2" id="relacionados">
              <?php foreach ($this->dados['relacionados'] as $s => $x) : ?><li><a href="<?= $h($r['guia'] . $s) ?>"><?= $h($x['titulo']) ?></a> <small class="text-muted">— <?= $h($x['resumo']) ?></small></li><?php endforeach; ?>
            </ul>
          <?php endif; ?>
          <div class="d-flex flex-wrap justify-content-between">
            <a href="<?= $h($r['indice']) ?>" class="btn btn-outline-secondary btn-sm mb-1"><i class="fas fa-arrow-left"></i> Voltar ao índice</a>
            <span>
              <?php if ($this->dados['anterior']) : ?><a href="<?= $h($r['guia'] . $this->dados['anterior'][0]) ?>" class="btn btn-outline-primary btn-sm mb-1">‹ <?= $h($this->dados['anterior'][1]) ?></a><?php endif; ?>
              <?php if ($this->dados['proximo']) : ?><a href="<?= $h($r['guia'] . $this->dados['proximo'][0]) ?>" class="btn btn-primary btn-sm mb-1">Próximo passo: <?= $h($this->dados['proximo'][1]) ?> ›</a><?php endif; ?>
            </span>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
