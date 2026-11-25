<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* Os planos de pagamento (Onda 8, D-98): a lista e o formulário (cadastrar ou editar). */
use App\adms\Models\financeiro\AdmsFinanceiro as F;

$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$d = $this->dados;
$ed = $d['editando'];
$f = $d['form'] ?? ($ed ? ['id' => $ed['id'], 'nome' => $ed['nome'], 'parcelas' => $ed['parcelas'], 'valor' => F::brl((int) $ed['valor_centavos']), 'dia_vencimento' => $ed['dia_vencimento'], 'mes_primeira' => $ed['mes_primeira']] : []);
$meses = [1 => 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Planos de pagamento<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <div class="row row-cards">
        <div class="col-lg-8">
          <div class="card">
            <div class="table-responsive">
              <table class="table table-vcenter card-table" id="tabelaPlanos">
                <thead><tr><th>Plano</th><th class="text-end">Parcelas</th><th class="text-end">Valor da parcela</th><th>Vencimento</th><th>Primeira</th><th></th></tr></thead>
                <tbody>
                <?php if (!$d['planos']) : ?><tr><td colspan="6" class="text-secondary" id="semPlanos">Nenhum plano ainda. Cadastre o primeiro ao lado.</td></tr><?php endif; ?>
                <?php foreach ($d['planos'] as $p) : $uso = (int) $p['situacao'] === 1; ?>
                  <tr data-plano="<?= $h($p['id']) ?>" class="<?= $uso ? '' : 'text-secondary' ?>">
                    <td><?= $h($p['nome']) ?><?= $uso ? '' : ' <span class="badge bg-secondary-lt">fora de uso</span>' ?></td>
                    <td class="text-end"><?= $h($p['parcelas']) ?></td><td class="text-end">R$ <?= $h(F::brl((int) $p['valor_centavos'])) ?></td>
                    <td>dia <?= $h($p['dia_vencimento']) ?></td><td><?= $h($meses[(int) $p['mes_primeira']] ?? '') ?></td>
                    <td class="text-end text-nowrap">
                      <a href="<?= URLADM ?>fin-planos/listar?editar=<?= $h($p['id']) ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                      <form method="post" action="<?= URLADM ?>fin-planos/situacao/<?= $h($p['id']) ?>" class="d-inline"><input type="hidden" name="situacao" value="<?= $uso ? 2 : 1 ?>">
                        <button type="submit" class="btn btn-sm btn-outline-secondary"><?= $uso ? 'Tirar de uso' : 'Pôr em uso' ?></button></form>
                    </td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <div class="col-lg-4">
          <form method="post" action="<?= URLADM ?>fin-planos/salvar" class="card" id="formPlano">
            <input type="hidden" name="id" value="<?= $h((int) ($f['id'] ?? 0)) ?>">
            <div class="card-header"><h3 class="card-title"><?= (int) ($f['id'] ?? 0) ? 'Editar o plano' : 'Novo plano' ?></h3></div>
            <div class="card-body">
              <div class="mb-2"><label class="form-label" for="pNome">Nome</label><input type="text" class="form-control" id="pNome" name="nome" maxlength="120" value="<?= $h($f['nome'] ?? '') ?>" placeholder="Ex.: Anuidade 2027 em 12 vezes" required></div>
              <div class="row g-2">
                <div class="col-6"><label class="form-label" for="pParcelas">Parcelas</label><input type="number" min="1" max="24" class="form-control" id="pParcelas" name="parcelas" value="<?= $h($f['parcelas'] ?? '12') ?>" required></div>
                <div class="col-6"><label class="form-label" for="pValor">Valor da parcela (R$)</label><input type="text" inputmode="decimal" class="form-control" id="pValor" name="valor" value="<?= $h($f['valor'] ?? '') ?>" placeholder="650,00" required></div>
                <div class="col-6"><label class="form-label" for="pDia">Dia de vencimento</label><input type="number" min="1" max="28" class="form-control" id="pDia" name="dia_vencimento" value="<?= $h($f['dia_vencimento'] ?? '10') ?>" required></div>
                <div class="col-6"><label class="form-label" for="pMes">Mês da primeira</label><select class="form-select" id="pMes" name="mes_primeira">
                  <?php foreach ($meses as $n => $m) : ?><option value="<?= $h($n) ?>"<?= (int) ($f['mes_primeira'] ?? 2) === $n ? ' selected' : '' ?>><?= $h($m) ?></option><?php endforeach; ?></select></div>
              </div>
              <small class="form-hint mt-2">O plano vale para as matrículas de depois: mudar um plano não muda as parcelas já geradas.</small>
            </div>
            <div class="card-footer"><button type="submit" class="btn btn-primary" id="salvarPlano">Salvar o plano</button>
              <?php if ((int) ($f['id'] ?? 0)) : ?><a href="<?= URLADM ?>fin-planos/listar" class="btn btn-link">Cancelar</a><?php endif; ?></div>
          </form>
        </div>
      </div>
    </div>
  </section>
</div>
