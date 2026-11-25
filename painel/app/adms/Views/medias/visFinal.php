<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* E4: notas, frequência e resultado da oferta — do motor único (AdmsCalculoNotas).
   E8c (A12): barra da disciplina (R5), lista padrão com pesquisa e exportar (R2), Nº e RA, situação da matrícula,
   resultado em etiqueta, abaixo do mínimo em vermelho, ação "boletim", "Atualizar lista" de volta (resolve a D-133).
   Do sistema escolar de origem: "Processa Médias" → Encerramento (prévia); "Apagar Médias" → Reabrir (só administrador, com justificativa,
   na tela Encerramento); "Trocar Bimestre" → sai (etapa única, D-76). */
use App\adms\Models\notas\AdmsCalculoNotas as Calc;
use App\adms\Models\helper\AdmsUi as Ui;

$c = $this->dados['calc'];
$o = $c['oferta'];
$rg = $c['regras'];
$bt = $this->dados['botao'] ?? [];
$sit = $this->dados['situacoes'] ?? [];
$h = fn($t) => htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
$encerrada = !empty($o['encerrada_em']);
$casasDe = fn($a) => (int) ($a['casas'] ?? $rg['casas']);
$mediaMin = (float) $rg['mediaMinima'];
$freqMin = (float) $rg['frequenciaMinima'];
$ehAdmin = \App\adms\Models\helper\AdmsNivel::podeSensivel(); /* Onda 2: quem reabre (ação sensível) */

