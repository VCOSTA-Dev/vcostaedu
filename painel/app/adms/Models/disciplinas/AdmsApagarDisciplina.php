<?php

namespace App\adms\Models\disciplinas;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsApagarDisciplina
{
    private $dados;
    private $logCodigo;
    private $logDescricao;
    private $infoEmpresa;
    private $resultado;

    function getResultado()
    {
        return $this->resultado;
    }

    public function delDisciplina(array $dados)
    {
        $this->dados = $dados;
        $this->existeAlunos();
    }

    private function existeAlunos()
    {
        $verItsMenu = new \App\adms\Models\helper\AdmsRead();
        $verItsMenu->fullRead("SELECT id FROM portal_disciplinas_alunos WHERE rm_prof_mat_id =:rmprof", "rmprof={$this->dados['id']}");
        $resultMenu = $verItsMenu->getResultado();
        if(isset($resultMenu[0]['id']) && !empty($resultMenu[0]['id'])){
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>ERRO: Não é possível apagar a Disciplina, pois ele tem alunos na disciplina!</div>";
            $this->resultado = false;
        }else{
            $this->apagarDisc();
        }
    }

    private function dadosEmpresa()
    {
        $verItensMenu = new \App\adms\Models\helper\AdmsRead();
        $verItensMenu->fullRead("SELECT id, materia FROM rm_professores_materias WHERE id=:id LIMIT :limit", "id={$this->dados['id']}&limit=1");
        $this->infoEmpresa = $verItensMenu->getResultado();
    }

    private function apagarDisc()
    {
        $this->dadosEmpresa();
        $deleDisc = new \App\adms\Models\helper\AdmsDeletar();
        $deleDisc->exeDelete("rm_professores_materias", "WHERE id=:id", "id={$this->dados['id']}");
        if($deleDisc->getResultado()){
            $this->logCodigo = 200;
            $this->logDescricao = "Disciplina apagada com sucesso! id={$this->dados['id']}&nome={$this->infoEmpresa[0]['nome']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Disciplina apagada com sucesso!</div>";
            $this->resultado = true;
        }else{
            $this->logCodigo = 400;
            $this->logDescricao = "Error: Não foi possível apagar a disciplina! id={$this->dados['id']}&nome={$this->infoEmpresa[0]['nome']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>ERRO: Não foi possível apagar a disciplina!</div>";
            $this->resultado = false;
        }
    }

    private function salvarLog()
    {
        $valLogsalvar = new \App\adms\Models\helper\AdmsSalvarLog();
        $valLogsalvar->salvarLogs(95, $this->logCodigo, 4, $this->logDescricao);
    }

}