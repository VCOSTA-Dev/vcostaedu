<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* 1.16.0 (D-436): o CATÁLOGO de regras de média — nome, resumo dos componentes, uso, situação e ações. Exatamente uma
   é o "Padrão da instituição" (vale para toda disciplina sem regra); trocar o padrão é ação explícita, com confirmação. */
use App\adms\Models\helper\AdmsUi as Ui;

$h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$d = $this->dados;
$admin = !empty($d['admin']);
$cp = $d['confirmarPadrao'] ?? null;
?>
<div class="vc-pagina">
  <?php ob_start(); ?><i class="fas fa-calculator me-2 text-info"></i>Regras de média<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <div class="alert alert-info small">
        Crie quantas regras quiser e associe cada uma às disciplinas: na <b>matriz</b> (uma a uma) ou em <b>Aplicar a disciplinas…</b>
        (várias de uma vez, na tela da regra). Para agrupar por curso, tipo ou módulo, aplique a mesma regra às disciplinas desse grupo.
        A regra <b>Padrão da instituição</b> vale para toda disciplina sem regra. Uma oferta aberta pode trocar a regra, com justificativa
        (barra da disciplina → Regra de média).
      </div>

      <?php if ($cp) : ?>
        <div class="card shadow" id="confirmarPadrao">
          <div class="card-header"><b>Trocar o Padrão da instituição?</b></div>
          <div class="card-body">
            <p>De <b><?= $h($cp['de']) ?></b> para <b><?= $h($cp['para']) ?></b>.</p>
            <p id="impactoPadrao"><b><?= htmlspecialchars((string) ((int) $cp['disciplinas']), ENT_QUOTES, 'UTF-8') ?></b> disciplina(s) da matriz sem regra própria passam a seguir o novo padrão,
              e <b><?= htmlspecialchars((string) (count($cp['ofertas'])), ENT_QUOTES, 'UTF-8') ?></b> oferta(s) aberta(s) mudam de regra. As disciplinas e ofertas com regra própria não mudam;
              disciplinas já encerradas também não (o encerramento guardou a regra da época).</p>
            <?php if ($cp['ofertas']) : ?><ul class="small" id="ofertasPadrao"><?php foreach ($cp['ofertas'] as $o) : ?><li><?= $h($o) ?></li><?php endforeach; ?></ul><?php endif; ?>
            <form method="POST" action="<?= htmlspecialchars((string) (URLADM . 'regra-media/padrao/' . (int) $cp['id']), ENT_QUOTES, 'UTF-8') ?>" class="d-inline"><input type="hidden" name="confirmado" value="1">
              <button type="submit" class="btn btn-warning" id="confirmarPadraoBtn"><i class="fas fa-star"></i> Confirmar a troca do padrão</button></form>
            <a href="<?= URLADM ?>regra-media/listar" class="btn btn-outline-secondary ms-2">Cancelar</a>
          </div>
        </div>
      <?php endif; ?>

      <div class="card shadow">
        <div class="card-body table-responsive">
          <table class="table table-sm table-hover table-compacta lista-dados mb-0" id="tabelaRegras" data-titulo="Regras de média" data-ordem='[[0,"asc"]]'>
            <thead><tr><th>Nome</th><th>Componentes</th><th>Em uso</th><th>Situação</th><th class="nao-exportar nao-ordenar text-end">Ações</th></tr></thead>
            <tbody>
            <?php foreach ($d['regras'] as $r) : $ativa = $r['situacao'] === 1; ?>
              <tr data-regra="<?= htmlspecialchars((string) ((int) $r['id']), ENT_QUOTES, 'UTF-8') ?>">
                <td><a href="<?= htmlspecialchars((string) (URLADM . 'regra-media/editar/' . (int) $r['id']), ENT_QUOTES, 'UTF-8') ?>"><b><?= $h($r['nome']) ?></b></a>
                  <?= $r['padrao'] ? ' <span class="badge bg-primary-lt">Padrão da instituição</span>' : '' ?>
                  <?= $r['descricao'] !== '' ? '<br><small class="text-muted">' . $h($r['descricao']) . '</small>' : '' ?></td>
                <td class="small"><?= $h($r['resumo']) ?></td>
                <td class="small uso">em uso por <?= htmlspecialchars((string) ((int) $r['disciplinas']), ENT_QUOTES, 'UTF-8') ?> disciplina(s)<?= htmlspecialchars((string) ($r['padrao'] ? ' (todas as sem regra)' : ''), ENT_QUOTES, 'UTF-8') ?> / <?= htmlspecialchars((string) ((int) $r['ofertasAbertas']), ENT_QUOTES, 'UTF-8') ?> oferta(s) aberta(s)</td>
                <td><span class="badge bg-<?= $ativa ? 'success' : 'secondary' ?>-lt"><?= $ativa ? 'Ativa' : 'Inativa' ?></span></td>
                <td class="acoes-linha text-end text-nowrap">
                  <?= Ui::acao('editar', URLADM . 'regra-media/editar/' . (int) $r['id'], 'Editar a regra') ?>
                  <?= Ui::acaoPost('acao', URLADM . 'regra-media/duplicar/' . (int) $r['id'], [], 'Duplicar (cópia sem associações)', null, ['icone' => 'far fa-copy', 'classe' => 'btn-outline-info']) ?>
                  <?= Ui::acao('ver', URLADM . 'regra-media/editar/' . (int) $r['id'] . '#onde', 'Ver onde é usada', null, ['icone' => 'fas fa-sitemap']) ?>
                  <?php if ($ativa) : ?>
                    <?= Ui::acaoPost('inativar', URLADM . 'regra-media/inativar/' . (int) $r['id'], ['acao' => 'inativar'], 'Inativar (só sem uso)',
                      $r['padrao'] ? 'O Padrão da instituição não pode ser inativado' : null, ['confirmar' => 'Inativar a regra "' . $r['nome'] . '"? Ela deixa de aparecer para escolha.']) ?>
                    <?php if ($admin && !$r['padrao']) : ?>
                      <?= Ui::acaoPost('acao', URLADM . 'regra-media/padrao/' . (int) $r['id'], [], 'Tornar o Padrão da instituição', null, ['icone' => 'far fa-star', 'classe' => 'btn-outline-warning']) ?>
                    <?php endif; ?>
                  <?php else : ?>
                    <?= Ui::acaoPost('reativar', URLADM . 'regra-media/inativar/' . (int) $r['id'], ['acao' => 'reativar'], 'Reativar') ?>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>
</div>
