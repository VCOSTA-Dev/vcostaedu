<?php

namespace App\adms\Models\helper;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use Exception;
use PDO;

class AdmsUpdate extends AdmsConn
{
    private $table;
    private $termos;
    private $data;
    private $values = [];
    private $resultado;
    private $update;
    private $query;
    private $conn;

    function getResultado()
    {
        return $this->resultado;
    }

    public function exeUpdate($table, array $data, $termos= null, $parseString = null)
    {
        $this->table = (string) $table;
        $this->data = $data;
        $this->termos = (string) $termos;
        parse_str($parseString, $this->values);

        $this->exeReplaceValues();
        $this->exeIntruction();
    }

    private function exeReplaceValues() {
        foreach ($this->data as $key => $value) {
            $values[] = $key . '=:' . $key;
        }
        $values = implode(', ', $values);

        $this->query = "UPDATE {$this->table} SET {$values} {$this->termos}";
    }

    private function exeIntruction() {
        $this->connection();
        try {
            $this->update->execute(array_merge($this->data, $this->values));
            if ($this->update->rowCount() >= 1) {
                $this->resultado = true;
            } else {
                $this->resultado = false;
            }
        } catch (Exception $ex) {
            // E5: o erro não aparece na tela, mas vai para o log do servidor (sem valores em violação de unicidade).
            error_log('[vcostaedu][sql] ' . __CLASS__ . ' ' . $ex->getCode() . ' ' . ((string) $ex->getCode() === '23000' ? preg_replace("/'[^']*'/", "'…'", $ex->getMessage()) : $ex->getMessage()));
            if (class_exists('VcBanco', false)) {
                \VcBanco::anotar($ex); // Onda 3, Etapa 2: o motivo da recusa do modo estrito, para a mensagem da tela
            }
            $this->resultado = null;
        }
    }

    private function connection() {
        $this->conn = $this->connect();
        $this->update = $this->conn->prepare($this->query);
    }
}
?>