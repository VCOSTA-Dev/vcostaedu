<?php

namespace App\adms\Models\relatorios;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\relatorios\AdmsRelatorioFonte as F;

/** R-DES-04 Pendências de lançamento (docs/RELATORIOS.md, C). Só ofertas abertas. */
class RelPendenciasLancamento extends AdmsRelatorioBase
{
    public const CODIGO = 'R-DES-04';
    public const NOME = 'Pendências de lançamento';
    public const CATEGORIA = 'C';
    public const PERGUNTA = 'O que falta lançar antes de encerrar?';
    public const SLUG = 'pendencias-lancamento';
    public const QUEM = 'administrador, secretaria, coordenação';

    public function filtros(): array
    {
        return ['curso', 'oferta'];
    }

    public function ficha(): array
    {
        return [
            'Uma linha por oferta ABERTA.',
            'Alunos com atividade sem nota: alunos enturmados ativos com ao menos uma atividade ativa sem nota lançada.',
            'Componente obrigatório faltando / acima do máximo: AdmsRegraMedia::limitesOferta, contra a regra efetiva da oferta.',
            'Chamada sem encontro: oferta com a frequência pela chamada e nenhum encontro registrado. Sem total de encontros: a frequência não pode ser calculada.',
            'Atrasada: a data de fim da oferta já passou.',
            'Pendentes pelo motor: alunos cuja média, se a oferta fosse encerrada hoje, ficaria pendente (motivos do motor). Provisório.',
        ];
    }

    public function dados(array $f): array
    {
        $ofertas = F::ofertas(['situacao_oferta' => 'aberta'] + $f);
        $enc = [];
        foreach (F::ler("SELECT rm_prof_mat_id, COUNT(*) AS n FROM rm_oferta_encontros GROUP BY rm_prof_mat_id") as $r) {
            $enc[(int) $r['rm_prof_mat_id']] = (int) $r['n'];
        }
        $rms = [];
        foreach ($ofertas as $o) {
            $rms = array_merge($rms, array_column(F::provisorio((int) $o['id'])['alunos'] ?? [], 'rm'));
        }
        $alunos = F::alunosPorRm($rms);
        $colAl = [self::col('ra', 'RA'), self::col('nome', 'Nome'), self::col('detalhe', 'Pendência')];
        $det = [];
        $linhas = [];
        $hoje = date('Y-m-d');
        $tot = ['semNota' => 0, 'pend' => 0, 'ofertas' => 0];
        foreach ($ofertas as $o) {
            $id = (int) $o['id'];
            $calc = F::provisorio($id);
            $lim = \App\adms\Models\notas\AdmsRegraMedia::limitesOferta($id);
            $det["o{$id}_semnota"] = ['titulo' => F::rotuloOferta($o) . ' — alunos com atividade sem nota', 'colunas' => $colAl, 'linhas' => []];
            $det["o{$id}_pendentes"] = ['titulo' => F::rotuloOferta($o) . ' — pendentes pelo motor (provisório)', 'colunas' => $colAl, 'linhas' => []];
            foreach ($calc['alunos'] ?? [] as $a) {
                $u = $alunos[(string) $a['rm']] ?? null;
                $nome = $u ? self::nomeAluno($u) : (string) $a['nome'];
                $falta = (int) $a['atividades'] - (int) $a['lancadas'];
                if ($falta > 0) {
                    $det["o{$id}_semnota"]['linhas'][] = ['ra' => (string) $a['rm'], 'nome' => $nome, 'detalhe' => "{$falta} de {$a['atividades']} atividade(s) sem nota"];
                }
                if (!empty($a['motivosPendencia'])) {
                    $det["o{$id}_pendentes"]['linhas'][] = ['ra' => (string) $a['rm'], 'nome' => $nome, 'detalhe' => 'Pendente — ' . implode('; ', (array) $a['motivosPendencia'])];
                }
            }
            $semEnc = (int) ($o['encontros'] ?? 0) <= 0;
            $chamadaVazia = $o['frequencia_fonte'] === 'chamada' && empty($enc[$id]);
            $atrasada = $o['data_fim'] && $o['data_fim'] < $hoje;
            $l = [
                'oferta' => self::celOferta($o), 'curso' => (string) $o['curso'],
                'semNota' => self::numero($det, "o{$id}_semnota"),
                'faltam' => $lim['faltam'] ? implode('; ', $lim['faltam']) : '',
                'sobram' => $lim['sobram'] ? implode('; ', $lim['sobram']) : '',
                'chamada' => $chamadaVazia ? 'sim' : '', 'encontros' => $semEnc ? 'sim' : '',
                'atrasada' => $atrasada ? self::cel(F::data($o['data_fim']), ['classe' => 'text-danger']) : '',
                'pendentes' => self::numero($det, "o{$id}_pendentes"),
            ];
            $tem = self::contar($det, "o{$id}_semnota") || $lim['faltam'] || $lim['sobram'] || $chamadaVazia || $semEnc || $atrasada || self::contar($det, "o{$id}_pendentes");
            if (!$tem) {
                $l['_classe'] = 'text-muted';
            } else {
                $tot['ofertas']++;
            }
            $tot['semNota'] += self::contar($det, "o{$id}_semnota");
            $tot['pend'] += self::contar($det, "o{$id}_pendentes");
            $linhas[] = $l;
        }
        return [
            'cartoes' => [
                ['rotulo' => 'Ofertas abertas', 'valor' => count($ofertas)],
                ['rotulo' => 'Ofertas com alguma pendência', 'valor' => $tot['ofertas'], 'cor' => $tot['ofertas'] ? 'bg-warning' : 'bg-success'],
            ],
            'detalhes' => $det,
            'tabela' => [
                'colunas' => [self::col('oferta', 'Oferta'), self::col('curso', 'Curso'), self::col('semNota', 'Alunos com atividade sem nota', ['num' => true]),
                    self::col('faltam', 'Componente obrigatório faltando'), self::col('sobram', 'Acima do máximo'), self::col('chamada', 'Chamada sem encontro'),
                    self::col('encontros', 'Sem total de encontros'), self::col('atrasada', 'Atrasada (fim)'), self::col('pendentes', 'Pendentes pelo motor (prov.)', ['num' => true])],
                'linhas' => $linhas,
                'rodape' => ['oferta' => 'Total', 'semNota' => (string) $tot['semNota'], 'pendentes' => (string) $tot['pend']],
            ],
        ];
    }
}
