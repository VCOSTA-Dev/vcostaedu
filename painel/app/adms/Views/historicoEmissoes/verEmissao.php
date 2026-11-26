<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
use App\adms\Models\historico\AdmsHistoricoEmissao as E;

$h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$e = $this->dados['e'];
$dt = fn($x) => $x ? date('d/m/Y H:i', strtotime($x)) : '—';
$alertas = $e['alertas_aceitos'] ? json_decode($e['alertas_aceitos'], true) : [];
$valida = (int) $e['situacao'] === 1;
?>
<div class="vc-pagina">
  <?php ob_start(); ?><?= $e['tipo'] === 'ATESTADO' && \App\adms\Models\helper\AdmsTipoInstituicao::ehEscola() ? 'Atestado de matrícula' : (str_starts_with((string) $e['tipo'], 'ESC_') ? $h(\App\adms\Models\historico\AdmsHistoricoModelos::nomeDocumento((string) $e['tipo'])) /* D-114 */ : 'Histórico ' . $h($e['tipo'])) /* 2.1 (D-87) */ ?> <small class="font-monospace" id="codigoEmissao"><?= $h($e['codigo']) ?></small><?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <div class="row">
        <div class="col-lg-7">
          <div class="card shadow">
            <div class="card-body p-0">
              <table class="table table-sm mb-0" id="dadosEmissao">
                <tr><th style="width: 32%">Situação</th><td><span class="badge bg-<?= $valida ? 'success' : 'danger' ?>-lt" id="situacaoEmissao"><?= $h(E::SITUACOES[(int) $e['situacao']] ?? '?') ?></span>
                  <?= !$valida ? ' em ' . $dt($e['cancelado_em']) . ' por ' . $h($e['cancelador']) . ' — motivo: ' . $h($e['cancelamento_motivo']) : '' ?></td></tr>
                <tr><th>Aluno</th><td><a href="<?= htmlspecialchars((string) (URLADM . 'academicos/visualizar/' . (int) $e['aluno_id'] . '?tab=tabeHistoricos'), ENT_QUOTES, 'UTF-8') ?>"><?= $h($e['aluno']) ?></a> (RA <?= $h($e['rm']) ?>)</td></tr>
                <tr><th>Curso</th><td><?= $h($e['curso']) ?></td></tr>
                <tr><th>Modelo</th><td><?= $h($e['modelo']) ?> — versão <b id="versaoUsada"><?= htmlspecialchars((string) ((int) $e['modeloVersao']), ENT_QUOTES, 'UTF-8') ?></b></td></tr>
                <tr><th>Emitido em</th><td><?= $dt($e['emitido_em']) ?> por <?= $h($e['emissor']) ?></td></tr>
                <tr><th>Hash do conteúdo<br><small>sha256(snapshot + HTML)</small></th><td class="font-monospace small" style="word-break: break-all"><?= $h($e['hash_conteudo']) ?><br>
                  <?= $e['integra'] ? '<span class="badge bg-success-lt" id="integridade">confere com os dados gravados</span>' : '<span class="badge bg-danger-lt" id="integridade">NÃO confere: dados gravados alterados</span>' ?></td></tr>
                <tr><th>Hash do PDF<br><small>sha256 do arquivo</small></th><td class="font-monospace small" style="word-break: break-all"><?= $h($e['hash_pdf']) ?><br>
                  <?= $e['arquivoConfere'] ? '<span class="badge bg-success-lt">arquivo confere</span>' : '<span class="badge bg-danger-lt">arquivo ausente ou alterado</span>' ?></td></tr>
                <tr><th>Alertas aceitos</th><td id="alertasAceitos"><?= $alertas ? '<ul class="mb-0 ps-3"><li>' . implode('</li><li>', array_map($h, $alertas)) . '</li></ul>' : '<span class="text-muted">nenhum</span>' ?></td></tr>
                <tr><th>Verificação pública</th><td><a href="<?= $h(E::urlVerificacao($e['codigo'])) ?>" target="_blank"><?= $h(E::urlVerificacao($e['codigo'])) ?></a></td></tr>
              </table>
            </div>
          </div>
        </div>
        <div class="col-lg-5">
          <div class="card shadow">
            <div class="card-header"><h3 class="card-title">Documentos</h3></div>
            <div class="card-body">
              <a class="btn btn-primary btn-sm mb-2" id="pdfOriginal" target="_blank" href="<?= htmlspecialchars((string) (URLADM . 'historico-emissoes/pdf/' . (int) $e['id']), ENT_QUOTES, 'UTF-8') ?>"><i class="fas fa-file-pdf"></i> PDF original</a>
              <?php foreach ($e['vias'] as $v) : ?>
                <br><a class="btn btn-outline-primary btn-sm mb-1" target="_blank" href="<?= htmlspecialchars((string) (URLADM . 'historico-emissoes/pdf/' . (int) $e['id'] . '?via=' . (int) $v['via']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ((int) $v['via']), ENT_QUOTES, 'UTF-8') ?>ª via</a> <small><?= $dt($v['emitido_em']) ?> por <?= $h($v['emissor']) ?></small>
              <?php endforeach; ?>
              <?php if ($valida) : ?>
                <form method="POST" action="<?= htmlspecialchars((string) (URLADM . 'historico-emissoes/segunda-via/' . (int) $e['id']), ENT_QUOTES, 'UTF-8') ?>" class="mt-2">
                  <input type="submit" name="SegundaVia" value="Gerar <?= htmlspecialchars((string) (count($e['vias']) + 2), ENT_QUOTES, 'UTF-8') ?>ª via" class="btn btn-outline-secondary btn-sm">
                  <small class="text-muted d-block">Reimprime o MESMO conteúdo gravado, com a marca da via e o mesmo código.</small>
                </form>
              <?php endif; ?>
            </div>
          </div>
          <?php if ($valida && !empty($this->dados['botao']['cancelar'])) : ?>
            <div class="card shadow">
              <div class="card-header"><h3 class="card-title">Cancelar esta emissão</h3></div>
              <div class="card-body">
                <form method="POST" action="<?= htmlspecialchars((string) (URLADM . 'historico-emissoes/cancelar/' . (int) $e['id']), ENT_QUOTES, 'UTF-8') ?>">
                  <textarea name="motivo" class="form-control form-control-sm mb-2" rows="2" maxlength="500" placeholder="Motivo (obrigatório)"></textarea>
                  <input type="submit" name="CancelarEmissao" value="Cancelar emissão" class="btn btn-danger btn-sm">
                  <small class="text-muted d-block">O documento não é apagado: a verificação pública passa a mostrar "cancelada".</small>
                </form>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>
</div>
