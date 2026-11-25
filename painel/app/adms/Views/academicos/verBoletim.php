<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* E4: boletim POR CURSO (uma etapa, Aprovado/Reprovado). Dados de AdmsBoletimCurso, que calcula tudo pelo motor
   único — os mesmos números do PDF e do portal. E8c (A11): faixa do aluno, tabela compacta, seletor de curso/ano,
   Imprimir e PDF; o desenho da tabela é o MESMO do PDF e do portal (AdmsBoletimCurso::tabela). */
use App\adms\Models\notas\AdmsBoletimCurso as Boletim;

$b = $this->dados['boletim'] ?? [];
$h = fn($t) => htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
$rg = $b['regras'] ?? ['mediaMinima' => '7.00', 'frequenciaMinima' => '75.00'];
$f = $this->dados['filtro'] ?? ['curso' => 0, 'ano' => 0];
$rm = $h($b['aluno']['rm'] ?? '');
$qs = http_build_query(array_filter(['filtro_curso' => $f['curso'], 'filtro_ano' => $f['ano']]));
?>
<div class="vc-pagina">
  <?php ob_start(); ?><a href="<?= URLADM . 'home/index' ?>" class="btn btn-outline-primary rounded-circle no-print" title="Voltar ao início"><i class="fas fa-arrow-left"></i></a>
            Boletim <?= $f['ano'] ? '<small class="text-muted">' . (int) $f['ano'] . '</small>' : '' ?><?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (empty($b['aluno'])) : ?>
        <div class="alert alert-danger">Aluno não encontrado.</div>
      <?php else : ?>
        <div class="barra-boletim">
          <?= Boletim::seletor($this->dados['opcoes'] ?? ['cursos' => [], 'anos' => []], $f['curso'], $f['ano'], URLADM . 'academicos/boletim/' . $rm) ?>
          <div class="acoes-linha">
            <button type="button" class="btn btn-sm btn-outline-dark" onclick="window.print()" title="Imprimir este boletim"><i class="fas fa-print"></i> Imprimir</button>
            <a href="<?= URLADM . 'academicos/imprimir-boletim/' . $rm . ($qs ? '?' . $qs : '') ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-danger" title="Boletim em PDF (com o mesmo filtro)"><i class="far fa-file-pdf"></i> PDF</a>
          </div>
        </div>
        <?php if (empty($b['blocos'])) : ?>
          <?= Boletim::faixaAluno($b['aluno'], null) ?>
          <div class="alert alert-info" id="semBoletim">O aluno não tem matrícula em curso nem disciplina enturmada<?= htmlspecialchars((string) ($f['curso'] || $f['ano'] ? ' com este filtro' : ''), ENT_QUOTES, 'UTF-8') ?>.</div>
        <?php endif; ?>
        <?php foreach ($b['blocos'] ?? [] as $bl) : ?>
          <div class="boletimCurso mb-3">
            <?= Boletim::faixaAluno($b['aluno'], $bl['matricula']) ?>
            <div class="card shadow-sm mb-0">
              <div class="card-body table-responsive p-0">
                <?= Boletim::tabela($bl, $rg, ['ano' => true]) ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
        <?= Boletim::regrasTexto($rg, true, $b['blocos'] ?? []) /* E5: os mínimos do curso */ ?>
      <?php endif; ?>
    </div>
  </section>
</div>
