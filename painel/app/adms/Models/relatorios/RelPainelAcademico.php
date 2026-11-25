<?php

namespace App\adms\Models\relatorios;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\notas\AdmsCalculoNotas as Calc;
use App\adms\Models\relatorios\AdmsRelatorioFonte as F;

/** R-PAI-01 Painel acadêmico (docs/RELATORIOS.md, A). Todos os cartões abrem a lista que os compõe. */
class RelPainelAcademico extends AdmsRelatorioBase
{
    public const CODIGO = 'R-PAI-01';
    public const NOME = 'Painel acadêmico';
    public const CATEGORIA = 'A';
    public const PERGUNTA = 'Como está a instituição agora?';
    public const SLUG = 'painel-academico';
    public const QUEM = 'administrador, diretoria, secretaria, coordenação';

    public function filtros(): array
    {
        return ['curso', 'de' => ['rotulo' => 'Encerradas de', 'tipo' => 'data'], 'ate' => ['rotulo' => 'até', 'tipo' => 'data']];
    }

    public function ficha(): array
    {
        return [
            'Alunos ativos: alunos distintos com matrícula Cursando (no curso do filtro).',
            'Matrículas por situação: contagem de rm_matriculas_curso por situação (Cursando, Concluído, Trancado, Cancelado, Transferido).',
            'Ofertas abertas / encerradas: ofertas do curso com o encerramento vazio / preenchido.',
            'Taxa de aprovação = aprovados ÷ resultados gravados × 100, nas ofertas ENCERRADAS no período (data do encerramento). O resultado é o gravado no fechamento vigente.',
            'Reprovação por nota = reprovados com motivo "nota" ou "nota e frequência" ÷ resultados × 100; por frequência = motivo "frequência" ou "nota e frequência" ÷ resultados × 100. Quem reprovou pelos dois conta nos dois percentuais.',
            'Frequência média = média aritmética das frequências gravadas (%), nos mesmos resultados.',
            'Pendentes: pares aluno × oferta ABERTA em que o motor acusa pendência (componente obrigatório faltando ou acima do máximo) ou há atividade sem nota do aluno.',
            'Alunos em risco: matrículas ativas com ao menos um sinal do relatório Alunos em risco (R-EVA-01).',
            'Ofertas com pendência: ofertas abertas com componente obrigatório faltando, acima do máximo ou atividade sem nota. Ofertas atrasadas: abertas com a data de fim anterior a hoje.',
            'Sem período, valem todos os encerramentos. Os números com lista: a soma da lista é o número.',
        ];
    }

