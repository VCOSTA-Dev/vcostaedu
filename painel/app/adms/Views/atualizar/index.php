<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* Onda 2, Etapa 7: a tela de atualização. Onda 3, Etapa 11: em passos, com a janela de progresso; a retomada do lote que
   parou (a etapa que falhou, a página fechada); o resultado no fim. */
$d = $this->dados;
$e = $d['estado'];
$l = $d['lote'];
$h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$v = $d['versoes'];
$parado = $l && empty($l['concluida']) && !$d['outro'];
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Atualizar o sistema<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <div class="card mb-3">
        <div class="card-body">
          <div class="row">
            <div class="col-md-4"><div class="text-secondary">Versão registrada no banco</div><div class="h2 mb-0" id="versaoBanco"><?= $h($v['banco'] ?: '—') ?></div></div>
            <div class="col-md-4"><div class="text-secondary">Versão dos arquivos</div><div class="h2 mb-0" id="versaoArquivos"><?= $h($v['arquivos'] ?: '—') ?></div></div>
            <div class="col-md-4"><div class="text-secondary">Etapas do banco</div><div class="h2 mb-0"><?= htmlspecialchars((string) ((int) $e['aplicadas']), ENT_QUOTES, 'UTF-8') ?> aplicadas</div></div>
          </div>
        </div>
      </div>

      <?php if ($l && !empty($l['concluida'])) : ?>
        <div class="alert alert-success" id="atualizacaoOk"><b>Atualizado:</b> <?= htmlspecialchars((string) (count($l['aplicadas'] ?? [])), ENT_QUOTES, 'UTF-8') ?> etapa(s) aplicada(s). A versão registrada agora é <strong id="versaoDepois"><?= $h($v['banco']) ?></strong>.</div>
      <?php elseif ($d['outro']) : ?>
        <div class="alert alert-warning" id="atualizacaoEmAndamento"><b>Outra atualização está em andamento agora</b>, pela tela (<?= $h($d['outro']) ?>). Espere ela terminar e abra esta tela de novo. Nada foi aplicado por aqui.</div>
      <?php elseif ($parado) : ?>
        <div class="alert alert-<?= htmlspecialchars((string) (!empty($l['falhou']) ? 'danger' : 'warning'), ENT_QUOTES, 'UTF-8') ?>" id="<?= htmlspecialchars((string) (!empty($l['falhou']) ? 'atualizacaoParou' : 'atualizacaoInterrompida'), ENT_QUOTES, 'UTF-8') ?>">
          <?php if (!empty($l['falhou'])) : ?>
            <b>A atualização parou na etapa <code><?= $h($l['falhou']['arquivo'] ?? '') ?></code>:</b> <?= $h($l['falhou']['motivo'] ?? '') ?>
          <?php else : ?>
            <b>Uma atualização começou e não terminou</b> (a página foi fechada no meio, ou a conexão caiu).
          <?php endif; ?>
          <br><?= htmlspecialchars((string) (count($l['aplicadas'] ?? [])), ENT_QUOTES, 'UTF-8') ?> etapa(s) já foram aplicadas e continuam valendo; <?= htmlspecialchars((string) (count($e['pendentes'])), ENT_QUOTES, 'UTF-8') ?> faltam. Resolvida a causa, continue de onde parou.
          <div class="mt-2"><button type="button" class="btn btn-warning" id="continuarAtualizacao" data-progresso="progressoAtualizacao" data-fase="passo"><i class="fas fa-play"></i> Continuar a atualização</button></div>
        </div>
      <?php endif; ?>
      <?php if ($l && !empty($l['aplicadas'])) : ?>
        <div class="card mb-3">
          <div class="card-header"><h3 class="card-title"><?= htmlspecialchars((string) (!empty($l['concluida']) ? 'As etapas aplicadas' : 'As que já passaram'), ENT_QUOTES, 'UTF-8') ?></h3></div>
          <table class="table table-sm card-table" id="etapasAplicadas">
            <thead><tr><th>Etapa</th><th>Situação</th><th class="text-end">Tempo</th></tr></thead>
            <tbody><?php foreach ($l['aplicadas'] as $a) : ?><tr><td><code><?= $h($a['arquivo']) ?></code></td><td><?= $h($a['situacao']) ?></td><td class="text-end"><?= htmlspecialchars((string) ((int) $a['duracao_ms']), ENT_QUOTES, 'UTF-8') ?> ms</td></tr><?php endforeach; ?></tbody>
          </table>
        </div>
      <?php endif; ?>

      <?php if (!$e['ok']) : ?>
        <div class="alert alert-danger" id="estadoIlegivel"><?= $h($e['erro']) ?></div>
      <?php elseif ($e['divergentes']) : ?>
        <div class="alert alert-danger" id="etapasDivergentes">
          <b>Há etapas já aplicadas que foram alteradas nos arquivos:</b> <?= $h(implode(', ', $e['divergentes'])) ?>. Uma etapa aplicada não muda — a correção é sempre uma etapa nova. Restaure os arquivos da versão instalada e procure o suporte. Nada pode ser aplicado assim.
        </div>
      <?php elseif ($e['emDia']) : ?>
        <div class="alert alert-success" id="sistemaEmDia"><b>O sistema está em dia.</b> Não há etapa do banco de dados por aplicar.<?php if ($e['adiadas']) : ?> As etapas que exigem um privilégio que o usuário do banco não tem (<?= $h(implode(', ', $e['adiadas'])) ?>) ficam adiadas de propósito; o sistema funciona sem elas.<?php endif; ?></div>
      <?php elseif (!$d['outro']) : ?>
        <div class="card mb-3" id="cartaoPendentes">
          <div class="card-status-top bg-warning"></div>
          <div class="card-header"><h3 class="card-title"><?= htmlspecialchars((string) (count($e['pendentes'])), ENT_QUOTES, 'UTF-8') ?> etapa(s) do banco de dados por aplicar</h3></div>
          <ul class="list-group list-group-flush" id="listaPendentes">
            <?php foreach ($e['pendentes'] as $p) : ?><li class="list-group-item"><code><?= $h($p) ?></code><?php if (in_array($p, $e['adiadas'], true)) : ?> <span class="small text-secondary">— exige o privilégio TRIGGER do usuário do banco; sem ele, fica adiada (o sistema funciona sem ela)</span><?php endif; ?></li><?php endforeach; ?>
          </ul>
          <?php if (!$parado) : ?>
          <div class="card-body">
            <form method="POST" action="<?= URLADM ?>atualizar/aplicar" id="formAtualizar">
              <div class="alert alert-warning">
                <b>Antes, faça uma cópia (backup) do banco de dados.</b> Algumas etapas mudam a estrutura do banco e não têm volta pela tela; se algo der errado no meio, é o backup que traz o sistema de volta. Por exemplo: <code>mysqldump -u USUARIO -p NOME_DO_BANCO &gt; backup.sql</code>, guardado fora do servidor.
              </div>
              <label class="form-check mb-3"><input type="checkbox" class="form-check-input" name="fiz_backup" value="1" id="fizBackup" required><span class="form-check-label">Tenho uma cópia do banco de dados feita agora, guardada fora do servidor.</span></label>
              <input type="hidden" name="Atualizar" value="1">
              <button type="submit" class="btn btn-primary" id="aplicarAtualizacao" data-progresso="progressoAtualizacao" data-fase="iniciar"><i class="fas fa-arrows-rotate"></i> Atualizar agora</button>
              <p class="text-secondary small mt-2 mb-0">A tela aplica uma etapa de cada vez e mostra o andamento; se a página for fechada no meio, ela continua de onde parou. O mesmo pode ser feito pelo comando <code>php bin/migrar.php aplicar</code>, na pasta do sistema, que aplica tudo em sequência: as duas portas aplicam do mesmo jeito, e uma recusa enquanto a outra estiver atualizando.</p>
            </form>
          </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>
</div>
<?php
/* a janela de progresso: um passo por etapa pendente */
$progressoId = 'progressoAtualizacao';
$progressoTitulo = 'Atualizando o sistema';
$progressoGrupos = [];
foreach (($parado ? array_merge(array_column($l['aplicadas'] ?? [], 'arquivo'), $e['pendentes']) : $e['pendentes']) as $p) {
  $progressoGrupos[$p] = 1;
}
$progressoGrupos = $progressoGrupos ?: ['O banco de dados' => 1];
$progressoUrl = URLADM . 'atualizar/aplicar';
$progressoFim = ['Ver o resultado', URLADM . 'atualizar/index'];
$progressoCampos = ['fiz_backup' => '#fizBackup'];
include 'app/adms/Views/include_tabler/_progresso.php';
