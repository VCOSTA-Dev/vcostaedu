<?php

namespace App\adms\Models\relatorios;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\notas\AdmsCalculoNotas as Calc;
use App\adms\Models\relatorios\AdmsRelatorioFonte as F;

/** R-DES-02 Resultado por disciplina, o grau de dificuldade (docs/RELATORIOS.md, C). Só resultados GRAVADOS. */
class RelResultadoDisciplina extends AdmsRelatorioBase
{
    public const CODIGO = 'R-DES-02';
    public const NOME = 'Resultado por disciplina';
    public const CATEGORIA = 'C';
    public const PERGUNTA = 'Onde estão os gargalos?';
    public const SLUG = 'resultado-disciplina';
    public const QUEM = 'administrador, diretoria, secretaria, coordenação';

    public function filtros(): array
    {
        return ['curso', 'grade', 'de' => ['rotulo' => 'Encerradas de', 'tipo' => 'data'], 'ate' => ['rotulo' => 'até', 'tipo' => 'data'],
            'agrupar' => ['rotulo' => 'Agrupar por', 'tipo' => 'select', 'opcoes' => ['disciplina' => 'disciplina', 'oferta' => 'oferta'], 'padrao' => 'disciplina']];
    }

    public function ficha(): array
    {
        return [
            'Só ofertas ENCERRADAS (o resultado gravado no fechamento vigente); o período filtra a data do encerramento. Pendentes não entram (só existem em oferta aberta: ver Pendências de lançamento).',
            'n = resultados gravados (aluno × oferta). Aprovados (%) = aprovados ÷ n × 100.',
            'Reprovados por nota (%) = motivo "nota" ou "nota e frequência" ÷ n × 100; por frequência (%) = motivo "frequência" ou "nota e frequência" ÷ n × 100. Quem reprovou pelos dois conta nos dois.',
            'Média das médias = média aritmética das médias gravadas; desvio-padrão POPULACIONAL das médias gravadas (2 casas).',
            'Agrupar por disciplina junta as ofertas da mesma matéria; por oferta, uma linha por turma.',
            'O gráfico mostra a taxa de reprovação (reprovados ÷ n) de cada linha.',
        ];
    }

