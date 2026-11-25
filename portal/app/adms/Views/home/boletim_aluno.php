<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Pagina nao encontrada!");
}
/* E4: boletim POR CURSO — um bloco por matrícula no curso, uma etapa, Aprovado/Reprovado. Dados de AdmsBoletimCurso
   (motor único): os mesmos números do boletim do painel e do PDF. E8c (A11): o mesmo desenho do painel (faixa do
   aluno, tabela compacta, seletor de curso/ano) e o botão Imprimir. */
use App\adms\Models\notas\AdmsBoletimCurso as Boletim;

$b = $this->dados['boletimCurso'] ?? [];
$h = fn($t) => htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
$rg = $b['regras'] ?? ['mediaMinima' => '7.00', 'frequenciaMinima' => '75.00'];
/* E5: parâmetro "Mostrar média parcial ao aluno" (Configurações → Instituição). A final aparece sempre. */
$mostraParcial = \App\adms\Models\helper\AdmsParametro::mostrarMediaParcial();
$f = $this->dados['filtroBoletim'] ?? ['curso' => 0, 'ano' => 0];
?>
<div class="vc-pagina">
  <?php ob_start(); ?><i class="fas fa-book-reader me-2 text-info"></i>Boletim <?= $f['ano'] ? '<small class="text-muted">' . (int) $f['ano'] . '</small>' : '' ?><?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (empty($b['aluno'])) : ?>
        <div class="alert alert-info">Não foi possível identificar o aluno para consultar o boletim.</div>
      <?php else : ?>
        <div class="barra-boletim">
          <?= Boletim::seletor($this->dados['opcoesBoletim'] ?? ['cursos' => [], 'anos' => []], $f['curso'], $f['ano'], URLACAD . 'home/boletim-aluno', ['ano' => (int) ($this->dados['ano'] ?? date('Y'))]) ?>
          <button type="button" class="btn btn-sm btn-outline-dark" onclick="window.print()" title="Imprimir o boletim" id="imprimirBoletim"><i class="fas fa-print"></i> Imprimir</button>
        </div>
        <?php if (empty($b['blocos'])) : ?>
          <?= Boletim::faixaAluno($b['aluno'], null) ?>
          <div class="alert alert-info" id="semBoletim">Você ainda não está em nenhuma disciplina<?= htmlspecialchars((string) ($f['curso'] || $f['ano'] ? ' com este filtro' : ''), ENT_QUOTES, 'UTF-8') ?>.</div>
        <?php endif; ?>
        <?php foreach ($b['blocos'] ?? [] as $bl) : ?>
          <div class="boletimCurso mb-3">
            <?= Boletim::faixaAluno($b['aluno'], $bl['matricula']) ?>
            <div class="card shadow-sm mb-0">
              <div class="card-body table-responsive p-0">
                <?= Boletim::tabela($bl, $rg, ['ocultarParcial' => !$mostraParcial]) ?>
              </div>
            </div>
            <?= Boletim::comoFoiCalculado($bl, !$mostraParcial) /* 1.13.0 (D-400) */ ?>
          </div>
        <?php endforeach; ?>
        <?= Boletim::regrasTexto($rg, $mostraParcial, $b['blocos'] ?? []) /* E5: os mínimos do curso */ ?>
      <?php endif; ?>
    </div>
  </section>
</div>
