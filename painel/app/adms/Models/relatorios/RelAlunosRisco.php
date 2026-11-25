<?php

namespace App\adms\Models\relatorios;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\relatorios\AdmsRelatorioFonte as F;

/** R-EVA-01 Alunos em risco, o alerta precoce (docs/RELATORIOS.md, E; D-458). Sem pontuação: os sinais e o porquê. */
class RelAlunosRisco extends AdmsRelatorioBase
{
    public const CODIGO = 'R-EVA-01';
    public const NOME = 'Alunos em risco';
    public const CATEGORIA = 'E';
    public const PERGUNTA = 'Quem precisa de atenção agora, e por quê?';
    public const SLUG = 'alunos-risco';
    public const QUEM = 'administrador, diretoria, secretaria, coordenação';

    public function filtros(): array
    {
        return ['curso', 'polo', 'sinal' => ['rotulo' => 'Sinal', 'tipo' => 'select', 'opcoes' => F::SINAIS]];
    }

    public function ficha(): array
    {
        $p = F::risco();
        return [
            'Só matrículas ATIVAS (Cursando). Não há nota de risco: a lista mostra quais sinais dispararam e por quê. Os limites ficam em Relatórios → Alerta de risco.',
            "Frequência: numa oferta aberta, as faltas que o aluno ainda pode ter (a conta do relatório Abaixo ou perto do mínimo; na oferta por chamada com encontros previstos, sobre os PREVISTOS): dispara se já está ABAIXO do mínimo, ou se tem ao menos 1 falta não compensada E pode ter {$p['faltas_margem']} falta(s) ou menos (risco.faltas_margem). Sem nenhuma falta efetiva, não dispara.",
            "Média parcial abaixo da mínima: numa oferta aberta, a média parcial (a da tela de médias, só das notas lançadas) < a nota mínima da regra da oferta, com ao menos {$p['min_notas']} nota(s) lançada(s) (risco.min_notas).",
            "Atividades sem nota: das atividades do aluno nas ofertas abertas do curso, {$p['sem_nota_pct']}% ou mais sem nota (risco.sem_nota_pct).",
            'Reprovação anterior: ao menos uma disciplina do curso reprovada (resultado gravado no fechamento vigente).',
            'Sem enturmação: matrícula Cursando sem enturmação ativa em nenhuma oferta aberta do curso.',
            'Cada cartão conta as matrículas com aquele sinal (uma matrícula pode ter vários); o total é o de matrículas com ao menos um.',
        ];
    }

    public function dados(array $f): array
    {
        $todos = F::sinaisRisco(array_intersect_key($f, ['curso' => 1, 'polo' => 1, 'turma' => 1]));
        $colunas = [self::col('ra', 'RA'), self::col('nome', 'Nome'), self::col('cpf', 'CPF', ['oculta' => true]), self::col('curso', 'Curso'), self::col('polo', 'Polo'),
            self::col('sinais', 'Sinais (por quê)'), self::col('qtd', 'Sinais', ['num' => true])];
        $det = [];
        foreach (F::SINAIS as $k => $t) {
            $det["sinal_{$k}"] = ['titulo' => $t, 'colunas' => $colunas, 'linhas' => []];
        }
        $linhas = [];
        foreach ($todos as $x) {
            $mc = $x['mc'];
            if (!empty($f['sinal']) && !isset($x['sinais'][$f['sinal']])) {
                continue;
            }
            $txt = [];
            foreach ($x['sinais'] as $k => $porques) {
                $txt[] = F::SINAIS[$k] . ': ' . implode('; ', $porques);
            }
            $l = self::linhaAluno($mc, ['polo' => (string) $mc['polo'], 'sinais' => implode(' | ', $txt), 'qtd' => (string) count($x['sinais'])]);
            $l['nome'] = self::cel((string) $mc['nome'], ['url' => URLADM . 'relatorios/desempenho-aluno?matricula=' . (int) $mc['id']]);
            $linhas[] = $l;
            foreach (array_keys($x['sinais']) as $k) {
                $det["sinal_{$k}"]['linhas'][] = $l;
            }
        }
        $det['todos'] = ['titulo' => 'Matrículas em risco', 'colunas' => $colunas, 'linhas' => $linhas];
        $cartoes = [['rotulo' => 'Matrículas em risco', 'valor' => self::contar($det, 'todos'), 'detalhe' => 'todos', 'cor' => self::contar($det, 'todos') ? 'bg-warning' : 'bg-success', 'nota' => 'com ao menos 1 sinal']];
        foreach (F::SINAIS as $k => $t) {
            $cartoes[] = ['rotulo' => $t, 'valor' => self::contar($det, "sinal_{$k}"), 'detalhe' => "sinal_{$k}"];
        }
        return [
            'cartoes' => $cartoes, 'detalhes' => $det,
            'tabela' => ['colunas' => $colunas, 'linhas' => $linhas, 'ordem' => [[6, 'desc'], [1, 'asc']], 'rodape' => ['ra' => 'Total', 'nome' => (string) count($linhas)]],
        ];
    }
}
