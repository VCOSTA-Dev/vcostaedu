<?php

namespace App\adms\Models\helper;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use Exception;
use PDO;

class AdmsRead extends AdmsConn
{
    private $select;
    private $values = [];
    private $resultado = [];
    private $query;
    private $conn;

    function getResultado()
    {
        return $this->resultado;
    }

    public function exeRead($tabela, $termos = null, $parseString = null)
    {
        if(!empty($parseString)){
            parse_str($parseString, $this->values);
        }
        $this->select = "SELECT * FROM {$tabela} {$termos}";
        $this->exeInstruction();
    }

    public function fullRead($query, $parseString = null)
    {
        $this->select = $query;
        if(!empty($parseString)){
            parse_str($parseString, $this->values);
        }
        $this->exeInstruction();
    }

    private function exeInstruction()
    {
        $this->connection();
        try {
            $this->exeParameter();
            $this->query->execute();
            $this->query->errorInfo();
            // var_dump($this->query->errorInfo());
            $this->resultado = $this->query->fetchAll();
        } catch (Exception $ex) {
            // E5: o erro não aparece na tela, mas vai para o log do servidor (sem valores em violação de unicidade).
            error_log('[vcostaedu][sql] ' . __CLASS__ . ' ' . $ex->getCode() . ' ' . ((string) $ex->getCode() === '23000' ? preg_replace("/'[^']*'/", "'…'", $ex->getMessage()) : $ex->getMessage()));
            $this->resultado = null;
        }
    }

    

    private function connection()
    {
        $this->conn = $this->connect();
        $this->query = $this->conn->prepare($this->select);
        $this->query->setFetchMode(PDO::FETCH_ASSOC);
    }

    private function exeParameter()
    {
        if($this->values){
            foreach($this->values as $link => $value){
                if($link == 'limit' || $link == 'offset'){
                    $value = (int) $value;
                }
                $this->query->bindValue(":{$link}", $value, (is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR));
            }
        }
    }
}

?>