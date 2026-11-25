<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
use App\adms\Models\historico\AdmsHistoricoModelos as M;

$h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$cor = [1 => 'secondary', 2 => 'success', 3 => 'dark'];
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Modelos de histórico<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <div class="alert alert-info">
        O administrador edita os modelos; a secretaria os usa para emitir. Salvar um modelo <b>ativo</b> cria uma <b>nova versão</b>: a emissão guarda a versão que usou, e ela nunca muda.
        Modelo sem curso marcado é o <b>padrão da instituição</b> para aquele tipo.
      </div>
      <div class="card shadow">
        <div class="card-header"><a href="<?= URLADM ?>historico-modelos/cadastrar" class="btn btn-success btn-sm"><i class="fas fa-plus"></i> Novo modelo</a></div>
        <div class="card-body table-responsive">
          <table class="table table-sm table-striped table-hover table-compacta lista-dados mb-0" id="listaModelos" data-titulo="Modelos de histórico" data-ordem='[[0,"asc"]]'>
            <thead><tr><th class="num">#</th><th>Tipo</th><th>Nome</th><th>Aplica-se a</th><th class="cen">Versão</th><th class="num">Emissões</th><th>Situação</th><th class="nao-exportar nao-ordenar text-end">Ações</th></tr></thead>
            <tbody>
            <?php foreach ($this->dados['modelos'] as $m) : ?>
              <tr data-modelo="<?= htmlspecialchars((string) ((int) $m['id']), ENT_QUOTES, 'UTF-8') ?>">
                <td class="num"><?= htmlspecialchars((string) ((int) $m['id']), ENT_QUOTES, 'UTF-8') ?></td>
                <td><span class="badge bg-<?= htmlspecialchars((string) ($m['tipo'] === 'FINAL' ? 'primary' : 'info'), ENT_QUOTES, 'UTF-8') ?>-lt"><?= $h(M::TIPOS[$m['tipo']] ?? $m['tipo']) ?></span></td>
                <td><?= $h($m['nome']) ?></td>
                <td><?= $m['cursos'] ? $h($m['cursos']) : '<i class="text-muted">padrão da instituição</i>' ?></td>
                <td class="cen">v<?= htmlspecialchars((string) ((int) $m['versao_atual']), ENT_QUOTES, 'UTF-8') ?> <small class="text-muted">(<?= htmlspecialchars((string) ((int) $m['versoes']), ENT_QUOTES, 'UTF-8') ?>)</small></td>
                <td class="num"><?= htmlspecialchars((string) ((int) $m['emissoes']), ENT_QUOTES, 'UTF-8') ?></td>
                <td><span class="badge bg-<?= htmlspecialchars((string) ($cor[(int) $m['situacao']] ?? 'light'), ENT_QUOTES, 'UTF-8') ?>-lt"><?= $h(M::SITUACOES[(int) $m['situacao']] ?? '?') ?></span></td>
                <td class="acoes-linha"><?= \App\adms\Models\helper\AdmsUi::acao('editar', URLADM . 'historico-modelos/editar/' . (int) $m['id'], 'Editar o modelo (cria uma nova versão)') ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>
</div>
