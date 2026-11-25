<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* Onda 2, Etapa 3: o histórico de uma nota (a avaliação e o aluno). A tela das notas abre o #historicoNota numa janela. */
use App\adms\Models\auditoria\AdmsAuditoriaConsulta as C;

$n = $this->dados['nota'] ?? [];
$info = $n['info'] ?? null;
$linhas = $n['linhas'] ?? [];
$e = fn($t) => htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
$v = fn($x) => $x === null ? '—' : str_replace('.', ',', (string) $x);
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Histórico desta nota<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <div id="historicoNota">
        <?php if (!$info || empty($info['rm'])) : ?>
          <div class="alert alert-warning mb-0">Nota não encontrada.</div>
        <?php else : ?>
          <p class="mb-2"><strong><?= $e($info['rm'] . ' ' . $info['nome_aluno']) ?></strong> — <?= $e($info['atividade']) ?> (<?= $e($info['materia'] . ' — ' . $info['codtur'] . ' ' . $info['ano']) ?>).
            Nota atual: <strong id="notaAtual"><?= $e($v($info['nota'])) ?></strong>.</p>
          <?php if (!$linhas) : ?>
            <p class="text-secondary mb-0" id="semHistorico">Nenhuma mudança registrada desde o início da auditoria.</p>
          <?php else : ?>
            <div class="table-responsive">
              <table class="table table-sm table-striped mb-0" id="tabelaHistoricoNota">
                <thead><tr><th>Quando</th><th>Ação</th><th>Antes</th><th>Depois</th><th>Quem</th><th>Origem</th></tr></thead>
                <tbody>
                  <?php foreach ($linhas as $l) : ?>
                    <tr>
                      <td><?= $e(date('d/m/Y H:i:s', strtotime(substr((string) $l['ocorrido_em'], 0, 19)))) ?></td>
                      <td><?= $e(C::ACOES[$l['acao']] ?? $l['acao']) ?><?= (int) $l['apos_fechamento'] === 1 ? ' <span class="badge bg-warning-lt">depois do fechamento</span>' : '' ?></td>
                      <td><?= $e($v($l['valor_anterior'])) ?></td>
                      <td><?= $e($v($l['valor_novo'])) ?></td>
                      <td><?= $e($l['usuario_nome'] . ' (' . $l['perfil'] . ')') ?></td>
                      <td><?= $e(C::ORIGENS[$l['origem']] ?? $l['origem']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </section>
</div>
