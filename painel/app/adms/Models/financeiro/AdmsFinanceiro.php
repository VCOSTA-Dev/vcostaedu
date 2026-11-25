<?php

namespace App\adms\Models\financeiro;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

require_once dirname(__DIR__, 5) . '/core/ReciboFinanceiro.php'; // o recibo, o mesmo no painel e no portal (D-99)

/**
 * O FINANCEIRO (Onda 8; D-98; docs/FINANCEIRO.md): as contas e as regras num ponto só.
 *   - o dinheiro em CENTAVOS INTEIROS; as taxas em centésimos de ponto percentual (2% = 200);
 *   - os encargos de um título numa data: até o vencimento, sem multa e sem juros, e com o desconto (se houver); depois,
 *     a multa (uma vez) e os juros ao mês pró-rata por dia de atraso (valor × juros ÷ 30 × dias), sem desconto. O
 *     arredondamento é o meio para cima, em centavos. O servidor recalcula sempre ao gravar (não confia na tela);
 *   - a situação mostrada: "a vencer" e "vencido" saem da data do aberto; as outras são as gravadas.
 */
final class AdmsFinanceiro
{
    public const SITUACOES = ['aberto' => 'Aberto', 'recebido' => 'Recebido', 'baixado' => 'Baixado', 'cancelado' => 'Cancelado', 'renegociado' => 'Renegociado', 'prescrito' => 'Prescrito'];
    /**
     * As formas de pagamento (D-99): registradas à mão, sem integração — o dinheiro, o Pix manual e o cartão. A
     * transferência saiu (pedido do responsável: não é mais usada); ela e o "Outro" só existem nos recebimentos da 1.92.0.
     */
    public const FORMAS = ['dinheiro' => 'Dinheiro', 'pix' => 'Pix manual', 'cartao' => 'Cartão', 'transferencia' => 'Transferência (registro antigo)', 'outro' => 'Outro (registro antigo)'];
    public const FORMAS_DO_RECEBER = ['dinheiro', 'pix', 'cartao'];

    public static function pdo(): \PDO
    {
        return \App\adms\Models\escola\AdmsEscolaBanco::pdo();
    }

    /** "1.234,56", "1234.56", "1234" → 123456 (centavos); vazio ou inválido → null. */
    public static function centavos(string $v): ?int
    {
        $v = trim(str_replace(['R$', ' '], '', $v));
        if ($v === '') {
            return null;
        }
        if (preg_match('/^\d{1,3}(\.\d{3})+(,\d{1,2})?$/', $v) || str_contains($v, ',')) {
            $v = str_replace('.', '', $v);
            $v = str_replace(',', '.', $v);
        }
        if (!preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', $v, $m)) {
            return null;
        }
        return (int) $m[1] * 100 + (int) str_pad($m[2] ?? '0', 2, '0');
    }

    /** 123456 → "1.234,56" (sem o "R$"). */
    public static function brl(?int $c): string
    {
        if ($c === null) {
            return '—';
        }
        $neg = $c < 0;
        $c = abs($c);
        return ($neg ? '-' : '') . number_format(intdiv($c, 100), 0, ',', '.') . ',' . str_pad((string) ($c % 100), 2, '0', STR_PAD_LEFT);
    }

    /** "2,5" ou "2.5" (%) → 250 (centésimos de ponto); vazio → 0; inválido → null. */
    public static function pontos(string $v): ?int
    {
        $v = trim(str_replace(['%', ' '], '', $v));
        if ($v === '') {
            return 0;
        }
        $v = str_replace(',', '.', $v);
        if (!preg_match('/^(\d{1,3})(?:\.(\d{1,2}))?$/', $v, $m)) {
            return null;
        }
        return (int) $m[1] * 100 + (int) str_pad($m[2] ?? '0', 2, '0');
    }

    /** 250 → "2,5". */
    public static function pct(int $bp): string
    {
        $s = number_format($bp / 100, 2, ',', '');
        return rtrim(rtrim($s, '0'), ',');
    }

    /** a × b ÷ d, arredondado (o meio para cima), em inteiros. */
    private static function mult(int $a, int $b, int $d): int
    {
        return intdiv(2 * $a * $b + $d, 2 * $d);
    }

    /** As regras da instalação. @return array{multa_bp: int, juros_mes_bp: int, desconto_bp: int} */
    public static function regras(): array
    {
        static $r = null;
        if ($r === null) {
            $q = self::pdo()->query('SELECT multa_bp, juros_mes_bp, desconto_bp FROM fin_regras WHERE id = 1')->fetch(\PDO::FETCH_ASSOC) ?: [];
            $r = ['multa_bp' => (int) ($q['multa_bp'] ?? 200), 'juros_mes_bp' => (int) ($q['juros_mes_bp'] ?? 100), 'desconto_bp' => (int) ($q['desconto_bp'] ?? 0)];
        }
        return $r;
    }

