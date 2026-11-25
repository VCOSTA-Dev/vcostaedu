<?php

namespace App\adms\Models\relatorios;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\relatorios\AdmsRelatorioFonte as F;

/** R-EVA-03 Aptos a concluir / certificar (docs/RELATORIOS.md, E). A MESMA regra que libera "Concluído". */
class RelAptosConcluir extends AdmsRelatorioBase
{
    public const CODIGO = 'R-EVA-03';
    public const NOME = 'Aptos a concluir';
    public const CATEGORIA = 'E';
    public const PERGUNTA = 'Quem já pode receber o histórico final?';
    public const SLUG = 'aptos-concluir';
    public const QUEM = 'administrador, secretaria';

    public function filtros(): array
    {
        return ['curso', 'polo'];
    }

    public function ficha(): array
    {
        return [
            'Matrículas em Cursando em que AdmsVidaAcademica::pendenciasConclusao não acusa nada: todas as disciplinas da grade aprovadas (na tentativa vigente) e o TCC aprovado quando o curso exige. É a mesma regra que libera a situação "Concluído".',
            'O histórico FINAL só é emitido com a matrícula em "Concluído" (com a data de conclusão) e sem nenhum alerta: o atalho abre a tela de emissão, que mostra os alertas.',
            'A lista "com pendência" mostra o que falta às demais matrículas em Cursando.',
        ];
    }

    public function dados(array $f): array
    {
        $colunas = [self::col('ra', 'RA'), self::col('nome', 'Nome'), self::col('cpf', 'CPF', ['oculta' => true]), self::col('curso', 'Curso'), self::col('ch', 'CH cumprida', ['num' => true]),
            self::col('tcc', 'TCC'), self::col('acao', '', ['classe' => 'nao-exportar nao-ordenar'])];
        $colPend = self::colunasAluno([self::col('pendencias', 'O que falta')]);
        $det = ['aptos' => ['titulo' => 'Aptos a concluir', 'colunas' => $colunas, 'linhas' => []], 'pendentes' => ['titulo' => 'Cursando com pendência de conclusão', 'colunas' => $colPend, 'linhas' => []]];
        $mats = F::matriculas(['situacao' => 1] + $f);
        /* 1.17.2 (D-464): a regra oficial EM LOTE — todas as matrículas de uma vez, sem a vida acadêmica aluno a aluno */
        $lote = \App\adms\Models\historico\AdmsVidaAcademica::pendenciasConclusaoLote(array_column($mats, 'id'));
        foreach ($mats as $mc) {
            $r = $lote[(int) $mc['id']] ?? ['pendencias' => ['Matrícula não encontrada.']];
            if ($r['pendencias']) {
                $det['pendentes']['linhas'][] = self::linhaAluno($mc, ['pendencias' => implode('; ', $r['pendencias'])]);
                continue;
            }
            $tcc = !$r['exigeTcc'] ? 'não exigido' : (($r['tcc'] ?? '') . (!empty($r['notaTcc']) ? ' (' . $r['notaTcc'] . ')' : ''));
            $det['aptos']['linhas'][] = self::linhaAluno($mc, [
                'ch' => $r['chIntegralizada'] . ' / ' . $r['chExigida'] . ' h', 'tcc' => $tcc,
                'acao' => self::cel('Emitir histórico final', ['url' => URLADM . 'historico-emissoes/emitir/' . (int) $mc['id'] . '?tipo=FINAL']),
            ]);
        }
        return [
            'avisos' => self::contar($det, 'aptos') ? ['Para emitir o FINAL, mude antes a situação da matrícula para "Concluído", com a data de conclusão (na ficha do aluno).'] : [],
            'cartoes' => [
                ['rotulo' => 'Aptos a concluir', 'valor' => self::contar($det, 'aptos'), 'detalhe' => 'aptos', 'cor' => 'bg-success'],
                ['rotulo' => 'Cursando com pendência', 'valor' => self::contar($det, 'pendentes'), 'detalhe' => 'pendentes'],
            ],
            'detalhes' => $det,
            'tabela' => ['colunas' => $colunas, 'linhas' => $det['aptos']['linhas'], 'rodape' => ['ra' => 'Total', 'nome' => (string) self::contar($det, 'aptos')]],
        ];
    }
}
