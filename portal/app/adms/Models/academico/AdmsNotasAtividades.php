<?php

namespace App\adms\Models\academico;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Pagina nao encontrada!");
}

class AdmsNotasAtividades
{
    public function listarDisciplinasPorRmAno($rm, $ano)
    {
        $rm = (int) $rm;
        $ano = (int) $ano;

        if($rm <= 0 || $ano <= 0){
            return [];
        }

        $leitor = new \App\adms\Models\helper\AdmsRead();
        $leitor->fullRead(
            "SELECT DISTINCT
                a.rm_prof_mat_id,
                m.codtur as turma,
                m.materia
            FROM portal_disciplinas_alunos a
            INNER JOIN rm_professores_materias m ON m.id = a.rm_prof_mat_id
            WHERE a.rm = :rm
                AND a.ano = :ano
                AND m.tipo NOT IN (4, 5)
            ORDER BY m.materia ASC",
            "rm={$rm}&ano={$ano}"
        );

        return $leitor->getResultado() ?? [];
    }

    public function listarNotasPorRmDisciplinas($rm, $disciplinasIds)
    {
        $rm = (int) $rm;

        if($rm <= 0 || empty($disciplinasIds) || !is_array($disciplinasIds)){
            return [];
        }

        $disciplinasIds = array_values(array_filter(array_map('intval', $disciplinasIds)));
        if(empty($disciplinasIds)){
            return [];
        }

        $idsIn = implode(',', $disciplinasIds);

        $leitor = new \App\adms\Models\helper\AdmsRead();
        $leitor->fullRead(
            "SELECT DISTINCT
                n.rm_prof_mat_id,
                m.materia,
                at.nomeAtividade,
                n.nota
            FROM rm_disciplina_atividade_notas n
            INNER JOIN rm_disciplina_atividade at
                ON at.rm_prof_mat_id = n.rm_prof_mat_id
                AND n.atividade_id = at.id
            INNER JOIN rm_professores_materias m
                ON m.id = n.rm_prof_mat_id
            WHERE n.rm = :rm
                AND n.rm_prof_mat_id IN ({$idsIn})
            ORDER BY m.materia ASC, at.data_inicial ASC, at.nomeAtividade ASC",
            "rm={$rm}"
        );
        $notas = $leitor->getResultado() ?? [];

        /* Correção da escola, Parte 1 (A5, D-100): na escola, a nota da recuperação aparece aqui como as outras notas
           lançadas (em tempo real), depois das atividades da etapa dela; a nota da etapa que resulta fica para o boletim
           liberado. No ensino superior não há recuperação por etapa: a lista é a de sempre. */
        if (!\App\adms\Models\helper\AdmsAtualizacaoPendente::exige('0027')) {
            return $notas;
        }
        $rec = new \App\adms\Models\helper\AdmsRead();
        $rec->fullRead(
            "SELECT r.oferta_id AS rm_prof_mat_id, m.materia, CONCAT('Recuperação (', e.nome, ')') AS nomeAtividade, r.nota, e.fim
               FROM esc_recuperacoes r INNER JOIN esc_etapas e ON e.id = r.etapa_id INNER JOIN rm_professores_materias m ON m.id = r.oferta_id
              WHERE r.rm = :rm AND r.oferta_id IN ({$idsIn}) AND r.nota IS NOT NULL
              ORDER BY e.ordem",
            "rm={$rm}"
        );
        $recs = $rec->getResultado() ?: [];
        if (!$recs) {
            return $notas;
        }
        /* a ordem da lista de sempre (a disciplina e a data inicial da atividade); a recuperação entra depois das
           atividades que começaram até o fim da etapa dela */
        $datas = [];
        $ld = new \App\adms\Models\helper\AdmsRead();
        $ld->fullRead("SELECT rm_prof_mat_id, nomeAtividade, MIN(data_inicial) AS data FROM rm_disciplina_atividade WHERE rm_prof_mat_id IN ({$idsIn}) GROUP BY rm_prof_mat_id, nomeAtividade");
        foreach ($ld->getResultado() ?: [] as $a) {
            $datas[(int) $a['rm_prof_mat_id'] . '|' . $a['nomeAtividade']] = (string) $a['data'];
        }
        $linhas = [];
        foreach ($notas as $i => $n) {
            $linhas[] = [(string) $n['materia'], substr($datas[(int) $n['rm_prof_mat_id'] . '|' . $n['nomeAtividade']] ?? '', 0, 10), 0, $i, $n];
        }
        foreach ($recs as $i => $r) {
            $linhas[] = [(string) $r['materia'], (string) $r['fim'], 1, $i, array_diff_key($r, ['fim' => 1])];
        }
        usort($linhas, static fn($a, $b) => [$a[0], $a[1], $a[2], $a[3]] <=> [$b[0], $b[1], $b[2], $b[3]]);
        $saida = array_column($linhas, 4);
        return $saida;
    }
}
