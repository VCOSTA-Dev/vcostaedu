<?php

namespace App\adms\Models\relatorios;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\relatorios\AdmsRelatorioFonte as F;
use App\adms\Models\financeiro\AdmsFinanceiro as Fin;

/**
 * R-FIN-01 Ficha do caixa (Onda 8, financeiro, Parte 5; D-98; refeita na Parte 1 do financeiro refeito, D-99): o que entrou
 * e o que saiu do caixa de cada pessoa num dia, pelos recibos e pelas formas de pagamento deles. O recibo entra no dia do
 * recebimento, no caixa de quem recebeu; o estorno (Desfazer recebimento) sai no dia em que foi feito, no caixa de quem
 * desfez — o caixa de um dia passado não muda depois.
 */
class RelCaixa extends AdmsRelatorioBase
{
    public const CODIGO = 'R-FIN-01';
    public const NOME = 'Ficha do caixa';
    public const CATEGORIA = 'I';
    public const PERGUNTA = 'Quanto entrou no caixa de cada pessoa num dia, e em que forma?';
    public const SLUG = 'caixa';
    public const QUEM = 'administrador, secretaria';

    public function filtros(): array
    {
        return ['dia' => ['rotulo' => 'Dia', 'tipo' => 'data'],
            'usuario' => ['rotulo' => 'Quem recebeu', 'tipo' => 'select', 'opcoes' => array_column(F::ler("SELECT DISTINCT u.id, u.nome AS rotulo FROM adms_usuarios u
                WHERE u.id IN (SELECT usuario_id FROM fin_recibos) OR u.id IN (SELECT estornado_por FROM fin_recibos WHERE estornado_por IS NOT NULL) ORDER BY u.nome"), 'rotulo', 'id')]];
    }

    public function ficha(): array
    {
        return [
            'Os recibos com a data do recebimento no dia escolhido (sem dia: hoje), uma linha por forma de pagamento, no caixa de quem recebeu.',
            'O estorno (Desfazer recebimento) sai do caixa no dia em que foi feito, no caixa de quem desfez, com o valor negativo: o caixa de um dia que já passou não muda depois.',
            'A baixa e o cancelamento não entram: não há entrada de dinheiro.',
            'Os totais: por forma de pagamento, por pessoa e o total do dia (as entradas menos os estornos); a soma das linhas é o total.',
        ];
    }

    public function dados(array $f): array
    {
        $dia = !empty($f['dia']) ? (string) $f['dia'] : date('Y-m-d');
        $u = !empty($f['usuario']) ? (int) $f['usuario'] : 0;
        $nomes = Fin::FORMAS;
        $linhas = [];
        $porForma = [];
        $porPessoa = [];
        $total = 0;
        $estornos = 0;
        $somar = static function (array &$m, string $k, int $v): void { $m[$k] = ($m[$k] ?? 0) + $v; };
        /* as entradas (o recibo no dia dele) e as saídas (o estorno no dia dele), cada forma uma linha */
        foreach ([['entrada', 'r.data_recebimento = :dia', 'r.usuario_id', 1], ['estorno', 'r.estorno_data = :dia', 'r.estornado_por', -1]] as [$tipo, $quando, $quem, $sinal]) {
            $w = [$quando, 'f.recibo_id = r.id'];
            $p = ['dia' => $dia];
            if ($u) {
                $w[] = "{$quem} = :u";
                $p['u'] = $u;
            }
            foreach (F::ler("SELECT r.id, r.numero, a.nome AS aluno, a.rm, u.nome AS usuario, f.forma, f.valor_centavos,
                    (SELECT GROUP_CONCAT(l.titulo_id ORDER BY l.titulo_id SEPARATOR ', ') FROM fin_recibo_titulos l WHERE l.recibo_id = r.id) AS titulos
                    FROM fin_recibos r JOIN fin_recibo_formas f ON f.recibo_id = r.id LEFT JOIN col_usuarios a ON a.id = r.aluno_id LEFT JOIN adms_usuarios u ON u.id = {$quem}
                   WHERE " . implode(' AND ', $w) . ' ORDER BY u.nome, r.numero, f.ordem', $p) as $r) {
                $v = $sinal * (int) $r['valor_centavos'];
                $total += $v;
                $estornos += $sinal < 0 ? 1 : 0;
                $somar($porForma, (string) $r['forma'], $v);
                $somar($porPessoa, (string) $r['usuario'], $v);
                $linhas[] = ['usuario' => (string) $r['usuario'], 'recibo' => self::cel((string) $r['numero'], ['o' => (int) $r['numero']]),
                    'aluno' => (string) $r['aluno'] . ' (' . $r['rm'] . ')', 'titulo' => (string) $r['titulos'],
                    'forma' => ($nomes[$r['forma']] ?? (string) $r['forma']) . ($sinal < 0 ? ' — estorno' : ''), 'valor' => self::cel(Fin::brl($v), ['o' => $v]),
                    '_classe' => $sinal < 0 ? 'table-warning' : ''];
            }
        }
        $cols = [self::col('usuario', 'Quem recebeu'), self::col('recibo', 'Recibo'), self::col('aluno', 'Aluno'), self::col('titulo', 'Títulos'), self::col('forma', 'Forma'),
            self::col('valor', 'Valor (R$)', ['num' => true])];
        $cartoes = [['rotulo' => 'Total do dia ' . date('d/m/Y', strtotime($dia)), 'valor' => 'R$ ' . Fin::brl($total), 'cor' => 'bg-success',
            'nota' => count($linhas) . ' linha(s)' . ($estornos ? ', ' . $estornos . ' de estorno' : '')]];
        foreach ($nomes as $k => $rot) {
            if (isset($porForma[$k])) {
                $cartoes[] = ['rotulo' => $rot, 'valor' => 'R$ ' . Fin::brl($porForma[$k])];
            }
        }
        foreach ($porPessoa as $nome => $v) {
            $cartoes[] = ['rotulo' => 'Caixa de ' . $nome, 'valor' => 'R$ ' . Fin::brl($v)];
        }
        return ['cartoes' => $cartoes, 'detalhes' => [], 'tabela' => ['colunas' => $cols, 'linhas' => $linhas, 'ordem' => [[0, 'asc']]]];
    }
}
