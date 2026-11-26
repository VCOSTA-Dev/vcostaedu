<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* A turma (escola): as ofertas da série com os professores, os alunos, enturmar em lote (em passos), sair, trocar. */
$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$d = $this->dados;
$t = $d['turma'];
$lote = $d['lote'] ?? null;
$pode = (new \App\adms\Models\AdmsBotao())->valBotao([
  'editar' => ['menu_controller' => 'turmas', 'menu_metodo' => 'editar'],
  'enturmar' => ['menu_controller' => 'turmas', 'menu_metodo' => 'enturmar'],
  'horario' => ['menu_controller' => 'turmas', 'menu_metodo' => 'horario'],
  'boletins' => ['menu_controller' => 'turmas', 'menu_metodo' => 'boletins'],
]);
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Turma <?= $h($t['codtur']) ?><?php $paginaTitulo = ob_get_clean();
  $paginaPretitulo = $h($t['segmento'] . ' · ' . $t['ano']);
  $paginaAcoesHtml = (!empty($pode['horario']) ? '<a href="' . URLADM . 'turmas/horario/' . (int) $t['id'] . '" class="btn btn-outline-secondary" id="horarioTurma"><i class="far fa-clock"></i> Horário</a>' : '')
    . (\App\adms\Models\helper\AdmsTipoInstituicao::ehLivre() ? '' : '<a href="' . URLADM . 'escola-conselho/turma/' . (int) $t['id'] . '" class="btn btn-outline-secondary" id="conselhoTurma"><i class="fas fa-people-arrows"></i> Conselho de classe</a>') /* D-106: o curso livre não tem conselho */
    /* Onda 8, correção (D-80): os boletins de todos os alunos, num PDF só */
    . (!empty($pode['boletins']) ? '<a href="' . URLADM . 'turmas/boletins/' . (int) $t['id'] . '" class="btn btn-outline-secondary" id="boletinsTurma" target="_blank" rel="noopener"><i class="fas fa-print"></i> Imprimir os boletins da turma</a>' : '')
    . (!empty($pode['editar']) ? '<a href="' . URLADM . 'turmas/editar/' . (int) $t['id'] . '" class="btn btn-outline-primary" id="editarTurma"><i class="fas fa-pen"></i> Editar</a>' : '');
  include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <?php if ($lote && !empty($lote['concluida'])) : ?>
        <div class="alert alert-success" id="loteConcluido"><?= htmlspecialchars((int) $lote['entraram'] === 1 ? '1 aluno entrou' : (int) $lote['entraram'] . ' alunos entraram', ENT_QUOTES, 'UTF-8') ?> na turma<?= htmlspecialchars((string) ($lote['recusas'] ? '; ' . count($lote['recusas']) . ' não entraram:' : '.'), ENT_QUOTES, 'UTF-8') ?>
          <?php if ($lote['recusas']) : ?><ul class="mb-0"><?php foreach ($lote['recusas'] as $r) : ?><li><?= $h($r) ?></li><?php endforeach; ?></ul><?php endif; ?></div>
      <?php elseif ($lote) : ?>
        <div class="alert alert-warning" id="loteInterrompido">A enturmação em lote não terminou (<?= htmlspecialchars((string) ((int) $lote['feitos']), ENT_QUOTES, 'UTF-8') ?> de <?= htmlspecialchars((string) ((int) $lote['total']), ENT_QUOTES, 'UTF-8') ?>).
          <button type="button" class="btn btn-warning btn-sm ms-2" id="continuarLote" data-progresso="progressoTurma" data-fase="passo"><i class="fas fa-play"></i> Continuar</button></div>
      <?php endif; ?>
      <div class="row row-cards">
        <div class="col-lg-4">
          <div class="card" id="dadosTurma"><div class="card-body">
            <dl class="row mb-0">
              <?php $livreTurma = \App\adms\Models\helper\AdmsTipoInstituicao::ehLivre(); /* D-106: no curso livre, sem a série, a unidade e as etapas */ ?>
              <?php if (!$livreTurma) : ?><dt class="col-5">Série</dt><dd class="col-7"><?= $h($t['serie']) ?></dd><?php endif; ?>
              <?php if (!empty($t['turno_nome'])) : ?><dt class="col-5">Turno</dt><dd class="col-7"><?= $h($t['turno_nome']) ?></dd><?php endif; ?>
              <?php if (!empty($t['sala'])) : ?><dt class="col-5">Sala</dt><dd class="col-7"><?= $h($t['sala']) ?></dd><?php endif; ?>
              <?php if (!empty($t['unidade']) && !$livreTurma) : ?><dt class="col-5">Unidade</dt><dd class="col-7"><?= $h($t['unidade']) ?></dd><?php endif; ?>
              <dt class="col-5">Alunos</dt><dd class="col-7" id="contaAlunos"><?= htmlspecialchars((string) (count($d['alunos'])), ENT_QUOTES, 'UTF-8') ?><?= htmlspecialchars((string) ($t['capacidade'] !== null ? ' de ' . (int) $t['capacidade'] : ''), ENT_QUOTES, 'UTF-8') ?></dd>
            </dl>
          </div></div>
        </div>
        <div class="col-lg-8">
          <div class="card" id="ofertasTurma">
            <div class="card-header"><h3 class="card-title">Disciplinas</h3></div>
            <div class="table-responsive">
              <table class="table table-vcenter card-table">
                <thead><tr><th>Disciplina</th><th>Professor</th><th class="text-end">Carga horária</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($d['ofertas'] as $o) : ?>
                  <tr data-oferta="<?= htmlspecialchars((string) ((int) $o['id']), ENT_QUOTES, 'UTF-8') ?>">
                    <td><a href="<?= URLADM ?>disciplinas/visualizar/<?= htmlspecialchars((string) ((int) $o['id']), ENT_QUOTES, 'UTF-8') ?>"><?= $h($o['materia']) ?></a></td>
                    <td><?= $o['professores'] ? $h($o['professores']) : '<span class="text-secondary">sem professor — indique na tela da disciplina</span>' ?></td>
                    <td class="text-end"><?= $h($o['ch_considerada']) ?> h</td>
                    <td class="text-end"><?php if (!$livreTurma) : ?><a href="<?= URLADM ?>escola-notas/etapas/<?= htmlspecialchars((string) ((int) $o['id']), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-primary vc-etapas-componente">Etapas e notas</a><?php endif; ?></td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="card" id="alunosTurma">
            <div class="card-header"><h3 class="card-title">Alunos da turma</h3></div>
            <?php if (!$d['alunos']) : ?><div class="card-body text-secondary" id="semAlunos">Nenhum aluno na turma.</div><?php else : ?>
            <div class="table-responsive">
              <table class="table table-vcenter card-table">
                <tbody>
                <?php foreach ($d['alunos'] as $a) : ?>
                  <tr data-aluno="<?= htmlspecialchars((string) ((int) $a['usuario_id']), ENT_QUOTES, 'UTF-8') ?>">
                    <td><?= $h($a['nome']) ?><div class="text-secondary small"><?= \App\adms\Models\helper\AdmsTipoInstituicao::rotuloNumero() ?> <?= $h($a['rm']) ?></div></td>
                    <?php if (!empty($pode['enturmar'])) : ?>
                    <td class="text-end">
                      <?php if ($d['outras']) : ?>
                      <form method="post" action="<?= URLADM ?>turmas/trocar/<?= htmlspecialchars((string) ((int) $t['id']), ENT_QUOTES, 'UTF-8') ?>" class="d-inline-flex gap-1 vc-trocar">
                        <input type="hidden" name="usuario_id" value="<?= htmlspecialchars((string) ((int) $a['usuario_id']), ENT_QUOTES, 'UTF-8') ?>">
                        <select name="destino_id" class="form-select form-select-sm" aria-label="Turma de destino">
                          <?php foreach ($d['outras'] as $ot) : ?><option value="<?= htmlspecialchars((string) ((int) $ot['id']), ENT_QUOTES, 'UTF-8') ?>"><?= $h(\App\adms\Models\escola\AdmsTurmas::codtur((string) $ot['serie'], (string) $ot['nome'])) ?></option><?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-sm btn-outline-primary">Trocar de turma</button>
                      </form>
                      <?php endif; ?>
                      <form method="post" action="<?= URLADM ?>turmas/sair/<?= htmlspecialchars((string) ((int) $t['id']), ENT_QUOTES, 'UTF-8') ?>" class="d-inline-flex gap-1 vc-sair" data-vc-confirma="Tirar o aluno da turma?" data-vc-confirma-extra="Ele sai de todas as disciplinas da turma, na data informada (a da transferência).">
                        <input type="hidden" name="usuario_id" value="<?= htmlspecialchars((string) ((int) $a['usuario_id']), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="date" name="data_saida" class="form-control form-control-sm vc-data-saida" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" aria-label="Data da saída" title="A data da saída (a da transferência)" style="width:9.5rem">
                        <button type="submit" class="btn btn-sm btn-outline-danger">Sair da turma</button>
                      </form>
                    </td>
                    <?php endif; ?>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
          </div>
        </div>
        <?php /* D-116: o itinerário formativo de cada aluno na matrícula do ano (o Médio com itinerário na matriz do histórico) */
          $itTurma = \App\adms\Models\helper\AdmsTipoInstituicao::ehEscola() ? \App\adms\Models\historicoEscola\AdmsHistVida::itinerariosDaTurma((int) $t['id']) : ['itinerarios' => []];
          if ($itTurma['itinerarios'] && $d['alunos']) : $podeIt = \App\adms\Controllers\HistoricoEscola::pode('itinerario-turma', 'historico-escola-vida'); ?>
        <div class="col-lg-6">
          <form method="post" action="<?= URLADM ?>historico-escola-vida/itinerario-turma/<?= (int) $t['id'] ?>" class="card" id="itinerariosTurma">
            <div class="card-header"><h3 class="card-title">Itinerário formativo dos alunos</h3></div>
            <div class="table-responsive"><table class="table table-vcenter card-table"><tbody>
              <?php foreach ($d['alunos'] as $a) : $atual = (int) ($itTurma['alunos'][(int) $a['id']] ?? 0); ?>
                <tr data-aluno="<?= (int) $a['usuario_id'] ?>"><td><?= $h($a['nome']) ?></td><td style="width: 55%"><select class="form-select form-select-sm vc-itinerario" name="itinerario[<?= (int) $a['id'] ?>]" aria-label="Itinerário de <?= $h($a['nome']) ?>"<?= $podeIt ? '' : ' disabled' ?>>
                  <option value="0">— sem itinerário —</option><?php foreach ($itTurma['itinerarios'] as $n => $nome) : ?><option value="<?= (int) $n ?>"<?= $atual === (int) $n ? ' selected' : '' ?>><?= (int) $n ?> — <?= $h($nome) ?></option><?php endforeach; ?></select></td></tr>
              <?php endforeach; ?>
            </tbody></table></div>
            <?php if ($podeIt) : ?><div class="card-footer"><button class="btn btn-primary" id="salvarItinerarios">Salvar</button> <span class="small text-secondary ms-2">O histórico escolar lê o itinerário daqui (os da matriz do histórico do ano).</span></div><?php endif; ?>
          </form>
        </div>
        <?php endif; ?>
        <?php if (!empty($pode['enturmar'])) : ?>
        <div class="col-lg-6">
          <div class="card" id="candidatosTurma">
            <div class="card-header"><h3 class="card-title">Enturmar alunos</h3></div>
            <?php if (!$d['candidatos']) : ?>
              <div class="card-body text-secondary" id="semCandidatos"><?= $livreTurma ? 'Nenhum aluno do curso sem turma em ' . (int) $t['ano'] . '. Matricule o aluno no curso antes (Alunos → o aluno → Matrículas).' : 'Nenhum aluno do segmento sem turma em ' . (int) $t['ano'] . '. Vincule o aluno ao segmento antes (Alunos → o aluno → Vínculo).' ?></div>
            <?php else : ?>
            <div class="card-body">
              <p class="text-secondary small">Os alunos matriculados <?= $livreTurma ? 'no curso' : 'na série' ?> e no ano desta turma e ainda sem turma. Cada um entra em todas as disciplinas da turma.</p>
              <div class="mb-2"><label class="form-check"><input type="checkbox" class="form-check-input" id="marcarTodos"> <span class="form-check-label">Marcar todos</span></label></div>
              <div class="vc-candidatos" style="max-height: 22rem; overflow:auto">
                <?php foreach ($d['candidatos'] as $c) : ?>
                  <label class="form-check"><input type="checkbox" class="form-check-input vc-candidato" value="<?= htmlspecialchars((string) ((int) $c['usuario_id']), ENT_QUOTES, 'UTF-8') ?>">
                    <span class="form-check-label"><?= $h($c['nome']) ?> <span class="text-secondary small"><?= \App\adms\Models\helper\AdmsTipoInstituicao::rotuloNumero() ?> <?= $h($c['rm']) ?><?= $c['serie_atual'] ? ' · ' . $h($c['serie_atual']) : '' ?><?= !empty($c['ano']) ? ' · ' . $h((int) $c['ano']) : '' ?></span></span></label>
                <?php endforeach; ?>
              </div>
              <input type="hidden" id="alunosMarcados" value="">
              <button type="button" class="btn btn-primary mt-3" id="enturmarMarcados" data-progresso="progressoTurma" data-fase="iniciar"><i class="fas fa-user-plus"></i> Enturmar os marcados</button>
            </div>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
</div>
<?php
$progressoId = 'progressoTurma';
$progressoTitulo = 'Enturmando na turma ' . $t['codtur'];
$progressoGrupos = ['Os alunos marcados, um a um' => 999];
$progressoUrl = URLADM . 'turmas/enturmar/' . (int) $t['id'];
$progressoFim = ['Ver a turma', URLADM . 'turmas/ver/' . (int) $t['id']];
$progressoCampos = ['alunos' => '#alunosMarcados'];
include 'app/adms/Views/include_tabler/_progresso.php';
?>
<script>
(function () {
  var todos = document.getElementById('marcarTodos');
  var campo = document.getElementById('alunosMarcados');
  function juntar() {
    if (!campo) { return; }
    campo.value = Array.prototype.map.call(document.querySelectorAll('.vc-candidato:checked'), function (c) { return c.value; }).join(',');
  }
  document.addEventListener('change', function (ev) {
    if (ev.target === todos) { document.querySelectorAll('.vc-candidato').forEach(function (c) { c.checked = todos.checked; }); }
    if (ev.target === todos || ev.target.classList.contains('vc-candidato')) { juntar(); }
  });
})();
</script>
