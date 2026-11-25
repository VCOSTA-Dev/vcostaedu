<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* E6a: prévia técnica do histórico — o objeto da fonte única, organizado, sem layout de documento. */
use App\adms\Models\notas\AdmsCalculoNotas as Calc;

$h = fn($t) => htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
$d = fn($v) => $v ? date('d/m/Y', strtotime($v)) : '—';
$vida = $this->dados['vida'];
$fmtMedia = fn($t) => $t['media'] === null ? '—' : Calc::formatar($t['media'], (int) $t['casas']) . ($t['parcial'] && !$t['encerrada'] ? ' (parcial)' : '');
$fmtFreq = fn($t) => $t['frequencia'] === null ? 'não informada' : Calc::formatar($t['frequencia'], 2) . '%';
$cor = ['Aprovado' => 'success', 'Reprovado' => 'danger', 'Em curso' => 'secondary', 'Não cursada' => 'light'];
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Prévia do histórico <small class="text-muted">conferência dos dados</small><?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <form method="GET" action="<?= URLADM . 'historico/previa' ?>" class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <input type="text" name="aluno" class="form-control me-2" placeholder="RA ou nome do aluno" value="<?= $h($this->dados['busca']) ?>" style="min-width:320px">
        <button type="submit" class="btn btn-primary">Buscar</button>
      </form>
      <?php if ($this->dados['matriculas']) : ?>
        <div class="card shadow"><div class="card-body p-0"><table class="table table-sm mb-0" id="matriculasPrevia">
          <thead><tr><th>RA</th><th>Aluno</th><th>Curso</th><th>Situação</th><th></th></tr></thead><tbody>
          <?php foreach ($this->dados['matriculas'] as $m) : ?>
            <tr class="<?= htmlspecialchars((string) ((int) $m['id'] === (int) $this->dados['mcId'] ? 'table-primary' : ''), ENT_QUOTES, 'UTF-8') ?>"><td><?= $h($m['rm']) ?></td><td><?= $h($m['nome']) ?></td><td><?= $h($m['curso']) ?> — grade <?= htmlspecialchars((string) ((int) $m['versao']), ENT_QUOTES, 'UTF-8') ?></td><td><?= $h($m['situacao']) ?></td>
              <td class="text-end"><a href="<?= htmlspecialchars((string) (URLADM . 'historico/previa/' . (int) $m['id']), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-primary">Ver prévia</a></td></tr>
          <?php endforeach; ?>
          </tbody></table></div></div>
      <?php elseif ($this->dados['busca'] !== '') : ?>
        <div class="alert alert-info">Nenhuma matrícula encontrada.</div>
      <?php endif; ?>

      <?php if ($vida) : $a = $vida['aluno']; $m = $vida['matricula']; $i = $vida['instituicao']; $t = $vida['totais']; ?>
        <div id="previa" data-matricula="<?= htmlspecialchars((string) ((int) $m['id']), ENT_QUOTES, 'UTF-8') ?>">
          <?php /* E8c (A16/A17): a conferência vira documento — PDF parcial/final com a tarja de prévia, imprimir e ficha do aluno */
          $bt = $this->dados['botao'] ?? []; $mp = $this->dados['modelosPrevia'] ?? []; $Ui = \App\adms\Models\helper\AdmsUi::class; ?>
          <div class="acoes-linha mb-3 no-print" id="barraPrevia">
            <?php foreach (['PARCIAL' => 'parcial', 'FINAL' => 'final'] as $tp => $rotTp) : if (empty($bt['pdf'])) { break; } ?>
              <?= $Ui::acao('pdf', URLADM . 'historico-emissoes/previa/' . (int) $m['id'] . '?tipo=' . $tp . '&modelo=' . (int) ($mp[$tp] ?? 0), "Prévia em PDF do histórico {$rotTp}, com a tarja PRÉVIA (nada é gravado)",
                empty($mp[$tp]) ? "Não há modelo {$rotTp} ativo para este curso (Modelos de histórico)" : null, ['rotulo' => "Prévia em PDF — {$rotTp}", 'nova_aba' => true, 'classe' => 'btn-outline-danger']) ?>
            <?php endforeach; ?>
            <button type="button" class="btn btn-sm btn-outline-dark" onclick="window.print()" title="Imprimir esta conferência (a página como está)"><i class="fas fa-print"></i> Imprimir esta conferência</button>
            <?php if (!empty($bt['ficha'])) : ?>
              <?= $Ui::acao('ver', URLADM . 'academicos/visualizar/' . (int) $a['id'] . '?tab=tabeMatricula', 'Abrir a ficha do aluno (dados pessoais, matrícula, TCC)', null, ['rotulo' => 'Ir para a ficha do aluno', 'icone' => 'fas fa-id-card']) ?>
            <?php endif; ?>
            <?php if (!empty($bt['emitir'])) : ?>
              <?= $Ui::acao('acao', URLADM . 'historico-emissoes/emitir/' . (int) $m['id'] . '?tipo=PARCIAL', 'Ir para a emissão do histórico', null, ['rotulo' => 'Emitir histórico', 'icone' => 'fas fa-scroll', 'classe' => 'btn-outline-primary']) ?>
            <?php endif; ?>
          </div>
          <?php if (!empty($this->dados['antesDeReabertura'])) : /* 1.14.0 (D-415) */ ?>
            <div class="alert alert-warning" id="avisoReabertura"><b><?= $h(\App\adms\Models\historico\AdmsHistoricoEmissao::AVISO_REABERTURA) ?></b> Emitido(s) antes: <?= $h(implode(', ', array_map(fn($x) => $x['tipo'] . ' ' . $x['codigo'] . ' (' . date('d/m/Y', strtotime($x['emitido_em'])) . ')', $this->dados['antesDeReabertura']))) ?>. O documento emitido continua válido na verificação.</div>
          <?php endif; ?>
          <?php if ($vida['alertas']) : ?>
            <div class="alert alert-warning" id="alertasPrevia"><b><?= htmlspecialchars((string) (count($vida['alertas'])), ENT_QUOTES, 'UTF-8') ?> alerta(s):</b><ul class="mb-0"><?php foreach ($vida['alertas'] as $al) : ?><li><?= $h($al) ?></li><?php endforeach; ?></ul></div>
          <?php else : ?>
            <div class="alert alert-success" id="alertasPrevia">Nenhum alerta: os dados estão completos para o histórico.</div>
          <?php endif; ?>
          <div class="row">
            <div class="col-md-6"><div class="card shadow h-100"><div class="card-header"><h3 class="card-title">Aluno</h3></div><div class="card-body p-0"><table class="table table-sm mb-0">
              <tr><th>Nome</th><td><?= $h($a['nome']) ?><?= $a['nomeSocial'] ? ' <small>(nome social: ' . $h($a['nomeSocial']) . ')</small>' : '' ?></td></tr>
              <tr><th>RA</th><td><?= $h($a['rm']) ?></td></tr>
              <tr><th>Nascimento</th><td><?= $d($a['nascimento']) ?></td></tr>
              <tr><th>Naturalidade / nacionalidade</th><td><?= $h($a['naturalidade'] ?? '—') ?> / <?= $h($a['nacionalidade'] ?? '—') ?></td></tr>
              <tr><th>RG / CPF</th><td><?= $h($a['rg'] ?? '—') ?> <?= $a['rgOrgao'] ? '(' . $h($a['rgOrgao']) . ')' : '' ?> / <?= $h($a['cpf'] ?? '—') ?></td></tr>
              <tr><th>Filiação</th><td><?= $h(implode(' e ', array_filter([$a['mae'], $a['pai']])) ?: '—') ?></td></tr>
            </table></div></div></div>
            <div class="col-md-6"><div class="card shadow h-100"><div class="card-header"><h3 class="card-title">Matrícula, instituição e ato legal</h3></div><div class="card-body p-0"><table class="table table-sm mb-0">
              <tr><th>Curso</th><td><?= $h($m['curso']) ?> — grade <?= htmlspecialchars((string) ((int) $m['grade']), ENT_QUOTES, 'UTF-8') ?> <?= $m['exigeTcc'] ? '<span class="badge bg-info-lt">exige TCC</span>' : '' ?></td></tr>
              <tr><th>Polo / modalidade</th><td id="poloModalidade"><?= $h($m['polo'] ?? '—') ?><?= $m['poloDoCurso'] ? ' <small>(do curso)</small>' : '' ?> / <?= $h($m['modalidade'] ?? '—') ?><?= $m['modalidadeDoCurso'] ? ' <small>(do curso)</small>' : '' ?></td></tr>
              <tr><th>Ingresso</th><td><?= $h($m['formaIngresso']) ?> em <?= $d($m['dataIngresso']) ?></td></tr>
              <tr><th>Situação</th><td><?= $h($m['situacao']) ?><?= $m['dataConclusao'] ? ' em ' . $d($m['dataConclusao']) : '' ?></td></tr>
              <tr><th>Instituição</th><td><?= $h($i['nome']) ?><?= $i['mantenedora'] ? ' — mantida por ' . $h($i['mantenedora']) : '' ?><?= $i['cnpj'] ? ' — CNPJ ' . $h($i['cnpj']) : '' ?><?= $i['codigoEmec'] ? ' — e-MEC ' . $h($i['codigoEmec']) : '' ?></td></tr>
              <tr><th>Ato legal</th><td id="atoLegal"><?= $vida['atoLegal']['descricao'] ? $h($vida['atoLegal']['descricao']) : '<span class="text-danger">' . $h($vida['atoLegal']['alerta']) . '</span>' ?> <small class="text-muted">(referência <?= $d($vida['atoLegal']['dataReferencia']) ?>)</small></td></tr>
            </table></div></div></div>
          </div>

          <?php
          /* E8c (D-268): sem módulos, UMA lista com as disciplinas da grade (sem CH por módulo) */
          $semMod = \App\adms\Models\helper\AdmsModuloInterno::ativo();
          $grupos = $vida['modulos'];
          if ($semMod) {
            $todas = [];
            foreach ($vida['modulos'] as $mm) {
              $todas = array_merge($todas, $mm['disciplinas']);
            }
            $grupos = [['nome' => 'Disciplinas da grade', 'ch' => (int) $t['chExigida'], 'disciplinas' => $todas]];
          }
          ?>
          <?php foreach ($grupos as $mod) : ?>
            <div class="card shadow mt-3 <?= $semMod ? 'disciplinasGrade' : 'modulo' ?>"><div class="card-header"><h3 class="card-title"><b><?= $h($mod['nome']) ?></b> <small class="text-muted"><?= htmlspecialchars((string) ((int) $mod['ch']), ENT_QUOTES, 'UTF-8') ?> h</small></h3></div>
              <div class="card-body table-responsive p-0"><table class="table table-sm mb-0">
                <thead><tr><th>Código</th><th>Disciplina</th><th class="text-center">CH</th><th>Tentativa</th><th>Realização</th><th>Docente</th><th class="text-center">Média</th><th class="text-center">Frequência</th><th>Resultado</th><th>Situação</th></tr></thead>
                <tbody>
                <?php foreach ($mod['disciplinas'] as $dsc) : $n = max(1, count($dsc['tentativas'])); ?>
                  <?php if (!$dsc['tentativas']) : ?>
                    <tr class="disciplina" data-linha="<?= htmlspecialchars((string) ((int) $dsc['linha_id']), ENT_QUOTES, 'UTF-8') ?>"><td><?= $h($dsc['codigo']) ?></td><td><?= $h($dsc['nome']) ?></td><td class="text-center"><?= htmlspecialchars((string) ((int) $dsc['ch']), ENT_QUOTES, 'UTF-8') ?></td><td colspan="6" class="text-muted">—</td><td><span class="badge bg-light text-dark">Não cursada</span></td></tr>
                  <?php endif; ?>
                  <?php foreach ($dsc['tentativas'] as $k => $tt) : ?>
                    <tr class="tentativa<?= htmlspecialchars((string) ($tt['vigente'] ? ' fw-bold' : ' text-muted'), ENT_QUOTES, 'UTF-8') ?>" data-oferta="<?= htmlspecialchars((string) ((int) $tt['oferta_id']), ENT_QUOTES, 'UTF-8') ?>" data-vigente="<?= htmlspecialchars((string) ($tt['vigente'] ? 1 : 0), ENT_QUOTES, 'UTF-8') ?>">
                      <?php if ($k === 0) : ?><td rowspan="<?= htmlspecialchars((string) ($n), ENT_QUOTES, 'UTF-8') ?>"><?= $h($dsc['codigo']) ?></td><td rowspan="<?= htmlspecialchars((string) ($n), ENT_QUOTES, 'UTF-8') ?>"><?= $h($dsc['nome']) ?></td><td rowspan="<?= htmlspecialchars((string) ($n), ENT_QUOTES, 'UTF-8') ?>" class="text-center"><?= htmlspecialchars((string) ((int) $dsc['ch']), ENT_QUOTES, 'UTF-8') ?></td><?php endif; ?>
                      <td><?= $h($tt['turma']) ?>/<?= htmlspecialchars((string) ((int) $tt['ano']), ENT_QUOTES, 'UTF-8') ?><?= $tt['vigente'] ? ' <span class="badge bg-primary-lt">vigente</span>' : '' ?></td>
                      <td><?= $d($tt['periodoInicio']) ?> a <?= $d($tt['periodoFim']) ?><?= $tt['periodoOrigem'] === 'atividades' ? ' <small>(atividades)</small>' : '' ?></td>
                      <td><?= $tt['docentes'] ? $h(implode('; ', array_map(fn($x) => $x['nome'] . ($x['titulacao'] ? ' (' . $x['titulacao'] . ')' : ''), $tt['docentes']))) : '<span class="text-danger">sem docente</span>' ?></td>
                      <td class="text-center media"><?= $h($fmtMedia($tt)) ?></td>
                      <td class="text-center frequencia"><?= $h($fmtFreq($tt)) ?></td>
                      <td class="resultado"><?= $h($tt['rotulo']) ?></td>
                      <?php if ($k === 0) : ?><td rowspan="<?= htmlspecialchars((string) ($n), ENT_QUOTES, 'UTF-8') ?>" class="situacao"><span class="badge bg-<?= htmlspecialchars((string) ($cor[$dsc['situacao']]), ENT_QUOTES, 'UTF-8') ?>-lt"><?= $h($dsc['situacao']) ?></span></td><?php endif; ?>
                    </tr>
                    <?php if ($tt['atividades']) : ?>
                      <tr class="atividades"><td colspan="10" class="small text-muted ps-5">Atividades (modelo parcial): <?= $h(implode(' · ', array_map(fn($x) => $x['nome'] . ' [' . $x['tipo'] . ', ' . ($x['data'] ? date('d/m/Y', strtotime($x['data'])) : 's/d') . ']: ' . ($x['notaFormatada'] ?? '—'), $tt['atividades']))) ?></td></tr>
                    <?php endif; ?>
                  <?php endforeach; ?>
                <?php endforeach; ?>
                </tbody>
              </table></div></div>
          <?php endforeach; ?>

          <div class="row mt-3">
            <div class="col-md-6"><div class="card shadow h-100"><div class="card-header"><h3 class="card-title">TCC</h3></div><div class="card-body" id="tccPrevia">
              <?php if ($vida['tcc']) : $c = $vida['tcc']; ?>
                <b><?= $h($c['titulo']) ?></b><br>Orientador: <?= $h($c['orientador'] ?? '—') ?><?= $c['orientadorTitulacao'] ? ' (' . $h($c['orientadorTitulacao']) . ')' : '' ?> · Nota: <?= $h($c['notaFormatada'] ?? '—') ?> · <?= $d($c['data_defesa']) ?> · <b><?= $h($c['situacaoNome']) ?></b>
              <?php else : ?><span class="text-muted">Sem TCC cadastrado.</span><?php endif; ?>
            </div></div></div>
            <div class="col-md-6"><div class="card shadow h-100"><div class="card-header"><h3 class="card-title">Totais</h3></div><div class="card-body p-0"><table class="table table-sm mb-0" id="totaisPrevia">
              <tr><th>CH exigida (grade)</th><td><?= htmlspecialchars((string) ((int) $t['chExigida']), ENT_QUOTES, 'UTF-8') ?> h</td></tr>
              <tr><th>CH integralizada (aprovadas)</th><td><?= htmlspecialchars((string) ((int) $t['chIntegralizada']), ENT_QUOTES, 'UTF-8') ?> h (<?= htmlspecialchars((string) ((int) $t['aprovadas']), ENT_QUOTES, 'UTF-8') ?> de <?= htmlspecialchars((string) ((int) $t['disciplinas']), ENT_QUOTES, 'UTF-8') ?> disciplinas)</td></tr>
              <tr><th>Período de realização</th><td><?= $d($t['periodoInicio']) ?> a <?= $d($t['periodoFim']) ?></td></tr>
              <tr><th>Duração total</th><td><?= htmlspecialchars((string) ($t['duracaoMeses'] !== null ? (int) $t['duracaoMeses'] . ' mes(es)' : '—'), ENT_QUOTES, 'UTF-8') ?></td></tr>
            </table></div></div></div>
          </div>
        </div>
      <?php elseif ($this->dados['mcId'] > 0) : ?>
        <div class="alert alert-danger">Matrícula não encontrada.</div>
      <?php endif; ?>
    </div>
  </section>
</div>
