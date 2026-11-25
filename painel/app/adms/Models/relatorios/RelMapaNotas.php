<?php

namespace App\adms\Models\relatorios;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\notas\AdmsCalculoNotas as Calc;
use App\adms\Models\relatorios\AdmsRelatorioFonte as F;
use Core\MotorMedia as Motor;

/** R-DES-01 Mapa de notas da oferta (docs/RELATORIOS.md, C). Nada recalculado: tudo da fachada e do gravado. */
class RelMapaNotas extends AdmsRelatorioBase
{
    public const CODIGO = 'R-DES-01';
    public const NOME = 'Mapa de notas da oferta';
    public const CATEGORIA = 'C';
    public const PERGUNTA = 'Como cada aluno está nesta disciplina?';
    public const SLUG = 'mapa-notas';
    public const QUEM = 'administrador, secretaria, coordenação, professor (as suas ofertas)';

    public function filtros(): array
    {
        return ['oferta' => ['obrigatorio' => true] + AdmsRelatorios::filtroPadrao('oferta')];
    }

    public function ficha(): array
    {
        return [
            'Uma coluna por atividade ativa da oferta (agrupadas pelo componente da regra), com a nota como lançada.',
            'Nota do componente: a da memória de cálculo do motor (só quando a regra tem mais de um componente).',
            'Média: oferta encerrada = a GRAVADA no encerramento; aberta = a média PARCIAL (só das notas lançadas), a mesma da tela de médias e do boletim.',
            'Se encerrasse hoje (provisório, só oferta aberta): o motor no modo do encerramento (AdmsCalculoNotas::oferta com final), com o resultado ou "Pendente — motivo".',
            'Frequência = 1 − (faltas − compensadas) ÷ encontros, a do motor. FS/FI: encerrada, com o mínimo gravado; aberta, com o mínimo efetivo de hoje, marcado "(prov.)".',
            'Em vermelho: nota ou média abaixo da nota mínima da regra da oferta.',
        ];
    }

