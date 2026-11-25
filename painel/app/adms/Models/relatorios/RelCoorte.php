<?php

namespace App\adms\Models\relatorios;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\relatorios\AdmsRelatorioFonte as F;

/** R-EVA-02 Coorte de ingresso / fluxo (docs/RELATORIOS.md, E; onda 2). */
class RelCoorte extends AdmsRelatorioBase
{
    public const CODIGO = 'R-EVA-02';
    public const NOME = 'Coorte de ingresso';
    public const CATEGORIA = 'E';
    public const PERGUNTA = 'Do que entrou em cada período, quantos concluíram, seguem, trancaram ou saíram?';
    public const SLUG = 'coorte-ingresso';
    public const QUEM = 'administrador, diretoria';

    public const GRUPOS = ['ingressantes' => 'Ingressantes', 'concluintes' => 'Concluintes', 'cursando' => 'Cursando', 'trancados' => 'Trancados', 'evadidos' => 'Evadidos', 'retidos' => 'Em atraso no fluxo'];

    public function filtros(): array
    {
        return ['curso', 'polo'];
    }

    public function ficha(): array
    {
        return [
            'Coorte = o SEMESTRE da data de ingresso da matrícula (janeiro a junho = /1; julho a dezembro = /2) (D-463).',
            'Ingressantes = matrículas da coorte. Concluintes = Concluído; Cursando; Trancados = Trancado; Evadidos = Cancelado + Transferido; Trancado não é evasão (D-463).',
            'Taxa de conclusão = concluintes ÷ ingressantes; evasão = evadidos ÷ ingressantes.',
            'Retenção (atraso no fluxo) = (trancados + cursando com a previsão de conclusão já vencida) ÷ ingressantes (D-463). A lista "Em atraso no fluxo" mostra quem conta.',
            'Atenção: NÃO é a retenção "reprovação + trancamento" usada por outras instituições; aqui mede quem está atrasado em relação ao fluxo previsto (trancou ou passou da previsão de conclusão sem concluir).',
            'A situação é a de HOJE de cada matrícula (não um retrato do passado). Cada número abre a lista.',
        ];
    }

    public function dados(array $f): array
    {
        $hoje = date('Y-m-d');
        $por = [];
        foreach (F::matriculas($f) as $mc) {
            $d = (string) $mc['data_ingresso'];
            $c = $d === '' ? 'sem data' : substr($d, 0, 4) . '/' . ((int) substr($d, 5, 2) <= 6 ? 1 : 2);
            $s = (int) $mc['situacao_id'];
            $por[$c]['ingressantes'][] = $mc;
            $g = [1 => 'cursando', 2 => 'concluintes', 3 => 'trancados', 4 => 'evadidos', 5 => 'evadidos'][$s] ?? null;
            if ($g) {
                $por[$c][$g][] = $mc;
            }
            if ($s === 3 || ($s === 1 && !empty($mc['previsao_conclusao']) && $mc['previsao_conclusao'] < $hoje)) {
                $por[$c]['retidos'][] = $mc;
            }
        }
        krsort($por);
        $colDet = self::colunasAluno([self::col('ingresso', 'Ingresso'), self::col('situacao', 'Situação'), self::col('previsao', 'Previsão'), self::col('conclusao', 'Conclusão')]);
        $det = [];
        $linhas = [];
        $graf = ['titulo' => 'Situação de hoje por coorte de ingresso', 'tipo' => 'bar', 'empilhado' => true, 'rotulos' => [], 'series' => []];
        $serie = ['concluintes' => '#28a745', 'cursando' => '#17a2b8', 'trancados' => '#ffc107', 'evadidos' => '#dc3545'];
        foreach ($serie as $g => $cor) {
            $graf['series'][] = ['rotulo' => self::GRUPOS[$g], 'dados' => [], 'cor' => $cor];
        }
        foreach ($por as $c => $gr) {
            $l = ['coorte' => self::cel($c, ['o' => $c])];
            $id = preg_replace('/[^0-9a-z]/', '', $c);
            foreach (self::GRUPOS as $g => $t) {
                $det["c{$id}_{$g}"] = ['titulo' => "Coorte {$c} — {$t}", 'colunas' => $colDet,
                    'linhas' => array_map(fn($mc) => self::linhaAluno($mc, ['ingresso' => F::data($mc['data_ingresso']), 'situacao' => $mc['situacao'],
                        'previsao' => F::data($mc['previsao_conclusao']), 'conclusao' => F::data($mc['data_conclusao'])]), $gr[$g] ?? [])];
                $l[$g] = self::numero($det, "c{$id}_{$g}");
            }
            $n = self::contar($det, "c{$id}_ingressantes");
            $l['conclusao'] = F::pct(self::contar($det, "c{$id}_concluintes"), $n);
            $l['evasao'] = F::pct(self::contar($det, "c{$id}_evadidos"), $n);
            $l['retencao'] = F::pct(self::contar($det, "c{$id}_retidos"), $n);
            $linhas[] = $l;
            $graf['rotulos'][] = $c;
            $i = 0;
            foreach (array_keys($serie) as $g) {
                $graf['series'][$i++]['dados'][] = self::contar($det, "c{$id}_{$g}");
            }
        }
        $cols = [self::col('coorte', 'Coorte')];
        foreach (self::GRUPOS as $g => $t) {
            $cols[] = self::col($g, $t, ['num' => true]);
        }
        $cols = array_merge($cols, [self::col('conclusao', 'Conclusão', ['num' => true]), self::col('evasao', 'Evasão', ['num' => true]), self::col('retencao', 'Retenção (atraso no fluxo)', ['num' => true, 'dica' => 'Não é a retenção "reprovação + trancamento" de outras instituições: trancados + cursando com a previsão vencida, ÷ ingressantes'])]);
        return ['grafico' => $graf, 'detalhes' => $det, 'tabela' => ['colunas' => $cols, 'linhas' => $linhas, 'ordem' => [[0, 'desc']]]];
    }
}
