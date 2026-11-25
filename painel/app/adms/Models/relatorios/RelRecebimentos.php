<?php

namespace App\adms\Models\relatorios;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\relatorios\AdmsRelatorioFonte as F;
use App\adms\Models\financeiro\AdmsFinanceiro as Fin;

/**
 * R-FIN-02 Recebimentos por período (Onda 8, financeiro, Parte 5; D-98; refeito na Parte 1 do financeiro refeito, D-99):
 * um recibo por linha, para a auditoria, com os títulos, os encargos, o acréscimo e o desconto, as formas, quem recebeu e
 * os estornados marcados; os totais por forma de pagamento.
 */
class RelRecebimentos extends AdmsRelatorioBase
{
    public const CODIGO = 'R-FIN-02';
    public const NOME = 'Recebimentos por período';
    public const CATEGORIA = 'I';
    public const PERGUNTA = 'O que foi recebido no período, com os encargos, a forma e quem recebeu?';
    public const SLUG = 'recebimentos';
    public const QUEM = 'administrador, secretaria';

    public function filtros(): array
    {
        return ['de' => ['rotulo' => 'Recebidos de', 'tipo' => 'data'], 'ate' => ['rotulo' => 'até', 'tipo' => 'data']];
    }

    public function ficha(): array
    {
        return [
            'Cada recibo pela data do recebimento, no período (sem período: todos): os títulos, o original, os encargos (multa e juros, menos o desconto até o vencimento), o acréscimo, o desconto e o recebido.',
            'Os valores são os gravados no recibo (os encargos calculados pela data, no servidor).',
            'O recibo estornado aparece marcado, com a data e o motivo do desfazer; ele não soma no total recebido.',
            'Os totais por forma de pagamento somam as formas dos recibos que valem. Exporta como os outros relatórios (Excel, CSV, PDF, imprimir).',
        ];
    }

    public function dados(array $f): array
    {
        $w = ['1 = 1'];
        $p = [];
        if (!empty($f['de'])) {
            $w[] = 'r.data_recebimento >= :de';
            $p['de'] = $f['de'];
        }
        if (!empty($f['ate'])) {
            $w[] = 'r.data_recebimento <= :ate';
            $p['ate'] = $f['ate'];
        }
        $linhas = [];
        $total = 0;
        $estornados = 0;
        $porForma = [];
        $formas = [];
        foreach (F::ler('SELECT f.recibo_id, f.forma, f.valor_centavos FROM fin_recibo_formas f JOIN fin_recibos r ON r.id = f.recibo_id WHERE ' . implode(' AND ', $w) . ' ORDER BY f.recibo_id, f.ordem', $p) as $x) {
            $formas[(int) $x['recibo_id']][] = $x;
        }
        foreach (F::ler("SELECT r.*, a.nome AS aluno, a.rm, u.nome AS usuario,
                (SELECT GROUP_CONCAT(l.titulo_id ORDER BY l.titulo_id SEPARATOR ', ') FROM fin_recibo_titulos l WHERE l.recibo_id = r.id) AS titulos
               FROM fin_recibos r LEFT JOIN col_usuarios a ON a.id = r.aluno_id LEFT JOIN adms_usuarios u ON u.id = r.usuario_id
              WHERE " . implode(' AND ', $w) . ' ORDER BY r.data_recebimento, r.numero', $p) as $r) {
            $est = $r['situacao'] === 'estornado';
            $estornados += $est ? 1 : 0;
            $total += $est ? 0 : (int) $r['total_centavos'];
            $fs = $formas[(int) $r['id']] ?? [];
            foreach ($fs as $x) {
                if (!$est) {
                    $porForma[(string) $x['forma']] = ($porForma[(string) $x['forma']] ?? 0) + (int) $x['valor_centavos'];
                }
            }
            $m = static fn(int $v) => self::cel(Fin::brl($v), ['o' => $v]);
            $linhas[] = ['data' => self::cel(date('d/m/Y', strtotime((string) $r['data_recebimento'])), ['o' => (string) $r['data_recebimento']]),
                'recibo' => self::cel((string) $r['numero'], ['o' => (int) $r['numero']]), 'aluno' => (string) $r['aluno'] . ' (' . $r['rm'] . ')', 'titulo' => (string) $r['titulos'],
                'original' => $m((int) $r['original_centavos']), 'encargos' => $m((int) $r['multa_centavos'] + (int) $r['juros_centavos'] - (int) $r['pontualidade_centavos']),
                'acrescimo' => $m((int) $r['acrescimo_centavos']), 'desconto' => $m((int) $r['desconto_centavos']), 'recebido' => $m((int) $r['total_centavos']),
                'forma' => implode(' + ', array_map(static fn($x) => (Fin::FORMAS[$x['forma']] ?? $x['forma']) . (count($fs) > 1 ? ' ' . Fin::brl((int) $x['valor_centavos']) : ''), $fs)),
                'usuario' => (string) $r['usuario'],
                'situacao' => $est ? 'Estornado em ' . date('d/m/Y', strtotime((string) $r['estorno_data'])) . ': ' . $r['estorno_motivo'] : 'Vale', '_classe' => $est ? 'table-warning' : ''];
        }
        $cols = [self::col('data', 'Data'), self::col('recibo', 'Recibo'), self::col('aluno', 'Aluno'), self::col('titulo', 'Títulos'), self::col('original', 'Original (R$)', ['num' => true]),
            self::col('encargos', 'Encargos', ['num' => true]), self::col('acrescimo', 'Acréscimo', ['num' => true]), self::col('desconto', 'Desconto', ['num' => true]),
            self::col('recebido', 'Recebido (R$)', ['num' => true]), self::col('forma', 'Forma'), self::col('usuario', 'Quem recebeu'), self::col('situacao', 'Situação')];
        $cartoes = [['rotulo' => 'Total recebido', 'valor' => 'R$ ' . Fin::brl($total), 'cor' => 'bg-success', 'nota' => (count($linhas) - $estornados) . ' recibo(s) que valem'],
            ['rotulo' => 'Recibos estornados', 'valor' => (string) $estornados, 'nota' => 'marcados na lista']];
        foreach (Fin::FORMAS as $k => $rot) {
            if (isset($porForma[$k])) {
                $cartoes[] = ['rotulo' => $rot, 'valor' => 'R$ ' . Fin::brl($porForma[$k])];
            }
        }
        return ['cartoes' => $cartoes, 'detalhes' => [], 'tabela' => ['colunas' => $cols, 'linhas' => $linhas, 'ordem' => [[0, 'asc']]]];
    }
}
