<?php

namespace App\adms\Models\escola;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\helper\AdmsRead;
use App\adms\Models\horarioAula\AdmsAulasDoDia as Aulas;

/**
 * O HORÁRIO DA TURMA NA SEMANA (Onda 8, correção, Parte 1.1; D-71): a grade das aulas do turno da turma (as aulas do
 * dia, AdmsAulasDoDia) nos dias da semana; em cada aula, um dos componentes da turma (a oferta, com o professor dela).
 * Guarda nas tabelas de sempre (rm_professores_horarios: a oferta, o dia, a aula e o turno), as mesmas que o portal do
 * aluno e o do responsável leem. Cada escolha grava na hora: numa transação, com a turma travada (duas pessoas na
 * mesma turma não deixam duas aulas no mesmo lugar), tira o que havia naquele dia e aula e põe o componente novo.
 * O mesmo professor em duas turmas na mesma aula não é recusado (o sistema de origem também não confere; ponto de
 * decisão no retorno).
 */
class AdmsHorarioTurma
{
    /** Os dias da grade (adms_dia_semana: 2 = segunda … 7 = sábado). */
    public const DIAS = [2 => 'Segunda', 3 => 'Terça', 4 => 'Quarta', 5 => 'Quinta', 6 => 'Sexta', 7 => 'Sábado'];

    /** O turno da turma (sem turno: o 1, o primeiro). */
    public static function turno(array $turma): int
    {
        return (int) ($turma['turno_id'] ?? 0) ?: 1;
    }

    /** @return array<string, int> "aula-dia" => a oferta, das ofertas da turma. */
    public static function grade(int $turmaId): array
    {
        $l = new AdmsRead();
        $l->fullRead('SELECT h.horario_id, h.dia_semana, h.rm_prof_mat_id FROM rm_professores_horarios h
            INNER JOIN esc_turma_ofertas x ON x.oferta_id = h.rm_prof_mat_id WHERE x.turma_id = :t AND h.situacao = 1 ORDER BY h.id', "t={$turmaId}");
        $g = [];
        foreach ($l->getResultado() ?: [] as $r) {
            $g[(int) $r['horario_id'] . '-' . (int) $r['dia_semana']] = (int) $r['rm_prof_mat_id'];
        }
        return $g;
    }

    /** Quantas aulas por semana cada oferta da turma tem na grade. @return array<int, int> */
    public static function contagem(array $grade): array
    {
        return array_count_values(array_values($grade));
    }

    /**
     * Grava uma aula: a oferta (0 = deixar a aula vaga) no dia e na aula. @return array{ok: bool, erro: string}
     */
    public static function gravar(array $turma, int $dia, int $aulaId, int $ofertaId, int $usuario): array
    {
        $turmaId = (int) $turma['id'];
        if (!isset(self::DIAS[$dia])) {
            return ['ok' => false, 'erro' => 'Dia da semana inválido.'];
        }
        $turno = self::turno($turma);
        $aula = Aulas::uma($aulaId);
        if (!$aula || (int) $aula['periodo'] !== $turno || (int) $aula['situacao'] !== 1) {
            return ['ok' => false, 'erro' => 'Esse horário de aula não está ativo no turno da turma.'];
        }
        $ofertas = array_column(AdmsTurmas::ofertas($turmaId), 'materia', 'id');
        if ($ofertaId !== 0 && !isset($ofertas[$ofertaId])) {
            return ['ok' => false, 'erro' => 'Essa disciplina não é desta turma.'];
        }
        $ids = implode(',', array_map('intval', array_keys($ofertas))) ?: '0';
        $antes = 0;
        $ok = \Core\Transacao::executar(static function () use ($turmaId, $dia, $aulaId, $ofertaId, $turno, $usuario, $ids, &$antes) {
            $pdo = AdmsEscolaBanco::pdo();
            $pdo->prepare('SELECT id FROM esc_turmas WHERE id = ? FOR UPDATE')->execute([$turmaId]);
            $q = $pdo->prepare("SELECT rm_prof_mat_id FROM rm_professores_horarios WHERE rm_prof_mat_id IN ({$ids}) AND dia_semana = ? AND horario_id = ? AND situacao = 1 LIMIT 1");
            $q->execute([$dia, $aulaId]);
            $antes = (int) ($q->fetchColumn() ?: 0);
            $pdo->prepare("DELETE FROM rm_professores_horarios WHERE rm_prof_mat_id IN ({$ids}) AND dia_semana = ? AND horario_id = ?")->execute([$dia, $aulaId]);
            if ($ofertaId) {
                $pdo->prepare('INSERT INTO rm_professores_horarios (rm_prof_mat_id, periodo, dia_semana, horario_id, situacao, usuario_cre, created) VALUES (?, ?, ?, ?, 1, ?, NOW())')
                    ->execute([$ofertaId, $turno, $dia, $aulaId, $usuario]);
            }
            return true;
        });
        if ($ok === false) {
            return ['ok' => false, 'erro' => 'Não foi possível gravar: ' . \Core\Transacao::ultimoErro() . '.'];
        }
        if ($antes !== $ofertaId) {
            (new \App\adms\Models\helper\AdmsSalvarLog())->salvarLogs(557, 200, 3, "Horário da turma: turma={$turmaId}; dia={$dia}; aula={$aulaId}; antes="
                . ($antes ? ($ofertas[$antes] ?? $antes) : 'vaga') . '; depois=' . ($ofertaId ? $ofertas[$ofertaId] : 'vaga'));
        }
        return ['ok' => true, 'erro' => ''];
    }
}
