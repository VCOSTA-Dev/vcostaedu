<?php

namespace App\adms\Models\relatorios;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\relatorios\AdmsRelatorioFonte as F;

/** R-SEC-03 Previsão de conclusão (docs/RELATORIOS.md, B). */
class RelPrevisaoConclusao extends AdmsRelatorioBase
{
    public const CODIGO = 'R-SEC-03';
    public const NOME = 'Previsão de conclusão';
    public const CATEGORIA = 'B';
    public const PERGUNTA = 'Quantos devem concluir em cada mês?';
    public const SLUG = 'previsao-conclusao';
    public const QUEM = 'administrador, diretoria, secretaria, coordenação';

    private const MESES = ['', 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];

    public function filtros(): array
    {
        return ['curso', 'polo', 'de' => ['rotulo' => 'Previsão de', 'tipo' => 'data'], 'ate' => ['rotulo' => 'até', 'tipo' => 'data']];
    }

    public function ficha(): array
    {
        return [
            'Matrículas em Cursando agrupadas pelo mês da previsão de conclusão (rm_matriculas_curso.previsao_conclusao).',
            'Sem previsão: a linha "sem previsão" (a mesma falta aparece no relatório Qualidade do cadastro).',
            'O período filtra a PREVISÃO (a linha "sem previsão" só aparece sem período). Cada quantidade abre a lista; a soma das linhas é o total.',
        ];
    }

    public function dados(array $f): array
    {
        $mats = F::matriculas(['situacao' => 1] + array_intersect_key($f, ['curso' => 1, 'polo' => 1]));
        $comPeriodo = !empty($f['de']) || !empty($f['ate']);
        $grupos = [];
        foreach ($mats as $mc) {
            $p = (string) $mc['previsao_conclusao'];
            if ($p === '') {
                if ($comPeriodo) {
                    continue;
                }
                $k = 'sem';
            } else {
                if ((!empty($f['de']) && $p < $f['de']) || (!empty($f['ate']) && $p > $f['ate'])) {
                    continue;
                }
                $k = substr($p, 0, 7);
            }
            $grupos[$k][] = $mc;
        }
        uksort($grupos, fn($a, $b) => $a === 'sem' ? 1 : ($b === 'sem' ? -1 : strcmp($a, $b)));
        $colDet = self::colunasAluno([self::col('previsao', 'Previsão'), self::col('situacao', 'Situação')]);
        $det = [];
        $linhas = [];
        $graf = ['titulo' => 'Matrículas com previsão de conclusão por mês', 'tipo' => 'bar', 'rotulos' => [], 'series' => [['rotulo' => 'Matrículas', 'dados' => [], 'cor' => '#17a2b8']]];
        $total = 0;
        foreach ($grupos as $k => $lista) {
            $chave = 'mes_' . str_replace('-', '_', $k);
            $rot = $k === 'sem' ? 'sem previsão' : self::MESES[(int) substr($k, 5, 2)] . '/' . substr($k, 0, 4);
            $det[$chave] = ['titulo' => 'Previsão: ' . $rot, 'colunas' => $colDet, 'linhas' => array_map(fn($mc) => self::linhaAluno($mc, ['previsao' => F::data($mc['previsao_conclusao']), 'situacao' => $mc['situacao']]), $lista)];
            $linhas[] = ['mes' => self::cel($rot, ['o' => $k === 'sem' ? '9999' : $k]), 'quantidade' => self::numero($det, $chave)];
            $total += self::contar($det, $chave);
            if ($k !== 'sem') {
                $graf['rotulos'][] = $rot;
                $graf['series'][0]['dados'][] = self::contar($det, $chave);
            }
        }
        return [
            'grafico' => $graf, 'detalhes' => $det,
            'tabela' => ['colunas' => [self::col('mes', 'Mês'), self::col('quantidade', 'Matrículas', ['num' => true])], 'linhas' => $linhas,
                'rodape' => ['mes' => 'Total', 'quantidade' => (string) $total]],
        ];
    }
}
