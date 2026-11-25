<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* 1.16.0 (D-438 c): a REGRA DA OFERTA — "Regra: <nome> (da disciplina | padrão da instituição | própria desta oferta)", o
   resumo, e a troca SÓ com a oferta aberta, com justificativa registrada (rm_media_regra_trocas). Encerrada: só leitura. */
use App\adms\Models\helper\AdmsUi as Ui;
use App\adms\Models\notas\AdmsRegraMedia as Regra;

$h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$d = $this->dados;
$o = $d['oferta'];
$ef = $d['ef'];
$aberta = empty($o['encerrada_em']);
$dis = $d['daDisciplina'];
?>
<div class="vc-pagina">
  <div class="pt-3"><?php /* VCOSTAEDU: o cabeçalho desta tela é a barra da disciplina */ ?>
    <div class="container-xl">
      <?= Ui::barraDisciplina((int) $o['id'], 'regra', $o['materia'] . ' — ' . $o['codtur'] . ' ' . $o['ano'], $this->dados['barra'] ?? []) ?>
    </div>
  </div>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <div class="card shadow" id="regraDaOferta" data-origem="<?= $h($ef['origem']) ?>" data-regra="<?= htmlspecialchars((string) ((int) $ef['regraId']), ENT_QUOTES, 'UTF-8') ?>">
        <div class="card-header"><h3 class="card-title">Regra de média da oferta</h3></div>
        <div class="card-body">
          <p class="mb-1 h5" id="regraNome">Regra: <b><?= $h($ef['nome'] ?? '') ?></b> <small class="text-muted">(<?= $h(Regra::ORIGENS[$ef['origem']] ?? $ef['origem']) ?>)</small></p>
          <p class="mb-2 small" id="regraResumo"><?= $h($d['resumo']) ?></p>
          <?php if ($ef['origem'] === 'oferta') : ?><p class="small text-muted mb-2">A disciplina da matriz usa "<?= $h($dis['nome'] ?? '') ?>"; esta oferta trocou.</p><?php endif; ?>
          <?php if (!empty($d['podeEditar']) && (int) $ef['regraId'] > 0) : ?><a href="<?= htmlspecialchars((string) (URLADM . 'regra-media/editar/' . (int) $ef['regraId']), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-info" id="verRegra"><i class="fas fa-calculator"></i> Ver a regra (e o simulador)</a><?php endif; ?>
          <?php if (!$aberta) : ?>
            <div class="alert alert-secondary mt-3 mb-0" id="ofertaEncerradaRegra"><i class="fas fa-lock"></i> Oferta encerrada: a regra não muda. O encerramento guardou a regra que valeu; para trocar, o administrador reabre a disciplina.</div>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($aberta) : ?>
      <form method="POST" action="<?= htmlspecialchars((string) (URLADM . 'regra-media/oferta/' . (int) $o['id']), ENT_QUOTES, 'UTF-8') ?>" class="card shadow" id="formTroca">
        <input type="hidden" name="acao" value="trocar">
        <div class="card-header"><h3 class="card-title">Trocar a regra desta oferta</h3></div>
        <div class="card-body">
          <div class="row g-2">
            <div class="mb-3 col-md-6"><label class="form-label" for="regraNova">Regra</label>
              <select class="form-select" id="regraNova" name="regra_media_id">
                <option value="" <?= htmlspecialchars((string) ($ef['origem'] !== 'oferta' ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>>A da disciplina (<?= $h($dis['nome'] ?? 'Padrão da instituição') ?>)</option>
                <?php foreach ($d['regras'] as $r) : ?><option value="<?= htmlspecialchars((string) ((int) $r['id']), ENT_QUOTES, 'UTF-8') ?>" <?= htmlspecialchars((string) ($ef['origem'] === 'oferta' && (int) $ef['regraId'] === (int) $r['id'] ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>><?= $h($r['nome']) ?></option><?php endforeach; ?>
              </select>
              <small class="text-muted">Só as regras ativas. A troca vale só para esta oferta.</small></div>
            <div class="mb-3 col-md-6"><label class="form-label" for="justificativa">Justificativa</label>
              <textarea class="form-control" id="justificativa" name="justificativa" rows="2" maxlength="500" required placeholder="por que esta oferta usa outra regra"></textarea></div>
          </div>
          <button type="submit" class="btn btn-primary" id="trocarRegra"><i class="fas fa-exchange-alt"></i> Trocar a regra</button>
        </div>
      </form>
      <?php endif; ?>

      <?php if ($d['trocas']) : ?>
        <div class="card shadow-sm" id="historicoTrocas"><div class="card-header"><b>Trocas de regra nesta oferta</b></div>
          <div class="card-body p-0"><table class="table table-sm mb-0"><thead><tr><th>Quando</th><th>De</th><th>Para</th><th>Justificativa</th><th>Quem</th></tr></thead><tbody>
            <?php foreach ($d['trocas'] as $t) : ?><tr><td><?= $h(date('d/m/Y H:i', strtotime($t['created']))) ?></td><td><?= $h($t['de'] ?? '—') ?></td><td><?= $h($t['para'] ?? 'a da disciplina') ?></td><td><?= $h($t['justificativa']) ?></td><td><?= $h($t['usuario'] ?? '') ?></td></tr><?php endforeach; ?>
          </tbody></table></div></div>
      <?php endif; ?>
    </div>
  </section>
</div>
