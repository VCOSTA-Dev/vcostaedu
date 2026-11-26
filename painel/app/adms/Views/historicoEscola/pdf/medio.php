<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/*
 * O MODELO DO MÉDIO (D-116), frente e verso, no Dompdf (Helvetica). O desenho é o do modelo da escola: na frente, os
 * resultados por área de conhecimento (a formação geral básica) e pelo itinerário que o aluno cursou, com as notas e a
 * carga de cada série, os totais da base e do itinerário e os totais gerais (as aulas semanais, as aulas anuais e as
 * horas anuais); no verso, os estudos realizados (o curso anterior, o Fundamental, e as séries do Médio), a transferência
 * durante o ano letivo (por área e por itinerário), as observações (60, com a escala) e o fecho (70). Todo número vem
 * de $d (a montagem). Variáveis: $d (os dados), $alt (a altura da linha, mm), $pt (o corpo da letra), $v (a verificação).
 * Técnica: a do Fundamental (larguras na primeira linha, em mm, descontando 0,6 mm por célula; sem table-layout: fixed;
 * o rótulo vertical num div girado com transform-origin 0 0; cada quadro sem quebra de página).
 */
$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$up = fn($x) => mb_strtoupper((string) $x);
$t = $d['texto'];
$a = $d['aluno'];
$em = $d['medio'];
$cols = $d['colunas'];
$nc = max(1, count($cols));
$UTIL = 184.0;
$w = fn(float $mm) => number_format(max(1, $mm - 1.0), 2, '.', '') . 'mm';
$larguras = fn(array $mm) => '<tr class="z">' . implode('', array_map(fn($x) => '<td style="width: ' . $w($x) . '"></td>', $mm)) . '</tr>';
$vertical = function (string $txt, float $alturaMm, float $larguraMm, ?float $corpo = null) use ($h, $pt) {
  $alturaMm = max(6, $alturaMm);
  $corpo ??= max(4.5, $pt - 2);
  return '<div style="width: ' . number_format($larguraMm - 1, 2, '.', '') . 'mm; height: ' . number_format($alturaMm, 2, '.', '') . 'mm; overflow: hidden">'
    . '<div style="position: relative; top: ' . number_format($alturaMm, 2, '.', '') . 'mm; width: ' . number_format($alturaMm, 2, '.', '') . 'mm; transform: rotate(-90deg); transform-origin: 0 0; text-align: center; font-size: ' . $corpo . 'pt; white-space: nowrap">'
    . $h($txt) . '</div></div>';
};
/* as larguras da frente (o modelo: o rótulo, a área, o componente, "Série/Ano", a nota e a carga por coluna, o total);
   com as retenções (4 ou 5 colunas), a nota, a carga, a área e o total estreitam, e o componente fica com o resto */
$wV = 5.0; $wA = 30.0; $wSA = 9.0; $wN = 12.0; $wC = 10.0; $wT = 12.0;
$wComp = $UTIL - ($wV + $wA + $wSA + $nc * $wN + $nc * $wC + $wT);
if ($wComp < 40) {
  $wN = 10.0; $wC = 9.0; $wA = 24.0; $wT = 11.0;
  $wComp = $UTIL - ($wV + $wA + $wSA + $nc * $wN + $nc * $wC + $wT);
}
/* a célula de uma linha numa coluna: em branco na série não cursada e na em curso (ou da transferência); traço quando
   a disciplina não foi cursada naquela série */
