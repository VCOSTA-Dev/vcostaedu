<?php

namespace Core;

/**
 * =============================================================================
 * MOTOR DE MÉDIA (E3/E4, 1.13.0, D-391) — uma função PURA, num lugar só.
 * =============================================================================
 *
 * Painel e portal usam este mesmo arquivo (como core/Requisicao.php e core/Transacao.php); `AdmsCalculoNotas` é a
 * fachada que lê o banco, resolve a regra efetiva e chama `MotorMedia::calcular`. Nada aqui lê banco, sessão ou
 * parâmetro: tudo o que o cálculo usa entra pelos argumentos, e tudo o que ele decidiu sai na memória de cálculo.
 *
 * ENTRADA
 * - `$atividades`: lista de ['id' => int, 'nome' => string, 'nota' => ?int (centésimos: 8,50 = 850), 'peso' => string
 *   decimal ("10", "2.5"), 'componente' => ?string (código; vazio ou desconhecido = o primeiro componente da regra)].
 * - `$regra`: ['componentes' => [['codigo', 'nome', 'peso' (string decimal, soma 100), 'modo', 'n' (?string), 'tipoN'
 *   (quantidade|percentual), 'ordem']], 'casas' (1|2), 'arredondamento', 'momento', 'semNota', 'componenteVazio',
 *   'notaMinima' ("7.00")].
 * - `$ctx`: ['final' => bool, 'encontros' => ?int, 'faltas' => int, 'compensadas' => int, 'frequenciaMinima' => "75.00"].
 *
 * CONTA (D-318 a D-320, levantamento F.2)
 * - Tudo em INTEIROS: as notas em centésimos e os pesos em décimos de milésimo; cada valor intermediário é uma FRAÇÃO
 *   exata [numerador, denominador] até o arredondamento. Sem ponto flutuante (6,95 nunca vira 6,9499…).
 * - Atividade sem nota: no FINAL, `semNota` = zero (conta 0, o padrão) ou ignorar; na PARCIAL (oferta aberta), sempre
 *   ignorada — a parcial só informa.
 * - Agregação dentro do componente (`MODOS`); com uma atividade só, ela é a nota do componente.
 *   - descartar as menores: N (quantidade) ou X% → N = piso(n × X ÷ 100); manter as maiores: X% → teto(n × X ÷ 100).
 *     Fica SEMPRE pelo menos 1 atividade; quando N pediria mais, a memória registra o caso.
 * - Composição: Σ(nota do componente × peso) ÷ Σ pesos dos componentes considerados. Componente sem atividade (ou, na
 *   parcial, sem nota): `componenteVazio` = redistribuir (sai e o peso se divide entre os outros na proporção; o
 *   padrão) ou zero (no FINAL, conta 0 com o peso dele). Nenhum componente considerado = sem média.
 * - Arredondamento (`ARREDONDAMENTOS`) com 1 ou 2 casas, no `momento` so_final (só a média) ou em_cada_componente
 *   (cada componente e depois a média).
 * - Frequência = 1 − (faltas − compensadas) ÷ encontros, 2 casas, meio para cima (a de sempre; não é da regra).
 * - Resultado (só no FINAL, com média e frequência): Aprovado se média ≥ nota mínima E frequência suficiente
 *   (`frequenciaSuficiente`, a MESMA função do FS/FI). Comparações com os valores já arredondados.
 *
 * COMPONENTE OBRIGATÓRIO E LIMITE (E10, 1.15.0, D-420 a D-423) — `min` (0 = opcional; N ≥ 1 = obrigatório, com
 * pelo menos N atividades) e `max` (null = sem limite) em cada componente; sem as chaves = 0 e sem limite (a regra
 * de antes e os fechamentos antigos continuam iguais).
 * - Obrigatório com menos atividades que o mínimo: PENDÊNCIA. O peso NÃO é redistribuído; não há média nem
 *   resultado ("Pendente — falta <nome>"), na parcial e no final.
 * - Obrigatório com atividade, no FINAL, e o aluno sem NENHUMA nota nele: vale 0 com o peso dele (em "ignorar"
 *   também — senão o aluno passaria sem a avaliação). Com pelo menos uma nota, "ignorar" vale dentro do componente.
 * - Na PARCIAL, a nota em branco continua de fora (D-401); o obrigatório ainda sem nota fica de fora da parcial
 *   com um aviso de que no encerramento vale 0.
 * - A redistribuição do peso (componenteVazio) só vale para componentes OPCIONAIS.
 * - Acima do máximo: aviso (o cadastro de atividade e o encerramento é que recusam).
 *
 * PRIORIDADE DA PENDÊNCIA (1.15.2, D-431): o que bloqueia o encerramento — obrigatório abaixo do mínimo OU componente
 * acima do máximo — vira PENDÊNCIA e manda no resultado: nunca Aprovado/Reprovado, e sim "Pendente — <motivo>"
 * (`motivosPendencia`). Acima do máximo, a média é calculada e sai como MÉDIA PROVISÓRIA (`mediaProvisoria`); faltando
 * o obrigatório, continua sem média (D-422). A memória lista as pendências antes do resultado. A decisão é só daqui:
 * simulador, tela de notas e encerramento leem estas chaves.
 *
 * O ACRÉSCIMO (Onda 4, Etapa 5; docs/ESCOLA.md, D-50) — um ponto somado POR CIMA da média fechada, não uma nota que
 * entra na conta (fechou com 5,0, acréscimo 1,0 → 6,0). Só com `$regra['acrescimo']` (['permitir' => true, 'limite' =>
 * ?"1.00"]); sem a chave — o padrão, e todas as regras de antes —, o cálculo é exatamente o de sempre. A atividade de
 * acréscimo (`'acrescimo' => true` na atividade) fica FORA dos componentes e não tem peso: as notas de acréscimo do
 * aluno se somam, com o limite da regra (vazio = sem limite), e entram DEPOIS da composição dos componentes e ANTES do
 * arredondamento, com teto na nota máxima da escala (10,00). A memória mostra a linha ("Acréscimo: + 1,0 → 6,0").
 * =============================================================================
 */
