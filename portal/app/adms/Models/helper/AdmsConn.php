<?php

namespace App\adms\Models\helper;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use Exception;
use PDO;

require_once dirname(__DIR__, 5) . "/core/Transacao.php";

abstract class AdmsConn
{
    private $host = HOST;
    private $user = USER;
    private $pass = PASS;
    private $dbName = DBNAME;
    private $connect;

    protected function connect()
    {
        /* E1b (D-345): dentro de \Core\Transacao::executar(), todos os helpers usam a MESMA conexão (a da transação). */
        if ($pdo = \Core\Transacao::pdo()) {
            return $this->connect = $pdo;
        }
        try {
            $this->connect = \VcBanco::daRequisicao(); // Onda 6, Etapa 1: uma conexão por requisição (core/Banco.php)
            return $this->connect;
        } catch (Exception $ex) {
            die('ERROR: Por Favor tente novamente.');
        }
    }

}