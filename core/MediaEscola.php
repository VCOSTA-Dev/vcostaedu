<?php
namespace Core;

/**
 * =============================================================================
 * A MÉDIA DA ESCOLA EM DOIS NÍVEIS (Onda 4, Etapa 3; docs/ESCOLA.md, D-48) — uma função PURA, por cima do motor.
 * =============================================================================
 *
 * O núcleo do motor (MotorMedia) NÃO muda: esta camada o chama UMA VEZ POR ETAPA, com a regra da oferta, sobre as
 * atividades daquela etapa, e compõe o resto. Como o motor, nada aqui lê banco, sessão ou parâmetro: tudo entra pelos
 * argumentos, e tudo o que foi decidido sai na memória de cálculo. Painel e portal usam este mesmo arquivo; a fachada
 * AdmsCalculoNotas lê o banco e o chama.
 *
 * ENTRADA
 * - `$etapas`: lista, na ordem, de ['id', 'ordem', 'nome', 'fechada' => bool, 'atividades' => [formato do motor],
 *   'recuperacao' => ?int (centésimos), 'gravada' => ?array (a nota da etapa guardada no fechamento: media, recuperacao,
 *   nota)]. Correção da escola, Parte 3 (A2; D-102): 'transferencia' => ?['nota' => "7.50", 'escola' => texto] (a nota
 *   trazida da escola de origem: vale como a nota da etapa, marcada) e 'antesDaMatricula' => bool (a etapa terminou antes da
 *   matrícula: sem nota, fora da média anual e sem pendência).
 * - `$regra`: a regra do motor (a da oferta).
 * - `$conf`: ['mediaMinima' => "6.00", 'recuperacao' => maior|media|maior_teto, 'composicao' => simples|pesos,
 *   'pesos' => [ordem => int], 'frequenciaMinima' => "75.00", 'frequenciaBase' => ano|componente].
 * - `$ctx`: ['final' => bool (o fechamento do ano), 'faltas', 'compensadas', 'encontros' (da oferta), 'frequenciaAno' =>
 *   ?string (o percentual no total de horas do ano, quando a base é o ano), 'conselho' => ?['observacao', 'por', 'em']].
 *
 * CONTA
 * 1. Média da etapa = o motor sobre as atividades da etapa (final se a etapa está fechada; senão, parcial). Etapa
 *    fechada com a nota guardada: vale a guardada (a regra copiada no fechamento).
 * 2. Recuperação: a etapa abaixo da média mínima fica EM RECUPERAÇÃO; com a nota da recuperação lançada, a nota da etapa
 *    é a MAIOR entre a média e a recuperação (o padrão), ou a MÉDIA das duas (arredondada como a regra), ou a MAIOR com a
 *    recuperação limitada à média mínima. Sem nota de recuperação, a nota da etapa é a média.
 * 3. Média anual = a composição das notas das etapas: média simples (o padrão) ou ponderada pelos pesos; frações exatas,
 *    arredondada como a regra da oferta. No ano em curso, só as etapas com nota; no fechamento do ano, toda etapa precisa
 *    de nota (senão, pendência).
 * 4. Frequência: no total de horas do ano (a base padrão; vem pronta em `frequenciaAno`) ou na oferta (o motor).
 * 5. Situação (só no fechamento do ano): frequência abaixo do mínimo → REPROVADO POR FALTA (mesmo com nota); senão,
 *    média anual abaixo da mínima → REPROVADO POR NOTA; senão APROVADO. O CONSELHO aprova quem foi reprovado: a nota
 *    não muda, a situação passa a APROVADO PELO CONSELHO, e a memória mostra a decisão (observação, quem, quando).
 * =============================================================================
 */
final class MediaEscola
{
    /** As regras que a escola recebe prontas (o padrão); a escola ajusta por tela (esc_configuracao guarda só o que mudou). */
    public const PADRAO = [
        'media.minima' => '6.00',          // a média mínima da instituição (a aprovação e a recuperação da etapa)
        'recuperacao.regra' => 'maior',    // maior | media | maior_teto (a maior, com teto na média mínima)
        'anual.composicao' => 'simples',   // simples | pesos
        'anual.pesos' => '',               // "1,1,2,2": o peso de cada etapa, na ordem (com a composição "pesos")
        'frequencia.base' => 'ano',        // ano (o total de horas do ano letivo) | componente (cada componente)
        'frequencia.minima' => '75.00',
        'frequencia.fonte' => 'etapa',     // etapa (as faltas lançadas por etapa) | chamada (a chamada por aula; Onda 6, Etapa 5)
        /* Onda 7, Etapa 2: os responsáveis (as mesmas Regras da escola; o cálculo não os lê) */
        'responsaveis.adicional' => '1',          // o acesso acadêmico adicional: permitido (1) ou não (0)
        'responsaveis.adicional_limite' => '0',   // quantos por aluno e por ano (0 = sem limite)
        'responsaveis.convite_dias' => '7',       // a validade do convite de primeiro acesso, em dias
        'responsaveis.mensagem' => 'Olá, {nome}! A escola convida você a acompanhar {alunos} no portal. Use este link para criar a sua senha (vale até {validade}): {link}',
    ];

