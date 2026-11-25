<?php

namespace App\adms\Models\disciplinas;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsAdicionarProfessor
{
    private $dados;
    private $dadosClean;
    private $professores;
    private $dadosProf;
    private $logAcao;
    private $logCodigo;
    private $logDescricao;
    private $resultado;

    function getResultado()
    {
        return $this->resultado;
    }

    public function cadProfessor(array $dados)
    {
        $this->dados = $dados;
        $this->professores = $this->dados['professores'];
        $this->limparProfessor();
        \App\adms\Models\moodle\AdmsMoodle::anotar('professor', null, (int) $this->dados['rm_prof_mat_id']); // Onda 3, Etapa 8: o professor da disciplina no Moodle
    }

    private function limparProfessor()
    {
        $this->dadosClean['usuario_mod'] = $_SESSION['colUsuario_id'];
        $this->dadosClean['situacao'] = 2;
        $this->dadosClean['modified'] = date("Y-m-d H:i:s");
        $altProf = new \App\adms\Models\helper\AdmsUpdate();
        $altProf->exeUpdate("rm_professores_mat_liga", $this->dadosClean, "WHERE rm_prof_mat_id =:rm AND acesso=:acesso", "rm={$this->dados['rm_prof_mat_id']}&acesso=1");
        $this->liberaProfessor();
    }

    private function liberaProfessor()
    {
        if(!empty($this->professores)){
            $this->dadosProf['rm_prof_mat_id'] = $this->dados['rm_prof_mat_id'];
            foreach($this->professores as $prof){
                $this->dadosProf['usuario_id'] = $prof;
                $this->checarDuplicidade();
            }
        }else{
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Professores cadastrados na disciplina com sucesso!</div>";
            $this->resultado = true;
        }
    }

    private function checarDuplicidade()
    {
        $infoProfMatLiga = new \App\adms\Models\helper\AdmsRead();
        $infoProfMatLiga->fullRead("SELECT id FROM rm_professores_mat_liga WHERE rm_prof_mat_id =:rm AND usuario_id=:usuario LIMIT :limit", "rm={$this->dadosProf['rm_prof_mat_id']}&usuario={$this->dadosProf['usuario_id']}&limit=1");
        $matliga = $infoProfMatLiga->getResultado();
        if(isset($matliga[0]['id']) && !empty($matliga[0]['id'])){
            $this->dadosProf['id'] = $matliga[0]['id'];
            unset($this->dadosProf['usuario_cre']);
            $this->upProfessor();
        }else{
            unset($this->dadosProf['id'], $this->dadosProf['usuario_mod']);
            $this->addProfessor();
        }
    }

    private function addProfessor()
    {
        $this->dadosProf['usuario_cre'] = $_SESSION['colUsuario_id'];
        $this->dadosProf['situacao'] = 1;
        $this->dadosProf['acesso'] = 1;
        $addProf = new \App\adms\Models\helper\AdmsCreate();
        $addProf->exeCreate("rm_professores_mat_liga", $this->dadosProf);
        if($addProf->getResultado()){
            $this->logCodigo = 200;
            $this->logDescricao = "Professor adicionado na disciplina! rm_prof_mat_id={$this->dadosProf['rm_prof_mat_id']}&usuario={$this->dadosProf['usuario_id']}";
            $this->logAcao = 2;
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Professor adicionado na disciplina!</div>";
            $this->resultado = true;
        }else{
            $this->logCodigo = 400;
            $this->logDescricao = "Erro: Não foi possível adicionar o professor na disciplina! rm_prof_mat_id={$this->dadosProf['rm_prof_mat_id']}&usuario={$this->dadosProf['usuario_id']}";
            $this->logAcao = 2;
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não foi possível adicionar o professor na disciplina!</div>";
            $this->resultado = false;
        }
    }

    private function upProfessor()
    {
        $this->dadosProf['usuario_mod'] = $_SESSION['colUsuario_id'];
        $this->dadosProf['situacao'] = 1;
        $this->dadosProf['acesso'] = 1;
        $altProf = new \App\adms\Models\helper\AdmsUpdate();
        $altProf->exeUpdate("rm_professores_mat_liga", $this->dadosProf, "WHERE id=:id", "id={$this->dadosProf['id']}");
        if($altProf->getResultado()){
            $this->logCodigo = 200;
            $this->logDescricao = "Professor adicionado na disciplina! rm_prof_mat_id={$this->dadosProf['rm_prof_mat_id']}&usuario={$this->dadosProf['usuario_id']}";
            $this->logAcao = 3;
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Professor adicionado na disciplina!</div>";
            $this->resultado = true;
        }else{
            $this->logCodigo = 400;
            $this->logDescricao = "Erro: Não foi possível adicionar o professor na disciplina! rm_prof_mat_id={$this->dadosProf['rm_prof_mat_id']}&usuario={$this->dadosProf['usuario_id']}";
            $this->logAcao = 3;
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não foi possível adicionar o professor na disciplina!</div>";
            $this->resultado = false;
        }
    }

    private function salvarLog()
    {
        $valLogsalvar = new \App\adms\Models\helper\AdmsSalvarLog();
        $valLogsalvar->salvarLogs(104, $this->logCodigo, $this->logAcao, $this->logDescricao);
    }

}