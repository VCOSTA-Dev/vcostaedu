<?php

namespace App\adms\Models\relatorios;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\relatorios\AdmsRelatorioFonte as F;

/**
 * R-AUD-01 Reaberturas (docs/RELATORIOS.md, H; onda 2). Correção da escola, Parte 1 (B10, C5, D-100): na escola, também
 * a reabertura de cada ETAPA, com o motivo digitado; sem nome de tabela nem código interno na tela; quem reabre (a
 * supervisão de secretaria, a direção acadêmica e o administrador) vê o relatório. No ensino superior, as linhas e as
 * colunas são as de antes (a disciplina encerrada e reaberta).
 */
class RelReaberturas extends AdmsRelatorioBase
{
    public const CODIGO = 'R-AUD-01';
    public const NOME = 'Reaberturas';
    public const CATEGORIA = 'H';
    public const PERGUNTA = 'Quem reabriu o que estava fechado, quando e por quê?';
    public const SLUG = 'reaberturas';
    public const QUEM = 'administrador, direção acadêmica, supervisão de secretaria';

    private static function escola(): bool
    {
        return \App\adms\Models\helper\AdmsTipoInstituicao::ehEscola();
    }

    public function filtros(): array
    {
        return ['curso', 'de' => ['rotulo' => 'Reabertas de', 'tipo' => 'data'], 'ate' => ['rotulo' => 'até', 'tipo' => 'data']];
    }

    public function ficha(): array
    {
        if (self::escola()) {
            return [
                'Cada reabertura: a de uma etapa fechada (com o motivo digitado por quem reabriu) e a de uma disciplina encerrada no fim do ano. O período filtra a data da reabertura.',
                'Mostra quem fechou e quando, quem reabriu e quando, o motivo e quantas notas o fechamento tinha guardado. O fechamento de antes da reabertura fica guardado.',
                'Os documentos emitidos antes de uma reabertura estão no relatório Documentos emitidos.',
            ];
        }
        return [
            'Cada encerramento desfeito: a disciplina foi reaberta depois de encerrada. O período filtra a data da reabertura.',
            'Mostra quem encerrou e quando, quem reabriu e quando, a justificativa e quantos resultados o encerramento guardou.',
            'Os documentos emitidos antes de uma reabertura estão no relatório Documentos emitidos.',
        ];
    }