    public function dados(array $f): array
    {
        $det = [];
        $cartoes = [];
        $base = array_intersect_key($f, ['curso' => 1, 'turma' => 1]); // Onda 6, Etapa 5: + a turma da escola

        /* matrículas e alunos ativos */
        $mats = F::matriculas($base);
        $vistos = [];
        $det['ativos'] = ['titulo' => 'Alunos ativos', 'colunas' => self::colunasAluno([self::col('situacao', 'Situação')]), 'linhas' => []];
        foreach ($mats as $mc) {
            if ((int) $mc['situacao_id'] === 1 && !isset($vistos[(int) $mc['usuario_id']])) {
                $vistos[(int) $mc['usuario_id']] = true;
                $det['ativos']['linhas'][] = self::linhaAluno($mc, ['situacao' => $mc['situacao']]);
            }
        }
        $cartoes[] = ['rotulo' => 'Alunos ativos', 'valor' => self::contar($det, 'ativos'), 'detalhe' => 'ativos', 'cor' => 'bg-info'];
        foreach (F::ler("SELECT id, nome FROM rm_matriculas_situacao ORDER BY id") as $s) {
            $k = 'situacao_' . (int) $s['id'];
            $det[$k] = ['titulo' => 'Matrículas ' . $s['nome'], 'colunas' => self::colunasAluno([self::col('ingresso', 'Ingresso'), self::col('conclusao', 'Conclusão')]), 'linhas' => []];
            foreach ($mats as $mc) {
                if ((int) $mc['situacao_id'] === (int) $s['id']) {
                    $det[$k]['linhas'][] = self::linhaAluno($mc, ['ingresso' => F::data($mc['data_ingresso']), 'conclusao' => F::data($mc['data_conclusao'])]);
                }
            }
            $cartoes[] = ['rotulo' => 'Matrículas: ' . $s['nome'], 'valor' => self::contar($det, $k), 'detalhe' => $k];
        }

        /* ofertas */
        $ofertas = F::ofertas($base);
        $colOf = [self::col('oferta', 'Oferta'), self::col('curso', 'Curso'), self::col('inicio', 'Início'), self::col('fim', 'Fim'), self::col('encerrada', 'Encerrada em')];
        $linOf = fn($o) => ['oferta' => self::celOferta($o), 'curso' => (string) $o['curso'], 'inicio' => F::data($o['data_inicio']), 'fim' => F::data($o['data_fim']), 'encerrada' => F::data($o['encerrada_em'])];
        $det['ofertas_abertas'] = ['titulo' => 'Ofertas abertas', 'colunas' => $colOf, 'linhas' => []];
        $det['ofertas_encerradas'] = ['titulo' => 'Ofertas encerradas', 'colunas' => $colOf, 'linhas' => []];
        $det['ofertas_atrasadas'] = ['titulo' => 'Ofertas atrasadas (abertas com a data de fim já passada)', 'colunas' => $colOf, 'linhas' => []];
        $det['ofertas_pendencia'] = ['titulo' => 'Ofertas abertas com pendência', 'colunas' => array_merge($colOf, [self::col('pendencia', 'Pendência')]), 'linhas' => []];
        $det['pendentes'] = ['titulo' => 'Pendentes (aluno × oferta aberta)', 'colunas' => [self::col('ra', 'RA'), self::col('nome', 'Nome'), self::col('oferta', 'Oferta'), self::col('motivo', 'Pendência')], 'linhas' => []];
        $hoje = date('Y-m-d');
        $abertas = [];
        foreach ($ofertas as $o) {
            if ($o['encerrada_em']) {
                $det['ofertas_encerradas']['linhas'][] = $linOf($o);
                continue;
            }
            $abertas[] = $o;
            $det['ofertas_abertas']['linhas'][] = $linOf($o);
            if ($o['data_fim'] && $o['data_fim'] < $hoje) {
                $det['ofertas_atrasadas']['linhas'][] = $linOf($o);
            }
        }
        $rms = [];
        foreach ($abertas as $o) {
            $rms = array_merge($rms, array_column(F::provisorio((int) $o['id'])['alunos'] ?? [], 'rm'));
        }
        $alunosAbertas = F::alunosPorRm($rms);
        foreach ($abertas as $o) {
            $lim = \App\adms\Models\notas\AdmsRegraMedia::limitesOferta((int) $o['id']);
            $calc = F::provisorio((int) $o['id']);
            $semNota = 0;
            foreach ($calc['alunos'] ?? [] as $a) {
                $mot = (array) ($a['motivosPendencia'] ?? []);
                $faltaNota = (int) $a['atividades'] - (int) $a['lancadas'];
                if ($faltaNota > 0) {
                    $semNota++;
                    $mot[] = $faltaNota . ' atividade(s) sem nota';
                }
                if ($mot) {
                    $u = $alunosAbertas[(string) $a['rm']] ?? null;
                    $det['pendentes']['linhas'][] = ['ra' => (string) $a['rm'], 'nome' => $u ? self::nomeAluno($u) : (string) $a['nome'], 'oferta' => self::celOferta($o), 'motivo' => implode('; ', array_unique($mot))];
                }
            }
            $p = array_merge($lim['faltam'] ? ['falta: ' . implode(', ', $lim['faltam'])] : [], $lim['sobram'] ? ['acima do máximo: ' . implode(', ', $lim['sobram'])] : [], $semNota ? ["{$semNota} aluno(s) com atividade sem nota"] : []);
            if ($p) {
                $det['ofertas_pendencia']['linhas'][] = $linOf($o) + ['pendencia' => implode('; ', $p)];
            }
        }
        $cartoes[] = ['rotulo' => 'Ofertas abertas', 'valor' => self::contar($det, 'ofertas_abertas'), 'detalhe' => 'ofertas_abertas'];
        $cartoes[] = ['rotulo' => 'Ofertas encerradas', 'valor' => self::contar($det, 'ofertas_encerradas'), 'detalhe' => 'ofertas_encerradas'];

        /* resultados gravados no período */
        $res = F::resultadosGravados(F::ofertas($base + array_intersect_key($f, ['de' => 1, 'ate' => 1]), true));
        $alunosRes = F::alunosPorRm(array_map(fn($x) => $x['aluno']['rm'], $res));
        $colRes = [self::col('ra', 'RA'), self::col('nome', 'Nome'), self::col('oferta', 'Oferta'), self::col('media', 'Média', ['num' => true]), self::col('frequencia', 'Frequência (%)', ['num' => true]), self::col('resultado', 'Resultado')];
        foreach (['resultados' => 'Resultados gravados no período', 'aprovados' => 'Aprovados', 'rep_nota' => 'Reprovados por nota (inclui nota e frequência)', 'rep_freq' => 'Reprovados por frequência (inclui nota e frequência)'] as $k => $t) {
            $det[$k] = ['titulo' => $t, 'colunas' => $colRes, 'linhas' => []];
        }
        $somaFreq = 0;
        $nFreq = 0;
        foreach ($res as $x) {
            $a = $x['aluno'];
            $u = $alunosRes[(string) $a['rm']] ?? null;
            $l = ['ra' => (string) $a['rm'], 'nome' => $u ? self::nomeAluno($u) : (string) $a['nome'], 'oferta' => self::celOferta($x['oferta']),
                'media' => Calc::formatar((string) $a['media'], (int) $a['casas']), 'frequencia' => Calc::formatar((string) $a['frequencia'], 2),
                'resultado' => Calc::rotuloResultado((int) $a['resultado'], $a['motivo'])];
            $det['resultados']['linhas'][] = $l;
            if ((int) $a['resultado'] === Calc::APROVADO) {
                $det['aprovados']['linhas'][] = $l;
            }
            if (in_array($a['motivo'], ['nota', 'nota_frequencia'], true)) {
                $det['rep_nota']['linhas'][] = $l;
            }
            if (in_array($a['motivo'], ['frequencia', 'nota_frequencia'], true)) {
                $det['rep_freq']['linhas'][] = $l;
            }
            if ((int) $a['encontros'] > 0) {
                $somaFreq += \Core\MotorMedia::centesimosDe((string) $a['frequencia']); // % (0 a 100): Calc::centesimos é só para nota
                $nFreq++;
            }
        }
        $n = self::contar($det, 'resultados');
        $periodo = (!empty($f['de']) || !empty($f['ate'])) ? 'encerradas no período' : 'todas as encerradas';
        foreach (['aprovados' => 'Taxa de aprovação', 'rep_nota' => 'Reprovação por nota', 'rep_freq' => 'Reprovação por frequência'] as $k => $rot) {
            $cartoes[] = ['rotulo' => $rot, 'valor' => F::pct(self::contar($det, $k), $n), 'detalhe' => self::contar($det, $k) ? $k : null,
                'nota' => self::contar($det, $k) . " de {$n} resultado(s) gravado(s), {$periodo}", 'cor' => $k === 'aprovados' ? 'bg-success' : 'bg-light'];
        }
        $cartoes[] = ['rotulo' => 'Frequência média', 'valor' => $nFreq ? number_format($somaFreq / $nFreq / 100, 2, ',', '.') . '%' : '—', 'detalhe' => $n ? 'resultados' : null,
            'nota' => "média de {$nFreq} frequência(s) gravada(s), {$periodo}"];

        /* risco e pendências */
        $risco = F::sinaisRisco($base);
        $det['risco'] = ['titulo' => 'Alunos em risco (matrículas ativas com ao menos 1 sinal)', 'colunas' => self::colunasAluno([self::col('sinais', 'Sinais')]), 'linhas' => []];
        foreach ($risco as $x) {
            $det['risco']['linhas'][] = self::linhaAluno($x['mc'], ['sinais' => implode('; ', array_map(fn($k) => F::SINAIS[$k], array_keys($x['sinais'])))]);
        }
        $cartoes[] = ['rotulo' => 'Alunos em risco', 'valor' => self::contar($det, 'risco'), 'detalhe' => 'risco', 'cor' => 'bg-warning', 'nota' => 'detalhes em Alunos em risco'];
        $cartoes[] = ['rotulo' => 'Pendentes (aluno × oferta aberta)', 'valor' => self::contar($det, 'pendentes'), 'detalhe' => 'pendentes', 'nota' => 'provisório'];
        $cartoes[] = ['rotulo' => 'Ofertas com pendência', 'valor' => self::contar($det, 'ofertas_pendencia'), 'detalhe' => 'ofertas_pendencia'];
        $cartoes[] = ['rotulo' => 'Ofertas atrasadas', 'valor' => self::contar($det, 'ofertas_atrasadas'), 'detalhe' => 'ofertas_atrasadas', 'cor' => self::contar($det, 'ofertas_atrasadas') ? 'bg-danger' : 'bg-light'];

        return ['cartoes' => $cartoes, 'detalhes' => $det];
    }
}
