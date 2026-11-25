<?php

namespace App\adms\Models\disciplinas;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsAdicionarPolivalencia
{
    private $dados;
    private $discPolis;
    private $logAcao;
    private $logCodigo;
    private $logDescricao;
    private $resultado;

    function getResultado()
    {
        return $this->resultado;
    }

    public function cadDisciplinas(array $dados)
    {
        $this->dados = $dados;
        $this->discPolis = $this->dados['rm_prof_validos'];
        unset($this->dados['rm_prof_validos']);
        $this->separaDiscPolis();
    }

    private function separaDiscPolis()
    {
        foreach($this->discPolis as $disc){
            $this->dados['rm_prof_valido'] = $disc;
            $this->checarDuplicidade();
        }
    }

    private function checarDuplicidade()
    {
        $infoDuploDisc = new \App\adms\Models\helper\AdmsRead();
        $infoDuploDisc->fullRead("SELECT id FROM rm_professores_materias_polivalencias WHERE rm_polivalente =:rmpol AND rm_prof_valido=:novadisc LIMIT :limit", "rmpol=" . urlencode((string) $this->dados['rm_polivalente']) . "&novadisc=" . urlencode((string) $this->dados['rm_prof_valido']) . "&limit=1");
        $matliga = $infoDuploDisc->getResultado();
        if(isset($matliga[0]['id']) && !empty($matliga[0]['id'])){
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Disciplina Polivalente ligadas com sucesso!</div>";
            $this->resultado = true;
        }else{
            $this->addLigacao();
        }
    }

    private function addLigacao()
    {
        $this->dados['usuario_cre'] = $_SESSION['colUsuario_id'];
        $this->dados['situacao'] = 1;
        $addProf = new \App\adms\Models\helper\AdmsCreate();
        $addProf->exeCreate("rm_professores_materias_polivalencias", $this->dados);
        if($addProf->getResultado()){
            $this->logCodigo = 200;
            $this->logDescricao = "Disciplina Polivalente ligadas com sucesso! rm_polivalente={$this->dados['rm_polivalente']}&disc_valida={$this->dados['rm_prof_valido']}";
            $this->logAcao = 2;
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Disciplina Polivalente ligadas com sucesso!</div>";
            $this->resultado = true;
        }else{
            $this->logCodigo = 400;
            $this->logDescricao = "Erro: Não foi possível vincular disciplina polivalente! rm_polivalente={$this->dados['rm_polivalente']}&disc_valida={$this->dados['rm_prof_valido']}";
            $this->logAcao = 2;
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não foi possível vincular disciplina polivalente!</div>";
            $this->resultado = false;
        }
    }

    private function salvarLog()
    {
        $valLogsalvar = new \App\adms\Models\helper\AdmsSalvarLog();
        $valLogsalvar->salvarLogs(148, $this->logCodigo, $this->logAcao, $this->logDescricao);
    }

}