final class MotorMedia
{
    public const APROVADO = 1;
    public const REPROVADO = 2;

    public const MODOS = [
        'aritmetica' => 'Média aritmética',
        'ponderada' => 'Média ponderada pelo peso da atividade',
        'soma' => 'Soma das notas (teto 10)',
        'maior' => 'Maior nota',
        'menor' => 'Menor nota',
        'descartar_menores' => 'Descartar as menores',
        'manter_maiores' => 'Manter as maiores',
    ];
    /** Modos que usam o peso da atividade (o cadastro da atividade só mostra o campo peso para estes). */
    public const MODOS_COM_PESO = ['ponderada'];
    /** Modos com o parâmetro N (quantidade ou percentual). */
    public const MODOS_COM_N = ['descartar_menores', 'manter_maiores'];
    public const ARREDONDAMENTOS = [
        'meio_para_cima' => 'Matemático (meio para cima)',
        'para_cima' => 'Sempre para cima',
        'para_baixo' => 'Sempre para baixo (truncar)',
        'meio_ponto' => 'Para o meio ponto mais próximo (0,5)',
    ];
    public const MOMENTOS = ['so_final' => 'Só na média final', 'em_cada_componente' => 'Em cada componente e na média'];
    public const SEM_NOTA = ['zero' => 'Conta zero', 'ignorar' => 'Fica de fora'];
    public const COMPONENTE_VAZIO = ['redistribuir' => 'Peso redistribuído entre os outros', 'zero' => 'Conta zero com o peso dele'];

    /** 10,00 em centésimos: o teto da escala (modo soma). */
    private const TETO = 1000;
    /** Pesos em décimos de milésimo (decimal(7,4)). */
    private const ESCALA_PESO = 10000;

    /** A regra PADRÃO de hoje (D-391, régua de ouro): um componente "Atividades", aritmética, peso 100, só no final. */
    public static function regraPadrao(int $casas, string $notaMinima): array
    {
        return [
            'componentes' => [['codigo' => 'atividades', 'nome' => 'Atividades', 'peso' => '100', 'modo' => 'aritmetica', 'n' => null, 'tipoN' => null, 'ordem' => 1]],
            'casas' => $casas, 'arredondamento' => 'meio_para_cima', 'momento' => 'so_final', 'semNota' => 'zero',
            'componenteVazio' => 'redistribuir', 'notaMinima' => $notaMinima,
        ];
    }

