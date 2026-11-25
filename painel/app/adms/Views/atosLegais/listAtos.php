<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* E6a: atos legais (D-36) — o histórico usa o ato ativo e vigente da modalidade da matrícula. */
$h = fn($t) => htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
$d = fn($v) => $v ? date('d/m/Y', strtotime($v)) : '—';
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Atos legais<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php
      if (isset($_SESSION['msg'])) {
        echo $_SESSION['msg'];
        unset($_SESSION['msg']);
      }
      ?>
      <div class="card shadow" id="vigentesHoje">
        <div class="card-header"><h3 class="card-title">Ato aplicável hoje, por modalidade</h3></div>
        <ul class="list-group list-group-flush">
          <?php foreach ($this->dados['vigentes'] as $v) : ?>
            <li class="list-group-item"><b><?= $h($v['modalidade']) ?>:</b>
              <?= $v['ato'] ? $h(\App\adms\Models\atosLegais\AdmsAtosLegais::descrever($v['ato'])) : '<span class="text-danger">' . $h($v['alerta']) . '</span>' ?></li>
          <?php endforeach; ?>
        </ul>
        <div class="card-footer"><small class="text-muted">O histórico usa o ato da <b>modalidade da matrícula</b> do aluno, ativo e vigente na data de referência (a conclusão, ou a data da emissão). Nenhum ou mais de um: o histórico sinaliza "ato legal não definido".</small></div>
      </div>
      <div class="card shadow">
        <div class="card-header"><h3 class="card-title"><?= count($this->dados['atos']) ?> ato(s)</h3>
          <a href="<?= URLADM . 'atos-legais/cadastrar' ?>" class="btn btn-sm btn-success float-end"><i class="fas fa-plus"></i> Novo ato</a></div>
        <div class="card-body table-responsive">
          <table class="table table-sm table-striped table-hover table-compacta lista-dados mb-0" id="tabelaAtos" data-titulo="Atos legais" data-ordem='[[0,"asc"]]'>
            <thead><tr><th>Modalidade</th><th>Ato</th><th>Órgão</th><th>Publicação</th><th>Vigência</th><th>Situação</th><th class="nao-exportar nao-ordenar text-end">Ações</th></tr></thead>
            <tbody>
              <?php foreach ($this->dados['atos'] as $a) : ?>
                <tr>
                  <td><?= $h($a['modalidade']) ?></td>
                  <td><?= $h($a['tipo']) ?> nº <?= $h($a['numero']) ?>, de <?= $d($a['data_ato']) ?></td>
                  <td><?= $h($a['orgao']) ?></td>
                  <td><?= $h($a['veiculo_publicacao'] ?? '') ?> <?= $a['data_publicacao'] ? $d($a['data_publicacao']) : '' ?></td>
                  <td><?= $d($a['vigencia_inicio']) ?> a <?= $a['vigencia_fim'] ? $d($a['vigencia_fim']) : 'sem fim' ?></td>
                  <td><?= (int) $a['situacao'] === 1 ? '<span class="badge bg-success-lt">Ativo</span>' : '<span class="badge bg-secondary-lt">Inativo</span>' ?></td>
                  <td class="acoes-linha"><?= \App\adms\Models\helper\AdmsUi::acao('editar', URLADM . 'atos-legais/editar/' . (int) $a['id'], 'Editar o ato legal') ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>
</div>
