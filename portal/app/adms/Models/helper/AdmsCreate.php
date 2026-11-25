<?php

namespace App\adms\Models\helper;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use Exception;
use PDO;

class AdmsCreate extends AdmsConn
{
    private $table;
    private $data;
    private $resultado;
    private $insert;
    private $query;
    private $conn;

    function getResultado()
    {
        return $this->resultado;
    }

    public function exeCreate($table, array $data)
    {
        $this->table = (string) $table;
        $this->data = $data;
        $this->exeReplaceValues();
        $this->exeInstruction();
    }

    private function exeReplaceValues()
    {
        $coluns = implode(', ', array_keys($this->data));
        $values = ':' . implode(', :', array_keys($this->data));
        $this->query = "INSERT INTO {$this->table} ({$coluns}) VALUES ({$values})";
    }

    private function exeInstruction()
    {
        $this->connection();
        try {
            $this->insert->execute($this->data);
            $this->resultado = $this->conn->lastInsertId();
        } catch (Exception $ex) {
            // E5: o erro não aparece na tela, mas vai para o log do servidor (sem valores em violação de unicidade).
            error_log('[vcostaedu][sql] ' . __CLASS__ . ' ' . $ex->getCode() . ' ' . ((string) $ex->getCode() === '23000' ? preg_replace("/'[^']*'/", "'…'", $ex->getMessage()) : $ex->getMessage()));
            if (class_exists('VcBanco', false)) {
                \VcBanco::anotar($ex); // Onda 3, Etapa 2: o motivo da recusa do modo estrito, para a mensagem da tela
            }
            $this->resultado = null;
        }
    }

    private function connection()
    {
        $this->conn = $this->connect();
        $this->insert = $this->conn->prepare($this->query);
    }

}

?>