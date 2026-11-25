<?php

namespace App\adms\Models\helper;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/** A conexão do sistema (a mesma de AdmsConn, com o modo de SQL do sistema) para as classes de core/ que pedem um PDO. */
class AdmsConexaoAvulsa extends AdmsConn
{
    public function pdo(): \PDO
    {
        $pdo = $this->connect();
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        return $pdo;
    }
}