    /**
     * Valida uma regra (a tela e o servidor usam esta mesma função). Devolve a lista de erros; vazia = regra válida.
     * Pesos dos componentes devem somar EXATAMENTE 100 (D-319).
     */
    public static function validar(array $r): array
    {
        /* Onda 4, Etapa 5: o limite do acréscimo — vazio (sem limite) ou uma nota de 0 a 10 */
        if (!empty($r['acrescimo']['permitir'])) {
            $lim = (string) ($r['acrescimo']['limite'] ?? '');
            if ($lim !== '' && (!preg_match('/^\d{1,2}(\.\d{1,2})?$/', $lim) || (float) $lim > 10)) {
                return ['O limite do acréscimo: uma nota de 0 a 10 (ou vazio, sem limite).'];
            }
        }
        $e = [];
        $comps = $r['componentes'] ?? [];
        if (!$comps) {
            $e[] = 'A regra precisa de pelo menos um componente.';
        }
        if (count($comps) > 12) {
            $e[] = 'No máximo 12 componentes.';
        }
        $soma = 0;
        $codigos = [];
        foreach ($comps as $i => $c) {
            $rot = 'Componente ' . ($i + 1) . (trim((string) ($c['nome'] ?? '')) !== '' ? ' (' . trim((string) $c['nome']) . ')' : '');
            $nome = trim((string) ($c['nome'] ?? ''));
            if ($nome === '' || mb_strlen($nome) > 80) {
                $e[] = "{$rot}: nome de 1 a 80 caracteres.";
            }
            $cod = (string) ($c['codigo'] ?? '');
            if (!preg_match('/^[a-z0-9_]{1,40}$/', $cod)) {
                $e[] = "{$rot}: código inválido (letras minúsculas, números e _, até 40).";
            } elseif (isset($codigos[$cod])) {
                $e[] = "{$rot}: código repetido ({$cod}).";
            }
            $codigos[$cod] = true;
            $p = self::pesoInt((string) ($c['peso'] ?? ''));
            if ($p === null || $p <= 0) {
                $e[] = "{$rot}: peso deve ser um número maior que 0 (até 4 casas).";
            } else {
                $soma += $p;
            }
            $modo = (string) ($c['modo'] ?? '');
            if (!isset(self::MODOS[$modo])) {
                $e[] = "{$rot}: modo de agregação inválido.";
            } elseif (in_array($modo, self::MODOS_COM_N, true)) {
                $tipo = (string) ($c['tipoN'] ?? '');
                $n = (string) ($c['n'] ?? '');
                if (!in_array($tipo, ['quantidade', 'percentual'], true)) {
                    $e[] = "{$rot}: escolha se N é uma quantidade ou um percentual.";
                } elseif ($tipo === 'quantidade' && !preg_match('/^[1-9]\d{0,1}$/', $n)) {
                    $e[] = "{$rot}: N deve ser uma quantidade inteira de 1 a 99.";
                } elseif ($tipo === 'percentual' && (!preg_match('/^\d{1,3}$/', $n) || (int) $n < 1 || (int) $n > 99)) {
                    $e[] = "{$rot}: o percentual deve ser um inteiro de 1 a 99.";
                }
            }
            /* E10 (D-420): mínimo ≥ 0; máximo vazio ou ≥ 1; máximo ≥ mínimo */
            $min = trim((string) ($c['min'] ?? '0'));
            $max = trim((string) ($c['max'] ?? ''));
            if (!preg_match('/^\d{1,2}$/', $min)) {
                $e[] = "{$rot}: o mínimo de atividades deve ser um inteiro de 0 a 99.";
            } elseif ($max !== '' && (!preg_match('/^\d{1,2}$/', $max) || (int) $max < 1)) {
                $e[] = "{$rot}: o máximo de atividades deve ficar vazio (sem limite) ou ser um inteiro de 1 a 99.";
            } elseif ($max !== '' && (int) $max < (int) $min) {
                $e[] = "{$rot}: o máximo de atividades ({$max}) não pode ser menor que o mínimo ({$min}).";
            }
        }
        if ($comps && $soma !== 100 * self::ESCALA_PESO) {
            $e[] = 'Os pesos dos componentes devem somar 100 (soma atual: ' . self::numeroPeso($soma) . ').';
        }
        if (!in_array((int) ($r['casas'] ?? 0), [1, 2], true)) {
            $e[] = 'Casas decimais da média: 1 ou 2.';
        }
        foreach ([['arredondamento', self::ARREDONDAMENTOS, 'Arredondamento'], ['momento', self::MOMENTOS, 'Momento do arredondamento'],
                  ['semNota', self::SEM_NOTA, 'Atividade sem nota'], ['componenteVazio', self::COMPONENTE_VAZIO, 'Componente sem atividade']] as [$k, $lista, $rot]) {
            if (!isset($lista[(string) ($r[$k] ?? '')])) {
                $e[] = "{$rot}: opção inválida.";
            }
        }
        $nm = str_replace(',', '.', trim((string) ($r['notaMinima'] ?? '')));
        if (!preg_match('/^(10(\.0{1,2})?|\d(\.\d{1,2})?)$/', $nm)) {
            $e[] = 'Nota mínima: um número de 0 a 10, com até 2 casas.';
        }
        return $e;
    }

    /**
     * E10 (D-421): avisos de uma regra válida, que NÃO impedem salvar. Hoje: nenhum componente obrigatório
     * (recomendado, não exigido).
     */
    public static function avisosRegra(array $r): array
    {
        foreach ($r['componentes'] ?? [] as $c) {
            if (self::minimo($c) > 0) {
                return [];
            }
        }
        return ['Nenhum componente obrigatório: uma disciplina pode ser encerrada sem nenhuma avaliação deste tipo.'];
    }

    /** E10: o mínimo de atividades do componente (0 = opcional). */
    public static function minimo(array $c): int
    {
        return max(0, (int) ($c['min'] ?? 0));
    }

    /** E10: o máximo de atividades do componente (null = sem limite). */
    public static function maximo(array $c): ?int
    {
        $m = $c['max'] ?? null;
        return ($m === null || $m === '') ? null : (int) $m;
    }

