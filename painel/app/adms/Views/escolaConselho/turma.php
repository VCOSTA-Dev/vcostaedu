<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* O conselho de classe da turma: cada aluno, os componentes, as decisões; a observação de uma etapa e a decisão do fim do ano. */
$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$v = fn($n) => \Core\MediaEscola::v($n === null ? null : (string) $n);
$d = $this->dados;
$t = $d['turma'];
$q = $d['quadro'];
$sit = \Core\MediaEscola::SITUACOES;
/* B15 (D-100): o formulário abre na etapa em andamento (a de hoje); depois da última etapa, no fim do ano */
$hoje = date('Y-m-d');
$ultima = $d['etapas'] ? end($d['etapas']) : null;
$etapaHoje = $ultima && $hoje > (string) $ultima['fim'] ? null : \App\adms\Models\notas\AdmsCalculoNotas::etapaDaData($d['etapas'], $hoje);
$etapaPadrao = (int) ($etapaHoje['id'] ?? 0);
$pode = (new \App\adms\Models\AdmsBotao())->valBotao(['registrar' => ['menu_controller' => 'escola-conselho', 'menu_metodo' => 'registrar'],
  'corrigir' => ['menu_controller' => 'escola-conselho', 'menu_metodo' => 'corrigir'], 'retirar' => ['menu_controller' => 'escola-conselho', 'menu_metodo' => 'retirar'],
  'boletins' => ['menu_controller' => 'turmas', 'menu_metodo' => 'boletins']]);
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Conselho de classe — <?= $h($t['codtur']) ?><?php $paginaTitulo = ob_get_clean(); $paginaPretitulo = $h($t['segmento'] . ' · ' . $t['ano']);
  /* Onda 8, correção (D-80): "Imprimir todos da turma", como no sistema de origem */
  $paginaAcoesHtml = !empty($pode['boletins']) ? '<a href="' . URLADM . 'turmas/boletins/' . (int) $t['id'] . '" class="btn btn-outline-secondary" id="boletinsConselho" target="_blank" rel="noopener"><i class="fas fa-print"></i> Imprimir os boletins da turma</a>' : '';
  include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <p class="text-secondary">A situação de cada disciplina é a do ano como se ele fechasse agora. O conselho registra a observação de cada etapa e, no fim do ano, pode aprovar quem ficou reprovado — a nota não muda, a situação passa a "Aprovado pelo conselho", com a observação, quem decidiu e quando.</p>
      <?php foreach ($q['alunos'] as $a) : $dec = []; foreach ($d['etapas'] as $e) { foreach ($q['decisoes'][(int) $e['id']][$a['rm']] ?? [] as $x) { $dec[] = [$e['nome'], $x]; } } foreach ($q['decisoes'][0][$a['rm']] ?? [] as $x) { $dec[] = ['Fim do ano', $x]; } ?>
        <div class="card mb-3 vc-aluno-conselho" data-rm="<?= $h($a['rm']) ?>" data-reprovado="<?= htmlspecialchars((string) ($a['reprovado'] ? '1' : '0'), ENT_QUOTES, 'UTF-8') ?>">
          <div class="card-header"><h3 class="card-title"><?= $h($a['nome']) ?> <span class="text-secondary small">RM <?= $h($a['rm']) ?></span></h3></div>
          <div class="card-body">
            <div class="d-flex flex-wrap gap-2 mb-2">
              <?php foreach ($a['componentes'] as $c) : $cor = in_array($c['situacao'], ['aprovado', 'aprovado_conselho'], true) ? 'success' : (str_starts_with($c['situacao'], 'reprovado') ? 'danger' : 'secondary'); ?>
                <span class="badge bg-<?= htmlspecialchars((string) ($cor), ENT_QUOTES, 'UTF-8') ?>-lt" data-oferta="<?= htmlspecialchars((string) ((int) $c['oferta']), ENT_QUOTES, 'UTF-8') ?>" data-situacao="<?= $h($c['situacao']) ?>"><?= $h($c['materia']) ?>: <?= $v($c['media']) ?> · <?= $h($sit[$c['situacao']] ?? '') ?></span>
              <?php endforeach; ?>
            </div>
            <?php if ($dec) : ?>
              <ul class="small mb-2 vc-decisoes">
                <?php foreach ($dec as [$quando, $x]) : $muda = \App\adms\Models\escola\AdmsConselho::podeMudar($x); ?><li class="vc-decisao" data-registro="<?= (int) $x['id'] ?>"><b><?= $h($quando) ?>:</b> <?= $x['decisao'] === 'aprovar' ? '<span class="text-success">aprovado pelo conselho</span> — ' : '' ?><span class="vc-obs"><?= $h($x['observacao']) ?></span> <span class="text-secondary">(<?= $h($x['por_nome'] ?? '') ?>, <?= htmlspecialchars((string) (date('d/m/Y', strtotime((string) $x['em']))), ENT_QUOTES, 'UTF-8') ?><?= !empty($x['alterado_em']) ? '; corrigida por ' . $h($x['alterado_por_nome'] ?? '') . ' em ' . $h(date('d/m/Y', strtotime((string) $x['alterado_em']))) : '' ?>)</span>
                  <?php if ($muda && (!empty($pode['corrigir']) || !empty($pode['retirar']))) : ?>
                    <details class="d-inline-block ms-1 vc-mudar-decisao"><summary class="small text-primary" style="cursor: pointer">corrigir ou retirar</summary>
                      <div class="d-flex flex-wrap gap-2 align-items-end mt-1">
                        <?php if (!empty($pode['corrigir'])) : ?>
                        <form method="post" action="<?= URLADM ?>escola-conselho/corrigir/<?= (int) $t['id'] ?>" class="d-flex gap-2 align-items-end vc-form-corrigir">
                          <input type="hidden" name="registro" value="<?= (int) $x['id'] ?>">
                          <input type="text" name="observacao" class="form-control form-control-sm" style="min-width: 18rem" value="<?= $h($x['observacao']) ?>" minlength="10" maxlength="1000" required aria-label="Corrigir a observação">
                          <button type="submit" class="btn btn-sm btn-outline-primary">Corrigir</button>
                        </form>
                        <?php endif; ?>
                        <?php if (!empty($pode['retirar'])) : ?>
                        <form method="post" action="<?= URLADM ?>escola-conselho/retirar/<?= (int) $t['id'] ?>" class="vc-form-retirar" data-vc-confirma="Retirar este registro do conselho?"
                          data-vc-confirma-extra="<?= $h($x['decisao'] === 'aprovar' ? 'A aprovação pelo conselho deixa de valer: a situação volta a ser a das notas. O registro fica no histórico.' : 'A observação sai da tela e fica no histórico.') ?>">
                          <input type="hidden" name="registro" value="<?= (int) $x['id'] ?>">
                          <button type="submit" class="btn btn-sm btn-outline-danger" data-vc-rotulo="Retirar">Retirar</button>
                        </form>
                        <?php endif; ?>
                      </div>
                    </details>
                  <?php endif; ?></li><?php endforeach; ?>
              </ul>
            <?php endif; ?>
            <?php if (!empty($pode['registrar'])) : ?>
              <form method="post" action="<?= URLADM ?>escola-conselho/registrar/<?= htmlspecialchars((string) ((int) $t['id']), ENT_QUOTES, 'UTF-8') ?>" class="row g-2 align-items-end vc-form-conselho">
                <input type="hidden" name="rm" value="<?= $h($a['rm']) ?>">
                <div class="col-md-3"><label class="form-label">Quando</label>
                  <select name="etapa_id" class="form-select form-select-sm">
                    <?php foreach ($d['etapas'] as $e) : ?><option value="<?= (int) $e['id'] ?>"<?= (int) $e['id'] === $etapaPadrao ? ' selected' : '' ?>><?= $h($e['nome']) ?></option><?php endforeach; ?>
                    <option value="0"<?= $etapaPadrao === 0 ? ' selected' : '' ?>>Fim do ano</option>
                  </select></div>
                <div class="col-md-3"><label class="form-label">Decisão</label>
                  <select name="decisao" class="form-select form-select-sm">
                    <option value="observacao">Só a observação</option>
                    <?php if ($a['reprovado']) : ?><option value="aprovar">Aprovar pelo conselho (fim do ano)</option><?php endif; ?>
                  </select></div>
                <div class="col-md-4"><label class="form-label">Observação (obrigatória)</label><input type="text" name="observacao" class="form-control form-control-sm" maxlength="1000" required></div>
                <div class="col-md-2"><button type="submit" class="btn btn-sm btn-primary w-100">Registrar</button></div>
              </form>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (!$q['alunos']) : ?><div class="alert alert-info">Nenhum aluno na turma.</div><?php endif; ?>
    </div>
  </section>
</div>
