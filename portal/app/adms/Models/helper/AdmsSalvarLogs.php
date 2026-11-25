<?php

namespace App\adms\Models\helper;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsSalvarLogs
{
    private $dados;
    private $resultado;

    function getResultado()
    {
        return $this->resultado;
    }

    public function salvarLogs(array $dados)
    {
        $this->dados = $dados;
        $this->dados['usuario_col'] = (isset($_SESSION['academico_id']) ? $_SESSION['academico_id'] : 0);
        $this->dados['usuario_ip'] = \Core\Seguranca::ipCliente(); // E8 (D-236)
        $addLogs = new \App\adms\Models\helper\AdmsCreate();
        $addLogs->exeCreate("col_logs", $this->dados);
        if($addLogs->getResultado()){
            $this->resultado = true;
        }else{
            $this->resultado = false; 
        }
    }

    public function salvarTentativas(array $dados)
    {
        $this->dados = $dados;
        $this->dados['usuario_ip'] = \Core\Seguranca::ipCliente(); // E8 (D-236)
        $addEstadoCivil = new \App\adms\Models\helper\AdmsCreate();
        $addEstadoCivil->exeCreate("col_tentativas_acesso", $this->dados);
        if($addEstadoCivil->getResultado()){
            $this->resultado = true;
        }else{
            $this->resultado = false; 
        }
    }

}
?>