    /**
     * O CÁLCULO. Devolve: media (?string, com ponto e as casas), lancadas, atividades, soma (centésimos lançados),
     * parcial, frequencia (?string), resultado (?int), motivo (?string), conta (resumo de uma linha) e memoria
     * (estruturada + `linhas` em texto).
     */
    public static function calcular(array $atividades, array $regra, array $ctx): array
    {
        $final = !empty($ctx['final']);
        $casas = (int) $regra['casas'];
        $comps = $regra['componentes'];
        usort($comps, fn($a, $b) => [(int) ($a['ordem'] ?? 0), (string) $a['codigo']] <=> [(int) ($b['ordem'] ?? 0), (string) $b['codigo']]);
        $porCodigo = [];
        foreach ($comps as $c) {
            $porCodigo[(string) $c['codigo']] = $c + ['atividades' => []];
        }
        $padrao = (string) $comps[0]['codigo'];
        $avisos = [];
        $lancadas = 0;
        $soma = 0;
        $acrescimos = []; // Onda 4, Etapa 5: fora dos componentes, sem peso
        foreach ($atividades as $a) {
            if (!empty($a['acrescimo'])) {
                if ($a['nota'] !== null) {
                    $acrescimos[] = (int) $a['nota'];
                }
                continue;
            }
            $cod = (string) ($a['componente'] ?? '');
            if ($cod === '' || !isset($porCodigo[$cod])) {
                if ($cod !== '') {
                    $avisos[] = 'Atividade "' . ($a['nome'] ?? $a['id']) . "\" aponta para o componente \"{$cod}\", que esta regra não tem: entra em " . $porCodigo[$padrao]['nome'] . '.';
                }
                $cod = $padrao;
            }
            $porCodigo[$cod]['atividades'][] = $a;
            if ($a['nota'] !== null) {
                $lancadas++;
                $soma += (int) $a['nota'];
            }
        }

        $memComps = [];
        $considerados = [];
        $pendencias = [];
        $faltam = [];
        $acima = []; /* 1.15.2 (D-431): componentes acima do máximo — também pendência */
        $redistNota = false; /* 1.15.1: o que foi redistribuído — componente sem atividade e/ou sem nota do aluno */
        $redistVazio = false;
        foreach ($porCodigo as $cod => $c) {
            /* E10 (D-422): obrigatório abaixo do mínimo = pendência; o peso dele NÃO vai para os outros */
            $min = self::minimo($c);
            $max = self::maximo($c);
            $qtd = count($c['atividades']);
            $rotulo = $c['nome'] . ' (peso ' . self::numeroPeso(self::pesoInt((string) $c['peso'])) . ')';
            if ($min > 0 && $qtd < $min) {
                $pendencias[] = $qtd === 0 ? 'componente obrigatório sem atividade: ' . $c['nome'] : 'componente obrigatório com menos atividades que o mínimo: ' . $c['nome'] . " ({$qtd} de {$min})";
                $faltam[] = $c['nome'];
                $memComps[] = ['codigo' => $cod, 'nome' => $c['nome'], 'peso' => (string) $c['peso'], 'modo' => $c['modo'], 'vazio' => true, 'obrigatorio' => true, 'pendente' => true, 'notas' => [],
                    'texto' => $rotulo . ': obrigatório, ' . ($qtd === 0 ? 'sem atividade' : "{$qtd} de no mínimo {$min} atividade(s)") . ' → PENDENTE (o peso não é redistribuído)'];
                continue;
            }
            if ($max !== null && $qtd > $max) {
                $pendencias[] = 'componente acima do máximo: ' . $c['nome'] . " ({$qtd} atividades, máximo {$max})";
                $acima[] = $c['nome'] . ' acima do máximo de ' . $max;
            }
            $agg = self::agregar($c, $final, (string) $regra['semNota']);
            $m = ['codigo' => $cod, 'nome' => $c['nome'], 'peso' => (string) $c['peso'], 'modo' => $c['modo'], 'vazio' => $agg === null] + ($min > 0 ? ['obrigatorio' => true] : []) + ($agg['memoria'] ?? ['notas' => []]);
            if ($agg === null && $min > 0) {
                /* E10 (D-422): obrigatório com atividade e o aluno sem NENHUMA nota nele */
                if ($final) {
                    $m['valor'] = [0, 1];
                    $m['texto'] = $rotulo . ': obrigatório, o aluno não tem nenhuma nota nele → conta 0 com o peso dele (não é redistribuído)';
                    $considerados[] = ['valor' => [0, 1], 'peso' => self::pesoInt((string) $c['peso']), 'nome' => $c['nome']];
                } else {
                    $m['texto'] = $rotulo . ': obrigatório, ainda sem nota → fica de fora da média parcial; no encerramento, sem nenhuma nota, conta 0';
                    $avisos[] = $c['nome'] . ' é obrigatório e o aluno ainda não tem nota nele: a parcial não o considera; no encerramento, sem nota, ele conta 0.';
                }
                $memComps[] = $m;
                continue;
            }
            if ($agg === null) {
                /* 1.15.1 (D-428): com atividade e o aluno sem nota, a memória diz isso (antes dizia "sem atividade").
                   Só o texto muda; o caso "sem atividade" de verdade fica com o texto de sempre. */
                $semNotaDoAluno = $qtd > 0;
                if ($final && $regra['componenteVazio'] === 'zero') {
                    $m['valor'] = [0, 1];
                    $m['texto'] = $rotulo . ($semNotaDoAluno ? ': sem nota do aluno: conta zero' : ': sem atividade com nota → conta 0');
                    $considerados[] = ['valor' => [0, 1], 'peso' => self::pesoInt((string) $c['peso']), 'nome' => $c['nome']];
                } else {
                    $m['texto'] = $rotulo . ($semNotaDoAluno ? ': sem nota do aluno: peso redistribuído entre os outros' : ': sem atividade' . ($final ? '' : ' com nota') . ' → peso redistribuído entre os outros');
                    if ($semNotaDoAluno) {
                        $redistNota = true;
                    } else {
                        $redistVazio = true;
                    }
                }
                $memComps[] = $m;
                continue;
            }
            $valor = $agg['valor'];
            if ($regra['momento'] === 'em_cada_componente') {
                $arr = self::arredondarFracao($valor[0], $valor[1] * 100, $casas, (string) $regra['arredondamento']);
                $m['arredondado'] = $arr;
                $valor = self::fracaoDe($arr);
            }
            $m['valor'] = $valor;
            $m['texto'] = $c['nome'] . ' (peso ' . self::numeroPeso(self::pesoInt((string) $c['peso'])) . ', ' . mb_strtolower(self::MODOS[$c['modo']]) . '): ' . $agg['texto']
                . (isset($m['arredondado']) ? ' → ' . self::virgula($m['arredondado']) . ' (arredondado no componente)' : '');
            $memComps[] = $m;
            $considerados[] = ['valor' => $valor, 'peso' => self::pesoInt((string) $c['peso']), 'nome' => $c['nome']];
        }

        $media = null;
        $composicao = null;
        if ($considerados && !$faltam) { /* 1.15.2: acima do máximo ainda calcula (média provisória); faltando o obrigatório, não (D-422) */
            [$num, $den] = [0, 1];
            $somaPesos = 0;
            foreach ($considerados as $k) {
                [$num, $den] = self::somarFracoes([$num, $den], [$k['valor'][0] * $k['peso'], $k['valor'][1]]);
                $somaPesos += $k['peso'];
            }
            [$num, $den] = self::reduzir($num, $den * $somaPesos);
            /* Onda 4, Etapa 5: o acréscimo, por cima da média dos componentes, antes do arredondamento; teto 10,00 */
            $memAcrescimo = null;
            if (!empty($regra['acrescimo']['permitir']) && $acrescimos) {
                $somaAcr = array_sum($acrescimos);
                $limite = ($regra['acrescimo']['limite'] ?? '') === '' || $regra['acrescimo']['limite'] === null ? null : self::centesimosDe((string) $regra['acrescimo']['limite']);
                $vale = $limite === null ? $somaAcr : min($somaAcr, $limite);
                $antes = [$num, $den];
                [$num, $den] = self::somarFracoes([$num, $den], [$vale, 1]);
                if ($num > self::TETO * $den) {
                    [$num, $den] = [self::TETO, 1];
                }
                $depois = self::arredondarFracao($num, $den * 100, $casas, (string) $regra['arredondamento']);
                $memAcrescimo = 'Acréscimo: + ' . self::virgulaCent($vale) . ($limite !== null && $somaAcr > $limite ? ' (o limite da regra; lançado ' . self::virgulaCent($somaAcr) . ')' : '')
                    . ' sobre ' . self::virgulaFracao($antes) . ' → ' . self::virgula($depois) . ($num === self::TETO && $den === 1 ? ' (o teto da nota)' : '');
            }
            $media = self::arredondarFracao($num, $den * 100, $casas, (string) $regra['arredondamento']);
            $redistribuido = $somaPesos !== 100 * self::ESCALA_PESO;
            $partes = array_map(fn($k) => self::virgulaFracao($k['valor']) . ' × ' . self::numeroPeso($k['peso']), $considerados);
            $composicao = [
                'somaPesos' => self::numeroPeso($somaPesos), 'redistribuido' => $redistribuido, 'valor' => [$num, $den],
                'texto' => 'Média = (' . implode(' + ', $partes) . ') ÷ ' . self::numeroPeso($somaPesos) . ' = ' . self::virgulaFracao([$num, $den])
                    . ' → ' . self::virgula($media) . ' (' . $casas . ' casa' . ($casas > 1 ? 's' : '') . ', ' . mb_strtolower(self::ARREDONDAMENTOS[$regra['arredondamento']]) . ', '
                    . ($regra['momento'] === 'so_final' ? 'só no final' : 'em cada componente e no final') . ')' . ($pendencias ? ' — média provisória' : ''),
            ];
            if ($redistribuido) {
                $quem = $redistNota ? ($redistVazio ? 'Componente sem atividade ou sem nota do aluno' : 'Componente sem nota do aluno') : 'Componente sem atividade'; // 1.15.1 (D-428)
                $avisos[] = $quem . ': o peso dele foi redistribuído entre os outros, na proporção (soma dos pesos considerados: ' . self::numeroPeso($somaPesos) . ').';
            }
        }

        $frequencia = null;
        $encontros = $ctx['encontros'] ?? null;
        $faltas = (int) ($ctx['faltas'] ?? 0);
        $compensadas = (int) ($ctx['compensadas'] ?? 0);
        $memFreq = null;
        if ($encontros !== null && $encontros > 0) {
            $efetivas = max(0, min($encontros, $faltas - $compensadas));
            $frequencia = self::arredondarFracao(($encontros - $efetivas) * 100, $encontros, 2, 'meio_para_cima');
            $memFreq = 'Frequência = 1 − (' . $faltas . ' − ' . $compensadas . ') ÷ ' . $encontros . ' = ' . self::virgula($frequencia) . '%';
        }
        $resultado = null;
        $motivo = null;
        $memRes = null;
        /* 1.15.2 (D-431): a pendência tem prioridade — sem Aprovado/Reprovado enquanto houver */
        $motivosPendencia = array_merge($faltam ? ['falta ' . implode(', ', $faltam)] : [], $acima);
        if ($motivosPendencia) {
            $memRes = 'Resultado: Pendente — ' . implode('; ', $motivosPendencia) . ($media !== null ? ' (média provisória ' . self::virgula($media) . ')' : '') . ' — a disciplina não encerra até ajustar';
        } elseif ($final && $media !== null && $frequencia !== null) {
            $notaOk = self::centesimosDe($media) >= self::centesimosDe((string) $regra['notaMinima']);
            $freqOk = self::frequenciaSuficiente($frequencia, (string) ($ctx['frequenciaMinima'] ?? '0'));
            $resultado = ($notaOk && $freqOk) ? self::APROVADO : self::REPROVADO;
            $motivo = $notaOk ? ($freqOk ? null : 'frequencia') : ($freqOk ? 'nota' : 'nota_frequencia');
            $memRes = 'Resultado: média ' . self::virgula($media) . ($notaOk ? ' ≥ ' : ' < ') . self::virgula(number_format((float) $regra['notaMinima'], 2, '.', ''))
                . ' (nota mínima) e frequência ' . self::virgula($frequencia) . '%' . ($freqOk ? ' ≥ ' : ' < ') . self::virgula((string) $ctx['frequenciaMinima']) . '% → '
                . ($resultado === self::APROVADO ? 'Aprovado' : 'Reprovado');
        }

        $semMedia = $faltam ? 'Sem média: componente obrigatório sem atividade suficiente (o peso dele não é redistribuído).' : 'Sem média: nenhum componente com nota.';
        $linhas = array_merge(array_column($memComps, 'texto'), $composicao ? [$composicao['texto']] : [$semMedia], !empty($memAcrescimo) ? [$memAcrescimo] : [],
            $memFreq ? [$memFreq] : [], $pendencias ? ['Pendências: ' . implode('; ', $pendencias) . '.'] : [], $memRes ? [$memRes] : [], array_map(fn($a) => 'Aviso: ' . $a, $avisos));
        return [
            'lancadas' => $lancadas,
            'atividades' => count($atividades),
            'soma' => $soma,
            'media' => $media,
            'parcial' => !$final,
            'conta' => self::conta($regra, $memComps, $composicao, $final, $lancadas, count($atividades), $soma),
            'faltas' => $faltas,
            'compensadas' => $compensadas,
            'encontros' => $encontros,
            'frequencia' => $frequencia,
            'resultado' => $resultado,
            'motivo' => $motivo,
            /* E10 (D-422): o que deixa o aluno "Pendente" (vazio = nada) e os componentes que faltam */
            'pendencias' => $pendencias,
            'faltam' => $faltam,
            /* 1.15.2 (D-431): os motivos curtos ("falta Prova", "Atividades acima do máximo de 1") e se a média é provisória */
            'motivosPendencia' => $motivosPendencia,
            'mediaProvisoria' => $media !== null && (bool) $motivosPendencia,
            'memoria' => [
                'final' => $final,
                'regra' => ['casas' => $casas, 'arredondamento' => $regra['arredondamento'], 'momento' => $regra['momento'], 'semNota' => $regra['semNota'],
                    'componenteVazio' => $regra['componenteVazio'], 'notaMinima' => (string) $regra['notaMinima']],
                'componentes' => array_map(fn($m) => array_diff_key($m, ['valor' => 1]) + ['valor' => isset($m['valor']) ? self::virgulaFracao($m['valor']) : null], $memComps),
                'composicao' => $composicao ? array_diff_key($composicao, ['valor' => 1]) : null,
                'media' => $media,
                'frequencia' => $memFreq,
                'resultado' => $memRes,
                'pendencias' => $pendencias,
                'avisos' => $avisos,
                'linhas' => $linhas,
            ],
        ];
    }

