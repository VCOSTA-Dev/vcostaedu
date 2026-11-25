<?php

namespace App\adms\Models\relatorios;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\relatorios\AdmsRelatorioFonte as Fonte;

/**
 * O CÁLCULO DOS RELATÓRIOS EM PASSOS (Onda 6, Etapa 4; docs/OPERACOES_LONGAS.md). Os relatórios calculam as notas pelas
 * ofertas (AdmsRelatorioFonte::parcial e ::provisorio, a fachada AdmsCalculoNotas). Com muitas ofertas, o relatório não
 * calcula tudo numa página só:
 *   1. preparar(): o relatório roda uma vez "a seco" — as ofertas que ele pede anotadas, nenhuma calculada (a seco, o
 *      cálculo de cada uma vem vazio, como o de uma oferta que não existe) — e, acima do limite, nasce uma execução;
 *   2. passo(): a janela de progresso calcula as ofertas anotadas, algumas por passo (até o orçamento de tempo), e guarda
 *      cada cálculo em adms_relatorio_calculos, exatamente como veio (serialize);
 *   3. carregar(): a página do relatório, aberta com ?calc=<execução>, monta o mesmo relatório com os cálculos guardados
 *      (os mesmos números da execução de uma vez; o que não estiver guardado é calculado na hora).
 * Uma execução vale uma hora e só para quem a começou. O limite (quantas ofertas de uma vez) é o parâmetro
 * relatorios.calculo_de_uma_vez (padrão 20).
 */
class AdmsRelatorioCalculo
{
    public const ORCAMENTO = 4.0;
    /** Onda 8 (D-77): quanto a própria página calcula antes de abrir a janela de passos (segundos). */
    public const DIRETO = 6.0;
    public const LIMITE_PADRAO = 20;
    public const VALIDADE_MINUTOS = 60;

    private static function chave(string $execucao): string
    {
        return 'relatorio.calc.' . $execucao;
    }

    public static function disponivel(): bool
    {
        return \App\adms\Models\helper\AdmsAtualizacaoPendente::exige('0033');
    }

    public static function limite(): int
    {
        return max(0, (int) \App\adms\Models\helper\AdmsParametro::ler('relatorios.calculo_de_uma_vez', (string) self::LIMITE_PADRAO));
    }

    private static function pdo(): \PDO
    {
        return \Core\Transacao::pdo() ?: \VcBanco::daRequisicao();
    }

    public static function estado(string $execucao): ?array
    {
        if (!preg_match('/^[0-9a-f]{32}$/', $execucao)) {
            return null;
        }
        $v = \App\adms\Models\helper\AdmsAmbiente::estado(self::chave($execucao));
        $e = $v ? json_decode($v, true) : null;
        return is_array($e) && (int) ($e['usuario'] ?? 0) === (int) ($_SESSION['colUsuario_id'] ?? -1) ? $e : null;
    }

    private static function gravar(string $execucao, ?array $e): void
    {
        \App\adms\Models\helper\AdmsAmbiente::gravarEstado(self::chave($execucao), $e === null ? null : json_encode($e, JSON_UNESCAPED_UNICODE));
    }

    /**
     * O relatório a seco: as ofertas que ele calcularia. Acima do limite, cria a execução e devolve o id dela; até o
     * limite (ou sem a tabela, numa instalação com a atualização pendente), null — o relatório sai de uma vez.
     */
    public static function preparar(string $codigo, callable $dados): ?string
    {
        if (!self::disponivel()) {
            return null;
        }
        $chaves = Fonte::coletar($dados);
        $ofertas = count(array_unique(array_map(fn($k) => substr($k, 2), $chaves)));
        if ($ofertas <= self::limite()) {
            return null;
        }
        /* Onda 8, correção, Parte 3.2 (D-77): antes da janela de passos, a própria página calcula, até um orçamento curto
           (DIRETO): na instalação pequena, tudo cabe e o relatório abre de uma vez, sem a janela (cada cálculo fica na
           memória da fachada, e o relatório é montado com eles); na grande, o que já foi calculado fica guardado e a janela
           faz o resto. Com o limite 0 (o parâmetro), a janela é sempre usada, como antes. */
        $feitos = [];
        if (self::limite() > 0) {
            $t0 = microtime(true);
            foreach ($chaves as $k) {
                if (microtime(true) - $t0 >= self::DIRETO) {
                    break;
                }
                $feitos[(string) $k] = Fonte::calcular((string) $k);
            }
            if (count($feitos) === count($chaves)) {
                return null;
            }
        }
        self::limpar();
        $execucao = bin2hex(random_bytes(16));
        if ($feitos) {
            $ins = self::pdo()->prepare('INSERT INTO adms_relatorio_calculos (execucao, chave, dados, criado) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE dados = VALUES(dados)');
            foreach ($feitos as $k => $v) {
                $ins->execute([$execucao, $k, serialize($v)]);
            }
        }
        self::gravar($execucao, ['usuario' => (int) $_SESSION['colUsuario_id'], 'relatorio' => $codigo, 'ofertas' => $ofertas,
            'total' => count($chaves), 'pendentes' => array_values(array_diff(array_map('strval', $chaves), array_keys($feitos))), 'feitas' => count($feitos), 'em' => date('Y-m-d H:i:s')]);
        return $execucao;
    }