    public const APROVADO = MotorMedia::APROVADO;
    public const REPROVADO = MotorMedia::REPROVADO;
    public const SITUACOES = [
        'aprovado' => 'Aprovado',
        'reprovado_nota' => 'Reprovado por nota',
        'reprovado_falta' => 'Reprovado por falta',
        'aprovado_conselho' => 'Aprovado pelo conselho',
        'em_curso' => 'Em curso',
        'pendente' => 'Pendente',
    ];

    public static function calcular(array $etapas, array $regra, array $conf, array $ctx): array
    {
        $final = !empty($ctx['final']);
        $casas = (int) $regra['casas'];
        $modo = (string) $regra['arredondamento'];
        $minima = MotorMedia::centesimosDe((string) $conf['mediaMinima']);
        $linhas = [];
        $saidaEtapas = [];
        $lancadas = 0;
        $totalAtiv = 0;
        $somaPond = 0;   // Σ nota × peso (centésimos)
        $somaPesos = 0;
        $partes = [];
        $semNota = [];
        foreach ($etapas as $e) {
            $ordem = (int) $e['ordem'];
            $nome = (string) $e['nome'];
            $fechada = !empty($e['fechada']);
            $totalAtiv += count($e['atividades'] ?? []);
            /* D-102 (A2): a nota trazida da escola de origem vale como a nota da etapa (antes de tudo: entra mesmo com a
               etapa fechada); a etapa que terminou antes da matrícula, sem ela, fica sem nota e fora da média */
            if (!empty($e['transferencia'])) {
                $nota = number_format(MotorMedia::centesimosDe((string) $e['transferencia']['nota']) / 100, $casas, '.', '');
                $saidaEtapas[$ordem] = ['id' => (int) $e['id'], 'ordem' => $ordem, 'nome' => $nome, 'fechada' => $fechada, 'media' => $nota,
                    'emRecuperacao' => false, 'recuperacao' => null, 'nota' => $nota, 'transferencia' => (string) ($e['transferencia']['escola'] ?? '')];
                $linhas[] = "{$nome}: nota trazida da escola de origem" . (($e['transferencia']['escola'] ?? '') !== '' ? ' (' . $e['transferencia']['escola'] . ')' : '') . ' → nota da etapa ' . self::v($nota);
                $peso = $conf['composicao'] === 'pesos' ? max(1, (int) ($conf['pesos'][$ordem] ?? 1)) : 1;
                $somaPond += MotorMedia::centesimosDe($nota) * $peso;
                $somaPesos += $peso;
                $partes[] = self::v($nota) . ($conf['composicao'] === 'pesos' ? ' × ' . $peso : '');
                continue;
            }
            if (!empty($e['antesDaMatricula']) || !empty($e['depoisDaSaida'])) {
                /* D-117: a etapa que começou depois da saída do aluno (a transferência) fica fora da conta, como a de antes da matrícula */
                $depois = empty($e['antesDaMatricula']);
                $saidaEtapas[$ordem] = ['id' => (int) $e['id'], 'ordem' => $ordem, 'nome' => $nome, 'fechada' => $fechada, 'media' => null,
                    'emRecuperacao' => false, 'recuperacao' => null, 'nota' => null, 'antesDaMatricula' => !$depois] + ($depois ? ['depoisDaSaida' => true] : []);
                $linhas[] = $depois ? "{$nome}: depois da saída do aluno — sem nota, fora da média anual" : "{$nome}: antes da matrícula do aluno — sem nota, fora da média anual";
                continue;
            }
            if ($fechada && !empty($e['gravada'])) {
                $g = $e['gravada'];
                $mediaEt = $g['media'];
                $rec = $g['recuperacao'];
                $nota = $g['nota'];
                $emRec = $mediaEt !== null && MotorMedia::centesimosDe($mediaEt) < $minima;
                $texto = "{$nome}: média " . self::v($mediaEt) . ($rec !== null ? ', recuperação ' . self::v($rec) : '') . ' → nota da etapa ' . self::v($nota) . ' (guardada no fechamento da etapa)';
                $lancadas += (int) ($g['lancadas'] ?? 0);
            } else {
                $m = MotorMedia::calcular($e['atividades'] ?? [], $regra, ['final' => $fechada, 'encontros' => null, 'faltas' => 0, 'compensadas' => 0, 'frequenciaMinima' => '0']);
                $lancadas += (int) $m['lancadas'];
                $mediaEt = $m['media'];
                $recC = $e['recuperacao'] ?? null;
                $rec = $recC === null ? null : number_format($recC / 100, $casas, '.', '');
                $emRec = $mediaEt !== null && MotorMedia::centesimosDe($mediaEt) < $minima;
                [$nota, $como] = self::notaDaEtapa($mediaEt, $emRec ? $recC : null, $minima, (string) $conf['recuperacao'], $casas, $modo);
                $texto = "{$nome}: " . ($mediaEt === null ? 'sem média' : 'média ' . self::v($mediaEt) . ' (' . ($m['conta'] ?? '') . ')')
                    . ($emRec ? ($recC === null ? ' — abaixo de ' . self::v((string) $conf['mediaMinima']) . ': em recuperação' : '; recuperação ' . self::v($rec) . ' → ' . $como) : '')
                    . ($nota !== null ? ' → nota da etapa ' . self::v($nota) : '');
            }
            $saidaEtapas[$ordem] = ['id' => (int) $e['id'], 'ordem' => $ordem, 'nome' => $nome, 'fechada' => $fechada, 'media' => $mediaEt,
                'emRecuperacao' => $emRec, 'recuperacao' => $rec, 'nota' => $nota];
            $linhas[] = $texto;
            if ($nota === null) {
                $semNota[] = $nome;
                continue;
            }
            $peso = $conf['composicao'] === 'pesos' ? max(1, (int) ($conf['pesos'][$ordem] ?? 1)) : 1;
            $somaPond += MotorMedia::centesimosDe($nota) * $peso;
            $somaPesos += $peso;
            $partes[] = self::v($nota) . ($conf['composicao'] === 'pesos' ? ' × ' . $peso : '');
        }

        /* a média anual */
        $media = null;
        $pendencias = [];
        if ($final && $semNota) {
            $pendencias[] = 'sem nota em ' . implode(', ', $semNota);
        }
        if ($somaPesos > 0) {
            $media = MotorMedia::arredondarFracao($somaPond, 100 * $somaPesos, $casas, $modo);
            $linhas[] = 'Média anual = (' . implode(' + ', $partes) . ') ÷ ' . $somaPesos . ' = ' . self::v($media)
                . ($conf['composicao'] === 'pesos' ? ' (ponderada pelas etapas)' : '') . ($final ? '' : ' (parcial: só as etapas com nota)');
        } else {
            $linhas[] = 'Média anual: nenhuma etapa com nota.';
        }

        /* a frequência */
        $faltas = (int) ($ctx['faltas'] ?? 0);
        $compensadas = (int) ($ctx['compensadas'] ?? 0);
        $encontros = $ctx['encontros'] ?? null;
        if (($conf['frequenciaBase'] ?? 'ano') === 'ano' && ($ctx['frequenciaAno'] ?? null) !== null) {
            $frequencia = (string) $ctx['frequenciaAno'];
            $linhas[] = 'Frequência no ano letivo (todos os componentes, no total de horas): ' . self::v($frequencia) . '%';
        } elseif ($encontros !== null && $encontros > 0) {
            $efetivas = max(0, min((int) $encontros, $faltas - $compensadas));
            $frequencia = MotorMedia::arredondarFracao(((int) $encontros - $efetivas) * 100, (int) $encontros, 2, 'meio_para_cima');
            $linhas[] = 'Frequência no componente = 1 − (' . $faltas . ' − ' . $compensadas . ') ÷ ' . $encontros . ' = ' . self::v($frequencia) . '%';
        } else {
            $frequencia = null;
        }

        /* a situação */
        $resultado = null;
        $motivo = null;
        $situacao = $final ? 'pendente' : 'em_curso';
        $conselho = $ctx['conselho'] ?? null;
        if ($final && !$pendencias && $media !== null && $frequencia !== null) {
            $freqOk = MotorMedia::frequenciaSuficiente($frequencia, (string) $conf['frequenciaMinima']);
            $notaOk = MotorMedia::centesimosDe($media) >= $minima;
            if (!$freqOk) {
                $situacao = 'reprovado_falta';
                $motivo = 'frequencia';
            } elseif (!$notaOk) {
                $situacao = 'reprovado_nota';
                $motivo = 'nota';
            } else {
                $situacao = 'aprovado';
            }
            $linhas[] = 'Situação: média anual ' . self::v($media) . ($notaOk ? ' ≥ ' : ' < ') . self::v((string) $conf['mediaMinima'])
                . ' e frequência ' . self::v($frequencia) . '%' . ($freqOk ? ' ≥ ' : ' < ') . self::v((string) $conf['frequenciaMinima']) . '% → ' . self::SITUACOES[$situacao];
            if ($situacao !== 'aprovado' && $conselho) {
                $situacao = 'aprovado_conselho';
                $motivo = 'conselho';
                $linhas[] = 'Conselho de classe: aprovado, em ' . (string) ($conselho['em'] ?? '') . ', por ' . (string) ($conselho['por'] ?? '') . ' — "' . (string) ($conselho['observacao'] ?? '') . '". A nota não muda.';
            }
            $resultado = in_array($situacao, ['aprovado', 'aprovado_conselho'], true) ? self::APROVADO : self::REPROVADO;
        } elseif ($pendencias) {
            $linhas[] = 'Situação: pendente — ' . implode('; ', $pendencias) . '.';
        }

        return [
            'lancadas' => $lancadas,
            'atividades' => $totalAtiv,
            'soma' => null,
            'media' => $media,
            'parcial' => !$final,
            'conta' => $partes ? '(' . implode(' + ', $partes) . ') ÷ ' . $somaPesos : '',
            'faltas' => $faltas,
            'compensadas' => $compensadas,
            'encontros' => $encontros,
            'frequencia' => $frequencia,
            'resultado' => $resultado,
            'motivo' => $motivo,
            'pendencias' => $pendencias,
            'faltam' => [],
            'motivosPendencia' => $pendencias,
            'mediaProvisoria' => false,
            'etapas' => $saidaEtapas,
            'situacao' => $situacao,
            'conselho' => $conselho,
            'memoria' => [
                'final' => $final,
                'escola' => true,
                'regra' => ['casas' => $casas, 'arredondamento' => $modo, 'notaMinima' => (string) $conf['mediaMinima'], 'recuperacao' => (string) $conf['recuperacao'],
                    'composicao' => (string) $conf['composicao'], 'frequenciaMinima' => (string) $conf['frequenciaMinima'], 'frequenciaBase' => (string) ($conf['frequenciaBase'] ?? 'ano')],
                'etapas' => $saidaEtapas,
                'media' => $media,
                'frequencia' => $frequencia,
                'situacao' => self::SITUACOES[$situacao],
                'pendencias' => $pendencias,
                'linhas' => $linhas,
            ],
        ];
    }

