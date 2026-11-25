<?php

namespace App\adms\Models\cursos;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsApagarCurso
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

    public function delCurso(array $dados)
    {
        $this->dados = $dados;
        $this->apagarCur();
    }

    private function dadosMenu()
    {
        $verInfo = new \App\adms\Models\helper\AdmsRead();
        $verInfo->fullRead("SELECT id, nome FROM rm_cursos WHERE id=:id LIMIT :limit", "id={$this->dados['id']}&limit=1");
        $this->infoCur = $verInfo->getResultado();
    }

    private function apagarCur()
    {
        $this->dadosMenu();
        /* E8c (D-270): curso com grade, matrícula ou oferta não é apagado (antes era apagado sem conferir) */
        if ($razao = \App\adms\Models\helper\AdmsUsos::curso((int) $this->dados['id'])) {
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible' id='apagarRecusado'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>" . htmlspecialchars($razao, ENT_QUOTES, 'UTF-8') . '</div>';
            $this->resultado = false;
            return;
        }
        $delUsuario = new \App\adms\Models\helper\AdmsDeletar();
        $delUsuario->exeDelete("rm_cursos", "WHERE id=:id ", "id={$this->dados['id']}");
        if($delUsuario->getResultado()){
            $this->logCodigo = 200;
            $this->logDescricao = "Curso apagado com sucesso! id={$this->dados['id']};nome={$this->infoCur[0]['nome']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Curso apagado com sucesso!</div>";
            $this->resultado = true;
        }else{
            $this->logCodigo = 400;
            $this->logDescricao = "Error: Não foi possível apagar o Curso! id={$this->dados['id']};nome={$this->infoCur[0]['nome']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>ERRO: Não foi possível apagar o curso!</div>";
            $this->resultado = false;
        }
    }

    private function salvarLog()
    {
        $valLogsalvar = new \App\adms\Models\helper\AdmsSalvarLog();
        $valLogsalvar->salvarLogs(79, $this->logCodigo, 4, $this->logDescricao);
    }

}