    public function dados(array $f): array
    {
        $porOferta = ($f['agrupar'] ?? 'disciplina') === 'oferta';
        $res = F::resultadosGravados(F::ofertas($f, true));
        $alunos = F::alunosPorRm(array_map(fn($x) => $x['aluno']['rm'], $res));
        $colDet = [self::col('ra', 'RA'), self::col('nome', 'Nome'), self::col('oferta', 'Oferta'), self::col('media', 'Média', ['num' => true]), self::col('frequencia', 'Frequência (%)', ['num' => true]), self::col('resultado', 'Resultado')];
        $grupos = [];
        foreach ($res as $x) {
            $o = $x['oferta'];
            $g = $porOferta ? 'o' . (int) $o['id'] : 'm' . (int) $o['codmat'];
            $grupos[$g]['rotulo'] ??= $porOferta ? F::rotuloOferta($o) : (string) $o['materia'];
            $grupos[$g]['curso'] ??= (string) $o['curso'];
            $grupos[$g]['itens'][] = $x;
        }
        uasort($grupos, fn($a, $b) => strcmp($a['rotulo'], $b['rotulo']));
        $det = [];
        $linhas = [];
        $graf = ['titulo' => 'Taxa de reprovação por ' . ($porOferta ? 'oferta' : 'disciplina'), 'tipo' => 'bar', 'rotulos' => [], 'sufixo' => '%', 'maximo' => 100,
            'series' => [['rotulo' => 'Reprovação', 'dados' => [], 'cor' => '#dc3545']], 'altura' => 320];
        $tot = ['n' => 0, 'ap' => 0, 'nota' => 0, 'freq' => 0, 'rep' => 0, 'medias' => []];
        foreach ($grupos as $g => $gr) {
            $k = ['n' => "g{$g}_todos", 'ap' => "g{$g}_aprovados", 'nota' => "g{$g}_nota", 'freq' => "g{$g}_frequencia"];
            foreach ($k as $tipo => $chave) {
                $det[$chave] = ['titulo' => $gr['rotulo'] . ' — ' . ['n' => 'resultados', 'ap' => 'aprovados', 'nota' => 'reprovados por nota', 'freq' => 'reprovados por frequência'][$tipo], 'colunas' => $colDet, 'linhas' => []];
            }
            $medias = [];
            $rep = 0;
            foreach ($gr['itens'] as $x) {
                $a = $x['aluno'];
                $u = $alunos[(string) $a['rm']] ?? null;
                $l = ['ra' => (string) $a['rm'], 'nome' => $u ? self::nomeAluno($u) : (string) $a['nome'], 'oferta' => self::celOferta($x['oferta']),
                    'media' => Calc::formatar((string) $a['media'], (int) $a['casas']), 'frequencia' => Calc::formatar((string) $a['frequencia'], 2),
                    'resultado' => Calc::rotuloResultado((int) $a['resultado'], $a['motivo'])];
                $det[$k['n']]['linhas'][] = $l;
                if ((int) $a['resultado'] === Calc::APROVADO) {
                    $det[$k['ap']]['linhas'][] = $l;
                } else {
                    $rep++;
                }
                if (in_array($a['motivo'], ['nota', 'nota_frequencia'], true)) {
                    $det[$k['nota']]['linhas'][] = $l;
                }
                if (in_array($a['motivo'], ['frequencia', 'nota_frequencia'], true)) {
                    $det[$k['freq']]['linhas'][] = $l;
                }
                $medias[] = Calc::centesimos((string) $a['media']) ?? 0;
            }
            $n = self::contar($det, $k['n']);
            $pct = fn($chave) => self::cel(self::contar($det, $chave) . ' (' . F::pct(self::contar($det, $chave), $n) . ')', ['detalhe' => self::contar($det, $chave) ? $chave : null, 'o' => $n ? self::contar($det, $chave) / $n : 0]);
            [$media, $dp] = self::mediaDesvio($medias);
            $linhas[] = [
                'grupo' => $gr['rotulo'], 'curso' => $gr['curso'], 'n' => self::numero($det, $k['n']), 'aprovados' => $pct($k['ap']),
                'nota' => $pct($k['nota']), 'frequencia' => $pct($k['freq']), 'media' => $media, 'desvio' => $dp,
            ];
            $graf['rotulos'][] = mb_strimwidth($gr['rotulo'], 0, 40, '…');
            $graf['series'][0]['dados'][] = $n ? round($rep * 100 / $n, 1) : 0;
            $tot['n'] += $n;
            $tot['ap'] += self::contar($det, $k['ap']);
            $tot['nota'] += self::contar($det, $k['nota']);
            $tot['freq'] += self::contar($det, $k['freq']);
            $tot['medias'] = array_merge($tot['medias'], $medias);
        }
        [$mt, $dt] = self::mediaDesvio($tot['medias']);
        return [
            'grafico' => $graf, 'detalhes' => $det,
            'avisos' => $res ? [] : ['Nenhum resultado gravado com estes filtros (só ofertas encerradas entram).'],
            'tabela' => [
                'colunas' => [self::col('grupo', $porOferta ? 'Oferta' : 'Disciplina'), self::col('curso', 'Curso'), self::col('n', 'Resultados (n)', ['num' => true]),
                    self::col('aprovados', 'Aprovados', ['num' => true]), self::col('nota', 'Reprovados por nota', ['num' => true]),
                    self::col('frequencia', 'Reprovados por frequência', ['num' => true]), self::col('media', 'Média das médias', ['num' => true]),
                    self::col('desvio', 'Desvio-padrão', ['num' => true])],
                'linhas' => $linhas, 'ordem' => [[0, 'asc']],
                'rodape' => ['grupo' => 'Total', 'n' => (string) $tot['n'], 'aprovados' => "{$tot['ap']} (" . F::pct($tot['ap'], $tot['n']) . ')',
                    'nota' => "{$tot['nota']} (" . F::pct($tot['nota'], $tot['n']) . ')', 'frequencia' => "{$tot['freq']} (" . F::pct($tot['freq'], $tot['n']) . ')', 'media' => $mt, 'desvio' => $dt],
            ],
        ];
    }

    /** Média e desvio-padrão populacional de médias em centésimos → ["7,25", "1,10"]. */
    public static function mediaDesvio(array $cent): array
    {
        $n = count($cent);
        if (!$n) {
            return ['—', '—'];
        }
        $m = array_sum($cent) / $n;
        $v = array_sum(array_map(fn($x) => ($x - $m) ** 2, $cent)) / $n;
        return [number_format($m / 100, 2, ',', '.'), number_format(sqrt($v) / 100, 2, ',', '.')];
    }
}
