<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* Onda 2, Etapa 6: o registro das chamadas da API. */
$d = $this->dados;
$h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Chamadas da API<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <div class="card mb-3">
        <div class="card-body">
          <form method="GET" action="<?= URLADM ?>integracoes/chamadas" class="row g-2 align-items-end" id="filtroChamadas">
            <div class="col-md-5"><label class="form-label" for="filtroChave">Chave</label>
              <select name="chave" id="filtroChave" class="form-select"><option value="">Todas</option>
                <?php foreach ($d['chaves'] as $k) : ?><option value="<?= htmlspecialchars((string) ((int) $k['id']), ENT_QUOTES, 'UTF-8') ?>" <?= htmlspecialchars((string) ((int) $d['filtro']['chave_id'] === (int) $k['id'] ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>><?= $h($k['nome'] . ' (' . $k['prefixo'] . '…)') ?></option><?php endforeach; ?>
              </select></div>
            <div class="col-md-3"><label class="form-label" for="filtroResultado">Resultado</label>
              <select name="resultado" id="filtroResultado" class="form-select"><option value="">Todos</option><option value="ok" <?= htmlspecialchars((string) ($d['filtro']['resultado'] === 'ok' ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>>Com sucesso</option><option value="erro" <?= htmlspecialchars((string) ($d['filtro']['resultado'] === 'erro' ? 'selected' : ''), ENT_QUOTES, 'UTF-8') ?>>Com erro</option></select></div>
            <div class="col-md-2"><button type="submit" class="btn btn-outline-primary w-100">Filtrar</button></div>
          </form>
        </div>
      </div>
      <div class="card">
        <div class="card-header"><h3 class="card-title">As últimas 500 chamadas</h3></div>
        <?php if (empty($d['chamadas'])) : ?>
          <div class="card-body text-secondary" id="semChamadas">Nenhuma chamada registrada.</div>
        <?php else : ?>
          <div class="table-responsive">
            <table class="table table-sm table-vcenter card-table" id="tabelaChamadas">
              <thead><tr><th>Quando</th><th>Chave</th><th>Operação</th><th>Resultado</th><th>IP</th><th class="text-end">ms</th></tr></thead>
              <tbody>
                <?php foreach ($d['chamadas'] as $c) : $erro = (int) $c['status'] >= 400; ?>
                  <tr data-status="<?= htmlspecialchars((string) ((int) $c['status']), ENT_QUOTES, 'UTF-8') ?>" data-operacao="<?= $h($c['operacao']) ?>">
                    <td class="text-nowrap"><?= $h(date('d/m/Y H:i:s', strtotime((string) $c['created']))) ?></td>
                    <td><?= $c['chave_nome'] !== null ? $h($c['chave_nome']) : '<span class="text-secondary">(sem chave válida)</span>' ?><?php if ($c['prefixo']) : ?><div class="small text-secondary"><code><?= $h($c['prefixo']) ?>…</code></div><?php endif; ?></td>
                    <td><code><?= $h($c['metodo'] . ' ' . $c['rota']) ?></code><?php if ($c['idempotency_key']) : ?><div class="small text-secondary">Idempotency-Key: <?= $h($c['idempotency_key']) ?></div><?php endif; ?></td>
                    <td><span class="badge <?= $erro ? 'bg-red-lt' : 'bg-green-lt' ?>"><?= htmlspecialchars((string) ((int) $c['status']), ENT_QUOTES, 'UTF-8') ?></span> <?= $c['codigo_erro'] ? '<span class="small">' . $h($c['codigo_erro']) . '</span>' : '' ?></td>
                    <td class="small"><?= $h($c['ip']) ?></td>
                    <td class="text-end small"><?= htmlspecialchars((string) ((int) $c['duracao_ms']), ENT_QUOTES, 'UTF-8') ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
</div>
