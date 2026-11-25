<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* As listas do financeiro em Configurações (D-99): os motivos de cada operação e as bandeiras de cartão. Cada linha se
   edita ali mesmo (o nome, a ordem, em uso ou fora de uso); embaixo, incluir. Nada se apaga. */
use App\adms\Models\financeiro\AdmsFinListas as L;

$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$d = $this->dados;
$motivos = $d['tipo'] === 'motivos';
$acao = URLADM . ($motivos ? 'fin-motivos/salvar' : 'fin-bandeiras/salvar');
?>
<div class="vc-pagina">
  <?php ob_start(); ?><?= $motivos ? 'Motivos das operações' : 'Bandeiras de cartão' ?><?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <p class="text-secondary"><?= $motivos
        ? 'A lista de motivos que cada operação do financeiro oferece (o Desfazer recebimento já a usa; baixar, cancelar, prescrever e renegociar passam a usar nas próximas partes). Tire de uso o que não serve: o motivo já usado continua no histórico.'
        : 'As bandeiras que o Receber oferece no pagamento com cartão (registrado à mão). Tire de uso a que não serve: o recibo que já a usou continua com ela.' ?></p>
      <div class="card mb-3">
        <div class="table-responsive">
          <table class="table table-sm table-vcenter card-table" id="tabelaLista">
            <thead><tr><?php if ($motivos) : ?><th>Operação</th><?php endif; ?><th><?= $motivos ? 'Motivo' : 'Bandeira' ?></th><th style="width:7rem">Ordem</th><th style="width:10rem">Situação</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($d['lista'] as $l) : ?>
              <tr data-id="<?= $h($l['id']) ?>" class="<?= (int) $l['situacao'] === 2 ? 'text-secondary' : '' ?>">
                <?php if ($motivos) : ?><td class="text-nowrap"><?= $h(L::OPERACOES[$l['operacao']] ?? $l['operacao']) ?></td><?php endif; ?>
                <td colspan="4" class="p-0">
                  <form method="post" action="<?= $h($acao) ?>" class="d-flex gap-2 align-items-center p-1 vc-linha-lista">
                    <input type="hidden" name="id" value="<?= $h($l['id']) ?>">
                    <input type="text" class="form-control form-control-sm" name="nome" value="<?= $h($l['nome']) ?>" maxlength="<?= $motivos ? 120 : 60 ?>" required aria-label="O nome">
                    <input type="number" class="form-control form-control-sm" style="width:6rem" name="ordem" value="<?= $h($l['ordem']) ?>" min="0" max="999" aria-label="A ordem">
                    <select class="form-select form-select-sm" style="width:9rem" name="situacao" aria-label="A situação"><option value="1"<?= (int) $l['situacao'] === 1 ? ' selected' : '' ?>>Em uso</option><option value="2"<?= (int) $l['situacao'] === 2 ? ' selected' : '' ?>>Fora de uso</option></select>
                    <button type="submit" class="btn btn-sm btn-outline-primary">Salvar</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <form method="post" action="<?= $h($acao) ?>" class="card" id="formIncluir" style="max-width:48rem">
        <div class="card-header"><h3 class="card-title"><?= $motivos ? 'Incluir um motivo' : 'Incluir uma bandeira' ?></h3></div>
        <div class="card-body row g-2">
          <?php if ($motivos) : ?><div class="col-md-5"><label class="form-label" for="novaOperacao">Operação</label><select class="form-select" id="novaOperacao" name="operacao"><?php foreach (L::OPERACOES as $k => $rot) : ?><option value="<?= $h($k) ?>"><?= $h($rot) ?></option><?php endforeach; ?></select></div><?php endif; ?>
          <div class="col"><label class="form-label" for="novoNome"><?= $motivos ? 'Motivo' : 'Bandeira' ?></label><input type="text" class="form-control" id="novoNome" name="nome" maxlength="<?= $motivos ? 120 : 60 ?>" required></div>
        </div>
        <div class="card-footer"><button type="submit" class="btn btn-primary" id="incluirNaLista">Incluir</button></div>
      </form>
    </div>
  </section>
</div>
