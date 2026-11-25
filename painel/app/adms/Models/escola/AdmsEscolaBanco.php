<?php

namespace App\adms\Models\escola;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/** A conexão para os models da escola: a da transação ativa (\Core\Transacao), ou uma por requisição. */
class AdmsEscolaBanco extends \App\adms\Models\helper\AdmsConn
{
    private static ?\PDO $pdo = null;

    public static function pdo(): \PDO
    {
        if ($t = \Core\Transacao::pdo()) {
            return $t;
        }
        return self::$pdo ??= (new self())->connect();
    }
}