    /**
     * Agrega as notas de um componente. Devolve null (componente vazio) ou ['valor' => [num, den] em centésimos,
     * 'texto' => a conta, 'memoria' => [...]].
     */
    private static function agregar(array $c, bool $final, string $semNota): ?array
    {
        $itens = [];
        foreach ($c['atividades'] as $a) {
            if ($a['nota'] === null) {
                if (!$final || $semNota === 'ignorar') {
                    continue;
                }
                $itens[] = ['nome' => (string) ($a['nome'] ?? ''), 'nota' => 0, 'peso' => self::pesoInt((string) ($a['peso'] ?? '10')) ?? 0, 'semNota' => true];
            } else {
                $itens[] = ['nome' => (string) ($a['nome'] ?? ''), 'nota' => (int) $a['nota'], 'peso' => self::pesoInt((string) ($a['peso'] ?? '10')) ?? 0, 'semNota' => false];
            }
        }
        $total = count($c['atividades']);
        if (!$itens) {
            return null;
        }
        $n = count($itens);
        $notas = array_column($itens, 'nota');
        $fmt = fn(array $l) => (count($l) > 1 ? '(' : '') . implode(' + ', array_map(fn($x) => self::virgulaCent($x), $l)) . (count($l) > 1 ? ')' : '');
        $mem = ['notas' => array_map(fn($i) => ['atividade' => $i['nome'], 'nota' => self::virgulaCent($i['nota']), 'peso' => self::numeroPeso($i['peso']), 'semNota' => $i['semNota']], $itens)];
        $sufixoSemNota = count(array_filter($itens, fn($i) => $i['semNota'])) ? ' (atividade sem nota conta 0)' : '';
        if ($n === 1) {
            return ['valor' => [$notas[0], 1], 'texto' => 'única atividade: ' . self::virgulaCent($notas[0]) . $sufixoSemNota, 'memoria' => $mem];
        }
        switch ($c['modo']) {
            case 'ponderada':
                $sp = array_sum(array_column($itens, 'peso'));
                if ($sp <= 0) {
                    return ['valor' => self::reduzir(array_sum($notas), $n), 'texto' => '(pesos zerados: média aritmética) ' . $fmt($notas) . ' ÷ ' . $n, 'memoria' => $mem];
                }
                $num = array_sum(array_map(fn($i) => $i['nota'] * $i['peso'], $itens));
                $v = self::reduzir($num, $sp);
                return ['valor' => $v, 'texto' => '(' . implode(' + ', array_map(fn($i) => self::virgulaCent($i['nota']) . ' × ' . self::numeroPeso($i['peso']), $itens)) . ') ÷ ' . self::numeroPeso($sp) . ' = ' . self::virgulaFracao($v) . $sufixoSemNota, 'memoria' => $mem];
            case 'soma':
                $s = array_sum($notas);
                $v = min($s, self::TETO);
                return ['valor' => [$v, 1], 'texto' => $fmt($notas) . ' = ' . self::virgulaCent($s) . ($s > self::TETO ? ' → teto 10,00' : '') . $sufixoSemNota, 'memoria' => $mem];
            case 'maior':
                return ['valor' => [max($notas), 1], 'texto' => 'a maior de ' . implode('; ', array_map(fn($x) => self::virgulaCent($x), $notas)) . ' = ' . self::virgulaCent(max($notas)) . $sufixoSemNota, 'memoria' => $mem];
            case 'menor':
                return ['valor' => [min($notas), 1], 'texto' => 'a menor de ' . implode('; ', array_map(fn($x) => self::virgulaCent($x), $notas)) . ' = ' . self::virgulaCent(min($notas)) . $sufixoSemNota, 'memoria' => $mem];
            case 'descartar_menores':
            case 'manter_maiores':
                $tipo = (string) ($c['tipoN'] ?? 'quantidade');
                $par = (int) ($c['n'] ?? 1);
                sort($notas);
                if ($c['modo'] === 'descartar_menores') {
                    $pedido = $tipo === 'percentual' ? intdiv($n * $par, 100) : $par;
                    $tirar = min($pedido, $n - 1);
                    $fica = array_slice($notas, $tirar);
                    $regraTxt = $tipo === 'percentual' ? "{$par}% de {$n} = " . self::virgulaFracao(self::reduzir($n * $par * 100, 100)) . ' → piso ' . $pedido : "N = {$pedido}";
                    $caso = $pedido > $tirar ? " (pedia {$pedido}; fica pelo menos 1 atividade)" : '';
                    $txt = 'descarta ' . $tirar . ' menor(es) (' . $regraTxt . $caso . '): ' . $fmt($fica) . ' ÷ ' . count($fica);
                    $mem['descartadas'] = array_map(fn($x) => self::virgulaCent($x), array_slice($notas, 0, $tirar));
                } else {
                    $pedido = $tipo === 'percentual' ? intdiv($n * $par + 99, 100) : $par;
                    $manter = max(1, min($pedido, $n));
                    $fica = array_slice($notas, $n - $manter);
                    $regraTxt = $tipo === 'percentual' ? "{$par}% de {$n} → teto " . $pedido : "N = {$pedido}";
                    $caso = $pedido > $n ? " (pedia {$pedido}; há só {$n})" : '';
                    $txt = 'mantém as ' . $manter . ' maior(es) (' . $regraTxt . $caso . '): ' . $fmt($fica) . ' ÷ ' . count($fica);
                    $mem['mantidas'] = array_map(fn($x) => self::virgulaCent($x), $fica);
                }
                $v = self::reduzir(array_sum($fica), count($fica));
                return ['valor' => $v, 'texto' => $txt . ' = ' . self::virgulaFracao($v) . $sufixoSemNota, 'memoria' => $mem];
            case 'aritmetica':
            default:
                $v = self::reduzir(array_sum($notas), $n);
                return ['valor' => $v, 'texto' => $fmt($notas) . ' ÷ ' . $n . ' = ' . self::virgulaFracao($v) . $sufixoSemNota, 'memoria' => $mem];
        }
    }

