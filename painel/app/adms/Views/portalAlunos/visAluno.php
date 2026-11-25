<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* E3b: enturmação SUGERIDA pela matriz e CONFIRMADA pela secretaria (D-00c).
   Dados de AdmsEnturmacaoSugerida: aluno, ano e as posições do aluno no ano. */
$d = $this->dados['dados_info'] ?? [];
$h = fn($t) => htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
$bt = $this->dados['botao'] ?? [];
$semMod = \App\adms\Models\helper\AdmsModuloInterno::ativo(); /* E8c (D-268): sem módulo na enturmação */
?>
<div class="vc-pagina">
  <?php ob_start(); ?><a href="<?= htmlspecialchars((string) (URLADM . 'portal-alunos/listar/' . (int) ($d['ano'] ?? 0)), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary rounded-circle" title="Voltar"><i class="fas fa-arrow-left"></i></a>
            Enturmação — <?= $h($d['aluno']['nome'] ?? 'aluno não encontrado') ?> (<?= htmlspecialchars((string) ((int) ($d['ano'] ?? 0)), ENT_QUOTES, 'UTF-8') ?>)<?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?php
      if (isset($_SESSION['msg'])) {
        echo $_SESSION['msg'];
        unset($_SESSION['msg']);
      }
      ?>
      <?php if (empty($d['aluno'])) : ?>
        <div class="alert alert-danger">Aluno não encontrado.</div>
      <?php elseif (empty($d['posicoes'])) : ?>
        <div class="alert alert-warning" id="semPosicao">
          <b>O aluno não tem vínculo com curso em <?= htmlspecialchars((string) ((int) $d['ano']), ENT_QUOTES, 'UTF-8') ?>.</b> Vincule na ficha do aluno (aba Matrículas): sem <?= $semMod ? 'ano + curso + grade' : 'ano + curso + grade + módulo' ?> não há enturmação sugerida.
        </div>
      <?php endif; ?>

      <?php foreach ($d['posicoes'] ?? [] as $p) : ?>
        <div class="card shadow posicaoAno" data-posicao="<?= htmlspecialchars((string) ((int) $p['id']), ENT_QUOTES, 'UTF-8') ?>">
          <div class="card-header">
            <h3 class="card-title"><b><?= $h($p['curso']) ?></b> — grade <?= htmlspecialchars((string) ($p['versao'] !== null ? (int) $p['versao'] : '—'), ENT_QUOTES, 'UTF-8') ?><?= $semMod ? '' : ' — ' . $h($p['modulo'] ?? 'sem módulo') ?>
              <span class="badge bg-light text-dark ms-1"><?= $h($p['nomeSituacao']) ?></span></h3>
            <div class="card-actions small">
              <?= $p['enturmacao_confirmada_em'] ? 'Confirmada em ' . $h(date('d/m/Y H:i', strtotime($p['enturmacao_confirmada_em']))) . ' por ' . $h($p['confirmadaPor']) : '<span class="text-warning">Ainda não confirmada: ' . ($semMod ? 'as ofertas abertas das disciplinas ainda não aprovadas vêm marcadas.' : 'as ofertas do módulo vêm marcadas.') . '</span>' ?>
            </div>
          </div>
          <?php if (empty($p['grade_id'])) : ?>
            <div class="card-body"><div class="alert alert-warning mb-0">Esta matrícula do ano não tem grade (anterior à E3b). <?= $semMod ? 'Registre o aluno no ano' : 'Grave o módulo do aluno no ano' ?>, na ficha do aluno.</div></div>
          <?php else : ?>
            <form method="POST" action="<?= htmlspecialchars((string) (URLADM . 'portal-alunos/confirmar-enturmacao/' . (int) $p['id']), ENT_QUOTES, 'UTF-8') ?>">
              <div class="card-body table-responsive p-0">
                <table class="table table-sm mb-0">
                  <thead><tr><th style="width:40px"></th><th>Disciplina</th><?php if (!$semMod) : ?><th>Módulo da matriz</th><?php endif; ?><th>Turma</th><th>Polo</th><th>Situação</th></tr></thead>
                  <tbody>
                    <?php if (!$p['ofertas']) : ?><tr><td colspan="<?= $semMod ? 5 : 6 ?>" class="text-muted">Não há oferta deste curso e desta grade em <?= htmlspecialchars((string) ((int) $p['ano']), ENT_QUOTES, 'UTF-8') ?>. Gere a oferta do ano a partir da matriz.</td></tr><?php endif; ?>
                    <?php foreach ($p['ofertas'] as $o) : ?>
                      <tr class="ofertaEnturmacao" data-oferta="<?= htmlspecialchars((string) ((int) $o['id']), ENT_QUOTES, 'UTF-8') ?>">
                        <td><input type="checkbox" name="ofertas[]" value="<?= htmlspecialchars((string) ((int) $o['id']), ENT_QUOTES, 'UTF-8') ?>" id="of<?= htmlspecialchars((string) ((int) $p['id']), ENT_QUOTES, 'UTF-8') ?>_<?= htmlspecialchars((string) ((int) $o['id']), ENT_QUOTES, 'UTF-8') ?>" <?= htmlspecialchars((string) ($o['marcada'] ? 'checked' : ''), ENT_QUOTES, 'UTF-8') ?><?= htmlspecialchars((string) (!empty($o['encerrada']) ? ' disabled title="Oferta encerrada — o administrador precisa reabrir"' : ''), ENT_QUOTES, 'UTF-8') ?>></td>
                        <td><label class="form-label" for="of<?= htmlspecialchars((string) ((int) $p['id']), ENT_QUOTES, 'UTF-8') ?>_<?= htmlspecialchars((string) ((int) $o['id']), ENT_QUOTES, 'UTF-8') ?>" class="mb-0 fw-normal"><?= $h($o['materia']) ?></label></td>
                        <?php if (!$semMod) : ?><td><?= $h($o['modulo'] ?? '— (sem matriz)') ?></td><?php endif; ?>
                        <td><?= $h($o['codtur']) ?></td>
                        <td><?= $h($o['polo'] ?? '—') ?></td>
                        <td>
                          <?= $o['enturmado'] ? '<span class="badge bg-success-lt">enturmado</span>' : '' ?>
                          <?= !empty($o['encerrada']) ? '<span class="badge bg-secondary-lt" title="Oferta encerrada — o administrador precisa reabrir">encerrada</span>' : '' ?>
                          <?= $o['sugerida'] ? '<span class="badge bg-info-lt">' . ($semMod ? 'sugerida' : 'sugerida (módulo do aluno)') . '</span>' : '' ?>
                          <?= $semMod && !empty($o['aprovadaAntes']) ? '<span class="badge bg-light text-dark" title="O aluno já foi aprovado nesta disciplina em outra oferta">já aprovada</span>' : '' ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
              <?php if ($p['ofertas'] && !empty($bt['confirmar'])) : ?>
                <div class="card-footer"><button type="submit" class="btn btn-primary">Confirmar enturmação</button>
                  <small class="text-muted ms-2">Grava exatamente as marcadas. Disciplina com nota, falta ou observação não sai.</small></div>
              <?php endif; ?>
            </form>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
</div>
<?php
/* Onda 6, Etapa 4: a enturmação confirmada vai ao Moodle em passos, nesta tela (o envio automático) */
foreach ($d['posicoes'] ?? [] as $p) {
    if (!empty($_SESSION['moodle.envio'][(int) $p['id']])) {
        $progressoId = 'progressoMoodleEnturmacao';
        $progressoTitulo = 'Enviando a enturmação ao Moodle';
        $progressoGrupos = ['As disciplinas marcadas, uma a uma' => max(1, (int) $_SESSION['moodle.envio'][(int) $p['id']]['total'])];
        $progressoUrl = URLADM . 'portal-alunos/confirmar-enturmacao/' . (int) $p['id'];
        $progressoFim = ['Fechar', '#'];
        include 'app/adms/Views/include_tabler/_progresso.php';
        echo '<button type="button" hidden id="enviarMoodleEnturmacao" data-progresso="progressoMoodleEnturmacao" data-fase="passo"></button>';
        echo "<script>document.addEventListener('DOMContentLoaded', function () { document.getElementById('enviarMoodleEnturmacao').click(); });</script>";
        break;
    }
}
?>
