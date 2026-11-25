<?php
/*
 * ONDA 4, ETAPA 5 — o acréscimo no motor (core/MotorMedia.php), direto na função pura: sem a opção, o cálculo é o de
 * sempre; com ela, os exemplos, o teto, o limite e a ordem (antes do arredondamento); na escola, o limite por etapa.
 * Imprime um JSON que a prova (etapa5.js) confere.
 *   php tests/tabler/onda4/acrescimo.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__, 3) . '/core/MotorMedia.php';
require dirname(__DIR__, 3) . '/core/MediaEscola.php';

use Core\MotorMedia as M;

$base = M::regraPadrao(1, '6.00');
$com = static fn(?string $lim) => $base + ['acrescimo' => ['permitir' => true, 'limite' => $lim]];
$ativ = static fn(string $nota, bool $acr = false, int $id = 1) => ['id' => $id, 'nome' => $acr ? 'Acréscimo' : 'Prova', 'nota' => M::centesimosDe($nota), 'peso' => $acr ? '0' : '10', 'componente' => null]
    + ($acr ? ['acrescimo' => true] : []);
$ctx = ['final' => true, 'encontros' => null, 'faltas' => 0, 'compensadas' => 0, 'frequenciaMinima' => '75.00'];
$saida = [];
/* sem a opção: a atividade de acréscimo não muda nada (o cálculo de sempre) */
$sem = M::calcular([$ativ('5.0')], $base, $ctx);
$semComAtiv = M::calcular([$ativ('5.0'), $ativ('1.0', true, 2)], $base, $ctx);
$saida['sem_opcao_igual'] = $sem['media'] === $semComAtiv['media'] && $sem['memoria']['linhas'] === $semComAtiv['memoria']['linhas'];
$saida['sem_opcao_media'] = $semComAtiv['media'];
/* com a opção: 5,0 + 1,0 → 6,0 */
$r = M::calcular([$ativ('5.0'), $ativ('1.0', true, 2)], $com(null), $ctx);
$saida['exemplo'] = $r['media'];
$saida['memoria'] = array_values(array_filter($r['memoria']['linhas'], fn($l) => str_starts_with($l, 'Acréscimo')));
/* o teto: 9,5 + 1,0 → 10,0 */
$saida['teto'] = M::calcular([$ativ('9.5'), $ativ('1.0', true, 2)], $com(null), $ctx)['media'];
/* o limite: 0,5 (lançado 1,0) */
$saida['limite'] = M::calcular([$ativ('5.0'), $ativ('1.0', true, 2)], $com('0.50'), $ctx)['media'];
/* dois acréscimos somam, até o limite */
$saida['dois_sem_limite'] = M::calcular([$ativ('5.0'), $ativ('0.6', true, 2), $ativ('0.6', true, 3)], $com(null), $ctx)['media'];
/* antes do arredondamento: média 5,04 (5,0 com 1 casa) + 0,04 = 5,08 → 5,1 (depois do arredondamento daria 5,0 + 0,04 → 5,0) */
$regra2 = M::regraPadrao(1, '6.00');
$regra2['componentes'][0]['modo'] = 'aritmetica';
$r = M::calcular([$ativ('5.04'), $ativ('0.04', true, 2)], $regra2 + ['acrescimo' => ['permitir' => true, 'limite' => null]], $ctx);
$saida['antes_do_arredondamento'] = $r['media'];
/* fora dos componentes: o acréscimo não conta como atividade do componente */
$saida['lancadas'] = M::calcular([$ativ('5.0'), $ativ('1.0', true, 2)], $com(null), $ctx)['lancadas'];
/* a escola: o limite vale por etapa (0,5 em cada uma) */
$etapa = static fn(int $o, string $n, string $acr) => ['id' => $o, 'ordem' => $o, 'nome' => "{$o}º bimestre", 'fechada' => true, 'recuperacao' => null, 'gravada' => null,
    'atividades' => [$ativ($n, false, $o * 10), $ativ($acr, true, $o * 10 + 1)]];
$conf = ['mediaMinima' => '6.00', 'recuperacao' => 'maior', 'composicao' => 'simples', 'pesos' => [], 'frequenciaMinima' => '75.00', 'frequenciaBase' => 'componente'];
$e = \Core\MediaEscola::calcular([$etapa(1, '5.0', '1.0'), $etapa(2, '7.0', '1.0')], $com('0.50'), $conf, ['final' => true, 'faltas' => 0, 'compensadas' => 0, 'encontros' => 100, 'frequenciaAno' => null, 'conselho' => null]);
$saida['escola_etapa1'] = $e['etapas'][1]['nota'];
$saida['escola_etapa2'] = $e['etapas'][2]['nota'];
$saida['escola_anual'] = $e['media'];
/* a validação do limite */
$saida['valida_limite'] = M::validar($com('11'));
echo json_encode($saida, JSON_UNESCAPED_UNICODE), "\n";
