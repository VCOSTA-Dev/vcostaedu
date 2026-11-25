<?php
if (!defined('VCOSTAEDU')) {
  header("Location: /");
  die("Erro: Página não encontrada!");
}
/* OS IMPRESSOS DA RENEGOCIAÇÃO (Onda 8, financeiro refeito, Parte 2; D-105), em PDF: a PROPOSTA DE ACORDO (as duas
   condições, o resumo, a validade, as assinaturas e duas testemunhas), o ACORDO EFETIVADO (os títulos renegociados, a
   condição escolhida e os lançamentos) e o TERMO DE CONFISSÃO DE DÍVIDA (a qualificação das partes, o total por extenso,
   as cláusulas, as parcelas e as assinaturas). A redação é um modelo padrão, neutro — Configurações › Regras do
   financeiro avisa que o jurídico da instituição deve revisá-la. Recebe $imp (tipo, proposta ou acordo, aluno, pagador,
   instituição) e devolve o HTML em $html. */
use App\adms\Models\financeiro\AdmsFinanceiro as F;
use App\adms\Models\financeiro\AdmsFinRenegociacao as RN;

$h = fn($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
$brl = fn($v) => 'R$ ' . F::brl((int) $v);
$dt = fn($x) => $x ? date('d/m/Y', strtotime((string) $x)) : '';
$tipo = $imp['tipo'];
$a = $imp['aluno'];
$pag = $imp['pagador'];
$inst = $imp['instituicao'];
$rotNum = $imp['rotuloNumero'];
$nomeAluno = $a['nome_social'] ?: $a['nome'];
$instNome = (string) ($inst['razao_social'] ?? '') ?: (string) ($inst['nome'] ?? 'a instituição');
$instEnd = trim(implode(', ', array_filter([trim(($inst['logradouro'] ?? '') . ' ' . ($inst['numero'] ?? '')), $inst['bairro'] ?? '', trim(($inst['cidade'] ?? '') . (($inst['uf'] ?? '') ? '/' . $inst['uf'] : '')), ($inst['cep'] ?? '') ? 'CEP ' . $inst['cep'] : ''])));
$cnpj = ($inst['cnpj'] ?? '') ? F::documentoFormatado((string) $inst['cnpj']) : '';
$titulos = [
  'proposta' => 'Proposta de acordo nº ' . ($imp['proposta']['id'] ?? ''),
  'acordo' => 'Acordo nº ' . ($imp['acordo']['numero'] ?? '') . ' — renegociação efetivada',
  'termo' => 'Termo de confissão de dívida — acordo nº ' . ($imp['acordo']['numero'] ?? ''),
];
$assinaturas = function () use ($h, $pag, $instNome) {
  return '<table class="assin"><tr><td><div class="linha"></div>' . $h($pag['nome']) . '<br><small>' . ($pag['aluno'] ? 'Devedor(a)' : 'Responsável financeiro(a) — devedor(a)') . '</small></td>'
    . '<td><div class="linha"></div>' . $h($instNome) . '<br><small>Credora</small></td></tr>'
    . '<tr><td><div class="linha"></div>Testemunha 1 — nome e CPF</td><td><div class="linha"></div>Testemunha 2 — nome e CPF</td></tr></table>';
};
$tabTitulos = function (array $linhas) use ($h, $brl, $dt) {
  $s = '<table class="t"><thead><tr><th>Título</th><th>Vencimento</th><th>Descrição</th><th class="n">Original</th><th class="n">Juros</th><th class="n">Multa</th><th class="n">Atualizado</th></tr></thead><tbody>';
  $tot = [0, 0, 0, 0];
  foreach ($linhas as $l) {
    $o = (int) $l['original_centavos']; $j = (int) $l['juros_centavos']; $m = (int) $l['multa_centavos'];
    $tot[0] += $o; $tot[1] += $j; $tot[2] += $m; $tot[3] += $o + $j + $m;
    $s .= '<tr><td>' . $h($l['id'] ?? $l['titulo_id']) . '</td><td>' . $h($dt($l['vencimento'])) . '</td><td>' . $h($l['descricao']) . '</td><td class="n">' . $h($brl($o)) . '</td><td class="n">' . $h($brl($j)) . '</td><td class="n">' . $h($brl($m)) . '</td><td class="n">' . $h($brl($o + $j + $m)) . '</td></tr>';
  }
  return $s . '</tbody><tfoot><tr><td colspan="3">Total</td><td class="n">' . $h($brl($tot[0])) . '</td><td class="n">' . $h($brl($tot[1])) . '</td><td class="n">' . $h($brl($tot[2])) . '</td><td class="n">' . $h($brl($tot[3])) . '</td></tr></tfoot></table>';
};
$tabParcelas = function (array $linhas, string $cv = 'vencimento', string $cvalor = 'valor_centavos', string $cn = 'numero', string $ct = 'total') use ($h, $brl, $dt) {
  $s = '<table class="t"><thead><tr><th>Parcela</th><th>Vencimento</th><th class="n">Valor</th></tr></thead><tbody>';
  foreach ($linhas as $x) {
    $s .= '<tr><td>' . $h(RN::rotuloParcela((int) $x[$cn], (int) $x[$ct])) . '</td><td>' . $h($dt($x[$cv])) . '</td><td class="n">' . $h($brl($x[$cvalor])) . '</td></tr>';
  }
  return $s . '</tbody></table>';
};
ob_start();
?><!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><title><?= $h($titulos[$tipo]) ?></title>
<style>
  @page { margin: 18mm 16mm; }
  body { font-family: Helvetica, Arial, sans-serif; font-size: 10.5pt; color: #222; }
  h1 { font-size: 14pt; margin: 0 0 4px; } h2 { font-size: 11.5pt; margin: 14px 0 6px; border-bottom: 1px solid #999; padding-bottom: 2px; }
  .cab { border-bottom: 2px solid #333; padding-bottom: 6px; margin-bottom: 10px; } .cab small { color: #555; }
  table.t { width: 100%; border-collapse: collapse; margin: 4px 0 8px; } table.t th, table.t td { border: 1px solid #bbb; padding: 3px 5px; font-size: 9.5pt; text-align: left; }
  table.t th { background: #eee; } .n { text-align: right !important; white-space: nowrap; } tfoot td { font-weight: bold; }
  table.res td { padding: 2px 10px 2px 0; } .demo { border: 2px solid #b00; color: #b00; text-align: center; font-weight: bold; padding: 4px; margin-bottom: 8px; }
  table.assin { width: 100%; margin-top: 34px; } table.assin td { width: 50%; text-align: center; padding: 26px 12px 0; vertical-align: top; font-size: 9.5pt; }
  .linha { border-top: 1px solid #333; margin-bottom: 3px; } .clausula { text-align: justify; margin: 5px 0; } .marca { font-weight: bold; }
</style></head><body>
<?php if (!empty($imp['demonstracao'])) : ?><div class="demo">DEMONSTRAÇÃO — documento fictício, sem validade</div><?php endif; ?>
<div class="cab"><h1><?= $h($titulos[$tipo]) ?></h1>
  <div><strong><?= $h($instNome) ?></strong><?= $cnpj ? ' · CNPJ ' . $h($cnpj) : '' ?></div><?php if ($instEnd !== '') : ?><small><?= $h($instEnd) ?></small><?php endif; ?></div>
<table class="res">
  <tr><td>Aluno(a):</td><td><strong><?= $h($nomeAluno) ?></strong> (<?= $h($rotNum) ?> <?= $h($a['rm']) ?>)</td></tr>
  <tr><td><?= $pag['aluno'] ? 'Devedor(a):' : 'Responsável financeiro(a):' ?></td><td><?= $h($pag['nome']) ?><?= !empty($pag['cpf']) ? ' · CPF ' . $h($pag['cpf']) : '' ?></td></tr>
</table>

<?php if ($tipo === 'proposta') : $p = $imp['proposta']; ?>
  <h2>Os títulos</h2>
  <?= $tabTitulos(array_map(static fn($l) => $l + ['id' => $l['titulo_id']], $p['titulos'])) ?>
  <table class="res">
    <tr><td>Total original</td><td class="n"><?= $h($brl($p['original_centavos'])) ?></td></tr>
    <tr><td>Encargos (juros e multa)</td><td class="n"><?= $h($brl((int) $p['juros_centavos'] + (int) $p['multa_centavos'])) ?></td></tr>
    <?php if ((int) $p['acrescimo_centavos']) : ?><tr><td>Acréscimo</td><td class="n"><?= $h($brl($p['acrescimo_centavos'])) ?></td></tr><?php endif; ?>
    <?php if ((int) $p['desconto_centavos']) : ?><tr><td>Desconto</td><td class="n">− <?= $h($brl($p['desconto_centavos'])) ?></td></tr><?php endif; ?>
    <tr><td><strong>Valor a renegociar</strong></td><td class="n"><strong><?= $h($brl($p['total_centavos'])) ?></strong></td></tr>
    <tr><td>Conta feita em</td><td><?= $h($dt($p['data_renegociacao'])) ?> · <span class="marca">esta proposta vale até <?= $h($dt($p['data_renegociacao'])) ?></span></td></tr>
  </table>
  <h2>Condição 1 — parcelada <?= (int) $p['p_interesse'] ? '(com interesse manifestado)' : '' ?></h2>
  <div><?= (int) $p['p_entrada_centavos'] ? 'Entrada de ' . $h($brl($p['p_entrada_centavos'])) . ' em ' . $h($dt($p['p_entrada_vencimento'])) . ' e ' : '' ?><?= $h($p['p_parcelas']) ?> parcela(s), com vencimento todo dia <?= $h($p['p_dia']) ?>.</div>
  <?= $tabParcelas($p['parcelas']['parcelada']) ?>
  <h2>Condição 2 — à vista <?= (int) $p['v_interesse'] ? '(com interesse manifestado)' : '' ?></h2>
  <div><?= (int) $p['v_desconto_centavos'] ? 'Desconto de ' . $h($brl($p['v_desconto_centavos'])) . '; ' : '' ?>valor a pagar <?= $h($brl($p['v_total_centavos'])) ?>, com vencimento em <?= $h($dt($p['v_vencimento'])) ?>.</div>
  <?= $tabParcelas($p['parcelas']['avista']) ?>
  <p class="clausula">A família escolhe uma das condições acima. A proposta não altera os títulos originais enquanto não for efetivada; depois da validade, uma proposta nova é feita com a conta do dia. As parcelas do acordo efetivado pagam juros de mora em caso de atraso e não pagam multa.</p>
  <?php if ($p['motivo']) : ?><div><small>Motivo: <?= $h($p['motivo']) ?><?= $p['observacao'] ? ' · ' . $h($p['observacao']) : '' ?></small></div><?php endif; ?>
  <?= $assinaturas() ?>

<?php else : $r = $imp['acordo']; $cond = $r['condicao'] ? RN::CONDICOES[$r['condicao']] : 'Parcelada'; ?>
  <?php if ($tipo === 'acordo') : ?>
    <?php if ($r['situacao'] === 'desfeita') : ?><div class="demo">ACORDO DESFEITO em <?= $h($dt($r['desfeita_em'])) ?><?= $r['motivo_desfazer'] ? ': ' . $h($r['motivo_desfazer']) : '' ?></div><?php endif; ?>
    <h2>Os títulos renegociados</h2>
    <?= $tabTitulos($r['originais']) ?>
    <table class="res">
      <tr><td>Total com os encargos</td><td class="n"><?= $h($brl($r['total_centavos'])) ?></td></tr>
      <?php if ((int) $r['desconto_centavos']) : ?><tr><td>Desconto</td><td class="n">− <?= $h($brl($r['desconto_centavos'])) ?></td></tr><?php endif; ?>
      <tr><td><strong>Valor do acordo</strong></td><td class="n"><strong><?= $h($brl((int) $r['total_centavos'] - (int) $r['desconto_centavos'])) ?></strong></td></tr>
      <tr><td>Condição</td><td><?= $h($cond) ?><?= (int) $r['entrada_centavos'] ? ', com entrada de ' . $h($brl($r['entrada_centavos'])) : '' ?></td></tr>
      <tr><td>Efetivado em</td><td><?= $h(date('d/m/Y H:i', strtotime((string) $r['criado_em']))) ?><?= $r['usuario'] ? ' por ' . $h($r['usuario']) : '' ?></td></tr>
    </table>
    <h2>Os lançamentos do acordo</h2>
    <?= $tabParcelas($r['parcelas'], 'vencimento', 'valor_centavos', 'parcela', 'parcelas_total') ?>
    <p class="clausula">Os títulos acima ficam renegociados e passam a ser cobrados pelos lançamentos do acordo. Em caso de atraso, cada lançamento do acordo paga juros de mora e não paga multa.</p>
    <?= $assinaturas() ?>
  <?php else : $valorAcordo = (int) $r['total_centavos'] - (int) $r['desconto_centavos']; ?>
    <p class="clausula"><span class="marca">DEVEDOR(A):</span> <?= $h($pag['nome']) ?><?= !empty($pag['cpf']) ? ', CPF ' . $h($pag['cpf']) : '' ?><?= !empty($pag['endereco']) ? ', residente em ' . $h($pag['endereco']) : '' ?><?= $pag['aluno'] ? '' : ', responsável financeiro(a) pelo(a) aluno(a) ' . $h($nomeAluno) ?>.</p>
    <p class="clausula"><span class="marca">CREDORA:</span> <?= $h($instNome) ?><?= $cnpj ? ', CNPJ ' . $h($cnpj) : '' ?><?= $instEnd !== '' ? ', com sede em ' . $h($instEnd) : '' ?>.</p>
    <p class="clausula"><span class="marca">Cláusula 1ª.</span> O(A) DEVEDOR(A) reconhece e confessa dever à CREDORA a quantia de <strong><?= $h($brl($valorAcordo)) ?></strong> (<?= $h(F::porExtenso($valorAcordo)) ?>), referente aos títulos relacionados abaixo, com os encargos calculados até a data do acordo.</p>
    <?= $tabTitulos($r['originais']) ?>
    <p class="clausula"><span class="marca">Cláusula 2ª.</span> A dívida será paga na condição <?= $h(mb_strtolower($cond)) ?>, conforme os lançamentos abaixo:</p>
    <?= $tabParcelas($r['parcelas'], 'vencimento', 'valor_centavos', 'parcela', 'parcelas_total') ?>
    <p class="clausula"><span class="marca">Cláusula 3ª.</span> O atraso no pagamento de qualquer lançamento sujeita o valor em atraso a juros de mora, conforme as regras financeiras da CREDORA, sem multa.</p>
    <p class="clausula"><span class="marca">Cláusula 4ª.</span> O pagamento de todos os lançamentos quita os títulos renegociados. Se o acordo for desfeito, o que só acontece antes do pagamento de qualquer lançamento, os títulos originais voltam a ser devidos.</p>
    <p class="clausula"><span class="marca">Cláusula 5ª.</span> As partes assinam este termo, com duas testemunhas, em duas vias de igual teor.</p>
    <p><?= $h(($inst['cidade'] ?? '') ? $inst['cidade'] . ', ' : '') ?><?= $h($dt(substr((string) $r['criado_em'], 0, 10))) ?>.</p>
    <?= $assinaturas() ?>
  <?php endif; ?>
<?php endif; ?>
</body></html>
<?php $html = ob_get_clean();
