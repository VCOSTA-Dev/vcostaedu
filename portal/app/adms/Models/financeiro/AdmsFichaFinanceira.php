<?php

namespace App\adms\Models\financeiro;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Pagina nao encontrada!");
}

/**
 * A FICHA FINANCEIRA NO PORTAL (Onda 8, financeiro, Parte 3; D-98): só de leitura, para o aluno e para o responsável
 * financeiro do filho (a barreira é a de sempre: VcAcessoFamilia::podeVerFinanceiro). Os mesmos filtros do painel (deste
 * mês, vencidos, pagos, a vencer); a descrição, o vencimento, o valor e a situação; sem ações e sem os encargos. A
 * situação mostrada é a mesma do painel: o aberto vira "a vencer" ou "vencido" pela data de hoje.
 */
final class AdmsFichaFinanceira
{
    public const FILTROS = ['todos' => 'Todos', 'mes' => 'Deste mês', 'vencidos' => 'Vencidos', 'pagos' => 'Pagos', 'a_vencer' => 'A vencer'];
    public const ROTULOS = ['a_vencer' => 'A vencer', 'vencido' => 'Vencido', 'recebido' => 'Recebido', 'baixado' => 'Baixado', 'cancelado' => 'Cancelado', 'renegociado' => 'Renegociado'];

    /** @return array{titulos: list<array>, contagem: array<string, int>} */
    public static function ficha(int $alunoId, string $filtro = 'todos'): array
    {
        $hoje = date('Y-m-d');
        /* D-99: o recibo válido de cada título recebido (para baixar) */
        $st = \VcBanco::daRequisicao()->prepare("SELECT t.id, t.descricao, t.vencimento, t.valor_centavos, t.situacao, l.recibo_id, r.ano AS recibo_ano,
            (SELECT COALESCE(a.numero, a.id) FROM fin_renegociacoes a WHERE a.id = t.renegociado_por AND t.situacao = 'renegociado') AS acordo_numero FROM fin_titulos t
            LEFT JOIN fin_recibo_titulos l ON l.titulo_id = t.id AND l.situacao = 'valido' LEFT JOIN fin_recibos r ON r.id = l.recibo_id
            WHERE t.aluno_id = ? ORDER BY t.vencimento, t.id");
        $st->execute([$alunoId]);
        $lista = [];
        $cont = array_fill_keys(array_keys(self::FILTROS), 0);
        foreach ($st->fetchAll(\PDO::FETCH_ASSOC) as $t) {
            $t['mostrada'] = $t['situacao'] !== 'aberto' ? $t['situacao'] : ((string) $t['vencimento'] < $hoje ? 'vencido' : 'a_vencer');
            $em = ['todos' => true, 'mes' => substr((string) $t['vencimento'], 0, 7) === substr($hoje, 0, 7), 'vencidos' => $t['mostrada'] === 'vencido',
                'pagos' => $t['situacao'] === 'recebido', 'a_vencer' => $t['mostrada'] === 'a_vencer'];
            foreach ($em as $k => $sim) {
                $cont[$k] += $sim ? 1 : 0;
            }
            if ($em[$filtro] ?? true) {
                $lista[] = $t;
            }
        }
        return ['titulos' => $lista, 'contagem' => $cont];
    }

    /** O resumo do cartão: quantos títulos vencidos (0 = "Em dia"). */
    public static function vencidos(int $alunoId): int
    {
        $st = \VcBanco::daRequisicao()->prepare("SELECT COUNT(*) FROM fin_titulos WHERE aluno_id = ? AND situacao = 'aberto' AND vencimento < CURDATE()");
        $st->execute([$alunoId]);
        return (int) $st->fetchColumn();
    }

    /** O recibo de um título deste aluno (D-99), para baixar; null se não é dele. */
    public static function recibo(int $alunoId, int $reciboId): ?array
    {
        require_once dirname(__DIR__, 5) . '/core/ReciboFinanceiro.php';
        $r = \VcReciboFinanceiro::dados(\VcBanco::daRequisicao(), $reciboId);
        return $r && (int) $r['aluno_id'] === $alunoId ? $r : null;
    }

    /** A página do recibo (a do core, a mesma do painel). */
    public static function mostrarRecibo(array $r, string $voltar): void
    {
        $i = \App\adms\Models\helper\AdmsInstituicao::class;
        \VcReciboFinanceiro::pagina($r, $i::dados() ?: [], \App\adms\Models\helper\AdmsParametro::naEscola() ? 'RM' : 'RA', $i::temLogo() ? $i::logoUrl() : '',
            ['Voltar à ficha financeira', $voltar]);
    }

    /** 123456 → "1.234,56". */
    public static function brl(int $c): string
    {
        return number_format(intdiv($c, 100), 0, ',', '.') . ',' . str_pad((string) ($c % 100), 2, '0', STR_PAD_LEFT);
    }
}
