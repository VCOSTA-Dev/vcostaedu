<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
use App\adms\Models\relatorios\AdmsRelatorios as Reg;

$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$porCategoria = $this->dados['porCategoria'] ?? [];
$salvos = $this->dados['salvos'] ?? [];
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Relatórios<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <p class="text-muted">Cada relatório responde a uma pergunta. Em cada um, <strong>Como é calculado</strong> mostra a fórmula, e os números clicáveis abrem a lista que os compõe.
        <?php if (!empty($this->dados['podeParametros'])) { ?>
          <a href="<?= URLADM ?>relatorios/parametros" class="btn btn-sm btn-outline-secondary ms-1"><i class="fas fa-sliders-h"></i> Alerta de risco</a>
        <?php } ?>
      </p>
      <?php if (!$porCategoria) { ?>
        <div class="alert alert-light border">Nenhum relatório disponível para o seu perfil.</div>
      <?php } ?>
      <div class="row">
        <?php foreach ($porCategoria as $letra => $defs) { ?>
          <div class="col-lg-6">
            <div class="card shadow-sm">
              <div class="card-header"><h3 class="card-title"><strong><?= $h($letra) ?>.</strong> <?= $h(Reg::CATEGORIAS[$letra] ?? '') ?></h3></div>
              <div class="card-body p-0">
                <table class="table table-sm mb-0">
                  <tbody>
                    <?php foreach ($defs as $d) { ?>
                      <tr>
                        <td style="width:40%"><a href="<?= URLADM . 'relatorios/' . $d::SLUG ?>"><?= $h($d::NOME) ?></a><?php if (!\App\adms\Models\helper\AdmsTipoInstituicao::ehEscola()) : /* C5 (D-100): na escola, sem o código interno */ ?><br><small class="text-muted"><?= $h($d::CODIGO) ?></small><?php endif; ?></td>
                        <td class="text-muted"><?= $h($d::PERGUNTA) ?></td>
                      </tr>
                    <?php } ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        <?php } ?>
      </div>
      <div class="card shadow-sm">
        <div class="card-header"><h3 class="card-title"><i class="far fa-bookmark"></i> Relatórios salvos</h3></div>
        <div class="card-body">
          <?php if (!$salvos) { ?>
            <p class="text-muted mb-0">Nenhum. Em qualquer relatório, ajuste os filtros e as colunas e clique em <strong>Salvar</strong>.</p>
          <?php } else { ?>
            <table class="table table-sm table-striped lista-dados" data-exportar="nao" data-paginar="nao">
              <thead><tr><th>Nome</th><th>Relatório</th><th>De quem</th><th>Compartilhado com</th><th class="nao-ordenar"></th></tr></thead>
              <tbody>
                <?php foreach ($salvos as $s) { $c = Reg::DEFINICOES[$s['relatorio']] ?? null; ?>
                  <tr>
                    <td><a href="<?= $h(Reg::urlSalvo($s)) ?>"><?= $h($s['nome']) ?></a></td>
                    <td><?= $h($s['relatorio'] . ' ' . ($c ? $c::NOME : '')) ?></td>
                    <td><?= $s['meu'] ? 'meu' : $h($s['dono']) ?></td>
                    <td><?= $s['nivel_acesso_id'] ? $h($s['perfil']) : '<span class="text-muted">pessoal</span>' ?></td>
                    <td class="text-end">
                      <?php if ($s['meu']) { ?>
                        <form method="POST" action="<?= URLADM ?>relatorios/apagar-salvo" class="d-inline form-apagar-salvo">
                          <input type="hidden" name="id" value="<?= htmlspecialchars((string) ((int) $s['id']), ENT_QUOTES, 'UTF-8') ?>">
                          <button type="submit" class="btn btn-sm btn-outline-danger" title="Apagar"><i class="fas fa-trash-alt"></i></button>
                        </form>
                      <?php } else { ?>
                        <small class="text-muted">só leitura</small>
                      <?php } ?>
                    </td>
                  </tr>
                <?php } ?>
              </tbody>
            </table>
          <?php } ?>
        </div>
      </div>
    </div>
  </section>
</div>
<script>
document.querySelectorAll('.form-apagar-salvo').forEach(function (f) {
  f.addEventListener('submit', function (e) {
    var b = f.querySelector('button');
    if (!b.dataset.confirmado) { e.preventDefault(); b.dataset.confirmado = '1'; b.classList.replace('btn-outline-danger', 'btn-danger'); b.innerHTML = 'Confirmar apagar'; }
  });
});
</script>
