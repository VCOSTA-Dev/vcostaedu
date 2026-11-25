<?php

namespace App\adms\Models\academico;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Pagina nao encontrada!");
}

class AdmsHorarioAluno
{
    public function listarHorarioPorRmAno($rm, $ano)
    {
        $rm  = (int) $rm;
        $ano = (int) $ano;

        if($rm <= 0 || $ano <= 0){
            return [];
        }

        $leitor = new \App\adms\Models\helper\AdmsRead();
        $leitor->fullRead(
            "SELECT
                m.id,
                m.codtur,
                m.materia,
                d.id AS dia_id,
                d.dia,
                ha.inicio,
                ha.termino,
                (SELECT f.nome FROM rm_cursos g INNER JOIN rm_cursos_periodos f ON f.id = g.periodo WHERE g.id = m.curso_id) AS periodoAula,
                /* E8d (P-2): no portal vale a modalidade da MATRÍCULA do aluno no curso (se houver); senão, a da oferta */
                (SELECT LOWER(md.nome) FROM uni_rh_cursos_modalidade md WHERE md.id = COALESCE(
                    (SELECT mc.modalidade_id FROM rm_matriculas_curso mc INNER JOIN col_usuarios cu ON cu.id = mc.usuario_id
                      WHERE cu.rm = a.rm AND mc.curso_id = m.curso_id ORDER BY mc.id DESC LIMIT 1),
                    m.modalidade_id, (SELECT g3.modalidade_id FROM rm_cursos g3 WHERE g3.id = m.curso_id))) AS modalidadeNome,
                (SELECT GROUP_CONCAT(DISTINCT r.nome ORDER BY r.nome ASC SEPARATOR ', ')
                    FROM rm_professores_materias_salas z
                    INNER JOIN rm_salas r ON r.id = z.sala_id
                    WHERE z.rm_prof_mat_id = m.id AND z.situacao = 1) AS sala,
                (SELECT GROUP_CONCAT(u.nome SEPARATOR ', ')
                    FROM rm_professores_mat_liga l
                    INNER JOIN adms_usuarios u ON u.id = l.usuario_id
                    WHERE l.rm_prof_mat_id = m.id AND l.acesso = 1 AND l.situacao = 1) AS professores
            FROM portal_disciplinas_alunos a
            INNER JOIN rm_professores_materias m ON m.id = a.rm_prof_mat_id
            INNER JOIN rm_professores_horarios h ON h.rm_prof_mat_id = m.id
            INNER JOIN adms_dia_semana d ON d.id = h.dia_semana
            INNER JOIN rm_professores_horario_aula ha ON ha.id = h.horario_id
            WHERE a.rm = :rm
                AND a.ano = :ano
                AND a.situacao = 1
                AND m.tipo NOT IN (4, 5)
                /* Onda 8, correção (D-71): a aula do dia desativada sai do portal (o que foi montado nela fica guardado) */
                AND h.situacao = 1
                AND ha.situacao = 1
            ORDER BY d.id ASC, ha.inicio ASC, m.materia ASC",
            "rm={$rm}&ano={$ano}"
        );

        return $leitor->getResultado() ?? [];
    }
}
