<?php

namespace Core;

require_once __DIR__ . '/Banco.php'; // Onda 3, Etapa 2: o modo de SQL num ponto só (VcBanco)

/**
 * TRANSAÇÃO (E1b, D-345) — uma conexão PDO ÚNICA para uma operação que grava em mais de uma tabela.
 *
 * Os helpers de banco de cada app (AdmsRead, AdmsCreate, AdmsUpdate, AdmsDeletar) pedem a conexão ao AdmsConn,
 * que abria um PDO novo a cada chamada: não havia como pôr duas gravações na mesma transação (achado 8 do
 * levantamento). Agora, enquanto uma transação está ativa, o AdmsConn do painel e o do portal devolvem ESTE PDO,
 * e tudo o que a operação lê e grava passa por ele — inclusive as leituras, que enxergam o que ainda não foi
 * confirmado.
 *
 * Uso:
 *   $ok = \Core\Transacao::executar(function () {
 *       ... helpers de sempre ...
 *       if ($algoFalhou) { \Core\Transacao::falhar('motivo'); }   // desfaz tudo
 *       return true;
 *   });
 * - Devolve o que a função devolve; `false` também desfaz. Exceção (inclusive a de `falhar`) desfaz e devolve
 *   false; o motivo fica em `ultimoErro()` e no log do servidor.
 * - Dentro da transação o PDO lança exceção a cada erro de SQL; os helpers já a capturam e devolvem resultado
 *   nulo/falso, que a operação confere com `exigir()`.
 * - Chamada dentro de outra transação: junta-se a ela (quem abriu confirma ou desfaz).
 * Arquivo único na raiz (como core/Requisicao.php), usado pelo painel e pelo portal; core/.htaccess nega a web.
 */
final class Transacao
{
    private static ?\PDO $pdo = null;
    private static string $ultimoErro = '';

    /** O PDO da transação ativa, ou null (o AdmsConn então abre a conexão de sempre). */
    public static function pdo(): ?\PDO
    {
        return self::$pdo;
    }

    public static function ativa(): bool
    {
        return self::$pdo !== null;
    }

    public static function ultimoErro(): string
    {
        return self::$ultimoErro;
    }

    /** Roda `$operacao` numa transação: confirma se ela devolver algo diferente de false; senão, desfaz. */
    public static function executar(callable $operacao)
    {
        if (self::$pdo !== null) {
            return $operacao(); // já dentro de uma transação: quem abriu decide
        }
        self::$ultimoErro = '';
        self::$pdo = \VcBanco::daRequisicao(); // Onda 6, Etapa 1: a transação corre na conexão da requisição
        \VcBanco::motivo(); // Onda 3, Etapa 2: começa sem motivo anotado
        try {
            self::$pdo->beginTransaction();
            $r = $operacao();
            if ($r === false) {
                self::$pdo->rollBack();
                /* o motivo do banco (o modo estrito recusou um valor) vale mais que o genérico */
                self::$ultimoErro = \VcBanco::motivo() ?: (self::$ultimoErro ?: 'a operação desistiu');
                return false;
            }
            self::$pdo->commit();
            return $r;
        } catch (\Throwable $e) {
            if (self::$pdo->inTransaction()) {
                self::$pdo->rollBack();
            }
            $motivo = \VcBanco::motivo();
            self::$ultimoErro = $motivo !== '' ? $motivo : ($e instanceof TransacaoFalhou ? $e->getMessage() : 'erro inesperado ao gravar');
            error_log('[vcostaedu][transacao] desfeita: ' . $e->getMessage());
            return false;
        } finally {
            self::$pdo = null;
        }
    }

    /** Desfaz a transação ativa com um motivo (lança; `executar` captura). */
    public static function falhar(string $motivo): void
    {
        throw new TransacaoFalhou($motivo);
    }

    /**
     * Resultado de helper que indica falha desfaz a transação com o motivo. Dentro da transação todo erro de SQL vira
     * exceção, que o helper captura e devolve como null (AdmsCreate, AdmsRead) ou false (AdmsDeletar). O id '0' do
     * AdmsCreate NÃO é falha: é o que o PDO devolve para tabela sem AUTO_INCREMENT. AdmsUpdate devolve false também
     * quando nenhuma linha mudou: confira-o com `=== null`, não com este método.
     */
    public static function exigir($resultado, string $motivo)
    {
        if ($resultado === null || $resultado === false) {
            self::falhar($motivo);
        }
        return $resultado;
    }
}

final class TransacaoFalhou extends \RuntimeException
{
}
