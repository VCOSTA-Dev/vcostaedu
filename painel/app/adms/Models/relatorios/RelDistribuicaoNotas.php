<?php

namespace App\adms\Models\relatorios;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\notas\AdmsCalculoNotas as Calc;
use App\adms\Models\relatorios\AdmsRelatorioFonte as F;

/** R-DES-03 Distribuição de notas (docs/RELATORIOS.md, C). */
class RelDistribuicaoNotas extends AdmsRelatorioBase
{
    public const CODIGO = 'R-DES-03';
    public const NOME = 'Distribuição de notas';
    public const CATEGORIA = 'C';
    public const PERGUNTA = 'As notas estão concentradas ou espalhadas?';
    public const SLUG = 'distribuicao-notas';
    public const QUEM = 'administrador, diretoria, secretaria, coordenação';

    public function filtros(): array
    {
        $mat = [];
        foreach (F::ler("SELECT DISTINCT m.id, m.nome FROM rm_materias m INNER JOIN rm_professores_materias o ON o.codmat = m.id ORDER BY m.nome") as $r) {
            $mat[(int) $r['id']] = $r['nome'];
        }
        return ['curso', 'oferta', 'disciplina' => ['rotulo' => 'Disciplina', 'tipo' => 'select', 'opcoes' => $mat],
            'de' => ['rotulo' => 'Encerradas de', 'tipo' => 'data'], 'ate' => ['rotulo' => 'até', 'tipo' => 'data']];
    }

    public function ficha(): array
    {
        return [
            'Histograma das médias em faixas de 1 ponto: 0 a 1, 1 a 2, …, 9 a 10 (a faixa inclui o início e exclui o fim; 10 entra na última).',
            'Sem oferta escolhida: as médias GRAVADAS das ofertas encerradas (o período filtra a data do encerramento).',
            'Com uma oferta ABERTA escolhida: as médias PROVISÓRIAS (como se encerrasse hoje, pelo motor), com o rótulo.',
            'Cada faixa abre a lista; a soma das faixas é o total de médias.',
        ];
    }

    public function dados(array $f): array
    {
        $itens = [];
        $provisorio = false;
        if (!empty($f['oferta'])) {
            $o = F::ofertas(['oferta' => $f['oferta']])[0] ?? null;
            if ($o && empty($o['encerrada_em'])) {
                $provisorio = true;
                foreach (F::provisorio((int) $o['id'])['alunos'] ?? [] as $a) {
                    if ($a['media'] !== null) {
                        $itens[] = ['oferta' => $o, 'aluno' => $a];
                    }
                }
            } elseif ($o) {
                $itens = F::resultadosGravados([$o]);
            }
        } else {
            $of = array_filter(F::ofertas($f, true), fn($o) => empty($f['disciplina']) || (int) $o['codmat'] === (int) $f['disciplina']);
            $itens = F::resultadosGravados($of);
        }
        $alunos = F::alunosPorRm(array_map(fn($x) => $x['aluno']['rm'], $itens));
        $colDet = [self::col('ra', 'RA'), self::col('nome', 'Nome'), self::col('oferta', 'Oferta'), self::col('media', $provisorio ? 'Média provisória' : 'Média', ['num' => true])];
        $det = [];
        for ($i = 0; $i < 10; $i++) {
            $det["faixa_{$i}"] = ['titulo' => "Médias de {$i} a " . ($i + 1) . ($provisorio ? ' (provisórias)' : ''), 'colunas' => $colDet, 'linhas' => []];
        }
        foreach ($itens as $x) {
            $a = $x['aluno'];
            $c = Calc::centesimos((string) $a['media']) ?? 0;
            $u = $alunos[(string) $a['rm']] ?? null;
            $det['faixa_' . min(9, intdiv($c, 100))]['linhas'][] = ['ra' => (string) $a['rm'], 'nome' => $u ? self::nomeAluno($u) : (string) $a['nome'],
                'oferta' => self::celOferta($x['oferta']), 'media' => self::cel(Calc::formatar((string) $a['media'], (int) $a['casas']), ['o' => $c])];
        }
        $linhas = [];
        $graf = ['titulo' => 'Distribuição das médias' . ($provisorio ? ' (provisórias)' : ''), 'tipo' => 'bar', 'rotulos' => [], 'series' => [['rotulo' => 'Alunos', 'dados' => [], 'cor' => '#17a2b8']]];
        $total = count($itens);
        for ($i = 0; $i < 10; $i++) {
            $rot = $i . ' a ' . ($i + 1) . ($i === 9 ? ' (inclui 10)' : '');
            $n = self::contar($det, "faixa_{$i}");
            $linhas[] = ['faixa' => self::cel($rot, ['o' => $i]), 'n' => self::numero($det, "faixa_{$i}"), 'pct' => F::pct($n, $total)];
            $graf['rotulos'][] = "{$i}–" . ($i + 1);
            $graf['series'][0]['dados'][] = $n;
        }
        return [
            'avisos' => $provisorio ? ['Oferta aberta: médias PROVISÓRIAS (se encerrasse hoje).'] : ($total ? [] : ['Nenhuma média com estes filtros.']),
            'grafico' => $graf, 'detalhes' => $det,
            'tabela' => ['colunas' => [self::col('faixa', 'Faixa'), self::col('n', 'Médias', ['num' => true]), self::col('pct', '%', ['num' => true])], 'linhas' => $linhas,
                'ordem' => [[0, 'asc']], 'rodape' => ['faixa' => 'Total', 'n' => (string) $total, 'pct' => $total ? '100%' : '—']],
        ];
    }
}
