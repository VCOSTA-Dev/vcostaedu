<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* A PRÉVIA E A EMISSÃO (D-114): os avisos (só "não cabe" e "sem série" impedem), a data do documento, o número de
   publicação, os dias letivos e as aulas dadas do bloco de transferência (propostos, editáveis); a prévia em PDF; emitir
   (confirmando os avisos); as vias emitidas: a 2ª via (a reimpressão), o cancelamento com o motivo, o PDF guardado. */
use App\adms\Models\historicoEscola\AdmsHistBase as B;

$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$d = $this->dados;
$a = $d['aluno'];
$m = $d['montagem'];
$nivel = $d['nivel'];
$op = $d['op'];
$tr = $m['dados']['transferencia'] ?? ['preenchido' => false];
$base = URLADM . 'historico-escola-emissao/';
$qs = fn(array $x) => http_build_query(['nivel' => $nivel] + $x);
?>
<div class="vc-pagina" id="historicoEmissao">
  <?php $paginaTitulo = 'Prévia e emissão — ' . $h($a['nome']);
  $paginaPretitulo = 'Histórico escolar · ' . $h(B::NIVEIS[$nivel]) . ' · RM ' . $h($a['rm']) . ($a['prova'] ? ' · PROVA' : '');
  $paginaAcoesHtml = '<a href="' . URLADM . 'historico-escola-vida/aluno/' . (int) $a['id'] . '?nivel=' . $nivel . '" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> A vida escolar</a>';
  include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <?php if ($m['bloqueios']) : ?><div class="alert alert-danger" id="bloqueios"><b>Não pode ser emitido:</b><ul class="mb-0"><?php foreach ($m['bloqueios'] as $x) : ?><li><?= $h($x) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
      <?php if ($m['avisos']) : ?><div class="alert alert-warning" id="avisos"><b><?= count($m['avisos']) ?> aviso<?= count($m['avisos']) === 1 ? '' : 's' ?></b> (não impedem a emissão; quem emite decide):<ul class="mb-0"><?php foreach ($m['avisos'] as $x) : ?><li><?= $h($x) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
      <form method="get" class="card mb-3" id="formPrevia" action="<?= $base ?>previa/<?= (int) $a['id'] ?>">
        <input type="hidden" name="nivel" value="<?= $h($nivel) ?>">
        <div class="card-header"><h3 class="card-title">O documento</h3></div>
        <div class="card-body row g-3">
          <div class="col-md-3"><label class="form-label" for="eData">Data do documento</label><input type="date" class="form-control" name="data" id="eData" value="<?= $h($m['dados']['data'] ?? date('Y-m-d')) ?>"></div>
          <div class="col-md-4"><label class="form-label" for="ePub">Número de publicação</label><input class="form-control" name="publicacao" id="ePub" maxlength="60" value="<?= $h($op['publicacao']) ?>"><div class="form-hint">Digitado quando houver; o rótulo vem dos textos do histórico.</div></div>
          <?php if (!empty($tr['preenchido'])) : ?>
            <div class="col-md-2"><label class="form-label" for="eDias">Dias letivos (até <?= $h($tr['ate']) ?>)</label><input type="number" class="form-control" name="dias_letivos" id="eDias" value="<?= $h($tr['dias']) ?>"><div class="form-hint">Proposto: <?= (int) ($tr['diasPropostos'] ?? 0) ?> (os dias úteis).</div></div>
            <div class="col-12"><div class="form-label">Aulas dadas no bloco de transferência (propostas pela matriz: aulas semanais × semanas decorridas)</div>
              <div class="d-flex flex-wrap gap-2"><?php foreach (array_merge($tr['bnc'], $tr['pd']) as $l) : ?><label class="d-flex align-items-center gap-1 small"><?= $h($l['nome']) ?><input type="number" class="form-control form-control-sm" style="width: 5rem" name="aulas[<?= $h($l['chave']) ?>]" value="<?= $h($l['aulas']) ?>"></label><?php endforeach; ?></div></div>
          <?php endif; ?>
        </div>
        <div class="card-footer d-flex gap-2">
          <button class="btn btn-outline-primary" id="atualizarPrevia"><i class="fas fa-sync me-1"></i> Atualizar os avisos</button>
          <a class="btn btn-outline-primary" target="_blank" id="abrirPrevia" href="<?= $base ?>previa-pdf/<?= (int) $a['id'] ?>?<?= $h($qs(array_filter($op, fn($v) => $v !== '' && $v !== []))) ?>"><i class="fas fa-file-pdf me-1"></i> Prévia em PDF</a>
        </div>
      </form>
      <?php if ($d['pode']['emitir'] && !$m['bloqueios']) : ?>
        <form method="post" action="<?= $base ?>emitir/<?= (int) $a['id'] ?>" class="card card-body mb-3" id="formEmitir">
          <input type="hidden" name="nivel" value="<?= $h($nivel) ?>"><input type="hidden" name="data" value="<?= $h($m['dados']['data'] ?? '') ?>"><input type="hidden" name="publicacao" value="<?= $h($op['publicacao']) ?>">
          <input type="hidden" name="dias_letivos" value="<?= $h($op['dias_letivos']) ?>"><?php foreach ($op['aulas'] as $k => $v) : ?><input type="hidden" name="aulas[<?= $h($k) ?>]" value="<?= $h($v) ?>"><?php endforeach; ?>
          <?php if ($m['avisos']) : ?><label class="form-check mb-2"><input type="checkbox" class="form-check-input" name="confirmo" value="1" id="confirmoAvisos"><span class="form-check-label">Li os avisos e quero emitir assim mesmo</span></label><?php endif; ?>
          <div><button class="btn btn-primary" id="emitirHistorico"><i class="fas fa-file-signature me-1"></i> Emitir (<?= $h($m['dados']['fecho']['titulo'] ?? '') ?>)</button>
            <span class="small text-secondary ms-2">A emissão guarda a cópia do que saiu (o PDF, o conteúdo e o hash) e dá o código de verificação.</span></div>
        </form>
      <?php endif; ?>
      <div class="card"><div class="card-header"><h3 class="card-title">Vias emitidas</h3></div>
        <div class="table-responsive"><table class="table table-vcenter card-table" id="tabelaEmissoes">
          <thead><tr><th>Código</th><th>Emitido em</th><th>Por</th><th>Situação</th><th>Vias</th><th></th></tr></thead><tbody>
          <?php foreach ($d['emissoes'] as $e) : $valida = (int) $e['situacao'] === 1; ?>
            <tr data-emissao="<?= (int) $e['id'] ?>"><td class="text-nowrap"><code><?= $h($e['codigo']) ?></code></td><td><?= $h(date('d/m/Y H:i', strtotime($e['emitido_em']))) ?></td><td><?= $h($e['emissor'] ?? '') ?></td>
              <td class="vc-situacao"><?= $valida ? 'válida' : 'cancelada' . ($e['cancelamento_motivo'] ? '<div class="small text-secondary">' . $h($e['cancelamento_motivo']) . '</div>' : '') ?></td>
              <td><a href="<?= $base ?>pdf/<?= (int) $e['id'] ?>" target="_blank" class="vc-via">1ª</a><?php foreach ($e['vias'] as $x) : ?> · <a href="<?= $base ?>pdf/<?= (int) $e['id'] ?>?via=<?= (int) $x['via'] ?>" target="_blank" class="vc-via"><?= (int) $x['via'] ?>ª</a><?php endforeach; ?></td>
              <td class="text-nowrap"><?php if ($valida && $d['pode']['via']) : ?><form method="post" action="<?= $base ?>segunda-via/<?= (int) $a['id'] ?>" class="d-inline"><input type="hidden" name="emissao" value="<?= (int) $e['id'] ?>"><input type="hidden" name="nivel" value="<?= $h($nivel) ?>"><button class="btn btn-sm btn-outline-primary vc-segunda-via">2ª via</button></form><?php endif; ?>
                <?php if ($valida && $d['pode']['cancelar']) : ?><form method="post" action="<?= $base ?>cancelar/<?= (int) $a['id'] ?>" class="d-inline-flex gap-1"><input type="hidden" name="emissao" value="<?= (int) $e['id'] ?>"><input type="hidden" name="nivel" value="<?= $h($nivel) ?>"><input class="form-control form-control-sm vc-motivo" name="motivo" placeholder="motivo do cancelamento" aria-label="Motivo do cancelamento"><button class="btn btn-sm btn-outline-danger vc-cancelar">Cancelar</button></form><?php endif; ?></td></tr>
          <?php endforeach; ?>
          <?php if (!$d['emissoes']) : ?><tr><td colspan="6" class="text-secondary">Nenhum histórico emitido para este aluno.</td></tr><?php endif; ?>
          </tbody></table></div></div>
    </div>
  </section>
</div>
