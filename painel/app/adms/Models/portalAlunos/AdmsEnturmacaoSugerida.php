<?php

namespace App\adms\Models\portalAlunos;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * ENTURMAÇÃO SUGERIDA (E3b, D-00c). A partir da POSIÇÃO do aluno no ano
 * (rm_matriculas_ensino: curso, grade_id, modulo_id), lista as ofertas do ano do
 * mesmo curso cuja linha da matriz é da MESMA GRADE (matriz_id → grade_id), e as
 * ofertas sem linha (legado) do curso. Marcadas:
 *   - antes da primeira confirmação: as do MÓDULO do aluno + as já enturmadas;
 *   - depois: exatamente as enturmadas.
 * A secretaria ajusta e confirma (AdmsConfirmarEnturmacao).
 */
class AdmsEnturmacaoSugerida
{
    public function montar(string $rm, int $ano): array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT id, rm, nome FROM col_usuarios WHERE rm = :rm AND col_niveis_acesso_id = :n LIMIT :limit", "rm={$rm}&n=1&limit=1");
        $aluno = $ler->getResultado()[0] ?? null;
        if (!$aluno) {
            return [];
        }
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT me.id, me.ano, me.ensino_id, me.grade_id, me.modulo_id, me.situacao, me.enturmacao_confirmada_em, me.enturmacao_modulo_id,
                    c.nome AS curso, g.versao, m.codnome AS modulo, s.nome AS nomeSituacao, u.nome AS confirmadaPor
               FROM rm_matriculas_ensino me
               INNER JOIN rm_cursos c ON c.id = me.ensino_id
               LEFT JOIN rm_cursos_grades g ON g.id = me.grade_id
               LEFT JOIN rm_cursos_turma_series m ON m.id = me.modulo_id
               LEFT JOIN rm_matriculas_situacao s ON s.id = me.situacao
               LEFT JOIN adms_usuarios u ON u.id = me.enturmacao_confirmada_por
              WHERE me.usuario_id = :u AND me.ano = :a
              ORDER BY c.nome",
            "u={$aluno['id']}&a={$ano}"
        );
        $posicoes = $ler->getResultado() ?: [];
        foreach ($posicoes as &$p) {
            $p['ofertas'] = self::ofertas($p, (string) $aluno['rm']);
        }
        unset($p);
        return ['aluno' => $aluno, 'ano' => $ano, 'posicoes' => $posicoes];
    }

    /** Ofertas do ano do curso e da grade da posição, com as marcas de sugestão e de enturmação. */
    public static function ofertas(array $posicao, string $rm): array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT o.id, o.materia, o.codtur, o.matriz_id, o.encerrada_em, l.modulo_id, m.codnome AS modulo, m.ordem,
                    (SELECT p.nome FROM rm_polos p WHERE p.id = o.codfilial) AS polo,
                    (SELECT COUNT(*) FROM rm_disciplina_media_final f
                       INNER JOIN rm_professores_materias o2 ON o2.id = f.rm_prof_mat_id
                       INNER JOIN uni_rh_cursos_disciplinas_grade l2 ON l2.id = o2.matriz_id
                      WHERE f.rm = :rm2 AND f.resultado = 1 AND l2.materia_id = l.materia_id AND o2.id <> o.id) AS aprovadaAntes,
                    (SELECT e.id FROM portal_disciplinas_alunos e WHERE e.rm = :rm AND e.rm_prof_mat_id = o.id LIMIT 1) AS enturmacaoId
               FROM rm_professores_materias o
               LEFT JOIN uni_rh_cursos_disciplinas_grade l ON l.id = o.matriz_id
               LEFT JOIN rm_cursos_turma_series m ON m.id = l.modulo_id
              WHERE o.ano = :a AND o.curso_id = :c AND o.situacao = :s
                AND (l.grade_id = :g OR o.matriz_id IS NULL)
              ORDER BY IFNULL(m.ordem, 999), o.materia, o.codtur",
            "rm={$rm}&rm2={$rm}&a={$posicao['ano']}&c={$posicao['ensino_id']}&s=1&g=" . (int) $posicao['grade_id']
        );
        $ofertas = $ler->getResultado() ?: [];
        /* E6a (decisão do responsável pelo projeto sobre a E5): a confirmação vale para o módulo em que foi feita. Se o
           aluno mudou de módulo depois dela, as disciplinas do novo módulo voltam SUGERIDAS E MARCADAS
           (só gravam quando a secretaria confirma). */
        $confirmada = !empty($posicao['enturmacao_confirmada_em']) && (int) ($posicao['enturmacao_modulo_id'] ?? 0) === (int) $posicao['modulo_id'];
        /* E8c (D-268): sem módulos, a sugestão são as ofertas ABERTAS das disciplinas da grade que o aluno ainda
           não cursou com aprovação; a secretaria ajusta e confirma. */
        $semModulos = \App\adms\Models\helper\AdmsModuloInterno::ativo();
        foreach ($ofertas as &$o) {
            $o['enturmado'] = !empty($o['enturmacaoId']);
            $o['aprovadaAntes'] = (int) $o['aprovadaAntes'] > 0;
            $o['sugerida'] = $semModulos
                ? ($o['matriz_id'] !== null && empty($o['encerrada_em']) && !$o['aprovadaAntes'])
                : ($o['modulo_id'] !== null && (int) $o['modulo_id'] === (int) $posicao['modulo_id']);
            $o['encerrada'] = !empty($o['encerrada_em']);
            /* E5: oferta encerrada não é sugerida (e o enturmar nela é recusado na confirmação). */
            $o['marcada'] = $o['enturmado'] || (!$confirmada && $o['sugerida'] && !$o['encerrada']);
        }
        unset($o);
        return $ofertas;
    }
}
