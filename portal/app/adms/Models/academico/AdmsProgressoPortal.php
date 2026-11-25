<?php

namespace App\adms\Models\academico;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\helper\AdmsPainelPonte;

/**
 * 1.17.1 (R-POR-01): o "Meu progresso" do aluno. A conta é a do painel (AdmsVidaAcademica + AdmsProgressoAluno, pela
 * ponte): a mesma do relatório "Desempenho do aluno". Toda leitura confere que a matrícula é do aluno da sessão.
 */
class AdmsProgressoPortal
{
    /** As matrículas do aluno (a mais recente primeiro). */
    public static function matriculas(int $alunoId): array
    {
        return AdmsAtestadoAluno::matriculas($alunoId);
    }

    /** O progresso de UMA matrícula do aluno; null se a matrícula não é dele. */
    public static function progresso(int $alunoId, int $mcId): ?array
    {
        if (!in_array($mcId, array_map('intval', array_column(self::matriculas($alunoId), 'id')), true)) {
            return null;
        }
        AdmsPainelPonte::carregar();
        $v = \App\adms\Models\historico\AdmsVidaAcademica::montar($mcId);
        if (!$v || (int) $v['aluno']['id'] !== $alunoId) {
            return null;
        }
        return ['curso' => $v['matricula']['curso'], 'situacao' => $v['matricula']['situacao']] + \App\adms\Models\historico\AdmsProgressoAluno::montar($v);
    }
}
