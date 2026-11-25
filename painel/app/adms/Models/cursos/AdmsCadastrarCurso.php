<?php

namespace App\adms\Models\cursos;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsCadastrarCurso
{
    private $dados;
    private $ordem;
    private $logCodigo;
    private $logDescricao;
    private $resultado;

    function getResultado()
    {
        return $this->resultado;
    }

    public function cadCurso(array $dados)
    {
        $this->dados = $dados;
        $this->dados['exige_tcc'] = (int) ($dados['exige_tcc'] ?? 0) === 1 ? 1 : 0; // E6a
        /* E8d (P-2): turno da lista; no EaD a tela o esconde e ele não se aplica (fica 1, a coluna é NOT NULL) */
        $this->dados['periodo'] = array_key_exists((int) ($dados['periodo'] ?? 0), \App\adms\Models\modalidades\AdmsModalidades::turnos()) ? (int) $dados['periodo'] : 1;
        /* E1a (D-340): nível, sigla, nome por extenso e linha do cabeçalho — conferidos antes de gravar */
        if ($erros = AdmsIdentidadeCurso::normalizar($this->dados)) {
            $_SESSION['msg'] = AdmsIdentidadeCurso::mensagemErro($erros);
            $this->resultado = false;
            return;
        }
        $this->addCurso();

    }

    private function addCurso()
    {
        $this->dados['usuario_cre'] = $_SESSION['colUsuario_id'];
        $this->dados['situacao'] = 1;
        $addItMenu = new \App\adms\Models\helper\AdmsCreate();
        $addItMenu->exeCreate("rm_cursos", $this->dados);
        if($addItMenu->getResultado()){
            $identificador = $addItMenu->getResultado();
            $this->logCodigo = 200;
            $this->logDescricao = "Curso cadastrado com sucesso! id={$identificador}&nome={$this->dados['nome']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Curso criado!</div>";
            $this->resultado = true; 
        }else{
            $this->logCodigo = 400;
            $this->logDescricao = "Curso cadastrado com sucesso! nome={$this->dados['nome']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: Não foi possível criar o Curso!</div>";
            $this->resultado = false; 
        }
    }

    public function listCadastro()
    {
        $listPosicoes = new \App\adms\Models\helper\AdmsRead();
        $listPosicoes->fullRead("SELECT id, nome FROM rm_cursos_periodos WHERE situacao=:sit", "sit=1");
        $this->resultado['periodos'] = $listPosicoes->getResultado();

        return $this->resultado;
    }

    private function salvarLog()
    {
        $valLogsalvar = new \App\adms\Models\helper\AdmsSalvarLog();
        $valLogsalvar->salvarLogs(76, $this->logCodigo, 2, $this->logDescricao);
    }

}