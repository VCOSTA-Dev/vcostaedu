<?php
/*
 * ONDA 4, ETAPA 3 — a média da escola em dois níveis (\Core\MediaEscola), direto na função pura: os exemplos do
 * pedido e as regras. Imprime um JSON {caso: resultado} que a prova (etapa3.js) confere.
 *   php tests/tabler/onda4/media_escola.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__, 3) . '/core/MotorMedia.php';
require dirname(__DIR__, 3) . '/core/MediaEscola.php';

use Core\MediaEscola as E;
use Core\MotorMedia as M;

$regra = M::regraPadrao(1, '6.00');
$conf = ['mediaMinima' => '6.00', 'recuperacao' => 'maior', 'composicao' => 'simples', 'pesos' => [], 'frequenciaMinima' => '75.00', 'frequenciaBase' => 'componente'];
/** Uma etapa com uma atividade de nota $n (texto "4.0"), a recuperação $r e fechada. */
$etapa = static fn(int $ordem, ?string $n, ?string $r = null) => ['id' => $ordem, 'ordem' => $ordem, 'nome' => "{$ordem}º bimestre", 'fechada' => true,
    'atividades' => [['id' => $ordem, 'nome' => 'Prova', 'nota' => $n === null ? null : M::centesimosDe($n), 'peso' => '10', 'componente' => null]],
    'recuperacao' => $r === null ? null : M::centesimosDe($r), 'gravada' => null];
$ctx = ['final' => true, 'faltas' => 0, 'compensadas' => 0, 'encontros' => 100, 'frequenciaAno' => null, 'conselho' => null];
$saida = [];
$nota = static fn(array $r, int $ordem) => $r['etapas'][$ordem]['nota'];

/* a recuperação: a maior (o padrão) */
$r = E::calcular([$etapa(1, '4.0', '5.0'), $etapa(2, '4.5', '4.0')], $regra, $conf, $ctx);
$saida['maior_4_5'] = $nota($r, 1);
$saida['maior_45_4'] = $nota($r, 2);
$saida['em_recuperacao'] = $r['etapas'][1]['emRecuperacao'];
/* a média das duas */
$r = E::calcular([$etapa(1, '4.0', '5.0')], $regra, ['recuperacao' => 'media'] + $conf, $ctx);
$saida['media_4_5'] = $nota($r, 1);
/* a maior, com teto na média mínima */
$r = E::calcular([$etapa(1, '4.0', '8.0'), $etapa(2, '5.5', '9.0')], $regra, ['recuperacao' => 'maior_teto'] + $conf, $ctx);
$saida['teto_4_8'] = $nota($r, 1);
$saida['teto_55_9'] = $nota($r, 2);
/* acima da média mínima: não há recuperação (a nota lançada por engano não conta) */
$r = E::calcular([$etapa(1, '7.0', '9.0')], $regra, $conf, $ctx);
$saida['sem_recuperacao_7'] = $nota($r, 1);
/* a média anual simples e com pesos */
$quatro = [$etapa(1, '5.0'), $etapa(2, '6.0'), $etapa(3, '7.0'), $etapa(4, '8.0')];
$r = E::calcular($quatro, $regra, $conf, $ctx);
$saida['anual_simples'] = $r['media'];
$r = E::calcular($quatro, $regra, ['composicao' => 'pesos', 'pesos' => [1 => 1, 2 => 1, 3 => 2, 4 => 2]] + $conf, $ctx);
$saida['anual_pesos'] = $r['media'];
/* reprovado por falta com nota suficiente */
$r = E::calcular([$etapa(1, '8.0'), $etapa(2, '9.0')], $regra, $conf, ['faltas' => 30] + $ctx);
$saida['falta_media'] = $r['media'];
$saida['falta_frequencia'] = $r['frequencia'];
$saida['falta_situacao'] = $r['situacao'];
/* reprovado por nota; o conselho aprova (a nota não muda) */
$r = E::calcular([$etapa(1, '4.0'), $etapa(2, '5.0')], $regra, $conf, $ctx);
$saida['nota_situacao'] = $r['situacao'];
$saida['nota_media'] = $r['media'];
$r = E::calcular([$etapa(1, '4.0'), $etapa(2, '5.0')], $regra, $conf, ['conselho' => ['observacao' => 'Evolução no segundo semestre (prova)', 'por' => 'Coordenação (prova)', 'em' => '2026-12-20']] + $ctx);
$saida['conselho_situacao'] = $r['situacao'];
$saida['conselho_media'] = $r['media'];
$saida['conselho_resultado'] = $r['resultado'];
$saida['conselho_memoria'] = (bool) array_filter($r['memoria']['linhas'], fn($l) => str_contains($l, 'Conselho de classe: aprovado'));
/* aprovado direto */
$r = E::calcular([$etapa(1, '7.0'), $etapa(2, '8.0')], $regra, $conf, $ctx);
$saida['aprovado'] = $r['situacao'];
/* o fechamento do ano com uma etapa sem nenhuma atividade (sem nota): pendente. (Atividade sem a nota do aluno, numa
   etapa fechada, vale 0 — a regra "sem nota" do motor.) */
$r = E::calcular([$etapa(1, '7.0'), ['atividades' => []] + $etapa(2, null)], $regra, $conf, $ctx);
$saida['sem_nota_vale_zero'] = E::calcular([$etapa(1, '7.0'), $etapa(2, null)], $regra, $conf, $ctx)['etapas'][2]['nota'];
$saida['pendente'] = $r['situacao'];
/* a frequência no total de horas do ano (a base padrão) */
$r = E::calcular([$etapa(1, '7.0')], $regra, ['frequenciaBase' => 'ano'] + $conf, ['frequenciaAno' => '70.00'] + $ctx);
$saida['ano_situacao'] = $r['situacao'];
/* a memória dos dois níveis */
$r = E::calcular([$etapa(1, '4.0', '5.0'), $etapa(2, '6.0')], $regra, $conf, $ctx);
$saida['memoria'] = $r['memoria']['linhas'];
echo json_encode($saida, JSON_UNESCAPED_UNICODE), "\n";