    /**
     * A nota da etapa: sem recuperação, a média; com ela, pela regra da escola.
     * @return array{0: ?string, 1: string} a nota (texto com ponto, as casas da regra) e como foi decidida
     */
    public static function notaDaEtapa(?string $media, ?int $recC, int $minimaC, string $regraRec, int $casas, string $modo): array
    {
        if ($media === null) {
            return [$recC === null ? null : number_format($recC / 100, $casas, '.', ''), 'só a recuperação'];
        }
        if ($recC === null) {
            return [$media, ''];
        }
        $m = MotorMedia::centesimosDe($media);
        switch ($regraRec) {
            case 'media':
                return [MotorMedia::arredondarFracao($m + $recC, 200, $casas, $modo), 'a média das duas'];
            case 'maior_teto':
                $r = min($recC, $minimaC);
                return [number_format(max($m, $r) / 100, $casas, '.', ''), 'a maior, com a recuperação limitada a ' . self::v(number_format($minimaC / 100, 2, '.', ''))];
            case 'maior':
            default:
                return [number_format(max($m, $recC) / 100, $casas, '.', ''), 'a maior das duas'];
        }
    }

    /** "6.50" → "6,5"; "6.00" → "6,0" (uma casa no mínimo). */
    public static function v(?string $n): string
    {
        if ($n === null || $n === '') {
            return '—';
        }
        [$i, $d] = array_pad(explode('.', $n, 2), 2, '0');
        $d = rtrim($d, '0');
        return $i . ',' . ($d === '' ? '0' : $d);
    }
}
