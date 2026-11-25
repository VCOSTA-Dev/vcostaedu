<?php

namespace App\adms\Models\helper;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * HÁ ATUALIZAÇÃO DO BANCO PENDENTE? (Onda 2, Etapa 1)
 *
 * Os arquivos do sistema podem chegar antes das migrations deles (o código novo copiado sobre uma instalação em
 * uso). Nesse intervalo o sistema continua de pé: a página nova responde com o aviso de "atualização pendente", e
 * o código novo que depende de tabela nova pergunta aqui antes de usá-la (exige()), em vez de cair num erro do banco.
 *
 * Compara os arquivos de database/migrations/ com a tabela adms_migrations — só os nomes, sem ler o conteúdo
 * (quem confere o hash é o runner). As migrations que exigem privilégio (os gatilhos, "-- vcostaedu:exige-privilegio")
 * não contam: sem o privilégio elas ficam adiadas de propósito, e o sistema funciona sem elas.
 */
class AdmsAtualizacaoPendente extends AdmsConn
{
    private static ?array $pendentes = null;

    /** @return list<string> os arquivos pendentes, em ordem */
    public static function pendentes(): array
    {
        if (self::$pendentes !== null) {
            return self::$pendentes;
        }
        $dir = dirname(__DIR__, 5) . '/database/migrations';
        $noDisco = [];
        foreach (glob($dir . '/*.sql') ?: [] as $caminho) {
            $arquivo = basename($caminho);
            if (!preg_match('/^(\d+)_/', $arquivo, $m)) {
                continue;
            }
            $fh = @fopen($caminho, 'r');
            $primeira = $fh ? (string) fgets($fh) : '';
            if ($fh) {
                fclose($fh);
            }
            if (preg_match('/^--\s*vcostaedu:exige-privilegio\b/', $primeira)) {
                continue;
            }
            $noDisco[$m[1]] = $arquivo;
        }
        ksort($noDisco, SORT_NATURAL);
        $aplicadas = [];
        try {
            $pdo = (new self())->connect();
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            foreach ($pdo->query('SELECT versao FROM adms_migrations')->fetchAll(\PDO::FETCH_COLUMN) as $v) {
                $aplicadas[(string) $v] = true;
            }
        } catch (\Throwable $e) {
            error_log('[vcostaedu][atualizacao] não foi possível ler adms_migrations');
        }

        return self::$pendentes = array_values(array_diff_key($noDisco, $aplicadas));
    }

    /** Esquece a conta guardada (depois de aplicar, na mesma requisição: a faixa e a tela do resultado medem de novo). */
    public static function esquecer(): void
    {
        self::$pendentes = null;
    }

    public static function haPendente(): bool
    {
        return self::pendentes() !== [];
    }

    /** A migration desta versão (o prefixo do arquivo, ex.: "0004") já foi aplicada? */
    public static function exige(string $versao): bool
    {
        foreach (self::pendentes() as $arquivo) {
            if (str_starts_with($arquivo, $versao . '_')) {
                return false;
            }
        }

        return true;
    }

    /** A página pedida existe no banco? (Sem olhar a permissão: só para separar "nova, ainda sem migration" de "sem acesso".) */
    public static function paginaExiste(string $controller, string $metodo): bool
    {
        $tabela = 'adms_paginas';
        $ler = new AdmsRead();
        $ler->fullRead("SELECT id FROM {$tabela} WHERE controller =:c AND metodo =:m LIMIT :limit", "c={$controller}&m={$metodo}&limit=1");

        return !empty($ler->getResultado());
    }

    /** A tela do aviso (HTTP 503, sem erro do PHP), no layout de quem pediu. */
    public static function responderPendente(): void
    {
        if (!headers_sent()) {
            http_response_code(503);
            header('Retry-After: 3600');
        }
        $view = new \Core\ConfigView('adms/Views/atualizacao/pendente', ['pendentes' => self::pendentes()]);
        $view->renderizar();
    }
}
