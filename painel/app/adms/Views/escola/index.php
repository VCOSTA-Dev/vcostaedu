<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* A estrutura da escola: os segmentos, as séries e os componentes curriculares (só leitura). */
$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$segmentos = $this->dados['segmentos'] ?? [];
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Estrutura da escola<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <p class="text-secondary" id="explicacaoEstrutura">Cada <strong>segmento</strong> (o Ensino Fundamental, o Ensino Médio) tem uma matriz; cada <strong>série</strong> da matriz tem as suas <strong>disciplinas</strong>, com a carga horária do ano. Os segmentos se cadastram em <a href="<?= URLADM ?>cursos/listar">Segmentos</a>, e as séries e as disciplinas, na matriz de cada um. Vale qualquer segmento e qualquer série, do 1º ano do Ensino Fundamental em diante (os anos iniciais, os finais, o Médio). A avaliação é por nota, de 0 a 10, em todas as séries.</p>
      <?php if (!$segmentos) : ?>
        <div class="alert alert-info" id="semSegmentos">Ainda não há segmento cadastrado. Comece em <a href="<?= URLADM ?>cursos/listar">Segmentos</a>.</div>
      <?php endif; ?>
      <?php foreach ($segmentos as $seg) : ?>
        <div class="card mb-3 vc-segmento" data-segmento="<?= htmlspecialchars((string) ((int) $seg['id']), ENT_QUOTES, 'UTF-8') ?>">
          <div class="card-header"><div><h3 class="card-title"><?= $h($seg['nome']) ?></h3><?php if ($seg['descricao'] !== '') : ?><div class="text-secondary small"><?= $h($seg['descricao']) ?></div><?php endif; ?></div></div>
          <div class="card-body">
            <?php if (!$seg['matrizes']) : ?>
              <p class="text-secondary mb-0">Sem matriz. <a href="<?= URLADM ?>matriz/listar">Cadastre a matriz do segmento</a>.</p>
            <?php endif; ?>
            <?php foreach ($seg['matrizes'] as $mat) : ?>
              <div class="text-secondary small mb-2">Matriz <?= $h($mat['versao']) ?></div>
              <?php if (!$mat['series']) : ?><p class="text-secondary">Sem séries.</p><?php endif; ?>
              <div class="row row-cards">
                <?php foreach ($mat['series'] as $s) : ?>
                  <div class="col-md-6 col-lg-4">
                    <div class="card card-sm vc-serie" data-serie="<?= htmlspecialchars((string) ((int) $s['id']), ENT_QUOTES, 'UTF-8') ?>">
                      <div class="card-body">
                        <div class="fw-medium"><?= $h($s['nome']) ?></div>
                        <div class="text-secondary small mb-2"><?= htmlspecialchars((string) (count($s['componentes'])), ENT_QUOTES, 'UTF-8') ?> disciplina(s) · <?= $h(rtrim(rtrim(number_format($s['horas'], 2, ',', '.'), '0'), ',')) ?> h no ano</div>
                        <?php if ($s['componentes']) : ?>
                          <ul class="list-unstyled small mb-0">
                            <?php foreach ($s['componentes'] as $c) : ?><li><?= $h($c['nome']) ?> <span class="text-secondary">· <?= $h($c['ch']) ?> h</span></li><?php endforeach; ?>
                          </ul>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
</div>
