<?php

namespace App\adms\Models\auditoria;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\helper\AdmsEscopo;
use App\adms\Models\helper\AdmsRead;

/**
 * A consulta da auditoria de notas e faltas (Onda 2, Etapa 3). Só SELECT. O recorte do coordenador vem de
 * AdmsEscopo (a função única), pelo curso gravado em cada linha.
 */
class AdmsAuditoriaConsulta
{
    public const LIMITE = 2000;
    public const TIPOS = ['nota' => 'Nota', 'falta' => 'Faltas', 'presenca' => 'Presença na chamada', 'compensacao' => 'Compensação de faltas', 'fechamento' => 'Encerramento e reabertura'];
    public const ACOES = ['lancou' => 'lançou', 'alterou' => 'alterou', 'apagou' => 'apagou', 'encerrou' => 'encerrou', 'reabriu' => 'reabriu'];
    public const ORIGENS = ['painel' => 'Tela do painel', 'professor' => 'Tela do professor', 'importacao' => 'Importação', 'api' => 'API'];

    /** Os filtros válidos da URL (o resto é ignorado). */
    public function filtrosDaUrl(array $get): array
    {
        $f = [];
        foreach (['curso', 'oferta', 'usuario'] as $k) {
            if (!empty($get[$k]) && ctype_digit((string) $get[$k])) {
                $f[$k] = (int) $get[$k];
            }
        }
        if (!empty($get['aluno'])) {
            $f['aluno'] = mb_substr(trim((string) $get['aluno']), 0, 60);
        }
        foreach (['de', 'ate'] as $k) {
            if (!empty($get[$k]) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $get[$k])) {
                $f[$k] = (string) $get[$k];
            }
        }
        if (!empty($get['tipo']) && isset(self::TIPOS[$get['tipo']])) {
            $f['tipo'] = (string) $get['tipo'];
        }
        if (!empty($get['so_alteracoes'])) {
            $f['so_alteracoes'] = 1;
        }
        return $f;
    }

    public function listar(array $f): array
    {
        $w = ['1 = 1'];
        $p = [];
        foreach (['curso' => 'a.curso_id', 'oferta' => 'a.oferta_id', 'usuario' => 'a.usuario_id'] as $k => $col) {
            if (!empty($f[$k])) {
                $w[] = "{$col} = :{$k}";
                $p[$k] = $f[$k];
            }
        }
        if (!empty($f['aluno'])) {
            $w[] = '(a.rm = :rm OR a.aluno_nome LIKE :nome)';
            $p['rm'] = $f['aluno'];
            $p['nome'] = '%' . $f['aluno'] . '%';
        }
        if (!empty($f['de'])) {
            $w[] = 'a.ocorrido_em >= :de';
            $p['de'] = $f['de'] . ' 00:00:00';
        }
        if (!empty($f['ate'])) {
            $w[] = 'a.ocorrido_em <= :ate';
            $p['ate'] = $f['ate'] . ' 23:59:59.999';
        }
        if (!empty($f['tipo'])) {
            $w[] = 'a.tipo = :tipo';
            $p['tipo'] = $f['tipo'];
        }
        if (!empty($f['so_alteracoes'])) {
            $w[] = "a.acao IN ('alterou', 'apagou')";
        }
        $sql = "SELECT a.*, o.materia, o.codtur, o.ano, c.nome AS curso, t.nomeAtividade AS atividade
                  FROM rm_auditoria_lancamentos a
                  LEFT JOIN rm_professores_materias o ON o.id = a.oferta_id
                  LEFT JOIN rm_cursos c ON c.id = a.curso_id
                  LEFT JOIN rm_disciplina_atividade t ON t.id = a.atividade_id
                 WHERE " . implode(' AND ', $w) . AdmsEscopo::sql('a.curso_id') . "
                 ORDER BY a.ocorrido_em DESC, a.id DESC LIMIT " . self::LIMITE;
        return self::ler($sql, $p);
    }

    /** O histórico de uma nota: a avaliação e o aluno. */
    public function historicoDaNota(int $atividadeId, string $rm): array
    {
        $info = self::ler("SELECT t.id, t.nomeAtividade AS atividade, o.id AS oferta_id, o.materia, o.codtur, o.ano,
                                  n.rm, n.nome_aluno, n.nota
                             FROM rm_disciplina_atividade t
                             JOIN rm_professores_materias o ON o.id = t.rm_prof_mat_id
                             LEFT JOIN rm_disciplina_atividade_notas n ON n.atividade_id = t.id AND n.rm = :rm
                            WHERE t.id = :a LIMIT 1", ['a' => $atividadeId, 'rm' => $rm])[0] ?? null;
        $linhas = $info ? self::ler("SELECT * FROM rm_auditoria_lancamentos WHERE tipo = 'nota' AND atividade_id = :a AND rm = :rm ORDER BY ocorrido_em DESC, id DESC",
            ['a' => $atividadeId, 'rm' => $rm]) : [];
        return ['info' => $info, 'linhas' => $linhas];
    }

    /** As opções dos filtros, já com o recorte (o coordenador só vê os cursos dele). */
    public function opcoes(): array
    {
        return [
            'cursos' => self::ler("SELECT id, nome FROM rm_cursos WHERE 1 = 1" . AdmsEscopo::sql('id') . " ORDER BY nome"),
            'ofertas' => self::ler("SELECT o.id, o.materia, o.codtur, o.ano FROM rm_professores_materias o WHERE 1 = 1" . AdmsEscopo::sql('o.curso_id') . " ORDER BY o.ano DESC, o.materia, o.codtur"),
            'usuarios' => self::ler("SELECT DISTINCT a.usuario_id AS id, a.usuario_nome AS nome FROM rm_auditoria_lancamentos a WHERE a.usuario_id IS NOT NULL" . AdmsEscopo::sql('a.curso_id') . " ORDER BY a.usuario_nome"),
        ];
    }

    private static function ler(string $sql, array $p = []): array
    {
        $ler = new AdmsRead();
        $ler->fullRead($sql, http_build_query($p, '', '&', PHP_QUERY_RFC3986));
        return $ler->getResultado() ?: [];
    }
}
