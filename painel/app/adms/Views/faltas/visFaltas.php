<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* E4: faltas da oferta em etapa única. E8c: barra da disciplina (R5), lista padrão (R2), compensação com tipo,
   justificativa e período (D-264, D-265: só secretaria e administrador; o professor vê só o número). */
use App\adms\Models\notas\AdmsCalculoNotas as Calc;
use App\adms\Models\helper\AdmsUi as Ui;
use App\adms\Models\faltas\AdmsCompensacoes as Comp;

$c = $this->dados['calc'];
$o = $c['oferta'];
$escF = \App\adms\Models\helper\AdmsTipoInstituicao::ehEscola(); // D-125 (1.102.1, C3.7): na escola, disciplina e aula (não oferta e encontro)
$f = $this->dados['ficha'] ?? [];
$bt = $this->dados['botao'] ?? [];
$sit = $this->dados['situacoes'] ?? [];
$h = fn($t) => htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
$encerrada = !empty($o['encerrada_em']);
$porChamada = ($o['frequencia_fonte'] ?? 'lancamento') === 'chamada'; // 1.14.0 (D-405)
$edita = !$encerrada && !$porChamada && !empty($bt['edit_falta']);
$podeComp = !empty($this->dados['podeCompensar']);
$freqMin = (float) $c['regras']['frequenciaMinima'];
$razaoComp = $encerrada ? 'Disciplina encerrada: não aceita compensação nova' : (!$podeComp ? 'Só a secretaria e o administrador lançam compensação' : null);
$compPorRm = [];
foreach ($this->dados['compensacoes'] ?? [] as $cp) { $compPorRm[$cp['rm']][] = $cp; }
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Faltas — <?= $h($o['materia']) ?> <small class="text-muted"><?= $h($o['codtur']) ?> · <?= htmlspecialchars((string) ((int) $o['ano']), ENT_QUOTES, 'UTF-8') ?></small><?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?= Ui::barraDisciplina((int) $o['id'], 'faltas', '', $this->dados['barra'] ?? []) ?>
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <?php if ($encerrada) : ?>
        <?= Ui::faixaTrava('Disciplina encerrada em ' . date('d/m/Y', strtotime($o['encerrada_em'])) . ': as faltas e as compensações estão em modo leitura.',
          'Para mudar, o administrador ou a supervisão da secretaria reabre a disciplina (com justificativa) na tela Encerramento.', \App\adms\Models\helper\AdmsNivel::podeSensivel() ? ['Ir ao Encerramento', URLADM . 'disciplinas/encerrar/' . (int) $o['id']] : null, 'situacaoFaltas') ?>
      <?php else : ?>
        <?php if ($porChamada) : ?>
          <div class="alert alert-warning py-2" id="faltasDaChamada"><?= $escF ? '<b>As faltas desta disciplina vêm da chamada</b> (só leitura aqui): cada ausência numa aula é uma falta, e o total de aulas é o de aulas registradas.' : '<b>As faltas desta oferta vêm da chamada</b> (só leitura aqui): cada ausência num encontro é uma falta, e o total de encontros é o de encontros registrados.' ?> <a href="<?= htmlspecialchars((string) (URLADM . 'chamada/listar/' . (int) $o['id']), ENT_QUOTES, 'UTF-8') ?>">Ir à chamada</a>. A compensação continua valendo.</div>
        <?php endif; ?>
        <div class="alert alert-info py-2" id="situacaoFaltas">
          Frequência = 1 − (faltas − compensadas) ÷ total de <?= $escF ? 'aulas' : 'encontros' ?>. Mínimo: <b><?= $h(Calc::numero($c['regras']['frequenciaMinima'])) ?>%</b>.
          <b>Total de <?= $escF ? 'aulas' : 'encontros' ?>:</b> <?= $o['encontros'] !== null ? (int) $o['encontros'] : '<span class="text-danger">não informado</span> (informe na edição da disciplina; sem ele a frequência não é calculada e o encerramento é recusado)' ?>.
          <?= $podeComp ? 'Compensação: pelo botão <i class="fas fa-notes-medical"></i> de cada aluno, com tipo, justificativa e período.' : 'As compensações são lançadas pela secretaria.' ?>
        </div>
      <?php endif; ?>
      <form method="POST" action="<?= htmlspecialchars((string) (URLADM . 'faltas/editar/' . (int) $o['id']), ENT_QUOTES, 'UTF-8') ?>">
        <div class="card shadow">
          <div class="card-body">
            <table class="table table-sm table-striped table-hover table-compacta lista-dados" id="tabelaFaltas" data-titulo="Faltas — <?= $h($o['materia']) ?> (<?= $h($o['codtur']) ?>)" data-paginar="nao">
              <thead><tr><th class="num">Nº</th><th><?= \App\adms\Models\helper\AdmsTipoInstituicao::rotuloNumero() ?></th><th>Aluno</th><th class="num">Faltas</th><th class="num">Compensadas</th><th class="num"><?= \App\adms\Models\helper\AdmsTipoInstituicao::ehEscola() ? 'Aulas' : 'Encontros' ?></th><th class="num">Frequência</th><th>Situação</th><th class="nao-exportar nao-ordenar text-end">Ações</th></tr></thead>
              <tbody>
                <?php $i = 0; foreach ($c['alunos'] as $al) : $i++; $abaixo = $al['frequencia'] !== null && (float) $al['frequencia'] < $freqMin; ?>
                  <tr data-rm="<?= $h($al['rm']) ?>">
                    <td class="num"><?= htmlspecialchars((string) ($i), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= $h($al['rm']) ?></td>
                    <td><?= $h($al['nome']) ?><?= isset($sit[$al['rm']]) && $sit[$al['rm']] !== 'Cursando' ? ' ' . Ui::etiqueta($sit[$al['rm']]) : '' ?></td>
                    <td class="num" data-order="<?= htmlspecialchars((string) ((int) $al['faltas']), ENT_QUOTES, 'UTF-8') ?>"><?php if ($edita) : ?><input type="number" min="<?= htmlspecialchars((string) ((int) $al['compensadas']), ENT_QUOTES, 'UTF-8') ?>" name="faltas[<?= $h($al['rm']) ?>]" value="<?= htmlspecialchars((string) ((int) $al['faltas']), ENT_QUOTES, 'UTF-8') ?>" class="form-control form-control-sm text-end d-inline-block" style="width: 5.5rem" aria-label="Faltas de <?= $h($al['nome']) ?>"><?php else : ?><?= htmlspecialchars((string) ((int) $al['faltas']), ENT_QUOTES, 'UTF-8') ?><?php endif; ?></td>
                    <td class="num compensadas"><?= htmlspecialchars((string) ((int) $al['compensadas']), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="num"><?= htmlspecialchars((string) ($o['encontros'] !== null ? (int) $o['encontros'] : '—'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="num frequencia<?= $abaixo ? ' abaixo' : '' ?>"><?= $al['frequencia'] === null ? '<span class="text-muted">—</span>' : $h(Calc::formatar($al['frequencia'], 2)) . '%' ?></td>
                    <td><?= $al['frequencia'] === null ? ($escF ? Ui::etiqueta('Sem aulas', ['Sem aulas' => 'secondary']) : Ui::etiqueta('Sem encontros', ['Sem encontros' => 'secondary'])) : ($abaixo ? Ui::etiqueta('Abaixo do mínimo', ['Abaixo do mínimo' => 'danger']) : Ui::etiqueta('Frequência ok', ['Frequência ok' => 'success'])) ?></td>
                    <td class="acoes-linha">
                      <?php if ($razaoComp === null) : ?>
                        <button type="button" class="btn btn-sm btn-acao btn-outline-info" title="Compensar faltas de <?= $h($al['nome']) ?>" data-bs-toggle="modal" data-bs-target="#modalCompensar" data-rm="<?= $h($al['rm']) ?>" data-nome="<?= $h($al['nome']) ?>" data-max="<?= htmlspecialchars((string) (max(0, (int) $al['faltas'] - (int) $al['compensadas'])), ENT_QUOTES, 'UTF-8') ?>"><i class="fas fa-notes-medical"></i></button>
                      <?php else : ?>
                        <?= Ui::acao('compensar', '#', null, $razaoComp) ?>
                      <?php endif; ?>
                      <?php if ($podeComp) : ?>
                        <?= !empty($compPorRm[$al['rm']]) ? '<a href="#historicoCompensacoes" class="btn btn-sm btn-acao btn-outline-secondary" title="Ver as compensações lançadas deste aluno"><i class="fas fa-clipboard-list"></i> ' . count($compPorRm[$al['rm']]) . '</a>' : Ui::acao('acao', '#', null, 'Nenhuma compensação lançada para este aluno', ['icone' => 'fas fa-clipboard-list']) ?>
                      <?php endif; ?>
                      <?= !empty($bt['boletim']) ? Ui::acao('boletim', URLADM . 'academicos/boletim/' . rawurlencode($al['rm']) . '?ano=' . (int) $o['ano'], 'Ver boletim do aluno: ' . $al['nome'], null, ['nova_aba' => true]) : '' ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            <?php if (!$c['alunos']) : ?><p class="text-muted mb-0">Nenhum aluno enturmado. Enture os alunos pela ficha de cada um (aba Matrículas → Enturmação).</p><?php endif; ?>
          </div>
          <?php if ($edita && $c['alunos']) : ?>
            <div class="card-footer"><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar faltas</button> <small class="text-muted ms-2">As compensadas não se editam aqui: use o botão <i class="fas fa-notes-medical"></i> (secretaria e administrador).</small></div>
          <?php endif; ?>
        </div>
      </form>

      <?php if ($podeComp) : ?>
        <div class="card shadow" id="historicoCompensacoes">
          <div class="card-header"><h3 class="card-title"><i class="fas fa-history"></i> Compensações lançadas</h3>
            <div class="card-actions"><small class="text-muted">Visível só à secretaria e ao administrador. O aluno vê só a quantidade no boletim.</small></div></div>
          <div class="card-body">
            <table class="table table-sm table-striped table-hover table-compacta lista-dados" id="tabelaCompensacoes" data-titulo="Compensações — <?= $h($o['materia']) ?> (<?= $h($o['codtur']) ?>)" data-ordem='[[6,"desc"]]'>
              <thead><tr><th>Aluno</th><th class="num">Qtde.</th><th>Tipo</th><th>Período</th><th>Justificativa</th><th>Lançado por</th><th>Em</th><th class="nao-exportar nao-ordenar text-end">Ações</th></tr></thead>
              <tbody>
                <?php foreach ($this->dados['compensacoes'] as $cp) : ?>
                  <tr>
                    <td><?= $h($cp['aluno'] ?: $cp['rm']) ?> <small class="text-muted"><?= $h($cp['rm']) ?></small></td>
                    <td class="num"><?= htmlspecialchars((string) ((int) $cp['quantidade']), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= $h(Comp::TIPOS[$cp['tipo']] ?? $cp['tipo']) ?></td>
                    <td data-order="<?= $h($cp['inicio']) ?>"><?= $h(date('d/m/Y', strtotime($cp['inicio']))) ?> a <?= $h(date('d/m/Y', strtotime($cp['fim']))) ?></td>
                    <td><?= nl2br($h($cp['justificativa'])) ?></td>
                    <td><?= $h($cp['usuario']) ?></td>
                    <td data-order="<?= $h($cp['created']) ?>"><?= $h(date('d/m/Y H:i', strtotime($cp['created']))) ?></td>
                    <td class="acoes-linha"><?= Ui::acaoPost('excluir', URLADM . 'faltas/remover-compensacao/' . (int) $o['id'], ['id' => (int) $cp['id']], 'Remover este lançamento', $encerrada ? 'Disciplina encerrada: as compensações não mudam' : null, ['confirmar' => 'Remover este lançamento de compensação? O total do aluno será recalculado.']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endif; ?>
      <?php /* 1.14.0 (E8, D-411): importar faltas por planilha rm;faltas */ $razaoImp = $encerrada ? 'Disciplina encerrada: não aceita importação' : ($porChamada ? ($escF ? 'As faltas desta disciplina vêm da chamada' : 'As faltas desta oferta vêm da chamada') : (empty($bt['importar']) ? 'Esta tela não é do seu perfil' : null)); ?>
      <div class="card shadow-sm" id="importarFaltas">
        <div class="card-header"><b><i class="fas fa-file-csv"></i> Importar faltas por planilha</b> <small class="text-muted">— CSV com <code>rm;faltas</code>, separado por ponto e vírgula; tudo ou nada</small></div>
        <div class="card-body">
          <?php if ($razaoImp === null) : ?>
            <form method="POST" action="<?= htmlspecialchars((string) (URLADM . 'faltas/importar/' . (int) $o['id']), ENT_QUOTES, 'UTF-8') ?>" enctype="multipart/form-data" class="d-flex flex-wrap align-items-center gap-2">
              <input type="file" name="arquivo" accept=".csv,text/csv" class="form-control me-2" required>
              <button type="submit" class="btn btn-sm btn-primary me-3" id="botaoImportarFaltas"><i class="fas fa-upload"></i> Importar</button>
              <a href="<?= htmlspecialchars((string) (URLADM . 'faltas/modelo/' . (int) $o['id'] . '?tipo=faltas'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-secondary" id="modeloFaltas"><i class="fas fa-download"></i> Baixar o modelo</a>
            </form>
            <small class="text-muted">Uma linha por aluno: o <?= \App\adms\Models\helper\AdmsTipoInstituicao::rotuloNumero() ?> e o número de faltas (0 ou mais, até o total de <?= \App\adms\Models\helper\AdmsTipoInstituicao::ehEscola() ? 'aulas' : 'encontros' ?>). Se alguma linha estiver errada, nada é gravado e a tela mostra a linha.</small>
          <?php else : ?><p class="text-muted mb-0" id="importarFaltasRazao"><?= $h($razaoImp) ?>.</p><?php endif; ?>
        </div>
      </div>
    </div>
  </section>
</div>
<?php if ($razaoComp === null) : ?>
<div class="modal fade" id="modalCompensar" tabindex="-1" role="dialog" aria-labelledby="tituloCompensar" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form method="POST" action="<?= htmlspecialchars((string) (URLADM . 'faltas/compensar/' . (int) $o['id']), ENT_QUOTES, 'UTF-8') ?>" class="modal-content" id="formCompensar">
      <div class="modal-header"><h5 class="modal-title" id="tituloCompensar"><i class="fas fa-notes-medical"></i> Compensar faltas</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button></div>
      <div class="modal-body">
        <input type="hidden" name="rm" id="compRm">
        <p class="mb-2">Aluno: <b id="compNome"></b> <small class="text-muted">(pode compensar até <span id="compMax"></span>)</small></p>
        <div class="row g-2">
          <div class="mb-3 col-4"><label class="form-label" for="compQtd">Quantidade</label><input type="number" min="1" name="quantidade" id="compQtd" class="form-control" required></div>
          <div class="mb-3 col-8"><label class="form-label" for="compTipo">Tipo</label>
            <select name="tipo" id="compTipo" class="form-select" required><option value="">Escolha…</option><?php foreach (Comp::TIPOS as $k => $v) : ?><option value="<?= $h($k) ?>"><?= $h($v) ?></option><?php endforeach; ?></select></div>
        </div>
        <div class="row g-2">
          <div class="mb-3 col-6"><label class="form-label" for="compIni">Início do período</label><input type="date" name="inicio" id="compIni" class="form-control" required></div>
          <div class="mb-3 col-6"><label class="form-label" for="compFim">Fim do período</label><input type="date" name="fim" id="compFim" class="form-control" required></div>
        </div>
        <div class="mb-3"><label class="form-label" for="compJust">Justificativa (obrigatória)</label><textarea name="justificativa" id="compJust" class="form-control" rows="3" minlength="5" required placeholder="Ex.: atestado entregue na secretaria, dias X a Y."></textarea></div>
        <small class="text-muted">Não anexe atestado: o documento fica na secretaria (dado de saúde). O aluno vê só a quantidade compensada no boletim, nunca o motivo. Quem lançou e quando ficam registrados.</small>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-info"><i class="fas fa-check"></i> Lançar compensação</button></div>
    </form>
  </div>
</div>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    if (!window.jQuery) { return; }
    jQuery('#modalCompensar').on('show.bs.modal', function (ev) {
      var b = jQuery(ev.relatedTarget);
      jQuery('#compRm').val(b.data('rm')); jQuery('#compNome').text(b.data('nome')); jQuery('#compMax').text(b.data('max'));
      jQuery('#compQtd').attr('max', b.data('max')).val(b.data('max') > 0 ? 1 : '');
    });
  });
</script>
<?php endif; ?>
