<?php

namespace App\adms\Models\relatorios;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\relatorios\AdmsRelatorioFonte as F;

/** R-SEC-02 Lista de turma, o diário da oferta (docs/RELATORIOS.md, B). O professor só vê as ofertas dele. */
class RelListaTurma extends AdmsRelatorioBase
{
    public const CODIGO = 'R-SEC-02';
    public const NOME = 'Lista de turma';
    public const CATEGORIA = 'B';
    public const PERGUNTA = 'Quem está enturmado nesta oferta?';
    public const SLUG = 'lista-turma';
    public const QUEM = 'administrador, secretaria, coordenação, professor (as suas ofertas)';

    public function filtros(): array
    {
        return ['oferta' => ['obrigatorio' => true] + AdmsRelatorios::filtroPadrao('oferta')];
    }

    public function ficha(): array
    {
        return [
            'A enturmação da oferta (portal_disciplinas_alunos), na ordem da lista; o total é o de enturmados ATIVOS.',
            'Situação da matrícula: a da matrícula do aluno no curso da oferta.',
            'A coluna Assinatura fica em branco para o PDF e a impressão (o diário de papel).',
        ];
    }

    public function dados(array $f): array
    {
        $o = F::ofertas(['oferta' => $f['oferta']])[0] ?? null;
        if (!$o) {
            return ['avisos' => ['Oferta não encontrada.']];
        }
        $linhas = [];
        $det = ['ativos' => ['titulo' => 'Enturmados ativos', 'colunas' => [], 'linhas' => []]];
        $colunas = [self::col('ordem', 'Nº', ['num' => true]), self::col('ra', 'RA'), self::col('nome', 'Nome'), self::col('matricula', 'Situação da matrícula'),
            self::col('enturmacao', 'Na oferta'), self::col('assinatura', 'Assinatura', ['classe' => 'nao-ordenar'])];
        $det['ativos']['colunas'] = $colunas;
        $n = 0;
        foreach (F::ler(
            "SELECT e.rm, e.nome_aluno, e.ordem, e.situacao, u.id AS usuario_id, u.nome,
                    (SELECT s.nome FROM rm_matriculas_curso mc INNER JOIN rm_matriculas_situacao s ON s.id = mc.situacao_id
                      WHERE mc.usuario_id = u.id AND mc.curso_id = :curso ORDER BY mc.situacao_id = 1 DESC, mc.id DESC LIMIT 1) AS situacaoMatricula
               FROM portal_disciplinas_alunos e LEFT JOIN col_usuarios u ON u.rm = e.rm
              WHERE e.rm_prof_mat_id = :o ORDER BY e.situacao = 1 DESC, e.ordem, e.nome_aluno",
            ['curso' => (int) $o['curso_id'], 'o' => (int) $o['id']]
        ) as $e) {
            $ativo = (int) $e['situacao'] === 1;
            $l = [
                'ordem' => $ativo ? (string) ++$n : '',
                'ra' => (string) $e['rm'],
                'nome' => $e['usuario_id'] ? self::nomeAluno($e) : (string) $e['nome_aluno'],
                'matricula' => (string) ($e['situacaoMatricula'] ?? '—'),
                'enturmacao' => $ativo ? 'Ativo' : 'Inativo',
                'assinatura' => '',
                '_classe' => $ativo ? '' : 'text-muted',
            ];
            $linhas[] = $l;
            if ($ativo) {
                $det['ativos']['linhas'][] = $l;
            }
        }
        return [
            'cartoes' => [
                ['rotulo' => 'Enturmados ativos', 'valor' => self::contar($det, 'ativos'), 'detalhe' => 'ativos', 'cor' => 'bg-info'],
                ['rotulo' => 'Oferta', 'valor' => $o['codtur'] . ' ' . $o['ano'], 'nota' => $o['materia'] . ($o['encerrada_em'] ? ' (encerrada)' : ' (aberta)')],
            ],
            'detalhes' => $det,
            'tabela' => ['titulo' => F::rotuloOferta($o) . ' — ' . $o['curso'], 'colunas' => $colunas, 'linhas' => $linhas, 'linhasPorPagina' => 100,
                'rodape' => ['ra' => 'Ativos', 'nome' => (string) self::contar($det, 'ativos')]],
        ];
    }
}