    /**
     * O resumo de uma linha (a coluna "conta" das telas). Regra de UM componente aritmético: o texto de sempre
     * ("S ÷ N atividade(s)" / "S ÷ L nota(s) lançada(s) de N"), para que as telas não mudem (régua de ouro).
     */
    private static function conta(array $regra, array $memComps, ?array $composicao, bool $final, int $lancadas, int $total, int $soma): string
    {
        if (!$composicao) {
            return '';
        }
        $c = $regra['componentes'];
        if (count($c) === 1 && $c[0]['modo'] === 'aritmetica') {
            $s = number_format($soma / 100, 2, ',', '.');
            return ($final && $regra['semNota'] === 'zero') ? $s . ' ÷ ' . $total . ' atividade(s)' : $s . ' ÷ ' . $lancadas . ' nota(s) lançada(s) de ' . $total;
        }
        return mb_substr($composicao['texto'], 0, 400);
    }

    /** FS/FI: frequência ≥ mínima, com os valores arredondados, em centésimos. A ÚNICA comparação de frequência. */
    public static function frequenciaSuficiente(string $frequencia, string $minima): bool
    {
        return self::centesimosDe($frequencia) >= self::centesimosDe($minima);
    }

    /**
     * num/den (≥ 0, em NOTA — não em centésimos) arredondado com `$casas` casas no modo dado; texto com ponto.
     * meio_ponto: o múltiplo de 0,5 mais próximo (meio para cima), escrito com as casas pedidas ("7.0", "7.50").
     */
    public static function arredondarFracao(int $num, int $den, int $casas, string $modo): string
    {
        $esc = 10 ** $casas;
        switch ($modo) {
            case 'para_baixo':
                $q = intdiv($num * $esc, $den);
                break;
            case 'para_cima':
                $q = intdiv($num * $esc + $den - 1, $den);
                break;
            case 'meio_ponto':
                $meios = intdiv(4 * $num + $den, 2 * $den); /* round(valor × 2), meio para cima */
                $q = intdiv($meios * $esc, 2);
                break;
            case 'meio_para_cima':
            default:
                $q = intdiv(2 * $num * $esc + $den, 2 * $den);
        }
        return $casas > 0 ? intdiv($q, $esc) . '.' . str_pad((string) ($q % $esc), $casas, '0', STR_PAD_LEFT) : (string) $q;
    }

