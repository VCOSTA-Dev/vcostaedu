<?php

namespace App\adms\Models\helper;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * O AMBIENTE EFETIVO e o ESTADO DA INSTALAÇÃO — Onda 2, Etapa 4.
 *
 * A demonstração pode ser ligada pela tela (adms_instalacao_estado, chave "demonstracao.ativa"), sem editar a
 * configuração. O ambiente efetivo é "demonstracao" quando ela está ligada (ou quando a Config diz AMBIENTE =
 * 'demonstracao', o jeito antigo das ferramentas); senão, o AMBIENTE da Config. É ele que a emissão de documentos
 * (a tarja), a auditoria e a faixa usam. Antes da migration 0008, só a Config vale.
 */
class AdmsAmbiente extends AdmsConn
{
    private static array $cache = [];

    public static function estado(string $chave, ?string $padrao = null): ?string
    {
        if (array_key_exists($chave, self::$cache)) {
            return self::$cache[$chave] ?? $padrao;
        }
        if (!AdmsAtualizacaoPendente::exige('0008')) {
            return $padrao;
        }
        try {
            $pdo = (new self())->connect();
            $st = $pdo->prepare('SELECT valor FROM adms_instalacao_estado WHERE chave = :c LIMIT 1');
            $st->execute(['c' => $chave]);
            $v = $st->fetchColumn();
            self::$cache[$chave] = $v === false ? null : (string) $v;
        } catch (\Throwable $e) {
            self::$cache[$chave] = null;
        }
        return self::$cache[$chave] ?? $padrao;
    }

    public static function gravarEstado(string $chave, ?string $valor): void
    {
        $pdo = (new self())->connect();
        if ($valor === null) {
            $pdo->prepare('DELETE FROM adms_instalacao_estado WHERE chave = :c')->execute(['c' => $chave]);
        } else {
            $pdo->prepare('INSERT INTO adms_instalacao_estado (chave, valor) VALUES (:c, :v) ON DUPLICATE KEY UPDATE valor = VALUES(valor)')->execute(['c' => $chave, 'v' => $valor]);
        }
        unset(self::$cache[$chave]);
    }

    public static function demonstracaoAtiva(): bool
    {
        if (defined('AMBIENTE') && (string) AMBIENTE === 'demonstracao') {
            return true;
        }
        return self::estado('demonstracao.ativa') === '1';
    }

    /** producao | homologacao | demonstracao */
    public static function atual(): string
    {
        return self::demonstracaoAtiva() ? 'demonstracao' : (defined('AMBIENTE') ? (string) AMBIENTE : 'producao');
    }
}
