<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* 1.18.0 (D-475) — VÍDEOS DA AJUDA (painel e portal; o mesmo arquivo nos dois): os vídeos dos guias do perfil, com poster,
   título e duração, e a busca pelo título e pela transcrição (no navegador). O aluno vê só os do portal; a equipe, os do
   painel (AdmsCentralGuias::comVideo). */
$h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$r = $this->dados['rotas'];
$base = \App\adms\Models\helper\AdmsCentralGuias::urlVideos();
$tempo = fn($s) => sprintf('%d:%02d', (int) floor($s / 60), (int) round($s) % 60);
$normal = fn($s) => mb_strtolower(strtr((string) $s, 'ÁÀÂÃÉÊÍÓÔÕÚÇáàâãéêíóôõúç', 'AAAAEEIOOOUCaaaaeeiooouc'), 'UTF-8');
?>
<style>
  .videos-grade .card-video img { width: 100%; aspect-ratio: 16 / 9; object-fit: cover; border-bottom: 1px solid #e3e7eb; }
  .videos-grade .card-video .duracao { position: absolute; right: 8px; top: 8px; background: rgba(0,0,0,.75); color: #fff; font-size: 12px; padding: 1px 6px; border-radius: 3px; }
  .videos-grade .card-video .play { position: absolute; left: 50%; top: 32%; transform: translate(-50%,-50%); font-size: 42px; color: rgba(255,255,255,.9); text-shadow: 0 2px 6px rgba(0,0,0,.5); }
  .videos-grade .card-video a { color: inherit; text-decoration: none; }
  .videos-grade .card-video:hover { box-shadow: 0 4px 14px rgba(0,0,0,.18); }
</style>
<div class="vc-pagina">
  <?php ob_start(); ?><i class="fas fa-film text-info me-2"></i>Vídeos dos guias<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (!$this->dados['videos']) : ?>
        <div class="alert alert-light border" id="semVideos">Ainda não há vídeos para os guias do seu perfil. Os guias continuam na <a href="<?= $h($r['indice']) ?>">Central de ajuda</a>.</div>
      <?php else : ?>
        <div class="input-group mb-3" style="max-width:520px">
          <span class="input-group-text"><i class="fas fa-search"></i></span>
          <input type="search" class="form-control" id="buscaVideos" placeholder="Buscar pelo título ou pelo que é dito no vídeo" aria-label="Buscar vídeos">
        </div>
        <p class="small text-muted" id="contagemVideos"><?= count($this->dados['videos']) ?> vídeo(s)</p>
        <div class="row videos-grade" id="gradeVideos">
          <?php foreach ($this->dados['videos'] as $slug => $g) : $v = $g['video']; ?>
            <div class="col-sm-6 col-lg-4 col-xl-3 mb-3 item-video" data-busca="<?= $h($normal($g['titulo'] . ' ' . $g['resumo'] . ' ' . $v['transcricao'])) ?>">
              <div class="card card-video h-100 mb-0">
                <a href="<?= $h($r['guia'] . $slug) ?>#videoGuia" title="Abrir o guia com o vídeo">
                  <div class="position-relative"><img src="<?= $h($base . $v['poster']) ?>" alt="" loading="lazy"><span class="duracao"><?= $h($tempo($v['duracao'])) ?></span><i class="far fa-play-circle play"></i></div>
                  <div class="card-body p-2"><b><?= $h($g['titulo']) ?></b><br><small class="text-muted"><?= $h($g['resumo']) ?></small></div>
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="alert alert-light border" id="nadaEncontrado" hidden>Nenhum vídeo com esse termo.</div>
      <?php endif; ?>
    </div>
  </section>
</div>
<script>
  (function () {
    var b = document.getElementById('buscaVideos'); if (!b) return;
    var itens = Array.prototype.slice.call(document.querySelectorAll('#gradeVideos .item-video'));
    var tira = function (s) { return s.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase(); };
    b.addEventListener('input', function () {
      var termos = tira(b.value).split(/\s+/).filter(Boolean), n = 0;
      itens.forEach(function (el) { var ok = termos.every(function (t) { return tira(el.getAttribute('data-busca')).indexOf(t) >= 0; }); el.hidden = !ok; if (ok) n++; });
      document.getElementById('contagemVideos').textContent = n + ' vídeo(s)';
      document.getElementById('nadaEncontrado').hidden = n > 0;
    });
  })();
</script>