    /** "7.00" / "75.5" → centésimos. */
    public static function centesimosDe(string $v): int
    {
        [$i, $d] = array_pad(explode('.', str_replace(',', '.', trim($v)), 2), 2, '0');
        return (int) $i * 100 + (int) str_pad(substr($d, 0, 2), 2, '0');
    }

    /** Peso decimal ("10", "2.5", "33,3333") → inteiro em décimos de milésimo; inválido → null. */
    public static function pesoInt(string $p): ?int
    {
        $v = str_replace(',', '.', trim($p));
        if (!preg_match('/^(\d{1,3})(?:\.(\d{1,4}))?$/', $v, $m)) {
            return null;
        }
        return (int) $m[1] * self::ESCALA_PESO + (int) str_pad($m[2] ?? '0', 4, '0');
    }

    /** Peso inteiro → "40", "33,3333", "2,5". */
    public static function numeroPeso(?int $p): string
    {
        if ($p === null) {
            return '—';
        }
        $i = intdiv($p, self::ESCALA_PESO);
        $d = rtrim(str_pad((string) ($p % self::ESCALA_PESO), 4, '0', STR_PAD_LEFT), '0');
        return $d === '' ? (string) $i : $i . ',' . $d;
    }

    /* ------------------------------------------------------------------ frações exatas */
    private static function mdc(int $a, int $b): int
    {
        $a = abs($a);
        $b = abs($b);
        while ($b) {
            [$a, $b] = [$b, $a % $b];
        }
        return $a ?: 1;
    }

