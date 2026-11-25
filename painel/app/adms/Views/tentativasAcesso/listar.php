<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
$h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$rot = ['login_painel' => 'Login do painel', 'login_portal' => 'Login do portal', 'esqueci_painel' => 'Esqueci a senha (painel)', 'esqueci_portal' => 'Esqueci a senha (portal)', 'verificacao' => 'Verificação pública', 'convite_portal' => 'Convite do responsável (portal)'];
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Tentativas de acesso<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <div class="alert alert-info">
        Depois de algumas falhas seguidas, o login, o "Esqueci a senha" e a verificação pública pedem uma <b>espera</b>, que começa em 30 segundos e dobra a cada nova falha (até 15 minutos).
        <?php $jan = fn(int $s) => $s >= 86400 ? ($s / 86400) . ' dia' : ($s >= 3600 ? ($s / 3600) . ' h' : ($s / 60) . ' min'); ?>
        Limites: <?php foreach ($this->dados['regras'] as $e => $r) : ?><?= $h($rot[$e]) ?>: <?= htmlspecialchars((string) ($r['id'] ? $r['id'] . ' por usuário em ' . $jan($r['janela_id']) . ', ' : ''), ENT_QUOTES, 'UTF-8') ?><?= htmlspecialchars((string) ((int) $r['ip']), ENT_QUOTES, 'UTF-8') ?> por IP em <?= htmlspecialchars((string) ($jan($r['janela_ip'])), ENT_QUOTES, 'UTF-8') ?>; <?php endforeach; ?>
        Os limites por IP se ajustam em <a href="<?= URLADM ?>parametros/listar">Configurações → Instituição</a>.
        A tabela guarda só uma <b>dica</b> do usuário ou do IP. Para liberar alguém, digite o usuário (ou o IP) abaixo.
      </div>
      <div class="card shadow">
        <div class="card-body pb-1">
          <form method="POST" action="<?= URLADM ?>tentativas-acesso/liberar" class="d-flex flex-wrap align-items-center gap-2">
            <input type="text" name="chave" class="form-control form-control-sm me-2 mb-2" style="min-width: 260px" placeholder="Usuário, e-mail, RA, CPF ou IP" aria-label="Usuário ou IP a liberar">
            <button class="btn btn-sm btn-warning mb-2">Liberar</button>
          </form>
        </div>
        <div class="card-body table-responsive">
          <table class="table table-sm table-striped table-hover table-compacta lista-dados mb-0" id="listaTentativas" data-titulo="Tentativas de acesso" data-ordem='[[4,"desc"]]'>
            <thead><tr><th>Onde</th><th>Por</th><th>Dica</th><th class="num">Falhas</th><th>Última</th><th>Espera até</th><th class="nao-exportar nao-ordenar text-end">Ações</th></tr></thead>
            <tbody>
            <?php foreach ($this->dados['linhas'] as $l) : ?>
              <tr class="<?= htmlspecialchars((string) ($l['ativo'] ? 'table-warning' : ''), ENT_QUOTES, 'UTF-8') ?>">
                <td><?= $h($rot[$l['escopo']] ?? $l['escopo']) ?></td><td><?= htmlspecialchars((string) ($l['tipo'] === 'ip' ? 'IP' : 'usuário'), ENT_QUOTES, 'UTF-8') ?></td><td class="font-monospace"><?= $h($l['dica']) ?></td>
                <td class="num"><?= htmlspecialchars((string) ((int) $l['falhas']), ENT_QUOTES, 'UTF-8') ?></td><td data-order="<?= $h($l['ultima']) ?>"><?= htmlspecialchars((string) (date('d/m/Y H:i:s', strtotime($l['ultima']))), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= $l['ativo'] ? '<b>' . date('d/m/Y H:i:s', strtotime($l['bloqueado_ate'])) . '</b>' : '—' ?></td>
                <td class="acoes-linha"><?= \App\adms\Models\helper\AdmsUi::acaoPost('liberar', URLADM . 'tentativas-acesso/liberar', ['id' => (int) $l['id']], 'Liberar: zera as falhas deste contador') ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>
</div>
