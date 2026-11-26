<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/*
 * A PÁGINA INICIAL DO PROFESSOR NO CURSO LIVRE (a correção do ensaio, B2.7; D-121): as disciplinas dele (as abertas) e
 * o botão "Entrar no Moodle" (o mesmo mecanismo do aluno: AdmsMoodleProfessor). O botão só aparece quando dá para
 * entrar (a conexão feita e o professor no Moodle); sem isso, a frase diz o porquê. Só no curso livre e só para o papel
 * de professor.
 */
if (!\App\adms\Models\helper\AdmsNivel::ehPapel('professor')) {
  return;
}
/* D-126 (1.102.1, C5): na escola e no ensino superior, o mesmo "Entrar no Moodle" (as disciplinas já estão em "O meu dia"
   e em Minhas Disciplinas): um cartão só do Moodle, e só quando dá para entrar */
if (!\App\adms\Models\helper\AdmsTipoInstituicao::ehLivre()) {
  $moodleLp = \App\adms\Models\moodle\AdmsMoodleProfessor::doProfessor((int) ($_SESSION['colUsuario_id'] ?? 0));
  if (!$moodleLp['entrar']) {
    return;
  }
  $hLp = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8'); ?>
<div class="row justify-content-center mt-2 mb-3">
  <div class="col-12 col-lg-8">
    <div class="card shadow" id="cartaoMoodleProfessor">
      <div class="card-body">
        <a href="<?= URLADM ?>moodle-professor/entrar" class="btn btn-primary w-100 mb-2" id="entrarMoodleProfessor" rel="noopener"><i class="fas fa-right-to-bracket me-1" aria-hidden="true"></i>Entrar no Moodle</a>
        <?php if ($moodleLp['disciplinas']) : ?>
          <div class="list-group list-group-flush" id="disciplinasMoodleProfessor">
            <?php foreach ($moodleLp['disciplinas'] as $dLp) : ?>
              <div class="list-group-item d-flex align-items-center gap-2 px-0" data-oferta="<?= $hLp((int) $dLp['id']) ?>">
                <span class="flex-fill"><?= $hLp($dLp['materia']) ?> <span class="text-secondary small"><?= $hLp($dLp['codtur'] ?? '') ?></span></span>
                <a href="<?= $hLp(URLADM . 'moodle-professor/abrir/' . (int) $dLp['id']) ?>" class="btn btn-sm btn-outline-primary vc-abrir-moodle-prof" rel="noopener">Abrir no Moodle</a>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php
  return;
}
$hLp = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$uLp = (int) ($_SESSION['colUsuario_id'] ?? 0);
$discLp = \App\adms\Models\moodle\AdmsMoodleProfessor::disciplinas($uLp);
$moodleLp = \App\adms\Models\moodle\AdmsMoodleProfessor::doProfessor($uLp);
$noMoodleLp = array_map('intval', array_column($moodleLp['disciplinas'], 'id'));
?>
<div class="row justify-content-center mt-2 mb-3">
  <div class="col-12 col-lg-8">
    <div class="card shadow" id="cartaoProfessorLivre">
      <div class="card-header"><h3 class="card-title"><i class="fas fa-chalkboard-user me-2" aria-hidden="true"></i>As suas disciplinas</h3></div>
      <div class="card-body">
        <?php if ($moodleLp['entrar']) : ?>
          <a href="<?= URLADM ?>moodle-professor/entrar" class="btn btn-primary w-100 mb-3" id="entrarMoodleProfessor" rel="noopener"><i class="fas fa-right-to-bracket me-1" aria-hidden="true"></i>Entrar no Moodle</a>
        <?php else : ?>
          <p class="text-secondary small mb-3" id="moodleProfessorIndisponivel">O botão "Entrar no Moodle" aparece aqui quando o sistema estiver conectado ao Moodle e as suas disciplinas tiverem sido enviadas. Se demorar, procure a secretaria.</p>
        <?php endif; ?>
        <?php if (!$discLp) : ?>
          <p class="mb-0" id="semDisciplinasLivre">Você ainda não tem disciplina aberta.</p>
        <?php else : ?>
          <div class="list-group list-group-flush" id="disciplinasProfessorLivre">
            <?php foreach ($discLp as $dLp) : $idLp = (int) $dLp['id']; ?>
              <div class="list-group-item d-flex align-items-center gap-2 px-0" data-oferta="<?= $hLp($idLp) ?>">
                <span class="flex-fill"><a href="<?= $hLp(URLADM . 'disciplinas/visualizar/' . $idLp) ?>"><?= $hLp($dLp['materia']) ?></a>
                  <span class="text-secondary small"><?= $hLp(trim(($dLp['curso'] ?? '') . ' · ' . ($dLp['codtur'] ?? ''), ' ·')) ?></span></span>
                <?php if (in_array($idLp, $noMoodleLp, true)) : ?>
                  <a href="<?= $hLp(URLADM . 'moodle-professor/abrir/' . $idLp) ?>" class="btn btn-sm btn-outline-primary vc-abrir-moodle-prof" rel="noopener">Abrir no Moodle</a>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