    public function dados(array $f): array
    {
        $oid = (int) $f['oferta'];
        $calc = F::parcial($oid);
        if (!$calc) {
            return ['avisos' => ['Oferta não encontrada.']];
        }
        $o = $calc['oferta'];
        $encerrada = !empty($o['encerrada_em']);
        $prov = $encerrada ? null : F::provisorio($oid);
        $min = Motor::centesimosDe((string) $calc['regras']['mediaMinima']);
        $comps = $calc['regraEfetiva']['regra']['componentes'] ?? [];
        $nomesComp = array_column($comps, 'nome', 'codigo');
        $varios = count($comps) > 1;
        $ativs = $calc['atividades'];
        if ($varios) {
            $ordem = array_flip(array_keys($nomesComp));
            usort($ativs, fn($a, $b) => ($ordem[$a['componente_codigo'] ?: array_key_first($nomesComp)] ?? 99) <=> ($ordem[$b['componente_codigo'] ?: array_key_first($nomesComp)] ?? 99));
        }
        $colunas = [self::col('ra', 'RA'), self::col('nome', 'Nome')];
        foreach ($ativs as $a) {
            $c = $a['componente_codigo'] ?: array_key_first($nomesComp);
            $colunas[] = self::col('a' . (int) $a['id'], ($varios ? ($nomesComp[$c] ?? $c) . ': ' : '') . $a['nomeAtividade'], ['num' => true]);
        }
        if ($varios) {
            foreach ($nomesComp as $cod => $nome) {
                $colunas[] = self::col('c_' . preg_replace('/[^a-z0-9_]/', '', strtolower((string) $cod)), 'Nota ' . $nome, ['num' => true]);
            }
        }
        /* Onda 8, correção, acréscimo A (D-79): na escola, o mapa fala a língua da escola — a nota de cada etapa (com a
           recuperação, quando houve), a média anual e a situação; sem a "média se encerrasse hoje", que é do ensino
           superior. As etapas são as do ano da oferta, na ordem. */
        $escola = (bool) Calc::turmaDaOferta($oid);
        $etapasAno = [];
        if ($escola) {
            foreach ($calc['alunos'] as $a) {
                foreach ($a['etapas'] ?? [] as $e) {
                    $etapasAno[(int) $e['id']] = ['nome' => (string) $e['nome'], 'ordem' => (int) $e['ordem']];
                }
            }
            uasort($etapasAno, fn($x, $y) => $x['ordem'] <=> $y['ordem']);
            foreach ($etapasAno as $eid => $e) {
                $colunas[] = self::col('e' . $eid, 'Nota — ' . $e['nome'], ['num' => true]);
                $colunas[] = self::col('r' . $eid, 'Recuperação — ' . $e['nome'], ['num' => true]);
            }
        }
        $colunas[] = self::col('media', $escola ? ($encerrada ? 'Média anual' : 'Média anual (parcial)') : ($encerrada ? 'Média (gravada)' : 'Média parcial'), ['num' => true]);
        if (!$encerrada && !$escola) {
            $colunas[] = self::col('mediaProv', 'Média se encerrasse hoje (prov.)', ['num' => true]);
        }
        $colunas[] = self::col('frequencia', 'Frequência (%)', ['num' => true]);
        $colunas[] = self::col('fsfi', 'FS/FI');
        $colunas[] = self::col('resultado', $escola ? 'Situação' : ($encerrada ? 'Resultado' : 'Resultado provisório'));

        $linhas = [];
        $det = ['abaixo' => ['titulo' => 'Média abaixo da mínima', 'colunas' => $colunas, 'linhas' => []], 'pendentes' => ['titulo' => 'Pendentes', 'colunas' => $colunas, 'linhas' => []]];
        $alunos = F::alunosPorRm(array_column($calc['alunos'], 'rm'));
        $vermelho = fn(?int $c) => $c !== null && $c < $min ? 'text-danger fw-bold' : '';
        foreach ($calc['alunos'] as $a) {
            $rm = (string) $a['rm'];
            $u = $alunos[$rm] ?? null;
            $l = ['ra' => $rm, 'nome' => $u ? self::nomeAluno($u) : (string) $a['nome']];
            foreach ($ativs as $at) {
                $n = $a['notas'][(int) $at['id']] ?? null;
                $l['a' . (int) $at['id']] = self::cel($n === null ? '—' : Calc::formatar(number_format($n / 100, 2, '.', ''), 2), ['o' => $n ?? -1, 'classe' => $vermelho($n)]);
            }
            if ($varios) {
                $mem = array_column($a['memoria']['componentes'] ?? [], null, 'codigo');
                foreach ($nomesComp as $cod => $nome) {
                    $v = $mem[$cod]['valor'] ?? null;
                    $l['c_' . preg_replace('/[^a-z0-9_]/', '', strtolower((string) $cod))] = $v === null ? '—' : (string) $v;
                }
            }
            if ($escola) {
                $porEtapa = array_column($a['etapas'] ?? [], null, 'id');
                foreach ($etapasAno as $eid => $e) {
                    $x = $porEtapa[$eid] ?? [];
                    $nc = ($x['nota'] ?? null) !== null ? Calc::centesimos((string) $x['nota']) : null;
                    $l['e' . $eid] = self::cel(\Core\MediaEscola::v(isset($x['nota']) ? (string) $x['nota'] : null), ['o' => $nc ?? -1, 'classe' => $vermelho($nc)]);
                    $l['r' . $eid] = !empty($x['emRecuperacao']) ? \Core\MediaEscola::v(isset($x['recuperacao']) ? (string) $x['recuperacao'] : null) : '';
                }
            }
            $mc = $a['media'] !== null ? Calc::centesimos((string) $a['media']) : null;
            $l['media'] = self::cel(Calc::formatar($a['media'], (int) $a['casas']), ['o' => $mc ?? -1, 'classe' => $vermelho($mc)]);
            $res = $a;
            if ($prov && !$escola) {
                $p = F::alunoDe($prov, $rm) ?? [];
                $pc = ($p['media'] ?? null) !== null ? Calc::centesimos((string) $p['media']) : null;
                $l['mediaProv'] = self::cel(Calc::formatar($p['media'] ?? null, (int) ($p['casas'] ?? $a['casas'])), ['o' => $pc ?? -1, 'classe' => $vermelho($pc)]);
                $res = $p + $a;
            }
            $fs = F::fsfi($a, $calc);
            $l['frequencia'] = $a['frequencia'] === null ? 'não informada' : Calc::formatar((string) $a['frequencia'], 2);
            $l['fsfi'] = $fs['sigla'] . ($fs['provisorio'] && $fs['sigla'] !== '' ? ' (prov.)' : '');
            $pend = (array) ($res['motivosPendencia'] ?? []);
            $l['resultado'] = Calc::rotuloResultado(isset($res['resultado']) ? (int) $res['resultado'] : null, $res['motivo'] ?? null, $pend);
            if ($pend) {
                $l['_classe'] = 'table-warning';
                $det['pendentes']['linhas'][] = $l;
            }
            $linhas[] = $l;
            $cmp = $prov ? (($p['media'] ?? null) !== null ? Calc::centesimos((string) $p['media']) : null) : $mc;
            if ($cmp !== null && $cmp < $min && !$pend) {
                $det['abaixo']['linhas'][] = $l;
            }
        }
        $avisos = [];
        if (!$encerrada) {
            $avisos[] = 'Oferta aberta: a média parcial é a da tela de médias; a coluna "se encerrasse hoje" e o resultado são PROVISÓRIOS (o encerramento é que grava).';
        }
        return [
            'avisos' => $avisos, 'detalhes' => $det,
            'cartoes' => [
                ['rotulo' => 'Alunos', 'valor' => count($linhas)],
                ['rotulo' => 'Atividades', 'valor' => count($ativs)],
                ['rotulo' => 'Nota mínima', 'valor' => Calc::formatar((string) $calc['regras']['mediaMinima'], (int) $calc['regras']['casas']), 'nota' => $encerrada ? 'a da regra da oferta' : 'a da regra efetiva de hoje'],
                ['rotulo' => $encerrada ? 'Abaixo da mínima' : 'Abaixo da mínima (prov.)', 'valor' => self::contar($det, 'abaixo'), 'detalhe' => 'abaixo', 'cor' => self::contar($det, 'abaixo') ? 'bg-danger' : 'bg-light'],
                ['rotulo' => 'Pendentes (prov.)', 'valor' => self::contar($det, 'pendentes'), 'detalhe' => 'pendentes', 'cor' => self::contar($det, 'pendentes') ? 'bg-warning' : 'bg-light'],
            ],
            'tabela' => ['titulo' => F::rotuloOferta($o) . ($encerrada ? ' (encerrada)' : ' (aberta)'), 'colunas' => $colunas, 'linhas' => $linhas, 'linhasPorPagina' => 100],
        ];
    }
}
