<?php

namespace App\adms\Models\relatorios;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\notas\AdmsCalculoNotas as Calc;
use App\adms\Models\relatorios\AdmsRelatorioFonte as F;

/** R-FRE-01 Frequência da oferta (docs/RELATORIOS.md, D). O professor só vê as ofertas dele. */
class RelFrequenciaOferta extends AdmsRelatorioBase
{
    public const CODIGO = 'R-FRE-01';
    public const NOME = 'Frequência da oferta';
    public const CATEGORIA = 'D';
    public const PERGUNTA = 'Quem está faltando nesta disciplina?';
    public const SLUG = 'frequencia-oferta';
    public const QUEM = 'administrador, secretaria, coordenação, professor (as suas ofertas)';

    public function filtros(): array
    {
        return ['oferta' => ['obrigatorio' => true] + AdmsRelatorios::filtroPadrao('oferta')];
    }

    public function ficha(): array
    {
        return [
            'Frequência pela CHAMADA: uma coluna por encontro (P = presente, F = falta), na ordem da data; pelo LANÇAMENTO: as faltas lançadas.',
            'Faltas, compensadas, encontros e frequência: os do cálculo (AdmsCalculoNotas): encerrada = os gravados; aberta = os de hoje.',
            'Frequência = 1 − (faltas − compensadas) ÷ encontros, com 2 casas (a do motor). Sem total de encontros: "não informada".',
            'FS/FI: encerrada, com o mínimo gravado; aberta, com o mínimo efetivo de hoje, marcado "(prov.)".',
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
        $chamada = $o['frequencia_fonte'] === 'chamada';
        $encontros = $chamada ? F::ler("SELECT id, data, titulo FROM rm_oferta_encontros WHERE rm_prof_mat_id = :o ORDER BY data, hora_inicio, id", ['o' => $oid]) : [];
        $pres = [];
        if ($encontros) {
            foreach (F::ler("SELECT p.encontro_id, p.rm, p.presente FROM rm_oferta_presencas p INNER JOIN rm_oferta_encontros e ON e.id = p.encontro_id WHERE e.rm_prof_mat_id = :o", ['o' => $oid]) as $p) {
                $pres[(string) $p['rm']][(int) $p['encontro_id']] = (int) $p['presente'];
            }
        }
        $colunas = [self::col('ra', 'RA'), self::col('nome', 'Nome')];
        foreach ($encontros as $e) {
            $colunas[] = self::col('e' . (int) $e['id'], date('d/m', strtotime($e['data'])), ['dica' => (string) $e['titulo'], 'classe' => 'text-center']);
        }
        $colunas = array_merge($colunas, [self::col('faltas', $chamada ? 'Faltas' : 'Faltas lançadas', ['num' => true]), self::col('compensadas', 'Compensadas', ['num' => true]),
            self::col('encontros', 'Encontros', ['num' => true]), self::col('frequencia', 'Frequência (%)', ['num' => true]), self::col('fsfi', 'FS/FI')]);
        $alunos = F::alunosPorRm(array_column($calc['alunos'], 'rm'));
        $det = ['fi' => ['titulo' => 'Frequência abaixo do mínimo', 'colunas' => $colunas, 'linhas' => []]];
        $linhas = [];
        $minima = (string) $calc['regras']['frequenciaMinima'];
        $somaF = 0;
        foreach ($calc['alunos'] as $a) {
            $rm = (string) $a['rm'];
            $u = $alunos[$rm] ?? null;
            $l = ['ra' => $rm, 'nome' => $u ? self::nomeAluno($u) : (string) $a['nome']];
            foreach ($encontros as $e) {
                $p = $pres[$rm][(int) $e['id']] ?? null;
                $l['e' . (int) $e['id']] = self::cel($p === null ? '' : ($p ? 'P' : 'F'), ['classe' => $p === 0 ? 'text-danger text-center' : 'text-center']);
            }
            $fs = F::fsfi($a, $calc);
            $l += [
                'faltas' => (string) (int) $a['faltas'], 'compensadas' => (string) (int) $a['compensadas'], 'encontros' => $a['encontros'] !== null ? (string) (int) $a['encontros'] : '—',
                'frequencia' => $a['frequencia'] === null ? 'não informada' : Calc::formatar((string) $a['frequencia'], 2),
                'fsfi' => $fs['sigla'] . ($fs['provisorio'] && $fs['sigla'] !== '' ? ' (prov.)' : ''),
            ];
            $somaF += (int) $a['faltas'];
            $abaixo = $a['frequencia'] !== null && (int) $a['encontros'] > 0 && !\Core\MotorMedia::frequenciaSuficiente((string) $a['frequencia'], !empty($a['gravado']) ? (string) $a['frequenciaMinima'] : $minima);
            if ($abaixo) {
                $l['_classe'] = 'table-danger';
                $det['fi']['linhas'][] = $l;
            }
            $linhas[] = $l;
        }
        $avisos = [];
        if (!$chamada) {
            $avisos[] = 'Esta oferta usa o LANÇAMENTO de faltas (não a chamada): não há encontro por encontro.';
        } elseif (!$encontros) {
            $avisos[] = 'Oferta com a frequência pela chamada e nenhum encontro registrado.';
        }
        if ((int) ($o['encontros'] ?? 0) <= 0) {
            $avisos[] = 'A oferta não tem o total de encontros: a frequência não pode ser calculada.';
        }
        return [
            'avisos' => $avisos, 'detalhes' => $det,
            'cartoes' => [
                ['rotulo' => 'Alunos', 'valor' => count($linhas)],
                ['rotulo' => 'Encontros previstos', 'valor' => $o['encontros'] !== null ? (string) (int) $o['encontros'] : '—', 'nota' => $chamada ? count($encontros) . ' registrado(s) na chamada' : 'lançamento de faltas'],
                ['rotulo' => 'Frequência mínima', 'valor' => Calc::formatar($minima, 2) . '%', 'nota' => empty($o['encerrada_em']) ? 'efetiva de hoje' : 'a de hoje (cada linha usa a gravada)'],
                ['rotulo' => empty($o['encerrada_em']) ? 'Abaixo do mínimo (prov.)' : 'Abaixo do mínimo (FI)', 'valor' => self::contar($det, 'fi'), 'detalhe' => 'fi', 'cor' => self::contar($det, 'fi') ? 'bg-danger' : 'bg-light'],
            ],
            'tabela' => ['titulo' => F::rotuloOferta($o) . (empty($o['encerrada_em']) ? ' (aberta)' : ' (encerrada)'), 'colunas' => $colunas, 'linhas' => $linhas, 'linhasPorPagina' => 100,
                'rodape' => ['ra' => 'Total', 'faltas' => (string) $somaF]],
        ];
    }
}
