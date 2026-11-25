<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Pagina nao encontrada!");
}

$alunos     = $this->dados['alunos'] ?? [];
$alunoId    = (int)($this->dados['aluno_id'] ?? 0);
$ano        = (int)($this->dados['ano'] ?? date('Y'));
$horario    = $this->dados['horarioAluno'] ?? [];
$aluno      = $horario['aluno'] ?? null;
$disciplinas = $horario['disciplinas'] ?? [];
$mensagem   = $horario['mensagem'] ?? null;

$nomeAluno  = $aluno['nome_aluno'] ?? 'Aluno';
$rm         = $aluno['rm'] ?? '-';

$voltarHome = URLACAD . 'home/index?ano=' . $ano . '&aluno_id=' . $alunoId;

$coresDias = [
  'Segunda' => 'primary',
  'Terça'   => 'success',
  'Quarta'  => 'warning',
  'Quinta'  => 'secondary',
  'Sexta'   => 'danger',
  'Sábado'  => 'dark',
];

$ordemDias = [
  2 => 'Segunda',
  3 => 'Terça',
  4 => 'Quarta',
  5 => 'Quinta',
  6 => 'Sexta',
  7 => 'Sábado'
];

$agendaPorDia = [
  2 => [],
  3 => [],
  4 => [],
  5 => [],
  6 => [],
  7 => []
];

foreach($disciplinas as $aulaItem){
  $diaId = (int)($aulaItem['dia_id'] ?? 0);
  if(isset($agendaPorDia[$diaId])){
    $agendaPorDia[$diaId][] = $aulaItem;
  }
}
?>

<style>
  .horario-header-wrap {
    position: relative;
  }

  .horario-home-action-mobile {
    position: absolute;
    top: 0;
    right: 15px;
    z-index: 2;
  }

  @media (min-width: 576px) {
    .horario-home-action-mobile {
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
</style>

<div class="vc-pagina">
  <?php ob_start(); ?><i class="far fa-clock me-2 text-info"></i>Horário de Aulas<span class="titulo-ano-letivo-sub">Ano Letivo - <b><?= htmlspecialchars((string) ($ano), ENT_QUOTES, 'UTF-8') ?></b></span><?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>

  <section class="page-body">
    <div class="container-xl">
      <?php if(!empty($mensagem)): ?>
        <div class="alert alert-warning"><i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($mensagem); ?></div>
      <?php endif; ?>

      <?php /* Onda 8, correção (D-71): sem horário, a mensagem honesta (antes: "não é o ano atual", que aparecia sempre que a lista vinha vazia) */ ?>
      <?php if(empty($disciplinas)): ?>
        <div class="alert alert-info" id="semHorario">
          <i class="fas fa-info-circle"></i> Ainda não há horário cadastrado para <strong><?php echo htmlspecialchars($nomeAluno); ?></strong> em <?php echo (int) $ano; ?>. Quando a escola montar o horário da turma, ele aparece aqui.
        </div>
      <?php else: ?>
        <div class="row mb-3">
          <div class="col-12">
            <div class="card shadow-sm border-0">
              <div class="card-header text-white" style="background-color:#009F8B;">
                <h5 class="mb-0"><i class="fas fa-user-graduate me-2"></i>
                  RM: <?php echo htmlspecialchars((string)$rm); ?> — <?php echo htmlspecialchars($nomeAluno); ?>
                  <span class="badge bg-light text-dark text-dark ms-2"><?php echo count($disciplinas); ?> aula<?php echo count($disciplinas) !== 1 ? 's' : ''; ?></span>
                </h5>
              </div>
            </div>
          </div>
        </div>
        <?php /* Onda 8, correção (D-71, D-83): a semana em cartões por dia, com o filtro (o mesmo do portal do responsável) */
        $semanaAulas = $disciplinas; include 'app/adms/Views/include_tabler/_semana_horario.php'; ?>

      <?php endif; ?>

    </div>
  </section>
</div>