    /** Um passo: algumas ofertas calculadas e guardadas, até o orçamento de tempo. */
    public static function passo(string $execucao): array
    {
        $e = self::estado($execucao);
        if (!$e) {
            return ['erro' => 'O cálculo deste relatório não foi encontrado (ele vale uma hora). Abra o relatório de novo.'];
        }
        $pdo = self::pdo();
        if ((int) $pdo->query("SELECT GET_LOCK('vcostaedu_rel_{$execucao}', 0)")->fetchColumn() !== 1) {
            return ['ocupado' => true];
        }
        try {
            $e = self::estado($execucao);
            $t0 = microtime(true);
            $ins = $pdo->prepare('INSERT INTO adms_relatorio_calculos (execucao, chave, dados, criado) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE dados = VALUES(dados)');
            while ($e['pendentes'] && microtime(true) - $t0 < self::ORCAMENTO) {
                $k = (string) array_shift($e['pendentes']);
                $ins->execute([$execucao, $k, serialize(Fonte::calcular($k))]);
                $e['feitas']++;
            }
            self::gravar($execucao, $e);
            return self::publico($e);
        } finally {
            $pdo->query("SELECT RELEASE_LOCK('vcostaedu_rel_{$execucao}')");
        }
    }

    /** A página do relatório com ?calc=: os cálculos guardados entram na Fonte. @return string|null a hora do cálculo */
    public static function carregar(string $execucao): ?string
    {
        if (!self::disponivel() || !($e = self::estado($execucao))) {
            return null;
        }
        $st = self::pdo()->prepare('SELECT chave, dados FROM adms_relatorio_calculos WHERE execucao = ?');
        $st->execute([$execucao]);
        $valores = [];
        foreach ($st->fetchAll(\PDO::FETCH_ASSOC) as $r) {
            $v = @unserialize((string) $r['dados'], ['allowed_classes' => false]);
            if (is_array($v)) {
                $valores[(string) $r['chave']] = $v;
            }
        }
        Fonte::carregar($valores);
        return (string) $e['em'];
    }

    /** O estado para a janela de progresso. */
    public static function publico(array $e): array
    {
        $total = max(1, (int) $e['total']);
        $feitas = (int) $e['feitas'];
        return ['passo' => $feitas, 'total' => $total, 'concluida' => !$e['pendentes'],
            'grupoAtual' => $e['pendentes'] ? 'cálculo ' . min($feitas + 1, $total) . ' de ' . $total : 'Pronto'];
    }

    /** As execuções de mais de uma hora saem (as linhas e o estado). */
    private static function limpar(): void
    {
        $pdo = self::pdo();
        $velhas = $pdo->query('SELECT DISTINCT execucao FROM adms_relatorio_calculos WHERE criado < NOW() - INTERVAL ' . self::VALIDADE_MINUTOS . ' MINUTE')->fetchAll(\PDO::FETCH_COLUMN);
        foreach ($velhas as $x) {
            $pdo->prepare('DELETE FROM adms_relatorio_calculos WHERE execucao = ?')->execute([$x]);
            \App\adms\Models\helper\AdmsAmbiente::gravarEstado(self::chave((string) $x), null);
        }
        $pdo->exec("DELETE FROM adms_instalacao_estado WHERE chave LIKE 'relatorio.calc.%' AND modified < NOW() - INTERVAL " . self::VALIDADE_MINUTOS . ' MINUTE');
    }
}
