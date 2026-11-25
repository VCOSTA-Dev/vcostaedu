<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Pagina nao encontrada!");
}

$alunos = $this->dados['alunos'] ?? [];
$alunoId = (int)($this->dados['aluno_id'] ?? 0);
$ano = (int)($this->dados['ano'] ?? date('Y'));
$notasAtividades = $this->dados['notasAtividades'] ?? [];

$aluno = $notasAtividades['aluno'] ?? null;
$disciplinas = $notasAtividades['disciplinas'] ?? [];
$disciplinasSelecionadas = $notasAtividades['disciplinasSelecionadas'] ?? [];
$filtroTodas = $notasAtividades['filtroTodas'] ?? true;
$notasPorDisciplina = $notasAtividades['notasPorDisciplina'] ?? [];
$mensagem = $notasAtividades['mensagem'] ?? null;

$rm = $aluno['rm'] ?? '-';
$nomeAluno = $aluno['nome_aluno'] ?? 'Aluno';
$turma = $disciplinas[0]['turma'] ?? '-';

$acaoFiltro = URLACAD . 'home/notas-atividades?ano=' . $ano . '&aluno_id=' . $alunoId;
$voltarHome = URLACAD . 'home/index?ano=' . $ano . '&aluno_id=' . $alunoId;
?>

<style>
  .notas-header-wrap {
    position: relative;
  }

  .notas-home-action-mobile {
    position: absolute;
    top: 0;
    right: 15px;
    z-index: 2;
  }

  @media (min-width: 576px) {
    .notas-home-action-mobile {
      display: none;
    }
  }

  .titulo-ano-letivo {
    line-height: 1.15;
  }

  .titulo-ano-letivo-sub {
    display: inline-block;
    margin-left: .35rem;
    font-size: .72em;
    color: var(--tblr-secondary);
    font-weight: 400;
  }

  @media (max-width: 575.98px) {
    .titulo-ano-letivo-sub {
      display: block;
      margin-left: 2rem;
      margin-top: .1rem;
      font-size: .58em;
      color: #8a94a0;
    }
  }

  .select2-container--default .select2-selection--multiple .select2-selection__choice {
    background-color: #0069d9 !important;
    border-color: #2e6da4 !important;
    color: #fff !important;
  }

  .select2-container--default .select2-selection--multiple {
    min-height: 38px;
  }
</style>

<div class="vc-pagina">
  <?php ob_start(); ?><i class="fas fa-clipboard-list me-2 text-teal"></i>Notas das Atividades<span class="titulo-ano-letivo-sub">Ano Letivo - <b><?= htmlspecialchars((string) ($ano), ENT_QUOTES, 'UTF-8') ?></b></span><?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>

  <section class="page-body">
    <div class="container-xl">
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header text-white d-flex justify-content-between align-items-center" style="background-color:#00796B;">
              <h3 class="card-title mb-0">RM: <?php echo htmlspecialchars((string)$rm); ?> - Aluno(a): <?php echo htmlspecialchars($nomeAluno); ?> - Turma: <?php echo htmlspecialchars((string)$turma); ?></h3>
            </div>
            <div class="card-body">
              <?php if(!empty($mensagem)): ?>
                <div class="alert alert-warning"><?php echo htmlspecialchars($mensagem); ?></div>
              <?php endif; ?>

              <div class="card mb-3">
                <div class="card-header bg-teal text-white">
                  <strong><i class="fas fa-filter"></i> Filtro</strong>
                </div>
                <div class="card-body">
                  <form action="<?php echo $acaoFiltro; ?>" method="post">
                    <div class="mb-3">
                      <label class="form-label" for="disciplinas">Disciplinas</label>
                      <select name="disciplinas[]" id="disciplinas" class="form-select select2" multiple>
                        <option value="all" <?php echo $filtroTodas ? 'selected' : ''; ?>>Todas as Disciplinas</option>
                        <?php foreach($disciplinas as $disciplina): ?>
                          <?php $disciplinaId = (int)($disciplina['rm_prof_mat_id'] ?? 0); ?>
                          <option value="<?php echo $disciplinaId; ?>" <?php echo (!$filtroTodas && in_array($disciplinaId, $disciplinasSelecionadas, true)) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($disciplina['materia'] ?? 'Disciplina'); ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Pesquisar</button>
                    <a href="<?php echo $acaoFiltro; ?>" class="btn btn-secondary btn-sm">Limpar</a>
                  </form>
                </div>
              </div>

              <?php if(empty($notasPorDisciplina)): ?>
                <div class="alert alert-info mb-0">Nenhuma nota de atividade encontrada para os filtros selecionados.</div>
              <?php else: ?>
                <div id="accordionNotas">
                  <?php foreach($notasPorDisciplina as $disciplina): ?>
                    <div class="card mb-2">
                      <div class="card-header py-2">
                        <h5 class="mb-0"><?php echo htmlspecialchars($disciplina['materia'] ?? 'Disciplina'); ?></h5>
                      </div>
                      <div class="card-body py-2">
                        <?php foreach(($disciplina['bimestres'] ?? []) as $bimestre => $listaNotas): ?>
                          <div class="card card-sm vc-indicador bg-light mb-2">
                            <div class="vc-indicador-conteudo d-block">
                              <strong><?php echo htmlspecialchars((string)$bimestre); ?></strong>
                              <ul class="mb-0 mt-2 ps-3">
                                <?php foreach($listaNotas as $nota): ?>
                                  <li>
                                    <?php echo htmlspecialchars($nota['nomeAtividade'] ?? 'Atividade'); ?>:
                                    <?php
                                      $valorNota = $nota['nota'] ?? null;
                                      echo ($valorNota !== null && $valorNota !== '') ? str_replace('.', ',', (string) $valorNota) : '-';
                                    ?>
                                  </li>
                                <?php endforeach; ?>
                              </ul>
                            </div>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<script>
function mudouContextoNotas() {
  const ano = document.getElementById('selAno').value;

  const url = new URL('<?= URLACAD ?>home/notas-atividades');
  url.searchParams.set('ano', ano);

  window.location.href = url.toString();
}

/* VCOSTAEDU (Etapa 6): o jQuery chega no fim da página, depois deste script; na origem o "$" ainda não existia
   aqui (erro no console e o Select2 nunca ligava). Espera o DOMContentLoaded, que vem depois dos scripts do fim. */
document.addEventListener('DOMContentLoaded', function () {
  if (window.jQuery && $.fn.select2) {
    $('#disciplinas').select2({
      placeholder: 'Selecione as disciplinas',
      width: '100%',
      closeOnSelect: false
    });

    $('#disciplinas').on('select2:select', function(e) {
      const val = e.params.data.id;
      const $sel = $(this);

      // Adia para o próximo tick, após o Select2 terminar de processar a seleção
      setTimeout(function() {
        const current = $sel.val() || [];

        if (val === 'all') {
          // Selecionou "Todas" — mantém só 'all', remove individuais
          $sel.val(['all']).trigger('change');
        } else {
          // Selecionou disciplina individual — remove 'all' se ainda estiver
          const semTodas = current.filter(v => v !== 'all');
          if (semTodas.length !== current.length) {
            $sel.val(semTodas).trigger('change');
          }
        }
      }, 0);
    });
  }
});
</script>
