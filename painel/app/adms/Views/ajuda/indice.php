<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* E6c — ÍNDICE DA CENTRAL DE AJUDA (painel e portal): "Comece por aqui" do perfil e todos os guias, com filtro. */
use App\adms\Models\helper\AdmsCentralGuias as G;

$h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$r = $this->dados['rotas'];
$perfil = $this->dados['perfil'];
?>
<style>
  .ajuda-passo { display: flex; gap: 12px; padding: 10px 0; border-bottom: 1px solid #eef1f4; }
  .ajuda-passo:last-child { border-bottom: 0; }
  .ajuda-num { flex: 0 0 34px; height: 34px; border-radius: 50%; background: #2B6F9E; color: #fff; font-weight: 700; display: flex; align-items: center; justify-content: center; }
  .ajuda-card { border: 1px solid #e3e7eb; border-radius: 4px; padding: 10px 12px; margin-bottom: 10px; height: calc(100% - 10px); }
  .ajuda-card a { font-weight: 600; }
</style>
<div class="vc-pagina">
  <?php ob_start(); ?><i class="fas fa-question-circle"></i> Central de ajuda<?php $paginaTitulo = ob_get_clean();
  /* Onda 5: o botão Novidades. D-122 (a correção do ensaio, B3.11): o botão Vídeos saiu (a página continua, fora do menu) */
  $paginaAcoesHtml = '<a href="' . $h($r['novidades']) . '" class="btn btn-outline-info btn-sm" id="linkNovidades"><i class="fas fa-bullhorn me-1" aria-hidden="true"></i>Novidades</a>';
  include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <?php if ($perfil === null) : ?>
        <div class="alert alert-light border">Não há guias para o seu perfil. Fale com o administrador do sistema.</div>
      <?php else : ?>
        <p class="text-muted">Guias curtos, um por tarefa, para o perfil <b><?= $h(G::ROTULOS[$perfil]) ?></b>. Em várias telas, o ícone <i class="fas fa-question-circle text-info"></i> no alto da página abre direto o guia daquela tela.</p>
        <div class="card shadow" id="comecePorAqui">
          <div class="card-header"><h3 class="card-title"><b>Comece por aqui</b> — o caminho, na ordem em que as coisas acontecem</h3></div>
          <div class="card-body py-1">
            <?php $n = 0; foreach ($this->dados['comece'] as $slug => $m) : $n++; ?>
              <div class="ajuda-passo"><div class="ajuda-num"><?= htmlspecialchars((string) ($n), ENT_QUOTES, 'UTF-8') ?></div>
                <div><a href="<?= $h($r['guia'] . $slug) ?>" class="guia-link"><?= $h($m['titulo']) ?></a><br><small class="text-muted"><?= $h($m['resumo']) ?></small></div></div>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="card shadow">
          <div class="card-header"><h3 class="card-title">Todos os guias do seu perfil (<?= count($this->dados['guias']) ?>)</h3>
            <div class="card-actions"><input type="search" id="filtroGuias" class="form-control form-control-sm" placeholder="Filtrar pelo título…" style="min-width: 240px" aria-label="Filtrar guias pelo título"></div></div>
          <div class="card-body pb-0">
            <div class="row" id="listaGuias">
              <?php foreach ($this->dados['guias'] as $slug => $m) : ?>
                <div class="col-md-6 col-lg-4 guia-item" data-titulo="<?= $h(mb_strtolower($m['titulo'])) ?>">
                  <div class="ajuda-card"><a href="<?= $h($r['guia'] . $slug) ?>" class="guia-link"><?= $h($m['titulo']) ?></a>
                    <?= !empty($m['mudou']) && $m['mudou'] === (G::indice()['versao'] ?? '') ? ' <span class="badge bg-info-lt">novo</span>' : '' ?><br>
                    <small class="text-muted"><?= $h($m['resumo']) ?></small></div>
                </div>
              <?php endforeach; ?>
            </div>
            <p class="text-muted" id="semResultado" hidden>Nenhum guia com esse título.</p>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </section>
</div>
<script>
  (function () {
    var f = document.getElementById('filtroGuias');
    if (!f) { return; }
    f.addEventListener('input', function () {
      var t = f.value.trim().toLowerCase(), vis = 0;
      document.querySelectorAll('.guia-item').forEach(function (el) {
        var ok = el.getAttribute('data-titulo').indexOf(t) !== -1;
        el.hidden = !ok; if (ok) { vis++; }
      });
      document.getElementById('semResultado').hidden = vis > 0;
    });
  })();
</script>
