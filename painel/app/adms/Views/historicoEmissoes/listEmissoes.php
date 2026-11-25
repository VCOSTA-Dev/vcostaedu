<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
use App\adms\Models\historico\AdmsHistoricoEmissao as E;

$h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$f = $this->dados['filtros'];
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Históricos emitidos<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <?php if (!empty($this->dados['semGatilhos'])) : ?><div class="alert alert-warning" id="semGatilhos"><b>Proteção do banco ausente:</b> os gatilhos que impedem alterar ou apagar uma emissão não estão instalados (o usuário do banco não tinha o privilégio TRIGGER). As emissões estão protegidas só pelo sistema. Veja o README, seção "Gatilhos".</div><?php endif; ?>
      <div class="card shadow">
        <div class="card-body pb-1">
          <form method="GET" action="" class="row g-2">
            <div class="mb-3 col-md-5"><input type="text" name="busca" class="form-control form-control-sm" placeholder="RA, nome do aluno ou código do documento" value="<?= $h($f['busca']) ?>"></div>
            <div class="mb-3 col-md-2"><select name="tipo" class="form-select form-select-sm"><option value="">Tipo: todos</option><option value="PARCIAL" <?= htmlspecialchars((string) ($f['tipo'] === 'PARCIAL' ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>>Parcial</option><option value="FINAL" <?= htmlspecialchars((string) ($f['tipo'] === 'FINAL' ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>>Final</option></select></div>
            <div class="mb-3 col-md-2"><select name="situacao" class="form-select form-select-sm"><option value="0">Situação: todas</option><?php foreach (E::SITUACOES as $k => $r) : ?><option value="<?= htmlspecialchars((string) ($k), ENT_QUOTES, 'UTF-8') ?>" <?= htmlspecialchars((string) ((int) $f['situacao'] === $k ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>><?= htmlspecialchars((string) ($r), ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
            <div class="mb-3 col-md-3"><button class="btn btn-primary btn-sm">Buscar</button></div>
          </form>
          <?php if ($this->dados['matriculas']) : ?>
            <div class="alert alert-light border mb-2" id="matriculasParaEmitir"><b>Emitir para:</b>
              <?php foreach ($this->dados['matriculas'] as $m) : ?>
                <div class="mt-1"><?= $h($m['nome']) ?> (<?= $h($m['rm']) ?>) — <?= $h($m['curso']) ?> — <i><?= $h($m['situacao']) ?></i>
                  <a class="btn btn-sm btn-info" href="<?= htmlspecialchars((string) (URLADM . 'historico-emissoes/emitir/' . (int) $m['id'] . '?tipo=PARCIAL'), ENT_QUOTES, 'UTF-8') ?>">Emitir parcial</a>
                  <a class="btn btn-sm btn-primary" href="<?= htmlspecialchars((string) (URLADM . 'historico-emissoes/emitir/' . (int) $m['id'] . '?tipo=FINAL'), ENT_QUOTES, 'UTF-8') ?>">Emitir final</a></div>
              <?php endforeach; ?>
            </div>
          <?php elseif ($f['busca'] === '') : ?>
            <p class="text-muted small mb-2">Para emitir, busque o aluno acima ou use a aba <b>Históricos</b> da ficha do aluno.</p>
          <?php endif; ?>
        </div>
        <div class="card-body table-responsive">
          <table class="table table-sm table-striped table-hover table-compacta lista-dados mb-0" id="listaEmissoes" data-titulo="Históricos emitidos" data-ordem='[[5,"desc"]]'>
            <thead><tr><th>Código</th><th>Tipo</th><th>Aluno</th><th>Curso</th><th>Modelo</th><th>Emitido em</th><th>Por</th><th class="num">Vias</th><th>Situação</th><th class="nao-exportar nao-ordenar text-end">Ações</th></tr></thead>
            <tbody>
            <?php foreach ($this->dados['emissoes'] as $e) : ?>
              <tr data-codigo="<?= $h($e['codigo']) ?>">
                <td class="font-monospace"><?= $h($e['codigo']) ?></td>
                <td><span class="badge bg-<?= htmlspecialchars((string) ($e['tipo'] === 'FINAL' ? 'primary' : 'info'), ENT_QUOTES, 'UTF-8') ?>-lt"><?= $h($e['tipo']) ?></span><?= (int) ($e['reaberturas'] ?? 0) > 0 ? ' <span class="badge bg-warning-lt aviso-reabertura" title="' . $h(\App\adms\Models\historico\AdmsHistoricoEmissao::AVISO_REABERTURA) . '">anterior a reabertura</span>' : '' ?><?= $e['alertas_aceitos'] ? ' <span class="badge bg-warning-lt" title="emitido com alertas confirmados">alertas</span>' : '' ?></td>
                <td><?= $h($e['aluno']) ?> <small class="text-muted">(<?= $h($e['rm']) ?>)</small></td>
                <td><?= $h($e['curso']) ?></td>
                <td><?= $h($e['modelo']) ?> <small>v<?= htmlspecialchars((string) ((int) $e['modeloVersao']), ENT_QUOTES, 'UTF-8') ?></small></td>
                <td data-order="<?= $h($e['emitido_em']) ?>"><?= htmlspecialchars((string) (date('d/m/Y H:i', strtotime($e['emitido_em']))), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= $h($e['emissor']) ?></td>
                <td class="num"><?= htmlspecialchars((string) (1 + (int) $e['vias']), ENT_QUOTES, 'UTF-8') ?></td>
                <td><span class="badge bg-<?= htmlspecialchars((string) ((int) $e['situacao'] === 1 ? 'success' : 'danger'), ENT_QUOTES, 'UTF-8') ?>-lt"><?= $h(E::SITUACOES[(int) $e['situacao']] ?? '?') ?></span></td>
                <td class="acoes-linha"><?= \App\adms\Models\helper\AdmsUi::acao('ver', URLADM . 'historico-emissoes/ver/' . (int) $e['id'], 'Ver a emissão (PDF, 2ª via, cancelar)') ?><?= \App\adms\Models\helper\AdmsUi::acao('pdf', URLADM . 'historico-emissoes/pdf/' . (int) $e['id'], 'Abrir o PDF emitido', null, ['nova_aba' => true, 'classe' => 'btn-outline-danger']) ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>
</div>
