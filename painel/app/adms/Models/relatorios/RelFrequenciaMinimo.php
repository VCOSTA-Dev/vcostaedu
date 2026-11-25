<?php

namespace App\adms\Models\relatorios;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\notas\AdmsCalculoNotas as Calc;
use App\adms\Models\relatorios\AdmsRelatorioFonte as F;

/** R-FRE-02 Alunos abaixo ou perto do mínimo de frequência (docs/RELATORIOS.md, D). Só ofertas abertas. */
class RelFrequenciaMinimo extends AdmsRelatorioBase
{
    public const CODIGO = 'R-FRE-02';
    public const NOME = 'Abaixo ou perto do mínimo';
    public const CATEGORIA = 'D';
    public const PERGUNTA = 'Quem vai reprovar por falta se faltar de novo?';
    public const SLUG = 'frequencia-minimo';
    public const QUEM = 'administrador, secretaria, coordenação';

    public const SITUACOES = ['abaixo' => 'Abaixo do mínimo', 'limite' => 'No limite', 'perto' => 'Perto do mínimo', 'sem' => 'Sem alerta'];

    public function filtros(): array
    {
        return ['curso', 'oferta', 'margem' => ['rotulo' => 'Margem (faltas)', 'tipo' => 'numero', 'padrao' => (string) F::risco()['faltas_margem']],
            /* 1.17.4 (D-468): abre filtrado nos alertas; "Mostrar todos" inclui os sem alerta. Na URL, como os outros filtros. */
            'mostrar' => ['rotulo' => 'Mostrar', 'tipo' => 'select', 'opcoes' => ['alertas' => 'Só os alertas', 'todos' => 'Mostrar todos'], 'padrao' => 'alertas']];
    }

    public function ficha(): array
    {
        return [
            'Só ofertas ABERTAS com o total de encontros (E) ou, na chamada, com os encontros previstos. m = a frequência mínima efetiva (a do curso; sem ela, a da instituição); f − c = faltas − compensadas.',
            'Oferta por chamada COM encontros previstos (P): faltas que ainda pode ter = piso(P × (100 − m) ÷ 100) − (f − c). Ex.: 10 previstos, 75%, 1 falta: piso(10 × 0,25) = 2, menos 1 = 1.',
            'Sem previstos: faltas que ainda pode ter = piso(E × (100 − m) ÷ 100) − (f − c) sobre os encontros registrados (E): a maior quantidade de faltas efetivas com que a frequência, arredondada como no motor (2 casas, meio para cima), ainda fica ≥ m, menos as de hoje.',
            'Os previstos valem só aqui e no alerta de risco: a frequência do boletim, do encerramento e dos documentos continua sobre os encontros registrados.',
            'Classificação (a mesma do sinal de frequência do alerta de risco, D-466): abaixo = pode < 0; no limite = (f − c) ≥ 1 e pode = 0; perto = (f − c) ≥ 1 e 0 < pode ≤ margem; sem alerta = os demais (nenhuma falta efetiva, ou pode > margem). Todos os alunos aparecem, com as faltas que ainda podem ter. A margem padrão é a do alerta de risco (risco.faltas_margem).',
            'A tabela abre em "Só os alertas" (abaixo, no limite e perto); "Mostrar todos" inclui os "Sem alerta". Os 4 cartões contam sempre todos os alunos, e cada um abre a sua lista (D-468).',
            'Por que exigir ao menos 1 falta efetiva: em disciplina curta (ex.: 10 previstos, 75% = 2 faltas permitidas), o aluno sem nenhuma falta tem "pode ter mais 2" ≤ margem 2 e acusaria todos.',
            'Ordenado pelas faltas que ainda pode ter (quem está pior primeiro). Provisório: a oferta ainda está aberta.',
        ];
    }

