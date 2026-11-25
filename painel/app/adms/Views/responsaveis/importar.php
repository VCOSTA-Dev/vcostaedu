<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* A planilha de responsáveis: enviar, conferir linha a linha, aplicar (tudo ou nada). */
$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$c = $this->dados['conferencia'] ?? null;
$P = \App\adms\Models\escola\AdmsResponsaveisPlanilha::class;
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Importar responsáveis<?php $paginaTitulo = ob_get_clean(); $paginaPretitulo = 'Responsáveis';
  include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <div class="card mb-3" id="instrucoesPlanilha"><div class="card-body">
        <p>Uma linha por papel: o RM do aluno (a coluna "ra"), o ano letivo, o papel (<b>financeiro</b> ou <b>titular</b>) e a pessoa (nome, e-mail, celular, CPF e parentesco). Para trocar quem já está no papel, preencha a coluna <b>motivo</b>.</p>
        <p class="mb-2">O sistema confere todas as linhas antes de gravar. Se uma linha tiver problema, nada é gravado: corrija a planilha e envie de novo. A pessoa que já existe (o mesmo e-mail ou CPF) não é cadastrada de novo. O acesso acadêmico adicional não entra pela planilha: ele pede o motivo e se libera na tela do aluno. Até <?= $h($P::MAX_LINHAS) ?> linhas por arquivo.</p>
        <a href="<?= URLADM ?>responsaveis/modelo" class="btn btn-outline-secondary btn-sm" id="baixarModelo"><i class="fas fa-download"></i> Baixar o modelo</a>
      </div></div>
      <form method="post" action="<?= URLADM ?>responsaveis/importar" enctype="multipart/form-data" class="card mb-3" id="formPlanilhaResponsaveis"><div class="card-body d-flex flex-wrap gap-2 align-items-end">
        <div><label class="form-label" for="planilha">A planilha (CSV)</label><input type="file" name="planilha" id="planilha" class="form-control" accept=".csv,.txt" required></div>
        <input type="hidden" name="acao" value="conferir"><button type="submit" class="btn btn-primary" id="conferirPlanilha">Conferir</button>
      </div></form>
      <?php if ($c) : ?>
        <div class="card" id="conferenciaPlanilha" data-ok="<?= $c['ok'] ? '1' : '0' ?>"><div class="card-header"><h3 class="card-title">A conferência: <?= $h(count($c['linhas'])) ?> linha(s)<?= $c['ok'] ? ' — tudo certo' : ' — ' . $h(count(array_filter($c['linhas'], fn($l) => $l['erros']))) . ' com problema' ?></h3></div>
          <div class="table-responsive"><table class="table table-sm card-table"><thead><tr><th>Linha</th><th>Aluno</th><th>Ano</th><th>Papel</th><th>Pessoa</th><th>O que acontece</th></tr></thead><tbody>
          <?php foreach ($c['linhas'] as $l) : ?>
            <tr class="<?= $l['erros'] ? 'table-danger' : '' ?>" data-linha="<?= $h($l['numero']) ?>"><td><?= $h($l['numero']) ?></td><td><?= $h($l['aluno']['nome'] ?? $l['ra']) ?></td><td><?= $h($l['ano']) ?></td><td><?= $h($l['papel']) ?></td>
              <td><?= $h($l['nome']) ?><?= !empty($l['pessoaExiste']) ? ' <span class="badge bg-azure-lt">já cadastrada</span>' : '' ?></td>
              <td><?= $l['erros'] ? $h(ucfirst(implode('; ', $l['erros'])) . '.') : $h(['designar' => 'designa', 'trocar' => 'troca (com o motivo)', 'igual' => 'já está assim: nada muda'][$l['acao']] ?? '') ?></td></tr>
          <?php endforeach; ?>
          </tbody></table></div>
          <?php if ($c['ok']) : ?>
            <div class="card-footer"><form method="post" action="<?= URLADM ?>responsaveis/importar"><input type="hidden" name="acao" value="aplicar"><input type="hidden" name="lote" value="<?= $h($c['lote']) ?>"><button type="submit" class="btn btn-primary" id="aplicarPlanilha">Aplicar a planilha</button></form></div>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>
</div>
