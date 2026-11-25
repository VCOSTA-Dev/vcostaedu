<?php

namespace App\adms\Models\cursos;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsEditarCurso
{
    private $dados;
    private $dadosId;
    private $logCodigo;
    private $logDescricao;
    private $resultado;

    function getResultado()
    {
        return $this->resultado;
    }

    public function visCurso($dadosId)
    {
        $this->dadosId = (int) $dadosId;
        $verCursoedicao = new \App\adms\Models\helper\AdmsRead();
        $verCursoedicao->fullRead("SELECT * FROM rm_cursos WHERE id=:id LIMIT :limit", "id={$this->dadosId}&limit=1");
        $this->resultado = $verCursoedicao->getResultado();

        return $this->resultado;
    }

    public function editCurso(array $dados)
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
        $this->altCurso();
    }

    private function altCurso()
    {
        $this->dados['usuario_mod'] = $_SESSION['colUsuario_id'];
        $this->dados['modified'] = date("Y-m-d H:i:s");
        $altPg = new \App\adms\Models\helper\AdmsUpdate();
        $altPg->exeUpdate("rm_cursos", $this->dados, "WHERE id=:id", "id={$this->dados['id']}");
        if($altPg->getResultado()){
            $this->logCodigo = 200;
            $this->logDescricao = "Curso editado com sucesso! id={$this->dados['id']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Curso atualizado com sucesso!</div>";
            $this->resultado = true; 
        }else{
            $this->logCodigo = 400;
            $this->logDescricao = "Erro: não foi possível editar o curso! id={$this->dados['id']}";
            $this->salvarLog();
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: não foi possível atualizar o curso!</div>";
            $this->resultado = false;
        }

    }

    private function salvarLog()
    {
        $valLogsalvar = new \App\adms\Models\helper\AdmsSalvarLog();
        $valLogsalvar->salvarLogs(78, $this->logCodigo, 3, $this->logDescricao);
    }

}