<?php

namespace App\adms\Models\relatorios;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\notas\AdmsCalculoNotas as Calc;
use App\adms\Models\relatorios\AdmsRelatorioFonte as F;

/** R-DES-05 Desempenho do aluno, a visão consolidada (docs/RELATORIOS.md, C). A MESMA fonte do histórico. */
class RelDesempenhoAluno extends AdmsRelatorioBase
{
    public const CODIGO = 'R-DES-05';
    public const NOME = 'Desempenho do aluno';
    public const CATEGORIA = 'C';
    public const PERGUNTA = 'Como este aluno está no curso todo?';
    public const SLUG = 'desempenho-aluno';
    public const QUEM = 'administrador, secretaria, coordenação';

    public function filtros(): array
    {
        $op = [];
        foreach (F::ler("SELECT mc.id, u.nome, u.rm, c.nome AS curso FROM rm_matriculas_curso mc INNER JOIN col_usuarios u ON u.id = mc.usuario_id INNER JOIN rm_cursos c ON c.id = mc.curso_id WHERE 1 = 1" . \App\adms\Models\helper\AdmsEscopo::sql('mc.curso_id') . " ORDER BY u.nome, c.nome") as $r) {
            $op[(int) $r['id']] = "{$r['nome']} ({$r['rm']}) — {$r['curso']}";
        }
        return ['matricula' => ['rotulo' => 'Matrícula', 'tipo' => 'select', 'opcoes' => $op, 'obrigatorio' => true]];
    }

    public function ficha(): array
    {
        return [
            'A mesma fonte do histórico: AdmsVidaAcademica::montar (a grade da matrícula, as tentativas e a vigente de cada disciplina).',
            'Situação: Aprovado / Reprovado (a vigente encerrada, resultado gravado), Em curso (a vigente aberta), Não cursada (sem enturmação).',
            'Média: encerrada = a gravada; em curso = a parcial (só das notas lançadas), a do boletim. FS/FI só com a oferta encerrada (mínimo gravado).',
            'CH cumprida = soma da CH das disciplinas aprovadas ÷ CH total da grade.',
            'Média final do aluno: a mesma do documento (AdmsVidaAcademica::mediaFinalTexto), só com as disciplinas encerradas, aritmética ou ponderada pela CH conforme a instituição.',
        ];
    }

    public function dados(array $f): array
    {
        $v = \App\adms\Models\historico\AdmsVidaAcademica::montar((int) $f['matricula']);
        if (!$v) {
            return ['avisos' => ['Matrícula não encontrada.']];
        }
        $pr = \App\adms\Models\historico\AdmsProgressoAluno::montar($v); // a mesma conta do "Meu progresso" do portal
        /* Onda 8, correção, acréscimo A (D-79, D-85): na escola, a série (no superior, o módulo); a disciplina nos dois */
        $escola = \App\adms\Models\helper\AdmsTipoInstituicao::ehEscola();
        $colunas = [self::col('modulo', $escola ? 'Série' : 'Módulo'), self::col('disciplina', 'Disciplina'), self::col('ch', 'CH', ['num' => true]), self::col('oferta', 'Oferta'),
            self::col('situacao', 'Situação'), self::col('media', 'Média', ['num' => true]), self::col('frequencia', 'Frequência (%)', ['num' => true]), self::col('fsfi', 'FS/FI')];
        $linhas = [];
        $det = [];
        $chaves = ['Aprovado' => 'sit_aprovado', 'Reprovado' => 'sit_reprovado', 'Em curso' => 'sit_emcurso', 'Não cursada' => 'sit_naocursada'];
        foreach ($chaves as $s => $k) {
            $det[$k] = ['titulo' => 'Disciplinas: ' . $s, 'colunas' => $colunas, 'linhas' => []];
        }
        foreach ($pr['linhas'] as $d) {
            $l = [
                'modulo' => self::cel($d['modulo'], ['o' => sprintf('%04d', $d['ordem'])]), 'disciplina' => $d['disciplina'], 'ch' => (string) $d['ch'],
                'oferta' => $d['oferta_id'] ? self::cel($d['turma'], ['url' => URLADM . 'medias/final/' . $d['oferta_id']]) : '',
                'situacao' => $d['situacao'], 'media' => $d['media'], 'frequencia' => $d['frequencia'], 'fsfi' => $d['fsfi'],
                '_classe' => $d['situacao'] === 'Reprovado' ? 'table-danger' : '',
            ];
            $linhas[] = $l;
            if (isset($chaves[$d['situacao']])) {
                $det[$chaves[$d['situacao']]]['linhas'][] = $l;
            }
        }
        $t = $pr['totais'];
        $mf = $t['mediaFinal'];
        $cartoes = [
            ['rotulo' => $v['aluno']['nome'], 'valor' => (string) $v['aluno']['rm'], 'nota' => $v['matricula']['curso'] . ' — ' . $v['matricula']['situacao']],
            ['rotulo' => 'CH cumprida', 'valor' => "{$t['chCumprida']} / {$t['chTotal']} h", 'nota' => F::pct($t['chCumprida'], $t['chTotal']), 'cor' => 'bg-info'],
            ['rotulo' => 'Média final do aluno', 'valor' => $mf !== '' ? $mf : '—', 'nota' => 'a do documento (só disciplinas encerradas)'],
        ];
        foreach (['Aprovado' => 'bg-success', 'Reprovado' => 'bg-danger', 'Em curso' => 'bg-light', 'Não cursada' => 'bg-light'] as $s => $cor) {
            $cartoes[] = ['rotulo' => $s, 'valor' => self::contar($det, $chaves[$s]), 'detalhe' => $chaves[$s], 'cor' => self::contar($det, $chaves[$s]) ? $cor : 'bg-light'];
        }
        if ($t['exigeTcc']) {
            $cartoes[] = ['rotulo' => 'TCC', 'valor' => $t['tcc'] ?? 'não cadastrado', 'nota' => $t['notaTcc'] ?? ''];
        }
        return [
            'cartoes' => $cartoes, 'detalhes' => $det,
            'tabela' => ['colunas' => $colunas, 'linhas' => $linhas, 'ordem' => [[0, 'asc']], 'linhasPorPagina' => 100,
                'rodape' => ['modulo' => 'Total', 'ch' => "{$t['chCumprida']} de {$t['chTotal']}", 'situacao' => "{$t['aprovadas']} de {$t['disciplinas']} aprovadas", 'media' => $mf]],
        ];
    }
}
