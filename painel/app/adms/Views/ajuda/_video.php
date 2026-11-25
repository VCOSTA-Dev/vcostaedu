<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* 1.18.0 (D-475) — O VÍDEO DO GUIA (painel e portal; o mesmo arquivo nos dois): <video> nativo, sem dependência externa,
   preload="none", poster, legenda ligada por padrão, capítulos que pulam para o tempo e marcam o atual, e a transcrição
   recolhida. $video vem de AdmsCentralGuias::video() (só existe se o mp4 está na pasta). */
$h = $h ?? fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$base = \App\adms\Models\helper\AdmsCentralGuias::urlVideos();
$tempo = fn($s) => sprintf('%d:%02d', (int) floor($s / 60), (int) round($s) % 60);
?>
<style>
  .video-guia video { width: 100%; aspect-ratio: 16 / 9; background: #000; color: #E7EAF0; border-radius: 4px; display: block; }
  .video-guia .capitulos { max-height: 420px; overflow-y: auto; }
  .video-guia .capitulos a { display: flex; gap: 8px; padding: 6px 8px; border-radius: 4px; color: #1f2933; text-decoration: none; }
  .video-guia .capitulos a:hover { background: #eef1f4; }
  .video-guia .capitulos a.atual { background: #e1effa; font-weight: 600; }
  .video-guia .capitulos .tempo { font-family: Consolas, monospace; color: #1C6FB5; min-width: 3.2em; }
  .video-guia details summary { cursor: pointer; color: #1C6FB5; }
  .video-guia .transcricao p { margin-bottom: 6px; }
  /* o tema escuro (Onda 6): as mesmas cores claras do resto do sistema, com contraste de 4,5:1 */
  [data-bs-theme="dark"] .video-guia .capitulos a { color: #E7EAF0; }
  [data-bs-theme="dark"] .video-guia .capitulos a:hover { background: #2A3546; }
  [data-bs-theme="dark"] .video-guia .capitulos a.atual { background: #16324F; }
  [data-bs-theme="dark"] .video-guia .capitulos .tempo, [data-bs-theme="dark"] .video-guia details summary { color: #8CC8F0; }
</style>
<div class="card shadow video-guia" id="videoGuia">
  <div class="card-body">
    <div class="row">
      <div class="col-lg-8 mb-2">
        <video id="playerGuia" controls preload="none" playsinline poster="<?= $h($base . $video['poster']) ?>" aria-label="Vídeo do guia: <?= $h($m['titulo']) ?>">
          <source src="<?= $h($base . $video['arquivo']) ?>" type="video/mp4">
          <?php if ($video['legenda'] !== '') : ?><track kind="subtitles" srclang="pt-BR" label="Português" src="<?= $h($base . $video['legenda']) ?>" default><?php endif; ?>
          <?php if ($video['capitulos_vtt'] !== '') : ?><track kind="chapters" srclang="pt-BR" label="Capítulos" src="<?= $h($base . $video['capitulos_vtt']) ?>"><?php endif; ?>
          Seu navegador não toca vídeo. Leia a transcrição abaixo.
        </video>
      </div>
      <div class="col-lg-4">
        <p class="mb-1"><b><i class="fas fa-film text-info"></i> Vídeo do guia</b> <small class="text-muted"><?= $h($tempo($video['duracao'])) ?> · gravado na versão <?= $h($video['versao']) ?></small></p>
        <?php if ($video['capitulos']) : ?>
          <p class="small text-muted mb-1">Capítulos (clique para pular):</p>
          <nav class="capitulos" id="capitulosGuia">
            <?php foreach ($video['capitulos'] as $i => $c) : ?>
              <a href="#" class="capitulo" data-inicio="<?= $h($c['inicio']) ?>"><span class="tempo"><?= $h($tempo($c['inicio'])) ?></span><span><?= $h($c['titulo']) ?></span></a>
            <?php endforeach; ?>
          </nav>
        <?php endif; ?>
      </div>
    </div>
    <?php if ($video['transcricao'] !== '') : ?>
      <details class="mt-2" id="transcricaoGuia"><summary>Ler a transcrição</summary>
        <div class="transcricao mt-2"><?php foreach (explode("\n", $video['transcricao']) as $l) : ?><p><?= $h($l) ?></p><?php endforeach; ?></div>
      </details>
    <?php endif; ?>
  </div>
</div>
<script>
  (function () {
    var v = document.getElementById('playerGuia'), caps = Array.prototype.slice.call(document.querySelectorAll('#capitulosGuia .capitulo'));
    if (!v) return;
    /* legenda ligada por padrão (o atributo default já liga; reforça nos navegadores que não respeitam) */
    v.addEventListener('loadedmetadata', function () { for (var i = 0; i < v.textTracks.length; i++) { if (v.textTracks[i].kind === 'subtitles') v.textTracks[i].mode = 'showing'; } });
    caps.forEach(function (a) {
      a.addEventListener('click', function (e) {
        e.preventDefault();
        var t = parseFloat(a.getAttribute('data-inicio')) || 0;
        var pular = function () { v.currentTime = t; v.play(); };
        if (v.readyState >= 1) pular(); else { v.addEventListener('loadedmetadata', pular, { once: true }); v.load(); }
      });
    });
    v.addEventListener('timeupdate', function () {
      var atual = null;
      caps.forEach(function (a) { if (parseFloat(a.getAttribute('data-inicio')) <= v.currentTime + 0.05) atual = a; });
      caps.forEach(function (a) { a.classList.toggle('atual', a === atual); });
    });
  })();
</script>
