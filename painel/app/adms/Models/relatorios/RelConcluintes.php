<?php

namespace App\adms\Models\relatorios;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\relatorios\AdmsRelatorioFonte as F;

/** R-EVA-04 Concluintes e tempo até a conclusão (docs/RELATORIOS.md, E; onda 2). */
class RelConcluintes extends AdmsRelatorioBase
{
    public const CODIGO = 'R-EVA-04';
    public const NOME = 'Concluintes e tempo até a conclusão';
    public const CATEGORIA = 'E';
    public const PERGUNTA = 'Quantos concluíram em cada período, e em quanto tempo?';
    public const SLUG = 'concluintes';
    public const QUEM = 'administrador, diretoria';

    public function filtros(): array
    {
        return ['curso', 'polo', 'de' => ['rotulo' => 'Conclusão de', 'tipo' => 'data'], 'ate' => ['rotulo' => 'até', 'tipo' => 'data']];
    }

    public function ficha(): array
    {
        return [
            'Matrículas em Concluído, agrupadas pelo SEMESTRE da data de conclusão; o período filtra a data de conclusão.',
            'Tempo até a conclusão = meses completos entre a data de ingresso e a data de conclusão (anos × 12 + meses; os dias que sobram não contam).',
            'Tempo médio (1 casa), mediano (o do meio; com quantidade par, a média dos dois do meio) e máximo, em meses.',
            'Concluído sem data de conclusão ou sem data de ingresso: fica de fora do tempo e entra no aviso.',
        ];
    }

    public static function meses(string $de, string $ate): int
    {
        $d = (new \DateTime($de))->diff(new \DateTime($ate));
        return $d->invert ? 0 : $d->y * 12 + $d->m;
    }

    public function dados(array $f): array
    {
        $por = [];
        $semData = 0;
        foreach (F::matriculas(['situacao' => 2] + array_intersect_key($f, ['curso' => 1, 'polo' => 1])) as $mc) {
            $c = (string) $mc['data_conclusao'];
            if ($c === '' || empty($mc['data_ingresso'])) {
                $semData++;
                continue;
            }
            if ((!empty($f['de']) && $c < $f['de']) || (!empty($f['ate']) && $c > $f['ate'])) {
                continue;
            }
            $mc['meses'] = self::meses((string) $mc['data_ingresso'], $c);
            $por[substr($c, 0, 4) . '/' . ((int) substr($c, 5, 2) <= 6 ? 1 : 2)][] = $mc;
        }
        krsort($por);
        $colDet = self::colunasAluno([self::col('ingresso', 'Ingresso'), self::col('conclusao', 'Conclusão'), self::col('meses', 'Meses', ['num' => true])]);
        $det = [];
        $linhas = [];
        $todos = [];
        foreach ($por as $p => $lista) {
            $k = 'p' . preg_replace('/\D/', '', $p);
            $det[$k] = ['titulo' => "Concluintes {$p}", 'colunas' => $colDet, 'linhas' => array_map(fn($mc) => self::linhaAluno($mc, ['ingresso' => F::data($mc['data_ingresso']),
                'conclusao' => F::data($mc['data_conclusao']), 'meses' => (string) $mc['meses']]), $lista)];
            $m = array_column($lista, 'meses');
            $todos = array_merge($todos, $m);
            $linhas[] = ['periodo' => self::cel($p, ['o' => $p]), 'n' => self::numero($det, $k)] + self::estat($m);
        }
        return [
            'avisos' => $semData ? ["{$semData} matrícula(s) em Concluído sem data de conclusão ou de ingresso ficaram de fora."] : [],
            'detalhes' => $det,
            'tabela' => ['colunas' => [self::col('periodo', 'Período (conclusão)'), self::col('n', 'Concluintes', ['num' => true]), self::col('media', 'Tempo médio (meses)', ['num' => true]),
                self::col('mediana', 'Mediano', ['num' => true]), self::col('maximo', 'Máximo', ['num' => true])], 'linhas' => $linhas, 'ordem' => [[0, 'desc']],
                'rodape' => ['periodo' => 'Total', 'n' => (string) count($todos)] + self::estat($todos)],
        ];
    }

    /** média (1 casa), mediana e máximo de uma lista de meses. */
    public static function estat(array $m): array
    {
        if (!$m) {
            return ['media' => '—', 'mediana' => '—', 'maximo' => '—'];
        }
        sort($m);
        $n = count($m);
        $med = $n % 2 ? $m[intdiv($n, 2)] : ($m[$n / 2 - 1] + $m[$n / 2]) / 2;
        return ['media' => number_format(array_sum($m) / $n, 1, ',', '.'), 'mediana' => number_format($med, $med == (int) $med ? 0 : 1, ',', '.'), 'maximo' => (string) max($m)];
    }
}
