<?php

namespace App\adms\Models\helper;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use Exception;
use PDO;

class AdmsDeletar extends AdmsConn
{
    private $table;
    private $termos;
    private $values = [];
    private $resultado;
    private $deletar;
    private $query;
    private $conn;

    function getResultado()
    {
        return $this->resultado;
    }

    public function exeDelete($table, $termos= null, $parseString = null)
    {
        $this->table = (string) $table;
        $this->termos = (string) $termos;
        parse_str($parseString, $this->values);

        $this->exeIntruction();
    }

    private function exeIntruction() {
        $this->query = "DELETE FROM {$this->table} {$this->termos}";
        $this->connection();
        try {
            $this->deletar->execute($this->values);
            $this->resultado = true;
        } catch (Exception $ex) {
            // E5: o erro não aparece na tela, mas vai para o log do servidor (sem valores em violação de unicidade).
            error_log('[vcostaedu][sql] ' . __CLASS__ . ' ' . $ex->getCode() . ' ' . ((string) $ex->getCode() === '23000' ? preg_replace("/'[^']*'/", "'…'", $ex->getMessage()) : $ex->getMessage()));
            $this->resultado = false;
        }
    }

    private function connection() {
        $this->conn = $this->connect();
        $this->deletar = $this->conn->prepare($this->query);
    }
}
?>