    public function dados(array $f): array
    {
        $w = ["f.situacao = 'substituido'"];
        $p = [];
        if (!empty($f['curso'])) {
            $w[] = 'o.curso_id = :curso';
            $p['curso'] = (int) $f['curso'];
        }
        if (!empty($f['de'])) {
            $w[] = 'f.reaberto_em >= :de';
            $p['de'] = $f['de'] . ' 00:00:00';
        }
        if (!empty($f['ate'])) {
            $w[] = 'f.reaberto_em <= :ate';
            $p['ate'] = $f['ate'] . ' 23:59:59';
        }
        $escola = self::escola();
        $linhas = [];
        foreach (F::ler(
            "SELECT f.*, o.id AS oid, o.materia, o.codtur, o.ano, c.nome AS curso, uf.nome AS fechou, ur.nome AS reabriu,
                    (SELECT COUNT(*) FROM rm_media_fechamento_resultados x WHERE x.fechamento_id = f.id) AS guardados
               FROM rm_media_fechamentos f INNER JOIN rm_professores_materias o ON o.id = f.rm_prof_mat_id LEFT JOIN rm_cursos c ON c.id = o.curso_id
               LEFT JOIN adms_usuarios uf ON uf.id = f.fechado_por LEFT JOIN adms_usuarios ur ON ur.id = f.reaberto_por
              WHERE " . implode(' AND ', $w) . " ORDER BY f.reaberto_em DESC",
            $p
        ) as $r) {
            $linhas[] = [
                'oferta' => self::cel(F::rotuloOferta($r), ['url' => URLADM . 'disciplinas/encerrar/' . (int) $r['oid']]), 'curso' => (string) $r['curso'],
                'fechamento' => (string) $r['numero'], 'fechou' => (string) $r['fechou'], 'fechadoEm' => self::cel(date('d/m/Y H:i', strtotime($r['fechado_em'])), ['o' => $r['fechado_em']]),
                'reabriu' => (string) $r['reabriu'], 'reabertoEm' => self::cel($r['reaberto_em'] ? date('d/m/Y H:i', strtotime($r['reaberto_em'])) : '', ['o' => (string) $r['reaberto_em']]),
                'justificativa' => (string) $r['justificativa_reabertura'], 'guardados' => (string) $r['guardados'],
            ] + ($escola ? ['oque' => 'A disciplina (o ano encerrado)'] : []);
        }
        if ($escola) {
            /* a reabertura da etapa (esc_fechamentos: o fechamento reaberto fica guardado, com o motivo) */
            foreach (F::ler(
                "SELECT f.*, o.id AS oid, o.materia, o.codtur, o.ano, c.nome AS curso, e.nome AS etapa, uf.nome AS fechou, ur.nome AS reabriu,
                        (SELECT COUNT(*) FROM esc_fechamento_notas x WHERE x.fechamento_id = f.id) AS guardados
                   FROM esc_fechamentos f INNER JOIN rm_professores_materias o ON o.id = f.oferta_id INNER JOIN esc_etapas e ON e.id = f.etapa_id
                   LEFT JOIN rm_cursos c ON c.id = o.curso_id LEFT JOIN adms_usuarios uf ON uf.id = f.fechado_por LEFT JOIN adms_usuarios ur ON ur.id = f.reaberto_por
                  WHERE " . implode(' AND ', $w) . " ORDER BY f.reaberto_em DESC",
                $p
            ) as $r) {
                $linhas[] = [
                    'oferta' => self::cel(F::rotuloOferta($r), ['url' => URLADM . 'escola-notas/etapas/' . (int) $r['oid'] . '?etapa=' . (int) $r['etapa_id']]), 'curso' => (string) $r['curso'],
                    'fechamento' => (string) $r['numero'], 'fechou' => (string) $r['fechou'], 'fechadoEm' => self::cel(date('d/m/Y H:i', strtotime($r['fechado_em'])), ['o' => $r['fechado_em']]),
                    'reabriu' => (string) $r['reabriu'], 'reabertoEm' => self::cel($r['reaberto_em'] ? date('d/m/Y H:i', strtotime($r['reaberto_em'])) : '', ['o' => (string) $r['reaberto_em']]),
                    'justificativa' => (string) $r['motivo'], 'guardados' => (string) $r['guardados'], 'oque' => 'A etapa: ' . $r['etapa'],
                ];
            }
            usort($linhas, static fn($a, $b) => strcmp((string) ($b['reabertoEm']['o'] ?? ''), (string) ($a['reabertoEm']['o'] ?? '')));
        }
        $det = ['todas' => ['titulo' => 'Reaberturas', 'colunas' => [], 'linhas' => $linhas]];
        $cols = $escola
            ? [self::col('oferta', 'Disciplina'), self::col('oque', 'O que foi reaberto'), self::col('curso', 'Segmento'), self::col('fechamento', 'Fechamento nº', ['num' => true]), self::col('fechou', 'Fechado por'),
                self::col('fechadoEm', 'Em'), self::col('reabriu', 'Reaberto por'), self::col('reabertoEm', 'Em'), self::col('justificativa', 'Motivo'), self::col('guardados', 'Notas guardadas', ['num' => true])]
            : [self::col('oferta', 'Oferta'), self::col('curso', 'Curso'), self::col('fechamento', 'Fechamento nº', ['num' => true]), self::col('fechou', 'Encerrada por'),
                self::col('fechadoEm', 'Em'), self::col('reabriu', 'Reaberta por'), self::col('reabertoEm', 'Em'), self::col('justificativa', 'Justificativa'), self::col('guardados', 'Resultados guardados', ['num' => true])];
        $det['todas']['colunas'] = $cols;
        return ['cartoes' => [['rotulo' => 'Reaberturas', 'valor' => self::contar($det, 'todas'), 'detalhe' => 'todas']], 'detalhes' => $det,
            'tabela' => ['colunas' => $cols, 'linhas' => $linhas, 'ordem' => [[$escola ? 7 : 6, 'desc']]]];
    }
}
