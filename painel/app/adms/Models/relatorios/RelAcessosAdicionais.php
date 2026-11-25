<?php

namespace App\adms\Models\relatorios;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\relatorios\AdmsRelatorioFonte as F;

/** R-AUD-05 Acessos acadêmicos adicionais em vigor (docs/RELATORIOS.md, H; Onda 7 — só na escola). */
class RelAcessosAdicionais extends AdmsRelatorioBase
{
    public const CODIGO = 'R-AUD-05';
    public const NOME = 'Acessos acadêmicos adicionais em vigor';
    public const CATEGORIA = 'H';
    public const PERGUNTA = 'Quem tem hoje o acesso acadêmico adicional a um aluno, por quê e quem liberou?';
    public const SLUG = 'acessos-adicionais';
    public const QUEM = 'administrador, diretoria, secretaria';

    public function filtros(): array
    {
        $anos = [];
        foreach (F::ler('SELECT ano FROM esc_anos ORDER BY ano DESC') as $r) {
            $anos[(int) $r['ano']] = (string) $r['ano'];
        }
        return ['ano' => ['rotulo' => 'Ano letivo', 'tipo' => 'select', 'opcoes' => $anos], 'turma'];
    }

    public function ficha(): array
    {
        return [
            'Cada liberação do acesso acadêmico adicional em vigor hoje (Responsáveis, na tela do aluno): começou e não foi encerrada.',
            'Mostra o aluno, a turma no ano, a pessoa, desde quando, o motivo, a referência do termo e quem liberou.',
            'Com a regra da escola desligada (Regras da escola), as liberações continuam listadas, mas não dão acesso ao portal; o aviso no alto diz quando.',
        ];
    }

    public function dados(array $f): array
    {
        $w = ["d.papel = 'adicional'", 'd.inicio <= CURDATE()', '(d.fim IS NULL OR d.fim > CURDATE())'];
        $p = [];
        if (!empty($f['ano'])) {
            $w[] = 'd.ano = :ano';
            $p['ano'] = (int) $f['ano'];
        }
        if (!empty($f['turma'])) {
            $w[] = 'EXISTS (SELECT 1 FROM esc_turma_alunos x INNER JOIN esc_turmas y ON y.id = x.turma_id WHERE x.rm = u.rm AND y.ano = d.ano AND y.id = :turma)';
            $p['turma'] = (int) $f['turma'];
        }
        $linhas = [];
        foreach (F::ler(
            "SELECT d.id, d.ano, d.inicio, d.responsavel_id, d.motivo, d.referencia, u.id AS aluno_id, u.rm, u.nome, u.nome_social, r.nome AS pessoa, q.nome AS liberou,
                    (SELECT CONCAT(m.codnome, ' ', t.nome) FROM esc_turma_alunos a INNER JOIN esc_turmas t ON t.id = a.turma_id INNER JOIN rm_cursos_turma_series m ON m.id = t.modulo_id
                      WHERE a.rm = u.rm AND t.ano = d.ano ORDER BY a.saida IS NULL DESC, a.id DESC LIMIT 1) AS turma
               FROM esc_responsavel_designacoes d INNER JOIN col_usuarios u ON u.id = d.aluno_id INNER JOIN esc_responsaveis r ON r.id = d.responsavel_id
               LEFT JOIN adms_usuarios q ON q.id = d.criado_por
              WHERE " . implode(' AND ', $w) . ' ORDER BY d.ano DESC, u.nome, r.nome',
            $p
        ) as $r) {
            $linhas[] = [
                'aluno' => self::cel((string) ($r['nome_social'] ?: $r['nome']), ['url' => URLADM . 'responsaveis/aluno/' . (int) $r['aluno_id'] . '?ano=' . (int) $r['ano']]),
                'ra' => (string) $r['rm'], 'turma' => (string) ($r['turma'] ?? ''), 'ano' => (string) $r['ano'],
                'pessoa' => self::cel((string) $r['pessoa'], ['url' => URLADM . 'responsaveis/ver/' . (int) $r['responsavel_id']]),
                'desde' => self::cel(date('d/m/Y', strtotime((string) $r['inicio'])), ['o' => (string) $r['inicio']]),
                'motivo' => (string) $r['motivo'], 'referencia' => (string) ($r['referencia'] ?? ''), 'liberou' => (string) ($r['liberou'] ?? ''),
            ];
        }
        $cols = [self::col('aluno', 'Aluno'), self::col('ra', 'RA'), self::col('turma', 'Turma'), self::col('ano', 'Ano', ['num' => true]), self::col('pessoa', 'Pessoa'),
            self::col('desde', 'Desde'), self::col('motivo', 'Motivo'), self::col('referencia', 'Referência'), self::col('liberou', 'Liberado por')];
        $det = ['todas' => ['titulo' => 'Acessos adicionais em vigor', 'colunas' => $cols, 'linhas' => $linhas]];
        $ret = ['cartoes' => [['rotulo' => 'Acessos adicionais em vigor', 'valor' => self::contar($det, 'todas'), 'detalhe' => 'todas']], 'detalhes' => $det,
            'tabela' => ['colunas' => $cols, 'linhas' => $linhas, 'ordem' => [[0, 'asc']]]];
        if (\App\adms\Models\escola\AdmsEscolaConfig::valor('responsaveis.adicional') === '0') {
            $ret['avisos'] = ['A regra da escola está desligada (Regras da escola): estas liberações não dão acesso ao portal enquanto ela estiver assim.'];
        }
        return $ret;
    }
}
