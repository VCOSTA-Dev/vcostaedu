<?php

namespace App\adms\Models\relatorios;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\notas\AdmsCalculoNotas as Calc;
use App\adms\Models\relatorios\AdmsRelatorioFonte as F;

/** R-TCC-01 Situação do TCC (docs/RELATORIOS.md, F). */
class RelSituacaoTcc extends AdmsRelatorioBase
{
    public const CODIGO = 'R-TCC-01';
    public const NOME = 'Situação do TCC';
    public const CATEGORIA = 'F';
    public const PERGUNTA = 'Como estão os TCC de quem vai concluir?';
    public const SLUG = 'situacao-tcc';
    public const QUEM = 'administrador, secretaria, coordenação';

    public function filtros(): array
    {
        $op = ['sem' => 'Sem TCC'];
        foreach (\App\adms\Models\tcc\AdmsTcc::SITUACOES as $k => $n) {
            $op[(string) $k] = $n;
        }
        return ['curso', 'situacao', 'de' => ['rotulo' => 'Previsão de', 'tipo' => 'data'], 'ate' => ['rotulo' => 'até', 'tipo' => 'data'],
            'tcc' => ['rotulo' => 'Situação do TCC', 'tipo' => 'select', 'opcoes' => $op]];
    }

    public function ficha(): array
    {
        return [
            'Uma linha por matrícula de curso que exige TCC (e por matrícula que tem TCC lançado em curso que não exige).',
            'Situação: Sem TCC (o curso exige e não há TCC cadastrado), Em andamento, Aprovado ou Reprovado (rm_tcc).',
            'Nota mínima: a da regra do TCC e dos documentos do curso (AdmsCalculoNotas::regras do curso, D-447), a mesma usada ao lançar o TCC.',
            'O período filtra a PREVISÃO de conclusão da matrícula.',
        ];
    }

    public function dados(array $f): array
    {
        $tccs = [];
        foreach (F::ler("SELECT t.*, u.nome AS orientador FROM rm_tcc t LEFT JOIN adms_usuarios u ON u.id = t.orientador_id") as $t) {
            $tccs[(int) $t['matricula_curso_id']] = $t;
        }
        $colunas = self::colunasAluno([self::col('matricula', 'Matrícula'), self::col('previsao', 'Previsão'), self::col('tcc', 'Situação do TCC'), self::col('nota', 'Nota', ['num' => true]),
            self::col('minima', 'Nota mínima', ['num' => true]), self::col('orientador', 'Orientador'), self::col('defesa', 'Defesa')]);
        $det = [];
        $rotulos = ['sem' => 'Sem TCC'] + array_combine(array_map('strval', array_keys(\App\adms\Models\tcc\AdmsTcc::SITUACOES)), \App\adms\Models\tcc\AdmsTcc::SITUACOES);
        foreach ($rotulos as $k => $r) {
            $det["tcc_{$k}"] = ['titulo' => 'TCC: ' . $r, 'colunas' => $colunas, 'linhas' => []];
        }
        $minimas = [];
        $linhas = [];
        foreach (F::matriculas(array_intersect_key($f, ['curso' => 1, 'situacao' => 1])) as $mc) {
            $t = $tccs[(int) $mc['id']] ?? null;
            if (!$t && (int) $mc['exige_tcc'] !== 1) {
                continue;
            }
            $p = (string) $mc['previsao_conclusao'];
            if ((!empty($f['de']) && ($p === '' || $p < $f['de'])) || (!empty($f['ate']) && ($p === '' || $p > $f['ate']))) {
                continue;
            }
            $k = $t ? (string) (int) $t['situacao'] : 'sem';
            if (!empty($f['tcc']) && $f['tcc'] !== $k) {
                continue;
            }
            $cid = (int) $mc['curso_id'];
            $minimas[$cid] ??= Calc::regras($cid);
            $c = $t ? Calc::centesimos($t['nota']) : null;
            $l = self::linhaAluno($mc, [
                'matricula' => (string) $mc['situacao'], 'previsao' => self::cel(F::data($mc['previsao_conclusao']), ['o' => $p]),
                'tcc' => $rotulos[$k] ?? '?', 'nota' => $c === null ? '' : Calc::formatar(number_format($c / 100, 2, '.', ''), $c % 10 ? 2 : 1),
                'minima' => Calc::formatar((string) $minimas[$cid]['mediaMinima'], (int) $minimas[$cid]['casas']),
                'orientador' => (string) ($t['orientador'] ?? ''), 'defesa' => F::data($t['data_defesa'] ?? null),
                '_classe' => $k === '3' ? 'table-danger' : ($k === 'sem' ? 'table-warning' : ''),
            ]);
            $linhas[] = $l;
            $det["tcc_{$k}"]['linhas'][] = $l;
        }
        $cartoes = [];
        foreach ($rotulos as $k => $r) {
            $cartoes[] = ['rotulo' => $r, 'valor' => self::contar($det, "tcc_{$k}"), 'detalhe' => "tcc_{$k}", 'cor' => ['sem' => 'bg-warning', '2' => 'bg-success', '3' => 'bg-danger'][$k] ?? 'bg-light'];
        }
        return [
            'cartoes' => $cartoes, 'detalhes' => $det,
            'tabela' => ['colunas' => $colunas, 'linhas' => $linhas, 'rodape' => ['ra' => 'Total', 'nome' => (string) count($linhas)]],
        ];
    }
}
