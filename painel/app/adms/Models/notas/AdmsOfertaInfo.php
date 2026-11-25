<?php

namespace App\adms\Models\notas;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * FICHA DA OFERTA (E8c) — os dados da disciplina que as telas da disciplina mostram no cartão lateral e na barra
 * (R5): matéria, turma, ano, curso, módulo, professor(es), polo (o do curso), modalidade, período, situação,
 * alunos enturmados. E a permissão de cada destino da barra para o perfil, com a razão quando não pode (R4).
 */
class AdmsOfertaInfo
{
    public static function ficha(int $ofertaId): ?array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT o.id, o.materia, o.codtur, o.ano, o.encontros, o.encerrada_em, o.data_inicio, o.data_fim, o.idMoodle,
                    c.nome AS curso, p.nome AS polo, COALESCE(mo.nome, mc.nome) AS modalidade, m.codnome AS modulo,
                    (SELECT GROUP_CONCAT(u.nome ORDER BY u.nome SEPARATOR ', ') FROM rm_professores_mat_liga l INNER JOIN adms_usuarios u ON u.id = l.usuario_id
                      WHERE l.rm_prof_mat_id = o.id AND l.acesso = 1 AND l.situacao = 1) AS professores,
                    (SELECT COUNT(*) FROM portal_disciplinas_alunos a WHERE a.rm_prof_mat_id = o.id AND a.situacao = 1) AS alunos
               FROM rm_professores_materias o
               LEFT JOIN rm_cursos c ON c.id = o.curso_id
               LEFT JOIN rm_polos p ON p.id = c.localidade
               LEFT JOIN uni_rh_cursos_modalidade mo ON mo.id = o.modalidade_id
               LEFT JOIN uni_rh_cursos_modalidade mc ON mc.id = c.modalidade_id
               LEFT JOIN uni_rh_cursos_disciplinas_grade l ON l.id = o.matriz_id
               LEFT JOIN rm_cursos_turma_series m ON m.id = l.modulo_id
              WHERE o.id = :id LIMIT :limit",
            "id={$ofertaId}&limit=1"
        );
        return $ler->getResultado()[0] ?? null;
    }

    /** Permissões da barra da disciplina (R5/R4): [destino => true | razão]. */
    public static function permissoesBarra(): array
    {
        $bt = (new \App\adms\Models\AdmsBotao())->valBotao([
            'disciplina' => ['menu_controller' => 'disciplinas', 'menu_metodo' => 'visualizar'],
            'atividades' => ['menu_controller' => 'atividades', 'menu_metodo' => 'listar'],
            'notas' => ['menu_controller' => 'medias', 'menu_metodo' => 'final'],
            'faltas' => ['menu_controller' => 'faltas', 'menu_metodo' => 'visualizar'],
            'chamada' => ['menu_controller' => 'chamada', 'menu_metodo' => 'listar'], // 1.14.0 (D-406)
            'encerramento' => ['menu_controller' => 'disciplinas', 'menu_metodo' => 'encerrar'],
            'regra' => ['menu_controller' => 'regra-media', 'menu_metodo' => 'oferta'], // 1.13.0 (D-398)
            'etapas' => ['menu_controller' => 'escola-notas', 'menu_metodo' => 'etapas'], // Onda 8, correção (D-72): só na escola
        ]);
        $razoes = ['encerramento' => 'Só a secretaria e o administrador encerram a disciplina', 'regra' => 'Só a secretaria e o administrador mudam a regra de média'];
        $o = [];
        foreach ($bt as $k => $pode) {
            $o[$k] = $pode ? true : ($razoes[$k] ?? 'Esta tela não é do seu perfil');
        }
        return $o;
    }

    /** Situação da matrícula no curso de cada aluno (RA => nome da situação), para as etiquetas das listas. */
    public static function situacoesMatricula(int $ofertaId): array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT u.rm, s.nome FROM rm_professores_materias o
               INNER JOIN portal_disciplinas_alunos a ON a.rm_prof_mat_id = o.id AND a.situacao = 1
               INNER JOIN col_usuarios u ON u.rm = a.rm
               INNER JOIN rm_matriculas_curso mc ON mc.usuario_id = u.id AND mc.curso_id = o.curso_id
               INNER JOIN rm_matriculas_situacao s ON s.id = mc.situacao_id
              WHERE o.id = :o",
            "o={$ofertaId}"
        );
        return array_column($ler->getResultado() ?: [], 'nome', 'rm');
    }
}
