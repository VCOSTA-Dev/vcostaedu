<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* O Moodle na Página Inicial do aluno (Onda 3, Etapa 8): só aparece com a conexão feita, o aluno no Moodle e a
   matrícula cursando; cada disciplina, só depois de enviada. Os botões levam ao servidor, que pede o link e redireciona. */
$moodle = \App\adms\Models\moodle\AdmsMoodlePortal::doAluno((int) ($_SESSION['academico_id'] ?? 0));
if ($moodle['entrar']) : ?>
  <div class="row justify-content-center mt-3">
    <div class="col-12 col-sm-8 col-md-6">
      <div class="card shadow" id="cartaoMoodle">
        <div class="card-body text-center">
          <i class="fas fa-graduation-cap fa-3x mb-3"></i>
          <h5>Moodle</h5>
          <a href="<?= URLACAD ?>moodle/entrar" class="btn btn-primary w-100 mb-2" id="entrarMoodle" rel="noopener"><i class="fas fa-right-to-bracket"></i> Entrar no Moodle</a>
          <?php if ($moodle['disciplinas']) : ?>
            <div class="list-group list-group-flush text-start mt-2" id="disciplinasMoodle">
              <?php foreach ($moodle['disciplinas'] as $dm) : ?>
                <div class="list-group-item d-flex align-items-center gap-2 px-0">
                  <span class="flex-fill"><?= htmlspecialchars((string) $dm['materia'], ENT_QUOTES, 'UTF-8') ?> <span class="text-secondary small"><?= htmlspecialchars((string) $dm['codtur'], ENT_QUOTES, 'UTF-8') ?></span></span>
                  <a href="<?= URLACAD ?>moodle/abrir/<?= htmlspecialchars((string) ((int) $dm['id']), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-primary vc-abrir-moodle" data-oferta="<?= htmlspecialchars((string) ((int) $dm['id']), ENT_QUOTES, 'UTF-8') ?>">Abrir no Moodle</a>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
<?php endif;
