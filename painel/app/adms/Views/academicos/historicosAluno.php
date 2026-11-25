<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* E6b — aba HISTÓRICOS da ficha do aluno: emitir por matrícula e as emissões já feitas. */
$hh = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
/* Onda 8, correção, segunda leva, 2.1 (D-87): na escola, a aba é DOCUMENTOS e emite só o atestado de matrícula — sem a
   grade, o parcial e o final (o histórico escolar da educação básica é uma etapa à parte) */
$escolaDoc = \App\adms\Models\helper\AdmsTipoInstituicao::ehEscola();
?>
<div id="historicosAluno">
  <?php foreach ($this->dados['vinculos'] ?? [] as $vv) : ?>
    <div class="mb-2 matricula-historico" data-matricula="<?= htmlspecialchars((string) ((int) $vv['id']), ENT_QUOTES, 'UTF-8') ?>">
      <b><?= $hh($vv['curso']) ?></b><?php if (!$escolaDoc) : ?> <span class="badge bg-secondary-lt ms-1 vc-grade" title="A versão da grade (a matriz) desta matrícula">Grade <?= htmlspecialchars((string) ((int) $vv['versao']), ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?> — <i><?= $hh($vv['situacao']) ?></i>
      <?php if (!$escolaDoc) : ?>
      <a class="btn btn-sm btn-info ms-2" href="<?= htmlspecialchars((string) (URLADM . 'historico-emissoes/emitir/' . (int) $vv['id'] . '?tipo=PARCIAL'), ENT_QUOTES, 'UTF-8') ?>">Emitir parcial</a>
      <a class="btn btn-sm btn-primary" href="<?= htmlspecialchars((string) (URLADM . 'historico-emissoes/emitir/' . (int) $vv['id'] . '?tipo=FINAL'), ENT_QUOTES, 'UTF-8') ?>">Emitir final</a>
      <?php endif; ?>
      <?php /* E6 (D-373): o atestado só com a matrícula em "Cursando"; senão, a razão */ ?>
      <?php if ((int) $vv['situacao_id'] === 1) : ?>
        <a class="btn btn-sm btn-outline-info botao-atestado" href="<?= htmlspecialchars((string) (URLADM . 'historico-emissoes/emitir/' . (int) $vv['id'] . '?tipo=ATESTADO'), ENT_QUOTES, 'UTF-8') ?>"><i class="fas fa-file-signature"></i> Emitir atestado</a>
      <?php else : ?>
        <button type="button" class="btn btn-sm btn-outline-secondary botao-atestado" disabled title="<?= $hh('O atestado de matrícula só sai com a matrícula em "Cursando" (atual: ' . $vv['situacao'] . ').') ?>"><i class="fas fa-file-signature"></i> Atestado</button>
        <small class="text-muted razao-atestado">atestado só com a matrícula em "Cursando"</small>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
  <?php if (empty($this->dados['vinculos'])) : ?><p class="text-muted">O aluno não tem matrícula em curso: vincule-o na aba Matrículas.</p><?php endif; ?>
  <table class="table table-sm mt-2 mb-0">
    <thead><tr><th>Código</th><th>Tipo</th><th>Curso</th><th>Modelo</th><th>Emitido em</th><th>Situação</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($this->dados['historicos'] ?? [] as $em) : ?>
      <tr><td class="font-monospace"><?= $hh($em['codigo']) ?></td><td><?= $hh($em['tipo']) ?><?= (int) ($em['reaberturas'] ?? 0) > 0 ? ' <span class="badge bg-warning-lt aviso-reabertura" title="' . $hh(\App\adms\Models\historico\AdmsHistoricoEmissao::AVISO_REABERTURA) . '">anterior a reabertura</span>' : '' ?></td><td><?= $hh($em['curso']) ?></td><td><?= $hh($em['modelo']) ?> v<?= htmlspecialchars((string) ((int) $em['modeloVersao']), ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars((string) (date('d/m/Y H:i', strtotime($em['emitido_em']))), ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= (int) $em['situacao'] === 1 ? '<span class="badge bg-success-lt">Válida</span>' : '<span class="badge bg-danger-lt">Cancelada</span>' ?></td>
        <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars((string) (URLADM . 'historico-emissoes/ver/' . (int) $em['id']), ENT_QUOTES, 'UTF-8') ?>">Ver</a></td></tr>
    <?php endforeach; ?>
    <?php if (empty($this->dados['historicos'])) : ?><tr><td colspan="7" class="text-muted">Nenhum documento emitido.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