$extras = '';
if (!empty($bt['atualizar'])) {
  $extras .= Ui::acaoPost('acao', URLADM . 'medias/atualizar-lista/' . (int) $o['id'], [], 'Atualizar a lista: cria as linhas de nota dos alunos enturmados depois das atividades', $encerrada ? 'Disciplina encerrada: a lista não muda' : null, ['icone' => 'fas fa-rotate', 'rotulo' => 'Atualizar lista']);
}
/* E8d (A21): "Encerrar disciplina" e "Reabrir" saíram daqui — levavam à mesma tela que "Encerramento", na barra */
?>
<div class="vc-pagina">
  <?php ob_start(); ?>Notas e resultado — <?= $h($o['materia']) ?> <small class="text-muted"><?= $h($o['codtur']) ?> · <?= htmlspecialchars((string) ((int) $o['ano']), ENT_QUOTES, 'UTF-8') ?></small><?php $paginaTitulo = ob_get_clean(); include 'app/adms/Views/include_tabler/_page_header.php'; ?>
  <section class="page-body">
    <div class="container-xl">
      <?= Ui::barraDisciplina((int) $o['id'], 'notas', '', $this->dados['barra'] ?? [], $extras) ?>
      <?= \App\adms\Models\notas\AdmsRegraMedia::avisoLimitesHtml((int) $o['id']) /* E10 (D-423): falta cadastrar / acima do máximo */ ?>
      <?php if (isset($_SESSION['msg'])) { echo $_SESSION['msg']; unset($_SESSION['msg']); } ?>
      <?php if ($encerrada) : ?>
        <?= Ui::faixaTrava('Disciplina encerrada em ' . date('d/m/Y H:i', strtotime($o['encerrada_em'])) . ': média final, frequência e resultado são os GRAVADOS no encerramento.',
          $ehAdmin ? 'Para mudar notas ou faltas, reabra a disciplina (com justificativa) e encerre de novo.' : 'Para mudar notas ou faltas, peça ao administrador ou à supervisão da secretaria que reabra a disciplina.', $ehAdmin ? ['Reabrir no Encerramento', URLADM . 'disciplinas/encerrar/' . (int) $o['id']] : null, 'situacaoOferta') ?>
      <?php else : ?>
        <?php /* D-103 (C7): na escola, com todas as etapas fechadas e o ano ainda não encerrado, a frase diz isso */
          $etapasAno = \App\adms\Models\helper\AdmsTipoInstituicao::ehEscola() ? \App\adms\Models\notas\AdmsCalculoNotas::etapasDoAno((int) $o['ano']) : [];
          $todasFechadas = $etapasAno && !array_filter($etapasAno, static fn($et) => \App\adms\Models\escola\AdmsEtapaNotas::fechamento((int) $o['id'], (int) $et['id']) === null); ?>
        <?php if ($todasFechadas) : ?>
        <div class="alert alert-info py-2" id="situacaoOferta"><b>Todas as etapas estão fechadas.</b> Falta encerrar a disciplina (o fim do ano), na aba Encerramento: a média anual e a situação ficam guardadas.</div>
        <?php else : ?>
        <div class="alert alert-info py-2" id="situacaoOferta"><b>Disciplina aberta.</b> A média é <b>parcial</b>: só das atividades com nota lançada. No encerramento, atividade sem nota conta 0.</div>
        <?php endif; ?>
      <?php endif; ?>
      <div class="card shadow">
        <div class="card-body">
          <table class="table table-sm table-striped table-hover table-compacta lista-dados" id="tabelaNotas" data-titulo="Notas e resultado — <?= $h($o['materia']) ?> (<?= $h($o['codtur']) ?>)" data-linhas="50">
            <thead>
              <tr>
                <th class="num">Nº</th><th><?= \App\adms\Models\helper\AdmsTipoInstituicao::rotuloNumero() ?></th><th>Aluno</th>
                <?php foreach ($c['atividades'] as $a) : ?><th class="num th-dinamico" title="<?= $h($a['nomeAtividade']) ?>"><span class="th-duas-linhas"><?= $h($a['nomeAtividade']) /* E8d (A22): nome inteiro, em até 2 linhas, com a dica */ ?></span></th><?php endforeach; ?>
                <th class="num">Média</th><th class="num">Faltas</th><th class="num">Frequência</th><th>Resultado</th><th class="nao-exportar nao-ordenar text-end">Boletim</th>
              </tr>
            </thead>
            <tbody>
              <?php $i = 0; foreach ($c['alunos'] as $al) : $i++;
                $m = $al['media'] !== null ? (float) $al['media'] : null;
                $mAbaixo = $m !== null && $m < $mediaMin;
                $fAbaixo = $al['frequencia'] !== null && (float) $al['frequencia'] < $freqMin;
                $rot = Calc::rotuloResultado($al['resultado'], $al['motivo'], $al['motivosPendencia'] ?? []); /* E10 / 1.15.2: Pendente (motivos do motor) */
                $cor = $al['resultado'] === 1 ? 'success' : ($al['resultado'] === 2 ? 'danger' : (!empty($al['motivosPendencia']) ? 'warning' : 'info')); ?>
                <tr data-rm="<?= $h($al['rm']) ?>">
                  <td class="num"><?= htmlspecialchars((string) ($i), ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= $h($al['rm']) ?></td>
                  <td><?= $h($al['nome']) ?><?= isset($sit[$al['rm']]) && $sit[$al['rm']] !== 'Cursando' ? ' ' . Ui::etiqueta($sit[$al['rm']]) : '' ?></td>
                  <?php foreach ($al['notas'] as $n) : ?>
                    <td class="num<?= $n !== null && $n / 100 < $mediaMin ? ' abaixo' : '' ?>"><?= $n === null ? '<span class="text-muted">—</span>' : $h(Calc::formatar(number_format($n / 100, 2, '.', ''), $n % 10 ? 2 : 1)) ?></td>
                  <?php endforeach; ?>
                  <td class="num media<?= $mAbaixo ? ' abaixo' : '' ?>" title="<?= $h($al['conta']) ?>"><b><?= $h(Calc::formatar($al['media'], $casasDe($al))) ?></b><?= $al['parcial'] && $al['media'] !== null ? ' <span class="badge bg-light text-dark etiqueta-parcial">parcial</span>' : '' ?><?= $al['media'] !== null ? ' <a href="#memoria-' . $h($al['rm']) . '" class="small ver-conta no-print" data-rm="' . $h($al['rm']) . '" title="Ver a conta">conta</a>' : '' ?></td>
                  <td class="num"><?= htmlspecialchars((string) ((int) $al['faltas']), ENT_QUOTES, 'UTF-8') ?><?= $al['compensadas'] ? ' <small class="text-muted" title="compensadas">(−' . (int) $al['compensadas'] . ')</small>' : '' ?></td>
                  <td class="num frequencia<?= $fAbaixo ? ' abaixo' : '' ?>"><?= $al['frequencia'] === null ? '<span class="text-muted">—</span>' : $h(Calc::formatar($al['frequencia'], 2)) . '%' ?></td>
                  <td class="resultado"><span class="badge bg-<?= htmlspecialchars((string) ($cor), ENT_QUOTES, 'UTF-8') ?>-lt"><?= $h($rot) ?></span></td>
                  <td class="acoes-linha"><?= !empty($bt['boletim']) ? Ui::acao('boletim', URLADM . 'academicos/boletim/' . rawurlencode($al['rm']) . '?ano=' . (int) $o['ano'], 'Ver boletim do aluno: ' . $al['nome'], null, ['nova_aba' => true]) : '' ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php if (!$c['alunos']) : ?><p class="text-muted mb-0">Nenhum aluno enturmado.</p><?php endif; ?>
          <?php /* 1.13.0 (D-400): a MEMÓRIA DE CÁLCULO de cada aluno — a mesma do motor e do simulador; na oferta encerrada, a gravada */ $ref = $c['regraEfetiva'] ?? null; ?>
          <div class="mt-3" id="memoriasCalculo">
            <h6 class="mb-1">Como foi calculado <small class="text-muted">— regra de média <?= $ref ? '"' . $h($ref['nome'] ?? '') . '" (' . $h(\App\adms\Models\notas\AdmsRegraMedia::ORIGENS[$ref['origem']] ?? $ref['origem']) . ')' : '' /* 1.16.0: o nome da regra; sem versão */ ?><?= $encerrada ? ' (no encerramento valeu a regra gravada nele)' : '' ?><?= (($this->dados['barra']['regra'] ?? null) === true) ? ' · <a href="' . URLADM . 'regra-media/oferta/' . (int) $o['id'] . '">ver a regra</a>' : '' ?></small></h6>
            <?php foreach ($c['alunos'] as $al) : $mem = $al['memoria'] ?? null; ?>
              <details class="memoria-aluno small" id="memoria-<?= $h($al['rm']) ?>"><summary><?= $h($al['rm']) ?> — <?= $h($al['nome']) ?>: <?= $h(Calc::formatar($al['media'], $casasDe($al))) ?></summary>
                <?php if ($mem && !empty($mem['linhas'])) : ?><ul class="mb-1 ps-3"><?php foreach ($mem['linhas'] as $l) : ?><li><?= $h($l) ?></li><?php endforeach; ?></ul>
                <?php else : ?><p class="mb-1 text-muted"><?= $h($al['conta'] ?: 'sem média') ?><?= htmlspecialchars((string) (!empty($al['gravado']) ? ' (encerramento anterior à 1.13.0: só o resultado foi gravado)' : ''), ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
              </details>
            <?php endforeach; ?>
          </div>
          <script>document.querySelectorAll('.ver-conta').forEach(function (a) { a.addEventListener('click', function () { var d = document.getElementById('memoria-' + a.getAttribute('data-rm')); if (d) d.open = true; }); });</script>
          <p class="small text-muted mb-0 mt-2">Regras: média mínima <?= $h(Calc::numero($rg['mediaMinima'], 1)) ?>; frequência mínima <?= $h(Calc::numero($rg['frequenciaMinima'])) ?>%; <?= htmlspecialchars(\App\adms\Models\helper\AdmsUi::plural((int) $rg['casas'], 'casa decimal', 'casas decimais'), ENT_QUOTES, 'UTF-8') ?> na média; <?= htmlspecialchars(\App\adms\Models\helper\AdmsUi::plural(count($c['atividades']), 'atividade', 'atividades'), ENT_QUOTES, 'UTF-8') ?>; <?= $o['encontros'] !== null ? (\App\adms\Models\helper\AdmsTipoInstituicao::ehEscola() ? (int) $o['encontros'] . ' aulas previstas' : \App\adms\Models\helper\AdmsUi::plural((int) $o['encontros'], 'encontro', 'encontros')) : 'total de encontros <b>não informado</b>' ?>. Em vermelho: abaixo do mínimo. Passe o mouse na média para ver a conta.</p>
        </div>
      </div>
    </div>
  </section>
</div>
