<?php

namespace App\adms\Models\relatorios;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\relatorios\AdmsRelatorioFonte as F;

/** R-SEC-05 Alunos sem responsável financeiro ou sem titular (docs/RELATORIOS.md, B; Onda 7 — só na escola). */
class RelResponsaveisFaltando extends AdmsRelatorioBase
{
    public const CODIGO = 'R-SEC-05';
    public const NOME = 'Alunos sem responsável financeiro ou sem titular';
    public const CATEGORIA = 'B';
    public const PERGUNTA = 'Quais alunos, em cada turma, ainda não têm o responsável financeiro ou o acadêmico titular no ano?';
    public const SLUG = 'responsaveis-faltando';
    public const QUEM = 'administrador, diretoria, secretaria';

    public function filtros(): array
    {
        $anos = [];
        foreach (F::ler('SELECT ano FROM esc_anos ORDER BY ano DESC') as $r) {
            $anos[(int) $r['ano']] = (string) $r['ano'];
        }
        return ['ano' => ['rotulo' => 'Ano letivo', 'tipo' => 'select', 'opcoes' => $anos, 'padrao' => (string) (array_key_first($anos) ?? '')], 'turma'];
    }

    public function ficha(): array
    {
        return [
            'Os alunos que estão na turma (sem a saída) no ano escolhido, turma a turma.',
            'Para cada um, o responsável financeiro e o acadêmico titular em vigor hoje naquele ano (Responsáveis, na tela do aluno). O acesso acadêmico adicional não conta.',
            'O aluno aparece se falta um dos dois; a coluna diz qual. O nome abre a tela dos responsáveis do aluno, no ano.',
        ];
    }

    public function dados(array $f): array
    {
        $ano = (int) ($f['ano'] ?? 0);
        $w = ['t.ano = :ano', 'a.saida IS NULL'];
        $p = ['ano' => $ano];
        if (!empty($f['turma'])) {
            $w[] = 't.id = :turma';
            $p['turma'] = (int) $f['turma'];
        }
        $vig = "SELECT COUNT(*) FROM esc_responsavel_designacoes d WHERE d.aluno_id = u.id AND d.ano = t.ano AND d.papel = '%s' AND d.inicio <= CURDATE() AND (d.fim IS NULL OR d.fim > CURDATE())";
        $linhas = ['financeiro' => [], 'titular' => []];
        $todas = [];
        foreach (F::ler(
            "SELECT u.id, u.rm, u.nome, u.nome_social, CONCAT(m.codnome, ' ', t.nome) AS turma, m.ordem, t.nome AS tnome,
                    (" . sprintf($vig, 'financeiro') . ") AS fin, (" . sprintf($vig, 'titular') . ") AS tit
               FROM esc_turma_alunos a INNER JOIN esc_turmas t ON t.id = a.turma_id INNER JOIN rm_cursos_turma_series m ON m.id = t.modulo_id
               INNER JOIN col_usuarios u ON u.rm = a.rm
              WHERE " . implode(' AND ', $w) . ' ORDER BY m.ordem, t.nome, u.nome',
            $p
        ) as $r) {
            $faltam = array_values(array_filter([(int) $r['fin'] === 0 ? 'financeiro' : null, (int) $r['tit'] === 0 ? 'titular' : null]));
            if (!$faltam) {
                continue;
            }
            $l = [
                'turma' => (string) $r['turma'], 'ra' => (string) $r['rm'],
                'nome' => self::cel((string) ($r['nome_social'] ?: $r['nome']), ['url' => URLADM . 'responsaveis/aluno/' . (int) $r['id'] . '?ano=' . $ano]),
                'falta' => count($faltam) === 2 ? 'o financeiro e o titular' : ($faltam[0] === 'financeiro' ? 'o responsável financeiro' : 'o acadêmico titular'),
            ];
            foreach ($faltam as $x) {
                $linhas[$x][] = $l;
            }
            $todas[] = $l;
        }
        $cols = [self::col('turma', 'Turma'), self::col('ra', 'RA'), self::col('nome', 'Aluno'), self::col('falta', 'Falta')];
        $det = [
            'todas' => ['titulo' => 'Alunos com responsável faltando', 'colunas' => $cols, 'linhas' => $todas],
            'financeiro' => ['titulo' => 'Sem o responsável financeiro', 'colunas' => $cols, 'linhas' => $linhas['financeiro']],
            'titular' => ['titulo' => 'Sem o acadêmico titular', 'colunas' => $cols, 'linhas' => $linhas['titular']],
        ];
        return ['cartoes' => [
            ['rotulo' => 'Alunos com responsável faltando', 'valor' => self::contar($det, 'todas'), 'detalhe' => 'todas'],
            ['rotulo' => 'Sem o responsável financeiro', 'valor' => self::contar($det, 'financeiro'), 'detalhe' => 'financeiro'],
            ['rotulo' => 'Sem o acadêmico titular', 'valor' => self::contar($det, 'titular'), 'detalhe' => 'titular'],
        ], 'detalhes' => $det, 'tabela' => ['colunas' => $cols, 'linhas' => $todas, 'ordem' => [[0, 'asc']]]];
    }
}
