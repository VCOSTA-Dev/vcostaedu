<?php

namespace App\adms\Models\vinculo;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/** Vínculos de um aluno (matrículas no curso + posições por ano) e as opções dos formulários. */
class AdmsListarVinculos
{
    public function doAluno(int $usuarioId): array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT mc.id, mc.curso_id, mc.grade_id, mc.forma_ingresso_id, mc.data_ingresso, mc.situacao_id, mc.observacao,
                    mc.polo_id, mc.modalidade_id, mc.data_conclusao, mc.previsao_conclusao, c.exige_tcc, c.localidade AS poloCurso, c.modalidade_id AS modalidadeCurso,
                    (SELECT p.nome FROM rm_polos p WHERE p.id = COALESCE(mc.polo_id, c.localidade)) AS polo,
                    (SELECT md.nome FROM uni_rh_cursos_modalidade md WHERE md.id = COALESCE(mc.modalidade_id, c.modalidade_id)) AS modalidade,
                    c.nome AS curso, g.versao, t.nome AS formaIngresso, s.nome AS situacao, s.cores
               FROM rm_matriculas_curso mc
               INNER JOIN rm_cursos c ON c.id = mc.curso_id
               INNER JOIN rm_cursos_grades g ON g.id = mc.grade_id
               LEFT JOIN rm_matricula_tipos t ON t.id = mc.forma_ingresso_id
               LEFT JOIN rm_matriculas_situacao s ON s.id = mc.situacao_id
              WHERE mc.usuario_id = :u
              ORDER BY mc.data_ingresso DESC, mc.id DESC",
            "u={$usuarioId}"
        );
        $vinculos = $ler->getResultado() ?: [];
        foreach ($vinculos as &$v) {
            $v['sugestaoConclusao'] = AdmsVinculoRegras::sugestaoConclusao((int) $v['grade_id']); // E1b (D-354)
            $ler = new \App\adms\Models\helper\AdmsRead();
            $ler->fullRead(
                "SELECT me.id, me.ano, me.modulo_id, m.codnome AS modulo, m.ordem, me.situacao,
                        me.enturmacao_confirmada_em, u.nome AS confirmadaPor,
                        (SELECT COUNT(*) FROM portal_disciplinas_alunos p
                           INNER JOIN rm_professores_materias o ON o.id = p.rm_prof_mat_id
                          WHERE p.rm = (SELECT rm FROM col_usuarios WHERE id = me.usuario_id) AND o.ano = me.ano
                            AND o.curso_id = me.ensino_id) AS enturmacoes
                   FROM rm_matriculas_ensino me
                   LEFT JOIN rm_cursos_turma_series m ON m.id = me.modulo_id
                   LEFT JOIN adms_usuarios u ON u.id = me.enturmacao_confirmada_por
                  WHERE me.matricula_curso_id = :mc
                  ORDER BY me.ano DESC",
                "mc={$v['id']}"
            );
            $v['posicoes'] = $ler->getResultado() ?: [];
            $ler = new \App\adms\Models\helper\AdmsRead();
            $ler->fullRead("SELECT id, codnome, ordem FROM rm_cursos_turma_series WHERE grade_id = :g ORDER BY ordem", "g={$v['grade_id']}");
            $v['modulosDaGrade'] = $ler->getResultado() ?: [];
            $v['moduloInterno'] = \App\adms\Models\helper\AdmsModuloInterno::ativo() ? \App\adms\Models\helper\AdmsModuloInterno::garantir((int) $v['grade_id']) : 0; // E8c (D-268)
            $v['tcc'] = \App\adms\Models\tcc\AdmsTcc::daMatricula((int) $v['id']); // E6a
        }
        unset($v);
        return $vinculos;
    }

    /** Opções: módulos de grades não desativadas (o módulo implica curso e grade), formas, situações, anos. */
    public function opcoes(): array
    {
        $o = [];
        /* E8c (D-268): sem módulos, uma opção por GRADE (o módulo interno vai escondido no valor) */
        $semModulos = \App\adms\Models\helper\AdmsModuloInterno::ativo();
        if ($semModulos) {
            $ler = new \App\adms\Models\helper\AdmsRead();
            $ler->fullRead("SELECT id FROM rm_cursos_grades WHERE situacao <> :d", "d=4");
            foreach ($ler->getResultado() ?: [] as $gr) {
                \App\adms\Models\helper\AdmsModuloInterno::garantir((int) $gr['id']);
            }
        }
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT m.id, m.codnome, m.ordem, g.versao, g.id AS grade_id, c.nome AS curso, s.nome AS situacaoGrade,
                    (SELECT p.nome FROM rm_polos p WHERE p.id = c.localidade) AS poloCurso
               FROM rm_cursos_turma_series m
               INNER JOIN rm_cursos_grades g ON g.id = m.grade_id
               INNER JOIN rm_cursos c ON c.id = g.curso_id
               LEFT JOIN uni_rh_cursos_grades_situacao s ON s.id = g.situacao
              WHERE g.situacao <> :d AND m.situacao = :a AND c.situacao = 1"
                . ($semModulos ? " AND m.id = (SELECT x.id FROM rm_cursos_turma_series x WHERE x.grade_id = g.id ORDER BY x.situacao = 1 DESC, x.ordem, x.id LIMIT 1)" : '') . "
              ORDER BY c.nome, g.versao, m.ordem",
            "d=4&a=1"
        );
        $o['modulos'] = $ler->getResultado() ?: [];
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT id, nome FROM rm_matricula_tipos WHERE situacao = :s ORDER BY id", "s=1");
        $o['formas'] = $ler->getResultado() ?: [];
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT id, nome FROM rm_matriculas_situacao WHERE situacao = :s ORDER BY id", "s=1");
        $o['situacoes'] = $ler->getResultado() ?: [];
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT ano FROM adms_periodos_letivos ORDER BY ano DESC");
        $o['anos'] = array_column($ler->getResultado() ?: [], 'ano');
        /* E6a: polo e modalidade da matrícula; orientadores do TCC */
        $o['polos'] = \App\adms\Models\polos\AdmsListarPolos::ativos();
        $o['modalidades'] = \App\adms\Models\modalidades\AdmsModalidades::ativas();
        $o['orientadores'] = \App\adms\Models\tcc\AdmsTcc::orientadores();
        return $o;
    }
}
