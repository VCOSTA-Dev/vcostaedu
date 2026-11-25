<?php

namespace App\adms\Models\disciplinas;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsCadastrarDisciplina
{
    private $dados;
    private $professores;
    private $dadosProf;
    private $idDisciplina;
    private $logCodigo;
    private $logDescricao;
    private $resultado;

    function getResultado()
    {
        return $this->resultado;
    }

    public function cadDisc(array $dados)
    {
        $this->professores = $dados['professores'] ?? [];
        /* E3b: curso, grade, módulo (periodo), matéria (codmat) e CH vêm da LINHA da
           matriz (`matriz_id`); a grade é a da linha escolhida na tela (sai a D-88). */
        $oferta = AdmsOfertaMatriz::montar($dados);
        if (is_string($oferta)) {
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: " . htmlspecialchars($oferta, ENT_QUOTES, 'UTF-8') . "</div>";
            $this->resultado = false;
            return;
        }
        if (AdmsOfertaMatriz::duplicada($oferta['ano'], $oferta['matriz_id'], $oferta['codtur'])) {
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: já existe oferta desta disciplina da matriz neste ano e nesta turma!</div>";
            $this->resultado = false;
            return;
        }
        $this->dados = $oferta + [
            'bimestre' => 3 /* E4: etapa única (3 = Anual; 3 = Somente Atividades), inerte no cálculo */,
            'tipo' => 3 /* E4: etapa única (3 = Anual; 3 = Somente Atividades), inerte no cálculo */,
        ];
        $this->addDisciplina();
    }
    private function addDisciplina()
    {
        $this->dados['usuario_cre'] = $_SESSION['colUsuario_id'];
        $this->dados['situacao'] = 1;
        $addDisc = new \App\adms\Models\helper\AdmsCreate();
        $addDisc->exeCreate("rm_professores_materias", $this->dados);
        if($addDisc->getResultado()){
            $rm = $addDisc->getResultado();
            $this->logCodigo = 200;
            $this->logDescricao = "Disciplina cadastrado com sucesso! id={$rm}&codmat={$this->dados['codmat']}&materia={$this->dados['materia']}";
            $this->salvarLog();
            if(!empty($this->professores)){
                $this->dadosProf['rm_prof_mat_id'] = $rm;
                $this->liberaProfessor();
            }else{
                $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Disciplina criada com sucesso!</div>";
                $this->resultado = true; 
            }
        }else{
            $this->logCodigo = 400;
            $this->logDescricao = "Erro: Não foi possível adicionar a a disciplina! codmat={$this->dados['codmat']}&materia={$this->dados['materia']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não foi possível criar o Disciplina!</div>";
            $this->resultado = false; 
        }
    }

    private function liberaProfessor()
    {
        foreach($this->professores as $prof){
            $this->dadosProf['usuario_id'] = $prof;
            $this->addProfessor();
        }
        if (!empty($this->dadosProf['rm_prof_mat_id'])) {
            \App\adms\Models\moodle\AdmsMoodle::anotar('professor', null, (int) $this->dadosProf['rm_prof_mat_id']); // Onda 3, Etapa 8
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
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Disciplina cadastrada com sucesso!</div>";
            $this->resultado = true; 
        }else{
            $this->logCodigo = 400;
            $this->logDescricao = "Erro: Não foi possível adicionar o professor na disciplina! rm_prof_mat_id={$this->dadosProf['rm_prof_mat_id']}&usuario={$this->dadosProf['usuario_id']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Disciplina cadastrada com sucesso!</div>";
            $this->resultado = true;
        }
    }

    private function salvarLog()
    {
        $valLogsalvar = new \App\adms\Models\helper\AdmsSalvarLog();
        $valLogsalvar->salvarLogs(92, $this->logCodigo, 2, $this->logDescricao);
    }

    public function listarCadastro()
    {
        $listCursos = new \App\adms\Models\helper\AdmsRead();
        $listCursos->fullRead("SELECT id, nome FROM rm_cursos WHERE situacao=:sit ORDER BY id asc", "sit=1");
        $this->resultado['cursos'] = $listCursos->getResultado();

        $listDisc = new \App\adms\Models\helper\AdmsRead();
        $listDisc->fullRead("SELECT id, nome FROM rm_materias WHERE situacao=:sit ORDER BY nome asc", "sit=1");
        $this->resultado['materias'] = $listDisc->getResultado();

        $listProf = new \App\adms\Models\helper\AdmsRead();
        $listProf->fullRead("SELECT id, nome FROM adms_usuarios WHERE adms_sits_usuario_id=:sit ORDER BY nome asc", "sit=1");
        $this->resultado['usuarios'] = $listProf->getResultado();

        return $this->resultado;
    }

}