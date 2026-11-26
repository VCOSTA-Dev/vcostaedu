<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/*
 * O MODELO DO FUNDAMENTAL (D-113), frente e verso, no Dompdf (Helvetica). O desenho é o do modelo da escola: os blocos
 * na mesma ordem, os mesmos títulos e colunas, os rótulos verticais, os números soltos antes dos títulos (10, 30, 40,
 * 50, 60, 70; não há 20). Todo número vem de $d (a montagem). Variáveis: $d (os dados), $alt (a altura da linha, mm),
 * $pt (o corpo da letra), $v (a verificação: codigo, url, qr).
 * Técnica (a da construção no sistema de origem): larguras na primeira linha, em mm, descontando 0,6 mm por célula; sem
 * table-layout: fixed; o rótulo vertical num div girado com transform-origin 0 0; cada quadro sem quebra de página.
 */
$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$t = $d['texto'];
$a = $d['aluno'];
$cols = $d['colunas'];
$k = max(1, count($cols));
$UTIL = 184.0;
$w = fn(float $mm) => number_format(max(1, $mm - 1.0), 2, '.', '') . 'mm';
/* o quadro da frente: o rótulo vertical, os componentes (duas colunas: o § da parte diversificada e o nome), as séries */
$wV = 6.0;
$wC1 = 22.0;
$wC2 = 26.0;
$wS = ($UTIL - $wV - $wC1 - $wC2) / $k;
$vertical = function (string $txt, float $alturaMm, float $larguraMm) use ($h, $pt) {
  $alturaMm = max(8, $alturaMm);
  return '<div style="width: ' . number_format($larguraMm - 1, 2, '.', '') . 'mm; height: ' . number_format($alturaMm, 2, '.', '') . 'mm; overflow: hidden">'
    . '<div style="position: relative; top: ' . number_format($alturaMm, 2, '.', '') . 'mm; width: ' . number_format($alturaMm, 2, '.', '') . 'mm; transform: rotate(-90deg); transform-origin: 0 0; text-align: center; font-size: ' . max(4.5, $pt - 2.5) . 'pt; white-space: nowrap">'
    . $h($txt) . '</div></div>';
};
$nBnc = max(12, count($d['bnc']));
$nPd = max(6, count($d['pd']));
$transfCol = null;
foreach ($cols as $i => $c) {
  if (!empty($c['transferese'])) { $transfCol = $i; }
}
$tr = $d['transferencia'];
$nEt = max(1, count($tr['etapas']));
/* o verso: o rótulo, os componentes, as notas das etapas, faltas, aulas dadas */
$wVv = 6.0; $wCv = 52.0; $wFal = 22.0; $wAul = 34.0;
$wEt = ($UTIL - $wVv - $wCv - $wFal - $wAul) / $nEt;
$tBnc = max(9, count($tr['bnc']));
$tPd = max(5, count($tr['pd']));
/* o fecho: a escola, o aluno e o registro em negrito, como no modelo */
$fecho = $h($d['fecho']['texto']);
foreach (['escola', 'aluno', 'ra'] as $kn) {
  $val = trim((string) ($d['fecho']['nomes'][$kn] ?? ''));
  if ($val !== '' && ($pos = strpos($fecho, $h($val))) !== false) {
    $fecho = substr_replace($fecho, '<b>' . $h($val) . '</b>', $pos, strlen($h($val)));
  }
}
/* a linha das larguras (a primeira da tabela, sem altura): o Dompdf toma as larguras dela */
$larguras = fn(array $mm) => '<tr class="z">' . implode('', array_map(fn($x) => '<td style="width: ' . $w($x) . '"></td>', $mm)) . '</tr>';
$fmtData = fn($ymd) => $ymd ? date('d/m/Y', strtotime($ymd)) : '';
?><!DOCTYPE html>
<html lang="pt-br"><head><meta charset="UTF-8"><title>Histórico escolar — Ensino Fundamental</title>
<style>
  @page { margin: 10mm 13mm 14mm 13mm; }
  body { font-family: Helvetica, Arial, sans-serif; font-size: <?= $pt ?>pt; color: #000; margin: 0; }
  table { border-collapse: collapse; }
  td, th { padding: 0 0.3mm; vertical-align: middle; }
  .b td, .b th { border: 0.5pt solid #000; }
  .c { text-align: center; } .r { text-align: right; } .n { font-weight: bold; }
  .som { background: #dbeef4; }
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
    </td></tr></table>
  <?php if (trim((string) $t['ato_escola']) !== '') : ?><div class="c pequeno" style="margin-top: 1mm">ATO LEGAL DA ESCOLA: <?= $h($t['ato_escola']) ?></div><?php endif; ?>

  <!-- 10: o título, o ato legal do curso e o registro de matrícula -->
  <table style="width: <?= $UTIL ?>mm; margin-top: 1.5mm"><tr>
    <td style="width: <?= $w(118) ?>; padding: 0"><table class="b" style="width: <?= $w(118) ?>">
      <tr><td colspan="2" class="c n tit" style="width: <?= $w(118) ?>">HISTÓRICO ESCOLAR - ENSINO FUNDAMENTAL</td></tr>
      <tr><td class="pequeno" style="width: <?= $w(22) ?>">ATO LEGAL</td><td class="c pequeno som" style="width: <?= $w(96) ?>"><?= $h($t['ato_curso']) ?></td></tr></table></td>
    <td style="width: <?= $w(8) ?>"></td>
    <td style="width: <?= $w(58) ?>; padding: 0"><table class="b" style="width: <?= $w(58) ?>">
      <tr><td colspan="2" class="pequeno"><span class="num">10</span> REGISTRO DE MATRÍCULA</td></tr>
      <tr><td class="pequeno" style="width: <?= $w(14) ?>">RM Nº</td><td class="c n som" style="width: <?= $w(44) ?>"><?= $h($a['rm']) ?></td></tr></table></td>
  </tr></table>

  <!-- a identificação -->
  <table class="b" style="width: <?= $UTIL ?>mm; margin-top: 1.5mm">
    <tr><td rowspan="2" class="pequeno" style="width: <?= $w(26) ?>">Nome do Aluno</td><td rowspan="2" class="c n som" style="width: <?= $w(104) ?>"><?= $h(mb_strtoupper($a['nome'])) ?></td>
      <td class="pequeno" style="width: <?= $w(16) ?>"><?= $h($t['rotulo_registro']) ?></td><td class="c n som" style="width: <?= $w(38) ?>"><?= $h($a['ra']) ?></td></tr>
    <tr><td class="pequeno">RG. Nº</td><td class="c n som"><?= $h($a['rg']) ?></td></tr>
  </table>
  <table class="b" style="width: <?= $UTIL ?>mm">
    <tr class="pequeno"><td style="width: <?= $w(26) ?>"></td><td class="c" style="width: <?= $w(74) ?>">Localidade</td><td class="c" style="width: <?= $w(12) ?>">UF</td><td class="c" style="width: <?= $w(30) ?>">Nacionalidade</td><td class="c" style="width: <?= $w(13) ?>">Dia</td><td class="c" style="width: <?= $w(13) ?>">Mês</td><td class="c" style="width: <?= $w(16) ?>">Ano</td></tr>
    <tr><td class="pequeno">Nascimento</td><td class="c som"><?= $h($a['nasc_local']) ?></td><td class="c som"><?= $h($a['nasc_uf']) ?></td><td class="c som"><?= $h($a['nacionalidade']) ?></td><td class="c som n"><?= $h($a['dia']) ?></td><td class="c som n"><?= $h($a['mes']) ?></td><td class="c som n"><?= $h($a['ano']) ?></td></tr>
  </table>

  <!-- 30: os resultados -->
  <div class="bloco">
  <table class="b q" style="width: <?= $UTIL ?>mm">
    <?= $larguras(array_merge([$wV, $wC1, $wC2], array_fill(0, $k, $wS))) ?>
    <tr><td class="pequeno" style="width: <?= $w($wV) ?>"><span class="num">30</span></td><td colspan="<?= 2 + $k ?>" class="c">RESULTADOS DOS ESTUDOS REALIZADOS NO ENSINO FUNDAMENTAL</td></tr>
    <tr><td rowspan="<?= 3 + $nBnc + 1 ?>" style="width: <?= $w($wV) ?>; padding: 0"><?= $vertical('BASE NACIONAL COMUM - Artº 26 da Lei 9394/96', ($nBnc + 3) * $alt, $wV) ?></td>
      <td colspan="2" style="width: <?= $w($wC1 + $wC2) ?>"></td><td colspan="<?= $k ?>" class="c pequeno">MENÇÕES OU NOTAS</td></tr>
    <tr><td style="width: <?= $w($wC1) ?>">COMPONENTES</td><td class="r pequeno" style="width: <?= $w($wC2) ?>">Série</td>
      <?php foreach ($cols as $c) : ?><td class="c pequeno" style="width: <?= $w($wS) ?>"><?= (int) $c['serie'] ?>º</td><?php endforeach; ?></tr>
    <tr><td>CURRICULARES</td><td class="r pequeno">Ano</td>
      <?php foreach ($cols as $c) : ?><td class="c n"><?= $h($c['ano']) ?></td><?php endforeach; ?></tr>
    <?php for ($i = 0; $i < $nBnc; $i++) : $l = $d['bnc'][$i] ?? null; ?>
      <tr><td colspan="2"><?= $l ? $h($l['nome']) : '' ?></td>
        <?php foreach ($cols as $ci => $c) :
          if ($ci === $transfCol) {
            if ($i === 0) { echo '<td rowspan="' . $nBnc . '" class="c" style="vertical-align: middle; line-height: 1.15">' . implode('<br>', array_map(fn($x) => $h($x), mb_str_split('TRANSFERE-SE'))) . '</td>'; }
            continue;
          } ?>
          <td class="c"><?= $l ? $h($l['cel'][$ci] ?? '') : '' ?></td>
        <?php endforeach; ?></tr>
    <?php endfor; ?>
    <tr><td colspan="2" class="pequeno">Total da Carga Horária Base Nacional Comum</td><?php foreach ($cols as $c) : ?><td class="c n"><?= $h($c['carga_bnc']) ?></td><?php endforeach; ?></tr>
    <?php for ($i = 0; $i < $nPd; $i++) : $l = $d['pd'][$i] ?? null; ?>
      <tr><?php if ($i === 0) : ?><td rowspan="<?= $nPd ?>" style="padding: 0"><?= $vertical('PARTE DIVERSIFICADA', $nPd * $alt, $wV) ?></td>
          <td rowspan="<?= $nPd ?>" class="c pequeno">§ 5º do Artigo 26 da Lei 9394/96</td><?php endif; ?>
        <td><?= $l ? $h($l['nome']) : '' ?></td>
        <?php foreach ($cols as $ci => $c) : ?><td class="c"><?= $l ? $h($l['cel'][$ci] ?? '') : '' ?></td><?php endforeach; ?></tr>
    <?php endfor; ?>
    <tr><td colspan="3" class="pequeno">Parte Diversificada - Total Carga Horária</td><?php foreach ($cols as $c) : ?><td class="c n"><?= $h($c['carga_pd']) ?></td><?php endforeach; ?></tr>
    <tr><td colspan="3" class="pequeno">CARGA HORÁRIA TOTAL</td><?php foreach ($cols as $c) : ?><td class="c n"><?= $h($c['carga_total']) ?></td><?php endforeach; ?></tr>
    <tr><td colspan="3" class="pequeno">ENSINO RELIGIOSO</td><?php foreach ($cols as $ci => $c) : ?><td class="c pequeno"><?= $h($d['religioso']['cel'][$ci] ?? '') ?></td><?php endforeach; ?></tr>
  </table>
  </div>

  <!-- 40: os estudos realizados -->
  <div class="bloco">
  <table class="b q" style="width: <?= $UTIL ?>mm">
    <?= $larguras([12, 16, 104, 40, 12]) ?>
    <tr><td class="pequeno" style="width: <?= $w(12) ?>"><span class="num">40</span></td><td colspan="4" class="c">ESTUDOS REALIZADOS NO ENSINO FUNDAMENTAL</td></tr>
    <tr class="pequeno"><td class="c">SÉRIE</td><td class="c" style="width: <?= $w(16) ?>">ANO</td><td class="c" style="width: <?= $w(104) ?>">ESTABELECIMENTO</td><td class="c" style="width: <?= $w(40) ?>">MUNICÍPIO</td><td class="c" style="width: <?= $w(12) ?>">UF</td></tr>
    <?php foreach ($d['estudos'] as $e) : ?>
      <tr><td class="c"><?= $h(preg_replace('/ ano$/', '', $e['serie'])) ?></td><td class="c n"><?= $h($e['ano']) ?></td><td class="c"><?= $h($e['escola']) ?></td><td class="c"><?= $h($e['municipio']) ?></td><td class="c"><?= $h($e['uf']) ?></td></tr>
    <?php endforeach; ?>
  </table>
  </div>
</div>

<!-- o verso -->
<!-- 50: a transferência durante o período letivo -->
<div class="bloco" style="margin-top: 0">
<table class="b q" style="width: <?= $UTIL ?>mm">
  <?= $larguras(array_merge([$wVv, $wCv], array_fill(0, $nEt, $wEt), [$wFal, $wAul])) ?>
  <tr><td class="pequeno" style="width: <?= $w($wVv) ?>"><span class="num">50</span></td><td colspan="<?= 3 + $nEt ?>" class="c">TRANSFERÊNCIA DURANTE O PERÍODO LETIVO</td></tr>
  <tr><td colspan="<?= 4 + $nEt ?>" class="c pequeno">Rendimento escolar do Aluno no Ano Letivo de <b><?= $h($tr['ano']) ?></b> &nbsp;&nbsp; até <b><?= $h($tr['ate']) ?></b></td></tr>
  <tr><td colspan="<?= 4 + $nEt ?>" class="pequeno">Classe: Turno <b><?= $h($tr['turno']) ?></b> &nbsp;&nbsp;&nbsp; Ensino Fundamental &nbsp;&nbsp;&nbsp; Ano <b><?= $h(preg_replace('/ ano$/', '', (string) $tr['serie'])) ?></b> &nbsp;&nbsp;&nbsp; Turma <b><?= $h($tr['turma']) ?></b> &nbsp;&nbsp;&nbsp; Nº de Chamada <b><?= $h($tr['chamada']) ?></b></td></tr>
  <tr><td rowspan="2" colspan="2" class="c pequeno" style="width: <?= $w($wVv + $wCv) ?>">COMPONENTES CURRICULARES</td><td colspan="<?= $nEt ?>" class="c pequeno">MENÇÕES OU NOTAS</td><td colspan="2" class="c pequeno">1º ao 9º anos</td></tr>
  <tr class="pequeno"><?php foreach ($tr['etapas'] as $e) : ?><td class="c" style="width: <?= $w($wEt) ?>"><?= $h($e) ?></td><?php endforeach; ?><td class="c" style="width: <?= $w($wFal) ?>">Faltas</td><td class="c" style="width: <?= $w($wAul) ?>">Aulas Dadas</td></tr>
  <?php for ($i = 0; $i < $tBnc; $i++) : $l = $tr['bnc'][$i] ?? null; ?>
    <tr><?php if ($i === 0) : ?><td rowspan="<?= $tBnc ?>" style="padding: 0"><?= $vertical('BASE NACIONAL COMUM', $tBnc * $alt, $wVv) ?></td><?php endif; ?>
      <td><?= $l ? $h($l['nome']) : '' ?></td>
      <?php for ($e = 0; $e < $nEt; $e++) : ?><td class="c"><?= $l ? $h($l['notas'][$e] ?? '') : '' ?></td><?php endfor; ?>
      <td class="c n"><?= $l ? $h($l['faltas']) : '' ?></td><td class="c"><?= $l ? $h($l['aulas']) : '' ?></td></tr>
  <?php endfor; ?>
  <?php for ($i = 0; $i < $tPd; $i++) : $l = $tr['pd'][$i] ?? null; ?>
    <tr><?php if ($i === 0) : ?><td rowspan="<?= $tPd ?>" style="padding: 0"><?= $vertical('PARTE DIVERSIFICADA', $tPd * $alt, $wVv) ?></td><?php endif; ?>
      <td><?= $l ? $h($l['nome']) : '' ?></td>
      <?php for ($e = 0; $e < $nEt; $e++) : ?><td class="c"><?= $l ? $h($l['notas'][$e] ?? '') : '' ?></td><?php endfor; ?>
      <td class="c n"><?= $l ? $h($l['faltas']) : '' ?></td><td class="c"><?= $l ? $h($l['aulas']) : '' ?></td></tr>
  <?php endfor; ?>
  <tr><td colspan="2" class="c n tit">1° ao 9° ano</td><td colspan="<?= $nEt ?>" class="c n">FALTAS &nbsp;&nbsp;&nbsp;&nbsp; <?= $h($tr['faltas']) ?></td><td colspan="2" class="c n">DIAS LETIVOS &nbsp;&nbsp;&nbsp;&nbsp; <?= $h($tr['dias']) ?></td></tr>
</table>
</div>

<!-- 60: a escala avaliatória -->
<div class="bloco">
<table class="b" style="width: <?= $UTIL ?>mm">
  <tr><td class="pequeno" style="width: <?= $w(8) ?>"><span class="num">60</span></td><td class="c" style="width: <?= $w(176) ?>">ESCALA AVALIATÓRIA</td></tr>
  <tr><td colspan="2" class="txt" style="height: <?= number_format($alt * 3, 2, '.', '') ?>mm; vertical-align: top"><?= nl2br($h($t['escala'])) ?></td></tr>
</table>
</div>

<!-- 70: as observações -->
<div class="bloco">
<table class="b" style="width: <?= $UTIL ?>mm">
  <tr><td class="pequeno" style="width: <?= $w(8) ?>"><span class="num">70</span></td><td class="c" style="width: <?= $w(176) ?>">OBSERVAÇÕES</td></tr>
  <tr><td colspan="2" class="txt" style="height: <?= number_format($alt * 6, 2, '.', '') ?>mm; vertical-align: top"><?php foreach ($d['observacoes'] as $o) : ?><div style="margin-bottom: 1.2mm"><?= nl2br($h($o)) ?></div><?php endforeach; ?></td></tr>
</table>
</div>

<!-- o fecho: a declaração ou o certificado, a data, as assinaturas, a publicação -->
<div class="bloco">
<table class="b" style="width: <?= $UTIL ?>mm">
  <tr><td class="c" style="width: <?= $w($UTIL) ?>"><?= $h($d['fecho']['titulo']) ?></td></tr>
  <tr><td class="txt" style="height: <?= number_format($alt * 3, 2, '.', '') ?>mm; vertical-align: top; line-height: 1.6"><?= nl2br($fecho) ?></td></tr>
  <tr><td style="padding: 2mm 1.5mm">
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