    /**
     * Os encargos do valor que vence em $vencimento, pago em $data (AAAA-MM-DD).
     * @return array{original: int, dias: int, multa: int, juros: int, desconto: int, total: int}
     */
    public static function encargos(int $valor, string $vencimento, string $data, ?array $regras = null): array
    {
        $r = $regras ?? self::regras();
        $dias = (int) (new \DateTimeImmutable($vencimento))->diff(new \DateTimeImmutable($data))->format('%r%a');
        $multa = $juros = $desconto = 0;
        if ($dias > 0) {
            $multa = self::mult($valor, $r['multa_bp'], 10000);
            $juros = self::mult($valor, $r['juros_mes_bp'] * $dias, 30 * 10000);
        } elseif ($r['desconto_bp'] > 0) {
            $desconto = self::mult($valor, $r['desconto_bp'], 10000);
        }
        return ['original' => $valor, 'dias' => max(0, $dias), 'multa' => $multa, 'juros' => $juros, 'desconto' => $desconto, 'total' => $valor + $multa + $juros - $desconto];
    }

    /**
     * Os encargos de um título (a linha de fin_titulos) numa data: a parcela do acordo (sem_multa, D-105) paga os juros e
     * não paga a multa.
     */
    public static function encargosTitulo(array $t, string $data, ?array $regras = null): array
    {
        $r = $regras ?? self::regras();
        if (!empty($t['sem_multa'])) {
            $r['multa_bp'] = 0;
        }
        return self::encargos((int) $t['valor_centavos'], (string) $t['vencimento'], $data, $r);
    }

    /** A situação para mostrar: o aberto vira "a vencer" ou "vencido" pela data de hoje. */
    public static function situacaoMostrada(array $t, ?string $hoje = null): string
    {
        if ($t['situacao'] !== 'aberto') {
            return (string) $t['situacao'];
        }
        return (string) $t['vencimento'] < ($hoje ?? date('Y-m-d')) ? 'vencido' : 'a_vencer';
    }

    public static function rotulo(string $mostrada): string
    {
        return ['a_vencer' => 'A vencer', 'vencido' => 'Vencido'][$mostrada] ?? (self::SITUACOES[$mostrada] ?? $mostrada);
    }

    /** Por extenso (o recibo): o texto é o do core, o mesmo do portal (core/ReciboFinanceiro.php). */
    public static function porExtenso(int $centavos): string
    {
        return \VcReciboFinanceiro::porExtenso($centavos);
    }

    /** O CPF ou o CNPJ formatado (o do core). */
    public static function documentoFormatado(?string $doc): string
    {
        return \VcReciboFinanceiro::documentoFormatado($doc);
    }

    /** O CPF ou o CNPJ com os dígitos verificadores certos. */
    public static function documentoValido(string $doc): bool
    {
        $n = preg_replace('/\D+/', '', $doc);
        if (strlen($n) === 11) {
            if (preg_match('/^(\d)\1{10}$/', $n)) {
                return false;
            }
            for ($t = 9; $t < 11; $t++) {
                $s = 0;
                for ($i = 0; $i < $t; $i++) {
                    $s += (int) $n[$i] * (($t + 1) - $i);
                }
                if ((int) $n[$t] !== ((10 * $s) % 11) % 10) {
                    return false;
                }
            }
            return true;
        }
        if (strlen($n) === 14) {
            if (preg_match('/^(\d)\1{13}$/', $n)) {
                return false;
            }
            foreach ([12, 13] as $t) {
                $pesos = $t === 12 ? [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2] : [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
                $s = 0;
                for ($i = 0; $i < $t; $i++) {
                    $s += (int) $n[$i] * $pesos[$i];
                }
                $r = $s % 11;
                if ((int) $n[$t] !== ($r < 2 ? 0 : 11 - $r)) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    /** O registro de uma operação (o histórico; nada é apagado). */
    public static function registrar(array $d): int
    {
        $c = ['titulo_id', 'aluno_id', 'tipo', 'data_operacao', 'forma', 'original_centavos', 'multa_centavos', 'juros_centavos', 'desconto_centavos', 'recebido_centavos',
            'motivo', 'observacao', 'situacao_antes', 'situacao_depois', 'renegociacao_id', 'recibo_id', 'envio', 'usuario_id'];
        $v = array_map(static fn($k) => $d[$k] ?? null, $c);
        $st = self::pdo()->prepare('INSERT INTO fin_operacoes (' . implode(', ', $c) . ', criado_em) VALUES (' . implode(', ', array_fill(0, count($c), '?')) . ', NOW())');
        $st->execute($v);
        return (int) self::pdo()->lastInsertId();
    }
}
