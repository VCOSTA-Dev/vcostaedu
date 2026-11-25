<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
$v = $this->dados['valores'] ?? [];
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Alerta de risco<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <div class="card shadow">
        <form method="POST" action="<?= URLADM ?>relatorios/parametros" novalidate>
          <div class="card-body">
            <p class="text-muted">Os limites dos sinais do relatório <a href="<?= URLADM ?>relatorios/alunos-risco">Alunos em risco</a>. Não há nota de risco: o relatório lista quais sinais dispararam e por quê. Os sinais "reprovação anterior" e "sem enturmação" não têm limite.</p>
            <div class="row g-2">
              <div class="mb-3 col-md-4">
                <label class="form-label" for="risco_faltas_margem">Frequência: faltas que ainda pode ter</label>
                <input type="number" min="0" max="50" class="form-control" id="risco_faltas_margem" name="risco_faltas_margem" value="<?= htmlspecialchars((string) ((int) ($v['faltas_margem'] ?? 2)), ENT_QUOTES, 'UTF-8') ?>">
                <small class="form-text text-muted">O sinal dispara quando o aluno, numa oferta aberta, pode ter esta quantidade de faltas ou menos (ou já passou do mínimo).</small>
              </div>
              <div class="mb-3 col-md-4">
                <label class="form-label" for="risco_min_notas">Média: notas lançadas no mínimo</label>
                <input type="number" min="1" max="50" class="form-control" id="risco_min_notas" name="risco_min_notas" value="<?= htmlspecialchars((string) ((int) ($v['min_notas'] ?? 1)), ENT_QUOTES, 'UTF-8') ?>">
                <small class="form-text text-muted">A média parcial abaixo da mínima só vira sinal com ao menos esta quantidade de notas lançadas na oferta.</small>
              </div>
              <div class="mb-3 col-md-4">
                <label class="form-label" for="risco_sem_nota_pct">Atividades sem nota (%)</label>
                <input type="number" min="1" max="100" class="form-control" id="risco_sem_nota_pct" name="risco_sem_nota_pct" value="<?= htmlspecialchars((string) ((int) ($v['sem_nota_pct'] ?? 50)), ENT_QUOTES, 'UTF-8') ?>">
                <small class="form-text text-muted">O sinal dispara quando esta porcentagem ou mais das atividades do aluno nas ofertas abertas está sem nota.</small>
              </div>
            </div>
          </div>
          <div class="card-footer">
            <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-save"></i> Salvar</button>
            <a href="<?= URLADM ?>relatorios/listar" class="btn btn-outline-secondary btn-sm">Voltar</a>
          </div>
        </form>
      </div>
    </div>
  </section>
</div>
