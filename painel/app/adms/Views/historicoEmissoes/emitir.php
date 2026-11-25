<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
use App\adms\Models\historico\AdmsHistoricoModelos as M;

$h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$v = $this->dados['vida'];
$mc = (int) $this->dados['mcId'];
$tipo = $this->dados['tipo'];
$p = $this->dados['prep'];
$bloqueado = !$p || $p['bloqueios'];
$doc = M::nomeDocumento($tipo); // E6: histórico ou atestado
?>
<div class="vc-pagina">
  <?php ob_start(); ?><?= $tipo === 'ATESTADO' ? 'Emitir atestado de matrícula' : 'Emitir histórico' ?><?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <div class="card shadow">
        <div class="card-body">
          <?php $escola = \App\adms\Models\helper\AdmsTipoInstituicao::ehEscola(); /* 2.1 (D-87): na escola, só o atestado */ ?>
          <?php if ($escola) : ?>
          <p class="mb-2"><b><?= $h($v['aluno']['nome']) ?></b> (matrícula <?= $h($v['aluno']['rm']) ?>) — <?= $h($v['matricula']['curso']) ?> — situação <b id="situacaoMatricula"><?= $h($v['matricula']['situacao']) ?></b>
            <a class="btn btn-sm btn-outline-secondary ms-2" href="<?= $h(URLADM . 'academicos/visualizar/' . (int) $v['aluno']['id'] . '?tab=tabeHistoricos') ?>">Voltar à ficha do aluno</a></p>
          <?php else : ?>
          <p class="mb-2"><b><?= $h($v['aluno']['nome']) ?></b> (<?= \App\adms\Models\helper\AdmsTipoInstituicao::rotuloNumero() ?> <?= $h($v['aluno']['rm']) ?>) — <?= $h($v['matricula']['curso']) ?> — <?= $h($v['matricula']['modalidade']) ?> — situação <b id="situacaoMatricula"><?= $h($v['matricula']['situacao']) ?></b></p>
          <?php endif; ?>
          <form method="GET" action="" class="row g-2 align-items-end">
            <?php if ($escola) : ?>
            <input type="hidden" name="tipo" value="ATESTADO">
            <?php else : ?>
            <div class="mb-3 col-md-3"><label class="form-label" for="tipo">Tipo</label>
              <select name="tipo" id="tipo" class="form-select" onchange="this.form.submit()"><?php foreach (M::TIPOS as $k => $r) : ?><option value="<?= htmlspecialchars((string) ($k), ENT_QUOTES, 'UTF-8') ?>" <?= $tipo === $k ? 'selected' : '' ?>><?= htmlspecialchars((string) ($r), ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
            <?php endif; ?>
            <div class="mb-3 col-md-5"><label class="form-label" for="modelo">Modelo</label>
              <select name="modelo" id="modelo" class="form-select" onchange="this.form.submit()">
                <?php foreach ($this->dados['modelos'] as $m) : ?><option value="<?= htmlspecialchars((string) ((int) $m['id']), ENT_QUOTES, 'UTF-8') ?>" <?= htmlspecialchars((string) ((int) $m['id'] === (int) $this->dados['modeloId'] ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>><?= $h($m['nome']) ?> (v<?= htmlspecialchars((string) ((int) $m['versao_atual']), ENT_QUOTES, 'UTF-8') ?><?= htmlspecialchars((string) ($m['doCurso'] ? ', do curso' : ', padrão'), ENT_QUOTES, 'UTF-8') ?>)</option><?php endforeach; ?>
                <?php if (!$this->dados['modelos']) : ?><option value="">— nenhum modelo ativo deste tipo —</option><?php endif; ?>
              </select></div>
            <div class="mb-3 col-md-4">
              <?php if ($this->dados['modeloId']) : ?><a class="btn btn-warning w-100" id="botaoPrevia" target="_blank" href="<?= URLADM . 'historico-emissoes/previa/' . $mc . '?tipo=' . $tipo . '&modelo=' . (int) $this->dados['modeloId'] ?>"><i class="fas fa-eye"></i> Prévia em PDF (não grava)</a><?php endif; ?>
            </div>
          </form>

          <?php $antesReab = array_filter($this->dados['anteriores'], fn($x) => (int) ($x['reaberturas'] ?? 0) > 0); /* 1.14.0 (D-415) */ ?>
          <?php if ($antesReab && $tipo !== 'ATESTADO') : ?>
            <div class="alert alert-warning" id="avisoReabertura"><b><?= $h(\App\adms\Models\historico\AdmsHistoricoEmissao::AVISO_REABERTURA) ?></b> Emitido(s) antes: <?= $h(implode(', ', array_map(fn($x) => $x['tipo'] . ' ' . $x['codigo'], $antesReab))) ?>. O documento emitido continua válido na verificação.</div>
          <?php endif; ?>
          <?php if ($p && $p['bloqueios']) : ?>
            <div class="alert alert-danger" id="bloqueios"><b><?= $h($tipo === 'ATESTADO' ? 'O atestado de matrícula' : 'O histórico ' . $tipo) ?> não pode ser emitido:</b><ul class="mb-0"><?php foreach ($p['bloqueios'] as $b) : ?><li><?= $h($b) ?></li><?php endforeach; ?></ul></div>
          <?php endif; ?>
          <?php if ($p && $p['alertas']) : ?>
            <div class="alert alert-warning" id="alertasEmissao"><b><?= htmlspecialchars(\App\adms\Models\helper\AdmsUi::plural(count($p['alertas']), 'alerta', 'alertas'), ENT_QUOTES, 'UTF-8') ?>:</b><ul class="mb-0"><?php foreach ($p['alertas'] as $a) : ?><li><?= $h($a) ?></li><?php endforeach; ?></ul></div>
          <?php elseif ($p) : ?>
            <div class="alert alert-success" id="alertasEmissao">Nenhum alerta: os dados estão completos para o documento.</div>
          <?php endif; ?>

          <form method="POST" action="" id="formEmitir">
            <input type="hidden" name="tipo" value="<?= $h($tipo) ?>"><input type="hidden" name="modelo" value="<?= (int) $this->dados['modeloId'] ?>">
            <?php if ($p && $p['alertas'] && !$p['bloqueios']) : ?>
              <div class="form-check mb-2"><input type="checkbox" class="form-check-input" id="confirmo_alertas" name="confirmo_alertas" value="1"><label class="form-check-label" for="confirmo_alertas">Li os alertas acima e emito o documento assim mesmo. <b>Os alertas ficam gravados na emissão.</b></label></div>
            <?php endif; ?>
            <input type="submit" name="EmitirHistorico" value="<?= $h($tipo === 'ATESTADO' ? 'Emitir atestado de matrícula' : 'Emitir histórico ' . $tipo) ?>" class="btn btn-primary" <?= $bloqueado ? 'disabled' : '' ?>>
            <small class="text-muted ms-2">A emissão grava a cópia fiel (snapshot), o PDF e os hashes, com um código de verificação. Emitir de novo gera outra emissão.</small>
          </form>
        </div>
      </div>

      <div class="card shadow">
        <div class="card-header"><h3 class="card-title">Emissões deste aluno</h3></div>
        <div class="card-body table-responsive p-0">
          <table class="table table-sm mb-0">
            <thead><tr><th>Código</th><th>Tipo</th><th>Curso</th><th>Emitido em</th><th>Situação</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($this->dados['anteriores'] as $e) : ?>
              <tr><td class="font-monospace"><?= $h($e['codigo']) ?></td><td><?= $h($e['tipo']) ?><?= (int) ($e['reaberturas'] ?? 0) > 0 ? ' <span class="badge bg-warning-lt aviso-reabertura" title="' . $h(\App\adms\Models\historico\AdmsHistoricoEmissao::AVISO_REABERTURA) . '">anterior a reabertura</span>' : '' ?></td><td><?= $h($e['curso']) ?></td><td><?= htmlspecialchars((string) (date('d/m/Y H:i', strtotime($e['emitido_em']))), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= (int) $e['situacao'] === 1 ? '<span class="badge bg-success-lt">Válida</span>' : '<span class="badge bg-danger-lt">Cancelada</span>' ?></td>
                <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars((string) (URLADM . 'historico-emissoes/ver/' . (int) $e['id']), ENT_QUOTES, 'UTF-8') ?>">Ver</a></td></tr>
            <?php endforeach; ?>
            <?php if (!$this->dados['anteriores']) : ?><tr><td colspan="6" class="text-muted">Nenhuma.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>
</div>
