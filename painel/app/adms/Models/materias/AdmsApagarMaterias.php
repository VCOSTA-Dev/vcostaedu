<?php

namespace App\adms\Models\materias;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsApagarMaterias
{
    private $dados;
    private $logCodigo;
    private $logDescricao;
    private $infoCur;
    private $resultado;

    function getResultado()
    {
        return $this->resultado;
    }

    public function delMateria(array $dados)
    {
        $this->dados = $dados;
        $this->apagarMat();
    }

    private function dadosMat()
    {
        $verInfo = new \App\adms\Models\helper\AdmsRead();
        $verInfo->fullRead("SELECT id, nome FROM rm_materias WHERE id=:id LIMIT :limit", "id={$this->dados['id']}&limit=1");
        $this->infoCur = $verInfo->getResultado();
    }

    private function apagarMat()
    {
        $this->dadosMat();
        /* E8c (D-270): matéria na matriz ou com oferta não é apagada (antes era apagada sem conferir) */
        if ($razao = \App\adms\Models\helper\AdmsUsos::materia((int) $this->dados['id'])) {
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible' id='apagarRecusado'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>" . htmlspecialchars($razao, ENT_QUOTES, 'UTF-8') . '</div>';
            $this->resultado = false;
            return;
        }
        $delUsuario = new \App\adms\Models\helper\AdmsDeletar();
        $delUsuario->exeDelete("rm_materias", "WHERE id=:id ", "id={$this->dados['id']}");
        if($delUsuario->getResultado()){
            $this->logCodigo = 200;
            $this->logDescricao = "Matéria apagada com sucesso! id={$this->dados['id']};nome={$this->infoCur[0]['nome']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Matéria apagada com sucesso!</div>";
            $this->resultado = true;
        }else{
            $this->logCodigo = 400;
            $this->logDescricao = "Erro: não foi possível apagar a matéria! id={$this->dados['id']};nome={$this->infoCur[0]['nome']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: não foi possível apagar a matéria!</div>";
            $this->resultado = false;
        }
    }

    private function salvarLog()
    {
        $valLogsalvar = new \App\adms\Models\helper\AdmsSalvarLog();
        $valLogsalvar->salvarLogs(84, $this->logCodigo, 4, $this->logDescricao);
    }

}