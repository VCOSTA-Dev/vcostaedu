<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* E4: prévia do encerramento (cálculo FINAL do motor) e confirmação; reabrir (administrador).
   E8c: barra da disciplina (R5), prévia em tabela padrão com imprimir/exportar (R1), faixa de trava (R6). */
use App\adms\Models\notas\AdmsCalculoNotas as Calc;
use App\adms\Models\helper\AdmsUi as Ui;

$c = $this->dados['previa'];
$o = $c['oferta'];
$rg = $c['regras'];
$h = fn($t) => htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
$encerrada = !empty($o['encerrada_em']);
$hist = $this->dados['historico'] ?? [];
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Encerramento — <?= $h($o['materia']) ?> <small class="text-muted"><?= $h($o['codtur']) ?> · <?= htmlspecialchars((string) ((int) $o['ano']), ENT_QUOTES, 'UTF-8') ?></small><?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?= Ui::barraDisciplina((int) $o['id'], 'encerramento', '', $this->dados['barra'] ?? [], $encerrada ? '' : '<button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()" title="Imprimir a prévia"><i class="fas fa-print"></i> Imprimir a prévia</button>') ?>
      <?php
      if (isset($_SESSION['msg'])) {
        echo $_SESSION['msg'];
        unset($_SESSION['msg']);
      }
      ?>
      <?php if ($encerrada) : ?>
        <?= Ui::faixaTrava('Disciplina encerrada em ' . date('d/m/Y H:i', strtotime($o['encerrada_em'])) . ': o resultado gravado está em Notas e resultado; notas e faltas estão travadas.',
          !empty($this->dados['podeReabrir']) ? 'Para mudar, reabra abaixo, com a justificativa.' : 'Para mudar, peça ao administrador que reabra a disciplina.', !empty($this->dados['podeReabrir']) ? ['Reabrir (abaixo)', '#reabrirOferta'] : null, 'ofertaEncerrada') ?>
      <?php elseif ($c['impedimentos']) : ?>
        <div class="alert alert-warning" id="impedimentos"><b>Não é possível encerrar ainda:</b><ul class="mb-0"><?php foreach ($c['impedimentos'] as $i) : ?><li><?= $h($i) ?></li><?php endforeach; ?></ul></div>
      <?php else : ?>
        <?php if ($avisoPrev = \App\adms\Models\faltas\AdmsChamada::avisoPrevistos((int) $o['id'])) : /* 1.17.2 (D-462): só aviso, não bloqueia */ ?>
          <div class="alert alert-warning" id="avisoPrevistos"><b>Encontros:</b> <?= $h($avisoPrev) ?> A frequência que será gravada é sobre os encontros registrados. Você pode encerrar assim mesmo.</div>
        <?php endif; ?>
        <div class="alert alert-info">
          <?php $ref = $c['regraEfetiva'] ?? null; $rgm = $ref['regra'] ?? null; /* 1.13.0 (D-396): a regra que vai valer e ser guardada */ ?>
          <b>Prévia do encerramento.</b> Regra de média <?= $ref ? '"' . $h($ref['nome'] ?? '') . '" (' . $h(\App\adms\Models\notas\AdmsRegraMedia::ORIGENS[$ref['origem']] ?? $ref['origem']) . ')' : '' /* 1.16.0: o nome da regra; sem versão */ ?><?= ($this->dados['barra']['regra'] ?? null) === true ? ' (<a href="' . URLADM . 'regra-media/oferta/' . (int) $o['id'] . '">ver</a>)' : '' ?>: <?= $rgm ? 'a média tem ' . \App\adms\Models\helper\AdmsUi::plural(count($rgm['componentes']), 'componente', 'componentes') . '; a atividade sem nota ' . ($rgm['semNota'] === 'zero' ? 'conta 0' : 'fica de fora') . '; o arredondamento é ' . $h(mb_strtolower(\Core\MotorMedia::ARREDONDAMENTOS[$rgm['arredondamento']] ?? '')) : '' ?>; <?= htmlspecialchars(\App\adms\Models\helper\AdmsUi::plural(count($c['atividades']), 'atividade', 'atividades'), ENT_QUOTES, 'UTF-8') ?>.
          A regra fica guardada no encerramento: mudá-la depois não altera o que for fechado.
          Média com <?= htmlspecialchars(\App\adms\Models\helper\AdmsUi::plural((int) $rg['casas'], 'casa decimal', 'casas decimais'), ENT_QUOTES, 'UTF-8') ?>; mínimo <?= $h(\App\adms\Models\notas\AdmsCalculoNotas::numero($rg['mediaMinima'], 1)) ?>; frequência mínima <?= $h(\App\adms\Models\notas\AdmsCalculoNotas::numero($rg['frequenciaMinima'])) ?>%.
          Ao confirmar, o resultado é gravado e notas e faltas ficam travadas.
        </div>
      <?php endif; ?>

      <?php if (!$encerrada) : ?>
        <div class="card shadow">
          <div class="card-body">
            <table class="table table-sm table-striped table-hover table-compacta lista-dados" id="tabelaPrevia" data-titulo="Prévia do encerramento — <?= $h($o['materia']) ?> (<?= $h($o['codtur']) ?>)" data-paginar="nao">
              <thead><tr><th>Aluno</th><th>Conta</th><th class="num">Média final</th><th class="num">Faltas</th><th class="num">Frequência</th><th>Resultado</th></tr></thead>
              <tbody>
                <?php foreach ($c['alunos'] as $al) : ?>
                  <tr data-rm="<?= $h($al['rm']) ?>">
                    <td><?= $h($al['nome']) ?> <small class="text-muted"><?= $h($al['rm']) ?></small></td>
                    <td><small><?= $h($al['conta']) ?></small></td>
                    <td class="num media<?= $al['media'] !== null && (float) $al['media'] < (float) $rg['mediaMinima'] ? ' abaixo' : '' ?>"><b><?= $h(Calc::formatar($al['media'], (int) $rg['casas'])) ?></b></td>
                    <td class="num"><?= htmlspecialchars((string) ((int) $al['faltas']), ENT_QUOTES, 'UTF-8') ?><?= $al['compensadas'] ? ' <small>(−' . (int) $al['compensadas'] . ')</small>' : '' ?></td>
                    <td class="num frequencia<?= $al['frequencia'] !== null && (float) $al['frequencia'] < (float) $rg['frequenciaMinima'] ? ' abaixo' : '' ?>"><?= $al['frequencia'] === null ? '<span class="text-muted">não informada</span>' : $h(Calc::formatar($al['frequencia'], 2)) . '%' ?></td>
                    <td class="resultado"><span class="badge bg-<?= htmlspecialchars((string) ($al['resultado'] === 1 ? 'success' : ($al['resultado'] === 2 ? 'danger' : (!empty($al['motivosPendencia']) ? 'warning' : 'info'))), ENT_QUOTES, 'UTF-8') ?>-lt"><?= $h(Calc::rotuloResultado($al['resultado'], $al['motivo'], $al['motivosPendencia'] ?? [])) /* E10 / 1.15.2: Pendente (motivos do motor) */ ?></span></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php if (!$c['impedimentos'] && !empty($this->dados['podeEncerrar'])) : ?>
            <form method="POST" action="<?= htmlspecialchars((string) (URLADM . 'disciplinas/encerrar/' . (int) $o['id']), ENT_QUOTES, 'UTF-8') ?>">
              <div class="card-footer no-print"><button type="submit" name="ConfirmarEncerramento" value="1" class="btn btn-success"><i class="fas fa-lock"></i> Confirmar encerramento</button> <small class="text-muted ms-2">O resultado é gravado e notas e faltas ficam travadas.</small></div>
            </form>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?php if ($encerrada && !empty($this->dados['podeReabrir'])) : ?>
        <div class="card shadow" id="reabrirOferta">
          <div class="card-header"><h3 class="card-title"><i class="fas fa-lock-open"></i> Reabrir a disciplina (administrador)</h3></div>
          <form method="POST" action="<?= htmlspecialchars((string) (URLADM . 'disciplinas/reabrir/' . (int) $o['id']), ENT_QUOTES, 'UTF-8') ?>">
            <div class="card-body">
              <label class="form-label" for="observacao">Justificativa (obrigatória; fica no histórico)</label>
              <textarea name="observacao" id="observacao" class="form-control" rows="2"></textarea>
              <small class="text-muted" id="dicaReabrir"><?php /* 1.18.2 (D-491): a dica diz o que o sistema faz (D-397) */ ?>Reabrir exige a justificativa e <b>não apaga</b> o resultado: o fechamento atual fica guardado como <b>substituído</b> (no quadro Fechamentos), notas e faltas voltam a ser lançadas, e o novo encerramento grava o fechamento seguinte.</small>
            </div>
            <div class="card-footer"><button type="submit" class="btn btn-warning"><i class="fas fa-lock-open"></i> Reabrir</button></div>
          </form>
        </div>
      <?php endif; ?>

      <?php if (!empty($this->dados['fechamentos'])) : /* 1.13.0 (D-397): fechamentos — o vigente e os substituídos (guardados, nunca apagados) */ ?>
        <div class="card shadow" id="fechamentos">
          <div class="card-header"><h3 class="card-title">Fechamentos</h3></div>
          <div class="card-body p-0"><table class="table table-sm mb-0"><thead><tr><th>Nº</th><th>Situação</th><th>Regra usada</th><th>Fechado em</th><th>Reaberto</th></tr></thead><tbody>
            <?php foreach ($this->dados['fechamentos'] as $fx) : ?>
              <tr data-numero="<?= htmlspecialchars((string) ((int) $fx['numero']), ENT_QUOTES, 'UTF-8') ?>" data-situacao="<?= $h($fx['situacao']) ?>" data-regra-versao-id="<?= htmlspecialchars((string) ((int) $fx['regra_versao_id']), ENT_QUOTES, 'UTF-8') ?>">
                <td><?= htmlspecialchars((string) ((int) $fx['numero']), ENT_QUOTES, 'UTF-8') ?></td>
                <td><span class="badge bg-<?= htmlspecialchars((string) ($fx['situacao'] === 'vigente' ? 'success' : 'secondary'), ENT_QUOTES, 'UTF-8') ?>-lt"><?= htmlspecialchars((string) ($fx['situacao'] === 'vigente' ? 'Vigente' : 'Substituído'), ENT_QUOTES, 'UTF-8') ?></span><?= $fx['situacao'] !== 'vigente' ? ' <small class="text-muted">(' . (int) $fx['guardados'] . ' resultado(s) guardado(s))</small>' : '' ?></td>
                <td><?= $fx['regra_origem'] === 'legado' ? 'anterior à 1.13.0' : ($fx['regraNome'] ? '"' . $h($fx['regraNome']) . '" ' : '') . '(' . $h((\App\adms\Models\notas\AdmsRegraMedia::ORIGENS + ['instituicao' => 'da instituição', 'curso' => 'do curso'])[$fx['regra_origem']] ?? $fx['regra_origem']) . '), guardada no encerramento' /* 1.16.0: sem versão */ ?></td>
                <td><?= $h(date('d/m/Y H:i', strtotime($fx['fechado_em']))) ?><?= $fx['fechadoPorNome'] ? ' — ' . $h($fx['fechadoPorNome']) : '' ?></td>
                <td><?= $fx['reaberto_em'] ? $h(date('d/m/Y H:i', strtotime($fx['reaberto_em']))) . ' — ' . $h($fx['reabertoPorNome'] ?? '') . '<br><small>' . $h($fx['justificativa_reabertura']) . '</small>' : '—' ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody></table></div>
        </div>
      <?php endif; ?>

      <?php if ($hist) : ?>
        <div class="card shadow">
          <div class="card-header"><h3 class="card-title">Histórico da oferta</h3></div>
          <ul class="list-group list-group-flush">
            <?php foreach ($hist as $x) : ?>
              <li class="list-group-item"><b><?= htmlspecialchars((string) ($x['acao'] === 'encerrar' ? 'Encerrada' : 'Reaberta'), ENT_QUOTES, 'UTF-8') ?></b> — <?= $h(date('d/m/Y H:i', strtotime($x['created']))) ?> — <?= $h($x['usuario']) ?><?= $x['observacao'] ? '<br><small>' . $h($x['observacao']) . '</small>' : '' ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
    </div>
  </section>
</div>