    public function dados(array $f): array
    {
        $margem = (int) ($f['margem'] ?? F::risco()['faltas_margem']);
        $todos = ($f['mostrar'] ?? 'alertas') === 'todos';
        $ofertas = F::ofertas(['situacao_oferta' => 'aberta'] + array_intersect_key($f, ['curso' => 1, 'oferta' => 1, 'turma' => 1]));
        $colunas = [self::col('ra', 'RA'), self::col('nome', 'Nome'), self::col('oferta', 'Oferta'), self::col('encontros', 'Encontros previstos', ['num' => true]),
            self::col('faltas', 'Faltas', ['num' => true]), self::col('compensadas', 'Compensadas', ['num' => true]), self::col('frequencia', 'Frequência atual (%)', ['num' => true]),
            self::col('minima', 'Mínimo (%)', ['num' => true]), self::col('pode', 'Faltas que ainda pode ter', ['num' => true]), self::col('situacao', 'Situação')];
        $det = [];
        foreach (self::SITUACOES as $k => $t) {
            $det[$k] = ['titulo' => $t, 'colunas' => $colunas, 'linhas' => []];
        }
        $linhas = [];
        $semEnc = 0;
        $rms = [];
        foreach ($ofertas as $o) {
            $rms = array_merge($rms, array_column(F::parcial((int) $o['id'])['alunos'] ?? [], 'rm'));
        }
        $alunos = F::alunosPorRm($rms);
        foreach ($ofertas as $o) {
            $calc = F::parcial((int) $o['id']);
            $e = (int) ($o['encontros'] ?? 0);
            $prev = F::previstos()[(int) $o['id']] ?? null; // 1.17.2 (D-462)
            if ($e <= 0 && $prev === null) {
                $semEnc++;
                continue;
            }
            $m = (string) $calc['regras']['frequenciaMinima'];
            foreach ($calc['alunos'] ?? [] as $a) {
                $mg = F::margemOferta((int) $o['id'], $e, $m, (int) $a['faltas'], (int) $a['compensadas']);
                $pode = $mg['pode'];
                $sit = F::situacaoFalta($pode, $margem, max(0, (int) $a['faltas'] - (int) $a['compensadas'])) ?: 'sem'; // 1.17.3 (D-466): todos listados; a classificação é a do risco
                $u = $alunos[(string) $a['rm']] ?? null;
                $l = [
                    'ra' => (string) $a['rm'], 'nome' => $u ? self::nomeAluno($u) : (string) $a['nome'], 'oferta' => self::celOferta($o),
                    'encontros' => $mg['previstos'] ? "{$mg['base']} previstos ({$e} registrados)" : (string) $e, 'faltas' => (string) (int) $a['faltas'], 'compensadas' => (string) (int) $a['compensadas'],
                    'frequencia' => $a['frequencia'] === null ? '—' : Calc::formatar((string) $a['frequencia'], 2), 'minima' => Calc::formatar($m, 2),
                    'pode' => self::cel((string) $pode, ['o' => $pode, 'classe' => $pode < 0 ? 'text-danger fw-bold' : '']),
                    'situacao' => self::SITUACOES[$sit] . ($pode > 0 ? " — pode ter mais {$pode} falta" . ($pode > 1 ? 's' : '') : ''), '_classe' => $sit === 'abaixo' ? 'table-danger' : ($sit === 'limite' ? 'table-warning' : ($sit === 'sem' ? 'text-muted' : '')),
                ];
                if ($todos || $sit !== 'sem') {
                    $linhas[] = $l; // a tabela segue o filtro "Mostrar"
                }
                $det[$sit]['linhas'][] = $l; // os cartões e as listas: sempre os 4 grupos
            }
        }
        $cartoes = [];
        foreach (self::SITUACOES as $k => $t) {
            $cartoes[] = ['rotulo' => $t, 'valor' => self::contar($det, $k), 'detalhe' => $k, 'cor' => ['abaixo' => 'bg-danger', 'limite' => 'bg-warning', 'perto' => 'bg-info', 'sem' => 'bg-light'][$k],
                'nota' => ['perto' => "com falta, pode ter de 1 a {$margem} falta(s)", 'limite' => 'com falta, não pode faltar mais', 'abaixo' => 'já passou do mínimo', 'sem' => 'nenhuma falta efetiva, ou acima da margem'][$k]];
        }
        return [
            'avisos' => $semEnc ? ["{$semEnc} oferta(s) aberta(s) sem o total de encontros ficaram de fora (a frequência não pode ser calculada)."] : [],
            'cartoes' => $cartoes, 'detalhes' => $det,
            'tabela' => ['colunas' => $colunas, 'linhas' => $linhas, 'ordem' => [[8, 'asc']], 'rodape' => ['ra' => 'Total', 'nome' => (string) count($linhas)]],
        ];
    }
}
