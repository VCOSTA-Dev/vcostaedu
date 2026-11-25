<?php

namespace App\adms\Models\historico;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\notas\AdmsCalculoNotas as Calc;

/**
 * 1.17.0/1.17.1 (D-459) — O PROGRESSO do aluno no curso, a partir da vida acadêmica (AdmsVidaAcademica::montar, a mesma
 * fonte do histórico). Usado pelo relatório R-DES-05 (painel) e pelo "Meu progresso" do portal (R-POR-01, pela ponte):
 * uma conta só. Nada aqui calcula média: a média, a frequência e FS/FI vêm da tentativa vigente de cada disciplina.
 */
class AdmsProgressoAluno
{
    public const SITUACOES = ['Aprovado', 'Reprovado', 'Em curso', 'Não cursada'];

    /** ['linhas' => [...], 'totais' => [...]] de uma vida acadêmica montada. */
    public static function montar(array $v): array
    {
        $linhas = [];
        $ordem = 0;
        foreach ($v['modulos'] as $m) {
            foreach ($m['disciplinas'] as $d) {
                $vg = $d['vigente'];
                $linhas[] = [
                    'ordem' => ++$ordem, 'modulo' => (string) $m['nome'], 'disciplina' => (string) $d['nome'], 'ch' => (int) $d['ch'], 'situacao' => (string) $d['situacao'],
                    'oferta_id' => $vg ? (int) $vg['oferta_id'] : null, 'turma' => $vg ? $vg['turma'] . '/' . $vg['ano'] : '',
                    'media' => $vg && $vg['media'] !== null ? Calc::formatar((string) $vg['media'], (int) $vg['casas']) . ($vg['encerrada'] ? '' : ' (parcial)') : '—',
                    /* sem total de encontros: "—", como no boletim */
                    'frequencia' => !$vg ? '' : ($vg['frequencia'] !== null ? Calc::formatar((string) $vg['frequencia'], 2) : '—'),
                    'fsfi' => $vg ? Calc::siglaFrequencia(['gravado' => $vg['gravado'], 'frequencia' => $vg['frequencia'], 'frequenciaMinima' => $vg['frequenciaMinima'], 'encontros' => $vg['encontros']]) : '',
                ];
            }
        }
        $t = $v['totais'];
        return [
            'linhas' => $linhas,
            'totais' => [
                'chCumprida' => (int) $t['chIntegralizada'], 'chTotal' => (int) $t['chExigida'], 'aprovadas' => (int) $t['aprovadas'], 'disciplinas' => (int) $t['disciplinas'],
                'mediaFinal' => AdmsVidaAcademica::mediaFinalTexto($v, (int) $v['regras']['casas']),
                'exigeTcc' => (bool) $v['matricula']['exigeTcc'], 'tcc' => $v['tcc'] ? $v['tcc']['situacaoNome'] : null, 'notaTcc' => $v['tcc']['notaFormatada'] ?? null,
            ],
        ];
    }
}
