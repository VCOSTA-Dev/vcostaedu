<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}

$btnDashboard = "col-12 col-sm-8 col-md-6";
$alunos = $this->dados['alunos'] ?? [];
$ano = (int)($this->dados['ano'] ?? date('Y'));
$aluno = $alunos[0] ?? null;
$alunoId = (int)($aluno['aluno_id'] ?? 0);
$academico = $this->dados['academico'] ?? [
  'boletim' => '#',
  'notas' => '#',
  'horario' => '#'
];
/* Fechamento (D-106): no curso livre, a Página Inicial é o Moodle — sem o ano, sem o cartão Acadêmico (o boletim, as notas
   e o horário não existem nesse tipo) e sem o cartão do financeiro */
$livreHome = \App\adms\Models\helper\AdmsTipoPortal::ehLivre();
?>

<div class="vc-pagina">
  <?php /* VCOSTAEDU (Etapa 6): o cabeçalho da origem não tinha título (a marca "Portal" ficava na barra de
     cima); aqui o título é o da Página Inicial e o ano e o aluno vão para as ações do page-header. */ ?>
  <?php ob_start(); ?><?php if ($livreHome) : ?><div class="d-flex align-items-center justify-content-end"><strong class="me-2">Aluno:</strong><span class="text-muted"><?= htmlspecialchars((string) ($_SESSION['academico_nome'] ?? '')) ?></span></div><?php else : ?><div class="d-flex flex-column flex-sm-row justify-content-sm-end text-start align-items-sm-center">
            <div class="d-flex align-items-center justify-content-end mb-2 mb-sm-0">
              <strong class="me-2">Ano:</strong>
              <select id="selAno" class="form-select form-select-sm" style="width:6.5rem;" onchange="mudouContextoAluno()">
                <?php
                  $anoAtual = (int)date('Y');
                  for($i = 0; $i < 3; $i++){
                    $anoOpcao = $anoAtual - $i;
                    $selected = ($anoOpcao == $ano) ? 'selected' : '';
                    echo "<option value='{$anoOpcao}' {$selected}>{$anoOpcao}</option>";
                  }
                ?>
              </select>
            </div>
            <span class="d-none d-sm-inline mx-2">|</span>
            <div class="d-flex align-items-center justify-content-end">
              <strong class="me-2">Aluno:</strong>
              <?php if(!empty($aluno)): ?>
                <span class="text-muted"><?php echo htmlspecialchars(($aluno['nome_aluno'] ?? '') . ' - ' . ($aluno['ensino_abrev'] ?? '')); ?></span>
              <?php else: ?>
                <span class="text-muted">Nenhum aluno encontrado</span>
              <?php endif; ?>
            </div>
          </div><?php endif; ?>
<?php $paginaAcoesHtml = ob_get_clean(); $paginaTitulo = 'Página Inicial'; include 'app/adms/Views/include_tabler/_page_header.php'; ?>

  <section class="page-body">
    <div class="container-xl">
      <?php /* E7/E8b (D-219, D-253): aviso de novidades ou boas-vindas, a cada abertura, até o Entendi ou o X */
      $avisoNov = \App\adms\Models\helper\AdmsNovidades::avisoPendente(\App\adms\Models\helper\AdmsCentralGuias::perfil());
      $rotasNov = ['indice' => URLACAD . 'home/ajuda', 'guia' => URLACAD . 'home/guia/', 'novidades' => URLACAD . 'home/novidades', 'visto' => URLACAD . 'home/novidades-visto'];
      include __DIR__ . '/../novidades/aviso.php'; ?>
      <?php
      if (isset($_SESSION['msg'])) {
        echo $_SESSION['msg'];
        unset($_SESSION['msg']);
      }
      ?>
      <?php if (!$livreHome && !empty($this->dados['cartaoFinanceiro'])) { /* Onda 8, financeiro, Parte 3 (D-98): o aluno com algum título */
        $vencidosF = (int) $this->dados['cartaoFinanceiro']['vencidos']; $urlConsultarF = (string) $this->dados['cartaoFinanceiro']['url'];
        include 'app/adms/Views/include_tabler/_cartao_financeiro.php';
      } ?>
      <?php if (!$livreHome) : ?>
      <div class="row justify-content-center">
        <div class="<?= htmlspecialchars((string) ($btnDashboard), ENT_QUOTES, 'UTF-8') ?> mb-3 mb-md-0">
          <div class="card shadow h-100 dashboard-card">
            <div class="card-body text-center">
              <i class="fas fa-user-graduate fa-3x mb-3"></i>
              <h5>Acadêmico</h5>
              <a href="<?= htmlspecialchars((string) ($academico['boletim']), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-dark w-100 mb-2" onclick="this.style.pointerEvents='none';this.textContent='Processando...'">Boletim</a>
              <a href="<?= htmlspecialchars((string) ($academico['notas']), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-dark w-100" onclick="this.style.pointerEvents='none';this.textContent='Processando...'">Notas das Atividades</a>
              <a href="<?= htmlspecialchars((string) ($academico['horario']), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-dark w-100" onclick="this.style.pointerEvents='none';this.textContent='Processando...'"><i class="far fa-clock"></i> Horário</a>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>
      <?php if ($livreHome) : /* D-121 (a correção do ensaio, B2.8): no curso livre, o curso em que o aluno está */
        $cursosLivre = \App\adms\Models\moodle\AdmsMoodlePortal::cursosDoAluno((int) ($_SESSION['academico_id'] ?? 0)); ?>
      <div class="row justify-content-center">
        <div class="col-12 col-sm-8 col-md-6">
          <div class="card shadow" id="cartaoCursoLivre">
            <div class="card-body text-center">
              <i class="fas fa-book-open fa-3x mb-3" aria-hidden="true"></i>
              <h5>O seu curso</h5>
              <?php if (!$cursosLivre) : ?>
                <p class="text-secondary mb-0" id="semCursoLivre">Você ainda não está matriculado num curso. Procure a secretaria.</p>
              <?php else : foreach ($cursosLivre as $cl) : ?>
                <p class="mb-1 vc-curso-livre"><strong><?= htmlspecialchars($cl['curso'], ENT_QUOTES, 'UTF-8') ?></strong><?php if ($cl['turma'] !== '') : ?><br><span class="text-secondary small">Turma <?= htmlspecialchars($cl['turma'], ENT_QUOTES, 'UTF-8') ?><?= $cl['ano'] ? ' · ' . (int) $cl['ano'] : '' ?></span><?php endif; ?></p>
              <?php endforeach; endif; ?>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>
      <?php include __DIR__ . '/_moodle.php'; /* Onda 3, Etapa 8: Entrar no Moodle e Abrir no Moodle */ ?>
    </div>
  </section>
</div>

<script>
function mudouContextoAluno() {
  const ano = document.getElementById('selAno').value;
  const url = new URL(window.location);
  url.searchParams.set('ano', ano);
  url.searchParams.set('aluno_id', <?php echo $alunoId; ?>);
  window.location.href = url.toString();
}
</script>
