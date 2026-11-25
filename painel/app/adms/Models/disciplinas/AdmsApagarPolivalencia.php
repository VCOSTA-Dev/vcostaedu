<?php

namespace App\adms\Models\disciplinas;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsApagarPolivalencia
{
    private $dados;
    private $logCodigo;
    private $logDescricao;
    private $infoDisc;
    private $resultado;

    function getResultado()
    {
        return $this->resultado;
    }

    public function delDiscPolivalente(array $dados)
    {
        $this->dados = $dados;
        $this->verDiscPoli();
        $this->apagarDiscPoli();
    }

    private function verDiscPoli()
    {
        $dadosDiscInfo = new \App\adms\Models\helper\AdmsRead();
        $dadosDiscInfo->fullRead("SELECT id, rm_polivalente, rm_prof_valido FROM rm_professores_materias_polivalencias WHERE id =:linha LIMIT :limit", "linha={$this->dados['id']}&limit=1");
        $this->infoDisc = $dadosDiscInfo->getResultado();
    }

    private function apagarDiscPoli()
    {
        $deleDisc = new \App\adms\Models\helper\AdmsDeletar();
        $deleDisc->exeDelete("rm_professores_materias_polivalencias", "WHERE id=:id", "id={$this->dados['id']}");
        if($deleDisc->getResultado()){
            $this->logCodigo = 200;
            $this->logDescricao = "Disciplinas polivalentes desconectadas com sucesso! id={$this->dados['id']}&discpoli={$this->infoDisc[0]['rm_polivalente']}&discligado={$this->infoDisc[0]['rm_prof_valido']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Disciplinas polivalentes desconectadas com sucesso!</div>";
            $this->resultado = true;
        }else{
            $this->logCodigo = 400;
            $this->logDescricao = "Error: Não foi possível apagar o vínculo das disciplinas! id={$this->dados['id']}&discpoli={$this->infoDisc[0]['rm_polivalente']}&discligado={$this->infoDisc[0]['rm_prof_valido']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>ERRO: Não foi possível apagar o vínculo das disciplinas!</div>";
            $this->resultado = false;
        }
    }

    private function salvarLog()
    {
        $valLogsalvar = new \App\adms\Models\helper\AdmsSalvarLog();
        $valLogsalvar->salvarLogs(150, $this->logCodigo, 4, $this->logDescricao);
    }

}