$cel = function (array $l, int $k, string $q) use ($cols) {
  $c = $cols[$k];
  if (!$c['cursada'] || !empty($c['em_curso']) || !empty($c['transferese'])) {
    return '';
  }
  $v = $q === 'n' ? ($l['cel'][$k] ?? null) : ($l['carga'][$k] ?? null);
  return $v === null ? '-' : (string) $v;
};
$porArea = [];
foreach ($em['fgb'] as $l) {
  $porArea[$l['area'] !== '' ? $l['area'] : ' '][] = $l;
}
$nFgb = max(1, count($em['fgb']));
$nIt = max(1, count($em['it']));
$itNum = (int) $em['itinerario']['numero'];
$itRot = 'ITINERÁRIO FORMATIVO' . ($itNum > 0 ? ' ' . $itNum : '');
/* o verso: a transferência por área e por itinerário */
$tr = $d['transferencia'];
$nEt = max(1, count($tr['etapas']));
$wVv = 6.0; $wAv = 28.0; $wFal = 14.0; $wAul = 18.0; $wEt = 14.0;
$wCv = $UTIL - $wVv - $wAv - $nEt * $wEt - $wFal - $wAul;
$tArea = [];
foreach ($tr['bnc'] as $l) {
  $tArea[($l['area'] ?? '') !== '' ? $l['area'] : ' '][] = $l;
}
$tIt = [];
foreach ($tr['pd'] as $l) {
  $tIt[($l['grupo'] ?? '') === 'IT_FORMATIVO' ? (int) ($l['itinerario'] ?? 0) : 0][] = $l;
}
ksort($tIt);
$tFgb = max(1, count($tr['bnc']));
$tItN = max(3, count($tr['pd']));
$ce = $em['conclusao_ef'];
$fecho = $h($d['fecho']['texto']);
foreach (['escola', 'aluno', 'ra'] as $kn) {
  $val = trim((string) ($d['fecho']['nomes'][$kn] ?? ''));
  if ($val !== '' && ($pos = strpos($fecho, $h($val))) !== false) {
    $fecho = substr_replace($fecho, '<b>' . $h($val) . '</b>', $pos, strlen($h($val)));
  }
}
$fmtData = fn($ymd) => $ymd ? date('d/m/Y', strtotime($ymd)) : '';
?><!DOCTYPE html>
<html lang="pt-br"><head><meta charset="UTF-8"><title>Histórico escolar — Ensino Médio</title>
<style>
  @page { margin: 10mm 13mm 14mm 13mm; }
  body { font-family: Helvetica, Arial, sans-serif; font-size: <?= $pt ?>pt; color: #000; margin: 0; }
  table { border-collapse: collapse; }
  td, th { padding: 0 0.3mm; vertical-align: middle; }
  .b td, .b th { border: 0.5pt solid #000; }
  .c { text-align: center; } .r { text-align: right; } .n { font-weight: bold; }
  .som { background: #dbeef4; }
  .cz { background: #a6a6a6; }
  .num { font-size: <?= max(5, $pt - 2) ?>pt; border: 0.5pt solid #000; padding: 0 0.6mm; }
  .tit { font-size: <?= $pt + 0.5 ?>pt; }
  .q td { height: <?= $alt ?>mm; }
  .pequeno { font-size: <?= max(4.5, $pt - 1.5) ?>pt; }
  .bloco { page-break-inside: avoid; margin-top: 2.2mm; }
  .frente { page-break-after: always; }
  .txt { padding: 1mm 1.5mm; text-align: justify; }
  .b .sem td { border: 0; }
  .b tr.z td { border: 0; padding: 0; height: 0; font-size: 0; line-height: 0; }
  .ass { border-top: 0.5pt solid #000; padding-top: 0.6mm; text-align: center; }
</style></head><body>

<div class="frente">
  <!-- o cabeçalho: o logo e as linhas do órgão, a escola, o endereço e o ato legal da escola -->
  <table style="width: <?= $UTIL ?>mm"><tr>
    <td style="width: <?= $w(42) ?>"><?= $d['logo'] ? '<img src="' . $h($d['logo']) . '" style="max-width: 40mm; max-height: 20mm">' : '' ?></td>
    <td class="c" style="width: <?= $w(142) ?>">
      <?php foreach (['orgao_linha1', 'orgao_linha2', 'orgao_linha3'] as $o) : if (trim((string) $t[$o]) !== '') : ?><div><?= $h($t[$o]) ?></div><?php endif; endforeach; ?>
      <div class="n tit" style="margin-top: 1mm"><?= $h($t['escola_nome']) ?></div>
      <div class="pequeno"><?= $h($t['escola_endereco']) ?></div>
      <?php if (trim((string) $t['ato_escola']) !== '') : ?><div class="pequeno"><?= $h($t['ato_escola']) ?></div><?php endif; ?>
    </td></tr></table>

  <!-- o título, o ato legal do curso e o registro de matrícula -->
  <table class="b" style="width: <?= $UTIL ?>mm; margin-top: 1.5mm">
    <tr><td colspan="4" class="c n tit" style="width: <?= $w($UTIL) ?>">HISTÓRICO ESCOLAR - ENSINO MÉDIO</td></tr>
    <tr><td class="pequeno" style="width: <?= $w(22) ?>">ATO LEGAL</td><td colspan="3" class="c pequeno som" style="width: <?= $w(162) ?>"><?= $h($t['ato_curso']) ?></td></tr>
  </table>
  <table class="b" style="width: <?= $UTIL ?>mm; margin-top: 1.5mm">
    <tr><td colspan="2" class="c pequeno" style="width: <?= $w(92) ?>">Registro de Matrícula do Aluno</td><td class="c pequeno" style="width: <?= $w(22) ?>"><?= $h($t['rotulo_registro']) ?></td><td class="c n som" style="width: <?= $w(70) ?>"><?= $h($a['ra']) ?></td></tr>
    <tr><td class="c pequeno" style="width: <?= $w(30) ?>">RM Nº</td><td class="c n som" style="width: <?= $w(62) ?>"><?= $h($a['rm']) ?></td><td class="c pequeno">RG Nº</td><td class="c n som"><?= $h($a['rg']) ?></td></tr>
  </table>

  <!-- a identificação -->
  <table class="b" style="width: <?= $UTIL ?>mm; margin-top: 1.5mm">
    <tr><td class="pequeno" style="width: <?= $w(26) ?>">Nome do Aluno</td><td colspan="6" class="c n som" style="width: <?= $w(158) ?>"><?= $h($up($a['nome'])) ?></td></tr>
    <tr class="pequeno"><td></td><td class="c" style="width: <?= $w(74) ?>">Localidade</td><td class="c" style="width: <?= $w(12) ?>">UF</td><td class="c" style="width: <?= $w(30) ?>">Nacionalidade</td><td class="c" style="width: <?= $w(13) ?>">Dia</td><td class="c" style="width: <?= $w(13) ?>">Mês</td><td class="c" style="width: <?= $w(16) ?>">Ano</td></tr>
    <tr><td class="pequeno">Nascimento</td><td class="c som"><?= $h($a['nasc_local']) ?></td><td class="c som"><?= $h($a['nasc_uf']) ?></td><td class="c som"><?= $h($a['nacionalidade']) ?></td><td class="c som n"><?= $h($a['dia']) ?></td><td class="c som n"><?= $h($a['mes']) ?></td><td class="c som n"><?= $h($a['ano']) ?></td></tr>
  </table>

  <!-- os resultados: a formação geral básica por área e o itinerário formativo -->
  <div class="bloco">
  <table class="b q" style="width: <?= $UTIL ?>mm">
    <?= $larguras(array_merge([$wV, $wA, $wComp, $wSA], array_fill(0, $nc, $wN), array_fill(0, $nc, $wC), [$wT])) ?>
    <tr><td colspan="<?= 5 + 2 * $nc ?>" class="c n">RESULTADOS DOS ESTUDOS REALIZADOS NO ENSINO MÉDIO</td></tr>
    <tr><td colspan="2" rowspan="3" class="c n pequeno">ÁREAS DE<br>CONHECIMENTO</td>
      <td rowspan="2" class="c n pequeno" style="border-right: 0">Componentes<br>Curriculares</td><td style="border-left: 0; border-bottom: 0"></td>
      <td colspan="<?= $nc ?>" class="c pequeno">MENÇÕES / NOTAS</td><td colspan="<?= $nc + 1 ?>" class="c pequeno">CARGA HORÁRIA</td></tr>
    <tr><td class="c pequeno" style="border-top: 0">Série</td>
      <?php foreach ($cols as $c) : ?><td class="c pequeno"><?= (int) $c['serie'] ?>ª</td><?php endforeach; ?>
      <?php foreach ($cols as $c) : ?><td class="c pequeno"><?= (int) $c['serie'] ?>ª</td><?php endforeach; ?>
      <td class="c pequeno">TOTAL</td></tr>
    <tr><td style="border-right: 0"></td><td class="c pequeno" style="border-left: 0">Ano</td>
      <?php foreach ($cols as $c) : ?><td class="c n"><?= $h($c['ano']) ?></td><?php endforeach; ?>
      <?php foreach ($cols as $c) : ?><td class="c n"><?= $h($c['ano']) ?></td><?php endforeach; ?>
      <td class="c">-</td></tr>
    <?php $primeira = true; foreach ($porArea as $area => $ls) : foreach ($ls as $j => $l) : ?>
      <tr><?php if ($primeira) : $primeira = false; ?><td rowspan="<?= $nFgb ?>" style="padding: 0"><?= $vertical('FORMAÇÃO GERAL BÁSICA', $nFgb * $alt, $wV) ?></td><?php endif; ?>
        <?php if ($j === 0) : ?><td rowspan="<?= count($ls) ?>" class="c pequeno"><?= $h($up(trim($area))) ?></td><?php endif; ?>
        <td colspan="2" class="pequeno"><?= $h($up($l['nome'])) ?></td>
        <?php foreach ($cols as $k => $c) : ?><td class="c"><?= $h($cel($l, $k, 'n')) ?></td><?php endforeach; ?>
        <?php foreach ($cols as $k => $c) : ?><td class="c"><?= $h($cel($l, $k, 'c')) ?></td><?php endforeach; ?>
        <td class="c"><?= $l['total'] ? $h(number_format((int) $l['total'], 0, ',', '.')) : '-' ?></td></tr>
    <?php endforeach; endforeach; ?>
    <?php if (!$em['fgb']) : ?><tr><td style="padding: 0"><?= $vertical('FORMAÇÃO GERAL BÁSICA', $alt, $wV) ?></td><td></td><td colspan="2"></td><?php for ($k = 0; $k < 2 * $nc + 1; $k++) : ?><td></td><?php endfor; ?></tr><?php endif; ?>
    <tr><td colspan="<?= 4 + $nc ?>" class="c n pequeno">BASE NACIONAL COMUM - TOTAL DA CARGA HORÁRIA</td>
      <?php foreach ($cols as $c) : ?><td class="c n"><?= $h($c['em']['carga_fgb'] ?? '') ?></td><?php endforeach; ?><td class="c n"><?= $h($em['totais']['carga_fgb']) ?></td></tr>
    <?php for ($i = 0; $i < $nIt; $i++) : $l = $em['it'][$i] ?? null; ?>
      <tr><?php if ($i === 0) : ?><td rowspan="<?= $nIt + 1 ?>" style="padding: 0"><?= $vertical('ITINERÁRIO FORMATIVO', ($nIt + 1) * $alt, $wV) ?></td>
          <td rowspan="<?= $nIt ?>" style="padding: 0"><table style="width: <?= $w($wA) ?>"><tr>
            <td style="width: <?= $w(5) ?>; padding: 0; border: 0; border-right: 0.5pt solid #000"><?= $vertical($itRot, $nIt * $alt, 5, max(4.2, $pt - 2.8)) ?></td>
            <td class="c pequeno" style="width: <?= $w($wA - 5) ?>; border: 0"><?= $h($up($em['itinerario']['nome'])) ?></td></tr></table></td><?php endif; ?>
        <td colspan="2" class="pequeno"><?= $l ? $h($up($l['nome'])) : '' ?></td>
        <?php foreach ($cols as $k => $c) : ?><td class="c"><?= $l ? $h($cel($l, $k, 'n')) : '' ?></td><?php endforeach; ?>
        <?php foreach ($cols as $k => $c) : ?><td class="c"><?= $l ? $h($cel($l, $k, 'c')) : '' ?></td><?php endforeach; ?>
        <td class="c"><?= $l ? ($l['total'] ? $h(number_format((int) $l['total'], 0, ',', '.')) : '-') : '' ?></td></tr>
    <?php endfor; ?>
    <tr><td colspan="<?= 3 + $nc ?>" class="c n pequeno">TOTAL DE CARGA HORÁRIA - <?= $h($itRot) ?></td>
      <?php foreach ($cols as $c) : ?><td class="c n"><?= $h($c['em']['carga_it'] ?? '') ?></td><?php endforeach; ?><td class="c n"><?= $h($em['totais']['carga_it']) ?></td></tr>
    <?php foreach ([['TOTAL ITINERÁRIO FORMATIVO PRESENCIAL DENTRO DO TURNO (AULAS SEMANAIS)', 'sem_it', false], ['TOTAL GERAL DE AULAS SEMANAIS PRESENCIAIS DENTRO DO TURNO', 'sem_total', false],
        ['TOTAL GERAL DE AULAS ANUAIS', 'anuais', true], ['TOTAL GERAL DE HORAS ANUAIS', 'horas', true]] as [$rotulo, $campo, $negrito]) : ?>
      <tr><td colspan="4" class="c pequeno<?= $negrito ? ' n' : '' ?>"><?= $h($rotulo) ?></td>
        <?php foreach ($cols as $c) : ?><td class="cz"></td><?php endforeach; ?>
        <?php foreach ($cols as $c) : ?><td class="c<?= $negrito ? ' n' : '' ?>"><?= $h($c['em'][$campo] ?? '') ?></td><?php endforeach; ?>
        <td class="c<?= $negrito ? ' n' : '' ?>"><?= $h($em['totais'][$campo]) ?></td></tr>
    <?php endforeach; ?>
  </table>
  </div>
</div>

<!-- o verso: os estudos realizados -->
<div class="bloco" style="margin-top: 0">
<table class="b q" style="width: <?= $UTIL ?>mm">
  <?= $larguras([16, 20, 82, 34, 12, 20]) ?>
  <tr><td colspan="6" class="c n">ESTUDOS REALIZADOS</td></tr>
  <tr><td colspan="2" class="c n pequeno">CURSO ANTERIOR</td><td colspan="4" class="c n pequeno">ENSINO FUNDAMENTAL</td></tr>
  <tr class="pequeno"><td colspan="2" class="c">ANO DE CONCLUSÃO</td><td class="c">ESTABELECIMENTO</td><td class="c">MUNICÍPIO</td><td class="c">UF</td><td class="c"><?= $h($t['rotulo_publicacao']) ?></td></tr>
  <tr><td colspan="2" class="c n"><?= $h($ce['ano']) ?></td><td class="c"><?= $h($up($ce['escola'])) ?></td><td class="c"><?= $h($up($ce['municipio'])) ?></td><td class="c"><?= $h($ce['uf']) ?></td><td class="c"><?= $h($ce['publicacao']) ?></td></tr>
  <tr><td colspan="6" class="c n pequeno">NO ENSINO MÉDIO</td></tr>
  <tr class="pequeno"><td class="c">SÉRIE</td><td class="c">ANO</td><td class="c">ESTABELECIMENTO</td><td colspan="2" class="c">MUNICÍPIO</td><td class="c">UF</td></tr>
  <?php foreach ($d['estudos'] as $e) : ?>
    <tr><td class="c"><?= $h($e['serie']) ?></td><td class="c n"><?= $h($e['ano']) ?></td><td class="c"><?= $h($up($e['escola'])) ?></td><td colspan="2" class="c"><?= $h($up($e['municipio'])) ?></td><td class="c"><?= $h($e['uf']) ?></td></tr>
  <?php endforeach; ?>
</table>
</div>

<!-- a transferência durante o ano letivo -->
<div class="bloco">
<table class="b q" style="width: <?= $UTIL ?>mm">
  <?= $larguras(array_merge([$wVv, $wAv, $wCv], array_fill(0, $nEt, $wEt), [$wFal, $wAul])) ?>
  <tr><td colspan="<?= 5 + $nEt ?>" class="c n">TRANSFERÊNCIA DURANTE O ANO LETIVO</td></tr>
  <tr><td colspan="<?= 5 + $nEt ?>" class="pequeno">Rendimento escolar do aluno no ano letivo de <b><?= $h($tr['ano']) ?></b> &nbsp;&nbsp; até <b><?= $h($tr['ate']) ?></b></td></tr>
  <tr><td colspan="<?= 5 + $nEt ?>" class="pequeno">Classe: Turno <b><?= $h($tr['turno']) ?></b> &nbsp;&nbsp;&nbsp; Ensino Médio &nbsp;&nbsp;&nbsp; Série <b><?= $h($tr['serie']) ?></b> &nbsp;&nbsp;&nbsp; Turma <b><?= $h($tr['turma']) ?></b> &nbsp;&nbsp;&nbsp; Nº de Chamada <b><?= $h($tr['chamada']) ?></b></td></tr>
  <tr><td colspan="3" rowspan="2" class="c pequeno">COMPONENTES CURRICULARES</td><td colspan="<?= $nEt ?>" class="c pequeno">MENÇÕES OU NOTAS</td><td rowspan="2" class="c pequeno">FALTAS</td><td rowspan="2" class="c pequeno">AULAS DADAS</td></tr>
  <tr class="pequeno"><?php foreach ($tr['etapas'] as $e) : ?><td class="c"><?= $h($e) ?></td><?php endforeach; ?></tr>
  <?php $primeira = true; foreach ($tArea ?: [' ' => [null]] as $area => $ls) : foreach ($ls as $j => $l) : ?>
    <tr><?php if ($primeira) : $primeira = false; ?><td rowspan="<?= $tFgb ?>" style="padding: 0"><?= $vertical('FORMAÇÃO GERAL BÁSICA', $tFgb * $alt, $wVv) ?></td><?php endif; ?>
      <?php if ($j === 0) : ?><td rowspan="<?= count($ls) ?>" class="c pequeno"><?= $h($up(trim($area))) ?></td><?php endif; ?>
      <td class="pequeno"><?= $l ? $h($up($l['nome'])) : '' ?></td>
      <?php for ($e = 0; $e < $nEt; $e++) : ?><td class="c"><?= $l ? $h($l['notas'][$e] ?? '') : '' ?></td><?php endfor; ?>
      <td class="c n"><?= $l ? $h($l['faltas']) : '' ?></td><td class="c"><?= $l ? $h($l['aulas']) : '' ?></td></tr>
  <?php endforeach; endforeach; ?>
  <?php $primeira = true; $grupos = $tIt ?: [0 => []]; $sobra = $tItN - count($tr['pd']);
    foreach ($grupos as $num => $ls) : if ($sobra > 0 && $num === array_key_last($grupos)) { $ls = array_merge($ls, array_fill(0, $sobra, null)); }
      foreach ($ls as $j => $l) : ?>
    <tr><?php if ($primeira) : $primeira = false; ?><td rowspan="<?= $tItN ?>" style="padding: 0"><?= $vertical('ITINERÁRIO FORMATIVO', $tItN * $alt, $wVv) ?></td><?php endif; ?>
      <?php if ($j === 0) : ?><td rowspan="<?= count($ls) ?>" class="c pequeno"><?= $h($num === 0 ? 'ITINERÁRIO COMUM' : 'ITINERÁRIO FORMATIVO ' . $num . (($em['itinerarios'][$num] ?? '') !== '' ? ' — ' . $up($em['itinerarios'][$num]) : '')) ?></td><?php endif; ?>
      <td class="pequeno"><?= $l ? $h($up($l['nome'])) : '' ?></td>
      <?php for ($e = 0; $e < $nEt; $e++) : ?><td class="c"><?= $l ? $h($l['notas'][$e] ?? '') : '' ?></td><?php endfor; ?>
      <td class="c n"><?= $l ? $h($l['faltas']) : '' ?></td><td class="c"><?= $l ? $h($l['aulas']) : '' ?></td></tr>
  <?php endforeach; endforeach; ?>
  <tr><td colspan="<?= 3 + $nEt ?>" class="r n">FALTAS &nbsp;&nbsp; <?= $h($tr['faltas']) ?></td><td colspan="2" class="c n">DIAS LETIVOS &nbsp;&nbsp; <?= $h($tr['dias']) ?></td></tr>
</table>
</div>

<!-- 60: as observações (a escala avaliatória primeiro, como no modelo) -->
<div class="bloco">
<table class="b" style="width: <?= $UTIL ?>mm">
  <tr><td class="pequeno" style="width: <?= $w(8) ?>"><span class="num">60</span></td><td class="c" style="width: <?= $w(176) ?>">OBSERVAÇÕES</td></tr>
  <tr><td colspan="2" class="txt" style="height: <?= number_format($alt * 6, 2, '.', '') ?>mm; vertical-align: top">
    <?php if (trim((string) $t['escala']) !== '') : ?><div style="margin-bottom: 1.2mm"><?= nl2br($h($t['escala'])) ?></div><?php endif; ?>
    <?php foreach ($d['observacoes'] as $o) : ?><div style="margin-bottom: 1.2mm"><?= nl2br($h($o)) ?></div><?php endforeach; ?></td></tr>
</table>
</div>

<!-- 70: o fecho (a declaração ou o certificado), a data, as assinaturas, a publicação -->
<div class="bloco">
<table class="b" style="width: <?= $UTIL ?>mm">
  <tr><td class="pequeno" style="width: <?= $w(8) ?>"><span class="num">70</span></td><td class="c" style="width: <?= $w(176) ?>"><?= $h($d['fecho']['titulo']) ?></td></tr>
  <tr><td colspan="2" class="txt" style="height: <?= number_format($alt * 3, 2, '.', '') ?>mm; vertical-align: top; line-height: 1.6"><?= nl2br($fecho) ?></td></tr>
  <tr><td colspan="2" style="padding: 2mm 1.5mm">
    <div>DATA : &nbsp;&nbsp;<u><?= $h($fmtData($d['data'])) ?></u></div>
    <table class="sem" style="width: <?= $w($UTIL - 4) ?>; margin-top: 9mm"><tr>
      <td style="width: <?= $w(20) ?>"></td>
      <td style="width: <?= $w(68) ?>"><div class="ass pequeno"><?= $h($t['secretaria_nome']) ?><?= trim((string) $t['secretaria_doc']) !== '' ? '<br>' . $h($t['secretaria_doc']) : '' ?><br><?= $h($t['titulo_secretaria']) ?></div></td>
      <td style="width: <?= $w(10) ?>"></td>
      <td style="width: <?= $w(78) ?>"><div class="ass pequeno"><?= $h($t['diretora_nome']) ?><?= trim((string) $t['diretora_doc']) !== '' ? '<br>' . $h($t['diretora_doc']) : '' ?><br><?= $h($t['titulo_diretora']) ?></div></td>
    </tr></table>
    <?php if (trim((string) $t['rotulo_publicacao']) !== '') : ?><div style="margin-top: 3mm"><?= $h($t['rotulo_publicacao']) ?> &nbsp;&nbsp; <?= $d['publicacao'] !== '' ? '<u>' . $h($d['publicacao']) . '</u>' : '______________________________' ?></div><?php endif; ?>
  </td></tr>
</table>
</div>

<!-- a verificação: o código, o endereço e o QR (o rodapé de cada página leva o código, a via e a página) -->
<div class="bloco">
<table style="width: <?= $UTIL ?>mm"><tr>
  <td class="pequeno" style="width: <?= $w(160) ?>">Confira a autenticidade deste documento em <?= $h($v['url']) ?> — código de verificação <b><?= $h($v['codigo']) ?></b>.</td>
  <td class="r" style="width: <?= $w(24) ?>"><?= $v['qr'] ? '<img src="' . $h($v['qr']) . '" style="width: 18mm">' : '' ?></td>
</tr></table>
</div>
</body></html>