    private static function reduzir(int $num, int $den): array
    {
        $g = self::mdc($num, $den);
        return [intdiv($num, $g), intdiv($den, $g)];
    }

    private static function somarFracoes(array $a, array $b): array
    {
        $g = self::mdc($a[1], $b[1]);
        $den = intdiv($a[1], $g) * $b[1];
        return self::reduzir($a[0] * intdiv($den, $a[1]) + $b[0] * intdiv($den, $b[1]), $den);
    }

    /** "7.5" (nota) → fração em centésimos. */
    private static function fracaoDe(string $nota): array
    {
        return [self::centesimosDe($nota), 1];
    }

    /* ------------------------------------------------------------------ escrita */
    private static function virgula(string $v): string
    {
        return str_replace('.', ',', $v);
    }

    private static function virgulaCent(int $c): string
    {
        return intdiv($c, 100) . ',' . str_pad((string) ($c % 100), 2, '0', STR_PAD_LEFT);
    }

    /** Fração em centésimos → "8,1666…" (4 casas e reticências se não for exata) ou "8,50". */
    private static function virgulaFracao(array $f): string
    {
        [$num, $den] = $f;
        if ($num % $den === 0) {
            return self::virgulaCent(intdiv($num, $den));
        }
        $q = intdiv($num * 100, $den); /* em 1/10000 da nota */
        $exato = ($num * 100) % $den === 0;
        $dec = str_pad((string) ($q % 10000), 4, '0', STR_PAD_LEFT);
        return intdiv($q, 10000) . ',' . ($exato ? str_pad(rtrim($dec, '0'), 2, '0') : $dec . '…');
    }
}
