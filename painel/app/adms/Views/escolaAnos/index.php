<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* O ano letivo e as etapas (escola). */
$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$d = $this->dados;
$pode = (new \App\adms\Models\AdmsBotao())->valBotao(['abrir' => ['menu_controller' => 'escola-anos', 'menu_metodo' => 'abrir']]);
$editar = !empty($pode['abrir']);
/* D-100: a liberação do boletim tem a tela dela (Liberação do boletim); aqui, a situação de cada etapa, só para ler */
$podeLiberar = !empty((new \App\adms\Models\AdmsBotao())->valBotao(['l' => ['menu_controller' => 'escola-liberacao', 'menu_metodo' => 'index']])['l']);
$liberadas = \App\adms\Models\escola\AdmsEtapas::liberadas();
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Ano letivo e etapas<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <p class="text-secondary">O ano letivo da escola é dividido em <strong>etapas</strong>: quatro bimestres (o padrão) ou três trimestres. Cada etapa tem as datas e a data do conselho de classe. Depois que há nota ou falta lançada no ano, o modelo não muda; as datas, sim.</p>
      <?php if (!$d['anos']) : ?>
        <div class="alert alert-info" id="semAnos">Cadastre o ano letivo antes, em <a href="<?= URLADM ?>anos-letivos/listar">Anos letivos</a>.</div>
      <?php endif; ?>
      <?php foreach ($d['anos'] as $a) : $e = $a['escola']; ?>
        <div class="card mb-3 vc-ano" data-ano="<?= htmlspecialchars((string) ((int) $a['ano']), ENT_QUOTES, 'UTF-8') ?>" data-modelo="<?= $h($e['modelo'] ?? '') ?>">
          <div class="card-header"><h3 class="card-title"><?= htmlspecialchars((string) ((int) $a['ano']), ENT_QUOTES, 'UTF-8') ?></h3>
            <div class="card-actions text-secondary small"><?= $e ? $h(\App\adms\Models\escola\AdmsEtapas::MODELOS[$e['modelo']]['nome'] ?? $e['modelo']) . ($e['lancamento'] ? ' · com lançamentos' : '') : 'ainda não aberto' ?></div></div>
          <div class="card-body">
            <?php if (!$e || ($editar && !$e['lancamento'])) : ?>
              <?php if ($editar) : ?>
              <form method="post" action="<?= URLADM ?>escola-anos/abrir" class="d-flex flex-wrap gap-2 align-items-end mb-3 vc-abrir">
                <input type="hidden" name="ano" value="<?= htmlspecialchars((string) ((int) $a['ano']), ENT_QUOTES, 'UTF-8') ?>">
                <div><label class="form-label" for="modelo<?= htmlspecialchars((string) ((int) $a['ano']), ENT_QUOTES, 'UTF-8') ?>"><?= $e ? 'Trocar o modelo' : 'Abrir o ano com' ?></label>
                  <select name="modelo" id="modelo<?= htmlspecialchars((string) ((int) $a['ano']), ENT_QUOTES, 'UTF-8') ?>" class="form-select">
                    <?php foreach (\App\adms\Models\escola\AdmsEtapas::MODELOS as $k => $m) : ?><option value="<?= $h($k) ?>"<?= htmlspecialchars((string) (($e['modelo'] ?? 'bimestre') === $k ? ' selected' : ''), ENT_QUOTES, 'UTF-8') ?>><?= $h($m['nome']) ?></option><?php endforeach; ?>
                  </select></div>
                <button type="submit" class="btn btn-<?= $e ? 'outline-secondary' : 'primary' ?>"><?= $e ? 'Trocar' : 'Abrir o ano' ?></button>
              </form>
              <?php else : ?><p class="text-secondary mb-0">Ainda não aberto.</p><?php endif; ?>
            <?php endif; ?>
            <?php if ($e) : ?>
              <form method="post" action="<?= URLADM ?>escola-anos/salvar" class="vc-etapas">
                <input type="hidden" name="ano" value="<?= htmlspecialchars((string) ((int) $a['ano']), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="etapa_id" value="">
                <div class="table-responsive">
                  <table class="table table-vcenter mb-2">
                    <thead><tr><th>Etapa</th><th>Início</th><th>Fim</th><th>Conselho de classe</th><th>Boletim no portal</th></tr></thead>
                    <tbody>
                    <?php foreach ($e['etapas'] as $et) : $o = (int) $et['ordem']; ?>
                      <tr data-etapa="<?= htmlspecialchars((string) ($o), ENT_QUOTES, 'UTF-8') ?>">
                        <td><input type="text" class="form-control" name="etapas[<?= htmlspecialchars((string) ($o), ENT_QUOTES, 'UTF-8') ?>][nome]" value="<?= $h($et['nome']) ?>" maxlength="40" <?= $editar ? '' : 'readonly' ?> aria-label="Nome da etapa <?= htmlspecialchars((string) ($o), ENT_QUOTES, 'UTF-8') ?>"></td>
                        <td><input type="date" class="form-control" name="etapas[<?= htmlspecialchars((string) ($o), ENT_QUOTES, 'UTF-8') ?>][inicio]" value="<?= $h($et['inicio']) ?>" <?= $editar ? '' : 'readonly' ?> aria-label="Início"></td>
                        <td><input type="date" class="form-control" name="etapas[<?= htmlspecialchars((string) ($o), ENT_QUOTES, 'UTF-8') ?>][fim]" value="<?= $h($et['fim']) ?>" <?= $editar ? '' : 'readonly' ?> aria-label="Fim"></td>
                        <td><input type="date" class="form-control" name="etapas[<?= htmlspecialchars((string) ($o), ENT_QUOTES, 'UTF-8') ?>][conselho]" value="<?= $h($et['conselho']) ?>" <?= $editar ? '' : 'readonly' ?> aria-label="Conselho de classe"></td>
                        <td class="vc-liberado" data-liberado="<?= in_array((int) $et['id'], $liberadas, true) ? '1' : '0' ?>"><?= in_array((int) $et['id'], $liberadas, true) ? '<span class="badge bg-green-lt">liberado</span>' : '<span class="text-secondary">não liberado</span>' ?></td>
                      </tr>
                    <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                  <?php if ($editar) : ?><button type="submit" class="btn btn-primary vc-salvar-etapas">Salvar as etapas</button><?php endif; ?>
                  <?php if ($podeLiberar) : ?><a class="btn btn-outline-secondary vc-ir-liberacao" href="<?= URLADM ?>escola-liberacao/index?ano=<?= (int) $a['ano'] ?>"><i class="fas fa-eye me-1"></i> Liberação do boletim</a>
                  <?php else : ?><span class="text-secondary small">O boletim no portal é liberado em Ensino → Liberação do boletim (a supervisão de secretaria, a direção acadêmica e o administrador).</span><?php endif; ?>
                </div>
              </form>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
</div>
