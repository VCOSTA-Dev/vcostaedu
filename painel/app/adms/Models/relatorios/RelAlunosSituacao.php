<?php

namespace App\adms\Models\relatorios;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\relatorios\AdmsRelatorioFonte as F;

/** R-SEC-01 Alunos por curso e situação (docs/RELATORIOS.md, B). */
class RelAlunosSituacao extends AdmsRelatorioBase
{
    public const CODIGO = 'R-SEC-01';
    public const NOME = 'Alunos por curso e situação';
    public const CATEGORIA = 'B';
    public const PERGUNTA = 'Quem está matriculado e em que situação?';
    public const SLUG = 'alunos-situacao';
    public const QUEM = 'administrador, diretoria, secretaria, coordenação';

    public function filtros(): array
    {
        return ['curso', 'grade', 'situacao', 'polo', 'coorte', 'de' => ['rotulo' => 'Ingresso de', 'tipo' => 'data'], 'ate' => ['rotulo' => 'até', 'tipo' => 'data']];
    }

    public function ficha(): array
    {
        return [
            'Uma linha por matrícula (rm_matriculas_curso): o aluno com duas matrículas aparece duas vezes, uma por curso.',
            'Polo: o da matrícula; vazio, o do curso. Coorte de ingresso: o semestre da data de ingresso (janeiro a junho = /1; julho a dezembro = /2).',
            'O período filtra a data de INGRESSO. Os cartões contam as matrículas de cada situação; a soma dos cartões é o total da lista.',
            'CPF mascarado, salvo a permissão "ver CPF completo em relatórios". O nome abre o desempenho consolidado do aluno (R-DES-05).',
        ];
    }

    public function dados(array $f): array
    {
        $mats = F::matriculas($f);
        $det = [];
        $cartoes = [];
        $colunas = [self::col('ra', 'RA'), self::col('nome', 'Nome'), self::col('cpf', 'CPF'), self::col('curso', 'Curso'), self::col('grade', 'Grade'),
            self::col('polo', 'Polo'), self::col('situacao', 'Situação'), self::col('ingresso', 'Ingresso'), self::col('previsao', 'Previsão de conclusão'),
            self::col('conclusao', 'Conclusão')];
        /* Onda 8, correção, segunda leva, 2.5 (D-91): na escola, o segmento; sem a grade e o polo (do ensino superior) */
        if (\App\adms\Models\helper\AdmsTipoInstituicao::porTurma()) { // D-106: o curso livre também sem a grade e o polo (e com "Curso")
            $colunas = array_values(array_filter(array_map(static fn($c) => $c['chave'] === 'curso' && \App\adms\Models\helper\AdmsTipoInstituicao::ehEscola() ? ['titulo' => 'Segmento'] + $c : $c, $colunas),
                static fn($c) => !in_array($c['chave'], ['grade', 'polo'], true)));
        }
        $linhas = [];
        $porSit = [];
        foreach ($mats as $mc) {
            $l = [
                'ra' => (string) $mc['rm'],
                'nome' => self::cel((string) $mc['nome'], ['url' => URLADM . 'relatorios/desempenho-aluno?matricula=' . (int) $mc['id']]),
                'cpf' => AdmsRelatorios::cpf($mc['cpf']), 'curso' => (string) $mc['curso'], 'grade' => (string) $mc['grade'], 'polo' => (string) $mc['polo'],
                'situacao' => (string) $mc['situacao'],
                'ingresso' => self::cel(F::data($mc['data_ingresso']), ['o' => (string) $mc['data_ingresso']]),
                'previsao' => self::cel(F::data($mc['previsao_conclusao']), ['o' => (string) $mc['previsao_conclusao']]),
                'conclusao' => self::cel(F::data($mc['data_conclusao']), ['o' => (string) $mc['data_conclusao']]),
            ];
            $linhas[] = $l;
            $porSit[(int) $mc['situacao_id']][] = $l;
        }
        $rod = [];
        foreach (F::ler("SELECT id, nome FROM rm_matriculas_situacao ORDER BY id") as $s) {
            $k = 'situacao_' . (int) $s['id'];
            $det[$k] = ['titulo' => 'Matrículas ' . $s['nome'], 'colunas' => $colunas, 'linhas' => $porSit[(int) $s['id']] ?? []];
            $cartoes[] = ['rotulo' => $s['nome'], 'valor' => self::contar($det, $k), 'detalhe' => $k];
            $rod[] = $s['nome'] . ' ' . self::contar($det, $k);
        }
        return [
            'cartoes' => $cartoes, 'detalhes' => $det,
            'tabela' => ['colunas' => $colunas, 'linhas' => $linhas, 'ordem' => [[1, 'asc']],
                'rodape' => ['ra' => 'Total', 'nome' => count($linhas) . ' matrícula(s)', 'situacao' => implode(' · ', $rod)]],
        ];
    }
}
